<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression;

use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

final class OrderExpression implements ExpressionInterface
{
    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $direction;

    public function __construct(string $field, string $direction)
    {
        $this->field = $field;
        $this->direction = $direction;
    }

    /**
     * Gets the order column.
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Gets the order direction (asc, desc).
     *
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '$order('.$this->field.', '.$this->direction.')';
    }

    /**
     * @inheritDoc
     */
    public function dispatch(TreeWalkerInterface $treeWalker)
    {
        return $treeWalker->walkOrder($this->field, $this->direction);
    }
}
