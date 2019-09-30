<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Entity\QueryLanguage;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="ql_foobar")
 */
class FooBar
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
    public $foobar = 'foobar';
}
