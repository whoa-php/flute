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

namespace Whoa\Flute\L10n;

/**
 * @package Whoa\Flute
 */
interface Messages extends \Whoa\Validation\I18n\Messages
{
    /** @var string Namespace name for message keys. */
    public const NAMESPACE_NAME = 'Whoa.Flute';

    /** @var string Validation Message Template */
    public const MSG_ERR_INVALID_ARGUMENT = 'Invalid argument.';

    /** @var string Validation Message Template */
    public const MSG_ERR_INVALID_JSON_DATA_IN_REQUEST = 'Invalid JSON data in request.';

    /** @var string Validation Message Template */
    public const MSG_ERR_CANNOT_CREATE_NON_UNIQUE_RESOURCE = 'Cannot create non unique resource.';

    /** @var string Validation Message Template */
    public const MSG_ERR_CANNOT_UPDATE_WITH_UNIQUE_CONSTRAINT_VIOLATION =
        'Cannot update resource because unique constraint violated.';

    /** @var string Validation Message Template */
    public const TYPE_MISSING = 'JSON API type should be specified.';

    /** @var string Validation Message Template */
    public const INVALID_ATTRIBUTES = 'JSON API attributes are invalid.';

    /** @var string Validation Message Template */
    public const UNKNOWN_ATTRIBUTE = 'Unknown JSON API attribute.';

    /** @var string Validation Message Template */
    public const INVALID_RELATIONSHIP_TYPE = 'The value should be a valid JSON API relationship type.';

    /** @var string Validation Message Template */
    public const INVALID_RELATIONSHIP = 'Invalid JSON API relationship.';

    /** @var string Validation Message Template */
    public const UNKNOWN_RELATIONSHIP = 'Unknown JSON API relationship.';

    /** @var string Validation Message Template */
    public const EXIST_IN_DATABASE_SINGLE = 'The value should be a valid identifier.';

    /** @var string Validation Message Template */
    public const EXIST_IN_DATABASE_MULTIPLE = 'The value should be valid identifiers.';

    /** @var string Validation Message Template */
    public const UNIQUE_IN_DATABASE_SINGLE = 'The value should be a unique identifier.';

    /** @var string Validation Message Template */
    public const INVALID_OPERATION_ARGUMENTS = 'Invalid Operation Arguments.';

    /** @var string Validation Message Template */
    public const IS_UUID = 'The value should be a valid UUID.';
}
