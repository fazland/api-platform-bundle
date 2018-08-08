<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Exception;

use Fazland\ApiPlatformBundle\Tests\Fixtures\Exception\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Marked as legacy. As of Symfony 4.1:
 * Referencing controllers with a single colon is deprecated since Symfony 4.1. Use fazland_api.exception_controller::showAction instead.
 *
 * @group legacy
 */
class FunctionalTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', $options['debug'] ?? true);
    }

    public function testShouldCatchExceptions(): void
    {
        $client = static::createClient(['debug' => false]);

        $client->request('GET', '/non-existent', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());

        $array = '{"error_message":"Not Found","error_code":0}';
        $this->assertEquals($array, $response->getContent());
    }

    public function testShouldCatchExceptionsAndExposeTracesInDebugMode(): void
    {
        $client = static::createClient(['debug' => true]);

        $client->request('GET', '/non-existent', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertJson($content);

        $res = json_decode($content, true);
        $this->assertArrayHasKey('error_message', $res);
        $this->assertEquals('No route found for "GET /non-existent"', $res['error_message']);
        $this->assertArrayHasKey('error_code', $res);
        $this->assertArrayHasKey('exception', $res);
        $this->assertInternalType('array', $res['exception']);
    }
}
