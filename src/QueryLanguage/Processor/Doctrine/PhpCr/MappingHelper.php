<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\QueryLanguage\Processor\Doctrine\PhpCr;

use Doctrine\ODM\PHPCR\Mapping\ClassMetadata;

final class MappingHelper
{
    /**
     * Processes PHPCR ODM mapping finding the root field by $fieldName.
     * Returns the root field (if found) and the rest part as an array of strings.
     *
     * @param ClassMetadata $classMetadata
     * @param string        $fieldName
     *
     * @return [string[], string]
     */
    public static function processFieldName(ClassMetadata $classMetadata, string $fieldName): array
    {
        $dots = \substr_count($fieldName, '.');
        $revFieldName = \strrev($fieldName);

        $rootField = null;
        $rest = null;
        for ($i = 1; $i <= $dots + 1; ++$i) {
            $field = \explode('.', $revFieldName, $i);
            $field = \strrev(\end($field));

            $rest = \strlen($field) !== \strlen($fieldName) ? \substr($fieldName, \strlen($field) + 1) : null;

            if ($classMetadata->nodename === $field || \in_array($field, $classMetadata->fieldMappings, true)) {
                $rootField = $classMetadata->mappings[$field];
            } elseif ($classMetadata->hasAssociation($field)) {
                $rootField = $classMetadata->getAssociation($field);
            }

            if (null !== $rootField) {
                break;
            }
        }

        return [$rootField, $rest];
    }
}
