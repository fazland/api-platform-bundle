<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Fazland\ApiPlatformBundle\Tests\Fixtures\Bundle\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class ApiPlatformBundleTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel($options['env'] ?? 'test', $options['debug'] ?? true);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../../var');
    }

    public function testIndexShouldBeOk(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals('{"test_foo":"foo.test"}', $response->getContent());
    }

    public function testBodyConvertersAreEnabled(): void
    {
        $client = static::createClient();
        $client->request('POST', '/body', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], '{"foo":"bar"}');

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(
'array:1 [
  "foo" => "bar"
]
"{"foo":"bar"}"', $response->getContent());
    }

    public function testBodyConvertersCanBeDisabled(): void
    {
        $client = static::createClient(['env' => 'no_body']);
        $client->request('POST', '/body', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], '{"foo":"bar"}');

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals(
'[]
"{"foo":"bar"}"', $response->getContent());
    }

    /**
     * Marked as legacy. As of Symfony 4.1:
     * Referencing controllers with a single colon is deprecated since Symfony 4.1. Use fazland_api.exception_controller::showAction instead.
     *
     * @group legacy
     */
    public function testViewHandlerCanBeDisabled(): void
    {
        $client = static::createClient(['env' => 'no_view', 'debug' => false]);
        $client->request('GET', '/', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertEquals('An error has occurred: Internal Server Error', $response->getContent());
    }
}
