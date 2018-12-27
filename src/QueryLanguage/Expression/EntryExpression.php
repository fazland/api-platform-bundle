<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;

final class EntryExpression implements ExpressionInterface
{
    use ExpressionTrait;

    /**
     * @var LiteralExpression
     */
    private $key;

    /**
     * @var ExpressionInterface
     */
    private $value;

    public function __construct(LiteralExpression $key, ExpressionInterface $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public static function create(ExpressionInterface $key, ExpressionInterface $value)
    {
        self::assertLiteral($key, 1);

        return new self($key, $value);
    }

    /**
     * @return LiteralExpression
     */
    public function getKey(): LiteralExpression
    {
        return $this->key;
    }

    /**
     * @return ExpressionInterface
     */
    public function getValue(): ExpressionInterface
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return '$entry('.(string) $this->key.', '.(string) $this->value.')';
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(TreeWalkerInterface $treeWalker)
    {
        return $treeWalker->walkEntry((string) $this->key, $this->value);
    }
}
