<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\HttpKernel;

use Kcs\ApiPlatformBundle\Decoder\DecoderProviderInterface;
use Kcs\ApiPlatformBundle\Decoder\Exception\UnsupportedFormatException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BodyConverter implements EventSubscriberInterface
{
    /**
     * @var DecoderProviderInterface
     */
    private $decoderProvider;

    public function __construct(DecoderProviderInterface $decoderProvider)
    {
        $this->decoderProvider = $decoderProvider;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $contentType = $request->headers->get('Content-Type', 'application/x-www-form-urlencoded');

        if (in_array($request->getMethod(), [Request::METHOD_GET, Request::METHOD_HEAD])) {
            return;
        }

        $format = $this->getFormat($request, $contentType);
        if (null === $format || 'form' === $format) {
            return;
        }

        try {
            $decoder = $this->decoderProvider->get($format);
        } catch (UnsupportedFormatException $ex) {
            $event->setResponse(new Response("Cannot decode $contentType", Response::HTTP_UNSUPPORTED_MEDIA_TYPE));

            return;
        }

        $parameters = $decoder->decode($request->getContent());
        $request->request = new ParameterBag($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 35],
        ];
    }

    private function getFormat(Request $request, string $contentType): ? string
    {
        $format = $request->getFormat($contentType);

        if (null === $format) {
            switch ($contentType) {
                case 'application/merge-patch+json':
                    return 'json';
            }
        }

        return $format;
    }
}
