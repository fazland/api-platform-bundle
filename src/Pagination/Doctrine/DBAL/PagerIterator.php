<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination\Doctrine\DBAL;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Fazland\ApiPlatformBundle\Pagination\Orderings;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator as BaseIterator;
use Fazland\DoctrineExtra\DBAL\IteratorTrait;
use Fazland\DoctrineExtra\ObjectIteratorInterface;

final class PagerIterator extends BaseIterator implements ObjectIteratorInterface
{
    use IteratorTrait;

    public function __construct(QueryBuilder $queryBuilder, $orderBy)
    {
        $this->queryBuilder = clone $queryBuilder;
        $this->totalCount = null;

        $this->apply(null);

        parent::__construct([], $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if (! $this->valid()) {
            return null;
        }

        if (null === $this->current) {
            $this->current = \call_user_func($this->callable, $this->currentElement);
        }

        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        parent::next();

        $this->current = null;
        $current = parent::current();
        $this->currentElement = \is_object($current) ? self::toArray($current) : $current;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        parent::rewind();

        $this->current = null;
        $current = parent::current();
        $this->currentElement = \is_object($current) ? self::toArray($current) : $current;
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjects(): array
    {
        $queryBuilder = clone $this->queryBuilder;

        $offset = $queryBuilder->getFirstResult();
        $queryBuilder->setFirstResult(null);

        $queryBuilder = $this->queryBuilder->getConnection()
            ->createQueryBuilder()
            ->select('*')
            ->from('('.$queryBuilder->getSQL().')', 'x')
            ->setFirstResult($offset)
        ;

        foreach ($this->orderBy as $key => [$field, $direction]) {
            $method = 0 === $key ? 'orderBy' : 'addOrderBy';
            $queryBuilder->{$method}($field, \strtoupper($direction));
        }

        $limit = $this->pageSize;
        if (null !== $this->token) {
            $timestamp = $this->token->getOrderValue();
            $limit += $this->token->getOffset();
            $mainOrder = $this->orderBy[0];

            $direction = Orderings::SORT_ASC === $mainOrder[1] ? '>=' : '<=';
            $queryBuilder->andWhere($mainOrder[0].' '.$direction.' :timeLimit');
            $queryBuilder->setParameter('timeLimit', $timestamp);
        }

        $queryBuilder->setMaxResults($limit);

        return $queryBuilder->execute()->fetchAll(FetchMode::STANDARD_OBJECT);
    }

    private static function toArray(\stdClass $rowObject): array
    {
        return \json_decode(\json_encode($rowObject, JSON_THROW_ON_ERROR, 512), true, 512, JSON_THROW_ON_ERROR);
    }
}
