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
		'Core_AbstractViewHelper' => array('injectValidatorResolver' => 'Compatibility_Validation_ValidatorResolver'),
		'Core_ParsingState' => array('injectVariableContainer' => 'Core_VariableContainer'),
		'Core_TemplateParser' => array('injectObjectFactory' => 'Compatibility_ObjectFactory'),
		'Core_VariableContainer' => array('injectObjectFactory' => 'Compatibility_ObjectFactory'),
	);

	public function create($objectName) {
		$object = t3lib_div::makeInstance($objectName);

		if (isset($this->injectors['Tx_Fluid_' . $objectName])) {
			foreach ($this->injectors['Tx_Fluid_' . $objectName] as $injectMethodName => $objectName) {
				call_user_func(array($object, $injectMethodName), t3lib_div::makeInstance('Tx_Fluid' . $objectName));
			}
		}
		return $object;
	}
}

?>