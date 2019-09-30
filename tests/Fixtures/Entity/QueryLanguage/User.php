<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\QueryLanguage;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class User
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column()
     */
    public $name;

    /**
     * @var FooBar
     *
     * @ORM\ManyToOne(targetEntity=FooBar::class, cascade={"persist", "remove"})
     */
    public $foobar;

    /**
     * @var int
     *
     * @ORM\Column()
     */
    public $nameLength;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->nameLength = \strlen($name);

        $this->foobar = new FooBar();
        $this->foobar->foobar .= '_'.$name;
    }
}
