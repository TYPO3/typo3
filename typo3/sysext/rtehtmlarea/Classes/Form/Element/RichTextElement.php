<?php
namespace TYPO3\CMS\Rtehtmlarea\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\FrontendEditing\FrontendEditingController;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ClientUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * Render rich text editor in FormEngine
 */
class RichTextElement extends AbstractFormElement
{
    /**
     * Main result array as defined in initializeResultArray() of AbstractNode
     *
     * @var array
     */
    protected $resultArray;

    /**
     * pid of page record the TSconfig is located at.
     * This is pid of record if table is not pages, or uid if table is pages
     *
     * @var int
     */
    protected $pidOfPageRecord;

    /**
     * pid of fixed versioned record.
     * This is the pid of the record in normal cases, but is changed to the pid
     * of the "mother" record in case the handled record is a versioned overlay
     * and "mother" is located at a different pid.
     *
     * @var int
     */
    protected $pidOfVersionedMotherRecord;

    /**
     * Native, not further processed TsConfig of RTE section for this record on given pid.
     *
     * Example:
     *
     * RTE = foo
     * RTE.bar = xy
     *
     * array(
     * 	'value' => 'foo',
     * 	'properties' => array(
     * 		'bar' => 'xy',
     * 	),
     * );
     *
     * @var array
     */
    protected $vanillaRteTsConfig;

    /**
     * Based on $vanillaRteTsConfig, this property contains "processed" configuration
     * where table and type specific RTE setup is merged into 'default.' array.
     *
     * @var array
     */
    protected $processedRteConfiguration;

    /**
     * An unique identifier based on field name to have id attributes in HTML referenced in javascript.
     *
     * @var string
     */
    protected $domIdentifier;

    /**
     * Parsed "defaultExtras" TCA
     *
     * @var array
     */
    protected $defaultExtras;

    /**
     * Some client info containing "user agent", "browser", "version", "system"
     *
     * @var array
     */
    protected $client;

    /**
     * Selected language
     *
     * @var string
     */
    protected $language;

    /**
     * TYPO3 language code of the content language
     *
     * @var string
     */
    protected $contentTypo3Language;

    /**
     * ISO language code of the content language
     *
     * @var string
     */
    protected $contentISOLanguage;

    /**
     * Uid of chosen content language
     *
     * @var int
     */
    protected $contentLanguageUid;

    /**
     * The order of the toolbar: the name is the TYPO3-button name
     *
     * @var string
     */
    protected $defaultToolbarOrder;

    /**
     * Conversion array: TYPO3 button names to htmlArea button names
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'space' => 'space',
        'bar' => 'separator',
        'linebreak' => 'linebreak'
    ];

    /**
     * Final toolbar array
     *
     * @var array
     */
    protected $toolbar = [];

    /**
     * Save the buttons for the toolbar
     *
     * @var array
     */
    protected $toolbarOrderArray = [];

    /**
     * Plugin buttons
     *
     * @var array
     */
    protected $pluginButton = [];

    /**
     * Plugin labels
     *
     * @var array
     */
    protected $pluginLabel = [];

    /**
     * Array of plugin id's enabled in the current RTE editing area
     *
     * @var array
     */
    protected $pluginEnabledArray = [];

    /**
     * Cumulative array of plugin id's enabled so far in any of the RTE editing areas of the form
     *
     * @var array
     */
    protected $pluginEnabledCumulativeArray = [];

    /**
     * Array of registered plugins indexed by their plugin Id's
     *
     * @var array
     */
    protected $registeredPlugins = [];

    /**
     * This will render a <textarea> OR RTE area form field,
     * possibly with various control/validation features
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];

        $backendUser = $this->getBackendUserAuthentication();

        $this->resultArray = $this->initializeResultArray();
        $this->defaultExtras = BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras']);
        $this->pidOfPageRecord = $this->data['effectivePid'];
        BackendUtility::fixVersioningPid($table, $row);
        $this->pidOfVersionedMotherRecord = (int)$row['pid'];
        $this->vanillaRteTsConfig = $backendUser->getTSConfig('RTE', BackendUtility::getPagesTSconfig($this->pidOfPageRecord));
        $this->processedRteConfiguration = BackendUtility::RTEsetup(
            $this->vanillaRteTsConfig['properties'],
            $table,
            $fieldName,
            $this->data['recordTypeValue']
        );
        $this->client = $this->clientInfo();
        $this->domIdentifier = preg_replace('/[^a-zA-Z0-9_:.-]/', '_', $parameterArray['itemFormElName']);
        $this->domIdentifier = htmlspecialchars(preg_replace('/^[^a-zA-Z]/', 'x', $this->domIdentifier));

        $this->initializeLanguageRelatedProperties();

        // Get skin file name from Page TSConfig if any
        $skinFilename = trim($this->processedRteConfiguration['skin']) ?: 'EXT:rtehtmlarea/Resources/Public/Css/Skin/htmlarea.css';
        $skinFilename = $this->getFullFileName($skinFilename);
        $skinDirectory = dirname($skinFilename);

        // jQuery UI Resizable style sheet and main skin stylesheet
        $this->resultArray['stylesheetFiles'][] = $skinDirectory . '/jquery-ui-resizable.css';
        $this->resultArray['stylesheetFiles'][] = $skinFilename;

        $this->enableRegisteredPlugins();

        // Configure toolbar
        $this->setToolbar();

        // Check if some plugins need to be disabled
        $this->setPlugins();

        // Merge the list of enabled plugins with the lists from the previous RTE editing areas on the same form
        $this->pluginEnabledCumulativeArray = $this->pluginEnabledArray;

        $this->addInstanceJavaScriptRegistration();

        $this->addOnSubmitJavaScriptCode();

        // Add RTE JavaScript
        $this->loadRequireModulesForRTE();

        // Create language labels
        $this->createJavaScriptLanguageLabelsFromFiles();

        // Get RTE init JS code
        $this->resultArray['additionalJavaScriptPost'][] = $this->getRteInitJsCode();

        $html = $this->getMainHtml();

        $this->resultArray['html'] = $this->renderWizards(
            [$html],
            $parameterArray['fieldConf']['config']['wizards'],
            $table,
            $row,
            $fieldName,
            $parameterArray,
            $parameterArray['itemFormElName'],
            $this->defaultExtras,
            true
        );

        return $this->resultArray;
    }

    /**
     * Create main HTML elements
     *
     * @return string Main RTE html
     */
    protected function getMainHtml()
    {
        $backendUser = $this->getBackendUserAuthentication();

        if ($this->isInFullScreenMode()) {
            $width = '100%';
            $height = '100%';
            $paddingRight = '0px';
            $editorWrapWidth = '100%';
        } else {
            $options = $backendUser->userTS['options.'];
            $width = 530 + (isset($options['RTELargeWidthIncrement']) ? (int)$options['RTELargeWidthIncrement'] : 150);
            /** @var InlineStackProcessor  $inlineStackProcessor */
            $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
            $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
            $inlineStructureDepth = $inlineStackProcessor->getStructureDepth();
            $width -= $inlineStructureDepth > 0 ? ($inlineStructureDepth + 1) * 12 : 0;
            $widthOverride = isset($backendUser->uc['rteWidth']) && trim($backendUser->uc['rteWidth']) ? trim($backendUser->uc['rteWidth']) : trim($this->processedRteConfiguration['RTEWidthOverride']);
            if ($widthOverride) {
                if (strstr($widthOverride, '%')) {
                    if ($this->client['browser'] !== 'msie') {
                        $width = (int)$widthOverride > 0 ? (int)$widthOverride : '100%';
                    }
                } else {
                    $width = (int)$widthOverride > 0 ? (int)$widthOverride : $width;
                }
            }
            $width = strstr($width, '%') ? $width : $width . 'px';
            $height = 380 + (isset($options['RTELargeHeightIncrement']) ? (int)$options['RTELargeHeightIncrement'] : 0);
            $heightOverride = isset($backendUser->uc['rteHeight']) && (int)$backendUser->uc['rteHeight'] ? (int)$backendUser->uc['rteHeight'] : (int)$this->processedRteConfiguration['RTEHeightOverride'];
            $height = $heightOverride > 0 ? $heightOverride . 'px' : $height . 'px';
            $paddingRight = '2';
            $editorWrapWidth = '99%';
        }
        $rteDivStyle = 'position:relative; left:0px; top:0px; height:' . $height . '; width:' . $width . '; border: 1px solid black; padding: 2 ' . $paddingRight . ' 2 2;';

        $itemFormElementName = $this->data['parameterArray']['itemFormElName'];

        // This seems to result in:
        //	_TRANSFORM_bodytext (the handled field name) in case the field is a direct DB field
        //	_TRANSFORM_vDEF (constant string) in case the RTE is within a flex form
        $triggerFieldName = preg_replace('/\\[([^]]+)\\]$/', '[_TRANSFORM_\\1]', $itemFormElementName);

        $value = $this->transformDatabaseContentToEditor($this->data['parameterArray']['itemFormElValue']);

        $result = [];
        // The hidden field tells the DataHandler that processing should be done on this value.
        $result[] = '<input type="hidden" name="' . htmlspecialchars($triggerFieldName) . '" value="RTE" />';
        $result[] = '<div id="pleasewait' . $this->domIdentifier . '" class="pleasewait" style="display: block;" >';
        $result[] =    $this->getLanguageService()->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang.xlf:Please wait');
        $result[] = '</div>';
        $result[] = '<div id="editorWrap' . $this->domIdentifier . '" class="editorWrap" style="visibility: hidden; width:' . $editorWrapWidth . '; height:100%;">';
        $result[] =    '<textarea ' . $this->getValidationDataAsDataAttribute($this->data['parameterArray']['fieldConf']['config']) . ' id="RTEarea' . $this->domIdentifier . '" name="' . htmlspecialchars($itemFormElementName) . '" rows="0" cols="0" style="' . htmlspecialchars($rteDivStyle) . '">';
        $result[] =        htmlspecialchars($value);
        $result[] =    '</textarea>';
        $result[] = '</div>';

        return implode(LF, $result);
    }

