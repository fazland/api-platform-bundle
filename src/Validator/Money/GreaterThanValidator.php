<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Validator\Money;

use Money\Money;

class GreaterThanValidator extends AbstractComparisonValidator
{
    /**
     * {@inheritdoc}
     */
    protected function compareMoneys(Money $value1, Money $value2): bool
    {
        return $value1->greaterThan($value2);
    }
}
