<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination\Doctrine\ORM;

use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateTimeTzType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\Pagination\Orderings;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator as BaseIterator;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Fazland\DoctrineExtra\ORM\IteratorTrait;

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
        $alias = $queryBuilder->getRootAliases()[0];

        foreach ($this->orderBy as $key => [$field, $direction]) {
            $method = 0 === $key ? 'orderBy' : 'addOrderBy';
            $queryBuilder->{$method}($alias.'.'.$field, \strtoupper($direction));
        }

        $limit = $this->pageSize;
        if (null !== $this->token) {
            $timestamp = $this->token->getOrderValue();
            $limit += $this->token->getOffset();
            $mainOrder = $this->orderBy[0];

            $type = $queryBuilder->getEntityManager()
                ->getClassMetadata($queryBuilder->getRootEntities()[0])
                ->getTypeOfField($mainOrder[0]);

            if (\is_string($type)) {
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