    /**
     * Add registered plugins to the array of enabled plugins
     *
     * @return void
     */
    protected function enableRegisteredPlugins()
    {
        // Traverse registered plugins
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins'] as $pluginId => $pluginObjectConfiguration) {
                if (is_array($pluginObjectConfiguration) && isset($pluginObjectConfiguration['objectReference'])) {
                    /** @var RteHtmlAreaApi $plugin */
                    $plugin = GeneralUtility::makeInstance($pluginObjectConfiguration['objectReference']);
                    $configuration = [
                        'language' => $this->language,
                        'contentTypo3Language' => $this->contentTypo3Language,
                        'contentISOLanguage' => $this->contentISOLanguage,
                        'contentLanguageUid' => $this->contentLanguageUid,
                        'RTEsetup' => $this->vanillaRteTsConfig,
                        'client' => $this->client,
                        'thisConfig' => $this->processedRteConfiguration,
                        'specConf' => $this->defaultExtras,
                    ];
                    if ($plugin->main($configuration)) {
                        $this->registeredPlugins[$pluginId] = $plugin;
                        // Override buttons from previously registered plugins
                        $pluginButtons = GeneralUtility::trimExplode(',', $plugin->getPluginButtons(), true);
                        foreach ($this->pluginButton as $previousPluginId => $buttonList) {
                            $this->pluginButton[$previousPluginId] = implode(',', array_diff(GeneralUtility::trimExplode(',', $this->pluginButton[$previousPluginId], true), $pluginButtons));
                        }
                        $this->pluginButton[$pluginId] = $plugin->getPluginButtons();
                        $pluginLabels = GeneralUtility::trimExplode(',', $plugin->getPluginLabels(), true);
                        foreach ($this->pluginLabel as $previousPluginId => $labelList) {
                            $this->pluginLabel[$previousPluginId] = implode(',', array_diff(GeneralUtility::trimExplode(',', $this->pluginLabel[$previousPluginId], true), $pluginLabels));
                        }
                        $this->pluginLabel[$pluginId] = $plugin->getPluginLabels();
                        $this->pluginEnabledArray[] = $pluginId;
                    }
                }
            }
        }
        // Process overrides
        $hidePlugins = [];
        foreach ($this->registeredPlugins as $pluginId => $plugin) {
            /** @var RteHtmlAreaApi $plugin */
            if ($plugin->addsButtons() && !$this->pluginButton[$pluginId]) {
                $hidePlugins[] = $pluginId;
            }
        }
        $this->pluginEnabledArray = array_unique(array_diff($this->pluginEnabledArray, $hidePlugins));
    }

    /**
     * Set the toolbar config (only in this PHP-Object, not in JS):
     *
     * @return void
     */
    protected function setToolbar()
    {
        $backendUser = $this->getBackendUserAuthentication();

        if ($this->client['browser'] === 'msie' || $this->client['browser'] === 'opera') {
            $this->processedRteConfiguration['keepButtonGroupTogether'] = 0;
        }
        $this->defaultToolbarOrder = 'bar, blockstylelabel, blockstyle, textstylelabel, textstyle, linebreak,
			bar, formattext, bold,  strong, italic, emphasis, big, small, insertedtext, deletedtext, citation, code,'
                . 'definition, keyboard, monospaced, quotation, sample, variable, bidioverride, strikethrough, subscript, superscript, underline, span,
			bar, fontstyle, fontsize, bar, formatblock, insertparagraphbefore, insertparagraphafter, blockquote, line,
			bar, left, center, right, justifyfull,
			bar, orderedlist, unorderedlist, definitionlist, definitionitem, outdent, indent,
			bar, language, showlanguagemarks,lefttoright, righttoleft,
			bar, textcolor, bgcolor, textindicator,
			bar, editelement, showmicrodata,
			bar, image, emoticon, insertcharacter, insertsofthyphen, abbreviation, user,
			bar, link, unlink,
			bar, table,'
                . ($this->processedRteConfiguration['hideTableOperationsInToolbar']
                    && is_array($this->processedRteConfiguration['buttons.'])
                    && is_array($this->processedRteConfiguration['buttons.']['toggleborders.'])
                    && $this->processedRteConfiguration['buttons.']['toggleborders.']['keepInToolbar'] ? ' toggleborders,' : '')
            . 'bar, findreplace, spellcheck,
			bar, chMode, inserttag, removeformat, bar, copy, cut, paste, pastetoggle, pastebehaviour, bar, undo, redo, bar, about, linebreak,'
            . ($this->processedRteConfiguration['hideTableOperationsInToolbar'] ? '' : 'bar, toggleborders,')
            . ' bar, tableproperties, tablerestyle, bar, rowproperties, rowinsertabove, rowinsertunder, rowdelete, rowsplit, bar,
			columnproperties, columninsertbefore, columninsertafter, columndelete, columnsplit, bar,
			cellproperties, cellinsertbefore, cellinsertafter, celldelete, cellsplit, cellmerge';

        // Additional buttons from registered plugins
        foreach ($this->registeredPlugins as $pluginId => $plugin) {
            /** @var RteHtmlAreaApi $plugin */
            if ($this->isPluginEnabled($pluginId)) {
                $pluginButtons = $plugin->getPluginButtons();
                //Add only buttons not yet in the default toolbar order
                $addButtons = implode(
                    ',',
                    array_diff(
                        GeneralUtility::trimExplode(',', $pluginButtons, true),
                        GeneralUtility::trimExplode(',', $this->defaultToolbarOrder, true)
                    )
                );
                $this->defaultToolbarOrder = ($addButtons ? 'bar,' . $addButtons . ',linebreak,' : '') . $this->defaultToolbarOrder;
            }
        }
        $toolbarOrder = $this->processedRteConfiguration['toolbarOrder'] ?: $this->defaultToolbarOrder;
        // Getting rid of undefined buttons
        $this->toolbarOrderArray = array_intersect(GeneralUtility::trimExplode(',', $toolbarOrder, true), GeneralUtility::trimExplode(',', $this->defaultToolbarOrder, true));
        $toolbarOrder = array_unique(array_values($this->toolbarOrderArray));
        // Fetching specConf for field from backend
        $pList = is_array($this->defaultExtras['richtext']['parameters']) ? implode(',', $this->defaultExtras['richtext']['parameters']) : '';
        if ($pList !== '*') {
            // If not all
            $show = is_array($this->defaultExtras['richtext']['parameters']) ? $this->defaultExtras['richtext']['parameters'] : [];
            if ($this->processedRteConfiguration['showButtons']) {
                if (!GeneralUtility::inList($this->processedRteConfiguration['showButtons'], '*')) {
                    $show = array_unique(array_merge($show, GeneralUtility::trimExplode(',', $this->processedRteConfiguration['showButtons'], true)));
                } else {
                    $show = array_unique(array_merge($show, $toolbarOrder));
                }
            }
            if (is_array($this->processedRteConfiguration['showButtons.'])) {
                foreach ($this->processedRteConfiguration['showButtons.'] as $buttonId => $value) {
                    if ($value) {
                        $show[] = $buttonId;
                    }
                }
                $show = array_unique($show);
            }
        } else {
            $show = $toolbarOrder;
        }
        $RTEkeyList = isset($backendUser->userTS['options.']['RTEkeyList']) ? $backendUser->userTS['options.']['RTEkeyList'] : '*';
        if ($RTEkeyList !== '*') {
            // If not all
            $show = array_intersect($show, GeneralUtility::trimExplode(',', $RTEkeyList, true));
        }
        // Hiding buttons of disabled plugins
        $hideButtons = ['space', 'bar', 'linebreak'];
        foreach ($this->pluginButton as $pluginId => $buttonList) {
            if (!$this->isPluginEnabled($pluginId)) {
                $buttonArray = GeneralUtility::trimExplode(',', $buttonList, true);
                foreach ($buttonArray as $button) {
                    $hideButtons[] = $button;
                }
            }
        }
        // Hiding labels of disabled plugins
        foreach ($this->pluginLabel as $pluginId => $label) {
            if (!$this->isPluginEnabled($pluginId)) {
                $hideButtons[] = $label;
            }
        }
        // Hiding buttons
        $show = array_diff($show, GeneralUtility::trimExplode(',', $this->processedRteConfiguration['hideButtons'], true));
        // Apply toolbar constraints from registered plugins
        foreach ($this->registeredPlugins as $pluginId => $plugin) {
            if ($this->isPluginEnabled($pluginId) && method_exists($plugin, 'applyToolbarConstraints')) {
                $show = $plugin->applyToolbarConstraints($show);
            }
        }
        // Getting rid of the buttons for which we have no position
        $show = array_intersect($show, $toolbarOrder);
        foreach ($this->registeredPlugins as $pluginId => $plugin) {
            /** @var RteHtmlAreaApi $plugin */
            $plugin->setToolbar($show);
        }
        $this->toolbar = $show;
    }

    /**
     * Disable some plugins
     *
     * @return void
     */
    protected function setPlugins()
    {
        // Disabling a plugin that adds buttons if none of its buttons is in the toolbar
        $hidePlugins = [];
        foreach ($this->pluginButton as $pluginId => $buttonList) {
            /** @var RteHtmlAreaApi $plugin */
            $plugin = $this->registeredPlugins[$pluginId];
            if ($plugin->addsButtons()) {
                $showPlugin = false;
                $buttonArray = GeneralUtility::trimExplode(',', $buttonList, true);
                foreach ($buttonArray as $button) {
                    if (in_array($button, $this->toolbar)) {
                        $showPlugin = true;
                    }
                }
                if (!$showPlugin) {
                    $hidePlugins[] = $pluginId;
                }
            }
        }
        $this->pluginEnabledArray = array_diff($this->pluginEnabledArray, $hidePlugins);
        // Hiding labels of disabled plugins
        $hideLabels = [];
        foreach ($this->pluginLabel as $pluginId => $label) {
            if (!$this->isPluginEnabled($pluginId)) {
                $hideLabels[] = $label;
            }
        }
        $this->toolbar = array_diff($this->toolbar, $hideLabels);
        // Adding plugins declared as prerequisites by enabled plugins
        $requiredPlugins = [];
        foreach ($this->registeredPlugins as $pluginId => $plugin) {
            /** @var RteHtmlAreaApi $plugin */
            if ($this->isPluginEnabled($pluginId)) {
                $requiredPlugins = array_merge($requiredPlugins, GeneralUtility::trimExplode(',', $plugin->getRequiredPlugins(), true));
            }
        }
        $requiredPlugins = array_unique($requiredPlugins);
        foreach ($requiredPlugins as $pluginId) {
            if (is_object($this->registeredPlugins[$pluginId]) && !$this->isPluginEnabled($pluginId)) {
                $this->pluginEnabledArray[] = $pluginId;
            }
        }
        $this->pluginEnabledArray = array_unique($this->pluginEnabledArray);
        // Completing the toolbar conversion array for htmlArea
        foreach ($this->registeredPlugins as $pluginId => $plugin) {
            /** @var RteHtmlAreaApi $plugin */
            if ($this->isPluginEnabled($pluginId)) {
                $this->convertToolbarForHtmlAreaArray = array_unique(array_merge($this->convertToolbarForHtmlAreaArray, $plugin->getConvertToolbarForHtmlAreaArray()));
            }
        }
    }

    /**
     * Add RTE main scripts and plugin scripts
     *
     * @return void
     */
    protected function loadRequireModulesForRTE()
    {
        $this->resultArray['requireJsModules'] = [];
        $this->resultArray['requireJsModules'][] = 'TYPO3/CMS/Rtehtmlarea/HTMLArea/HTMLArea';
        foreach ($this->pluginEnabledCumulativeArray as $pluginId) {
            /** @var RteHtmlAreaApi $plugin */
            $plugin = $this->registeredPlugins[$pluginId];
            $extensionKey = is_object($plugin) ? $plugin->getExtensionKey() : 'rtehtmlarea';
            $requirePath = 'TYPO3/CMS/' . GeneralUtility::underscoredToUpperCamelCase($extensionKey);
            $this->resultArray['requireJsModules'][] = $requirePath . '/Plugins/' . $pluginId;
        }
    }

    /**
     * Return RTE initialization inline JavaScript code
     *
     * @return string RTE initialization inline JavaScript code
     */
    protected function getRteInitJsCode()
    {
        $skinFilename = trim($this->processedRteConfiguration['skin']) ?: 'EXT:rtehtmlarea/Resources/Public/Css/Skin/htmlarea.css';
        $skinFilename = $this->getFullFileName($skinFilename);
        $skinDirectory = dirname($skinFilename);
        // Editing area style sheet
        $editedContentCSS = GeneralUtility::createVersionNumberedFilename($skinDirectory . '/htmlarea-edited-content.css');

        return 'require(["TYPO3/CMS/Rtehtmlarea/HTMLArea/HTMLArea"], function (HTMLArea) {
			if (typeof RTEarea === "undefined") {
				RTEarea = new Object();
				RTEarea[0] = new Object();
				RTEarea[0].version = "' . $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['version'] . '";
				RTEarea[0].editorUrl = "' . ExtensionManagementUtility::extRelPath('rtehtmlarea') . '";
				RTEarea[0].editorSkin = "' . $skinDirectory . '/";
				RTEarea[0].editedContentCSS = "' . $editedContentCSS . '";
				RTEarea.init = function() {
					if (typeof HTMLArea === "undefined" || !Ext.isReady) {
						window.setTimeout(function () {
							RTEarea.init();
						}, 10);
					} else {
						HTMLArea.init();
					}
				};
				RTEarea.initEditor = function(editorNumber) {
					if (typeof HTMLArea === "undefined" || !HTMLArea.isReady) {
						window.setTimeout(function () {
							RTEarea.initEditor(editorNumber);
						}, 40);
					} else {
						HTMLArea.initEditor(editorNumber);
					}
				};
			}
			RTEarea.init();
		});';
    }

    /**
     * Return the Javascript code for configuring the RTE
     *
     * @return void
     */
    protected function addInstanceJavaScriptRegistration()
    {
        $backendUser = $this->getBackendUserAuthentication();

        $jsArray = [];
        $jsArray[] = 'if (typeof configureEditorInstance === "undefined") {';
        $jsArray[] = '	configureEditorInstance = new Object();';
        $jsArray[] = '}';
        $jsArray[] = 'configureEditorInstance[' . GeneralUtility::quoteJSvalue($this->domIdentifier) . '] = function() {';
        $jsArray[] = 'if (typeof RTEarea === "undefined" || typeof HTMLArea === "undefined") {';
        $jsArray[] = '	window.setTimeout("configureEditorInstance[' . GeneralUtility::quoteJSvalue($this->domIdentifier) . ']();", 40);';
        $jsArray[] = '} else {';
        $jsArray[] = 'editornumber = ' . GeneralUtility::quoteJSvalue($this->domIdentifier) . ';';
        $jsArray[] = 'RTEarea[editornumber] = new Object();';
        $jsArray[] = 'RTEarea[editornumber].RTEtsConfigParams = "&RTEtsConfigParams=' . rawurlencode($this->RTEtsConfigParams()) . '";';
        $jsArray[] = 'RTEarea[editornumber].number = editornumber;';
        $jsArray[] = 'RTEarea[editornumber].deleted = false;';
        $jsArray[] = 'RTEarea[editornumber].textAreaId = ' . GeneralUtility::quoteJSvalue($this->domIdentifier) . ';';
        $jsArray[] = 'RTEarea[editornumber].id = "RTEarea" + editornumber;';
        $jsArray[] = 'RTEarea[editornumber].RTEWidthOverride = "'
            . (isset($backendUser->uc['rteWidth']) && trim($backendUser->uc['rteWidth'])
                ? trim($backendUser->uc['rteWidth'])
                : trim($this->processedRteConfiguration['RTEWidthOverride'])) . '";';
        $jsArray[] = 'RTEarea[editornumber].RTEHeightOverride = "'
            . (isset($backendUser->uc['rteHeight']) && (int)$backendUser->uc['rteHeight']
                ? (int)$backendUser->uc['rteHeight']
                : (int)$this->processedRteConfiguration['RTEHeightOverride']) . '";';
        $jsArray[] = 'RTEarea[editornumber].resizable = '
            . (isset($backendUser->uc['rteResize']) && $backendUser->uc['rteResize']
                ? 'true;'
                : (trim($this->processedRteConfiguration['rteResize']) ? 'true;' : 'false;'));
        $jsArray[] = 'RTEarea[editornumber].maxHeight = "'
            . (isset($backendUser->uc['rteMaxHeight']) && (int)$backendUser->uc['rteMaxHeight']
                ? trim($backendUser->uc['rteMaxHeight'])
                : ((int)$this->processedRteConfiguration['rteMaxHeight'] ?: '2000')) . '";';
        $jsArray[] = 'RTEarea[editornumber].fullScreen = ' . ($this->isInFullScreenMode() ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].showStatusBar = ' . (trim($this->processedRteConfiguration['showStatusBar']) ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].enableWordClean = ' . (trim($this->processedRteConfiguration['enableWordClean']) ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].htmlRemoveComments = ' . (trim($this->processedRteConfiguration['removeComments']) ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].disableEnterParagraphs = ' . (trim($this->processedRteConfiguration['disableEnterParagraphs']) ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].disableObjectResizing = ' . (trim($this->processedRteConfiguration['disableObjectResizing']) ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].removeTrailingBR = ' . (trim($this->processedRteConfiguration['removeTrailingBR']) ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].useCSS = ' . (trim($this->processedRteConfiguration['useCSS']) ? 'true' : 'false') . ';';
        $jsArray[] = 'RTEarea[editornumber].keepButtonGroupTogether = ' . (trim($this->processedRteConfiguration['keepButtonGroupTogether']) ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].disablePCexamples = ' . (trim($this->processedRteConfiguration['disablePCexamples']) ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].showTagFreeClasses = ' . (trim($this->processedRteConfiguration['showTagFreeClasses']) ? 'true;' : 'false;');
        $jsArray[] = 'RTEarea[editornumber].tceformsNested = ' . (!empty($this->data) ? json_encode($this->data['tabAndInlineStack']) : '[]') . ';';
        $jsArray[] = 'RTEarea[editornumber].dialogueWindows = new Object();';
        if (isset($this->processedRteConfiguration['dialogueWindows.']['defaultPositionFromTop'])) {
            $jsArray[] = 'RTEarea[editornumber].dialogueWindows.positionFromTop = ' . (int)$this->processedRteConfiguration['dialogueWindows.']['defaultPositionFromTop'] . ';';
        }
        if (isset($this->processedRteConfiguration['dialogueWindows.']['defaultPositionFromLeft'])) {
            $jsArray[] = 'RTEarea[editornumber].dialogueWindows.positionFromLeft = ' . (int)$this->processedRteConfiguration['dialogueWindows.']['defaultPositionFromLeft'] . ';';
        }
        $jsArray[] = 'RTEarea[editornumber].sys_language_content = "' . $this->contentLanguageUid . '";';
        $jsArray[] = 'RTEarea[editornumber].typo3ContentLanguage = "' . $this->contentTypo3Language . '";';
        $jsArray[] = 'RTEarea[editornumber].userUid = "' . 'BE_' . $backendUser->user['uid'] . '";';

        // Setting the plugin flags
        $jsArray[] = 'RTEarea[editornumber].plugin = new Object();';
        foreach ($this->pluginEnabledArray as $pluginId) {
            $jsArray[] = 'RTEarea[editornumber].plugin.' . $pluginId . ' = true;';
        }

        // Setting the buttons configuration
        $jsArray[] = 'RTEarea[editornumber].buttons = new Object();';
        if (is_array($this->processedRteConfiguration['buttons.'])) {
            foreach ($this->processedRteConfiguration['buttons.'] as $buttonIndex => $conf) {
                $button = substr($buttonIndex, 0, -1);
                if (is_array($conf)) {
                    $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . ' = ' . $this->buildNestedJSArray($conf) . ';';
                }
            }
        }

        // Setting the list of tags to be removed if specified in the RTE config
        if (trim($this->processedRteConfiguration['removeTags'])) {
            $jsArray[] = 'RTEarea[editornumber].htmlRemoveTags = /^(' . implode('|', GeneralUtility::trimExplode(',', $this->processedRteConfiguration['removeTags'], true)) . ')$/i;';
        }

        // Setting the list of tags to be removed with their contents if specified in the RTE config
        if (trim($this->processedRteConfiguration['removeTagsAndContents'])) {
            $jsArray[] = 'RTEarea[editornumber].htmlRemoveTagsAndContents = /^(' . implode('|', GeneralUtility::trimExplode(',', $this->processedRteConfiguration['removeTagsAndContents'], true)) . ')$/i;';
        }

        // Setting array of custom tags if specified in the RTE config
        if (!empty($this->processedRteConfiguration['customTags'])) {
            $customTags = GeneralUtility::trimExplode(',', $this->processedRteConfiguration['customTags'], true);
            if (!empty($customTags)) {
                $jsArray[] = 'RTEarea[editornumber].customTags= ' . json_encode($customTags) . ';';
            }
        }

        // Setting array of content css files if specified in the RTE config
        $versionNumberedFileNames = [];
        $contentCssFileNames = $this->getContentCssFileNames();
        foreach ($contentCssFileNames as $contentCssFileName) {
            $versionNumberedFileNames[] = GeneralUtility::createVersionNumberedFilename($contentCssFileName);
        }
        $jsArray[] = 'RTEarea[editornumber].pageStyle = ["' . implode('","', $versionNumberedFileNames) . '"];';

        $jsArray[] = 'RTEarea[editornumber].classesUrl = "' . $this->writeTemporaryFile(('classes_' . $this->language), 'js', $this->buildJSClassesArray()) . '";';

        // Add Javascript configuration for registered plugins
        foreach ($this->registeredPlugins as $pluginId => $plugin) {
            /** @var RteHtmlAreaApi $plugin */
            if ($this->isPluginEnabled($pluginId)) {
                $jsPluginString = $plugin->buildJavascriptConfiguration();
                if ($jsPluginString) {
                    $jsArray[] = $plugin->buildJavascriptConfiguration();
                }
            }
        }

        // Avoid premature reference to HTMLArea when being initially loaded by IRRE Ajax call
        $jsArray[] = 'RTEarea[editornumber].toolbar = ' . $this->getJSToolbarArray() . ';';
        $jsArray[] = 'RTEarea[editornumber].convertButtonId = ' . json_encode(array_flip($this->convertToolbarForHtmlAreaArray)) . ';';
        $jsArray[] = 'RTEarea.initEditor(editornumber);';
        $jsArray[] = '}';
        $jsArray[] = '}';
        $jsArray[] = 'configureEditorInstance[' . GeneralUtility::quoteJSvalue($this->domIdentifier) . ']();';

        $this->resultArray['additionalJavaScriptPost'][] =  implode(LF, $jsArray);
    }

    /**
     * Get the name of the contentCSS files to use
     *
     * @return array An array of full file name of the content css files to use
     */
    protected function getContentCssFileNames()
    {
        $contentCss = is_array($this->processedRteConfiguration['contentCSS.']) ? $this->processedRteConfiguration['contentCSS.'] : [];
        if (isset($this->processedRteConfiguration['contentCSS'])) {
            $contentCss[] = trim($this->processedRteConfiguration['contentCSS']);
        }
        $contentCssFiles = [];
        if (!empty($contentCss)) {
            foreach ($contentCss as $contentCssKey => $contentCssfile) {
                $fileName = trim($contentCssfile);
                $absolutePath = GeneralUtility::getFileAbsFileName($fileName);
                if (file_exists($absolutePath) && filesize($absolutePath)) {
                    $contentCssFiles[$contentCssKey] = $this->getFullFileName($fileName);
                }
            }
        } else {
            // Fallback to default content css file if none of the configured files exists and is not empty
            $contentCssFiles['default'] = $this->getFullFileName('EXT:rtehtmlarea/Resources/Public/Css/ContentCss/Default.css');
        }
        return array_unique($contentCssFiles);
    }

    /**
     * Return TRUE, if the plugin can be loaded
     *
     * @param string $pluginId: The identification string of the plugin
     * @return bool TRUE if the plugin can be loaded
     */
    protected function isPluginEnabled($pluginId)
    {
        return in_array($pluginId, $this->pluginEnabledArray);
    }

    /**
     * Return JS arrays of classes configuration
     *
     * @return string JS classes arrays
     */
    protected function buildJSClassesArray()
    {
        $RTEProperties = $this->vanillaRteTsConfig['properties'];
        // Declare sub-arrays
        $classesArray = [
            'labels' => [],
            'values' => [],
            'noShow' => [],
            'alternating' => [],
            'counting' => [],
            'selectable' => [],
            'requires' => [],
            'requiredBy' => [],
            'XOR' => []
        ];
        $JSClassesArray = '';
        // Scanning the list of classes if specified in the RTE config
        if (is_array($RTEProperties['classes.'])) {
            foreach ($RTEProperties['classes.'] as $className => $conf) {
                $className = rtrim($className, '.');

                $label = '';
                if (!empty($conf['name'])) {
                    $label = $this->getLanguageService()->sL(trim($conf['name']));
                    $label = str_replace('"', '\\"', str_replace('\\\'', '\'', $label));
                }
                $classesArray['labels'][$className] = $label;
                $classesArray['values'][$className] = str_replace('\\\'', '\'', $conf['value']);
                if (isset($conf['noShow'])) {
                    $classesArray['noShow'][$className] = $conf['noShow'];
                }
                if (is_array($conf['alternating.'])) {
                    $classesArray['alternating'][$className] = $conf['alternating.'];
                }
                if (is_array($conf['counting.'])) {
                    $classesArray['counting'][$className] = $conf['counting.'];
                }
                if (isset($conf['selectable'])) {
                    $classesArray['selectable'][$className] = $conf['selectable'];
                }
                if (isset($conf['requires'])) {
                    $classesArray['requires'][$className] = explode(',', GeneralUtility::rmFromList($className, $this->cleanList($conf['requires'])));
                }
            }
            // Remove circularities from classes dependencies
            $requiringClasses = array_keys($classesArray['requires']);
            foreach ($requiringClasses as $requiringClass) {
                if ($this->hasCircularDependency($classesArray, $requiringClass, $requiringClass)) {
                    unset($classesArray['requires'][$requiringClass]);
                }
            }
            // Reverse relationship for the dependency checks when removing styles
            $requiringClasses = array_keys($classesArray['requires']);
            foreach ($requiringClasses as $className) {
                foreach ($classesArray['requires'][$className] as $requiredClass) {
                    if (!is_array($classesArray['requiredBy'][$requiredClass])) {
                        $classesArray['requiredBy'][$requiredClass] = [];
                    }
                    if (!in_array($className, $classesArray['requiredBy'][$requiredClass])) {
                        $classesArray['requiredBy'][$requiredClass][] = $className;
                    }
                }
            }
        }
        // Scanning the list of sets of mutually exclusives classes if specified in the RTE config
        if (is_array($RTEProperties['mutuallyExclusiveClasses.'])) {
            foreach ($RTEProperties['mutuallyExclusiveClasses.'] as $listName => $conf) {
                $classSet = GeneralUtility::trimExplode(',', $conf, true);
                $classList = implode(',', $classSet);
                foreach ($classSet as $className) {
                    $classesArray['XOR'][$className] = '/^(' . implode('|', GeneralUtility::trimExplode(',', GeneralUtility::rmFromList($className, $classList), true)) . ')$/';
                }
            }
        }
        foreach ($classesArray as $key => $subArray) {
            $JSClassesArray .= 'HTMLArea.classes' . ucfirst($key) . ' = ' . $this->buildNestedJSArray($subArray) . ';' . LF;
        }
        return $JSClassesArray;
    }

    /**
     * Check for possible circularity in classes dependencies
     *
     * @param array $classesArray: reference to the array of classes dependencies
     * @param string $requiringClass: class requiring at some iteration level from the initial requiring class
     * @param string $initialClass: initial class from which a circular relationship is being searched
     * @param int $recursionLevel: depth of recursive call
     * @return bool TRUE, if a circular relationship is found
     */
    protected function hasCircularDependency(&$classesArray, $requiringClass, $initialClass, $recursionLevel = 0)
    {
        if (is_array($classesArray['requires'][$requiringClass])) {
            if (in_array($initialClass, $classesArray['requires'][$requiringClass])) {
                return true;
            } else {
                if ($recursionLevel++ < 20) {
                    foreach ($classesArray['requires'][$requiringClass] as $requiringClass2) {
                        if ($this->hasCircularDependency($classesArray, $requiringClass2, $initialClass, $recursionLevel)) {
                            return true;
                        }
                    }
                }
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Translate Page TS Config array in JS nested array definition
     * Replace 0 values with false
     * Unquote regular expression values
     * Replace empty arrays with empty objects
     *
     * @param array $conf: Page TSConfig configuration array
     * @return string nested JS array definition
     */
    protected function buildNestedJSArray($conf)
    {
        $convertedConf = GeneralUtility::removeDotsFromTS($conf);
        return str_replace(
            [':"0"', ':"\\/^(', ')$\\/i"', ':"\\/^(', ')$\\/"', '[]'],
            [':false', ':/^(', ')$/i', ':/^(', ')$/', '{}'], json_encode($convertedConf)
        );
    }

    /**
     * Writes contents in a file in typo3temp and returns the file name
     *
     * @param string $label: A label to insert at the beginning of the name of the file
     * @param string $fileExtension: The file extension of the file, defaulting to 'js'
     * @param string $contents: The contents to write into the file
     * @return string The name of the file written to typo3temp
     * @throws \RuntimeException If writing to file failed
     */
    protected function writeTemporaryFile($label, $fileExtension = 'js', $contents = '')
    {
        $relativeFilename = 'typo3temp/RteHtmlArea/' . str_replace('-', '_', $label) . '_' . GeneralUtility::shortMD5($contents, 20) . '.' . $fileExtension;
        $destination = PATH_site . $relativeFilename;
        if (!file_exists($destination)) {
            $minifiedJavaScript = '';
            if ($fileExtension === 'js' && $contents !== '') {
                $minifiedJavaScript = GeneralUtility::minifyJavaScript($contents);
            }
            $failure = GeneralUtility::writeFileToTypo3tempDir($destination, $minifiedJavaScript ? $minifiedJavaScript : $contents);
            if ($failure) {
                throw new \RuntimeException($failure, 1294585668);
            }
        }
        if (isset($GLOBALS['TSFE'])) {
            $fileName = $relativeFilename;
        } else {
            $fileName = '../' . $relativeFilename;
        }
        return GeneralUtility::resolveBackPath($fileName);
    }

    /**
     * Both rte framework and rte plugins can have label files that are
     * used in JS. The methods gathers those and creates a JS object from
     * file labels.
     *
     * @return string
     */
    protected function createJavaScriptLanguageLabelsFromFiles()
    {
        $labelArray = [];
        // Load labels of 3 base files into JS
        foreach (['tooltips', 'msg', 'dialogs'] as $identifier) {
            $fileName = 'EXT:rtehtmlarea/Resources/Private/Language/locallang_' . $identifier . '.xlf';
            $newLabels = $this->getMergedLabelsFromFile($fileName);
            if (!empty($newLabels)) {
                $labelArray[$identifier] = $newLabels;
            }
        }
        // Load labels of plugins into JS
        foreach ($this->pluginEnabledCumulativeArray as $pluginId) {
            /** @var RteHtmlAreaApi $plugin */
            $plugin = $this->registeredPlugins[$pluginId];
            $extensionKey = is_object($plugin) ? $plugin->getExtensionKey() : 'rtehtmlarea';
            $fileName = 'EXT:' . $extensionKey . '/Resources/Private/Language/Plugins/' . $pluginId . '/locallang_js.xlf';
            $newLabels = $this->getMergedLabelsFromFile($fileName);
            if (!empty($newLabels)) {
                $labelArray[$pluginId] = $newLabels;
            }
        }
        $javaScriptString = 'TYPO3.jQuery(function() {';
        $javaScriptString .= 'HTMLArea.I18N = new Object();' . LF;
        $javaScriptString .= 'HTMLArea.I18N = ' . json_encode($labelArray);
        $javaScriptString .= '});';
        $this->resultArray['additionalJavaScriptPost'][] = $javaScriptString;
    }

    /**
     * Get all labels from a specific label file, merge default
     * labels and target language labels.
     *
     * @param string $fileName The file to merge labels from
     * @return array Label keys and values
     */
    protected function getMergedLabelsFromFile($fileName)
    {
        /** @var $languageFactory LocalizationFactory */
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
        $localizationArray = $languageFactory->getParsedData($fileName, $this->language, 'utf-8', 1);
        if (is_array($localizationArray) && !empty($localizationArray)) {
            if (!empty($localizationArray[$this->language])) {
                $finalLocalLang = $localizationArray['default'];
                ArrayUtility::mergeRecursiveWithOverrule($finalLocalLang, $localizationArray[$this->language], true, false);
                $localizationArray[$this->language] = $finalLocalLang;
            } else {
                $localizationArray[$this->language] = $localizationArray['default'];
            }
        } else {
            $localizationArray = [];
        }
        return $localizationArray[$this->language];
    }

    /**
     * Return the JS code of the toolbar configuration for the HTMLArea editor
     *
     * @return string The JS code as nested JS arrays
     */
    protected function getJSToolbarArray()
    {
        // The toolbar array
        $toolbar = [];
        // The current row;  a "linebreak" ends the current row
        $row = [];
        // The current group; each group is between "bar"s; a "linebreak" ends the current group
        $group = [];
        // Process each toolbar item in the toolbar order list
        foreach ($this->toolbarOrderArray as $item) {
            switch ($item) {
                case 'linebreak':
                    // Add row to toolbar if not empty
                    if (!empty($group)) {
                        $row[] = $group;
                        $group = [];
                    }
                    if (!empty($row)) {
                        $toolbar[] = $row;
                        $row = [];
                    }
                    break;
                case 'bar':
                    // Add group to row if not empty
                    if (!empty($group)) {
                        $row[] = $group;
                        $group = [];
                    }
                    break;
                case 'space':
                    if (end($group) != $this->convertToolbarForHtmlAreaArray[$item]) {
                        $group[] = $this->convertToolbarForHtmlAreaArray[$item];
                    }
                    break;
                default:
                    if (in_array($item, $this->toolbar)) {
                        // Add the item to the group
                        $convertedItem = $this->convertToolbarForHtmlAreaArray[$item];
                        if ($convertedItem) {
                            $group[] = $convertedItem;
                        }
                    }
            }
        }
        // Add the last group and last line, if not empty
        if (!empty($group)) {
            $row[] = $group;
        }
        if (!empty($row)) {
            $toolbar[] = $row;
        }
        return json_encode($toolbar);
    }

    /**
     * Make a file name relative to the PATH_site or to the PATH_typo3
     *
     * @param string $filename: a file name of the form EXT:.... or relative to the PATH_site
     * @return string the file name relative to the PATH_site if in frontend or relative to the PATH_typo3 if in backend
     */
    protected function getFullFileName($filename)
    {
        if (substr($filename, 0, 4) === 'EXT:') {
            // extension
            list($extKey, $local) = explode('/', substr($filename, 4), 2);
            $newFilename = '';
            if ((string)$extKey !== '' && ExtensionManagementUtility::isLoaded($extKey) && (string)$local !== '') {
                $newFilename = ($this->isFrontendEditActive()
                        ? ExtensionManagementUtility::siteRelPath($extKey)
                        : ExtensionManagementUtility::extRelPath($extKey))
                    . $local;
            }
        } else {
            $path = ($this->isFrontendEditActive() ? '' : '../');
            $newFilename = $path . ($filename[0] === '/' ? substr($filename, 1) : $filename);
        }
        return GeneralUtility::resolveBackPath($newFilename);
    }

    /**
     * Return the Javascript code for copying the HTML code from the editor into the hidden input field.
     *
     * @return void
     */
    protected function addOnSubmitJavaScriptCode()
    {
        $onSubmitCode = [];
        $onSubmitCode[] = 'if (RTEarea[' . GeneralUtility::quoteJSvalue($this->domIdentifier) . ']) {';
        $onSubmitCode[] =    'document.editform[' . GeneralUtility::quoteJSvalue($this->data['parameterArray']['itemFormElName']) . '].value = RTEarea[' . GeneralUtility::quoteJSvalue($this->domIdentifier) . '].editor.getHTML();';
        $onSubmitCode[] = '} else {';
        $onSubmitCode[] =    'OK = 0;';
        $onSubmitCode[] = '};';
        $this->resultArray['additionalJavaScriptSubmit'][] = implode(LF, $onSubmitCode);
    }

    /**
     * Checks if frontend editing is active.
     *
     * @return bool TRUE if frontend editing is active
     */
    protected function isFrontendEditActive()
    {
        return is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->beUserLogin && $GLOBALS['BE_USER']->frontendEdit instanceof FrontendEditingController;
    }

    /**
     * Client Browser Information
     *
     * @return array Contains keys "user agent", "browser", "version", "system"
     */
    protected function clientInfo()
    {
        $userAgent = GeneralUtility::getIndpEnv('HTTP_USER_AGENT');
        $browserInfo = ClientUtility::getBrowserInfo($userAgent);
        // Known engines: order is not irrelevant!
        $knownEngines = ['opera', 'msie', 'gecko', 'webkit'];
        if (is_array($browserInfo['all'])) {
            foreach ($knownEngines as $engine) {
                if ($browserInfo['all'][$engine]) {
                    $browserInfo['browser'] = $engine;
                    $browserInfo['version'] = ClientUtility::getVersion($browserInfo['all'][$engine]);
                    break;
                }
            }
        }
        return $browserInfo;
    }

    /**
     * Initialize a couple of language related local properties
     *
     * @return void
     */
    public function initializeLanguageRelatedProperties()
    {
        $database = $this->getDatabaseConnection();
        $this->language = $GLOBALS['LANG']->lang;
        if ($this->language === 'default' || !$this->language) {
            $this->language = 'en';
        }
        $currentLanguageUid = $this->data['databaseRow']['sys_language_uid'];
        if (is_array($currentLanguageUid)) {
            $currentLanguageUid = $currentLanguageUid[0];
        }
        $this->contentLanguageUid = (int)max($currentLanguageUid, 0);
        if ($this->contentLanguageUid) {
            $this->contentISOLanguage = $this->language;
            if (ExtensionManagementUtility::isLoaded('static_info_tables')) {
                $tableA = 'sys_language';
                $tableB = 'static_languages';
                $selectFields = $tableA . '.uid,' . $tableB . '.lg_iso_2,' . $tableB . '.lg_country_iso_2';
                $tableAB = $tableA . ' LEFT JOIN ' . $tableB . ' ON ' . $tableA . '.static_lang_isocode=' . $tableB . '.uid';
                $whereClause = $tableA . '.uid = ' . intval($this->contentLanguageUid);
                $whereClause .= BackendUtility::BEenableFields($tableA);
                $whereClause .= BackendUtility::deleteClause($tableA);
                $res = $database->exec_SELECTquery($selectFields, $tableAB, $whereClause);
                while ($languageRow = $database->sql_fetch_assoc($res)) {
                    $this->contentISOLanguage = strtolower(trim($languageRow['lg_iso_2']) . (trim($languageRow['lg_country_iso_2']) ? '_' . trim($languageRow['lg_country_iso_2']) : ''));
                }
                $database->sql_free_result($res);
            }
        } else {
            $this->contentISOLanguage = trim($this->processedRteConfiguration['defaultContentLanguage']) ?: 'en';
            $languageCodeParts = explode('_', $this->contentISOLanguage);
            $this->contentISOLanguage = strtolower($languageCodeParts[0]) . ($languageCodeParts[1] ? '_' . strtoupper($languageCodeParts[1]) : '');
            // Find the configured language in the list of localization locales
            /** @var $locales Locales */
            $locales = GeneralUtility::makeInstance(Locales::class);
            // If not found, default to 'en'
            if (!in_array($this->contentISOLanguage, $locales->getLocales())) {
                $this->contentISOLanguage = 'en';
            }
        }
        $this->contentTypo3Language = $this->contentISOLanguage === 'en' ? 'default' : $this->contentISOLanguage;
    }

    /**
     * Log usage of deprecated Page TS Config Property
     *
     * @param string $deprecatedProperty: Name of deprecated property
     * @param string $useProperty: Name of property to use instead
     * @param string $version: Version of TYPO3 in which the property will be removed
     * @return void
     */
    protected function logDeprecatedProperty($deprecatedProperty, $useProperty, $version)
    {
        $backendUser = $this->getBackendUserAuthentication();
        if (!$this->processedRteConfiguration['logDeprecatedProperties.']['disabled']) {
            $message = sprintf(
                'RTE Page TSConfig property "%1$s" used on page id #%4$s is DEPRECATED and will be removed in TYPO3 %3$s. Use "%2$s" instead.',
                $deprecatedProperty,
                $useProperty,
                $version,
                $this->data['databaseRow']['pid']
            );
            GeneralUtility::deprecationLog($message);
            if ($this->processedRteConfiguration['logDeprecatedProperties.']['logAlsoToBELog']) {
                $message = sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang.xlf:deprecatedPropertyMessage'),
                    $deprecatedProperty,
                    $useProperty,
                    $version,
                    $this->data['databaseRow']['pid']
                );
                $backendUser->simplelog($message, 'rtehtmlarea');
            }
        }
    }

    /**
     * A list of parameters that is mostly given as GET/POST to other RTE controllers.
     *
     * @return string
     */
    protected function RTEtsConfigParams()
    {
        $parameters = BackendUtility::getSpecConfParametersFromArray($this->defaultExtras['rte_transform']['parameters']);
        $result = [
            $this->data['tableName'],
            $this->data['databaseRow']['uid'],
            $this->data['fieldName'],
            $this->pidOfVersionedMotherRecord,
            $this->data['recordTypeValue'],
            $this->pidOfPageRecord,
            $parameters['imgpath'],
        ];
        return implode(':', $result);
    }

    /**
     * Clean list
     *
     * @param string $str String to clean
     * @return string Cleaned string
     */
    protected function cleanList($str)
    {
        if (strstr($str, '*')) {
            $str = '*';
        } else {
            $str = implode(',', array_unique(GeneralUtility::trimExplode(',', $str, true)));
        }
        return $str;
    }

    /**
     * Performs transformation of content from database to richtext editor
     *
     * @param string $value Value to transform.
     * @return string Transformed content
     */
    protected function transformDatabaseContentToEditor($value)
    {
        // change <strong> to <b>
        $value = preg_replace('/<(\\/?)strong/i', '<$1b', $value);
        // change <em> to <i>
        $value = preg_replace('/<(\\/?)em([^b>]*>)/i', '<$1i$2', $value);

        if ($this->defaultExtras['rte_transform']) {
            $parameters = BackendUtility::getSpecConfParametersFromArray($this->defaultExtras['rte_transform']['parameters']);
            // There must be a mode set for transformation
            if ($parameters['mode']) {
                /** @var RteHtmlParser $parseHTML */
                $parseHTML = GeneralUtility::makeInstance(RteHtmlParser::class);
                $parseHTML->init($this->data['table'] . ':' . $this->data['fieldName'], $this->pidOfVersionedMotherRecord);
                $parseHTML->setRelPath('');
                $value = $parseHTML->RTE_transform($value, $this->defaultExtras, 'rte', $this->processedRteConfiguration);
            }
        }
        return $value;
    }

    /**
     * True if RTE is in full screen mode / called via wizard controller
     *
     * @return bool
     */
    protected function isInFullScreenMode()
    {
        return GeneralUtility::_GP('M') === 'wizard_rte';
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
