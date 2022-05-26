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
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Whoa\Flute\Contracts\Validation\ErrorCodes;
use Whoa\Flute\L10n\Messages;
use Whoa\Validation\Contracts\Execution\ContextInterface;
use Whoa\Validation\Rules\ExecuteRule;

use function count;
use function is_array;

/**
 * @package Whoa\Flute
 */
final class ExistInDbTableMultipleWithDoctrineRule extends ExecuteRule
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
            ExistInDbTableMultipleWithDoctrineRule::PROPERTY_TABLE_NAME => $tableName,
            ExistInDbTableMultipleWithDoctrineRule::PROPERTY_PRIMARY_NAME => $primaryName,
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
        // let's consider an empty index list as `exists`
        $result = is_array($value);

        if ($result === true && empty($value) === false) {
            $tableName = $context->getProperties()->getProperty(
                ExistInDbTableMultipleWithDoctrineRule::PROPERTY_TABLE_NAME
            );
            $primaryName = $context->getProperties()->getProperty(
                ExistInDbTableMultipleWithDoctrineRule::PROPERTY_PRIMARY_NAME
            );

            /** @var Connection $connection */
            $connection = $context->getContainer()->get(Connection::class);
            $builder = $connection->createQueryBuilder();
            $placeholders = [];
            foreach ($value as $v) {
                $placeholders[] = $builder->createPositionalParameter($v);
            }
            $statement = $builder
                ->select('count(*)')
                ->from($tableName)
                ->where($builder->expr()->in($primaryName, $placeholders))
                ->execute();

            $count = (int)$statement->fetchOne();
            $result = $count === count($value);
        }

        return $result === true ?
            ExistInDbTableMultipleWithDoctrineRule::createSuccessReply($value) :
            ExistInDbTableMultipleWithDoctrineRule::createErrorReply(
                $context,
                $value,
                ErrorCodes::EXIST_IN_DATABASE_MULTIPLE,
                Messages::EXIST_IN_DATABASE_MULTIPLE,
                []
            );
    }
}
