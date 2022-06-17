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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * This class displays the submodule "TypoScript Object Browser" inside the Web > Template module
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class ObjectBrowserController extends AbstractTemplateModuleController
{
    protected ExtendedTemplateService $templateService;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $moduleData = $request->getAttribute('moduleData');
        if ($moduleData->cleanUp($this->getAllowedModuleOptions())) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $pageId = (int)($queryParams['id'] ?? 0);
        if ($pageId === 0) {
            // Redirect to template record overview if on page 0.
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }
        $pageRecord = BackendUtility::readPageAccess($pageId, '1=1') ?: [];

        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageId);
        if ($moduleData->clean('templatesOnPage', array_column($allTemplatesOnPage, 'uid') ?: [0])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $selectedTemplateRecord = (int)$moduleData->get('templatesOnPage');
        $templateRow = $this->parseTemplate($pageId, $selectedTemplateRecord);
        $tsBrowserType = (string)$moduleData->get('ts_browser_type');

        $openCloseBranch = $queryParams['tsbr'] ?? null;
        if (is_array($openCloseBranch)) {
            // If any plus-signs were clicked, it's registered.
            $moduleData->set('tsbrowser_depthKeys_' . $tsBrowserType, $this->depthKeys($openCloseBranch, $moduleData->get('tsbrowser_depthKeys_' . $tsBrowserType, [])));
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        if ($parsedBody['Submit'] ?? false) {
            // If any POST-vars are sent, update the condition array
            $moduleData->set('tsbrowser_conditions', $parsedBody['conditions']);
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $view = $this->moduleTemplateFactory->create($request);

        if ($templateRow) {
            $this->writeLinesToTemplate($view, $request, $templateRow, $tsBrowserType);
            $this->parseTemplate($pageId, $selectedTemplateRecord);
        }

        $this->templateService->matchAlternative = (array)$moduleData->get('tsbrowser_conditions', []);
        // This is just here to make sure that at least one element is in the array so that the tsparser actually uses this array to match.
        $this->templateService->matchAlternative[] = 'dummydummydummydummydummydummydummydummydummydummydummy';
        $this->templateService->constantMode = (string)$moduleData->get('ts_browser_const');
        // "sObj" is set by ExtendedTemplateService to edit single keys
        $sObj = $parsedBody['sObj'] ?? $queryParams['sObj'] ?? null;
        if (!empty($sObj) && $this->templateService->constantMode) {
            $this->templateService->constantMode = 'untouched';
        }
        $this->templateService->regexMode = (bool)$moduleData->get('ts_browser_regexsearch');
        $this->templateService->linkObjects = true;
        $this->templateService->ext_regLinenumbers = true;
        $this->templateService->ext_regComments = (bool)$moduleData->get('ts_browser_showComments');
        $this->templateService->bType = $tsBrowserType;
        $this->templateService->generateConfig();
        if ($tsBrowserType === 'setup') {
            $theSetup = $this->templateService->setup;
        } else {
            $theSetup = $this->templateService->setup_constants;
        }

        if (!empty($sObj)) {
            // Edit related view assignments
            if (!$templateRow) {
                $view->addFlashMessage(
                    $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:noCurrentTemplate'),
                    $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:edit'),
                    ContextualFeedbackSeverity::ERROR
                );
            }
            [$theSetup, $theSetupValue] = $this->getSetup($theSetup, $sObj);
            $view->assign('theSetupValue', $theSetupValue);
        }

        if (empty($sObj)) {
            // Tree creation and view assignments
            $this->templateService->tsbrowser_depthKeys = (array)$moduleData->get('tsbrowser_depthKeys_' . $tsBrowserType, []);
            $postSearchField = $parsedBody['search_field'] ?? null;
            if ($postSearchField) {
                try {
                    // If any POST-vars are sent, update the condition array
                    $this->templateService->tsbrowser_depthKeys = $this->templateService->ext_getSearchKeys($theSetup, '', $postSearchField, []);
                } catch (Exception $e) {
                    $view->addFlashMessage(
                        sprintf($languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:error.' . $e->getCode()), $postSearchField),
                        '',
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            }
            [$theSetup] = $this->getSetup($theSetup, '');
            $tree = $this->templateService->ext_getObjTree($theSetup, '', '', (bool)$moduleData->get('ts_browser_alphaSort'), $currentModuleIdentifier);
            $tree = $this->templateService->substituteCMarkers($tree);
            $activeConditions = 0;
            $tsConditions = [];
            if (is_array($this->templateService->sections) && !empty($this->templateService->sections)) {
                foreach ($this->templateService->sections as $key => $val) {
                    $isSet = (bool)($moduleData->get('tsbrowser_conditions')[$key] ?? false);
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
            }
            $parseErrorType = $tsBrowserType === 'setup' ? 'config' : 'constants';
            $view->assignMultiple([
                'postSearchField' => $parsedBody['search_field'] ?? null,
                'regexSearchCheckbox' => BackendUtility::getFuncCheck($pageId, 'ts_browser_regexsearch', (bool)$moduleData->get('ts_browser_regexsearch'), '', '', 'id="checkTs_browser_regexsearch"'),
                'browserTypeDropdownMenu' => BackendUtility::getDropdownMenu($pageId, 'ts_browser_type', $tsBrowserType, $this->getAllowedModuleOptions()['ts_browser_type'], '', '', ['id' => 'ts_browser_type']),
                'hasParseErrors' => !empty($this->templateService->parserErrors[$parseErrorType]),
                'parseErrors' => $this->templateService->parserErrors[$parseErrorType],
                'showErrorDetailsUri' => (string)$this->uriBuilder->buildUriFromRoute($currentModuleIdentifier, ['id' => $pageId, 'highlightType' => $tsBrowserType, 'highlightLine' => '']),
                'tsTree' => $tree,
                'isSetupAndCropLinesDisabled' => $tsBrowserType === 'setup',
                'checkBoxShowComments' => BackendUtility::getFuncCheck($pageId, 'ts_browser_showComments', (bool)$moduleData->get('ts_browser_showComments'), '', '', 'id="checkTs_browser_showComments"'),
                'checkBoxAlphaSort' => BackendUtility::getFuncCheck($pageId, 'ts_browser_alphaSort', (bool)$moduleData->get('ts_browser_alphaSort'), '', '', 'id="checkTs_browser_alphaSort"'),
                'dropdownDisplayConstants' => BackendUtility::getDropdownMenu($pageId, 'ts_browser_const', (string)$moduleData->get('ts_browser_const'), $this->getAllowedModuleOptions()['ts_browser_const'], '', '', ['id' => 'ts_browser_const']),
                'tsConditions' => $tsConditions,
                'activeConditions' => $activeConditions,
            ]);
        }

        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addPreviewButtonToDocHeader($view, $pageId, (int)$pageRecord['doktype']);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageId);
        $this->addBackButtonToDocHeader($view, $request, $currentModuleIdentifier, $pageId);
        $view->makeDocHeaderModuleMenu(['id' => $pageId]);
        $view->assignMultiple([
            'moduleIdentifier' => $currentModuleIdentifier,
            'pageId' => $pageId,
            'tsBrowserType' => $tsBrowserType,
            'templateRecord' => $templateRow,
            'manyTemplatesMenu' => BackendUtility::getFuncMenu($pageId, 'templatesOnPage', $moduleData->get('templatesOnPage'), array_column($allTemplatesOnPage, 'title', 'uid')),
            'typoScriptPath' => $sObj,
        ]);

        return $view->renderResponse('ObjectBrowser');
    }

    protected function parseTemplate(int $pageId, int $selectedTemplateRecord): ?array
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $templateRow = $this->getFirstTemplateRecordOnPage($pageId, $selectedTemplateRecord);
        $hasFirstTemplate = is_array($templateRow);
        // No explicitly selected template on this page was found, so we behave like
        // the Frontend (e.g. when a template is hidden but on the page above)
        if (!$hasFirstTemplate) {
            // Re-initialize the templateService but do not include hidden templates
            $context = clone GeneralUtility::makeInstance(Context::class);
            $context->setAspect('visibility', GeneralUtility::makeInstance(VisibilityAspect::class));
            $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class, $context);
        }
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
        // Generates the constants/config + hierarchy info for the template.
        $this->templateService->runThroughTemplates($rootLine, $selectedTemplateRecord);
        return $templateRow ?: null;
    }

    /**
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

    protected function getAllowedModuleOptions(): array
    {
        $languageService = $this->getLanguageService();
        return [
            'ts_browser_type' => [
                'const' => $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:constants'),
                'setup' => $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:setup'),
            ],
            'ts_browser_const' => [
                '0' => $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:plainSubstitution'),
                'subst' => $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:substitutedGreen'),
                'const' => $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:unsubstitutedGreen'),
            ],
        ];
    }

    protected function addBackButtonToDocHeader(ModuleTemplate $view, ServerRequestInterface $request, string $moduleIdentifier, int $pageId): void
    {
        $languageService = $this->getLanguageService();
        $sObj = $request->getParsedBody()['sObj'] ?? $request->getQueryParams()['sObj'] ?? null;
        if ($pageId && !empty($sObj)) {
            $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
            $backButton = $buttonBar->makeLinkButton()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute($moduleIdentifier, ['id' => $pageId]))
                ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }
    }

    protected function writeLinesToTemplate(ModuleTemplate $view, ServerRequestInterface $request, array $templateRow, string $tsBrowserType): void
    {
        $languageService = $this->getLanguageService();
        $parsedBody = $request->getParsedBody();
        if ($templateRow && ($parsedBody['add_property'] ?? false) || ($parsedBody['update_value'] ?? false) || ($parsedBody['clear_object'] ?? false)) {
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
                        $view->addFlashMessage(
                            $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:noSpaces')
                            . $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:nothingUpdated'),
                            $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:badProperty'),
                            ContextualFeedbackSeverity::ERROR
                        );
                    } else {
                        $propertyLine = $name . '.' . $property . ' = ' . trim($parsedBody['data'][$name]['propertyValue']);
                        $view->addFlashMessage(
                            $propertyLine,
                            $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:propertyAdded')
                        );
                        $line .= LF . $propertyLine;
                    }
                } elseif ($parsedBody['update_value'] ?? false) {
                    $propertyLine = $name . ' = ' . trim($parsedBody['data'][$name]['value']);
                    $view->addFlashMessage(
                        $propertyLine,
                        $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:valueUpdated')
                    );
                    $line .= LF . $propertyLine;
                } elseif ($parsedBody['clear_object'] ?? false) {
                    if ($parsedBody['data'][$name]['clearValue'] ?? false) {
                        $propertyLine = $name . ' >';
                        $view->addFlashMessage(
                            $propertyLine,
                            $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:objectCleared')
                        );
                        $line .= LF . $propertyLine;
                    }
                }
            }
            if ($line) {
                // Save data and re-init template parsing
                $saveId = ($templateRow['_ORIG_uid'] ?? false) ?: $templateRow['uid'] ?? 0;
                $recordData = [];
                $field = $tsBrowserType === 'setup' ? 'config' : 'constants';
                $recordData['sys_template'][$saveId][$field] = $templateRow[$field] . $line;
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start($recordData, []);
                $dataHandler->process_datamap();
            }
        }
    }
}
