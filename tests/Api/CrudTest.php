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

namespace Whoa\Tests\Flute\Api;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Types\Type;
use Exception;
use Whoa\Container\Container;
use Whoa\Contracts\Data\ModelSchemaInfoInterface;
use Whoa\Contracts\L10n\FormatterFactoryInterface;
use Whoa\Doctrine\Types\DateTimeType;
use Whoa\Doctrine\Types\DateType;
use Whoa\Doctrine\Types\UuidType;
use Whoa\Flute\Adapters\ModelQueryBuilder;
use Whoa\Flute\Api\BasicRelationshipPaginationStrategy;
use Whoa\Flute\Api\Crud;
use Whoa\Flute\Contracts\Api\CrudInterface;
use Whoa\Flute\Contracts\Api\RelationshipPaginationStrategyInterface;
use Whoa\Flute\Contracts\FactoryInterface;
use Whoa\Flute\Contracts\Http\Query\FilterParameterInterface;
use Whoa\Flute\Contracts\Models\PaginatedDataInterface;
use Whoa\Flute\Exceptions\InvalidArgumentException;
use Whoa\Flute\Factory;
use Whoa\Tests\Flute\Data\Api\CommentsApi;
use Whoa\Tests\Flute\Data\Api\PostsApi;
use Whoa\Tests\Flute\Data\Api\StringPKModelApi;
use Whoa\Tests\Flute\Data\Api\UsersApi;
use Whoa\Tests\Flute\Data\L10n\FormatterFactory;
use Whoa\Tests\Flute\Data\Models\Board;
use Whoa\Tests\Flute\Data\Models\Comment;
use Whoa\Tests\Flute\Data\Models\CommentEmotion;
use Whoa\Tests\Flute\Data\Models\Emotion;
use Whoa\Tests\Flute\Data\Models\Model;
use Whoa\Tests\Flute\Data\Models\Post;
use Whoa\Tests\Flute\Data\Models\StringPKModel;
use Whoa\Tests\Flute\Data\Models\User;
use Whoa\Tests\Flute\Data\Types\SystemDateTimeType;
use Whoa\Tests\Flute\Data\Types\SystemDateType;
use Whoa\Tests\Flute\Data\Types\SystemUuidType;
use Whoa\Tests\Flute\TestCase;
use PDO;

/**
 * @package Whoa\Tests\Flute
 */
class CrudTest extends TestCase
{
    public const DEFAULT_PAGE = 3;

    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @inheritDoc
     * @throws DBALException
     */
    protected function setUp(): void
    {
        parent::setUp();

        // If test is run withing the whole test suite then those lines not needed, however
        // if only tests from this file are run then the lines are required.
        Type::hasType(DateTimeType::NAME) === true ?: Type::addType(DateTimeType::NAME, SystemDateTimeType::class);
        Type::hasType(DateType::NAME) === true ?: Type::addType(DateType::NAME, SystemDateType::class);
        Type::hasType(UuidType::NAME) === true ?: Type::addType(UuidType::NAME, SystemUuidType::class);
    }

    /**
     * Test create read and delete newly created resource.
     * @throws Exception
     * @throws DBALException
     */
    public function testCreateReadAndDeletePost(): void
    {
        $userId = 1;
        $boardId = 2;
        $text = 'Some text';
        $title = 'Some title';
        $attributes = [
            Post::FIELD_TITLE => $title,
            Post::FIELD_TEXT => $text,
            Post::FIELD_ID_BOARD => $boardId,
            Post::FIELD_ID_USER => $userId,
        ];
        $toMany = [];

        $crud = $this->createCrud(PostsApi::class);

        $this->assertNotNull($index = $crud->create(null, $attributes, $toMany));
        $this->assertNotNull($model = $crud->read($index));

        /** @var Post $model */

        $this->assertEquals($userId, $model->{Post::FIELD_ID_USER});
        $this->assertEquals($boardId, $model->{Post::FIELD_ID_BOARD});
        $this->assertEquals($title, $model->{Post::FIELD_TITLE});
        $this->assertEquals($text, $model->{Post::FIELD_TEXT});
        $this->assertEquals($index, $model->{Post::FIELD_ID});

        $this->assertNotNull($crud->read($index));

        $crud->remove($index);

        $this->assertNull($crud->read($index));

        // second delete does nothing (already deleted)
        $crud->remove($index);
    }

