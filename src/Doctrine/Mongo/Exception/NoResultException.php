<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Doctrine\Mongo\Exception;

use Doctrine\ODM\MongoDB\MongoDBException;
use Fazland\ApiPlatformBundle\Doctrine\Exception\NoResultException as NoResultExceptionInterface;

class NoResultException extends MongoDBException implements NoResultExceptionInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('No result was found for query although at least one document was expected.');
    }
}
