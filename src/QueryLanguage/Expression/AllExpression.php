<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression;

use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

final class AllExpression implements ExpressionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return '$all()';
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(TreeWalkerInterface $treeWalker)
    {
        return $treeWalker->walkAll();
    }
}
