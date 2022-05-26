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

namespace Whoa\Tests\Flute\Data\Models;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Whoa\Contracts\Data\ModelSchemaInfoInterface;
use Whoa\Contracts\Data\RelationshipTypes;

/**
 * @package Whoa\Flute
 */
class ModelSchemas implements ModelSchemaInfoInterface
{
    /**
     * @throws DBALException
     */
    public function getAttributeTypeInstances($class): array
    {
        $types = $this->getAttributeTypes($class);
        $result = [];

        foreach ($types as $name => $type) {
            $result[$name] = Type::getType($type);
        }

        return $result;
    }

    // Code below copy-pasted from Application component

    /**
     * @var array
     */
    private array $relationshipTypes = [];

    /**
     * @var array
     */
    private array $reversedRelationships = [];

    /**
     * @var array
     */
    private array $reversedClasses = [];

    /**
     * @var array
     */
    private array $foreignKeys = [];

    /**
     * @var array
     */
    private array $belongsToMany = [];

    /**
     * @var array
     */
    private array $tableNames = [];

    /**
     * @var array
     */
    private array $primaryKeys = [];

    /**
     * @var array
     */
    private array $attributeTypes = [];

    /**
     * @var array
     */
    private array $attributeLengths = [];

    /**
     * @var array
     */
    private array $attributes = [];

