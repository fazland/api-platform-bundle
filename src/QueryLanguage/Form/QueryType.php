<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Form;

use Fazland\ApiPlatformBundle\Form\PageTokenType;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\OrderExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\DTO\Query;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Validator\Expression;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\OrderWalker;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalkerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class QueryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (null !== $options['order_field']) {
            $builder->add($options['order_field'], FieldType::class, [
                'data_class' => null,
                'property_path' => 'ordering',
                'constraints' => [
                    new Expression($options['order_validation_walker']),
                ],
            ]);

            if (isset($options['default_order'])) {
                $default = $options['default_order'];
                $field = $options['order_field'];
                $builder->addEventListener(
                    FormEvents::PRE_SUBMIT,
                    static function (FormEvent $event) use ($default, $field): void {
                        $data = $event->getData();
                        if (isset($data[$field])) {
                            return;
                        }

                        $data[$field] = $default;
                        $event->setData($data);
                    }
                );
            }
        }

        if (null !== $options['continuation_token_field']) {
            $builder->add($options['continuation_token_field'], PageTokenType::class, ['property_path' => 'pageToken']);
        }

        if (null !== $options['skip_field']) {
            $builder->add($options['skip_field'], IntegerType::class, [
                'property_path' => 'skip',
                'constraints' => [new Range(['min' => 0])],
            ]);
        }

        if (null !== $options['limit_field']) {
            $builder->add($options['limit_field'], IntegerType::class, [
                'property_path' => 'limit',
                'constraints' => [new Range(['min' => 0])],
            ]);
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
                'default_order' => null,
                'allow_extra_fields' => true,
                'method' => Request::METHOD_GET,
                'orderable_columns' => static fn (Options $options) => \array_keys($options['columns']),
                'order_validation_walker' => static fn (Options $options) => new OrderWalker($options['orderable_columns']),
            ])
            ->setAllowedTypes('skip_field', ['null', 'string'])
            ->setAllowedTypes('limit_field', ['null', 'string'])
            ->setAllowedTypes('continuation_token_field', ['null', 'string'])
            ->setAllowedTypes('order_field', ['null', 'string'])
            ->setAllowedTypes('default_order', ['null', OrderExpression::class])
            ->setAllowedTypes('order_validation_walker', ['null', ValidationWalkerInterface::class])
            ->setRequired('columns')
        ;
    }
}
