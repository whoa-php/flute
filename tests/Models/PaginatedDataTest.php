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

namespace Whoa\Tests\Flute\Models;

use Exception;
use Whoa\Flute\Models\PaginatedData;
use PHPUnit\Framework\TestCase;

/**
 * @package Whoa\Tests\Flute
 */
class PaginatedDataTest extends TestCase
{
    /**
     * Test getters and setters.
     * @throws Exception
     */
    public function testGettersAndSetters()
    {
        $paginated = (new PaginatedData('whatever'))
            ->setLimit(1)
            ->setOffset(2)
            ->markAsSingleItem()
            ->markHasNoMoreItems();

        $this->assertEquals('whatever', $paginated->getData());
        $this->assertEquals(1, $paginated->getLimit());
        $this->assertEquals(2, $paginated->getOffset());
        $this->assertFalse($paginated->isCollection());
        $this->assertFalse($paginated->hasMoreItems());

        $paginated
            ->markAsCollection()
            ->markHasMoreItems();

        $this->assertTrue($paginated->isCollection());
        $this->assertTrue($paginated->hasMoreItems());
    }
}
