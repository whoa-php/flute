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

namespace Whoa\Flute\Contracts\Validation;

use Whoa\Validation\Contracts\Errors\ErrorCodes as BaseErrorCodes;

/**
 * @package Whoa\Flute
 */
interface ErrorCodes extends BaseErrorCodes
{
    /** @var string Message code */
    public const INVALID_ATTRIBUTES = self::LAST + 1;

    /** @var string Message code */
    public const TYPE_MISSING = self::INVALID_ATTRIBUTES + 1;

    /** Message code */
    public const UNKNOWN_ATTRIBUTE = self::TYPE_MISSING + 1;

    /** @var string Message code */
    public const INVALID_RELATIONSHIP_TYPE = self::UNKNOWN_ATTRIBUTE + 1;

    /** @var string Message code */
    public const INVALID_RELATIONSHIP = self::INVALID_RELATIONSHIP_TYPE + 1;

    /** @var string Message code */
    public const UNKNOWN_RELATIONSHIP = self::INVALID_RELATIONSHIP + 1;

    /** @var string Message code */
    public const EXIST_IN_DATABASE_SINGLE = self::UNKNOWN_RELATIONSHIP + 1;

    /** @var string Message code */
    public const EXIST_IN_DATABASE_MULTIPLE = self::EXIST_IN_DATABASE_SINGLE + 1;

    /** @var string Message code */
    public const UNIQUE_IN_DATABASE_SINGLE = self::EXIST_IN_DATABASE_MULTIPLE + 1;

    /** @var string Message code */
    public const INVALID_OPERATION_ARGUMENTS = self::UNIQUE_IN_DATABASE_SINGLE + 1;

    // Special code for those who extend this enum

    /** @var string Message code */
    public const IS_UUID = self::INVALID_OPERATION_ARGUMENTS + 1;

    /** @var string Message code */
    public const FLUTE_LAST = self::IS_UUID;
}
