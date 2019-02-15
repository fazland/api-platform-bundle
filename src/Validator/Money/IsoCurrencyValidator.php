<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Validator\Money;

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsoCurrencyValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (! $constraint instanceof IsoCurrency) {
            throw new UnexpectedTypeException($constraint, IsoCurrency::class);
        }

        $currencies = new ISOCurrencies();

        if (! $value instanceof Currency) {
            throw new UnexpectedTypeException($value, Currency::class);
        }

        if (! $currencies->contains($value)) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ code }}' => $value->getCode()]
            );
        }
    }
}
