<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Fazland\ApiPlatformBundle\QueryLanguage\Exception\Doctrine\FieldNotFoundException;
use Fazland\ApiPlatformBundle\QueryLanguage\Processor\ColumnInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Doctrine\DiscriminatorWalker;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\EnumWalker;

/**
 * @internal
 */
class Column implements ColumnInterface
{
    /**
     * @var string
     */
    public $requestName;

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

    public function __construct(
        string $requestName,
        string $fieldName,
        ClassMetadata $rootEntity,
        EntityManagerInterface $entityManager
    ) {
        $this->requestName = $requestName;
        $this->fieldName = $fieldName;

        [$rootField, $rest] = MappingHelper::processFieldName($rootEntity, $fieldName);

        if (null === $rootField) {
            $this->searchForDiscriminator($rootEntity, $fieldName);
        }

        $this->mapping = $rootField;
        $this->associations = [];

        if (null !== $rest) {
            $this->processAssociations($entityManager, $rest);
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
