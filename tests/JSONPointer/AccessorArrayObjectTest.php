<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\JSONPointer;

class AccessorArrayObjectTest extends AccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return new \ArrayObject($array);
    }
}