    /**
     * Test create read and delete newly created resource with string primary key.
     * @throws Exception
     * @throws DBALException
     */
    public function testCreateReadAndDeleteStringPKModel(): void
    {
        $pk = 'new_pk_value';
        $name = 'Some title';
        $attributes = [
            StringPKModel::FIELD_NAME => $name,
        ];

        $crud = $this->createCrud(StringPKModelApi::class);

        $this->assertNotNull($index = $crud->create($pk, $attributes, []));
        $this->assertEquals($pk, $index);
        $this->assertNotNull($model = $crud->read($index));

        /** @var StringPKModel $model */

        $this->assertEquals($pk, $model->{StringPKModel::FIELD_ID});
        $this->assertEquals($name, $model->{StringPKModel::FIELD_NAME});

        $this->assertNotNull($crud->read($index));

        // use equivalent index filter + delete
        $crud->withIndexFilter($index)->delete();

        $this->assertNull($crud->read($index));

        // second delete does nothing (already deleted)
        $crud->remove($index);
    }

    /**
     * Test create resource with to-many (belongs-to-many relationships).
     * @throws DBALException
     * @throws DBALDriverException
     */
    public function testCreateCommentsWithEmotions(): void
    {
        $userId = 1;
        $postId = 2;
        $text = 'Some text';
        $uuid = '64c7660d-01f6-406a-8d13-e137ce268fde';
        $attributes = [
            Model::FIELD_UUID => $uuid,
            Comment::FIELD_TEXT => $text,
            Comment::FIELD_ID_POST => $postId,
            Comment::FIELD_ID_USER => $userId,
        ];
        $toMany = [
            Comment::REL_EMOTIONS => ['3', '4'],
        ];

        $crud = $this->createCrud(CommentsApi::class);

        $this->assertNotNull($index = $crud->create(null, $attributes, $toMany));
        $this->assertNotNull($model = $crud->read($index));

        /** @var Comment $model */

        $this->assertEquals($userId, $model->{Comment::FIELD_ID_USER});
        $this->assertEquals($postId, $model->{Comment::FIELD_ID_POST});
        $this->assertEquals($uuid, $model->{Model::FIELD_UUID});
        $this->assertEquals($text, $model->{Comment::FIELD_TEXT});
        $this->assertEquals($index, $model->{Comment::FIELD_ID});

        // check resources is saved
        /** @noinspection SqlDialectInspection */
        $res = $this->connection
            ->executeQuery('SELECT * FROM ' . Comment::TABLE_NAME . ' WHERE ' . Comment::FIELD_ID . " = $index")
            ->fetchAssociative(PDO::FETCH_ASSOC);
        $this->assertNotEquals(false, $res);
        $this->assertEquals($userId, $res[Comment::FIELD_ID_USER]);
        $this->assertEquals($postId, $res[Comment::FIELD_ID_POST]);
        // check resource to-many relationship are saved
        /** @noinspection SqlDialectInspection */
        $res = $this->connection->executeQuery(
            'SELECT * FROM ' . CommentEmotion::TABLE_NAME . ' WHERE ' . CommentEmotion::FIELD_ID_COMMENT . " = $index"
        )->fetchAllAssociative();
        $this->assertNotEquals(false, $res);
        $this->assertCount(2, (array)$res);

        // same checks but this time via API
        $includePaths = [
            [Comment::REL_USER],
            [Comment::REL_POST],
            [Comment::REL_EMOTIONS],
        ];
        $this->assertNotNull(
            $comment = $crud->withIncludes($includePaths)->read($index)
        );
        $this->assertEquals(
            $userId,
            $comment->{Comment::REL_USER}->{User::FIELD_ID}
        );
        $this->assertEquals(
            $postId,
            $comment->{Comment::REL_POST}->{Post::FIELD_ID}
        );
        /** @var PaginatedDataInterface $emotions */
        $emotions = $comment->{Comment::REL_EMOTIONS};
        $this->assertCount(2, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());
    }

