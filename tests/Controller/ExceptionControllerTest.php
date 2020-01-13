<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Controller;

use Fazland\ApiPlatformBundle\Controller\ExceptionController;
use Fazland\ApiPlatformBundle\HttpKernel\Exception\DebugSerializableException;
use Fazland\ApiPlatformBundle\HttpKernel\Exception\SerializableException;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionControllerTest extends TestCase
{
    /**
     * @var SerializerInterface|ObjectProphecy
     */
    private object $serializer;

    private SerializationContext $serializationContext;

    private ExceptionController $controller;

    private Request $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $status = \ob_get_status(true);

        $this->request = Request::create('/');
        $this->request->setRequestFormat('json');
        $this->request->headers->set('X-Php-Ob-Level', \count($status));

        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->serializationContext = SerializationContext::create();

        $this->controller = new ExceptionController($this->serializer->reveal(), $this->serializationContext, false);
    }

    public function testShouldSerializeDebugExceptionIfDebugIsEnabled(): void
    {
        $this->serializer->serialize(Argument::cetera())->willReturn();
        $controller = new ExceptionController($this->serializer->reveal(), $this->serializationContext, true);

        $response = $controller($this->request, FlattenException::create(new NotFoundHttpException()));

        $this->serializer
            ->serialize(Argument::type(DebugSerializableException::class), 'json', $this->serializationContext)
            ->shouldHaveBeenCalled()
        ;

        self::assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testShouldSerializeException(): void
    {
        $this->serializer->serialize(Argument::cetera())->willReturn();
        $response = ($this->controller)($this->request, FlattenException::create(new AccessDeniedHttpException()));

        $this->serializer
            ->serialize(Argument::type(SerializableException::class), 'json', $this->serializationContext)
            ->shouldHaveBeenCalled()
        ;

        self::assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testShouldReturnATextResponseIfFormatIsNotSerializable(): void
    {
        $this->serializer->serialize(Argument::cetera())->willThrow(new UnsupportedFormatException());
        $this->request->setRequestFormat('md');

        $response = ($this->controller)($this->request, FlattenException::create(new BadRequestHttpException('A message.')));

        $this->serializer
            ->serialize(Argument::type(SerializableException::class), 'md', $this->serializationContext)
            ->shouldHaveBeenCalled()
        ;

        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertEquals('An error has occurred: Bad Request', $response->getContent());
    }
}
