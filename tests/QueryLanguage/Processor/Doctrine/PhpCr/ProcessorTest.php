<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Processor\Doctrine\PhpCr;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Fazland\ApiPlatformBundle\Form\Extension\AutoSubmitRequestHandler;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\PhpCr\Processor;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalker;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Document\PhpCrQueryLanguage\FooBar;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Document\PhpCrQueryLanguage\User;
use Fazland\ApiPlatformBundle\Tests\QueryLanguage\Doctrine\PhpCr\FixturesTrait;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ValidatorBuilder;

class ProcessorTest extends TestCase
{
    use FixturesTrait;

    private Processor $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $formFactory = (new FormFactoryBuilder(true))
            ->addExtension(new ValidatorExtension((new ValidatorBuilder())->getValidator()))
            ->addTypeExtension(new FormTypeHttpFoundationExtension(new AutoSubmitRequestHandler()))
            ->getFormFactory()
        ;

        $this->processor = new Processor(
            self::$documentManager->getRepository(User::class)->createQueryBuilder('u'),
            self::$documentManager,
            $formFactory
        );
    }

    public function testBuiltinColumnWorks(): void
    {
        $this->processor->addColumn('name');
        $itr = $this->processor->processRequest(new Request(['name' => 'goofy']));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(1, $result);
        self::assertInstanceOf(User::class, $result[0]);
        self::assertEquals('goofy', $result[0]->name);
    }

    public function testBuiltinLikeColumnWorks(): void
    {
        $this->processor->addColumn('name');
        $itr = $this->processor->processRequest(new Request(['name' => '$like(GOOFY)']));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(1, $result);
        self::assertInstanceOf(User::class, $result[0]);
        self::assertEquals('goofy', $result[0]->name);
    }

    public function testBuiltinOrColumnWorks(): void
    {
        $this->processor->addColumn('name');
        $itr = $this->processor->processRequest(new Request(['name' => '$or(goofy, barbar)']));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(2, $result);
        self::assertInstanceOf(User::class, $result[0]);
        self::assertEquals('goofy', $result[1]->name);
        self::assertInstanceOf(User::class, $result[1]);
        self::assertEquals('barbar', $result[0]->name);
    }

    public function testBuiltinNotColumnWorks(): void
    {
        $this->processor->addColumn('name');
        $itr = $this->processor->processRequest(new Request(['name' => '$not($or(goofy, barbar))']));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(5, $result);
    }

    public function testBuiltinOrderColumnWorks(): void
    {
        $formFactory = (new FormFactoryBuilder(true))
            ->addExtension(new ValidatorExtension((new ValidatorBuilder())->getValidator()))
            ->addTypeExtension(new FormTypeHttpFoundationExtension(new AutoSubmitRequestHandler()))
            ->getFormFactory();

        $this->processor = new Processor(
            self::$documentManager->getRepository(User::class)->createQueryBuilder('u'),
            self::$documentManager,
            $formFactory,
            [
                'order_field' => 'order',
            ]
        );

        $this->processor->addColumn('name');
        $itr = $this->processor->processRequest(new Request(['order' => '$order(name, desc)']));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(7, $result);
    }

    public function testBuiltinOrderPaginationColumnWorks(): void
    {
        $formFactory = (new FormFactoryBuilder(true))
            ->addExtension(new ValidatorExtension((new ValidatorBuilder())->getValidator()))
            ->addTypeExtension(new FormTypeHttpFoundationExtension(new AutoSubmitRequestHandler()))
            ->getFormFactory();

        $this->processor = new Processor(
            self::$documentManager->getRepository(User::class)->createQueryBuilder('u'),
            self::$documentManager,
            $formFactory,
            [
                'order_field' => 'order',
            ]
        );

        $this->processor->addColumn('name');
        /** @var PagerIterator $itr */
        $itr = $this->processor->processRequest(new Request(['order' => '$order(name, desc)']));

        self::assertInstanceOf(PagerIterator::class, $itr);
        $itr->setPageSize(2);
        $result = \iterator_to_array($itr);

        self::assertCount(2, $result);
        self::assertEquals('=Zm9vYmFy_1_12r6se5', (string) $itr->getNextPageToken());

        $this->processor = new Processor(
            self::$documentManager->getRepository(User::class)->createQueryBuilder('u'),
            self::$documentManager,
            $formFactory,
            [
                'order_field' => 'order',
            ]
        );

        $this->processor->addColumn('name');
        /** @var PagerIterator $itr */
        $itr = $this->processor->processRequest(new Request(['order' => '$order(name, desc)', 'continue' => '=Zm9vYmFy_1_12r6se5']));

        self::assertInstanceOf(PagerIterator::class, $itr);
        $itr->setPageSize(2);
        $result = \iterator_to_array($itr);

        self::assertCount(2, $result);
        self::assertEquals('=ZG9uYWxkIGR1Y2s=_1_epxv00', (string) $itr->getNextPageToken());
    }

    public function testRelationColumnWorks(): void
    {
        $this->processor->addColumn('foobar');
        $itr = $this->processor->processRequest(new Request(['foobar' => '$entry(foobar, foobar_donald duck)']));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(1, $result);
        self::assertInstanceOf(User::class, $result[0]);
        self::assertEquals('donald duck', $result[0]->name);
    }

    public function testColumnWithFieldInRelatedEntityWorks(): void
    {
        $this->processor->addColumn('foobar', [
            'field_name' => 'foobar.foobar',
        ]);
        $itr = $this->processor->processRequest(new Request(['foobar' => 'foobar_donald duck']));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(1, $result);
        self::assertInstanceOf(User::class, $result[0]);
        self::assertEquals('donald duck', $result[0]->name);
    }

    public function provideParamsForPageSize(): iterable
    {
        yield [ [] ];
        yield [ ['order' => '$order(name)'] ];
        yield [ ['order' => '$order(name)', 'continue' => '=YmF6_1_10tf9ny'] ];
    }

    /**
     * @dataProvider provideParamsForPageSize
     */
    public function testPageSizeOptionShouldWork(array $params): void
    {
        $formFactory = (new FormFactoryBuilder(true))
            ->addExtension(new ValidatorExtension((new ValidatorBuilder())->getValidator()))
            ->addTypeExtension(new FormTypeHttpFoundationExtension(new AutoSubmitRequestHandler()))
            ->getFormFactory();

        $this->processor = new Processor(
            self::$documentManager->getRepository(User::class)->createQueryBuilder('u'),
            self::$documentManager,
            $formFactory,
            [
                'order_field' => 'order',
                'continuation_token' => true,
                'default_page_size' => 3,
            ]
        );

        $this->processor->addColumn('name');
        $itr = $this->processor->processRequest(new Request($params));

        self::assertInstanceOf(ObjectIteratorInterface::class, $itr);
        $result = \iterator_to_array($itr);

        self::assertCount(3, $result);
    }

    public function testCustomColumnWorks(): void
    {
        $this->processor->addColumn('foobar', new class(self::$documentManager) implements ColumnInterface {
            /**
             * @var DocumentManagerInterface
             */
            private $documentManager;

            public function __construct(DocumentManagerInterface $entityManager)
            {
                $this->documentManager = $entityManager;
            }

            /**
             * @param QueryBuilder        $queryBuilder
             * @param ExpressionInterface $expression
             */
            public function addCondition($queryBuilder, ExpressionInterface $expression): void
            {
                $queryBuilder->addJoinInner()
                    ->right()->document(FooBar::class, 'f')->end()
                    ->condition()->equi('u.foobar', 'f.uuid')->end()
                ->end();

                $queryBuilder->andWhere()
                    ->eq()->field('f.foobar')->literal($expression->getValue());
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
        self::assertInstanceOf(User::class, $result[0]);
        self::assertEquals('barbar', $result[0]->name);
    }
}
