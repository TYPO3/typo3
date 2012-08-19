<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * View Helper for rendering Extension Manager Configuration Form
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage ViewHelpers
 */
class Tx_Extensionmanager_ViewHelpers_Form_TypoScriptConstantsViewHelper
	extends Tx_Fluid_Core_ViewHelper_AbstractTagBasedViewHelper {

	public $viewHelperMapping = array(
		'int' => 'renderIntegerField',
		'int+' =>  'renderPositiveIntegerField',
		'integer' => 'renderIntegerField',
		'color' =>  'renderColorPicker',
		'wrap' =>  'renderWrapField',
		'offset' =>  'renderOffsetField',
		'options' => 'renderOptionSelect',
		'boolean' => 'renderCheckbox',
		'user' => 'renderUserFunction',
		'small' =>  'renderSmallTextField',
		'string' => 'renderTextField'
	);

	public $tagName = 'input';

	/**
	 * Initialize arguments of this view helper
	 *
	 * @return void
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('name', 'string', 'Name of input tag');
		$this->registerArgument('value', 'mixed', 'Value of input tag');
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Render
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string the rendered tag
	 */
	public function render(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$input = '';
		if (
			isset($this->viewHelperMapping[$configuration->getType()]) &&
			method_exists($this, $this->viewHelperMapping[$configuration->getType()])
		) {
			$input = $this->{$this->viewHelperMapping[$configuration->getType()]}($configuration);
		}
		return $input;
	}

	/**
	 * Render field of type color picker
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderColorPicker(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'text');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', $configuration->getName());

		$doc = $this->getDocInstance();
		$pageRenderer = $doc->getPageRenderer();
		$pageRenderer->addCssFile('sysext/extensionmanager/Resources/Public/Contrib/Farbtastic/farbtastic.css');
		$pageRenderer->addJsFile('sysext/extensionmanager/Resources/Public/Contrib/Farbtastic/farbtastic.js');
		$pageRenderer->addJsInlineCode('colorpicker', '
			jQuery(document).ready(function() {
				jQuery(".colorPicker").farbtastic("#' . $configuration->getName() . '");
			});
		');

		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render() . '<div class="colorPicker"></div>';
	}

	/**
	 * Render field of type "offset"
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderOffsetField(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'text');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', $configuration->getName());
		$this->tag->addAttribute('class', 'offset');

		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "wrap"
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderWrapField(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'text');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', $configuration->getName());
		$this->tag->addAttribute('class', 'wrap');

		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "option"
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderOptionSelect(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', $configuration->getName());
		$this->tag->setTagName('select');
		$optionValueArray = $configuration->getGeneric();
		$output = '';
		foreach ($optionValueArray as $label => $value) {
			$output .= '<option value="' . htmlspecialchars($value) . '"';
			if ($configuration->getValue() == $value) {
				$output .= ' selected="selected"';
			}
			$output .= '>' . htmlspecialchars($label) . '</option>';
		}
		$this->tag->setContent($output);
		return $this->tag->render();
	}

	/**
	 * Render field of type "int+"
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderPositiveIntegerField(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'number');
		$this->tag->addAttribute('min', '0');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', $configuration->getName());

		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "integer"
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderIntegerField(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'number');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', $configuration->getName());

		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "text"
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderTextField(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'text');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', $configuration->getName());

		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "small text"
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderSmallTextField(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$this->tag->addAttribute('class', 'small');
		return $this->renderTextField($configuration);
	}

	/**
	 * Render field of type "checkbox"
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	public function renderCheckbox(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$this->tag->addAttribute('type', 'checkbox');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('value', 1);
		$this->tag->addAttribute('id', $configuration->getName());
		if ($configuration->getValue() == 1) {
			$this->tag->addAttribute('checked', 'checked');
		}

		$hiddenField = $this->renderHiddenFieldForEmptyValue($configuration);
		return $hiddenField . $this->tag->render();
	}

	/**
	 * Render field of type "userFunc"
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderUserFunction(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		$userFunction = $configuration->getGeneric();
		$userFunctionParams = array(
			'fieldName' => $this->getName($configuration),
			'fieldValue' => $configuration->getValue(),
			'propertyName' => $configuration->getName(),
		);
		return t3lib_div::callUserFunction($userFunction, $userFunctionParams, $this, '');
	}

	/**
	 * Get Field Name
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function getName(Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration) {
		return 'tx_extensionmanager_tools_extensionmanagerextensionmanager[config][' .
			$configuration->getName() .
			'][value]';
	}

	/**
	 * Render a hidden field for empty values
	 *
	 * @param Tx_Extensionmanager_Domain_Model_ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderHiddenFieldForEmptyValue($configuration) {
		$hiddenFieldNames = array();
		if ($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_FormViewHelper', 'renderedHiddenFields')) {
			$hiddenFieldNames = $this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_FormViewHelper', 'renderedHiddenFields');
		}

		$fieldName = $this->getName($configuration);
		if (substr($fieldName, -2) === '[]') {
			$fieldName = substr($fieldName, 0, -2);
		}
		if (!in_array($fieldName, $hiddenFieldNames)) {
			$hiddenFieldNames[] = $fieldName;
			$this->viewHelperVariableContainer->addOrUpdate(
				'Tx_Fluid_ViewHelpers_FormViewHelper',
				'renderedHiddenFields',
				$hiddenFieldNames
			);

			return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="0" />';
		}
		return '';
	}

	/**
	* Gets instance of template if exists or create a new one.
	*
	* @return template $doc
	*/
	public function getDocInstance() {
		if (!isset($GLOBALS['SOBE']->doc)) {
			$GLOBALS['SOBE']->doc = t3lib_div::makeInstance('template');
			$GLOBALS['SOBE']->doc->backPath = $GLOBALS['BACK_PATH'];
		}
		return $GLOBALS['SOBE']->doc;
	}
}

?>