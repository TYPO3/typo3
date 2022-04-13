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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\Traverser\AstTraverser;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstSortChildrenVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\ConditionVerdictAwareIncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSetupConditionConstantSubstitutionVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Tstemplate\TypoScript\AST\Visitor\AstExpandStateVisitor;
use TYPO3\CMS\Tstemplate\TypoScript\AST\Visitor\AstSearchVisitor;
use TYPO3\CMS\Tstemplate\TypoScript\IncludeTree\Visitor\IncludeTreeConditionAggregatorVisitor;
use TYPO3\CMS\Tstemplate\TypoScript\IncludeTree\Visitor\IncludeTreeConditionEnforcerVisitor;

/**
 * This class displays the submodule "TypoScript Object Browser" inside the Web > Template module
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
final class ObjectBrowserController extends AbstractTemplateModuleController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly FlashMessageService $flashMessageService,
        private readonly IncludeTreeTraverser $treeTraverser,
        private readonly ConditionVerdictAwareIncludeTreeTraverser $treeTraverserConditionVerdictAware,
        private readonly TreeBuilder $treeBuilder,
        private readonly AstTraverser $astTraverser,
        LosslessTokenizer $losslessTokenizer,
    ) {
        $this->treeBuilder->setTokenizer($losslessTokenizer);
    }

    /**
     * Main public action dispatcher.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if (($request->getQueryParams()['action'] ?? '') === 'edit') {
            return $this->editAction($request);
        }
        if (($request->getParsedBody()['action'] ?? '') === 'update') {
            return $this->updateAction($request);
        }
        return $this->showAction($request);
    }

    /**
     * Default view renders options, constant and setup conditions, constant and setup tree.
     */
    private function showAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();
        $moduleData = $request->getAttribute('moduleData');

        $pageUid = (int)($queryParams['id'] ?? 0);
        if ($pageUid === 0) {
            // Redirect to template record overview if on page 0.
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }
        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();

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
        foreach ($allTemplatesOnPage as $templateRow) {
            if ((int)$templateRow['uid'] === $selectedTemplateUid) {
                $templateTitle = $templateRow['title'];
            }
        }

        // Force boolean toggles to bool and init further get/post vars
        if ($moduleData->clean('sortAlphabetically', [true, false])) {
            $this->getBackendUser()->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $sortAlphabetically = $moduleData->get('sortAlphabetically');
        if ($moduleData->clean('displayConstantSubstitutions', [true, false])) {
            $this->getBackendUser()->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $displayConstantSubstitutions = $moduleData->get('displayConstantSubstitutions');
        $searchValue = $moduleData->get('searchValue');

        // Build the constant include tree
        $constantIncludeTree = $this->treeBuilder->getTreeByRootline($rootLine, 'constants', false, $selectedTemplateUid);
        // Set enabled conditions in constant include tree
        $constantConditions = $this->handleToggledConstantConditions($constantIncludeTree, $moduleData, $parsedBody);
        $conditionEnforcerVisitor = GeneralUtility::makeInstance(IncludeTreeConditionEnforcerVisitor::class);
        $conditionEnforcerVisitor->setEnabledConditions(array_column(array_filter($constantConditions, static fn ($condition) => $condition['active']), 'value'));
        $this->treeTraverser->resetVisitors();
        $this->treeTraverser->addVisitor($conditionEnforcerVisitor);
        $this->treeTraverser->traverse($constantIncludeTree);
        // Build the constant AST
        $constantAstBuilderVisitor = GeneralUtility::makeInstance(IncludeTreeAstBuilderVisitor::class);
        $this->treeTraverserConditionVerdictAware->resetVisitors();
        $this->treeTraverserConditionVerdictAware->addVisitor($constantAstBuilderVisitor);
        $this->treeTraverserConditionVerdictAware->traverse($constantIncludeTree);
        $constantAst = $constantAstBuilderVisitor->getAst();
        // Constant expand & collapse handling with and without search value
        if (($parsedBody['constantExpand'] ?? false) || ($parsedBody['constantCollapse'] ?? false) || empty($searchValue)) {
            // Apply expanded & collapsed state to AST
            $astExpandStateVisitor = GeneralUtility::makeInstance(AstExpandStateVisitor::class);
            $astExpandStateVisitor->setToExpandPath($parsedBody['constantExpand'] ?? '');
            $astExpandStateVisitor->setToCollapsePath($parsedBody['constantCollapse'] ?? '');
            $astExpandStateVisitor->setStoredExpands($moduleData->get('constantExpandState'));
            $this->astTraverser->resetVisitors();
            $this->astTraverser->addVisitor($astExpandStateVisitor);
            if ($sortAlphabetically) {
                $this->astTraverser->addVisitor(GeneralUtility::makeInstance(AstSortChildrenVisitor::class));
            }
            $this->astTraverser->traverse($constantAst);
            if (($parsedBody['constantExpand'] ?? false) || ($parsedBody['constantCollapse'] ?? false)) {
                // Reset search word if expanding / collapsing after a search happened
                $searchValue = '';
                $moduleData->set('searchValue', $searchValue);
                // Persist updated expand / collapsed state if needed
                $updatedStoredExpands = $astExpandStateVisitor->getStoredExpands();
                $moduleData->set('constantExpandState', $updatedStoredExpands);
                $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
            }
        } else {
            $astSearchVisitor = GeneralUtility::makeInstance(AstSearchVisitor::class);
            $astSearchVisitor->setSearchValue($searchValue);
            $this->astTraverser->resetVisitors();
            $this->astTraverser->addVisitor($astSearchVisitor);
            if ($sortAlphabetically) {
                $this->astTraverser->addVisitor(GeneralUtility::makeInstance(AstSortChildrenVisitor::class));
            }
            $this->astTraverser->traverse($constantAst);
            // Persist updated expand / collapsed state
            $updatedStoredExpands = $astSearchVisitor->getStoredExpands();
            $moduleData->set('constantExpandState', $updatedStoredExpands);
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        // Flatten constant AST. Needed for setup condition display and setup AST constant substitution.
        $flattenedConstants = $constantAst->flatten();
        // Build the setup include tree, note this uses the 'cached' variant from the constant run above to suppress db calls.
        $setupIncludeTree = $this->treeBuilder->getTreeByRootline($rootLine, 'setup', true);
        // Set enabled conditions in setup include tree and let it handle constant substitutions in setup conditions.
        $setupConditions = $this->handleToggledSetupConditions($setupIncludeTree, $moduleData, $parsedBody, $flattenedConstants);
        $conditionEnforcerVisitor = GeneralUtility::makeInstance(IncludeTreeConditionEnforcerVisitor::class);
        $conditionEnforcerVisitor->setEnabledConditions(array_column(array_filter($setupConditions, static fn ($condition) => $condition['active']), 'value'));
        $this->treeTraverser->resetVisitors();
        $this->treeTraverser->addVisitor($conditionEnforcerVisitor);
        $this->treeTraverser->traverse($setupIncludeTree);
        // Build the setup AST
        $setupAstBuilderVisitor = GeneralUtility::makeInstance(IncludeTreeAstBuilderVisitor::class);
        $setupAstBuilderVisitor->setFlatConstants($flattenedConstants);
        $this->treeTraverserConditionVerdictAware->resetVisitors();
        $this->treeTraverserConditionVerdictAware->addVisitor($setupAstBuilderVisitor);
        $this->treeTraverserConditionVerdictAware->traverse($setupIncludeTree);
        $setupAst = $setupAstBuilderVisitor->getAst();
        // Setup expand & collapse handling with and without search value
        if (($parsedBody['setupExpand'] ?? false) || ($parsedBody['setupCollapse'] ?? false) || empty($searchValue)) {
            // Apply expanded & collapsed state to AST
            $astExpandStateVisitor = GeneralUtility::makeInstance(AstExpandStateVisitor::class);
            $astExpandStateVisitor->setToExpandPath($parsedBody['setupExpand'] ?? '');
            $astExpandStateVisitor->setToCollapsePath($parsedBody['setupCollapse'] ?? '');
            $astExpandStateVisitor->setStoredExpands((array)$moduleData->get('setupExpandState'));
            $this->astTraverser->resetVisitors();
            $this->astTraverser->addVisitor($astExpandStateVisitor);
            if ($sortAlphabetically) {
                $this->astTraverser->addVisitor(GeneralUtility::makeInstance(AstSortChildrenVisitor::class));
            }
            $this->astTraverser->traverse($setupAst);
            if (($parsedBody['setupExpand'] ?? false) || ($parsedBody['setupCollapse'] ?? false)) {
                // Reset search word if expanding / collapsing after a search happened
                $searchValue = '';
                $moduleData->set('searchValue', $searchValue);
                // Persist updated expand / collapsed state if needed
                $updatedStoredExpands = $astExpandStateVisitor->getStoredExpands();
                $moduleData->set('setupExpandState', $updatedStoredExpands);
                $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
            }
        } else {
            $astSearchVisitor = GeneralUtility::makeInstance(AstSearchVisitor::class);
            $astSearchVisitor->setSearchValue($searchValue);
            if ($displayConstantSubstitutions) {
                $astSearchVisitor->enableSearchInConstants();
            }
            $this->astTraverser->resetVisitors();
            $this->astTraverser->addVisitor($astSearchVisitor);
            if ($sortAlphabetically) {
                $this->astTraverser->addVisitor(GeneralUtility::makeInstance(AstSortChildrenVisitor::class));
            }
            $this->astTraverser->traverse($setupAst);
            // Persist updated expand / collapsed state
            $updatedStoredExpands = $astSearchVisitor->getStoredExpands();
            $moduleData->set('setupExpandState', $updatedStoredExpands);
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addPreviewButtonToDocHeader($view, $pageUid, (int)$pageRecord['doktype']);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageUid);
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'templateTitle' => $templateTitle,
            'selectedTemplateUid' => $selectedTemplateUid,
            'pageUid' => $pageUid,
            'allTemplatesOnPage' => $allTemplatesOnPage,
            'searchValue' => $searchValue,
            'sortAlphabetically' => $sortAlphabetically,
            'displayConstantSubstitutions' => $displayConstantSubstitutions,
            'constantCurrentObjectPath' => new CurrentObjectPath(),
            'constantConditions' => $constantConditions,
            'constantConditionsActiveCount' => count(array_filter($constantConditions, static fn ($condition) => $condition['active'])),
            'constantAst' => $constantAst,
            'setupCurrentObjectPath' => new CurrentObjectPath(),
            'setupConditions' => $setupConditions,
            'setupConditionsActiveCount' => count(array_filter($setupConditions, static fn ($condition) => $condition['active'])),
            'setupAst' => $setupAst,
        ]);

        return $view->renderResponse('ObjectBrowserMain');
    }

    /**
     * Edit a single property. Linked from "show" view when clicking a property.
     */
    private function editAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $queryParams = $request->getQueryParams();
        $currentModule = $request->getAttribute('module');
        $moduleData = $request->getAttribute('moduleData');
        $pageUid = (int)($queryParams['id'] ?? 0);
        $type = $queryParams['type'] ?? '';
        $currentObjectPath = $queryParams['currentObjectPath'] ?? '';

        if (empty($pageUid) || !in_array($type, ['constant', 'setup']) || empty($currentObjectPath)) {
            throw new \RuntimeException('Required action argument missing or invalid', 1658562276);
        }

        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();

        // Template selection handling
        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageUid);
        $selectedTemplateFromModuleData = (array)$moduleData->get('selectedTemplatePerPage');
        $selectedTemplateUid = (int)($selectedTemplateFromModuleData[$pageUid] ?? 0);
        if (!in_array($selectedTemplateUid, array_column($allTemplatesOnPage, 'uid'))) {
            $selectedTemplateUid = (int)($allTemplatesOnPage[0]['uid'] ?? 0);
        }

        $hasTemplate = false;
        $templateTitle = '';
        foreach ($allTemplatesOnPage as $templateRow) {
            if ((int)$templateRow['uid'] === $selectedTemplateUid) {
                $hasTemplate = true;
                $templateTitle = $templateRow['title'];
            }
        }

        // Get current value of to-edit object path
        // Build the constant include tree
        $constantIncludeTree = $this->treeBuilder->getTreeByRootline($rootLine, 'constants', false, $selectedTemplateUid);
        // Set enabled conditions in constant include tree
        $constantConditions = $this->handleToggledConstantConditions($constantIncludeTree, $moduleData, null);
        $conditionEnforcerVisitor = GeneralUtility::makeInstance(IncludeTreeConditionEnforcerVisitor::class);
        $conditionEnforcerVisitor->setEnabledConditions(array_column(array_filter($constantConditions, static fn ($condition) => $condition['active']), 'value'));
        $this->treeTraverser->resetVisitors();
        $this->treeTraverser->addVisitor($conditionEnforcerVisitor);
        $this->treeTraverser->traverse($constantIncludeTree);
        // Build the constant AST
        $constantAstBuilderVisitor = GeneralUtility::makeInstance(IncludeTreeAstBuilderVisitor::class);
        $this->treeTraverserConditionVerdictAware->resetVisitors();
        $this->treeTraverserConditionVerdictAware->addVisitor($constantAstBuilderVisitor);
        $this->treeTraverserConditionVerdictAware->traverse($constantIncludeTree);
        $flattenedConstants = $constantAstBuilderVisitor->getAst()->flatten();
        if ($type === 'constant') {
            $currentValue = $flattenedConstants[$currentObjectPath] ?? '';
        } else {
            // Build the setup include tree
            $setupIncludeTree = $this->treeBuilder->getTreeByRootline($rootLine, 'setup', false, $selectedTemplateUid);
            // Set enabled conditions in setup include tree
            $setupConditions = $this->handleToggledSetupConditions($setupIncludeTree, $moduleData, null, $flattenedConstants);
            $conditionEnforcerVisitor = GeneralUtility::makeInstance(IncludeTreeConditionEnforcerVisitor::class);
            $conditionEnforcerVisitor->setEnabledConditions(array_column(array_filter($setupConditions, static fn ($condition) => $condition['active']), 'value'));
            $this->treeTraverser->resetVisitors();
            $this->treeTraverser->addVisitor($conditionEnforcerVisitor);
            $this->treeTraverser->traverse($setupIncludeTree);
            // Build the setup AST
            $setupAstBuilderVisitor = GeneralUtility::makeInstance(IncludeTreeAstBuilderVisitor::class);
            $setupAstBuilderVisitor->setFlatConstants($flattenedConstants);
            $this->treeTraverserConditionVerdictAware->resetVisitors();
            $this->treeTraverserConditionVerdictAware->addVisitor($setupAstBuilderVisitor);
            $this->treeTraverserConditionVerdictAware->traverse($setupIncludeTree);
            $flattenedSetup = $setupAstBuilderVisitor->getAst()->flatten();
            $currentValue = $flattenedSetup[$currentObjectPath] ?? '';
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addBackButtonToDocHeader($view, $pageUid);
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'pageUid' => $pageUid,
            'hasTemplate' => $hasTemplate,
            'templateTitle' => $templateTitle,
            'type' => $type,
            'currentObjectPath' => $currentObjectPath,
            'currentValue' => $currentValue,
        ]);

        return $view->renderResponse('ObjectBrowserEdit');
    }

    /**
     * Add a line to selected sys_template record of given page after editing or clearing a
     * property or adding a child in 'edit' view. Update either 'constants' or 'config' field
     * using DataHandler, add a flash message and redirect to default "show" action.
     */
    private function updateAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $moduleData = $request->getAttribute('moduleData');
        $parsedBody = $request->getParsedBody();
        $pageUid = (int)($parsedBody['pageUid'] ?? 0);
        $type = $parsedBody['type'] ?? '';
        $currentObjectPath = $parsedBody['currentObjectPath'] ?? '';

        $command = null;
        if (isset($parsedBody['updateValue'])) {
            $command = 'updateValue';
        } elseif (isset($parsedBody['addChild'])) {
            $command = 'addChild';
        } elseif (isset($parsedBody['clear'])) {
            $command = 'clear';
        }

        if (empty($pageUid) || !in_array($type, ['constant', 'setup']) || empty($currentObjectPath) || empty($command)) {
            throw new \RuntimeException('Required action argument missing or invalid', 1658568446);
        }

        // Template selection handling
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
            throw new \RuntimeException('No template on page found', 1658568794);
        }

        $newLine = null;
        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        switch ($command) {
            case 'updateValue':
                $newLine = $currentObjectPath . ' = ' . trim($parsedBody['value'] ?? '');
                break;
            case 'addChild':
                $childName = str_replace('\\', '', $parsedBody['childName'] ?? '');
                if (empty($childName) || preg_replace('/[^a-zA-Z0-9_\.]*/', '', $childName) != $childName) {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:noSpaces'),
                        $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:lineNotAdded'),
                        ContextualFeedbackSeverity::WARNING,
                        true
                    );
                    $flashMessageQueue->enqueue($flashMessage);
                    break;
                }
                $childName = addcslashes($parsedBody['childName'] ?? '', '.');
                $childValue = trim($parsedBody['childValue'] ?? '');
                $newLine = $currentObjectPath . '.' . $childName . ' = ' . $childValue;
                break;
            case 'clear':
                $newLine = $currentObjectPath . ' >';
                break;
        }

        if ($newLine) {
            $fieldName = $type === 'constant' ? 'constants' : 'config';
            $recordData = [
                'sys_template' => [
                    $selectedTemplateUid => [
                        $fieldName => ($templateRow[$fieldName] ?? '') . LF . $newLine,
                    ],
                ],
            ];
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start($recordData, []);
            $dataHandler->process_datamap();
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $newLine,
                $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_objbrowser.xlf:lineAdded'),
                ContextualFeedbackSeverity::OK,
                true
            );
            $flashMessageQueue->enqueue($flashMessage);
        }

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_objectbrowser', ['id' => $pageUid]));
    }

    /**
     * Align module data active constant conditions with toggled conditions from POST,
     * write updated active conditions to user's module data if needed and
     * prepare a list of active conditions for view.
     */
    private function handleToggledConstantConditions(RootInclude $constantTree, ModuleData $moduleData, ?array $parsedBody): array
    {
        $conditionAggregatorVisitor = GeneralUtility::makeInstance(IncludeTreeConditionAggregatorVisitor::class);
        $this->treeTraverser->resetVisitors();
        $this->treeTraverser->addVisitor($conditionAggregatorVisitor);
        $this->treeTraverser->traverse($constantTree);
        $constantConditions = $conditionAggregatorVisitor->getConditions();
        $conditionsFromPost = $parsedBody['constantConditions'] ?? [];
        $conditionsFromModuleData = array_flip((array)$moduleData->get('constantConditions'));
        $typoscriptConditions = [];
        foreach ($constantConditions as $condition) {
            $conditionHash = sha1($condition['value']);
            $conditionActive = array_key_exists($conditionHash, $conditionsFromModuleData);
            // Note we're not feeding the post values directly to module data, but filter
            // them through available conditions to prevent polluting module data with
            // manipulated post values.
            if (($conditionsFromPost[$conditionHash] ?? null) === '0') {
                unset($conditionsFromModuleData[$conditionHash]);
                $conditionActive = false;
            } elseif (($conditionsFromPost[$conditionHash] ?? null) === '1') {
                $conditionsFromModuleData[$conditionHash] = true;
                $conditionActive = true;
            }
            $typoscriptConditions[] = [
                'value' => $condition['value'],
                'hash' => $conditionHash,
                'active' => $conditionActive,
            ];
        }
        if ($conditionsFromPost) {
            $moduleData->set('constantConditions', array_keys($conditionsFromModuleData));
            $this->getBackendUser()->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }
        return $typoscriptConditions;
    }

    /**
     * Align module data active setup conditions with toggled conditions from POST,
     * write updated active conditions to user's module data if needed and
     * prepare a list of active conditions for view.
     */
    private function handleToggledSetupConditions(RootInclude $constantTree, ModuleData $moduleData, ?array $parsedBody, array $flattenedConstants): array
    {
        $this->treeTraverser->resetVisitors();
        $setupConditionConstantSubstitutionVisitor = GeneralUtility::makeInstance(IncludeTreeSetupConditionConstantSubstitutionVisitor::class);
        $setupConditionConstantSubstitutionVisitor->setFlattenedConstants($flattenedConstants);
        $this->treeTraverser->addVisitor($setupConditionConstantSubstitutionVisitor);
        $conditionAggregatorVisitor = GeneralUtility::makeInstance(IncludeTreeConditionAggregatorVisitor::class);
        $this->treeTraverser->addVisitor($conditionAggregatorVisitor);
        $this->treeTraverser->traverse($constantTree);
        $setupConditions = $conditionAggregatorVisitor->getConditions();
        $conditionsFromPost = $parsedBody['setupConditions'] ?? [];
        $conditionsFromModuleData = array_flip((array)$moduleData->get('setupConditions'));
        $typoscriptConditions = [];
        foreach ($setupConditions as $condition) {
            $conditionHash = sha1($condition['value']);
            $conditionActive = array_key_exists($conditionHash, $conditionsFromModuleData);
            // Note we're not feeding the post values directly to module data, but filter
            // them through available conditions to prevent polluting module data with
            // manipulated post values.
            if (($conditionsFromPost[$conditionHash] ?? null) === '0') {
                unset($conditionsFromModuleData[$conditionHash]);
                $conditionActive = false;
            } elseif (($conditionsFromPost[$conditionHash] ?? null) === '1') {
                $conditionsFromModuleData[$conditionHash] = true;
                $conditionActive = true;
            }
            $typoscriptConditions[] = [
                'value' => $condition['value'],
                'originalValue' => $condition['originalValue'],
                'hash' => $conditionHash,
                'active' => $conditionActive,
            ];
        }
        if ($conditionsFromPost) {
            $moduleData->set('setupConditions', array_keys($conditionsFromModuleData));
            $this->getBackendUser()->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }
        return $typoscriptConditions;
    }

    private function addBackButtonToDocHeader(ModuleTemplate $view, int $pageUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $backButton = $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('web_typoscript_objectbrowser', ['id' => $pageUid]))
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
        $buttonBar->addButton($backButton);
    }
}
