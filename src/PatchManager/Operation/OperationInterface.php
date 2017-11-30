<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager\Operation;

interface OperationInterface
{
    /**
     * Executes the operation.
     *
     * @param object|array $subject
     * @param object       $operation
     */
    public function execute(&$subject, $operation): void;
}
