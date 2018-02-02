<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Cake\Chronos\Chronos;
use Fazland\ApiPlatformBundle\Annotation\View as ViewAnnotation;
use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;
use Fazland\ApiPlatformBundle\HttpKernel\View\View;
use Fazland\ApiPlatformBundle\HttpKernel\ViewHandler;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator;
use Fazland\ApiPlatformBundle\Pagination\PageToken;
use Fazland\ApiPlatformBundle\Tests\Fixtures\TestObject;
use Fazland\ApiPlatformBundle\Tests\Fixtures\View\AppKernel;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Serializer;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ViewHandlerTest extends WebTestCase
{
    /**
     * @var Serializer|ObjectProphecy
     */
    private $serializer;

    /**
     * @var HttpKernelInterface|ObjectProphecy
     */
    private $httpKernel;

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
        $this->httpKernel = $this->prophesize(HttpKernelInterface::class);
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
        $annot = new ViewAnnotation();
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
        $annot = new ViewAnnotation();
        $annot->groups = ['group_foo', 'bar_bar'];

        $request = new Request();
        $request->attributes->set('_rest_view', $annot);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::type(SerializationContext::class), null)
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
        $annot = new ViewAnnotation();
        $annot->groupsProvider = 'testGroupProvider';

        $request = new Request();
        $request->attributes->set('_rest_view', $annot);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::type(SerializationContext::class), null)
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
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new \stdClass());

        $this->serializer
            ->serialize(Argument::any(), Argument::any(), Argument::type(SerializationContext::class), null)
            ->willThrow(new UnsupportedFormatException());

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && 406 == $response->getStatusCode();
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSerializeInvalidFormAndSetBadRequestStatus()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $form = $this->prophesize(Form::class);
        $form->isSubmitted()->willReturn(true);
        $form->isValid()->willReturn(false);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($form->reveal());

        $this->serializer
            ->serialize($form->reveal(), Argument::any(), Argument::type(SerializationContext::class), null)
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && 400 == $response->getStatusCode();
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldCallSubmitOnUnsubmittedForms()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $form = $this->prophesize(Form::class);
        $form->isSubmitted()->willReturn(false);
        $form->isValid()->willReturn(false);

        $form->submit(null)->shouldBeCalled();

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($form->reveal());

        $event->setResponse(Argument::any())->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldTransformAnIteratorIntoAnArrayBeforeSerializing()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $iterator = new \ArrayIterator(['foo' => 'bar']);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($iterator);

        $this->serializer
            ->serialize(['foo' => 'bar'], Argument::any(), Argument::type(SerializationContext::class), null)
            ->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldAddXTotalCountHeaderForEntityIterators()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $iterator = $this->prophesize(ObjectIterator::class);
        $iterator->count()->willReturn(42);
        $iterator->rewind()->willReturn();
        $iterator->valid()->willReturn(false);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($iterator);

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type(SerializationContext::class), null)
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && 42 == $response->headers->get('X-Total-Count');
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldAddXContinuationTokenHeaderForPagerIterators()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $iterator = $this->prophesize(PagerIterator::class);
        $iterator->getNextPageToken()->willReturn(new PageToken(new Chronos('1991-11-24 02:00:00'), 1, 1275024653));
        $iterator->rewind()->willReturn();
        $iterator->valid()->willReturn(false);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($iterator);

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type(SerializationContext::class), null)
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && '8tf0lkw0_1_l347bh' === $response->headers->get('X-Continuation-Token');
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testSerializationContextShouldBeReusable()
    {
        $annot = new ViewAnnotation();
        $annot->groups = ['group_foo', 'bar_bar'];

        $request = new Request();
        $request->attributes->set('_rest_view', $annot);

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $self = $this;
        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::type(SerializationContext::class), null)
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

    public function testViewObjectShouldBeCorrectlyHandled()
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $event = $this->prophesize(GetResponseForControllerResultEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new View(['foobar' => 'no no no'], Response::HTTP_PAYMENT_REQUIRED));

        $self = $this;
        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type(SerializationContext::class), null)
            ->will(function ($args) use ($self) {
                /** @var SerializationContext $context */
                list(, , $context) = $args;
                Assert::assertNotEquals(spl_object_hash($self->serializationContext), spl_object_hash($context));

                return '{"foobar": "no no no"}';
            })
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function (Response $response) {
            return Response::HTTP_PAYMENT_REQUIRED === $response->getStatusCode();
        }))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSetCorrectSerializationType()
    {
        $client = static::createClient();
        $client->request('GET', '/custom-serialization-type', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('[{"data":"foobar","additional":"foo"},{"test":"barbar","additional":"foo"}]', $response->getContent());
    }

    public function testShouldSetCorrectSerializationTypeWhenProcessingAnIterator()
    {
        $client = static::createClient();
        $client->request('GET', '/custom-serialization-type-iterator', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('[{"data":"foobar","additional":"foo"},{"test":"barbar","additional":"foo"}]', $response->getContent());
    }

    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', true);
    }
}
