<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination\Accessor;

interface ValueAccessorInterface
{
    /**
     * Gets an object value at given path.
     *
     * @param object $object
     * @param string $propertyPath
     *
     * @return mixed
     */
    public function getValue(object $object, string $propertyPath);
}
