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
 * View Helper which creates a simple checkbox (<input type="checkbox">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.checkbox name="myCheckBox" value="someValue" />
 * </code>
 *
 * Output:
 * <input type="checkbox" name="myCheckBox" value="someValue" />
 *
 * <code title="Preselect">
 * <f:form.checkbox name="myCheckBox" value="someValue" checked="{object.value} == 5" />
 * </code>
 *
 * Output:
 * <input type="checkbox" name="myCheckBox" value="someValue" checked="checked" />
 * (depending on $object)
 *
 * <code title="Bind to object property">
 * <f:form.checkbox property="interests" value="TYPO3" />
 * </code>
 *
 * Output:
 * <input type="checkbox" name="user[interests][]" value="TYPO3" checked="checked" />
 * (depending on property "interests")
 *
 * @version $Id: CheckboxViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage ViewHelpers\Form
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Form_CheckboxViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormFieldViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'input';

	/**
	 * Initialize the arguments.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
		$this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', FALSE, 'f3-form-error');
		#$this->registerArgument('value', 'string', 'Value of input tag. Required for checkboxes', TRUE);
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Renders the checkbox.
	 *
	 * @param boolean $checked Specifies that the input element should be preselected
	 *
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function render($checked = NULL) {
		$this->tag->addAttribute('type', 'checkbox');

		$nameAttribute = $this->getName();
		$valueAttribute = $this->getValue();
		if ($checked === NULL && $this->isObjectAccessorMode()) {
			$propertyValue = $this->getPropertyValue();
			if (is_bool($propertyValue)) {
				$checked = $propertyValue === (boolean)$valueAttribute;
			} elseif (is_array($propertyValue)) {
				$checked = in_array($valueAttribute, $propertyValue);
				$nameAttribute .= '[]';
			} else {
				throw new Tx_Fluid_Core_ViewHelper_Exception('Checkbox viewhelpers can only be bound to properties of type boolean or array. Property "' . $this->arguments['property'] . '" is of type "' . (is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)) . '".' , 1248261038);
			}
		}

		$this->registerFieldNameForFormTokenGeneration($nameAttribute);
		$this->tag->addAttribute('name', $nameAttribute);
		$this->tag->addAttribute('value', $valueAttribute);
		if ($checked) {
			$this->tag->addAttribute('checked', 'checked');
		}

		$this->setErrorClassAttribute();

		$hiddenField = $this->renderHiddenFieldForEmptyValue();
		return $hiddenField . $this->tag->render();
	}
	
	/**
	 * Renders a hidden field with the same name as the element, to make sure the empty value is submitted
	 * in case the checkbox is not selected.
	 * 
	 * @return string the hidden field.
	 */
	protected function renderHiddenFieldForEmptyValue() {
		if ($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_Form_CheckboxViewHelper', 'checkboxFieldNames')) {
			$checkboxFieldNames = $this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_Form_CheckboxViewHelper', 'checkboxFieldNames');
		} else {
			$checkboxFieldNames = array();
		}

		$nameOfElement = $this->getName();
		if (!in_array($nameOfElement, $checkboxFieldNames)) {
			$checkboxFieldNames[] = $nameOfElement;
			$this->viewHelperVariableContainer->addOrUpdate('Tx_Fluid_ViewHelpers_Form_CheckboxViewHelper', 'checkboxFieldNames', $checkboxFieldNames);
			
			$tagBuilder = t3lib_div::makeInstance('Tx_Fluid_Core_ViewHelper_TagBuilder', 'input');
			$tagBuilder->addAttribute('type', 'hidden');
			$tagBuilder->addAttribute('name', $nameOfElement);
			$tagBuilder->addAttribute('value', '');
			return $tagBuilder->render();
		}
		return '';
	}
}

?>