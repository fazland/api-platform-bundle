<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Decoder;

use Fazland\ApiPlatformBundle\Decoder\DecoderInterface;
use Fazland\ApiPlatformBundle\Decoder\DecoderProvider;
use Fazland\ApiPlatformBundle\Decoder\Exception\UnsupportedFormatException;
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

        self::assertFalse($provider->supports('xml'));
    }

    public function testSupportShouldReturnTrueIfFormatIsSupported(): void
    {
        $provider = new DecoderProvider($this->getProviders());

        self::assertTrue($provider->supports('json'));
    }

    public function testGetShouldThrowIfFormatIsNotSupported(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $provider = new DecoderProvider($this->getProviders());

        $provider->get('xml');
    }

    public function testGetShouldNotThrowIfFormatIsSupported(): void
    {
        $provider = new DecoderProvider($this->getProviders());

        self::assertNotNull($provider->get('json'));
    }
}
