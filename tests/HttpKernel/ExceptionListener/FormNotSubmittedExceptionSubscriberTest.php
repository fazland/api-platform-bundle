<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\ExceptionListener;

use Kcs\ApiPlatformBundle\HttpKernel\ExceptionListener\FormNotSubmittedExceptionSubscriber;
use Kcs\ApiPlatformBundle\PatchManager\Exception\FormNotSubmittedException;
use Kcs\ApiPlatformBundle\Tests\Fixtures\View\AppKernel;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class FormNotSubmittedExceptionSubscriberTest extends WebTestCase
{
    /**
     * @var FormNotSubmittedExceptionSubscriber
     */
    private $subscriber;

    protected function setUp()
    {
        $this->subscriber = new FormNotSubmittedExceptionSubscriber();
    }

    public function testShouldSubscribeExceptionEvent()
    {
        $this->assertArrayHasKey('kernel.exception', FormNotSubmittedExceptionSubscriber::getSubscribedEvents());
    }

    public function testShouldSkipIncorrectExceptions()
    {
        $event = $this->prophesize(GetResponseForExceptionEvent::class);
        $event->getException()->willReturn(new \Exception());
        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onException($event->reveal());
    }

    public function testShouldHandleFormNotSubmittedException()
    {
        $event = $this->prophesize(GetResponseForExceptionEvent::class);
        $event->getException()->willReturn($exception = $this->prophesize(FormNotSubmittedException::class));
        $event->setResponse(Argument::type(Response::class))->shouldBeCalled();

        $exception->getForm()->willReturn($this->prophesize(FormInterface::class));

        $this->subscriber->onException($event->reveal());
    }

    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', true);
    }

    public function testShouldInterceptFormNotSubmittedExceptionsAndReturnsCorrectResponse()
    {
        $client = static::createClient();
        $client->request('GET', '/form-not-submitted', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"No data sent.","name":"form"}', $response->getContent());
    }
}
