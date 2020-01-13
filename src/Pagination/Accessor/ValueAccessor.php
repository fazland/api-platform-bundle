<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination\Accessor;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ValueAccessor implements ValueAccessorInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(object $object, string $propertyPath)
    {
        return $this->propertyAccessor->getValue($object, $propertyPath);
    }
}
