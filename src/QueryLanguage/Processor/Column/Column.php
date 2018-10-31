<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Column;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\TreeWalkerInterface;
use Fazland\ApiPlatformBundle\QueryLanguage\Walker\Validation\ValidationWalkerInterface;

/**
 * @internal
 */
class Column
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
     * @var null|ValidationWalkerInterface
     */
    public $validationWalker;

    /**
     * @var null|string|callable
     */
    public $customWalker;

    public function __construct(
        string $requestName,
        string $fieldName,
        ClassMetadata $rootEntity,
        EntityManagerInterface $entityManager
    ) {
        $this->requestName = $requestName;
        $this->fieldName = $fieldName;

        [$rootField, $rest] = $this->getFieldMapping($rootEntity, $fieldName);

        if (null === $rootField) {
            throw new \Exception();
        }

        $associations = [];
        $associationField = $rootField;

        while (null !== $rest) {
            $targetEntity = $entityManager->getClassMetadata($associationField['targetEntity']);
            [$associationField, $rest] = $this->getFieldMapping($targetEntity, $rest);

            if (null === $associationField) {
                throw new \Exception();
            }

            $associations[] = $associationField;
        }

        $this->mapping = $rootField;
        $this->associations = $associations;
    }

    public function getMappingFieldName(): string
    {
        return $this->mapping['fieldName'];
    }

    public function getTargetEntity(): string
    {
        return $this->mapping['targetEntity'];
    }

    public function isAssociation(): bool
    {
        return isset($this->mapping['targetEntity']) || 0 < count($this->associations);
    }

    public function isManyToMany(): bool
    {
        return isset($this->mapping['joinTable']);
    }

    public function isOwningSide(): bool
    {
        return $this->mapping['isOwningSide'];
    }

    private function getFieldMapping(ClassMetadata $classMetadata, string $fieldName): array
    {
        $dots = substr_count($fieldName, '.');
        $revFieldName = strrev($fieldName);

        $rootField = $classMetadata->fieldMappings[$fieldName] ??
            $classMetadata->associationMappings[$fieldName] ?? null;
        $rest = null;

        for($i = 1; $i <= $dots + 1; $i++) {
            $field = explode('.', $revFieldName, $i);
            $field = strrev(end($field));

            $rest = strlen($field) !== strlen($fieldName) ? substr($fieldName, strlen($field) + 1) : null;

            $rootField = $classMetadata->fieldMappings[$field] ??
                $classMetadata->associationMappings[$field] ?? null;

            if (null !== $rootField) {
                break;
            }
        }

        return [$rootField, $rest];
    }
}