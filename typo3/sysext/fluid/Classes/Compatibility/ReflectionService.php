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
 * @version $Id:$
 */
class Tx_Fluid_Compatibility_ReflectionService {
	public function getMethodParameters($className, $methodName) {
		$method = new Tx_ExtBase_Reflection_MethodReflection($className, $methodName);

		$methodParameters = array();
		foreach ($method->getParameters() as $parameter) {
			$methodParameters[$parameter->getName()] = $this->convertParameterReflectionToArray($parameter, $method);
		}
		return $methodParameters;

	}
	public function getMethodTagsValues($className, $methodName) {

	}

	/**
	 * Converts the given parameter reflection into an information array.
	 *
	 * TAKEN FROM FLOW3!!!!
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
}


?>