<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer\Money;

use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * This transformer requires the moneyphp/money library.
 */
class MoneyTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value): ?array
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (! $value instanceof Money) {
            throw new TransformationFailedException(\sprintf('Expected %s instance.', Money::class));
        }

        return [
            'amount' => $value->getAmount(),
            'currency' => $value->getCurrency()->getCode(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?Money
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof Money) {
            return $value;
        }

        if (\is_array($value) && isset($value['amount'], $value['currency'])) {
            if (! \is_numeric($value['amount'])) {
                throw new TransformationFailedException('Amount must be numeric');
            }

            return new Money($value['amount'], new Currency($value['currency']));
        }

        if (! \is_numeric($value)) {
            throw new TransformationFailedException('Value must be numeric or an array with amount and currency keys set');
        }

        return Money::EUR($value);
    }
}
