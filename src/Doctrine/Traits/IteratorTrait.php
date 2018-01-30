<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\Traits;

use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;

trait IteratorTrait
{
    /**
     * A function to be applied to each element
     * while iterating.
     *
     * @var null|callable
     */
    private $_apply;

    /**
     * The current element from the underlying iterator.
     *
     * @var mixed
     */
    private $_currentElement;

    /**
     * The current element, which results by the application
     * of the apply function.
     *
     * @var mixed
     */
    private $_current;

    /**
     * {@inheritdoc}
     */
    public function apply(callable $func = null): ObjectIterator
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
}
