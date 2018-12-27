<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsListener implements EventSubscriberInterface
{
    /**
     * Allowed origins regex pattern.
     * Null if all origins are allowed.
     *
     * @var string
     */
    private $allowedOrigins;

    /**
     * Allowed exposed headers.
     *
     * @var string
     */
    private $exposedHeaders;

    /**
     * Constructor.
     * An array of domain globs could be passed to disallow forbidden origins.
     *
     * @param array|null $allowedOrigins
     * @param array      $exposedHeaders
     */
    public function __construct(array $allowedOrigins = null, array $exposedHeaders = [])
    {
        if (null !== $allowedOrigins) {
            $allowedOrigins = (function (string ...$origins) {
                return $origins;
            })(...$allowedOrigins);

            $allowedOrigins = '#^\w+:\/\/(?:.+@)?(?:'.\implode('|', \array_map([$this, 'toRegex'], $allowedOrigins)).')$#';
        }

        $this->allowedOrigins = $allowedOrigins;

        $headers = [];
        $exposedHeaders = \array_merge(\array_values($exposedHeaders), ['Authorization', 'Content-Length', 'X-Total-Count', 'X-Continuation-Token']);

        foreach ($exposedHeaders as $header) {
            $headers[\strtolower($header)] = $header;
        }

        $this->exposedHeaders = \implode(', ', $headers);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();
        $request = $event->getRequest();
        if (! $exception instanceof MethodNotAllowedHttpException || Request::METHOD_OPTIONS !== $request->getMethod()) {
            return;
        }

        $allow = $exception->getHeaders()['Allow'];

        $response = Response::create();
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', $allow);
        $response->headers->set('Allow', $allow);

        if ($headers = $request->headers->get('Access-Control-Request-Headers')) {
            $response->headers->set('Access-Control-Allow-Headers', $headers);
        }

        $response->headers->set('Access-Control-Expose-Headers', $this->exposedHeaders);

        $event->setResponse($response);

        if (\method_exists($event, 'allowCustomResponseCode')) {
            $event->allowCustomResponseCode();
        }
    }

    public function onResponse(FilterResponseEvent $event): void
    {
        $request = $event->getRequest();
        $origin = $request->headers->get('Origin');
        if (null === $origin || '*' === $origin) {
            return;
        }

        if (null === $this->allowedOrigins) {
            $origin = '*';
        } elseif (! $this->isValidOrigin($origin)) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Expose-Headers', $this->exposedHeaders);

        $vary = $response->getVary();
        if ('*' !== $origin && ! \in_array('Origin', $vary)) {
            $vary[] = 'Origin';
            $response->setVary($vary);
        }
    }

    /**
     * Converts a domain glob to regex pattern.
     *
     * @param string $domain
     *
     * @return string
     */
    private function toRegex(string $domain): string
    {
        $regex = '';
        $escaping = false;
        $size = \strlen($domain);
        for ($i = 0; $i < $size; ++$i) {
            $char = $domain[$i];

            if ($escaping) {
                $escaping = false;
                $char = \preg_quote($char, '#');
            } elseif ('*' === $char) {
                $char = '.*';
            } elseif ('?' === $char) {
                $char = '.';
            } elseif ('\\' === $char) {
                $escaping = true;
            } else {
                $char = \preg_quote($char, '#');
            }

            $regex .= $char;
        }

        return $regex;
    }

    /**
     * Checks whether $origin is allowed for CORS.
     *
     * @param $origin
     *
     * @return bool
     */
    private function isValidOrigin(string $origin): bool
    {
        if (null === $this->allowedOrigins) {
            return true;
        }

        if (\preg_match($this->allowedOrigins, $origin)) {
            return true;
        }

        return false;
    }
}
