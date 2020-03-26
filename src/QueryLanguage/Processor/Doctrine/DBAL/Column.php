<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\DBAL;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\DBAL\SqlWalker;

/**
 * @internal
 */
class Column implements ColumnInterface
{
    private string $columnType;
    public string $fieldName;
    public ?string $tableName;

    /**
     * @var string|callable|null
     */
    public $validationWalker;

    /**
     * @var string|callable|null
     */
    public $customWalker;

    public function __construct(string $fieldName, ?string $tableName = null, string $type = Types::STRING)
    {
        $this->fieldName = $fieldName;
        $this->tableName = $tableName;
        $this->columnType = $type;

        $this->validationWalker = null;
        $this->customWalker = null;
    }

    /**
     * {@inheritdoc}
     */
    public function addCondition($queryBuilder, ExpressionInterface $expression): void
    {
        $this->addWhereCondition($queryBuilder, $expression);
    }

    /**
     * Adds a simple condition to the query builder.
     *
     * @param QueryBuilder        $queryBuilder
     * @param ExpressionInterface $expression
     */
    private function addWhereCondition(QueryBuilder $queryBuilder, ExpressionInterface $expression): void
    {
        $walker = $this->customWalker;
        $fieldName = ($this->tableName ? $this->tableName.'.' : '').$this->fieldName;

        if (null !== $walker) {
            $walker = \is_string($walker) ? new $walker($queryBuilder, $fieldName) : $walker($queryBuilder, $fieldName, $this->columnType);
        } else {
            $walker = new SqlWalker($queryBuilder, $fieldName, $this->columnType);
        }

        $queryBuilder->andWhere($expression->dispatch($walker));
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationWalker()
    {
        return $this->validationWalker;
    }
}
