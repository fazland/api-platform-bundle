<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

class NullableMappingTransformer extends MappingTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform($value): ?array
    {
        if (null === $value) {
            return $value;
        }

        return parent::transform($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?array
    {
        if (null === $value) {
            return $value;
        }

        return parent::reverseTransform($value);
    }
}
