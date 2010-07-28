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
 * A backport of the FLOW3 reflection service for aquiring reflection based information.
 * Most of the code is based on the FLOW3 reflection service.
 *
 * @package Extbase
 * @subpackage Reflection
 * @version $Id: Service.php 1789 2010-01-18 21:31:59Z jocrau $
 * @api
 */
class Tx_Extbase_Reflection_Service implements t3lib_Singleton {

	/**
	 * Whether this service has been initialized.
	 *
	 * @var boolean
	 */
	protected $initialized = FALSE;

	/**
	 * @var t3lib_cache_frontend_VariableFrontend
	 */
	protected $cache;

	/**
	 * Whether class alterations should be detected on each initialization.
	 *
	 * @var boolean
	 */
	protected $detectClassChanges = FALSE;

	/**
	 * All available class names to consider. Class name = key, value is the
	 * UNIX timestamp the class was reflected.
	 *
	 * @var array
	 */
	protected $reflectedClassNames = array();

	/**
	 * Array of tags and the names of classes which are tagged with them.
	 *
	 * @var array
	 */
	protected $taggedClasses = array();

	/**
	 * Array of class names and their tags and values.
	 *
	 * @var array
	 */
	protected $classTagsValues = array();

	/**
	 * Array of class names, method names and their tags and values.
	 *
	 * @var array
	 */
	protected $methodTagsValues = array();

	/**
	 * Array of class names, method names, their parameters and additional
	 * information about the parameters.
	 *
	 * @var array
	 */
	protected $methodParameters = array();

	/**
	 * Array of class names and names of their properties.
	 *
	 * @var array
	 */
	protected $classPropertyNames = array();

	/**
	 * Array of class names, property names and their tags and values.
	 *
	 * @var array
	 */
	protected $propertyTagsValues = array();

	/**
	 * List of tags which are ignored while reflecting class and method annotations.
	 *
	 * @var array
	 */
	protected $ignoredTags = array('package', 'subpackage', 'license', 'copyright', 'author', 'version', 'const');

	/**
	 * Indicates whether the Reflection cache needs to be updated.
	 *
	 * This flag needs to be set as soon as new Reflection information was
	 * created.
	 *
	 * @see reflectClass()
	 * @see getMethodReflection()
	 *
	 * @var boolean
	 */
	protected $cacheNeedsUpdate = FALSE;

	/**
	 * Local cache for Class schemata
	 * @var array
	 */
	protected $classSchemata = array();

