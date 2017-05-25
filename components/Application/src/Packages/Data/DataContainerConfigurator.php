<?php namespace Limoncello\Application\Packages\Data;

/**
 * Copyright 2015-2017 info@neomerx.com
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
use Doctrine\DBAL\DriverManager;
use Limoncello\Application\Data\ModelSchemeInfo;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Data\ModelSchemeInfoInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @package Limoncello\Application
 */
class DataContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const HANDLER = [self::class, self::METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configure(LimoncelloContainerInterface $container)
    {
        $container[ModelSchemeInfoInterface::class] = function (PsrContainerInterface $container) {
            $settings = $container->get(SettingsProviderInterface::class)->get(DataSettings::class);
            $data     = $settings[DataSettings::KEY_MODELS_SCHEME_INFO];
            $schemes  = new ModelSchemeInfo();
            $schemes->setData($data);

            return $schemes;
        };

        $container[Connection::class] = function (PsrContainerInterface $container) {
            $settings = $container->get(SettingsProviderInterface::class)->get(DoctrineSettings::class);
            $params   = [
                'dbname'   => $settings[DoctrineSettings::KEY_DATABASE_NAME],
                'user'     => $settings[DoctrineSettings::KEY_USER_NAME],
                'password' => $settings[DoctrineSettings::KEY_PASSWORD],
                'host'     => $settings[DoctrineSettings::KEY_HOST],
                'port'     => $settings[DoctrineSettings::KEY_PORT],
                'driver'   => $settings[DoctrineSettings::KEY_DRIVER],
                'charset'  => $settings[DoctrineSettings::KEY_CHARSET],
            ];

            $connection = DriverManager::getConnection($params);

            return $connection;
        };
    }
}