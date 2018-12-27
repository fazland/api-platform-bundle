<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager\Operation;

use Fazland\ApiPlatformBundle\PatchManager\Operation\AddOperation;
use PHPUnit\Framework\TestCase;

class AddOperationTest extends TestCase
{
    /**
     * @var AddOperation
     */
    private $operation;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->operation = new AddOperation();
    }

    public function testShouldAddValue(): void
    {
        $obj = [];
        $this->operation->execute($obj, (object) ['path' => '/one', 'value' => 'foo']);

        self::assertEquals('foo', $obj['one']);
    }
}
