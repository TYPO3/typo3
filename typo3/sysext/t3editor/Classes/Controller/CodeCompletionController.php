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

namespace TYPO3\CMS\T3editor\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Code completion for t3editor
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class CodeCompletionController
{
    public function __construct(
        private readonly SysTemplateRepository $sysTemplateRepository,
        private readonly SysTemplateTreeBuilder $treeBuilder,
        private readonly LossyTokenizer $lossyTokenizer,
        private readonly IncludeTreeTraverser $treeTraverser,
    ) {
    }

    /**
     * Loads all templates up to a given page id (walking the rootline) and
     * cleans parts that are not required for the t3editor code-completion.
     */
    public function loadCompletions(ServerRequestInterface $request): ResponseInterface
    {
        // Check whether access is granted (only admin have access to sys_template records):
        if (!$GLOBALS['BE_USER']->isAdmin()) {
            return new HtmlResponse($this->getLanguageService()->sL('LLL:EXT:t3editor/Resources/Private/Language/locallang.xlf:noPermission'), 500);
        }
        $pageId = (int)($request->getParsedBody()['pageId'] ?? $request->getQueryParams()['pageId']);
        // Check whether there is a pageId given:
        if (!$pageId) {
            return new HtmlResponse($this->getLanguageService()->sL('LLL:EXT:t3editor/Resources/Private/Language/locallang.xlf:pageIDInteger'), 500);
        }
        // Fetch the templates
        return new JsonResponse($this->getMergedTemplates($pageId, $request));
    }

    /**
     * Gets merged templates by walking the rootline to a given page id.
     * This is loaded once via ajax when a t3editor in typoscript mode is fired.
     * JS then knows the object types and can auto-complete on CTRL+space.
     *
     * @return array Setup part of merged template records
     */
    protected function getMergedTemplates(int $pageId, ServerRequestInterface $request): array
    {
        $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
        $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootline($rootLine, $request);
        /** @var SiteInterface|null $site */
        $site = $request->getAttribute('site');
        $setupIncludeTree = $this->treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, $this->lossyTokenizer, $site);
        $setupAstBuilderVisitor = GeneralUtility::makeInstance(IncludeTreeAstBuilderVisitor::class);
        $this->treeTraverser->traverse($setupIncludeTree, [$setupAstBuilderVisitor]);
        $setupAst = $setupAstBuilderVisitor->getAst();
        $result = $this->treeWalkCleanup($setupAst->toArray());
        return $result;
    }

    /**
     * Walks through a tree of TypoScript configuration and prepares it for JS.
     */
    private function treeWalkCleanup(array $treeBranch): array
    {
        $cleanedTreeBranch = [];
        foreach ($treeBranch as $key => $value) {
            $key = is_int($key) ? (string)$key : $key;
            //type definition or value-assignment
            if (substr($key, -1) !== '.') {
                if ($value != '') {
                    if (mb_strlen($value) > 20) {
                        $value = mb_substr($value, 0, 20);
                    }
                    if (!isset($cleanedTreeBranch[$key])) {
                        $cleanedTreeBranch[$key] = [];
                    }
                    $cleanedTreeBranch[$key]['v'] = $value;
                }
            } else {
                // subtree (definition of properties)
                $subBranch = $this->treeWalkCleanup($value);
                if ($subBranch) {
                    if (substr($key, -1) === '.') {
                        $key = rtrim($key, '.');
                    }
                    if (!isset($cleanedTreeBranch[$key])) {
                        $cleanedTreeBranch[$key] = [];
                    }
                    $cleanedTreeBranch[$key]['c'] = $subBranch;
                }
            }
        }
        return $cleanedTreeBranch;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
