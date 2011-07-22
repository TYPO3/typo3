<?php
declare(encoding = 'utf-8');
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Patrick Broens <patrick@patrickbroens.nl>
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
 * The form wizard view
 *
 * @category View
 * @package TYPO3
 * @subpackage form
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @license http://www.gnu.org/copyleft/gpl.html
 * @version $Id$
 */
class tx_form_view_wizard_wizard {
	/**
	 * The document template object
	 *
	 * Needs to be a local variable of the class, because this will be used by
	 * the TYPO3 Backend Template Class typo3/template.php
	 *
	 * @var mediumDoc
	 */
	public $doc;

	/**
	 * Is the referenced record available
	 *
	 * @var boolean TRUE if available, FALSE if not
	 */
	private $recordIsAvailable = FALSE;

	/**
	 * Constructs this view
	 *
	 * Defines the global variable SOBE. Normally this is used by the wizards
	 * which are one file only. SOBE is used by typo3/template.php. This view is
	 * now the class with the global variable name SOBE.
	 *
	 * Defines the document template object.
	 *
	 * @return void
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile(
			'EXT:form/Resources/Private/Language/locallang_wizard.xml'
		);
		$GLOBALS['SOBE'] = $this;

			// Define the document template object
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate(
			'EXT:form/Resources/Private/Templates/Wizard.html'
		);
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function jumpToUrl(URL,formEl)	{
				window.location.href = URL;
			}
		');

		$this->pageRenderer = $this->doc->getPageRenderer();
		$this->pageRenderer->enableConcatenateFiles();
		$this->pageRenderer->enableCompressCss();
		$this->pageRenderer->enableCompressJavascript();
	}

	/**
	 * The main render method
	 *
	 * Gathers all content and echos it to the screen
	 *
	 * @return void
	 */
	public function render() {
			// Check if the referenced record is available
		$this->recordIsAvailable = $this->repository->hasRecord();

		if ($this->recordIsAvailable) {
				// Load necessary JavaScript
			$this->loadJavascript();

				// Load necessary CSS
			$this->loadCss();

				// Load the settings
			$this->loadSettings();

				// Localization
			$this->loadLocalization();


				// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers['CSH'] = $docHeaderButtons['csh'];
		}

			// Getting the body content
		$markers['CONTENT'] = $this->getBodyContent();

			// Build the HTML for the module
		$content = $this->doc->startPage($GLOBALS['LANG']->getLL('title', 1));
		$content.= $this->doc->moduleBody(array(), $docHeaderButtons, $markers);
		$content.= $this->doc->endPage();
		$content = $this->doc->insertStylesAndJS($content);

		echo $content;
		exit();
	}

