<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Decoder;

interface DecoderInterface
{
    /**
     * Decode a request content and make a application/x-www-form-encoded compliant values.
     *
     * @param string $content
     *
     * @return array
     */
    public function decode(string $content): array;

    /**
     * Get the format supported by this decoder.
     *
     * @return string
     */
    public static function getFormat(): string;
}
