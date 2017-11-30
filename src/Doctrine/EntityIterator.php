<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine;

use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\QueryBuilder;

/**
 * This class allows iterating a query iterator for a single entity query.
 */
class EntityIterator implements \Iterator, \Countable
{
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
     * Registers a callable to apply to each element of the iterator.
     *
     * @param callable $func
     *
     * @return EntityIterator
     */
    public function apply(callable $func = null): self
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
