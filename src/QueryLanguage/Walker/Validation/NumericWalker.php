<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;

class NumericWalker extends ValidationWalker
{
    /**
     * {@inheritdoc}
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        if (! \is_numeric($expression->getValue())) {
            $this->addViolation('"{{ value }}" is not numeric.', [
                '{{ value }}' => (string) $expression,
            ]);
        }

        return parent::walkLiteral($expression);
    }
}
