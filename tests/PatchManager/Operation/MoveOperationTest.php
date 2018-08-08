<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager\Operation;

use Fazland\ApiPlatformBundle\PatchManager\Operation\MoveOperation;
use PHPUnit\Framework\TestCase;

class MoveOperationTest extends TestCase
{
    /**
     * @var MoveOperation
     */
    private $operation;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->operation = new MoveOperation();
    }

    public function testShouldMoveValue(): void
    {
        $obj = ['one' => 'foo'];
        $this->operation->execute($obj, (object) ['path' => '/two', 'from' => '/one']);

        $this->assertArrayNotHasKey('one', $obj);
        $this->assertEquals('foo', $obj['two']);
    }
}
