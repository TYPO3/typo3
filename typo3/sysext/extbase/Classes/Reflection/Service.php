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
 * @package TYPO3
 * @subpackage extbase
 * @version $Id:$
 */
class Tx_ExtBase_Reflection_Service implements t3lib_Singleton {

	/**
	 * List of tags which are ignored while reflecting class and method annotations
	 *
	 * @var array
	 */
	protected $ignoredTags = array('package', 'subpackage', 'license', 'copyright', 'author', 'version', 'const');

	/**
	 * @var array Array of class reflections by class name
	 */
	protected $classReflections;

	/**
	 * Returns all tags and their values the specified method is tagged with
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return the tags and values of
	 * @return array An array of tags and their values or an empty array of no tags were found
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getMethodTagsValues($className, $methodName) {
		if (!isset($this->methodTagsValues[$className][$methodName])) {
			$this->methodTagsValues[$className][$methodName] = array();
			$class = $this->getClassReflection($className);			
			foreach ($class->getMethods() as $method) {
				$classMethodName = $method->getName();
				foreach ($method->getTagsValues() as $tag => $values) {
					if (array_search($tag, $this->ignoredTags) === FALSE) {
						$this->methodTagsValues[$className][$classMethodName][$tag] = $values;
					}
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getMethodParameters($className, $methodName) {
		if (!isset($this->methodParameters[$className][$methodName])) {
			$method = new ReflectionMethod($className, $methodName);
			$this->methodParameters[$className][$methodName] = array();
			foreach($method->getParameters() as $parameter) {
				$this->methodParameters[$className][$methodName][$parameter->getName()] = $this->convertParameterReflectionToArray($parameter);
			}
		}
		return $this->methodParameters[$className][$methodName];
	}

	/**
	 * Converts the given parameter reflection into an information array
	 *
	 * @param ReflectionParameter $parameter The parameter to reflect
	 * @return array Parameter information array
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function convertParameterReflectionToArray(ReflectionParameter $parameter, ReflectionMethod $method = NULL) {
		$parameterInformation = array(
			'position' => $parameter->getPosition(),
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
			if (isset($methodTagsAndValues['param']) && isset($methodTagsAndValues['param'][$parameter->getPosition()])) {
				$explodedParameters = explode(' ', $methodTagsAndValues['param'][$parameter->getPosition()]);
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

	protected function getClassReflection($className) {
		if (!isset($this->classReflections[$className])) {
			$this->classReflections[$className] = new Tx_ExtBase_Reflection_ClassReflection($className);
		}
		return $this->classReflections[$className];
	}
}
?>