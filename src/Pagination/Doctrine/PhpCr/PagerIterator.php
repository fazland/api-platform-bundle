<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination\Doctrine\PhpCr;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\ConverterPhpcr;
use Doctrine\ODM\PHPCR\Query\Builder\From;
use Doctrine\ODM\PHPCR\Query\Builder\Ordering;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;
use Fazland\ApiPlatformBundle\Pagination\Orderings;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator as BaseIterator;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Fazland\DoctrineExtra\ODM\PhpCr\IteratorTrait;

final class PagerIterator extends BaseIterator implements ObjectIteratorInterface
{
    use IteratorTrait;

    public function __construct(QueryBuilder $searchable, $orderBy)
    {
        $this->queryBuilder = clone $searchable;
        $this->apply(null);

        parent::__construct([], $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        parent::next();

        $this->_current = null;
        $this->_currentElement = parent::current();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        parent::rewind();

        $this->_current = null;
        $this->_currentElement = parent::current();
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjects(): array
    {
        $queryBuilder = clone $this->queryBuilder;

        /** @var From $fromNode */
        $fromNode = $queryBuilder->getChildOfType(AbstractNode::NT_FROM);
        /** @var SourceDocument $source */
        $source = $fromNode->getChildOfType(AbstractNode::NT_SOURCE);
        $alias = $source->getAlias();

        $method = new \ReflectionMethod(QueryBuilder::class, 'getConverter');
        $method->setAccessible(true);
        $converter = $method->invoke($queryBuilder);

        /** @var DocumentManagerInterface $documentManager */
        $documentManager = (function (): DocumentManagerInterface {
            return $this->dm;
        })->bindTo($converter, ConverterPhpcr::class)();

        $classMetadata = $documentManager->getClassMetadata($source->getDocumentFqn());

        foreach ($this->orderBy as $key => [$field, $direction]) {
            $method = 0 === $key ? 'orderBy' : 'addOrderBy';

            if ('nodename' === $classMetadata->getTypeOfField($field)) {
                $queryBuilder->{$method}()->{$direction}()->localName($alias);
            } else {
                $queryBuilder->{$method}()->{$direction}()->field($alias.'.'.$field);
            }
        }

        $limit = $this->pageSize;
        if (null !== $this->token) {
            $timestamp = $this->token->getOrderValue();
            $limit += $this->token->getOffset();
            $mainOrder = $this->orderBy[0];

            $type = $documentManager->getClassMetadata($source->getDocumentFqn())->getTypeOfField($mainOrder[0]);
            if ('date' === $type) {
                $timestamp = \DateTimeImmutable::createFromFormat('U', (string) $timestamp);
            }

            $direction = Orderings::SORT_ASC === $mainOrder[1] ? 'gte' : 'lte';
            /** @var Ordering $ordering */
            $ordering = $queryBuilder->andWhere()->{$direction}();

            if ('nodename' === $classMetadata->getTypeOfField($mainOrder[0])) {
                $ordering->localName($alias)->literal($timestamp);
            } else {
                $ordering->field($alias.'.'.$mainOrder[0])->literal($timestamp);
            }
        }

        $queryBuilder->setMaxResults($limit);
        $result = $queryBuilder->getQuery()->getResult();

        return \is_array($result) ? \array_values($result) : \iterator_to_array($result, false);
    }
}
