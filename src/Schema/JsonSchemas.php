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

use Whoa\Common\Reflection\ClassIsTrait;
use Whoa\Contracts\Data\ModelSchemaInfoInterface;
use Whoa\Flute\Contracts\Schema\JsonSchemasInterface;
use Whoa\Flute\Contracts\Schema\SchemaInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface as JsonSchemaInterface;

use function array_key_exists;
use function assert;
use function get_class;
use function is_object;

/**
 * @package Whoa\Flute
 */
class JsonSchemas implements JsonSchemasInterface
{
    use ClassIsTrait;

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * @var array
     */
    private array $modelToSchemaMap;

    /**
     * @var array
     */
    private array $typeToSchemaMap;

    /**
     * @var array
     */
    private array $schemaInstances = [];

    /**
     * @var ModelSchemaInfoInterface
     */
    private ModelSchemaInfoInterface $modelSchemas;

    /**
     * @param FactoryInterface $factory
     * @param array $modelToSchemaMap
     * @param array $typeToSchemaMap
     * @param ModelSchemaInfoInterface $modelSchemas
     */
    public function __construct(
        FactoryInterface $factory,
        array $modelToSchemaMap,
        array $typeToSchemaMap,
        ModelSchemaInfoInterface $modelSchemas
    ) {
        $this->factory = $factory;
        $this->modelToSchemaMap = $modelToSchemaMap;
        $this->typeToSchemaMap = $typeToSchemaMap;
        $this->modelSchemas = $modelSchemas;
    }

    /**
     * @inheritdoc
     */
    public function getSchema($resourceObject): JsonSchemaInterface
    {
        assert($this->hasSchema($resourceObject));

        return $this->getSchemaByModelClass($this->getResourceClass($resourceObject));
    }

    /**
     * @inheritdoc
     */
    public function hasSchema($resourceObject): bool
    {
        return is_object($resourceObject) === true &&
            array_key_exists($this->getResourceClass($resourceObject), $this->modelToSchemaMap);
    }

    /**
     * @inheritdoc
     */
    public function hasRelationshipSchema(string $schemaClass, string $relationshipName): bool
    {
        assert(static::classImplements($schemaClass, SchemaInterface::class));

        /** @var SchemaInterface $schemaClass */

        $hasRel = $schemaClass::getMappings()[SchemaInterface::SCHEMA_RELATIONSHIPS][$relationshipName] ?? false;

        assert($hasRel === false || $this->getRelationshipSchema($schemaClass, $relationshipName) !== null);

        return $hasRel !== false;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipSchema(string $schemaClass, string $relationshipName): SchemaInterface
    {
        assert(static::classImplements($schemaClass, SchemaInterface::class));

        /** @var SchemaInterface $schemaClass */

        $modelRelName = $schemaClass::getMappings()[SchemaInterface::SCHEMA_RELATIONSHIPS][$relationshipName];
        return $this->getModelRelationshipSchema($schemaClass::MODEL, $modelRelName);
    }

    /**
     * @inheritdoc
     */
    public function getModelRelationshipSchema(string $modelClass, string $relationshipName): SchemaInterface
    {
        $reverseModelClass = $this->getModelSchemas()->getReverseModelClass($modelClass, $relationshipName);

        /** @var SchemaInterface $targetSchema */
        return $this->getSchemaByModelClass($reverseModelClass);
    }

    /**
     * @inheritdoc
     */
    public function getSchemaByResourceType(string $resourceType): SchemaInterface
    {
        assert(array_key_exists($resourceType, $this->typeToSchemaMap));

        $schemaClass = $this->typeToSchemaMap[$resourceType];

        return $this->getSchemaByClass($schemaClass);
    }

    /**
     * @return FactoryInterface
     */
    private function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    /**
     * @return ModelSchemaInfoInterface
     */
    private function getModelSchemas(): ModelSchemaInfoInterface
    {
        return $this->modelSchemas;
    }

    /**
     * @param mixed $resource
     * @return string
     */
    private function getResourceClass($resource): string
    {
        assert(
            is_object($resource) === true,
            'Unable to get a type of the resource as it is not an object.'
        );

        return get_class($resource);
    }

    /**
     * @inheritdoc
     */
    private function getSchemaByModelClass(string $modelClass): JsonSchemaInterface
    {
        assert(array_key_exists($modelClass, $this->modelToSchemaMap));

        $schemaClass = $this->modelToSchemaMap[$modelClass];

        return $this->getSchemaByClass($schemaClass);
    }

    /**
     * @param string $schemaClass
     * @return SchemaInterface
     */
    private function getSchemaByClass(string $schemaClass): JsonSchemaInterface
    {
        assert(static::classImplements($schemaClass, JsonSchemaInterface::class));

        if (array_key_exists($schemaClass, $this->schemaInstances) === false) {
            $this->schemaInstances[$schemaClass] =
                ($schema = new $schemaClass($this->getFactory(), $this, $this->getModelSchemas()));

            return $schema;
        }

        return $this->schemaInstances[$schemaClass];
    }
}
