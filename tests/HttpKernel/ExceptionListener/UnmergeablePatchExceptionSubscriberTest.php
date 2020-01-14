<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\ExceptionListener;

use Fazland\ApiPlatformBundle\HttpKernel\ExceptionListener\UnmergeablePatchExceptionSubscriber;
use Fazland\ApiPlatformBundle\PatchManager\Exception\UnmergeablePatchException;
use Fazland\ApiPlatformBundle\Tests\Fixtures\View\AppKernel;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;

class UnmergeablePatchExceptionSubscriberTest extends WebTestCase
{
    private UnmergeablePatchExceptionSubscriber $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->subscriber = new UnmergeablePatchExceptionSubscriber();
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
        self::assertArrayHasKey('kernel.exception', UnmergeablePatchExceptionSubscriber::getSubscribedEvents());
    }

    public function testShouldSkipIncorrectExceptions(): void
    {
        $event = $this->prophesize(ExceptionEvent::class);
        $event->getThrowable()->willReturn(new \Exception());
        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->subscriber->onException($event->reveal());
    }

    public function testShouldHandleUnmergeablePatchException(): void
    {
        $event = $this->prophesize(ExceptionEvent::class);
        $event->getThrowable()->willReturn($exception = $this->prophesize(UnmergeablePatchException::class));
        $event->setResponse(Argument::type(Response::class))->shouldBeCalled();

        $this->subscriber->onException($event->reveal());
    }

    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    public function testShouldInterceptFormNotSubmittedExceptionsAndReturnsCorrectResponse(): void
    {
        $client = static::createClient();
        $client->request('GET', '/invalid-json', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"Invalid."}', $response->getContent());
    }
}
