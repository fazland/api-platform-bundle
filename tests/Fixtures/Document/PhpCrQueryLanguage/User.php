<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Fixtures\Document\PhpCrQueryLanguage;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCR;

/**
 * @PHPCR\Document(referenceable=true)
 */
class User
{
    /**
     * @PHPCR\Id(strategy="PARENT")
     */
    public ?string $id;

    /**
     * @var mixed
     *
     * @PHPCR\ParentDocument()
     */
    private $parent;

    /**
     * @PHPCR\Nodename()
     */
    public string $name;

    /**
     * @PHPCR\ReferenceOne(targetDocument=FooBar::class, strategy="hard", cascade={"persist", "remove"})
     */
    public FooBar $foobar;

    /**
     * @PHPCR\Field(type="int")
     */
    public int $nameLength;

    public function __construct(string $name, $parent = null)
    {
        $this->id = null;
        $this->parent = null;
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
