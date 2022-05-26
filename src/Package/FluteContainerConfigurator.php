<?php

/**
 * Copyright 2015-2019 info@neomerx.com
 * Modification Copyright 2021 info@whoaphp.com
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

namespace Whoa\Flute\Package;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Type;
use Whoa\Contracts\Application\ApplicationConfigurationInterface as A;
use Whoa\Contracts\Application\CacheSettingsProviderInterface;
use Whoa\Contracts\Application\ContainerConfiguratorInterface;
use Whoa\Contracts\Container\ContainerInterface as WhoaContainerInterface;
use Whoa\Contracts\Data\ModelSchemaInfoInterface;
use Whoa\Contracts\Exceptions\ThrowableHandlerInterface;
use Whoa\Contracts\Settings\Packages\FluteSettingsInterface as FCI;
use Whoa\Contracts\Settings\SettingsProviderInterface;
use Whoa\Doctrine\Types\DateTimeType as WhoaDateTimeType;
use Whoa\Doctrine\Types\DateType as WhoaDateType;
use Whoa\Doctrine\Types\TimeType as WhoaTimeType;
use Whoa\Doctrine\Types\UuidType as WhoaUuidType;
use Whoa\Flute\Api\BasicRelationshipPaginationStrategy;
use Whoa\Flute\Contracts\Api\RelationshipPaginationStrategyInterface;
use Whoa\Flute\Contracts\Encoder\EncoderInterface;
use Whoa\Flute\Contracts\FactoryInterface;
use Whoa\Flute\Contracts\Http\Query\ParametersMapperInterface;
use Whoa\Flute\Contracts\Schema\JsonSchemasInterface;
use Whoa\Flute\Contracts\Validation\FormValidatorFactoryInterface;
use Whoa\Flute\Contracts\Validation\JsonApiParserFactoryInterface;
use Whoa\Flute\Factory;
use Whoa\Flute\Http\Query\ParametersMapper;
use Whoa\Flute\Http\ThrowableHandlers\FluteThrowableHandler;
use Whoa\Flute\Validation\Form\Execution\FormValidatorFactory;
use Whoa\Flute\Validation\JsonApi\Execution\JsonApiParserFactory;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @package Whoa\Flute
 */
class FluteContainerConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    public const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /** @var callable */
    public const CONFIGURE_EXCEPTION_HANDLER = [self::class, 'configureExceptionHandler'];

    /**
     * @inheritdoc
     * @throws DBALException
     */
    public static function configureContainer(WhoaContainerInterface $container): void
    {
        $factory = new Factory($container);

        $container[FactoryInterface::class] = $factory;

        $container[JsonSchemasInterface::class] = function (PsrContainerInterface $container) use ($factory) {
            $settings = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);
            $modelSchemas = $container->get(ModelSchemaInfoInterface::class);

            return $factory->createJsonSchemas(
                $settings[FCI::KEY_MODEL_TO_SCHEMA_MAP],
                $settings[FCI::KEY_TYPE_TO_SCHEMA_MAP],
                $modelSchemas
            );
        };

        $container[ParametersMapperInterface::class] = function (PsrContainerInterface $container) {
            return new ParametersMapper($container->get(JsonSchemasInterface::class));
        };

        $container[EncoderInterface::class] = function (PsrContainerInterface $container) use ($factory) {
            /** @var JsonSchemasInterface $jsonSchemas */
            $jsonSchemas = $container->get(JsonSchemasInterface::class);
            $settings = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);
            $encoder = $factory
                ->createEncoder($jsonSchemas)
                ->withEncodeOptions($settings[FCI::KEY_JSON_ENCODE_OPTIONS])
                ->withEncodeDepth($settings[FCI::KEY_JSON_ENCODE_DEPTH])
                ->withUrlPrefix($settings[FCI::KEY_URI_PREFIX]);
            isset($settings[FCI::KEY_META]) ? $encoder->withMeta($settings[FCI::KEY_META]) : null;
            ($settings[FCI::KEY_IS_SHOW_VERSION] ?? false) ?
                $encoder->withJsonApiVersion(FluteSettings::DEFAULT_JSON_API_VERSION) : null;

            return $encoder;
        };

        $container[RelationshipPaginationStrategyInterface::class] = function (PsrContainerInterface $container) {
            $settings = $container->get(SettingsProviderInterface::class)->get(FluteSettings::class);

            return new BasicRelationshipPaginationStrategy($settings[FluteSettings::KEY_DEFAULT_PAGING_SIZE]);
        };

        $container[JsonApiParserFactoryInterface::class] = function (PsrContainerInterface $container) {
            return new JsonApiParserFactory($container);
        };

        $container[FormValidatorFactoryInterface::class] = function (PsrContainerInterface $container) {
            return new FormValidatorFactory($container);
        };

        // register date/date time types
        Type::hasType(WhoaDateTimeType::NAME) === true ?: Type::addType(
            WhoaDateTimeType::NAME,
            WhoaDateTimeType::class
        );
        Type::hasType(WhoaDateType::NAME) === true ?: Type::addType(WhoaDateType::NAME, WhoaDateType::class);
        Type::hasType(WhoaTimeType::NAME) === true ?: Type::addType(WhoaTimeType::NAME, WhoaTimeType::class);

        // register UUID type
        Type::hasType(WhoaUuidType::NAME) === true ?: Type::addType(WhoaUuidType::NAME, WhoaUuidType::class);
    }

    /**
     * @param WhoaContainerInterface $container
     * @return void
     */
    public static function configureExceptionHandler(WhoaContainerInterface $container)
    {
        $container[ThrowableHandlerInterface::class] = function (PsrContainerInterface $container) {
            /** @var CacheSettingsProviderInterface $provider */
            $provider = $container->get(CacheSettingsProviderInterface::class);
            $appConfig = $provider->getApplicationConfiguration();
            $fluteSettings = $provider->get(FluteSettings::class);

            $isLogEnabled = $appConfig[A::KEY_IS_LOG_ENABLED];
            $isDebug = $appConfig[A::KEY_IS_DEBUG];

            $ignoredErrorClasses = $fluteSettings[FCI::KEY_DO_NOT_LOG_EXCEPTIONS_LIST__AS_KEYS];
            $codeForUnexpected = $fluteSettings[FCI::KEY_HTTP_CODE_FOR_UNEXPECTED_THROWABLE];
            $throwableConverter =
                $fluteSettings[FCI::KEY_THROWABLE_TO_JSON_API_EXCEPTION_CONVERTER] ?? null;

            /** @var EncoderInterface $encoder */
            $encoder = $container->get(EncoderInterface::class);

            $handler = new FluteThrowableHandler(
                $encoder,
                $ignoredErrorClasses,
                $codeForUnexpected,
                $isDebug,
                $throwableConverter
            );

            if ($isLogEnabled === true && $container->has(LoggerInterface::class) === true) {
                /** @var LoggerInterface $logger */
                $logger = $container->get(LoggerInterface::class);
                $handler->setLogger($logger);
            }

            return $handler;
        };
    }
}