    /**
     * @var array
     */
    private array $rawAttributes = [];

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        return [
            $this->foreignKeys,
            $this->belongsToMany,
            $this->relationshipTypes,
            $this->reversedRelationships,
            $this->tableNames,
            $this->primaryKeys,
            $this->attributeTypes,
            $this->attributeLengths,
            $this->attributes,
            $this->rawAttributes,
            $this->reversedClasses,
        ];
    }

    /**
     * @param array $data
     * @return void
     */
    public function setData(array $data)
    {
        [
            $this->foreignKeys,
            $this->belongsToMany,
            $this->relationshipTypes,
            $this->reversedRelationships,
            $this->tableNames,
            $this->primaryKeys,
            $this->attributeTypes,
            $this->attributeLengths,
            $this->attributes,
            $this->rawAttributes,
            $this->reversedClasses
        ] = $data;
    }

    /**
     * @inheritdoc
     */
    public function registerClass(
        string $class,
        string $tableName,
        string $primaryKey,
        array $attributeTypes,
        array $attributeLengths,
        array $rawAttributes = [],
        array $virtualAttributes = []
    ): ModelSchemaInfoInterface {
        if (empty($class) === true) {
            throw new InvalidArgumentException('class');
        }

        if (empty($tableName) === true) {
            throw new InvalidArgumentException('tableName');
        }

        if (empty($primaryKey) === true) {
            throw new InvalidArgumentException('primaryKey');
        }

        $this->tableNames[$class] = $tableName;
        $this->primaryKeys[$class] = $primaryKey;
        $this->attributeTypes[$class] = $attributeTypes;
        $this->attributeLengths[$class] = $attributeLengths;
        $this->attributes[$class] = array_keys($attributeTypes);
        $this->rawAttributes[$class] = $rawAttributes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hasClass(string $class): bool
    {
        return array_key_exists($class, $this->tableNames);
    }

    /**
     * @inheritdoc
     */
    public function getTable(string $class): string
    {
        return $this->tableNames[$class];
    }

    /**
     * @inheritdoc
     */
    public function getPrimaryKey(string $class): string
    {
        return $this->primaryKeys[$class];
    }

    /**
     * @inheritdoc
     */
    public function getAttributeTypes(string $class): array
    {
        return $this->attributeTypes[$class];
    }

    /**
     * @inheritdoc
     */
    public function getAttributeType(string $class, string $name): string
    {
        return $this->attributeTypes[$class][$name];
    }

    /**
     * @inheritdoc
     */
    public function hasAttributeType(string $class, string $name): bool
    {
        return isset($this->attributeTypes[$class][$name]);
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLengths(string $class): array
    {
        return $this->attributeLengths[$class];
    }

    /**
     * @inheritdoc
     */
    public function hasAttributeLength(string $class, string $name): bool
    {
        return isset($this->attributeLengths[$class][$name]);
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLength(string $class, string $name): int
    {
        return $this->attributeLengths[$class][$name];
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(string $class): array
    {
        return $this->attributes[$class];
    }

    /**
     * @inheritdoc
     */
    public function getRawAttributes(string $class): array
    {
        return $this->rawAttributes[$class];
    }

    /**
     * @inheritdoc
     */
    public function hasRelationship(string $class, string $name): bool
    {
        return isset($this->relationshipTypes[$class][$name]);
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipType(string $class, string $name): int
    {
        return $this->relationshipTypes[$class][$name];
    }

    /**
     * @inheritdoc
     */
    public function getReverseRelationship(string $class, string $name): array
    {
        return $this->reversedRelationships[$class][$name];
    }

    /**
     * @inheritdoc
     */
    public function getReversePrimaryKey(string $class, string $name): array
    {
        $reverseClass = $this->getReverseModelClass($class, $name);

        $table = $this->getTable($reverseClass);
        $key = $this->getPrimaryKey($reverseClass);

        return [$key, $table];
    }

    /**
     * @inheritdoc
     */
    public function getReverseForeignKey(string $class, string $name): array
    {
        [$reverseClass, $reverseName] = $this->getReverseRelationship($class, $name);

        $table = $this->getTable($reverseClass);
        // would work only if $name is hasMany relationship
        $key = $this->getForeignKey($reverseClass, $reverseName);

        return [$key, $table];
    }

    /**
     * @inheritdoc
     */
    public function getReverseModelClass(string $class, string $name): string
    {
        $reverseClass = $this->reversedClasses[$class][$name];

        return $reverseClass;
    }

    /**
     * @inheritdoc
     */
    public function getForeignKey(string $class, string $name): string
    {
        $result = $this->foreignKeys[$class][$name];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getBelongsToManyRelationship(string $class, string $name): array
    {
        return $this->belongsToMany[$class][$name];
    }

    /**
     * @inheritdoc
     */
    public function registerBelongsToOneRelationship(
        string $class,
        string $name,
        string $foreignKey,
        string $reverseClass,
        string $reverseName
    ): ModelSchemaInfoInterface {
        $this->registerRelationshipType(RelationshipTypes::BELONGS_TO, $class, $name);
        $this->registerRelationshipType(RelationshipTypes::HAS_MANY, $reverseClass, $reverseName);

        $this->registerReversedRelationship($class, $name, $reverseClass, $reverseName);
        $this->registerReversedRelationship($reverseClass, $reverseName, $class, $name);

        $this->foreignKeys[$class][$name] = $foreignKey;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function registerBelongsToManyRelationship(
        string $class,
        string $name,
        string $table,
        string $foreignKey,
        string $reverseForeignKey,
        string $reverseClass,
        string $reverseName
    ): ModelSchemaInfoInterface {
        $this->registerRelationshipType(RelationshipTypes::BELONGS_TO_MANY, $class, $name);
        $this->registerRelationshipType(RelationshipTypes::BELONGS_TO_MANY, $reverseClass, $reverseName);

        // NOTE:
        // `registerReversedRelationship` relies on duplicate registration check in `registerRelationshipType`
        // so it must be called afterwards
        $this->registerReversedRelationship($class, $name, $reverseClass, $reverseName);
        $this->registerReversedRelationship($reverseClass, $reverseName, $class, $name);

        $this->belongsToMany[$class][$name] = [$table, $foreignKey, $reverseForeignKey];
        $this->belongsToMany[$reverseClass][$reverseName] = [$table, $reverseForeignKey, $foreignKey];

        return $this;
    }

    /**
     * @param int $type
     * @param string $class
     * @param string $name
     * @return void
     */
    private function registerRelationshipType(int $type, string $class, string $name)
    {
        assert(empty($class) === false && empty($name) === false);
        assert(
            isset($this->relationshipTypes[$class][$name]) === false,
            "Relationship `$name` for class `$class` was already used."
        );

        $this->relationshipTypes[$class][$name] = $type;
    }

    /**
     * @param string $class
     * @param string $name
     * @param string $reverseClass
     * @param string $reverseName
     * @return void
     */
    private function registerReversedRelationship(
        string $class,
        string $name,
        string $reverseClass,
        string $reverseName
    ) {
        assert(
            empty($class) === false &&
            empty($name) === false &&
            empty($reverseClass) === false &&
            empty($reverseName) === false
        );

        // NOTE:
        // this function relies on it would be called after
        // `registerRelationshipType` which prevents duplicate registrations

        $this->reversedRelationships[$class][$name] = [$reverseClass, $reverseName];
        $this->reversedClasses[$class][$name] = $reverseClass;
    }

    public function getVirtualAttributes(string $class): array
    {
        return [];
    }
}
