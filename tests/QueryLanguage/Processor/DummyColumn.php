<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\QueryLanguage\Processor;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalker;

class DummyColumn implements ColumnInterface
{
    public function addCondition($queryBuilder, ExpressionInterface $expression): void
    {
        // Do nothing.
    }

    public function getValidationWalker()
    {
        return new ValidationWalker();
    }
}
