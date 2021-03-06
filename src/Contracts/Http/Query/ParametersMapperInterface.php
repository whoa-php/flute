<?php

/**
 * Copyright 2015-2019 info@neomerx.com
 * Modification Copyright 2021-2022 info@dreamsbond.com
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

namespace Whoa\Flute\Contracts\Http\Query;

use Whoa\Flute\Contracts\Api\CrudInterface;
use Whoa\Flute\Contracts\Validation\JsonApiQueryParserInterface;

/**
 * @package Whoa\Flute
 */
interface ParametersMapperInterface
{
    /**
     * @param string $resourceType
     * @return self
     */
    public function selectRootSchemaByResourceType(string $resourceType): self;

    /**
     * @param iterable $filters
     * @return self
     */
    public function withFilters(iterable $filters): self;

    /**
     * @param iterable $sorts
     * @return self
     */
    public function withSorts(iterable $sorts): self;

    /**
     * @param iterable $includes
     * @return self
     */
    public function withIncludes(iterable $includes): self;

    /**
     * @return iterable
     */
    public function getMappedFilters(): iterable;

    /**
     * @return iterable
     */
    public function getMappedSorts(): iterable;

    /**
     * @return iterable
     */
    public function getMappedIncludes(): iterable;

    /**
     * @param JsonApiQueryParserInterface $parser
     * @param CrudInterface $api
     * @return CrudInterface
     */
    public function applyQueryParameters(
        JsonApiQueryParserInterface $parser,
        CrudInterface $api
    ): CrudInterface;
}
