<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Fazland\ApiPlatformBundle\QueryLanguage\Exception\Doctrine\FieldNotFoundException;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine\DiscriminatorWalker;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine\DqlWalker;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\EnumWalker;

/**
 * @internal
 */
class Column implements ColumnInterface
{
    /**
     * @var string
     */
    private $rootAlias;

    /**
     * @var string
     */
    public $fieldName;

    /**
     * @var array
     */
    public $mapping;

    /**
     * @var array
     */
    public $associations;

    /**
     * @var null|string|callable
     */
    public $validationWalker;

    /**
     * @var null|string|callable
     */
    public $customWalker;
    /**
     * @var bool
     */
    public $discriminator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        string $fieldName,
        string $rootAlias,
        ClassMetadata $rootEntity,
        EntityManagerInterface $entityManager
    ) {
        $this->fieldName = $fieldName;
        $this->rootAlias = $rootAlias;

        [$rootField, $rest] = MappingHelper::processFieldName($rootEntity, $fieldName);

        if (null === $rootField) {
            $this->searchForDiscriminator($rootEntity, $fieldName);
        }

        $this->mapping = $rootField;
        $this->associations = [];

        if (null !== $rest) {
            $this->processAssociations($entityManager, $rest);
        }
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function addCondition($queryBuilder, ExpressionInterface $expression): void
    {
        if (! $this->isAssociation()) {
            $this->addWhereCondition($queryBuilder, $expression);
        } else {
            $this->addAssociationCondition($queryBuilder, $expression);
        }
    }

    /**
     * Adds a simple condition to the query builder.
     *
     * @param QueryBuilder        $queryBuilder
     * @param ExpressionInterface $expression
     */
    private function addWhereCondition(QueryBuilder $queryBuilder, ExpressionInterface $expression): void
    {
        $alias = $this->discriminator ? null : $this->getMappingFieldName();
        $walker = $this->customWalker;

        $fieldName = $this->discriminator ? $this->rootAlias : $this->rootAlias.'.'.$alias;
        if (null !== $walker) {
            $walker = \is_string($walker) ? new $walker($queryBuilder, $fieldName) : $walker($queryBuilder, $fieldName);
        } else {
            $walker = new DqlWalker($queryBuilder, $fieldName);
        }

        $queryBuilder->andWhere($expression->dispatch($walker));
    }

    /**
     * Processes an association column and attaches the conditions to the query builder.
     *
     * @param QueryBuilder        $queryBuilder
     * @param ExpressionInterface $expression
     */
    private function addAssociationCondition(QueryBuilder $queryBuilder, ExpressionInterface $expression): void
    {
        $alias = $this->getMappingFieldName();
        $walker = $this->customWalker;

        $subQb = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from($this->getTargetEntity(), $alias)
            ->setParameters($queryBuilder->getParameters())
        ;

        $currentFieldName = $alias;
        $currentAlias = $alias;
        foreach ($this->associations as $association) {
            if (isset($association['targetEntity'])) {
                $subQb->join($currentFieldName.'.'.$association['fieldName'], $association['fieldName']);
                $currentAlias = $association['fieldName'];
                $currentFieldName = $association['fieldName'];
            } else {
                $currentFieldName = $currentAlias.'.'.$association['fieldName'];
            }
        }

        if (null !== $walker) {
            $walker = \is_string($walker) ? new $walker($subQb, $currentFieldName) : $walker($subQb, $currentFieldName);
        } else {
            $walker = new DqlWalker($subQb, $currentFieldName);
        }

        $subQb->where($expression->dispatch($walker));

        if ($this->isManyToMany()) {
            $queryBuilder
                ->distinct()
                ->join($this->rootAlias.'.'.$alias, $alias, Join::WITH, $subQb->getDQLPart('where'));
        } else {
            if ($this->isOwningSide()) {
                $subQb->andWhere($subQb->expr()->eq($this->rootAlias.'.'.$alias, $alias));
            } else {
                $subQb->andWhere($subQb->expr()->eq($alias.'.'.$this->mapping['inversedBy'], $this->rootAlias));
            }

            $queryBuilder
                ->andWhere($queryBuilder->expr()->exists($subQb->getDQL()))
                ->setParameters($subQb->getParameters())
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationWalker()
    {
        return $this->validationWalker;
    }

    /**
     * Gets the mapping field name.
     *
     * @return string
     */
    public function getMappingFieldName(): string
    {
        return $this->mapping['fieldName'];
    }

    /**
     * Whether this column navigates into associations.
     *
     * @return bool
     */
    public function isAssociation(): bool
    {
        return isset($this->mapping['targetEntity']) || 0 < \count($this->associations);
    }

    /**
     * Gets the association target entity.
     *
     * @return string
     */
    public function getTargetEntity(): string
    {
        return $this->mapping['targetEntity'];
    }

    /**
     * Whether this association is a many to many.
     *
     * @return bool
     */
    public function isManyToMany(): bool
    {
        return isset($this->mapping['joinTable']);
    }

    /**
     * Whether this column represents the owning side of the association.
     *
     * @return bool
     */
    public function isOwningSide(): bool
    {
        return $this->mapping['isOwningSide'];
    }

    /**
     * Checks if the field name is a discriminator column name.
     *
     * @param ClassMetadata $rootEntity
     * @param string        $fieldName
     */
    private function searchForDiscriminator(ClassMetadata $rootEntity, string $fieldName): void
    {
        if (! isset($rootEntity->discriminatorColumn['name']) || $fieldName !== $rootEntity->discriminatorColumn['name']) {
            throw new FieldNotFoundException($fieldName, $rootEntity->name);
        }

        $this->discriminator = true;
        $this->validationWalker = function () use ($rootEntity): EnumWalker {
            return new EnumWalker(\array_keys($rootEntity->discriminatorMap));
        };
        $this->customWalker = DiscriminatorWalker::class;
    }

    /**
     * Process associations chain.
     *
     * @param EntityManagerInterface $entityManager
     * @param string                 $rest
     */
    private function processAssociations(EntityManagerInterface $entityManager, string $rest): void
    {
        $associations = [];
        $associationField = $this->mapping;

        while (null !== $rest) {
            $targetEntity = $entityManager->getClassMetadata($associationField['targetEntity']);
            [$associationField, $rest] = MappingHelper::processFieldName($targetEntity, $rest);

            if (null === $associationField) {
                throw new FieldNotFoundException($rest, $targetEntity->name);
            }

            $associations[] = $associationField;
        }

        $this->associations = $associations;
    }
}
