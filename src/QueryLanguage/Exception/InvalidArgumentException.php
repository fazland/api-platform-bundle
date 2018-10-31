<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Exception;

final class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param mixed $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }
}
