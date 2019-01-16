<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Decoder\Exception;

class InvalidJSONException extends \RuntimeException
{
    /**
     * @var string
     */
    private $invalidJson;

    public function __construct(string $invalidJson, string $error)
    {
        parent::__construct("Cannot decode JSON: $error");

        $this->invalidJson = $invalidJson;
    }

    /**
     * Gets the invalid json.
     *
     * @return string
     */
    public function getInvalidJson(): string
    {
        return $this->invalidJson;
    }
}
