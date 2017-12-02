<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Fazland\ApiPlatformBundle\Tests\Fixtures\Bundle\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiPlatformBundleTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = [])
    {
        return new AppKernel($options['env'] ?? 'test', true);
    }

    public function testIndexShouldBeOk()
    {
        $client = static::createClient();
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"test_foo":"foo.test"}', $response->getContent());
    }

    public function testBodyConvertersAreEnabled()
    {
        $client = static::createClient();
        $client->request('POST', '/body', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], '{"foo":"bar"}');

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
'array:1 [
  "foo" => "bar"
]
"{"foo":"bar"}"', $response->getContent());
    }

    public function testBodyConvertersCanBeDisabled()
    {
        $client = static::createClient(['env' => 'no_body']);
        $client->request('POST', '/body', [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], '{"foo":"bar"}');

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
'[]
"{"foo":"bar"}"', $response->getContent());
    }

    public function testViewHandlerCanBeDisabled()
    {
        $client = static::createClient(['env' => 'no_view']);
        $client->request('GET', '/', [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $response = $client->getResponse();

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('An error has occurred: The controller must return a response (Array(test_foo => foo.test) given).', $response->getContent());
    }
}
