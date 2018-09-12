<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Doctrine\Mongo;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Fazland\ApiPlatformBundle\Doctrine\Mongo\DocumentIterator;
use Fazland\ApiPlatformBundle\Doctrine\Mongo\DocumentRepository;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Bundle\AppKernel;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine\MongoDocumentManagerMockTrait;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Document\FooBar;
use MongoDB\Model\BSONDocument;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ResettableContainerInterface;

class DocumentRepositoryTest extends WebTestCase
{
    use MongoDocumentManagerMockTrait;

    /**
     * @var DocumentRepository
     */
    private $repository;

    /**
     * {@inheritdoc}
     */
    protected static function getKernelClass(): string
    {
        return AppKernel::class;
    }

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

        $this->repository = new class($documentManager, $documentManager->getUnitOfWork(), $class) extends DocumentRepository {
        };
    }

    public function testAllShouldReturnAnEntityIterator(): void
    {
        $this->_collection->find([], Argument::any())->willReturn(new \ArrayIterator([]));
        $this->assertInstanceOf(DocumentIterator::class, $this->repository->all());
    }

    public function testCountWillReturnRowCount(): void
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

        $this->assertSame(42, $this->repository->count());
    }

    public function testFindOneByCachedShouldCheckCache(): void
    {
        $this->markTestSkipped('Mongo ODM does not support result cache');
    }

    /**
     * @expectedException \Doctrine\ORM\NonUniqueResultException
     */
    public function testFindOneByCachedShouldThrowIdNonUniqueResultHasBeenReturned(): void
    {
        $this->markTestSkipped('Mongo ODM does not support result cache');
    }

    public function testFindByCachedShouldCheckCache(): void
    {
        $this->markTestSkipped('Mongo ODM does not support result cache');
    }

    public function testFindByCachedShouldFireTheCorrectQuery(): void
    {
        $this->markTestSkipped('Mongo ODM does not support result cache');
    }

    public function testGetShouldReturnADocument(): void
    {
        $this->_collection->find(new BSONDocument([
            '_id' => '5a3d346ab7f26e18ba119308',
        ]), Argument::any())
            ->shouldBeCalledTimes(1)
            ->willReturn(new \ArrayIterator([
                [
                    '_id' => '5a3d346ab7f26e18ba119308',
                    'id' => '5a3d346ab7f26e18ba119308',
                ],
            ]));

        $obj1 = $this->repository->get('5a3d346ab7f26e18ba119308');

        $this->assertInstanceOf(FooBar::class, $obj1);
        $this->assertEquals('5a3d346ab7f26e18ba119308', $obj1->id);
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\Doctrine\Exception\NoResultException
     */
    public function testGetShouldThrowIfNoResultIsFound(): void
    {
        $this->_collection->find(new BSONDocument([
            '_id' => '5a3d346ab7f26e18ba119308',
        ]), Argument::any())
            ->shouldBeCalledTimes(1)
            ->willReturn(new \ArrayIterator([]));

        $this->repository->get('5a3d346ab7f26e18ba119308');
    }

    public function testGetOneByShouldReturnADocument(): void
    {
        $this->_collection->find(new BSONDocument([
            '_id' => '5a3d346ab7f26e18ba119308',
        ]), Argument::any())
            ->shouldBeCalledTimes(1)
            ->willReturn(new \ArrayIterator([
                [
                    '_id' => '5a3d346ab7f26e18ba119308',
                    'id' => '5a3d346ab7f26e18ba119308',
                ],
            ]));

        $obj1 = $this->repository->getOneBy(['id' => '5a3d346ab7f26e18ba119308']);

        $this->assertInstanceOf(FooBar::class, $obj1);
        $this->assertEquals('5a3d346ab7f26e18ba119308', $obj1->id);
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\Doctrine\Exception\NoResultException
     */
    public function testGetOneByShouldThrowIfNoResultIsFound(): void
    {
        $this->_collection->find(new BSONDocument([
            '_id' => '5a3d346ab7f26e18ba119308',
        ]), Argument::any())
            ->shouldBeCalledTimes(1)
            ->willReturn(new \ArrayIterator([]));

        $this->repository->getOneBy(['id' => '5a3d346ab7f26e18ba119308']);
    }

    public function testGetOneByCachedShouldCheckTheCache(): void
    {
        $this->markTestSkipped('Mongo ODM does not support result cache');
    }

    /**
     * @dataProvider getDocumentClasses
     */
    public function testRepositoryIsInstanceOfDocumentRepository(string $class): void
    {
        $this->assertTrue(DocumentRepository::class === $class || is_subclass_of($class, DocumentRepository::class));
    }

    public function getDocumentClasses(): iterable
    {
        $kernel = $this->createKernel();
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var DocumentManager $dm */
        $dm = $container->get('doctrine_mongodb')->getManager();

        /** @var ClassMetadata $metadata */
        foreach ($dm->getMetadataFactory()->getAllMetadata() as $metadata) {
            yield [get_class($dm->getRepository($metadata->name))];
        }

        $kernel->shutdown();
        if ($container instanceof ResettableContainerInterface) {
            $container->reset();
        }
    }
}
