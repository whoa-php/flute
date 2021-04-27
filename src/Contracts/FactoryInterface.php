<?php declare (strict_types = 1);

namespace Whoa\Flute\Contracts;

/**
 * Copyright 2015-2019 info@neomerx.com
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
use Whoa\Contracts\Data\ModelSchemaInfoInterface;
use Whoa\Flute\Adapters\ModelQueryBuilder;
use Whoa\Flute\Contracts\Api\CrudInterface;
use Whoa\Flute\Contracts\Encoder\EncoderInterface;
use Whoa\Flute\Contracts\Models\ModelStorageInterface;
use Whoa\Flute\Contracts\Models\PaginatedDataInterface;
use Whoa\Flute\Contracts\Models\TagStorageInterface;
use Whoa\Flute\Contracts\Schema\JsonSchemasInterface;
use Neomerx\JsonApi\Schema\ErrorCollection;

/**
 * @package Whoa\Flute
 */
interface FactoryInterface
{
    /**
     * @return ErrorCollection
     */
    public function createErrorCollection(): ErrorCollection;

    /**
     * @param Connection               $connection
     * @param string                   $modelClass
     * @param ModelSchemaInfoInterface $modelSchemas
     *
     * @return ModelQueryBuilder
     */
    public function createModelQueryBuilder(
        Connection $connection,
        string $modelClass,
        ModelSchemaInfoInterface $modelSchemas
    ): ModelQueryBuilder;

    /**
     * @param ModelSchemaInfoInterface $modelSchemas
     *
     * @return ModelStorageInterface
     */
    public function createModelStorage(ModelSchemaInfoInterface $modelSchemas): ModelStorageInterface;

    /**
     * @return TagStorageInterface
     */
    public function createTagStorage(): TagStorageInterface;

    /**
     * @param array                    $modelToSchemaMap
     * @param array                    $typeToSchemaMap
     * @param ModelSchemaInfoInterface $modelSchemas
     *
     * @return JsonSchemasInterface
     */
    public function createJsonSchemas(
        array $modelToSchemaMap,
        array $typeToSchemaMap,
        ModelSchemaInfoInterface $modelSchemas
    ): JsonSchemasInterface;

    /**
     * @param JsonSchemasInterface $schemas
     *
     * @return EncoderInterface
     */
    public function createEncoder(JsonSchemasInterface $schemas): EncoderInterface;

    /**
     * @param mixed $data
     *
     * @return PaginatedDataInterface
     */
    public function createPaginatedData($data): PaginatedDataInterface;

    /**
     * @param string $apiClass
     *
     * @return CrudInterface
     */
    public function createApi(string $apiClass): CrudInterface;
}
