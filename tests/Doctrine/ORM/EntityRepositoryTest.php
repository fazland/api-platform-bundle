<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Doctrine\ORM;

use Doctrine\DBAL\Cache\ArrayStatement;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Fazland\ApiPlatformBundle\Doctrine\ORM\EntityIterator;
use Fazland\ApiPlatformBundle\Doctrine\ORM\EntityRepository;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Bundle\AppKernel;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Doctrine\EntityManagerMockTrait;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\TestEntity;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ResettableContainerInterface;

class EntityRepositoryTest extends WebTestCase
{
    use EntityManagerMockTrait;

    /**
     * @var EntityRepository
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
        $classMetadata = new ClassMetadata(TestEntity::class);
        $classMetadata->identifier = ['id'];
        $classMetadata->mapField([
            'fieldName' => 'id',
            'type' => 'integer',
            'scale' => null,
            'length' => null,
            'unique' => true,
            'nullable' => false,
            'precision' => null,
        ]);

        $this->getEntityManager()->getMetadataFactory()->setMetadataFor(TestEntity::class, $classMetadata);

        $this->repository = new class($this->getEntityManager(), $classMetadata) extends EntityRepository {
        };
    }

    public function testAllShouldReturnAnEntityIterator(): void
    {
        $this->_innerConnection->query('SELECT t0_.id AS id_0 FROM TestEntity t0_')
            ->willReturn(new ArrayStatement([]));

        self::assertInstanceOf(EntityIterator::class, $this->repository->all());
    }

    public function testCountWillReturnRowCount(): void
    {
        $this->_innerConnection->query('SELECT COUNT(t0_.id) AS sclr_0 FROM TestEntity t0_')
            ->willReturn(new ArrayStatement([
                ['sclr_0' => '42'],
            ]));

        self::assertSame(42, $this->repository->count());
    }

    public function testFindOneByCachedShouldCheckCache(): void
    {
        $this->_innerConnection->query('SELECT t0_.id AS id_0 FROM TestEntity t0_ LIMIT 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1);

        $statement->setFetchMode(Argument::any())->willReturn();
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                ['id_0' => '1'],
                false
            );
        $statement->closeCursor()->willReturn();

        $obj1 = $this->repository->findOneByCached([]);
        $this->repository->findOneByCached([]);

        $cache = $this->_configuration->getResultCacheImpl();
        self::assertNotFalse($cache->fetch('__'.\get_class($this->repository).'::findOneByCachedf6e6f43434391be8b061460900c36046255187c8'));

        self::assertInstanceOf(TestEntity::class, $obj1);
        self::assertEquals(1, $obj1->id);
    }

    /**
     * @expectedException \Doctrine\ORM\NonUniqueResultException
     */
    public function testFindOneByCachedShouldThrowIdNonUniqueResultHasBeenReturned(): void
    {
        $this->_innerConnection->query('SELECT t0_.id AS id_0 FROM TestEntity t0_ LIMIT 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1);

        $statement->setFetchMode(Argument::any())->willReturn();
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                ['id_0' => '1'],
                ['id_0' => '2'],
                false
            );
        $statement->closeCursor()->willReturn();

        $this->repository->findOneByCached([]);
    }

    public function testFindByCachedShouldCheckCache(): void
    {
        $this->_innerConnection->query('SELECT t0_.id AS id_0 FROM TestEntity t0_')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1);

