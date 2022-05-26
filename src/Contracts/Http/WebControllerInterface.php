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

namespace Whoa\Flute\Contracts\Http;

use Whoa\Flute\Contracts\Http\Controller\ControllerCreateInterface as CCI;
use Whoa\Flute\Contracts\Http\Controller\ControllerDeleteInterface as CDI;
use Whoa\Flute\Contracts\Http\Controller\ControllerIndexInterface as CII;
use Whoa\Flute\Contracts\Http\Controller\ControllerInstanceInterface as CInI;
use Whoa\Flute\Contracts\Http\Controller\ControllerReadInterface as CRI;
use Whoa\Flute\Contracts\Http\Controller\ControllerUpdateInterface as CUI;

/**
 * @package Whoa\Flute
 */
interface WebControllerInterface extends CII, CCI, CRI, CUI, CDI, CInI
{
    /** URI key used in routing table */
    public const ROUTE_KEY_INDEX = 'idx';
}
