<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\PatchManager\Operation;

use Kcs\ApiPlatformBundle\JSONPointer\Accessor;

abstract class AbstractOperation implements OperationInterface
{
    /**
     * @var Accessor
     */
    protected $accessor;

    public function __construct(Accessor $accessor = null)
    {
        if (null === $accessor) {
            $accessor = new Accessor();
        }

        $this->accessor = $accessor;
    }
}
