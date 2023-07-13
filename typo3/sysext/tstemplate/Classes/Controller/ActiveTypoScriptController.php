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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
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
use TYPO3\CMS\Core\TypoScript\AST\Traverser\AstTraverser;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstNodeFinderVisitor;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstSortChildrenVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\ConditionVerdictAwareIncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeCommentAwareAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionAggregatorVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionEnforcerVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSetupConditionConstantSubstitutionVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * The "TypoScript -> Active TypoScript" Backend module
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
final class ActiveTypoScriptController extends AbstractTemplateModuleController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly FlashMessageService $flashMessageService,
        private readonly SysTemplateRepository $sysTemplateRepository,
        private readonly SysTemplateTreeBuilder $treeBuilder,
    ) {
    }

    /**
     * Default view renders options, constant and setup conditions, constant and setup tree.
     */
    public function indexAction(ServerRequestInterface $request): ResponseInterface
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
        if (empty($pageRecord)) {
            // Redirect to records overview if page could not be determined.
            // Edge case if page has been removed meanwhile.
            BackendUtility::setUpdateSignal('updatePageTree');
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }

        // @todo: Switch to BU::BEgetRootLine($pageUid, '', true) as in PageTsConfig? Similar in other controllers and actions.
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
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $sortAlphabetically = $moduleData->get('sortAlphabetically');
        if ($moduleData->clean('displayConstantSubstitutions', [true, false])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $displayConstantSubstitutions = $moduleData->get('displayConstantSubstitutions');
        if ($moduleData->clean('displayComments', [true, false])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $displayComments = $moduleData->get('displayComments');

        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootlineWithUidOverride($rootLine, $request, $selectedTemplateUid);

        // Build the constant include tree
        $site = $request->getAttribute('site');
        $constantIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRows, new LosslessTokenizer(), $site);
        // Set enabled conditions in constant include tree
        $constantConditions = $this->handleToggledConstantConditions($constantIncludeTree, $moduleData, $parsedBody);
        $conditionEnforcerVisitor = new IncludeTreeConditionEnforcerVisitor();
        $conditionEnforcerVisitor->setEnabledConditions(array_column(array_filter($constantConditions, static fn ($condition) => $condition['active']), 'value'));
        $treeTraverser = new ConditionVerdictAwareIncludeTreeTraverser();
        $treeTraverserVisitors = [];
        $treeTraverserVisitors[] = $conditionEnforcerVisitor;
        $constantAstBuilderVisitor = $this->container->get(IncludeTreeCommentAwareAstBuilderVisitor::class);
        $treeTraverserVisitors[] = $constantAstBuilderVisitor;
        $treeTraverser->traverse($constantIncludeTree, $treeTraverserVisitors);
        $constantAst = $constantAstBuilderVisitor->getAst();
        $constantAst->setIdentifier('TypoScript constants');
        if ($sortAlphabetically) {
            $astTraverser = new AstTraverser();
            $astTraverser->traverse($constantAst, [new AstSortChildrenVisitor()]);
        }

        // Flatten constant AST. Needed for setup condition display and setup AST constant substitution.
        $flattenedConstants = $constantAst->flatten();
        // Build the setup include tree
        $setupIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, new LosslessTokenizer(), $site);
        // Set enabled conditions in setup include tree and let it handle constant substitutions in setup conditions.
        $setupConditions = $this->handleToggledSetupConditions($setupIncludeTree, $moduleData, $parsedBody, $flattenedConstants);
        $conditionEnforcerVisitor = new IncludeTreeConditionEnforcerVisitor();
        $conditionEnforcerVisitor->setEnabledConditions(array_column(array_filter($setupConditions, static fn ($condition) => $condition['active']), 'value'));
        $treeTraverser = new ConditionVerdictAwareIncludeTreeTraverser();
        $treeTraverserVisitors = [];
        $treeTraverserVisitors[] = $conditionEnforcerVisitor;
        $setupAstBuilderVisitor = $this->container->get(IncludeTreeCommentAwareAstBuilderVisitor::class);
        $setupAstBuilderVisitor->setFlatConstants($flattenedConstants);
        $treeTraverserVisitors[] = $setupAstBuilderVisitor;
        $treeTraverser->traverse($setupIncludeTree, $treeTraverserVisitors);
        // Build the setup AST
        $setupAst = $setupAstBuilderVisitor->getAst();
        $setupAst->setIdentifier('TypoScript setup');
        if ($sortAlphabetically) {
            $astTraverser = new AstTraverser();
            $astTraverser->traverse($setupAst, [new AstSortChildrenVisitor()]);
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
            'sortAlphabetically' => $sortAlphabetically,
            'displayConstantSubstitutions' => $displayConstantSubstitutions,
            'displayComments' => $displayComments,
            'constantConditions' => $constantConditions,
            'constantConditionsActiveCount' => count(array_filter($constantConditions, static fn ($condition) => $condition['active'])),
            'constantAst' => $constantAst,
            'setupConditions' => $setupConditions,
            'setupConditionsActiveCount' => count(array_filter($setupConditions, static fn ($condition) => $condition['active'])),
            'setupAst' => $setupAst,
        ]);

        return $view->renderResponse('ActiveMain');
    }

    /**
     * Edit a single property. Linked from "show" view when clicking a property.
     */
    public function editAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $queryParams = $request->getQueryParams();
        $currentModule = $request->getAttribute('module');
        $moduleData = $request->getAttribute('moduleData');
        $pageUid = (int)($queryParams['id'] ?? 0);
        $type = $queryParams['type'] ?? '';
        $nodeIdentifier = $queryParams['nodeIdentifier'] ?? '';

        if (empty($pageUid) || !in_array($type, ['constant', 'setup']) || empty($nodeIdentifier)) {
            throw new \RuntimeException('Required action argument missing or invalid', 1658562276);
        }

        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        if (empty($pageRecord)) {
            // Redirect to records overview if page could not be determined.
            // Edge case if page has been removed meanwhile.
            BackendUtility::setUpdateSignal('updatePageTree');
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('web_typoscript_recordsoverview'));
        }

        // @todo: Switch to BU::BEgetRootLine($pageUid, '', true) as in PageTsConfig? Similar in other controllers and actions.
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

        $site = $request->getAttribute('site');
        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootlineWithUidOverride($rootLine, $request, $selectedTemplateUid);

        // Get current value of to-edit object path
        // Build the constant include tree
        $constantIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRows, new LosslessTokenizer(), $site);
        // Set enabled conditions in constant include tree
        $constantConditions = $this->handleToggledConstantConditions($constantIncludeTree, $moduleData, null);
        $conditionEnforcerVisitor = new IncludeTreeConditionEnforcerVisitor();
        $conditionEnforcerVisitor->setEnabledConditions(array_column(array_filter($constantConditions, static fn ($condition) => $condition['active']), 'value'));
        $treeTraverser = new ConditionVerdictAwareIncludeTreeTraverser();
        $treeTraverserVisitors = [];
        $treeTraverserVisitors[] = $conditionEnforcerVisitor;
        $constantAstBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
        $treeTraverserVisitors[] = $constantAstBuilderVisitor;
        $treeTraverser->traverse($constantIncludeTree, $treeTraverserVisitors);

        $astNodeFinderVisitor = new AstNodeFinderVisitor();
        $astNodeFinderVisitor->setNodeIdentifier($nodeIdentifier);
        if ($type === 'constant') {
            $constantAst = $constantAstBuilderVisitor->getAst();
            $constantAst->setIdentifier('TypoScript constants');
            $astTraverser = new AstTraverser();
            $astTraverser->traverse($constantAst, [$astNodeFinderVisitor]);
        } else {
            // Build the setup include tree
            $setupIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, new LosslessTokenizer(), $site);
            $flattenedConstants = $constantAstBuilderVisitor->getAst()->flatten();
            // Set enabled conditions in setup include tree
            $setupConditions = $this->handleToggledSetupConditions($setupIncludeTree, $moduleData, null, $flattenedConstants);
            $conditionEnforcerVisitor = new IncludeTreeConditionEnforcerVisitor();
            $conditionEnforcerVisitor->setEnabledConditions(array_column(array_filter($setupConditions, static fn ($condition) => $condition['active']), 'value'));
            $treeTraverser = new ConditionVerdictAwareIncludeTreeTraverser();
            $treeTraverserVisitors = [];
            $treeTraverserVisitors[] = $conditionEnforcerVisitor;
            $setupAstBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
            $setupAstBuilderVisitor->setFlatConstants($flattenedConstants);
            $treeTraverserVisitors[] = $setupAstBuilderVisitor;
            $treeTraverser->traverse($setupIncludeTree, $treeTraverserVisitors);
            $setupAst = $setupAstBuilderVisitor->getAst();
            $setupAst->setIdentifier('TypoScript setup');
            $astTraverser = new AstTraverser();
            $astTraverser->traverse($setupAst, [$astNodeFinderVisitor]);
        }
        $foundNode = $astNodeFinderVisitor->getFoundNode();
        $foundNodeCurrentObjectPath = $astNodeFinderVisitor->getFoundNodeCurrentObjectPath();

        if ($foundNode === null || $foundNodeCurrentObjectPath === null) {
            throw new \RuntimeException('Node with identifier ' . $nodeIdentifier . ' to edit not found', 1675241994);
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
            'currentObjectPath' => $foundNodeCurrentObjectPath->getPathAsString(),
            'currentValue' => $foundNode->getValue(),
        ]);

        return $view->renderResponse('ActiveEdit');
    }

    /**
     * Add a line to selected sys_template record of given page after editing or clearing a
     * property or adding a child in 'edit' view. Update either 'constants' or 'config' field
     * using DataHandler, add a flash message and redirect to default "show" action.
     */
    public function updateAction(ServerRequestInterface $request): ResponseInterface
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
                        $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_active.xlf:updateAction.noSpaces'),
                        $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_active.xlf:updateAction.lineNotAdded'),
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
                $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_active.xlf:updateAction.lineAdded'),
                ContextualFeedbackSeverity::OK,
                true
            );
            $flashMessageQueue->enqueue($flashMessage);
        }

        return new RedirectResponse($this->uriBuilder->buildUriFromRoute('typoscript_active', ['id' => $pageUid]));
    }

    /**
     * Align module data active constant conditions with toggled conditions from POST,
     * write updated active conditions to user's module data if needed and
     * prepare a list of active conditions for view.
     */
    private function handleToggledConstantConditions(RootInclude $constantTree, ModuleData $moduleData, ?array $parsedBody): array
    {
        $conditionAggregatorVisitor = new IncludeTreeConditionAggregatorVisitor();
        $treeTraverser = new IncludeTreeTraverser();
        $treeTraverser->traverse($constantTree, [$conditionAggregatorVisitor]);
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
    private function handleToggledSetupConditions(RootInclude $setupTree, ModuleData $moduleData, ?array $parsedBody, array $flattenedConstants): array
    {
        $setupConditionConstantSubstitutionVisitor = new IncludeTreeSetupConditionConstantSubstitutionVisitor();
        $setupConditionConstantSubstitutionVisitor->setFlattenedConstants($flattenedConstants);
        $treeTraverser = new IncludeTreeTraverser();
        $treeTraverserVisitors = [];
        $treeTraverserVisitors[] = $setupConditionConstantSubstitutionVisitor;
        $conditionAggregatorVisitor = new IncludeTreeConditionAggregatorVisitor();
        $treeTraverserVisitors[] = $conditionAggregatorVisitor;
        $treeTraverser->traverse($setupTree, $treeTraverserVisitors);
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

    private function addShortcutButtonToDocHeader(ModuleTemplate $view, string $moduleIdentifier, array $pageInfo, int $pageUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutTitle = sprintf(
            '%s: %s [%d]',
            $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_active.xlf:submodule.title'),
            BackendUtility::getRecordTitle('pages', $pageInfo),
            $pageUid
        );
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($moduleIdentifier)
            ->setDisplayName($shortcutTitle)
            ->setArguments(['id' => $pageUid]);
        $buttonBar->addButton($shortcutButton);
    }

    private function addBackButtonToDocHeader(ModuleTemplate $view, int $pageUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $backButton = $buttonBar->makeLinkButton()
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('typoscript_active', ['id' => $pageUid]))
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
        $buttonBar->addButton($backButton);
    }
}
