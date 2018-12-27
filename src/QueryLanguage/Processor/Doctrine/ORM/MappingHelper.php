<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\ORM;

use Doctrine\ORM\Mapping\ClassMetadata;

final class MappingHelper
{
    /**
     * Processes ORM mapping finding the root field by $fieldName.
     * Returns the root field (if found) and the rest part as an array of strings.
     *
     * @param ClassMetadata $classMetadata
     * @param string        $fieldName
     *
     * @return string[]
     */
    public static function processFieldName(ClassMetadata $classMetadata, string $fieldName): array
    {
        $dots = \substr_count($fieldName, '.');
        $revFieldName = \strrev($fieldName);

        $rootField = $classMetadata->fieldMappings[$fieldName] ??
            $classMetadata->associationMappings[$fieldName] ?? null;
        $rest = null;

        for ($i = 1; $i <= $dots + 1; ++$i) {
            $field = \explode('.', $revFieldName, $i);
            $field = \strrev(\end($field));

            $rest = \strlen($field) !== \strlen($fieldName) ? \substr($fieldName, \strlen($field) + 1) : null;

            $rootField = $classMetadata->fieldMappings[$field] ??
                $classMetadata->associationMappings[$field] ?? null;

            if (null !== $rootField) {
                break;
            }
        }

        return [$rootField, $rest];
    }
}
