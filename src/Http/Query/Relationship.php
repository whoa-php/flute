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

namespace Whoa\Flute\Http\Query;

use Whoa\Flute\Contracts\Http\Query\RelationshipInterface;
use Whoa\Flute\Contracts\Schema\SchemaInterface;

/**
 * @package Whoa\Flute
 */
class Relationship implements RelationshipInterface
{
    /**
     * @var string
     */
    private string $nameInSchema;

    /**
     * @var string
     */
    private ?string $nameInModel;

    /**
     * @var SchemaInterface
     */
    private SchemaInterface $fromSchema;

    /**
     * @var SchemaInterface
     */
    private SchemaInterface $toSchema;

    /**
     * @param string $nameInSchema
     * @param SchemaInterface $fromSchema
     * @param SchemaInterface $toSchema
     */
    public function __construct(
        string $nameInSchema,
        SchemaInterface $fromSchema,
        SchemaInterface $toSchema
    ) {
        $this->nameInSchema = $nameInSchema;
        $this->fromSchema = $fromSchema;
        $this->toSchema = $toSchema;

        $this->nameInModel = null;
    }

    /**
     * @inheritdoc
     */
    public function getNameInSchema(): string
    {
        return $this->nameInSchema;
    }

    /**
     * @inheritdoc
     */
    public function getNameInModel(): string
    {
        if ($this->nameInModel === null) {
            $this->nameInModel = $this->getFromSchema()->getRelationshipMapping($this->getNameInSchema());
        }

        return $this->nameInModel;
    }

    /**
     * @inheritdoc
     */
    public function getFromSchema(): SchemaInterface
    {
        return $this->fromSchema;
    }

    /**
     * @inheritdoc
     */
    public function getToSchema(): SchemaInterface
    {
        return $this->toSchema;
    }
}
