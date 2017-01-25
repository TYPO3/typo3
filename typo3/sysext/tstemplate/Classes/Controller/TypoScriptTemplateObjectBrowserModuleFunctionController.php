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

use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * This class displays the submodule "TypoScript Object Browser" inside the Web > Template module
 */
class TypoScriptTemplateObjectBrowserModuleFunctionController extends AbstractFunctionModule
{
    /**
     * @var TypoScriptTemplateModuleController
     */
    public $pObj;

    /**
     * Init
     *
     * @param TypoScriptTemplateModuleController $pObj
     * @param array $conf
     * @return void
     */
    public function init(&$pObj, $conf)
    {
        parent::init($pObj, $conf);
        $this->pObj->modMenu_dontValidateList .= ',ts_browser_toplevel_setup,ts_browser_toplevel_const,ts_browser_TLKeys_setup,ts_browser_TLKeys_const';
        $this->pObj->modMenu_setDefaultList .= ',ts_browser_fixedLgd,ts_browser_showComments';
    }

    /**
     * Mod menu
     *
     * @return array
     */
    public function modMenu()
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf');
        $modMenu = [
            'ts_browser_type' => [
                'const' => $lang->getLL('constants'),
                'setup' => $lang->getLL('setup')
            ],
            'ts_browser_toplevel_setup' => [
                '0' => $lang->csConvObj->conv_case($lang->charSet, $lang->getLL('all'), 'toUpper')
            ],
            'ts_browser_toplevel_const' => [
                '0' => $lang->csConvObj->conv_case($lang->charSet, $lang->getLL('all'), 'toUpper')
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
                $this->getBackendUserAuthentication()->pushModuleData($this->pObj->MCONF['name'], $this->pObj->MOD_SETTINGS);
            }
            if (!empty($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType])) {
                $modMenu['ts_browser_toplevel_' . $bType]['-'] = '---';
                $modMenu['ts_browser_toplevel_' . $bType] = $modMenu['ts_browser_toplevel_' . $bType] + $this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType];
            }
        }
        return $modMenu;
    }

    /**
     * Verify TS objects
     *
     * @param array $propertyArray
     * @param string $parentType
     * @param string $parentValue
     * @return array|NULL
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function verify_TSobjects($propertyArray, $parentType, $parentValue)
    {
        GeneralUtility::logDeprecatedFunction();
        $TSobjTable = [
            'PAGE' => [
                'prop' => [
                    'typeNum' => 'int',
                    '1,2,3' => 'COBJ',
                    'bodyTag' => 'string'
                ]
            ],
            'TEXT' => [
                'prop' => [
                    'value' => 'string'
                ]
            ],
            'HTML' => [
                'prop' => [
                    'value' => 'stdWrap'
                ]
            ],
            'stdWrap' => [
                'prop' => [
                    'field' => 'string',
                    'current' => 'boolean'
                ]
            ]
        ];
        $TSobjDataTypes = [
            'COBJ' => 'TEXT,CONTENT',
            'PAGE' => 'PAGE',
            'stdWrap' => ''
        ];
        if ($parentType) {
            if (isset($TSobjDataTypes[$parentType]) && (!$TSobjDataTypes[$parentType] || GeneralUtility::inlist($TSobjDataTypes[$parentType], $parentValue))) {
                $ObjectKind = $parentValue;
            } else {
                // Object kind is "" if it should be known.
                $ObjectKind = '';
            }
        } else {
            // If parentType is not given, then it can be anything. Free.
            $ObjectKind = $parentValue;
        }
        if ($ObjectKind && is_array($TSobjTable[$ObjectKind])) {
            $result = [];
            if (is_array($propertyArray)) {
                foreach ($propertyArray as $key => $val) {
                    if (MathUtility::canBeInterpretedAsInteger($key)) {
                        // If num-arrays
                        $result[$key] = $TSobjTable[$ObjectKind]['prop']['1,2,3'];
                    } else {
                        // standard
                        $result[$key] = $TSobjTable[$ObjectKind]['prop'][$key];
                    }
                }
            }
            return $result;
        }
        return null;
    }

    /**
     * Initialize editor
     *
     * Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
     *
     * @param int $pageId
     * @param int $template_uid
     * @return int
     */
    public function initialize_editor($pageId, $template_uid = 0)
    {
        // Defined global here!
        $templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $GLOBALS['tmpl'] = $templateService;

        // Do not log time-performance information
        $templateService->tt_track = false;
        $templateService->init();

        // Gets the rootLine
        $sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $rootLine = $sys_page->getRootLine($pageId);
        // This generates the constants/config + hierarchy info for the template.
        $templateService->runThroughTemplates($rootLine, $template_uid);

        // Get the row of the first VISIBLE template of the page. whereclause like the frontend.
        $GLOBALS['tplRow'] = $templateService->ext_getFirstTemplate($pageId, $template_uid);
        return is_array($GLOBALS['tplRow']);
    }

    /**
     * Main
     *
     * @return string
     */
    public function main()
    {
        $lang = $this->getLanguageService();
        $POST = GeneralUtility::_POST();
        $documentTemplate = $this->getDocumentTemplate();

        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->pObj->templateMenu();
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }
        $bType = $this->pObj->MOD_SETTINGS['ts_browser_type'];
        $existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);
        $tplRow = $this->getTemplateRow();
        // initialize
        $theOutput = '';
        if ($existTemplate) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $content = ' ' . $iconFactory->getIconForRecord('sys_template', $tplRow, Icon::SIZE_SMALL)->render() . ' <strong>'
                . $this->pObj->linkWrapTemplateTitle($tplRow['title'], ($bType == 'setup' ? 'config' : 'constants')) . '</strong>'
                . (trim($tplRow['sitetitle']) ? htmlspecialchars(' (' . $tplRow['sitetitle'] . ')') : '');
            $theOutput .= '<h3>' . $lang->getLL('currentTemplate', true) . '</h3>';
            $theOutput .= '<div>';
            $theOutput .= $content;
            $theOutput .= '</div>';

            if ($manyTemplatesMenu) {
                $theOutput .= $manyTemplatesMenu;
            }
            $theOutput .= '<div style="padding-top: 10px;"></div>';
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
                    $saveId = $tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid'];
                    // Set the data to be saved
                    $recData = [];
                    $field = $bType == 'setup' ? 'config' : 'constants';
                    $recData['sys_template'][$saveId][$field] = $tplRow[$field] . $line;
                    // Create new  tce-object
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->stripslashes_values = false;
                    // Initialize
                    $tce->start($recData, []);
                    // Saved the stuff
                    $tce->process_datamap();
                    // Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
                    $tce->clear_cacheCmd('all');
                    // re-read the template ...
                    $this->initialize_editor($this->pObj->id, $template_uid);
                }
            }
        }
        $tsbr = GeneralUtility::_GET('tsbr');
        $templateService = $this->getExtendedTemplateService();
        $update = 0;
        if (is_array($tsbr)) {
            // If any plus-signs were clicked, it's registred.
            $this->pObj->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType] = $templateService->ext_depthKeys($tsbr, $this->pObj->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType]);
            $update = 1;
        }
        if ($POST['Submit']) {
            // If any POST-vars are send, update the condition array
            $this->pObj->MOD_SETTINGS['tsbrowser_conditions'] = $POST['conditions'];
            $update = 1;
        }
        if ($update) {
            $this->getBackendUserAuthentication()->pushModuleData($this->pObj->MCONF['name'], $this->pObj->MOD_SETTINGS);
        }
        $templateService->matchAlternative = $this->pObj->MOD_SETTINGS['tsbrowser_conditions'];
        $templateService->matchAlternative[] = 'dummydummydummydummydummydummydummydummydummydummydummy';
        // This is just here to make sure that at least one element is in the array so that the tsparser actually uses this array to match.
        $templateService->constantMode = $this->pObj->MOD_SETTINGS['ts_browser_const'];
        if ($this->pObj->sObj && $templateService->constantMode) {
            $templateService->constantMode = 'untouched';
        }
        $templateService->regexMode = $this->pObj->MOD_SETTINGS['ts_browser_regexsearch'];
        $templateService->fixedLgd = $this->pObj->MOD_SETTINGS['ts_browser_fixedLgd'];
        $templateService->linkObjects = true;
        $templateService->ext_regLinenumbers = true;
        $templateService->ext_regComments = $this->pObj->MOD_SETTINGS['ts_browser_showComments'];
        $templateService->bType = $bType;
        if ($this->pObj->MOD_SETTINGS['ts_browser_type'] == 'const') {
            $templateService->ext_constants_BRP = (int)GeneralUtility::_GP('breakPointLN');
        } else {
            $templateService->ext_config_BRP = (int)GeneralUtility::_GP('breakPointLN');
        }
        $templateService->generateConfig();
        if ($bType == 'setup') {
            $theSetup = $templateService->setup;
        } else {
            $theSetup = $templateService->setup_constants;
        }
        // EDIT A VALUE:
        if ($this->pObj->sObj) {
            list($theSetup, $theSetupValue) = $templateService->ext_getSetup($theSetup, $this->pObj->sObj ? $this->pObj->sObj : '');
            if ($existTemplate) {
                // Inline Form Area Begin
                $theOutput .= '<div class="form-inline form-inline-spaced">';
                // Value
                $out = '';
                $out .= '<div class="form-group">';
                $out .= '	<label>' . htmlspecialchars($this->pObj->sObj) . ' =' . '</label>';
                $out .= '	<input class="form-control" type="text" name="data[' . htmlspecialchars($this->pObj->sObj) . '][value]" value="' . htmlspecialchars($theSetupValue) . '"' . $documentTemplate->formWidth(40) . ' />';
                $out .= '	<input class="btn btn-default" type="submit" name="update_value" value="' . $lang->getLL('updateButton') . '" />';
                $out .= '</div>';
                $theOutput .= '<h3>' . $lang->getLL('editProperty', true) . '</h3>';
                $theOutput .= $out;
                // Property
                $out = '<div class="form-group">';
                $out .= '	<label>' . htmlspecialchars($this->pObj->sObj) . '.';
                $out .= '		<input class="form-control" type="text" name="data[' . htmlspecialchars($this->pObj->sObj) . '][name]"' . $documentTemplate->formWidth(20) . ' /> = ';
                $out .= '	</label>';
                $out .= '	<input class="form-control" type="text" name="data[' . htmlspecialchars($this->pObj->sObj) . '][propertyValue]"' . $documentTemplate->formWidth(40) . ' />';
                $out .= '	<input class="btn btn-default" type="submit" name="add_property" value="' . $lang->getLL('addButton') . '" />';
                $out .= '</div>';
                $theOutput .= '<div style="padding-top: 20px;"></div>';
                $theOutput .= '<h3>' . $lang->getLL('addProperty', true) . '</h3>';
                $theOutput .= $out;
                // clear
                $out = '<div class="form-group">';
                $out .= '	<div class="checkbox">';
                $out .= '		<label>';
                $out .= '			' . htmlspecialchars($this->pObj->sObj) . ' ' . $lang->csConvObj->conv_case($lang->charSet, $lang->getLL('clear'), 'toUpper');
                $out .= '			<input type="checkbox" name="data[' . htmlspecialchars($this->pObj->sObj) . '][clearValue]" value="1" />';
                $out .= '		</label>';
                $out .= '		<input class="btn btn-default" type="submit" name="clear_object" value="' . $lang->getLL('clearButton') . '" />';
                $out .= '	</div>';
                $out .= '</div>';
                $theOutput .='<div style="padding-top: 20px;"></div>';
                $theOutput .= '<h3>' . $lang->getLL('clearObject', true) . '</h3>';
                $theOutput .= $out;
                $theOutput .= '<div style="padding-top: 10px;"></div>';
                // Inline Form Area End
                $theOutput .= '</div>';
            } else {
                $noTemplateMessage = GeneralUtility::makeInstance(FlashMessage::class, $lang->getLL('noCurrentTemplate'), $lang->getLL('edit'), FlashMessage::ERROR);
                $this->addFlashMessage($noTemplateMessage);
                $theOutput .= htmlspecialchars($this->pObj->sObj) . ' = <strong>' . htmlspecialchars($theSetupValue) . '</strong>';
                $theOutput .= '<div style="padding-top: 10px;"></div>';
            }
            // Links:
            $out = '';
            $urlParameters = [
                'id' => $this->pObj->id
            ];
            $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
            if (!$this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][$this->pObj->sObj]) {
                if (!empty($theSetup)) {
                    $out = '<a href="' . htmlspecialchars(($aHref . '&addKey[' . rawurlencode($this->pObj->sObj) . ']=1&SET[ts_browser_toplevel_' . $bType . ']=' . rawurlencode($this->pObj->sObj))) . '">';
                    $out .= sprintf($lang->getLL('addKey'), htmlspecialchars($this->pObj->sObj));
                }
            } else {
                $out = '<a href="' . htmlspecialchars(($aHref . '&addKey[' . rawurlencode($this->pObj->sObj) . ']=0&SET[ts_browser_toplevel_' . $bType . ']=0')) . '">';
                $out .= sprintf($lang->getLL('removeKey'), htmlspecialchars($this->pObj->sObj));
            }
            if ($out) {
                $theOutput .= '<div><hr style="margin-top: 5px; margin-bottom: 5px;" />' . $out . '</div>';
            }
            // back
            $out = $lang->getLL('back');
            $out = '<a href="' . htmlspecialchars($aHref) . '" class="btn btn-default"><strong><i class="fa fa-chevron-left"></i>&nbsp;' . $out . '</strong></a>';
            $theOutput .= '<div><hr style="margin-top: 5px; margin-bottom: 5px;" />' . $out . '</div>';
        } else {
            $templateService->tsbrowser_depthKeys = $this->pObj->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType];
            if (GeneralUtility::_POST('search') && GeneralUtility::_POST('search_field')) {
                // If any POST-vars are send, update the condition array
                $searchString = GeneralUtility::_POST('search_field');
                try {
                    $templateService->tsbrowser_depthKeys =
                        $templateService->ext_getSearchKeys(
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
            $theOutput .= '
				<div class="tsob-menu">
					<div class="form-inline">';
            if (is_array($this->pObj->MOD_MENU['ts_browser_type']) && count($this->pObj->MOD_MENU['ts_browser_type']) > 1) {
                $theOutput .= '
						<div class="form-group">
							<label class="control-label">' . $lang->getLL('browse') . '</label>'
                            . BackendUtility::getDropdownMenu($this->pObj->id, 'SET[ts_browser_type]', $bType, $this->pObj->MOD_MENU['ts_browser_type']) . '
						</div>';
            }
            if (is_array($this->pObj->MOD_MENU['ts_browser_toplevel_' . $bType]) && count($this->pObj->MOD_MENU['ts_browser_toplevel_' . $bType]) > 1) {
                $theOutput .= '
						<div class="form-group">
							<label class="control-label" for="ts_browser_toplevel_' . $bType . '">' . $lang->getLL('objectList') . '</label> '
                            . BackendUtility::getDropdownMenu($this->pObj->id, 'SET[ts_browser_toplevel_' . $bType . ']', $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType], $this->pObj->MOD_MENU['ts_browser_toplevel_' . $bType]) . '
						</div>';
            }

            $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Tstemplate/TypoScriptObjectBrowser');

            $theOutput .= '
						<div class="form-group">
							<label class="control-label" for="search_field">' . $lang->getLL('search') . '</label>
							<div class="form-group"><input class="form-control" type="search" name="search_field" id="search_field" value="' . htmlspecialchars($POST['search_field']) . '" /></div>
						</div>
						<input class="btn btn-default tsob-search-submit" type="submit" name="search" value="' . $lang->sL('LLL:EXT:lang/locallang_common.xlf:search') . '" />
					</div>
					<div class="checkbox">
						<label for="checkTs_browser_regexsearch">
							' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_browser_regexsearch]', $this->pObj->MOD_SETTINGS['ts_browser_regexsearch'], '', '', 'id="checkTs_browser_regexsearch"') . $lang->getLL('regExp') . '
						</label>
					</div>
				</div>';
            $theKey = $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType];
            if (!$theKey || !str_replace('-', '', $theKey)) {
                $theKey = '';
            }
            list($theSetup, $theSetupValue) = $templateService->ext_getSetup($theSetup, $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType] ? $this->pObj->MOD_SETTINGS['ts_browser_toplevel_' . $bType] : '');
            $tree = $templateService->ext_getObjTree($theSetup, $theKey, '', '', $theSetupValue, $this->pObj->MOD_SETTINGS['ts_browser_alphaSort']);
            $tree = $templateService->substituteCMarkers($tree);
            $urlParameters = [
                'id' => $this->pObj->id
            ];
            $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);
            // Parser Errors:
            $pEkey = $bType == 'setup' ? 'config' : 'constants';
            if (!empty($templateService->parserErrors[$pEkey])) {
                $errMsg = [];
                foreach ($templateService->parserErrors[$pEkey] as $inf) {
                    $errorLink = ' <a href="' . htmlspecialchars(($aHref . '&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TemplateAnalyzerModuleFunctionController&template=all&SET[ts_analyzer_checkLinenum]=1#line-' . $inf[2])) . '" class="text-warning">' . $lang->getLL('errorShowDetails') . '</a>';
                    $errMsg[] = $lang->getLL('severity.' . $inf[1]) . ':&nbsp;' . $inf[0] . $errorLink;
                }
                $theOutput .= '<div style="padding-top: 10px;"></div>';

                $title = $lang->getLL('errorsWarnings');
                $message = '<p>' . implode($errMsg, '<br />') . '</p>';
                $view = GeneralUtility::makeInstance(StandaloneView::class);
                $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:tstemplate/Resources/Private/Templates/InfoBox.html'));
                $view->assignMultiple([
                    'title' => $title,
                    'message' => $message,
                    'state' => InfoboxViewHelper::STATE_WARNING
                ]);
                $theOutput .= $view->render();
            }

            if (isset($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][$theKey])) {
                $remove = '<a href="' . htmlspecialchars(($aHref . '&addKey[' . $theKey . ']=0&SET[ts_browser_toplevel_' . $bType . ']=0')) . '">' . $lang->getLL('removeKey') . '</a>';
            } else {
                $remove = '';
            }

            $label = $theKey ? $theKey : ($bType == 'setup' ? $lang->csConvObj->conv_case($lang->charSet, $lang->getLL('setupRoot'), 'toUpper') : $lang->csConvObj->conv_case($lang->charSet, $lang->getLL('constantRoot'), 'toUpper'));

            $theOutput .= '<div class="panel panel-space panel-default">';
            $theOutput .= '<div class="panel-heading">';
            $theOutput .= '<strong>' . $label . ' ' . $remove . '</strong>';
            $theOutput .= '</div>';
            $theOutput .= '<div class="panel-body">' . $tree . '</div>';
            $theOutput .= '</div>';

            // second row options
            $menu = '<div class="typo3-listOptions">';
            $menu .= '<div class="checkbox"><label for="checkTs_browser_showComments">' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_browser_showComments]', $this->pObj->MOD_SETTINGS['ts_browser_showComments'], '', '', 'id="checkTs_browser_showComments"');
            $menu .= $lang->getLL('displayComments') . '</label></div>';
            $menu .= '<div class="checkbox"><label for="checkTs_browser_alphaSort">' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_browser_alphaSort]', $this->pObj->MOD_SETTINGS['ts_browser_alphaSort'], '', '', 'id="checkTs_browser_alphaSort"');
            $menu .= $lang->getLL('sortAlphabetically') . '</label></div>';
            $menu .= '<div class="checkbox"><label for="checkTs_browser_fixedLgd">' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_browser_fixedLgd]', $this->pObj->MOD_SETTINGS['ts_browser_fixedLgd'], '', '', 'id="checkTs_browser_fixedLgd"');
            $menu .= $lang->getLL('cropLines') . '</label></div>';
            if ($bType == 'setup' && !$this->pObj->MOD_SETTINGS['ts_browser_fixedLgd']) {
                $menu .= '<div class="form"><label>' . $lang->getLL('displayConstants') . '</label>';
                $menu .= BackendUtility::getDropdownMenu($this->pObj->id, 'SET[ts_browser_const]', $this->pObj->MOD_SETTINGS['ts_browser_const'], $this->pObj->MOD_MENU['ts_browser_const']);
                $menu .= '</div>';
            }
            $menu .= '</div>';

            //start section displayoptions
            $theOutput .= '<div>';
            $theOutput .= '<h2>' . $lang->getLL('displayOptions', true) . '</h2>';
            $theOutput .= $menu;
            // Conditions:
            if (is_array($templateService->sections) && !empty($templateService->sections)) {
                $theOutput .= '<h2>' . $lang->getLL('conditions', true) . '</h2>';
                $out = '';
                foreach ($templateService->sections as $key => $val) {
                    $out .= '<div class="checkbox"><label for="check' . $key . '">';
                    $out .= '<input class="checkbox" type="checkbox" name="conditions[' . $key . ']" id="check' . $key . '" value="' . htmlspecialchars($val) . '"' . ($this->pObj->MOD_SETTINGS['tsbrowser_conditions'][$key] ? ' checked' : '') . ' />' . $templateService->substituteCMarkers(htmlspecialchars($val));
                    $out .= '</label></div>';
                }
                $theOutput .=  '<div class="typo3-listOptions">' . $out . '</div><input class="btn btn-default" type="submit" name="Submit" value="' . $lang->getLL('setConditions') . '" />';
            }
            // Ending section displayoptions
            $theOutput .= '</div>';
        }
        return $theOutput;
    }

    /**
     * Add flash message to queue
     *
     * @param FlashMessage $flashMessage
     * @return void
     */
    protected function addFlashMessage(FlashMessage $flashMessage)
    {
        /** @var $flashMessageService FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * @return ExtendedTemplateService
     */
    protected function getExtendedTemplateService()
    {
        return $GLOBALS['tmpl'];
    }

    /**
     * @return array
     */
    protected function getTemplateRow()
    {
        return $GLOBALS['tplRow'];
    }
}
