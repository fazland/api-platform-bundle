<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager\Operation;

use Fazland\ApiPlatformBundle\PatchManager\Operation\MoveOperation;
use PHPUnit\Framework\TestCase;

class MoveOperationTest extends TestCase
{
    private MoveOperation $operation;

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

        self::assertArrayNotHasKey('one', $obj);
        self::assertEquals('foo', $obj['two']);
    }
}
