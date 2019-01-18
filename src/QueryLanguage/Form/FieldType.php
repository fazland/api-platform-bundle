<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Form;

use Fazland\ApiPlatformBundle\QueryLanguage\Form\EventListener\SyntaxErrorTransformationFailureListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FieldType extends AbstractType
{
    /**
     * @var TranslatorInterface|null
     */
    private $translator;

    public function __construct(?TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventSubscriber(new SyntaxErrorTransformationFailureListener($this->translator))
            ->addViewTransformer(new DataTransformer\StringToExpresionTransformer())
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return TextType::class;
    }
}
