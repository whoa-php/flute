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

namespace Whoa\Tests\Flute\Data\Validation\JsonQueries;

use Whoa\Flute\Contracts\Schema\SchemaInterface;
use Whoa\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Whoa\Flute\Validation\JsonApi\Rules\DefaultQueryValidationRules;
use Whoa\Tests\Flute\Data\Schemas\CommentSchema as Schema;
use Whoa\Tests\Flute\Data\Schemas\EmotionSchema;
use Whoa\Tests\Flute\Data\Schemas\PostSchema;
use Whoa\Tests\Flute\Data\Validation\AppRules as v;
use Whoa\Validation\Contracts\Rules\RuleInterface;

/**
 * @package Whoa\Tests\Flute
 */
class ReadCommentsQueryRules implements JsonApiQueryRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getIdentityRule(): ?RuleInterface
    {
        return v::success();
    }

    /**
     * @inheritdoc
     */
    public static function getFilterRules(): ?array
    {
        return [
            SchemaInterface::RESOURCE_ID => v::stringToInt(v::moreThan(0)),
            Schema::ATTR_TEXT => v::isString(v::stringLengthMin(1)),
            Schema::REL_POST => v::stringToInt(v::moreThan(0)),
            Schema::REL_POST . '.' . PostSchema::ATTR_TEXT => v::isString(v::stringLengthMin(1)),
            Schema::REL_EMOTIONS => v::stringToInt(v::moreThan(0)),
            Schema::REL_EMOTIONS . '.' . EmotionSchema::ATTR_NAME => v::isString(v::stringLengthMin(1)),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getFieldSetRules(): ?array
    {
        return [
            Schema::TYPE => v::isString(v::inValues([Schema::ATTR_TEXT, Schema::REL_USER, Schema::REL_POST])),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getSortsRule(): ?RuleInterface
    {
        return v::isString(
            v::inValues([
                SchemaInterface::RESOURCE_ID,
                Schema::REL_POST,
            ])
        );
    }

    /**
     * @inheritdoc
     */
    public static function getIncludesRule(): ?RuleInterface
    {
        return v::isString(
            v::inValues([
                Schema::REL_USER,
            ])
        );
    }

    /**
     * @inheritdoc
     */
    public static function getPageOffsetRule(): ?RuleInterface
    {
        return DefaultQueryValidationRules::getPageOffsetRule();
    }

    /**
     * @inheritdoc
     */
    public static function getPageLimitRule(): ?RuleInterface
    {
        return DefaultQueryValidationRules::getPageLimitRule();
    }
}
