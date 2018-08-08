<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\JSONPointer;

class AccessorCollectionTest_Car
{
    private $axes;

    public function __construct($axes = null)
    {
        $this->axes = $axes;
    }

    // In the test, use a name that StringUtil can't uniquely singularify
    public function addAxis($axis)
    {
        $this->axes[] = $axis;
    }

    public function removeAxis($axis)
    {
        foreach ($this->axes as $key => $value) {
            if ($value === $axis) {
                unset($this->axes[$key]);

                return;
            }
        }
    }

    public function getAxes()
    {
        return $this->axes;
    }
}

class AccessorCollectionTest_CarOnlyAdder
{
    public function addAxis($axis)
    {
    }

    public function getAxes()
    {
    }
}

class AccessorCollectionTest_CarOnlyRemover
{
    public function removeAxis($axis)
    {
    }

    public function getAxes()
    {
    }
}

class AccessorCollectionTest_CarNoAdderAndRemover
{
    public function getAxes()
    {
    }
}

class AccessorCollectionTest_CompositeCar
{
    public function getStructure()
    {
    }

    public function setStructure($structure)
    {
    }
}

class AccessorCollectionTest_CarStructure
{
    public function addAxis($axis)
    {
    }

    public function removeAxis($axis)
    {
    }

    public function getAxes()
    {
    }
}

abstract class AccessorCollectionTest extends AccessorArrayAccessTest
{
    public function testSetValueCallsAdderAndRemoverForCollections(): void
    {
        $axesBefore = $this->getContainer([1 => 'second', 3 => 'fourth', 4 => 'fifth']);
        $axesMerged = $this->getContainer([1 => 'first', 2 => 'second', 3 => 'third']);
        $axesAfter = $this->getContainer([1 => 'second', 5 => 'first', 6 => 'third']);
        $axesMergedCopy = is_object($axesMerged) ? clone $axesMerged : $axesMerged;

        // Don't use a mock in order to test whether the collections are
        // modified while iterating them
        $car = new AccessorCollectionTest_Car($axesBefore);

        $this->propertyAccessor->setValue($car, '/axes', $axesMerged);

        $this->assertEquals($axesAfter, $car->getAxes());

        // The passed collection was not modified
        $this->assertEquals($axesMergedCopy, $axesMerged);
    }

    public function testSetValueCallsAdderAndRemoverForNestedCollections(): void
    {
        $car = $this->getMockBuilder(__CLASS__.'_CompositeCar')->getMock();
        $structure = $this->getMockBuilder(__CLASS__.'_CarStructure')->getMock();
        $axesBefore = $this->getContainer([1 => 'second', 3 => 'fourth']);
        $axesAfter = $this->getContainer([0 => 'first', 1 => 'second', 2 => 'third']);

        $car->expects($this->any())
            ->method('getStructure')
            ->will($this->returnValue($structure));

        $structure->expects($this->at(0))
            ->method('getAxes')
            ->will($this->returnValue($axesBefore));
        $structure->expects($this->at(1))
            ->method('removeAxis')
            ->with('fourth');
        $structure->expects($this->at(2))
            ->method('addAxis')
            ->with('first');
        $structure->expects($this->at(3))
            ->method('addAxis')
            ->with('third');

        $this->propertyAccessor->setValue($car, '/structure/axes', $axesAfter);
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @expectedExceptionMessage Could not determine access type for property "axes".
     */
    public function testSetValueFailsIfNoAdderNorRemoverFound(): void
    {
        $car = $this->getMockBuilder(__CLASS__.'_CarNoAdderAndRemover')->getMock();
        $axesBefore = $this->getContainer([1 => 'second', 3 => 'fourth']);
        $axesAfter = $this->getContainer([0 => 'first', 1 => 'second', 2 => 'third']);

        $car->expects($this->any())
            ->method('getAxes')
            ->will($this->returnValue($axesBefore));

        $this->propertyAccessor->setValue($car, '/axes', $axesAfter);
    }

    public function testIsWritableReturnsTrueIfAdderAndRemoverExists(): void
    {
        $car = $this->getMockBuilder(__CLASS__.'_Car')->getMock();
        $this->assertTrue($this->propertyAccessor->isWritable($car, '/axes'));
    }

    public function testIsWritableReturnsFalseIfOnlyAdderExists(): void
    {
        $car = $this->getMockBuilder(__CLASS__.'_CarOnlyAdder')->getMock();
        $this->assertFalse($this->propertyAccessor->isWritable($car, '/axes'));
    }

    public function testIsWritableReturnsFalseIfOnlyRemoverExists(): void
    {
        $car = $this->getMockBuilder(__CLASS__.'_CarOnlyRemover')->getMock();
        $this->assertFalse($this->propertyAccessor->isWritable($car, '/axes'));
    }

    public function testIsWritableReturnsFalseIfNoAdderNorRemoverExists(): void
    {
        $car = $this->getMockBuilder(__CLASS__.'_CarNoAdderAndRemover')->getMock();
        $this->assertFalse($this->propertyAccessor->isWritable($car, '/axes'));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * expectedExceptionMessageRegExp /The property "axes" in class "Mock_PropertyAccessorCollectionTest_Car[^"]*" can be defined with the methods "addAxis()", "removeAxis()" but the new value must be an array or an instance of \Traversable, "string" given./
     */
    public function testSetValueFailsIfAdderAndRemoverExistButValueIsNotTraversable(): void
    {
        $car = $this->getMockBuilder(__CLASS__.'_Car')->getMock();

        $this->propertyAccessor->setValue($car, '/axes', 'Not an array or Traversable');
    }
}