    /**
     * Test update resource with to-many (belongs-to-many relationships).
     * @throws Exception
     * @throws DBALException
     */
    public function testUpdateCommentsWithEmotions(): void
    {
        $commentId = '1';
        $userId = '1';
        $postId = '3';
        $text = 'Some text';
        $attributes = [
            Comment::FIELD_TEXT => $text,
            Comment::FIELD_ID_POST => $postId,
            Comment::FIELD_ID_USER => $userId,
        ];
        $toMany = [
            Comment::REL_EMOTIONS => ['3', '4'],
        ];

        $crud = $this->createCrud(CommentsApi::class);

        $changedRecords = $crud->update($commentId, $attributes, $toMany);
        $this->assertEquals(3, $changedRecords);
        $this->assertNotNull($model = $crud->read($commentId));

        /** @var Comment $model */

        $this->assertEquals($userId, $model->{Comment::FIELD_ID_USER});
        $this->assertEquals($postId, $model->{Comment::FIELD_ID_POST});
        $this->assertEquals($text, $model->{Comment::FIELD_TEXT});
        $this->assertEquals($commentId, $model->{Comment::FIELD_ID});

        $includePaths = [
            [Comment::REL_USER],
            [Comment::REL_POST],
            [Comment::REL_EMOTIONS],
        ];
        $this->assertNotNull(
            $comment = $crud->withIncludes($includePaths)->read($commentId)
        );
        $this->assertEquals(
            $userId,
            $comment->{Comment::REL_USER}->{User::FIELD_ID}
        );
        $this->assertEquals(
            $postId,
            $comment->{Comment::REL_POST}->{Post::FIELD_ID}
        );
        /** @var PaginatedDataInterface $emotions */
        $emotions = $comment->{Comment::REL_EMOTIONS};
        $this->assertCount(2, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());
    }

    /**
     * @throws Exception
     * @throws DBALException
     */
    public function testDeleteResourceWithConstraints(): void
    {
        $this->expectException(DriverException::class);

        $crud = $this->createCrud(PostsApi::class);
        $crud->remove('1');
    }

    /**
     * Check 'read' with included paths.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadWithIncludes(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $index = '18';
        $includePaths = [
            [Post::REL_BOARD],
            [Post::REL_COMMENTS],
            [Post::REL_COMMENTS, Comment::REL_EMOTIONS],
            [Post::REL_COMMENTS, Comment::REL_POST, Post::REL_USER],
        ];
        $this->assertNotNull(
            $model = $crud->withIncludes($includePaths)->read($index)
        );

        $board = $model->{Post::REL_BOARD};
        $this->assertEquals(Board::class, get_class($board));
        $this->assertEquals($model->{Post::FIELD_ID_BOARD}, $board->{Board::FIELD_ID});

        /** @var PaginatedDataInterface $commentsRel */
        $commentsRel = $model->{Post::REL_COMMENTS};
        $comments = $commentsRel->getData();
        $hasMore = $commentsRel->hasMoreItems();
        $offset = $commentsRel->getOffset();
        $limit = $commentsRel->getLimit();
        $this->assertNotEmpty($comments);
        $this->assertCount(3, $comments);
        $this->assertEquals(Comment::class, get_class($comments[0]));
        $this->assertEquals($index, $comments[0]->{Comment::FIELD_ID_POST});
        $this->assertTrue($hasMore);
        $this->assertCount(self::DEFAULT_PAGE, $comments);
        $this->assertEquals(0, $offset);
        $this->assertEquals(self::DEFAULT_PAGE, $limit);

        /** @var PaginatedDataInterface $emotions */
        $emotions = $comments[0]->{Comment::REL_EMOTIONS};
        $this->assertCount(2, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertEquals(0, $emotions->getOffset());
        $this->assertEquals(self::DEFAULT_PAGE, $emotions->getLimit());

        $emotions = $comments[1]->{Comment::REL_EMOTIONS};
        $this->assertCount(1, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());

        $comment = $comments[2];
        $emotions = $comment->{Comment::REL_EMOTIONS};
        $this->assertCount(3, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());

        $this->assertNotNull($post = $comment->{Comment::REL_POST});
        $this->assertNotNull($user = $post->{Post::REL_USER});

        // check no data for relationships we didn't ask to download
        $this->assertFalse(property_exists($user, User::REL_ROLE));
        $this->assertFalse(property_exists($user, User::REL_COMMENTS));
    }

