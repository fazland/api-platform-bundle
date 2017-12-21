<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Fazland\ApiPlatformBundle\HttpKernel\CorsListener;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Cors\AppKernel;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class CorsListenerTest extends WebTestCase
{
    /**
     * @var CorsListener
     */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', true);
    }

    protected function setUp()
    {
        $this->listener = new CorsListener();
    }

    public function wrongExceptionRequestProvider()
    {
        $exception = new MethodNotAllowedHttpException(['POST']);
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
    public function testOnExceptionShouldNotSetAResponseForWrongExceptionOrRequestMethod(\Exception $exception, ObjectProphecy $request)
    {
        $event = new GetResponseForExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $this->listener->onException($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testOnExceptionShouldSetAResponse()
    {
        $exception = new MethodNotAllowedHttpException(['GET', 'POST']);
        $request = $this->prophesize(Request::class);
        $request->getMethod()->willReturn('OPTIONS');
        $request->headers = new HeaderBag([
            'Access-Control-Request-Headers' => 'Authorization',
        ]);

        $event = new GetResponseForExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $exception
        );

        $this->listener->onException($event);

        $this->assertTrue($event->hasResponse());

        if (method_exists($event, 'isAllowingCustomResponseCode')) {
            $this->assertTrue($event->isAllowingCustomResponseCode());
        }

        $response = $event->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $headers = $response->headers->all();
        $this->assertArrayHasKey('access-control-allow-credentials', $headers);
        $this->assertEquals(['true'], $headers['access-control-allow-credentials']);
        $this->assertArrayHasKey('access-control-allow-methods', $headers);
        $this->assertEquals(['GET, POST'], $headers['access-control-allow-methods']);
        $this->assertArrayHasKey('allow', $headers);
        $this->assertEquals(['GET, POST'], $headers['allow']);
        $this->assertArrayHasKey('access-control-allow-headers', $headers);
        $this->assertEquals(['Authorization'], $headers['access-control-allow-headers']);
        $this->assertArrayHasKey('access-control-expose-headers', $headers);
        $this->assertEquals(['Authorization, Content-Length, X-Total-Count'], $headers['access-control-expose-headers']);
    }

    public function testOnResponseShouldNotSetHeaderIfNoOriginIsSpecified()
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag();

        $event = new FilterResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $this->listener->onResponse($event);

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testOnResponseShouldNotSetHeaderIfOriginIsStar()
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag(['Origin' => '*']);

        $event = new FilterResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $this->listener->onResponse($event);

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testOnResponseShouldSetHeaderAsStar()
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag(['Origin' => 'https://localhost']);

        $event = new FilterResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $this->listener->onResponse($event);

        $this->assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testOnResponseShouldNotSetHeaderIfOriginIsNotAllowed()
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag(['Origin' => 'https://localhost']);

        $event = new FilterResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $listener = new CorsListener(['www.foobar.com']);
        $listener->onResponse($event);

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function testOnResponseShouldSetHeaderIfOriginIsAllowed()
    {
        $request = $this->prophesize(Request::class);
        $request->headers = new HeaderBag(['Origin' => 'https://www.foobar.com']);

        $event = new FilterResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request->reveal(),
            HttpKernelInterface::MASTER_REQUEST,
            $response = Response::create()
        );

        $listener = new CorsListener(['*.foobar.com']);
        $listener->onResponse($event);

        $this->assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertEquals('https://www.foobar.com', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testListenerShouldWork()
    {
        $client = static::createClient();

        $client->request('OPTIONS', '/', [], [], ['HTTP_ORIGIN' => 'https://localhost']);
        $response = $client->getResponse();

        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(Kernel::VERSION_ID < 30000 ? 'GET, HEAD' : 'GET', $response->headers->get('Access-Control-Allow-Methods'));
    }
}
