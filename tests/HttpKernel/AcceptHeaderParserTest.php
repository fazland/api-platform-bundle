<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Cake\Chronos\Chronos;
use Fazland\ApiPlatformBundle\HttpKernel\AcceptHeaderParser;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @property AcceptHeaderParser parser
 */
class AcceptHeaderParserTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->parser = new AcceptHeaderParser();
    }

    public function dataProviderForNotAcceptableHeader(): iterable
    {
        return [
            ['text/html'],
            ['image/png'],
            ['application/json; version='],
            ['application/xml; version=12'],
            ['application/xml; version=asd'],
        ];
    }

    /**
     * @dataProvider dataProviderForNotAcceptableHeader
     */
    public function testNotAcceptableHeader(string $header): void
    {
        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn(Request::create('/', Request::METHOD_GET, [], [], [], ['HTTP_ACCEPT' => $header]));
        $event->setResponse(Argument::that(function (Response $response): bool {
            Assert::assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());

            return true;
        }))->shouldBeCalled();

        $this->parser->onKernelRequest($event->reveal());
    }

    public function dataProviderForAcceptableHeader(): iterable
    {
        return [
            ['application/json; version=2016-02-28'],
            ['application/xml; version=20160228'],
            ['application/json'],
        ];
    }

    /**
     * @dataProvider dataProviderForAcceptableHeader
     */
    public function testAcceptableHeader(string $header): void
    {
        Chronos::setTestNow(Chronos::createFromDate(2016, 2, 28));
        $request = Request::create('/', Request::METHOD_GET, [], [], [], ['HTTP_ACCEPT' => $header]);

        $event = $this->prophesize(RequestEvent::class);
        $event->getRequest()->willReturn($request);

        $this->parser->onKernelRequest($event->reveal());

        self::assertEquals(20160228, $request->attributes->get('_version'));
    }

    public function testPriorityMustBeHigherThenRoutersOne(): void
    {
        $events = $this->parser->getSubscribedEvents();
        self::assertArrayHasKey(KernelEvents::REQUEST, $events);

        $routerEvents = RouterListener::getSubscribedEvents();
        self::assertArrayHasKey(KernelEvents::REQUEST, $routerEvents);

        self::assertTrue($events[KernelEvents::REQUEST][1] > $routerEvents[KernelEvents::REQUEST][0][1]);
    }
}
