<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\Exception;

/**
 * Exception thrown when a result is expected, but no rows are returned by the source.
 */
interface NoResultException extends \Throwable
{
}
