<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;

class DateTimeWalker extends DqlWalker
{
    /**
     * {@inheritdoc}
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        return new \DateTimeImmutable(parent::walkLiteral($expression));
    }
}
