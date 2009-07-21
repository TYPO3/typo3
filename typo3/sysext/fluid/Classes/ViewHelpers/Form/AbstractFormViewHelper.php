<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: AbstractFormViewHelper.php 2522 2009-06-02 10:32:21Z k-fish $
 */

/**
 * Abstract Form View Helper. Bundles functionality related to direct property access of objects in other Form ViewHelpers.
 *
 * If you set the "property" attribute to the name of the property to resolve from the object, this class will
 * automatically set the name and value of a form element.
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: AbstractFormViewHelper.php 2522 2009-06-02 10:32:21Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
abstract class Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper extends Tx_Fluid_Core_ViewHelper_TagBasedViewHelper {

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('name', 'string', 'Name of input tag');
		$this->registerArgument('value', 'mixed', 'Value of input tag');
		$this->registerArgument('property', 'string', 'Name of Object Property. If used in conjunction with <f3:form object="...">, "name" and "value" properties will be ignored.');
	}

	/**
	 * Get the name of this form element.
	 * Either returns arguments['name'], or the correct name for Object Access.
	 *
	 * @return string Name
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getName() {
		$name = ($this->isObjectAccessorMode()) ? $this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName') . '[' . $this->arguments['property'] . ']' : $this->arguments['name'];
		if (is_object($this->arguments['value']) && is_callable(array($this->arguments['value'], 'getUid'))) {
			$name .= '[uid]';
		}
		return $name;
	}

	/**
	 * Get the value of this form element.
	 * Either returns arguments['value'], or the correct value for Object Access.
	 *
	 * @return string Value
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getValue() {
		if ($this->isObjectAccessorMode() && $this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject') && ($this->arguments['value'] === NULL)) {
			$value = $this->getObjectValue($this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject'), $this->arguments['property']);
		} else {
			$value = $this->arguments['value'];
		}
		if (is_object($this->arguments['value']) && is_callable(array($this->arguments['value'], 'getUid'))) {
			$value = $this->arguments['value']->getUid();
		}
		return $value;
	}

	/**
	 * Internal method which checks if we should evaluate a domain object or just output arguments['name'] and arguments['value']
	 *
	 * @return boolean TRUE if we should evaluate the domain object, FALSE otherwise.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function isObjectAccessorMode() {
		return (($this->arguments['property'] !== NULL) && $this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName')) ? TRUE : FALSE;
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
		$getterMethodName = 'get' . ucfirst($propertyName);
		return $object->$getterMethodName();
	}
	
	/**
	 * Get errors for the property and form name of this view helper
	 *
	 * @return array An array of F3\FLOW3\Error\Error objects
	 */
	protected function getErrorsForProperty() {
		$errors = $this->controllerContext->getRequest()->getErrors();
		$formName = $this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName');

		if ($this->arguments->hasArgument('property')) {
			$propertyName = $this->arguments['property'];

			$formErrors = array();
			foreach ($errors as $error) {
				if ($error instanceof Tx_Extbase_Validation_PropertyError && $error->getPropertyName() == $formName) {
					
					$formErrors = $error->getErrors();
					foreach ($formErrors as $formError) {
						if ($formError instanceof Tx_Extbase_Validation_PropertyError && $formError->getPropertyName() == $propertyName) {
							return $formError->getErrors();
						}
					}
				}
			}
		}
		return array();
	}
}

?>