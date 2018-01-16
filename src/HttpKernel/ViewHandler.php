<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel;

use Fazland\ApiPlatformBundle\Annotation\View;
use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;
use Fazland\ApiPlatformBundle\HttpKernel\View\Context;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Serializer;
use Kcs\Serializer\Type\Type;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ViewHandler implements EventSubscriberInterface
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var SerializationContext
     */
    private $serializationContext;

    /**
     * @var TokenStorageInterface|null
     */
    private $tokenStorage;

    public function __construct(Serializer $serializer, SerializationContext $serializationContext, ?TokenStorageInterface $tokenStorage)
    {
        $this->serializer = $serializer;
        $this->serializationContext = $serializationContext;
        $this->tokenStorage = $tokenStorage;
    }

    public function onView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $result = $event->getControllerResult();
        if ($result instanceof Response) {
            return;
        }

        $annotation = $request->attributes->get('_rest_view');
        if (! $annotation instanceof View) {
            return;
        }

        $headers = [
            'Content-Type' => $request->getMimeType($request->attributes->get('_format')),
        ];

        if ($result instanceof ObjectIterator) {
            $headers['X-Total-Count'] = $result->count();
        }

        if ($result instanceof \Iterator) {
            $result = iterator_to_array($result);
        }

        try {
            $content = $this->handle($result, $request, $annotation = clone $annotation);
            $response = new Response($content, $annotation->statusCode, $headers);
        } catch (UnsupportedFormatException $e) {
            $response = new Response(null, Response::HTTP_NOT_ACCEPTABLE);
        }

        $event->setResponse($response);
    }

    private function handle($result, Request $request, View $view)
    {
        $format = $request->attributes->get('_format');
        $context = clone $this->serializationContext;

        if ($result instanceof Form) {
            if (! $result->isSubmitted()) {
                $result->submit(null);
            }

            if (! $result->isValid()) {
                $view->statusCode = Response::HTTP_BAD_REQUEST;

                return $this->serializer->serialize($result, $format, $context);
            }
        }

        if ($method = $view->groupsProvider) {
            $viewContext = Context::create($request, $this->tokenStorage);
            $context->setGroups($result->$method($viewContext));
        } elseif ($groups = $view->groups) {
            $context->setGroups($groups);
        }

        $type = $view->serializationType === null ? null : Type::parse($view->serializationType);
        return $this->serializer->serialize($result, $format, $context, $type);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => 'onView',
        ];
    }
}
