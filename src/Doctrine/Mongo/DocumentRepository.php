<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\Mongo;

use Doctrine\ODM\MongoDB\DocumentRepository as BaseRepository;
use Doctrine\ODM\MongoDB\Query\Builder;
use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;
use Fazland\ApiPlatformBundle\Doctrine\ObjectRepository;

class DocumentRepository extends BaseRepository implements ObjectRepository
{
    /**
     * {@inheritdoc}
     */
    public function all(): ObjectIterator
    {
        return new DocumentIterator($this->createQueryBuilder());
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        return (int) $this->buildQueryBuilderForCriteria($criteria)
            ->count()
            ->getQuery()
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByCached(array $criteria, array $orderBy = null, $ttl = 28800)
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->limit(1);
//        $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        return $query->getQuery()->getSingleResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findByCached(array $criteria, array $orderBy = null, $limit = null, $offset = null, $ttl = 28800)
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
//        $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        return iterator_to_array($query->getQuery()->getIterator());
    }

    /**
     * {@inheritdoc}
     */
    public function get($id, $lockMode = null, $lockVersion = null)
    {
        $entity = $this->find($id, $lockMode, $lockVersion);

        if (null === $entity) {
            throw new Exception\NoResultException();
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getOneBy(array $criteria, array $orderBy = null)
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->limit(1);

        $object = $query->getQuery()->getSingleResult();

        if (null === $object) {
            throw new Exception\NoResultException();
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function getOneByCached(array $criteria, array $orderBy = null, $ttl = 28800)
    {
        $query = $this->buildQueryBuilderForCriteria($criteria, $orderBy);
        $query->limit(1);
//        $query->getQuery()->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        $object = $query->getQuery()->getSingleResult();

        if (null === $object) {
            throw new Exception\NoResultException();
        }

        return $object;
    }
    /**
     * Builds a query builder for find operations.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return Builder
     */
    private function buildQueryBuilderForCriteria(array $criteria, array $orderBy = null): Builder
    {
        $qb = $this->createQueryBuilder();

        foreach ($criteria as $key => $value) {
            $method = is_array($value) ? 'in' : 'equals';
            $qb->field($key)->{$method}($value);
        }

        if (null !== $orderBy) {
            $qb->sort($orderBy);
        }

        return $qb;
    }
}
