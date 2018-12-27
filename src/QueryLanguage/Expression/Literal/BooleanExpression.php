<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal;

final class BooleanExpression extends LiteralExpression
{
    protected function __construct(bool $value)
    {
        parent::__construct($value);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->value ? 'true' : 'false';
    }
}
