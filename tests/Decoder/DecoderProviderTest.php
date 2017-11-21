<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\Decoder;

use Kcs\ApiPlatformBundle\Decoder\DecoderInterface;
use Kcs\ApiPlatformBundle\Decoder\DecoderProvider;
use PHPUnit\Framework\TestCase;

class DecoderProviderTest extends TestCase
{
    public function getProviders()
    {
        return [
            'json' => $this->prophesize(DecoderInterface::class)->reveal(),
        ];
    }

    public function testSupportShouldReturnFalseIfFormatIsNotSupported()
    {
        $provider = new DecoderProvider($this->getProviders());

        $this->assertFalse($provider->supports('xml'));
    }

    public function testSupportShouldReturnTrueIfFormatIsSupported()
    {
        $provider = new DecoderProvider($this->getProviders());

        $this->assertTrue($provider->supports('json'));
    }

    /**
     * @expectedException \Kcs\ApiPlatformBundle\Decoder\Exception\UnsupportedFormatException
     */
    public function testGetShouldThrowIfFormatIsNotSupported()
    {
        $provider = new DecoderProvider($this->getProviders());

        $provider->get('xml');
    }

    public function testGetShouldNotThrowIfFormatIsSupported()
    {
        $provider = new DecoderProvider($this->getProviders());

        $this->assertNotNull($provider->get('json'));
    }
}
