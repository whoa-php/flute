<?php

/**
 * Copyright 2015-2019 info@neomerx.com
 * Copyright 2021 info@whoaphp.com
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

/**
 * @package Whoa\Tests\Flute
 */
class StringPKModel extends Model
{
    /** @inheritdoc */
    const TABLE_NAME = 'string_pk_table';

    /** @inheritdoc */
    const FIELD_ID = 'string_pk';

    /** Field name */
    const FIELD_NAME = 'name';

    /**
     * @inheritdoc
     */
    public static function getAttributeTypes(): array
    {
        return [
            self::FIELD_ID   => Types::STRING,
            self::FIELD_NAME => Types::STRING,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeLengths(): array
    {
        return [
            self::FIELD_ID   => 255,
            self::FIELD_NAME => 255,
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getRelationships(): array
    {
        return [];
    }
}
