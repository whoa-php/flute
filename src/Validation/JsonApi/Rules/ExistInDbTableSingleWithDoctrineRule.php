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

namespace Whoa\Flute\Validation\JsonApi\Rules;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Doctrine\DBAL\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Whoa\Flute\Contracts\Validation\ErrorCodes;
use Whoa\Flute\L10n\Messages;
use Whoa\Validation\Contracts\Execution\ContextInterface;
use Whoa\Validation\Rules\ExecuteRule;

/**
 * @package Whoa\Flute
 */
final class ExistInDbTableSingleWithDoctrineRule extends ExecuteRule
{
    /** @var int Property key */
    public const PROPERTY_TABLE_NAME = self::PROPERTY_LAST + 1;

    /** @var int Property key */
    public const PROPERTY_PRIMARY_NAME = self::PROPERTY_TABLE_NAME + 1;

    /**
     * @param string $tableName
     * @param string $primaryName
     */
    public function __construct(string $tableName, string $primaryName)
    {
        parent::__construct([
            ExistInDbTableSingleWithDoctrineRule::PROPERTY_TABLE_NAME => $tableName,
            ExistInDbTableSingleWithDoctrineRule::PROPERTY_PRIMARY_NAME => $primaryName,
        ]);
    }

    /**
     * @inheritDoc
     * @param $value
     * @param ContextInterface $context
     * @param null $extras
     * @return array
     * @throws Exception
     * @throws DBALDriverException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function execute($value, ContextInterface $context, $extras = null): array
    {
        $count = 0;

        if (is_scalar($value) === true) {
            $tableName = $context->getProperties()->getProperty(
                ExistInDbTableSingleWithDoctrineRule::PROPERTY_TABLE_NAME
            );
            $primaryName = $context->getProperties()->getProperty(
                ExistInDbTableSingleWithDoctrineRule::PROPERTY_PRIMARY_NAME
            );

            /** @var Connection $connection */
            $connection = $context->getContainer()->get(Connection::class);
            $builder = $connection->createQueryBuilder();
            $statement = $builder
                ->select('count(*)')
                ->from($tableName)
                ->where($builder->expr()->eq($primaryName, $builder->createPositionalParameter($value)))
                ->execute();

            $count = $statement->fetchOne();
        }

        return $count > 0 ?
            ExistInDbTableSingleWithDoctrineRule::createSuccessReply($value) :
            ExistInDbTableSingleWithDoctrineRule::createErrorReply(
                $context,
                $value,
                ErrorCodes::EXIST_IN_DATABASE_SINGLE,
                Messages::EXIST_IN_DATABASE_SINGLE,
                []
            );
    }
}
