<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Logical;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Comparison\ComparisonExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\EntryExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

final class NotExpression implements LogicalExpressionInterface
{
    /**
     * @var ExpressionInterface
     */
    private $argument;

    private function __construct(ExpressionInterface $expression)
    {
        $this->argument = $expression;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '$not('.$this->argument.')';
    }

    public static function create(ExpressionInterface $expression): ExpressionInterface
    {
        if ($expression instanceof self) {
            return $expression->argument;
        }

        if ($expression instanceof EntryExpression) {
            return new EntryExpression($expression->getKey(), self::create($expression->getValue()));
        }

        if (! $expression instanceof ComparisonExpressionInterface && ! $expression instanceof LogicalExpressionInterface) {
            throw new \Exception(sprintf('Cannot negate %s expression', get_class($expression)));
        }

        return new self($expression);
    }

    /**
     * @inheritDoc
     */
    public function dispatch(TreeWalkerInterface $treeWalker)
    {
        return $treeWalker->walkNot($this->argument);
    }
}
