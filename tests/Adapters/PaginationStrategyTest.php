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

namespace Whoa\Tests\Flute\Adapters;

use Whoa\Flute\Api\BasicRelationshipPaginationStrategy as PS;
use Whoa\Tests\Flute\TestCase;

/**
 * @package Whoa\Tests\Flute
 */
class PaginationStrategyTest extends TestCase
{
    /**
     * Test get methods.
     */
    public function testGetMethods(): void
    {
        $strategy = new PS($defaultPageSize = 30);

        $this->assertEquals(
            [0, $defaultPageSize],
            $strategy->getParameters('RootClass', 'TargetClass', 'relationship1.relationship2', 'relationship3')
        );
        $this->assertEquals($defaultPageSize, $strategy->getDefaultPageSize());
    }
}
