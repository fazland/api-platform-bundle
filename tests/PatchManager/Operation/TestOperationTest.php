<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager\Operation;

use Fazland\ApiPlatformBundle\PatchManager\Operation\TestOperation;
use PHPUnit\Framework\TestCase;

class TestOperationTest extends TestCase
{
    /**
     * @var TestOperation
     */
    private $operation;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->operation = new TestOperation();
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException
     */
    public function testShouldThrowIfValuesAreNotEqual(): void
    {
        $op = (object) ['a' => 'foo'];
        $this->operation->execute($op, (object) ['path' => '/a', 'value' => 'bar']);
    }

    public function getTestObject()
    {
        return (object) [
            'boolT' => true,
            'boolF' => false,
            'int' => 47,
            'strInt' => '47',
            'float' => 47.0,
            'strFloat' => '47.0',
            'string' => 'foobar',
            'array' => ['foo' => 'bar', 'baz' => 'barbar'],
            'array_with_subarray' => ['foo' => ['bar', 'baz'], 'foobar' => 'barbar'],
            'object' => (object) ['foo' => 'bar', 'baz' => 'barbar'],
            'object_with_subobject' => (object) ['foo' => (object) ['bar', 'baz'], 'foobar' => 'barbar'],
        ];
    }

    public function getEqualValues(): iterable
    {
        yield ['/boolT', true];
        yield ['/boolT', 'true'];
        yield ['/boolF', false];
        yield ['/boolF', 'false'];
        yield ['/int', 47];
        yield ['/int', '47'];
        yield ['/int', 47.0];
        yield ['/int', '47.0'];
        yield ['/strInt', 47];
        yield ['/strInt', '47'];
        yield ['/strInt', 47.0];
        yield ['/strInt', '47.0'];
        yield ['/float', 47];
        yield ['/float', '47'];
        yield ['/float', 47.0];
        yield ['/float', '47.0'];
        yield ['/strFloat', 47];
        yield ['/strFloat', '47'];
        yield ['/strFloat', 47.0];
        yield ['/strFloat', '47.0'];
        yield ['/string', 'foobar'];
        yield ['/array', ['foo' => 'bar', 'baz' => 'barbar']];
        yield ['/array', ['baz' => 'barbar', 'foo' => 'bar']];
        yield ['/array', (object) ['foo' => 'bar', 'baz' => 'barbar']];
        yield ['/array', (object) ['baz' => 'barbar', 'foo' => 'bar']];
        yield ['/array_with_subarray', ['foo' => ['bar', 'baz'], 'foobar' => 'barbar']];
        yield ['/object', ['foo' => 'bar', 'baz' => 'barbar']];
        yield ['/object', ['baz' => 'barbar', 'foo' => 'bar']];
        yield ['/object', (object) ['foo' => 'bar', 'baz' => 'barbar']];
        yield ['/object', (object) ['baz' => 'barbar', 'foo' => 'bar']];
        yield ['/object_with_subobject', ['foo' => ['bar', 'baz'], 'foobar' => 'barbar']];
        yield ['/object_with_subobject', (object) ['foo' => ['bar', 'baz'], 'foobar' => 'barbar']];
    }

    /**
     * @dataProvider getEqualValues
     */
    public function testEqualValues($path, $value): void
    {
        $testObj = $this->getTestObject();

        $this->operation->execute($testObj, (object) ['path' => $path, 'value' => $value]);
        $this->assertTrue(true);
    }

    public function getUnequalValues(): iterable
    {
        yield ['/boolT', false];
        yield ['/boolT', 'false'];
        yield ['/boolF', true];
        yield ['/boolF', 'true'];
        yield ['/int', 42];
        yield ['/int', '42'];
        yield ['/int', 42.0];
        yield ['/int', '42.0'];
        yield ['/strInt', 42];
        yield ['/strInt', '42'];
        yield ['/strInt', 42.0];
        yield ['/strInt', '42.0'];
        yield ['/float', 42];
        yield ['/float', '42'];
        yield ['/float', 42.0];
        yield ['/float', '42.0'];
        yield ['/strFloat', 42];
        yield ['/strFloat', '42'];
        yield ['/strFloat', 42.0];
        yield ['/strFloat', '42.0'];
        yield ['/string', 'barbar'];
        yield ['/array', ['baz' => 'barbar']];
        yield ['/array', ['baz' => 'barbar', 'fooz' => 'barz']];
        yield ['/array', (object) ['baz' => 'barbar']];
        yield ['/array', (object) ['baz' => 'barbar', 'fooz' => 'barz']];
        yield ['/array_with_subarray', ['foo' => ['baz', 'bar'], 'foobar' => 'barbar']];
        yield ['/object', ['baz' => 'barbar']];
        yield ['/object', ['baz' => 'barbar', 'fooz' => 'barz']];
        yield ['/object', (object) ['baz' => 'barbar']];
        yield ['/object', (object) ['baz' => 'barbar', 'fooz' => 'barz']];
        yield ['/object_with_subobject', ['foo' => ['baz', 'bar'], 'foobar' => 'barbar']];
        yield ['/object_with_subobject', (object) ['foo' => ['baz', 'bar'], 'foobar' => 'barbar']];
    }

    /**
     * @dataProvider getEqualValues
     */
    public function testUnequalValues($path, $value): void
    {
        $testObj = $this->getTestObject();

        $this->operation->execute($testObj, (object) ['path' => $path, 'value' => $value]);
        $this->assertTrue(true);
    }
}
