<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Reflection;

use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException;

/**
 * Provides methods to call appropriate getter/setter on an object given the
 * property name. It does this following these rules:
 * - if the target object is an instance of ArrayAccess, it gets/sets the property
 * - if public getter/setter method exists, call it.
 * - if public property exists, return/set the value of it.
 * - else, throw exception
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ObjectAccess
{
    /**
     * @var PropertyAccessorInterface
     */
    private static $propertyAccessor;

    /**
     * Get a property of a given object.
     * Tries to get the property the following ways:
     * - if the target is an array, and has this property, we call it.
     * - if public getter method exists, call it.
     * - if the target object is an instance of ArrayAccess, it gets the property
     * on it if it exists.
     * - if public property exists, return the value of it.
     * - else, throw exception
     *
     * @param mixed $subject Object or array to get the property from
     * @param string $propertyName name of the property to retrieve
     *
     * @throws \InvalidArgumentException in case $subject was not an object or $propertyName was not a string
     * @throws Exception\PropertyNotAccessibleException
     * @return mixed Value of the property
     */
    public static function getProperty($subject, string $propertyName)
    {
        if (!is_object($subject) && !is_array($subject)) {
            throw new \InvalidArgumentException(
                '$subject must be an object or array, ' . gettype($subject) . ' given.',
                1237301367
            );
        }
        try {
            return self::getPropertyInternal($subject, $propertyName);
        } catch (NoSuchIndexException $ignoredException) {
            return null;
        }
    }

    /**
     * Gets a property of a given object or array.
     * This is an internal method that does only limited type checking for performance reasons.
     * If you can't make sure that $subject is either of type array or object and $propertyName of type string you should use getProperty() instead.
     *
     * @see getProperty()
     *
     * @param mixed $subject Object or array to get the property from
     * @param string $propertyName name of the property to retrieve
     *
     * @throws Exception\PropertyNotAccessibleException
     * @return mixed Value of the property
     * @internal
     */
    public static function getPropertyInternal($subject, string $propertyName)
    {
        if ($subject instanceof \SplObjectStorage || $subject instanceof ObjectStorage) {
            $subject = iterator_to_array(clone $subject, false);
        }

        $propertyPath = new PropertyPath($propertyName);

        if ($subject instanceof \ArrayAccess) {
            $accessor = self::createAccessor();

            // Check if $subject is an instance of \ArrayAccess and therefore maybe has actual accessible properties.
            if ($accessor->isReadable($subject, $propertyPath)) {
                return $accessor->getValue($subject, $propertyPath);
            }

            // Use array style property path for instances of \ArrayAccess
            // https://symfony.com/doc/current/components/property_access.html#reading-from-arrays

            $propertyPath = self::convertToArrayPropertyPath($propertyPath);
        }

        if (is_object($subject)) {
            return self::getObjectPropertyValue($subject, $propertyPath);
        }

        if (is_array($subject)) {
            try {
                return self::getArrayIndexValue($subject, self::convertToArrayPropertyPath($propertyPath));
            } catch (NoSuchIndexException $ignoredException) {
                return null;
            }
        }

        return null;
    }

    /**
     * Gets a property path from a given object or array.
     *
     * If propertyPath is "bla.blubb", then we first call getProperty($object, 'bla'),
     * and on the resulting object we call getProperty(..., 'blubb')
     *
     * For arrays the keys are checked likewise.
     *
     * @param mixed $subject Object or array to get the property path from
     * @param string $propertyPath
     *
     * @return mixed Value of the property
     */
    public static function getPropertyPath($subject, string $propertyPath)
    {
        try {
            foreach (new PropertyPath($propertyPath) as $pathSegment) {
                $subject = self::getPropertyInternal($subject, $pathSegment);
            }
        } catch (PropertyNotAccessibleException $error) {
            return null;
        }
        return $subject;
    }

    /**
     * Set a property for a given object.
     * Tries to set the property the following ways:
     * - if target is an array, set value
     * - if super cow powers should be used, set value through reflection
     * - if public setter method exists, call it.
     * - if public property exists, set it directly.
     * - if the target object is an instance of ArrayAccess, it sets the property
     * on it without checking if it existed.
     * - else, return FALSE
     *
     * @param mixed $subject The target object or array
     * @param string $propertyName Name of the property to set
     * @param mixed $propertyValue Value of the property
     *
     * @throws \InvalidArgumentException in case $object was not an object or $propertyName was not a string
     * @return bool TRUE if the property could be set, FALSE otherwise
     */
    public static function setProperty(&$subject, string $propertyName, $propertyValue): bool
    {
        if (is_array($subject) || $subject instanceof \ArrayAccess) {
            $subject[$propertyName] = $propertyValue;
            return true;
        }
        if (!is_object($subject)) {
            throw new \InvalidArgumentException('subject must be an object or array, ' . gettype($subject) . ' given.', 1237301368);
        }

        $accessor = self::createAccessor();
        if ($accessor->isWritable($subject, $propertyName)) {
            $accessor->setValue($subject, $propertyName, $propertyValue);
            return true;
        }
        return false;
    }

    /**
     * Returns an array of properties which can be get with the getProperty()
     * method.
     * Includes the following properties:
     * - which can be get through a public getter method.
     * - public properties which can be directly get.
     *
     * @param object $object Object to receive property names for
     *
     * @return array Array of all gettable property names
     * @throws Exception\UnknownClassException
     */
    public static function getGettablePropertyNames(object $object): array
    {
        if ($object instanceof \stdClass) {
            $properties = array_keys((array)$object);
            sort($properties);
            return $properties;
        }

        $classSchema = GeneralUtility::makeInstance(ReflectionService::class)
            ->getClassSchema($object);

        $accessor = self::createAccessor();
        $propertyNames = array_keys($classSchema->getProperties());
        $accessiblePropertyNames = array_filter($propertyNames, static function ($propertyName) use ($accessor, $object) {
            return $accessor->isReadable($object, $propertyName);
        });

        foreach ($classSchema->getMethods() as $methodName => $methodDefinition) {
            if (!$methodDefinition->isPublic()) {
                continue;
            }

            foreach ($methodDefinition->getParameters() as $methodParam) {
                if (!$methodParam->isOptional()) {
                    continue 2;
                }
            }

            if (str_starts_with($methodName, 'get')) {
                $accessiblePropertyNames[] = lcfirst(substr($methodName, 3));
                continue;
            }

            if (str_starts_with($methodName, 'has')) {
                $accessiblePropertyNames[] = lcfirst(substr($methodName, 3));
                continue;
            }

            if (str_starts_with($methodName, 'is')) {
                $accessiblePropertyNames[] = lcfirst(substr($methodName, 2));
            }
        }

        $accessiblePropertyNames = array_unique($accessiblePropertyNames);
        sort($accessiblePropertyNames);
        return $accessiblePropertyNames;
    }

    /**
     * Returns an array of properties which can be set with the setProperty()
     * method.
     * Includes the following properties:
     * - which can be set through a public setter method.
     * - public properties which can be directly set.
     *
     * @param object $object Object to receive property names for
     *
     * @throws \InvalidArgumentException
     * @return array Array of all settable property names
     */
    public static function getSettablePropertyNames(object $object): array
    {
        $accessor = self::createAccessor();

        if ($object instanceof \stdClass || $object instanceof \ArrayAccess) {
            $propertyNames = array_keys((array)$object);
        } else {
            $classSchema = GeneralUtility::makeInstance(ReflectionService::class)->getClassSchema($object);

            $propertyNames = array_filter(array_keys($classSchema->getProperties()), static function ($methodName) use ($accessor, $object) {
                return $accessor->isWritable($object, $methodName);
            });

            $setters = array_filter(array_keys($classSchema->getMethods()), static function ($methodName) use ($object) {
                return str_starts_with($methodName, 'set') && is_callable([$object, $methodName]);
            });

            foreach ($setters as $setter) {
                $propertyNames[] = lcfirst(substr($setter, 3));
            }
        }

        $propertyNames = array_unique($propertyNames);
        sort($propertyNames);
        return $propertyNames;
    }

    /**
     * Tells if the value of the specified property can be set by this Object Accessor.
     *
     * @param object $object Object containing the property
     * @param string $propertyName Name of the property to check
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    public static function isPropertySettable(object $object, $propertyName): bool
    {
        if ($object instanceof \stdClass && array_key_exists($propertyName, get_object_vars($object))) {
            return true;
        }
        if (array_key_exists($propertyName, get_class_vars(get_class($object)))) {
            return true;
        }
        return is_callable([$object, 'set' . ucfirst($propertyName)]);
    }

    /**
     * Tells if the value of the specified property can be retrieved by this Object Accessor.
     *
     * @param object $object Object containing the property
     * @param string $propertyName Name of the property to check
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    public static function isPropertyGettable($object, $propertyName): bool
    {
        if (is_array($object) || ($object instanceof \ArrayAccess && $object->offsetExists($propertyName))) {
            $propertyName = self::wrap($propertyName);
        }

        return self::createAccessor()->isReadable($object, $propertyName);
    }

    /**
     * Get all properties (names and their current values) of the current
     * $object that are accessible through this class.
     *
     * @param object $object Object to get all properties from.
     *
     * @throws \InvalidArgumentException
     * @return array Associative array of all properties.
     * @todo What to do with ArrayAccess
     */
    public static function getGettableProperties(object $object): array
    {
        $properties = [];
        foreach (self::getGettablePropertyNames($object) as $propertyName) {
            $properties[$propertyName] = self::getPropertyInternal($object, $propertyName);
        }
        return $properties;
    }

    /**
     * @return PropertyAccessor
     */
    private static function createAccessor(): PropertyAccessor
    {
        if (self::$propertyAccessor === null) {
            self::$propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->enableExceptionOnInvalidIndex()
                ->getPropertyAccessor();
        }

        return self::$propertyAccessor;
    }

    /**
     * @param object $subject
     * @param PropertyPath $propertyPath
     * @return mixed
     * @throws Exception\PropertyNotAccessibleException
     */
    private static function getObjectPropertyValue(object $subject, PropertyPath $propertyPath)
    {
        $accessor = self::createAccessor();

        if ($accessor->isReadable($subject, $propertyPath)) {
            return $accessor->getValue($subject, $propertyPath);
        }

        throw new PropertyNotAccessibleException('The property "' . (string)$propertyPath . '" on the subject does not exist.', 1476109666);
    }

    /**
     * @param array $subject
     * @param PropertyPath $propertyPath
     * @return mixed
     */
    private static function getArrayIndexValue(array $subject, PropertyPath $propertyPath)
    {
        return self::createAccessor()->getValue($subject, $propertyPath);
    }

    /**
     * @param PropertyPath $propertyPath
     * @return PropertyPath
     */
    private static function convertToArrayPropertyPath(PropertyPath $propertyPath): PropertyPath
    {
        $segments = array_map(static function ($segment) {
            return static::wrap($segment);
        }, $propertyPath->getElements());

        return new PropertyPath(implode('.', $segments));
    }

    /**
     * @param string $segment
     * @return string
     */
    private static function wrap(string $segment): string
    {
        return '[' . $segment . ']';
    }
}
