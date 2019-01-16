<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\Mongo\Exception;

use Doctrine\ODM\MongoDB\MongoDBException;
use Fazland\ApiPlatformBundle\Doctrine\Exception\NonUniqueResultException as NonUniqueResultExceptionInterface;

class NonUniqueResultException extends MongoDBException implements NonUniqueResultExceptionInterface
{
    private const DEFAULT_MESSAGE = 'More than one result was found for query although one document or none was expected.';

    /**
     * @param string|null $message
     */
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? self::DEFAULT_MESSAGE);
    }
}
