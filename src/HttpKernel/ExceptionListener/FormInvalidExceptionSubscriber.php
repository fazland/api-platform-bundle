<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel\ExceptionListener;

use Fazland\ApiPlatformBundle\Annotation\View;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class FormInvalidExceptionSubscriber implements EventSubscriberInterface
{
    public function onException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        if (! $exception instanceof FormInvalidException) {
            return;
        }

        $request = $this->duplicateRequest($exception->getForm(), $event->getRequest());
        $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);

        $event->setResponse($response);
    }

    /**
     * Clones the request for the exception.
     *
     * @param FormInterface $form
     * @param Request       $request The original request
     *
     * @return Request $request The cloned request
     */
    protected function duplicateRequest(FormInterface $form, Request $request)
    {
        $attributes = [
            '_controller' => [$this, 'formAction'],
            'form' => $form,
        ];

        $request = $request->duplicate(null, null, $attributes);
        $request->setMethod(Request::METHOD_GET);

        return $request;
    }

    /**
     * This is public to be callable. DO NOT USE IT!
     * This method should be considered as private.
     *
     * @param FormInterface $form
     *
     * @return FormInterface
     *
     * @View()
     *
     * @internal
     */
    public function formAction(FormInterface $form)
    {
        return $form;
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
