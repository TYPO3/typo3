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
 * @version $Id: SelectViewHelper.php 2052 2009-03-25 15:46:33Z robert $
 */

/**
 * This view helper generates a <select> dropdown list for the use with a form.
 *
 * Example:
 * (1) Basic usage
 *
 * <f3:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" />
 * Generates a dropdown with two options. The array key is used as key, and the value is used as human-readable name.
 *
 *
 * (2) Pre-select a value
 *
 * <f3:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" selectedValue="visa" />
 * Generates a dropdown box like above, except that "VISA Card" is selected.
 *
 *
 * (3) Usage on domain objects
 *
 * <f3:form.select name="users" options="{userArray}" optionKey="id" optionValue="firstName" />
 * In the above example, the userArray is an array of "User" domain objects, with no array key specified.
 * If the optionKey variable is set, the getter named after that value is used to retrieve the option key.
 * If the optionValue variable is set, the getter named after that value is used to retrieve the option key.
 *
 * So, in the above example, the method $user->getId() is called to retrieve the key, and $user->getFirstName() to retrieve
 * the displayed value of each entry.
 *
 * The "selectedValue" property now expects a domain object, and tests for object equivalence.
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: SelectViewHelper.php 2052 2009-03-25 15:46:33Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Form_SelectViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper {

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('multiple', 'string', 'if TRUE, multiple select field');
		$this->registerTagAttribute('size', 'string', 'Size of input field');
		$this->registerArgument('options', 'array', 'Associative array with internal IDs as key, and the values are displayed in the select box', FALSE);
		$this->registerArgument('optionKey', 'string', 'If specified, will call the appropriate getter on each object to determine the key.', FALSE);
		$this->registerArgument('optionValue', 'string', 'If specified, will call the appropriate getter on each object to determine the value.', FALSE);
	}

	/**
	 * Render the tag.
	 *
	 * @return string rendered tag.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo htmlspecialchar() output
	 */
	public function render() {
		$name = $this->getName();
		if ($this->arguments['multiple']) {
			$name .= '[]';
		}
		$out = '<select name="' . $name . '" ' . $this->renderTagAttributes() . '>';

		$selectedValue = $this->getValue();

		if ($this->arguments['options']) {
			if ($this->arguments['optionKey']) {
				foreach ($this->arguments['options'] as $domainObject) {
					$key = Tx_Fluid_Reflection_ObjectAccess::getProperty($domainObject, $this->arguments['optionKey']);

					$selected = '';
					if ($domainObject == $selectedValue
					    || ($this->arguments['multiple'] && in_array($domainObject, $selectedValue))) {
						$selected = 'selected="selected"';
					}

					$value = Tx_Fluid_Reflection_ObjectAccess::getProperty($domainObject, $this->arguments['optionValue']);

					$out .= '<option ' . $selected . ' value="' . $key . '">' . $value . '</option>';
				}
			} else {
				foreach ($this->arguments['options'] as $key => $value) {
					$selected = '';
					if ($key == $selectedValue) {
						$selected = 'selected="selected"';
					}
					$out .= '<option ' . $selected . ' value="' . $key . '">' . $value . '</option>';
				}
			}
		}

		$out .= '</select>';

		return $out;
	}
}

?>
