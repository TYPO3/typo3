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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\SiteInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\TsConfigInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TsConfigTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeNodeFinderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSourceAggregatorVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSyntaxScannerVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;

/**
 * Page TSconfig > Included page TSconfig
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
final readonly class PageTsConfigIncludesController
{
    public function __construct(
        private UriBuilder $uriBuilder,
        private ModuleTemplateFactory $moduleTemplateFactory,
        private TsConfigTreeBuilder $tsConfigTreeBuilder,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {}

    public function indexAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        $queryParams = $request->getQueryParams();

        $currentModule = $request->getAttribute('module');
        $currentModuleIdentifier = $currentModule->getIdentifier();

        $pageUid = (int)($queryParams['id'] ?? 0);
        $pageRecord = BackendUtility::readPageAccess($pageUid, '1=1') ?: [];
        if (empty($pageRecord)) {
            // Redirect to records overview if page could not be determined.
            // Edge case if page has been removed meanwhile.
            BackendUtility::setUpdateSignal('updatePageTree');
            return new RedirectResponse($this->uriBuilder->buildUriFromRoute('pagetsconfig_pages'));
        }

        // Prepare site constants if any
        $site = $request->getAttribute('site');
        $siteSettingsTree = new RootInclude();
        if ($site instanceof Site && !$site->getSettings()->isEmpty()) {
            $siteSettings = $site->getSettings()->getAllFlat();
            $siteConstants = '';
            foreach ($siteSettings as $nodeIdentifier => $value) {
                $siteConstants .= $nodeIdentifier . ' = ' . $value . LF;
            }
            $siteSettingsNode = new SiteInclude();
            $siteSettingsNode->setName('Site constants settings of site "' . $site->getIdentifier() . '"');
            $siteSettingsNode->setLineStream((new LosslessTokenizer())->tokenize($siteConstants));
            $siteSettingsTree->addChild($siteSettingsNode);
            $siteSettingsTree->setIdentifier('pageTsConfig-siteSettingsTree');
        }

        // Base page TSconfig tree
        $rootLine = BackendUtility::BEgetRootLine($pageUid, '', true);
        ksort($rootLine);
        $pageTsConfigTree = $this->tsConfigTreeBuilder->getPagesTsConfigTree($rootLine, new LosslessTokenizer());

        // Overload tree with user TSconfig if any
        $userTsConfig = $backendUser->getUserTsConfig();
        if ($userTsConfig === null) {
            throw new \RuntimeException('User TSconfig not initialized', 1675535278);
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
            $pageTsConfigTree->addChild($includeNode);
        }
        $pageTsConfigTree->setIdentifier('pageTsConfig-pageTsConfigTree');

        $treeTraverser = new IncludeTreeTraverser();
        $treeTraverserVisitors = [];
        $syntaxScannerVisitor = new IncludeTreeSyntaxScannerVisitor();
        $treeTraverserVisitors[] = $syntaxScannerVisitor;
        $treeTraverser->traverse($pageTsConfigTree, $treeTraverserVisitors);

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title'] ?? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? '');
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageUid);
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'pageUid' => $pageUid,
            'pageTitle' => $pageRecord['title'] ?? $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? '',
            'siteSettingsTree' => $siteSettingsTree,
            'pageTsConfigTree' => $pageTsConfigTree,
            'syntaxErrors' => $syntaxScannerVisitor->getErrors(),
            'syntaxErrorCount' => count($syntaxScannerVisitor->getErrors()),
        ]);
        return $view->renderResponse('PageTsConfig/Includes');
    }

    public function sourceAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $queryParams = $request->getQueryParams();
        $pageUid = (int)($queryParams['id'] ?? 0);
        $type = $queryParams['includeType'] ?? null;
        $includeIdentifier = $queryParams['identifier'] ?? null;
        if ($pageUid === 0 || $includeIdentifier === null || !in_array($type, ['constants', 'setup'], true)) {
            return $this->responseFactory->createResponse(400);
        }

        if ($type === 'constants') {
            // Prepare site constants if any
            $site = $request->getAttribute('site');
            $includeTree = new RootInclude();
            if ($site instanceof Site && !$site->getSettings()->isEmpty()) {
                $siteSettings = $site->getSettings()->getAllFlat();
                $siteConstants = '';
                foreach ($siteSettings as $nodeIdentifier => $value) {
                    $siteConstants .= $nodeIdentifier . ' = ' . $value . LF;
                }
                $siteSettingsNode = new SiteInclude();
                $siteSettingsNode->setName('Site constants settings of site "' . $site->getIdentifier() . '"');
                $siteSettingsNode->setLineStream((new LosslessTokenizer())->tokenize($siteConstants));
                $includeTree->addChild($siteSettingsNode);
                $includeTree->setIdentifier('pageTsConfig-siteSettingsTree');
            }
        } else {
            // Base page TSconfig tree
            $rootLine = BackendUtility::BEgetRootLine($pageUid, '', true);
            ksort($rootLine);
            $includeTree = $this->tsConfigTreeBuilder->getPagesTsConfigTree($rootLine, new LosslessTokenizer());

            // Overload tree with user TSconfig if any
            $userTsConfig = $backendUser->getUserTsConfig();
            if ($userTsConfig === null) {
                throw new \RuntimeException('UserTsConfig not initialized', 1675535279);
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
                $includeTree->addChild($includeNode);
            }
            $includeTree->setIdentifier('pageTsConfig-pageTsConfigTree');
        }

        $nodeFinderVisitor = new IncludeTreeNodeFinderVisitor();
        $nodeFinderVisitor->setNodeIdentifier($includeIdentifier);
        $treeTraverser = new IncludeTreeTraverser();
        $treeTraverser->traverse($includeTree, [$nodeFinderVisitor]);
        $lineStream = $nodeFinderVisitor->getFoundNode()?->getLineStream();
        if ($lineStream === null) {
            return $this->responseFactory->createResponse(400);
        }

        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($this->streamFactory->createStream((string)$lineStream));
    }

    public function sourceWithIncludesAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $queryParams = $request->getQueryParams();
        $pageUid = (int)($queryParams['id'] ?? 0);
        $type = $queryParams['includeType'] ?? null;
        $includeIdentifier = $queryParams['identifier'] ?? null;
        if ($pageUid === 0 || $includeIdentifier === null || !in_array($type, ['constants', 'setup'], true)) {
            return $this->responseFactory->createResponse(400);
        }

        if ($type === 'constants') {
            // Prepare site constants if any
            $site = $request->getAttribute('site');
            $includeTree = new RootInclude();
            if ($site instanceof Site && !$site->getSettings()->isEmpty()) {
                $siteSettings = $site->getSettings()->getAllFlat();
                $siteConstants = '';
                foreach ($siteSettings as $nodeIdentifier => $value) {
                    $siteConstants .= $nodeIdentifier . ' = ' . $value . LF;
                }
                $siteSettingsNode = new SiteInclude();
                $siteSettingsNode->setName('Site constants settings of site "' . $site->getIdentifier() . '"');
                $siteSettingsNode->setLineStream((new LosslessTokenizer())->tokenize($siteConstants));
                $includeTree->addChild($siteSettingsNode);
                $includeTree->setIdentifier('pageTsConfig-siteSettingsTree');
            }
        } else {
            // Base page TSconfig tree
            $rootLine = BackendUtility::BEgetRootLine($pageUid, '', true);
            ksort($rootLine);
            $includeTree = $this->tsConfigTreeBuilder->getPagesTsConfigTree($rootLine, new LosslessTokenizer());

            // Overload tree with user TSconfig if any
            $userTsConfig = $backendUser->getUserTsConfig();
            if ($userTsConfig === null) {
                throw new \RuntimeException('UserTsConfig not initialized', 1675535280);
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
                $includeTree->addChild($includeNode);
            }
            $includeTree->setIdentifier('pageTsConfig-pageTsConfigTree');
        }

        $sourceAggregatorVisitor = new IncludeTreeSourceAggregatorVisitor();
        $sourceAggregatorVisitor->setStartNodeIdentifier($includeIdentifier);
        $treeTraverser = new IncludeTreeTraverser();
        $treeTraverser->traverse($includeTree, [$sourceAggregatorVisitor]);
        $source =  $sourceAggregatorVisitor->getSource();

        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($this->streamFactory->createStream($source));
    }

    private function addShortcutButtonToDocHeader(ModuleTemplate $view, string $moduleIdentifier, array $pageInfo, int $pageUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutTitle = sprintf(
            '%s: %s [%d]',
            $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_pagetsconfig.xlf:module.pagetsconfig_includes'),
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
