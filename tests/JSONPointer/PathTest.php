<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\JSONPointer;

use Kcs\ApiPlatformBundle\JSONPointer\Path;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    /**
     * @dataProvider providePath
     */
    public function testPathShouldBeCleanedUp(string $expected, string $path)
    {
        $path = new Path($path);

        $this->assertEquals($expected, $path->getPath());
    }

    public function providePath()
    {
        yield ['/foo', '/foo'];
        yield ['/foo/0', '/foo/0'];
        yield ['/', '/'];
        yield ['/a~1b', '/a~1b'];
        yield ['/c%d', '/c%d'];
        yield ['/e^f', '/e^f'];
        yield ['/g|h', '/g|h'];
        yield ['/i\\j', '/i\\j'];
        yield ['/k"l', '/k"l'];
        yield ['/ ', '/ '];
        yield ['/m~0n', '/m~0n'];

        yield ['/foo', '#/foo'];
        yield ['/foo/0', '#/foo/0'];
        yield ['/', '#/'];
        yield ['/a~1b', '#/a~1b'];
        yield ['/c%d', '#/c%25d'];
        yield ['/e^f', '#/e%5Ef'];
        yield ['/g|h', '#/g%7Ch'];
        yield ['/i\\j', '#/i%5Cj'];
        yield ['/k"l', '#/k%22l'];
        yield ['/ ', '#/%20'];
        yield ['/m~0n', '#/m~0n'];
    }

    public function provideInvalidPath()
    {
        yield ['##foo'];
        yield ['bar'];
        yield ['/foo~2'];
    }

    /**
     * @dataProvider provideInvalidPath
     * @expectedException \Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException
     */
    public function testPathShouldThrowOnInvalidPaths($value)
    {
        new Path($value);
    }

    public function testPathShouldBeIterable()
    {
        $path = new Path('/foo/0/a~1b/c%d/m~0n');

        $this->assertEquals([
            'foo',
            '0',
            'a/b',
            'c%d',
            'm~n',
        ], iterator_to_array($path));
    }

    public function testParentOfRootShouldReturnNull()
    {
        $path = new Path('/root_prop');

        $this->assertNull($path->getParent());
    }

    public function testParentShouldWork()
    {
        $path = new Path('/foo/0/a~1b/c%d/m~0n');

        $this->assertEquals('/foo/0/a~1b/c%d', (string) ($path = $path->getParent()));
        $this->assertEquals('/foo/0/a~1b', (string) ($path = $path->getParent()));
        $this->assertEquals('/foo/0', (string) ($path = $path->getParent()));
        $this->assertEquals('/foo', (string) ($path = $path->getParent()));
    }

    public function testIsPropertyIsAlwaysTrue()
    {
        $path = new Path('/foo');

        $this->assertTrue($path->isProperty(null));
    }

    public function testIsIndexIsAlwaysFalse()
    {
        $path = new Path('/foo');

        $this->assertFalse($path->isIndex(null));
    }

    /**
     * @expectedException \Symfony\Component\PropertyAccess\Exception\OutOfBoundsException
     */
    public function testGetElementShouldThrowIfOutOfBoundElementIsRequested()
    {
        $path = new Path('/foo/bar');
        $path->getElement(2);
    }

    public function testGetElementShouldReturnUnescapedValues()
    {
        $path = new Path('/foo/0/a~1b/c%d/m~0n');

        $this->assertEquals('foo', $path->getElement(0));
        $this->assertEquals('0', $path->getElement(1));
        $this->assertEquals('a/b', $path->getElement(2));
        $this->assertEquals('c%d', $path->getElement(3));
        $this->assertEquals('m~n', $path->getElement(4));
    }
}
