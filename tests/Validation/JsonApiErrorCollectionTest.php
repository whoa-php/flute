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

namespace Whoa\Tests\Flute\Validation;

use Exception;
use Whoa\Flute\Contracts\Validation\ErrorCodes;
use Whoa\Flute\L10n\Messages;
use Whoa\Flute\Validation\JsonApi\Execution\JsonApiErrorCollection;
use Whoa\Tests\Flute\Data\L10n\FormatterFactory;
use Whoa\Tests\Flute\TestCase;
use Whoa\Validation\Errors\Error;

/**
 * @package Whoa\Tests\Flute
 */
class JsonApiErrorCollectionTest extends TestCase
{
    /**
     * Test adding errors.
     *
     * @throws Exception
     */
    public function testAddIdAndTypeErrors(): void
    {
        $formatter  = (new FormatterFactory())->createFormatter(Messages::NAMESPACE_NAME);
        $collection = new JsonApiErrorCollection($formatter);

        $this->assertCount(0, $collection);

        $collection->addValidationIdError(
            new Error('id', 'whatever', ErrorCodes::INVALID_VALUE, Messages::INVALID_VALUE, [])
        );
        $collection->addValidationTypeError(
            new Error('id', 'whatever', ErrorCodes::TYPE_MISSING, Messages::TYPE_MISSING, [])
        );
        $collection->addValidationAttributeError(
            new Error('uuid', 'whatever', ErrorCodes::IS_UUID, Messages::IS_UUID, [])
        );

        $this->assertCount(3, $collection);
        $errors = $collection->getArrayCopy();

        $this->assertEquals(['pointer' => '/data/id'], $errors[0]->getSource());
        $this->assertEquals('The value is invalid.', $errors[0]->getDetail());

        $this->assertEquals(['pointer' => '/data/type'], $errors[1]->getSource());
        $this->assertEquals('JSON API type should be specified.', $errors[1]->getDetail());

        $this->assertEquals(['pointer' => '/data/attributes/uuid'], $errors[2]->getSource());
        $this->assertEquals('The value should be a valid UUID.', $errors[2]->getDetail());
    }
}
