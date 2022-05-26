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

use Doctrine\DBAL\Types\Types;
use Whoa\Contracts\Data\RelationshipTypes;
use Whoa\Doctrine\Types\DateTimeType;
use Whoa\Doctrine\Types\UuidType;

/**
 * @package Whoa\Tests\Flute
 */
class Comment extends Model
{
    /** @inheritdoc */
    public const TABLE_NAME = 'comments';

    /** @inheritdoc */
    public const FIELD_ID = 'id_comment';

    /** Field name */
    public const FIELD_ID_POST = 'id_post_fk';

    /** Field name */
    public const FIELD_ID_USER = 'id_user_fk';

    /** Relationship name */
    public const REL_POST = 'post';

    /** Relationship name */
    public const REL_USER = 'user';

    /** Relationship name */
    public const REL_EMOTIONS = 'emotions';

    /** Field name */
    public const FIELD_TEXT = 'text';

    /** Field name */
    public const FIELD_INT = 'int_value';

    /** Field name */
    public const FIELD_FLOAT = 'float_value';

    /** Field name */
    public const FIELD_BOOL = 'bool_value';

    /** Field name */
    public const FIELD_DATE_TIME = 'datetime_value';

    /** Length constant */
    public const LENGTH_TEXT = 255;

    /**
     * @inheritdoc
     */
    public static function getAttributeTypes(): array
    {
        return [
            self::FIELD_ID => Types::INTEGER,
            self::FIELD_ID_POST => Types::INTEGER,
            self::FIELD_ID_USER => Types::INTEGER,
            self::FIELD_UUID => UuidType::NAME,
            self::FIELD_TEXT => Types::STRING,
            self::FIELD_INT => Types::INTEGER,
            self::FIELD_FLOAT => Types::FLOAT,
            self::FIELD_BOOL => Types::BOOLEAN,
            self::FIELD_DATE_TIME => Types::DATETIME_IMMUTABLE,
            self::FIELD_CREATED_AT => DateTimeType::NAME,
            self::FIELD_UPDATED_AT => DateTimeType::NAME,
            self::FIELD_DELETED_AT => DateTimeType::NAME,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeLengths(): array
    {
        return [
            self::FIELD_TEXT => self::LENGTH_TEXT,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getRelationships(): array
    {
        return [
            RelationshipTypes::BELONGS_TO => [
                self::REL_POST => [Post::class, self::FIELD_ID_POST, Post::REL_COMMENTS],
                self::REL_USER => [User::class, self::FIELD_ID_USER, User::REL_COMMENTS],
            ],
            RelationshipTypes::BELONGS_TO_MANY => [
                self::REL_EMOTIONS => [
                    Emotion::class,
                    CommentEmotion::TABLE_NAME,
                    CommentEmotion::FIELD_ID_COMMENT,
                    CommentEmotion::FIELD_ID_EMOTION,
                    Emotion::REL_COMMENTS,
                ],
            ],
        ];
    }
}
