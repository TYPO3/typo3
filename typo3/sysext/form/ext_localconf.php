<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    // Apply PageTSconfig
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:form/Configuration/PageTS/modWizards.ts">'
    );

    // Backend view
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['mailform'] =
        \TYPO3\CMS\Form\Hooks\PageLayoutView\MailformPreviewRenderer::class;
} else {
    // Handling of cObjects "FORM" and "FORM_INT"
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = [
        'FORM',
        \TYPO3\CMS\Form\Hooks\ContentObjectHook::class
    ];
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass'][] = [
        'FORM_INT',
        \TYPO3\CMS\Form\Hooks\ContentObjectHook::class
    ];

    // Extbase handling
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(
        \TYPO3\CMS\Form\Domain\Property\TypeConverter\ArrayToValidationElementConverter::class
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'TYPO3.CMS.Form',
        'Form',
        ['Frontend' => 'show, confirmation, dispatchConfirmationButtonClick, process, afterProcess'],
        ['Frontend' => 'show, confirmation, dispatchConfirmationButtonClick, process, afterProcess']
    );

    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    $signalSlotDispatcher->connect(
        \TYPO3\CMS\Form\Domain\Builder\FormBuilder::class,
        'txFormHandleIncomingValues',
        \TYPO3\CMS\Form\Hooks\HandleIncomingFormValues::class,
        'handleIncomingFormValues'
    );
}
