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

namespace Whoa\Tests\Flute\Http\Query;

use Neomerx\JsonApi\Contracts\Http\Query\BaseQueryParserInterface;
use Whoa\Container\Container;
use Whoa\Contracts\L10n\FormatterFactoryInterface;
use Whoa\Flute\Contracts\Schema\SchemaInterface;
use Whoa\Flute\Contracts\Validation\JsonApiQueryParserInterface;
use Whoa\Flute\L10n\Messages;
use Whoa\Flute\Validation\JsonApi\Execution\JsonApiErrorCollection;
use Whoa\Flute\Validation\JsonApi\Execution\JsonApiQueryRulesSerializer;
use Whoa\Flute\Validation\JsonApi\QueryParser;
use Whoa\Tests\Flute\Data\L10n\FormatterFactory;
use Whoa\Tests\Flute\Data\Models\Comment;
use Whoa\Tests\Flute\Data\Schemas\CommentSchema;
use Whoa\Tests\Flute\Data\Schemas\PostSchema;
use Whoa\Tests\Flute\Data\Validation\JsonQueries\AllowEverythingRules;
use Whoa\Tests\Flute\Data\Validation\JsonQueries\CommentsIndexRules;
use Whoa\Tests\Flute\TestCase;
use Whoa\Validation\Captures\CaptureAggregator;
use Whoa\Validation\Errors\ErrorAggregator;
use Whoa\Validation\Execution\BlockSerializer;
use Whoa\Validation\Execution\ContextStorage;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * @package Whoa\Tests\Flute
 */
