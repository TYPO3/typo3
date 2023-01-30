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

namespace TYPO3\CMS\Backend\Controller\PageTsConfig;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TypoScript\AST\Traverser\AstTraverser;
use TYPO3\CMS\Core\TypoScript\AST\Visitor\AstSortChildrenVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\SiteInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\TsConfigInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\ConditionVerdictAwareIncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TsConfigTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSetupConditionConstantSubstitutionVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\TypoScript\UserTsConfig;
use TYPO3\CMS\Tstemplate\TypoScript\IncludeTree\Visitor\IncludeTreeCommentAwareAstBuilderVisitor;
use TYPO3\CMS\Tstemplate\TypoScript\IncludeTree\Visitor\IncludeTreeConditionAggregatorVisitor;
use TYPO3\CMS\Tstemplate\TypoScript\IncludeTree\Visitor\IncludeTreeConditionEnforcerVisitor;

/**
 * PageTsConfig > Active Page TsConfig
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[Controller]
class PageTsConfigActiveController
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly UriBuilder $uriBuilder,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly TsConfigTreeBuilder $tsConfigTreeBuilder,
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

        $pageUid = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        if ($pageUid <= 0) {
            // Redirect to records overview if on page 0 or invalid uid.
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('pagetsconfig_records'));
        }

        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        if (empty($pageRecord)) {
            // Redirect to records overview if page could not be determined.
            // Edge case if page has been removed meanwhile.
            BackendUtility::setUpdateSignal('updatePageTree');
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('pagetsconfig_records'));
        }

        // Force boolean toggles to bool and init further get/post vars
        if ($moduleData->clean('displayConstantSubstitutions', [true, false])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $displayConstantSubstitutions = $moduleData->get('displayConstantSubstitutions');
        if ($moduleData->clean('displayComments', [true, false])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $displayComments = $moduleData->get('displayComments');
        if ($moduleData->clean('sortAlphabetically', [true, false])) {
            $backendUser->pushModuleData($currentModuleIdentifier, $moduleData->toArray());
        }
        $sortAlphabetically = $moduleData->get('sortAlphabetically');

        // Prepare site constants if any
        $site = $request->getAttribute('site');
        $siteSettingsAst = null;
        $siteSettingsFlat = [];
        if ($site instanceof Site && !$site->getSettings()->isEmpty()) {
            $siteSettings = $site->getSettings()->getAllFlat();
            $siteConstants = '';
            foreach ($siteSettings as $nodeIdentifier => $value) {
                $siteConstants .= $nodeIdentifier . ' = ' . $value . LF;
            }
            $siteSettingsHash = 'site-settings-flat-' . hash('xxh3', $siteConstants);
            $siteSettingsNode = new SiteInclude();
            $siteSettingsNode->setName('Site constants settings of site ' . $site->getIdentifier());
            $siteSettingsNode->setLineStream((new LosslessTokenizer())->tokenize($siteConstants));
            $siteSettingsTreeRoot = new RootInclude();
            $siteSettingsTreeRoot->addChild($siteSettingsNode);
            /** @var IncludeTreeAstBuilderVisitor $astBuilderVisitor */
            $astBuilderVisitor = $this->container->get(IncludeTreeAstBuilderVisitor::class);
            $includeTreeTraverser = new IncludeTreeTraverser();
            $includeTreeTraverser->addVisitor($astBuilderVisitor);
            $includeTreeTraverser->traverse($siteSettingsTreeRoot);
            $siteSettingsAst = $astBuilderVisitor->getAst();
            // Trigger unique identifier creation for entire tree
            $siteSettingsAst->setIdentifier('pageTsConfig-siteSettingsAst');
            $siteSettingsFlat = $siteSettingsAst->flatten();
            if ($sortAlphabetically) {
                // Traverse AST to sort if needed
                $astTraverser = new AstTraverser();
                $astTraverser->addVisitor(new AstSortChildrenVisitor());
                $astTraverser->traverse($siteSettingsAst);
            }
        }

        // Base PageTsConfig tree
        $rootLine = BackendUtility::BEgetRootLine($pageUid, '', true);
        ksort($rootLine);
        $pagesTsConfigTree = $this->tsConfigTreeBuilder->getPagesTsConfigTree($rootLine, new LosslessTokenizer());

        // Overload tree with userTsConfig if any
        $userTsConfig = $backendUser->getUserTsConfig();
        if (!$userTsConfig instanceof UserTsConfig) {
            throw new \RuntimeException('UserTsConfig not initialized', 1674609098);
        }
        $userTsConfigAst = $userTsConfig->getUserTsConfigTree();
        $userTsConfigPageOverrides = '';
        // @todo: Ugly, similar in PageTsConfigFactory.
        $userTsConfigFlat = $userTsConfigAst->flatten();
        foreach ($userTsConfigFlat as $userTsConfigIdentifier => $userTsConfigValue) {
            if (str_starts_with($userTsConfigIdentifier, 'page.')) {
                $userTsConfigPageOverrides .= substr($userTsConfigIdentifier, 5) . ' = ' . $userTsConfigValue . chr(10);
            }
        }
        if (!empty($userTsConfigPageOverrides)) {
            $includeNode = new TsConfigInclude();
            $includeNode->setName('pageTsConfig-overrides-by-userTsConfig');
            $includeNode->setLineStream((new LosslessTokenizer())->tokenize($userTsConfigPageOverrides));
            $pagesTsConfigTree->addChild($includeNode);
        }

        // Set enabled conditions in pageTsConfig include tree and let it handle constant substitutions in pageTsConfig conditions.
        $pageTsConfigConditions = $this->handleToggledPageTsConfigConditions($pagesTsConfigTree, $moduleData, $parsedBody, $siteSettingsFlat);
        $conditionEnforcerVisitor = new IncludeTreeConditionEnforcerVisitor();
        $conditionEnforcerVisitor->setEnabledConditions(array_column(array_filter($pageTsConfigConditions, static fn ($condition) => $condition['active']), 'value'));
        $treeTraverser = new IncludeTreeTraverser();
        $treeTraverser->addVisitor($conditionEnforcerVisitor);
        $treeTraverser->traverse($pagesTsConfigTree);

        // Create AST with constants from site and conditions
        $includeTreeTraverser = new ConditionVerdictAwareIncludeTreeTraverser();
        /** @var IncludeTreeCommentAwareAstBuilderVisitor $astBuilderVisitor */
        $astBuilderVisitor = $this->container->get(IncludeTreeCommentAwareAstBuilderVisitor::class);
        $astBuilderVisitor->setFlatConstants($siteSettingsFlat);
        $includeTreeTraverser->addVisitor($astBuilderVisitor);
        $includeTreeTraverser->traverse($pagesTsConfigTree);
        $pageTsConfigAst = $astBuilderVisitor->getAst();
        // Trigger unique identifier creation for entire tree
        $pageTsConfigAst->setIdentifier('pageTsConfig');
        if ($sortAlphabetically) {
            // Traverse AST to sort if needed
            $astTraverser = new AstTraverser();
            $astTraverser->addVisitor(new AstSortChildrenVisitor());
            $astTraverser->traverse($pageTsConfigAst);
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageUid);
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'pageUid' => $pageUid,
            'pageTitle' => $pageRecord['title'],
            'displayConstantSubstitutions' => $displayConstantSubstitutions,
            'displayComments' => $displayComments,
            'sortAlphabetically' => $sortAlphabetically,
            'siteSettingsAst' => $siteSettingsAst,
            'pageTsConfigAst' => $pageTsConfigAst,
            'pageTsConfigConditions' => $pageTsConfigConditions,
            'pageTsConfigConditionsActiveCount' => count(array_filter($pageTsConfigConditions, static fn ($condition) => $condition['active'])),
        ]);
        return $view->renderResponse('PageTsConfig/Active');
    }

    /**
     * Align module data active pageTsConfig conditions with toggled conditions from POST,
     * write updated active conditions to user's module data if needed and
     * prepare a list of active conditions for view.
     */
    private function handleToggledPageTsConfigConditions(RootInclude $pageTsConfigTree, ModuleData $moduleData, ?array $parsedBody, array $flattenedConstants): array
    {
        $treeTraverser = new IncludeTreeTraverser();
        $setupConditionConstantSubstitutionVisitor = new IncludeTreeSetupConditionConstantSubstitutionVisitor();
        $setupConditionConstantSubstitutionVisitor->setFlattenedConstants($flattenedConstants);
        $treeTraverser->addVisitor($setupConditionConstantSubstitutionVisitor);
        $conditionAggregatorVisitor = new IncludeTreeConditionAggregatorVisitor();
        $treeTraverser->addVisitor($conditionAggregatorVisitor);
        $treeTraverser->traverse($pageTsConfigTree);
        $pageTsConfigConditions = $conditionAggregatorVisitor->getConditions();
        $conditionsFromPost = $parsedBody['pageTsConfigConditions'] ?? [];
        $conditionsFromModuleData = array_flip((array)$moduleData->get('pageTsConfigConditions'));
        $conditions = [];
        foreach ($pageTsConfigConditions as $condition) {
            $conditionHash = hash('xxh3', $condition['value']);
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
            $conditions[] = [
                'value' => $condition['value'],
                'originalValue' => $condition['originalValue'],
                'hash' => $conditionHash,
                'active' => $conditionActive,
            ];
        }
        if ($conditionsFromPost) {
            $moduleData->set('pageTsConfigConditions', array_keys($conditionsFromModuleData));
            $this->getBackendUser()->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }
        return $conditions;
    }

    private function addShortcutButtonToDocHeader(ModuleTemplate $view, string $moduleIdentifier, array $pageInfo, int $pageUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutTitle = sprintf(
            '%s: %s [%d]',
            $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig_active'),
            BackendUtility::getRecordTitle('pages', $pageInfo),
            $pageUid
        );
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($moduleIdentifier)
            ->setDisplayName($shortcutTitle)
            ->setArguments(['id' => $pageUid]);
        $buttonBar->addButton($shortcutButton);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
