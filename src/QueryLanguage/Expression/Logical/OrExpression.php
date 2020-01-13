<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Logical;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\AllExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

final class OrExpression implements LogicalExpressionInterface
{
    private array $arguments;

    private function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return '$or('.\implode(', ', $this->arguments).')';
    }

    public static function create(array $arguments)
    {
        foreach ($arguments as $argument) {
            if ($argument instanceof AllExpression) {
                return new AllExpression();
            }
        }

        $arguments = \array_values($arguments);
        switch (\count($arguments)) {
            case 0:
                return new AllExpression();

            case 1:
                return $arguments[0];

            default:
                return new self($arguments);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(TreeWalkerInterface $treeWalker)
    {
        return $treeWalker->walkOr($this->arguments);
    }
}
