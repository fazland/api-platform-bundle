<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\ExceptionListener;

use Fazland\ApiPlatformBundle\HttpKernel\ExceptionListener\FormInvalidExceptionSubscriber;
use Fazland\ApiPlatformBundle\PatchManager\Exception\FormInvalidException;
use Fazland\ApiPlatformBundle\Tests\Fixtures\View\AppKernel;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FormInvalidExceptionSubscriberTest extends WebTestCase
{
    /**
     * @var FormInvalidExceptionSubscriber
     */
    private $subscriber;

    protected function setUp()
    {
        $this->subscriber = new FormInvalidExceptionSubscriber();
    }

    public function testShouldSubscribeExceptionEvent()
    {
        $this->assertArrayHasKey('kernel.exception', FormInvalidExceptionSubscriber::getSubscribedEvents());
    }

    public function testShouldSkipIncorrectExceptions()
    {
        $event = $this->prophesize(GetResponseForExceptionEvent::class);
        $event->getException()->willReturn(new \Exception());
        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onException($event->reveal());
    }

    public function testShouldHandleFormInvalidException()
    {
        $event = $this->prophesize(GetResponseForExceptionEvent::class);
        $event->getException()->willReturn($exception = $this->prophesize(FormInvalidException::class));
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

    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', true);
    }

    public function testShouldInterceptFormInvalidExceptionsAndReturnsCorrectResponse()
    {
        $client = static::createClient();
        $client->request('GET', '/form-invalid', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"errors":[],"children":[{"errors":["Foo error."],"children":[],"name":"first"},{"errors":[],"children":[],"name":"second"}],"name":"form"}', $response->getContent());
    }
}
