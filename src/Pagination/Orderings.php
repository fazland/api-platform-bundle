<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Pagination;

/**
 * Represents the orderings set for pager.
 */
final class Orderings implements \Countable, \IteratorAggregate, \ArrayAccess
{
    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';

    /**
     * @var array
     */
    private $orderings;

    /**
     * Constructor.
     * $orderings accepts the following formats:
     * - field name as value: means the field should be ascending ordered
     * - field name as key, asc or desc as value
     * - array containing field name and the direction.
     *
     * @param array $orderings
     */
    public function __construct(array $orderings)
    {
        $this->orderings = self::normalize($orderings);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): iterable
    {
        yield from $this->orderings;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->orderings);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->orderings[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): array
    {
        return $this->orderings[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        // Do nothing.
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        // Do nothing.
    }

    /**
     * Normalizes the orderings array.
     *
     * @param array $orderings
     *
     * @return array
     */
    private static function normalize(array $orderings): array
    {
        $normalized = [];
        foreach ($orderings as $field => $direction) {
            if (is_int($field)) {
                if (is_array($direction)) {
                    $normalized[] = [$direction[0], self::normalizeDirection($direction[1])];
                } else {
                    $normalized[] = [$direction, self::SORT_ASC];
                }
            } else {
                $normalized[] = [$field, self::normalizeDirection($direction)];
            }
        }

        return $normalized;
    }

    /**
     * Normalizes orderBy direction.
     *
     * @param string $direction
     *
     * @return string
     */
    private static function normalizeDirection(string $direction): string
    {
        if (! preg_match('/'.self::SORT_ASC.'|'.self::SORT_DESC.'/i', $direction)) {
            throw new Exception\InvalidArgumentException('Invalid ordering direction "'.$direction.'"');
        }

        return strtolower($direction);
    }
}
