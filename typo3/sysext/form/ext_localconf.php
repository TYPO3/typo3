<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
    // Apply PageTSconfig
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:form/Configuration/PageTS/modWizards.ts">'
    );

    // Add default User TS Config FORM configuration
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:form/Configuration/UserTSconfig/userTSConfig.txt">'
    );

    // Backend view
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['mailform'] =
        \TYPO3\CMS\Form\Hooks\PageLayoutView\MailformPreviewRenderer::class;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1440772316] = array(
        'nodeName' => 'formwizard',
        'priority' => 40,
        'class'    => \TYPO3\CMS\Form\View\Wizard\Element\FormWizardElement::class,
    );
}

// Extbase handling
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(
    \TYPO3\CMS\Form\Domain\Property\TypeConverter\ArrayToValidationElementConverter::class
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'TYPO3.CMS.Form',
    'Form',
    array('Frontend' => 'show, confirmation, dispatchConfirmationButtonClick, process, afterProcess'),
    array('Frontend' => 'show, confirmation, dispatchConfirmationButtonClick, process, afterProcess')
);

$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$signalSlotDispatcher->connect(
    \TYPO3\CMS\Form\Domain\Builder\FormBuilder::class,
    'txFormHandleIncomingValues',
    \TYPO3\CMS\Form\Hooks\HandleIncomingFormValues::class,
    'handleIncomingFormValues'
);

// Register the extbase plugin as shorthand for typoscript 10 = FORM
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['FORM'] = \TYPO3\CMS\Form\ContentObject\FormContentObject::class;
