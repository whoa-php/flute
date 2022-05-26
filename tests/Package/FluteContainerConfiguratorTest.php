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

namespace Whoa\Tests\Flute\Package;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Whoa\Container\Container;
use Whoa\Contracts\Application\ApplicationConfigurationInterface;
use Whoa\Contracts\Application\CacheSettingsProviderInterface;
use Whoa\Contracts\Data\ModelSchemaInfoInterface;
use Whoa\Contracts\Exceptions\ThrowableHandlerInterface;
use Whoa\Contracts\L10n\FormatterFactoryInterface;
use Whoa\Contracts\Settings\SettingsProviderInterface;
use Whoa\Flute\Contracts\Api\RelationshipPaginationStrategyInterface;
use Whoa\Flute\Contracts\Encoder\EncoderInterface;
use Whoa\Flute\Contracts\FactoryInterface;
use Whoa\Flute\Contracts\Http\Query\ParametersMapperInterface;
use Whoa\Flute\Contracts\Schema\JsonSchemasInterface;
use Whoa\Flute\Contracts\Validation\FormValidatorFactoryInterface;
use Whoa\Flute\Contracts\Validation\JsonApiParserFactoryInterface;
use Whoa\Flute\Package\FluteContainerConfigurator;
use Whoa\Flute\Package\FluteSettings;
use Whoa\Tests\Flute\Data\L10n\FormatterFactory;
use Whoa\Tests\Flute\Data\Package\CacheSettingsProvider;
use Whoa\Tests\Flute\Data\Package\Flute;
use Whoa\Tests\Flute\Data\Validation\JsonData\CreateCommentRules;
use Whoa\Tests\Flute\Data\Validation\JsonQueries\CommentsIndexRules;
use Whoa\Tests\Flute\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @package Whoa\Tests\Flute
 */
class FluteContainerConfiguratorTest extends TestCase
{
    /**
     * Test configurator.
     * @throws DBALException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function testProvider(): void
    {
        $container = new Container();

        $appConfig = [
            ApplicationConfigurationInterface::KEY_IS_LOG_ENABLED => true,
            ApplicationConfigurationInterface::KEY_IS_DEBUG => true,
            ApplicationConfigurationInterface::KEY_ROUTES_FOLDER =>
                implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Data', 'Http']),
            ApplicationConfigurationInterface::KEY_WEB_CONTROLLERS_FOLDER =>
                implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Data', 'Http']),
        ];
        [$modelToSchemaMap,] = $this->getSchemaMap();
        $cacheSettingsProvider = new CacheSettingsProvider(
            $appConfig,
            [
                FluteSettings::class =>
                    (new Flute(
                        $modelToSchemaMap,
                        $this->getJsonValidationRuleSets(),
                        $this->getFormValidationRuleSets(),
                        $this->getQueryValidationRuleSets()
                    ))->get($appConfig),
            ]
        );
        $container[CacheSettingsProviderInterface::class] = $cacheSettingsProvider;
        $container[SettingsProviderInterface::class] = $cacheSettingsProvider;

        $container[LoggerInterface::class] = new NullLogger();
        $container[ModelSchemaInfoInterface::class] = $this->getModelSchemas();
        $container[Connection::class] = $this->createConnection();
        $container[FormatterFactoryInterface::class] = new FormatterFactory();

        FluteContainerConfigurator::configureContainer($container);
        FluteContainerConfigurator::configureExceptionHandler($container);

        $this->assertNotNull($container->get(FactoryInterface::class));
        $this->assertNotNull($container->get(ThrowableHandlerInterface::class));
        $this->assertNotNull($container->get(JsonSchemasInterface::class));
        $this->assertNotNull($container->get(EncoderInterface::class));
        $this->assertNotNull($container->get(ParametersMapperInterface::class));
        $this->assertNotNull($container->get(RelationshipPaginationStrategyInterface::class));
        $this->assertNotNull($container->get(FormValidatorFactoryInterface::class));
        /** @var JsonApiParserFactoryInterface $factory */
        $this->assertNotNull($factory = $container->get(JsonApiParserFactoryInterface::class));
        $this->assertNotNull($factory->createDataParser(CreateCommentRules::class));
        $this->assertNotNull($factory->createQueryParser(CommentsIndexRules::class));
    }
}
