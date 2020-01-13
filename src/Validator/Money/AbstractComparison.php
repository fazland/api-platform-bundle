<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Validator\Money;

use Money\Currency;
use Money\Money;
use Symfony\Component\Validator\Constraints\AbstractComparison as BaseAbstractComparison;

abstract class AbstractComparison extends BaseAbstractComparison
{
    /**
     * @var string
     */
    public string $currency;

    public function __construct($options = null)
    {
        $this->currency = 'EUR';

        if (null === $options) {
            $options = [];
        }

        parent::__construct($options);

        $this->value = new Money($this->value, new Currency($this->currency));
    }
}
