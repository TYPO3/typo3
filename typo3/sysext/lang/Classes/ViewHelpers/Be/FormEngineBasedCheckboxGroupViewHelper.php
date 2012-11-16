<?php
namespace TYPO3\CMS\Lang\ViewHelpers\Be;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Kai Vogel <kai.vogel@speedprogs.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Checkbox group view helper
 *
 * Usage:
 *
 * {namespace myext=ENET\MyExt\ViewHelpers}
 * <myext:be.formEngineBasedCheckboxGroup name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" value="{paymentOptions}" />
 *
 * or:
 *
 * <myext:be.formEngineBasedCheckboxGroup name="paymentOptions" options="{options}" optionValueField="id" optionLabelField="firstName" />
 *
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class FormEngineBasedCheckboxGroupViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'div';

	/**
	 * @var TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Injects the object manager
	 *
	 * @param TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}


	/**
	 * Arguments initialization
	 *
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('optionIcon', 'string', 'If specified, will show the given icon in front of each option label.');
	}

	/**
	 * Render the field
	 *
	 * @return string Rendered field
	 */
	public function render() {
		if ($items === NULL) {
			$items = $this->renderChildren();
		}

		$formName = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'formObjectName');
		$fieldName = $this->getName();
		$content = $this->renderCheckboxGroupField($formName, $fieldName);
		$this->tag->setContent($content);

		return $this->tag->render();
	}


	/**
	 * Returns the HTML content for a checkbox group field
	 *
	 * @param string $formName Name of the form
	 * @param string $fieldName Name of the field
	 * @return string HTML content
	 */
	protected function renderCheckboxGroupField($formName, $fieldName) {
		if (empty($this->arguments['options'])) {
			return '';
		}

		$options = $this->getOptions();
		if (empty($options)) {
			$options = array('' => '');
		}

		$icon = (!empty($this->arguments['optionIcon']) ? $this->arguments['optionIcon'] : '');
		$items = array();
		$checkedItems = array();

		foreach($options as $value => $label) {
			$items[] = array($label, $value, $icon);
			if ($this->isChecked($value)) {
				$checkedItems[] = $value;
			}
				// Register the field name for each option for the token generation
			$this->registerFieldNameForFormTokenGeneration($fieldName . '[]');
		}

		$setup = array(
			'itemFormElValue' => implode(',', $checkedItems),
			'itemFormElName'  => $this->getName(),
			'fieldChangeFunc' => array(),
		);

			// Render field with default form engine
		$formEngine = $this->objectManager->create('TYPO3\CMS\Backend\Form\FormEngine');
		$formEngine->formName = $formName;
		$content = $formEngine->getSingleField_typeSelect_checkbox('', '', array(), $setup, array(), $items, '%s');
		unset($formEngine);

		return $content;
	}

	/**
	 * Check if an option is checked
	 *
	 * @param string $value Value to check
	 * @return boolean TRUE if the value is checked
	 */
	protected function isChecked($value) {
		$selectedValue = $this->getSelectedValue();
		return (is_array($selectedValue) && in_array($value, $selectedValue));
	}

}
?>