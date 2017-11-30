<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Decoder;

class JsonDecoder implements DecoderInterface
{
    /**
     * {@inheritdoc}
     */
    public function decode(string $content): array
    {
        if (empty($content)) {
            return [];
        }

        $content = @json_decode($content, true);

        array_walk_recursive($content, function (&$value) {
            if (false === $value) {
                $value = null;
            } elseif (! is_string($value)) {
                $value = strval($value);
            }
        });

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public static function getFormat(): string
    {
        return 'json';
    }
}
