<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\HttpKernel\Exception;

use Kcs\Serializer\Annotation as Serializer;
use Symfony\Component\Debug\Exception\FlattenException;

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
     * @var array
     *
     * @Serializer\Type("array")
     */
    private $exception;

    public function __construct(FlattenException $exception)
    {
        $this->exception = $exception->toArray();

        parent::__construct($exception);
    }
}
