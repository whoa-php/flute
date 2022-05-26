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

namespace Whoa\Tests\Flute\Data\Validation;

use Whoa\Flute\Validation\Rules\ApiRulesTrait;
use Whoa\Flute\Validation\Rules\DatabaseRulesTrait;
use Whoa\Flute\Validation\Rules\RelationshipRulesTrait;
use Whoa\Flute\Validation\Rules\UuidRulesTrait;
use Whoa\Tests\Flute\Data\Api\PostsApi;
use Whoa\Tests\Flute\Data\Models\Board;
use Whoa\Tests\Flute\Data\Models\Category;
use Whoa\Tests\Flute\Data\Models\Comment;
use Whoa\Tests\Flute\Data\Models\Emotion;
use Whoa\Tests\Flute\Data\Models\Post;
use Whoa\Validation\Contracts\Rules\RuleInterface;
use Whoa\Validation\Rules;

/**
 * @package Whoa\Tests\Flute
 */
class AppRules extends Rules
{
    use ApiRulesTrait;
    use DatabaseRulesTrait;
    use RelationshipRulesTrait;
    use UuidRulesTrait;

    /**
     * @return RuleInterface
     */
    public static function boardId(): RuleInterface
    {
        return static::stringToInt(static::exists(Board::TABLE_NAME, Board::FIELD_ID));
    }

    /**
     * @return RuleInterface
     */
    public static function postId(): RuleInterface
    {
        return static::stringToInt(static::exists(Post::TABLE_NAME, Post::FIELD_ID));
    }

    /**
     * @return RuleInterface
     */
    public static function readablePost(): RuleInterface
    {
        return static::stringToInt(static::readable(PostsApi::class));
    }

    /**
     * @return RuleInterface
     */
    public static function commentId(): RuleInterface
    {
        return static::stringToInt(static::exists(Comment::TABLE_NAME, Comment::FIELD_ID));
    }

    /**
     * @return RuleInterface
     */
    public static function categoryId(): RuleInterface
    {
        return static::stringToInt(static::exists(Category::TABLE_NAME, Category::FIELD_ID));
    }

    /**
     * @return RuleInterface
     */
    public static function emotionId(): RuleInterface
    {
        return static::stringToInt(static::exists(Emotion::TABLE_NAME, Emotion::FIELD_ID));
    }

    /**
     * @return RuleInterface
     */
    public static function emotionIds(): RuleInterface
    {
        return static::stringArrayToIntArray(
            static::existAll(Emotion::TABLE_NAME, Emotion::FIELD_ID)
        );
    }

    /**
     * @return RuleInterface
     */
    public static function readableEmotions(): RuleInterface
    {
        return static::stringArrayToIntArray(static::readableAll(PostsApi::class));
    }

    /**
     * @return RuleInterface
     */
    public static function userIdWithoutCheckInDatabase(): RuleInterface
    {
        return static::stringToInt();
    }
}
