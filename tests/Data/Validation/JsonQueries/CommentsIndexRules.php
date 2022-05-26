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
use Whoa\Tests\Flute\Data\Models\Comment;
use Whoa\Tests\Flute\Data\Schemas\CommentSchema;
use Whoa\Tests\Flute\Data\Schemas\PostSchema;
use Whoa\Validation\Contracts\Rules\RuleInterface;
use Whoa\Validation\Rules as r;

/**
 * @package Whoa\Tests
 */
class CommentsIndexRules implements JsonApiQueryRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getIdentityRule(): ?RuleInterface
    {
        return r::success();
    }

    /**
     * @inheritdoc
     */
    public static function getFilterRules(): ?array
    {
        return [
            SchemaInterface::RESOURCE_ID => r::stringToInt(r::inValues([3, 5, 7, 9])),
            CommentSchema::ATTR_TEXT => r::isString(r::stringLengthMin(2)),
            CommentSchema::ATTR_INT => r::stringToInt(r::between(1, 10)),
            CommentSchema::ATTR_BOOL => r::stringToBool(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getFieldSetRules(): ?array
    {
        return [
            CommentSchema::TYPE =>
                r::isString(r::inValues([CommentSchema::ATTR_TEXT, CommentSchema::REL_USER, CommentSchema::REL_POST])),
            PostSchema::TYPE =>
                r::isString(r::inValues([PostSchema::ATTR_TITLE, PostSchema::REL_USER, PostSchema::REL_COMMENTS])),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getSortsRule(): ?RuleInterface
    {
        return r::isString(r::inValues([Comment::FIELD_TEXT, Comment::FIELD_FLOAT]));
    }

    /**
     * @inheritdoc
     */
    public static function getIncludesRule(): ?RuleInterface
    {
        return r::isString(r::inValues([Comment::REL_USER, Comment::REL_POST]));
    }

    /**
     * @inheritdoc
     */
    public static function getPageOffsetRule(): ?RuleInterface
    {
        return r::ifX(
            r::IS_NULL_CALLABLE,
            r::value(0),
            r::stringToInt(r::moreOrEquals(0))
        );
    }

    /**
     * @inheritdoc
     */
    public static function getPageLimitRule(): ?RuleInterface
    {
        return r::ifX(
            r::IS_NULL_CALLABLE,
            r::value(30),
            r::stringToInt(r::between(1, 50))
        );
    }
}
