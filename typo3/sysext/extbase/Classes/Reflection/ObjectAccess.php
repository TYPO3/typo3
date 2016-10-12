<?php
namespace TYPO3\CMS\Extbase\Reflection;

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

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Provides methods to call appropriate getter/setter on an object given the
 * property name. It does this following these rules:
 * - if the target object is an instance of ArrayAccess, it gets/sets the property
 * - if public getter/setter method exists, call it.
 * - if public property exists, return/set the value of it.
 * - else, throw exception
 */
class ObjectAccess
{
    const ACCESS_GET = 0;

    const ACCESS_SET = 1;

    const ACCESS_PUBLIC = 2;

    /**
     * Get a property of a given object.
     * Tries to get the property the following ways:
     * - if the target is an array, and has this property, we call it.
     * - if super cow powers should be used, fetch value through reflection
     * - if public getter method exists, call it.
     * - if the target object is an instance of ArrayAccess, it gets the property
     * on it if it exists.
     * - if public property exists, return the value of it.
     * - else, throw exception
     *
     * @param mixed $subject Object or array to get the property from
     * @param string $propertyName name of the property to retrieve
     * @param bool $forceDirectAccess directly access property using reflection(!)
     *
     * @throws Exception\PropertyNotAccessibleException
     * @throws \InvalidArgumentException in case $subject was not an object or $propertyName was not a string
     * @return mixed Value of the property
     */
    public static function getProperty($subject, $propertyName, $forceDirectAccess = false)
    {
        if (!is_object($subject) && !is_array($subject)) {
            throw new \InvalidArgumentException('$subject must be an object or array, ' . gettype($subject) . ' given.', 1237301367);
        }
        if (!is_string($propertyName) && (!is_array($subject) && !$subject instanceof \ArrayAccess)) {
            throw new \InvalidArgumentException('Given property name is not of type string.', 1231178303);
        }
        $propertyExists = false;
        $propertyValue = self::getPropertyInternal($subject, $propertyName, $forceDirectAccess, $propertyExists);
        if ($propertyExists === true) {
            return $propertyValue;
        }
        throw new Exception\PropertyNotAccessibleException('The property "' . $propertyName . '" on the subject was not accessible.', 1263391473);
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
     * @param bool $forceDirectAccess directly access property using reflection(!)
     * @param bool &$propertyExists (by reference) will be set to TRUE if the specified property exists and is gettable
     *
     * @throws Exception\PropertyNotAccessibleException
     * @return mixed Value of the property
     * @internal
     */
    public static function getPropertyInternal($subject, $propertyName, $forceDirectAccess, &$propertyExists)
    {
        if ($subject === null || is_scalar($subject)) {
            return null;
        }
        $propertyExists = true;
        if (is_array($subject)) {
            if (array_key_exists($propertyName, $subject)) {
                return $subject[$propertyName];
            }
            $propertyExists = false;
            return null;
        }
        if ($forceDirectAccess === true) {
            if (property_exists(get_class($subject), $propertyName)) {
                $propertyReflection = new PropertyReflection(get_class($subject), $propertyName);
                return $propertyReflection->getValue($subject);
            } elseif (property_exists($subject, $propertyName)) {
                return $subject->{$propertyName};
            } else {
                throw new Exception\PropertyNotAccessibleException('The property "' . $propertyName . '" on the subject does not exist.', 1302855001);
            }
        }
        if ($subject instanceof \SplObjectStorage || $subject instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
            if (MathUtility::canBeInterpretedAsInteger($propertyName)) {
                $index = 0;
                foreach ($subject as $value) {
                    if ($index === (int)$propertyName) {
                        return $value;
                    }
                    $index++;
                }
                $propertyExists = false;
                return null;
            }
        } elseif ($subject instanceof \ArrayAccess && isset($subject[$propertyName])) {
            return $subject[$propertyName];
        }
        $getterMethodName = 'get' . ucfirst($propertyName);
        if (is_callable([$subject, $getterMethodName])) {
            return $subject->{$getterMethodName}();
        }
        $getterMethodName = 'is' . ucfirst($propertyName);
        if (is_callable([$subject, $getterMethodName])) {
            return $subject->{$getterMethodName}();
        }
        $getterMethodName = 'has' . ucfirst($propertyName);
        if (is_callable([$subject, $getterMethodName])) {
            return $subject->{$getterMethodName}();
        }
        if (is_object($subject) && array_key_exists($propertyName, get_object_vars($subject))) {
            return $subject->{$propertyName};
        }
        $propertyExists = false;
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
    public static function getPropertyPath($subject, $propertyPath)
    {
        $propertyPathSegments = explode('.', $propertyPath);
        foreach ($propertyPathSegments as $pathSegment) {
            $propertyExists = false;
            $subject = self::getPropertyInternal($subject, $pathSegment, false, $propertyExists);
            if (!$propertyExists || $subject === null) {
                return $subject;
            }
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
     * @param mixed &$subject The target object or array
     * @param string $propertyName Name of the property to set
     * @param mixed $propertyValue Value of the property
     * @param bool $forceDirectAccess directly access property using reflection(!)
     *
     * @throws \InvalidArgumentException in case $object was not an object or $propertyName was not a string
     * @return bool TRUE if the property could be set, FALSE otherwise
     */
    public static function setProperty(&$subject, $propertyName, $propertyValue, $forceDirectAccess = false)
    {
        if (is_array($subject)) {
            $subject[$propertyName] = $propertyValue;
            return true;
        }
        if (!is_object($subject)) {
            throw new \InvalidArgumentException('subject must be an object or array, ' . gettype($subject) . ' given.', 1237301368);
        }
        if (!is_string($propertyName)) {
            throw new \InvalidArgumentException('Given property name is not of type string.', 1231178878);
        }
        if ($forceDirectAccess === true) {
            if (property_exists(get_class($subject), $propertyName)) {
                $propertyReflection = new PropertyReflection(get_class($subject), $propertyName);
                $propertyReflection->setAccessible(true);
                $propertyReflection->setValue($subject, $propertyValue);
            } else {
                $subject->{$propertyName} = $propertyValue;
            }
        } elseif (is_callable([$subject, $setterMethodName = self::buildSetterMethodName($propertyName)])) {
            $subject->{$setterMethodName}($propertyValue);
        } elseif ($subject instanceof \ArrayAccess) {
            $subject[$propertyName] = $propertyValue;
        } elseif (array_key_exists($propertyName, get_object_vars($subject))) {
            $subject->{$propertyName} = $propertyValue;
        } else {
            return false;
        }
        return true;
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
     * @throws \InvalidArgumentException
     * @return array Array of all gettable property names
     */
    public static function getGettablePropertyNames($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1237301369);
        }
        if ($object instanceof \stdClass) {
            $properties = array_keys((array)$object);
            sort($properties);
            return $properties;
        }

        $reflection = new \ReflectionClass($object);
        $declaredPropertyNames = array_map(
            function (\ReflectionProperty $property) {
                return $property->getName();
            },
            $reflection->getProperties(\ReflectionProperty::IS_PUBLIC)
        );
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodParameters = $method->getParameters();
            if (!empty($methodParameters)) {
                foreach ($methodParameters as $parameter) {
                    if (!$parameter->isOptional()) {
                        continue 2;
                    }
                }
            }
            $methodName = $method->getName();
            if (substr($methodName, 0, 2) === 'is') {
                $declaredPropertyNames[] = lcfirst(substr($methodName, 2));
            }
            if (substr($methodName, 0, 3) === 'get') {
                $declaredPropertyNames[] = lcfirst(substr($methodName, 3));
            }
            if (substr($methodName, 0, 3) === 'has') {
                $declaredPropertyNames[] = lcfirst(substr($methodName, 3));
            }
        }
        $propertyNames = array_unique($declaredPropertyNames);
        sort($propertyNames);

        return $propertyNames;
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
    public static function getSettablePropertyNames($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1264022994);
        }
        if ($object instanceof \stdClass) {
            $declaredPropertyNames = array_keys(get_object_vars($object));
        } else {
            $declaredPropertyNames = array_keys(get_class_vars(get_class($object)));
        }
        foreach (get_class_methods($object) as $methodName) {
            if (substr($methodName, 0, 3) === 'set' && is_callable([$object, $methodName])) {
                $declaredPropertyNames[] = lcfirst(substr($methodName, 3));
            }
        }
        $propertyNames = array_unique($declaredPropertyNames);
        sort($propertyNames);
        return $propertyNames;
    }

    /**
     * Tells if the value of the specified property can be set by this Object Accessor.
     *
     * @param object $object Object containting the property
     * @param string $propertyName Name of the property to check
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    public static function isPropertySettable($object, $propertyName)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1259828920);
        }
        if ($object instanceof \stdClass && array_search($propertyName, array_keys(get_object_vars($object))) !== false) {
            return true;
        } elseif (array_search($propertyName, array_keys(get_class_vars(get_class($object)))) !== false) {
            return true;
        }
        return is_callable([$object, self::buildSetterMethodName($propertyName)]);
    }

    /**
     * Tells if the value of the specified property can be retrieved by this Object Accessor.
     *
     * @param object $object Object containting the property
     * @param string $propertyName Name of the property to check
     *
     * @throws \InvalidArgumentException
     * @return bool
     */
    public static function isPropertyGettable($object, $propertyName)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1259828921);
        }
        if ($object instanceof \ArrayAccess && isset($object[$propertyName]) === true) {
            return true;
        } elseif ($object instanceof \stdClass && array_search($propertyName, array_keys(get_object_vars($object))) !== false) {
            return true;
        } elseif ($object instanceof \ArrayAccess && isset($object[$propertyName]) === true) {
            return true;
        }
        if (is_callable([$object, 'get' . ucfirst($propertyName)])) {
            return true;
        }
        if (is_callable([$object, 'has' . ucfirst($propertyName)])) {
            return true;
        }
        if (is_callable([$object, 'is' . ucfirst($propertyName)])) {
            return true;
        }
        return array_search($propertyName, array_keys(get_class_vars(get_class($object)))) !== false;
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
    public static function getGettableProperties($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1237301370);
        }
        $properties = [];
        foreach (self::getGettablePropertyNames($object) as $propertyName) {
            $propertyExists = false;
            $propertyValue = self::getPropertyInternal($object, $propertyName, false, $propertyExists);
            if ($propertyExists === true) {
                $properties[$propertyName] = $propertyValue;
            }
        }
        return $properties;
    }

    /**
     * Build the setter method name for a given property by capitalizing the
     * first letter of the property, and prepending it with "set".
     *
     * @param string $propertyName Name of the property
     *
     * @return string Name of the setter method name
     */
    public static function buildSetterMethodName($propertyName)
    {
        return 'set' . ucfirst($propertyName);
    }
}
