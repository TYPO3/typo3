<?php
defined('TYPO3_MODE') or die();

// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);
if (!$_EXTCONF || $_EXTCONF['setPageTSconfig']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
		# Removes obsolete type values and fields from "Content Element" table "tt_content"
		TCEFORM.tt_content.image_frames.disabled = 1
	');
}


// Register language aware flex form handling in FormEngine
// Register render elements
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361297] = [
    'nodeName' => 'flex',
    'priority' => 40,
    'class' => \TYPO3\CMS\Compatibility6\Form\Container\FlexFormEntryContainer::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361298] = [
    'nodeName' => 'flexFormNoTabsContainer',
    'priority' => 40,
    'class' => \TYPO3\CMS\Compatibility6\Form\Container\FlexFormNoTabsContainer::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361299] = [
    'nodeName' => 'flexFormTabsContainer',
    'priority' => 40,
    'class' => \TYPO3\CMS\Compatibility6\Form\Container\FlexFormTabsContainer::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1443361300] = [
    'nodeName' => 'flexFormElementContainer',
    'priority' => 40,
    'class' => \TYPO3\CMS\Compatibility6\Form\Container\FlexFormElementContainer::class,
];
// Unregister stock TcaFlexProcess data provider and substitute with own data provider at the same position
unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
    [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class]
);
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
    [\TYPO3\CMS\Compatibility6\Form\FormDataProvider\TcaFlexProcess::class] = [
        'depends' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexPrepare::class,
        ]
    ];
unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
    [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class]['depends'][\TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexProcess::class]
);
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
    [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaRadioItems::class]['depends'][]
        = \TYPO3\CMS\Compatibility6\Form\FormDataProvider\TcaFlexProcess::class;
// Register "XCLASS" of FlexFormTools for language parsing
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['className']
    = TYPO3\CMS\Compatibility6\Configuration\FlexForm\FlexFormTools::class;
// Language diff updating in flex
$GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] = true;


// TCA migration if TCA registration still happened in ext_tables.php
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'] = array();
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'][] = \TYPO3\CMS\Compatibility6\Hooks\ExtTablesPostProcessing\TcaMigration::class;

// Register legacy content objects
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['IMGTEXT']      = \TYPO3\CMS\Compatibility6\ContentObject\ImageTextContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['CLEARGIF']     = \TYPO3\CMS\Compatibility6\ContentObject\ClearGifContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['CTABLE']       = \TYPO3\CMS\Compatibility6\ContentObject\ContentTableContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['OTABLE']       = \TYPO3\CMS\Compatibility6\ContentObject\OffsetTableContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['COLUMNS']      = \TYPO3\CMS\Compatibility6\ContentObject\ColumnsContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['HRULER']       = \TYPO3\CMS\Compatibility6\ContentObject\HorizontalRulerContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['FORM']         = \TYPO3\CMS\Compatibility6\ContentObject\FormContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['SEARCHRESULT'] = \TYPO3\CMS\Compatibility6\ContentObject\SearchResultContentObject::class;
// deprecated alias names for cObjects in use
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['COBJ_ARRAY']   = \TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject::class;
$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects']['CASEFUNC']     = \TYPO3\CMS\Frontend\ContentObject\CaseContentObject::class;

// Register a hook for data submission
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission']['mailform'] = \TYPO3\CMS\Compatibility6\Controller\FormDataSubmissionController::class;

// Register hooks for xhtml_cleaning and prefixLocalAnchors
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] = \TYPO3\CMS\Compatibility6\Hooks\TypoScriptFrontendController\ContentPostProcHook::class . '->contentPostProcAll';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'][] = \TYPO3\CMS\Compatibility6\Hooks\TypoScriptFrontendController\ContentPostProcHook::class . '->contentPostProcCached';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = \TYPO3\CMS\Compatibility6\Hooks\TypoScriptFrontendController\ContentPostProcHook::class . '->contentPostProcOutput';

/**
 * CType "mailform"
 */
// Only apply fallback to plain old FORM/mailform if extension "form" is not loaded
if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('form')) {
    // Add Default TypoScript for CType "mailform" after default content rendering
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('compatibility6', 'constants', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:compatibility6/Configuration/TypoScript/Form/constants.txt">', 'defaultContentRendering');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('compatibility6', 'setup', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:compatibility6/Configuration/TypoScript/Form/setup.txt">', 'defaultContentRendering');

    // Add the search CType to the "New Content Element" wizard
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	mod.wizards.newContentElement.wizardItems.forms {
		elements.mailform {
			iconIdentifier = content-elements-mailform
			title = LLL:EXT:compatibility6/Resources/Private/Language/locallang.xlf:forms_mail_title
			description = LLL:EXT:compatibility6/Resources/Private/Language/locallang.xlf:forms_mail_description
			tt_content_defValues {
				CType = mailform
				bodytext (
			# Example content:
			Name: | *name = input,40 | Enter your name here
			Email: | *email=input,40 |
			Address: | address=textarea,40,5 |
			Contact me: | tv=check | 1

			|formtype_mail = submit | Send form!
			|html_enabled=hidden | 1
			|subject=hidden| This is the subject
				)
			}
		}
		show :=addToList(mailform)
	}
	');

    // Register for hook to show preview of tt_content element of CType="mailform" in page module
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['mailform'] = \TYPO3\CMS\Compatibility6\Hooks\PageLayoutView\MailformPreviewRenderer::class;

    // Register for hook to show preview of tt_content element of CType="script" in page module
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['script'] = \TYPO3\CMS\Compatibility6\Hooks\PageLayoutView\ScriptPreviewRenderer::class;
}

/**
 * CType "search"
 */

// Add Default TypoScript for CType "search" after default content rendering
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('compatibility6', 'constants', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:compatibility6/Configuration/TypoScript/Search/constants.txt">', 'defaultContentRendering');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('compatibility6', 'setup', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:compatibility6/Configuration/TypoScript/Search/setup.txt">', 'defaultContentRendering');

// Add the search CType to the "New Content Element" wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
mod.wizards.newContentElement.wizardItems.forms {
	elements.search {
		iconIdentifier = content-elements-searchform
		title = LLL:EXT:compatibility6/Resources/Private/Language/locallang.xlf:forms_search_title
		description = LLL:EXT:compatibility6/Resources/Private/Language/locallang.xlf:forms_search_description
		tt_content_defValues.CType = search
	}
	show :=addToList(search)
}
');
