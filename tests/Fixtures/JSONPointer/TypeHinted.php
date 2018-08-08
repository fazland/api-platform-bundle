<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\JSONPointer;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class TypeHinted
{
    private $date;

    /**
     * @var \Countable
     */
    private $countable;

    public function setDate(\DateTimeInterface $date)
    {
        $this->date = $date;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return \Countable
     */
    public function getCountable(): \Countable
    {
        return $this->countable;
    }

    /**
     * @param \Countable $countable
     */
    public function setCountable(\Countable $countable): void
    {
        $this->countable = $countable;
    }
}
