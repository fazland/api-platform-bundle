<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\ErrorRenderer;

use Fazland\ApiPlatformBundle\HttpKernel\Exception\DebugSerializableException;
use Fazland\ApiPlatformBundle\HttpKernel\Exception\SerializableException;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\RequestStack;

class SerializerErrorRenderer implements ErrorRendererInterface
{
    private ErrorRendererInterface $fallbackErrorRenderer;
    private RequestStack $requestStack;
    private SerializerInterface $serializer;
    private SerializationContext $serializationContext;
    private string $exceptionClass;

    public function __construct(ErrorRendererInterface $fallbackErrorRenderer, RequestStack $requestStack, SerializerInterface $serializer, SerializationContext $serializationContext, bool $debug = false)
    {
        $this->fallbackErrorRenderer = $fallbackErrorRenderer;
        $this->requestStack = $requestStack;
        $this->serializer = $serializer;
        $this->serializationContext = $serializationContext;
        $this->exceptionClass = $debug ? DebugSerializableException::class : SerializableException::class;
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Throwable $exception): FlattenException
    {
        $request = $this->requestStack->getMasterRequest();
        if (null === $request) {
            return $this->fallbackErrorRenderer->render($exception);
        }

        $flatten = FlattenException::createFromThrowable($exception);

        $format = $request->getRequestFormat();
        $ex = new $this->exceptionClass($flatten);

        try {
            $data = $this->serializer->serialize($ex, $format, clone $this->serializationContext);
        } catch (UnsupportedFormatException $e) {
            return $this->fallbackErrorRenderer->render($exception);
        }

        $flatten->setAsString($data);
        $flatten->setHeaders([
            'Content-Type' => $request->getMimeType($format) ?: 'text/html',
            'Vary' => 'Accept',
        ] + $flatten->getHeaders());

        return $flatten;
    }
}
