<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\JSONPointer;

use Fazland\ApiPlatformBundle\JSONPointer\Accessor;
use Fazland\ApiPlatformBundle\JSONPointer\Path;
use Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TestClass;
use Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TestClassIsWritable;
use Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TestClassMagicGet;
use Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TestClassSetValue;
use Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer\Ticket5775Object;
use Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TypeHinted;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;

class AccessorTest extends TestCase
{
    /**
     * @var Accessor
     */
    private $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->propertyAccessor = new Accessor();
    }

    public function getPathsWithUnexpectedType(): iterable
    {
        return [
            ['', '/foobar'],
            ['foo', '/foobar'],
            [null, '/foobar'],
            [123, '/foobar'],
            [(object) ['prop' => null], '/prop/foobar'],
            [(object) ['prop' => (object) ['subProp' => null]], '/prop/subProp/foobar'],
            [['index' => null], '/index/foobar'],
            [['index' => ['subIndex' => null]], '/index/subIndex/foobar'],
        ];
    }

    public function getPathsWithMissingProperty(): iterable
    {
        return [
            [(object) ['firstName' => 'Bernhard'], '/lastName'],
            [(object) ['property' => (object) ['firstName' => 'Bernhard']], '/property/lastName'],
            [['index' => (object) ['firstName' => 'Bernhard']], '/index/lastName'],
            [new TestClass('Bernhard'), '/protectedProperty'],
            [new TestClass('Bernhard'), '/privateProperty'],
            [new TestClass('Bernhard'), '/protectedAccessor'],
            [new TestClass('Bernhard'), '/protectedIsAccessor'],
            [new TestClass('Bernhard'), '/protectedHasAccessor'],
            [new TestClass('Bernhard'), '/privateAccessor'],
            [new TestClass('Bernhard'), '/privateIsAccessor'],
            [new TestClass('Bernhard'), '/privateHasAccessor'],
        ];
    }

    public function getUnreachablePaths(): iterable
    {
        yield from $this->getPathsWithMissingProperty();
        yield [(object) ['firstName' => 'Bernhard'], '/firstName/foo'];
    }

    public function getPathsWithMissingIndex(): iterable
    {
        return [
            [['firstName' => 'Bernhard'], '/lastName'],
            [[], '/index/lastName'],
            [['index' => []], '/index/lastName'],
            [['index' => ['firstName' => 'Bernhard']], '/index/lastName'],
            [(object) ['property' => ['firstName' => 'Bernhard']], '/property/lastName'],
        ];
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testGetValue($objectOrArray, string $path, $value): void
    {
        self::assertSame($value, $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     */
    public function testGetValueThrowsExceptionIfPropertyNotFound($objectOrArray, string $path)
    {
        $this->expectException(NoSuchPropertyException::class);
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testGetValueThrowsNoExceptionIfIndexNotFound($objectOrArray, string $path): void
    {
        self::assertNull($this->propertyAccessor->getValue($objectOrArray, $path));
    }

    public function testGetValueThrowsExceptionIfNotArrayAccess(): void
    {
        $this->expectException(NoSuchPropertyException::class);
        $this->propertyAccessor->getValue(new \stdClass(), '/index');
    }

    public function testGetValueReadsMagicGet(): void
    {
        self::assertSame('Bernhard', $this->propertyAccessor->getValue(new TestClassMagicGet('Bernhard'), '/magicProperty'));
    }

    public function testGetValueConvertsExceptionsWhenMagicGetThrows(): void
    {
        $this->expectException(NoSuchPropertyException::class);
        $this->propertyAccessor->getValue(new TestClassMagicGet('Bernhard'), '/throwing');
    }

    public function testGetValueReadsArrayWithMissingIndexForCustomPropertyPath(): void
    {
        $object = new \ArrayObject();
        $array = ['child' => ['index' => $object]];

        self::assertNull($this->propertyAccessor->getValue($array, '/child/index/foo/bar'));
        self::assertSame([], $object->getArrayCopy());
    }

    // https://github.com/symfony/symfony/pull/4450
    public function testGetValueReadsMagicGetThatReturnsConstant(): void
    {
        self::assertSame('constant value', $this->propertyAccessor->getValue(new TestClassMagicGet('Bernhard'), '/constantMagicProperty'));
    }

    public function testGetValueNotModifyObject(): void
    {
        $object = new \stdClass();
        $object->firstName = ['Bernhard'];

        self::assertNull($this->propertyAccessor->getValue($object, '/firstName/1'));
        self::assertSame(['Bernhard'], $object->firstName);
    }

    public function testGetValueNotModifyObjectException(): void
    {
        $propertyAccessor = new Accessor();
        $object = new \stdClass();
        $object->firstName = ['Bernhard'];

        try {
            $propertyAccessor->getValue($object, '/firstName/1');
        } catch (NoSuchIndexException $e) {
        }

        self::assertSame(['Bernhard'], $object->firstName);
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     */
    public function testGetValueThrowsExceptionIfNotObjectOrArray($objectOrArray, string $path): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('PropertyAccessor requires a graph of objects or arrays to operate on');
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValue($objectOrArray, string $path): void
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        self::assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValueWithPropertyPath($objectOrArray, string $path): void
    {
        $this->propertyAccessor->setValue($objectOrArray, $path = new Path($path), 'Updated');

        self::assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     */
    public function testSetValueThrowsExceptionIfPropertyNotFound($objectOrArray, string $path): void
    {
        $this->expectException(NoSuchPropertyException::class);
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFound($objectOrArray, string $path): void
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        self::assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFoundAndIndexExceptionsEnabled($objectOrArray, string $path): void
    {
        $this->propertyAccessor = new Accessor();
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        self::assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    public function testSetValueThrowsExceptionIfNotArrayAccess(): void
    {
        $this->expectException(NoSuchPropertyException::class);
        $object = new \stdClass();

        $this->propertyAccessor->setValue($object, '/index', 'Updated');
    }

    public function testSetValueUpdatesMagicSet(): void
    {
        $author = new TestClassMagicGet('Bernhard');

        $this->propertyAccessor->setValue($author, '/magicProperty', 'Updated');

        self::assertEquals('Updated', $author->__get('magicProperty'));
    }

    public function testSetValueThrowsExceptionIfThereAreMissingParameters(): void
    {
        $this->expectException(NoSuchPropertyException::class);
        $object = new TestClass('Bernhard');

        $this->propertyAccessor->setValue($object, '/publicAccessorWithMoreRequiredParameters', 'Updated');
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     */
    public function testSetValueThrowsExceptionIfNotObjectOrArray($objectOrArray, string $path): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('PropertyAccessor requires a graph of objects or arrays to operate on');
        $this->propertyAccessor->setValue($objectOrArray, $path, 'value');
    }

    public function testGetValueWhenArrayValueIsNull(): void
    {
        $this->propertyAccessor = new Accessor();
        self::assertNull($this->propertyAccessor->getValue(['index' => ['nullable' => null]], '/index/nullable'));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsReadable($objectOrArray, string $path): void
    {
        self::assertTrue($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getUnreachablePaths
     */
    public function testIsReadableReturnsFalseIfPropertyNotFound($objectOrArray, string $path): void
    {
        self::assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsReadableReturnsTrueIfIndexNotFound($objectOrArray, string $path): void
    {
        // Non-existing indices can be read. In this case, null is returned
        self::assertTrue($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    public function testIsReadableRecognizesMagicGet(): void
    {
        self::assertTrue($this->propertyAccessor->isReadable(new TestClassMagicGet('Bernhard'), '/magicProperty'));
    }

    public function testIsReadableCatchesMagicGetExceptions(): void
    {
        self::assertFalse($this->propertyAccessor->isReadable(new TestClassMagicGet('Bernhard'), '/throwing'));
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     */
    public function testIsReadableReturnsFalseIfNotObjectOrArray($objectOrArray, string $path): void
    {
        self::assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsWritable($objectOrArray, string $path): void
    {
        self::assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getUnreachablePaths
     */
    public function testIsWritableReturnsFalseIfPropertyNotFound($objectOrArray, string $path): void
    {
        self::assertFalse($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsWritableReturnsTrueIfIndexNotFound($objectOrArray, string $path): void
    {
        // Non-existing indices can be written. Arrays are created on-demand.
        self::assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsWritableReturnsTrueIfIndexNotFoundAndIndexExceptionsEnabled($objectOrArray, string $path): void
    {
        $this->propertyAccessor = new Accessor();

        // Non-existing indices can be written even if exceptions are enabled
        self::assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    public function testIsWritableRecognizesMagicSet(): void
    {
        self::assertTrue($this->propertyAccessor->isWritable(new TestClassMagicGet('Bernhard'), '/magicProperty'));
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     */
    public function testIsWritableReturnsFalseIfNotObjectOrArray($objectOrArray, string $path): void
    {
        self::assertFalse($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    public function getValidPropertyPaths(): iterable
    {
        return [
            [['Bernhard', 'Schussek'], '/0', 'Bernhard'],
            [['Bernhard', 'Schussek'], '/1', 'Schussek'],
            [['firstName' => 'Bernhard'], '/firstName', 'Bernhard'],
            [['index' => ['firstName' => 'Bernhard']], '/index/firstName', 'Bernhard'],
            [(object) ['firstName' => 'Bernhard'], '/firstName', 'Bernhard'],
            [(object) ['property' => ['firstName' => 'Bernhard']], '/property/firstName', 'Bernhard'],
            [['index' => (object) ['firstName' => 'Bernhard']], '/index/firstName', 'Bernhard'],
            [(object) ['property' => (object) ['firstName' => 'Bernhard']], '/property/firstName', 'Bernhard'],
            [[(object) ['property' => ['firstName' => 'Bernhard']]], '/0/property/firstName', 'Bernhard'],

            // Accessor methods
            [new TestClass('Bernhard'), '/publicProperty', 'Bernhard'],
            [new TestClass('Bernhard'), '/publicAccessor', 'Bernhard'],
            [new TestClass('Bernhard'), '/publicAccessorWithDefaultValue', 'Bernhard'],
            [new TestClass('Bernhard'), '/publicAccessorWithRequiredAndDefaultValue', 'Bernhard'],
            [new TestClass('Bernhard'), '/publicIsAccessor', 'Bernhard'],
            [new TestClass('Bernhard'), '/publicHasAccessor', 'Bernhard'],
            [new TestClass('Bernhard'), '/publicGetSetter', 'Bernhard'],

            // Methods are camelized
            [new TestClass('Bernhard'), '/public_accessor', 'Bernhard'],
            [new TestClass('Bernhard'), '/_public_accessor', 'Bernhard'],

            // Properties are camelized
            [new TestClass('Bernhard'), '/public_property', 'Bernhard'],

            // Missing indices
            [['index' => []], '/index/firstName', null],
            [['root' => ['index' => []]], '/root/index/firstName', null],

            // Special chars
            [['%!@$§.' => 'Bernhard'], '/%!@$§.', 'Bernhard'],
            [['index' => ['%!@$§.' => 'Bernhard']], '/index/%!@$§.', 'Bernhard'],
            [(object) ['%!@$§' => 'Bernhard'], '/%!@$§', 'Bernhard'],
            [(object) ['property' => (object) ['%!@$§' => 'Bernhard']], '/property/%!@$§', 'Bernhard'],

            // nested objects and arrays
            [['foo' => new TestClass('bar')], '/foo/publicGetSetter', 'bar'],
            [new TestClass(['foo' => 'bar']), '/publicGetSetter/foo', 'bar'],
            [new TestClass(new TestClass('bar')), '/publicGetter/publicGetSetter', 'bar'],
            [new TestClass(['foo' => new TestClass('bar')]), '/publicGetter/foo/publicGetSetter', 'bar'],
            [new TestClass(new TestClass(new TestClass('bar'))), '/publicGetter/publicGetter/publicGetSetter', 'bar'],
            [new TestClass(['foo' => ['baz' => new TestClass('bar')]]), '/publicGetter/foo/baz/publicGetSetter', 'bar'],
        ];
    }

    public function testTicket5755(): void
    {
        $object = new Ticket5775Object();

        $this->propertyAccessor->setValue($object, '/property', 'foobar');

        self::assertEquals('foobar', $object->getProperty());
    }

    public function testSetValueDeepWithMagicGetter(): void
    {
        $obj = new TestClassMagicGet('foo');
        $obj->publicProperty = ['foo' => ['bar' => 'some_value']];
        $this->propertyAccessor->setValue($obj, '/publicProperty/foo/bar', 'Updated');
        self::assertSame('Updated', $obj->publicProperty['foo']['bar']);
    }

    public function getReferenceChainObjectsForSetValue(): iterable
    {
        return [
            [['a' => ['b' => ['c' => 'old-value']]], '/a/b/c', 'new-value'],
            [new TestClassSetValue(new TestClassSetValue('old-value')), '/value/value', 'new-value'],
            [new TestClassSetValue(['a' => ['b' => ['c' => new TestClassSetValue('old-value')]]]), '/value/a/b/c/value', 'new-value'],
            [new TestClassSetValue(['a' => ['b' => 'old-value']]), '/value/a/b', 'new-value'],
            [new \ArrayIterator(['a' => ['b' => ['c' => 'old-value']]]), '/a/b/c', 'new-value'],
        ];
    }

    /**
     * @dataProvider getReferenceChainObjectsForSetValue
     */
    public function testSetValueForReferenceChainIssue($object, string $path, $value): void
    {
        $this->propertyAccessor->setValue($object, $path, $value);

        self::assertEquals($value, $this->propertyAccessor->getValue($object, $path));
    }

    public function getReferenceChainObjectsForIsWritable(): iterable
    {
        return [
            [new TestClassIsWritable(['a' => ['b' => 'old-value']]), '/value/a/b', true],
            [new TestClassIsWritable(new \ArrayIterator(['a' => ['b' => 'old-value']])), '/value/a/b', true],
            [new TestClassIsWritable(['a' => ['b' => ['c' => new TestClassSetValue('old-value')]]]), '/value/a/b/c/value', true],
        ];
    }

    /**
     * @dataProvider getReferenceChainObjectsForIsWritable
     */
    public function testIsWritableForReferenceChainIssue($object, string $path, $value): void
    {
        self::assertEquals($value, $this->propertyAccessor->isWritable($object, $path));
    }

    public function testThrowTypeError(): void
    {
        $this->expectException(\TypeError::class);
        $object = new TypeHinted();

        $this->propertyAccessor->setValue($object, '/date', 'This is a string, \DateTime expected.');
    }

    public function testSetTypeHint(): void
    {
        $date = new \DateTime();
        $object = new TypeHinted();

        $this->propertyAccessor->setValue($object, '/date', $date);
        self::assertSame($date, $object->getDate());
    }

    public function testArrayNotBeingOverwritten(): void
    {
        $value = ['value1' => 'foo', 'value2' => 'bar'];
        $object = new TestClass($value);

        $this->propertyAccessor->setValue($object, '/publicAccessor/value2', 'baz');
        self::assertSame('baz', $this->propertyAccessor->getValue($object, '/publicAccessor/value2'));
        self::assertSame(['value1' => 'foo', 'value2' => 'baz'], $object->getPublicAccessor());
    }

    public function testCacheReadAccess(): void
    {
        $obj = new TestClass('foo');

        $propertyAccessor = new Accessor($cacheAdapter = new ArrayAdapter());
        self::assertEquals('foo', $propertyAccessor->getValue($obj, '/publicGetSetter'));
        $propertyAccessor->setValue($obj, '/publicGetSetter', 'bar');

        self::assertCount(3, $cacheAdapter->getValues());

        $propertyAccessor = new Accessor($cacheAdapter);
        $propertyAccessor->setValue($obj, '/publicGetSetter', 'baz');
        self::assertEquals('baz', $propertyAccessor->getValue($obj, '/publicGetSetter'));
    }

    public function testArrayAppendOnWrite(): void
    {
        $array = ['value' => ['foo' => ['bar']]];

        $this->propertyAccessor->setValue($array, '/value/foo/-', 'foofoo');
        self::assertCount(2, $array['value']['foo']);
        self::assertEquals('foofoo', $array['value']['foo'][1]);
    }

    public function testArrayDashIsNotAppendOnWrite(): void
    {
        $array = ['value' => ['foo' => ['bar']]];

        $this->propertyAccessor->setValue($array, '/value/-/foo', 'foofoo');
        self::assertEquals('foofoo', $array['value']['-']['foo']);
    }

    public function testArrayAppendOnArrayAccessObject(): void
    {
        $array = ['value' => ['foo' => new \ArrayObject(['bar'])]];

        $this->propertyAccessor->setValue($array, '/value/foo/-', 'foofoo');
        self::assertCount(2, $array['value']['foo']);
        self::assertEquals(['bar', 'foofoo'], $array['value']['foo']->getArrayCopy());
    }

    public function getUnappendableObjects(): iterable
    {
        yield [['value' => ['foo' => (object) ['bar']]]];
        yield [['value' => (object) ['foo' => (object) ['bar']]]];
        yield [(object) ['value' => (object) ['foo' => (object) ['bar']]]];
        yield [(object) ['value' => ['foo' => (object) ['bar']]]];
    }

    /**
     * @dataProvider getUnappendableObjects
     */
    public function testArrayAppendThrowsIfAdderIsNotFound($array): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->propertyAccessor->setValue($array, '/value/foo/-', 'foofoo');
    }

    public function testThrowTypeErrorWithInterface(): void
    {
        $this->expectException(\TypeError::class);
        $object = new TypeHinted();

        $this->propertyAccessor->setValue($object, '/countable', 'This is a string, \Countable expected.');
    }

    public function testSetValueCallsAdderIfAppendFlagIsSpecified(): void
    {
        $car = $this->getMockBuilder(AccessorCollectionTest_CompositeCar::class)->getMock();
        $structure = $this->getMockBuilder(AccessorCollectionTest_CarStructure::class)->getMock();
        $axesBefore = (object) [0 => 'first', 1 => 'second'];
        $axesAfterOne = (object) [0 => 'first', 1 => 'second', 2 => 'third'];

        $car->expects(self::any())
            ->method('getStructure')
            ->will(self::returnValue($structure))
        ;

        $structure->expects(self::at(0))
            ->method('getAxes')
            ->will(self::returnValue($axesBefore))
        ;

        $structure->expects(self::at(1))
            ->method('addAxis')
            ->with('third')
        ;

        $structure->expects(self::at(2))
            ->method('getAxes')
            ->will(self::returnValue($axesAfterOne))
        ;

        $structure->expects(self::at(3))
            ->method('addAxis')
            ->with('fourth')
        ;

        $this->propertyAccessor->setValue($car, '/structure/axes/-', 'third');
        $this->propertyAccessor->setValue($car, '/structure/axes/-', 'fourth');
    }
}
