<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\JSONPointer;

use Fazland\ApiPlatformBundle\JSONPointer\Path;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\Exception\OutOfBoundsException;

class PathTest extends TestCase
{
    /**
     * @dataProvider providePath
     */
    public function testPathShouldBeCleanedUp(string $expected, string $path): void
    {
        $path = new Path($path);

        self::assertEquals($expected, $path->getPath());
    }

    public function providePath(): iterable
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

    public function provideInvalidPath(): iterable
    {
        yield ['##foo'];
        yield ['bar'];
        yield ['/foo~2'];
    }

    /**
     * @dataProvider provideInvalidPath
     */
    public function testPathShouldThrowOnInvalidPaths(string $value): void
    {
        $this->expectException(InvalidPropertyPathException::class);
        new Path($value);
    }

    public function testPathShouldBeIterable(): void
    {
        $path = new Path('/foo/0/a~1b/c%d/m~0n');

        self::assertEquals([
            'foo',
            '0',
            'a/b',
            'c%d',
            'm~n',
        ], \iterator_to_array($path));
    }

    public function testParentOfRootShouldReturnNull(): void
    {
        $path = new Path('/root_prop');

        self::assertNull($path->getParent());
    }

    public function testParentShouldWork(): void
    {
        $path = new Path('/foo/0/a~1b/c%d/m~0n');

        self::assertEquals('/foo/0/a~1b/c%d', (string) ($path = $path->getParent()));
        self::assertEquals('/foo/0/a~1b', (string) ($path = $path->getParent()));
        self::assertEquals('/foo/0', (string) ($path = $path->getParent()));
        self::assertEquals('/foo', (string) ($path = $path->getParent()));
    }

    public function testIsPropertyIsAlwaysTrue(): void
    {
        $path = new Path('/foo');

        self::assertTrue($path->isProperty(null));
    }

    public function testIsIndexIsAlwaysFalse(): void
    {
        $path = new Path('/foo');

        self::assertFalse($path->isIndex(null));
    }

    public function testGetElementShouldThrowIfOutOfBoundElementIsRequested(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $path = new Path('/foo/bar');
        $path->getElement(2);
    }

    public function testGetElementShouldReturnUnescapedValues(): void
    {
        $path = new Path('/foo/0/a~1b/c%d/m~0n');

        self::assertEquals('foo', $path->getElement(0));
        self::assertEquals('0', $path->getElement(1));
        self::assertEquals('a/b', $path->getElement(2));
        self::assertEquals('c%d', $path->getElement(3));
        self::assertEquals('m~n', $path->getElement(4));
    }
}
