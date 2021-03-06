<?php

/**
 * Copyright 2015-2019 info@neomerx.com
 * Modification Copyright 2021-2022 info@whoaphp.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare (strict_types=1);

namespace Whoa\Flute\Api;

use ArrayObject;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException as UcvException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Generator;
use Whoa\Container\Traits\HasContainerTrait;
use Whoa\Contracts\Data\ModelSchemaInfoInterface;
use Whoa\Contracts\Data\RelationshipTypes;
use Whoa\Contracts\L10n\FormatterFactoryInterface;
use Whoa\Flute\Adapters\ModelQueryBuilder;
use Whoa\Flute\Contracts\Api\CrudInterface;
use Whoa\Flute\Contracts\Api\RelationshipPaginationStrategyInterface;
use Whoa\Flute\Contracts\FactoryInterface;
use Whoa\Flute\Contracts\Http\Query\FilterParameterInterface;
use Whoa\Flute\Contracts\Models\ModelStorageInterface;
use Whoa\Flute\Contracts\Models\PaginatedDataInterface;
use Whoa\Flute\Contracts\Models\TagStorageInterface;
use Whoa\Flute\Exceptions\InvalidArgumentException;
use Whoa\Flute\L10n\Messages;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Traversable;

use function array_key_exists;
use function asort;
use function assert;
use function call_user_func;
use function get_class;
use function is_array;
use function is_int;
use function is_string;
use function iterator_to_array;

/**
 * @package Whoa\Flute
 */
class Crud implements CrudInterface
{
    use HasContainerTrait;

    /** Internal constant. Path constant. */
    protected const ROOT_PATH = '';

    /** Internal constant. Path constant. */
    protected const PATH_SEPARATOR = DocumentInterface::PATH_SEPARATOR;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var string
     */
    private string $modelClass;

    /**
     * @var ModelSchemaInfoInterface
     */
    private $modelSchemas;

    /**
     * @var RelationshipPaginationStrategyInterface
     */
    private $relPagingStrategy;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var iterable|null
     */
    private ?iterable $filterParameters = null;

    /**
     * @var bool
     */
    private bool $areFiltersWithAnd = true;

    /**
     * @var iterable|null
     */
    private ?iterable $sortingParameters = null;

    /**
     * @var array
     */
    private array $relFiltersAndSorts = [];

    /**
     * @var iterable|null
     */
    private ?iterable $includePaths = null;

    /**
     * @var int|null
     */
    private ?int $pagingOffset = null;

    /**
     * @var Closure|null
     */
    private ?Closure $columnMapper = null;

    /**
     * @var bool
     */
    private bool $isFetchTyped;

    /**
     * @var int|null
     */
    private ?int $pagingLimit = null;

    /** internal constant */
    private const REL_FILTERS_AND_SORTS__FILTERS = 0;

    /** internal constant */
    private const REL_FILTERS_AND_SORTS__SORTS = 1;

