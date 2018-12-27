<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ValueExpression;

interface TreeWalkerInterface
{
    /**
     * Evaluates a literal expression.
     *
     * @param LiteralExpression $expression
     *
     * @return mixed
     */
    public function walkLiteral(LiteralExpression $expression);

    /**
     * Evaluates a comparison expression.
     *
     * @param string          $operator
     * @param ValueExpression $expression
     *
     * @return mixed
     */
    public function walkComparison(string $operator, ValueExpression $expression);

    /**
     * Evaluates a match all expression.
     *
     * @return mixed
     */
    public function walkAll();

    /**
     * Evaluates an order expression.
     *
     * @param string $field
     * @param string $direction
     *
     * @return mixed
     */
    public function walkOrder(string $field, string $direction);

    /**
     * Evaluates a NOT expression.
     *
     * @param ExpressionInterface $expression
     *
     * @return mixed
     */
    public function walkNot(ExpressionInterface $expression);

    /**
     * Evaluates an AND expression.
     *
     * @param array $arguments
     *
     * @return mixed
     */
    public function walkAnd(array $arguments);

    /**
     * Evaluates an OR expression.
     *
     * @param array $arguments
     *
     * @return mixed
     */
    public function walkOr(array $arguments);

    /**
     * Evaluates an ENTRY expression.
     *
     * @param string              $key
     * @param ExpressionInterface $expression
     *
     * @return mixed
     */
    public function walkEntry(string $key, ExpressionInterface $expression);
}
