<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Comparison;

final class EqualExpression extends ComparisonExpression
{
    public function __construct($value)
    {
        parent::__construct($value, '=');
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return '$eq('.$this->getValue().')';
    }
}
