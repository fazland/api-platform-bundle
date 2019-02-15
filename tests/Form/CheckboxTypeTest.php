<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Tests\Form;

use Fazland\ApiPlatformBundle\Form\CheckboxType;
use Fazland\ApiPlatformBundle\Form\DataTransformer\BooleanTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType as BaseType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckboxTypeTest extends TestCase
{
    /**
     * @var CheckboxType
     */
    private $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->type = new CheckboxType();
    }

    public function testGetParentShouldReturnBaseCollectionTypeFullyQualifiedClassName(): void
    {
        self::assertEquals(BaseType::class, $this->type->getParent());
    }

    public function testConfigureOptionsShouldConfigureDefaults(): void
    {
        $resolver = $this->prophesize(OptionsResolver::class);
        $resolver
            ->setDefault('false_values', BooleanTransformer::FALSE_VALUES)
            ->shouldBeCalled()
        ;

        $this->type->configureOptions($resolver->reveal());
    }
}
