<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel\Exception;

use Kcs\Serializer\Annotation as Serializer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

/**
 * Used as helper to serialize exceptions when debug is enabled
 * Exposes stack traces.
 *
 * @internal
 * @Serializer\AccessType("property")
 */
class DebugSerializableException extends SerializableException
{
    /**
     * @Serializer\Type("array")
     */
    private array $exception;

    public function __construct(FlattenException $exception)
    {
        parent::__construct($exception);

        $this->exception = $exception->toArray();
        $this->errorMessage = $exception->getMessage();
    }
}
