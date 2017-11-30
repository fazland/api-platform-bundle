<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\JSONPointer;

use Fazland\ApiPlatformBundle\JSONPointer\Accessor;
use PHPUnit\Framework\TestCase;

abstract class AccessorArrayAccessTest extends TestCase
{
    /**
     * @var Accessor
     */
    protected $propertyAccessor;

    protected function setUp()
    {
        $this->propertyAccessor = new Accessor();
    }

    abstract protected function getContainer(array $array);

    public function getValidPropertyPaths()
    {
        return [
            [$this->getContainer(['firstName' => 'Bernhard']), '/firstName', 'Bernhard'],
            [$this->getContainer(['person' => $this->getContainer(['firstName' => 'Bernhard'])]), '/person/firstName', 'Bernhard'],
        ];
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testGetValue($collection, $path, $value)
    {
        $this->assertSame($value, $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValue($collection, $path)
    {
        $this->propertyAccessor->setValue($collection, $path, 'Updated');

        $this->assertSame('Updated', $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsReadable($collection, $path)
    {
        $this->assertTrue($this->propertyAccessor->isReadable($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsWritable($collection, $path)
    {
        $this->assertTrue($this->propertyAccessor->isWritable($collection, $path));
    }
}
