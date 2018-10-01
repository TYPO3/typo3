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

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

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
        return self::getPropertyInternal($subject, $propertyName, $forceDirectAccess);
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
     *
     * @throws Exception\PropertyNotAccessibleException
     * @return mixed Value of the property
     * @internal
     */
    public static function getPropertyInternal($subject, $propertyName, $forceDirectAccess = false)
    {
        // type check and conversion of iterator to numerically indexed array
        if ($subject === null || is_scalar($subject)) {
            return null;
        }
        if (!$forceDirectAccess && ($subject instanceof \SplObjectStorage || $subject instanceof ObjectStorage)) {
            $subject = iterator_to_array(clone $subject, false);
        }

        // value get based on data type of $subject (possibly converted above)
        if (($subject instanceof \ArrayAccess && $subject->offsetExists($propertyName)) || is_array($subject)) {
            // isset() is safe; array_key_exists would only be needed to determine
            // if the value is NULL - and that's already what we return as fallback.
            if (isset($subject[$propertyName])) {
                return $subject[$propertyName];
            }
        } elseif (is_object($subject)) {
            if ($forceDirectAccess) {
                if (property_exists($subject, $propertyName)) {
                    $propertyReflection = new \ReflectionProperty($subject, $propertyName);
                    if ($propertyReflection->isPublic()) {
                        return $propertyReflection->getValue($subject);
                    }
                    $propertyReflection->setAccessible(true);
                    return $propertyReflection->getValue($subject);
                }
                throw new Exception\PropertyNotAccessibleException('The property "' . $propertyName . '" on the subject does not exist.', 1302855001);
            }
            $upperCasePropertyName = ucfirst($propertyName);
            $getterMethodName = 'get' . $upperCasePropertyName;
            if (is_callable([$subject, $getterMethodName])) {
                return $subject->{$getterMethodName}();
            }
            $getterMethodName = 'is' . $upperCasePropertyName;
            if (is_callable([$subject, $getterMethodName])) {
                return $subject->{$getterMethodName}();
            }
            $getterMethodName = 'has' . $upperCasePropertyName;
            if (is_callable([$subject, $getterMethodName])) {
                return $subject->{$getterMethodName}();
            }
            if (property_exists($subject, $propertyName)) {
                return $subject->{$propertyName};
            }
            throw new Exception\PropertyNotAccessibleException('The property "' . $propertyName . '" on the subject does not exist.', 1476109666);
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
    public static function getPropertyPath($subject, $propertyPath)
    {
        $propertyPathSegments = explode('.', $propertyPath);
        try {
            foreach ($propertyPathSegments as $pathSegment) {
                $subject = self::getPropertyInternal($subject, $pathSegment);
            }
        } catch (Exception\PropertyNotAccessibleException $error) {
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
        if (is_array($subject) || ($subject instanceof \ArrayAccess && !$forceDirectAccess)) {
            $subject[$propertyName] = $propertyValue;
            return true;
        }
        if (!is_object($subject)) {
            throw new \InvalidArgumentException('subject must be an object or array, ' . gettype($subject) . ' given.', 1237301368);
        }
        if (!is_string($propertyName)) {
            throw new \InvalidArgumentException('Given property name is not of type string.', 1231178878);
        }
        $result = true;
        if ($forceDirectAccess) {
            if (property_exists($subject, $propertyName)) {
                $propertyReflection = new \ReflectionProperty($subject, $propertyName);
                $propertyReflection->setAccessible(true);
                $propertyReflection->setValue($subject, $propertyValue);
            } else {
                $subject->{$propertyName} = $propertyValue;
            }
            return $result;
        }
        $setterMethodName = self::buildSetterMethodName($propertyName);
        if (is_callable([$subject, $setterMethodName])) {
            $subject->{$setterMethodName}($propertyValue);
        } elseif (property_exists($subject, $propertyName)) {
            $reflection = new \ReflectionProperty($subject, $propertyName);
            if ($reflection->isPublic()) {
                $subject->{$propertyName} = $propertyValue;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
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
            if (strpos($methodName, 'is') === 0) {
                $declaredPropertyNames[] = lcfirst(substr($methodName, 2));
            }
            if (strpos($methodName, 'get') === 0) {
                $declaredPropertyNames[] = lcfirst(substr($methodName, 3));
            }
            if (strpos($methodName, 'has') === 0) {
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
            $declaredPropertyNames = array_keys((array)$object);
        } else {
            $declaredPropertyNames = array_keys(get_class_vars(get_class($object)));
        }
        foreach (get_class_methods($object) as $methodName) {
            if (strpos($methodName, 'set') === 0 && is_callable([$object, $methodName])) {
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
        if ($object instanceof \stdClass && array_key_exists($propertyName, get_object_vars($object))) {
            return true;
        }
        if (array_key_exists($propertyName, get_class_vars(get_class($object)))) {
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
        if ($object instanceof \ArrayAccess && isset($object[$propertyName])) {
            return true;
        }
        if ($object instanceof \stdClass && isset($object->$propertyName)) {
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
        if (property_exists($object, $propertyName)) {
            $propertyReflection = new \ReflectionProperty($object, $propertyName);
            return $propertyReflection->isPublic();
        }
        return false;
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
            $properties[$propertyName] = self::getPropertyInternal($object, $propertyName);
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
