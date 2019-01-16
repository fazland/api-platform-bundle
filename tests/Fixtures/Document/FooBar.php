<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document()
 */
class FooBar
{
    /**
     * @var string
     *
     * @ODM\Id()
     */
    public $id;

    /**
     * @var mixed
     */
    public $prop;
}
