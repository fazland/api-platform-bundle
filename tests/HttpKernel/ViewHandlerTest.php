<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\HttpKernel;

use Kcs\ApiPlatformBundle\Annotation\View;
use Kcs\ApiPlatformBundle\Doctrine\EntityIterator;
use Kcs\ApiPlatformBundle\HttpKernel\ViewHandler;
use Kcs\ApiPlatformBundle\Tests\Fixtures\TestObject;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Serializer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ViewHandlerTest extends TestCase
{
    /**
     * @var Serializer|ObjectProphecy
     */
    private $serializer;

    /**
     * @var HttpKernelInterface|ObjectProphecy
     */
    private $kernel;

    /**
     * @var ViewHandler
     */
    private $viewHandler;

    /**
     * @var SerializationContext
     */
    private $serializationContext;

    /**
     * @var TokenStorageInterface|ObjectProphecy
     */
    private $tokenStorage;

    protected function setUp()
    {
        $this->serializer = $this->prophesize(Serializer::class);
        $this->serializationContext = SerializationContext::create();
        $this->kernel = $this->prophesize(HttpKernelInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->viewHandler = new ViewHandler($this->serializer->reveal(), $this->serializationContext, $this->tokenStorage->reveal());
    }

    public function skipProvider()
    {
        $tests = [];

        $tests[] = [new Request(), new Response()];

        $request = new Request();
        $request->attributes->set('_rest_view', new \stdClass());
        $tests[] = [$request, ['foo' => 'bar']];

        return $tests;
    }

    /**
     * @dataProvider skipProvider
     */
    public function testSkip($request, $result)
    {
        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($result);

        $this->serializer->serialize(Argument::cetera())->shouldNotBeCalled();
        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSetStatusCode()
    {
        $annot = new View();
        $annot->statusCode = 201;

        $request = new Request();
        $request->attributes->set('_rest_view', $annot);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $this->serializer->serialize(Argument::type(TestObject::class), Argument::cetera())->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->will(function ($args) {
            /** @var Response $response */
            $response = $args[0];

            TestCase::assertEquals(201, $response->getStatusCode());
        });

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSerializeWithCorrectGroups()
    {
        $annot = new View();
        $annot->groups = ['group_foo', 'bar_bar'];

        $request = new Request();
        $request->attributes->set('_rest_view', $annot);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::type(SerializationContext::class))
            ->will(function ($args) {
                /** @var SerializationContext $context */
                list(, , $context) = $args;
                Assert::assertEquals(['group_foo', 'bar_bar'], $context->attributes->get('groups'));

                return '';
            })
            ->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldCallSerializationGroupProvider()
    {
        $annot = new View();
        $annot->groupsProvider = 'testGroupProvider';

        $request = new Request();
        $request->attributes->set('_rest_view', $annot);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::type(SerializationContext::class))
            ->will(function ($args) {
                /** @var SerializationContext $context */
                list(, , $context) = $args;
                Assert::assertEquals(['foobar'], $context->attributes->get('groups'));

                return '';
            })
            ->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSetResponseCode405IfFormatIsNotSupported()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new View());

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new \stdClass());

        $this->serializer
            ->serialize(Argument::any(), Argument::any(), Argument::type(SerializationContext::class))
            ->willThrow(new UnsupportedFormatException());

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && 406 == $response->getStatusCode();
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSerializeInvalidFormAndSetBadRequestStatus()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new View());

        $form = $this->prophesize(Form::class);
        $form->isValid()->willReturn(false);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($form->reveal());

        $this->serializer
            ->serialize($form->reveal(), Argument::any(), Argument::type(SerializationContext::class))
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && 400 == $response->getStatusCode();
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldTransformAnIteratorIntoAnArrayBeforeSerializing()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new View());

        $iterator = new \ArrayIterator(['foo' => 'bar']);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($iterator);

        $this->serializer
            ->serialize(['foo' => 'bar'], Argument::any(), Argument::type(SerializationContext::class))
            ->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldAddXTotalCountHeaderForEntityIterators()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new View());

        $iterator = $this->prophesize(EntityIterator::class);
        $iterator->count()->willReturn(42);
        $iterator->rewind()->willReturn();
        $iterator->valid()->willReturn(false);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($iterator);

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type(SerializationContext::class))
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && 42 == $response->headers->get('X-Total-Count');
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testSerializationContextShouldBeReusable()
    {
        $annot = new View();
        $annot->groups = ['group_foo', 'bar_bar'];

        $request = new Request();
        $request->attributes->set('_rest_view', $annot);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $self = $this;
        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::type(SerializationContext::class))
            ->will(function ($args) use ($self) {
                /** @var SerializationContext $context */
                list(, , $context) = $args;
                Assert::assertNotEquals(spl_object_hash($self->serializationContext), spl_object_hash($context));

                return '';
            })
            ->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }
}
