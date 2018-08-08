<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Decoder;

use Fazland\ApiPlatformBundle\Decoder\DecoderInterface;
use Fazland\ApiPlatformBundle\Decoder\DecoderProvider;
use PHPUnit\Framework\TestCase;

class DecoderProviderTest extends TestCase
{
    public function getProviders(): iterable
    {
        return [
            'json' => $this->prophesize(DecoderInterface::class)->reveal(),
        ];
    }

    public function testSupportShouldReturnFalseIfFormatIsNotSupported(): void
    {
        $provider = new DecoderProvider($this->getProviders());

        $this->assertFalse($provider->supports('xml'));
    }

    public function testSupportShouldReturnTrueIfFormatIsSupported(): void
    {
        $provider = new DecoderProvider($this->getProviders());

        $this->assertTrue($provider->supports('json'));
    }

    /**
     * @expectedException \Fazland\ApiPlatformBundle\Decoder\Exception\UnsupportedFormatException
     */
    public function testGetShouldThrowIfFormatIsNotSupported(): void
    {
        $provider = new DecoderProvider($this->getProviders());

        $provider->get('xml');
    }

    public function testGetShouldNotThrowIfFormatIsSupported(): void
    {
        $provider = new DecoderProvider($this->getProviders());

        $this->assertNotNull($provider->get('json'));
    }
}
