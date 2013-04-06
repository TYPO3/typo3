<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog, <typo3@susannemoog.de>
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
 */
class TypoScriptConstantsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper {

	public $viewHelperMapping = array(
		'int' => 'renderIntegerField',
		'int+' => 'renderPositiveIntegerField',
		'integer' => 'renderIntegerField',
		'color' => 'renderColorPicker',
		'wrap' => 'renderWrapField',
		'offset' => 'renderOffsetField',
		'options' => 'renderOptionSelect',
		'boolean' => 'renderCheckbox',
		'user' => 'renderUserFunction',
		'small' => 'renderSmallTextField',
		'string' => 'renderTextField',
		'input' => 'renderTextField',  // only for backwards compatibility, many extensions depend on that
		'default' => 'renderTextField' // only for backwards compatibility, many extensions depend on that
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
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string the rendered tag
	 */
	public function render(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$input = '';
		if (isset($this->viewHelperMapping[$configuration->getType()]) && method_exists($this, $this->viewHelperMapping[$configuration->getType()])) {
			$input = $this->{$this->viewHelperMapping[$configuration->getType()]}($configuration);
		} else {
			$input = $this->{$this->viewHelperMapping['default']}($configuration);
		}

		return $input;
	}

	/**
	 * Render field of type color picker
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderColorPicker(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'text');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', 'em-' . $configuration->getName());
		$doc = $this->getDocInstance();
		$pageRenderer = $doc->getPageRenderer();
		$pageRenderer->addCssFile('sysext/extensionmanager/Resources/Public/Contrib/Farbtastic/farbtastic.css');
		$pageRenderer->addJsFile('sysext/extensionmanager/Resources/Public/Contrib/Farbtastic/farbtastic.js');
		$pageRenderer->addJsInlineCode('colorpicker', '
			jQuery(document).ready(function() {
				jQuery(".colorPicker").farbtastic("#em-' . $configuration->getName() . '");
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
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderOffsetField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'text');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', 'em-' . $configuration->getName());
		$this->tag->addAttribute('class', 'offset');
		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "wrap"
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderWrapField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'text');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', 'em-' . $configuration->getName());
		$this->tag->addAttribute('class', 'wrap');
		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "option"
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderOptionSelect(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', 'em-' . $configuration->getName());
		$this->tag->setTagName('select');
		$optionValueArray = $configuration->getGeneric();
		$output = '';
		foreach ($optionValueArray as $label => $value) {
			$output .= '<option value="' . htmlspecialchars($value) . '"';
			if ($configuration->getValue() == $value) {
				$output .= ' selected="selected"';
			}
			$output .= '>' . $GLOBALS['LANG']->sL($label, TRUE) . '</option>';
		}
		$this->tag->setContent($output);
		return $this->tag->render();
	}

	/**
	 * Render field of type "int+"
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderPositiveIntegerField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'number');
		$this->tag->addAttribute('min', '0');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', 'em-' . $configuration->getName());
		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "integer"
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderIntegerField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'number');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', 'em-' . $configuration->getName());
		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "text"
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderTextField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$this->tag->setTagName('input');
		$this->tag->addAttribute('type', 'text');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('id', 'em-' . $configuration->getName());
		if ($configuration->getValue() !== NULL) {
			$this->tag->addAttribute('value', $configuration->getValue());
		}
		return $this->tag->render();
	}

	/**
	 * Render field of type "small text"
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderSmallTextField(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$this->tag->addAttribute('class', 'small');
		return $this->renderTextField($configuration);
	}

	/**
	 * Render field of type "checkbox"
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	public function renderCheckbox(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$this->tag->addAttribute('type', 'checkbox');
		$this->tag->addAttribute('name', $this->getName($configuration));
		$this->tag->addAttribute('value', 1);
		$this->tag->addAttribute('id', 'em-' . $configuration->getName());
		if ($configuration->getValue() == 1) {
			$this->tag->addAttribute('checked', 'checked');
		}
		$hiddenField = $this->renderHiddenFieldForEmptyValue($configuration);
		return $hiddenField . $this->tag->render();
	}

	/**
	 * Render field of type "userFunc"
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderUserFunction(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		$userFunction = $configuration->getGeneric();
		$userFunctionParams = array(
			'fieldName' => $this->getName($configuration),
			'fieldValue' => $configuration->getValue(),
			'propertyName' => $configuration->getName()
		);
		return \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($userFunction, $userFunctionParams, $this, '');
	}

	/**
	 * Get Field Name
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function getName(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration) {
		return 'tx_extensionmanager_tools_extensionmanagerextensionmanager[config][' . $configuration->getName() . '][value]';
	}

	/**
	 * Render a hidden field for empty values
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem $configuration
	 * @return string
	 */
	protected function renderHiddenFieldForEmptyValue($configuration) {
		$hiddenFieldNames = array();
		if ($this->viewHelperVariableContainer->exists('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'renderedHiddenFields')) {
			$hiddenFieldNames = $this->viewHelperVariableContainer->get('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'renderedHiddenFields');
		}
		$fieldName = $this->getName($configuration);
		if (substr($fieldName, -2) === '[]') {
			$fieldName = substr($fieldName, 0, -2);
		}
		if (!in_array($fieldName, $hiddenFieldNames)) {
			$hiddenFieldNames[] = $fieldName;
			$this->viewHelperVariableContainer->addOrUpdate('TYPO3\\CMS\\Fluid\\ViewHelpers\\FormViewHelper', 'renderedHiddenFields', $hiddenFieldNames);
			return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="0" />';
		}
		return '';
	}

	/**
	 * Gets instance of template if exists or create a new one.
	 *
	 * @return \TYPO3\CMS\Backend\Template\DocumentTemplate $doc
	 */
	public function getDocInstance() {
		if (!isset($GLOBALS['SOBE']->doc)) {
			$GLOBALS['SOBE']->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
			$GLOBALS['SOBE']->doc->backPath = $GLOBALS['BACK_PATH'];
		}
		return $GLOBALS['SOBE']->doc;
	}

}


?>