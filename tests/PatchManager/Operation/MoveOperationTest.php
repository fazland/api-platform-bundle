<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\PatchManager\Operation;

use Kcs\ApiPlatformBundle\PatchManager\Operation\MoveOperation;
use PHPUnit\Framework\TestCase;

class MoveOperationTest extends TestCase
{
    /**
     * @var MoveOperation
     */
    private $operation;

    protected function setUp()
    {
        $this->operation = new MoveOperation();
    }

    public function testShouldMoveValue()
    {
        $obj = ['one' => 'foo'];
        $this->operation->execute($obj, (object) ['path' => '/two', 'from' => '/one']);

        $this->assertArrayNotHasKey('one', $obj);
        $this->assertEquals('foo', $obj['two']);
    }
}
