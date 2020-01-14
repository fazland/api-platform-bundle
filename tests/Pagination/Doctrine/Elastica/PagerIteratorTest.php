<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Pagination\Elastica;

use Cake\Chronos\Chronos;
use Elastica\Query;
use Elastica\Response;
use Elastica\Type;
use Fazland\ApiPlatformBundle\Pagination\Doctrine\Elastica\PagerIterator;
use Fazland\ApiPlatformBundle\Pagination\PageToken;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine\ElasticaDocumentManagerMockTrait;
use Fazland\ApiPlatformBundle\Tests\Pagination\TestObject;
use Fazland\ODM\Elastica\Collection\CollectionInterface;
use Fazland\ODM\Elastica\Metadata\DocumentMetadata;
use Fazland\ODM\Elastica\Metadata\FieldMetadata;
use Fazland\ODM\Elastica\Search\Search;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class PagerIteratorTest extends TestCase
{
    use ElasticaDocumentManagerMockTrait;

    private PagerIterator $iterator;
    private CollectionInterface $collection;
    private Search $search;
    private Type $type;
    private Query $query;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->documentManager = null;
        $class = new DocumentMetadata(new \ReflectionClass(TestObject::class));
        $class->collectionName = 'test-object/test-object';

        $id = new FieldMetadata($class, 'id');
        $id->identifier = true;
        $id->type = 'string';
        $id->fieldName = 'id';
        $class->identifier = $id;

        $field = new FieldMetadata($class, 'timestamp');
        $field->type = 'datetime_immutable';
        $field->fieldName = 'timestamp';

        $class->addAttributeMetadata($id);
        $class->addAttributeMetadata($field);

        $documentManager = $this->getDocumentManager();
        $documentManager->getMetadataFactory()->setMetadataFor(TestObject::class, $class);

        $this->collection = $documentManager->getCollection(TestObject::class);

        $this->query = new Query();
        $this->query
            ->setSort(['timestamp', 'id'])
            ->setSize(3)
        ;

        $this->search = $this->collection->createSearch($documentManager, $this->query);
        $this->iterator = new PagerIterator($this->search, ['timestamp', 'id']);
        $this->iterator->setPageSize(3);
    }

    public function testPagerShouldGenerateFirstPageWithToken(): void
    {
        $expectedQuery = [
            'query' => [
                'bool' => (object) [],
            ],
            '_source' => [
                'id',
                'timestamp',
            ],
            'sort' => [
                ['timestamp' => 'asc'],
                ['id' => 'asc'],
            ],
            'size' => 3,
        ];

        $response = new Response([
            'took' => 1,
            'timed_out' => false,
            '_shards' => [
                'total' => 3,
                'successful' => 3,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 1000,
                'max_score' => 0.0,
                'hits' => [
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'b4902bde-28d2-4ff9-8971-8bfeb3e943c1',
                            'timestamp' => '1991-11-24 00:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe',
                            'timestamp' => '1991-11-24 01:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed',
                            'timestamp' => '1991-11-24 02:00:00',
                        ],
                    ],
                ],
            ],
        ], 200);

        $this->client->request('test-object/test-object/_search', 'GET', $expectedQuery, [])
            ->willReturn($response)
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag([]);

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            new TestObject('b4902bde-28d2-4ff9-8971-8bfeb3e943c1', new Chronos('1991-11-24 00:00:00')),
            new TestObject('191a54d8-990c-4ea7-9a23-0aed29d1fffe', new Chronos('1991-11-24 01:00:00')),
            new TestObject('9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', new Chronos('1991-11-24 02:00:00')),
        ], \iterator_to_array($this->iterator));

        self::assertEquals('bfdew0_1_1jvdwz4', (string) $this->iterator->getNextPageToken());
    }

    public function testPagerShouldGenerateSecondPageWithTokenAndLastPage(): void
    {
        $expectedQuery = [
            'query' => [
                'bool' => [
                    'filter' => [[
                        'range' => [
                            'timestamp' => [
                                'gte' => '1991-11-24T02:00:00+0000',
                            ],
                        ],
                    ]],
                ],
            ],
            '_source' => [
                'id',
                'timestamp',
            ],
            'sort' => [
                ['timestamp' => 'asc'],
                ['id' => 'asc'],
            ],
            'size' => 4,
        ];

        $response = new Response([
            'took' => 1,
            'timed_out' => false,
            '_shards' => [
                'total' => 4,
                'successful' => 4,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 1000,
                'max_score' => 0.0,
                'hits' => [
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed',
                            'timestamp' => '1991-11-24 02:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade',
                            'timestamp' => '1991-11-24 03:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '84810e2e-448f-4f58-acb8-4db1381f5de3',
                            'timestamp' => '1991-11-24 04:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6',
                            'timestamp' => '1991-11-24 05:00:00',
                        ],
                    ],
                ],
            ],
        ], 200);

        $this->client->request('test-object/test-object/_search', 'GET', $expectedQuery, [])
            ->willReturn($response)
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => 'bfdew0_1_1jvdwz4']);

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            new TestObject('af6394a4-7344-4fe8-9748-e6c67eba5ade', new Chronos('1991-11-24 03:00:00')),
            new TestObject('84810e2e-448f-4f58-acb8-4db1381f5de3', new Chronos('1991-11-24 04:00:00')),
            new TestObject('eadd7470-95f5-47e8-8e74-083d45c307f6', new Chronos('1991-11-24 05:00:00')),
        ], \iterator_to_array($this->iterator));

        self::assertEquals('bfdn80_1_cukvcs', (string) $this->iterator->getNextPageToken());
    }

    public function testOffsetShouldWork(): void
    {
        $expectedQuery = [
            'query' => [
                'bool' => (object) [],
            ],
            '_source' => [
                'id',
                'timestamp',
            ],
            'sort' => [
                ['timestamp' => 'asc'],
                ['id' => 'asc'],
            ],
            'size' => 3,
        ];

        $response = new Response([
            'took' => 1,
            'timed_out' => false,
            '_shards' => [
                'total' => 3,
                'successful' => 3,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 1000,
                'max_score' => 0.0,
                'hits' => [
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'b4902bde-28d2-4ff9-8971-8bfeb3e943c1',
                            'timestamp' => '1991-11-24 00:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe',
                            'timestamp' => '1991-11-24 01:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '84810e2e-448f-4f58-acb8-4db1381f5de3',
                            'timestamp' => '1991-11-24 01:00:00',
                        ],
                    ],
                ],
            ],
        ], 200);

        $this->client->request('test-object/test-object/_search', 'GET', $expectedQuery, [])
            ->willReturn($response)
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag([]);

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            new TestObject('b4902bde-28d2-4ff9-8971-8bfeb3e943c1', new Chronos('1991-11-24 00:00:00')),
            new TestObject('191a54d8-990c-4ea7-9a23-0aed29d1fffe', new Chronos('1991-11-24 01:00:00')),
            new TestObject('84810e2e-448f-4f58-acb8-4db1381f5de3', new Chronos('1991-11-24 01:00:00')),
        ], \iterator_to_array($this->iterator));

        self::assertEquals(2, $this->iterator->getNextPageToken()->getOffset());

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => 'bfdc40_2_hzr9o9']);

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        $expectedQuery = [
            'query' => [
                'bool' => [
                    'filter' => [[
                        'range' => [
                            'timestamp' => [
                                'gte' => '1991-11-24T01:00:00+0000',
                            ],
                        ],
                    ]],
                ],
            ],
            '_source' => [
                'id',
                'timestamp',
            ],
            'sort' => [
                ['timestamp' => 'asc'],
                ['id' => 'asc'],
            ],
            'size' => 5,
        ];

        $response = new Response([
            'took' => 1,
            'timed_out' => false,
            '_shards' => [
                'total' => 5,
                'successful' => 5,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 1000,
                'max_score' => 0.0,
                'hits' => [
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe',
                            'timestamp' => '1991-11-24 01:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '84810e2e-448f-4f58-acb8-4db1381f5de3',
                            'timestamp' => '1991-11-24 01:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed',
                            'timestamp' => '1991-11-24 01:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade',
                            'timestamp' => '1991-11-24 01:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6',
                            'timestamp' => '1991-11-24 02:00:00',
                        ],
                    ],
                ],
            ],
        ], 200);

        $this->client->request('test-object/test-object/_search', 'GET', $expectedQuery, [])
            ->willReturn($response)
        ;

        self::assertEquals([
            new TestObject('9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', new Chronos('1991-11-24 01:00:00')),
            new TestObject('af6394a4-7344-4fe8-9748-e6c67eba5ade', new Chronos('1991-11-24 01:00:00')),
            new TestObject('eadd7470-95f5-47e8-8e74-083d45c307f6', new Chronos('1991-11-24 02:00:00')),
        ], \iterator_to_array($this->iterator));
    }

    public function testPagerShouldReturnFirstPageWithTimestampDifference(): void
    {
        $expectedQuery = [
            'query' => [
                'bool' => [
                    'filter' => [[
                        'range' => [
                            'timestamp' => [
                                'gte' => '1991-11-24T02:00:00+0000',
                            ],
                        ],
                    ]],
                ],
            ],
            '_source' => [
                'id',
                'timestamp',
            ],
            'sort' => [
                ['timestamp' => 'asc'],
                ['id' => 'asc'],
            ],
            'size' => 4,
        ];

        $response = new Response([
            'took' => 1,
            'timed_out' => false,
            '_shards' => [
                'total' => 4,
                'successful' => 4,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 1000,
                'max_score' => 0.0,
                'hits' => [
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed',
                            'timestamp' => '1991-11-24 02:30:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade',
                            'timestamp' => '1991-11-24 03:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '84810e2e-448f-4f58-acb8-4db1381f5de3',
                            'timestamp' => '1991-11-24 04:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6',
                            'timestamp' => '1991-11-24 05:00:00',
                        ],
                    ],
                ],
            ],
        ], 200);

        $this->client->request('test-object/test-object/_search', 'GET', $expectedQuery, [])
            ->willReturn($response)
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => 'bfdew0_1_1jvdwz4']); // This token represents a request with the 02:00:00 timestamp

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            new TestObject('9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', new Chronos('1991-11-24 02:30:00')),
            new TestObject('af6394a4-7344-4fe8-9748-e6c67eba5ade', new Chronos('1991-11-24 03:00:00')),
            new TestObject('84810e2e-448f-4f58-acb8-4db1381f5de3', new Chronos('1991-11-24 04:00:00')),
        ], \iterator_to_array($this->iterator));

        self::assertEquals('bfdkg0_1_1xirtcr', (string) $this->iterator->getNextPageToken());
    }

    public function testPagerShouldReturnFirstPageWithChecksumDifference(): void
    {
        $expectedQuery = [
            'query' => [
                'bool' => [
                    'filter' => [[
                        'range' => [
                            'timestamp' => [
                                'gte' => '1991-11-24T02:00:00+0000',
                            ],
                        ],
                    ]],
                ],
            ],
            '_source' => [
                'id',
                'timestamp',
            ],
            'sort' => [
                ['timestamp' => 'asc'],
                ['id' => 'asc'],
            ],
            'size' => 4,
        ];

        $response = new Response([
            'took' => 1,
            'timed_out' => false,
            '_shards' => [
                'total' => 4,
                'successful' => 4,
                'failed' => 0,
            ],
            'hits' => [
                'total' => 1000,
                'max_score' => 0.0,
                'hits' => [
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade',
                            'timestamp' => '1991-11-24 02:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed',
                            'timestamp' => '1991-11-24 03:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe',
                            'timestamp' => '1991-11-24 04:00:00',
                        ],
                    ],
                    [
                        '_index' => 'test-object',
                        '_type' => 'test-object',
                        '_score' => 1.0,
                        '_source' => [
                            'id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6',
                            'timestamp' => '1991-11-24 05:00:00',
                        ],
                    ],
                ],
            ],
        ], 200);

        $this->client->request('test-object/test-object/_search', 'GET', $expectedQuery, [])
            ->willReturn($response)
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => 'bfdew0_1_1jvdwz4']); // This token represents a request with the 02:00:00 timestamp

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            new TestObject('af6394a4-7344-4fe8-9748-e6c67eba5ade', new Chronos('1991-11-24 02:00:00')),
            new TestObject('9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', new Chronos('1991-11-24 03:00:00')),
            new TestObject('191a54d8-990c-4ea7-9a23-0aed29d1fffe', new Chronos('1991-11-24 04:00:00')),
        ], \iterator_to_array($this->iterator));

        self::assertEquals('bfdkg0_1_7gqxdp', (string) $this->iterator->getNextPageToken());
    }
}
