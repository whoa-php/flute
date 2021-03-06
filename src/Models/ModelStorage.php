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

use Whoa\Contracts\Data\ModelSchemaInfoInterface;
use Whoa\Flute\Contracts\Models\ModelStorageInterface;

use function get_class;

/**
 * @package Whoa\Flute
 */
class ModelStorage implements ModelStorageInterface
{
    /**
     * @var array
     */
    private array $models = [];

    /**
     * @var ModelSchemaInfoInterface
     */
    private ModelSchemaInfoInterface $schemas;

    /**
     * @param ModelSchemaInfoInterface $schemas
     */
    public function __construct(ModelSchemaInfoInterface $schemas)
    {
        $this->schemas = $schemas;
    }

    /**
     * @inheritdoc
     */
    public function register($model)
    {
        $registered = null;

        if ($model !== null) {
            $class = get_class($model);
            $pkName = $this->schemas->getPrimaryKey($class);
            $index = $model->{$pkName};

            $registered = $this->models[$class][$index] ?? null;
            if ($registered === null) {
                $this->models[$class][$index] = $registered = $model;
            }
        }

        return $registered;
    }

    /**
     * @inheritdoc
     */
    public function has(string $class, string $index): bool
    {
        return isset($this->models[$class][$index]);
    }

    /**
     * @inheritdoc
     */
    public function get(string $class, string $index)
    {
        return $this->models[$class][$index];
    }
}
