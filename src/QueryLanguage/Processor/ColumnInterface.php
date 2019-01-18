<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;

interface ColumnInterface
{
    /**
     * Adds condition to query builder.
     *
     * @param $queryBuilder
     * @param ExpressionInterface $expression
     */
    public function addCondition($queryBuilder, ExpressionInterface $expression): void;

    /**
     * Gets the validation walker factory for the current column.
     *
     * @return string|callable|null
     */
    public function getValidationWalker();
}
