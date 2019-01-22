<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Fazland\ApiPlatformBundle\Annotation\Sunset;
use Fazland\ApiPlatformBundle\HttpKernel\SunsetHandler;
use Fazland\ApiPlatformBundle\Tests\Fixtures\Sunset\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class SunsetHandlerTest extends WebTestCase
{
    /**
     * @var SunsetHandler
     */
    private $sunsetHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->sunsetHandler = new SunsetHandler();
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
    public function testSkip(Request $request, Response $response)
    {
        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getResponse()->willReturn($response);

        $this->sunsetHandler->onResponse($event->reveal());

        self::assertFalse($response->headers->has('Sunset'));
    }

    public function testShouldSetStatusCode(): void
    {
        $annot = new Sunset();
        $annot->date = '2019-02-01';

        $request = new Request();
        $request->attributes->set('_rest_sunset', $annot);

        $event = $this->prophesize(FilterResponseEvent::class);
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