        $statement->setFetchMode(Argument::any())->willReturn();
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                ['id_0' => '1'],
                ['id_0' => '2'],
                ['id_0' => '3'],
                false
            );
        $statement->closeCursor()->willReturn();

        $objs = $this->repository->findByCached([]);
        $this->repository->findByCached([]);

        $cache = $this->_configuration->getResultCacheImpl();
        self::assertNotFalse($cache->fetch('__'.\get_class($this->repository).'::findByCachedf6e6f43434391be8b061460900c36046255187c8'));

        self::assertCount(3, $objs);
        self::assertEquals(1, $objs[0]->id);
        self::assertEquals(2, $objs[1]->id);
        self::assertEquals(3, $objs[2]->id);
    }

    public function testFindByCachedShouldFireTheCorrectQuery(): void
    {
        $this->_innerConnection->prepare('SELECT t0_.id AS id_0 FROM TestEntity t0_ WHERE t0_.id IN (?, ?) ORDER BY t0_.id ASC LIMIT 2 OFFSET 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1);

        $statement->setFetchMode(Argument::any())->willReturn();
        $statement->bindValue(1, 2, \PDO::PARAM_INT)->willReturn();
        $statement->bindValue(2, 3, \PDO::PARAM_INT)->willReturn();
        $statement->execute()->willReturn(true);
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                ['id_0' => '2'],
                ['id_0' => '3'],
                false
            );
        $statement->closeCursor()->willReturn();

        $objs = $this->repository->findByCached([
            'id' => [2, 3],
        ], ['id' => 'asc'], 2, 1);

        self::assertCount(2, $objs);
        self::assertEquals(2, $objs[0]->id);
        self::assertEquals(3, $objs[1]->id);
    }

    public function testGetShouldReturnAnEntity(): void
    {
        $this->_innerConnection->prepare('SELECT t0.id AS id_1 FROM TestEntity t0 WHERE t0.id = ?')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1);

        /* @var Statement|ObjectProphecy $statement */
        $statement->setFetchMode(Argument::any())->willReturn();
        $statement->bindValue(1, 1, \PDO::PARAM_INT)->willReturn();
        $statement->execute()->willReturn(true);
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                ['id_1' => '1'],
                false
            );
        $statement->closeCursor()->willReturn();

        $obj1 = $this->repository->get(1);

        self::assertInstanceOf(TestEntity::class, $obj1);
        self::assertEquals(1, $obj1->id);
    }

    /**
     * @expectedException \Doctrine\ORM\NoResultException
     */
    public function testGetShouldThrowIfNoResultIsFound(): void
    {
        $this->_innerConnection->prepare('SELECT t0.id AS id_1 FROM TestEntity t0 WHERE t0.id = ?')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1);

        /* @var Statement|ObjectProphecy $statement */
        $statement->setFetchMode(Argument::any())->willReturn();
        $statement->bindValue(1, 1, \PDO::PARAM_INT)->willReturn();
        $statement->execute()->willReturn(true);
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                false
            );
        $statement->closeCursor()->willReturn();

        $this->repository->get(1);
    }

    public function testGetOneByShouldReturnAnEntity(): void
    {
        $this->_innerConnection->prepare('SELECT t0.id AS id_1 FROM TestEntity t0 WHERE t0.id = ? LIMIT 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1);

        /* @var Statement|ObjectProphecy $statement */
        $statement->setFetchMode(Argument::any())->willReturn();
        $statement->bindValue(1, 12, \PDO::PARAM_INT)->willReturn();
        $statement->execute()->willReturn(true);
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                ['id_1' => '12'],
                false
            );
        $statement->closeCursor()->willReturn();

        $obj1 = $this->repository->getOneBy(['id' => 12]);

        self::assertInstanceOf(TestEntity::class, $obj1);
        self::assertEquals(12, $obj1->id);
    }

    /**
     * @expectedException \Doctrine\ORM\NoResultException
     */
    public function testGetOneByShouldThrowIfNoResultIsFound(): void
    {
        $this->_innerConnection->prepare('SELECT t0.id AS id_1 FROM TestEntity t0 WHERE t0.id = ? LIMIT 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1);

        /* @var Statement|ObjectProphecy $statement */
        $statement->setFetchMode(Argument::any())->willReturn();
        $statement->bindValue(1, 12, \PDO::PARAM_INT)->willReturn();
        $statement->execute()->willReturn(true);
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                false
            );
        $statement->closeCursor()->willReturn();

        $this->repository->getOneBy(['id' => 12]);
    }

    public function testGetOneByCachedShouldCheckTheCache(): void
    {
        $this->_innerConnection->prepare('SELECT t0_.id AS id_0 FROM TestEntity t0_ WHERE t0_.id = ? LIMIT 1')
            ->willReturn($statement = $this->prophesize(Statement::class))
            ->shouldBeCalledTimes(1);

        /* @var Statement|ObjectProphecy $statement */
        $statement->setFetchMode(Argument::any())->willReturn();
        $statement->bindValue(1, 12, \PDO::PARAM_INT)->willReturn();
        $statement->execute()->willReturn(true);
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(
                ['id_0' => '12'],
                false
            );
        $statement->closeCursor()->willReturn();

        $obj1 = $this->repository->getOneByCached(['id' => 12]);
        $this->repository->getOneByCached(['id' => 12]);

        $cache = $this->_configuration->getResultCacheImpl();
        self::assertNotFalse($cache->fetch('__'.\get_class($this->repository).'::getOneByCached48b7e8dc8f3d4c52abba542ba5f3d423da65cf5e'));

        self::assertInstanceOf(TestEntity::class, $obj1);
        self::assertEquals(12, $obj1->id);
    }

    /**
     * @dataProvider getEntityClasses
     */
    public function testRepositoryIsInstanceOfEntityRepository(string $class): void
    {
        self::assertTrue(EntityRepository::class === $class || \is_subclass_of($class, EntityRepository::class));
    }

    public function getEntityClasses(): iterable
    {
        $kernel = $this->createKernel();
        $kernel->boot();
        $container = $kernel->getContainer();

        /** @var EntityManager $em */
        $em = $container->get(EntityManager::class);

        /** @var ClassMetadata $metadata */
        foreach ($em->getMetadataFactory()->getAllMetadata() as $metadata) {
            yield [\get_class($em->getRepository($metadata->name))];
        }

        $kernel->shutdown();
        if ($container instanceof ResettableContainerInterface) {
            $container->reset();
        }
    }
}