    /**
     * @param ContainerInterface $container
     * @param string $modelClass
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container, string $modelClass)
    {
        $this->setContainer($container);

        $this->modelClass = $modelClass;
        $this->factory = $this->getContainer()->get(FactoryInterface::class);
        $this->modelSchemas = $this->getContainer()->get(ModelSchemaInfoInterface::class);
        $this->relPagingStrategy = $this->getContainer()->get(RelationshipPaginationStrategyInterface::class);
        $this->connection = $this->getContainer()->get(Connection::class);

        $this->clearBuilderParameters()->clearFetchParameters();
    }

    /**
     * @param Closure $mapper
     * @return self
     */
    public function withColumnMapper(Closure $mapper): self
    {
        $this->columnMapper = $mapper;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withFilters(iterable $filterParameters): CrudInterface
    {
        $this->filterParameters = $filterParameters;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withIndexFilter(string $index): CrudInterface
    {
        $pkName = $this->getModelSchemas()->getPrimaryKey($this->getModelClass());
        $this->withFilters([
            $pkName => [
                FilterParameterInterface::OPERATION_EQUALS => [$index],
            ],
        ]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withIndexesFilter(array $indexes): CrudInterface
    {
        if (empty($indexes) === true) {
            throw new InvalidArgumentException($this->getMessage(Messages::MSG_ERR_INVALID_ARGUMENT));
        }

        assert(
            call_user_func(function () use ($indexes) {
                $allOk = true;

                foreach ($indexes as $index) {
                    $allOk = ($allOk === true && (is_string($index) === true || is_int($index) === true));
                }

                return $allOk;
            }) === true
        );

        $pkName = $this->getModelSchemas()->getPrimaryKey($this->getModelClass());
        $this->withFilters([
            $pkName => [
                FilterParameterInterface::OPERATION_IN => $indexes,
            ],
        ]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withRelationshipFilters(string $name, iterable $filters): CrudInterface
    {
        assert($this->getModelSchemas()->hasRelationship($this->getModelClass(), $name) === true);

        $this->relFiltersAndSorts[$name][self::REL_FILTERS_AND_SORTS__FILTERS] = $filters;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withRelationshipSorts(string $name, iterable $sorts): CrudInterface
    {
        assert($this->getModelSchemas()->hasRelationship($this->getModelClass(), $name) === true);

        $this->relFiltersAndSorts[$name][self::REL_FILTERS_AND_SORTS__SORTS] = $sorts;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function combineWithAnd(): CrudInterface
    {
        $this->areFiltersWithAnd = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function combineWithOr(): CrudInterface
    {
        $this->areFiltersWithAnd = false;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasColumnMapper(): bool
    {
        return $this->columnMapper !== null;
    }

    /**
     * @return Closure
     */
    private function getColumnMapper(): Closure
    {
        return $this->columnMapper;
    }

    /**
     * @return bool
     */
    private function hasFilters(): bool
    {
        return empty($this->filterParameters) === false;
    }

    /**
     * @return iterable
     */
    private function getFilters(): iterable
    {
        return $this->filterParameters;
    }

    /**
     * @return bool
     */
    private function areFiltersWithAnd(): bool
    {
        return $this->areFiltersWithAnd;
    }

    /**
     * @inheritdoc
     */
    public function withSorts(iterable $sortingParameters): CrudInterface
    {
        $this->sortingParameters = $sortingParameters;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasSorts(): bool
    {
        return empty($this->sortingParameters) === false;
    }

    /**
     * @return iterable
     */
    private function getSorts(): ?iterable
    {
        return $this->sortingParameters;
    }

    /**
     * @inheritdoc
     */
    public function withIncludes(iterable $includePaths): CrudInterface
    {
        $this->includePaths = $includePaths;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasIncludes(): bool
    {
        return empty($this->includePaths) === false;
    }

    /**
     * @return iterable
     */
    private function getIncludes(): iterable
    {
        return $this->includePaths;
    }

    /**
     * @inheritdoc
     */
    public function withPaging(int $offset, int $limit): CrudInterface
    {
        $this->pagingOffset = $offset;
        $this->pagingLimit = $limit;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function withoutPaging(): CrudInterface
    {
        $this->pagingOffset = null;
        $this->pagingLimit = null;

        return $this;
    }

    /**
     * @return self
     */
    public function shouldBeTyped(): self
    {
        $this->isFetchTyped = true;

        return $this;
    }

    /**
     * @return self
     */
    public function shouldBeUntyped(): self
    {
        $this->isFetchTyped = false;

        return $this;
    }

    /**
     * @return bool
     */
    private function hasPaging(): bool
    {
        return $this->pagingOffset !== null && $this->pagingLimit !== null;
    }

    /**
     * @return int
     */
    private function getPagingOffset(): int
    {
        return $this->pagingOffset;
    }

    /**
     * @return int
     */
    private function getPagingLimit(): int
    {
        return $this->pagingLimit;
    }

    /**
     * @return bool
     */
    private function isFetchTyped(): bool
    {
        return $this->isFetchTyped;
    }

    /**
     * @return Connection
     */
    protected function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param string $modelClass
     * @return ModelQueryBuilder
     */
    protected function createBuilder(string $modelClass): ModelQueryBuilder
    {
        return $this->createBuilderFromConnection($this->getConnection(), $modelClass);
    }

    /**
     * @param Connection $connection
     * @param string $modelClass
     * @return ModelQueryBuilder
     */
    private function createBuilderFromConnection(Connection $connection, string $modelClass): ModelQueryBuilder
    {
        return $this->getFactory()->createModelQueryBuilder($connection, $modelClass, $this->getModelSchemas());
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return Crud
     */
    protected function applyColumnMapper(ModelQueryBuilder $builder): self
    {
        if ($this->hasColumnMapper() === true) {
            $builder->setColumnToDatabaseMapper($this->getColumnMapper());
        }

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return Crud
     * @throws DBALException
     */
    protected function applyAliasFilters(ModelQueryBuilder $builder): self
    {
        if ($this->hasFilters() === true) {
            $filters = $this->getFilters();
            $this->areFiltersWithAnd() === true ?
                $builder->addFiltersWithAndToAlias($filters) : $builder->addFiltersWithOrToAlias($filters);
        }

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return self
     * @throws DBALException
     */
    protected function applyTableFilters(ModelQueryBuilder $builder): self
    {
        if ($this->hasFilters() === true) {
            $filters = $this->getFilters();
            $this->areFiltersWithAnd() === true ?
                $builder->addFiltersWithAndToTable($filters) : $builder->addFiltersWithOrToTable($filters);
        }

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return self
     * @throws DBALException
     */
    protected function applyRelationshipFiltersAndSorts(ModelQueryBuilder $builder): self
    {
        // While joining tables we select distinct rows. This flag used to apply `distinct` no more than once.
        $distinctApplied = false;

        foreach ($this->relFiltersAndSorts as $relationshipName => $filtersAndSorts) {
            assert(is_string($relationshipName) === true && is_array($filtersAndSorts) === true);
            $builder->addRelationshipFiltersAndSorts(
                $relationshipName,
                $filtersAndSorts[self::REL_FILTERS_AND_SORTS__FILTERS] ?? [],
                $filtersAndSorts[self::REL_FILTERS_AND_SORTS__SORTS] ?? []
            );

            if ($distinctApplied === false) {
                $builder->distinct();
                $distinctApplied = true;
            }
        }

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return self
     * @throws DBALException
     */
    protected function applySorts(ModelQueryBuilder $builder): self
    {
        if ($this->hasSorts() === true) {
            $builder->addSorts($this->getSorts());
        }

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return self
     */
    protected function applyPaging(ModelQueryBuilder $builder): self
    {
        if ($this->hasPaging() === true) {
            $builder->setFirstResult($this->getPagingOffset());
            $builder->setMaxResults($this->getPagingLimit() + 1);
        }

        return $this;
    }

    /**
     * @return self
     */
    protected function clearBuilderParameters(): self
    {
        $this->columnMapper = null;
        $this->filterParameters = null;
        $this->areFiltersWithAnd = true;
        $this->sortingParameters = null;
        $this->pagingOffset = null;
        $this->pagingLimit = null;
        $this->relFiltersAndSorts = [];

        return $this;
    }

    /**
     * @return self
     */
    private function clearFetchParameters(): self
    {
        $this->includePaths = null;
        $this->shouldBeTyped();

        return $this;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderOnCount(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderOnIndex(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderOnReadRelationship(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderSaveResourceOnCreate(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderSaveResourceOnUpdate(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param string $relationshipName
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderSaveRelationshipOnCreate(
        string $relationshipName,
        ModelQueryBuilder $builder
    ): ModelQueryBuilder {
        return $builder;
    }

    /**
     * @param string $relationshipName
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderSaveRelationshipOnUpdate(
        string $relationshipName,
        ModelQueryBuilder $builder
    ): ModelQueryBuilder {
        return $builder;
    }

    /**
     * @param string $relationshipName
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderOnCreateInBelongsToManyRelationship(
        string $relationshipName,
        ModelQueryBuilder $builder
    ): ModelQueryBuilder {
        return $builder;
    }

    /**
     * @param string $relationshipName
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderOnRemoveInBelongsToManyRelationship(
        string $relationshipName,
        ModelQueryBuilder $builder
    ): ModelQueryBuilder {
        return $builder;
    }

    /**
     * @param string $relationshipName
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderCleanRelationshipOnUpdate(
        string $relationshipName,
        ModelQueryBuilder $builder
    ): ModelQueryBuilder {
        return $builder;
    }

    /**
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function builderOnDelete(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        return $builder;
    }

    /**
     * @param PaginatedDataInterface|mixed|null $data
     * @return void
     * @throws DBALException
     */
    private function loadRelationships($data): void
    {
        $isPaginated = $data instanceof PaginatedDataInterface;
        $hasData = ($isPaginated === true && empty($data->getData()) === false) ||
            ($isPaginated === false && $data !== null);

        if ($hasData === true && $this->hasIncludes() === true) {
            $modelStorage = $this->getFactory()->createModelStorage($this->getModelSchemas());
            $modelsAtPath = $this->getFactory()->createTagStorage();

            // we're going to send these objects via function params so it is an equivalent for &array
            $classAtPath = new ArrayObject();
            $idsAtPath = new ArrayObject();

            $registerModelAtRoot = function ($model) use ($modelStorage, $modelsAtPath, $idsAtPath): void {
                self::registerModelAtPath(
                    $model,
                    static::ROOT_PATH,
                    $this->getModelSchemas(),
                    $modelStorage,
                    $modelsAtPath,
                    $idsAtPath
                );
            };

            $model = null;
            if ($isPaginated === true) {
                foreach ($data->getData() as $model) {
                    $registerModelAtRoot($model);
                }
            } else {
                $model = $data;
                $registerModelAtRoot($model);
            }
            assert($model !== null);
            $classAtPath[static::ROOT_PATH] = get_class($model);

            foreach ($this->getPaths($this->getIncludes()) as list ($parentPath, $childPaths)) {
                $this->loadRelationshipsLayer(
                    $modelsAtPath,
                    $classAtPath,
                    $idsAtPath,
                    $modelStorage,
                    $parentPath,
                    $childPaths
                );
            }
        }
    }

    /**
     * A helper to remember all model related data. Helps to ensure we consistently handle models in CRUD.
     * @param mixed $model
     * @param string $path
     * @param ModelSchemaInfoInterface $modelSchemas
     * @param ModelStorageInterface $modelStorage
     * @param TagStorageInterface $modelsAtPath
     * @param ArrayObject $idsAtPath
     * @return mixed
     */
    private static function registerModelAtPath(
        $model,
        string $path,
        ModelSchemaInfoInterface $modelSchemas,
        ModelStorageInterface $modelStorage,
        TagStorageInterface $modelsAtPath,
        ArrayObject $idsAtPath
    ) {
        $uniqueModel = $modelStorage->register($model);
        if ($uniqueModel !== null) {
            $modelsAtPath->register($uniqueModel, $path);
            $pkName = $modelSchemas->getPrimaryKey(get_class($uniqueModel));
            $modelId = $uniqueModel->{$pkName};
            $idsAtPath[$path][] = $modelId;
        }

        return $uniqueModel;
    }

    /**
     * @param iterable $paths (string[])
     * @return iterable
     */
    private static function getPaths(iterable $paths): iterable
    {
        // The idea is to normalize paths. It means build all intermediate paths.
        // e.g. if only `a.b.c` path it has given it will be normalized to `a`, `a.b` and `a.b.c`.
        // Path depths store depth of each path (e.g. 0 for root, 1 for `a`, 2 for `a.b` etc).
        // It is needed for yielding them in correct order (from top level to bottom).
        $normalizedPaths = [];
        $pathsDepths = [];
        foreach ($paths as $path) {
            assert(is_array($path) || $path instanceof Traversable);
            $parentDepth = 0;
            $tmpPath = static::ROOT_PATH;
            foreach ($path as $pathPiece) {
                assert(is_string($pathPiece));
                $parent = $tmpPath;
                $tmpPath = empty($tmpPath) === true ?
                    $pathPiece : $tmpPath . static::PATH_SEPARATOR . $pathPiece;
                $normalizedPaths[$tmpPath] = [$parent, $pathPiece];
                $pathsDepths[$parent] = $parentDepth++;
            }
        }

        // Here we collect paths in form of parent => [list of children]
        // e.g. '' => ['a', 'c', 'b'], 'b' => ['bb', 'aa'] and etc
        $parentWithChildren = [];
        foreach ($normalizedPaths as $path => list ($parent, $childPath)) {
            $parentWithChildren[$parent][] = $childPath;
        }

        // And finally sort by path depth and yield parent with its children. Top level paths first then deeper ones.
        asort($pathsDepths, SORT_NUMERIC);
        foreach ($pathsDepths as $parent => $depth) {
            assert($depth !== null); // suppress unused
            $childPaths = $parentWithChildren[$parent];
            yield [$parent, $childPaths];
        }
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function createIndexBuilder(iterable $columns = null): QueryBuilder
    {
        return $this->createIndexModelBuilder($columns);
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function createDeleteBuilder(): QueryBuilder
    {
        return $this->createDeleteModelBuilder();
    }

    /**
     * @param iterable|null $columns
     * @return ModelQueryBuilder
     * @throws DBALException
     */
    protected function createIndexModelBuilder(iterable $columns = null): ModelQueryBuilder
    {
        $builder = $this->createBuilder($this->getModelClass());

        $this
            ->applyColumnMapper($builder);

        $builder
            ->selectModelColumns($columns)
            ->fromModelTable();

        $this
            ->applyAliasFilters($builder)
            ->applySorts($builder)
            ->applyRelationshipFiltersAndSorts($builder)
            ->applyPaging($builder);

        $result = $this->builderOnIndex($builder);

        $this->clearBuilderParameters();

        return $result;
    }

    /**
     * @return ModelQueryBuilder
     * @throws DBALException
     */
    protected function createDeleteModelBuilder(): ModelQueryBuilder
    {
        $builder = $this
            ->createBuilder($this->getModelClass())
            ->deleteModels();

        $this->applyTableFilters($builder);

        $result = $this->builderOnDelete($builder);

        $this->clearBuilderParameters();

        return $result;
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function index(): PaginatedDataInterface
    {
        $builder = $this->createIndexModelBuilder();
        return $this->fetchResources($builder, $builder->getModelClass());
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function indexIdentities(): array
    {
        $pkName = $this->getModelSchemas()->getPrimaryKey($this->getModelClass());
        $builder = $this->createIndexModelBuilder([$pkName]);
        /** @var Generator $data */
        $data = $this->fetchColumn($builder, $builder->getModelClass(), $pkName);
        return iterator_to_array($data);
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function read(string $index)
    {
        $this->withIndexFilter($index);

        $builder = $this->createIndexModelBuilder();
        return $this->fetchResource($builder, $builder->getModelClass());
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function count(): ?int
    {
        $result = $this->builderOnCount(
            $this->createCountBuilderFromBuilder($this->createIndexModelBuilder())
        )->execute()->fetchColumn();

        return $result === false ? null : (int)$result;
    }

    /**
     * @param string $relationshipName
     * @param iterable|null $relationshipFilters
     * @param iterable|null $relationshipSorts
     * @param iterable|null $columns
     * @return ModelQueryBuilder
     * @throws DBALException
     */
    public function createReadRelationshipBuilder(
        string $relationshipName,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null,
        iterable $columns = null
    ): ModelQueryBuilder {
        assert(
            $this->getModelSchemas()->hasRelationship($this->getModelClass(), $relationshipName),
            "Relationship `$relationshipName` do not exist in model `" . $this->getModelClass() . '`'
        );

        // as we read data from a relationship our main table and model would be the table/model in the relationship
        // so 'root' model(s) will be located in the reverse relationship.

        list (
            $targetModelClass, $reverseRelName
            ) =
            $this->getModelSchemas()->getReverseRelationship($this->getModelClass(), $relationshipName);

        $builder = $this
            ->createBuilder($targetModelClass)
            ->selectModelColumns($columns)
            ->fromModelTable();

        // 'root' filters would be applied to the data in the reverse relationship ...
        if ($this->hasFilters() === true) {
            $filters = $this->getFilters();
            $sorts = $this->getSorts();
            $joinCondition = $this->areFiltersWithAnd() === true ? ModelQueryBuilder::AND : ModelQueryBuilder::OR;
            $builder->addRelationshipFiltersAndSorts($reverseRelName, $filters, $sorts, $joinCondition);
        }
        // ... and the input filters to actual data we select
        if ($relationshipFilters !== null) {
            $builder->addFiltersWithAndToAlias($relationshipFilters);
        }
        if ($relationshipSorts !== null) {
            $builder->addSorts($relationshipSorts);
        }

        $this->applyPaging($builder);

        // While joining tables we select distinct rows.
        $builder->distinct();

        return $this->builderOnReadRelationship($builder);
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function indexRelationship(
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ) {
        assert(
            $this->getModelSchemas()->hasRelationship($this->getModelClass(), $name),
            "Relationship `$name` do not exist in model `" . $this->getModelClass() . '`'
        );

        // depending on the relationship type we expect the result to be either single resource or a collection
        $relationshipType = $this->getModelSchemas()->getRelationshipType($this->getModelClass(), $name);
        $isExpectMany = $relationshipType === RelationshipTypes::HAS_MANY ||
            $relationshipType === RelationshipTypes::BELONGS_TO_MANY;

        $builder = $this->createReadRelationshipBuilder($name, $relationshipFilters, $relationshipSorts);

        $modelClass = $builder->getModelClass();
        return $isExpectMany === true ?
            $this->fetchResources($builder, $modelClass) : $this->fetchResource($builder, $modelClass);
    }

    /**
     * @inheritdoc
     * @param string $name
     * @param iterable|null $relationshipFilters
     * @param iterable|null $relationshipSorts
     * @return array
     * @throws ContainerExceptionInterface
     * @throws DBALException
     * @throws NotFoundExceptionInterface
     */
    public function indexRelationshipIdentities(
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ): array {
        assert(
            $this->getModelSchemas()->hasRelationship($this->getModelClass(), $name),
            "Relationship `$name` do not exist in model `" . $this->getModelClass() . '`'
        );

        // depending on the relationship type we expect the result to be either single resource or a collection
        $relationshipType = $this->getModelSchemas()->getRelationshipType($this->getModelClass(), $name);
        $isExpectMany = $relationshipType === RelationshipTypes::HAS_MANY ||
            $relationshipType === RelationshipTypes::BELONGS_TO_MANY;
        if ($isExpectMany === false) {
            throw new InvalidArgumentException($this->getMessage(Messages::MSG_ERR_INVALID_ARGUMENT));
        }

        list ($targetModelClass) = $this->getModelSchemas()->getReverseRelationship($this->getModelClass(), $name);
        $targetPk = $this->getModelSchemas()->getPrimaryKey($targetModelClass);

        $builder = $this->createReadRelationshipBuilder($name, $relationshipFilters, $relationshipSorts, [$targetPk]);

        $modelClass = $builder->getModelClass();
        /** @var Generator $data */
        $data = $this->fetchColumn($builder, $modelClass, $targetPk);
        return iterator_to_array($data);
    }

    /**
     * @inheritdoc
     */
    public function readRelationship(
        string $index,
        string $name,
        iterable $relationshipFilters = null,
        iterable $relationshipSorts = null
    ) {
        return $this->withIndexFilter($index)->indexRelationship($name, $relationshipFilters, $relationshipSorts);
    }

    /**
     * @inheritdoc
     */
    public function hasInRelationship(string $parentId, string $name, string $childId): bool
    {
        $parentPkName = $this->getModelSchemas()->getPrimaryKey($this->getModelClass());
        $parentFilters = [$parentPkName => [FilterParameterInterface::OPERATION_EQUALS => [$parentId]]];
        list($childClass) = $this->getModelSchemas()->getReverseRelationship($this->getModelClass(), $name);
        $childPkName = $this->getModelSchemas()->getPrimaryKey($childClass);
        $childFilters = [$childPkName => [FilterParameterInterface::OPERATION_EQUALS => [$childId]]];

        $data = $this
            ->clearBuilderParameters()
            ->clearFetchParameters()
            ->withFilters($parentFilters)
            ->indexRelationship($name, $childFilters);

        return empty($data->getData()) === false;
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function delete(): int
    {
        $deleted = $this->createDeleteBuilder()->execute();

        $this->clearFetchParameters();

        return (int)$deleted;
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function remove(string $index): bool
    {
        $this->withIndexFilter($index);

        $deleted = $this->createDeleteBuilder()->execute();

        $this->clearFetchParameters();

        return (int)$deleted > 0;
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function create(?string $index, array $attributes, array $toMany): string
    {
        $allowedChanges = $this->filterAttributesOnCreate($index, $attributes);
        $saveMain = $this
            ->createBuilder($this->getModelClass())
            ->createModel($allowedChanges);
        $saveMain = $this->builderSaveResourceOnCreate($saveMain);
        $saveMain->getSQL(); // prepare

        $this->clearBuilderParameters()->clearFetchParameters();

        $this->inTransaction(function () use ($saveMain, $toMany, &$index) {
            $saveMain->execute();

            // if no index given will use last insert ID as index
            $connection = $saveMain->getConnection();
            $index !== null ?: $index = $connection->lastInsertId();

            $builderHook = Closure::fromCallable([$this, 'builderSaveRelationshipOnCreate']);
            foreach ($toMany as $relationshipName => $secondaryIds) {
                $this->addInToManyRelationship($connection, $index, $relationshipName, $secondaryIds, $builderHook);
            }
        });

        return $index;
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function update(string $index, array $attributes, array $toMany): int
    {
        $updated = 0;
        $pkName = $this->getModelSchemas()->getPrimaryKey($this->getModelClass());
        $filters = [
            $pkName => [
                FilterParameterInterface::OPERATION_EQUALS => [$index],
            ],
        ];
        $allowedChanges = $this->filterAttributesOnUpdate($attributes);
        $saveMain = $this
            ->createBuilder($this->getModelClass())
            ->updateModels($allowedChanges)
            ->addFiltersWithAndToTable($filters);
        $saveMain = $this->builderSaveResourceOnUpdate($saveMain);
        $saveMain->getSQL(); // prepare

        $this->clearBuilderParameters()->clearFetchParameters();

        $this->inTransaction(function () use ($saveMain, $toMany, $index, &$updated) {
            $updated = $saveMain->execute();

            $builderHook = Closure::fromCallable([$this, 'builderSaveRelationshipOnUpdate']);
            foreach ($toMany as $relationshipName => $secondaryIds) {
                $connection = $saveMain->getConnection();

                // clear existing
                $this->builderCleanRelationshipOnUpdate(
                    $relationshipName,
                    $this
                        ->createBuilderFromConnection($this->getConnection(), $this->getModelClass())
                        ->clearToManyRelationship($relationshipName, $index)
                )->execute();

                // add new ones
                $updated += $this->addInToManyRelationship(
                    $connection,
                    $index,
                    $relationshipName,
                    $secondaryIds,
                    $builderHook
                );
            }
        });

        return (int)$updated;
    }

    /**
     * @param string $parentId
     * @param string $name
     * @param iterable $childIds
     * @return int
     * @throws DBALException
     */
    public function createInBelongsToManyRelationship(string $parentId, string $name, iterable $childIds): int
    {
        // Check that relationship is `BelongsToMany`
        assert(
            call_user_func(function () use ($name): bool {
                $relType = $this->getModelSchemas()->getRelationshipType($this->getModelClass(), $name);
                $errMsg = "Relationship `$name` of class `" . $this->getModelClass() .
                    '` either is not `belongsToMany` or do not exist in the class.';
                $isOk = $relType === RelationshipTypes::BELONGS_TO_MANY;

                assert($isOk, $errMsg);

                return $isOk;
            })
        );

        $builderHook = Closure::fromCallable([$this, 'builderOnCreateInBelongsToManyRelationship']);

        return $this->addInToManyRelationship($this->getConnection(), $parentId, $name, $childIds, $builderHook);
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function removeInBelongsToManyRelationship(string $parentId, string $name, iterable $childIds): int
    {
        // Check that relationship is `BelongsToMany`
        assert(
            call_user_func(function () use ($name): bool {
                $relType = $this->getModelSchemas()->getRelationshipType($this->getModelClass(), $name);
                $errMsg = "Relationship `$name` of class `" . $this->getModelClass() .
                    '` either is not `belongsToMany` or do not exist in the class.';
                $isOk = $relType === RelationshipTypes::BELONGS_TO_MANY;

                assert($isOk, $errMsg);

                return $isOk;
            })
        );

        return $this->removeInToManyRelationship($this->getConnection(), $parentId, $name, $childIds);
    }

    /**
     * @return FactoryInterface
     */
    protected function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    /**
     * @return string
     */
    protected function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * @return ModelSchemaInfoInterface
     */
    protected function getModelSchemas(): ModelSchemaInfoInterface
    {
        return $this->modelSchemas;
    }

    /**
     * @return RelationshipPaginationStrategyInterface
     */
    protected function getRelationshipPagingStrategy(): RelationshipPaginationStrategyInterface
    {
        return $this->relPagingStrategy;
    }

    /**
     * @param Closure $closure
     * @return void
     * @throws DBALException
     */
    public function inTransaction(Closure $closure): void
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();
        try {
            $isOk = ($closure() === false ? null : true);
        } finally {
            isset($isOk) === true ? $connection->commit() : $connection->rollBack();
        }
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function fetchResources(QueryBuilder $builder, string $modelClass): PaginatedDataInterface
    {
        $data = $this->fetchPaginatedResourcesWithoutRelationships($builder, $modelClass);

        if ($this->hasIncludes() === true) {
            $this->loadRelationships($data);
            $this->clearFetchParameters();
        }

        return $data;
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function fetchResource(QueryBuilder $builder, string $modelClass)
    {
        $data = $this->fetchResourceWithoutRelationships($builder, $modelClass);

        if ($this->hasIncludes() === true) {
            $this->loadRelationships($data);
            $this->clearFetchParameters();
        }

        return $data;
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function fetchRow(QueryBuilder $builder, string $modelClass): ?array
    {
        $model = null;

        $statement = $builder->execute();
        $statement->setFetchMode(PDOConnection::FETCH_ASSOC);

        if (($attributes = $statement->fetch()) !== false) {
            if ($this->isFetchTyped() === true) {
                $platform = $builder->getConnection()->getDatabasePlatform();
                $typeNames = $this->getModelSchemas()->getAttributeTypes($modelClass);
                $model = $this->readRowFromAssoc($attributes, $typeNames, $platform);
            } else {
                $model = $attributes;
            }
        }

        $this->clearFetchParameters();

        return $model;
    }

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public function fetchColumn(QueryBuilder $builder, string $modelClass, string $columnName): iterable
    {
        $statement = $builder->execute();
        $statement->setFetchMode(PDOConnection::FETCH_ASSOC);

        if ($this->isFetchTyped() === true) {
            $platform = $builder->getConnection()->getDatabasePlatform();
            $typeName = $this->getModelSchemas()->getAttributeTypes($modelClass)[$columnName];
            $type = Type::getType($typeName);
            while (($attributes = $statement->fetch()) !== false) {
                $value = $attributes[$columnName];
                yield $type->convertToPHPValue($value, $platform);
            }
        } else {
            while (($attributes = $statement->fetch()) !== false) {
                yield $attributes[$columnName];
            }
        }

        $this->clearFetchParameters();
    }

    /**
     * @param QueryBuilder $builder
     * @return ModelQueryBuilder
     */
    protected function createCountBuilderFromBuilder(QueryBuilder $builder): ModelQueryBuilder
    {
        $countBuilder = $this->createBuilder($this->getModelClass());
        $countBuilder->setParameters($builder->getParameters());
        $countBuilder->select('COUNT(*)')->from('(' . $builder->getSQL() . ') AS RESULT');

        return $countBuilder;
    }

    /**
     * @param Connection $connection
     * @param string $primaryIdentity
     * @param string $name
     * @param iterable $secondaryIdentities
     * @param Closure $builderHook
     * @return int
     * @throws DBALException
     */
    private function addInToManyRelationship(
        Connection $connection,
        string $primaryIdentity,
        string $name,
        iterable $secondaryIdentities,
        Closure $builderHook
    ): int {
        $inserted = 0;

        $secondaryIdBindName = ':secondaryId';
        $saveToMany = $this
            ->createBuilderFromConnection($connection, $this->getModelClass())
            ->prepareCreateInToManyRelationship($name, $primaryIdentity, $secondaryIdBindName);

        $saveToMany = call_user_func($builderHook, $name, $saveToMany);

        foreach ($secondaryIdentities as $secondaryId) {
            try {
                $inserted += (int)$saveToMany->setParameter($secondaryIdBindName, $secondaryId)->execute();
            } catch (UcvException $exception) {
                // Spec: If all the specified resources can be added to, or are already present in,
                // the relationship then the server MUST return a successful response.
                //
                // Currently, DBAL cannot do insert or update in the same request.
                // https://github.com/doctrine/dbal/issues/1320
                continue;
            }
        }

        return $inserted;
    }

    /**
     * @param Connection $connection
     * @param string $primaryIdentity
     * @param string $name
     * @param iterable $secondaryIdentities
     * @return int
     * @throws DBALException
     */
    private function removeInToManyRelationship(
        Connection $connection,
        string $primaryIdentity,
        string $name,
        iterable $secondaryIdentities
    ): int {
        $removeToMany = $this->builderOnRemoveInBelongsToManyRelationship(
            $name,
            $this
                ->createBuilderFromConnection($connection, $this->getModelClass())
                ->prepareDeleteInToManyRelationship($name, $primaryIdentity, $secondaryIdentities)
        );
        return $removeToMany->execute();
    }

    /**
     * @param QueryBuilder $builder
     * @param string $modelClass
     * @return mixed|null
     * @throws DBALException
     */
    private function fetchResourceWithoutRelationships(QueryBuilder $builder, string $modelClass)
    {
        $model = null;
        $statement = $builder->execute();

        if ($this->isFetchTyped() === true) {
            $statement->setFetchMode(PDOConnection::FETCH_ASSOC);
            if (($attributes = $statement->fetch()) !== false) {
                $platform = $builder->getConnection()->getDatabasePlatform();
                $typeNames = $this->getModelSchemas()->getAttributeTypes($modelClass);
                $model = $this->readResourceFromAssoc($modelClass, $attributes, $typeNames, $platform);
            }
        } else {
            $statement->setFetchMode(PDOConnection::FETCH_CLASS, $modelClass);
            if (($fetched = $statement->fetch()) !== false) {
                $model = $fetched;
            }
        }

        return $model;
    }

    /**
     * @param QueryBuilder $builder
     * @param string $modelClass
     * @param string $keyColumnName
     * @return iterable
     * @throws DBALException
     */
    private function fetchResourcesWithoutRelationships(
        QueryBuilder $builder,
        string $modelClass,
        string $keyColumnName
    ): iterable {
        $statement = $builder->execute();

        if ($this->isFetchTyped() === true) {
            $statement->setFetchMode(PDOConnection::FETCH_ASSOC);
            $platform = $builder->getConnection()->getDatabasePlatform();
            $typeNames = $this->getModelSchemas()->getAttributeTypes($modelClass);
            while (($attributes = $statement->fetch()) !== false) {
                $model = $this->readResourceFromAssoc($modelClass, $attributes, $typeNames, $platform);
                yield $model->{$keyColumnName} => $model;
            }
        } else {
            $statement->setFetchMode(PDOConnection::FETCH_CLASS, $modelClass);
            while (($model = $statement->fetch()) !== false) {
                yield $model->{$keyColumnName} => $model;
            }
        }
    }

    /**
     * @param QueryBuilder $builder
     * @param string $modelClass
     * @return PaginatedDataInterface
     * @throws DBALException
     */
    private function fetchPaginatedResourcesWithoutRelationships(
        QueryBuilder $builder,
        string $modelClass
    ): PaginatedDataInterface {
        list($models, $hasMore, $limit, $offset) = $this->fetchResourceCollection($builder, $modelClass);

        $data = $this->getFactory()
            ->createPaginatedData($models)
            ->markAsCollection()
            ->setOffset($offset)
            ->setLimit($limit);

        $hasMore === true ? $data->markHasMoreItems() : $data->markHasNoMoreItems();

        return $data;
    }

    /**
     * @param QueryBuilder $builder
     * @param string $modelClass
     * @return array
     * @throws DBALException
     */
    private function fetchResourceCollection(QueryBuilder $builder, string $modelClass): array
    {
        $statement = $builder->execute();

        $models = [];
        $counter = 0;
        $hasMoreThanLimit = false;
        $limit = $builder->getMaxResults() !== null ? $builder->getMaxResults() - 1 : null;

        if ($this->isFetchTyped() === true) {
            $platform = $builder->getConnection()->getDatabasePlatform();
            $typeNames = $this->getModelSchemas()->getAttributeTypes($modelClass);
            $statement->setFetchMode(PDOConnection::FETCH_ASSOC);
            while (($attributes = $statement->fetch()) !== false) {
                $counter++;
                if ($limit !== null && $counter > $limit) {
                    $hasMoreThanLimit = true;
                    break;
                }
                $models[] = $this->readResourceFromAssoc($modelClass, $attributes, $typeNames, $platform);
            }
        } else {
            $statement->setFetchMode(PDOConnection::FETCH_CLASS, $modelClass);
            while (($fetched = $statement->fetch()) !== false) {
                $counter++;
                if ($limit !== null && $counter > $limit) {
                    $hasMoreThanLimit = true;
                    break;
                }
                $models[] = $fetched;
            }
        }

        return [$models, $hasMoreThanLimit, $limit, $builder->getFirstResult()];
    }

    /**
     * @param null|string $index
     * @param iterable $attributes
     * @return iterable
     */
    protected function filterAttributesOnCreate(?string $index, iterable $attributes): iterable
    {
        if ($index !== null) {
            yield $this->getModelSchemas()->getPrimaryKey($this->getModelClass()) => $index;
        }

        $knownAttrAndTypes = $this->getModelSchemas()->getAttributeTypes($this->getModelClass());
        foreach ($attributes as $attribute => $value) {
            if (array_key_exists($attribute, $knownAttrAndTypes) === true) {
                yield $attribute => $value;
            }
        }
    }

    /**
     * @param iterable $attributes
     * @return iterable
     */
    protected function filterAttributesOnUpdate(iterable $attributes): iterable
    {
        $knownAttrAndTypes = $this->getModelSchemas()->getAttributeTypes($this->getModelClass());
        foreach ($attributes as $attribute => $value) {
            if (array_key_exists($attribute, $knownAttrAndTypes) === true) {
                yield $attribute => $value;
            }
        }
    }

    /**
     * @param TagStorageInterface $modelsAtPath
     * @param ArrayObject $classAtPath
     * @param ArrayObject $idsAtPath
     * @param ModelStorageInterface $deDup
     * @param string $parentsPath
     * @param array $childRelationships
     * @return void
     * @throws DBALException
     */
    private function loadRelationshipsLayer(
        TagStorageInterface $modelsAtPath,
        ArrayObject $classAtPath,
        ArrayObject $idsAtPath,
        ModelStorageInterface $deDup,
        string $parentsPath,
        array $childRelationships
    ): void {
        $rootClass = $classAtPath[static::ROOT_PATH];
        $parentClass = $classAtPath[$parentsPath];
        $parents = $modelsAtPath->get($parentsPath);

        // What should we do? We have do find all child resources for $parents at paths $childRelationships (1 level
        // child paths) and add them to $relationships. While doing it we have to deduplicate resources with
        // $models.

        $pkName = $this->getModelSchemas()->getPrimaryKey($parentClass);

        $registerModelAtPath = function ($model, string $path) use ($deDup, $modelsAtPath, $idsAtPath) {
            return self::registerModelAtPath(
                $model,
                $path,
                $this->getModelSchemas(),
                $deDup,
                $modelsAtPath,
                $idsAtPath
            );
        };

        foreach ($childRelationships as $name) {
            $childrenPath = $parentsPath !== static::ROOT_PATH ? $parentsPath . static::PATH_SEPARATOR . $name : $name;

            $relationshipType = $this->getModelSchemas()->getRelationshipType($parentClass, $name);
            list (
                $targetModelClass, $reverseRelName
                ) =
                $this->getModelSchemas()->getReverseRelationship($parentClass, $name);

            $builder = $this
                ->createBuilder($targetModelClass)
                ->selectModelColumns()
                ->fromModelTable();

            $classAtPath[$childrenPath] = $targetModelClass;

            switch ($relationshipType) {
                case RelationshipTypes::BELONGS_TO:
                    // some paths might not have any records in the database
                    $areParentsLoaded = $idsAtPath->offsetExists($parentsPath);
                    if ($areParentsLoaded === false) {
                        break;
                    }
                    // for 'belongsTo' relationship all resources could be read at once.
                    $parentIds = $idsAtPath[$parentsPath];
                    $clonedBuilder = (clone $builder)->addRelationshipFiltersAndSorts(
                        $reverseRelName,
                        [$pkName => [FilterParameterInterface::OPERATION_IN => $parentIds]],
                        null
                    );
                    $unregisteredChildren = $this->fetchResourcesWithoutRelationships(
                        $clonedBuilder,
                        $clonedBuilder->getModelClass(),
                        $this->getModelSchemas()->getPrimaryKey($clonedBuilder->getModelClass())
                    );
                    $children = [];
                    foreach ($unregisteredChildren as $index => $unregisteredChild) {
                        $children[$index] = $registerModelAtPath($unregisteredChild, $childrenPath);
                    }
                    $fkNameToChild = $this->getModelSchemas()->getForeignKey($parentClass, $name);
                    foreach ($parents as $parent) {
                        $fkToChild = $parent->{$fkNameToChild};
                        $parent->{$name} = $children[$fkToChild] ?? null;
                    }
                    break;
                case RelationshipTypes::HAS_MANY:
                case RelationshipTypes::BELONGS_TO_MANY:
                    // unfortunately we have paging limits for 'many' relationship thus we have read such
                    // relationships for each 'parent' individually
                    list ($queryOffset, $queryLimit) = $this->getRelationshipPagingStrategy()
                        ->getParameters($rootClass, $parentClass, $parentsPath, $name);
                    $builder->setFirstResult($queryOffset)->setMaxResults($queryLimit + 1);
                    // pagination requires predictable data order from the database so we are adding sorting by PK asc
                    $targetPkName = $this->getModelSchemas()->getPrimaryKey($targetModelClass);
                    $builder->addSorts([$targetPkName => true]);
                    foreach ($parents as $parent) {
                        $clonedBuilder = (clone $builder)->addRelationshipFiltersAndSorts(
                            $reverseRelName,
                            [$pkName => [FilterParameterInterface::OPERATION_EQUALS => [$parent->{$pkName}]]],
                            []
                        );
                        $children = $this->fetchPaginatedResourcesWithoutRelationships(
                            $clonedBuilder,
                            $clonedBuilder->getModelClass()
                        );

                        $deDupedChildren = [];
                        foreach ($children->getData() as $child) {
                            $deDupedChildren[] = $registerModelAtPath($child, $childrenPath);
                        }

                        $paginated = $this->getFactory()
                            ->createPaginatedData($deDupedChildren)
                            ->markAsCollection()
                            ->setOffset($children->getOffset())
                            ->setLimit($children->getLimit());
                        $children->hasMoreItems() === true ?
                            $paginated->markHasMoreItems() : $paginated->markHasNoMoreItems();

                        $parent->{$name} = $paginated;
                    }
                    break;
            }
        }
    }

    /**
     * @param string $message
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getMessage(string $message): string
    {
        /** @var FormatterFactoryInterface $factory */
        $factory = $this->getContainer()->get(FormatterFactoryInterface::class);
        $formatter = $factory->createFormatter(Messages::NAMESPACE_NAME);
        return $formatter->formatMessage($message);
    }

    /**
     * @param string $class
     * @param array $attributes
     * @param Type[] $typeNames
     * @param AbstractPlatform $platform
     * @return mixed
     * @throws DBALException
     */
    private function readResourceFromAssoc(
        string $class,
        array $attributes,
        array $typeNames,
        AbstractPlatform $platform
    ) {
        $instance = new $class();
        foreach ($this->readTypedAttributes($attributes, $typeNames, $platform) as $name => $value) {
            $instance->{$name} = $value;
        }

        return $instance;
    }

    /**
     * @param array $attributes
     * @param Type[] $typeNames
     * @param AbstractPlatform $platform
     * @return array
     * @throws DBALException
     */
    private function readRowFromAssoc(array $attributes, array $typeNames, AbstractPlatform $platform): array
    {
        $row = [];
        foreach ($this->readTypedAttributes($attributes, $typeNames, $platform) as $name => $value) {
            $row[$name] = $value;
        }

        return $row;
    }

    /**
     * @param iterable $attributes
     * @param array $typeNames
     * @param AbstractPlatform $platform
     * @return iterable
     * @throws DBALException
     */
    private function readTypedAttributes(iterable $attributes, array $typeNames, AbstractPlatform $platform): iterable
    {
        foreach ($attributes as $name => $value) {
            yield $name => (array_key_exists($name, $typeNames) === true ?
                Type::getType($typeNames[$name])->convertToPHPValue($value, $platform) : $value);
        }
    }
}
