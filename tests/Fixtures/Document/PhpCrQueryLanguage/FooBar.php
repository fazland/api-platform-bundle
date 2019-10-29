<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Document\PhpCrQueryLanguage;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

/**
 * @PHPCR\Document(referenceable=true)
 */
class FooBar
{
    /**
     * @var string
     *
     * @PHPCR\Id(strategy="ASSIGNED")
     */
    public $id;

    /**
     * @var string
     *
     * @PHPCR\Uuid()
     */
    public $uuid;

    /**
     * @var string
     *
     * @PHPCR\Field()
     */
    public $foobar = 'foobar';
}
