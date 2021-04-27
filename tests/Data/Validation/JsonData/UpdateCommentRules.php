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

namespace Whoa\Tests\Flute\Data\Validation\JsonData;

use Whoa\Flute\Contracts\Validation\JsonApiDataRulesInterface;
use Whoa\Tests\Flute\Data\Schemas\CommentSchema as Schema;
use Whoa\Tests\Flute\Data\Schemas\EmotionSchema;
use Whoa\Tests\Flute\Data\Schemas\PostSchema;
use Whoa\Tests\Flute\Data\Validation\AppRules as v;
use Whoa\Validation\Contracts\Rules\RuleInterface;

/**
 * @package Whoa\Tests\Flute
 */
class UpdateCommentRules implements JsonApiDataRulesInterface
{
    /**
     * @inheritdoc
     */
    public static function getTypeRule(): RuleInterface
    {
        return v::equals(Schema::TYPE);
    }

    /**
     * @inheritdoc
     */
    public static function getIdRule(): RuleInterface
    {
        return v::commentId();
    }

    /**
     * @inheritdoc
     */
    public static function getAttributeRules(): array
    {
        return [
            Schema::ATTR_TEXT => v::isString(),
            Schema::ATTR_UUID => v::stringToUuid(v::isUuid()),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getToOneRelationshipRules(): array
    {
        return [
            Schema::REL_POST => v::toOneRelationship(PostSchema::TYPE, v::stringToInt(v::postId())),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getToManyRelationshipRules(): array
    {
        return [
            Schema::REL_EMOTIONS =>
                v::toManyRelationship(EmotionSchema::TYPE, v::stringArrayToIntArray(v::emotionIds())),
        ];
    }
}
