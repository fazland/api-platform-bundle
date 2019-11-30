<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Validator\Money;

use Money\Money;
use Symfony\Component\Validator\Constraints\AbstractComparisonValidator as BaseAbstractComparisonValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

abstract class AbstractComparisonValidator extends BaseAbstractComparisonValidator
{
    /**
     * @param Money $value1
     * @param Money $value2
     *
     * {@inheritdoc}
     */
    final protected function compareValues($value1, $value2): bool
    {
        if (! \is_object($value1) || ! $value1 instanceof Money) {
            throw new UnexpectedValueException($value1, Money::class);
        }

        if (! \is_object($value2) || ! $value2 instanceof Money) {
            throw new UnexpectedValueException($value2, Money::class);
        }

        if (! $value1->getCurrency()->equals($value2->getCurrency())) {
            return false;
        }

        return $this->compareMoneys($value1, $value2);
    }

    /**
     * Compares two Money instances.
     *
     * @param Money $value1
     * @param Money $value2
     *
     * @return bool
     */
    abstract protected function compareMoneys(Money $value1, Money $value2): bool;
}
