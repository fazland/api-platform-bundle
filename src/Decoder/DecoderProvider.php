<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Decoder;

use Fazland\ApiPlatformBundle\Decoder\Exception\UnsupportedFormatException;

class DecoderProvider implements DecoderProviderInterface
{
    /**
     * @var array
     */
    private $decoders;

    public function __construct(array $decoders)
    {
        $this->decoders = $decoders;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $format): DecoderInterface
    {
        if (! isset($this->decoders[$format])) {
            throw new UnsupportedFormatException("Format $format is not supported");
        }

        return $this->decoders[$format];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $format): bool
    {
        return isset($this->decoders[$format]);
    }
}
