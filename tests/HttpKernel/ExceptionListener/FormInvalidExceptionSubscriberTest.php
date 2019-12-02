<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\ExceptionListener;

use Fazland\ApiPlatformBundle\HttpKernel\ExceptionListener\FormInvalidExceptionSubscriber;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Fazland\ApiPlatformBundle\Tests\Fixtures\View\AppKernel;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class FormInvalidExceptionSubscriberTest extends WebTestCase
{
    /**
     * @var FormInvalidExceptionSubscriber
     */
    private $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->subscriber = new FormInvalidExceptionSubscriber();
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../../../var');
    }

    public function testShouldSubscribeExceptionEvent(): void
    {
        self::assertArrayHasKey('kernel.exception', FormInvalidExceptionSubscriber::getSubscribedEvents());
    }

    public function testShouldSkipIncorrectExceptions(): void
    {
        $event = $this->prophesize(ExceptionEvent::class);
        $event->getThrowable()->willReturn(new \Exception());
        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onException($event->reveal());
    }

    public function testShouldHandleFormInvalidException(): void
    {
        $event = $this->prophesize(ExceptionEvent::class);
        $event->getThrowable()->willReturn($exception = $this->prophesize(FormInvalidException::class));
        $event->getRequest()->willReturn($request = $this->prophesize(Request::class));
        $event->setResponse($response = $this->prophesize(Response::class))->shouldBeCalled();
        $event->getKernel()->willReturn($kernel = $this->prophesize(HttpKernelInterface::class));

        $exception->getForm()->willReturn($this->prophesize(FormInterface::class));

        $request->duplicate(null, null, Argument::type('array'))
            ->willReturn($request = $this->prophesize(Request::class));

        $kernel->handle($request, HttpKernelInterface::SUB_REQUEST, false)
            ->shouldBeCalled()
            ->willReturn($response);

        $this->subscriber->onException($event->reveal());
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    public function testShouldInterceptFormInvalidExceptionsAndReturnsCorrectResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/form-invalid', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"errors":[],"children":[{"errors":["Foo error."],"children":[],"name":"first"},{"errors":[],"children":[],"name":"second"}],"name":"form"}', $response->getContent());
    }
}