	/**
	 * Load the necessarry javascript
	 *
	 * This will only be done when the referenced record is available
	 *
	 * @return void
	 */
	private function loadJavascript() {
		$compress = TRUE;
		$baseUrl = t3lib_div::resolveBackPath(
			'../../../../../' .
			t3lib_extMgm::siteRelPath('form') .
			'Resources/Public/JavaScript/Wizard/'
		);

		$javascriptFiles = array(
			'Initialize.js',
			'Ux/Ext.ux.merge.js',
			'Ux/Ext.ux.isemptyobject.js',
			'Ux/Ext.ux.spinner.js',
			'Ux/Ext.ux.form.spinnerfield.js',
			'Ux/Ext.ux.form.textfieldsubmit.js',
			'Ux/Ext.ux.grid.CheckColumn.js',
			'Ux/Ext.ux.grid.SingleSelectCheckColumn.js',
			'Ux/Ext.ux.grid.ItemDeleter.js',
			'Helpers/History.js',
			'Helpers/Element.js',
			'Elements/ButtonGroup.js',
			'Elements/Container.js',
			'Elements/Elements.js',
			'Elements/Dummy.js',
			'Elements/Basic/Button.js',
			'Elements/Basic/Captcha.js',
			'Elements/Basic/Checkbox.js',
			'Elements/Basic/Fieldset.js',
			'Elements/Basic/Fileupload.js',
			'Elements/Basic/Form.js',
			'Elements/Basic/Hidden.js',
			'Elements/Basic/Password.js',
			'Elements/Basic/Radio.js',
			'Elements/Basic/Reset.js',
			'Elements/Basic/Select.js',
			'Elements/Basic/Submit.js',
			'Elements/Basic/Textarea.js',
			'Elements/Basic/Textline.js',
			'Elements/Predefined/Email.js',
			'Elements/Predefined/CheckboxGroup.js',
			'Elements/Predefined/Name.js',
			'Elements/Predefined/RadioGroup.js',
			'Elements/Content/Header.js',
			'Viewport.js',
			'Viewport/Left.js',
			'Viewport/Right.js',
			'Viewport/Left/Elements.js',
			'Viewport/Left/Elements/ButtonGroup.js',
			'Viewport/Left/Elements/Basic.js',
			'Viewport/Left/Elements/Predefined.js',
			'Viewport/Left/Elements/Content.js',
			'Viewport/Left/Options.js',
			'Viewport/Left/Options/Dummy.js',
			'Viewport/Left/Options/Panel.js',
			'Viewport/Left/Options/Forms/Attributes.js',
			'Viewport/Left/Options/Forms/Label.js',
			'Viewport/Left/Options/Forms/Legend.js',
			'Viewport/Left/Options/Forms/Options.js',
			'Viewport/Left/Options/Forms/Various.js',
			'Viewport/Left/Options/Forms/Filters.js',
			'Viewport/Left/Options/Forms/Filters/Filter.js',
			'Viewport/Left/Options/Forms/Filters/Dummy.js',
			'Viewport/Left/Options/Forms/Filters/Alphabetic.js',
			'Viewport/Left/Options/Forms/Filters/Alphanumeric.js',
			'Viewport/Left/Options/Forms/Filters/Currency.js',
			'Viewport/Left/Options/Forms/Filters/Digit.js',
			'Viewport/Left/Options/Forms/Filters/Integer.js',
			'Viewport/Left/Options/Forms/Filters/LowerCase.js',
			'Viewport/Left/Options/Forms/Filters/RegExp.js',
			'Viewport/Left/Options/Forms/Filters/RemoveXSS.js',
			'Viewport/Left/Options/Forms/Filters/StripNewLines.js',
			'Viewport/Left/Options/Forms/Filters/TitleCase.js',
			'Viewport/Left/Options/Forms/Filters/Trim.js',
			'Viewport/Left/Options/Forms/Filters/UpperCase.js',
			'Viewport/Left/Options/Forms/Validation.js',
			'Viewport/Left/Options/Forms/Validation/Rule.js',
			'Viewport/Left/Options/Forms/Validation/Dummy.js',
			'Viewport/Left/Options/Forms/Validation/Alphabetic.js',
			'Viewport/Left/Options/Forms/Validation/Alphanumeric.js',
			'Viewport/Left/Options/Forms/Validation/Between.js',
			'Viewport/Left/Options/Forms/Validation/Captcha.js',
			'Viewport/Left/Options/Forms/Validation/Date.js',
			'Viewport/Left/Options/Forms/Validation/Digit.js',
			'Viewport/Left/Options/Forms/Validation/Email.js',
			'Viewport/Left/Options/Forms/Validation/Equals.js',
			'Viewport/Left/Options/Forms/Validation/FileAllowedTypes.js',
			'Viewport/Left/Options/Forms/Validation/FileMaximumSize.js',
			'Viewport/Left/Options/Forms/Validation/FileMinimumSize.js',
			'Viewport/Left/Options/Forms/Validation/Float.js',
			'Viewport/Left/Options/Forms/Validation/GreaterThan.js',
			'Viewport/Left/Options/Forms/Validation/InArray.js',
			'Viewport/Left/Options/Forms/Validation/Integer.js',
			'Viewport/Left/Options/Forms/Validation/Ip.js',
			'Viewport/Left/Options/Forms/Validation/Length.js',
			'Viewport/Left/Options/Forms/Validation/LessThan.js',
			'Viewport/Left/Options/Forms/Validation/RegExp.js',
			'Viewport/Left/Options/Forms/Validation/Required.js',
			'Viewport/Left/Options/Forms/Validation/Uri.js',
			'Viewport/Left/Form.js',
			'Viewport/Left/Form/Attributes.js',
			'Viewport/Left/Form/Prefix.js',
			'Viewport/Left/Form/PostProcessor.js',
			'Viewport/Left/Form/PostProcessors/PostProcessor.js',
			'Viewport/Left/Form/PostProcessors/Dummy.js',
			'Viewport/Left/Form/PostProcessors/Mail.js'
		);

			// Load ExtJS
		$this->pageRenderer->loadExtJS();

			// Load the wizards javascript
		foreach ($javascriptFiles as $javascriptFile) {
			$this->pageRenderer->addJsFile(
				$baseUrl . $javascriptFile,
				'text/javascript',
				$compress,
				FALSE
			);
		}
	}

