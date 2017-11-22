<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Doctrine;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;

class EntityRepository extends BaseRepository
{
    public function all(): \Iterator
    {
        return new EntityIterator($this->createQueryBuilder('a'));
    }

    public function count(): int
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a)')
        ;

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findOneByCached(array $criteria, array $orderBy = null, $ttl = 28800)
    {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        $query->setMaxResults(1);
        $query->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        return $query->getOneOrNullResult();
    }

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

    public function get($id, $lockMode = null, $lockVersion = null)
    {
        $entity = $this->find($id, $lockMode, $lockVersion);

        if (null === $entity) {
            throw new NoResultException();
        }

        return $entity;
    }

    public function getOneBy(array $criteria, array $orderBy = null)
    {
        $entity = $this->findOneBy($criteria, $orderBy);

        if (null === $entity) {
            throw new NoResultException();
        }

        return $entity;
    }

    public function getOneByCached(array $criteria, array $orderBy = null, $ttl = 28800)
    {
        $query = $this->buildQueryForFind($criteria, $orderBy);
        $query->setMaxResults(1);
        $query->useResultCache(true, $ttl, '__'.get_called_class().'::'.__FUNCTION__.sha1(serialize(func_get_args())));

        return $query->getSingleResult();
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

        return $qb->getQuery();
    }
}
