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
 * This view helper generates a <select> dropdown list for the use with a form.
 *
 * = Basic usage =
 *
 * The most straightforward way is to supply an associative array as the "options" parameter.
 * The array key is used as option key, and the value is used as human-readable name.
 *
 * <code title="Basic usage">
 * <f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" />
 * </code>
 *
 * = Pre-select a value =
 *
 * To pre-select a value, set "value" to the option key which should be selected.
 * <code title="Default value">
 * <f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" value="visa" />
 * </code>
 * Generates a dropdown box like above, except that "VISA Card" is selected.
 *
 * If the select box is a multi-select box (multiple="true"), then "value" can be an array as well.
 *
 * = Usage on domain objects =
 *
 * If you want to output domain objects, you can just pass them as array into the "options" parameter.
 * To define what domain object value should be used as option key, use the "optionValueField" variable. Same goes for optionLabelField.
 * If neither is given, the Identifier (UID/uid) and the __toString() method are tried as fallbacks.
 *
 * If the optionValueField variable is set, the getter named after that value is used to retrieve the option key.
 * If the optionLabelField variable is set, the getter named after that value is used to retrieve the option value.
 *
 * <code title="Domain objects">
 * <f:form.select name="users" options="{userArray}" optionValueField="id" optionLabelField="firstName" />
 * </code>
 * In the above example, the userArray is an array of "User" domain objects, with no array key specified.
 *
 * So, in the above example, the method $user->getId() is called to retrieve the key, and $user->getFirstName() to retrieve the displayed value of each entry.
 *
 * The "value" property now expects a domain object, and tests for object equivalence.
 *
 * @version $Id: SelectViewHelper.php 2043 2010-03-16 08:49:45Z sebastian $
 * @package Fluid
 * @subpackage ViewHelpers\Form
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Form_SelectViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormFieldViewHelper {

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
	 * @api
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('multiple', 'string', 'if set, multiple select field');
		$this->registerTagAttribute('size', 'string', 'Size of input field');
		$this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
		$this->registerArgument('options', 'array', 'Associative array with internal IDs as key, and the values are displayed in the select box', TRUE);
		$this->registerArgument('optionValueField', 'string', 'If specified, will call the appropriate getter on each object to determine the value.');
		$this->registerArgument('optionLabelField', 'string', 'If specified, will call the appropriate getter on each object to determine the label.');
		$this->registerArgument('sortByOptionLabel', 'boolean', 'If true, List will be sorted by label.', FALSE, FALSE);
		$this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', FALSE, 'f3-form-error');
	}

	/**
	 * Render the tag.
	 *
	 * @return string rendered tag.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function render() {
		$name = $this->getName();
		if ($this->arguments->hasArgument('multiple')) {
			$name .= '[]';
		}

		$this->tag->addAttribute('name', $name);

		$options = $this->getOptions();
		if (empty($options)) {
			$options = array('' => '');
		}
		$this->tag->setContent($this->renderOptionTags($options));

		$this->setErrorClassAttribute();

		// register field name for token generation.
		// in case it is a multi-select, we need to register the field name
		// as often as there are elements in the box
		if ($this->arguments->hasArgument('multiple')) {
			for ($i=0; $i<count($options); $i++) {
				$this->registerFieldNameForFormTokenGeneration($name);
			}
		} else {
			$this->registerFieldNameForFormTokenGeneration($name);
		}

		return $this->tag->render();
	}

	/**
	 * Render the option tags.
	 *
	 * @param array $options the options for the form.
	 * @return string rendered tags.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function renderOptionTags($options) {
		$output = '';

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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getOptions() {
		if (!is_array($this->arguments['options']) && !($this->arguments['options'] instanceof Traversable)) {
			return array();
		}
		$options = array();
		foreach ($this->arguments['options'] as $key => $value) {
			if (is_object($value)) {

				if ($this->arguments->hasArgument('optionValueField')) {
					$key = Tx_Extbase_Reflection_ObjectAccess::getProperty($value, $this->arguments['optionValueField']);
					if (is_object($key)) {
						if (method_exists($key, '__toString')) {
							$key = (string)$key;
						} else {
							throw new Tx_Fluid_Core_ViewHelper_Exception('Identifying value for object of class "' . get_class($value) . '" was an object.' , 1247827428);
						}
					}
				} elseif ($this->persistenceManager->getBackend()->getIdentifierByObject($value) !== NULL) {
					$key = $this->persistenceManager->getBackend()->getIdentifierByObject($value);
				} elseif (method_exists($value, '__toString')) {
					$key = (string)$value;
				} else {
					throw new Tx_Fluid_Core_ViewHelper_Exception('No identifying value for object of class "' . get_class($value) . '" found.' , 1247826696);
				}

				if ($this->arguments->hasArgument('optionLabelField')) {
					$value = Tx_Extbase_Reflection_ObjectAccess::getProperty($value, $this->arguments['optionLabelField']);
					if (is_object($value)) {
						if (method_exists($value, '__toString')) {
							$value = (string)$value;
						} else {
							throw new Tx_Fluid_Core_ViewHelper_Exception('Label value for object of class "' . get_class($value) . '" was an object without a __toString() method.' , 1247827553);
						}
					}
				} elseif (method_exists($value, '__toString')) {
					$value = (string)$value;
				} elseif ($this->persistenceManager->getBackend()->getIdentifierByObject($value) !== NULL) {
					$value = $this->persistenceManager->getBackend()->getIdentifierByObject($value);
				}
			}
			$options[$key] = $value;
		}
		if ($this->arguments['sortByOptionLabel']) {
			asort($options);
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
		if (!is_array($value) && !($value instanceof Iterator)) {
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