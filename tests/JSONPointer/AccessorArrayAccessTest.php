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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->propertyAccessor = new Accessor();
    }

    abstract protected function getContainer(array $array);

    public function getValidPropertyPaths(): iterable
    {
        return [
            [$this->getContainer(['firstName' => 'Bernhard']), '/firstName', 'Bernhard'],
            [$this->getContainer(['person' => $this->getContainer(['firstName' => 'Bernhard'])]), '/person/firstName', 'Bernhard'],
        ];
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testGetValue($collection, string $path, string $value): void
    {
        self::assertSame($value, $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testSetValue($collection, string $path): void
    {
        $this->propertyAccessor->setValue($collection, $path, 'Updated');

        self::assertSame('Updated', $this->propertyAccessor->getValue($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsReadable($collection, string $path): void
    {
        self::assertTrue($this->propertyAccessor->isReadable($collection, $path));
    }

    /**
     * @dataProvider getValidPropertyPaths
     */
    public function testIsWritable($collection, string $path): void
    {
        self::assertTrue($this->propertyAccessor->isWritable($collection, $path));
    }
}
