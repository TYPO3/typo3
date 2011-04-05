<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Extbase Team
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Internal TYPO3 Dependency Injection container
 *
 * @author Daniel Pötzinger
 * @author Sebastian Kurfürst
 * @author Timo Schmidt
 */
class Tx_Extbase_Object_Container_Container implements t3lib_Singleton {

	/**
	 * internal cache for classinfos
	 *
	 * @var Tx_Extbase_Object_Container_ClassInfoCache
	 */
	private $cache = NULL;

	/**
	 * registered alternative implementations of a class
	 * e.g. used to know the class for a AbstractClass or a Dependency
	 *
	 * @var array
	 */
	private $alternativeImplementation;

	/**
	 * reference to the classinfofactory, that analyses dependencys
	 *
	 * @var Tx_Extbase_Object_Container_ClassInfoFactory
	 */
	private $classInfoFactory = NULL;

	/**
	 * holds references of singletons
	 *
	 * @var array
	 */
	private $singletonInstances = array();

	/**
	 * Array of prototype objects currently being built, to prevent recursion.
	 *
	 * @var array
	 */
	private $prototypeObjectsWhichAreCurrentlyInstanciated;

	/**
	 * Constructor is protected since container should
	 * be a singleton.
	 *
	 * @see getContainer()
	 * @param void
	 * @return void
	 */
	public function __construct() { }

	/**
	 * Internal method to create the classInfoFactory, extracted to be mockable.
	 *
	 * @return Tx_Extbase_Object_Container_ClassInfoFactory
	 */
	protected function getClassInfoFactory() {
		if($this->classInfoFactory == NULL) {
			$this->classInfoFactory = t3lib_div::makeInstance('Tx_Extbase_Object_Container_ClassInfoFactory');
		}

		return $this->classInfoFactory;
	}

	/**
	 * Internal method to create the classInfoCache, extracted to be mockable.
	 *
	 * @return Tx_Extbase_Object_Container_ClassInfoCache
	 */
	protected function getClassInfoCache() {
		if($this->cache == NULL) {
			$this->cache = t3lib_div::makeInstance('Tx_Extbase_Object_Container_ClassInfoCache');
		}

		return $this->cache;
	}

	/**
	 * Main method which should be used to get an instance of the wished class
	 * specified by $className.
	 *
	 * @param string $className
	 * @param array $givenConstructorArguments the list of constructor arguments as array
	 * @return object the built object
	 */
	public function getInstance($className, $givenConstructorArguments = array()) {
		$this->prototypeObjectsWhichAreCurrentlyInstanciated = array();

		return $this->getInstanceInternal($className, $givenConstructorArguments);
	}

	/**
	 * Create an instance of $className without calling its constructor
	 *
	 * @param string $className
	 * @return object
	 */
	public function getEmptyObject($className) {
		$className = $this->getImplementationClassName($className);
		$classInfo = $this->getClassInfo($className);
		// get an object and avoid calling __construct()
		$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		$this->injectDependencies($object, $classInfo);
		return $object;
	}

	/**
	 * Internal implementation for getting a class.
	 *
	 * @param string $className
	 * @param array $givenConstructorArguments the list of constructor arguments as array
	 * @return object the built object
	 */
	protected function getInstanceInternal($className, $givenConstructorArguments = array()) {
		$className = $this->getImplementationClassName($className);

		if ($className === 'Tx_Extbase_Object_Container_Container') {
			return $this;
		}

		if (isset($this->singletonInstances[$className])) {
			if (count($givenConstructorArguments) > 0) {
				throw new Tx_Extbase_Object_Exception('Object "' . $className . '" fetched from singleton cache, thus, explicit constructor arguments are not allowed.', 1292857934);
			}
			return $this->singletonInstances[$className];
		}

		$classInfo = $this->getClassInfo($className);

		$classIsSingleton = $classInfo->getIsSingleton();
		if (!$classIsSingleton) {
			if (array_key_exists($className, $this->prototypeObjectsWhichAreCurrentlyInstanciated) !== FALSE) {
				throw new Tx_Extbase_Object_Exception_CannotBuildObject('Cyclic dependency in prototype object, for class "' . $className . '".', 1295611406);
			}
			$this->prototypeObjectsWhichAreCurrentlyInstanciated[$className] = TRUE;
		}

		$instance = $this->instanciateObject($classInfo, $givenConstructorArguments);
		$this->injectDependencies($instance, $classInfo);

		if ($classInfo->getIsInitializeable() && is_callable(array($instance, 'initializeObject'))) {
			$instance->initializeObject();
		}

		if (!$classIsSingleton) {
			unset($this->prototypeObjectsWhichAreCurrentlyInstanciated[$className]);
		}

		return $instance;
	}

