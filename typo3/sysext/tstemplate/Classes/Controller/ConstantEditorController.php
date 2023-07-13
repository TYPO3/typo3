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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilderInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\AST\Traverser\AstTraverser;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstConstantCommentVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeCommentAwareAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * TypoScript Constant editor
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class ConstantEditorController extends AbstractTemplateModuleController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly SysTemplateRepository $sysTemplateRepository,
        private readonly SysTemplateTreeBuilder $treeBuilder,
        private readonly IncludeTreeTraverser $treeTraverser,
        private readonly AstTraverser $astTraverser,
        private readonly AstBuilderInterface $astBuilder,
        private readonly LosslessTokenizer $losslessTokenizer,
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $pageUid = (int)($queryParams['id'] ?? 0);
        if ($pageUid === 0) {
            // Redirect to template record overview if on page 0.
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }

        if (($parsedBody['action'] ?? '') === 'createExtensionTemplate') {
            return $this->createExtensionTemplateAction($request, 'web_typoscript_constanteditor');
        }
        if (($parsedBody['action'] ?? '') === 'createNewWebsiteTemplate') {
            return $this->createNewWebsiteTemplateAction($request, 'web_typoscript_constanteditor');
        }
        if (($parsedBody['_savedok'] ?? false) === '1') {
            return $this->saveAction($request);
        }

        $pageUid = (int)($queryParams['id'] ?? 0);
        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageUid);
        if (empty($allTemplatesOnPage)) {
            return $this->noTemplateAction($request);
        }

        return $this->showAction($request);
    }

    private function showAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();

        $pageUid = (int)($queryParams['id'] ?? 0);

        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $moduleData = $request->getAttribute('moduleData');
        if ($moduleData->cleanUp([])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        if (empty($pageRecord)) {
            // Redirect to records overview if page could not be determined.
            // Edge case if page has been removed meanwhile.
            BackendUtility::setUpdateSignal('updatePageTree');
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }

        // Template selection handling for this page
        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageUid);
        $selectedTemplateFromModuleData = (array)$moduleData->get('selectedTemplatePerPage');
        $selectedTemplateUid = (int)($parsedBody['selectedTemplate'] ?? $selectedTemplateFromModuleData[$pageUid] ?? 0);
        if (!in_array($selectedTemplateUid, array_column($allTemplatesOnPage, 'uid'))) {
            $selectedTemplateUid = (int)($allTemplatesOnPage[0]['uid'] ?? 0);
        }
        if (($moduleData->get('selectedTemplatePerPage')[$pageUid] ?? 0) !== $selectedTemplateUid) {
            $selectedTemplateFromModuleData[$pageUid] = $selectedTemplateUid;
            $moduleData->set('selectedTemplatePerPage', $selectedTemplateFromModuleData);
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $templateTitle = '';
        $currentTemplateConstants = '';
        foreach ($allTemplatesOnPage as $templateRow) {
            if ((int)$templateRow['uid'] === $selectedTemplateUid) {
                $templateTitle = $templateRow['title'];
                $currentTemplateConstants = $templateRow['constants'] ?? '';
            }
        }

        // Build the constant include tree
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();
        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootlineWithUidOverride($rootLine, $request, $selectedTemplateUid);
        $site = $request->getAttribute('site');
        $constantIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRows, $this->losslessTokenizer, $site);
        $constantAstBuilderVisitor = GeneralUtility::makeInstance(IncludeTreeCommentAwareAstBuilderVisitor::class);
        $this->treeTraverser->traverse($constantIncludeTree, [$constantAstBuilderVisitor]);
        $constantAst = $constantAstBuilderVisitor->getAst();
        $astConstantCommentVisitor = GeneralUtility::makeInstance(AstConstantCommentVisitor::class);
        $currentTemplateFlatConstants = $this->astBuilder->build($this->losslessTokenizer->tokenize($currentTemplateConstants), new RootNode())->flatten();
        $astConstantCommentVisitor->setCurrentTemplateFlatConstants($currentTemplateFlatConstants);
        $this->astTraverser->traverse($constantAst, [$astConstantCommentVisitor]);

        $constants = $astConstantCommentVisitor->getConstants();
        $categories = $astConstantCommentVisitor->getCategories();

        $relevantCategories = [];
        foreach ($categories as $categoryKey => $aCategory) {
            if ($aCategory['usageCount'] > 0) {
                $relevantCategories[$categoryKey] = $aCategory;
            }
        }
        $selectedCategory = array_key_first($relevantCategories) ?? '';
        $selectedCategoryFromModuleData = (string)$moduleData->get('selectedCategory');
        if (array_key_exists($selectedCategoryFromModuleData, $relevantCategories)) {
            $selectedCategory = $selectedCategoryFromModuleData;
        }
        if (($parsedBody['selectedCategory'] ?? '') && array_key_exists($parsedBody['selectedCategory'], $relevantCategories)) {
            $selectedCategory = (string)$parsedBody['selectedCategory'];
        }
        if ($selectedCategory && $selectedCategory !== $selectedCategoryFromModuleData) {
            $moduleData->set('selectedCategory', $selectedCategory);
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $displayConstants = [];
        foreach ($constants as $constant) {
            if ($constant['cat'] === $selectedCategory) {
                $displayConstants[$constant['subcat_sorting_first']]['label'] = $constant['subcat_label'];
                $displayConstants[$constant['subcat_sorting_first']]['items'][$constant['subcat_sorting_second']] = $constant;
            }
        }
        ksort($displayConstants);
        foreach ($displayConstants as &$constant) {
            ksort($constant['items']);
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addPreviewButtonToDocHeader($view, $pageUid, (int)$pageRecord['doktype']);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageUid);
        if (!empty($relevantCategories)) {
            $this->addSaveButtonToDocHeader($view);
        }
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'templateTitle' => $templateTitle,
            'pageUid' => $pageUid,
            'allTemplatesOnPage' => $allTemplatesOnPage,
            'selectedTemplateUid' => $selectedTemplateUid,
            'relevantCategories' => $relevantCategories,
            'selectedCategory' => $selectedCategory,
            'displayConstants' => $displayConstants,
        ]);
        return $view->renderResponse('ConstantEditorMain');
    }

    private function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $moduleData = $request->getAttribute('moduleData');

        $pageUid = (int)($queryParams['id'] ?? 0);
        if ($pageUid === 0) {
            throw new \RuntimeException('No proper page uid given', 1661333862);
        }

        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageUid);
        $selectedTemplateFromModuleData = (array)$moduleData->get('selectedTemplatePerPage');
        $selectedTemplateUid = (int)($selectedTemplateFromModuleData[$pageUid] ?? 0);
        $templateRow = null;
        foreach ($allTemplatesOnPage as $template) {
            if ($selectedTemplateUid === (int)$template['uid']) {
                $templateRow = $template;
            }
        }
        if (!in_array($selectedTemplateUid, array_column($allTemplatesOnPage, 'uid'))) {
            $templateRow = $allTemplatesOnPage[0] ?? [];
            $selectedTemplateUid = (int)($templateRow['uid'] ?? 0);
        }
        if ($selectedTemplateUid < 1) {
            throw new \RuntimeException('No template found on page', 1661350211);
        }

        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();
        $site = $request->getAttribute('site');
        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootlineWithUidOverride($rootLine, $request, $selectedTemplateUid);
        $constantIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRows, $this->losslessTokenizer, $site);
        $constantAstBuilderVisitor = GeneralUtility::makeInstance(IncludeTreeCommentAwareAstBuilderVisitor::class);
        $this->treeTraverser->traverse($constantIncludeTree, [$constantAstBuilderVisitor]);
        $constantAst = $constantAstBuilderVisitor->getAst();
        $astConstantCommentVisitor = GeneralUtility::makeInstance(AstConstantCommentVisitor::class);
        $this->astTraverser->traverse($constantAst, [$astConstantCommentVisitor]);

        $constants = $astConstantCommentVisitor->getConstants();
        $updatedTemplateConstantsArray = $this->updateTemplateConstants($request, $constants, $templateRow['constants'] ?? '');
        if ($updatedTemplateConstantsArray) {
            $templateUid = empty($templateRow['_ORIG_uid']) ? $templateRow['uid'] : $templateRow['_ORIG_uid'];
            $recordData = [];
            $recordData['sys_template'][$templateUid]['constants'] = implode(LF, $updatedTemplateConstantsArray);
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($recordData, []);
            $dataHandler->process_datamap();
        }

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_constanteditor', ['id' => $pageUid]));
    }

    private function noTemplateAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $pageUid = (int)($request->getQueryParams()['id'] ?? 0);
        if ($pageUid === 0) {
            throw new \RuntimeException('No proper page uid given', 1661365944);
        }
        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        if (empty($pageRecord)) {
            // Redirect to records overview if page could not be determined.
            // Edge case if page has been removed meanwhile.
            BackendUtility::setUpdateSignal('updatePageTree');
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addPreviewButtonToDocHeader($view, $pageUid, (int)$pageRecord['doktype']);
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'pageUid' => $pageUid,
            'moduleIdentifier' => $currentModuleIdentifier,
            'previousPage' => $this->getClosestAncestorPageWithTemplateRecord($pageUid),
        ]);
        return $view->renderResponse('ConstantEditorNoTemplate');
    }

    private function updateTemplateConstants(ServerRequestInterface $request, array $constantDefinitions, string $rawTemplateConstants): ?array
    {
        $rawTemplateConstantsArray = explode(LF, $rawTemplateConstants);
        $constantPositions = $this->calculateConstantPositions($rawTemplateConstantsArray);

        $parsedBody = $request->getParsedBody();
        $data = $parsedBody['data'] ?? null;
        $check = $parsedBody['check'] ?? [];

        $valuesHaveChanged = false;
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (!isset($constantDefinitions[$key])) {
                    // Ignore if there is no constant definition for this constant key
                    continue;
                }
                if (!isset($check[$key]) || ($check[$key] !== 'checked' && isset($constantPositions[$key]))) {
                    // Remove value if the checkbox is not set, indicating "value to be dropped from template"
                    $rawTemplateConstantsArray = $this->removeValueFromConstantsArray($rawTemplateConstantsArray, $constantPositions, $key);
                    $valuesHaveChanged = true;
                    continue;
                }
                if ($check[$key] !== 'checked') {
                    // Don't process if this value is not set
                    continue;
                }
                $constantDefinition = $constantDefinitions[$key];
                switch ($constantDefinition['type']) {
                    case 'int':
                        $min = $constantDefinition['typeIntMin'] ?? PHP_INT_MIN;
                        $max = $constantDefinition['typeIntMax'] ?? PHP_INT_MAX;
                        $value = (string)MathUtility::forceIntegerInRange((int)$value, (int)$min, (int)$max);
                        break;
                    case 'int+':
                        $min = $constantDefinition['typeIntMin'] ?? 0;
                        $max = $constantDefinition['typeIntMax'] ?? PHP_INT_MAX;
                        $value = (string)MathUtility::forceIntegerInRange((int)$value, (int)$min, (int)$max);
                        break;
                    case 'color':
                        $col = [];
                        if ($value) {
                            $value = preg_replace('/[^A-Fa-f0-9]*/', '', $value) ?? '';
                            $useFulHex = strlen($value) > 3;
                            $col[] = (int)hexdec($value[0]);
                            $col[] = (int)hexdec($value[1]);
                            $col[] = (int)hexdec($value[2]);
                            if ($useFulHex) {
                                $col[] = (int)hexdec($value[3]);
                                $col[] = (int)hexdec($value[4]);
                                $col[] = (int)hexdec($value[5]);
                            }
                            $value = substr('0' . dechex($col[0]), -1) . substr('0' . dechex($col[1]), -1) . substr('0' . dechex($col[2]), -1);
                            if ($useFulHex) {
                                $value .= substr('0' . dechex($col[3]), -1) . substr('0' . dechex($col[4]), -1) . substr('0' . dechex($col[5]), -1);
                            }
                            $value = '#' . strtoupper($value);
                        }
                        break;
                    case 'comment':
                        if ($value) {
                            $value = '';
                        } else {
                            $value = '#';
                        }
                        break;
                    case 'wrap':
                        if (($data[$key]['left'] ?? false) || $data[$key]['right']) {
                            $value = $data[$key]['left'] . '|' . $data[$key]['right'];
                        } else {
                            $value = '';
                        }
                        break;
                    case 'offset':
                        $value = rtrim(implode(',', $value), ',');
                        if (trim($value, ',') === '') {
                            $value = '';
                        }
                        break;
                    case 'boolean':
                        if ($value) {
                            $value = ($constantDefinition['trueValue'] ?? false) ?: '1';
                        }
                        break;
                }
                if ((string)($constantDefinition['value'] ?? '') !== (string)$value) {
                    // Put value in, if changed.
                    $rawTemplateConstantsArray = $this->addOrUpdateValueInConstantsArray($rawTemplateConstantsArray, $constantPositions, $key, $value);
                    $valuesHaveChanged = true;
                }
            }
        }
        if ($valuesHaveChanged) {
            return $rawTemplateConstantsArray;
        }
        return null;
    }

    private function calculateConstantPositions(
        array $rawTemplateConstantsArray,
        array &$constantPositions = [],
        string $prefix = '',
        int $braceLevel = 0,
        int &$lineCounter = 0
    ): array {
        while (isset($rawTemplateConstantsArray[$lineCounter])) {
            $line = ltrim($rawTemplateConstantsArray[$lineCounter]);
            $lineCounter++;
            if (!$line || $line[0] === '[') {
                // Ignore empty lines and conditions
                continue;
            }
            if (strcspn($line, '}#/') !== 0) {
                $operatorPosition = strcspn($line, ' {=<');
                $key = substr($line, 0, $operatorPosition);
                $line = ltrim(substr($line, $operatorPosition));
                if ($line[0] === '=') {
                    $constantPositions[$prefix . $key] = $lineCounter - 1;
                } elseif ($line[0] === '{') {
                    $braceLevel++;
                    $this->calculateConstantPositions($rawTemplateConstantsArray, $constantPositions, $prefix . $key . '.', $braceLevel, $lineCounter);
                }
            } elseif ($line[0] === '}') {
                $braceLevel--;
                if ($braceLevel < 0) {
                    $braceLevel = 0;
                } else {
                    // Leaving this brace level: Force return to caller recursion
                    break;
                }
            }
        }
        return $constantPositions;
    }

    /**
     * Update a constant value in current template constants if key exists already,
     * or add key/value at the end if it does not exist yet.
     */
    private function addOrUpdateValueInConstantsArray(array $templateConstantsArray, array $constantPositions, string $constantKey, string $value): array
    {
        $theValue = ' ' . trim($value);
        if (isset($constantPositions[$constantKey])) {
            $lineNum = $constantPositions[$constantKey];
            $parts = explode('=', $templateConstantsArray[$lineNum], 2);
            if (count($parts) === 2) {
                $parts[1] = $theValue;
            }
            $templateConstantsArray[$lineNum] = implode('=', $parts);
        } else {
            $templateConstantsArray[] = $constantKey . ' =' . $theValue;
        }
        return $templateConstantsArray;
    }

    /**
     * Remove a key from constant array.
     */
    private function removeValueFromConstantsArray(array $templateConstantsArray, array $constantPositions, string $constantKey): array
    {
        if (isset($constantPositions[$constantKey])) {
            $lineNum = $constantPositions[$constantKey];
            unset($templateConstantsArray[$lineNum]);
        }
        return $templateConstantsArray;
    }

    private function addSaveButtonToDocHeader(ModuleTemplate $view): void
    {
        $languageService = $this->getLanguageService();
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

    private function addShortcutButtonToDocHeader(ModuleTemplate $view, string $moduleIdentifier, array $pageInfo, int $pageUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutTitle = sprintf(
            '%s: %s [%d]',
            $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_ceditor.xlf:submodule.title'),
            BackendUtility::getRecordTitle('pages', $pageInfo),
            $pageUid
        );
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($moduleIdentifier)
            ->setDisplayName($shortcutTitle)
            ->setArguments(['id' => $pageUid]);
        $buttonBar->addButton($shortcutButton);
    }
}
