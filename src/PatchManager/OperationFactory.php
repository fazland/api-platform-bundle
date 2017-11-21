<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\PatchManager;

use Kcs\ApiPlatformBundle\JSONPointer\Accessor;
use Kcs\ApiPlatformBundle\PatchManager\Exception\UnknownOperationException;
use Kcs\ApiPlatformBundle\PatchManager\Operation\AddOperation;
use Kcs\ApiPlatformBundle\PatchManager\Operation\CopyOperation;
use Kcs\ApiPlatformBundle\PatchManager\Operation\MoveOperation;
use Kcs\ApiPlatformBundle\PatchManager\Operation\OperationInterface;
use Kcs\ApiPlatformBundle\PatchManager\Operation\RemoveOperation;
use Kcs\ApiPlatformBundle\PatchManager\Operation\ReplaceOperation;
use Kcs\ApiPlatformBundle\PatchManager\Operation\TestOperation;

class OperationFactory
{
    public function __construct(Accessor $accessor = null)
    {
        if (null === $accessor) {
            $accessor = new Accessor();
        }

        $this->accessor = $accessor;
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
