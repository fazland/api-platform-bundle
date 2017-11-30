<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Negotiation;

use Negotiation\AcceptHeader;
use Negotiation\BaseAccept;
use Negotiation\Exception\InvalidMediaType;

class Priority extends BaseAccept implements AcceptHeader
{
    private $basePart;

    private $subPart;

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
    public function getSubPart()
    {
        return $this->subPart;
    }

    /**
     * @return string
     */
    public function getBasePart()
    {
        return $this->basePart;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }
}
