<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel\ExceptionListener;

use Fazland\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FormNotSubmittedExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if (! $exception instanceof FormNotSubmittedException) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => 'No data sent.',
            'name' => $exception->getForm()->getName(),
        ], Response::HTTP_BAD_REQUEST));
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