	/**
	 * Load the necessarry css
	 *
	 * This will only be done when the referenced record is available
	 *
	 * @return void
	 */
	private function loadCss() {
			// TODO Set to TRUE when finished
		$compress = FALSE;
		$baseUrl = t3lib_div::resolveBackPath(
			'../../../../../' .
			t3lib_extMgm::siteRelPath('form') .
			'Resources/Public/CSS/'
		);

		$cssFiles = array(
			'Form.css',
			'Wizard/Wizard.css',
		);

			// Load the wizards css
		foreach ($cssFiles as $cssFile) {
			$this->pageRenderer->addCssFile(
				$baseUrl . $cssFile,
				'stylesheet',
				'all',
				'',
				$compress,
				FALSE
			);
		}
	}

	/**
	 * Load the settings
	 *
	 * The settings are defined in pageTSconfig mod.wizards.form
	 *
	 * @return void
	 */
	private function loadSettings() {
		$record = $this->repository->getRecord();
		$pageId = $record->getPageId();
		$modTSconfig = t3lib_BEfunc::getModTSconfig(
			$pageId,
			'mod.wizards.form'
		);
		$settings = $modTSconfig['properties'];
		$this->removeTrailingDotsFromTyposcript($settings);

		$this->doc->JScode .= $this->doc->wrapScriptTags(
			'TYPO3.Form.Wizard.Settings = ' . json_encode($settings) . ';'
		);

	}

	/**
	 * Reads locallang file into array (for possible include in header)
	 *
	 * @param $file
	 */
	private function loadLocalization() {
		$wizardLabels = $GLOBALS['LANG']->includeLLFile(
			'EXT:form/Resources/Private/Language/locallang_wizard.xml',
			FALSE,
			TRUE
		);
		$controllerLabels = $GLOBALS['LANG']->includeLLFile(
			'EXT:form/Resources/Private/Language/locallang_controller.xml',
			FALSE,
			TRUE
		);

		$labels = t3lib_div::array_merge_recursive_overrule(
			$controllerLabels,
			$wizardLabels
		);

		$this->pageRenderer->addInlineLanguageLabelArray($labels['default']);
	}

	/**
	 * Remove the trailing dots from the values in Typoscript
	 *
	 * @param array $array The array with the trailing dots
	 */
	private function removeTrailingDotsFromTyposcript(&$array) {
		if (is_array($array)) {
			foreach ($array as $key => $value) {
				if (is_array($value)) {
					$this->removeTrailingDotsFromTyposcript($value);
				}
				if (substr($key, -1) === '.') {
					$newKey = substr($key, 0, -1);
					unset($array[$key]);
					$array[$newKey] = $value;
				}
			}
		}
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform
	 * operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	private function getButtons() {
		$buttons = array(
			'csh' => '',
			'csh_buttons' => '',
			'close' => '',
			'save' => '',
			'save_close' => '',
			'reload' => '',
		);

			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem(
			'xMOD_csh_corebe',
			'wizard_forms_wiz',
			$GLOBALS['BACK_PATH'],
			''
		);

			// CSH Buttons
		$buttons['csh_buttons'] = t3lib_BEfunc::cshItem(
			'xMOD_csh_corebe',
			'wizard_forms_wiz_buttons',
			$GLOBALS['BACK_PATH'],
			''
		);

			// Close
		$getPostVariables = t3lib_div::_GP('P');
		$buttons['close'] = '<a href="#" onclick="' .
			htmlspecialchars('jumpToUrl(unescape(\'' .
				rawurlencode(
					t3lib_div::sanitizeLocalUrl(
						$getPostVariables['returnUrl']
					)
				) . '\')); return false;'
			) . '">' .
			t3lib_iconWorks::getSpriteIcon(
				'actions-document-close',
				array(
					'title' => $GLOBALS['LANG']->sL(
						'LLL:EXT:lang/locallang_core.php:rm.closeDoc',
						TRUE
					)
				)
			) . '</a>';

		return $buttons;
	}

	/**
	 * Generate the body content
	 *
	 * If there is an error, no reference to a record, a Flash Message will be
	 * displayed
	 *
	 * @return string The body content
	 */
	private function getBodyContent() {
		if ($this->recordIsAvailable) {
			$bodyContent = '';
		} else {
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('errorMessage', 1),
				$GLOBALS['LANG']->getLL('errorTitle', 1),
				t3lib_FlashMessage::ERROR
			);
			$bodyContent = $flashMessage->render();
		}

		return $bodyContent;
	}

	/**
	 * Set the content repository to use in this view
	 *
	 * @param tx_form_domain_repository_content $repository
	 * @return void
	 */
	public function setRepository(tx_form_domain_repository_content $repository) {
		$this->repository = $repository;
	}
}
?>