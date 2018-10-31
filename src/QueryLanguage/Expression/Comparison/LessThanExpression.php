<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Comparison;

final class LessThanExpression extends ComparisonExpression
{
    public function __construct($value)
    {
        parent::__construct($value, '<');
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '$lt('.$this->getValue().')';
    }
}