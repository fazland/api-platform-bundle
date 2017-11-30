<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Exception;

use Fazland\ApiPlatformBundle\Tests\Fixtures\Exception\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FunctionalTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = [])
    {
        return new AppKernel('test', $options['debug'] ?? true);
    }

    public function testShouldCatchExceptions()
    {
        $client = static::createClient(['debug' => false]);

        $client->request('GET', '/non-existent', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());

        $array = '{"error_message":"No route found for \"GET \/non-existent\"","error_code":0}';
        $this->assertEquals($array, $response->getContent());
    }

    public function testShouldCatchExceptionsAndExposeTracesInDebugMode()
    {
        $client = static::createClient(['debug' => true]);

        $client->request('GET', '/non-existent', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertJson($content);

        $res = json_decode($content, true);
        $this->assertArrayHasKey('error_message', $res);
        $this->assertArrayHasKey('error_code', $res);
        $this->assertArrayHasKey('exception', $res);
        $this->assertInternalType('array', $res['exception']);
    }
}
