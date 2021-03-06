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

namespace Whoa\Tests\Flute\Data\Package;

use Whoa\Flute\Contracts\Validation\FormRulesInterface;
use Whoa\Flute\Contracts\Validation\JsonApiDataRulesInterface;
use Whoa\Flute\Contracts\Validation\JsonApiQueryRulesInterface;
use Whoa\Flute\Package\FluteSettings;

/**
 * @package Whoa\Tests\Flute
 */
class Flute extends FluteSettings
{
    /**
     * During the test we need to replace one of the static methods we inherit but non-static
     * replacement is more preferable for us so we keep last instance as a static variable.
     *
     * @var self
     */
    private static Flute $currentInstance;

    /**
     * @var string[]
     */
    private array $modelToSchemaMap;

    /**
     * @var string[]
     */
    private array $jsonValRuleSets;

    /**
     * @var string[]
     */
    private array $formValRuleSets;

    /**
     * @var string[]
     */
    private array $queryValRuleSets;

    /**
     * @var string
     */
    private string $apiFolder;

    /**
     * @var string
     */
    private string $valRulesFolder;

    /**
     * @var string
     */
    private string $jsonCtrlFolder;

    /**
     * @var string
     */
    private string $schemasPath;

    /**
     * @var string
     */
    private string $formValPath;

    /**
     * @var string
     */
    private string $jsonValPath;

    /**
     * @var string
     */
    private string $queryValPath;

    /**
     * @param string[] $modelToSchemaMap
     * @param string[] $jsonRuleSets
     * @param string[] $formRuleSets
     * @param string[] $queryRuleSets
     */
    public function __construct(
        array $modelToSchemaMap,
        array $jsonRuleSets,
        array $formRuleSets,
        array $queryRuleSets
    ) {
        $this->apiFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Api']);
        $this->jsonCtrlFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Http']);
        $this->schemasPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Schemas']);
        $this->valRulesFolder = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation']);
        $this->formValPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', 'Forms', '**']);
        $this->jsonValPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', 'JsonData', '**']);
        $this->queryValPath = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'Validation', 'JsonQueries', '**']);

        $this->modelToSchemaMap = $modelToSchemaMap;
        $this->jsonValRuleSets = $jsonRuleSets;
        $this->formValRuleSets = $formRuleSets;
        $this->queryValRuleSets = $queryRuleSets;

        static::$currentInstance = $this;
    }

    /**
     * @inheritdoc
     */
    protected function getSettings(): array
    {
        return parent::getSettings() + [
                static::KEY_API_FOLDER => $this->apiFolder,
                static::KEY_JSON_CONTROLLERS_FOLDER => $this->jsonCtrlFolder,
                static::KEY_SCHEMAS_FOLDER => $this->schemasPath,
                static::KEY_JSON_VALIDATION_RULES_FOLDER => $this->valRulesFolder,
                static::KEY_JSON_VALIDATORS_FOLDER => $this->jsonValPath,
                static::KEY_FORM_VALIDATORS_FOLDER => $this->formValPath,
                static::KEY_QUERY_VALIDATORS_FOLDER => $this->queryValPath,
            ];
    }

    /**
     * @inheritdoc
     */
    protected static function selectClasses(string $path, string $classOrInterface): iterable
    {
        return static::$currentInstance->selectClassesCustom($path, $classOrInterface);
    }

    /**
     * @param string $path
     * @param string $implementClassName
     * @return iterable
     */
    protected function selectClassesCustom(string $path, string $implementClassName): iterable
    {
        $settings = parent::getSettings();
        $schemasPath = $this->schemasPath . DIRECTORY_SEPARATOR . $settings[static::KEY_SCHEMAS_FILE_MASK];
        $jsonFilePath = $this->jsonValPath . DIRECTORY_SEPARATOR . $settings[static::KEY_JSON_VALIDATORS_FILE_MASK];
        $formsFilePath = $this->formValPath . DIRECTORY_SEPARATOR . $settings[static::KEY_FORM_VALIDATORS_FILE_MASK];
        $queryFilePath = $this->queryValPath . DIRECTORY_SEPARATOR . $settings[static::KEY_QUERY_VALIDATORS_FILE_MASK];

        switch ($path) {
            case $schemasPath:
                foreach ($this->getModelToSchemaMap() as $schemaClass) {
                    yield $schemaClass;
                }
                break;
            case $jsonFilePath:
                assert($implementClassName === JsonApiDataRulesInterface::class);
                foreach ($this->getJsonValidationRuleSets() as $ruleSet) {
                    yield $ruleSet;
                }
                break;
            case $formsFilePath:
                assert($implementClassName === FormRulesInterface::class);
                foreach ($this->getFormValidationRuleSets() as $ruleSet) {
                    yield $ruleSet;
                }
                break;
            case $queryFilePath:
                assert($implementClassName === JsonApiQueryRulesInterface::class);
                foreach ($this->getQueryValidationRuleSets() as $ruleSet) {
                    if (in_array($implementClassName, class_implements($ruleSet)) === true) {
                        yield $ruleSet;
                    }
                }
                break;
            default:
                assert("Unknown path `$path`.");
        }
    }

    /**
     * @return string[]
     */
    private function getModelToSchemaMap(): array
    {
        return $this->modelToSchemaMap;
    }

    /**
     * @return string[]
     */
    private function getJsonValidationRuleSets(): array
    {
        return $this->jsonValRuleSets;
    }

    /**
     * @return string[]
     */
    private function getFormValidationRuleSets(): array
    {
        return $this->formValRuleSets;
    }

    /**
     * @return string[]
     */
    private function getQueryValidationRuleSets(): array
    {
        return $this->queryValRuleSets;
    }
}
