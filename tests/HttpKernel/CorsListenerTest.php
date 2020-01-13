<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Fazland\ApiPlatformBundle\HttpKernel\CorsListener;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Cors\AppKernel;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

class CorsListenerTest extends WebTestCase
{
    private CorsListener $listener;

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->listener = new CorsListener();
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../../var');
    }

    public function wrongExceptionRequestProvider(): iterable
    {
        $exception = new MethodNotAllowedHttpException([Request::METHOD_POST]);
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('GET');

        yield [$exception, $request];

        $exception = new NotFoundHttpException();
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('OPTIONS');

        yield [$exception, $request];
    }

    /**
     * @dataProvider wrongExceptionRequestProvider
     */
    public function testOnExceptionShouldNotSetAResponseForWrongExceptionOrRequestMethod(
        \Throwable $exception,
        ObjectProphecy $request
    ): void {
        $event = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $this->listener->onException($event);
        self::assertFalse($event->hasResponse());
    }

    public function testOnExceptionShouldSetAResponse(): void
    {
        $exception = new MethodNotAllowedHttpException(['GET', 'POST']);
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('OPTIONS');
        $request->headers = new HeaderBag([
            'Access-Control-Request-Headers' => 'Authorization',
        ]);

        $event = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $this->listener->onException($event);

        self::assertTrue($event->hasResponse());

        if (\method_exists($event, 'isAllowingCustomResponseCode')) {
            self::assertTrue($event->isAllowingCustomResponseCode());
        }

        $response = $event->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $headers = $response->headers->all();
        self::assertArrayHasKey('access-control-allow-credentials', $headers);
        self::assertEquals(['true'], $headers['access-control-allow-credentials']);
        self::assertArrayHasKey('access-control-allow-methods', $headers);
        self::assertEquals(['GET, POST'], $headers['access-control-allow-methods']);
        self::assertArrayHasKey('allow', $headers);
        self::assertEquals(['GET, POST'], $headers['allow']);
        self::assertArrayHasKey('access-control-allow-headers', $headers);
        self::assertEquals(['Authorization'], $headers['access-control-allow-headers']);
        self::assertArrayHasKey('access-control-expose-headers', $headers);
        self::assertEquals(['Authorization, Content-Length, X-Total-Count, X-Continuation-Token'], $headers['access-control-expose-headers']);
    }

    public function testOnResponseShouldNotSetHeaderIfNoOriginIsSpecified(): void
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag();

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $this->listener->onResponse($event);

        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testOnResponseShouldNotSetHeaderIfOriginIsStar(): void
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag(['Origin' => '*']);

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $this->listener->onResponse($event);

        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testOnResponseShouldSetHeaderAsStar(): void
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag(['Origin' => 'https://localhost']);

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $this->listener->onResponse($event);

        self::assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        self::assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testOnResponseShouldNotSetHeaderIfOriginIsNotAllowed(): void
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag(['Origin' => 'https://localhost']);

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $listener = new CorsListener(['www.foobar.com']);
        $listener->onResponse($event);

        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testOnResponseShouldSetHeaderIfOriginIsAllowed(): void
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag(['Origin' => 'https://www.foobar.com']);

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $listener = new CorsListener(['*.foobar.com']);
        $listener->onResponse($event);

        self::assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        self::assertEquals('https://www.foobar.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testListenerShouldWork(): void
    {
        $client = static::createClient();

        $client->request(Request::METHOD_OPTIONS, '/', [], [], ['HTTP_ORIGIN' => 'https://localhost']);
        $response = $client->getResponse();

        self::assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals(Request::METHOD_GET, $response->headers->get('Access-Control-Allow-Methods'));
    }
}
