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

namespace Whoa\Tests\Flute\Data\Api;

use Whoa\Tests\Flute\Data\Models\Model;
use Whoa\Tests\Flute\Data\Models\StringPKModel;

/**
 * @package Whoa\Tests\Flute
 */
class StringPKModelApi extends AppCrud
{
    public const MODEL_CLASS = StringPKModel::class;

    /**
     * @inheritdoc
     */
    protected function filterAttributesOnCreate(?string $index, iterable $attributes): iterable
    {
        foreach (parent::filterAttributesOnCreate($index, $attributes) as $attribute => $value) {
            if ($attribute !== Model::FIELD_CREATED_AT) {
                yield $attribute => $value;
            }
        }
    }
}
