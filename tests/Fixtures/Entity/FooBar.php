<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class FooBar
{
    /**
     * @var int
     *
     * @ORM\Column()
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $id;

    public $prop;
}
