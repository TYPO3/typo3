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
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: AbstractFormViewHelper.php 1962 2009-03-03 12:10:41Z k-fish $
 */

/**
 * Abstract Form View Helper. Bundles functionality related to direct property access of objects in other Form ViewHelpers.
 *
 * If you set the "property" attribute to the name of the property to resolve from the object, this class Tx_Fluid_ViewHelpers_Form_will
 * automatically set the name and value of a form element.
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: AbstractFormViewHelper.php 1962 2009-03-03 12:10:41Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
abstract class Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper extends Tx_Fluid_Core_TagBasedViewHelper {

	/**
	 * Initialize arguments. Registers:
	 * - name
	 * - value
	 * - property
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		$this->registerArgument('name', 'string', 'Name of input tag');
		$this->registerArgument('value', 'string', 'Value of input tag');
		$this->registerArgument('property', 'string', 'Name of Object Property. If used in conjunction with <f3:form object="...">, "name" and "value" properties will be ignored.');
	}

	/**
	 * Get the name of this form element.
	 * Either returns arguments['name'], or the correct name for Object Access.
	 *
	 * @return string Name
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getName() {
		return $this->variableContainer->get('__formName') . '[' . $this->arguments['name'] . ']';
	}

	/**
	 * Get the value of this form element.
	 * Either returns arguments['value'], or the correct value for Object Access.
	 *
	 * @return string Value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getValue() {
		if ($this->isObjectAccessorMode() && $this->variableContainer->exists('__formObject')) {
			return $this->getObjectValue($this->variableContainer->get('__formObject'), $this->arguments['property']);
		} else {
			return $this->arguments['value'];
		}
	}

	/**
	 * Internal method which checks if we should evaluate a domain object or just output arguments['name'] and arguments['value']
	 *
	 * @return boolean TRUE if we should evaluate the domain object, FALSE otherwise.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	private function isObjectAccessorMode() {
		return ($this->arguments['property'] && $this->variableContainer->exists('__formName')) ? TRUE : FALSE;
	}

	/**
	 * Get object value. Calls the appropriate getter.
	 *
	 * @param object $object Object to get the value from
	 * @param string $propertyName Name of property to get.
	 * @todo replace with something generic.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	private function getObjectValue($object, $propertyName) {
		$methodName = 'get' . ucfirst($propertyName);
		return $object->$methodName();
	}
}

?>