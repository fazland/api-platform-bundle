<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Controller;

use Fazland\ApiPlatformBundle\ErrorRenderer\SerializerErrorRenderer;
use Fazland\ApiPlatformBundle\HttpKernel\Exception\DebugSerializableException;
use Fazland\ApiPlatformBundle\HttpKernel\Exception\SerializableException;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\ErrorHandler\ErrorRenderer\ErrorRendererInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionControllerTest extends TestCase
{
    private SerializationContext $serializationContext;
    private SerializerErrorRenderer $renderer;
    private Request $request;
    private ErrorRendererInterface $fallbackErrorRenderer;

    /**
     * @var SerializerInterface|ObjectProphecy
     */
    private ObjectProphecy $serializer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $status = \ob_get_status(true);

        $this->request = Request::create('/');
        $this->request->setRequestFormat('json');
        $this->request->headers->set('X-Php-Ob-Level', \count($status));

        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->fallbackErrorRenderer = new HtmlErrorRenderer(false);

        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->serializationContext = SerializationContext::create();

        $this->renderer = new SerializerErrorRenderer($this->fallbackErrorRenderer, $requestStack, $this->serializer->reveal(), $this->serializationContext, false);
    }

    public function testShouldSerializeDebugExceptionIfDebugIsEnabled(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push($this->request);

        $this->serializer
            ->serialize(Argument::type(DebugSerializableException::class), 'json', $this->serializationContext)
            ->shouldBeCalled()
            ->willReturn('{}')
        ;

        $renderer = new SerializerErrorRenderer($this->fallbackErrorRenderer, $requestStack, $this->serializer->reveal(), $this->serializationContext, true);
        $flatten = $renderer->render(new NotFoundHttpException());

        self::assertEquals(Response::HTTP_NOT_FOUND, $flatten->getStatusCode());
        self::assertEquals('{}', $flatten->getAsString());
    }

    public function testShouldSerializeException(): void
    {
        $this->serializer->serialize(Argument::cetera())->willReturn();
        $flatten = $this->renderer->render(new AccessDeniedHttpException());

        $this->serializer
            ->serialize(Argument::type(SerializableException::class), 'json', $this->serializationContext)
            ->shouldHaveBeenCalled()
        ;

        self::assertEquals(Response::HTTP_FORBIDDEN, $flatten->getStatusCode());
    }

    public function testShouldCallTheFallbackRendererIfFormatIsNotSerializable(): void
    {
        $this->serializer->serialize(Argument::cetera())->willThrow(new UnsupportedFormatException());
        $this->request->setRequestFormat('md');

        $flatten = $this->renderer->render(new BadRequestHttpException('A message.'));

        $this->serializer
            ->serialize(Argument::type(SerializableException::class), 'md', $this->serializationContext)
            ->shouldHaveBeenCalled()
        ;

        self::assertEquals(Response::HTTP_BAD_REQUEST, $flatten->getStatusCode());
        self::assertStringContainsString('The server returned a "400 Bad Request".', $flatten->getAsString());
    }
}
