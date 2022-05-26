<?php

declare (strict_types=1);

namespace Whoa\Tests\Flute\Data\Api;

use Doctrine\DBAL\Exception as DBALException;
use Whoa\Flute\Adapters\ModelQueryBuilder;
use Whoa\Flute\Contracts\Http\Query\FilterParameterInterface;
use Whoa\Tests\Flute\Data\Models\Comment;

/**
 * @package Whoa\Tests\Flute
 */
class CommentsApi extends AppCrud
{
    public const MODEL_CLASS = Comment::class;

    public const DEBUG_KEY_DEFAULT_FILTER_INDEX = true;

    /** @var bool Key for tests */
    public static bool $isFilterIndexForCurrentUser = self::DEBUG_KEY_DEFAULT_FILTER_INDEX;

    /**
     * @inheritdoc
     * @param ModelQueryBuilder $builder
     * @return ModelQueryBuilder
     * @throws DBALException
     */
    protected function builderOnIndex(ModelQueryBuilder $builder): ModelQueryBuilder
    {
        $builder = parent::builderOnIndex($builder);

        if (static::$isFilterIndexForCurrentUser) {
            // suppose we want to limit API `index` method to only comments of current user
            // we can extend builder here

            $curUserId = 1;
            $builder->addFiltersWithAndToAlias([
                Comment::FIELD_ID_USER => [
                    FilterParameterInterface::OPERATION_EQUALS => [$curUserId],
                ],
            ]);
        }

        return $builder;
    }

    /**
     * @inheritdoc
     */
    public function create(?string $index, array $attributes, array $toMany): string
    {
        // suppose we want to create comments using current user as an author.
        $curUserId = 1;
        $attributes[Comment::FIELD_ID_USER] = $curUserId;

        return parent::create($index, $attributes, $toMany);
    }
}
