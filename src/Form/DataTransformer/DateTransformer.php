<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

use Cake\Chronos\Chronos;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DateTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (! $value instanceof \DateTimeInterface) {
            throw new TransformationFailedException(\sprintf(
                'Expected a %s instance',
                \DateTimeInterface::class
            ));
        }

        return Chronos::instance($value)
            ->startOfDay()
            ->format('Y-m-d')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?\DateTimeInterface
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value;
        }

        if (! \is_string($value)) {
            throw new TransformationFailedException('Expected a string');
        }

        $dateTime = Chronos::now()->startOfDay();
        if (\preg_match('/(\d{2})\/(\d{2})\/(\d{4,})/', $value, $matches)) {
            return $dateTime->setDate($matches[3], $matches[2], $matches[1]);
        }

        if (\preg_match('/(\d{4,})-(\d{2})-(\d{2})/', $value, $matches)) {
            return $dateTime->setDate($matches[1], $matches[2], $matches[3]);
        }

        throw new TransformationFailedException('Unexpected date format');
    }
}
