<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\Mongo;

use Doctrine\MongoDB\Iterator;
use Doctrine\ODM\MongoDB\Query\Builder;
use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;

/**
 * This class allows iterating a query iterator for a single entity query.
 */
class DocumentIterator implements ObjectIterator
{
    /**
     * @var Iterator
     */
    private $internalIterator;

    /**
     * @var Builder
     */
    private $queryBuilder;

    /**
     * @var null|int
     */
    private $_totalCount;

    /**
     * @var null|callable
     */
    private $_apply;

    /**
     * @var mixed
     */
    private $_currentElement;

    /**
     * @var mixed
     */
    private $_current;

    public function __construct(Builder $queryBuilder)
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->internalIterator = $this->queryBuilder->getQuery()->getIterator();
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
            $queryBuilder->count();

            $this->_totalCount = (int) $queryBuilder->getQuery()->execute();
        }

        return $this->_totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(callable $func = null): ObjectIterator
    {
        if (null === $func) {
            $func = function ($val) {
                return $val;
            };
        }

        $this->_current = null;
        $this->_apply = $func;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (! $this->valid()) {
            return null;
        }

        if (null === $this->_current) {
            $this->_current = call_user_func($this->_apply, $this->_currentElement);
        }

        return $this->_current;
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
        $this->_currentElement = $this->internalIterator->current();
    }
}