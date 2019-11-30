<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;
use MyCLabs\Enum\Enum;

class EnumWalker extends ValidationWalker
{
    /**
     * @var array
     */
    private $values;

    public function __construct($values)
    {
        if (\is_string($values) && \class_exists($values) && \is_subclass_of($values, Enum::class, true)) {
            $values = $values::toArray();
        }

        if (! \is_array($values)) {
            throw new \TypeError(\sprintf('Argument 1 passed to %s must be an array or an %s class name. %s given', __METHOD__, Enum::class, \is_object($values) ? \get_class($values) : \gettype($values)));
        }

        $this->values = $values;
    }

    /**
     * {@inheritdoc}
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        $expressionValue = $expression->getValue();
        if (\in_array($expressionValue, $this->values, true)) {
            return;
        }

        $this->addViolation('Value "{{ value }}" is not allowed. Must be one of "{{ allowed_values }}".', [
            '{{ value }}' => (string) $expressionValue,
            '{{ allowed_values }}' => \implode('", "', $this->values),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function walkOrder(string $field, string $direction)
    {
        $this->addViolation('Invalid operation');
    }

    /**
     * {@inheritdoc}
     */
    public function walkEntry(string $key, ExpressionInterface $expression)
    {
        $this->addViolation('Invalid operation');
    }
}
