<?php declare(strict_types=1);

namespace Fazland\ApiPlatformBundle\JSONPointer;

use Fazland\ApiPlatformBundle\JSONPointer\Helper\AccessHelper;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class Accessor implements PropertyAccessorInterface
{
    /**
     * @internal
     */
    const VALUE = 0;

    /**
     * @internal
     */
    const REF = 1;

    /**
     * @internal
     */
    const IS_REF_CHAINED = 2;

    /**
     * @internal
     */
    const ACCESS_HAS_PROPERTY = 0;

    /**
     * @internal
     */
    const ACCESS_TYPE = 1;

    /**
     * @internal
     */
    const ACCESS_NAME = 2;

    /**
     * @internal
     */
    const ACCESS_REF = 3;

    /**
     * @internal
     */
    const ACCESS_ADDER = 4;

    /**
     * @internal
     */
    const ACCESS_REMOVER = 5;

    /**
     * @internal
     */
    const ACCESS_TYPE_METHOD = 0;

    /**
     * @internal
     */
    const ACCESS_TYPE_PROPERTY = 1;

    /**
     * @internal
     */
    const ACCESS_TYPE_ADDER_AND_REMOVER = 3;

    /**
     * @internal
     */
    const ACCESS_TYPE_NOT_FOUND = 4;

    /**
     * @internal
     */
    const CACHE_PREFIX_READ = 'r';

    /**
     * @internal
     */
    const CACHE_PREFIX_WRITE = 'w';

    /**
     * @internal
     */
    const CACHE_PREFIX_PROPERTY_PATH = 'p';

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    /**
     * @var array
     */
    private $readPropertyCache = [];

    /**
     * @var array
     */
    private $writePropertyCache = [];
    private static $resultProto = [self::VALUE => null];

    public function __construct(CacheItemPoolInterface $cacheItemPool = null)
    {
        $this->cacheItemPool = null === $cacheItemPool ? new ArrayAdapter() : $cacheItemPool; // Replace the NullAdapter by the null value
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(&$objectOrArray, $propertyPath, $value): void
    {
        $propertyPath = $this->getPath($propertyPath);
        $appendToArray = '-' === $propertyPath->getElement($propertyPath->getLength() - 1);

        $zval = [
            self::VALUE => $objectOrArray,
            self::REF => &$objectOrArray,
        ];
        $propertyValues = $this->readPropertiesUntil($zval, $propertyPath, $propertyPath->getLength() - 1);
        $overwrite = true;

        $propertiesCount = \count($propertyValues);

        for ($i = $propertiesCount - 1; 0 <= $i; --$i) {
            $zval = $propertyValues[$i];
            unset($propertyValues[$i]);

            // You only need set value for current element if:
            // 1. it's the parent of the last index element
            // OR
            // 2. its child is not passed by reference
            //
            // This may avoid uncessary value setting process for array elements.
            // For example:
            // '/a/b/c' => 'old-value'
            // If you want to change its value to 'new-value',
            // you only need set value for '/a/b/c' and it's safe to ignore '/a/b' and '/a'
            //
            if ($overwrite) {
                $property = $propertyPath->getElement($i);
                $val = $zval[self::VALUE];

                if (\is_array($val) || $val instanceof \ArrayAccess) {
                    if ($overwrite = ! isset($zval[self::REF])) {
                        $ref = &$zval[self::REF];
                        $ref = $zval[self::VALUE];
                    }

                    if ($appendToArray && $propertiesCount - 1 === $i) {
                        $zval[self::REF][] = $value;
                        $appendToArray = false;
                    } elseif ($appendToArray && $propertiesCount - 2 === $i) {
                        throw new InvalidArgumentException('Cannot append to a non-array object');
                    } else {
                        $zval[self::REF][$property] = $value;
                    }

                    if ($overwrite) {
                        $zval[self::VALUE] = $zval[self::REF];
                    }
                } else {
                    if ($appendToArray && $propertiesCount - 1 === $i) {
                        continue;
                    } elseif ($appendToArray && $propertiesCount - 2 === $i) {
                        $object = $zval[self::VALUE];
                        $access = $this->getWriteAccessInfo(\get_class($object), $property, [$value]);

                        if (! isset($access[self::ACCESS_ADDER])) {
                            throw new InvalidArgumentException('Cannot append to a non-array object');
                        }

                        $adder = $access[self::ACCESS_ADDER];
                        $object->{$adder}($value);
                        $appendToArray = false;
                    } else {
                        $this->writeProperty($zval, $property, $value);
                    }
                }

                // if current element is an object
                // OR
                // if current element's reference chain is not broken - current element
                // as well as all its ancients in the property path are all passed by reference,
                // then there is no need to continue the value setting process
                if (\is_object($zval[self::VALUE]) || isset($zval[self::IS_REF_CHAINED])) {
                    break;
                }
            }

            $value = $zval[self::VALUE];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        $propertyPath = $this->getPath($propertyPath);

        $zval = [
            self::VALUE => $objectOrArray,
        ];
        $propertyValues = $this->readPropertiesUntil($zval, $propertyPath, $propertyPath->getLength());

        return $propertyValues[\count($propertyValues) - 1][self::VALUE];
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($objectOrArray, $propertyPath): bool
    {
        $propertyPath = $this->getPath($propertyPath);

        try {
            $zval = [
                self::VALUE => $objectOrArray,
            ];
            $this->readPropertiesUntil($zval, $propertyPath, $propertyPath->getLength());

            return true;
        } catch (\TypeError | UnexpectedTypeException | NoSuchPropertyException $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($objectOrArray, $propertyPath): bool
    {
        $propertyPath = $this->getPath($propertyPath);

        try {
            $zval = [
                self::VALUE => $objectOrArray,
            ];
            $propertyValues = $this->readPropertiesUntil($zval, $propertyPath, $propertyPath->getLength() - 1);

            $i = \count($propertyValues) - 1;
            $zval = $propertyValues[$i];

            if ($zval[self::VALUE] instanceof \ArrayAccess || \is_array($zval[self::VALUE])) {
                return true;
            } elseif (! $this->isPropertyWritable($zval[self::VALUE], $propertyPath->getElement($i))) {
                return false;
            }

            return \is_object($zval[self::VALUE]);
        } catch (\TypeError | UnexpectedTypeException | NoSuchPropertyException $e) {
            return false;
        }
    }

    /**
     * Gets a PropertyPath instance and caches it.
     *
     * @param string|PropertyPathInterface $propertyPath
     *
     * @return PropertyPathInterface
     */
    private function getPath($propertyPath): PropertyPathInterface
    {
        if ($propertyPath instanceof PropertyPathInterface) {
            // Don't call the copy constructor has it is not needed here
            return $propertyPath;
        }

        $item = $this->cacheItemPool->getItem(self::CACHE_PREFIX_PROPERTY_PATH.\str_replace('/', '.', \rawurlencode($propertyPath)));
        if ($item->isHit()) {
            return $item->get();
        }

        $propertyPathInstance = new Path($propertyPath);
        if (isset($item)) {
            $item->set($propertyPathInstance);
            $this->cacheItemPool->save($item);
        }

        return $propertyPathInstance;
    }

    /**
     * Reads the path from an object up to a given path index.
     *
     * @param array                 $zval         The array containing the object or array to read from
     * @param PropertyPathInterface $propertyPath The property path to read
     * @param int                   $lastIndex    The index up to which should be read
     *
     * @return array The values read in the path
     *
     * @throws UnexpectedTypeException if a value within the path is neither object nor array
     * @throws NoSuchIndexException    If a non-existing index is accessed
     */
    private function readPropertiesUntil(array $zval, PropertyPathInterface $propertyPath, int $lastIndex): array
    {
        if (! \is_object($zval[self::VALUE]) && ! \is_array($zval[self::VALUE])) {
            throw new UnexpectedTypeException($zval[self::VALUE], $propertyPath, 0);
        }

        // Add the root object to the list
        $propertyValues = [$zval];

        for ($i = 0; $i < $lastIndex; ++$i) {
            $property = $propertyPath->getElement($i);

            if ($zval[self::VALUE] instanceof \ArrayAccess || \is_array($zval[self::VALUE])) {
                // Create missing nested arrays on demand
                if (($zval[self::VALUE] instanceof \ArrayAccess && ! $zval[self::VALUE]->offsetExists($property)) ||
                    (\is_array($zval[self::VALUE]) && ! isset($zval[self::VALUE][$property]) && ! \array_key_exists($property, $zval[self::VALUE]))
                ) {
                    if ($i + 1 < $propertyPath->getLength()) {
                        if (isset($zval[self::REF])) {
                            $zval[self::VALUE][$property] = [];
                            $zval[self::REF] = $zval[self::VALUE];
                        } else {
                            $zval[self::VALUE] = [$property => []];
                        }
                    }
                }

                $zval = $this->readIndex($zval, $property);
            } else {
                $zval = $this->readProperty($zval, $property);
            }

            // the final value of the path must not be validated
            if ($i + 1 < $propertyPath->getLength() && ! \is_object($zval[self::VALUE]) && ! \is_array($zval[self::VALUE])) {
                throw new UnexpectedTypeException($zval[self::VALUE], $propertyPath, $i + 1);
            }

            if (isset($zval[self::REF]) && (0 === $i || isset($propertyValues[$i - 1][self::IS_REF_CHAINED]))) {
                // Set the IS_REF_CHAINED flag to true if:
                // current property is passed by reference and
                // it is the first element in the property path or
                // the IS_REF_CHAINED flag of its parent element is true
                // Basically, this flag is true only when the reference chain from the top element to current element is not broken
                $zval[self::IS_REF_CHAINED] = true;
            }

            $propertyValues[] = $zval;
        }

        return $propertyValues;
    }

    /**
     * Reads a key from an array-like structure.
     *
     * @param array      $zval  The array containing the array or \ArrayAccess object to read from
     * @param string|int $index The key to read
     *
     * @return array The array containing the value of the key
     *
     * @throws NoSuchIndexException If the array does not implement \ArrayAccess or it is not an array
     */
    private function readIndex(array $zval, $index): array
    {
        $result = self::$resultProto;

        if (isset($zval[self::VALUE][$index])) {
            $result[self::VALUE] = $zval[self::VALUE][$index];

            if (! isset($zval[self::REF])) {
                // Save creating references when doing read-only lookups
            } elseif (\is_array($zval[self::VALUE])) {
                $result[self::REF] = &$zval[self::REF][$index];
            } elseif (\is_object($result[self::VALUE])) {
                $result[self::REF] = $result[self::VALUE];
            }
        }

        return $result;
    }

    /**
     * Reads the a property from an object.
     *
     * @param array  $zval     The array containing the object to read from
     * @param string $property The property to read
     *
     * @return array The array containing the value of the property
     *
     * @throws NoSuchPropertyException if the property does not exist or is not public
     */
    private function readProperty(array $zval, string $property): array
    {
        $result = self::$resultProto;
        $object = $zval[self::VALUE];
        $access = $this->getReadAccessInfo(\get_class($object), $property);
        $camelized = AccessHelper::camelize($property);

        if (self::ACCESS_TYPE_METHOD === $access[self::ACCESS_TYPE]) {
            $result[self::VALUE] = $object->{$access[self::ACCESS_NAME]}();
        } elseif (self::ACCESS_TYPE_PROPERTY === $access[self::ACCESS_TYPE]) {
            try {
                $result[self::VALUE] = $object->{$access[self::ACCESS_NAME]};

                if ($access[self::ACCESS_REF] && isset($zval[self::REF])) {
                    $result[self::REF] = &$object->{$access[self::ACCESS_NAME]};
                }
            } catch (\Throwable $e) {
                throw new NoSuchPropertyException($access[self::ACCESS_NAME]);
            }
        } elseif (! $access[self::ACCESS_HAS_PROPERTY] && (\property_exists($object, $property) || \property_exists($object, $camelized))) {
            // Needed to support \stdClass instances. We need to explicitly
            // exclude $access[self::ACCESS_HAS_PROPERTY], otherwise if
            // a *protected* property was found on the class, property_exists()
            // returns true, consequently the following line will result in a
            // fatal error.
            if (\property_exists($object, $camelized)) {
                $property = $camelized;
            }

            $result[self::VALUE] = $object->$property;
            if (isset($zval[self::REF])) {
                $result[self::REF] = &$object->$property;
            }
        } else {
            throw new NoSuchPropertyException($access[self::ACCESS_NAME]);
        }

        // Objects are always passed around by reference
        if (isset($zval[self::REF]) && \is_object($result[self::VALUE])) {
            $result[self::REF] = $result[self::VALUE];
        }

        return $result;
    }

    /**
     * Guesses how to read the property value.
     *
     * @param string $class
     * @param string $property
     *
     * @return array
     */
    private function getReadAccessInfo(string $class, string $property): array
    {
        $key = \rawurlencode($class).'..'.\rawurlencode($property);

        if (isset($this->readPropertyCache[$key])) {
            return $this->readPropertyCache[$key];
        }

        $item = $this->cacheItemPool->getItem(self::CACHE_PREFIX_READ.\str_replace('\\', '.', $key));
        if ($item->isHit()) {
            return $this->readPropertyCache[$key] = $item->get();
        }

        $helper = new AccessHelper($class, $property);
        $access = $helper->getReadAccessInfo();

        if (isset($item)) {
            $this->cacheItemPool->save($item->set($access));
        }

        return $this->readPropertyCache[$key] = $access;
    }

    /**
     * Sets the value of a property in the given object.
     *
     * @param array  $zval     The array containing the object to write to
     * @param string $property The property to write
     * @param mixed  $value    The value to write
     *
     * @throws NoSuchPropertyException if the property does not exist or is not public
     */
    private function writeProperty(array $zval, string $property, $value): void
    {
        $object = $zval[self::VALUE];
        $access = $this->getWriteAccessInfo(\get_class($object), $property, $value);
        $camelized = AccessHelper::camelize($property);

        if (self::ACCESS_TYPE_METHOD === $access[self::ACCESS_TYPE]) {
            $object->{$access[self::ACCESS_NAME]}($value);
        } elseif (self::ACCESS_TYPE_PROPERTY === $access[self::ACCESS_TYPE]) {
            $object->{$access[self::ACCESS_NAME]} = $value;
        } elseif (self::ACCESS_TYPE_ADDER_AND_REMOVER === $access[self::ACCESS_TYPE]) {
            $this->writeCollection($zval, $property, $value, $access[self::ACCESS_ADDER], $access[self::ACCESS_REMOVER]);
        } elseif (! $access[self::ACCESS_HAS_PROPERTY] && (\property_exists($object, $property) || \property_exists($object, $camelized))) {
            // Needed to support \stdClass instances. We need to explicitly
            // exclude $access[self::ACCESS_HAS_PROPERTY], otherwise if
            // a *protected* property was found on the class, property_exists()
            // returns true, consequently the following line will result in a
            // fatal error.
            if (\property_exists($object, $camelized)) {
                $property = $camelized;
            }

            $object->$property = $value;
        } elseif (self::ACCESS_TYPE_NOT_FOUND === $access[self::ACCESS_TYPE]) {
            throw new NoSuchPropertyException(\sprintf('Could not determine access type for property "%s".', $property));
        } else {
            throw new NoSuchPropertyException($access[self::ACCESS_NAME]);
        }
    }

    /**
     * Adjusts a collection-valued property by calling add*() and remove*() methods.
     *
     * @param array    $zval         The array containing the object to write to
     * @param string   $property     The property to write
     * @param iterable $collection   The collection to write
     * @param string   $addMethod    The add*() method
     * @param string   $removeMethod The remove*() method
     */
    private function writeCollection(array $zval, string $property, iterable $collection, string $addMethod, string $removeMethod): void
    {
        // At this point the add and remove methods have been found
        $previousValue = $this->readProperty($zval, $property);
        $previousValue = $previousValue[self::VALUE];

        if ($previousValue instanceof \Traversable) {
            $previousValue = \iterator_to_array($previousValue);
        }
        if ($previousValue && \is_array($previousValue)) {
            if (\is_object($collection)) {
                $collection = \iterator_to_array($collection);
            }
            foreach ($previousValue as $key => $item) {
                if (! \in_array($item, $collection, true)) {
                    unset($previousValue[$key]);
                    $zval[self::VALUE]->{$removeMethod}($item);
                }
            }
        } else {
            $previousValue = false;
        }

        foreach ($collection as $item) {
            if (! $previousValue || ! \in_array($item, $previousValue, true)) {
                $zval[self::VALUE]->{$addMethod}($item);
            }
        }
    }

    /**
     * Guesses how to write the property value.
     *
     * @param string $class
     * @param string $property
     * @param mixed  $value
     *
     * @return array
     */
    private function getWriteAccessInfo(string $class, string $property, $value): array
    {
        $key = \rawurlencode($class).'..'.\rawurlencode($property);

        if (isset($this->writePropertyCache[$key])) {
            return $this->writePropertyCache[$key];
        }

        $item = $this->cacheItemPool->getItem(self::CACHE_PREFIX_WRITE.\str_replace('\\', '.', $key));
        if ($item->isHit()) {
            return $this->writePropertyCache[$key] = $item->get();
        }

        $helper = new AccessHelper($class, $property);
        $access = $helper->getWriteAccessInfo($value);

        if (isset($item)) {
            $this->cacheItemPool->save($item->set($access));
        }

        return $this->writePropertyCache[$key] = $access;
    }

    /**
     * Returns whether a property is writable in the given object.
     *
     * @param object $object   The object to write to
     * @param string $property The property to write
     *
     * @return bool Whether the property is writable
     */
    private function isPropertyWritable($object, string $property): bool
    {
        if (! \is_object($object)) {
            return false;
        }

        $access = $this->getWriteAccessInfo(\get_class($object), $property, []);

        return self::ACCESS_TYPE_METHOD === $access[self::ACCESS_TYPE]
            || self::ACCESS_TYPE_PROPERTY === $access[self::ACCESS_TYPE]
            || self::ACCESS_TYPE_ADDER_AND_REMOVER === $access[self::ACCESS_TYPE]
            || (! $access[self::ACCESS_HAS_PROPERTY] && \property_exists($object, $property));
    }
}
