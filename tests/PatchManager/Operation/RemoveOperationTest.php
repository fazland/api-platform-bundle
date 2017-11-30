<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager\Operation;

use Doctrine\Common\Collections\ArrayCollection;
use Fazland\ApiPlatformBundle\PatchManager\Operation\RemoveOperation;
use PHPUnit\Framework\TestCase;

class RemoveOperationTest extends TestCase
{
    /**
     * @var RemoveOperation
     */
    private $operation;

    protected function setUp()
    {
        $this->operation = new RemoveOperation();
    }

    public function testShouldRemoveValue()
    {
        $obj = ['one' => 'bar'];
        $this->operation->execute($obj, (object) ['path' => '/one']);

        $this->assertArrayNotHasKey('one', $obj);
    }

    public function testShouldRemoveValueNested()
    {
        $obj = ['one' => ['bar' => ['baz', 'two']]];
        $this->operation->execute($obj, (object) ['path' => '/one/bar/1']);

        $this->assertCount(1, $obj['one']['bar']);
    }

    public function testShouldRemoveValueNestedCollection()
    {
        $obj = ['one' => new ArrayCollection(['bar' => ['baz', 'two']])];
        $this->operation->execute($obj, (object) ['path' => '/one/bar/1']);

        $this->assertCount(1, $obj['one']['bar']);
    }

    public function testShouldRemoveValueNestedIterable()
    {
        $iterable = new class() implements \IteratorAggregate {
            public function getIterator()
            {
                return new \ArrayIterator(['baz', 'two']);
            }
        };

        $obj = ['one' => ['bar' => $iterable]];
        $this->operation->execute($obj, (object) ['path' => '/one/bar/1']);

        $this->assertCount(1, $obj['one']['bar']);
    }

    public function testShouldRemoveValueNull()
    {
        $obj = ['one' => ['bar' => null]];
        $this->operation->execute($obj, (object) ['path' => '/one/bar/1']);

        $this->assertNull($obj['one']['bar']);
    }

    public function testShouldRemoveValueFromObject()
    {
        $obj = (object) ['one' => 'bar'];
        $this->operation->execute($obj, (object) ['path' => '/one']);

        $this->assertFalse(isset($obj->one));
    }

    public function testShouldRemoveWhenNullShouldNotThrow()
    {
        $obj = (object) ['one' => null];
        $this->operation->execute($obj, (object) ['path' => '/one']);

        $this->assertFalse(isset($obj->one));
    }

    public function testShouldRemoveShouldUnsetIfObjectHasArrayAccess()
    {
        $obj = new \ArrayObject(['one' => 'bar']);
        $this->operation->execute($obj, (object) ['path' => '/one']);

        $this->assertFalse(isset($obj['one']));
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException
     */
    public function testShouldThrowIfPropertyIsNotAccessible()
    {
        $obj = new class() {
            private $elements = [];
        };
        $this->operation->execute($obj, (object) ['path' => '/one']);
    }
}
