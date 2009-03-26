<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id: ObjectAccess.php 2031 2009-03-24 11:36:56Z robert $
 */
/**
 * Provides methods to call appropriate getter/setter on an object given the
 * property name. It does this following these rules:
 * - if the target object is an instance of ArrayAccess, it gets/sets the property
 * - if public getter/setter method exists, call it.
 * - if public property exists, return/set the value of it.
 * - else, throw exception
 *
 * @package
 * @subpackage
 * @version $Id: ObjectAccess.php 2031 2009-03-24 11:36:56Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_ExtBase_Reflection_ObjectAccess {

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
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static public function getProperty($object, $propertyName) {
		if (!is_object($object)) throw new InvalidArgumentException('$object must be an object, ' . gettype($object). ' given.', 1237301367);
		if (!is_string($propertyName)) throw new InvalidArgumentException('Given property name is not of type string.', 1231178303);

		if (is_callable(array($object, $getterMethodName = self::buildGetterMethodName($propertyName)))) {
			return call_user_func(array($object, $getterMethodName));
		} elseif ($object instanceof ArrayAccess && isset($object[$propertyName])) {
			return $object[$propertyName];
		} elseif (array_key_exists($propertyName, get_object_vars($object))) {
			return $object->$propertyName;
		}
		return NULL;
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
	 * @throws Tx_ExtBase_Reflection_Exception if property was could not be set
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static public function setProperty($object, $propertyName, $propertyValue) {
		if (!is_object($object)) throw new InvalidArgumentException('$object must be an object, ' . gettype($object). ' given.', 1237301368);
		if (!is_string($propertyName)) throw new InvalidArgumentException('Given property name is not of type string.', 1231178878);

		if (is_callable(array($object, $setterMethodName = self::buildSetterMethodName($propertyName)))) {
			call_user_func(array($object, $setterMethodName), $propertyValue);
		} elseif ($object instanceof \ArrayAccess) {
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo What to do with ArrayAccess
	 */
	static public function getAccessiblePropertyNames($object) {
		if (!is_object($object)) throw new InvalidArgumentException('$object must be an object, ' . gettype($object). ' given.', 1237301369);
		$declaredPropertyNames = array_keys(get_class_vars(get_class($object)));

		foreach (get_class_methods($object) as $methodName) {
			if (substr($methodName, 0, 3) === 'get') {
				$declaredPropertyNames[] = lcfirst(substr($methodName, 3));
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
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
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	static protected function buildSetterMethodName($property) {
		return 'set' . ucfirst($property);
	}
}


?>