<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms string boolean values to a bool.
 */
class BooleanTransformer implements DataTransformerInterface
{
    public const TRUE_VALUES = ['1', 'true', 'yes', 'on', 'y', 't'];
    public const FALSE_VALUES = ['0', 'false', 'no', 'off', 'n', 'f'];

    /**
     * {@inheritdoc}
     */
    public function transform($value): ?bool
    {
        if (null === $value) {
            return $value;
        }

        if (! \is_bool($value)) {
            throw new TransformationFailedException('Expected a bool');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?bool
    {
        if (null === $value) {
            return null;
        }

        if (\is_bool($value)) {
            return $value;
        }

        if (! \is_scalar($value)) {
            throw new TransformationFailedException(\sprintf('Expected a scalar value, %s passed', \gettype($value)));
        }

        $value = \mb_strtolower((string) $value);
        if (\in_array($value, self::TRUE_VALUES, true)) {
            return true;
        }

        if (\in_array($value, self::FALSE_VALUES, true)) {
            return false;
        }

        throw new TransformationFailedException('Cannot transform value "'.$value.'"');
    }
}
