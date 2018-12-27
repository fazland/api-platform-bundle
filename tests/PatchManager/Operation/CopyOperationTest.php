<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager\Operation;

use Fazland\ApiPlatformBundle\PatchManager\Operation\CopyOperation;
use PHPUnit\Framework\TestCase;

class CopyOperationTest extends TestCase
{
    /**
     * @var CopyOperation
     */
    private $operation;

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

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException
     */
    public function testShouldThrowIfPropertyDoesNotExist(): void
    {
        $obj = (object) ['bar' => 'foo'];
        $this->operation->execute($obj, (object) ['path' => '/two', 'from' => '/one']);

        self::assertEquals('foo', $obj['two']);
        self::assertEquals('foo', $obj['one']);
    }
}
