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

namespace Whoa\Flute\Contracts\Models;


/**
 * @package Whoa\Flute
 */
interface PaginatedDataInterface
{
    /**
     * @return mixed
     */
    public function getData();

    /**
     * @return bool
     */
    public function isCollection(): bool;

    /**
     * @return self
     */
    public function markAsCollection(): self;

    /**
     * @return self
     */
    public function markAsSingleItem(): self;

    /**
     * @return bool
     */
    public function hasMoreItems(): bool;

    /**
     * @return self
     */
    public function markHasMoreItems(): self;

    /**
     * @return self
     */
    public function markHasNoMoreItems(): self;

    /**
     * @return int|null
     */
    public function getOffset(): ?int;

    /**
     * @param int|null $offset
     * @return self
     */
    public function setOffset(int $offset = null): self;

    /**
     * @return int|null
     */
    public function getLimit(): ?int;

    /**
     * @param int|null $size
     * @return self
     */
    public function setLimit(int $size = null): self;
}
