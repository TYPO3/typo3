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
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * TypoScript Constant editor
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class ConstantEditorController extends AbstractTemplateModuleController
{
    protected array $categories = [
        // Constants of superior importance for the template-layout. This is dimensions, imagefiles and enabling of various features.
        //The most basic constants, which you would almost always want to configure.
        'basic' => [],
        // Menu setup. This includes fontfiles, sizes, background images. Depending on the menutype.
        'menu' => [],
        // All constants related to the display of pagecontent elements
        'content' => [],
        // General configuration like metatags, link targets
        'page' => [],
        // Advanced functions, which are used very seldom.
        'advanced' => [],
        'all' => [],
    ];

    protected ExtendedTemplateService $templateService;
    protected array $constants = [];

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConstantConfigurationParser $constantParser,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();

        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $moduleData = $request->getAttribute('moduleData');
        if ($moduleData->cleanUp([])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $pageId = (int)($request->getQueryParams()['id'] ?? 0);
        if ($pageId === 0) {
            // Redirect to template record overview if on page 0.
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }
        $pageRecord = BackendUtility::readPageAccess($pageId, '1=1') ?: [];

        $this->createTemplateIfRequested($request, $pageId);

        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageId);
        if ($moduleData->clean('templatesOnPage', array_column($allTemplatesOnPage, 'uid') ?: [0])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $selectedTemplateRecord = (int)$moduleData->get('templatesOnPage');
        $templateRow = $this->parseTemplate($pageId, $selectedTemplateRecord);

        if ($request->getParsedBody()['_savedok'] ?? false) {
            // Update template with new data on save
            $constantsHaveChanged = $this->templateService->ext_procesInput($request->getParsedBody(), $this->constants);
            if ($constantsHaveChanged) {
                $saveId = empty($templateRow['_ORIG_uid']) ? $templateRow['uid'] : $templateRow['_ORIG_uid'];
                $recordData = [];
                $recordData['sys_template'][$saveId]['constants'] = implode(LF, $this->templateService->raw);
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start($recordData, []);
                $dataHandler->process_datamap();
                // Re-init template state as constants have changed
                $this->parseTemplate($pageId, $selectedTemplateRecord);
            }
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addPreviewButtonToDocHeader($view, $pageId, (int)$pageRecord['doktype']);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageId);
        $this->addSaveButtonToDocHeader($view, $moduleData, $pageId);
        $view->makeDocHeaderModuleMenu(['id' => $pageId]);
        $availableCategories = $this->getCategoryLabels($this->categories);
        $currentCategory = (string)$moduleData->get('constant_editor_cat');
        if (!empty($availableCategories)) {
            if ($currentCategory === '') {
                $currentCategory = array_key_first($availableCategories);
            }
            $view->assign('constantsMenu', BackendUtility::getDropdownMenu($pageId, 'constant_editor_cat', $currentCategory, $availableCategories, '', '', ['id' => 'constant_editor_cat']));
        }
        $view->assignMultiple([
            'pageId' => $pageId,
            'previousPage' => $this->getClosestAncestorPageWithTemplateRecord($pageId),
            'moduleIdentifier' => $currentModuleIdentifier,
            'editorFields' => $this->printFields($this->constants, $this->categories, $currentCategory),
            'templateRecord' => $templateRow,
            'manyTemplatesMenu' => BackendUtility::getFuncMenu($pageId, 'templatesOnPage', $moduleData->get('templatesOnPage'), array_column($allTemplatesOnPage, 'title', 'uid')),
        ]);
        return $view->renderResponse('ConstantEditor');
    }

    /**
     * Set $this->templateService with parsed template and set $this->constants.
     */
    protected function parseTemplate(int $pageId, int $selectedTemplateRecord): ?array
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        // Get the row of the first *visible* template of the page. where clause like in frontend.
        $templateRow = $this->getFirstTemplateRecordOnPage($pageId, $selectedTemplateRecord);
        if (is_array($templateRow)) {
            // If there is a template
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
            // Generate constants/config + hierarchy info for the template.
            $this->templateService->runThroughTemplates($rootLine, $selectedTemplateRecord);
            // Editable constants are returned in an array.
            $this->constants = $this->templateService->generateConfig_constants();
            // Returned constants are sorted in categories, that goes into the $tmpl->categories array
            $this->categories = $this->categorizeEditableConstants($this->categories, $this->constants);
            // This array contains key=[expanded constant name], value=line number in template.
            $this->templateService->ext_regObjectPositions((string)$templateRow['constants']);
        }
        return $templateRow ?: null;
    }

    /**
     * Create editor HTML.
     */
    protected function printFields(array $theConstants, array $categories, string $category): array
    {
        $languageService = $this->getLanguageService();
        reset($theConstants);
        $groupedOutput = [];
        $subcat = '';
        if (!empty($categories[$category]) && is_array($categories[$category])) {
            asort($categories[$category]);
            $categoryLoop = 0;
            foreach ($categories[$category] as $name => $type) {
                $params = $theConstants[$name];
                if (is_array($params)) {
                    if ($subcat !== (string)($params['subcat_name'] ?? '')) {
                        $categoryLoop++;
                        $subcat = (string)($params['subcat_name'] ?? '');
                        $subcat_name = $subcat ? (string)($this->constantParser->getSubCategories()[$subcat][0] ?? '') : 'Others';
                        $groupedOutput[$categoryLoop] = [
                            'label' => $subcat_name,
                            'fields' => [],
                        ];
                    }
                    $label = $languageService->sL($params['label']);
                    $label_parts = explode(':', $label, 2);
                    if (count($label_parts) === 2) {
                        $head = trim($label_parts[0]);
                        $body = trim($label_parts[1]);
                    } else {
                        $head = trim($label_parts[0]);
                        $body = '';
                    }
                    $typeDat = $this->templateService->ext_getTypeData($params['type']);
                    $p_field = '';
                    $fragmentName = substr(md5($params['name']), 0, 10);
                    $fragmentNameEscaped = htmlspecialchars($fragmentName);
                    [$fN, $fV, $params, $idName] = $this->fNandV($params);
                    $idName = htmlspecialchars($idName);
                    $hint = '';
                    switch ($typeDat['type']) {
                        case 'int':
                        case 'int+':
                            $additionalAttributes = '';
                            if ($typeDat['paramstr'] ?? false) {
                                $hint = ' Range: ' . $typeDat['paramstr'];
                            } elseif ($typeDat['type'] === 'int+') {
                                $hint = ' Range: 0 - ';
                                $typeDat['min'] = 0;
                            } else {
                                $hint = ' (Integer)';
                            }

                            if (isset($typeDat['min'])) {
                                $additionalAttributes .= ' min="' . (int)$typeDat['min'] . '" ';
                            }
                            if (isset($typeDat['max'])) {
                                $additionalAttributes .= ' max="' . (int)$typeDat['max'] . '" ';
                            }

                            $p_field =
                                '<input class="form-control" id="' . $idName . '" type="number"'
                                . ' name="' . $fN . '" value="' . $fV . '" data-form-update-fragment="' . $fragmentNameEscaped . '" ' . $additionalAttributes . ' />';
                            break;
                        case 'color':
                            $p_field = '
                                <input class="form-control t3js-color-input" type="text" id="input-' . $idName . '" rel="' . $idName .
                                '" name="' . $fN . '" value="' . $fV . '" data-form-update-fragment="' . $fragmentNameEscaped . '"/>';
                            break;
                        case 'wrap':
                            $wArr = explode('|', $fV);
                            $p_field = '<div class="input-group">
                                            <input class="form-control form-control-adapt" type="text" id="' . $idName . '" name="' . $fN . '" value="' . $wArr[0] . '" data-form-update-fragment="' . $fragmentNameEscaped . '" />
                                            <span class="input-group-addon input-group-icon">|</span>
                                            <input class="form-control form-control-adapt" type="text" name="W' . $fN . '" value="' . $wArr[1] . '" data-form-update-fragment="' . $fragmentNameEscaped . '" />
                                         </div>';
                            break;
                        case 'offset':
                            $wArr = explode(',', $fV);
                            $labels = GeneralUtility::trimExplode(',', $typeDat['paramstr']);
                            $p_field = '<span class="input-group-addon input-group-icon">' . ($labels[0] ?: 'x') . '</span><input type="text" class="form-control form-control-adapt" name="' . $fN . '" value="' . $wArr[0] . '" data-form-update-fragment="' . $fragmentNameEscaped . '" />';
                            $p_field .= '<span class="input-group-addon input-group-icon">' . ($labels[1] ?: 'y') . '</span><input type="text" name="W' . $fN . '" value="' . $wArr[1] . '" class="form-control form-control-adapt" data-form-update-fragment="' . $fragmentNameEscaped . '" />';
                            $labelsCount = count($labels);
                            for ($aa = 2; $aa < $labelsCount; $aa++) {
                                if ($labels[$aa]) {
                                    $p_field .= '<span class="input-group-addon input-group-icon">' . $labels[$aa] . '</span><input type="text" name="W' . $aa . $fN . '" value="' . $wArr[$aa] . '" class="form-control form-control-adapt" data-form-update-fragment="' . $fragmentNameEscaped . '" />';
                                } else {
                                    $p_field .= '<input type="hidden" name="W' . $aa . $fN . '" value="' . $wArr[$aa] . '" />';
                                }
                            }
                            $p_field = '<div class="input-group">' . $p_field . '</div>';
                            break;
                        case 'options':
                            if (is_array($typeDat['params'])) {
                                $p_field = '';
                                foreach ($typeDat['params'] as $val) {
                                    $vParts = explode('=', $val, 2);
                                    $label = $vParts[0];
                                    $val = $vParts[1] ?? $vParts[0];
                                    // option tag:
                                    $sel = '';
                                    if ($val === $params['value']) {
                                        $sel = ' selected';
                                    }
                                    $p_field .= '<option value="' . htmlspecialchars($val) . '"' . $sel . '>' . $languageService->sL($label) . '</option>';
                                }
                                $p_field = '<select class="form-select" id="' . $idName . '" name="' . $fN . '" data-form-update-fragment="' . $fragmentNameEscaped . '">' . $p_field . '</select>';
                            }
                            break;
                        case 'boolean':
                            $sel = $fV ? 'checked' : '';
                            $p_field =
                                '<input type="hidden" name="' . $fN . '" value="0" />'
                                . '<div class="form-check form-check-type-icon-toggle">'
                                . '<input type="checkbox" name="' . $fN . '" id="' . $idName . '" class="form-check-input" value="' . (($typeDat['paramstr'] ?? false) ?: 1) . '" ' . $sel . ' data-form-update-fragment="' . $fragmentNameEscaped . '" />'
                                . '<label class="form-check-label" for="' . $idName . '">'
                                . '<span class="form-check-label-icon">'
                                . '<span class="form-check-label-icon-checked">' . $this->iconFactory->getIcon('actions-check', Icon::SIZE_SMALL)->render() . '</span>'
                                . '<span class="form-check-label-icon-unchecked">' . $this->iconFactory->getIcon('actions-square', Icon::SIZE_SMALL)->render() . '</span>'
                                . '</span>'
                                . '</label>'
                                . '</div>';
                            break;
                        case 'comment':
                            $sel = $fV ? '' : 'checked';
                            $p_field =
                                '<input type="hidden" name="' . $fN . '" value="0" />'
                                . '<div class="form-check form-check-type-icon-toggle">'
                                . '<input type="checkbox" name="' . $fN . '" id="' . $idName . '" class="form-check-input" value="1" ' . $sel . ' data-form-update-fragment="' . $fragmentNameEscaped . '" />'
                                . '<label class="form-check-label" for="' . $idName . '">'
                                . '<span class="form-check-label-icon">'
                                . '<span class="form-check-label-icon-checked">' . $this->iconFactory->getIcon('actions-check', Icon::SIZE_SMALL)->render() . '</span>'
                                . '<span class="form-check-label-icon-unchecked">' . $this->iconFactory->getIcon('actions-square', Icon::SIZE_SMALL)->render() . '</span>'
                                . '</span>'
                                . '</label>'
                                . '</div>';
                            break;
                        case 'file':
                            // extensionlist
                            $extList = $typeDat['paramstr'];
                            if ($extList === 'IMAGE_EXT') {
                                $extList = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
                            }
                            $p_field = '<option value="">(' . $extList . ')</option>';
                            if (trim($params['value'])) {
                                $val = $params['value'];
                                $p_field .= '<option value=""></option>';
                                $p_field .= '<option value="' . htmlspecialchars($val) . '" selected>' . $val . '</option>';
                            }
                            $p_field = '<select class="form-select" id="' . $idName . '" name="' . $fN . '" data-form-update-fragment="' . $fragmentNameEscaped . '">' . $p_field . '</select>';
                            break;
                        case 'user':
                            $userFunction = $typeDat['paramstr'];
                            $userFunctionParams = ['fieldName' => $fN, 'fieldValue' => $fV];
                            $p_field = GeneralUtility::callUserFunction($userFunction, $userFunctionParams, $this);
                            break;
                        default:
                            $p_field = '<input class="form-control" id="' . $idName . '" type="text" name="' . $fN . '" value="' . $fV . '" data-form-update-fragment="' . $fragmentNameEscaped . '" />';
                    }
                    // Define default names and IDs
                    $userTyposcriptID = 'userTS-' . $idName;
                    $defaultTyposcriptID = 'defaultTS-' . $idName;
                    $userTyposcriptStyle = '';
                    // Set the default styling options
                    if (isset($this->templateService->objReg[$params['name']])) {
                        $checkboxValue = 'checked';
                        $defaultTyposcriptStyle = 'style="display:none;"';
                    } else {
                        $checkboxValue = '';
                        $userTyposcriptStyle = 'style="display:none;"';
                        $defaultTyposcriptStyle = '';
                    }
                    $deleteIconHTML =
                        '<button type="button" class="btn btn-default t3js-toggle" data-bs-toggle="undo" rel="' . $idName . '">'
                        . '<span title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deleteTitle')) . '">'
                        . $this->iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL)->render()
                        . '</span>'
                        . '</button>';
                    $editIconHTML =
                        '<button type="button" class="btn btn-default t3js-toggle" data-bs-toggle="edit"  rel="' . $idName . '">'
                        . '<span title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editTitle')) . '">'
                        . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render()
                        . '</span>'
                        . '</button>';
                    $constantCheckbox = '<input type="hidden" name="check[' . $params['name'] . ']" id="check-' . $idName . '" value="' . $checkboxValue . '"/>';
                    // If there's no default value for the field, use a static label.
                    if (!$params['default_value']) {
                        $params['default_value'] = '[Empty]';
                    }
                    $constantDefaultRow =
                        '<div class="input-group defaultTS" id="' . $defaultTyposcriptID . '" ' . $defaultTyposcriptStyle . '>'
                        . '<span class="input-group-btn">' . $editIconHTML . '</span>'
                        . '<input class="form-control" type="text" placeholder="' . htmlspecialchars($params['default_value']) . '" readonly>'
                        . '</div>';
                    $constantEditRow =
                        '<div class="input-group userTS" id="' . $userTyposcriptID . '" ' . $userTyposcriptStyle . '>'
                        . '<span class="input-group-btn">' . $deleteIconHTML . '</span>'
                        . $p_field
                        . '</div>';
                    $constantData =
                        $constantCheckbox
                        . $constantEditRow
                        . $constantDefaultRow;

                    $groupedOutput[$categoryLoop]['items'][] = [
                        'identifier' => $fragmentName,
                        'label' => $head,
                        'name' => $params['name'],
                        'description' => $body,
                        'hint' => $hint,
                        'data' => $constantData,
                    ];
                } else {
                    debug('Error. Constant did not exist. Should not happen.');
                }
            }
        }
        return $groupedOutput;
    }

    protected function fNandV(array $params): array
    {
        $fN = 'data[' . $params['name'] . ']';
        $idName = str_replace('.', '-', $params['name']);
        $fV = $params['value'];
        // Values entered from the constants edit cannot be constants!	230502; removed \{ and set {
        if (preg_match('/^{[\\$][a-zA-Z0-9\\.]*}$/', trim($fV), $reg)) {
            $fV = '';
        }
        $fV = htmlspecialchars($fV);
        return [$fN, $fV, $params, $idName];
    }

    protected function categorizeEditableConstants(array $categories, array $editConstArray): array
    {
        // Runs through the available constants and fills the categories array with pointers and priority-info
        foreach ($editConstArray as $constName => $constData) {
            if (!$constData['type']) {
                $constData['type'] = 'string';
            }
            $cats = explode(',', $constData['cat']);
            // if = only one category, while allows for many. We have agreed on only one category is the most basic way...
            foreach ($cats as $theCat) {
                $theCat = trim($theCat);
                if ($theCat) {
                    $categories[$theCat][$constName] = $constData['subcat'];
                }
            }
        }
        return $categories;
    }

    protected function getCategoryLabels(array $categories): array
    {
        // Returns array used for labels in the menu.
        $retArr = [];
        foreach ($categories as $k => $v) {
            if (!empty($v)) {
                $retArr[$k] = strtoupper($k) . ' (' . count($v) . ')';
            }
        }
        return $retArr;
    }

    protected function addSaveButtonToDocHeader(ModuleTemplate $view, ModuleData $moduleData, int $pageId): void
    {
        $languageService = $this->getLanguageService();
        if ($pageId && !empty($moduleData->get('constant_editor_cat'))) {
            $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
            $saveButton = $buttonBar->makeInputButton()
                ->setName('_savedok')
                ->setValue('1')
                ->setForm('TypoScriptConstantEditorController')
                ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
                ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
                ->setShowLabelText(true);
            $buttonBar->addButton($saveButton);
        }
    }
}
