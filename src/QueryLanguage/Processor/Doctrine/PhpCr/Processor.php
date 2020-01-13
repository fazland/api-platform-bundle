<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\PhpCr;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\From;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Builder\SourceDocument;
use Fazland\ApiPlatformBundle\Pagination\Doctrine\PhpCr\PagerIterator;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\OrderExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\DTO\Query;
use Fazland\ApiPlatformBundle\QueryLanguage\Form\QueryType;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\DoctrineExtra\ObjectIteratorInterface;
use Fazland\DoctrineExtra\ODM\PhpCr\DocumentIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Processor
{
    private QueryBuilder $queryBuilder;

    private DocumentManagerInterface $documentManager;

    /**
     * @var ColumnInterface[]
     */
    private array $columns;

    private FormFactoryInterface $formFactory;

    private array $options;

    private ClassMetadata $rootDocument;

    private string $rootAlias;

    public function __construct(
        QueryBuilder $queryBuilder,
        DocumentManagerInterface $documentManager,
        FormFactoryInterface $formFactory,
        array $options = []
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->documentManager = $documentManager;
        $this->columns = [];
        $this->options = $this->resolveOptions($options);

        /** @var From $fromNode */
        $fromNode = $this->queryBuilder->getChildOfType(AbstractNode::NT_FROM);
        /** @var SourceDocument $sourceNode */
        $sourceNode = $fromNode->getChildOfType(AbstractNode::NT_SOURCE);

        $this->rootDocument = $this->documentManager->getClassMetadata($sourceNode->getDocumentFqn());
        $this->rootAlias = $sourceNode->getAlias();
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

        $column = new Column($options['field_name'], $this->rootAlias, $this->rootDocument, $this->documentManager);

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

        return new DocumentIterator($this->queryBuilder);
    }

    /**
     * @param Request $request
     *
     * @return Query|FormInterface
     */
    private function handleRequest(Request $request)
    {
        $dto = new Query();
        $form = $this->formFactory->createNamed('', QueryType::class, $dto, [
            'limit_field' => $this->options['limit_field'],
            'skip_field' => $this->options['skip_field'],
            'order_field' => $this->options['order_field'],
            'continuation_token_field' => $this->options['continuation_token']['field'] ?? null,
            'columns' => $this->columns,
            'orderable_columns' => \array_keys(\array_filter($this->columns, static function (ColumnInterface $column): bool {
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
        $checksumColumn = $this->rootDocument->getIdentifierFieldNames()[0];
        if (isset($this->options['continuation_token']['checksum_field'])) {
            $checksumColumn = $this->options['continuation_token']['checksum_field'];
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
        ;

        return $resolver->resolve($options);
    }
}
