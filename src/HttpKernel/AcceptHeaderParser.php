<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\HttpKernel;

use Carbon\Carbon;
use Kcs\ApiPlatformBundle\Negotiation\VersionAwareNegotiator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class AcceptHeaderParser implements EventSubscriberInterface
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $negotiator = new VersionAwareNegotiator();
        $header = $negotiator->getBest($request->headers->get('Accept', 'application/json'), [
            'application/json', 'application/x-json',
            'text/xml', 'application/xml', 'application/x-xml',
        ]);

        if (null === $header) {
            throw new NotAcceptableHttpException();
        }

        $version = str_replace('-', '', null !== $header->getVersion() ? $header->getVersion() : Carbon::now()->format('Ymd'));
        if (! preg_match('/\d{8}/', $version)) {
            throw new NotAcceptableHttpException();
        }

        $request->attributes->set('_format', $request->getFormat($header->getType()));
        $request->attributes->set('_version', $version);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 40],
        ];
    }
}
