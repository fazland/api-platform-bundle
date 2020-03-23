<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Fazland\ApiPlatformBundle\Pagination\Doctrine\ORM\PagerIterator;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\AbstractProcessor;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Fazland\DoctrineExtra\ORM\EntityIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class Processor extends AbstractProcessor
{
    private string $rootAlias;
    private QueryBuilder $queryBuilder;
    private EntityManagerInterface $entityManager;
    private ClassMetadata $rootEntity;

    public function __construct(QueryBuilder $queryBuilder, FormFactoryInterface $formFactory, array $options = [])
    {
        parent::__construct($formFactory, $options);

        $this->queryBuilder = $queryBuilder;
        $this->entityManager = $this->queryBuilder->getEntityManager();

        $this->rootAlias = $this->queryBuilder->getRootAliases()[0];
        $this->rootEntity = $this->entityManager->getClassMetadata($this->queryBuilder->getRootEntities()[0]);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumn(string $fieldName): ColumnInterface
    {
        return new Column($fieldName, $this->rootAlias, $this->rootEntity, $this->entityManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifierFieldNames(): array
    {
        return $this->rootEntity->getIdentifierColumnNames();
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

            $fieldName = $this->columns[$result->ordering->getField()]->fieldName;
            $this->queryBuilder->orderBy($this->rootAlias.'.'.$fieldName, $result->ordering->getDirection());
        }

        if (null !== $pageSize) {
            $this->queryBuilder->setMaxResults($pageSize);
        }

        return new EntityIterator($this->queryBuilder);
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