    /**
     * Check 'read' with included paths.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadWithDeepIncludesWhenIntermediateRelationshipIsEmpty(): void
    {
        $crud = $this->createCrud(PostsApi::class);
        $connection = $crud->createIndexBuilder()->getConnection();

        $postsTable = Post::TABLE_NAME;
        $postsIdPost = Post::FIELD_ID;
        $postsIdEditor = Post::FIELD_ID_EDITOR;
        $commentsTable = Comment::TABLE_NAME;
        $commentsIdPost = Comment::FIELD_ID_POST;
        $commentsNumberSql = "SELECT COUNT(*) " .
            "FROM $commentsTable " .
            "WHERE $commentsTable.$commentsIdPost = $postsTable.$postsIdPost";
        $noCommentsPostId = $connection->fetchColumn(
            "SELECT $postsIdPost, ($commentsNumberSql) AS comments_number " .
            "FROM $postsTable " .
            "WHERE comments_number = 0 AND $postsIdEditor IS NULL LIMIT 1"
        );
        assert($noCommentsPostId !== false, 'A post without any comments is not found.');

        $includePaths = [
            [Post::REL_EDITOR, User::REL_COMMENTS],
            [Post::REL_COMMENTS, Comment::REL_EMOTIONS],
            [Post::REL_COMMENTS, Comment::REL_POST, Post::REL_USER],
        ];
        $this->assertNotNull(
            $model = $crud->withIncludes($includePaths)->read((string)$noCommentsPostId)
        );

        self::assertNull($model->{Post::REL_EDITOR});
        self::assertEmpty($model->{Post::REL_COMMENTS}->getData());
    }

    /**
     * Check 'read' with included paths.
     * @throws Exception
     * @throws DBALException
     */
    public function testUntypedReadWithIncludes(): void
    {
        /** @var Crud $crud */
        $crud = $this->createCrud(PostsApi::class);
        $this->assertTrue($crud instanceof Crud);

        $index = '18';
        $includePaths = [
            [Post::REL_BOARD],
            [Post::REL_COMMENTS],
            [Post::REL_COMMENTS, Comment::REL_EMOTIONS],
            [Post::REL_COMMENTS, Comment::REL_POST, Post::REL_USER],
        ];
        $this->assertNotNull(
            $model = $crud->shouldBeUntyped()->withIncludes($includePaths)->read($index)
        );

        $this->assertSame((string)$index, $model->{Post::FIELD_ID});
        $this->assertTrue(is_string($model->{Model::FIELD_CREATED_AT}));

        $board = $model->{Post::REL_BOARD};
        $this->assertEquals(Board::class, get_class($board));
        $this->assertEquals($model->{Post::FIELD_ID_BOARD}, $board->{Board::FIELD_ID});

        /** @var PaginatedDataInterface $commentsRel */
        $commentsRel = $model->{Post::REL_COMMENTS};
        $comments = $commentsRel->getData();
        $hasMore = $commentsRel->hasMoreItems();
        $offset = $commentsRel->getOffset();
        $limit = $commentsRel->getLimit();
        $this->assertNotEmpty($comments);
        $this->assertCount(3, $comments);
        $this->assertEquals(Comment::class, get_class($comments[0]));
        $this->assertEquals($index, $comments[0]->{Comment::FIELD_ID_POST});
        $this->assertTrue($hasMore);
        $this->assertCount(self::DEFAULT_PAGE, $comments);
        $this->assertEquals(0, $offset);
        $this->assertEquals(self::DEFAULT_PAGE, $limit);
        $this->assertTrue(is_string($comments[0]->{Model::FIELD_CREATED_AT}));

        /** @var PaginatedDataInterface $emotions */
        $emotions = $comments[0]->{Comment::REL_EMOTIONS};
        $this->assertCount(2, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertEquals(0, $emotions->getOffset());
        $this->assertEquals(self::DEFAULT_PAGE, $emotions->getLimit());
        $this->assertTrue(is_string($emotions->getData()[0]->{Model::FIELD_CREATED_AT}));

        $emotions = $comments[1]->{Comment::REL_EMOTIONS};
        $this->assertCount(1, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());

        $comment = $comments[2];
        $emotions = $comment->{Comment::REL_EMOTIONS};
        $this->assertCount(3, $emotions->getData());
        $this->assertFalse($emotions->hasMoreItems());
        $this->assertSame(0, $emotions->getOffset());
        $this->assertSame(self::DEFAULT_PAGE, $emotions->getLimit());

        $this->assertNotNull($post = $comment->{Comment::REL_POST});
        $this->assertNotNull($user = $post->{Post::REL_USER});

        // check no data for relationships we didn't ask to download
        $this->assertFalse(property_exists($user, User::REL_ROLE));
        $this->assertFalse(property_exists($user, User::REL_COMMENTS));
    }

    /**
     * Check 'read' with included paths where could be nulls.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadWithNullableInclude(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $index = '18';

        // check that editor relationship for selected post is `null`
        /** @noinspection SqlDialectInspection */
        $query = 'SELECT ' . Post::FIELD_ID_EDITOR . ' FROM ' . Post::TABLE_NAME .
            ' WHERE ' . Post::FIELD_ID . " = $index";
        $idEditor = $this->connection->query($query)->fetch(PDO::FETCH_NUM)[0];
        $this->assertNull($idEditor);

        $includePaths = [
            [Post::REL_EDITOR],
        ];

        $this->assertNotNull(
            $model = $crud->withIncludes($includePaths)->read($index)
        );
        $this->assertNull($model->{Post::REL_EDITOR});
    }

