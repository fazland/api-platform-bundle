<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\ORM;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;
use Fazland\ApiPlatformBundle\Doctrine\ObjectRepository;

class EntityRepository extends BaseRepository implements ObjectRepository
{
    /**
     * {@inheritdoc}
     */
    public function all(): ObjectIterator
    {
        return new EntityIterator($this->createQueryBuilder('a'));
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = []): int
    {
        return (int) $this->buildQueryBuilderForCriteria($criteria)
            ->select('COUNT(a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneByCached(array $criteria, array $orderBy = null, $ttl = 28800)
    {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        $query->setMaxResults(1);
        $query->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        try {
            return $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new Exception\NonUniqueResultException($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByCached(array $criteria, array $orderBy = null, $limit = null, $offset = null, $ttl = 28800)
    {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        if (null !== $limit) {
            $query->setMaxResults($limit);
        }

        if (null !== $offset) {
            $query->setFirstResult($offset);
        }

        $query->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        return $query->getResult();
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
        $entity = $this->findOneBy($criteria, $orderBy);

        if (null === $entity) {
            throw new Exception\NoResultException();
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getOneByCached(array $criteria, array $orderBy = null, $ttl = 28800)
    {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        $query->setMaxResults(1);
        $query->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        try {
            return $query->getSingleResult();
        } catch (NonUniqueResultException $e) {
            throw new Exception\NonUniqueResultException($e->getMessage());
        } catch (NoResultException $e) {
            throw new Exception\NoResultException();
        }
    }

    /**
     * Builds a query for find method.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return Query
     */
    private function buildQueryForFind(array $criteria, array $orderBy = null): Query
    {
        return $this->buildQueryBuilderForCriteria($criteria, $orderBy)->getQuery();
    }

    /**
     * Builds a query builder for find operations.
     *
     * @param array      $criteria
     * @param array|null $orderBy
     *
     * @return QueryBuilder
     */
    private function buildQueryBuilderForCriteria(array $criteria, array $orderBy = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');
        $and = $qb->expr()->andX();
        foreach ($criteria as $key => $value) {
            $condition = is_array($value) ?
                $qb->expr()->in("a.$key", ":$key") :
                $qb->expr()->eq("a.$key", ":$key");
            $and->add($condition);

            $qb->setParameter($key, $value);
        }

        if ($and->count() > 0) {
            $qb->where($and);
        }

        if (null !== $orderBy) {
            foreach ($orderBy as $fieldName => $orientation) {
                $qb->addOrderBy("a.$fieldName", $orientation);
            }
        }

        return $qb;
    }
}
