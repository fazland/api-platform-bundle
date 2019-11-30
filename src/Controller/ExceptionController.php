<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Controller;

use Fazland\ApiPlatformBundle\HttpKernel\Exception\DebugSerializableException;
use Fazland\ApiPlatformBundle\HttpKernel\Exception\SerializableException;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExceptionController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var SerializationContext
     */
    private $serializationContext;

    /**
     * @var bool
     */
    private $exceptionClass;

    public function __construct(SerializerInterface $serializer, SerializationContext $serializationContext, bool $debug = false)
    {
        $this->serializer = $serializer;
        $this->serializationContext = $serializationContext;
        $this->exceptionClass = $debug ? DebugSerializableException::class : SerializableException::class;
    }

    /**
     * Converts an Exception to a Response.
     *
     * A "showException" request parameter can be used to force display of an error page (when set to false) or
     * the exception page (when true). If it is not present, the "debug" value passed into the constructor will
     * be used.
     *
     * @param Request          $request
     * @param FlattenException $exception
     *
     * @return Response
     */
    public function __invoke(Request $request, FlattenException $exception): Response
    {
        Response::closeOutputBuffers($request->headers->get('X-Php-Ob-Level', '-1') + 1, true);
        $code = $exception->getStatusCode();
        $format = $request->getRequestFormat();

        $ex = new $this->exceptionClass($exception);

        try {
            $data = $this->serializer->serialize($ex, $format, clone $this->serializationContext);
        } catch (UnsupportedFormatException $e) {
            return new Response((string) $ex, $code, ['Content-Type' => 'plain/text']);
        }

        return new Response($data, $code, ['Content-Type' => $request->getMimeType($format) ?: 'text/html']);
    }
}
