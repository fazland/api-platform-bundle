<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\PatchManager\Operation;

use Kcs\ApiPlatformBundle\JSONPointer\Path;
use Kcs\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class ReplaceOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    public function execute(&$subject, $operation): void
    {
        $path = new Path($operation->path);
        $value = null;

        try {
            $value = $this->accessor->getValue($subject, $path);
        } catch (NoSuchPropertyException $e) {
            // Do nothing.
        }

        if (null === $value) {
            throw new InvalidJSONException('Element at path "'.(string) $path.'" does not exist.');
        }

        $this->accessor->setValue($subject, $operation->path, $operation->value);
    }
}
