<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\PatchManager\Operation;

use Kcs\ApiPlatformBundle\PatchManager\Operation\CopyOperation;
use PHPUnit\Framework\TestCase;

class CopyOperationTest extends TestCase
{
    /**
     * @var CopyOperation
     */
    private $operation;

    protected function setUp()
    {
        $this->operation = new CopyOperation();
    }

    public function testShouldCopyValue()
    {
        $obj = ['one' => 'foo'];
        $this->operation->execute($obj, (object) ['path' => '/two', 'from' => '/one']);

        $this->assertEquals('foo', $obj['two']);
        $this->assertEquals('foo', $obj['one']);
    }

    /**
     * @expectedException \Kcs\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException
     */
    public function testShouldThrowIfPropertyDoesNotExist()
    {
        $obj = (object) ['bar' => 'foo'];
        $this->operation->execute($obj, (object) ['path' => '/two', 'from' => '/one']);

        $this->assertEquals('foo', $obj['two']);
        $this->assertEquals('foo', $obj['one']);
    }
}
