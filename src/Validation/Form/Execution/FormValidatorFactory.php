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

namespace Whoa\Flute\Validation\Form\Execution;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Whoa\Container\Traits\HasContainerTrait;
use Whoa\Contracts\L10n\FormatterFactoryInterface;
use Whoa\Contracts\Settings\SettingsProviderInterface;
use Whoa\Flute\Contracts\Validation\FormValidatorFactoryInterface;
use Whoa\Flute\Contracts\Validation\FormValidatorInterface;
use Whoa\Flute\L10n\Messages;
use Whoa\Flute\Package\FluteSettings as S;
use Whoa\Flute\Validation\Form\FormValidator;
use Whoa\Validation\Execution\ContextStorage;
use Psr\Container\ContainerInterface;

/**
 * @package Whoa\Flute
 */
class FormValidatorFactory implements FormValidatorFactoryInterface
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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function createValidator(string $rulesClass): FormValidatorInterface
    {
        /** @var SettingsProviderInterface $settingsProvider */
        $settingsProvider = $this->getContainer()->get(SettingsProviderInterface::class);
        $serializedData = S::getFormSerializedRules($settingsProvider->get(S::class));

        /** @var FormatterFactoryInterface $factory */
        $factory = $this->getContainer()->get(FormatterFactoryInterface::class);
        $formatter = $factory->createFormatter(Messages::NAMESPACE_NAME);

        return new FormValidator(
            $rulesClass,
            FormRulesSerializer::class,
            $serializedData,
            new ContextStorage(FormRulesSerializer::readBlocks($serializedData), $this->getContainer()),
            $formatter
        );
    }
}
