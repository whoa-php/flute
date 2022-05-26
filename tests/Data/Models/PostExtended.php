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

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Types;
use Whoa\Doctrine\Types\DateTimeType;
use Whoa\Tests\Flute\Data\Types\SystemDateTimeType;

/**
 * @package Whoa\Tests\Flute
 */
class PostExtended extends Model
{
    /** @inheritdoc */
    public const TABLE_NAME = 'posts_extended';

    /** @inheritdoc */
    public const FIELD_ID = 'id_post';

    /** Field name */
    public const FIELD_ID_BOARD = 'id_board_fk';

    /** Field name */
    public const FIELD_ID_USER = 'id_user_fk';

    /** Field name */
    public const FIELD_ID_EDITOR = 'id_editor_fk';

    /** Relationship name */
    public const REL_BOARD = 'board';

    /** Relationship name */
    public const REL_USER = 'user';

    /** Relationship name */
    public const REL_EDITOR = 'editor';

    /** Relationship name */
    public const REL_COMMENTS = 'comments';

    /** Field name */
    public const FIELD_TITLE = 'title';

    /** Field name */
    public const FIELD_TEXT = 'text';

    /** Length constant */
    public const LENGTH_TITLE = 255;

    /**
     * @inheritdoc
     */
    public static function getAttributeTypes(): array
    {
        return [
            self::FIELD_ID => Types::INTEGER,
            self::FIELD_ID_BOARD => Types::INTEGER,
            self::FIELD_ID_USER => Types::INTEGER,
            self::FIELD_ID_EDITOR => Types::INTEGER,
            self::FIELD_TITLE => Types::STRING,
            self::FIELD_TEXT => Types::TEXT,
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
            self::FIELD_TITLE => self::LENGTH_TITLE,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getRelationships(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getRawAttributes(): array
    {
        return [
                function (string $tableAlias, AbstractPlatform $platform): array {
                    $usersTable = User::TABLE_NAME;
                    $userId = User::FIELD_ID;
                    $userName = User::FIELD_FIRST_NAME;
                    $authorId = $platform->quoteSingleIdentifier($tableAlias) . '.' .
                        $platform->quoteSingleIdentifier(self::FIELD_ID_USER);
                    
                    return ["(SELECT $userName FROM $usersTable WHERE $userId = $authorId)", "user_name"];
                },

            ] + parent::getRawAttributes();
    }
}
