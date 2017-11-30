<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\PatchManager;

use Fazland\ApiPlatformBundle\Exception\TypeError;
use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Symfony\Component\HttpFoundation\Request;

interface PatchManagerInterface
{
    /**
     * Executes the PATCH operations.
     *
     * @param PatchableInterface $patchable
     * @param Request            $request
     *
     * @throws TypeError|InvalidJSONException
     */
    public function patch($patchable, Request $request): void;
}
