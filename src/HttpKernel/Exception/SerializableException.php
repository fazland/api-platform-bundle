<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel\Exception;

use Kcs\Serializer\Annotation as Serializer;
use Symfony\Component\Debug\Exception\FlattenException;

/**
 * This class is used as helper to serialize an exception.
 *
 * @internal
 * @Serializer\AccessType("property")
 */
class SerializableException
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $errorMessage;

    /**
     * @var int
     *
     * @Serializer\Type("int")
     */
    private $errorCode;

    public function __construct(FlattenException $exception)
    {
        $this->errorMessage = $exception->getMessage();
        $this->errorCode = $exception->getCode();
    }

    public function __toString(): string
    {
        return 'An error has occurred: '.$this->errorMessage;
    }
}
