<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\JSONPointer;

class AccessorArrayTest extends AccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return $array;
    }
}
