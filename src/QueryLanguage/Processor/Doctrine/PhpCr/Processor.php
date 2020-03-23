<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\PhpCr;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\From;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;
use Fazland\ApiPlatformBundle\Pagination\Doctrine\PhpCr\PagerIterator;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\AbstractProcessor;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Fazland\DoctrineExtra\ODM\PhpCr\DocumentIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class Processor extends AbstractProcessor
{
    private QueryBuilder $queryBuilder;
    private DocumentManagerInterface $documentManager;
    private ClassMetadata $rootDocument;
    private string $rootAlias;

    public function __construct(
        QueryBuilder $queryBuilder,
        DocumentManagerInterface $documentManager,
        FormFactoryInterface $formFactory,
        array $options = []
    ) {
        parent::__construct($formFactory, $options);

        $this->queryBuilder = $queryBuilder;
        $this->documentManager = $documentManager;
        $this->columns = [];

        /** @var From $fromNode */
        $fromNode = $this->queryBuilder->getChildOfType(AbstractNode::NT_FROM);
        /** @var SourceDocument $sourceNode */
        $sourceNode = $fromNode->getChildOfType(AbstractNode::NT_SOURCE);

        $this->rootDocument = $this->documentManager->getClassMetadata($sourceNode->getDocumentFqn());
        $this->rootAlias = $sourceNode->getAlias();
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumn(string $fieldName): ColumnInterface
    {
        return new Column($fieldName, $this->rootAlias, $this->rootDocument, $this->documentManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifierFieldNames(): array
    {
        return $this->rootDocument->getIdentifierFieldNames();
    }

    /**
     * Processes and validates the request, adds the filters to the query builder and
     * returns the iterator with the results.
     *
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

            $direction = $result->ordering->getDirection();
            $fieldName = $this->columns[$result->ordering->getField()]->fieldName;

            /** @var From $fromNode */
            $fromNode = $this->queryBuilder->getChildOfType(AbstractNode::NT_FROM);
            /** @var SourceDocument $source */
            $source = $fromNode->getChildOfType(AbstractNode::NT_SOURCE);
            $alias = $source->getAlias();

            if ('nodename' === $this->rootDocument->getTypeOfField($fieldName)) {
                $this->queryBuilder->orderBy()->{$direction}()->localName($alias);
            } else {
                $this->queryBuilder->orderBy()->{$direction}()->field($alias.'.'.$fieldName);
            }
        }

        if (null !== $pageSize) {
            $this->queryBuilder->setMaxResults($pageSize);
        }

        return new DocumentIterator($this->queryBuilder);
    }

    /**
     * Add conditions to query builder.
     *
     * @param array $filters
     */
    private function attachToQueryBuilder(array $filters): void
    {
        foreach ($filters as $key => $expr) {
            $column = $this->columns[$key];
            $column->addCondition($this->queryBuilder, $expr);
        }
    }
}
