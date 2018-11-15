<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination\Doctrine\ORM;

use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateTimeTzType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;
use Fazland\ApiPlatformBundle\Doctrine\Traits\IteratorTrait;
use Fazland\ApiPlatformBundle\Pagination\Orderings;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator as BaseIterator;

final class PagerIterator extends BaseIterator implements ObjectIterator
{
    use IteratorTrait;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var null|int
     */
    private $_totalCount;

    public function __construct(QueryBuilder $searchable, $orderBy)
    {
        $this->queryBuilder = clone $searchable;
        $this->apply(null);

        parent::__construct([], $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (null === $this->_totalCount) {
            $queryBuilder = clone $this->queryBuilder;
            $alias = $queryBuilder->getRootAliases()[0];

            $this->_totalCount = (int) $queryBuilder->select('COUNT(DISTINCT '.$alias.')')
                ->setFirstResult(0)
                ->setMaxResults(1)
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
        parent::next();

        $this->_current = null;
        $this->_currentElement = parent::current();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
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
        $alias = $queryBuilder->getRootAliases()[0];

        foreach ($this->orderBy as $key => list($field, $direction)) {
            $method = 0 == $key ? 'orderBy' : 'addOrderBy';
            $queryBuilder->{$method}($alias.'.'.$field, strtoupper($direction));
        }

        $limit = $this->pageSize;
        if (null !== $this->token) {
            $timestamp = $this->token->getTimestamp();
            $limit += $this->token->getOffset();
            $mainOrder = $this->orderBy[0];

            $type = $queryBuilder->getEntityManager()
                ->getClassMetadata($queryBuilder->getRootEntities()[0])
                ->getTypeOfField($mainOrder[0]);

            if (is_string($type)) {
                $type = Type::getType($type);
            }

            if ($type instanceof DateTimeType || $type instanceof DateTimeTzType) {
                $timestamp = \DateTimeImmutable::createFromFormat('U', (string) $timestamp);
            }

            $direction = Orderings::SORT_ASC === $mainOrder[1] ? '>=' : '<=';
            $queryBuilder->andWhere($alias.'.'.$mainOrder[0].' '.$direction.' :timeLimit');
            $queryBuilder->setParameter('timeLimit', $timestamp);
        }

        $queryBuilder->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
