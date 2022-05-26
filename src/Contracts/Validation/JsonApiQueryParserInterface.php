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

namespace Whoa\Flute\Contracts\Validation;

use Neomerx\JsonApi\Contracts\Http\Query\BaseQueryParserInterface;

/**
 * @package Whoa\Flute
 */
interface JsonApiQueryParserInterface extends BaseQueryParserInterface
{
    /** @var string Query parameter */
    public const PARAM_IDENTITY = 'id';

    /** @var string  Query parameter */
    public const PARAM_PAGING_LIMIT = 'limit';

    /** @var string  Query parameter */
    public const PARAM_PAGING_OFFSET = 'offset';

    /**
     * @param null|string $identity
     * @param array $parameters
     * @return self
     */
    public function parse(?string $identity, array $parameters = []): self;

    /**
     * If filters are joined with `AND` (or with `OR` otherwise).
     * @return bool
     */
    public function areFiltersWithAnd(): bool;

    /**
     * @return bool
     */
    public function hasFilters(): bool;

    /**
     * @return bool
     */
    public function hasFields(): bool;

    /**
     * @return bool
     */
    public function hasIncludes(): bool;

    /**
     * @return bool
     */
    public function hasSorts(): bool;

    /**
     * @return bool
     */
    public function hasPaging(): bool;

    /**
     * @return mixed
     */
    public function getIdentity();

    /**
     * @return iterable
     */
    public function getFilters(): iterable;

    /**
     * @return int|null
     */
    public function getPagingOffset(): ?int;

    /**
     * @return int|null
     */
    public function getPagingLimit(): ?int;
}
