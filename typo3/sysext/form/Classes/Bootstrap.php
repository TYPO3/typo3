<?php
namespace TYPO3\CMS\Form;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Form\Hooks\PageLayoutView\MailformPreviewRenderer;
use TYPO3\CMS\Form\Hooks\ContentObjectHook;
use TYPO3\CMS\Form\Domain\Property\TypeConverter\ArrayToValidationElementConverter;

/**
 * Bootstrapping EXT:form configuration & behavior
 */
class Bootstrap {

	/**
	 * Gets registered element names that can be used
	 * in form's pseudo TypoScript to define form elements.
	 *
	 * @return array
	 */
	static public function getRegisteredElementNames() {
		return array(
			'BUTTON',
			'CHECKBOX',
			'CHECKBOXGROUP',
			'FIELDSET',
			'FILEUPLOAD',
			'HEADER',
			'HIDDEN',
			'IMAGEBUTTON',
			'OPTGROUP',
			'OPTION',
			'PASSWORD',
			'RADIO',
			'RADIOGROUP',
			'RESET',
			'SELECT',
			'SUBMIT',
			'TEXTAREA',
			'TEXTBLOCK',
			'TEXTLINE'
		);
	}

	/**
	 * Initializes configuration.
	 */
	static public function initializeConfiguration() {
		// Apply PageTSconfig
		ExtensionManagementUtility::addPageTSConfig(
			'<INCLUDE_TYPOSCRIPT: source="FILE:EXT:form/Configuration/PageTS/modWizards.ts">'
		);

		// Backend view
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['mailform'] = MailformPreviewRenderer::class;

		// Handling of cObjects "FORM" and "FORM_INT"
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array('FORM', ContentObjectHook::class);
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = array('FORM_INT', ContentObjectHook::class);

		// Extbase handling
		ExtensionUtility::registerTypeConverter(
			ArrayToValidationElementConverter::class
		);
		ExtensionUtility::configurePlugin(
			'TYPO3.CMS.Form',
			'Form',
			array('Frontend' => 'show, confirmation, dispatchConfirmationButtonClick, process, afterProcess'),
			array('Frontend' => 'show, confirmation, dispatchConfirmationButtonClick, process, afterProcess')
		);
	}

	/**
	 * Initializes settings.
	 */
	static public function initializeSettings() {
		// Register form wizard as backend module
		ExtensionManagementUtility::addModulePath(
			'wizard_form',
			'EXT:form/Modules/Wizards/FormWizard/'
		);

		// Register static TypoScript resource
		ExtensionManagementUtility::addStaticFile('form', 'Configuration/TypoScript/', 'Default TS');
	}

	/**
	 * Registers slots.
	 */
	static public function registerSlots() {
		$signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

		$signalSlotDispatcher->connect(
			\TYPO3\CMS\Form\Domain\Builder\FormBuilder::class,
			'txFormHandleIncomingValues',
			\TYPO3\CMS\Form\Hooks\HandleIncomingFormValues::class,
			'handleIncomingFormValues'
		);
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	static public function getObjectManager() {
		return GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
	}

}