    /**
     * Test index.
     * @throws Exception
     * @throws DBALException
     */
    public function testIndex(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $includePaths = [
            [Post::REL_BOARD],
            [Post::REL_COMMENTS],
            [Post::REL_COMMENTS, Comment::REL_EMOTIONS],
            [Post::REL_COMMENTS, Comment::REL_POST, Post::REL_USER],
        ];

        $sortParameters = [
            Post::FIELD_ID_BOARD => false,
            Post::FIELD_TITLE => true,
        ];
        $pagingOffset = 1;
        $pagingSize = 2;
        $filters = [
            Post::FIELD_TITLE => [
                FilterParameterInterface::OPERATION_LIKE => ['%'],
            ],
            Post::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_LESS_THAN => ['5'],
            ],
        ];

        $data = $crud
            ->withFilters($filters)
            ->combineWithAnd()
            ->withSorts($sortParameters)
            ->withIncludes($includePaths)
            ->withPaging($pagingOffset, $pagingSize)
            ->index();

        $this->assertNotEmpty($data->getData());
        $this->assertCount($pagingSize, $data->getData());
        $this->assertTrue(($firstPost = $data->getData()[0]) instanceof Post);
        $this->assertEquals(20, $firstPost->{Post::FIELD_ID});
        $this->assertEquals(9, $data->getData()[1]->{Post::FIELD_ID});
        $this->assertTrue($data->isCollection());
        $this->assertEquals($pagingOffset, $data->getOffset());
        $this->assertEquals($pagingSize, $data->getLimit());

