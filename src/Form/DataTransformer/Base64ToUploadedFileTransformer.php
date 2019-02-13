<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form\DataTransformer;

use Fazland\ApiPlatformBundle\HttpFoundation\SyntheticUploadedFile;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Transforms a base64-encoded file to an object instance of UploadedFile.
 */
class Base64ToUploadedFileTransformer implements DataTransformerInterface
{
    private const DATA_URI_PATTERN = '/^data:([a-z0-9][a-z0-9\!\#\$\&\-\^\_\+\.]{0,126}\/[a-z0-9][a-z0-9\!\#\$\&\-\^\_\+\.]{0,126})((?:;[a-z0-9\-]+=[^\/\\\?\*:\|\"<>;=]+)*?)?(;base64)?,([a-z0-9\!\$\&\\\'\,\(\)\*\+\,\;\=\-\.\_\~\:\@\/\?\%\s]*\s*)$/i';

    /**
     * {@inheritdoc}
     */
    public function transform($value): string
    {
        if (empty($value)) {
            return '';
        }

        if (! $value instanceof File) {
            throw new TransformationFailedException('Cannot encode '.\get_class($value).'. Expected File.');
        }

        if (null === $value->getMimeType()) {
            throw new TransformationFailedException('Unable to guess file MIME-Type.');
        }

        $filename = $value instanceof UploadedFile ? $value->getClientOriginalName() : $value->getFilename();

        return \sprintf('data:%s;%sbase64,%s',
            $value->getMimeType(),
            null !== $filename ? 'filename='.\urlencode($filename).';' : '',
            \base64_encode(\file_get_contents($value->getPathname()))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?File
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof File) {
            return $value;
        }

        if (! \is_string($value)) {
            throw new TransformationFailedException('Cannot transform a non-string value to an instance of UploadedFile.');
        }

        if (! \preg_match(self::DATA_URI_PATTERN, $value, $matches)) {
            throw new TransformationFailedException('Invalid data: URI provided');
        }

        [, $mime, $attributes, $base64, $data] = $matches;

        if (! empty($attributes)) {
            $attributes = \array_filter(\array_map(function ($value) {
                return \array_map('urldecode', \explode('=', $value));
            }, \explode(';', $attributes)));

            $attributes = \array_column($attributes, 1, 0);
        } else {
            $attributes = [];
        }

        if (! empty($base64)) {
            $data = $decoded = @\base64_decode($data, true);
            if (false === $decoded) {
                throw new TransformationFailedException("Cannot decode $base64 to string.", 0);
            }
        } else {
            $data = \urldecode($data);
        }

        return new SyntheticUploadedFile($data, $attributes['filename'] ?? null, $mime);
    }
}
