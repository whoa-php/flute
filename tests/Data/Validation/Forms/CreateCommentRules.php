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

namespace Whoa\Tests\Flute\Data\Validation\Forms;

use Whoa\Flute\Contracts\Validation\FormRulesInterface;
use Whoa\Flute\Validation\Rules\UuidRulesTrait as uR;
use Whoa\Tests\Flute\Data\Models\Comment;
use Whoa\Validation\Rules as r;

/**
 * @package Whoa\Tests
 */
class CreateCommentRules implements FormRulesInterface
{
    use uR;

    /**
     * @inheritdoc
     */
    public static function getAttributeRules(): array
    {
        return [
            Comment::FIELD_TEXT => r::isString(r::stringLengthMax(Comment::LENGTH_TEXT)),
            Comment::FIELD_UUID => uR::stringToUuid(uR::isUuid()),
        ];
    }
}
