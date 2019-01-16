<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager\Operation;

use Fazland\ApiPlatformBundle\JSONPointer\Accessor;

abstract class AbstractOperation implements OperationInterface
{
    /**
     * @var Accessor
     */
    protected $accessor;

    public function __construct(?Accessor $accessor = null)
    {
        $this->accessor = $accessor ?? new Accessor();
    }
}
