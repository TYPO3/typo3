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
 * @version $Id: ObjectFactory.php 1734 2009-11-25 21:53:57Z stucki $
 */
/**
 * Class emulating the object factory for Fluid v4.
 *
 * DO NOT USE DIRECTLY!
 * @internal
 */
class Tx_Fluid_Compatibility_ObjectManager implements t3lib_Singleton {

	protected $injectors = array(
		'Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper' => array(
			'injectPersistenceManager' => 'Tx_Extbase_Persistence_Manager'
		),
		'Tx_Fluid_Core_ViewHelper_AbstractViewHelper' => array(
			'injectReflectionService' => 'Tx_Extbase_Reflection_Service'
		),
		'Tx_Fluid_Core_ViewHelper_TagBasedViewHelper' => array(
			'injectTagBuilder' => 'Tx_Fluid_Core_ViewHelper_TagBuilder'
		),
		'Tx_Fluid_Core_Parser_ParsingState' => array(
			'injectVariableContainer' => 'Tx_Fluid_Core_ViewHelper_TemplateVariableContainer'
		),
		'Tx_Fluid_Core_Parser_TemplateParser' => array(
			'injectObjectManager' => 'Tx_Fluid_Compatibility_ObjectManager'
		),
		'Tx_Fluid_Core_Rendering_RenderingContext' => array(
			'injectObjectManager' => 'Tx_Fluid_Compatibility_ObjectManager'
		),
		'Tx_Fluid_Core_Parser_Interceptor_Escape' => array(
			'injectObjectManager' => 'Tx_Fluid_Compatibility_ObjectManager'
		),
		'Tx_Extbase_Validation_ValidatorResolver' => array(
			'injectObjectManager' => 'Tx_Extbase_Object_Manager'
		),
		'Tx_Fluid_ViewHelpers_FormViewHelper' => array(
			'injectRequestHashService' => 'Tx_Extbase_Security_Channel_RequestHashService'
		)
	);

	/**
	 * Create a certain object name
	 *
	 * DO NOT USE DIRECTLY!
	 *
	 * @param string $objectName Object name to create
	 * @return object Object which was created
	 * @internal
	 */
	public function create($objectName) {
		$constructorArguments = func_get_args();

		$object = call_user_func_array(array('t3lib_div', 'makeInstance'), $constructorArguments);
		$injectObjects = array();

		if (isset($this->injectors[$objectName])) {
			$injectObjects = array_merge($injectObjects, $this->injectors[$objectName]);
		}
		foreach (class_parents($objectName) as $parentObjectName) {
			if (isset($this->injectors[$parentObjectName])) {
				$injectObjects = array_merge($injectObjects, $this->injectors[$parentObjectName]);
			}
		}
		foreach (class_implements($objectName) as $parentObjectName) {
			if (isset($this->injectors[$parentObjectName])) {
				$injectObjects = array_merge($injectObjects, $this->injectors[$parentObjectName]);
			}
		}
		foreach ($injectObjects as $injectMethodName => $injectObjectName) {
			call_user_func(array($object, $injectMethodName), $this->create($injectObjectName));
		}
		return $object;
	}

	public function get($objectName) {
		return $this->create($objectName);
	}
}

?>