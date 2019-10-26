<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Processor\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Fazland\ApiPlatformBundle\Form\Extension\AutoSubmitRequestHandler;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\ORM\Processor;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalker;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\QueryLanguage\FooBar;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\QueryLanguage\User;
use Fazland\ApiPlatformBundle\Tests\QueryLanguage\QueryBuilderFixturesTrait;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\Type\FormTypeHttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ValidatorBuilder;

class ProcessorTest extends TestCase
{
    use QueryBuilderFixturesTrait;

    /**
     * @var Processor
     */
    private $processor;

    protected function setUp(): void
    {
        $formFactory = (new FormFactoryBuilder(true))
            ->addExtension(new ValidatorExtension((new ValidatorBuilder())->getValidator()))
            ->addTypeExtension(new FormTypeHttpFoundationExtension(new AutoSubmitRequestHandler()))
            ->getFormFactory();

        $this->processor = new Processor(
            self::$entityManager->getRepository(User::class)->createQueryBuilder('u'),
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

    public function testCustomColumnWorks(): void
    {
        $this->processor->addColumn('foobar', new class(self::$entityManager) implements ColumnInterface {
            /**
             * @var EntityManagerInterface
             */
            private $entityManager;

            public function __construct(EntityManagerInterface $entityManager)
            {
                $this->entityManager = $entityManager;
            }

            public function addCondition($queryBuilder, ExpressionInterface $expression): void
            {
                $foobar = $this->entityManager->getRepository(FooBar::class)
                    ->findOneBy(['foobar' => $expression->getValue()]);

                $queryBuilder->andWhere('u.foobar = :foobar')
                    ->setParameter('foobar', $foobar);
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
