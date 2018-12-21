<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ValueExpression;

class DiscriminatorWalker extends DqlWalker
{
    /**
     * @var ClassMetadata
     */
    private $rootEntity;

    public function __construct(QueryBuilder $queryBuilder, string $field)
    {
        parent::__construct($queryBuilder, $field);

        $entityManager = $this->queryBuilder->getEntityManager();
        $this->rootEntity = $entityManager->getClassMetadata($this->queryBuilder->getRootEntities()[0]);
    }

    /**
     * @inheritDoc
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        $value = $expression->getValue();

        return $this->rootEntity->discriminatorMap[$value];
    }

    /**
     * @inheritDoc
     */
    public function walkComparison(string $operator, ValueExpression $expression)
    {
        if ('=' !== $operator) {
            throw new \Exception();
        }

        return new Expr\Comparison($this->field, 'INSTANCE OF', $expression->dispatch($this));
    }

}
