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

namespace Whoa\Flute\Validation\Serialize;

use Whoa\Validation\Contracts\Execution\BlockSerializerInterface;
use Whoa\Validation\Contracts\Rules\RuleInterface;
use Whoa\Validation\Execution\BlockSerializer;

use function array_key_exists;
use function assert;

/**
 * @package Whoa\Flute
 */
class RulesSerializer
{
    // Single rule serialization keys

    /** Index key */
    protected const RULES_SINGLE_INDEX = 0;

    /** Index key */
    protected const RULES_SINGLE_START_INDEXES = self::RULES_SINGLE_INDEX + 1;

    /** Index key */
    protected const RULES_SINGLE_END_INDEXES = self::RULES_SINGLE_START_INDEXES + 1;

    // Rules array serialization keys

    /** Index key */
    protected const RULES_ARRAY_INDEXES = 0;

    /** Index key */
    protected const RULES_ARRAY_START_INDEXES = self::RULES_ARRAY_INDEXES + 1;

    /** Index key */
    protected const RULES_ARRAY_END_INDEXES = self::RULES_ARRAY_START_INDEXES + 1;

    /** Index key. Every rule is serialized independently */
    protected const RULES_ARRAY_SINGLE_INDEXES = self::RULES_ARRAY_END_INDEXES + 1;

    /**
     * @var BlockSerializerInterface
     */
    private BlockSerializerInterface $blockSerializer;

    /**
     * @param RuleInterface $rule
     * @return array
     */
    public function addRule(RuleInterface $rule): array
    {
        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        return [
            static::RULES_SINGLE_INDEX => $this->getSerializer()->addBlock($rule->toBlock()),
            static::RULES_SINGLE_START_INDEXES => $this->getSerializer()->getBlocksWithStart(),
            static::RULES_SINGLE_END_INDEXES => $this->getSerializer()->getBlocksWithEnd(),
        ];
    }

    /**
     * @param BlockSerializerInterface $blockSerializer
     */
    public function __construct(BlockSerializerInterface $blockSerializer)
    {
        $this->blockSerializer = $blockSerializer;
    }

    /**
     * @param RuleInterface[] $rules
     * @return array
     */
    public function addRules(array $rules): array
    {
        // serialize the rules altogether

        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        $indexes = [];
        foreach ($rules as $name => $rule) {
            assert($rule instanceof RuleInterface);

            $ruleName = $rule->getName();
            if (empty($ruleName) === true) {
                $ruleName = $name;
            }

            $block = $rule->setName($ruleName)->enableCapture()->toBlock();
            $indexes[$name] = $this->getSerializer()->addBlock($block);
        }

        $ruleIndexes = [
            static::RULES_ARRAY_INDEXES => $indexes,
            static::RULES_ARRAY_START_INDEXES => $this->getSerializer()->getBlocksWithStart(),
            static::RULES_ARRAY_END_INDEXES => $this->getSerializer()->getBlocksWithEnd(),
        ];

        $this->getSerializer()->clearBlocksWithStart()->clearBlocksWithEnd();

        // sometimes (e.g. update in relationship) an individual validation rule is needed,
        // so we should have a second serialization of each rule individually

        $individualRules = [];
        foreach ($rules as $name => $rule) {
            $individualRules[$name] = $this->addRule($rule);
        }
        $ruleIndexes[static::RULES_ARRAY_SINGLE_INDEXES] = $individualRules;

        return $ruleIndexes;
    }

    /**
     * @return array
     */
    public function getBlocks(): array
    {
        return BlockSerializer::unserializeBlocks($this->getSerializer()->get());
    }

    /**
     * @param array $singleRuleIndexes
     * @return int
     */
    public static function getRuleIndex(array $singleRuleIndexes): int
    {
        assert(array_key_exists(static::RULES_SINGLE_INDEX, $singleRuleIndexes));
        return $singleRuleIndexes[static::RULES_SINGLE_INDEX];
    }

    /**
     * @param array $singleRuleIndexes
     * @return array
     */
    public static function getRuleStartIndexes(array $singleRuleIndexes): array
    {
        assert(array_key_exists(static::RULES_SINGLE_START_INDEXES, $singleRuleIndexes));
        return $singleRuleIndexes[static::RULES_SINGLE_START_INDEXES];
    }

    /**
     * @param array $singleRuleIndexes
     * @return array
     */
    public static function getRuleEndIndexes(array $singleRuleIndexes): array
    {
        assert(array_key_exists(static::RULES_SINGLE_END_INDEXES, $singleRuleIndexes));
        return $singleRuleIndexes[static::RULES_SINGLE_END_INDEXES];
    }

    /**
     * @param array $arrayRulesIndexes
     * @return array
     */
    public static function getRulesIndexes(array $arrayRulesIndexes): array
    {
        assert(array_key_exists(static::RULES_ARRAY_INDEXES, $arrayRulesIndexes));
        return $arrayRulesIndexes[static::RULES_ARRAY_INDEXES];
    }

    /**
     * @param array $arrayRulesIndexes
     * @return array
     */
    public static function getRulesStartIndexes(array $arrayRulesIndexes): array
    {
        assert(array_key_exists(static::RULES_ARRAY_START_INDEXES, $arrayRulesIndexes));
        return $arrayRulesIndexes[static::RULES_ARRAY_START_INDEXES];
    }

    /**
     * @param array $arrayRulesIndexes
     * @return array
     */
    public static function getRulesEndIndexes(array $arrayRulesIndexes): array
    {
        assert(array_key_exists(static::RULES_ARRAY_END_INDEXES, $arrayRulesIndexes));
        return $arrayRulesIndexes[static::RULES_ARRAY_END_INDEXES];
    }

    /**
     * @param array $arrayRulesIndexes
     * @param string $name
     * @return array
     */
    public static function geSingleRuleIndexes(array $arrayRulesIndexes, string $name): array
    {
        assert(array_key_exists(static::RULES_ARRAY_SINGLE_INDEXES, $arrayRulesIndexes));
        $rules = $arrayRulesIndexes[static::RULES_ARRAY_SINGLE_INDEXES];
        assert(array_key_exists($name, $rules));
        return $rules[$name];
    }

    /**
     * @return BlockSerializerInterface
     */
    protected function getSerializer(): BlockSerializerInterface
    {
        return $this->blockSerializer;
    }
}
