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
 * @version $Id: SelectViewHelper.php 2279 2009-05-19 21:16:46Z k-fish $
 */

/**
 * This view helper generates a <select> dropdown list for the use with a form.
 *
 * = Basic usage =
 *
 * The most straightforward way is to supply an associative array as the "options" parameter.
 * The array key is used as option key, and the value is used as human-readable name.
 *
 * <code title="Basic usage">
 * <f3:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" />
 * </code>
 *
 * = Pre-select a value =
 *
 * To pre-select a value, set "selectedValue" to the option key which should be selected.
 * <code title="Default value">
 * <f3:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" selectedValue="visa" />
 * </code>
 * Generates a dropdown box like above, except that "VISA Card" is selected.
 *
 * If the select box is a multi-select box (multiple="true"), then "selectedValue" can be an array as well.
 *
 * = Usage on domain objects =
 *
 * If you want to output domain objects, you can just pass them as array into the "options" parameter.
 * To define what domain object value should be used as option key, use the "optionValueField" variable. Same goes for optionLabelField.
 *
 * If the optionValueField variable is set, the getter named after that value is used to retrieve the option key.
 * If the optionLabelField variable is set, the getter named after that value is used to retrieve the option value.
 *
 * <code title="Domain objects">
 * <f3:form.select name="users" options="{userArray}" optionValueField="id" optionLabelField="firstName" />
 * </code>
 * In the above example, the userArray is an array of "User" domain objects, with no array key specified.
 *
 * So, in the above example, the method $user->getId() is called to retrieve the key, and $user->getFirstName() to retrieve the displayed value of each entry.
 *
 * The "selectedValue" property now expects a domain object, and tests for object equivalence.
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: SelectViewHelper.php 2279 2009-05-19 21:16:46Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Form_SelectViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'select';

	/**
	 * @var mixed the selected value
	 */
	protected $selectedValue = NULL;

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('multiple', 'string', 'if set, multiple select field');
		$this->registerTagAttribute('size', 'string', 'Size of input field');
		$this->registerArgument('options', 'array', 'Associative array with internal IDs as key, and the values are displayed in the select box', FALSE);
		$this->registerArgument('optionValueField', 'string', 'If specified, will call the appropriate getter on each object to determine the value.', FALSE);
		$this->registerArgument('optionLabelField', 'string', 'If specified, will call the appropriate getter on each object to determine the label.', FALSE);
	}

	/**
	 * Render the tag.
	 *
	 * @return string rendered tag.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render() {
		$name = $this->getName();
		if ($this->arguments->hasArgument('multiple')) {
			$name .= '[]';
		}

		$this->tag->addAttribute('name', $name);
		$this->tag->setContent($this->renderOptionTags());

		return $this->tag->render();
	}

	/**
	 * Render the option tags.
	 *
	 * @return string rendered tags.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function renderOptionTags() {
		$output = '';
		$options = $this->getOptions();
		foreach ($options as $value => $label) {
			$isSelected = $this->isSelected($value);
			$output.= $this->renderOptionTag($value, $label, $isSelected) . chr(10);
		}
		return $output;
	}

	/**
	 * Render the option tags.
	 *
	 * @return array an associative array of options, key will be the value of the option tag
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getOptions() {
		if (!$this->arguments->hasArgument('optionValueField')) {
			return $this->arguments['options'];
		}
		$options = array();
		foreach ($this->arguments['options'] as $domainObject) {
			$value = Tx_Extbase_Reflection_ObjectAccess::getProperty($domainObject, $this->arguments['optionValueField']);
			$label = Tx_Extbase_Reflection_ObjectAccess::getProperty($domainObject, $this->arguments['optionLabelField']);
			$options[$value] = $label;
		}
		return $options;
	}

	/**
	 * Render the option tags.
	 *
	 * @return boolean true if the
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function isSelected($value) {
		$selectedValue = $this->getSelectedValue();
		if ($value === $selectedValue || (string)$value === $selectedValue) {
			return TRUE;
		}
		if ($this->arguments->hasArgument('multiple') && is_array($selectedValue) && in_array($value, $selectedValue)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Retrieves the selected value(s)
	 *
	 * @return mixed value string or an array of strings
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getSelectedValue() {
		$value = $this->getValue();
		if (!$this->arguments->hasArgument('optionValueField')) {
			return $value;
		}
		if (!is_array($value)) {
			if (is_object($value)) {
				return Tx_Extbase_Reflection_ObjectAccess::getProperty($value, $this->arguments['optionValueField']);
			} else {
				return $value;
			}
		}
		$selectedValues = array();
		foreach($value as $selectedValueElement) {
			if (is_object($selectedValueElement)) {
				$selectedValues[] = Tx_Extbase_Reflection_ObjectAccess::getProperty($selectedValueElement, $this->arguments['optionValueField']);
			} else {
				$selectedValues[] = $selectedValueElement;
			}
		}
		return $selectedValues;
	}

	/**
	 * Render one option tag
	 *
	 * @param string $value value attribute of the option tag (will be escaped)
	 * @param string $label content of the option tag (will be escaped)
	 * @param boolean $isSelected specifies wheter or not to add selected attribute
	 * @return string the rendered option tag
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function renderOptionTag($value, $label, $isSelected) {
		$output = '<option value="' . htmlspecialchars($value) . '"';
		if ($isSelected) {
			$output.= ' selected="selected"';
		}
		$output.= '>' . htmlspecialchars($label) . '</option>';

		return $output;
	}
}

?>