<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\PatchManager;

use Fazland\ApiPlatformBundle\PatchManager\Exception\UnknownOperationException;
use Fazland\ApiPlatformBundle\PatchManager\Operation\OperationInterface;
use Fazland\ApiPlatformBundle\PatchManager\OperationFactory;
use PHPUnit\Framework\TestCase;

class OperationFactoryTest extends TestCase
{
    private OperationFactory $factory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->factory = new OperationFactory();
    }

    public function getOperations(): iterable
    {
        foreach (OperationFactory::OPERATION_MAP as $operationType => $operationClass) {
            yield [$operationType, $operationClass];
        }
    }

    /**
     * @dataProvider getOperations
     */
    public function testFactoryShouldReturnAnOperationObject(string $operationType, string $operationClass): void
    {
        self::assertInstanceOf($operationClass, $this->factory->factory($operationType));
    }

    public function testFactoryShouldThrowIfOperationIsUnknown(): void
    {
        $this->expectException(UnknownOperationException::class);
        $this->factory->factory('non-existent');
    }
}
