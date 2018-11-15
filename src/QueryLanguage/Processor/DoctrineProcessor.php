<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\Doctrine\ORM\EntityIterator;
use Fazland\ApiPlatformBundle\QueryLanguage\Exception\SyntaxError;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\OrderExpression;
use Fazland\ApiPlatformBundle\QueryLanguage\Grammar\Grammar;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\Column\Column;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine\DqlWalker;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalkerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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

    public function setOrderField(string $name)
    {
        $this->orderField = $name;

        return $this;
    }

    public function addColumn(string $name, ?string $fieldName = null): self
    {
        $this->columns[$name] = new Column($name, $fieldName ?? $name, $this->rootEntity, $this->entityManager);

        return $this;
    }

    public function setValidationWalker(string $column, ?ValidationWalkerInterface $validationWalker): self
    {
        $this->columns[$column]->validationWalker = $validationWalker;

        return $this;
    }

    public function setCustomWalker(string $column, $customWalker = null): self
    {
        if (null !== $customWalker && ! is_string($customWalker) && ! is_callable($customWalker)) {
            throw new \InvalidArgumentException('Custom walker must be either a class name, a callable or null.');
        }

        $this->columns[$column]->customWalker = $customWalker;

        return $this;
    }

    public function processRequest(Request $request)
    {
        $result = $this->handleRequest($request);
        if ($result instanceof FormInterface) {
            return $result;
        }

        $this->attachToQueryBuilder($result);
        return new EntityIterator($this->queryBuilder);
    }

    private function handleRequest(Request $request)
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

        $form = $builder->getForm()->handleRequest($request);
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

            if (null !== $ordering && ! is_string($ordering)) {
                $form[$this->orderField]->addError(new FormError('This value is not valid', null, [], null, null));
            } elseif (null !== $ordering) {
                try {
                    $ordering = $grammar->parse($ordering);
                } catch (SyntaxError $e) {
                    $form[$this->orderField]->addError(new FormError('This value is not valid', null, [], null, null));
                }
            }
        }


        foreach ($formData as $key => $filter) {
            if (null === $filter || '' === $filter) {
                continue;
            }

            if (! is_string($filter)) {
                $form[$key]->addError(new FormError('This value is not valid', null, [], null, null));
                continue;
            }

            try {
                $expression = $grammar->parse($filter);
            } catch (SyntaxError $exception) {
                $form[$key]->addError(new FormError($exception->getMessage(), null, [], null, null));
                continue;
            }

            $column = $this->columns[$key];
            if (null !== $column->validationWalker) {
                try {
                    $expression->dispatch($column->validationWalker);
                } catch (\Throwable $e) {
                    $form[$key]->addError(new FormError($e->getMessage(), null, [], null, null));
                    continue;
                }
            }

            $filters[$key] = $expression;
        }

        if (null !== $ordering &&
            (! $ordering instanceof OrderExpression || ! in_array($ordering->getField(), array_keys($this->columns)))
        ) {
            $form[$this->orderField]->addError(new FormError('This value is not valid', null, [], null, null));
        }

        if ($form->isSubmitted() && ! $form->isValid()) {
            return $form;
        }

        return [ $filters, $ordering ];
    }

    private function attachToQueryBuilder(array $queryData)
    {
        /** @var OrderExpression $ordering */
        [$filters, $ordering] = $queryData;

        foreach ($filters as $key => $expr) {
            $column = $this->columns[$key];
            $alias = $column->getMappingFieldName();
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
                    $walker = is_string($walker) ? new $walker($subQb, $currentFieldName) : $walker($subQb, $currentFieldName);
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
                $fieldName = $this->rootAlias.'.'.$alias;
                if (null !== $walker) {
                    $walker = is_string($walker) ? new $walker($this->queryBuilder, $fieldName) : $walker($this->queryBuilder, $fieldName);
                } else {
                    $walker = new DqlWalker($this->queryBuilder, $fieldName);
                }

                $this->queryBuilder->andWhere($expr->dispatch($walker));
            }
        }

        if (null !== $ordering) {
            $this->queryBuilder->orderBy(
                $this->rootAlias.'.'.$this->columns[$ordering->getField()]->fieldName,
                $ordering->getDirection()
            );
        }
    }
}
