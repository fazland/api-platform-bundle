<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination\Doctrine\Elastica;

use Elastica\Query;
use Fazland\ApiPlatformBundle\Pagination\Orderings;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator as BaseIterator;
use Fazland\DoctrineExtra\IteratorTrait;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Fazland\ODM\Elastica\Search\Search;
use Fazland\ODM\Elastica\Type\AbstractDateTimeType;

final class PagerIterator extends BaseIterator implements ObjectIteratorInterface
{
    use IteratorTrait;

    private Search $search;

    private ?int $totalCount;

    public function __construct(Search $search, $orderBy)
    {
        $this->search = clone $search;
        $this->totalCount = null;

        $this->apply(null);

        parent::__construct([], $orderBy);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        if (null === $this->totalCount) {
            $this->totalCount = $this->search->count();
        }

        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        parent::next();

        $this->current = null;
        $this->currentElement = parent::current();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        parent::rewind();

        $this->current = null;
        $this->currentElement = parent::current();
    }

    /**
     * {@inheritdoc}
     */
    protected function getObjects(): array
    {
        $search = clone $this->search;

        $sort = [];
        foreach ($this->orderBy as [$field, $direction]) {
            $sort[$field] = $direction;
        }

        $query = new Query\BoolQuery();
        $searchQuery = $search->getQuery();
        if ($searchQuery->hasParam('query')) {
            $query->addFilter($searchQuery->getQuery());
        }

        $limit = $this->pageSize;
        if (null !== $this->token) {
            $timestamp = $this->token->getOrderValue();
            $limit += $this->token->getOffset();
            $mainOrder = $this->orderBy[0];

            $documentManager = $this->search->getDocumentManager();

            $type = $documentManager->getTypeManager()
                ->getType($documentManager->getClassMetadata($this->search->getDocumentClass())->getTypeOfField($mainOrder[0]))
            ;

            if ($type instanceof AbstractDateTimeType) {
                $datetime = \DateTimeImmutable::createFromFormat('U', (string) $timestamp);
                $timestamp = $datetime->format(\DateTime::ISO8601);
            }

            $direction = Orderings::SORT_ASC === $mainOrder[1] ? 'gte' : 'lte';

            $query->addFilter(new Query\Range($mainOrder[0], [
                $direction => $timestamp,
            ]));
        }

        $search
            ->setQuery($query)
            ->setSort($sort)
            ->setLimit($limit)
        ;

        return $search->execute();
    }
}
