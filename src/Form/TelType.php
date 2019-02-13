<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\Form;

use Fazland\ApiPlatformBundle\Form\DataTransformer\PhoneNumberToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType as BaseTelType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * This type requires the giggsey/libphonenumber-for-php library.
 */
class TelType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new PhoneNumberToStringTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return BaseTelType::class;
    }
}
