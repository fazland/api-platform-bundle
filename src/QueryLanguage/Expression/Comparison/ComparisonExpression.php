<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Comparison;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionTrait;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

abstract class ComparisonExpression implements ComparisonExpressionInterface
{
    use ExpressionTrait;

    /**
     * @var LiteralExpression
     */
    private $value;

    /**
     * Can be <, <=, >, >=, =.
     *
     * @var string
     */
    private $operator;

    public function __construct($value, string $operator)
    {
        self::assertLiteral($value);

        $this->value = $value;
        $this->operator = $operator;
    }

    /**
     * Gets the comparison value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Gets the operator.
     *
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(TreeWalkerInterface $treeWalker)
    {
        return $treeWalker->walkComparison($this->operator, $this->value);
    }
}
