<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation;

use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface ValidationWalkerInterface extends TreeWalkerInterface
{
    /**
     * Sets the execution context.
     *
     * @param ExecutionContextInterface $context
     */
    public function setValidationContext(ExecutionContextInterface $context): void;
}