	/**
	 * Instanciates an object, possibly setting the constructor dependencies.
	 * Additionally, directly registers all singletons in the singleton registry,
	 * such that circular references of singletons are correctly instanciated.
	 *
	 * @param Tx_Extbase_Object_Container_ClassInfo $classInfo
	 * @param array $givenConstructorArguments
	 * @return object the new instance
	 */
	protected function instanciateObject(Tx_Extbase_Object_Container_ClassInfo $classInfo, array $givenConstructorArguments) {
		$className = $classInfo->getClassName();
		$classIsSingleton = $classInfo->getIsSingleton();

		if ($classIsSingleton && count($givenConstructorArguments) > 0) {
			throw new Tx_Extbase_Object_Exception('Object "' . $className . '" has explicit constructor arguments but is a singleton; this is not allowed.', 1292858051);
		}

		$constructorArguments = $this->getConstructorArguments($className, $classInfo, $givenConstructorArguments);
		array_unshift($constructorArguments, $className);
		$instance = call_user_func_array(array('t3lib_div', 'makeInstance'), $constructorArguments);

		if ($classIsSingleton) {
			$this->singletonInstances[$className] = $instance;
		}
		return $instance;
	}

	/**
	 * Inject setter-dependencies into $instance
	 *
	 * @param object $instance
	 * @param Tx_Extbase_Object_Container_ClassInfo $classInfo
	 * @return void
	 */
	protected function injectDependencies($instance, Tx_Extbase_Object_Container_ClassInfo $classInfo) {
		if (!$classInfo->hasInjectMethods()) return;

		foreach ($classInfo->getInjectMethods() as $injectMethodName => $classNameToInject) {

			$instanceToInject = $this->getInstanceInternal($classNameToInject);
			if ($classInfo->getIsSingleton() && !($instanceToInject instanceof t3lib_Singleton)) {
				$this->log('The singleton "' . $classInfo->getClassName() . '" needs a prototype in "' . $injectMethodName . '". This is often a bad code smell; often you rather want to inject a singleton.', 1);
			}

			$instance->$injectMethodName($instanceToInject);
		}
	}

	/**
	 * Wrapper for dev log, in order to ease testing
	 *
	 * @param	string		Message (in english).
	 * @param	integer		Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
	 * @return	void
	 */
	protected function log($message, $severity) {
		t3lib_div::devLog($message, 'extbase', $severity);
	}

	/**
	 * register a classname that should be used if a dependency is required.
	 * e.g. used to define default class for a interface
	 *
	 * @param string $className
	 * @param string $alternativeClassName
	 */
	public function registerImplementation($className,$alternativeClassName) {
		$this->alternativeImplementation[$className] = $alternativeClassName;
	}

	/**
	 * gets array of parameter that can be used to call a constructor
	 *
	 * @param string $className
	 * @param Tx_Extbase_Object_Container_ClassInfo $classInfo
	 * @param array $givenConstructorArguments
	 * @return array
	 */
	private function getConstructorArguments($className, Tx_Extbase_Object_Container_ClassInfo $classInfo, array $givenConstructorArguments) {
		$parameters=array();

		$constructorArgumentInformation = $classInfo->getConstructorArguments();

		foreach ($constructorArgumentInformation as $argumentInformation) {
			$argumentName = $argumentInformation['name'];

			// We have a dependency we can automatically wire,
			// AND the class has NOT been explicitely passed in
			if (isset($argumentInformation['dependency']) && !(count($givenConstructorArguments) && is_a($givenConstructorArguments[0], $argumentInformation['dependency']))) {
				// Inject parameter
				$parameter = $this->getInstanceInternal($argumentInformation['dependency']);
				if ($classInfo->getIsSingleton() && !($parameter instanceof t3lib_Singleton)) {
					$this->log('The singleton "' . $className . '" needs a prototype in the constructor. This is often a bad code smell; often you rather want to inject a singleton.', 1);
				}
			} elseif (count($givenConstructorArguments)) {
				// EITHER:
				// No dependency injectable anymore, but we still have
				// an explicit constructor argument
				// OR:
				// the passed constructor argument matches the type for the dependency
				// injection, and thus the passed constructor takes precendence over
				// autowiring.
				$parameter = array_shift($givenConstructorArguments);
			} elseif (array_key_exists('defaultValue', $argumentInformation)) {
				// no value to set anymore, we take default value
				$parameter = $argumentInformation['defaultValue'];
			} else {
				throw new InvalidArgumentException('not a correct info array of constructor dependencies was passed!');
			}
			$parameters[] = $parameter;
		}
		return $parameters;
	}

	/**
	 * Returns the class name for a new instance, taking into account the
	 * class-extension API.
	 *
	 * @param	string		Base class name to evaluate
	 * @return	string		Final class name to instantiate with "new [classname]"
	 */
	protected function getImplementationClassName($className) {
		if (isset($this->alternativeImplementation[$className])) {
			$className = $this->alternativeImplementation[$className];
		}

		if (substr($className, -9) === 'Interface') {
			$className = substr($className, 0, -9);
		}

		return $className;
	}

	/**
	 * Gets Classinfos for the className - using the cache and the factory
	 *
	 * @param string $className
	 * @return Tx_Extbase_Object_Container_ClassInfo
	 */
	private function getClassInfo($className) {
			// we also need to make sure that the cache is returning a vaild object
			// in case something went wrong with unserialization etc..
		if (!$this->getClassInfoCache()->has($className) || !is_object($this->getClassInfoCache()->get($className))) {
			$this->getClassInfoCache()->set($className, $this->getClassInfoFactory()->buildClassInfoFromClassName($className));
		}

		return $this->getClassInfoCache()->get($className);
	}
}