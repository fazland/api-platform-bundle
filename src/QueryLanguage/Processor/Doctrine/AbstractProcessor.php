<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine;

use Fazland\ApiPlatformBundle\QueryLanguage\Expression\OrderExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\DTO\Query;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\QueryType;
use Fazland\ApiPlatformBundle\QueryLanguage\Grammar\Grammar;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\ORM\Column as ORMColumn;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\PhpCr\Column as PhpCrColumn;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalkerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractProcessor
{
    /**
     * @var ColumnInterface[]
     */
    protected array $columns;
    protected array $options;
    private FormFactoryInterface $formFactory;

    public function __construct(FormFactoryInterface $formFactory, array $options = [])
    {
        $this->options = $this->resolveOptions($options);
        $this->columns = [];
        $this->formFactory = $formFactory;
    }

    /**
     * Sets the default page size (will be used if limit is disabled or not passed by the user).
     *
     * @param int|null $size
     */
    public function setDefaultPageSize(?int $size): void
    {
        $this->options['default_page_size'] = $size;
    }

    /**
     * Adds a column to this list processor.
     *
     * @param string                $name
     * @param array|ColumnInterface $options
     *
     * @return $this
     */
    public function addColumn(string $name, $options = []): self
    {
        if ($options instanceof ColumnInterface) {
            $this->columns[$name] = $options;

            return $this;
        }

        $resolver = new OptionsResolver();
        $options = $resolver
            ->setDefaults([
                'field_name' => $name,
                'walker' => null,
                'validation_walker' => null,
            ])
            ->setAllowedTypes('field_name', 'string')
            ->setAllowedTypes('walker', ['null', 'string', 'callable'])
            ->setAllowedTypes('validation_walker', ['null', 'string', 'callable'])
            ->resolve($options)
        ;

        $column = $this->createColumn($options['field_name']);

        if (null !== $options['walker']) {
            $column->customWalker = $options['walker'];
        }

        if (null !== $options['validation_walker']) {
            $column->validationWalker = $options['validation_walker'];
        }

        $this->columns[$name] = $column;

        return $this;
    }

    /**
     * Binds and validates the request to the internal Query object.
     *
     * @param Request $request
     *
     * @return Query|FormInterface
     */
    protected function handleRequest(Request $request)
    {
        $options = [
            'limit_field' => $this->options['limit_field'],
            'skip_field' => $this->options['skip_field'],
            'order_field' => $this->options['order_field'],
            'default_order' => $this->options['default_order'],
            'continuation_token_field' => $this->options['continuation_token']['field'] ?? null,
            'columns' => $this->columns,
            'orderable_columns' => \array_keys(\array_filter($this->columns, static function (ColumnInterface $column): bool {
                return $column instanceof PhpCrColumn || $column instanceof ORMColumn;
            })),
        ];

        if (null !== $this->options['order_validation_walker']) {
            $options['order_validation_walker'] = $this->options['order_validation_walker'];
        }

        $form = $this->formFactory->createNamed('', QueryType::class, $dto = new Query(), $options);
        $form->handleRequest($request);
        if ($form->isSubmitted() && ! $form->isValid()) {
            return $form;
        }

        return $dto;
    }

    /**
     * Creates a Column instance.
     *
     * @param string $fieldName
     *
     * @return ColumnInterface
     */
    abstract protected function createColumn(string $fieldName): ColumnInterface;

    /**
     * Gets the identifier field names from doctrine metadata.
     *
     * @return string[]
     */
    abstract protected function getIdentifierFieldNames(): array;

    /**
     * Parses the ordering expression for continuation token.
     *
     * @param OrderExpression $ordering
     *
     * @return array
     */
    protected function parseOrderings(OrderExpression $ordering): array
    {
        $checksumColumn = $this->getIdentifierFieldNames()[0];
        if (isset($this->options['continuation_token']['checksum_field'])) {
            $checksumColumn = $this->options['continuation_token']['checksum_field'];
            if (! $this->columns[$checksumColumn] instanceof PhpCrColumn && ! $this->columns[$checksumColumn] instanceof ORMColumn) {
                throw new \InvalidArgumentException(\sprintf('%s is not a valid field for checksum', $this->options['continuation_token']['checksum_field']));
            }

            $checksumColumn = $this->columns[$checksumColumn]->fieldName;
        }

        $fieldName = $this->columns[$ordering->getField()]->fieldName;
        $direction = $ordering->getDirection();

        return [
            $fieldName => $direction,
            $checksumColumn => 'ASC',
        ];
    }

    /**
     * Allow to deeply configure the options resolver.
     *
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        // Do nothing.
    }

    /**
     * Resolves options for this processor.
     *
     * @param array $options
     *
     * @return array
     */
    private function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();

        foreach (['order_field', 'skip_field', 'limit_field', 'default_order'] as $field) {
            $resolver
                ->setDefault($field, null)
                ->setAllowedTypes($field, ['null', 'string'])
            ;
        }

        $resolver
            ->setDefault('default_page_size', null)
            ->setAllowedTypes('default_page_size', ['null', 'int'])
            ->setDefault('order_validation_walker', null)
            ->setAllowedTypes('order_validation_walker', ['null', ValidationWalkerInterface::class])
            ->setDefault('continuation_token', [
                'field' => 'continue',
                'checksum_field' => null,
            ])
            ->setAllowedTypes('continuation_token', ['bool', 'array'])
            ->setNormalizer('continuation_token', static function (Options $options, $value): array {
                if (true === $value) {
                    return [
                        'field' => 'continue',
                        'checksum_field' => null,
                    ];
                }

                if (! isset($value['field'])) {
                    throw new InvalidOptionsException('Continuation token field must be set');
                }

                return $value;
            })
            ->setNormalizer('default_order', static function (Options $options, $value): ?OrderExpression {
                if (empty($value)) {
                    return null;
                }

                if (false === \strpos($value, '$')) {
                    $value = '$order('.$value.')';
                }

                $grammar = Grammar::getInstance();
                $expression = $grammar->parse($value);

                if (! $expression instanceof OrderExpression) {
                    throw new InvalidOptionsException('Invalid default order specified');
                }

                return $expression;
            })
        ;

        $this->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
