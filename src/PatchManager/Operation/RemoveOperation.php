<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\PatchManager\Operation;

use Kcs\ApiPlatformBundle\JSONPointer\Path;
use Kcs\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;

class RemoveOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    public function execute(&$subject, $operation): void
    {
        $path = new Path($operation->path);
        $element = $path->getElement($path->getLength() - 1);

        if ($path->getLength() > 1) {
            $value = $this->accessor->getValue($subject, $path->getParent());
        } else {
            $value = &$subject;
        }

        if (null === $value) {
            return;
        } elseif (is_array($value) || $value instanceof \ArrayAccess) {
            unset($value[$element]);
        } elseif (is_iterable($value)) {
            $value = iterator_to_array($value);
            unset($value[$element]);
        } elseif ($this->accessor->isWritable($subject, $path)) {
            $value = null;
        } else {
            throw new InvalidJSONException('Cannot remove "'.$element.'": path does not represents a collection.');
        }

        if ($path->getLength() > 1) {
            $this->accessor->setValue($subject, $path->getParent(), $value);
        }
    }
}
