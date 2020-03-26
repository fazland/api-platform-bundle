<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Pagination\DBAL;

use Doctrine\DBAL\Cache\ArrayStatement;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Fazland\ApiPlatformBundle\Pagination\Doctrine\DBAL\PagerIterator;
use Fazland\ApiPlatformBundle\Pagination\PageToken;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine\EntityManagerMockTrait;
use PDO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class PagerIteratorTest extends TestCase
{
    use EntityManagerMockTrait;

    private QueryBuilder $queryBuilder;
    private PagerIterator $iterator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->entityManager = null;
        $this->getEntityManager();

        $this->queryBuilder = $this->connection->createQueryBuilder();
        $this->queryBuilder
            ->select('t.id', 't.timestamp')
            ->from('test_table', 't')
        ;

        $this->innerConnection->query('')->shouldNotBeCalled();

        $this->iterator = new PagerIterator($this->queryBuilder, ['timestamp', 'id']);
        $this->iterator->setPageSize(3);
    }

    public function testPagerShouldGenerateFirstPageWithToken(): void
    {
        $this->innerConnection->query('SELECT * FROM (SELECT t.id, t.timestamp FROM test_table t) x ORDER BY timestamp ASC, id ASC LIMIT 3')
            ->willReturn(new StdObjectStatement([
                ['id' => 'b4902bde-28d2-4ff9-8971-8bfeb3e943c1', 'timestamp' => '1991-11-24 00:00:00'],
                ['id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe', 'timestamp' => '1991-11-24 01:00:00'],
                ['id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', 'timestamp' => '1991-11-24 02:00:00'],
            ]))
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag([]);

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            ['id' => 'b4902bde-28d2-4ff9-8971-8bfeb3e943c1', 'timestamp' => '1991-11-24 00:00:00'],
            ['id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe', 'timestamp' => '1991-11-24 01:00:00'],
            ['id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', 'timestamp' => '1991-11-24 02:00:00'],
        ], \iterator_to_array($this->iterator));

        self::assertEquals('=MTk5MS0xMS0yNCAwMjowMDowMA==_1_1jvdwz4', (string) $this->iterator->getNextPageToken());
    }

    public function testPagerShouldGenerateSecondPageWithTokenAndLastPage(): void
    {
        $this->innerConnection->prepare('SELECT * FROM (SELECT t.id, t.timestamp FROM test_table t) x WHERE timestamp >= ? ORDER BY timestamp ASC, id ASC LIMIT 4')
            ->willReturn($stmt = $this->prophesize(Statement::class))
        ;

        $stmt->bindValue(1, '1991-11-24 02:00:00', \PDO::PARAM_STR)->willReturn();
        $stmt->setFetchMode(\PDO::FETCH_ASSOC)->willReturn();
        $stmt->execute()->willReturn(true);
        $stmt->closeCursor()->willReturn(true);

        $stmt->fetchAll(\PDO::FETCH_OBJ)
            ->willReturn([
                (object) ['id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', 'timestamp' => '1991-11-24 02:00:00'],
                (object) ['id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade', 'timestamp' => '1991-11-24 03:00:00'],
                (object) ['id' => '84810e2e-448f-4f58-acb8-4db1381f5de3', 'timestamp' => '1991-11-24 04:00:00'],
                (object) ['id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6', 'timestamp' => '1991-11-24 05:00:00'],
            ])
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => '=MTk5MS0xMS0yNCAwMjowMDowMA==_1_1jvdwz4']);

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            ['id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade', 'timestamp' => '1991-11-24 03:00:00'],
            ['id' => '84810e2e-448f-4f58-acb8-4db1381f5de3', 'timestamp' => '1991-11-24 04:00:00'],
            ['id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6', 'timestamp' => '1991-11-24 05:00:00'],
        ], \iterator_to_array($this->iterator));

        self::assertEquals('=MTk5MS0xMS0yNCAwNTowMDowMA==_1_cukvcs', (string) $this->iterator->getNextPageToken());
    }

    public function testOffsetShouldWork(): void
    {
        $this->innerConnection->query('SELECT * FROM (SELECT t.id, t.timestamp FROM test_table t) x ORDER BY timestamp ASC, id ASC LIMIT 3')
            ->willReturn(new StdObjectStatement([
                ['id' => 'b4902bde-28d2-4ff9-8971-8bfeb3e943c1', 'timestamp' => '1991-11-24 00:00:00'],
                ['id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe', 'timestamp' => '1991-11-24 01:00:00'],
                ['id' => '84810e2e-448f-4f58-acb8-4db1381f5de3', 'timestamp' => '1991-11-24 01:00:00'],
            ]))
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag([]);

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            ['id' => 'b4902bde-28d2-4ff9-8971-8bfeb3e943c1', 'timestamp' => '1991-11-24 00:00:00'],
            ['id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe', 'timestamp' => '1991-11-24 01:00:00'],
            ['id' => '84810e2e-448f-4f58-acb8-4db1381f5de3', 'timestamp' => '1991-11-24 01:00:00'],
        ], \iterator_to_array($this->iterator));

        self::assertEquals(2, $this->iterator->getNextPageToken()->getOffset());

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => '=MTk5MS0xMS0yNCAwMTowMDowMA==_2_hzr9o9']);

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        $this->innerConnection->prepare('SELECT * FROM (SELECT t.id, t.timestamp FROM test_table t) x WHERE timestamp >= ? ORDER BY timestamp ASC, id ASC LIMIT 5')
            ->willReturn($stmt = $this->prophesize(Statement::class))
        ;

        $stmt->bindValue(1, '1991-11-24 01:00:00', \PDO::PARAM_STR)->willReturn();
        $stmt->setFetchMode(\PDO::FETCH_ASSOC)->willReturn();
        $stmt->execute()->willReturn(true);
        $stmt->closeCursor()->willReturn(true);

        $stmt->fetchAll(\PDO::FETCH_OBJ)
            ->willReturn([
                (object) ['id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe', 'timestamp' => '1991-11-24 01:00:00'],
                (object) ['id' => '84810e2e-448f-4f58-acb8-4db1381f5de3', 'timestamp' => '1991-11-24 01:00:00'],
                (object) ['id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', 'timestamp' => '1991-11-24 01:00:00'],
                (object) ['id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade', 'timestamp' => '1991-11-24 01:00:00'],
                (object) ['id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6', 'timestamp' => '1991-11-24 02:00:00'],
            ])
        ;

        self::assertEquals([
            ['id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', 'timestamp' => '1991-11-24 01:00:00'],
            ['id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade', 'timestamp' => '1991-11-24 01:00:00'],
            ['id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6', 'timestamp' => '1991-11-24 02:00:00'],
        ], \iterator_to_array($this->iterator));
    }

    public function testPagerShouldReturnFirstPageWithTimestampDifference(): void
    {
        $this->innerConnection->prepare('SELECT * FROM (SELECT t.id, t.timestamp FROM test_table t) x WHERE timestamp >= ? ORDER BY timestamp ASC, id ASC LIMIT 4')
            ->willReturn($stmt = $this->prophesize(Statement::class));

        $stmt->bindValue(1, '1991-11-24 02:00:00', \PDO::PARAM_STR)->willReturn();
        $stmt->setFetchMode(\PDO::FETCH_ASSOC)->willReturn();
        $stmt->execute()->willReturn(true);
        $stmt->closeCursor()->willReturn(true);

        $stmt->fetchAll(\PDO::FETCH_OBJ)
            ->willReturn([
                (object) ['id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', 'timestamp' => '1991-11-24 02:30:00'],
                (object) ['id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade', 'timestamp' => '1991-11-24 03:00:00'],
                (object) ['id' => '84810e2e-448f-4f58-acb8-4db1381f5de3', 'timestamp' => '1991-11-24 04:00:00'],
                (object) ['id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6', 'timestamp' => '1991-11-24 05:00:00'],
            ])
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => '=MTk5MS0xMS0yNCAwMjowMDowMA==_1_1jvdwz4']); // This token represents a request with the 02:00:00 timestamp

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            ['id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', 'timestamp' => '1991-11-24 02:30:00'],
            ['id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade', 'timestamp' => '1991-11-24 03:00:00'],
            ['id' => '84810e2e-448f-4f58-acb8-4db1381f5de3', 'timestamp' => '1991-11-24 04:00:00'],
        ], \iterator_to_array($this->iterator));

        self::assertEquals('=MTk5MS0xMS0yNCAwNDowMDowMA==_1_1xirtcr', (string) $this->iterator->getNextPageToken());
    }

    public function testPagerShouldReturnFirstPageWithChecksumDifference(): void
    {
        $this->innerConnection->prepare('SELECT * FROM (SELECT t.id, t.timestamp FROM test_table t) x WHERE timestamp >= ? ORDER BY timestamp ASC, id ASC LIMIT 4')
            ->willReturn($stmt = $this->prophesize(Statement::class))
        ;

        $stmt->bindValue(1, '1991-11-24 02:00:00', \PDO::PARAM_STR)->willReturn();
        $stmt->setFetchMode(\PDO::FETCH_ASSOC)->willReturn();
        $stmt->execute()->willReturn(true);
        $stmt->closeCursor()->willReturn(true);

        $stmt->fetchAll(\PDO::FETCH_OBJ)
            ->willReturn([
                (object) ['id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade', 'timestamp' => '1991-11-24 02:00:00'],
                (object) ['id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', 'timestamp' => '1991-11-24 03:00:00'],
                (object) ['id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe', 'timestamp' => '1991-11-24 04:00:00'],
                (object) ['id' => 'eadd7470-95f5-47e8-8e74-083d45c307f6', 'timestamp' => '1991-11-24 05:00:00'],
            ])
        ;

        $request = $this->prophesize(Request::class);
        $request->query = new ParameterBag(['continue' => '=MTk5MS0xMS0yNCAwMjowMDowMA==_1_1jvdwz4']); // This token represents a request with the 02:00:00 timestamp

        $this->iterator->setToken(PageToken::fromRequest($request->reveal()));

        self::assertEquals([
            ['id' => 'af6394a4-7344-4fe8-9748-e6c67eba5ade', 'timestamp' => '1991-11-24 02:00:00'],
            ['id' => '9c5f6ff7-b28f-48fb-ba47-8bcc3b235bed', 'timestamp' => '1991-11-24 03:00:00'],
            ['id' => '191a54d8-990c-4ea7-9a23-0aed29d1fffe', 'timestamp' => '1991-11-24 04:00:00'],
        ], \iterator_to_array($this->iterator));

        self::assertEquals('=MTk5MS0xMS0yNCAwNDowMDowMA==_1_7gqxdp', (string) $this->iterator->getNextPageToken());
    }
}

class StdObjectStatement extends ArrayStatement
{
    public function fetch($fetchMode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        if (FetchMode::STANDARD_OBJECT === $fetchMode) {
            $result = parent::fetch(FetchMode::ASSOCIATIVE, $cursorOrientation, $cursorOffset);

            return false !== $result ? \json_decode(\json_encode($result), false) : $result;
        }

        return parent::fetch($fetchMode, $cursorOrientation, $cursorOffset);
    }
}
