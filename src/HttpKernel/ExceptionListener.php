<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\HttpKernel;

use Psr\Log\LoggerInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $controller;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(string $controller, LoggerInterface $logger = null)
    {
        $this->controller = $controller;
        $this->logger = $logger;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $request = $event->getRequest();

        $this->logException($exception, sprintf('Uncaught PHP Exception %s: "%s" at %s line %s', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine()));

        $request = $this->duplicateRequest($exception, $request);

        try {
            $response = $event->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST, false);
        } catch (\Exception $e) {
            $this->logException($e, sprintf('Exception thrown when handling an exception (%s: %s at %s line %s)', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

            $wrapper = $e;

            while ($prev = $wrapper->getPrevious()) {
                if ($exception === $wrapper = $prev) {
                    throw $e;
                }
            }

            $prev = new \ReflectionProperty('Exception', 'previous');
            $prev->setAccessible(true);
            $prev->setValue($wrapper, $exception);

            throw $e;
        }

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -255],
        ];
    }

    /**
     * Logs an exception.
     *
     * @param \Exception $exception The \Exception instance
     * @param string     $message   The error message to log
     */
    protected function logException(\Exception $exception, $message)
    {
        if (null !== $this->logger) {
            $method = 'error';

            if ($exception instanceof HttpExceptionInterface) {
                $statusCode = $exception->getStatusCode();

                switch (true) {
                    case $statusCode < 400:
                        $method = 'notice';
                        break;

                    case $statusCode >= 400 && $statusCode < 500:
                        $method = 'warning';
                        break;

                    default:
                        $method = 'critical';
                        break;
                }
            }

            $this->logger->{$method}($message, ['exception' => $exception]);
        }
    }

    /**
     * Clones the request for the exception.
     *
     * @param \Exception $exception The thrown exception
     * @param Request    $request   The original request
     *
     * @return Request $request The cloned request
     */
    protected function duplicateRequest(\Exception $exception, Request $request)
    {
        $attributes = [
            '_controller' => $this->controller,
            'exception' => FlattenException::create($exception),
        ];

        $request = $request->duplicate(null, null, $attributes);
        $request->setMethod(Request::METHOD_GET);

        return $request;
    }
}
