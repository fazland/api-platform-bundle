<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ValueExpression;

class ValidationWalker implements ValidationWalkerInterface
{
    /**
     * @inheritdoc
     */
    public function walkLiteral(LiteralExpression $expression)
    {
    }

    /**
     * @inheritDoc
     */
    public function walkComparison(string $operator, ValueExpression $expression)
    {
        if ($expression instanceof LiteralExpression) {
            $this->walkLiteral($expression);
        }
    }

    /**
     * @inheritDoc
     */
    public function walkAll()
    {
    }

    /**
     * @inheritDoc
     */
    public function walkOrder(string $field, string $direction)
    {
    }

    /**
     * @inheritDoc
     */
    public function walkNot(ExpressionInterface $expression)
    {
        $expression->dispatch($this);
    }

    /**
     * @inheritDoc
     */
    public function walkAnd(array $arguments)
    {
        foreach ($arguments as $expression) {
            $expression->dispatch($this);
        }
    }

    /**
     * @inheritDoc
     */
    public function walkOr(array $arguments)
    {
        foreach ($arguments as $expression) {
            $expression->dispatch($this);
        }
    }

    /**
     * @inheritDoc
     */
    public function walkEntry(string $key, ExpressionInterface $expression)
    {
        $expression->dispatch($this);
    }
}
