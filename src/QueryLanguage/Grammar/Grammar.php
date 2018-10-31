<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Grammar;

use Fazland\ApiPlatformBundle\QueryLanguage\Exception\InvalidArgumentException;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\AllExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Comparison;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\EntryExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Logical;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\OrderExpression;

final class Grammar extends AbstractGrammar
{
    /**
     * Parses an expression into an AST.
     *
     * @param string $input
     * @param string|string[] $accept
     *
     * @return ExpressionInterface
     */
    public function parse(string $input, $accept = ExpressionInterface::class): ExpressionInterface
    {
        $expr = parent::parse($input);

        foreach ((array) $accept as $acceptedClass) {
            if ($expr instanceof $acceptedClass) {
                return $expr;
            }
        }

        throw new InvalidArgumentException(get_class($expr) . ' is not acceptable');
    }

    /**
     * @inheritdoc
     */
    protected function unaryExpression(string $type, $value)
    {
        switch ($type) {
            case 'all':
                return new AllExpression();

            case 'not':
                return Logical\NotExpression::create($value);

            case 'eq':
                return new Comparison\EqualExpression($value);

            case 'neq':
                return Logical\NotExpression::create(new Comparison\EqualExpression($value));

            case 'lt':
                return new Comparison\LessThanExpression($value);

            case 'lte':
                return new Comparison\LessThanOrEqualExpression($value);

            case 'gt':
                return new Comparison\GreaterThanExpression($value);

            case 'gte':
                return new Comparison\GreaterThanOrEqualExpression($value);

            case 'like':
                return new Comparison\LikeExpression($value);

            default:
                throw new \Exception('Unknown unary operator "'.$type.'"');
        }
    }

    /**
     * @inheritdoc
     */
    protected function binaryExpression(string $type, $left, $right)
    {
        switch ($type) {
            case 'range':
                return Logical\AndExpression::create([ new Comparison\GreaterThanOrEqualExpression($left), new Comparison\LessThanOrEqualExpression($right) ]);

            case 'entry':
                return EntryExpression::create($left, $right);

            default:
                throw new \Exception('Unknown binary operator "'.$type.'"');
        }
    }

    /**
     * @inheritdoc
     */
    protected function orderExpression(string $field, string $direction)
    {
        return new OrderExpression($field, $direction);
    }

    /**
     * @inheritdoc
     */
    protected function variadicExpression(string $type, array $arguments)
    {
        switch ($type) {
            case 'and':
                return Logical\AndExpression::create($arguments);

            case 'in':
            case 'or':
                return Logical\OrExpression::create($arguments);

            default:
                throw new \Exception('Unknown operator "'.$type.'"');
        }
    }
}
