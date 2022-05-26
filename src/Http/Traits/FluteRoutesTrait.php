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

namespace Whoa\Flute\Http\Traits;

use Whoa\Contracts\Routing\GroupInterface;
use Whoa\Contracts\Routing\RouteInterface;
use Whoa\Flute\Contracts\Http\Controller\ControllerCreateInterface as CCI;
use Whoa\Flute\Contracts\Http\Controller\ControllerDeleteInterface as CDI;
use Whoa\Flute\Contracts\Http\Controller\ControllerIndexInterface as CII;
use Whoa\Flute\Contracts\Http\Controller\ControllerInstanceInterface as INI;
use Whoa\Flute\Contracts\Http\Controller\ControllerReadInterface as CRI;
use Whoa\Flute\Contracts\Http\Controller\ControllerUpdateInterface as CUI;
use Whoa\Flute\Contracts\Http\JsonApiControllerInterface as JCI;
use Whoa\Flute\Contracts\Http\WebControllerInterface as FCI;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;

use function assert;
use function class_exists;
use function class_implements;
use function in_array;
use function substr;

/**
 * @package Whoa\Flute
 */
trait FluteRoutesTrait
{
    /**
     * @param GroupInterface $group
     * @param string $resourceName
     * @param string $controllerClass
     * @return GroupInterface
     */
    protected static function apiController(
        GroupInterface $group,
        string $resourceName,
        string $controllerClass
    ): GroupInterface {
        assert(class_exists($controllerClass) === true);

        $groupPrefix = $group->getUriPrefix();
        $indexSlug = '/{' . JCI::ROUTE_KEY_INDEX . '}';
        $params = function (string $method) use ($groupPrefix, $resourceName): array {
            return [RouteInterface::PARAM_NAME => static::routeName($groupPrefix, $resourceName, $method)];
        };
        $handler = function (string $method) use ($controllerClass): array {
            return [$controllerClass, $method];
        };

        // if the class implements any of CRUD methods a corresponding route will be added
        $classInterfaces = class_implements($controllerClass);
        if (in_array(CII::class, $classInterfaces) === true) {
            $group->get(
                $resourceName,
                $handler(CII::METHOD_INDEX),
                $params(
                    CII::METHOD_INDEX
                )
            );
        }
        if (in_array(CCI::class, $classInterfaces) === true) {
            $group->post($resourceName, $handler(CCI::METHOD_CREATE), $params(CCI::METHOD_CREATE));
        }
        if (in_array(CRI::class, $classInterfaces) === true) {
            $group->get(
                $resourceName . $indexSlug,
                $handler(CRI::METHOD_READ),
                $params(
                    CRI::METHOD_READ
                )
            );
        }
        if (in_array(CUI::class, $classInterfaces) === true) {
            $group->patch($resourceName . $indexSlug, $handler(CUI::METHOD_UPDATE), $params(CUI::METHOD_UPDATE));
        }
        if (in_array(CDI::class, $classInterfaces) === true) {
            $group->delete($resourceName . $indexSlug, $handler(CDI::METHOD_DELETE), $params(CDI::METHOD_DELETE));
        }

        return $group;
    }

