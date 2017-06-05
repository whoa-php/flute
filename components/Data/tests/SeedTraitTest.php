<?php namespace Limoncello\Tests\Data;

/**
 * Copyright 2015-2017 info@neomerx.com
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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Data\SeedInterface;
use Limoncello\Data\Seeds\SeedTrait;
use Limoncello\Tests\Data\Data\TestContainer;
use Mockery;
use Mockery\Mock;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;

/**
 * @package Limoncello\Tests\Core
 */
class SeedTraitTest extends TestCase implements SeedInterface
{
    use SeedTrait;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Test seeds.
     */
    public function testSeeds()
    {
        $modelClass = 'TestClass';
        $tableName  = 'table_name';
        $columnName = 'value';

        $modelSchemes = Mockery::mock(ModelSchemeInfoInterface::class);

        $this->init($this->createContainer($modelSchemes));
        $this->prepareTable($modelSchemes, $modelClass, $tableName);

        $manager = $this->connection->getSchemaManager();
        $table   = new Table(
            $tableName,
            [new Column($columnName, Type::getType(Type::STRING))]
        );
        $table->addUniqueIndex([$columnName]);
        $manager->createTable($table);

        $this->assertTrue(is_string($this->now()));

        $this->assertCount(0, $this->readModelsData($modelClass));

        $this->seedModelsData(1, $modelClass, function () use ($columnName) {
            return [$columnName => 'value1'];
        });
        $this->assertCount(1, $this->readModelsData($modelClass));

        $this->seedModelData($modelClass, [$columnName => 'value2']);
        $this->assertCount(2, $this->readModelsData($modelClass));

        $this->assertSame('2', $this->getLastInsertId());

        // inserting non-unique row will be ignored
        $this->seedModelData($modelClass, [$columnName => 'value2']);
        $this->assertCount(2, $this->readModelsData($modelClass));
    }

    /**
     * @param MockInterface $modelSchemes
     *
     * @return ContainerInterface
     */
    private function createContainer(MockInterface $modelSchemes): ContainerInterface
    {
        $container                    = new TestContainer();
        $container[Connection::class] = $this->connection = $this->createConnection();

        $container[ModelSchemeInfoInterface::class] = $modelSchemes;

        return $container;
    }

    /**
     * @return Connection
     */
    private function createConnection(): Connection
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///', 'memory' => true]);
        $this->assertNotSame(false, $connection->exec('PRAGMA foreign_keys = ON;'));

        return $connection;
    }

    /**
     * @param MockInterface $mock
     * @param string        $modelClass
     * @param string        $tableName
     *
     * @return Mock
     */
    private function prepareTable($mock, string $modelClass, string $tableName)
    {
        /** @var Mock $mock */
        $mock->shouldReceive('getTable')->zeroOrMoreTimes()->with($modelClass)->andReturn($tableName);

        return $mock;
    }
}