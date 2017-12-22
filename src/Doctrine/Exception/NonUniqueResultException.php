<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\Exception;

/**
 * Exception thrown when a query unexpectedly returns more than one result.
 */
interface NonUniqueResultException extends \Throwable
{
}
