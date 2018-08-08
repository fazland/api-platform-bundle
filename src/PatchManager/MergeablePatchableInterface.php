<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager;

/**
 * Represents an object that can be merge patched.
 */
interface MergeablePatchableInterface extends PatchableInterface
{
    /**
     * Get type for the current object.
     *
     * @return string
     */
    public function getTypeClass(): string;
}
