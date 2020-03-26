<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\DBAL;

use Doctrine\DBAL\Query\QueryBuilder;
use Fazland\ApiPlatformBundle\Pagination\Doctrine\DBAL\PagerIterator;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\OrderExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\AbstractProcessor;
use Fazland\DoctrineExtra\DBAL\RowIterator;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Processor extends AbstractProcessor
{
    private QueryBuilder $queryBuilder;
    private array $identifierFields;

    public function __construct(QueryBuilder $queryBuilder, FormFactoryInterface $formFactory, array $options = [])
    {
        parent::__construct($formFactory, $options);

        $this->identifierFields = \array_values($this->options['identifiers']);
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @param Request $request
     *
     * @return ObjectIteratorInterface|FormInterface
     */
    public function processRequest(Request $request)
    {
        $result = $this->handleRequest($request);
        if ($result instanceof FormInterface) {
            return $result;
        }

        $this->attachToQueryBuilder($result->filters);
        $pageSize = $this->options['default_page_size'] ?? $result->limit;

        if (null !== $result->skip) {
            $this->queryBuilder->setFirstResult($result->skip);
        }

        if (null !== $result->ordering) {
            if ($this->options['continuation_token']) {
                $iterator = new PagerIterator($this->queryBuilder, $this->parseOrderings($result->ordering));
                $iterator->setToken($result->pageToken);
                if (null !== $pageSize) {
                    $iterator->setPageSize($pageSize);
                }

                return $iterator;
            }

            $sql = $this->queryBuilder->getSQL();
            $direction = $result->ordering->getDirection();
            $fieldName = $this->columns[$result->ordering->getField()]->fieldName;

            $this->queryBuilder = $this->queryBuilder->getConnection()
                ->createQueryBuilder()
                ->select('*')
                ->from("($sql)", 'x')
                ->setFirstResult($result->skip ?? 0)
                ->orderBy($fieldName, $direction)
            ;
        }

        if (null !== $pageSize) {
            $this->queryBuilder->setMaxResults($pageSize);
        }

        return new RowIterator($this->queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumn(string $fieldName): ColumnInterface
    {
        $tableName = null;
        if (false !== \strpos($fieldName, '.')) {
            [$tableName, $fieldName] = \explode('.', $fieldName);
        }

        return new Column($fieldName, $tableName);
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifierFieldNames(): array
    {
        return $this->identifierFields;
    }

    /**
     * Parses the ordering expression for continuation token.
     *
     * @param OrderExpression $ordering
     *
     * @return array
     */
    protected function parseOrderings(OrderExpression $ordering): array
    {
        $checksumColumn = $this->options['continuation_token']['checksum_field'] ?? $this->getIdentifierFieldNames()[0] ?? null;
        $column = $this->columns[$ordering->getField()];

        if (null === $checksumColumn) {
            foreach ($this->columns as $column) {
                if ($checksumColumn === $column) {
                    continue;
                }

                $checksumColumn = $column;
                break;
            }
        }

        $checksumField = $checksumColumn instanceof Column ? $checksumColumn->fieldName : $checksumColumn;
        $fieldName = $column->fieldName;
        $direction = $ordering->getDirection();

        return [
            $fieldName => $direction,
            $checksumField => 'ASC',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'identifiers' => [],
        ]);

        $resolver->setAllowedTypes('identifiers', 'array');
    }

    /**
     * Add conditions to query builder.
     *
     * @param array $filters
     */
    private function attachToQueryBuilder(array $filters): void
    {
        $this->queryBuilder->andWhere('1 = 1');

        foreach ($filters as $key => $expr) {
            $column = $this->columns[$key];
            $column->addCondition($this->queryBuilder, $expr);
        }
    }
}
