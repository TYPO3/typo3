<?php
defined('TYPO3_MODE') or die();

// Register FormEngine node type resolver hook to render RTE in FormEngine if enabled
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'][1433167475] = [
    'nodeName' => 'text',
    'priority' => 40,
    'class' => \TYPO3\CMS\Rtehtmlarea\Form\Resolver\RichTextNodeResolver::class,
];

// Unserializing the configuration so we can use it here
$_EXTCONF = unserialize($_EXTCONF, ['allowed_classes' => false]);

// Add default RTE transformation configuration
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rtehtmlarea/Configuration/PageTSconfig/Proc/pageTSConfig.txt">');

// Add default Page TS Config RTE configuration
if (strpos($_EXTCONF['defaultConfiguration'], 'Minimal') !== false) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['defaultConfiguration'] = 'Advanced';
} elseif (strpos($_EXTCONF['defaultConfiguration'], 'Demo') !== false) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['defaultConfiguration'] = 'Demo';
    // Add default User TS Config RTE configuration
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rtehtmlarea/Configuration/UserTSconfig/Demo.txt">');
} else {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['defaultConfiguration'] = 'Typical';
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rtehtmlarea/Configuration/PageTSconfig/' . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['defaultConfiguration'] . '/pageTSConfig.txt">');

// Registering soft reference parser for image tags in RTE content
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser']['rtehtmlarea_images'] = \TYPO3\CMS\Rtehtmlarea\Hook\SoftReferenceHook::class;

// Add Status Report about Conflicting Extensions
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['htmlArea RTE'][] = \TYPO3\CMS\Rtehtmlarea\Hook\StatusReportConflictsCheckHook::class;

// Set warning in the Update Wizard of the Install Tool for deprecated Page TS Config properties
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['checkForDeprecatedRtePageTSConfigProperties'] = \TYPO3\CMS\Rtehtmlarea\Hook\Install\DeprecatedRteProperties::class;
// Set warning in the Update Wizard of the Install Tool for replacement of "acronym" button by "abbreviation" button
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['checkForRteAcronymButtonRenamedToAbbreviation'] = \TYPO3\CMS\Rtehtmlarea\Hook\Install\RteAcronymButtonRenamedToAbbreviation::class;

// Initialize plugin registration array
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins'] = [];

// Editor Mode configuration
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['EditorMode'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\EditorMode::class
];

// General Element configuration
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['EditElement'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\EditElement::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['MicrodataSchema'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\MicroDataSchema::class
];

// Inline Elements configuration
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['DefaultInline'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\DefaultInline::class
];

if ($_EXTCONF['enableInlineElements']) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['InlineElements'] = [
        'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\InlineElements::class
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rtehtmlarea/Configuration/PageTSconfig/Extensions/InlineElements/pageTSConfig.txt">');
}

// Block Elements configuration
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['BlockElements'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\BlockElements::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['DefinitionList'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\DefinitionList::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['BlockStyle'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\BlockStyle::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['CharacterMap'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\CharacterMap::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['Abbreviation'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\Abbreviation::class,
    'disableInFE' => true
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['UserElements'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\UserElements::class,
    'disableInFE'     => true
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TextStyle'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\TextStyle::class
];

// Enable images and add default Page TS Config RTE configuration for enabling images with the Minimal and Typical default configuration
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['enableImages'] = isset($_EXTCONF['enableImages']) && $_EXTCONF['enableImages'];
if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['defaultConfiguration'] === 'Demo') {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['enableImages'] = true;
}
if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['enableImages']) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['DefaultImage'] = [
        'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\DefaultImage::class
    ];

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Image'] = [
        'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\Typo3Image::class,
        'disableInFE'     => true
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rtehtmlarea/Configuration/PageTSconfig/Image/pageTSConfig.txt">');
}
// Add frontend image rendering TypoScript anyways
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('rtehtmlarea', 'setup', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rtehtmlarea/Configuration/TypoScript/ImageRendering/setup.txt">', 'defaultContentRendering');

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['DefaultLink'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\DefaultLink::class
];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\Typo3Link::class,
    'disableInFE' => true,
    'additionalAttributes' => 'rel'
];

// Add default Page TS Config RTE configuration for enabling links accessibility icons
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['enableAccessibilityIcons'] = isset($_EXTCONF['enableAccessibilityIcons']) && $_EXTCONF['enableAccessibilityIcons'];
if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['enableAccessibilityIcons']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rtehtmlarea/Configuration/PageTSconfig/AccessibilityIcons/pageTSConfig.txt">');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('rtehtmlarea', 'setup', '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rtehtmlarea/Configuration/TypoScript/AccessibilityIcons/setup.txt">', 'defaultContentRendering');
}

// Register features that use the style attribute
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['allowStyleAttribute'] = isset($_EXTCONF['allowStyleAttribute']) && $_EXTCONF['allowStyleAttribute'];
if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['allowStyleAttribute']) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Color'] = [
        'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\Typo3Color::class
    ];

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['SelectFont'] = [
        'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\SelectFont::class
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:rtehtmlarea/Configuration/PageTSconfig/Style/pageTSConfig.txt">');
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TextIndicator'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\TextIndicator::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['InsertSmiley'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\InsertSmiley::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['Language'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\Language::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['SpellChecker'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\Spellchecker::class,
    'AspellDirectory' => $_EXTCONF['AspellDirectory'] ?: '/usr/bin/aspell',
    'noSpellCheckLanguages' => $_EXTCONF['noSpellCheckLanguages'] ?: 'ja,km,ko,lo,th,zh,b5,gb',
    'forceCommandMode' => $_EXTCONF['forceCommandMode'] ?: false
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['FindReplace'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\FindReplace::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['RemoveFormat'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\RemoveFormat::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['PlainText'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\Plaintext::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['DefaultClean'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\DefaultClean::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3HtmlParser'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\Typo3HtmlParser::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['QuickTag'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\QuickTag::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TableOperations'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\TableOperations::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['AboutEditor'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\AboutEditor::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['ContextMenu'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\ContextMenu::class
];

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['UndoRedo'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\UndoRedo::class
];

// Copy & Paste configuration
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['CopyPaste'] = [
    'objectReference' => \TYPO3\CMS\Rtehtmlarea\Extension\CopyPaste::class
];
