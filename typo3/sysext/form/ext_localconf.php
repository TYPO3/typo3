<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    // Register FE plugin
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'TYPO3.CMS.Form',
        'Formframework',
        ['FormFrontend' => 'render, perform'],
        ['FormFrontend' => 'perform'],
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    // Add new content element wizard entry
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:form/Configuration/PageTS/modWizards.ts">'
    );

    // FE file upload processing
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(
        \TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter::class
    );

    // Hook to enrich tt_content form flex element with finisher settings and form list drop down
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['flexParsing'][
        \TYPO3\CMS\Form\Hooks\DataStructureIdentifierHook::class
    ] = \TYPO3\CMS\Form\Hooks\DataStructureIdentifierHook::class;

    // Hook to count used forms elements in tt_content
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser']['formPersistenceIdentifier'] =
        \TYPO3\CMS\Form\Hooks\SoftReferenceParserHook::class;

    // Add a bunch of icons to icon registry
    $iconIdentifiers = [
        'advanced-password',
        'checkbox',
        'content-element',
        'date-picker',
        'duplicate',
        'fieldset',
        'file-upload',
        'finisher',
        'image-upload',
        'insert-after',
        'insert-in',
        'multi-checkbox',
        'multi-select',
        'page',
        'password',
        'radio-button',
        'single-select',
        'static-text',
        'summary-page',
        'text',
        'textarea',
        'validator'
    ];
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    foreach ($iconIdentifiers as $iconIdentifier) {
        $iconRegistry->registerIcon(
            't3-form-icon-' . $iconIdentifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:form/Resources/Public/Images/' . $iconIdentifier . '.svg']
        );
    }
});
