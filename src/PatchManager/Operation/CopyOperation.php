<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager\Operation;

use Fazland\ApiPlatformBundle\JSONPointer\Path;
use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class CopyOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    public function execute(&$subject, $operation): void
    {
        try {
            $value = $this->accessor->getValue($subject, $operation->from);
        } catch (NoSuchPropertyException $e) {
            throw new InvalidJSONException('Element at path "'.$operation->from.'" does not exist');
        }

        $this->accessor->setValue($subject, $operation->path, $value);
    }
}
