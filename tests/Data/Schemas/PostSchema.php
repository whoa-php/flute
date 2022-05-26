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

use Whoa\Tests\Flute\Data\Models\Model as BaseModel;
use Whoa\Tests\Flute\Data\Models\Post as Model;

/**
 * @package Whoa\Tests\Flute
 */
class PostSchema extends BaseSchema
{
    /** Type */
    public const TYPE = 'posts';

    /** Model class name */
    public const MODEL = Model::class;

    /** Attribute name */
    public const ATTR_TITLE = 'title-attribute';

    /** Attribute name */
    public const ATTR_TEXT = 'text-attribute';

    /** Relationship name */
    public const REL_USER = 'user-relationship';

    /** Relationship name */
    public const REL_EDITOR = 'editor-relationship';

    /** Relationship name */
    public const REL_BOARD = 'board-relationship';

    /** Relationship name */
    public const REL_COMMENTS = 'comments-relationship';

    /**
     * @inheritdoc
     */
    public static function getMappings(): array
    {
        return [
            self::SCHEMA_ATTRIBUTES => [
                self::RESOURCE_ID => Model::FIELD_ID,
                self::ATTR_TITLE => Model::FIELD_TITLE,
                self::ATTR_TEXT => Model::FIELD_TEXT,
                self::ATTR_CREATED_AT => BaseModel::FIELD_CREATED_AT,
                self::ATTR_UPDATED_AT => BaseModel::FIELD_UPDATED_AT,
            ],
            self::SCHEMA_RELATIONSHIPS => [
                self::REL_USER => Model::REL_USER,
                self::REL_EDITOR => Model::REL_EDITOR,
                self::REL_BOARD => Model::REL_BOARD,
                self::REL_COMMENTS => Model::REL_COMMENTS,
            ],
        ];
    }
}
