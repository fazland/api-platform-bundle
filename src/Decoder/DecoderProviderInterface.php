<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Decoder;

use Kcs\ApiPlatformBundle\Decoder\Exception\UnsupportedFormatException;

interface DecoderProviderInterface
{
    /**
     * Get a corresponding decoder from format.
     *
     * @param string $format
     *
     * @return DecoderInterface
     *
     * @throws UnsupportedFormatException
     */
    public function get(string $format): DecoderInterface;

    /**
     * Check if there's a decoder supporting the format.
     *
     * @param string $format
     *
     * @return bool
     */
    public function supports(string $format): bool;
}
