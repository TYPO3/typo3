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
 * TYPO3 Dependency Injection container
 * Initial Usage:
 *  $container = Tx_Extbase_Object_Container_Container::getContainer()
 *
 * @author Daniel Pötzinger
 */
class Tx_Extbase_Object_Container_Container {

	/**
	 * PHP singleton impelementation
	 *
	 * @var Tx_Extbase_Object_Container_Container
	 */
	static private $containerInstance = null;

	/**
	 * internal cache for classinfos
	 *
	 * @var Tx_Extbase_Object_Container_ClassInfoCache
	 */
	private $cache;

	/**
	 * registered alternative implementations of a class
	 * e.g. used to know the class for a AbstractClass or a Dependency
	 *
	 * @var array
	 */
	private $alternativeImplementation;

	/**
	 * reference to the classinfofactory, that analyses dependencys
	 * @var classInfoFactory
	 */
	private $classInfoFactory;

	/**
	 * holds references of singletons
	 * @var array
	 */
	private $singletonInstances = array();

	/**
	 * holds references of objects that still needs setter injection processing
	 * @var array
	 */
	private $setterInjectionRegistry = array();

	/**
	 * Constructor is protected since container should
	 * be a singleton.
	 *
	 * @see getContainer()
	 * @param void
	 * @return void
	 */
	protected function __construct() {
		$this->classInfoFactory = new Tx_Extbase_Object_Container_ClassInfoFactory();
		$this->cache = new Tx_Extbase_Object_Container_ClassInfoCache();
	}

	/**
	 * Returns an instance of the container singleton.
	 *
	 * @return Tx_Extbase_Object_Container_Container
	 */
	static public function getContainer() {
		if (self::$containerInstance === NULL) {
			self::$containerInstance = new Tx_Extbase_Object_Container_Container();
		}
		return self::$containerInstance;
	}

	private function __clone() {}

	/**
	 * gets an instance of the given class
	 * @param string $className
	 * @return object
	 */
	public function getInstance($className) {
		$givenConstructorArguments=array();
		if (func_num_args() > 1) {
				$givenConstructorArguments = func_get_args();
				array_shift($givenConstructorArguments);
		}
		$object = $this->getInstanceFromClassName($className, $givenConstructorArguments, 0);
		$this->processSetterInjectionRegistry();
		return $object;
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
	 * gets an instance of the given class
	 * @param string $className
	 * @param array $givenConstructorArguments
	 */
	private function getInstanceFromClassName($className, array $givenConstructorArguments=array(), $level=0) {
		if ($level > 30) {
			throw new Tx_Extbase_Object_Container_Exception_TooManyRecursionLevelsException('Too many recursion levels. This should never happen, please file a bug!' . $className, 1289386945);
		}
		if ($className === 'Tx_Extbase_Object_Container_Container') {
			return $this;
		}
		if (isset($this->singletonInstances[$className])) {
			return $this->singletonInstances[$className];
		}

		$className = $this->getClassName($className);

		$classInfo = $this->getClassInfo($className);

		$constructorArguments = $this->getConstructorArguments($classInfo->getConstructorArguments(), $givenConstructorArguments, $level);
		$instance = $this->newObject($className, $constructorArguments);

		if ($level > 0 && !($instance instanceof t3lib_Singleton)) {
			throw new Tx_Extbase_Object_Exception_WrongScope('Object "' . $className . '" is of not of scope singleton, but only singleton instances can be injected into other classes.', 1289387047);
		}

		if ($classInfo->hasInjectMethods()) {
			$this->setterInjectionRegistry[]=array($instance, $classInfo->getInjectMethods(), $level);
		}

		if ($instance instanceof t3lib_Singleton) {
			$this->singletonInstances[$className] = $instance;
		}
		return $instance;
	}

	/**
	 * returns a object of the given type, called with the constructor arguments.
	 * For speed improvements reflection is avoided
	 *
	 * @param string $className
	 * @param array $constructorArguments
	 */
	private function newObject($className, array $constructorArguments) {
		array_unshift($constructorArguments, $className);
		return call_user_func_array(array('t3lib_div', 'makeInstance'), $constructorArguments);
	}

	/**
	 * gets array of parameter that can be used to call a constructor
	 *
	 * @param array $constructorArgumentInformation
	 * @param array $givenConstructorArguments
	 * @return array
	 */
	private function getConstructorArguments(array $constructorArgumentInformation, array $givenConstructorArguments, $level) {
		$parameters=array();
		foreach ($constructorArgumentInformation as $argumentInformation) {
			$argumentName = $argumentInformation['name'];

			if (count($givenConstructorArguments)) {
				// we have a value to set
				$parameter = array_shift($givenConstructorArguments);
			} elseif (isset($argumentInformation['dependency'])) {
				// Inject parameter
				$parameter = $this->getInstanceFromClassName($argumentInformation['dependency'], array(), $level+1);
			} elseif (isset($argumentInformation['defaultValue'])) {
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
	protected function getClassName($className) {
		if (isset($this->alternativeImplementation[$className])) {
			$className = $this->alternativeImplementation[$className];
		}

		if (substr($className, -9) === 'Interface') {
			$className = substr($className, 0, -9);
		}

		return $className;
	}

	/**
	 * do inject dependecies to object $instance using the given methods
	 *
	 * @param object $instance
	 * @param array $setterMethods
	 * @param integer $level
	 */
	private function handleSetterInjection($instance, array $setterMethods, $level) {
		foreach ($setterMethods as $method => $dependency) {
			$instance->$method($this->getInstanceFromClassName($dependency, array(), $level+1));
		}
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
		if (!$this->cache->has($className) || !is_object($this->cache->get($className))) {
			$this->cache->set($className, $this->classInfoFactory->buildClassInfoFromClassName($className));
		}
		return $this->cache->get($className);
	}

	/**
	 * does setter injection based on the data in $this->setterInjectionRegistry
	 * Its done as long till no setters are left
	 *
	 * @return void
	 */
	private function processSetterInjectionRegistry() {
		while (count($this->setterInjectionRegistry)>0) {
			$currentSetterData = $this->setterInjectionRegistry;
			$this->setterInjectionRegistry = array();
			foreach ($currentSetterData as $setterInjectionData) {
				$this->handleSetterInjection($setterInjectionData[0], $setterInjectionData[1], $setterInjectionData[2]);
			}
		}
	}
}