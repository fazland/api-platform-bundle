<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\Mongo;

use Doctrine\MongoDB\Iterator;
use Doctrine\ODM\MongoDB\Query\Builder;
use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;
use Fazland\ApiPlatformBundle\Doctrine\Traits\IteratorTrait;

/**
 * This class allows iterating a query iterator for a single entity query.
 */
class DocumentIterator implements ObjectIterator
{
    use IteratorTrait;

    /**
     * @var Iterator
     */
    private $internalIterator;

    /**
     * @var Builder
     */
    private $queryBuilder;

    /**
     * @var int|null
     */
    private $_totalCount;

    public function __construct(Builder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->internalIterator = $this->queryBuilder->getQuery()->getIterator();

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
            $queryBuilder->count();

            $this->_totalCount = (int) $queryBuilder->getQuery()->execute();
        }

        return $this->_totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->internalIterator->next();

        $this->_current = null;
        $this->_currentElement = $this->internalIterator->current();

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
    public function valid(): bool
    {
        return $this->internalIterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->_current = null;
        $this->internalIterator->rewind();
        $this->_currentElement = $this->internalIterator->current();
    }
}
