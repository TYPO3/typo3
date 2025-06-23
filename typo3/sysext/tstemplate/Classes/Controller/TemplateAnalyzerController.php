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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeNodeFinderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSourceAggregatorVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSyntaxScannerVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * TypoScript template analyzer.
 * Show TypoScript constants and setup include tree of current page.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final class TemplateAnalyzerController extends AbstractTemplateModuleController
{
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly SysTemplateRepository $sysTemplateRepository,
        private readonly IncludeTreeTraverser $treeTraverser,
        private readonly SysTemplateTreeBuilder $treeBuilder,
        private readonly LosslessTokenizer $losslessTokenizer,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

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

        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();
        $site = $request->getAttribute('site');
        $rootLine = $this->getScopedRootline($site, $rootLine);
        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootlineWithUidOverride($rootLine, $request, $selectedTemplateUid);

        // Build the constant include tree
        $constantIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRows, $this->losslessTokenizer, $site);
        $constantIncludeTree->setIdentifier('constants tstemplate includes');
        $treeTraverserVisitors = [];
        $constantSyntaxScannerVisitor = new IncludeTreeSyntaxScannerVisitor();
        $treeTraverserVisitors[] = $constantSyntaxScannerVisitor;
        $this->treeTraverser->traverse($constantIncludeTree, $treeTraverserVisitors);

        // Build the setup include tree
        $setupIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, $this->losslessTokenizer, $site);
        $setupIncludeTree->setIdentifier('setup tstemplate includes');
        $treeTraverserVisitors = [];
        $setupSyntaxScannerVisitor = new IncludeTreeSyntaxScannerVisitor();
        $treeTraverserVisitors[] = $setupSyntaxScannerVisitor;
        $this->treeTraverser->traverse($setupIncludeTree, $treeTraverserVisitors);

        $view = $this->moduleTemplateFactory->create($request);
        $view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
        $view->getDocHeaderComponent()->setMetaInformation($pageRecord);
        $this->addPreviewButtonToDocHeader($view, $pageRecord);
        $this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageUid);
        $view->makeDocHeaderModuleMenu(['id' => $pageUid]);
        $view->assignMultiple([
            'pageUid' => $pageUid,
            'allTemplatesOnPage' => $allTemplatesOnPage,
            'selectedTemplateUid' => $selectedTemplateUid,
            'templateTitle' => $templateTitle,
            'constantIncludeTree' => $constantIncludeTree,
            'constantErrors' => $constantSyntaxScannerVisitor->getErrors(),
            'constantErrorCount' => count($constantSyntaxScannerVisitor->getErrors()),
            'setupIncludeTree' => $setupIncludeTree,
            'setupErrors' => $setupSyntaxScannerVisitor->getErrors(),
            'setupErrorCount' => count($setupSyntaxScannerVisitor->getErrors()),
        ]);

        return $view->renderResponse('Analyzer');
    }

    public function sourceAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $pageUid = (int)($queryParams['id'] ?? 0);
        $type = $queryParams['includeType'] ?? null;
        $includeIdentifier = $queryParams['identifier'] ?? null;
        $moduleData = $request->getAttribute('moduleData');
        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageUid);
        $selectedTemplateUid = (int)($moduleData->get('selectedTemplatePerPage')[$pageUid] ?? 0);
        if (!in_array($selectedTemplateUid, array_column($allTemplatesOnPage, 'uid'))) {
            $selectedTemplateUid = (int)($allTemplatesOnPage[0]['uid'] ?? 0);
        }
        if ($pageUid === 0 || $includeIdentifier === null || !in_array($type, ['constants', 'setup'], true)) {
            return $this->responseFactory->createResponse(400);
        }
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();
        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootlineWithUidOverride($rootLine, $request, $selectedTemplateUid);
        $site = $request->getAttribute('site');
        $includeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite($type, $sysTemplateRows, $this->losslessTokenizer, $site);
        $includeTree->setIdentifier($type . ' tstemplate includes');

        $nodeFinderVisitor = GeneralUtility::makeInstance(IncludeTreeNodeFinderVisitor::class);
        $nodeFinderVisitor->setNodeIdentifier($includeIdentifier);
        $this->treeTraverser->traverse($includeTree, [$nodeFinderVisitor]);
        $foundNode = $nodeFinderVisitor->getFoundNode();
        if ($foundNode?->getLineStream() === null) {
            return $this->responseFactory->createResponse(400);
        }

        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($this->streamFactory->createStream((string)$foundNode->getLineStream()));
    }

    public function sourceWithIncludesAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $pageUid = (int)($queryParams['id'] ?? 0);
        $type = $queryParams['includeType'] ?? null;
        $includeIdentifier = $queryParams['identifier'] ?? null;
        $moduleData = $request->getAttribute('moduleData');
        $allTemplatesOnPage = $this->getAllTemplateRecordsOnPage($pageUid);
        $selectedTemplateUid = (int)($moduleData->get('selectedTemplatePerPage')[$pageUid] ?? 0);
        if (!in_array($selectedTemplateUid, array_column($allTemplatesOnPage, 'uid'))) {
            $selectedTemplateUid = (int)($allTemplatesOnPage[0]['uid'] ?? 0);
        }
        if ($pageUid === 0 || $includeIdentifier === null || !in_array($type, ['constants', 'setup'], true)) {
            return $this->responseFactory->createResponse(400);
        }
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageUid)->get();
        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootlineWithUidOverride($rootLine, $request, $selectedTemplateUid);
        $site = $request->getAttribute('site');
        $includeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite($type, $sysTemplateRows, $this->losslessTokenizer, $site);
        $includeTree->setIdentifier($type . ' tstemplate includes');

        $sourceAggregatorVisitor = new IncludeTreeSourceAggregatorVisitor();
        $sourceAggregatorVisitor->setStartNodeIdentifier($includeIdentifier);
        $this->treeTraverser->traverse($includeTree, [$sourceAggregatorVisitor]);
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
            $languageService->sL('LLL:EXT:tstemplate/Resources/Private/Language/locallang_analyzer.xlf:submodule.title'),
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
