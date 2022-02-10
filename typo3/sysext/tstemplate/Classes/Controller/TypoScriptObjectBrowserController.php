<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Tstemplate\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * This class displays the submodule "TypoScript Object Browser" inside the Web > Template module
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptObjectBrowserController extends TypoScriptTemplateModuleController
{
    /**
     * The currently selected sys_template record
     * @var array|false|null
     */
    protected $templateRow;

    /**
     * Init, called from parent object
     *
     * @param TypoScriptTemplateModuleController $pObj
     * @param ServerRequestInterface $request
     */
    public function init($pObj, ServerRequestInterface $request)
    {
        parent::init($pObj, $request);
        // Setting MOD_MENU items as we need them for logging:
        $this->MOD_MENU = array_merge($this->MOD_MENU, $this->modMenu());
        $this->modMenu_dontValidateList .= ',ts_browser_toplevel_setup,ts_browser_toplevel_const,ts_browser_TLKeys_setup,ts_browser_TLKeys_const';
        $this->modMenu_setDefaultList .= ',ts_browser_showComments';
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
                'setup' => $lang->getLL('setup'),
            ],
            'ts_browser_toplevel_setup' => [
                '0' => mb_strtolower($lang->getLL('all'), 'utf-8'),
            ],
            'ts_browser_toplevel_const' => [
                '0' => mb_strtolower($lang->getLL('all'), 'utf-8'),
            ],
            'ts_browser_const' => [
                '0' => $lang->getLL('plainSubstitution'),
                'subst' => $lang->getLL('substitutedGreen'),
                'const' => $lang->getLL('unsubstitutedGreen'),
            ],
            'ts_browser_regexsearch' => '1',
            'ts_browser_showComments' => '1',
            'ts_browser_alphaSort' => '1',
        ];
        foreach (['setup', 'const'] as $bType) {
            $addKey = $this->request->getQueryParams()['addKey'] ?? null;
            // If any plus-signs were clicked, it's registered.
            if (is_array($addKey)) {
                reset($addKey);
                if (current($addKey)) {
                    $this->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][key($addKey)] = key($addKey);
                } else {
                    unset($this->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][key($addKey)]);
                }
                $this->getBackendUser()->pushModuleData('web_ts', $this->MOD_SETTINGS);
            }
            if (!empty($this->MOD_SETTINGS['ts_browser_TLKeys_' . $bType])) {
                $modMenu['ts_browser_toplevel_' . $bType]['-'] = '---';
                $modMenu['ts_browser_toplevel_' . $bType] = $modMenu['ts_browser_toplevel_' . $bType] + $this->MOD_SETTINGS['ts_browser_TLKeys_' . $bType];
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

        $this->templateRow = $this->getFirstTemplateRecordOnPage((int)$pageId, $template_uid);
        $hasFirstTemplate = is_array($this->templateRow);
        // No explicitly selected template on this page was found, so we behave like the Frontend (e.g. when a template is hidden but on the page above)
        if (!$hasFirstTemplate) {
            // Re-initiatlize the templateService but do not include hidden templates
            $context = clone GeneralUtility::makeInstance(Context::class);
            $context->setAspect('visibility', GeneralUtility::makeInstance(VisibilityAspect::class));
            $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class, $context);
        }
        // Gets the rootLine
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId);
        $rootLine = $rootlineUtility->get();
        // This generates the constants/config + hierarchy info for the template.
        $this->templateService->runThroughTemplates($rootLine, $template_uid);
        return $hasFirstTemplate;
    }

    /**
     * Main, called from parent object
     *
     * @return string
     */
    public function main()
    {
        $lang = $this->getLanguageService();
        $POST = $this->request->getParsedBody();
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->templateMenu($this->request);
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->MOD_SETTINGS['templatesOnPage'];
        }
        $bType = $this->MOD_SETTINGS['ts_browser_type'];
        $existTemplate = $this->initialize_editor($this->id, $template_uid);
        // initialize
        $assigns = [];
        $assigns['existTemplate'] = $existTemplate;
        $assigns['tsBrowserType'] = $this->MOD_SETTINGS['ts_browser_type'];
        if ($existTemplate) {
            $assigns['templateRecord'] = $this->templateRow;
            $assigns['linkWrapTemplateTitle'] = $this->linkWrapTemplateTitle($this->templateRow['title'], ($bType === 'setup' ? 'config' : 'constants'));
            $assigns['manyTemplatesMenu'] = $manyTemplatesMenu;

            if (($POST['add_property'] ?? false) || ($POST['update_value'] ?? false) || ($POST['clear_object'] ?? false)) {
                // add property
                $line = '';
                if (is_array($POST['data'])) {
                    $name = key($POST['data']);
                    if (($POST['data'][$name]['name'] ?? null) !== '') {
                        // Workaround for this special case: User adds a key and submits by pressing the return key. The form however will use "add_property" which is the name of the first submit button in this form.
                        unset($POST['update_value']);
                        $POST['add_property'] = 'Add';
                    }
                    if ($POST['add_property'] ?? false) {
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
                    } elseif ($POST['update_value'] ?? false) {
                        $pline = $name . ' = ' . trim($POST['data'][$name]['value']);
                        $updatedMessage = GeneralUtility::makeInstance(FlashMessage::class, $pline, $lang->getLL('valueUpdated'));
                        $this->addFlashMessage($updatedMessage);
                        $line .= LF . $pline;
                    } elseif ($POST['clear_object'] ?? false) {
                        if ($POST['data'][$name]['clearValue'] ?? false) {
                            $pline = $name . ' >';
                            $objectClearedMessage = GeneralUtility::makeInstance(FlashMessage::class, $pline, $lang->getLL('objectCleared'));
                            $this->addFlashMessage($objectClearedMessage);
                            $line .= LF . $pline;
                        }
                    }
                }
                if ($line) {
                    $saveId = ($this->templateRow['_ORIG_uid'] ?? false) ?: $this->templateRow['uid'] ?? 0;
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
                    // re-read the template ...
                    $this->initialize_editor($this->id, $template_uid);
                }
            }
        }
        $tsbr = $this->request->getQueryParams()['tsbr'] ?? null;
        $update = 0;
        if (is_array($tsbr)) {
            // If any plus-signs were clicked, it's registered.
            $this->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType] = $this->depthKeys($tsbr, $this->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType] ?? []);
            $update = 1;
        }
        if ($POST['Submit'] ?? false) {
            // If any POST-vars are send, update the condition array
            $this->MOD_SETTINGS['tsbrowser_conditions'] = $POST['conditions'];
            $update = 1;
        }
        if ($update) {
            $this->getBackendUser()->pushModuleData('web_ts', $this->MOD_SETTINGS);
        }
        $this->templateService->matchAlternative = $this->MOD_SETTINGS['tsbrowser_conditions'] ?? [];
        $this->templateService->matchAlternative[] = 'dummydummydummydummydummydummydummydummydummydummydummy';
        // This is just here to make sure that at least one element is in the array so that the tsparser actually uses this array to match.
        $this->templateService->constantMode = $this->MOD_SETTINGS['ts_browser_const'];
        // "sObj" is set by ExtendedTemplateService to edit single keys
        $sObj = $this->request->getParsedBody()['sObj'] ?? $this->request->getQueryParams()['sObj'] ?? null;
        if (!empty($sObj) && $this->templateService->constantMode) {
            $this->templateService->constantMode = 'untouched';
        }
        $this->templateService->regexMode = $this->MOD_SETTINGS['ts_browser_regexsearch'] ?? '';
        $this->templateService->linkObjects = true;
        $this->templateService->ext_regLinenumbers = true;
        $this->templateService->ext_regComments = $this->MOD_SETTINGS['ts_browser_showComments'];
        $this->templateService->bType = $bType;
        $this->templateService->generateConfig();
        if ($bType === 'setup') {
            $theSetup = $this->templateService->setup;
        } else {
            $theSetup = $this->templateService->setup_constants;
        }
        // EDIT A VALUE:
        $assigns['typoScriptPath'] = $sObj;
        if (!empty($sObj)) {
            [$theSetup, $theSetupValue] = $this->getSetup($theSetup, $sObj);
            $assigns['theSetupValue'] = $theSetupValue;
            if ($existTemplate === false) {
                $noTemplateMessage = GeneralUtility::makeInstance(FlashMessage::class, $lang->getLL('noCurrentTemplate'), $lang->getLL('edit'), FlashMessage::ERROR);
                $this->addFlashMessage($noTemplateMessage);
            }
            // Links:
            $urlParameters = [
                'id' => $this->id,
            ];
            $aHref = (string)$this->uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
            $assigns['moduleUrl'] = (string)$this->uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
            $assigns['isNotInTopLevelKeyList'] = !isset($this->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][$sObj]);
            $assigns['hasProperties'] = !empty($theSetup);
            if ($this->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][$sObj] ?? false) {
                $assigns['moduleUrlObjectListAction'] = $aHref . '&addKey[' . rawurlencode($sObj) . ']=0&SET[ts_browser_toplevel_' . $bType . ']=0';
            } else {
                if (!empty($theSetup)) {
                    $assigns['moduleUrlObjectListAction'] = $aHref . '&addKey[' . rawurlencode($sObj) . ']=1&SET[ts_browser_toplevel_' . $bType . ']=' . rawurlencode($sObj);
                }
            }
        } else {
            $this->templateService->tsbrowser_depthKeys = $this->MOD_SETTINGS['tsbrowser_depthKeys_' . $bType] ?? null;
            if ($this->request->getParsedBody()['search_field'] ?? false) {
                // If any POST-vars are send, update the condition array
                $searchString = $this->request->getParsedBody()['search_field'];
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
            $assigns['hasTsBrowserTypes'] = is_array($this->MOD_MENU['ts_browser_type'] ?? false) && count($this->MOD_MENU['ts_browser_type']) > 1;
            if (is_array($this->MOD_MENU['ts_browser_type']) && count($this->MOD_MENU['ts_browser_type']) > 1) {
                $assigns['browserTypeDropdownMenu'] = BackendUtility::getDropdownMenu($this->id, 'SET[ts_browser_type]', $bType, $this->MOD_MENU['ts_browser_type']);
            }
            $assigns['hasTopLevelInObjectList'] = is_array($this->MOD_MENU['ts_browser_toplevel_' . $bType] ?? false) && count($this->MOD_MENU['ts_browser_toplevel_' . $bType]) > 1;
            if (is_array($this->MOD_MENU['ts_browser_toplevel_' . $bType]) && count($this->MOD_MENU['ts_browser_toplevel_' . $bType]) > 1) {
                $assigns['objectListDropdownMenu'] = BackendUtility::getDropdownMenu($this->id, 'SET[ts_browser_toplevel_' . $bType . ']', $this->MOD_SETTINGS['ts_browser_toplevel_' . $bType], $this->MOD_MENU['ts_browser_toplevel_' . $bType]);
            }

            $assigns['regexSearchCheckbox'] = BackendUtility::getFuncCheck($this->id, 'SET[ts_browser_regexsearch]', $this->MOD_SETTINGS['ts_browser_regexsearch'] ?? false, '', '', 'id="checkTs_browser_regexsearch"');
            $assigns['postSearchField'] = $POST['search_field'] ?? null;
            $theKey = $this->MOD_SETTINGS['ts_browser_toplevel_' . $bType] ?? '';
            if (!$theKey || !str_replace('-', '', $theKey)) {
                $theKey = '';
            }
            [$theSetup] = $this->getSetup($theSetup, $this->MOD_SETTINGS['ts_browser_toplevel_' . $bType] ?? '');
            $tree = $this->templateService->ext_getObjTree($theSetup, $theKey, '', (bool)($this->MOD_SETTINGS['ts_browser_alphaSort'] ?? false));
            $tree = $this->templateService->substituteCMarkers($tree);
            $urlParameters = [
                'id' => $this->id,
            ];
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $aHref = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
            // Parser Errors:
            $pEkey = $bType === 'setup' ? 'config' : 'constants';
            $assigns['hasParseErrors'] = !empty($this->templateService->parserErrors[$pEkey]);
            if (!empty($this->templateService->parserErrors[$pEkey])) {
                $assigns['showErrorDetailsUri'] = $aHref . '&SET[function]=TYPO3\\CMS\\Tstemplate\\Controller\\TemplateAnalyzerModuleFunctionController&highlightType=' . $bType . '&highlightLine=';
                $assigns['parseErrors'] = $this->templateService->parserErrors[$pEkey];
            }

            if (isset($this->MOD_SETTINGS['ts_browser_TLKeys_' . $bType][$theKey])) {
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
            $assigns['isSetupAndCropLinesDisabled'] = $bType === 'setup';
            $assigns['checkBoxShowComments'] = BackendUtility::getFuncCheck($this->id, 'SET[ts_browser_showComments]', $this->MOD_SETTINGS['ts_browser_showComments'] ?? '', '', '', 'id="checkTs_browser_showComments"');
            $assigns['checkBoxAlphaSort'] = BackendUtility::getFuncCheck($this->id, 'SET[ts_browser_alphaSort]', $this->MOD_SETTINGS['ts_browser_alphaSort'] ?? '', '', '', 'id="checkTs_browser_alphaSort"');
            if ($bType === 'setup') {
                $assigns['dropdownDisplayConstants'] = BackendUtility::getDropdownMenu($this->id, 'SET[ts_browser_const]', $this->MOD_SETTINGS['ts_browser_const'] ?? '', $this->MOD_MENU['ts_browser_const']);
            }

            // Conditions:
            $assigns['hasConditions'] = is_array($this->templateService->sections) && !empty($this->templateService->sections);
            $activeConditions = 0;
            if (is_array($this->templateService->sections) && !empty($this->templateService->sections)) {
                $tsConditions = [];
                foreach ($this->templateService->sections as $key => $val) {
                    $isSet = (bool)($this->MOD_SETTINGS['tsbrowser_conditions'][$key] ?? false);
                    if ($isSet) {
                        $activeConditions++;
                    }

                    $tsConditions[] = [
                        'key' => $key,
                        'value' => $val,
                        'label' => $this->templateService->substituteCMarkers(htmlspecialchars($val)),
                        'isSet' => $isSet,
                    ];
                }
                $assigns['tsConditions'] = $tsConditions;
            }
            $assigns['activeConditions'] = $activeConditions;
            // Ending section displayoptions
        }
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tooltip.js');
        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setTemplateRootPaths(['EXT:tstemplate/Resources/Private/Templates']);
        $view->assignMultiple($assigns);

        return $view->render('TemplateObjectBrowserModuleFunction');
    }

    /**
     * Add flash message to queue
     *
     * @param FlashMessage $flashMessage
     */
    protected function addFlashMessage(FlashMessage $flashMessage)
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * @param array $theSetup
     * @param string $theKey
     * @return array{0: array, 1: string}
     */
    protected function getSetup(array $theSetup, string $theKey): array
    {
        $theKey = trim((string)$theKey);
        if (empty($theKey)) {
            // Early return the whole setup in case key is empty
            return [(array)$theSetup, ''];
        }
        // 'a.b.c' --> ['a', 'b.c']
        $parts = explode('.', $theKey, 2);
        $pathSegment = $parts[0] ?? '';
        $pathRest = trim($parts[1] ?? '');
        if ($pathSegment !== '' && is_array($theSetup[$pathSegment . '.'] ?? false)) {
            if ($pathRest !== '') {
                // Current path segment is a sub array, check it recursively by applying the rest of the key
                return $this->getSetup($theSetup[$pathSegment . '.'], $pathRest);
            }
            // No further path to evaluate, return current setup and the value for the current path segment - if any
            return [$theSetup[$pathSegment . '.'], $theSetup[$pathSegment] ?? ''];
        }
        // Return the key value - if any - along with an empty setup since no sub array exists
        return [[], $theSetup[$theKey] ?? ''];
    }

    protected function depthKeys(array $arr, array $settings): array
    {
        $tsbrArray = [];
        foreach ($arr as $theK => $theV) {
            $theKeyParts = explode('.', $theK);
            $depth = '';
            $c = count($theKeyParts);
            $a = 0;
            foreach ($theKeyParts as $p) {
                $a++;
                $depth .= ($depth ? '.' : '') . $p;
                $tsbrArray[$depth] = $c == $a ? $theV : 1;
            }
        }
        // Modify settings
        foreach ($tsbrArray as $theK => $theV) {
            if ($theV) {
                $settings[$theK] = 1;
            } else {
                unset($settings[$theK]);
            }
        }
        return $settings;
    }
}
