<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\HttpKernel\ExceptionListener;

use Kcs\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class InvalidJSONExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if (! $exception instanceof InvalidJSONException) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => $exception->getMessage() ?: 'Invalid document.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }
}
