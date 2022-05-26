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

use Whoa\Contracts\Application\ModelInterface;
use Whoa\Contracts\Data\TimestampFields;
use Whoa\Contracts\Data\UuidFields;

/**
 * @package Whoa\Tests\Flute
 */
abstract class Model implements ModelInterface
{
    /** @var string|null Table name */
    public const TABLE_NAME = null;

    /** @var string|null Primary key */
    public const FIELD_ID = null;

    /** Field name */
    public const FIELD_UUID = UuidFields::FIELD_UUID;

    /** Field name */
    public const FIELD_CREATED_AT = TimestampFields::FIELD_CREATED_AT;

    /** Field name */
    public const FIELD_UPDATED_AT = TimestampFields::FIELD_UPDATED_AT;

    /** Field name */
    public const FIELD_DELETED_AT = TimestampFields::FIELD_DELETED_AT;

    /**
     * @inheritdoc
     */
    public static function getTableName(): string
    {
        return static::TABLE_NAME;
    }

    /**
     * @inheritdoc
     */
    public static function getPrimaryKeyName(): string
    {
        return static::FIELD_ID;
    }

    /**
     * @inheritdoc
     */
    public static function getRawAttributes(): array
    {
        return [];
    }

    public static function getVirtualAttributes(): array
    {
        return [];
    }
}
