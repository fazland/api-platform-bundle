<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\JSONPointer;

use Kcs\ApiPlatformBundle\JSONPointer\Accessor;
use Kcs\ApiPlatformBundle\JSONPointer\Path;
use Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TestClass;
use Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TestClassIsWritable;
use Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TestClassMagicGet;
use Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TestClassSetValue;
use Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer\Ticket5775Object;
use Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer\TypeHinted;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;

class AccessorTest extends TestCase
{
    /**
     * @var Accessor
     */
    private $propertyAccessor;

    protected function setUp()
    {
        $this->propertyAccessor = new Accessor();
    }

    public function getPathsWithUnexpectedType()
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

    public function getPathsWithMissingProperty()
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

            // Properties are not camelized
            [new TestClass('Bernhard'), '/public_property'],
        ];
    }

    public function getUnreachablePaths()
    {
        yield from $this->getPathsWithMissingProperty();
        yield [(object) ['firstName' => 'Bernhard'], '/firstName/foo'];
    }

    public function getPathsWithMissingIndex()
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
    public function testGetValue($objectOrArray, $path, $value)
    {
        $this->assertSame($value, $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testGetValueThrowsExceptionIfPropertyNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testGetValueThrowsNoExceptionIfIndexNotFound($objectOrArray, $path)
    {
        $this->assertNull($this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testGetValueThrowsExceptionIfNotArrayAccess()
    {
        $this->propertyAccessor->getValue(new \stdClass(), '/index');
    }

    public function testGetValueReadsMagicGet()
    {
        $this->assertSame('Bernhard', $this->propertyAccessor->getValue(new TestClassMagicGet('Bernhard'), '/magicProperty'));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testGetValueConvertsExceptionsWhenMagicGetThrows()
    {
        $this->propertyAccessor->getValue(new TestClassMagicGet('Bernhard'), '/throwing');
    }

    public function testGetValueReadsArrayWithMissingIndexForCustomPropertyPath()
    {
        $object = new \ArrayObject();
        $array = ['child' => ['index' => $object]];

        $this->assertNull($this->propertyAccessor->getValue($array, '/child/index/foo/bar'));
        $this->assertSame([], $object->getArrayCopy());
    }

    // https://github.com/symfony/symfony/pull/4450
    public function testGetValueReadsMagicGetThatReturnsConstant()
    {
        $this->assertSame('constant value', $this->propertyAccessor->getValue(new TestClassMagicGet('Bernhard'), '/constantMagicProperty'));
    }

    public function testGetValueNotModifyObject()
    {
        $object = new \stdClass();
        $object->firstName = ['Bernhard'];

        $this->assertNull($this->propertyAccessor->getValue($object, '/firstName/1'));
        $this->assertSame(['Bernhard'], $object->firstName);
    }

    public function testGetValueNotModifyObjectException()
    {
        $propertyAccessor = new Accessor();
        $object = new \stdClass();
        $object->firstName = ['Bernhard'];

        try {
            $propertyAccessor->getValue($object, '/firstName/1');
        } catch (NoSuchIndexException $e) {
        }

        $this->assertSame(['Bernhard'], $object->firstName);
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     * @expectedException \Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on
     */
    public function testGetValueThrowsExceptionIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->propertyAccessor->getValue($objectOrArray, $path);
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValue($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValueWithPropertyPath($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path = new Path($path), 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingProperty
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfPropertyNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFound($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testSetValueThrowsNoExceptionIfIndexNotFoundAndIndexExceptionsEnabled($objectOrArray, $path)
    {
        $this->propertyAccessor = new Accessor();
        $this->propertyAccessor->setValue($objectOrArray, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($objectOrArray, $path));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfNotArrayAccess()
    {
        $object = new \stdClass();

        $this->propertyAccessor->setValue($object, '/index', 'Updated');
    }

    public function testSetValueUpdatesMagicSet()
    {
        $author = new TestClassMagicGet('Bernhard');

        $this->propertyAccessor->setValue($author, '/magicProperty', 'Updated');

        $this->assertEquals('Updated', $author->__get('magicProperty'));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testSetValueThrowsExceptionIfThereAreMissingParameters()
    {
        $object = new TestClass('Bernhard');

        $this->propertyAccessor->setValue($object, '/publicAccessorWithMoreRequiredParameters', 'Updated');
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     * @expectedException \Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException
     * @expectedExceptionMessage PropertyAccessor requires a graph of objects or arrays to operate on
     */
    public function testSetValueThrowsExceptionIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->propertyAccessor->setValue($objectOrArray, $path, 'value');
    }

    public function testGetValueWhenArrayValueIsNull()
    {
        $this->propertyAccessor = new Accessor();
        $this->assertNull($this->propertyAccessor->getValue(['index' => ['nullable' => null]], '/index/nullable'));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsReadable($objectOrArray, $path)
    {
        $this->assertTrue($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getUnreachablePaths
     */
    public function testIsReadableReturnsFalseIfPropertyNotFound($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsReadableReturnsTrueIfIndexNotFound($objectOrArray, $path)
    {
        // Non-existing indices can be read. In this case, null is returned
        $this->assertTrue($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    public function testIsReadableRecognizesMagicGet()
    {
        $this->assertTrue($this->propertyAccessor->isReadable(new TestClassMagicGet('Bernhard'), '/magicProperty'));
    }

    public function testIsReadableCatchesMagicGetExceptions()
    {
        $this->assertFalse($this->propertyAccessor->isReadable(new TestClassMagicGet('Bernhard'), '/throwing'));
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     */
    public function testIsReadableReturnsFalseIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isReadable($objectOrArray, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsWritable($objectOrArray, $path)
    {
        $this->assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getUnreachablePaths
     */
    public function testIsWritableReturnsFalseIfPropertyNotFound($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsWritableReturnsTrueIfIndexNotFound($objectOrArray, $path)
    {
        // Non-existing indices can be written. Arrays are created on-demand.
        $this->assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    /**
     * @dataProvider getPathsWithMissingIndex
     */
    public function testIsWritableReturnsTrueIfIndexNotFoundAndIndexExceptionsEnabled($objectOrArray, $path)
    {
        $this->propertyAccessor = new Accessor();

        // Non-existing indices can be written even if exceptions are enabled
        $this->assertTrue($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    public function testIsWritableRecognizesMagicSet()
    {
        $this->assertTrue($this->propertyAccessor->isWritable(new TestClassMagicGet('Bernhard'), '/magicProperty'));
    }

    /**
     * @dataProvider getPathsWithUnexpectedType
     */
    public function testIsWritableReturnsFalseIfNotObjectOrArray($objectOrArray, $path)
    {
        $this->assertFalse($this->propertyAccessor->isWritable($objectOrArray, $path));
    }

    public function getValidPropertyPaths()
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

    public function testTicket5755()
    {
        $object = new Ticket5775Object();

        $this->propertyAccessor->setValue($object, '/property', 'foobar');

        $this->assertEquals('foobar', $object->getProperty());
    }

    public function testSetValueDeepWithMagicGetter()
    {
        $obj = new TestClassMagicGet('foo');
        $obj->publicProperty = ['foo' => ['bar' => 'some_value']];
        $this->propertyAccessor->setValue($obj, '/publicProperty/foo/bar', 'Updated');
        $this->assertSame('Updated', $obj->publicProperty['foo']['bar']);
    }

    public function getReferenceChainObjectsForSetValue()
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
    public function testSetValueForReferenceChainIssue($object, $path, $value)
    {
        $this->propertyAccessor->setValue($object, $path, $value);

        $this->assertEquals($value, $this->propertyAccessor->getValue($object, $path));
    }

    public function getReferenceChainObjectsForIsWritable()
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
    public function testIsWritableForReferenceChainIssue($object, $path, $value)
    {
        $this->assertEquals($value, $this->propertyAccessor->isWritable($object, $path));
    }

    /**
     * @expectedException \TypeError
     */
    public function testThrowTypeError()
    {
        $object = new TypeHinted();

        $this->propertyAccessor->setValue($object, '/date', 'This is a string, \DateTime expected.');
    }

    public function testSetTypeHint()
    {
        $date = new \DateTime();
        $object = new TypeHinted();

        $this->propertyAccessor->setValue($object, '/date', $date);
        $this->assertSame($date, $object->getDate());
    }

    public function testArrayNotBeingOverwritten()
    {
        $value = ['value1' => 'foo', 'value2' => 'bar'];
        $object = new TestClass($value);

        $this->propertyAccessor->setValue($object, '/publicAccessor/value2', 'baz');
        $this->assertSame('baz', $this->propertyAccessor->getValue($object, '/publicAccessor/value2'));
        $this->assertSame(['value1' => 'foo', 'value2' => 'baz'], $object->getPublicAccessor());
    }

    public function testCacheReadAccess()
    {
        $obj = new TestClass('foo');

        $propertyAccessor = new Accessor($cacheAdapter = new ArrayAdapter());
        $this->assertEquals('foo', $propertyAccessor->getValue($obj, '/publicGetSetter'));
        $propertyAccessor->setValue($obj, '/publicGetSetter', 'bar');

        $this->assertCount(3, $cacheAdapter->getValues());

        $propertyAccessor = new Accessor($cacheAdapter);
        $propertyAccessor->setValue($obj, '/publicGetSetter', 'baz');
        $this->assertEquals('baz', $propertyAccessor->getValue($obj, '/publicGetSetter'));
    }

    public function testArrayAppendOnWrite()
    {
        $array = ['value' => ['foo' => ['bar']]];

        $this->propertyAccessor->setValue($array, '/value/foo/-', 'foofoo');
        $this->assertCount(2, $array['value']['foo']);
        $this->assertEquals('foofoo', $array['value']['foo'][1]);
    }

    public function testArrayDashIsNotAppendOnWrite()
    {
        $array = ['value' => ['foo' => ['bar']]];

        $this->propertyAccessor->setValue($array, '/value/-/foo', 'foofoo');
        $this->assertEquals('foofoo', $array['value']['-']['foo']);
    }

    public function testArrayAppendOnArrayAccessObject()
    {
        $array = ['value' => ['foo' => new \ArrayObject(['bar'])]];

        $this->propertyAccessor->setValue($array, '/value/foo/-', 'foofoo');
        $this->assertCount(2, $array['value']['foo']);
        $this->assertEquals(['bar', 'foofoo'], $array['value']['foo']->getArrayCopy());
    }

    public function getUnappendableObjects()
    {
        yield [['value' => ['foo' => (object) ['bar']]]];
        yield [['value' => (object) ['foo' => (object) ['bar']]]];
        yield [(object) ['value' => (object) ['foo' => (object) ['bar']]]];
        yield [(object) ['value' => ['foo' => (object) ['bar']]]];
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\InvalidArgumentException
     * @dataProvider getUnappendableObjects
     */
    public function testArrayAppendThrowsIfAdderIsNotFound($array)
    {
        $this->propertyAccessor->setValue($array, '/value/foo/-', 'foofoo');
    }

    /**
     * @expectedException \TypeError
     */
    public function testThrowTypeErrorWithInterface()
    {
        $object = new TypeHinted();

        $this->propertyAccessor->setValue($object, '/countable', 'This is a string, \Countable expected.');
    }

    public function testSetValueCallsAdderIfAppendFlagIsSpecified()
    {
        $car = $this->getMockBuilder(AccessorCollectionTest_CompositeCar::class)->getMock();
        $structure = $this->getMockBuilder(AccessorCollectionTest_CarStructure::class)->getMock();
        $axesBefore = (object) [0 => 'first', 1 => 'second'];
        $axesAfterOne = (object) [0 => 'first', 1 => 'second', 2 => 'third'];

        $car->expects($this->any())
            ->method('getStructure')
            ->will($this->returnValue($structure));

        $structure->expects($this->at(0))
            ->method('getAxes')
            ->will($this->returnValue($axesBefore));
        $structure->expects($this->at(1))
            ->method('addAxis')
            ->with('third');
        $structure->expects($this->at(2))
            ->method('getAxes')
            ->will($this->returnValue($axesAfterOne));
        $structure->expects($this->at(3))
            ->method('addAxis')
            ->with('fourth');

        $this->propertyAccessor->setValue($car, '/structure/axes/-', 'third');
        $this->propertyAccessor->setValue($car, '/structure/axes/-', 'fourth');
    }
}
