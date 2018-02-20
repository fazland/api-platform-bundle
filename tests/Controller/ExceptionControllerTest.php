<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Controller;

use Fazland\ApiPlatformBundle\Controller\ExceptionController;
use Fazland\ApiPlatformBundle\HttpKernel\Exception\DebugSerializableException;
use Fazland\ApiPlatformBundle\HttpKernel\Exception\SerializableException;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Serializer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionControllerTest extends TestCase
{
    /**
     * @var Serializer|ObjectProphecy
     */
    private $serializer;

    /**
     * @var SerializationContext
     */
    private $serializationContext;

    /**
     * @var ExceptionController
     */
    private $controller;

    /**
     * @var Request
     */
    private $request;

    protected function setUp()
    {
        $status = ob_get_status(true);

        $this->request = Request::create('/');
        $this->request->setRequestFormat('json');
        $this->request->headers->set('X-Php-Ob-Level', count($status));

        $this->serializer = $this->prophesize(Serializer::class);
        $this->serializationContext = SerializationContext::create();

        $this->controller = new ExceptionController($this->serializer->reveal(), $this->serializationContext, false);
    }

    public function testShouldSerializeDebugExceptionIfDebugIsEnabled()
    {
        $this->serializer->serialize(Argument::cetera())->willReturn();
        $controller = new ExceptionController($this->serializer->reveal(), $this->serializationContext, true);

        $response = $controller->showAction($this->request, FlattenException::create(new NotFoundHttpException()));

        $this->serializer
            ->serialize(Argument::type(DebugSerializableException::class), 'json', $this->serializationContext)
            ->shouldHaveBeenCalled();

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testShouldSerializeException()
    {
        $this->serializer->serialize(Argument::cetera())->willReturn();
        $response = $this->controller->showAction($this->request, FlattenException::create(new AccessDeniedHttpException()));

        $this->serializer
            ->serialize(Argument::type(SerializableException::class), 'json', $this->serializationContext)
            ->shouldHaveBeenCalled();

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testShouldReturnATextResponseIfFormatIsNotSerializable()
    {
        $this->serializer->serialize(Argument::cetera())->willThrow(new UnsupportedFormatException());
        $this->request->setRequestFormat('md');

        $response = $this->controller->showAction($this->request, FlattenException::create(new BadRequestHttpException('A message.')));

        $this->serializer
            ->serialize(Argument::type(SerializableException::class), 'md', $this->serializationContext)
            ->shouldHaveBeenCalled();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('An error has occurred: Bad Request', $response->getContent());
    }
}
