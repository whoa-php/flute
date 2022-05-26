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

namespace Whoa\Flute\Validation\Rules;

use Whoa\Flute\Validation\JsonApi\Rules\ExistInDbTableMultipleWithDoctrineRule;
use Whoa\Flute\Validation\JsonApi\Rules\ExistInDbTableSingleWithDoctrineRule;
use Whoa\Flute\Validation\JsonApi\Rules\UniqueInDbTableSingleWithDoctrineRule;
use Whoa\Validation\Contracts\Rules\RuleInterface;
use Whoa\Validation\Rules\Generic\AndOperator;

/**
 * @package Whoa\Flute
 */
trait DatabaseRulesTrait
{
    /**
     * @param string $tableName
     * @param string $primaryName
     * @param RuleInterface|null $next
     * @return RuleInterface
     */
    public static function exists(string $tableName, string $primaryName, RuleInterface $next = null): RuleInterface
    {
        $primary = new ExistInDbTableSingleWithDoctrineRule($tableName, $primaryName);

        return $next === null ? $primary : new AndOperator($primary, $next);
    }

    /**
     * @param string $tableName
     * @param string $primaryName
     * @param RuleInterface|null $next
     * @return RuleInterface
     */
    public static function existAll(string $tableName, string $primaryName, RuleInterface $next = null): RuleInterface
    {
        $primary = new ExistInDbTableMultipleWithDoctrineRule($tableName, $primaryName);

        return $next === null ? $primary : new AndOperator($primary, $next);
    }

    /**
     * @param string $tableName
     * @param string $primaryName
     * @param RuleInterface|null $next
     * @param string|null $primaryKey
     * @return RuleInterface
     */
    public static function unique(
        string $tableName,
        string $primaryName,
        ?string $primaryKey = null,
        RuleInterface $next = null
    ): RuleInterface {
        $primary = new UniqueInDbTableSingleWithDoctrineRule($tableName, $primaryName, $primaryKey);

        return $next === null ? $primary : new AndOperator($primary, $next);
    }
}
