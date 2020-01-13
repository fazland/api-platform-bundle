<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Comparison;

final class LikeExpression extends ComparisonExpression
{
    public function __construct($value)
    {
        parent::__construct($value, 'like');
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return "\$like($this->value)";
    }
}
