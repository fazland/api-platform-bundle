<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Document\PhpCrQueryLanguage;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

/**
 * @PHPCR\Document(referenceable=true)
 */
class User
{
    /**
     * @var string
     *
     * @PHPCR\Id(strategy="PARENT")
     */
    public $id;

    /**
     * @var mixed
     *
     * @PHPCR\ParentDocument()
     */
    private $parent;

    /**
     * @var string
     *
     * @PHPCR\Nodename()
     */
    public $name;

    /**
     * @var FooBar
     *
     * @PHPCR\ReferenceOne(targetDocument=FooBar::class, strategy="hard", cascade={"persist", "remove"})
     */
    public $foobar;

    /**
     * @var int
     *
     * @PHPCR\Field(type="int")
     */
    public $nameLength;

    public function __construct(string $name, $parent = null)
    {
        $this->name = $name;
        $this->nameLength = \strlen($name);
        if (null === $parent) {
            $this->id = '/'.$name;
        } else {
            $this->parent = $parent;
        }

        $this->foobar = new FooBar();
        $this->foobar->id = '/'.$name.'_'.\mt_rand();
        $this->foobar->foobar .= '_'.$name;
    }
}
