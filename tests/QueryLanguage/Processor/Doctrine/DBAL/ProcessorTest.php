<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Processor\Doctrine\DBAL;

use Doctrine\ORM\EntityManagerInterface;
use Fazland\ApiPlatformBundle\Form\Extension\AutoSubmitRequestHandler;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\DBAL\Processor;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalker;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\QueryLanguage\FooBar;
use Fazland\ApiPlatformBundle\Tests\QueryLanguage\Doctrine\ORM\FixturesTrait;
use Fazland\DoctrineExtra\DBAL\RowIterator;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Validator\ValidatorBuilder;

class ProcessorTest extends TestCase
{
    use FixturesTrait;

    private Processor $processor;

    protected function setUp(): void
    {
        $formFactory = (new FormFactoryBuilder(true))
            ->addExtension(new ValidatorExtension((new ValidatorBuilder())->getValidator()))
            ->addTypeExtension(new FormTypeHttpFoundationExtension(new AutoSubmitRequestHandler()))
            ->getFormFactory();

        $queryBuilder = self::$entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('id', 'name', 'nameLength')
            ->from('user', 'u')
        ;

        $this->processor = new Processor(
            $queryBuilder,
            $formFactory,
            [
                'order_field' => 'order',
                'continuation_token' => true,
                'identifiers' => ['id'],
            ],
        );
    }

    public function testBuiltinColumnWorks(): void
    {
        $this->processor->addColumn('name');
        $itr = $this->processor->processRequest(new Request(['name' => 'goofy']));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(1, $result);
        self::assertEquals('goofy', $result[0]['name']);
    }

    public function provideParamsForPageSize(): iterable
    {
        yield [[]];
        yield [['order' => '$order(name)']];
        yield [['order' => '$order(name)', 'continue' => '=YmF6_1_10tf9ny']];
    }

    /**
     * @dataProvider provideParamsForPageSize
     */
    public function testPageSizeOptionShouldWork(array $params): void
    {
        $this->processor->addColumn('name');
        $this->processor->setDefaultPageSize(3);
        $itr = $this->processor->processRequest(new Request($params));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(3, $result);
    }

    public function testOrderByDefaultFieldShouldThrowOnInvalidOptions(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $formFactory = (new FormFactoryBuilder(true))
            ->addExtension(new ValidatorExtension((new ValidatorBuilder())->getValidator()))
            ->addTypeExtension(new FormTypeHttpFoundationExtension(new AutoSubmitRequestHandler()))
            ->getFormFactory();

        $queryBuilder = self::$entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('id', 'name', 'nameLength')
            ->from('user', 'u')
        ;

        $this->processor = new Processor(
            $queryBuilder,
            $formFactory,
            [
                'default_order' => '$eq(name)',
                'order_field' => 'order',
                'continuation_token' => true,
                'identifiers' => ['id'],
            ],
        );

        $this->processor->addColumn('name');
        $this->processor->setDefaultPageSize(3);
        $this->processor->processRequest(new Request([]));
    }

    public function provideParamsForDefaultOrder(): iterable
    {
        yield [true, '$order(name)'];
        yield [true, 'name'];
        yield [true, 'name, desc'];
        yield [false, '$order(nonexistent, asc)'];
    }

    /**
     * @dataProvider provideParamsForDefaultOrder
     */
    public function testOrderByDefaultFieldShouldWork(bool $valid, string $defaultOrder): void
    {
        $formFactory = (new FormFactoryBuilder(true))
            ->addExtension(new ValidatorExtension((new ValidatorBuilder())->getValidator()))
            ->addTypeExtension(new FormTypeHttpFoundationExtension(new AutoSubmitRequestHandler()))
            ->getFormFactory();

        $queryBuilder = self::$entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('id', 'name', 'nameLength')
            ->from('user', 'u')
        ;

        $this->processor = new Processor(
            $queryBuilder,
            $formFactory,
            [
                'default_order' => $defaultOrder,
                'order_field' => 'order',
                'continuation_token' => true,
                'identifiers' => ['id'],
            ],
        );

        $this->processor->addColumn('name');
        $this->processor->setDefaultPageSize(3);
        $itr = $this->processor->processRequest(new Request([]));

        if (! $valid) {
            self::assertInstanceOf(FormInterface::class, $itr);
        } else {
            self::assertInstanceOf(PagerIterator::class, $itr);
        }
    }

    public function testContinuationTokenCouldBeDisabled(): void
    {
        $formFactory = (new FormFactoryBuilder(true))
            ->addExtension(new ValidatorExtension((new ValidatorBuilder())->getValidator()))
            ->addTypeExtension(new FormTypeHttpFoundationExtension(new AutoSubmitRequestHandler()))
            ->getFormFactory();

        $queryBuilder = self::$entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('id', 'name', 'nameLength')
            ->from('user', 'u')
        ;

        $this->processor = new Processor(
            $queryBuilder,
            $formFactory,
            [
                'default_order' => 'name, desc',
                'order_field' => 'order',
                'continuation_token' => false,
                'identifiers' => ['id'],
            ],
        );

        $this->processor->addColumn('name');
        $this->processor->setDefaultPageSize(3);
        $itr = $this->processor->processRequest(new Request([]));

        self::assertInstanceOf(RowIterator::class, $itr);
    }

    public function testCustomColumnWorks(): void
    {
        $this->processor->addColumn('foobar', new class(self::$entityManager) implements ColumnInterface {
            private EntityManagerInterface $entityManager;

            public function __construct(EntityManagerInterface $entityManager)
            {
                $this->entityManager = $entityManager;
            }

            public function addCondition($queryBuilder, ExpressionInterface $expression): void
            {
                $foobar = $this->entityManager->getRepository(FooBar::class)
                    ->findOneBy(['foobar' => $expression->getValue()]);

                $queryBuilder->andWhere('u.foobar_id = :foobar')
                    ->setParameter('foobar', $foobar->id);
            }

            public function getValidationWalker()
            {
                return new ValidationWalker();
            }
        });
        $itr = $this->processor->processRequest(new Request(['foobar' => 'foobar_barbar']));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(1, $result);
        self::assertEquals('barbar', $result[0]['name']);
    }
}
