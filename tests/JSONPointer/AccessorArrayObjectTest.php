<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\JSONPointer;

class AccessorArrayObjectTest extends AccessorCollectionTest
{
    /**
     * {@inheritdoc}
     */
    protected function getContainer(array $array)
    {
        return new \ArrayObject($array);
    }
}
