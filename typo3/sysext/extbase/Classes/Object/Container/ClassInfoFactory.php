<?php
namespace TYPO3\CMS\Extbase\Object\Container;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Extbase Team
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 *
 * @author Daniel Pötzinger
 */
class ClassInfoFactory {

	/**
	 * Factory metod that builds a ClassInfo Object for the given classname - using reflection
	 *
	 * @param string $className The class name to build the class info for
	 * @throws Exception\UnknownObjectException
	 * @return \TYPO3\CMS\Extbase\Object\Container\ClassInfo the class info
	 */
	public function buildClassInfoFromClassName($className) {
		try {
			$reflectedClass = new \ReflectionClass($className);
		} catch (\Exception $e) {
			throw new \TYPO3\CMS\Extbase\Object\Container\Exception\UnknownObjectException('Could not analyse class:' . $className . ' maybe not loaded or no autoloader?', 1289386765);
		}
		$constructorArguments = $this->getConstructorArguments($reflectedClass);
		$injectMethods = $this->getInjectMethods($reflectedClass);
		$injectProperties = $this->getInjectProperties($reflectedClass);
		$isSingleton = $this->getIsSingleton($className);
		$isInitializeable = $this->getIsInitializeable($className);
		return new \TYPO3\CMS\Extbase\Object\Container\ClassInfo($className, $constructorArguments, $injectMethods, $isSingleton, $isInitializeable, $injectProperties);
	}

	/**
	 * Build a list of constructor arguments
	 *
	 * @param \ReflectionClass $reflectedClass
	 * @return array of parameter infos for constructor
	 */
	private function getConstructorArguments(\ReflectionClass $reflectedClass) {
		$reflectionMethod = $reflectedClass->getConstructor();
		if (!is_object($reflectionMethod)) {
			return array();
		}
		$result = array();
		foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
			/* @var $reflectionParameter \ReflectionParameter */
			$info = array();
			$info['name'] = $reflectionParameter->getName();
			if ($reflectionParameter->getClass()) {
				$info['dependency'] = $reflectionParameter->getClass()->getName();
			}
			if ($reflectionParameter->isOptional()) {
				$info['defaultValue'] = $reflectionParameter->getDefaultValue();
			}
			$result[] = $info;
		}
		return $result;
	}

	/**
	 * Build a list of inject methods for the given class.
	 *
	 * @param \ReflectionClass $reflectedClass
	 * @throws \Exception
	 * @return array (nameOfInjectMethod => nameOfClassToBeInjected)
	 */
	private function getInjectMethods(\ReflectionClass $reflectedClass) {
		$result = array();
		$reflectionMethods = $reflectedClass->getMethods();
		if (is_array($reflectionMethods)) {
			foreach ($reflectionMethods as $reflectionMethod) {
				if ($reflectionMethod->isPublic() && substr($reflectionMethod->getName(), 0, 6) === 'inject' && $reflectionMethod->getName() !== 'injectSettings') {
					$reflectionParameter = $reflectionMethod->getParameters();
					if (isset($reflectionParameter[0])) {
						if (!$reflectionParameter[0]->getClass()) {
							throw new \Exception('Method "' . $reflectionMethod->getName() . '" of class "' . $reflectedClass->getName() . '" is marked as setter for Dependency Injection, but does not have a type annotation');
						}
						$result[$reflectionMethod->getName()] = $reflectionParameter[0]->getClass()->getName();
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Build a list of properties to be injected for the given class.
	 *
	 * @param \ReflectionClass $reflectedClass
	 * @return array (nameOfInjectProperty => nameOfClassToBeInjected)
	 */
	private function getInjectProperties(\ReflectionClass $reflectedClass) {
		$result = array();
		$reflectionProperties = $reflectedClass->getProperties();
		if (is_array($reflectionProperties)) {
			foreach ($reflectionProperties as $reflectionProperty) {
				$reflectedProperty = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Reflection\\PropertyReflection', $reflectedClass->getName(), $reflectionProperty->getName());
				if ($reflectedProperty->isTaggedWith('inject') && $reflectedProperty->getName() !== 'settings') {
					$varValues = $reflectedProperty->getTagValues('var');
					if (count($varValues) == 1) {
						$result[$reflectedProperty->getName()] = ltrim($varValues[0], '\\');
					}
				}
			}
		}
		return $result;
	}

	/**
	 * This method is used to determin if a class is a singleton or not.
	 *
	 * @param string $classname
	 * @return boolean
	 */
	private function getIsSingleton($classname) {
		return in_array('TYPO3\\CMS\\Core\\SingletonInterface', class_implements($classname));
	}

	/**
	 * This method is used to determine of the object is initializeable with the
	 * method initializeObject.
	 *
	 * @param string $classname
	 * @return boolean
	 */
	private function getIsInitializeable($classname) {
		return method_exists($classname, 'initializeObject');
	}
}

?>