<?php
namespace TYPO3\CMS\Tstemplate\Controller;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class displays the submodule "TypoScript Object Browser" inside the Web > Template module
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptTemplateObjectBrowserModuleFunctionController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'pObj' => 'Using TypoScriptTemplateObjectBrowserModuleFunctionController::$pObj is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'function_key' => 'Using TypoScriptTemplateObjectBrowserModuleFunctionController::$function_key is deprecated, property will be removed in TYPO3 v10.0.',
        'extClassConf' => 'Using TypoScriptTemplateObjectBrowserModuleFunctionController::$extClassConf is deprecated, property will be removed in TYPO3 v10.0.',
        'localLangFile' => 'Using TypoScriptTemplateObjectBrowserModuleFunctionController::$localLangFile is deprecated, property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'initialize_editor' => 'Using TypoScriptTemplateObjectBrowserModuleFunctionController::initialize_editor() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'modMenu' => 'Using TypoScriptTemplateObjectBrowserModuleFunctionController::modMenu() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'handleExternalFunctionValue' => 'Using TypoScriptTemplateObjectBrowserModuleFunctionController::handleExternalFunctionValue() is deprecated, method will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var string
     */
    protected $localLanguageFilePath;

    /**
     * @var TypoScriptTemplateModuleController
     */
    protected $pObj;

    /**
     * The currently selected sys_template record
     * @var array
     */
    protected $templateRow;

    /**
     * @var ExtendedTemplateService
     */
    protected $templateService;

    /**
     * @var int GET/POST var 'id'
     */
    protected $id;

    /**
     * Can be hardcoded to the name of a locallang.xlf file (from the same directory as the class file) to use/load
     * and is included / added to $GLOBALS['LOCAL_LANG']
     *
     * @see init()
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $localLangFile = '';

    /**
     * Contains module configuration parts from TBE_MODULES_EXT if found
     *
     * @see handleExternalFunctionValue()
     * @var array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $extClassConf;

    /**
     * If this value is set it points to a key in the TBE_MODULES_EXT array (not on the top level..) where another classname/filepath/title can be defined for sub-subfunctions.
     * This is a little hard to explain, so see it in action; it used in the extension 'func_wizards' in order to provide yet a layer of interfacing with the backend module.
     * The extension 'func_wizards' has this description: 'Adds the 'Wizards' item to the function menu in Web>Func. This is just a framework for wizard extensions.' - so as you can see it is designed to allow further connectivity - 'level 2'
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $function_key = '';

    /**
     * Init, called from parent object
     *
     * @param TypoScriptTemplateModuleController $pObj
     */
    public function init($pObj)
    {
        $this->pObj = $pObj;
        // Local lang:
        if (!empty($this->localLangFile)) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
            $this->getLanguageService()->includeLLFile($this->localLangFile);
        }
        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
        $this->pObj->modMenu_dontValidateList .= ',ts_browser_toplevel_setup,ts_browser_toplevel_const,ts_browser_TLKeys_setup,ts_browser_TLKeys_const';
        $this->pObj->modMenu_setDefaultList .= ',ts_browser_fixedLgd,ts_browser_showComments';
        $this->localLanguageFilePath = 'EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf';
        $this->id = (int)GeneralUtility::_GP('id');
    }

    /**
     * Mod menu
     *
     * @return array
     */
    protected function modMenu()
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf');
        $modMenu = [
            'ts_browser_type' => [
                'const' => $lang->getLL('constants'),
                'setup' => $lang->getLL('setup')
            ],
            'ts_browser_toplevel_setup' => [
                '0' => mb_strtolower($lang->getLL('all'), 'utf-8')
            ],
            'ts_browser_toplevel_const' => [
                '0' => mb_strtolower($lang->getLL('all'), 'utf-8')
            ],
            'ts_browser_const' => [
                '0' => $lang->getLL('plainSubstitution'),
                'subst' => $lang->getLL('substitutedGreen'),
                'const' => $lang->getLL('unsubstitutedGreen')
            ],
            'ts_browser_regexsearch' => '1',
            'ts_browser_fixedLgd' => '1',
            'ts_browser_showComments' => '1',
            'ts_browser_alphaSort' => '1'
        ];
        foreach (['setup', 'const'] as $bType) {
            $addKey = GeneralUtility::_GET('addKey');
            // If any plus-signs were clicked, it's registered.
            if (is_array($addKey)) {
                reset($addKey);
                if (current($addKey)) {
                    $this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][key($addKey)] = key($addKey);
                } else {
                    unset($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][key($addKey)]);
                }
                $this->getBackendUserAuthentication()->pushModuleData('web_ts', $this->pObj->MOD_SETTINGS);
            }
            if (!empty($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType])) {
                $modMenu['ts_browser_toplevel_' . $bType]['-'] = '---';
                $modMenu['ts_browser_toplevel_' . $bType] = $modMenu['ts_browser_toplevel_' . $bType] + $this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType];
            }
        }
        return $modMenu;
    }

    /**
     * Initialize editor
     *
     * Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
     *
     * @param int $pageId
     * @param int $template_uid
     * @return bool
     */
    protected function initialize_editor($pageId, $template_uid = 0)
    {
        // Defined global here!
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);

        // Gets the rootLine
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId);
        $rootLine = $rootlineUtility->get();
        // This generates the constants/config + hierarchy info for the template.
        $this->templateService->runThroughTemplates($rootLine, $template_uid);

        // Get the row of the first VISIBLE template of the page. whereclause like the frontend.
        $this->templateRow = $this->templateService->ext_getFirstTemplate($pageId, $template_uid);
        return is_array($this->templateRow);
    }

    /**
     * Main, called from parent object
     *
     * @return string
     */
    public function main()
    {
        $lang = $this->getLanguageService();
        $POST = GeneralUtility::_POST();

        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->pObj->templateMenu();
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }
        $bType = $this->pObj->MOD_SETTINGS['ts_browser_type'];
        $existTemplate = $this->initialize_editor($this->id, $template_uid);
        // initialize
        $assigns = [];
        $assigns['LLPrefix'] = 'LLL:' . $this->localLanguageFilePath . ':';
        $assigns['existTemplate'] = $existTemplate;
        $assigns['tsBrowserType'] = $this->pObj->MOD_SETTINGS['ts_browser_type'];
        if ($existTemplate) {
            $assigns['templateRecord'] = $this->templateRow;
            $assigns['linkWrapTemplateTitle'] = $this->pObj->linkWrapTemplateTitle($this->templateRow['title'], ($bType === 'setup' ? 'config' : 'constants'));
            $assigns['manyTemplatesMenu'] = $manyTemplatesMenu;

            if ($POST['add_property'] || $POST['update_value'] || $POST['clear_object']) {
                // add property
                $line = '';
                if (is_array($POST['data'])) {
                    $name = key($POST['data']);
                    if ($POST['data'][$name]['name'] !== '') {
                        // Workaround for this special case: User adds a key and submits by pressing the return key. The form however will use "add_property" which is the name of the first submit button in this form.
                        unset($POST['update_value']);
                        $POST['add_property'] = 'Add';
                    }
                    if ($POST['add_property']) {
                        $property = trim($POST['data'][$name]['name']);
                        if (preg_replace('/[^a-zA-Z0-9_\\.]*/', '', $property) != $property) {
                            $badPropertyMessage = GeneralUtility::makeInstance(FlashMessage::class, $lang->getLL('noSpaces') . $lang->getLL('nothingUpdated'), $lang->getLL('badProperty'), FlashMessage::ERROR);
                            $this->addFlashMessage($badPropertyMessage);
                        } else {
                            $pline = $name . '.' . $property . ' = ' . trim($POST['data'][$name]['propertyValue']);
                            $propertyAddedMessage = GeneralUtility::makeInstance(FlashMessage::class, $pline, $lang->getLL('propertyAdded'));
                            $this->addFlashMessage($propertyAddedMessage);
                            $line .= LF . $pline;
                        }
                    } elseif ($POST['update_value']) {
                        $pline = $name . ' = ' . trim($POST['data'][$name]['value']);
                        $updatedMessage = GeneralUtility::makeInstance(FlashMessage::class, $pline, $lang->getLL('valueUpdated'));
                        $this->addFlashMessage($updatedMessage);
                        $line .= LF . $pline;
                    } elseif ($POST['clear_object']) {
                        if ($POST['data'][$name]['clearValue']) {
                            $pline = $name . ' >';
                            $objectClearedMessage = GeneralUtility::makeInstance(FlashMessage::class, $pline, $lang->getLL('objectCleared'));
                            $this->addFlashMessage($objectClearedMessage);
                            $line .= LF . $pline;
                        }
                    }
                }
                if ($line) {
                    $saveId = $this->templateRow['_ORIG_uid'] ?: $this->templateRow['uid'];
                    // Set the data to be saved
                    $recData = [];
                    $field = $bType === 'setup' ? 'config' : 'constants';
                    $recData['sys_template'][$saveId][$field] = $this->templateRow[$field] . $line;
                    // Create new  tce-object
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    // Initialize
                    $tce->start($recData, []);
                    // Saved the stuff
                    $tce->process_datamap();
                    // Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
                    $tce->clear_cacheCmd('all');
                    // re-read the template ...
                    $this->initialize_editor($this->id, $template_uid);
                }
            }
        }
        $tsbr = GeneralUtility::_GET('tsbr');
        $update = 0;
        if (is_array($tsbr)) {
            // If any plus-signs were clicked, it's registred.
            $this->pObj->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType] = $this->templateService->ext_depthKeys($tsbr, $this->pObj->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType]);
            $update = 1;
        }
        if ($POST['Submit']) {
            // If any POST-vars are send, update the condition array
            $this->pObj->MOD_SETTINGS['tsbrowser_conditions'] = $POST['conditions'];
            $update = 1;
        }
        if ($update) {
            $this->getBackendUserAuthentication()->pushModuleData('web_ts', $this->pObj->MOD_SETTINGS);
        }
        $this->templateService->matchAlternative = $this->pObj->MOD_SETTINGS['tsbrowser_conditions'];
        $this->templateService->matchAlternative[] = 'dummydummydummydummydummydummydummydummydummydummydummy';
        // This is just here to make sure that at least one element is in the array so that the tsparser actually uses this array to match.
        $this->templateService->constantMode = $this->pObj->MOD_SETTINGS['ts_browser_const'];
        // "sObj" is set by ExtendedTemplateService to edit single keys
        $sObj = GeneralUtility::_GP('sObj');
        if (!empty($sObj) && $this->templateService->constantMode) {
            $this->templateService->constantMode = 'untouched';
        }
        $this->templateService->regexMode = $this->pObj->MOD_SETTINGS['ts_browser_regexsearch'];
        $this->templateService->fixedLgd = $this->pObj->MOD_SETTINGS['ts_browser_fixedLgd'];
        $this->templateService->linkObjects = true;
        $this->templateService->ext_regLinenumbers = true;
        $this->templateService->ext_regComments = $this->pObj->MOD_SETTINGS['ts_browser_showComments'];
        $this->templateService->bType = $bType;
        if ($this->pObj->MOD_SETTINGS['ts_browser_type'] === 'const') {
            $this->templateService->ext_constants_BRP = (int)GeneralUtility::_GP('breakPointLN');
        } else {
            $this->templateService->ext_config_BRP = (int)GeneralUtility::_GP('breakPointLN');
        }
        $this->templateService->generateConfig();
        if ($bType === 'setup') {
            $theSetup = $this->templateService->setup;
        } else {
            $theSetup = $this->templateService->setup_constants;
        }
        // EDIT A VALUE:
        $assigns['typoScriptPath'] = $sObj;
        if (!empty($sObj)) {
            list($theSetup, $theSetupValue) = $this->templateService->ext_getSetup($theSetup, $sObj);
            $assigns['theSetupValue'] = $theSetupValue;
            if ($existTemplate === false) {
                $noTemplateMessage = GeneralUtility::makeInstance(FlashMessage::class, $lang->getLL('noCurrentTemplate'), $lang->getLL('edit'), FlashMessage::ERROR);
                $this->addFlashMessage($noTemplateMessage);
            }
            // Links:
            $urlParameters = [
                'id' => $this->id
            ];
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            $aHref = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
            $assigns['moduleUrl'] = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
            $assigns['isNotInTopLevelKeyList'] = !isset($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][$sObj]);
            $assigns['hasProperties'] = !empty($theSetup);
            if (!$this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][$sObj]) {
                if (!empty($theSetup)) {
                    $assigns['moduleUrlObjectListAction'] = $aHref . '&addKey[' . rawurlencode($sObj) . ']=1&SET[ts_browser_toplevel_' . $bType . ']=' . rawurlencode($sObj);
                }
            } else {
                $assigns['moduleUrlObjectListAction'] = $aHref . '&addKey[' . rawurlencode($sObj) . ']=0&SET[ts_browser_toplevel_' . $bType . ']=0';
            }
        } else {
            $this->templateService->tsbrowser_depthKeys = $this->pObj->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType];
            if (GeneralUtility::_POST('search') && GeneralUtility::_POST('search_field')) {
                // If any POST-vars are send, update the condition array
                $searchString = GeneralUtility::_POST('search_field');
                try {
                    $this->templateService->tsbrowser_depthKeys =
                        $this->templateService->ext_getSearchKeys(
                            $theSetup,
                            '',
                            $searchString,
                            []
                        );
                } catch (Exception $e) {
                    $this->addFlashMessage(
                        GeneralUtility::makeInstance(FlashMessage::class, sprintf($lang->getLL('error.' . $e->getCode()), $searchString), '', FlashMessage::ERROR)
                    );
                }
            }
            $assigns['hasTsBrowserTypes'] = is_array($this->pObj->MOD_MENU['ts_browser_type']) && count($this->pObj->MOD_MENU['ts_browser_type']) > 1;
            if (is_array($this->pObj->MOD_MENU['ts_browser_type']) && count($this->pObj->MOD_MENU['ts_browser_type']) > 1) {
                $assigns['browserTypeDropdownMenu'] = BackendUtility::getDropdownMenu($this->id, 'SET[ts_browser_type]', $bType, $this->pObj->MOD_MENU['ts_browser_type']);
            }
            $assigns['hasTopLevelInObjectList'] = is_array($this->pObj->MOD_MENU['ts_browser_toplevel_' . $bType]) && count($this->pObj->MOD_MENU['ts_browser_toplevel_' . $bType]) > 1;
            if (is_array($this->pObj->MOD_MENU['ts_browser_toplevel_' . $bType]) && count($this->pObj->MOD_MENU['ts_browser_toplevel_' . $bType]) > 1) {
                $assigns['objectListDropdownMenu'] = BackendUtility::getDropdownMenu($this->id, 'SET[ts_browser_toplevel_' . $bType . ']', $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType], $this->pObj->MOD_MENU['ts_browser_toplevel_' . $bType]);
            }

            $assigns['regexSearchCheckbox'] = BackendUtility::getFuncCheck($this->id, 'SET[ts_browser_regexsearch]', $this->pObj->MOD_SETTINGS['ts_browser_regexsearch'], '', '', 'id="checkTs_browser_regexsearch"');
            $assigns['postSearchField'] = $POST['search_field'];
            $theKey = $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType];
            if (!$theKey || !str_replace('-', '', $theKey)) {
                $theKey = '';
            }
            list($theSetup, $theSetupValue) = $this->templateService->ext_getSetup($theSetup, $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType] ? $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType] : '');
            $tree = $this->templateService->ext_getObjTree($theSetup, $theKey, '', '', $theSetupValue, $this->pObj->MOD_SETTINGS['ts_browser_alphaSort']);
            $tree = $this->templateService->substituteCMarkers($tree);
            $urlParameters = [
                'id' => $this->id
            ];
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
            $aHref = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
            // Parser Errors:
            $pEkey = $bType === 'setup' ? 'config' : 'constants';
            $assigns['hasParseErrors'] = !empty($this->templateService->parserErrors[$pEkey]);
            if (!empty($this->templateService->parserErrors[$pEkey])) {
                $assigns['showErrorDetailsUri'] = $aHref . '&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TemplateAnalyzerModuleFunctionController&template=all&SET[ts_analyzer_checkLinenum]=1#line-';
                $assigns['parseErrors'] = $this->templateService->parserErrors[$pEkey];
            }

            if (isset($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][$theKey])) {
                $assigns['moduleUrlRemoveFromObjectList'] = $aHref . '&addKey[' . $theKey . ']=0&SET[ts_browser_toplevel_' . $bType . ']=0';
            }

            $assigns['hasKeySelected'] = $theKey !== '';

            if ($theKey) {
                $assigns['treeLabel'] = $theKey;
            } else {
                $assigns['rootLLKey'] = $bType === 'setup' ? 'setupRoot' : 'constantRoot';
            }
            $assigns['tsTree'] = $tree;

            // second row options
            $assigns['isSetupAndCropLinesDisabled'] = $bType === 'setup' && !$this->pObj->MOD_SETTINGS['ts_browser_fixedLgd'];
            $assigns['checkBoxShowComments'] = BackendUtility::getFuncCheck($this->id, 'SET[ts_browser_showComments]', $this->pObj->MOD_SETTINGS['ts_browser_showComments'], '', '', 'id="checkTs_browser_showComments"');
            $assigns['checkBoxAlphaSort'] = BackendUtility::getFuncCheck($this->id, 'SET[ts_browser_alphaSort]', $this->pObj->MOD_SETTINGS['ts_browser_alphaSort'], '', '', 'id="checkTs_browser_alphaSort"');
            $assigns['checkBoxCropLines'] = BackendUtility::getFuncCheck($this->id, 'SET[ts_browser_fixedLgd]', $this->pObj->MOD_SETTINGS['ts_browser_fixedLgd'], '', '', 'id="checkTs_browser_fixedLgd"');
            if ($bType === 'setup' && !$this->pObj->MOD_SETTINGS['ts_browser_fixedLgd']) {
                $assigns['dropdownDisplayConstants'] = BackendUtility::getDropdownMenu($this->id, 'SET[ts_browser_const]', $this->pObj->MOD_SETTINGS['ts_browser_const'], $this->pObj->MOD_MENU['ts_browser_const']);
            }

            // Conditions:
            $assigns['hasConditions'] = is_array($this->templateService->sections) && !empty($this->templateService->sections);
            if (is_array($this->templateService->sections) && !empty($this->templateService->sections)) {
                $tsConditions = [];
                foreach ($this->templateService->sections as $key => $val) {
                    $tsConditions[] = [
                        'key' => $key,
                        'value' => $val,
                        'label' => $this->templateService->substituteCMarkers(htmlspecialchars($val)),
                        'isSet' => $this->pObj->MOD_SETTINGS['tsbrowser_conditions'][$key] ? true : false
                    ];
                }
                $assigns['tsConditions'] = $tsConditions;
            }
            // Ending section displayoptions
        }
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:tstemplate/Resources/Private/Templates/TemplateObjectBrowserModuleFunction.html'
        ));
        $view->assignMultiple($assigns);

        return $view->render();
    }

    /**
     * Add flash message to queue
     *
     * @param FlashMessage $flashMessage
     */
    protected function addFlashMessage(FlashMessage $flashMessage)
    {
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * If $this->function_key is set (which means there are two levels of object connectivity) then
     * $this->extClassConf is loaded with the TBE_MODULES_EXT configuration for that sub-sub-module
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function handleExternalFunctionValue()
    {
        // Must clean first to make sure the correct key is set...
        $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), 'web_ts');
        if ($this->function_key) {
            $this->extClassConf = $this->pObj->getExternalItemConfig('web_ts', $this->function_key, $this->pObj->MOD_SETTINGS[$this->function_key]);
        }
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
