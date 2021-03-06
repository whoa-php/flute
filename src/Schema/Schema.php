<?php

/**
 * Copyright 2015-2019 info@neomerx.com
 * Modification Copyright 2021 info@whoaphp.com
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

namespace Whoa\Flute\Schema;

use Whoa\Contracts\Application\ModelInterface;
use Whoa\Contracts\Data\ModelSchemaInfoInterface;
use Whoa\Contracts\Data\RelationshipTypes;
use Whoa\Flute\Contracts\Models\PaginatedDataInterface;
use Whoa\Flute\Contracts\Schema\JsonSchemasInterface;
use Whoa\Flute\Contracts\Schema\SchemaInterface;
use Whoa\Flute\Contracts\Validation\JsonApiQueryParserInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use Neomerx\JsonApi\Schema\BaseSchema;
use Neomerx\JsonApi\Schema\Identifier;

use function array_key_exists;
use function assert;
use function http_build_query;
use function property_exists;

/**
 * @package Whoa\Flute
 */
abstract class Schema extends BaseSchema implements SchemaInterface
{
    /**
     * @var ModelSchemaInfoInterface
     */
    private ModelSchemaInfoInterface $modelSchemas;

    /**
     * @var JsonSchemasInterface
     */
    private JsonSchemasInterface $jsonSchemas;

    /**
     * @var array|null
     */
    private ?array $attributesMapping;

    /**
     * @var array|null
     */
    private ?array $relationshipsMapping;

