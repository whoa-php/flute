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

namespace Whoa\Flute\Models;

use Whoa\Flute\Contracts\Models\PaginatedDataInterface;

/**
 * @package Whoa\Flute
 */
class PaginatedData implements PaginatedDataInterface
{
    /** @var  mixed */
    private $data;

    /** @var  bool */
    private bool $isCollection = false;

    /** @var  bool */
    private bool $hasMoreItems = false;

    /** @var  int|null */
    private ?int $offset = null;

    /** @var  int|null */
    private ?int $size = null;

    /**
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    /**
     * @inheritdoc
     */
    public function markAsCollection(): PaginatedDataInterface
    {
        $this->isCollection = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function markAsSingleItem(): PaginatedDataInterface
    {
        $this->isCollection = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hasMoreItems(): bool
    {
        return $this->hasMoreItems;
    }

    /**
     * @inheritdoc
     */
    public function markHasMoreItems(): PaginatedDataInterface
    {
        $this->hasMoreItems = true;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function markHasNoMoreItems(): PaginatedDataInterface
    {
        $this->hasMoreItems = false;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * @inheritdoc
     */
    public function setOffset(int $offset = null): PaginatedDataInterface
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLimit(): ?int
    {
        return $this->size;
    }

    /**
     * @inheritdoc
     */
    public function setLimit(int $size = null): PaginatedDataInterface
    {
        $this->size = $size;

        return $this;
    }
}
