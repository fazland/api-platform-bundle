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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->factory = new OperationFactory();
    }

    public function getOperations(): iterable
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
    public function testFactoryShouldReturnAnOperationObject(string $op): void
    {
        $this->assertInstanceOf(OperationInterface::class, $this->factory->factory($op));
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\PatchManager\Exception\UnknownOperationException
     */
    public function testFactoryShouldThrowIfOperationIsUnknown(): void
    {
        $this->factory->factory('non-existent');
    }
}
