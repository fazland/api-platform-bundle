<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager;

use Fazland\ApiPlatformBundle\PatchManager\Operation\OperationInterface;
use Fazland\ApiPlatformBundle\PatchManager\OperationFactory;
use PHPUnit\Framework\TestCase;

class OperationFactoryTest extends TestCase
{
    /**
     * @var OperationFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->factory = new OperationFactory();
    }

    public function getOperations()
    {
        yield ['test'];
        yield ['remove'];
        yield ['add'];
        yield ['replace'];
        yield ['move'];
        yield ['copy'];
    }

    /**
     * @dataProvider getOperations
     */
    public function testFactoryShouldReturnAnOperationObject($op)
    {
        $this->assertInstanceOf(OperationInterface::class, $this->factory->factory($op));
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\UnknownOperationException
     */
    public function testFactoryShouldThrowIfOperationIsUnknown()
    {
        $this->factory->factory('non-existent');
    }
}
