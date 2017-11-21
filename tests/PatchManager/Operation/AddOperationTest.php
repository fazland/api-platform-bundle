<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\PatchManager\Operation;

use Kcs\ApiPlatformBundle\PatchManager\Operation\AddOperation;
use PHPUnit\Framework\TestCase;

class AddOperationTest extends TestCase
{
    /**
     * @var AddOperation
     */
    private $operation;

    protected function setUp()
    {
        $this->operation = new AddOperation();
    }

    public function testShouldAddValue()
    {
        $obj = [];
        $this->operation->execute($obj, (object) ['path' => '/one', 'value' => 'foo']);

        $this->assertEquals('foo', $obj['one']);
    }
}
