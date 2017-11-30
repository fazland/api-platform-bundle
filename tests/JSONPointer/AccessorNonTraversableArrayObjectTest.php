<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\JSONPointer;

use Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer\NonTraversableArrayObject;

class AccessorNonTraversableArrayObjectTest extends AccessorArrayAccessTest
{
    protected function getContainer(array $array)
    {
        return new NonTraversableArrayObject($array);
    }
}
