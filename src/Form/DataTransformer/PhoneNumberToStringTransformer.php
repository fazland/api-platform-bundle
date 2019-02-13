<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * This transformer requires the giggsey/libphonenumber-for-php library.
 */
class PhoneNumberToStringTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value): string
    {
        if (null === $value) {
            return '';
        }

        if (! $value instanceof PhoneNumber) {
            throw new TransformationFailedException('Expected a '.PhoneNumber::class.'.');
        }

        $util = PhoneNumberUtil::getInstance();

        return $util->format($value, PhoneNumberFormat::E164);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?Phonenumber
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof PhoneNumber) {
            return $value;
        }

        if (! \is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $util = PhoneNumberUtil::getInstance();
        try {
            return $util->parse($value);
        } catch (NumberParseException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
