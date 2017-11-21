<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\JSONPointer;

use Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer\NonTraversableArrayObject;

class AccessorNonTraversableArrayObjectTest extends AccessorArrayAccessTest
{
    protected function getContainer(array $array)
    {
        return new NonTraversableArrayObject($array);
    }
}
