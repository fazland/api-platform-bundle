<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Validator\Money;

use Symfony\Component\Validator\Constraint;

class IsoCurrency extends Constraint
{
    /**
     * @var string
     */
    public $message = '{{ code }} is not a valid ISO currency';
}
