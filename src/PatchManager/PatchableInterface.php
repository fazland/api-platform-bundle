<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager;

/**
 * Represents an object that can be patched.
 */
interface PatchableInterface
{
    /**
     * Get type for the current object.
     *
     * @return string
     */
    public function getTypeClass(): string;

    /**
     * Commit modifications to the underlying object.
     */
    public function commit(): void;
}
