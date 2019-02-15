<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class IntegerTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value): ?int
    {
        return $this->integerTransformation($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?int
    {
        return $this->integerTransformation($value);
    }

    private function integerTransformation($value): ?int
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (\is_int($value)) {
            return $value;
        }

        if (! \is_string($value) || ! \is_numeric($value)) {
            throw new TransformationFailedException('Cannot transform a non-numeric string value to an integer');
        }

        return (int) $value;
    }
}
