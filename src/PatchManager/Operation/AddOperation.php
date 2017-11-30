<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager\Operation;

class AddOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    public function execute(&$subject, $operation): void
    {
        $this->accessor->setValue($subject, $operation->path, $operation->value);
    }
}
