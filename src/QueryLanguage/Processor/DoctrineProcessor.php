<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\Doctrine\ObjectIterator;
use Fazland\ApiPlatformBundle\Doctrine\ORM\EntityIterator;
use Fazland\ApiPlatformBundle\Form\PageTokenType;
use Fazland\ApiPlatformBundle\Pagination\Doctrine\ORM\PagerIterator;
use Fazland\ApiPlatformBundle\Pagination\Exception\InvalidTokenException;
use Fazland\ApiPlatformBundle\Pagination\Orderings;
use Fazland\ApiPlatformBundle\Pagination\PageToken;
use Fazland\ApiPlatformBundle\QueryLanguage\Exception\SyntaxError;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\OrderExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Grammar\Grammar;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Column\Column;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine\DiscriminatorWalker;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine\DqlWalker;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalkerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class DoctrineProcessor
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
     * @var Column[]
     */
    private $columns;

    /**
     * @var null|string
     */
    private $orderField;

    /**
     * @var null|string
     */
    private $skipField;

    /**
     * @var null|string
     */
    private $limitField;

    /**
     * @var null|string
     */
    private $continuationTokenField;

    /**
     * @var null|string
     */
    private $checksumColumn;

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

    public function __construct(QueryBuilder $queryBuilder, FormFactoryInterface $formFactory)
    {
        $this->queryBuilder = $queryBuilder;
        $this->entityManager = $this->queryBuilder->getEntityManager();
        $this->columns = [];

        $this->rootAlias = $this->queryBuilder->getRootAliases()[0];
        $this->rootEntity = $this->entityManager->getClassMetadata($this->queryBuilder->getRootEntities()[0]);
        $this->formFactory = $formFactory;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function enableOrderField(string $name = 'order'): self
    {
        $this->orderField = $name;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function enableSkipField(string $name = 'skip'): self
    {
        $this->skipField = $name;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function enableLimitField(string $name = 'limit'): self
    {
        $this->limitField = $name;

        return $this;
    }

    /**
     * @param string $name
     * @param string|null $checksumColumn
     *
     * @return $this
     */
    public function enableContinuationToken(string $name = 'continue', ?string $checksumColumn = null): self
    {
        $this->continuationTokenField = $name;
        $this->checksumColumn = $this->rootEntity->getIdentifierColumnNames()[0];

        if (null !== $checksumColumn) {
            $this->checksumColumn = $this->columns[$checksumColumn]->fieldName;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string|null $fieldName
     *
     * @return $this
     */
    public function addColumn(string $name, ?string $fieldName = null): self
    {
        $this->columns[$name] = new Column($name, $fieldName ?? $name, $this->rootEntity, $this->entityManager);

        return $this;
    }

    /**
     * @param string $column
     * @param ValidationWalkerInterface|null $validationWalker
     *
     * @return $this
     */
    public function setValidationWalker(string $column, ?ValidationWalkerInterface $validationWalker): self
    {
        $this->columns[$column]->validationWalker = $validationWalker;

        return $this;
    }

    /**
     * @param string $column
     * @param TreeWalkerInterface|callable|string|null $customWalker
     *
     * @return $this
     */
    public function setCustomWalker(string $column, $customWalker = null): self
    {
        if (null !== $customWalker && ! \is_string($customWalker) && ! \is_callable($customWalker)) {
            throw new \InvalidArgumentException('Custom walker must be either a class name, a callable or null.');
        }

        $this->columns[$column]->customWalker = $customWalker;

        return $this;
    }

    /**
     * @param Request $request
     *
     * @return ObjectIterator|FormInterface
     */
    public function processRequest(Request $request)
    {
        $result = $this->handleRequest($request);
        if ($result instanceof FormInterface) {
            return $result;
        }

        $this->attachToQueryBuilder($result['filters']);

        if (null !== $this->continuationTokenField && null !== $result['ordering']) {
            $iterator = new PagerIterator($this->queryBuilder, $this->parseOrderings($result['ordering']));
            $iterator->setToken($result['page_token']);

            return $iterator;
        }

        return new EntityIterator($this->queryBuilder);
    }

    /**
     * @param Request $request
     *
     * @return array|FormInterface
     */
    private function handleRequest(Request $request)
    {
        $form = $this->createForm()->handleRequest($request);
        if ($form->isSubmitted() && ! $form->isValid()) {
            return $form;
        }

        $ordering = null;
        $grammar = new Grammar();
        $filters = [];
        $formData = $form->getData();

        if (null !== $this->orderField) {
            $ordering = $formData[$this->orderField] ?? null;
            unset($formData[$this->orderField]);

            if (null !== $ordering && ! \is_string($ordering)) {
                $form[$this->orderField]->addError(new FormError('This value is not valid'));
            } elseif (null !== $ordering) {
                try {
                    $ordering = $grammar->parse($ordering);
                } catch (SyntaxError $e) {
                    $form[$this->orderField]->addError(new FormError('This value is not valid'));
                }
            }
        }

        foreach ($formData as $key => $filter) {
            if (null === $filter || '' === $filter) {
                continue;
            }

            if (! \is_string($filter)) {
                $form[$key]->addError(new FormError('This value is not valid'));
                continue;
            }

            try {
                $expression = $grammar->parse($filter);
            } catch (SyntaxError $exception) {
                $form[$key]->addError(new FormError($exception->getMessage()));
                continue;
            }

            if (!isset($this->columns[$key])) {
                continue;
            }

            $column = $this->columns[$key];

            if (null !== $column->validationWalker) {
                try {
                    $expression->dispatch($column->validationWalker);
                } catch (\Throwable $e) {
                    $form[$key]->addError(new FormError($e->getMessage()));
                    continue;
                }
            }

            $filters[$key] = $expression;
        }

        if (null !== $ordering &&
            (! $ordering instanceof OrderExpression || ! \array_key_exists($ordering->getField(), $this->columns))
        ) {
            $form[$this->orderField]->addError(new FormError('This value is not valid'));
        }

        if ($form->isSubmitted() && ! $form->isValid()) {
            return $form;
        }

        return [ 'filters' => $filters, 'ordering' => $ordering, 'page_token' => $form[$this->continuationTokenField]->getData() ];
    }

    private function attachToQueryBuilder(array $filters)
    {
        $this->queryBuilder->andWhere('1 = 1');

        foreach ($filters as $key => $expr) {
            $column = $this->columns[$key];
            $alias = $column->discriminator ? null : $column->getMappingFieldName();
            $walker = $column->customWalker;

            if ($column->isAssociation()) {
                $subQb = $this->entityManager->createQueryBuilder()
                    ->select('1')
                    ->from($column->getTargetEntity(), $alias)
                    ->setParameters($this->queryBuilder->getParameters())
                ;

                $currentFieldName = $alias;
                $currentAlias = $alias;
                foreach ($column->associations as $association) {
                    if (isset($association['targetEntity'])) {
                        $currentAlias = $association['fieldName'];
                        $currentFieldName = $association['fieldName'];
                        $subQb->join($currentFieldName.'.'.$association['fieldName'], $association['fieldName']);
                    } else {
                        $currentFieldName = $currentAlias . '.' . $association['fieldName'];
                    }
                }

                if (null !== $walker) {
                    $walker = \is_string($walker) ? new $walker($subQb, $currentFieldName) : $walker($subQb, $currentFieldName);
                } else {
                    $walker = new DqlWalker($subQb, $currentFieldName);
                }

                $subQb->where($expr->dispatch($walker));

                if ($column->isManyToMany()) {
                    // Many-to-Many
                    dd($column);
                } elseif ($column->isOwningSide()) {
                    $subQb->andWhere($subQb->expr()->eq($this->rootAlias.'.'.$alias, $alias));
                } else {
                    $subQb->andWhere($subQb->expr()->eq($alias.'.'.$column->mapping['inversedBy'], $this->rootAlias));
                }

                $this->queryBuilder
                    ->andWhere($this->queryBuilder->expr()->exists($subQb->getDQL()))
                    ->setParameters($subQb->getParameters())
                ;
            } else {
                $fieldName = $column->discriminator ? $this->rootAlias : $this->rootAlias.'.'.$alias;
                if (null !== $walker) {
                    $walker = \is_string($walker) ? new $walker($this->queryBuilder, $fieldName) : $walker($this->queryBuilder, $fieldName);
                } else {
                    $walker = new DqlWalker($this->queryBuilder, $fieldName);
                }

                $this->queryBuilder->andWhere($expr->dispatch($walker));
            }
        }
    }

    private function parseOrderings(OrderExpression $ordering): array
    {
        $fieldName = $this->columns[$ordering->getField()]->fieldName;
        $direction = $ordering->getDirection();

        return [
            $fieldName => $direction,
            $this->checksumColumn => 'ASC',
        ];
    }

    private function createForm(): FormInterface
    {
        $builder = $this->formFactory->createNamedBuilder(null, FormType::class, [], [
            'allow_extra_fields' => true,
            'method' => Request::METHOD_GET,
        ]);

        foreach ($this->columns as $key => $column) {
            $builder->add($key, TextType::class);
        }

        if (null !== $this->orderField) {
            $builder->add($this->orderField, TextType::class);
        }

        if (null !== $this->skipField) {
            $builder->add($this->skipField, IntegerType::class);
        }

        if (null !== $this->limitField) {
            $builder->add($this->limitField, IntegerType::class);
        }

        if (null !== $this->continuationTokenField) {
            $builder->add($this->continuationTokenField, PageTokenType::class);
        }

        return $builder->getForm();
    }
}
