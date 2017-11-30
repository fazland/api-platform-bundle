<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Doctrine;

use Doctrine\DBAL\Cache\ArrayStatement;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\Doctrine\EntityIterator;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine\EntityManagerMockTrait;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\FooBar;
use PHPUnit\Framework\TestCase;

class EntityIteratorTest extends TestCase
{
    use EntityManagerMockTrait;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var EntityIterator
     */
    private $iterator;

    protected function setUp()
    {
        $this->getEntityManager()->getMetadataFactory()->setMetadataFor(FooBar::class, $metadata = new ClassMetadata(FooBar::class));

        $metadata->identifier = ['id'];
        $metadata->mapField([
            'fieldName' => 'id',
            'type' => 'integer',
            'scale' => null,
            'length' => null,
            'unique' => true,
            'nullable' => false,
            'precision' => null,
        ]);
        $metadata->reflFields['id'] = new \ReflectionProperty(FooBar::class, 'id');

        $this->queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $this->queryBuilder->select('a')
            ->from(FooBar::class, 'a');

        $this->_innerConnection->query('SELECT f0_.id AS id_0 FROM FooBar f0_')
            ->willReturn(new ArrayStatement([
                ['id_0' => '42'],
                ['id_0' => '45'],
                ['id_0' => '48'],
            ]));

        $this->iterator = new EntityIterator($this->queryBuilder);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage QueryBuilder must have exactly one root aliases for the iterator to work.
     */
    public function testShouldThrowIfQueryBuilderHasMoreThanOneRootAlias()
    {
        $this->queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $this->queryBuilder->select('a')
            ->addSelect('b')
            ->from(FooBar::class, 'a')
            ->from(FooBar::class, 'b');

        new EntityIterator($this->queryBuilder);
    }

    public function testShouldBeIterable()
    {
        $this->assertTrue(is_iterable($this->iterator));
    }

    public function testShouldBeAnIterator()
    {
        $this->assertInstanceOf(\Iterator::class, $this->iterator);
    }

    public function testCountShouldExecuteACountQuery()
    {
        $this->_innerConnection->query('SELECT COUNT(f0_.id) AS sclr_0 FROM FooBar f0_')
            ->willReturn(new ArrayStatement([
                ['sclr_0' => '42'],
            ]));

        $this->assertCount(42, $this->iterator);
    }

    public function testShouldIterateAgainstAQueryResult()
    {
        $obj1 = new FooBar();
        $obj1->id = 42;
        $obj2 = new FooBar();
        $obj2->id = 45;
        $obj3 = new FooBar();
        $obj3->id = 48;

        $this->assertEquals([$obj1, $obj2, $obj3], iterator_to_array($this->iterator));
    }

    public function testShouldCallCallableSpecifiedWithApply()
    {
        $calledCount = 0;
        $this->iterator->apply(function (FooBar $bar) use (&$calledCount) {
            ++$calledCount;

            return $bar->id;
        });

        $this->assertEquals([42, 45, 48], iterator_to_array($this->iterator));
        $this->assertEquals(3, $calledCount);
    }
}