	/**
	 * Sets the cache.
	 *
	 * The cache must be set before initializing the Reflection Service.
	 *
	 * @param t3lib_cache_frontend_VariableFrontend $cache Cache for the Reflection service
	 * @return void
	 */
	public function setCache(t3lib_cache_frontend_VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Initializes this service
	 *
	 * @param array $classNamesToReflect Names of available classes to consider in this reflection service
	 * @return void
	 */
	public function initialize() {
		if ($this->initialized) throw new Tx_Extbase_Reflection_Exception('The Reflection Service can only be initialized once.', 1232044696);

		$this->loadFromCache();

		$this->initialized = TRUE;
	}

	/**
	 * Returns whether the Reflection Service is initialized.
	 *
	 * @return boolean true if the Reflection Service is initialized, otherwise false
	 */
	public function isInitialized() {
		return $this->initialized;
	}

	/**
	 * Shuts the Reflection Service down.
	 *
	 * @return void
	 */
	public function shutdown() {
		if ($this->cacheNeedsUpdate) {
			$this->saveToCache();
		}
	}

	/**
	 * Returns the names of all properties of the specified class
	 *
	 * @param string $className Name of the class to return the property names of
	 * @return array An array of property names or an empty array if none exist
	 */
	public function getClassPropertyNames($className) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->classPropertyNames[$className])) ? $this->classPropertyNames[$className] : array();
	}

	/**
	 * Returns the class schema for the given class
	 *
	 * @param mixed $classNameOrObject The class name or an object
	 * @return Tx_Extbase_Reflection_ClassSchema
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getClassSchema($classNameOrObject) {
		$className = is_object($classNameOrObject) ? get_class($classNameOrObject) : $classNameOrObject;
		if (isset($this->classSchemata[$className])) {
			return $this->classSchemata[$className];
		} else {
			return $this->buildClassSchema($className);
		}

	}

	/**
	 * Returns all tags and their values the specified method is tagged with
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return the tags and values of
	 * @return array An array of tags and their values or an empty array of no tags were found
	 */
	public function getMethodTagsValues($className, $methodName) {
		if (!isset($this->methodTagsValues[$className][$methodName])) {
			$this->methodTagsValues[$className][$methodName] = array();
			$method = $this->getMethodReflection($className, $methodName);
			foreach ($method->getTagsValues() as $tag => $values) {
				if (array_search($tag, $this->ignoredTags) === FALSE) {
					$this->methodTagsValues[$className][$methodName][$tag] = $values;
				}
			}
		}
		return $this->methodTagsValues[$className][$methodName];
	}


	/**
	 * Returns an array of parameters of the given method. Each entry contains
	 * additional information about the parameter position, type hint etc.
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return parameter information of
	 * @return array An array of parameter names and additional information or an empty array of no parameters were found
	 */
	public function getMethodParameters($className, $methodName) {
		if (!isset($this->methodParameters[$className][$methodName])) {
			$method = $this->getMethodReflection($className, $methodName);
			$this->methodParameters[$className][$methodName] = array();
			foreach($method->getParameters() as $parameterPosition => $parameter) {
				$this->methodParameters[$className][$methodName][$parameter->getName()] = $this->convertParameterReflectionToArray($parameter, $parameterPosition, $method);
			}
		}
		return $this->methodParameters[$className][$methodName];
	}

	/**
	 * Returns all tags and their values the specified class property is tagged with
	 *
	 * @param string $className Name of the class containing the property
	 * @param string $propertyName Name of the property to return the tags and values of
	 * @return array An array of tags and their values or an empty array of no tags were found
	 */
	public function getPropertyTagsValues($className, $propertyName) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->propertyTagsValues[$className])) return array();
		return (isset($this->propertyTagsValues[$className][$propertyName])) ? $this->propertyTagsValues[$className][$propertyName] : array();
	}

	/**
	 * Returns the values of the specified class property tag
	 *
	 * @param string $className Name of the class containing the property
	 * @param string $propertyName Name of the tagged property
	 * @param string $tag Tag to return the values of
	 * @return array An array of values or an empty array if the tag was not found
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPropertyTagValues($className, $propertyName, $tag) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->propertyTagsValues[$className][$propertyName])) return array();
		return (isset($this->propertyTagsValues[$className][$propertyName][$tag])) ? $this->propertyTagsValues[$className][$propertyName][$tag] : array();
	}

	/**
	 * Tells if the specified class is known to this reflection service and
	 * reflection information is available.
	 *
	 * @param string $className Name of the class
	 * @return boolean If the class is reflected by this service
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isClassReflected($className) {
		return isset($this->reflectedClassNames[$className]);
	}

	/**
	 * Tells if the specified class is tagged with the given tag
	 *
	 * @param string $className Name of the class
	 * @param string $tag Tag to check for
	 * @return boolean TRUE if the class is tagged with $tag, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isClassTaggedWith($className, $tag) {
		if ($this->initialized === FALSE) return FALSE;
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->classTagsValues[$className])) return FALSE;
		return isset($this->classTagsValues[$className][$tag]);
	}

	/**
	 * Tells if the specified class property is tagged with the given tag
	 *
	 * @param string $className Name of the class
	 * @param string $propertyName Name of the property
	 * @param string $tag Tag to check for
	 * @return boolean TRUE if the class property is tagged with $tag, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isPropertyTaggedWith($className, $propertyName, $tag) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->propertyTagsValues[$className])) return FALSE;
		if (!isset($this->propertyTagsValues[$className][$propertyName])) return FALSE;
		return isset($this->propertyTagsValues[$className][$propertyName][$tag]);
	}

	/**
	 * Reflects the given class and stores the results in this service's properties.
	 *
	 * @param string $className Full qualified name of the class to reflect
	 * @return void
	 */
	protected function reflectClass($className) {
		$class = new Tx_Extbase_Reflection_ClassReflection($className);
		$this->reflectedClassNames[$className] = time();

		foreach ($class->getTagsValues() as $tag => $values) {
			if (array_search($tag, $this->ignoredTags) === FALSE) {
				$this->taggedClasses[$tag][] = $className;
				$this->classTagsValues[$className][$tag] = $values;
			}
		}

		foreach ($class->getProperties() as $property) {
			$propertyName = $property->getName();
			$this->classPropertyNames[$className][] = $propertyName;

			foreach ($property->getTagsValues() as $tag => $values) {
				if (array_search($tag, $this->ignoredTags) === FALSE) {
					$this->propertyTagsValues[$className][$propertyName][$tag] = $values;
				}
			}
		}

		foreach ($class->getMethods() as $method) {
			$methodName = $method->getName();
			foreach ($method->getTagsValues() as $tag => $values) {
				if (array_search($tag, $this->ignoredTags) === FALSE) {
					$this->methodTagsValues[$className][$methodName][$tag] = $values;
				}
			}

			foreach ($method->getParameters() as $parameterPosition => $parameter) {
				$this->methodParameters[$className][$methodName][$parameter->getName()] = $this->convertParameterReflectionToArray($parameter, $parameterPosition, $method);
			}
		}
		ksort($this->reflectedClassNames);

		$this->cacheNeedsUpdate = TRUE;
	}

	/**
	 * Builds class schemata from classes annotated as entities or value objects
	 *
	 * @return Tx_Extbase_Reflection_ClassSchema The class schema
	 */
	protected function buildClassSchema($className) {
		if (!class_exists($className)) {
			return NULL;
		}
		$classSchema = new Tx_Extbase_Reflection_ClassSchema($className);
		if (is_subclass_of($className, 'Tx_Extbase_DomainObject_AbstractEntity')) {
			$classSchema->setModelType(Tx_Extbase_Reflection_ClassSchema::MODELTYPE_ENTITY);

			$possibleRepositoryClassName = str_replace('_Model_', '_Repository_', $className) . 'Repository';
			if (class_exists($possibleRepositoryClassName)) {
				$classSchema->setAggregateRoot(TRUE);
			}
		} elseif (is_subclass_of($className, 'Tx_Extbase_DomainObject_AbstractValueObject')) {
			$classSchema->setModelType(Tx_Extbase_Reflection_ClassSchema::MODELTYPE_VALUEOBJECT);
		} else {
			return NULL;
		}

		foreach ($this->getClassPropertyNames($className) as $propertyName) {
			if (!$this->isPropertyTaggedWith($className, $propertyName, 'transient') && $this->isPropertyTaggedWith($className, $propertyName, 'var')) {
				$cascadeTagValues = $this->getPropertyTagValues($className, $propertyName, 'cascade');
				$classSchema->addProperty($propertyName, implode(' ', $this->getPropertyTagValues($className, $propertyName, 'var')), $this->isPropertyTaggedWith($className, $propertyName, 'lazy'), $cascadeTagValues[0]);
			}
			if ($this->isPropertyTaggedWith($className, $propertyName, 'uuid')) {
				$classSchema->setUUIDPropertyName($propertyName);
			}
			if ($this->isPropertyTaggedWith($className, $propertyName, 'identity')) {
				$classSchema->markAsIdentityProperty($propertyName);
			}
		}
		$this->classSchemata[$className] = $classSchema;
		$this->cacheNeedsUpdate = TRUE;
		return $classSchema;
	}

	/**
	 * Converts the given parameter reflection into an information array
	 *
	 * @param ReflectionParameter $parameter The parameter to reflect
	 * @return array Parameter information array
	 */
	protected function convertParameterReflectionToArray(ReflectionParameter $parameter, $parameterPosition, ReflectionMethod $method = NULL) {
		$parameterInformation = array(
			'position' => $parameterPosition,
			'byReference' => $parameter->isPassedByReference() ? TRUE : FALSE,
			'array' => $parameter->isArray() ? TRUE : FALSE,
			'optional' => $parameter->isOptional() ? TRUE : FALSE,
			'allowsNull' => $parameter->allowsNull() ? TRUE : FALSE
		);

		$parameterClass = $parameter->getClass();
		$parameterInformation['class'] = ($parameterClass !== NULL) ? $parameterClass->getName() : NULL;
		if ($parameter->isDefaultValueAvailable()) {
			$parameterInformation['defaultValue'] = $parameter->getDefaultValue();
		}
		if ($parameterClass !== NULL) {
			$parameterInformation['type'] = $parameterClass->getName();
		} elseif ($method !== NULL) {
			$methodTagsAndValues = $this->getMethodTagsValues($method->getDeclaringClass()->getName(), $method->getName());
			if (isset($methodTagsAndValues['param']) && isset($methodTagsAndValues['param'][$parameterPosition])) {
				$explodedParameters = explode(' ', $methodTagsAndValues['param'][$parameterPosition]);
				if (count($explodedParameters) >= 2) {
					$parameterInformation['type'] = $explodedParameters[0];
				}
			}
		}
		if (isset($parameterInformation['type']) && $parameterInformation['type']{0} === '\\') {
			$parameterInformation['type'] = substr($parameterInformation['type'], 1);
		}
		return $parameterInformation;
	}

	/**
	 * Returns the Reflection of a method.
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return the Reflection for
	 * @return Tx_Extbase_Reflection_MethodReflection the method Reflection object
	 */
	protected function getMethodReflection($className, $methodName) {
		if (!isset($this->methodReflections[$className][$methodName])) {
			$this->methodReflections[$className][$methodName] = new Tx_Extbase_Reflection_MethodReflection($className, $methodName);
			$this->cacheNeedsUpdate = TRUE;
		}
		return $this->methodReflections[$className][$methodName];
	}

	/**
	 * Tries to load the reflection data from this service's cache.
	 *
	 * @return void
	 */
	protected function loadFromCache() {
		$cacheKey = $this->getCacheKey();
		if ($this->cache->has($cacheKey)) {
			$data = $this->cache->get($cacheKey);
			foreach ($data as $propertyName => $propertyValue) {
				$this->$propertyName = $propertyValue;
			}
		}
	}

	/**
	 * Exports the internal reflection data into the ReflectionData cache.
	 *
	 * @return void
	 */
	protected function saveToCache() {
		if (!is_object($this->cache)) {
			throw new Tx_Extbase_Reflection_Exception(
				'A cache must be injected before initializing the Reflection Service.',
				1232044697
			);
		}

		$data = array();
		$propertyNames = array(
			'reflectedClassNames',
			'classPropertyNames',
			'classTagsValues',
			'methodTagsValues',
			'methodParameters',
			'propertyTagsValues',
			'taggedClasses',
			'classSchemata'
		);
		foreach ($propertyNames as $propertyName) {
			$data[$propertyName] = $this->$propertyName;
		}
		$this->cache->set($this->getCacheKey(), $data);
	}

	/**
	 * Get the name of the cache row identifier. Incorporates the extension name
	 * and the plugin name so that all information which is needed for a single
	 * plugin can be found in one cache row.
	 *
	 * @return string
	 */
	protected function getCacheKey() {
		$frameworkConfiguration = Tx_Extbase_Dispatcher::getExtbaseFrameworkConfiguration();
		return $frameworkConfiguration['extensionName'] . '_' . $frameworkConfiguration['pluginName'];
	}
}
?>
