<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpKernel;

use Cake\Chronos\Chronos;
use Fazland\ApiPlatformBundle\Negotiation\VersionAwareNegotiator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\NoConfigurationException;

class AcceptHeaderParser implements EventSubscriberInterface
{
    private string $defaultType;
    private bool $debug;

    /**
     * @var string[]
     */
    private array $uris;

    public function __construct(string $defaultType = 'application/json', array $uris = ['.*'], bool $debug = false)
    {
        if (empty($uris)) {
            throw new \InvalidArgumentException('URIs argument cannot be empty');
        }

        $this->defaultType = $defaultType;
        $this->debug = $debug;
        $this->uris = \array_map(static fn (string $uri): string => '#'.\str_replace('#', '\\#', $uri).'#i', $uris);
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (! $this->isApplicable($request)) {
            return;
        }

        $negotiator = new VersionAwareNegotiator();
        $header = $negotiator->getBest($request->headers->get('Accept', $this->defaultType), [
            'application/json', 'application/x-json',
            'text/xml', 'application/xml', 'application/x-xml',
        ]);

        if (null === $header) {
            $event->setResponse(new Response('', Response::HTTP_NOT_ACCEPTABLE));

            return;
        }

        $version = \str_replace('-', '', $header->getVersion() ?? Chronos::now()->format('Ymd'));
        if (! \preg_match('/\d{8}/', $version)) {
            $event->setResponse(new Response('', Response::HTTP_NOT_ACCEPTABLE));

            return;
        }

        $request->attributes->set('_format', $request->getFormat($header->getType()));
        $request->attributes->set('_version', $version);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (! $this->debug || !($e = $event->getThrowable()) instanceof NotFoundHttpException) {
            return;
        }

        if ($e->getPrevious() instanceof NoConfigurationException) {
            $request = $event->getRequest();
            $request->attributes->set('_format', 'html');
            $request->setRequestFormat('html');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 40],
            KernelEvents::EXCEPTION => ['onKernelException', 250],
        ];
    }

    /**
     * Checks whether this parser can be executed on the given request.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isApplicable(Request $request): bool
    {
        $uri = $request->getUri();
        foreach ($this->uris as $pattern) {
            if (\preg_match($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }
}
