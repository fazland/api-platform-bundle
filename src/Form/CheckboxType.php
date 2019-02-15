<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form;

use Fazland\ApiPlatformBundle\Form\DataTransformer\BooleanTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType as BaseType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckboxType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('false_values', BooleanTransformer::FALSE_VALUES);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return BaseType::class;
    }
}
