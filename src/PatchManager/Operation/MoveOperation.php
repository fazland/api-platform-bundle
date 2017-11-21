<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\PatchManager\Operation;

class MoveOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    public function execute(&$subject, $operation): void
    {
        $copyOp = new CopyOperation($this->accessor);
        $copyOp->execute($subject, $operation);

        $removeOp = new RemoveOperation($this->accessor);
        $operation = clone $operation;
        $operation->path = $operation->from;
        $removeOp->execute($subject, $operation);
    }
}
