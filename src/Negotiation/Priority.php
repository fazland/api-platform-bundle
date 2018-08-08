<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Negotiation;

use Negotiation\AcceptHeader;
use Negotiation\BaseAccept;
use Negotiation\Exception\InvalidMediaType;

class Priority extends BaseAccept implements AcceptHeader
{
    /**
     * @var string
     */
    private $basePart;

    /**
     * @var string
     */
    private $subPart;

    /**
     * @var string|null
     */
    private $version;

    public function __construct($value)
    {
        parent::__construct($value);
        $parts = explode('/', $this->type);

        if (2 !== count($parts) || ! $parts[0] || ! $parts[1]) {
            throw new InvalidMediaType();
        }

        $this->basePart = $parts[0];
        $this->subPart = $parts[1];
    }

    /**
     * @return string
     */
    public function getBasePart(): string
    {
        return $this->basePart;
    }

    /**
     * @return string
     */
    public function getSubPart(): string
    {
        return $this->subPart;
    }

    /**
     * @param string $version
     */
    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }
}
