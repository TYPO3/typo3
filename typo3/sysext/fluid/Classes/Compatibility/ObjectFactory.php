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

class Tx_Fluid_Compatibility_ObjectFactory implements t3lib_Singleton {

	protected $injectors = array(
		'Tx_Fluid_Core_AbstractViewHelper' => array(
			'injectValidatorResolver' => 'Tx_Fluid_Compatibility_Validation_ValidatorResolver',
			'injectReflectionService' => 'Tx_Fluid_Compatibility_ReflectionService'
		),
		'Tx_Fluid_Core_ParsingState' => array('injectVariableContainer' => 'Tx_Fluid_Core_VariableContainer'),
		'Tx_Fluid_Core_TemplateParser' => array('injectObjectFactory' => 'Tx_Fluid_Compatibility_ObjectFactory'),
		'Tx_Fluid_Core_VariableContainer' => array('injectObjectFactory' => 'Tx_Fluid_Compatibility_ObjectFactory'),
	);

	public function create($objectName) {
		$constructorArguments = func_get_args();
		array_shift($constructorArguments);

		if (count($constructorArguments)) {
			$reflectedClass = new ReflectionClass($objectName);
			$object = $reflectedClass->newInstanceArgs($constructorArguments);
		} else {
			$object = new $objectName;
		}

		$injectVariables = array();
		if (isset($this->injectors[$objectName])) {
			$injectVariables = $this->injectors[$objectName];
		} elseif (in_array('Tx_Fluid_Core_ViewHelperInterface',class_implements($objectName))) {
			$injectVariables = $this->injectors['Tx_Fluid_Core_AbstractViewHelper'];
		}

		if (count($injectVariables)) {
			foreach ($injectVariables as $injectMethodName => $objectName) {
				call_user_func(array($object, $injectMethodName), $this->create($objectName));
			}
		}
		return $object;
	}
}

?>