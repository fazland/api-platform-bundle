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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->operation = new RemoveOperation();
    }

    public function testShouldRemoveValue(): void
    {
        $obj = ['one' => 'bar'];
        $this->operation->execute($obj, (object) ['path' => '/one']);

        $this->assertArrayNotHasKey('one', $obj);
    }

    public function testShouldRemoveValueNested(): void
    {
        $obj = ['one' => ['bar' => ['baz', 'two']]];
        $this->operation->execute($obj, (object) ['path' => '/one/bar/1']);

        $this->assertCount(1, $obj['one']['bar']);
    }

    public function testShouldRemoveValueNestedCollection(): void
    {
        $obj = ['one' => new ArrayCollection(['bar' => ['baz', 'two']])];
        $this->operation->execute($obj, (object) ['path' => '/one/bar/1']);

        $this->assertCount(1, $obj['one']['bar']);
    }

    public function testShouldRemoveValueNestedIterable(): void
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

    public function testShouldRemoveValueNull(): void
    {
        $obj = ['one' => ['bar' => null]];
        $this->operation->execute($obj, (object) ['path' => '/one/bar/1']);

        $this->assertNull($obj['one']['bar']);
    }

    public function testShouldRemoveValueFromObject(): void
    {
        $obj = (object) ['one' => 'bar'];
        $this->operation->execute($obj, (object) ['path' => '/one']);

        $this->assertFalse(isset($obj->one));
    }

    public function testShouldRemoveWhenNullShouldNotThrow(): void
    {
        $obj = (object) ['one' => null];
        $this->operation->execute($obj, (object) ['path' => '/one']);

        $this->assertFalse(isset($obj->one));
    }

    public function testShouldRemoveShouldUnsetIfObjectHasArrayAccess(): void
    {
        $obj = new \ArrayObject(['one' => 'bar']);
        $this->operation->execute($obj, (object) ['path' => '/one']);

        $this->assertFalse(isset($obj['one']));
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException
     */
    public function testShouldThrowIfPropertyIsNotAccessible(): void
    {
        $obj = new class() {
            private $elements = [];
        };
        $this->operation->execute($obj, (object) ['path' => '/one']);
    }
}
