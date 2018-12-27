<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor;

interface ColumnInterface
{
    /**
     * Gets the validation walker factory for the current column.
     *
     * @return null|string|callable
     */
    public function getValidationWalker();
}
