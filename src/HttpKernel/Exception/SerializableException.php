<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel\Exception;

use Kcs\Serializer\Annotation as Serializer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * This class is used as helper to serialize an exception.
 *
 * @internal
 * @Serializer\AccessType("property")
 */
class SerializableException
{
    /**
     * @Serializer\Type("string")
     */
    protected string $errorMessage;

    /**
     * @Serializer\Type("int")
     */
    protected int $errorCode;

    public function __construct(FlattenException $exception)
    {
        $this->errorMessage = Response::$statusTexts[$exception->getStatusCode()] ?? 'Unknown error';
        $this->errorCode = $exception->getCode();
    }

    public function __toString(): string
    {
        return 'An error has occurred: '.$this->errorMessage;
    }
}
