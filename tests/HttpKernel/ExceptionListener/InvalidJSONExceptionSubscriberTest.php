<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\ExceptionListener;

use Fazland\ApiPlatformBundle\HttpKernel\ExceptionListener\InvalidJSONExceptionSubscriber;
use Fazland\ApiPlatformBundle\PatchManager\Exception\InvalidJSONException;
use Fazland\ApiPlatformBundle\Tests\Fixtures\View\AppKernel;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class InvalidJSONExceptionSubscriberTest extends WebTestCase
{
    /**
     * @var InvalidJSONExceptionSubscriber
     */
    private $subscriber;

    protected function setUp()
    {
        $this->subscriber = new InvalidJSONExceptionSubscriber();
    }

    public function testShouldSubscribeExceptionEvent()
    {
        $this->assertArrayHasKey('kernel.exception', InvalidJSONExceptionSubscriber::getSubscribedEvents());
    }

    public function testShouldSkipIncorrectExceptions()
    {
        $event = $this->prophesize(GetResponseForExceptionEvent::class);
        $event->getException()->willReturn(new \Exception());
        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onException($event->reveal());
    }

    public function testShouldHandleInvalidJSONException()
    {
        $event = $this->prophesize(GetResponseForExceptionEvent::class);
        $event->getException()->willReturn($exception = $this->prophesize(InvalidJSONException::class));
        $event->setResponse(Argument::type(Response::class))->shouldBeCalled();

        $this->subscriber->onException($event->reveal());
    }

    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', true);
    }

    public function testShouldInterceptFormNotSubmittedExceptionsAndReturnsCorrectResponse()
    {
        $client = static::createClient();
        $client->request('GET', '/invalid-json', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Invalid."}', $response->getContent());
    }
}
