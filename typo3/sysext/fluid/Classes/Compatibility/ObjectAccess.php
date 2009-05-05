<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package
 * @subpackage
 * @version $Id$
 */
class Tx_Fluid_Compatibility_ObjectAccess {

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
				$declaredPropertyNames[] = strtolower($methodName[3]) . substr($methodName, 4);
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
}


?>