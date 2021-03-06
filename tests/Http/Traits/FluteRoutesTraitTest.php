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

namespace Whoa\Tests\Flute\Http\Traits;

use Exception;
use Whoa\Contracts\Routing\GroupInterface;
use Whoa\Flute\Http\Traits\FluteRoutesTrait;
use Whoa\Tests\Flute\Data\Http\ApiCategoriesController;
use Whoa\Tests\Flute\Data\Http\WebCategoriesController;
use Whoa\Tests\Flute\Data\Schemas\CategorySchema;
use Whoa\Tests\Flute\TestCase;
use Mockery;
use Mockery\Mock;

/**
 * @package Whoa\Tests\Flute
 */
class FluteRoutesTraitTest extends TestCase
{
    use FluteRoutesTrait;

    /**
     * Test helper method.
     * @throws Exception
     */
    public function testControllerMethod(): void
    {
        /** @var Mock $group */
        $group = Mockery::mock(GroupInterface::class);

        $group->shouldReceive('get')->times(3)->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('post')->times(3)->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('getUriPrefix')->times(1)->withNoArgs()->andReturn('');

        /** @var GroupInterface $group */

        $this->webController($group, '/categories/', WebCategoriesController::class);

        // mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test helper method.
     * @throws Exception
     */
    public function testApiControllerMethod(): void
    {
        /** @var Mock $group */
        $group = Mockery::mock(GroupInterface::class);

        $group->shouldReceive('get')->twice()->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('post')->once()->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('patch')->once()->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('delete')->once()->withAnyArgs()->andReturnSelf();
        $group->shouldReceive('getUriPrefix')->times(1)->withNoArgs()->andReturn('');

        /** @var GroupInterface $group */

        $this->apiController($group, CategorySchema::TYPE, ApiCategoriesController::class);

        // mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test helper method.
     * @throws Exception
     */
    public function testRelationshipMethod(): void
    {
        /** @var Mock $group */
        $group = Mockery::mock(GroupInterface::class);

        $group->shouldReceive('get')->twice()->withAnyArgs()->andReturnSelf();

        /** @var GroupInterface $group */

        $this->relationship(
            $group,
            CategorySchema::TYPE,
            CategorySchema::REL_CHILDREN,
            ApiCategoriesController::class,
            'readChildren'
        );

        // mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test helper method.
     * @throws Exception
     */
    public function testAddInRelationshipMethod(): void
    {
        /** @var Mock $group */
        $group = Mockery::mock(GroupInterface::class);

        $group->shouldReceive('post')->once()->withAnyArgs()->andReturnSelf();

        /** @var GroupInterface $group */

        $this->addInRelationship(
            $group,
            CategorySchema::TYPE,
            CategorySchema::REL_CHILDREN,
            ApiCategoriesController::class,
            'readChildren'
        );

        // mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test helper method.
     * @throws Exception
     */
    public function testRemoveInRelationshipMethod(): void
    {
        /** @var Mock $group */
        $group = Mockery::mock(GroupInterface::class);

        $group->shouldReceive('delete')->once()->withAnyArgs()->andReturnSelf();

        /** @var GroupInterface $group */

        $this->removeInRelationship(
            $group,
            CategorySchema::TYPE,
            CategorySchema::REL_CHILDREN,
            ApiCategoriesController::class,
            'readChildren'
        );

        // mockery will do checks when the test finished
        $this->assertTrue(true);
    }

    /**
     * Test how predictable/stable generated route names are.
     * @return void
     */
    public function testRouteNamePredictability(): void
    {
        $this->assertEquals('/::index', static::routeName('', '', 'index'));
        $this->assertEquals('/::index', static::routeName('/', '', 'index'));
        $this->assertEquals('/::index', static::routeName('', '/', 'index'));
        $this->assertEquals('/::index', static::routeName('/', '/', 'index'));
    }
}
