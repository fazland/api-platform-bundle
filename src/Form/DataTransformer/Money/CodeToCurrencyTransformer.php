<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer\Money;

use Money\Currency;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * This transformer requires the moneyphp/money library.
 */
class CodeToCurrencyTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (! $value instanceof Currency) {
            throw new TransformationFailedException('Expected '.Currency::class);
        }

        return $value->getCode();
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?Currency
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof Currency) {
            return $value;
        }

        if (! \is_string($value)) {
            throw new TransformationFailedException('Expected a string');
        }

        return new Currency($value);
    }
}
