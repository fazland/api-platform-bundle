<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Exception;

use Fazland\ApiPlatformBundle\Tests\Fixtures\Exception\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class FunctionalTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', $options['debug'] ?? true);
    }

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__.'/../../var');
    }

    public function testShouldCatchExceptions(): void
    {
        $client = static::createClient(['debug' => false]);

        $client->request('GET', '/non-existent', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        self::assertEquals(404, $response->getStatusCode());

        $array = '{"error_message":"Not Found","error_code":0}';
        self::assertEquals($array, $response->getContent());
    }

    public function testShouldCatchExceptionsAndExposeTracesInDebugMode(): void
    {
        $client = static::createClient(['debug' => true]);

        $client->request('GET', '/non-existent', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $client->getResponse();

        self::assertEquals(404, $response->getStatusCode());

        $content = $response->getContent();
        self::assertJson($content);

        $res = \json_decode($content, true);
        self::assertArrayHasKey('error_message', $res);
        self::assertEquals('No route found for "GET /non-existent"', $res['error_message']);
        self::assertArrayHasKey('error_code', $res);
        self::assertArrayHasKey('exception', $res);
        self::assertIsArray($res['exception']);
    }
}
