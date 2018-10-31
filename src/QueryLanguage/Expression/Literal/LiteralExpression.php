<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

abstract class LiteralExpression implements ExpressionInterface
{
    /**
     * The value represented by the literal expression.
     *
     * @var mixed
     */
    protected $value;

    protected function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Gets the literal expression value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(TreeWalkerInterface $treeWalker)
    {
        return $treeWalker->walkLiteral($this);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }

    public static function create(string $value): self
    {
        switch (true) {
            case 'true' === $value:
            case 'false' === $value:
                return new BooleanExpression('true' === $value);

            case 1 === preg_match('/^\d+$/', $value):
                return new IntegerExpression((int) $value);

            case is_numeric($value):
                return new NumericExpression($value);

            default:
                return new StringExpression($value);
        }
    }
}