        // make sure that `comments` and `comments -> emotions` are sorted.
        $this->assertCount(2, $comments = $firstPost->{Post::REL_COMMENTS}->getData());
        $this->assertEquals(3, $comments[0]->{Comment::FIELD_ID});
        $this->assertEquals(72, $comments[1]->{Comment::FIELD_ID});
        $this->assertCount(3, $emotions = $comments[1]->{Comment::REL_EMOTIONS}->getData());
        $this->assertEquals(1, $emotions[0]->{Emotion::FIELD_ID});
        $this->assertEquals(2, $emotions[1]->{Emotion::FIELD_ID});
        $this->assertEquals(4, $emotions[2]->{Emotion::FIELD_ID});
    }

    /**
     * Test index.
     * @throws Exception
     * @throws DBALException
     */
    public function testIndexWithoutPaging(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $pagingOffset = 1;
        $pagingSize = 2;

        $crud
            ->combineWithAnd()
            ->withPaging($pagingOffset, $pagingSize);

        $data = $crud
            ->withoutPaging()
            ->index();

        $this->assertNotEmpty($data->getData());
        $this->assertGreaterThan($pagingSize, count($data->getData()));
    }

    /**
     * Test index.
     * @throws Exception
     * @throws DBALException
     */
    public function testIndexFilterOperationOnRelationshipById(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $pagingOffset = 0;
        $pagingSize = 20;
        $filters = [
            Post::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_IN => [2, 4],
            ],
        ];

        $data = $crud->withFilters($filters)->withPaging($pagingOffset, $pagingSize)->index();

        $this->assertCount(6, $data->getData());
    }

    /**
     * Test index.
     * @throws Exception
     * @throws DBALException
     */
    public function testIndexFilterOperationOnRelationshipByName(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $filters = [
            User::FIELD_ID => [
                FilterParameterInterface::OPERATION_IN => [2, 4],
            ],
        ];

        $data = $crud
            ->withRelationshipFilters(Post::REL_USER, $filters)
            ->index();

        $this->assertCount(6, $data->getData());
    }

    /**
     * Test index.
     * @throws Exception
     * @throws DBALException
     */
    public function testCommentsIndex(): void
    {
        // check that API returns comments from only specific user (as configured in Comments API)
        $expectedUserId = 1;

        $crud = $this->createCrud(CommentsApi::class);

        $data = $crud->index();

        $this->assertNotEmpty($comments = $data->getData());
        foreach ($comments as $comment) {
            $this->assertEquals($expectedUserId, $comment->{Comment::FIELD_ID_USER});
        }
    }

    /**
     * Test read relationship.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadRelationship(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $pagingOffset = 1;
        $pagingSize = 2;
        $postFilters = [
            Post::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [1],
            ],
        ];
        $commentFilters = [
            Comment::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_LESS_THAN => [5],
            ],
            Comment::FIELD_TEXT => [
                FilterParameterInterface::OPERATION_LIKE => ['%'],
            ],
        ];
        $commentSorts = [
            Comment::FIELD_ID_USER => false,
            Comment::FIELD_TEXT => true,
        ];

        $data = $crud
            ->withFilters($postFilters)
            ->withPaging($pagingOffset, $pagingSize)
            ->indexRelationship(Post::REL_COMMENTS, $commentFilters, $commentSorts);

        $this->assertNotEmpty($data->getData());
        $this->assertCount($pagingSize, $data->getData());
        $this->assertEquals(45, $data->getData()[0]->{Comment::FIELD_ID});
        $this->assertEquals(91, $data->getData()[1]->{Comment::FIELD_ID});
        $this->assertTrue($data->isCollection());
        $this->assertEquals($pagingOffset, $data->getOffset());
        $this->assertEquals($pagingSize, $data->getLimit());
    }

    /**
     * Test read relationship.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadRelationshipWithOrFilters(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        // will select all comments for Posts with ID 1 or 2...
        $postFilters = [
            Post::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [1],
                FilterParameterInterface::OPERATION_IN => [2],
            ],
        ];
        // ... where comments have user ID 1 or 2 as an author and ...
        $commentFilters = [
            Comment::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_IN => [1, 2],
            ],
        ];
        // ... sort them by parent post ID
        $commentSorts = [
            Comment::FIELD_ID_POST => true,
        ];

        $data = $crud
            ->combineWithOr()
            ->withFilters($postFilters)
            ->indexRelationship(Post::REL_COMMENTS, $commentFilters, $commentSorts);

        $this->assertNotEmpty($comments = $data->getData());
        $this->assertCount(3, $comments);
        $this->assertEquals(9, $data->getData()[0]->{Comment::FIELD_ID});
        $this->assertEquals(91, $data->getData()[1]->{Comment::FIELD_ID});
        $this->assertEquals(57, $data->getData()[2]->{Comment::FIELD_ID});
        $this->assertTrue($data->isCollection());
    }

    /**
     * Test read relationship.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadRelationshipIdentities(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $pagingOffset = 1;
        $pagingSize = 2;
        $postFilters = [
            Post::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [1],
            ],
        ];
        $commentFilters = [
            Comment::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_LESS_THAN => [5],
            ],
            Comment::FIELD_TEXT => [
                FilterParameterInterface::OPERATION_LIKE => ['%'],
            ],
        ];
        $commentSorts = [
            Comment::FIELD_ID_USER => false,
            Comment::FIELD_TEXT => true,
        ];

        $data = $crud
            ->withFilters($postFilters)
            ->withPaging($pagingOffset, $pagingSize)
            ->indexRelationshipIdentities(Post::REL_COMMENTS, $commentFilters, $commentSorts);

        $this->assertEquals([45, 91, 9], $data);
    }

    /**
     * Test read relationship.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadRelationshipIdentitiesForBelongsToRelationship(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createCrud(PostsApi::class)->indexRelationshipIdentities(Post::REL_USER);
    }

    /**
     * Test index.
     * @throws Exception
     * @throws DBALException
     */
    public function testIndexWithFilterByBooleanColumn(): void
    {
        $crud = $this->createCrud(UsersApi::class);

        $filters = [
            User::FIELD_IS_ACTIVE => [
                FilterParameterInterface::OPERATION_EQUALS => [true],
            ],
        ];

        $data = $crud->withFilters($filters)->index();
        $users = $data->getData();
        $this->assertNotEmpty($users);

        /** @noinspection SqlDialectInspection */
        $query = 'SELECT COUNT(*) FROM ' . User::TABLE_NAME . ' WHERE ' . User::FIELD_IS_ACTIVE . ' = 1';
        $actives = $this->connection->query($query)->fetch(PDO::FETCH_NUM)[0];

        $this->assertEquals($actives, count($users));
    }

    /**
     * Test index.
     * @throws Exception
     * @throws DBALException
     */
    public function testIndexWithEqualsOperator(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $index = 2;
        $filters = [
            Post::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => [$index],
            ],
        ];

        $data = $crud->withFilters($filters)->index();

        $this->assertNotEmpty($data->getData());
        $this->assertCount(1, $data->getData());
        $this->assertEquals($index, $data->getData()[0]->{Post::FIELD_ID});
        $this->assertTrue($data->isCollection());
    }

    /**
     * Test read typed row.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadRow(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $builder = $crud->withIndexFilter('1')->createIndexBuilder();
        $row = $crud->fetchRow($builder, Post::class);

        $this->assertTrue(is_int($row[Post::FIELD_ID_BOARD]));
        $this->assertTrue(is_string($row[Post::FIELD_TEXT]));
    }

    /**
     * Test read typed row.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadRows(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $ids = $crud->withIndexesFilter([1, 2])->indexIdentities();

        $this->assertEquals([1, 2], $ids);
    }

    /**
     * Test invalid argument.
     * @throws DBALException
     */
    public function testInvalidInputWithEmptyIndexList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $crud = $this->createCrud(PostsApi::class);

        $crud->withIndexesFilter([]);
    }

    /**
     * Test read typed row.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadUntypedRow(): void
    {
        /** @var Crud $crud */
        $crud = $this->createCrud(PostsApi::class);
        $this->assertTrue($crud instanceof Crud);

        $builder = $crud->shouldBeUntyped()->withIndexFilter('1')->createIndexBuilder();
        $row = $crud->fetchRow($builder, Post::class);

        $this->assertTrue(is_string($row[Post::FIELD_ID_BOARD]));
        $this->assertTrue(is_string($row[Post::FIELD_CREATED_AT]));
    }

    /**
     * Test read typed row.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadUntypedModelWithCustomColumnBuilder(): void
    {
        /** @var Crud $crud */
        $crud = $this->createCrud(PostsApi::class);
        $this->assertTrue($crud instanceof Crud);

        $model = $crud
            ->shouldBeUntyped()
            ->withColumnMapper(function (string $aliasName, string $columnName, ModelQueryBuilder $builder): string {
                // a bit naive implementation but fine for testing purposes
                $quotedColumnName = $builder->quoteDoubleIdentifier($aliasName, $columnName);
                $dateTimeColumns = [Model::FIELD_CREATED_AT, Model::FIELD_UPDATED_AT, Model::FIELD_DELETED_AT];
                if (in_array($columnName, $dateTimeColumns) === true) {
                    // emulate output datetime in JSON API as 2015-05-22T14:56:29.000Z
                    // this function is specific for SQLite
                    return "strftime('%Y-%m-%dT%H:%M:%fZ', $quotedColumnName) as $columnName";
                }

                return $columnName;
            })
            ->read('1');

        $this->assertTrue(is_string($model->{Post::FIELD_ID_BOARD}));
        $this->assertMatchesRegularExpression(
            '/\d\d\d\d-\d\d-\d\dT\d\d:\d\d:\d\d.\d\d\dZ/',
            $model->{Model::FIELD_CREATED_AT}
        );
    }

    /**
     * Test read typed row.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadColumn(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $column = $crud
            ->withFilters([
                Post::FIELD_ID => [
                    FilterParameterInterface::OPERATION_GREATER_OR_EQUALS => [5],
                    FilterParameterInterface::OPERATION_LESS_OR_EQUALS => [8],
                ],
            ])
            ->withSorts([
                Post::FIELD_ID => false,
            ])
            ->indexIdentities();

        $this->assertEquals([8, 7, 6, 5], $column);
    }

    /**
     * Test read typed row.
     * @throws Exception
     * @throws DBALException
     */
    public function testReadUntypedColumn(): void
    {
        /** @var Crud $crud */
        $crud = $this->createCrud(PostsApi::class);
        $this->assertTrue($crud instanceof Crud);

        $column = $crud
            ->shouldBeUntyped()
            ->withFilters([
                Post::FIELD_ID => [
                    FilterParameterInterface::OPERATION_GREATER_OR_EQUALS => [5],
                    FilterParameterInterface::OPERATION_LESS_OR_EQUALS => [8],
                ],
            ])
            ->withSorts([
                Post::FIELD_ID => false,
            ])
            ->indexIdentities();

        $this->assertSame(['8', '7', '6', '5'], $column);
    }

    /**
     * Test index.
     * @throws Exception
     * @throws DBALException
     */
    public function testCount(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $filters = [
            Post::FIELD_ID_USER => [
                FilterParameterInterface::OPERATION_LESS_THAN => ['5'],
            ],
            Post::FIELD_TITLE => [
                FilterParameterInterface::OPERATION_LIKE => ['%'],
            ],
        ];

        $result = $crud->withFilters($filters)->count();

        $this->assertEquals(14, $result);
    }

    /**
     * Test index.
     * @throws Exception
     * @throws DBALException
     */
    public function testCountWithFilterInRelationship(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $filters = [
            User::FIELD_ID => [
                FilterParameterInterface::OPERATION_EQUALS => ['1'],
            ],
        ];

        $result = $crud->withRelationshipFilters(Post::REL_USER, $filters)->count();

        $this->assertEquals(4, $result);
    }

    /**
     * Test check resource exists in relationship.
     * @throws Exception
     * @throws DBALException
     */
    public function testHasInRelationship(): void
    {
        $crud = $this->createCrud(PostsApi::class);

        $this->assertFalse($crud->hasInRelationship('1', Post::REL_COMMENTS, '1'));
        $this->assertTrue($crud->hasInRelationship('1', Post::REL_COMMENTS, '9'));
    }

    /**
     * @param string $class
     * @return CrudInterface
     * @throws Exception
     * @throws DBALException
     */
    private function createCrud(string $class): CrudInterface
    {
        $container = new Container();

        $container[FormatterFactoryInterface::class] = $formatterFactory = new FormatterFactory();
        $container[Connection::class] = $this->connection = $this->initDb();
        $container[FactoryInterface::class] = $factory = new Factory($container);
        $container[ModelSchemaInfoInterface::class] = $modelSchemas = $this->getModelSchemas();

        $container[RelationshipPaginationStrategyInterface::class]
            = new BasicRelationshipPaginationStrategy(self::DEFAULT_PAGE);

        return new $class($container);
    }
}
