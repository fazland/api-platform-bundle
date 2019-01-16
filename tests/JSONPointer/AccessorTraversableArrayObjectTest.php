<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\JSONPointer;

use Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TraversableArrayObject;

class AccessorTraversableArrayObjectTest extends AccessorCollectionTest
{
    /**
     * {@inheritdoc}
     */
    protected function getContainer(array $array)
    {
        return new TraversableArrayObject($array);
    }
}
