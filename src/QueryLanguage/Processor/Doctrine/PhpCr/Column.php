<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\PhpCr;

use Doctrine\ODM\PHPCR\DocumentManagerInterface;
use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;
use Doctrine\ODM\PHPCR\Query\Builder\AbstractNode;
use Doctrine\ODM\PHPCR\Query\Builder\From;
use Doctrine\ODM\PHPCR\Query\Builder\QueryBuilder;
use Doctrine\ODM\PHPCR\Query\Builder\WhereAnd;
use Fazland\ApiPlatformBundle\QueryLanguage\Exception\Doctrine\FieldNotFoundException;
use Fazland\ApiPlatformBundle\QueryLanguage\Expression\ExpressionInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\PhpCr\NodeWalker;

/**
 * @internal
 */
class Column implements ColumnInterface
{
    private string $rootAlias;

    /**
     * @var string[]|null
     */
    private ?array $mapping;

    public string $fieldName;

    private string $fieldType;

    /**
     * @var string|callable|null
     */
    public $validationWalker;

    /**
     * @var string|callable|null
     */
    public $customWalker;

    /**
     * @var array
     */
    private array $associations;

    private DocumentManagerInterface $documentManager;

    public function __construct(
        string $fieldName,
        string $rootAlias,
        ClassMetadata $rootEntity,
        DocumentManagerInterface $documentManager
    ) {
        $this->fieldName = $fieldName;
        $this->rootAlias = $rootAlias;
        $this->documentManager = $documentManager;
        $this->validationWalker = null;
        $this->customWalker = null;

        [$rootField, $rest] = MappingHelper::processFieldName($rootEntity, $fieldName);
        $this->mapping = $rootField;

        $this->fieldType = 'string';
        if (isset($this->mapping['type']) && ! isset($this->mapping['targetDocument'])) {
            $this->fieldType = $this->mapping['type'];
        }

        $this->associations = [];
        if (null !== $rest) {
            $this->processAssociations($documentManager, $rest);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addCondition($queryBuilder, ExpressionInterface $expression): void
    {
        if ($this->isAssociation()) {
            $this->addAssociationCondition($queryBuilder, $expression);
        } else {
            $this->addWhereCondition($queryBuilder, $expression);
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
        return isset($this->mapping['targetDocument']) || 0 < \count($this->associations);
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

        $targetDocument = $this->documentManager->getClassMetadata($this->getTargetDocument());
        if (null === $targetDocument->uuidFieldName) {
            throw new \RuntimeException('Uuid field must be declared to build association conditions');
        }

        $queryBuilder->addJoinInner()
            ->right()->document($this->getTargetDocument(), $alias)->end()
            ->condition()->equi($this->rootAlias.'.'.$alias, $alias.'.'.$targetDocument->uuidFieldName)->end()
        ->end();

        $currentFieldName = $alias;
        $currentAlias = $alias;
        foreach ($this->associations as $association) {
            if (isset($association['targetDocument'])) {
                /** @var From $from */
                $from = $queryBuilder->getChildOfType(AbstractNode::NT_FROM);
                $from->joinInner()
                    ->left()->document($association['sourceDocument'], $currentAlias)->end()
                    ->right()->document($association['targetDocument'], $currentFieldName = $association['fieldName'])->end()
                ->end();

                $currentAlias = $association['fieldName'];
            } else {
                $currentFieldName = $currentAlias.'.'.$association['fieldName'];
            }
        }

        if (null !== $walker) {
            $walker = \is_string($walker) ? new $walker($queryBuilder, $currentFieldName) : $walker($queryBuilder, $currentFieldName, $this->fieldType);
        } else {
            $walker = new NodeWalker($currentFieldName, $this->fieldType);
        }

        $where = new WhereAnd();
        $where->addChild($expression->dispatch($walker));

        $queryBuilder->addChild($where);
    }

    /**
     * Adds a simple condition to the query builder.
     *
     * @param QueryBuilder        $queryBuilder
     * @param ExpressionInterface $expression
     */
    private function addWhereCondition(QueryBuilder $queryBuilder, ExpressionInterface $expression): void
    {
        $alias = $this->getMappingFieldName();
        $walker = $this->customWalker;

        $fieldName = $this->rootAlias.'.'.$alias;
        if (null !== $walker) {
            $walker = \is_string($walker) ? new $walker($fieldName) : $walker($fieldName, $this->fieldType);
        } else {
            $walker = new NodeWalker($fieldName, $this->fieldType);
        }

        /** @var AbstractNode $node */
        $node = $expression->dispatch($walker);
        if (AbstractNode::NT_CONSTRAINT === $node->getNodeType()) {
            $where = new WhereAnd();
            $where->addChild($node);

            $queryBuilder->addChild($where);
        } else {
            $queryBuilder->addChild($node);
        }
    }

    /**
     * Process associations chain.
     *
     * @param DocumentManagerInterface $documentManager
     * @param string                   $rest
     */
    private function processAssociations(DocumentManagerInterface $documentManager, string $rest): void
    {
        $associations = [];
        $associationField = $this->mapping;

        while (null !== $rest) {
            $targetDocument = $documentManager->getClassMetadata($associationField['targetDocument']);
            [$associationField, $rest] = MappingHelper::processFieldName($targetDocument, $rest);

            if (null === $associationField) {
                throw new FieldNotFoundException($rest, $targetDocument->name);
            }

            $associations[] = $associationField;
        }

        $this->associations = $associations;
    }

    /**
     * Gets the target document class.
     *
     * @return string
     */
    private function getSourceDocument(): string
    {
        return $this->mapping['sourceDocument'];
    }

    /**
     * Gets the target document class.
     *
     * @return string
     */
    private function getTargetDocument(): string
    {
        return $this->mapping['targetDocument'];
    }
}
