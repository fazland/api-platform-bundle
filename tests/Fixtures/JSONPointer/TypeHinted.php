<?php declare(strict_types=1);

namespace Kcs\ApiPlatformBundle\Tests\Fixtures\JSONPointer;

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

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return \Countable
     */
    public function getCountable()
    {
        return $this->countable;
    }

    /**
     * @param \Countable $countable
     */
    public function setCountable(\Countable $countable)
    {
        $this->countable = $countable;
    }
}
