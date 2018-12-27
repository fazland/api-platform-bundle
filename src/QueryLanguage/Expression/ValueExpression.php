<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression;

use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

class ValueExpression implements ExpressionInterface
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
     * {@inheritdoc}
     */
    public function dispatch(TreeWalkerInterface $treeWalker)
    {
        return $this->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Creates a new value expression.
     *
     * @param $value
     *
     * @return ValueExpression
     */
    public static function create($value): self
    {
        return new self($value);
    }
}
