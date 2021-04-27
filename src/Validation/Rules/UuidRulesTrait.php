<?php

/**
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

namespace Whoa\Flute\Validation\Rules;

use Whoa\Flute\Validation\JsonApi\Rules\IsUuid;
use Whoa\Flute\Validation\JsonApi\Rules\StringToUuid;
use Whoa\Validation\Contracts\Rules\RuleInterface;
use Whoa\Validation\Rules\Generic\AndOperator;

/**
 * @package Whoa\Flute
 */
trait UuidRulesTrait
{
    /**
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    public static function isUuid(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new IsUuid() : new AndOperator(new IsUuid(), $next);
    }

    /**
     * @param RuleInterface|null $next
     *
     * @return RuleInterface
     */
    public static function stringToUuid(RuleInterface $next = null): RuleInterface
    {
        return $next === null ? new StringToUuid() : new AndOperator(new StringToUuid(), $next);
    }
}
