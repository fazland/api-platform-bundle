<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Exception;

final class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
