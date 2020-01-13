<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class FooBar
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public ?int $id;

    /**
     * @var mixed
     */
    public $prop;
}
