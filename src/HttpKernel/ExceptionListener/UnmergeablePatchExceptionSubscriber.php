<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel\ExceptionListener;

use Fazland\ApiPlatformBundle\PatchManager\Exception\UnmergeablePatchException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class UnmergeablePatchExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();
        if (! $exception instanceof UnmergeablePatchException) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => $exception->getMessage() ?: 'Unmergeable resource.',
        ], Response::HTTP_NOT_ACCEPTABLE));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }
}