    /**
     * @param FactoryInterface $factory
     * @param JsonSchemasInterface $jsonSchemas
     * @param ModelSchemaInfoInterface $modelSchemas
     */
    public function __construct(
        FactoryInterface $factory,
        JsonSchemasInterface $jsonSchemas,
        ModelSchemaInfoInterface $modelSchemas
    ) {
        assert(empty(static::TYPE) === false);
        assert(empty(static::MODEL) === false);

        parent::__construct($factory);

        $this->modelSchemas = $modelSchemas;
        $this->jsonSchemas = $jsonSchemas;

        $this->attributesMapping = null;
        $this->relationshipsMapping = null;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return static::TYPE;
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeMapping(string $jsonName): string
    {
        return static::getMappings()[static::SCHEMA_ATTRIBUTES][$jsonName];
    }

    /**
     * @inheritdoc
     */
    public static function getRelationshipMapping(string $jsonName): string
    {
        return static::getMappings()[static::SCHEMA_RELATIONSHIPS][$jsonName];
    }

    /**
     * @inheritdoc
     */
    public static function hasAttributeMapping(string $jsonName): bool
    {
        $mappings = static::getMappings();

        return
            array_key_exists(static::SCHEMA_ATTRIBUTES, $mappings) === true &&
            array_key_exists($jsonName, $mappings[static::SCHEMA_ATTRIBUTES]) === true;
    }

    /**
     * @inheritdoc
     */
    public static function hasRelationshipMapping(string $jsonName): bool
    {
        $mappings = static::getMappings();

        return
            array_key_exists(static::SCHEMA_RELATIONSHIPS, $mappings) === true &&
            array_key_exists($jsonName, $mappings[static::SCHEMA_RELATIONSHIPS]) === true;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes($resource): iterable
    {
        foreach ($this->getAttributesMapping() as $jsonAttrName => $modelAttrName) {
            if ($this->hasProperty($resource, $modelAttrName) === true) {
                yield $jsonAttrName => $this->getProperty($resource, $modelAttrName);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getRelationships($resource): iterable
    {
        assert($resource instanceof ModelInterface);

        foreach ($this->getRelationshipsMapping() as $jsonRelName => [$modelRelName, $belongsToFkName, $reverseType]) {
            // if model has relationship data then use it
            if ($this->hasProperty($resource, $modelRelName) === true) {
                yield $jsonRelName => $this->createRelationshipRepresentationFromData(
                    $resource,
                    $modelRelName,
                    $jsonRelName
                );
                continue;
            }

            // if relationship is `belongs-to` and has that ID we can add relationship as identifier
            if ($belongsToFkName !== null && $this->hasProperty($resource, $belongsToFkName) === true) {
                $reverseIndex = $this->getProperty($resource, $belongsToFkName);
                $identifier = $reverseIndex === null ?
                    null : new Identifier((string)$reverseIndex, $reverseType, false, null);

                yield $jsonRelName => [
                    static::RELATIONSHIP_DATA => $identifier,
                    static::RELATIONSHIP_LINKS_SELF => $this->isAddSelfLinkInRelationshipWithData($jsonRelName),
                ];
                continue;
            }

            // if we are here it's nothing left but show relationship as a link
            yield $jsonRelName => [static::RELATIONSHIP_LINKS_SELF => true];
        }
    }

    /**
     * @inheritdoc
     */
    public function isAddSelfLinkInRelationshipWithData(string $relationshipName): bool
    {
        return false;
    }

    /**
     * @return ModelSchemaInfoInterface
     */
    protected function getModelSchemas(): ModelSchemaInfoInterface
    {
        return $this->modelSchemas;
    }

    /**
     * @return JsonSchemasInterface
     */
    protected function getJsonSchemas(): JsonSchemasInterface
    {
        return $this->jsonSchemas;
    }

    /**
     * @param ModelInterface $model
     * @param string $modelRelName
     * @param string $jsonRelName
     * @return array
     */
    protected function createRelationshipRepresentationFromData(
        ModelInterface $model,
        string $modelRelName,
        string $jsonRelName
    ): array {
        assert($this->hasProperty($model, $modelRelName) === true);
        $relationshipData = $this->getProperty($model, $modelRelName);
        $isPaginatedData = $relationshipData instanceof PaginatedDataInterface;

        $description = [static::RELATIONSHIP_LINKS_SELF => $this->isAddSelfLinkInRelationshipWithData($jsonRelName)];

        if ($isPaginatedData === false) {
            $description[static::RELATIONSHIP_DATA] = $relationshipData;

            return $description;
        }

        assert($relationshipData instanceof PaginatedDataInterface);

        $description[static::RELATIONSHIP_DATA] = $relationshipData->getData();

        if ($relationshipData->hasMoreItems() === false) {
            return $description;
        }

        // if we are here then relationship contains paginated data, so we have to add pagination links
        $offset = $relationshipData->getOffset();
        $limit = $relationshipData->getLimit();
        $urlPrefix = $this->getRelationshipSelfSubUrl($model, $jsonRelName) . '?';
        $buildLink = function (int $offset, int $limit) use ($urlPrefix): LinkInterface {
            $paramsWithPaging = [
                JsonApiQueryParserInterface::PARAM_PAGING_OFFSET => $offset,
                JsonApiQueryParserInterface::PARAM_PAGING_LIMIT => $limit,
            ];

            $subUrl = $urlPrefix . http_build_query($paramsWithPaging);

            return $this->getFactory()->createLink(true, $subUrl, false);
        };

        $nextOffset = $offset + $limit;
        $nextLimit = $limit;
        if ($offset <= 0) {
            $description[static::RELATIONSHIP_LINKS] = [
                DocumentInterface::KEYWORD_NEXT => $buildLink($nextOffset, $nextLimit),
            ];
        } else {
            $prevOffset = $offset - $limit;
            if ($prevOffset < 0) {
                // set offset 0 and decrease limit
                $prevLimit = $limit + $prevOffset;
                $prevOffset = 0;
            } else {
                $prevLimit = $limit;
            }
            $description[static::RELATIONSHIP_LINKS] = [
                DocumentInterface::KEYWORD_PREV => $buildLink($prevOffset, $prevLimit),
                DocumentInterface::KEYWORD_NEXT => $buildLink($nextOffset, $nextLimit),
            ];
        }

        return $description;
    }

    /**
     * @param ModelInterface $model
     * @param string $name
     * @return bool
     */
    protected function hasProperty(ModelInterface $model, string $name): bool
    {
        return property_exists($model, $name) || isset($model->{$name});
    }

    /**
     * @param ModelInterface $model
     * @param string $name
     * @return mixed
     */
    protected function getProperty(ModelInterface $model, string $name)
    {
        assert($this->hasProperty($model, $name));

        return $model->{$name};
    }

    /**
     * @return array
     */
    private function getAttributesMapping(): array
    {
        if ($this->attributesMapping !== null) {
            return $this->attributesMapping;
        }

        $attributesMapping = static::getMappings()[static::SCHEMA_ATTRIBUTES] ?? [];

        // `id` is a `special` attribute and cannot be included in JSON API resource
        unset($attributesMapping[static::RESOURCE_ID]);

        $this->attributesMapping = $attributesMapping;

        return $this->attributesMapping;
    }

    /**
     * @return array
     */
    private function getRelationshipsMapping(): array
    {
        if ($this->relationshipsMapping !== null) {
            return $this->relationshipsMapping;
        }

        $relationshipsMapping = [];
        foreach (static::getMappings()[static::SCHEMA_RELATIONSHIPS] ?? [] as $jsonRelName => $modelRelName) {
            $belongsToFkName = null;
            $reverseJsonType = null;

            $relType = $this->getModelSchemas()->getRelationshipType(static::MODEL, $modelRelName);
            if ($relType === RelationshipTypes::BELONGS_TO) {
                $belongsToFkName = $this->getModelSchemas()->getForeignKey(static::MODEL, $modelRelName);
                $reverseSchema = $this->getJsonSchemas()
                    ->getRelationshipSchema(static::class, $jsonRelName);
                $reverseJsonType = $reverseSchema->getType();
            }

            $relationshipsMapping[$jsonRelName] = [$modelRelName, $belongsToFkName, $reverseJsonType];
        }

        $this->relationshipsMapping = $relationshipsMapping;

        return $this->relationshipsMapping;
    }

    /**
     * @param ModelInterface $model
     * @param string $jsonRelName
     * @return string
     */
    private function getRelationshipSelfSubUrl(ModelInterface $model, string $jsonRelName): string
    {
        return $this->getSelfSubUrl($model) . '/' . DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $jsonRelName;
    }
}
