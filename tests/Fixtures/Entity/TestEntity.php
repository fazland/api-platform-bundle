<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class TestEntity
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id()
     */
    public $id;
}
