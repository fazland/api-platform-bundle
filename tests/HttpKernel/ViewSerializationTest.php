<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\HttpKernel;

use Fazland\ApiPlatformBundle\Tests\Fixtures\View\AppKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

class ViewSerializationTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    public function testAuthenticationOkForCorrectCredentials(): void
    {
        $client = static::createClient();
        $client->request(Request::METHOD_GET, '/', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertEquals('{"test_foo":"foo.test"}', $response->getContent());
    }
}
