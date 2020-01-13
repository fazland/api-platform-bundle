<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Cake\Chronos\Chronos;
use Fazland\ApiPlatformBundle\Annotation\View as ViewAnnotation;
use Fazland\ApiPlatformBundle\HttpKernel\View\View;
use Fazland\ApiPlatformBundle\HttpKernel\ViewHandler;
use Fazland\ApiPlatformBundle\Pagination\PagerIterator;
use Fazland\ApiPlatformBundle\Pagination\PageToken;
use Fazland\ApiPlatformBundle\Tests\Fixtures\TestObject;
use Fazland\ApiPlatformBundle\Tests\Fixtures\View\AppKernel;
use Fazland\ApiPlatformBundle\Tests\Fixtures\View\Controller\TestController;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Kcs\Serializer\Exception\UnsupportedFormatException;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ViewHandlerTest extends WebTestCase
{
    /**
     * @var SerializerInterface|ObjectProphecy
     */
    private object $serializer;

    /**
     * @var HttpKernelInterface|ObjectProphecy
     */
    private object $httpKernel;

    /**
     * @var TokenStorageInterface|ObjectProphecy
     */
    private object $tokenStorage;

    private SerializationContext $serializationContext;

    private ViewHandler $viewHandler;

    private string $defaultResponseCharset;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->serializer = $this->prophesize(SerializerInterface::class);
        $this->serializationContext = SerializationContext::create();
        $this->httpKernel = $this->prophesize(HttpKernelInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->defaultResponseCharset = 'UTF-8';

        $this->viewHandler = new ViewHandler(
            $this->serializer->reveal(),
            $this->serializationContext,
            $this->tokenStorage->reveal(),
            $this->defaultResponseCharset
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../../var');
    }

    public function skipProvider(): array
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
    public function testSkip(Request $request, $result): void
    {
        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($result);

        $this->serializer->serialize(Argument::cetera())->shouldNotBeCalled();
        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSetStatusCode(): void
    {
        $annotation = new ViewAnnotation();
        $annotation->statusCode = Response::HTTP_CREATED;

        $request = new Request();
        $request->attributes->set('_rest_view', $annotation);

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $this->serializer->serialize(Argument::type(TestObject::class), Argument::cetera())->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->will(function ($args) {
            /** @var Response $response */
            $response = $args[0];

            TestCase::assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        });

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSerializeWithCorrectGroups(): void
    {
        $annotation = new ViewAnnotation();
        $annotation->groups = ['group_foo', 'bar_bar'];

        $request = new Request();
        $request->attributes->set('_rest_view', $annotation);

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::type(SerializationContext::class), null)
            ->will(function ($args) {
                /** @var SerializationContext $context */
                [, , $context] = $args;
                Assert::assertEquals(['group_foo', 'bar_bar'], $context->attributes->get('groups'));

                return '';
            })
            ->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldCallSerializationGroupProvider(): void
    {
        $annotation = new ViewAnnotation();
        $annotation->groupsProvider = 'testGroupProvider';

        $request = new Request();
        $request->attributes->set('_rest_view', $annotation);

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::type(SerializationContext::class), null)
            ->will(function ($args) {
                /** @var SerializationContext $context */
                [, , $context] = $args;
                Assert::assertEquals(['foobar'], $context->attributes->get('groups'));

                return '';
            })
            ->shouldBeCalled()
        ;

        $event->setResponse(Argument::type(Response::class))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSetResponseCode405IfFormatIsNotSupported(): void
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new \stdClass());

        $this->serializer
            ->serialize(Argument::any(), Argument::any(), Argument::type(SerializationContext::class), null)
            ->willThrow(new UnsupportedFormatException())
        ;

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && Response::HTTP_NOT_ACCEPTABLE === $response->getStatusCode();
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldSerializeInvalidFormAndSetBadRequestStatus(): void
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $form = $this->prophesize(Form::class);
        $form->isSubmitted()->willReturn(true);
        $form->isValid()->willReturn(false);

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($form->reveal());

        $this->serializer
            ->serialize($form->reveal(), Argument::any(), Argument::type(SerializationContext::class), null)
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && Response::HTTP_BAD_REQUEST === $response->getStatusCode();
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldCallSubmitOnUnsubmittedForms(): void
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $form = $this->prophesize(Form::class);
        $form->isSubmitted()->willReturn(false);
        $form->isValid()->willReturn(false);

        $form->submit(null)->shouldBeCalled();

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($form->reveal());

        $event->setResponse(Argument::any())->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function provideIterator(): iterable
    {
        yield [new \ArrayIterator(['foo' => 'bar'])];
        yield [new class() implements \IteratorAggregate {
            public function getIterator(): \Generator
            {
                yield from ['foo' => 'bar'];
            }
        }];
    }

    /**
     * @dataProvider provideIterator
     */
    public function testShouldTransformAnIteratorIntoAnArrayBeforeSerializing(iterable $iterator): void
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($iterator);

        $this->serializer
            ->serialize(['foo' => 'bar'], Argument::any(), Argument::type(SerializationContext::class), null)
            ->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldAddXTotalCountHeaderForEntityIterators(): ObjectProphecy
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $iterator = $this->prophesize(ObjectIteratorInterface::class);
        $iterator->count()->willReturn(42);
        $iterator->rewind()->willReturn();
        $iterator->valid()->willReturn(false);

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($iterator);

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type(SerializationContext::class), null)
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && 42 === (int) $response->headers->get('X-Total-Count');
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());

        return $iterator;
    }

    /**
     * @depends testShouldAddXTotalCountHeaderForEntityIterators
     */
    public function testShouldUnwrapIteratorFromIteratorAggregate(ObjectProphecy $iterator): void
    {
        $result = new class($iterator->reveal()) implements \IteratorAggregate {
            private $iterator;

            public function __construct(\Iterator $iterator)
            {
                $this->iterator = $iterator;
            }

            public function getIterator(): \Iterator
            {
                return $this->iterator;
            }
        };

        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($result);

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type(SerializationContext::class), null)
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && 42 === (int) $response->headers->get('X-Total-Count');
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testShouldAddXContinuationTokenHeaderForPagerIterators(): void
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $iterator = $this->prophesize(PagerIterator::class);
        $iterator->getNextPageToken()->willReturn(new PageToken((new Chronos('1991-11-24 02:00:00'))->getTimestamp(), 1, 1275024653));
        $iterator->rewind()->shouldBeCalled();
        $iterator->valid()->willReturn(false);

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn($iterator);

        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type(SerializationContext::class), null)
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function ($response) {
            return $response instanceof Response && 'bfdew0_1_l347bh' === $response->headers->get('X-Continuation-Token');
        }))->shouldBeCalled();

        $this->viewHandler->onView($event->reveal());
    }

    public function testSerializationContextShouldBeReusable(): void
    {
        $annotation = new ViewAnnotation();
        $annotation->groups = ['group_foo', 'bar_bar'];

        $request = new Request();
        $request->attributes->set('_rest_view', $annotation);

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new TestObject());

        $self = $this;
        $this->serializer
            ->serialize(Argument::type(TestObject::class), Argument::any(), Argument::type(SerializationContext::class), null)
            ->will(function ($args) use ($self) {
                /** @var SerializationContext $context */
                [, , $context] = $args;
                Assert::assertNotEquals(\spl_object_hash($self->serializationContext), \spl_object_hash($context));

                return '';
            })
            ->shouldBeCalled();
        $event->setResponse(Argument::type(Response::class))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testViewObjectShouldBeCorrectlyHandled(): void
    {
        $request = new Request();
        $request->attributes->set('_rest_view', new ViewAnnotation());

        $event = $this->prophesize(ViewEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getControllerResult()->willReturn(new View(['foobar' => 'no no no'], Response::HTTP_PAYMENT_REQUIRED));

        $self = $this;
        $this->serializer
            ->serialize(Argument::type('array'), Argument::any(), Argument::type(SerializationContext::class), null)
            ->will(function ($args) use ($self) {
                /** @var SerializationContext $context */
                [, , $context] = $args;
                Assert::assertNotEquals(\spl_object_hash($self->serializationContext), \spl_object_hash($context));

                return '{"foobar": "no no no"}';
            })
            ->shouldBeCalled();

        $event->setResponse(Argument::that(function (Response $response) {
            return Response::HTTP_PAYMENT_REQUIRED === $response->getStatusCode();
        }))->willReturn();

        $this->viewHandler->onView($event->reveal());
    }

    public function testDeprecatedAnnotationShouldBeHandled(): void
    {
        $controller = new TestController();

        $request = new Request();
        $request->attributes->set('_controller', [$controller, 'deprecatedAction']);

        $event = $this->prophesize(ControllerEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getController()->willReturn($request->attributes->get('_controller'));

        $this->viewHandler->onController($event->reveal());

        self::assertTrue($request->attributes->has('_deprecated'));
    }

    public function testDeprecatedWithCommentAnnotationShouldBeHandled(): void
    {
        $controller = new TestController();

        $request = new Request();
        $request->attributes->set('_controller', [$controller, 'deprecatedWithNoticeAction']);

        $event = $this->prophesize(ControllerEvent::class);
        $event->getRequest()->willReturn($request);
        $event->getController()->willReturn($request->attributes->get('_controller'));

        $this->viewHandler->onController($event->reveal());

        self::assertEquals('With Notice', $request->attributes->get('_deprecated'));
    }

    public function testShouldSetCorrectSerializationType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/custom-serialization-type', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('[{"data":"foobar","additional":"foo"},{"test":"barbar","additional":"foo"}]', $response->getContent());
    }

    public function testShouldSetCorrectSerializationTypeWhenProcessingAnIterator(): void
    {
        $client = static::createClient();
        $client->request('GET', '/custom-serialization-type-iterator', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('[{"data":"foobar","additional":"foo"},{"test":"barbar","additional":"foo"}]', $response->getContent());
    }

    public function testShouldSetEmitXDeprecatedHeader(): void
    {
        $client = static::createClient();
        $client->request('GET', '/deprecated', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals('This endpoint has been deprecated and will be discontinued in a future version. Please upgrade your application.', $response->headers->get('X-Deprecated'));
    }

    public function testShouldSetResponseCharsetInContentType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $response = $client->getResponse();

        self::assertRegExp('/; charset=UTF-8/', $response->headers->get('Content-Type'));
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }
}
