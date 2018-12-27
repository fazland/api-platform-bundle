<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\Literal\LiteralExpression;

class DateTimeWalker extends ValidationWalker
{
    /**
     * {@inheritdoc}
     */
    public function walkLiteral(LiteralExpression $expression)
    {
        try {
            new \DateTimeImmutable($expression->getValue());
        } catch (\Exception $e) {
            $this->addViolation('{{ value }} is not a valid date time', [
                '{{ value }}' => (string) $expression->getValue(),
            ]);
        }
    }
}
