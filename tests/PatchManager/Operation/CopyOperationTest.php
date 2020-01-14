<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager\Operation;

use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Fazland\ApiPlatformBundle\PatchManager\Operation\CopyOperation;
use PHPUnit\Framework\TestCase;

class CopyOperationTest extends TestCase
{
    private CopyOperation $operation;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->operation = new CopyOperation();
    }

    public function testShouldCopyValue(): void
    {
        $obj = ['one' => 'foo'];
        $this->operation->execute($obj, (object) ['path' => '/two', 'from' => '/one']);

        self::assertEquals('foo', $obj['two']);
        self::assertEquals('foo', $obj['one']);
    }

    public function testShouldThrowIfPropertyDoesNotExist(): void
    {
        $this->expectException(InvalidJSONException::class);
        $obj = (object) ['bar' => 'foo'];
        $this->operation->execute($obj, (object) ['path' => '/two', 'from' => '/one']);

        self::assertEquals('foo', $obj['two']);
        self::assertEquals('foo', $obj['one']);
    }
}
