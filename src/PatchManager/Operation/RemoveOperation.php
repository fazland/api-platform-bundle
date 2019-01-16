<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager\Operation;

use Fazland\ApiPlatformBundle\JSONPointer\Path;
use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;

class RemoveOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    public function execute(&$subject, $operation): void
    {
        $path = new Path($operation->path);
        $element = $path->getElement($path->getLength() - 1);

        $pathLength = $path->getLength();
        if ($pathLength > 1) {
            $value = $this->accessor->getValue($subject, $path->getParent());
        } else {
            $value = &$subject;
        }

        if (null === $value) {
            return;
        }

        if (\is_array($value) || $value instanceof \ArrayAccess) {
            unset($value[$element]);
        } elseif (\is_iterable($value)) {
            $value = \iterator_to_array($value);
            unset($value[$element]);
        } elseif ($this->accessor->isWritable($subject, $path)) {
            $this->accessor->setValue($subject, $path, null);

            return;
        } else {
            throw new InvalidJSONException('Cannot remove "'.$element.'": path does not represents a collection.');
        }

        if ($pathLength > 1) {
            $this->accessor->setValue($subject, $path->getParent(), $value);
        }
    }
}
