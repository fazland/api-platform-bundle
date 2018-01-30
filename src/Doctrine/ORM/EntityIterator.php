<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\ORM;

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;
use Fazland\ApiPlatformBundle\Doctrine\Traits\IteratorTrait;

/**
 * This class allows iterating a query iterator for a single entity query.
 */
class EntityIterator implements ObjectIterator
{
    use IteratorTrait;

    /**
     * @var IterableResult
     */
    private $internalIterator;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var null|int
     */
    private $_totalCount;

    public function __construct(QueryBuilder $queryBuilder)
    {
        if (1 !== count($queryBuilder->getRootAliases())) {
            throw new \InvalidArgumentException('QueryBuilder must have exactly one root aliases for the iterator to work.');
        }

        $this->queryBuilder = clone $queryBuilder;
        $this->internalIterator = $this->queryBuilder->getQuery()->iterate();
        $this->_totalCount = null;

        $this->apply(null);
        $this->_currentElement = $this->internalIterator->current()[0];
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (null === $this->_totalCount) {
            $queryBuilder = clone $this->queryBuilder;
            $alias = $queryBuilder->getRootAliases()[0];

            $this->_totalCount = (int) $queryBuilder->select('COUNT('.$alias.')')
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->_totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->_current = null;
        $this->_currentElement = $this->internalIterator->next()[0];

        return $this->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->internalIterator->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->internalIterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->_current = null;
        $this->internalIterator->rewind();
        $this->_currentElement = $this->internalIterator->current()[0];
    }
}
