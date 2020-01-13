<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel;

use Fazland\ApiPlatformBundle\Decoder\DecoderProviderInterface;
use Fazland\ApiPlatformBundle\Decoder\Exception\UnsupportedFormatException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BodyConverter implements EventSubscriberInterface
{
    private DecoderProviderInterface $decoderProvider;

    public function __construct(DecoderProviderInterface $decoderProvider)
    {
        $this->decoderProvider = $decoderProvider;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $contentType = $request->headers->get('Content-Type', 'application/x-www-form-urlencoded');

        if (\in_array($request->getMethod(), [Request::METHOD_GET, Request::METHOD_HEAD], true)) {
            return;
        }

        $format = $this->getFormat($request, $contentType);
        if (null === $format || 'form' === $format) {
            return;
        }

        try {
            $decoder = $this->decoderProvider->get($format);
        } catch (UnsupportedFormatException $ex) {
            return;
        }

        $parameters = $decoder->decode($request->getContent());
        $request->request = new ParameterBag($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 35],
        ];
    }

    private function getFormat(Request $request, string $contentType): ?string
    {
        $format = $request->getFormat($contentType);

        if (null === $format && 'application/merge-patch+json' === $contentType) {
            return 'json';
        }

        return $format;
    }
}
