<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel;

use Fazland\ApiPlatformBundle\Annotation\Sunset;
use Fazland\ApiPlatformBundle\Annotation\View as ViewAnnotation;
use Fazland\ApiPlatformBundle\HttpKernel\View\Context;
use Fazland\ApiPlatformBundle\HttpKernel\View\View;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\Type\Type;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Sunset header field if Sunset annotation is found.
 *
 * @see https://tools.ietf.org/html/draft-wilde-sunset-header-10
 */
class SunsetHandler implements EventSubscriberInterface
{
    /**
     * Modify the response adding a Sunset header if needed.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event): void
    {
        $request = $event->getRequest();

        $annotation = $request->attributes->get('_rest_sunset');
        if (! $annotation instanceof Sunset) {
            return;
        }

        $date = new \DateTimeImmutable($annotation->date);

        $response = $event->getResponse();
        $response->headers->set('Sunset', $date->format(\DateTime::RFC2822));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }
}