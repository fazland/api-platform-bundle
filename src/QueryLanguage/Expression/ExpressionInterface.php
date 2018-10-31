<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression;

use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

interface ExpressionInterface
{
    /**
     * Returns expression as string.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Dispatches the expression to the tree walker.
     *
     * @param TreeWalkerInterface $treeWalker
     *
     * @return mixed
     */
    public function dispatch(TreeWalkerInterface $treeWalker);
}
