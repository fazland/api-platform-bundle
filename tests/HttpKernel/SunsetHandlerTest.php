<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Fazland\ApiPlatformBundle\Annotation\Sunset;
use Fazland\ApiPlatformBundle\HttpKernel\SunsetHandler;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Sunset\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class SunsetHandlerTest extends WebTestCase
{
    private SunsetHandler $sunsetHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->sunsetHandler = new SunsetHandler();
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../../var');
    }

    public function skipProvider(): iterable
    {
        yield [new Request(), new Response()];

        $request = new Request();
        $request->attributes->set('_rest_sunset', new \stdClass());
        yield [$request, new Response()];
    }

    /**
     * @dataProvider skipProvider
     */
    public function testSkip(Request $request, Response $response): void
    {
        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getResponse()->willReturn($response);

        $this->sunsetHandler->onResponse($event->reveal());

        self::assertFalse($response->headers->has('Sunset'));
    }

    public function testShouldSetStatusCode(): void
    {
        $annotation = new Sunset();
        $annotation->date = '2019-02-01';

        $request = new Request();
        $request->attributes->set('_rest_sunset', $annotation);

        $event = $this->prophesize(ResponseEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getResponse()->willReturn($response = new Response());

        $this->sunsetHandler->onResponse($event->reveal());

        self::assertTrue($response->headers->has('Sunset'));
        self::assertEquals(new \DateTime('2019-02-01T00:00:00Z'), $response->headers->getDate('Sunset'));
    }

    public function testShouldSetResponseSunsetHeaderField(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $response = $client->getResponse();

        self::assertTrue($response->headers->has('Sunset'));
        self::assertEquals(new \DateTime('2019-03-01T00:00:00Z'), $response->headers->getDate('Sunset'));
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }
}
