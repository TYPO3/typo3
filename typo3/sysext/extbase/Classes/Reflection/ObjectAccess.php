<?php
namespace TYPO3\CMS\Extbase\Reflection;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Provides methods to call appropriate getter/setter on an object given the
 * property name. It does this following these rules:
 * - if the target object is an instance of ArrayAccess, it gets/sets the property
 * - if public getter/setter method exists, call it.
 * - if public property exists, return/set the value of it.
 * - else, throw exception
 */
class ObjectAccess {

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
	 * @param boolean $forceDirectAccess directly access property using reflection(!)
	 *
	 * @throws Exception\PropertyNotAccessibleException
	 * @throws \InvalidArgumentException in case $subject was not an object or $propertyName was not a string
	 * @return mixed Value of the property
	 */
	static public function getProperty($subject, $propertyName, $forceDirectAccess = FALSE) {
		if (!is_object($subject) && !is_array($subject)) {
			throw new \InvalidArgumentException('$subject must be an object or array, ' . gettype($subject) . ' given.', 1237301367);
		}
		if (!is_string($propertyName) && (!is_array($subject) && !$subject instanceof \ArrayAccess)) {
			throw new \InvalidArgumentException('Given property name is not of type string.', 1231178303);
		}
		$propertyExists = FALSE;
		$propertyValue = self::getPropertyInternal($subject, $propertyName, $forceDirectAccess, $propertyExists);
		if ($propertyExists === TRUE) {
			return $propertyValue;
		}
		throw new \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException('The property "' . $propertyName . '" on the subject was not accessible.', 1263391473);
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
	 * @param boolean $forceDirectAccess directly access property using reflection(!)
	 * @param boolean &$propertyExists (by reference) will be set to TRUE if the specified property exists and is gettable
	 *
	 * @throws Exception\PropertyNotAccessibleException
	 * @return mixed Value of the property
	 * @internal
	 */
	static public function getPropertyInternal($subject, $propertyName, $forceDirectAccess, &$propertyExists) {
		if ($subject === NULL) {
			return NULL;
		}
		$propertyExists = TRUE;
		if (is_array($subject)) {
			if (array_key_exists($propertyName, $subject)) {
				return $subject[$propertyName];
			}
			$propertyExists = FALSE;
			return NULL;
		}
		if ($forceDirectAccess === TRUE) {
			if (property_exists(get_class($subject), $propertyName)) {
				$propertyReflection = new \TYPO3\CMS\Extbase\Reflection\PropertyReflection(get_class($subject), $propertyName);
				return $propertyReflection->getValue($subject);
			} elseif (property_exists($subject, $propertyName)) {
				return $subject->{$propertyName};
			} else {
				throw new \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException('The property "' . $propertyName . '" on the subject does not exist.', 1302855001);
			}
		}
		if (
			$subject instanceof \ArrayAccess
			&& !($subject instanceof \SplObjectStorage || $subject instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage)
			&& isset($subject[$propertyName])
		) {
			return $subject[$propertyName];
		}
		$getterMethodName = 'get' . ucfirst($propertyName);
		if (is_callable(array($subject, $getterMethodName))) {
			return $subject->{$getterMethodName}();
		}
		$getterMethodName = 'is' . ucfirst($propertyName);
		if (is_callable(array($subject, $getterMethodName))) {
			return $subject->{$getterMethodName}();
		}
		if (is_object($subject) && array_key_exists($propertyName, get_object_vars($subject))) {
			return $subject->{$propertyName};
		}
		$propertyExists = FALSE;
		return NULL;
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
	static public function getPropertyPath($subject, $propertyPath) {
		$propertyPathSegments = explode('.', $propertyPath);
		foreach ($propertyPathSegments as $pathSegment) {
			$propertyExists = FALSE;
			$propertyValue = self::getPropertyInternal($subject, $pathSegment, FALSE, $propertyExists);
			if (
				$propertyExists !== TRUE
				&& ($subject instanceof \SplObjectStorage || $subject instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage)
			) {
				$subject = NULL;
			} elseif (
				$propertyExists !== TRUE
				&& (is_array($subject) || $subject instanceof \ArrayAccess)
				&& isset($subject[$pathSegment])
			) {
				$subject = $subject[$pathSegment];
			} else {
				$subject = $propertyValue;
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
	 * @param boolean $forceDirectAccess directly access property using reflection(!)
	 *
	 * @throws \InvalidArgumentException in case $object was not an object or $propertyName was not a string
	 * @return boolean TRUE if the property could be set, FALSE otherwise
	 */
	static public function setProperty(&$subject, $propertyName, $propertyValue, $forceDirectAccess = FALSE) {
		if (is_array($subject)) {
			$subject[$propertyName] = $propertyValue;
			return TRUE;
		}
		if (!is_object($subject)) {
			throw new \InvalidArgumentException('subject must be an object or array, ' . gettype($subject) . ' given.', 1237301368);
		}
		if (!is_string($propertyName)) {
			throw new \InvalidArgumentException('Given property name is not of type string.', 1231178878);
		}
		if ($forceDirectAccess === TRUE) {
			if (property_exists(get_class($subject), $propertyName)) {
				$propertyReflection = new \TYPO3\CMS\Extbase\Reflection\PropertyReflection(get_class($subject), $propertyName);
				$propertyReflection->setAccessible(TRUE);
				$propertyReflection->setValue($subject, $propertyValue);
			} else {
				$subject->{$propertyName} = $propertyValue;
			}
		} elseif (is_callable(array($subject, $setterMethodName = self::buildSetterMethodName($propertyName)))) {
			$subject->{$setterMethodName}($propertyValue);
		} elseif ($subject instanceof \ArrayAccess) {
			$subject[$propertyName] = $propertyValue;
		} elseif (array_key_exists($propertyName, get_object_vars($subject))) {
			$subject->{$propertyName} = $propertyValue;
		} else {
			return FALSE;
		}
		return TRUE;
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
	static public function getGettablePropertyNames($object) {
		if (!is_object($object)) {
			throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1237301369);
		}
		if ($object instanceof \stdClass) {
			$declaredPropertyNames = array_keys(get_object_vars($object));
		} else {
			$declaredPropertyNames = array_keys(get_class_vars(get_class($object)));
		}
		foreach (get_class_methods($object) as $methodName) {
			if (is_callable(array($object, $methodName))) {
				if (substr($methodName, 0, 2) === 'is') {
					$declaredPropertyNames[] = lcfirst(substr($methodName, 2));
				}
				if (substr($methodName, 0, 3) === 'get') {
					$declaredPropertyNames[] = lcfirst(substr($methodName, 3));
				}
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
	static public function getSettablePropertyNames($object) {
		if (!is_object($object)) {
			throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1264022994);
		}
		if ($object instanceof \stdClass) {
			$declaredPropertyNames = array_keys(get_object_vars($object));
		} else {
			$declaredPropertyNames = array_keys(get_class_vars(get_class($object)));
		}
		foreach (get_class_methods($object) as $methodName) {
			if (substr($methodName, 0, 3) === 'set' && is_callable(array($object, $methodName))) {
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
	 * @return boolean
	 */
	static public function isPropertySettable($object, $propertyName) {
		if (!is_object($object)) {
			throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1259828920);
		}
		if ($object instanceof \stdClass && array_search($propertyName, array_keys(get_object_vars($object))) !== FALSE) {
			return TRUE;
		} elseif (array_search($propertyName, array_keys(get_class_vars(get_class($object)))) !== FALSE) {
			return TRUE;
		}
		return is_callable(array($object, self::buildSetterMethodName($propertyName)));
	}

	/**
	 * Tells if the value of the specified property can be retrieved by this Object Accessor.
	 *
	 * @param object $object Object containting the property
	 * @param string $propertyName Name of the property to check
	 *
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	static public function isPropertyGettable($object, $propertyName) {
		if (!is_object($object)) {
			throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1259828921);
		}
		if ($object instanceof \ArrayAccess && isset($object[$propertyName]) === TRUE) {
			return TRUE;
		} elseif ($object instanceof \stdClass && array_search($propertyName, array_keys(get_object_vars($object))) !== FALSE) {
			return TRUE;
		} elseif ($object instanceof \ArrayAccess && isset($object[$propertyName]) === TRUE) {
			return TRUE;
		}
		if (is_callable(array($object, 'get' . ucfirst($propertyName)))) {
			return TRUE;
		}
		if (is_callable(array($object, 'is' . ucfirst($propertyName)))) {
			return TRUE;
		}
		return array_search($propertyName, array_keys(get_class_vars(get_class($object)))) !== FALSE;
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
	static public function getGettableProperties($object) {
		if (!is_object($object)) {
			throw new \InvalidArgumentException('$object must be an object, ' . gettype($object) . ' given.', 1237301370);
		}
		$properties = array();
		foreach (self::getGettablePropertyNames($object) as $propertyName) {
			$propertyExists = FALSE;
			$propertyValue = self::getPropertyInternal($object, $propertyName, FALSE, $propertyExists);
			if ($propertyExists === TRUE) {
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
	static public function buildSetterMethodName($propertyName) {
		return 'set' . ucfirst($propertyName);
	}
}

?>