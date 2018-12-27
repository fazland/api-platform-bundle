<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Form;

use Fazland\ApiPlatformBundle\Form\PageTokenType;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\DTO\Query;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Validator\Expression;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QueryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (null !== $options['order_field']) {
            $builder->add($options['order_field'], FieldType::class, ['property_path' => 'ordering']);
        }

        if (null !== $options['continuation_token_field']) {
            $builder->add($options['continuation_token_field'], PageTokenType::class, ['property_path' => 'pageToken']);
        }

        if (null !== $options['skip_field']) {
            $builder->add($options['skip_field'], IntegerType::class, ['property_path' => 'skip']);
        }

        if (null !== $options['limit_field']) {
            $builder->add($options['limit_field'], IntegerType::class, ['property_path' => 'limit']);
        }

        /** @var ColumnInterface $column */
        foreach ($options['columns'] as $key => $column) {
            $builder->add($key, FieldType::class, [
                'constraints' => [
                    new Expression($column->getValidationWalker()),
                ],
                'property_path' => 'filters['.$key.']',
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => Query::class,
                'skip_field' => null,
                'limit_field' => null,
                'continuation_token_field' => null,
                'order_field' => null,
                'allow_extra_fields' => true,
                'method' => Request::METHOD_GET,
            ])
            ->setAllowedTypes('skip_field', ['null', 'string'])
            ->setAllowedTypes('limit_field', ['null', 'string'])
            ->setAllowedTypes('continuation_token_field', ['null', 'string'])
            ->setAllowedTypes('order_field', ['null', 'string'])
            ->setRequired('columns')
        ;
    }
}
