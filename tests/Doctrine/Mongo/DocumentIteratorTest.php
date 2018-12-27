<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Doctrine\Mongo;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Builder;
use Fazland\ApiPlatformBundle\Doctrine\Mongo\DocumentIterator;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine\MongoDocumentManagerMockTrait;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Document\FooBar;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class DocumentIteratorTest extends TestCase
{
    use MongoDocumentManagerMockTrait;

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var DocumentIterator
     */
    private $iterator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $documentManager = $this->getDocumentManager();
        $documentManager->getMetadataFactory()->setMetadataFor(FooBar::class, $class = new ClassMetadata(FooBar::class));

        $class->mapField([
            'fieldName' => 'id',
            'type' => 'id',
        ]);
        $class->setIdentifier('id');

        $this->builder = $documentManager->createQueryBuilder(FooBar::class);

        $this->iterator = new DocumentIterator($this->builder);
    }

    public function testShouldBeIterable(): void
    {
        self::assertTrue(\is_iterable($this->iterator));
    }

    public function testShouldBeAnIterator(): void
    {
        self::assertInstanceOf(\Iterator::class, $this->iterator);
    }

    public function testCountShouldExecuteACountQuery(): void
    {
        $this->_db->command(new BSONDocument([
            'count' => 'FooBar',
            'query' => new BSONDocument(),
            'limit' => 0,
            'skip' => 0,
        ]), Argument::any())
            ->willReturn(new \ArrayIterator([
                [
                    'n' => 42,
                    'query' => (object) [],
                    'ok' => true,
                ],
            ]));

        self::assertCount(42, $this->iterator);
    }

    public function testShouldIterateAgainstAQueryResult(): void
    {
        $this->_collection->find([], Argument::any())
            ->willReturn(new \ArrayIterator([
                [
                    '_id' => 42,
                    'id' => 42,
                ],
                [
                    '_id' => 45,
                    'id' => 45,
                ],
                [
                    '_id' => 48,
                    'id' => 48,
                ],
            ]));

        $obj1 = new FooBar();
        $obj1->id = 42;
        $obj2 = new FooBar();
        $obj2->id = 45;
        $obj3 = new FooBar();
        $obj3->id = 48;

        self::assertEquals([$obj1, $obj2, $obj3], \iterator_to_array($this->iterator));
    }

    public function testShouldCallCallableSpecifiedWithApply(): void
    {
        $this->_collection->find([], Argument::any())
            ->willReturn(new \ArrayIterator([
                [
                    '_id' => 42,
                    'id' => 42,
                ],
                [
                    '_id' => 45,
                    'id' => 45,
                ],
                [
                    '_id' => 48,
                    'id' => 48,
                ],
            ]));

        $calledCount = 0;
        $this->iterator->apply(function (FooBar $bar) use (&$calledCount) {
            ++$calledCount;

            return $bar->id;
        });

        self::assertEquals([42, 45, 48], \iterator_to_array($this->iterator));
        self::assertEquals(3, $calledCount);
    }
}
