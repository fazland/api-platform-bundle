<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;

class EnumWalker extends ValidationWalker
{
    /**
     * @var array
     */
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * @inheritDoc
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        if (\in_array($expression->getValue(), $this->values, true)) {
            return;
        }

        throw new \InvalidArgumentException('Value not allowed');
    }

    /**
     * @inheritdoc
     */
    public function walkOrder(string $field, string $direction)
    {
        throw new \InvalidArgumentException('Operation unavailable');
    }

    /**
     * @inheritdoc
     */
    public function walkEntry(string $key, ExpressionInterface $expression)
    {
        throw new \InvalidArgumentException('Operation unavailable');
    }
}
