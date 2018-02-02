<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel;

use Fazland\ApiPlatformBundle\Annotation\View as ViewAnnotation;
use Fazland\ApiPlatformBundle\HttpKernel\View\Context;
use Fazland\ApiPlatformBundle\HttpKernel\View\View;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Serializer;
use Kcs\Serializer\Type\Type;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

    /**
     * Handles the result of a controller, serializing it when needed.
     *
     * @param GetResponseForControllerResultEvent $event
     */
    public function onView(GetResponseForControllerResultEvent $event): void
    {
        $request = $event->getRequest();
        $result = $event->getControllerResult();
        if ($result instanceof Response) {
            return;
        }

        $annotation = $request->attributes->get('_rest_view');
        if (! $annotation instanceof ViewAnnotation) {
            return;
        }

        if (! $result instanceof View) {
            $view = new View($result, $annotation->statusCode);
            $view->serializationType = null === $annotation->serializationType ? null : Type::parse($annotation->serializationType);

            if ($method = $annotation->groupsProvider) {
                $viewContext = Context::create($request, $this->tokenStorage);
                $view->serializationGroups = $result->$method($viewContext);
            } elseif ($groups = $annotation->groups) {
                $view->serializationGroups = $groups;
            }

            $result = $view;
        }

        $headers = $result->headers;
        $headers['Content-Type'] = $request->getMimeType($request->attributes->get('_format'));

        try {
            $content = $this->handle($result, $request);
            $response = new Response($content, $result->statusCode, $headers);
        } catch (UnsupportedFormatException $e) {
            $response = new Response(null, Response::HTTP_NOT_ACCEPTABLE);
        }

        $event->setResponse($response);
    }

    /**
     * Serializes the view with given serialization groups
     * and given type.
     *
     * @param View    $view
     * @param Request $request
     *
     * @return mixed|string
     */
    private function handle(View $view, Request $request)
    {
        $format = $request->attributes->get('_format');
        $context = clone $this->serializationContext;

        $result = $view->result;
        $context->setGroups($view->serializationGroups);

        return $this->serializer->serialize($result, $format, $context, $view->serializationType);
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