class QueryParserTest extends TestCase
{
    /**
     * Parser test.
     */
    public function testParsePaging(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        // check both in the input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_PAGE => [
                JsonApiQueryParserInterface::PARAM_PAGING_OFFSET => '10',
                JsonApiQueryParserInterface::PARAM_PAGING_LIMIT => '20',
            ],
        ]);
        $this->assertSame(10, $parser->getPagingOffset());
        $this->assertSame(20, $parser->getPagingLimit());
        $this->assertTrue($parser->hasPaging());

        // check no offset in the input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_PAGE => [
                JsonApiQueryParserInterface::PARAM_PAGING_LIMIT => '20',
            ],
        ]);
        $this->assertSame(0, $parser->getPagingOffset());
        $this->assertSame(20, $parser->getPagingLimit());

        // check no offset & limit in the input
        $parser->parse(null, []);
        $this->assertSame(0, $parser->getPagingOffset());
        $this->assertSame(30, $parser->getPagingLimit());
        $this->assertFalse($parser->hasPaging());
    }

    /**
     * Parser test.
     */
    public function testParseInclude(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $relUser = Comment::REL_USER;
        $relPost = Comment::REL_POST;

        // check with valid input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_INCLUDE => "$relUser,$relPost",
        ]);
        $includes = $this->deepIterableToArray($parser->getIncludes());

        // that's the format of parsed path: 'some.long.path' => ['some', 'long', 'path']
        $this->assertEquals([
            $relUser => [$relUser],
            $relPost => [$relPost],
        ], $includes);

        // check with invalid input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_INCLUDE => "$relUser,foo,$relPost,boo",
        ]);

        $exception = null;
        try {
            $this->deepIterableToArray($parser->getIncludes());
        } catch (JsonApiException $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertCount(2, $errors = $exception->getErrors());
        $errors = $errors->getArrayCopy();
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => BaseQueryParserInterface::PARAM_INCLUDE],
            $errors[0]->getSource()
        );
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => BaseQueryParserInterface::PARAM_INCLUDE],
            $errors[1]->getSource()
        );
    }

    /**
     * Parser test.
     */
    public function testParseSort(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $fieldText = Comment::FIELD_TEXT;
        $fieldFloat = Comment::FIELD_FLOAT;

        // check with valid input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_SORT => "+$fieldText,-$fieldFloat",
        ]);
        $sorts = $this->deepIterableToArray($parser->getSorts());
        $this->assertTrue($parser->hasSorts());

        $this->assertEquals([
            $fieldText => true,
            $fieldFloat => false,
        ], $sorts);

        // check with invalid input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_SORT => "-$fieldText,foo,$fieldFloat,boo",
        ]);

        $exception = null;
        try {
            $this->deepIterableToArray($parser->getSorts());
        } catch (JsonApiException $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertCount(2, $errors = $exception->getErrors());
        $errors = $errors->getArrayCopy();
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => BaseQueryParserInterface::PARAM_SORT],
            $errors[0]->getSource()
        );
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => BaseQueryParserInterface::PARAM_SORT],
            $errors[1]->getSource()
        );
    }

    /**
     * Parser test.
     */
    public function testParseFieldSets(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $commentText = CommentSchema::ATTR_TEXT;
        $commentUser = CommentSchema::REL_USER;
        $commentPost = CommentSchema::REL_POST;
        $postTitle = PostSchema::ATTR_TITLE;
        $postUser = PostSchema::REL_USER;
        $postComments = PostSchema::REL_COMMENTS;

        // check with valid input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_FIELDS => [
                CommentSchema::TYPE => "$commentText,$commentUser,$commentPost",
                PostSchema::TYPE => "$postTitle,$postUser,$postComments",
            ],
        ]);
        $fieldSets = $this->deepIterableToArray($parser->getFields());

        $this->assertEquals([
            CommentSchema::TYPE => [$commentText, $commentUser, $commentPost],
            PostSchema::TYPE => [$postTitle, $postUser, $postComments],
        ], $fieldSets);

        // check with invalid input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_FIELDS => [
                CommentSchema::TYPE => "$commentText,foo,$commentUser,$commentPost",
                PostSchema::TYPE => "$postTitle,$postUser,boo,$postComments",
                'UnknownType' => 'whatever',
            ],
        ]);

        $exception = null;
        try {
            $this->deepIterableToArray($parser->getFields());
        } catch (JsonApiException $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertCount(3, $errors = $exception->getErrors());
        $errors = $errors->getArrayCopy();
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => BaseQueryParserInterface::PARAM_FIELDS],
            $errors[0]->getSource()
        );
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => BaseQueryParserInterface::PARAM_FIELDS],
            $errors[1]->getSource()
        );
        $this->assertEquals(
            [ErrorInterface::SOURCE_PARAMETER => BaseQueryParserInterface::PARAM_FIELDS],
            $errors[2]->getSource()
        );
    }

    /**
     * Parser test.
     */
    public function testParseFilters(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $commentText = CommentSchema::ATTR_TEXT;
        $commentInt = CommentSchema::ATTR_INT;
        $commentBool = CommentSchema::ATTR_BOOL;

        // check with valid input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_FILTER => [
                $commentText => ['like' => '%foo%', 'not_in' => 'food,foolish'],
                $commentInt => ['gte' => '3', 'lte' => '9'],
                $commentBool => ['eq' => 'true'],
            ],
        ]);
        $this->assertTrue($parser->hasFilters());
        $filters = $this->deepIterableToArray($parser->getFilters());

        $this->assertSame([
            $commentText => ['like' => ['%foo%'], 'not_in' => ['food', 'foolish']],
            $commentInt => ['gte' => [3], 'lte' => [9]],
            $commentBool => ['eq' => [true]],
        ], $filters);

        // check with invalid input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_FILTER => [
                $commentText => ['like' => '%', 'not_in' => 'f,g'],
                $commentInt => ['gte' => '0', 'lte' => '11'],
            ],
        ]);

        $exception = null;
        try {
            $this->deepIterableToArray($parser->getFilters());
        } catch (JsonApiException $exception) {
        }
        $this->assertNotNull($exception);
        $this->assertCount(5, $errors = $exception->getErrors());
        foreach ($errors->getArrayCopy() as $error) {
            $this->assertEquals(
                [ErrorInterface::SOURCE_PARAMETER => BaseQueryParserInterface::PARAM_FILTER],
                $error->getSource()
            );
        }
    }

    /**
     * Parser test.
     */
    public function testParseEmptyFilterArguments(): void
    {
        $parser = $this->createParser(CommentsIndexRules::class);

        $commentText = CommentSchema::ATTR_TEXT;

        // check with valid input
        $parser->parse(null, [
            BaseQueryParserInterface::PARAM_FILTER => [
                $commentText => ['not_in' => ''],
            ],
        ]);
        $filters = $this->deepIterableToArray($parser->getFilters());

        $this->assertSame([
            $commentText => ['not_in' => []],
        ], $filters);
    }

    /**
     * Test query.
     */
    public function testGetFiltersWithInvalidValues1(): void
    {
        $this->expectException(\Whoa\Flute\Exceptions\InvalidQueryParametersException::class);

        $queryParameters = [
            BaseQueryParserInterface::PARAM_FILTER => [
                SchemaInterface::RESOURCE_ID => 'cannot be string',
            ],
        ];

        $this->deepIterableToArray(
            $this->createParser(CommentsIndexRules::class)->parse(null, $queryParameters)->getFilters()
        );
    }

    /**
     * Test query.
     */
    public function testGetFiltersWithInvalidValues2(): void
    {
        $this->expectException(\Neomerx\JsonApi\Exceptions\JsonApiException::class);

        $queryParameters = [
            BaseQueryParserInterface::PARAM_FILTER => [
                'UnknownField' => ['gte' => '0'],
            ],
        ];

        $this->deepIterableToArray(
            $this->createParser(CommentsIndexRules::class)->parse(null, $queryParameters)->getFilters()
        );
    }

    /**
     * Test query.
     */
    public function testGetFiltersWithInvalidValues3(): void
    {
        $this->expectException(\Whoa\Flute\Exceptions\InvalidQueryParametersException::class);

        $queryParameters = [
            BaseQueryParserInterface::PARAM_FILTER => [
                SchemaInterface::RESOURCE_ID => [
                    'in' => ['must be string but not array'],
                ],
            ],
        ];

        $this->deepIterableToArray(
            $this->createParser(CommentsIndexRules::class)->parse(null, $queryParameters)->getFilters()
        );
    }

    /**
     * Test query.
     *
     * @return void
     */
    public function testEmptyQueryParams(): void
    {
        $queryParameters = [];

        $parser = $this->createParser(CommentsIndexRules::class);

        $this->assertTrue($parser->parse(null, $queryParameters)->areFiltersWithAnd());
    }

    /**
     * Test query.
     */
    public function testInvalidEmptyFilter1(): void
    {
        $this->expectException(\Neomerx\JsonApi\Exceptions\JsonApiException::class);

        $queryParameters = [
            BaseQueryParserInterface::PARAM_FILTER => '',
        ];

        $this->createParser(CommentsIndexRules::class)->parse(null, $queryParameters)->areFiltersWithAnd();
    }

    /**
     * Test query.
     */
    public function testInvalidEmptyFilter2(): void
    {
        $this->expectException(\Neomerx\JsonApi\Exceptions\JsonApiException::class);

        $queryParameters = [
            BaseQueryParserInterface::PARAM_FILTER => [],
        ];

        $this->createParser(CommentsIndexRules::class)->parse(null, $queryParameters)->areFiltersWithAnd();
    }

    /**
     * Test query.
     */
    public function testInvalidFilterTooManyRootItems(): void
    {
        $this->expectException(\Neomerx\JsonApi\Exceptions\JsonApiException::class);

        $queryParameters = [
            BaseQueryParserInterface::PARAM_FILTER => [
                'or' => [
                    SchemaInterface::RESOURCE_ID => [
                        'in' => '3,5,7',
                    ],
                ],
                'xxx' => 'only one top-level element is allowed if AND/OR is used',
            ],
        ];

        $this->createParser(CommentsIndexRules::class)->parse(null, $queryParameters)->areFiltersWithAnd();
    }

    /**
     * Test query.
     * @return void
     */
    public function testTopLevelConditionWithOr(): void
    {
        $queryParameters = [
            BaseQueryParserInterface::PARAM_FILTER => [
                'or' => [
                    SchemaInterface::RESOURCE_ID => [
                        'in' => '3,5,7',
                    ],
                ],
            ],
        ];

        $parser = $this->createParser(CommentsIndexRules::class)->parse(null, $queryParameters);

        $this->assertFalse($parser->areFiltersWithAnd());

        $filters = $this->deepIterableToArray($parser->getFilters());
        $this->assertSame([
            SchemaInterface::RESOURCE_ID => [
                'in' => [3, 5, 7],
            ],
        ], $filters);
    }

    /**
     * Test validator that allows any input data.
     * @return void
     */
    public function testAllowAnyInput(): void
    {
        $relUser = Comment::REL_USER;
        $relPost = Comment::REL_POST;
        $fieldText = Comment::FIELD_TEXT;
        $fieldFloat = Comment::FIELD_FLOAT;
        $fieldInt = Comment::FIELD_INT;

        $parser = $this->createParser(AllowEverythingRules::class)->parse(null, [
            BaseQueryParserInterface::PARAM_FILTER => [
                $fieldText => ['like' => '%foo%', 'not_in' => 'food,foolish'],
                $fieldInt => ['gte' => '3', 'lte' => '9'],
            ],
            BaseQueryParserInterface::PARAM_FIELDS => [
                CommentSchema::TYPE => "$fieldText,$relUser,$relPost",
            ],
            BaseQueryParserInterface::PARAM_SORT => "+$fieldText,-$fieldFloat",
            BaseQueryParserInterface::PARAM_INCLUDE => "$relUser,$relPost",
            BaseQueryParserInterface::PARAM_PAGE => [
                JsonApiQueryParserInterface::PARAM_PAGING_OFFSET => '10',
                JsonApiQueryParserInterface::PARAM_PAGING_LIMIT => '20',
            ],
        ]);

        $this->assertSame([
            $fieldText => ['like' => ['%foo%'], 'not_in' => ['food', 'foolish']],
            $fieldInt => ['gte' => ['3'], 'lte' => ['9']],
        ], $this->deepIterableToArray($parser->getFilters()));
        $this->assertSame([
            CommentSchema::TYPE => [$fieldText, $relUser, $relPost],
        ], $this->deepIterableToArray($parser->getFields()));
        $this->assertSame([
            $fieldText => true,
            $fieldFloat => false,
        ], $this->deepIterableToArray($parser->getSorts()));
        $this->assertSame([
            $relUser => [$relUser],
            $relPost => [$relPost],
        ], $this->deepIterableToArray($parser->getIncludes()));
        $this->assertSame(10, $parser->getPagingOffset());
        $this->assertSame(20, $parser->getPagingLimit());
    }

    /**
     *
     * @param string $ruleClass
     * @return JsonApiQueryParserInterface
     */
    private function createParser(string $ruleClass): JsonApiQueryParserInterface
    {
        $ruleClasses = [
            CommentsIndexRules::class,
            AllowEverythingRules::class,
        ];

        $serializer = new JsonApiQueryRulesSerializer(new BlockSerializer());
        foreach ($ruleClasses as $class) {
            $serializer->addRulesFromClass($class);
        }
        $serializedData = $serializer->getData();

        $container = new Container();
        $container[FormatterFactoryInterface::class] = $formatterFactory = new FormatterFactory();

        $blocks = JsonApiQueryRulesSerializer::readBlocks($serializedData);
        return new QueryParser(
            $ruleClass,
            JsonApiQueryRulesSerializer::class,
            $serializedData,
            new ContextStorage($blocks, $container),
            new CaptureAggregator(),
            new ErrorAggregator(),
            new JsonApiErrorCollection($formatterFactory->createFormatter(Messages::NAMESPACE_NAME)),
            $formatterFactory
        );
    }
}
