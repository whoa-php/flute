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

namespace Whoa\Tests\Flute\Data\Http;

use Whoa\Flute\Validation\JsonApi\Rules\DefaultQueryValidationRules;
use Whoa\Tests\Flute\Data\Api\CategoriesApi as Api;
use Whoa\Tests\Flute\Data\Models\Category as Model;
use Whoa\Tests\Flute\Data\Schemas\CategorySchema as Schema;
use Whoa\Tests\Flute\Data\Validation\JsonQueries\ReadCategoriesQueryRules;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package Whoa\Tests\Flute
 */
class ApiCategoriesController extends ApiBaseController
{
    /** @inheritdoc */
    public const API_CLASS = Api::class;

    /** @inheritdoc */
    public const SCHEMA_CLASS = Schema::class;

    /** @inheritdoc */
    public const ON_INDEX_QUERY_VALIDATION_RULES_CLASS = ReadCategoriesQueryRules::class;

    /** @inheritdoc */
    public const ON_READ_QUERY_VALIDATION_RULES_CLASS = ReadCategoriesQueryRules::class;

    /**
     * @param array $routeParams
     * @param ContainerInterface $container
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function readChildren(
        array $routeParams,
        ContainerInterface $container,
        ServerRequestInterface $request
    ): ResponseInterface {
        return static::readRelationship(
            (string)$routeParams[static::ROUTE_KEY_INDEX],
            Model::REL_CHILDREN,
            DefaultQueryValidationRules::class,
            $container,
            $request
        );
    }
}
