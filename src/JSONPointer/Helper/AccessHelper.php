<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\JSONPointer\Helper;

use Fazland\ApiPlatformBundle\JSONPointer\Accessor;
use Symfony\Component\Inflector\Inflector;

/**
 * @internal
 */
final class AccessHelper
{
    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var \ReflectionProperty
     */
    private $reflectionProperty;

    /**
     * @var string
     */
    private $property;

    /**
     * @var string
     */
    private $camelized;

    public function __construct(string $class, string $property)
    {
        $this->reflectionClass = new \ReflectionClass($class);
        $this->reflectionProperty = null;

        $this->property = $property;
        $this->camelized = self::camelize($property);

        if (
            $this->reflectionClass->hasProperty($property) &&
            ($reflectionProperty = $this->reflectionClass->getProperty($property))
        ) {
            $this->reflectionProperty = $reflectionProperty;
        } elseif (
            $this->reflectionClass->hasProperty($this->camelized) &&
            ($reflectionProperty = $this->reflectionClass->getProperty($this->camelized))
        ) {
            $this->reflectionProperty = $reflectionProperty;
        }
    }

    /**
     * Gets the read access informations.
     *
     * @return array
     */
    public function getReadAccessInfo(): array
    {
        $hasProperty = null !== $this->reflectionProperty;

        $methods = ['get'.$this->camelized, $this->camelized, 'is'.$this->camelized, 'has'.$this->camelized];
        foreach ($methods as $method) {
            if (! $this->reflectionClass->hasMethod($method) || ! $this->reflectionClass->getMethod($method)->isPublic()) {
                continue;
            }

            return [
                Accessor::ACCESS_HAS_PROPERTY => $hasProperty,
                Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_METHOD,
                Accessor::ACCESS_NAME => $method,
            ];
        }

        if ($this->reflectionClass->hasMethod('__get') && $this->reflectionClass->getMethod('__get')->isPublic()) {
            return [
                Accessor::ACCESS_HAS_PROPERTY => $hasProperty,
                Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_PROPERTY,
                Accessor::ACCESS_NAME => $this->property,
                Accessor::ACCESS_REF => false,
            ];
        }

        if ($hasProperty && $this->reflectionProperty->isPublic()) {
            return [
                Accessor::ACCESS_HAS_PROPERTY => true,
                Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_PROPERTY,
                Accessor::ACCESS_NAME => $this->reflectionProperty->name,
                Accessor::ACCESS_REF => true,
            ];
        }

        return [
            Accessor::ACCESS_HAS_PROPERTY => $hasProperty,
            Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_NOT_FOUND,
            Accessor::ACCESS_NAME => \sprintf(
                'Neither the property "%s" nor one of the methods "%s()" '.
                'exist and have public access in class "%s".',
                $this->property,
                \implode('()", "', $methods),
                $this->reflectionClass->name
            ),
        ];
    }

    /**
     * Gets the write access informations.
     *
     * @param mixed $value
     *
     * @return array
     */
    public function getWriteAccessInfo($value): array
    {
        $hasProperty = null !== $this->reflectionProperty;

        if (\is_array($value) || $value instanceof \Traversable) {
            $methods = $this->findAdderAndRemover();

            if (null !== $methods) {
                return [
                    Accessor::ACCESS_HAS_PROPERTY => $hasProperty,
                    Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_ADDER_AND_REMOVER,
                    Accessor::ACCESS_ADDER => $methods[0],
                    Accessor::ACCESS_REMOVER => $methods[1],
                ];
            }
        }

        $methods = ['set'.$this->camelized, $this->camelized];
        foreach ($methods as $method) {
            if (! $this->isMethodAccessible($method, 1)) {
                continue;
            }

            return [
                Accessor::ACCESS_HAS_PROPERTY => $hasProperty,
                Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_METHOD,
                Accessor::ACCESS_NAME => $method,
            ];
        }

        if ($this->isMethodAccessible('__set', 2)) {
            return [
               Accessor::ACCESS_HAS_PROPERTY => $hasProperty,
               Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_PROPERTY,
               Accessor::ACCESS_NAME => $this->property,
           ];
        }

        if ($hasProperty && $this->reflectionProperty->isPublic()) {
            return [
                Accessor::ACCESS_HAS_PROPERTY => true,
                Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_PROPERTY,
                Accessor::ACCESS_NAME => $this->reflectionProperty->name,
                Accessor::ACCESS_REF => true,
            ];
        }

        if (null !== $adderRemover = $this->findAdderAndRemover()) {
            return [
                Accessor::ACCESS_HAS_PROPERTY => $hasProperty,
                Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_NOT_FOUND,
                Accessor::ACCESS_NAME => \sprintf(
                    'The property "%s" in class "%s" can be defined with the methods "%s()" but '.
                    'the new value must be an array or an instance of \Traversable, '.
                    '"%s" given.',
                    $this->property,
                    $this->reflectionClass->name,
                    \implode('()", "', $adderRemover),
                    \is_object($value) ? \get_class($value) : \gettype($value)
                ),
            ];
        }

        return [
            Accessor::ACCESS_HAS_PROPERTY => $hasProperty,
            Accessor::ACCESS_TYPE => Accessor::ACCESS_TYPE_NOT_FOUND,
            Accessor::ACCESS_NAME => \sprintf(
                'Neither the property "%s" nor one of the methods %s"%s()", '.
                '"__set()" or "__call()" exist and have public access in class "%s".',
                $this->property,
                \implode('', \array_map(function ($singular) {
                    return '"add'.$singular.'()"/"remove'.$singular.'()", ';
                }, (array) Inflector::singularize($this->camelized))),
                \implode('()", "', $methods),
                $this->reflectionClass->name
            ),
        ];
    }

    /**
     * Searches for add and remove methods.
     *
     * @return array|null An array containing the adder and remover when found, null otherwise
     */
    private function findAdderAndRemover(): ?array
    {
        $singulars = (array) Inflector::singularize($this->camelized);

        foreach ($singulars as $singular) {
            $addMethod = 'add'.$singular;
            $removeMethod = 'remove'.$singular;

            if ($this->isMethodAccessible($addMethod, 1) && $this->isMethodAccessible($removeMethod, 1)) {
                return [$addMethod, $removeMethod];
            }
        }

        return null;
    }

    /**
     * Returns whether a method is public and has the number of required parameters.
     *
     * @param string $methodName The method name
     * @param int    $parameters The number of parameters
     *
     * @return bool Whether the method is public and has $parameters required parameters
     */
    private function isMethodAccessible(string $methodName, int $parameters): bool
    {
        if ($this->reflectionClass->hasMethod($methodName)) {
            $method = $this->reflectionClass->getMethod($methodName);

            return $method->isPublic()
                && $method->getNumberOfRequiredParameters() <= $parameters
                && $method->getNumberOfParameters() >= $parameters
            ;
        }

        return false;
    }

    /**
     * Camelizes a given string.
     *
     * @param string $string Some string
     *
     * @return string The camelized version of the string
     */
    public static function camelize($string)
    {
        return \lcfirst(\str_replace(' ', '', \ucwords(\str_replace('_', ' ', $string))));
    }
}
