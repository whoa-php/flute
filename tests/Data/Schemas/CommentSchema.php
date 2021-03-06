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

namespace Whoa\Tests\Flute\Data\Schemas;

use Whoa\Tests\Flute\Data\Models\Comment as Model;
use Whoa\Tests\Flute\Data\Models\Model as BaseModel;

/**
 * @package Whoa\Tests\Flute
 */
class CommentSchema extends BaseSchema
{
    /** Type */
    public const TYPE = 'comments';

    /** Model class name */
    public const MODEL = Model::class;

    /** Attribute name */
    public const ATTR_TEXT = 'text-attribute';

    /** Attribute name */
    public const ATTR_INT = 'int-attribute';

    /** Attribute name */
    public const ATTR_FLOAT = 'float-attribute';

    /** Attribute name */
    public const ATTR_BOOL = 'bool-attribute';

    /** Attribute name */
    public const ATTR_DATE_TIME = 'date-time-attribute';

    /** Relationship name */
    public const REL_USER = 'user-relationship';

    /** Relationship name */
    public const REL_POST = 'post-relationship';

    /** Relationship name */
    public const REL_EMOTIONS = 'emotions-relationship';

    /**
     * @inheritdoc
     */
    public static function getMappings(): array
    {
        return [
            self::SCHEMA_ATTRIBUTES => [
                self::RESOURCE_ID => Model::FIELD_ID,
                self::ATTR_UUID => BaseModel::FIELD_UUID,
                self::ATTR_TEXT => Model::FIELD_TEXT,
                self::ATTR_INT => Model::FIELD_INT,
                self::ATTR_FLOAT => Model::FIELD_FLOAT,
                self::ATTR_BOOL => Model::FIELD_BOOL,
                self::ATTR_DATE_TIME => Model::FIELD_DATE_TIME,
                self::ATTR_CREATED_AT => BaseModel::FIELD_CREATED_AT,
                self::ATTR_UPDATED_AT => BaseModel::FIELD_UPDATED_AT,
            ],
            self::SCHEMA_RELATIONSHIPS => [
                self::REL_USER => Model::REL_USER,
                self::REL_POST => Model::REL_POST,
                self::REL_EMOTIONS => Model::REL_EMOTIONS,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isAddRelatedLinkInRelationshipByDefault(string $relationshipName): bool
    {
        return
            parent::isAddRelatedLinkInRelationshipByDefault($relationshipName) === true &&
            in_array($relationshipName, [
                self::REL_USER,
                self::REL_POST,
                self::REL_EMOTIONS,
            ]) === false;
    }
}
