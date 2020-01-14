<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class TypeHinted
{
    private $date;
    private ?\Countable $countable;

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function getCountable(): ?\Countable
    {
        return $this->countable;
    }

    public function setCountable(\Countable $countable): void
    {
        $this->countable = $countable;
    }
}
