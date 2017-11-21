<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\JSONPointer;

use Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TraversableArrayObject;

class AccessorTraversableArrayObjectTest extends AccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return new TraversableArrayObject($array);
    }
}
