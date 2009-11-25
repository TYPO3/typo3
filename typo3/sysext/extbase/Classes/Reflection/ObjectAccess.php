<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
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
 *
 * @package Extbase
 * @subpackage Reflection
 * @version $Id: ObjectAccess.php 1481 2009-10-21 09:44:20Z sebastian $
 */
class Tx_Extbase_Reflection_ObjectAccess {

	const ACCESS_GET = 0;
	const ACCESS_SET = 1;
	const ACCESS_PUBLIC = 2;

	/**
	 * Get a property of a given object.
	 * Tries to get the property the following ways:
	 * - if the target object is an instance of ArrayAccess, it gets the property
	 *   on it if it exists.
	 * - if public getter method exists, call it.
	 * - if public property exists, return the value of it.
	 * - else, throw exception
	 *
	 * @param object $object Object to get the property from
	 * @param string $propertyName name of the property to retrieve
	 * @return object Value of the property.
	 */
	static public function getProperty($object, $propertyName) {
		if (!is_object($object) && !is_array($object)) throw new InvalidArgumentException('$object must be an object or an array, ' . gettype($object). ' given.', 1237301367);
		if (!is_string($propertyName)) throw new InvalidArgumentException('Given property name is not of type string.', 1231178303);

		if (is_array($object) && array_key_exists($propertyName, $object)) {
			return $object[$propertyName];
		} elseif (is_callable(array($object, $getterMethodName = self::buildGetterMethodName($propertyName)))) {
			return call_user_func(array($object, $getterMethodName));
		} elseif ($object instanceof ArrayAccess && isset($object[$propertyName])) {
			return $object[$propertyName];
		} elseif (array_key_exists($propertyName, get_object_vars($object))) {
			return $object->$propertyName;
		}
		return NULL;
	}

	/**
	 * Gets a property path from a given object.
	 * If propertyPath is "bla.blubb", then we first call getProperty($object, 'bla'),
	 * and on the resulting object we call getProperty(..., 'blubb')
	 *
	 * @param object $object
	 * @param string $propertyPath
	 * @return object Value of the property
	 */
	static public function getPropertyPath($object, $propertyPath) {
		$propertyPathSegments = explode('.', $propertyPath);
		foreach ($propertyPathSegments as $pathSegment) {
			$object = self::getProperty($object, $pathSegment);
			if ($object === NULL) return NULL;
		}
		return $object;
	}

	/**
	 * Set a property for a given object.
	 * Tries to set the property the following ways:
	 * - if public setter method exists, call it.
	 * - if public property exists, set it directly.
	 * - if the target object is an instance of ArrayAccess, it sets the property
	 *   on it without checking if it existed.
	 * - else, return FALSE
	 *
	 * @param object $object The target object
	 * @param string $propertyName Name of the property to set
	 * @param object $propertyValue Value of the property
	 * @return void
	 * @throws Tx_Extbase_Reflection_Exception if property was could not be set
	 */
	static public function setProperty($object, $propertyName, $propertyValue) {
		if (!is_object($object)) throw new InvalidArgumentException('$object must be an object, ' . gettype($object). ' given.', 1237301368);
		if (!is_string($propertyName)) throw new InvalidArgumentException('Given property name is not of type string.', 1231178878);

		if (is_callable(array($object, $setterMethodName = self::buildSetterMethodName($propertyName)))) {
			call_user_func(array($object, $setterMethodName), $propertyValue);
		} elseif ($object instanceof ArrayAccess) {
			$object[$propertyName] = $propertyValue;
		} elseif (array_key_exists($propertyName, get_object_vars($object))) {
			$object->$propertyName = $propertyValue;
		} else {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Returns an array of properties which can be get/set with the getProperty
	 * and setProperty methods.
	 * Includes the following properties:
	 * - which can be set through a public setter method.
	 * - public properties which can be directly set.
	 *
	 * @param object $object Object to receive property names for
	 * @return array Array of all declared property names
	 * @todo What to do with ArrayAccess
	 */
	static public function getAccessiblePropertyNames($object) {
		if (!is_object($object)) throw new InvalidArgumentException('$object must be an object, ' . gettype($object). ' given.', 1237301369);
		$declaredPropertyNames = array_keys(get_class_vars(get_class($object)));

		foreach (get_class_methods($object) as $methodName) {
			if (substr($methodName, 0, 3) === 'get') {
				$propertyName = substr($methodName, 3);
				$propertyName[0] = strtolower($propertyName[0]);
				$declaredPropertyNames[] = $propertyName;
			}
		}

		$propertyNames = array_unique($declaredPropertyNames);
		sort($propertyNames);
		return $propertyNames;
	}

	/**
	 * Get all properties (names and their current values) of the current
	 * $object that are accessible through this class.
	 *
	 * @param object $object Object to get all properties from.
	 * @return array Associative array of all properties.
	 * @todo What to do with ArrayAccess
	 */
	static public function getAccessibleProperties($object) {
		if (!is_object($object)) throw new InvalidArgumentException('$object must be an object, ' . gettype($object). ' given.', 1237301370);
		$properties = array();
		foreach (self::getAccessiblePropertyNames($object) as $propertyName) {
			$properties[$propertyName] = self::getProperty($object, $propertyName);
		}
		return $properties;
	}

	/**
	 * Build the getter method name for a given property by capitalizing the
	 * first letter of the property, and prepending it with "get".
	 *
	 * @param string $property Name of the property
	 * @return string Name of the getter method name
	 */
	static protected function buildGetterMethodName($property) {
		return 'get' . ucfirst($property);
	}

	/**
	 * Build the setter method name for a given property by capitalizing the
	 * first letter of the property, and prepending it with "set".
	 *
	 * @param string $property Name of the property
	 * @return string Name of the setter method name
	 */
	static protected function buildSetterMethodName($property) {
		return 'set' . ucfirst($property);
	}
}


?>