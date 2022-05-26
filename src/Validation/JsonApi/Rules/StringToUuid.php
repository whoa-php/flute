<?php

/**
 * Copyright 2021-2022 info@whoaphp.com
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

declare(strict_types=1);

namespace Whoa\Flute\Validation\JsonApi\Rules;

use Whoa\Flute\Contracts\Validation\ErrorCodes;
use Whoa\Flute\L10n\Messages;
use Whoa\Validation\Contracts\Execution\ContextInterface;
use Whoa\Validation\Rules\ExecuteRule;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @package Whoa\Flute
 */
final class StringToUuid extends ExecuteRule
{
    /**
     * @inheritDoc
     */
    public static function execute($value, ContextInterface $context, $extras = null): array
    {
        if (is_string($value) === true && Uuid::isValid($value) === true) {
            $reply = StringToUuid::createSuccessReply(Uuid::fromString($value));
        } elseif ($value instanceof UuidInterface) {
            $reply = StringToUuid::createSuccessReply($value);
        } else {
            $reply = StringToUuid::createErrorReply(
                $context,
                $value,
                ErrorCodes::IS_UUID,
                Messages::IS_UUID,
                []
            );
        }

        return $reply;
    }
}
