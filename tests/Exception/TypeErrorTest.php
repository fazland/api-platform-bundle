<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Exception;

use Fazland\ApiPlatformBundle\Exception\TypeError;
use Fazland\ApiPlatformBundle\Tests\Fixtures\TestObject;
use PHPUnit\Framework\TestCase;

class TypeErrorTest extends TestCase
{
    public function providerForCreateArgumentInvalid(): iterable
    {
        $tests = [];

        $tests[] = [
            'Argument 1 passed to Foo::bar must be of type string, integer given',
            1, 'Foo::bar', 'string', 23,
        ];

        $tests[] = [
            'Argument 1 passed to Foo::bar must be of type Foo or Foox, string given',
            1, 'Foo::bar', ['Foo', 'Foox'], 'asf',
        ];

        $tests[] = [
            'Argument 2 passed to Foo::bar must be of type Foo, Foox or null, Fazland\ApiPlatformBundle\Tests\Fixtures\TestObject given',
            2, 'Foo::bar', ['Foo', 'Foox', 'null'], new TestObject(),
        ];

        return $tests;
    }

    /**
     * @dataProvider providerForCreateArgumentInvalid
     */
    public function testCreateArgumentInvalidShouldFormatMessageCorrectly(string $message, int $no, string $function, $expected, $given): void
    {
        self::assertEquals($message, TypeError::createArgumentInvalid($no, $function, $expected, $given)->getMessage());
    }
}
