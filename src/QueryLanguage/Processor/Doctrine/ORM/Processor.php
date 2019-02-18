<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\ORM;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\Pagination\Doctrine\ORM\PagerIterator;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\OrderExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\DTO\Query;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\QueryType;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Fazland\DoctrineExtra\ORM\EntityIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Processor
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ColumnInterface[]
     */
    private $columns;

    /**
     * @var string
     */
    private $rootAlias;

    /**
     * @var ClassMetadata
     */
    private $rootEntity;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var array
     */
    private $options;

    public function __construct(QueryBuilder $queryBuilder, FormFactoryInterface $formFactory, array $options = [])
    {
        $this->queryBuilder = $queryBuilder;
        $this->entityManager = $this->queryBuilder->getEntityManager();
        $this->columns = [];
        $this->options = $this->resolveOptions($options);

        $this->rootAlias = $this->queryBuilder->getRootAliases()[0];
        $this->rootEntity = $this->entityManager->getClassMetadata($this->queryBuilder->getRootEntities()[0]);
        $this->formFactory = $formFactory;
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

        $column = new Column($options['field_name'], $this->rootAlias, $this->rootEntity, $this->entityManager);

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
     * @param Request $request
     *
     * @return ObjectIteratorInterface|FormInterface
     */
    public function processRequest(Request $request)
    {
        $result = $this->handleRequest($request);
        if ($result instanceof FormInterface) {
            return $result;
        }

        $this->attachToQueryBuilder($result->filters);

        if (null !== $result->skip) {
            $this->queryBuilder->setFirstResult($result->skip);
        }

        if (null !== $result->limit) {
            $this->queryBuilder->setMaxResults($result->limit);
        }

        if (null !== $result->ordering) {
            $iterator = new PagerIterator($this->queryBuilder, $this->parseOrderings($result->ordering));
            $iterator->setToken($result->pageToken);

            return $iterator;
        }

        return new EntityIterator($this->queryBuilder);
    }

    /**
     * @param Request $request
     *
     * @return Query|FormInterface
     */
    private function handleRequest(Request $request)
    {
        $dto = new Query();
        $form = $this->formFactory->createNamed(null, QueryType::class, $dto, [
            'limit_field' => $this->options['limit_field'],
            'skip_field' => $this->options['skip_field'],
            'order_field' => $this->options['order_field'],
            'continuation_token_field' => $this->options['continuation_token']['field'] ?? null,
            'columns' => $this->columns,
            'orderable_columns' => \array_keys(\array_filter($this->columns, function (ColumnInterface $column): bool {
                return $column instanceof Column;
            })),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && ! $form->isValid()) {
            return $form;
        }

        return $dto;
    }

    /**
     * Add conditions to query builder.
     *
     * @param array $filters
     */
    private function attachToQueryBuilder(array $filters): void
    {
        $this->queryBuilder->andWhere('1 = 1');

        foreach ($filters as $key => $expr) {
            $column = $this->columns[$key];
            $column->addCondition($this->queryBuilder, $expr);
        }
    }

    /**
     * Parses the ordering expression for continuation token.
     *
     * @param OrderExpression $ordering
     *
     * @return array
     */
    private function parseOrderings(OrderExpression $ordering): array
    {
        $checksumColumn = $this->rootEntity->getIdentifierColumnNames()[0];
        if (isset($this->options['continuation_token']['checksum_field'])) {
            if (! $this->columns[$checksumColumn] instanceof Column) {
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
     * Resolves options for this processor.
     *
     * @param array $options
     *
     * @return array
     */
    private function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();

        foreach (['order_field', 'skip_field', 'limit_field'] as $field) {
            $resolver
                ->setDefault($field, null)
                ->setAllowedTypes($field, ['null', 'string'])
            ;
        }

        $resolver
            ->setDefault('continuation_token', [
                'field' => 'continue',
                'checksum_field' => null,
            ])
            ->setAllowedTypes('continuation_token', ['bool', 'array'])
            ->setNormalizer('continuation_token', function (Options $options, $value): array {
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
        ;

        return $resolver->resolve($options);
    }
}
