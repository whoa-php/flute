<?php
declare (strict_types=1);

namespace Whoa\Flute\Contracts\Schema;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface as BaseSchemaInterface;

/**
 * @package Whoa\Flute
 */
interface SchemaInterface extends BaseSchemaInterface
{
    /** @var string|null Type */
    public const TYPE = null;

    /** @var string|null Model class name */
    public const MODEL = null;

    /** Attribute name */
    public const RESOURCE_ID = DocumentInterface::KEYWORD_ID;

    /** Attribute name */
    public const RESOURCE_TYPE = DocumentInterface::KEYWORD_TYPE;

    /** Mapping key */
    public const SCHEMA_ATTRIBUTES = 0;

    /** Mapping key */
    public const SCHEMA_RELATIONSHIPS = self::SCHEMA_ATTRIBUTES + 1;

    /**
     * @return array
     */
    public static function getMappings(): array;

    /**
     * @param string $jsonName
     *
     * @return string
     */
    public static function getAttributeMapping(string $jsonName): string;

    /**
     * @param string $jsonName
     *
     * @return string
     */
    public static function getRelationshipMapping(string $jsonName): string;

    /**
     * @param string $jsonName
     *
     * @return bool
     */
    public static function hasAttributeMapping(string $jsonName): bool;

    /**
     * @param string $jsonName
     *
     * @return bool
     */
    public static function hasRelationshipMapping(string $jsonName): bool;

    /**
     * @param string $relationshipName
     *
     * @return bool
     */
    public function isAddSelfLinkInRelationshipWithData(string $relationshipName): bool;
}
