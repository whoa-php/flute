<?php

/**
 * Copyright 2015-2019 info@neomerx.com
 * Copyright 2021 info@whoaphp.com
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
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Whoa\Flute\Contracts\Validation\ErrorCodes;
use Whoa\Flute\L10n\Messages;
use Whoa\Validation\Contracts\Execution\ContextInterface;
use Whoa\Validation\Rules\ExecuteRule;

use function is_scalar;

/**
 * @package Whoa\Flute
 */
final class UniqueInDbTableSingleWithDoctrineRule extends ExecuteRule
{
    /** @var int Property key */
    public const PROPERTY_TABLE_NAME = self::PROPERTY_LAST + 1;

    /** @var int Property key */
    public const PROPERTY_PRIMARY_NAME = self::PROPERTY_TABLE_NAME + 1;

    /** @var int Property key */
    public const PROPERTY_PRIMARY_KEY = self::PROPERTY_PRIMARY_NAME + 1;

    /**
     * @param string $tableName
     * @param string $primaryName
     * @param string|null $primaryKey
     */
    public function __construct(string $tableName, string $primaryName, ?string $primaryKey = null)
    {
        parent::__construct([
            UniqueInDbTableSingleWithDoctrineRule::PROPERTY_TABLE_NAME => $tableName,
            UniqueInDbTableSingleWithDoctrineRule::PROPERTY_PRIMARY_NAME => $primaryName,
            UniqueInDbTableSingleWithDoctrineRule::PROPERTY_PRIMARY_KEY => $primaryKey,
        ]);
    }

    /**
     * @inheritDoc
     * @param $value
     * @param ContextInterface $context
     * @param null $extras
     * @return array
     * @throws Exception
     * @throws DBALException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function execute($value, ContextInterface $context, $extras = null): array
    {
        $found = false;

        if (is_scalar($value) === true) {
            /** @var Connection $connection */
            $connection = $context->getContainer()->get(Connection::class);
            $builder = $connection->createQueryBuilder();
            $tableName = $context->getProperties()->getProperty(
                UniqueInDbTableSingleWithDoctrineRule::PROPERTY_TABLE_NAME
            );
            $primaryName = $context->getProperties()->getProperty(
                UniqueInDbTableSingleWithDoctrineRule::PROPERTY_PRIMARY_NAME
            );
            $primaryKey = $context->getProperties()->getProperty(
                UniqueInDbTableSingleWithDoctrineRule::PROPERTY_PRIMARY_KEY
            );
            $columns = $primaryKey === null ? "`{$primaryName}`" : "`{$primaryKey}`, `{$primaryName}`";
            $statement = $builder
                ->select($columns)
                ->from($tableName)
                ->where($builder->expr()->eq($primaryName, $builder->createPositionalParameter($value)))
                ->setMaxResults(1);

            $fetched = $statement->execute()->fetchOne();
            $found = isset($primaryKeyName) ?
                $fetched !== false && (int)$fetched[$primaryKey] !== (int)$extras :
                $fetched !== false;
        }

        return $found == false ?
            UniqueInDbTableSingleWithDoctrineRule::createSuccessReply($value) :
            UniqueInDbTableSingleWithDoctrineRule::createErrorReply(
                $context,
                $value,
                ErrorCodes::UNIQUE_IN_DATABASE_SINGLE,
                Messages::UNIQUE_IN_DATABASE_SINGLE,
                []
            );
    }
}
