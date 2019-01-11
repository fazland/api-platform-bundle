<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\NullExpression;

class DateTimeWalker extends DqlWalker
{
    /**
     * {@inheritdoc}
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        if ($expression instanceof NullExpression) {
            return parent::walkLiteral($expression);
        }

        return new \DateTimeImmutable(parent::walkLiteral($expression));
    }
}
