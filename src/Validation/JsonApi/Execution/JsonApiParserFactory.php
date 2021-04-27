<?php declare (strict_types = 1);

namespace Whoa\Flute\Validation\JsonApi\Execution;

/**
 * Copyright 2015-2019 info@neomerx.com
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

use Whoa\Container\Traits\HasContainerTrait;
use Whoa\Contracts\L10n\FormatterFactoryInterface;
use Whoa\Contracts\Settings\SettingsProviderInterface;
use Whoa\Flute\Contracts\Validation\JsonApiDataParserInterface;
use Whoa\Flute\Contracts\Validation\JsonApiParserFactoryInterface;
use Whoa\Flute\Contracts\Validation\JsonApiQueryParserInterface;
use Whoa\Flute\L10n\Messages;
use Whoa\Flute\Package\FluteSettings;
use Whoa\Flute\Validation\JsonApi\DataParser;
use Whoa\Flute\Validation\JsonApi\QueryParser;
use Whoa\Validation\Captures\CaptureAggregator;
use Whoa\Validation\Errors\ErrorAggregator;
use Whoa\Validation\Execution\ContextStorage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @package Whoa\Flute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JsonApiParserFactory implements JsonApiParserFactoryInterface
{
    use HasContainerTrait;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createDataParser(string $rulesClass): JsonApiDataParserInterface
    {
        $serializedData = FluteSettings::getJsonDataSerializedRules($this->getFluteSettings());

        /** @var FormatterFactoryInterface $formatterFactory */
        $formatterFactory = $this->getContainer()->get(FormatterFactoryInterface::class);
        $parser           = new DataParser(
            $rulesClass,
            JsonApiDataRulesSerializer::class,
            $serializedData,
            new ContextStorage(JsonApiQueryRulesSerializer::readBlocks($serializedData), $this->getContainer()),
            new JsonApiErrorCollection($formatterFactory->createFormatter(Messages::NAMESPACE_NAME)),
            $this->getContainer()->get(FormatterFactoryInterface::class)
        );

        return $parser;
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function createQueryParser(string $rulesClass): JsonApiQueryParserInterface
    {
        $serializedData = FluteSettings::getJsonQuerySerializedRules($this->getFluteSettings());

        /** @var FormatterFactoryInterface $formatterFactory */
        $formatterFactory = $this->getContainer()->get(FormatterFactoryInterface::class);
        $parser           = new QueryParser(
            $rulesClass,
            JsonApiQueryRulesSerializer::class,
            $serializedData,
            new ContextStorage(JsonApiQueryRulesSerializer::readBlocks($serializedData), $this->getContainer()),
            new CaptureAggregator(),
            new ErrorAggregator(),
            new JsonApiErrorCollection($formatterFactory->createFormatter(Messages::NAMESPACE_NAME)),
            $this->getContainer()->get(FormatterFactoryInterface::class)
        );

        return $parser;
    }

    /**
     * @return array
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getFluteSettings(): array
    {
        /** @var SettingsProviderInterface $settingsProvider */
        $settingsProvider = $this->getContainer()->get(SettingsProviderInterface::class);
        $settings         = $settingsProvider->get(FluteSettings::class);

        return $settings;
    }
}
