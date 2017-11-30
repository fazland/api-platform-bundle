<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\JSONPointer;

use Symfony\Component\PropertyAccess\Exception\InvalidPropertyPathException;
use Symfony\Component\PropertyAccess\Exception\OutOfBoundsException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\PropertyAccess\PropertyPathIterator;

class Path implements \IteratorAggregate, PropertyPathInterface
{
    /**
     * @var string[]
     */
    private $parts;

    /**
     * @var int
     */
    private $length;

    public function __construct(string $path)
    {
        $this->decode($path);
    }

    /**
     * Gets the cleaned up path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return '/'.implode('/', array_map([$this, 'escape'], $this->parts));
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Iterator
    {
        return new PropertyPathIterator($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        if ($this->length <= 1) {
            return null;
        }

        $parent = clone $this;
        --$parent->length;
        array_pop($parent->parts);

        return $parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getElements()
    {
        return $this->parts;
    }

    /**
     * {@inheritdoc}
     */
    public function getElement($index)
    {
        if (! isset($this->parts[$index])) {
            throw new OutOfBoundsException(sprintf('The index %s is not within the property path', $index));
        }

        return $this->parts[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function isProperty($index)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isIndex($index)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getPath();
    }

    /**
     * Decodes and cleans up the path.
     *
     * @param string $path
     */
    private function decode(string $path): void
    {
        if ('#' === substr($path, 0, 1)) {
            $path = urldecode(substr($path, 1));
        }

        if (! empty($path) && '/' !== $path[0]) {
            throw new InvalidPropertyPathException('Invalid path syntax');
        }

        $this->parts = array_map([$this, 'unescape'], explode('/', substr($path, 1)));
        $this->length = count($this->parts);
    }

    private function unescape(string $token): string
    {
        if (preg_match('/~[^01]/', $token)) {
            throw new InvalidPropertyPathException('Invalid path syntax');
        }

        $token = str_replace('~1', '/', $token);
        $token = str_replace('~0', '~', $token);

        return $token;
    }

    private function escape(string $token): string
    {
        $token = str_replace('~', '~0', $token);
        $token = str_replace('/', '~1', $token);

        return $token;
    }
}
