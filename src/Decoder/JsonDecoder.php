<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Decoder;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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

        try {
            $content = self::doDecode($content);
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Invalid request body', $e);
        }

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

    /**
     * Decodes a json string. Throw an exception if json is not valid.
     *
     * @param string $json
     *
     * @return array
     *
     * @throws \Exception
     */
    private static function doDecode(string $json): array
    {
        $returnValue = @\json_decode($json, true);

        if (null === $returnValue && JSON_ERROR_NONE !== \json_last_error()) {
            throw new \Exception('Cannot decode JSON: '.\json_last_error_msg());
        }

        return $returnValue;
    }
}
