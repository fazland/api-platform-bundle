<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Walker;

use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ValueExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine\DqlWalker;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\QueryLanguage as QueryLanguageFixtures;
use Fazland\ApiPlatformBundle\Tests\QueryLanguage\Doctrine\ORM\FixturesTrait;
use PHPUnit\Framework\TestCase;

class DqlWalkerTest extends TestCase
{
    use FixturesTrait;

    /**
     * @var DqlWalker
     */
    private $walker;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    protected function setUp()
    {
        $userRepository = self::$entityManager->getRepository(QueryLanguageFixtures\User::class);
        $this->queryBuilder = $userRepository->createQueryBuilder('u');
        $this->walker = new DqlWalker($this->queryBuilder, 'u.name');
    }

    public function testShouldBuildEqualComparison(): void
    {
        $this->queryBuilder->andWhere(
            $this->walker->walkComparison('=', LiteralExpression::create('foo'))
        );

        self::assertEquals('SELECT u FROM Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\QueryLanguage\User u WHERE u.name = :u_name', $this->queryBuilder->getDQL());
        self::assertEquals('foo', $this->queryBuilder->getParameter('u_name')->getValue());
        self::assertEquals(ParameterType::STRING, $this->queryBuilder->getParameter('u_name')->getType());
    }

    public function testShouldBuildEqualComparisonWithRelatedObjects(): void
    {
        $this->walker = new DqlWalker($this->queryBuilder, 'u.foobar');
        $foobar = self::$entityManager->getRepository(QueryLanguageFixtures\FooBar::class)
            ->findOneBy(['foobar' => 'foobar_goofy']);

        self::assertNotNull($foobar);

        $this->queryBuilder->andWhere(
            $this->walker->walkComparison('=', ValueExpression::create($foobar))
        );

        self::assertEquals('SELECT u FROM Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\QueryLanguage\User u WHERE u.foobar = :u_foobar', $this->queryBuilder->getDQL());
        self::assertEquals($foobar, $this->queryBuilder->getParameter('u_foobar')->getValue());
        self::assertEquals(ParameterType::STRING, $this->queryBuilder->getParameter('u_foobar')->getType());

        $user = $this->queryBuilder->getQuery()->getOneOrNullResult();
        self::assertEquals('goofy', $user->name);
    }
}
