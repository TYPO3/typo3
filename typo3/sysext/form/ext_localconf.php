<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    if (TYPO3_MODE === 'BE') {
        // Hook to enrich tt_content form flex element with finisher settings and form list drop down
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['flexParsing'][
            \TYPO3\CMS\Form\Hooks\DataStructureIdentifierHook::class
        ] = \TYPO3\CMS\Form\Hooks\DataStructureIdentifierHook::class;

        // Hook to count used forms elements in tt_content
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser']['formPersistenceIdentifier'] =
            \TYPO3\CMS\Form\Hooks\SoftReferenceParserHook::class;

        // Register for hook to show preview of tt_content element of CType="form_formframework" in page module
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['form_formframework'] =
            \TYPO3\CMS\Form\Hooks\FormPagePreviewRenderer::class;

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
            'form-element-selector',
            'gridcontainer',
            'gridrow',
            'hidden',
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

        // Add new content element wizard entry
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:form/Configuration/PageTS/modWizards.ts">'
        );
    }

    if (TYPO3_MODE === 'FE') {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterSubmit'][1489772699]
            = \TYPO3\CMS\Form\Hooks\FormElementsOnSubmitHooks::class;

        // FE file upload processing
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished'][1489772699]
            = \TYPO3\CMS\Form\Mvc\Property\PropertyMappingConfiguration::class;

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(
            \TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter::class
        );
    }

    // Register "formvh:" namespace
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['formvh'][] = 'TYPO3\\CMS\\Form\\ViewHelpers';

    // Register FE plugin
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'TYPO3.CMS.Form',
        'Formframework',
        ['FormFrontend' => 'render, perform'],
        ['FormFrontend' => 'perform'],
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
});
