<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Form\Controller\FormFrontendController;
use TYPO3\CMS\Form\Evaluation\EmailOrFormElementIdentifier;
use TYPO3\CMS\Form\Hooks\FormDefinitionDataHandlerHook;
use TYPO3\CMS\Form\Hooks\ImportExportHook;

defined('TYPO3') or die();

if (ExtensionManagementUtility::isLoaded('impexp')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/impexp/class.tx_impexp.php']['before_addSysFileRecord'][1530637161]
        = ImportExportHook::class . '->beforeAddSysFileRecordOnImport';
}

// Register RTE presets for form extension
// form-label: Simple formatting for labels (bold, italic, link)
if (empty($GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['form-label'])) {
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['form-label'] = 'EXT:form/Configuration/RTE/FormLabel.yaml';
}
// form-content: Extended formatting for content fields (includes lists)
if (empty($GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['form-content'])) {
    $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['form-content'] = 'EXT:form/Configuration/RTE/FormContent.yaml';
}

// Deny direct DataHandler write access to form_definition: only DatabaseStorageAdapter may persist form definitions
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['form'] = FormDefinitionDataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['form'] = FormDefinitionDataHandlerHook::class;

// Add validation call for input which contains email or form element identifier
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][EmailOrFormElementIdentifier::class] = '';

// Register FE plugin
ExtensionUtility::configurePlugin('Form', 'Formframework', [FormFrontendController::class => ['render', 'perform']], [FormFrontendController::class => ['perform']]);
