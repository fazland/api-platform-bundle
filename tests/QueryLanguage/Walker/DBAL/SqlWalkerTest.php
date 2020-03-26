<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Walker\DBAL;

use Doctrine\DBAL\Query\QueryBuilder;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\DBAL\SqlWalker;
use Fazland\ApiPlatformBundle\Tests\QueryLanguage\Doctrine\ORM\FixturesTrait;
use PHPUnit\Framework\TestCase;

class SqlWalkerTest extends TestCase
{
    use FixturesTrait;

    private SqlWalker $walker;
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $connection = self::$entityManager->getConnection();
        $this->queryBuilder = $connection->createQueryBuilder()
            ->select('id', 'name')
            ->from('user', 'u')
        ;

        $this->walker = new SqlWalker($this->queryBuilder, 'u.name');
    }

    public function testShouldBuildEqualComparison(): void
    {
        $this->queryBuilder->andWhere(
            $this->walker->walkComparison('=', LiteralExpression::create('foo'))
        );

        self::assertEquals('SELECT id, name FROM user u WHERE "u"."name" = :u_name', $this->queryBuilder->getSQL());
        self::assertEquals('foo', $this->queryBuilder->getParameter('u_name'));
    }
}
