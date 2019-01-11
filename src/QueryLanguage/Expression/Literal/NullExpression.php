<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal;

final class NullExpression extends LiteralExpression
{
    protected function __construct()
    {
        parent::__construct(null);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return 'null';
    }
}