    /**
     * @param GroupInterface $group
     * @param string $subUri
     * @param string $controllerClass
     * @param string $createSubUrl
     * @return GroupInterface
     */
    protected static function webController(
        GroupInterface $group,
        string $subUri,
        string $controllerClass,
        string $createSubUrl = '/create'
    ): GroupInterface {
        // normalize url to have predictable URLs and their names
        if ($subUri[-1] === '/') {
            $subUri = substr($subUri, 0, -1);
        }

        $groupPrefix = $group->getUriPrefix();
        $slugged = $subUri . '/{' . FCI::ROUTE_KEY_INDEX . '}';
        $params = function (string $method) use ($groupPrefix, $subUri): array {
            return [RouteInterface::PARAM_NAME => static::routeName($groupPrefix, $subUri, $method)];
        };
        $handler = function (string $method) use ($controllerClass): array {
            return [$controllerClass, $method];
        };

        // if the class implements any of CRUD methods a corresponding route will be added
        // as HTML forms do not support methods other than GET/POST we use POST and special URI for update and delete.
        $classInterfaces = class_implements($controllerClass);
        if (in_array(CII::class, $classInterfaces) === true) {
            $group->get($subUri, $handler(CII::METHOD_INDEX), $params(CII::METHOD_INDEX));
        }
        if (in_array(INI::class, $classInterfaces) === true) {
            $group->get($subUri . $createSubUrl, $handler(INI::METHOD_INSTANCE), $params(INI::METHOD_INSTANCE));
        }
        if (in_array(CCI::class, $classInterfaces) === true) {
            $group->post($subUri . $createSubUrl, $handler(CCI::METHOD_CREATE), $params(CCI::METHOD_CREATE));
        }
        if (in_array(CRI::class, $classInterfaces) === true) {
            $group->get($slugged, $handler(CRI::METHOD_READ), $params(CRI::METHOD_READ));
        }
        if (in_array(CUI::class, $classInterfaces) === true) {
            $group->post($slugged, $handler(CUI::METHOD_UPDATE), $params(CUI::METHOD_UPDATE));
        }
        if (in_array(CDI::class, $classInterfaces) === true) {
            $deleteUri = $slugged . '/' . CDI::METHOD_DELETE;
            $group->post($deleteUri, $handler(CDI::METHOD_DELETE), $params(CDI::METHOD_DELETE));
        }

        return $group;
    }

    /**
     * @param GroupInterface $group
     * @param string $resourceName
     * @param string $relationshipName
     * @param string $controllerClass
     * @param string $selfGetMethod
     * @return GroupInterface
     */
    protected static function relationship(
        GroupInterface $group,
        string $resourceName,
        string $relationshipName,
        string $controllerClass,
        string $selfGetMethod
    ): GroupInterface {
        $resourceIdUri = $resourceName . '/{' . JCI::ROUTE_KEY_INDEX . '}/';
        $selfUri = $resourceIdUri . DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $relationshipName;

        return $group
            // `self`
            ->get($selfUri, [$controllerClass, $selfGetMethod])
            // `related`
            ->get($resourceIdUri . $relationshipName, [$controllerClass, $selfGetMethod]);
    }

    /**
     * @param GroupInterface $group
     * @param string $resourceName
     * @param string $relationshipName
     * @param string $controllerClass
     * @param string $addMethod
     * @return GroupInterface
     */
    protected static function addInRelationship(
        GroupInterface $group,
        string $resourceName,
        string $relationshipName,
        string $controllerClass,
        string $addMethod
    ): GroupInterface {
        $url = $resourceName . '/{' . JCI::ROUTE_KEY_INDEX . '}/' .
            DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $relationshipName;

        return $group->post($url, [$controllerClass, $addMethod]);
    }

    /**
     * @param GroupInterface $group
     * @param string $resourceName
     * @param string $relationshipName
     * @param string $controllerClass
     * @param string $deleteMethod
     * @return GroupInterface
     */
    protected static function removeInRelationship(
        GroupInterface $group,
        string $resourceName,
        string $relationshipName,
        string $controllerClass,
        string $deleteMethod
    ): GroupInterface {
        $url = $resourceName . '/{' . JCI::ROUTE_KEY_INDEX . '}/' .
            DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $relationshipName;

        return $group->delete($url, [$controllerClass, $deleteMethod]);
    }

    /**
     * @param string $prefix
     * @param string $subUri
     * @param string $method
     * @return string
     */
    protected static function routeName(string $prefix, string $subUri, string $method): string
    {
        assert(empty($method) === false);

        // normalize prefix and url to have predictable name

        if (empty($prefix) === true || $prefix[-1] !== '/') {
            $prefix .= '/';
        }

        if (empty($subUri) === false && $subUri[-1] === '/') {
            $subUri = substr($subUri, 0, -1);
        }

        return $prefix . $subUri . '::' . $method;
    }
}
