<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager;

use Fazland\ApiPlatformBundle\JSONPointer\Accessor;
use Fazland\ApiPlatformBundle\PatchManager\Exception\UnknownOperationException;
use Fazland\ApiPlatformBundle\PatchManager\Operation\AddOperation;
use Fazland\ApiPlatformBundle\PatchManager\Operation\CopyOperation;
use Fazland\ApiPlatformBundle\PatchManager\Operation\MoveOperation;
use Fazland\ApiPlatformBundle\PatchManager\Operation\OperationInterface;
use Fazland\ApiPlatformBundle\PatchManager\Operation\RemoveOperation;
use Fazland\ApiPlatformBundle\PatchManager\Operation\ReplaceOperation;
use Fazland\ApiPlatformBundle\PatchManager\Operation\TestOperation;

class OperationFactory
{
    private Accessor $accessor;

    public function __construct(?Accessor $accessor = null)
    {
        $this->accessor = $accessor ?? new Accessor();
    }

    /**
     * Creates a new Operation object.
     *
     * @param string $op
     *
     * @return OperationInterface
     *
     * @throws UnknownOperationException
     */
    public function factory(string $op): OperationInterface
    {
        switch ($op) {
            case 'test':
                return new TestOperation($this->accessor);

            case 'remove':
                return new RemoveOperation($this->accessor);

            case 'add':
                return new AddOperation($this->accessor);

            case 'replace':
                return new ReplaceOperation($this->accessor);

            case 'copy':
                return new CopyOperation($this->accessor);

            case 'move':
                return new MoveOperation($this->accessor);

            default:
                throw new UnknownOperationException('Unknown operation "'.$op.'" has been requested.');
        }
    }
}
