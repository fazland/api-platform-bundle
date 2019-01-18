<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form;

use Fazland\ApiPlatformBundle\Form\DataTransformer\DateTimeToIso8601Transformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class IsoDateTimeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new DateTimeToIso8601Transformer());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return TextType::class;
    }
}
