<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\HttpFoundation;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SyntheticUploadedFile extends UploadedFile
{
    /**
     * SyntheticUploadedFile constructor.
     *
     * @param string $contents
     * @param string|null $originalName
     * @param string|null $mimeType
     * @param int|null $error
     */
    public function __construct(
        string $contents,
        ?string $originalName = null,
        ?string $mimeType = null,
        ?int $error = null
    ) {
        $tempPath = \tempnam(\sys_get_temp_dir(), 'synt_uploaded_file');
        \file_put_contents($tempPath, $contents);

        parent::__construct($tempPath, $originalName ?? 'up_'.\mt_rand(), $mimeType, $error, false);
    }

    public function __destruct()
    {
        if (\file_exists($this->getPathname())) {
            @\unlink($this->getPathname());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function move($directory, $name = null): File
    {
        if ($this->isValid()) {
            $target = $this->getTargetFile($directory, $name);

            if (! @\rename($this->getPathname(), $target)) {
                $error = \error_get_last();
                throw new FileException(\sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, \strip_tags($error['message'])));
            }

            @\chmod($target, 0666 & ~\umask());

            return $target;
        }

        throw new FileException($this->getErrorMessage());
    }
}
