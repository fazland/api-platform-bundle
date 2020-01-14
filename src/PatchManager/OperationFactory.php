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
    public const TEST_OPERATION = 'test';
    public const REMOVE_OPERATION = 'remove';
    public const ADD_OPERATION = 'add';
    public const REPLACE_OPERATION = 'replace';
    public const COPY_OPERATION = 'copy';
    public const MOVE_OPERATION = 'move';
    public const OPERATION_MAP = [
        self::TEST_OPERATION => TestOperation::class,
        self::REMOVE_OPERATION => RemoveOperation::class,
        self::ADD_OPERATION => AddOperation::class,
        self::REPLACE_OPERATION => ReplaceOperation::class,
        self::COPY_OPERATION => CopyOperation::class,
        self::MOVE_OPERATION => MoveOperation::class,
    ];

    private Accessor $accessor;

    public function __construct(?Accessor $accessor = null)
    {
        $this->accessor = $accessor ?? new Accessor();
    }

    /**
     * Creates a new Operation object.
     *
     * @param string $type
     *
     * @return OperationInterface
     *
     * @throws UnknownOperationException
     */
    public function factory(string $type): OperationInterface
    {
        if (! isset(self::OPERATION_MAP[$type])) {
            throw new UnknownOperationException('Unknown operation "'.$type.'" has been requested.');
        }

        $class = self::OPERATION_MAP[$type];

        return new $class($this->accessor);
    }
}
