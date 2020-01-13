<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Validator\Money;

use Symfony\Component\Validator\Constraint;

class IsoCurrency extends Constraint
{
    public string $message = '{{ code }} is not a valid ISO currency';
}
