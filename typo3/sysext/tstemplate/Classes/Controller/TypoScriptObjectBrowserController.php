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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * This class displays the submodule "TypoScript Object Browser" inside the Web > Template module
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptObjectBrowserController extends TypoScriptTemplateModuleController
{
    /**
     * Initialize editor
     *
     * Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
     */
    protected function initialize_editor(int $selectedTemplateRecord): bool
    {
        // Defined global here!
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);

        $this->templateRow = $this->getFirstTemplateRecordOnPage($this->id, $selectedTemplateRecord);
        $hasFirstTemplate = is_array($this->templateRow);
        // No explicitly selected template on this page was found, so we behave like the Frontend (e.g. when a template is hidden but on the page above)
        if (!$hasFirstTemplate) {
            // Re-initiatlize the templateService but do not include hidden templates
            $context = clone GeneralUtility::makeInstance(Context::class);
            $context->setAspect('visibility', GeneralUtility::makeInstance(VisibilityAspect::class));
            $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class, $context);
        }
        // Gets the rootLine
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $this->id);
        $rootLine = $rootlineUtility->get();
        // This generates the constants/config + hierarchy info for the template.
        $this->templateService->runThroughTemplates($rootLine, $selectedTemplateRecord);
        return $hasFirstTemplate;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        // Fallback to regular module when on root level
        if ($this->id === 0) {
            return $this->overviewAction();
        }
        $lang = $this->getLanguageService();
        $parsedBody = $this->request->getParsedBody();
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->templateMenu();
        $selectedTemplateRecord = 0;
        if ($manyTemplatesMenu) {
            $selectedTemplateRecord = (int)$this->moduleData->get('templatesOnPage');
        }
        $tsBrowserType = (string)$this->moduleData->get('ts_browser_type');
        $existTemplate = $this->initialize_editor($selectedTemplateRecord);
        // initialize
        $assigns = [];
        $assigns['existTemplate'] = $existTemplate;
        $assigns['tsBrowserType'] = $tsBrowserType;
        if ($existTemplate) {
            $assigns['templateRecord'] = $this->templateRow;
            $assigns['linkWrapTemplateTitle'] = $this->linkWrapTemplateTitle($this->templateRow['title'], ($tsBrowserType === 'setup' ? 'config' : 'constants'));
            $assigns['manyTemplatesMenu'] = $manyTemplatesMenu;

            if (($parsedBody['add_property'] ?? false) || ($parsedBody['update_value'] ?? false) || ($parsedBody['clear_object'] ?? false)) {
                // add property
                $line = '';
                if (is_array($parsedBody['data'])) {
                    $name = key($parsedBody['data']);
                    if (($parsedBody['data'][$name]['name'] ?? null) !== '') {
                        // Workaround for this special case: User adds a key and submits by pressing the return key. The form however will use "add_property" which is the name of the first submit button in this form.
                        unset($parsedBody['update_value']);
                        $parsedBody['add_property'] = 'Add';
                    }
                    if ($parsedBody['add_property'] ?? false) {
                        $property = trim($parsedBody['data'][$name]['name']);
                        if (preg_replace('/[^a-zA-Z0-9_\\.]*/', '', $property) != $property) {
                            $badPropertyMessage = GeneralUtility::makeInstance(FlashMessage::class, $lang->getLL('noSpaces') . $lang->getLL('nothingUpdated'), $lang->getLL('badProperty'), FlashMessage::ERROR);
                            $this->addFlashMessage($badPropertyMessage);
                        } else {
                            $pline = $name . '.' . $property . ' = ' . trim($parsedBody['data'][$name]['propertyValue']);
                            $propertyAddedMessage = GeneralUtility::makeInstance(FlashMessage::class, $pline, $lang->getLL('propertyAdded'));
                            $this->addFlashMessage($propertyAddedMessage);
                            $line .= LF . $pline;
                        }
                    } elseif ($parsedBody['update_value'] ?? false) {
                        $pline = $name . ' = ' . trim($parsedBody['data'][$name]['value']);
                        $updatedMessage = GeneralUtility::makeInstance(FlashMessage::class, $pline, $lang->getLL('valueUpdated'));
                        $this->addFlashMessage($updatedMessage);
                        $line .= LF . $pline;
                    } elseif ($parsedBody['clear_object'] ?? false) {
                        if ($parsedBody['data'][$name]['clearValue'] ?? false) {
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
                    $field = $tsBrowserType === 'setup' ? 'config' : 'constants';
                    $recData['sys_template'][$saveId][$field] = $this->templateRow[$field] . $line;
                    // Create new  tce-object
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    // Initialize
                    $tce->start($recData, []);
                    // Saved the stuff
                    $tce->process_datamap();
                    // re-read the template ...
                    $this->initialize_editor($selectedTemplateRecord);
                }
            }
        }
        $tsbr = $this->request->getQueryParams()['tsbr'] ?? null;
        $update = 0;
        if (is_array($tsbr)) {
            // If any plus-signs were clicked, it's registered.
            $this->moduleData->set('tsbrowser_depthKeys_' . $tsBrowserType, $this->depthKeys($tsbr, $this->moduleData->get('tsbrowser_depthKeys_' . $tsBrowserType, [])));
            $update = 1;
        }
        if ($parsedBody['Submit'] ?? false) {
            // If any POST-vars are send, update the condition array
            $this->moduleData->set('tsbrowser_conditions', $parsedBody['conditions']);
            $update = 1;
        }
        if ($update) {
            $this->getBackendUser()->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
        }
        $this->templateService->matchAlternative = (array)$this->moduleData->get('tsbrowser_conditions', []);
        $this->templateService->matchAlternative[] = 'dummydummydummydummydummydummydummydummydummydummydummy';
        // This is just here to make sure that at least one element is in the array so that the tsparser actually uses this array to match.
        $this->templateService->constantMode = (string)$this->moduleData->get('ts_browser_const');
        // "sObj" is set by ExtendedTemplateService to edit single keys
        $sObj = $parsedBody['sObj'] ?? $this->request->getQueryParams()['sObj'] ?? null;
        if (!empty($sObj) && $this->templateService->constantMode) {
            $this->templateService->constantMode = 'untouched';
        }
        $this->templateService->regexMode = (bool)$this->moduleData->get('ts_browser_regexsearch');
        $this->templateService->linkObjects = true;
        $this->templateService->ext_regLinenumbers = true;
        $this->templateService->ext_regComments = (bool)$this->moduleData->get('ts_browser_showComments');
        $this->templateService->bType = $tsBrowserType;
        $this->templateService->generateConfig();
        if ($tsBrowserType === 'setup') {
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
            $assigns['moduleUrl'] = (string)$this->uriBuilder->buildUriFromRoute($this->currentModule->getIdentifier(), $urlParameters);
            $assigns['isNotInTopLevelKeyList'] = !isset($this->moduleData->get('ts_browser_TLKeys_' . $tsBrowserType, [])[$sObj]);
            $assigns['hasProperties'] = !empty($theSetup);
            $topLevelKeyList = (array)$this->moduleData->get('ts_browser_TLKeys_' . $tsBrowserType, []);
            if ($topLevelKeyList[$sObj] ?? false) {
                $assigns['moduleUrlObjectListAction'] = (string)$this->uriBuilder->buildUriFromRoute(
                    $this->currentModule->getIdentifier(),
                    [
                        'id' => $this->id,
                        'addKey[' . rawurlencode($sObj) . ']' => '0',
                        'ts_browser_toplevel_' . $tsBrowserType => '0',
                    ]
                );
            } elseif ($assigns['hasProperties']) {
                $assigns['moduleUrlObjectListAction'] = (string)$this->uriBuilder->buildUriFromRoute(
                    $this->currentModule->getIdentifier(),
                    [
                        'id' => $this->id,
                        'addKey[' . rawurlencode($sObj) . ']' => '1',
                        'ts_browser_toplevel_' . $tsBrowserType => rawurlencode($sObj),
                    ]
                );
            }
        } else {
            $this->templateService->tsbrowser_depthKeys = (array)$this->moduleData->get('tsbrowser_depthKeys_' . $tsBrowserType, []);
            if ($parsedBody['search_field'] ?? false) {
                // If any POST-vars are send, update the condition array
                $searchString = $parsedBody['search_field'];
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
            $tsBrowserTypes = $this->getAllowedModuleOptions()['ts_browser_type'] ?? [];
            if (is_array($tsBrowserTypes) && count($tsBrowserTypes) > 1) {
                $assigns['browserTypeDropdownMenu'] = BackendUtility::getDropdownMenu($this->id, 'ts_browser_type', $tsBrowserType, $tsBrowserTypes, '', '', ['id' => 'ts_browser_type']);
            }
            $topLevelBrowserType = $this->getTopLevelObjectList()['ts_browser_toplevel_' . $tsBrowserType] ?? null;
            if (is_array($topLevelBrowserType) && count($topLevelBrowserType) > 1) {
                $assigns['objectListDropdownMenu'] = BackendUtility::getDropdownMenu($this->id, 'ts_browser_toplevel_' . $tsBrowserType, (string)$this->moduleData->get('ts_browser_toplevel_' . $tsBrowserType, ''), $topLevelBrowserType, '', '', ['id' => 'ts_browser_toplevel_' . $tsBrowserType]);
            }

            $assigns['regexSearchCheckbox'] = BackendUtility::getFuncCheck($this->id, 'ts_browser_regexsearch', (bool)$this->moduleData->get('ts_browser_regexsearch'), '', '', 'id="checkTs_browser_regexsearch"');
            $assigns['postSearchField'] = $parsedBody['search_field'] ?? null;
            $theKey = (string)$this->moduleData->get('ts_browser_toplevel_' . $tsBrowserType, '');
            if (!$theKey || !str_replace('-', '', $theKey)) {
                $theKey = '';
            }
            [$theSetup] = $this->getSetup($theSetup, (string)$this->moduleData->get('ts_browser_toplevel_' . $tsBrowserType, ''));
            $tree = $this->templateService->ext_getObjTree($theSetup, $theKey, '', (bool)$this->moduleData->get('ts_browser_alphaSort'), $this->currentModule->getIdentifier());
            $tree = $this->templateService->substituteCMarkers($tree);

            // Parser Errors:
            $pEkey = $tsBrowserType === 'setup' ? 'config' : 'constants';
            $assigns['hasParseErrors'] = !empty($this->templateService->parserErrors[$pEkey]);

            if (!empty($this->templateService->parserErrors[$pEkey])) {
                $assigns['showErrorDetailsUri'] = (string)$this->uriBuilder->buildUriFromRoute(
                    $this->currentModule->getIdentifier(),
                    [
                        'id' => $this->id,
                        'highlightType' => $tsBrowserType,
                        'highlightLine' => '',
                    ]
                );
                $assigns['parseErrors'] = $this->templateService->parserErrors[$pEkey];
            }

            if (isset($this->moduleData->get('ts_browser_TLKeys_' . $tsBrowserType)[$theKey])) {
                $assigns['moduleUrlRemoveFromObjectList'] = (string)$this->uriBuilder->buildUriFromRoute(
                    $this->currentModule->getIdentifier(),
                    [
                        'id' => $this->id,
                        'addKey[' . $theKey . ']' => '0',
                        'ts_browser_toplevel_' . $tsBrowserType => '0',
                    ]
                );
            }

            $assigns['hasKeySelected'] = $theKey !== '';

            if ($theKey) {
                $assigns['treeLabel'] = $theKey;
            } else {
                $assigns['rootLLKey'] = $tsBrowserType === 'setup' ? 'setupRoot' : 'constantRoot';
            }
            $assigns['tsTree'] = $tree;

            // second row options
            $assigns['isSetupAndCropLinesDisabled'] = $tsBrowserType === 'setup';
            $assigns['checkBoxShowComments'] = BackendUtility::getFuncCheck($this->id, 'ts_browser_showComments', (bool)$this->moduleData->get('ts_browser_showComments'), '', '', 'id="checkTs_browser_showComments"');
            $assigns['checkBoxAlphaSort'] = BackendUtility::getFuncCheck($this->id, 'ts_browser_alphaSort', (bool)$this->moduleData->get('ts_browser_alphaSort'), '', '', 'id="checkTs_browser_alphaSort"');
            if ($tsBrowserType === 'setup') {
                $assigns['dropdownDisplayConstants'] = BackendUtility::getDropdownMenu($this->id, 'ts_browser_const', (string)$this->moduleData->get('ts_browser_const'), $this->getAllowedModuleOptions()['ts_browser_const'], '', '', ['id' => 'ts_browser_const']);
            }

            // Conditions:
            $assigns['hasConditions'] = is_array($this->templateService->sections) && !empty($this->templateService->sections);
            $activeConditions = 0;
            if (is_array($this->templateService->sections) && !empty($this->templateService->sections)) {
                $tsConditions = [];
                foreach ($this->templateService->sections as $key => $val) {
                    $isSet = (bool)($this->moduleData->get('tsbrowser_conditions')[$key] ?? false);
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
        $this->view->assignMultiple($assigns);
        return $this->view->renderResponse('TemplateObjectBrowser');
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
        $theKey = trim($theKey);
        if (empty($theKey)) {
            // Early return the whole setup in case key is empty
            return [$theSetup, ''];
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

    /**
     * Evaluate some module data options
     */
    protected function init(ServerRequestInterface $request): void
    {
        parent::init($request);
        $this->getLanguageService()->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf');
        foreach (['setup', 'const'] as $bType) {
            $addKey = $this->request->getQueryParams()['addKey'] ?? null;
            $topLevelKeyList = $this->moduleData->get('ts_browser_TLKeys_' . $bType) ?? [];
            // If any plus-signs were clicked, it's registered.
            if (is_array($addKey)) {
                reset($addKey);
                if (current($addKey)) {
                    $topLevelKeyList[key($addKey)] = key($addKey);
                } else {
                    unset($topLevelKeyList[key($addKey)]);
                }
                $this->moduleData->set('ts_browser_TLKeys_' . $bType, $topLevelKeyList);
                $this->getBackendUser()->pushModuleData($this->moduleData->getModuleIdentifier(), $this->moduleData->toArray());
            }
        }
    }

    protected function getAllowedModuleOptions(): array
    {
        $lang = $this->getLanguageService();
        return [
            'ts_browser_type' => [
                'const' => $lang->getLL('constants'),
                'setup' => $lang->getLL('setup'),
            ],
            'ts_browser_const' => [
                '0' => $lang->getLL('plainSubstitution'),
                'subst' => $lang->getLL('substitutedGreen'),
                'const' => $lang->getLL('unsubstitutedGreen'),
            ],
        ];
    }

    protected function getTopLevelObjectList(): array
    {
        $topLevelObjectList = [];
        foreach (['setup', 'const'] as $bType) {
            if (!empty($this->moduleData->get('ts_browser_TLKeys_' . $bType, []))) {
                $topLevelObjectList['ts_browser_toplevel_' . $bType]['0'] = mb_strtolower($this->getLanguageService()->getLL('all'), 'utf-8');
                $topLevelObjectList['ts_browser_toplevel_' . $bType]['-'] = '---';
                $topLevelObjectList['ts_browser_toplevel_' . $bType] += (array)$this->moduleData->get('ts_browser_TLKeys_' . $bType, []);
            }
        }
        return $topLevelObjectList;
    }

    /**
     * Add additional "BACK" button to the button bar
     */
    protected function getButtons(): void
    {
        parent::getButtons();

        $sObj = $this->request->getParsedBody()['sObj'] ?? $this->request->getQueryParams()['sObj'] ?? null;
        if ($this->id && $this->access && !empty($sObj)) {
            $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();
            $backButton = $buttonBar->makeLinkButton()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute($this->currentModule->getIdentifier(), ['id' => $this->id]))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }
    }
}
