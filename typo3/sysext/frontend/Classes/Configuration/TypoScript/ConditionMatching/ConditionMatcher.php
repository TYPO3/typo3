<?php

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

namespace TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\ExpressionLanguage\RequestWrapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageLayoutResolver;

/**
 * Matching TypoScript conditions for frontend disposal.
 *
 * Used with the TypoScript parser. Matches browserinfo
 * and IP numbers for use with templates.
 *
 * @deprecated since v12, will be removed in v13 together with old TypoScript parser
 */
class ConditionMatcher extends AbstractConditionMatcher
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * This is the "full" rootline, identical to TSFE->rootLine:
     * Deepest nested page first, then up until (but not including) pseudo-page 0.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $fullRootLine;

    /**
     * @param Context|null $context optional context to fetch data from
     * @param int|null $pageId
     * @param array|null $rootLine
     * @param array<int, array<string, mixed>>|null $fullRootLine
     * @todo: Refactor to be properly DI-aware. Get $context injected, but use
     *        setters for pageId and the (two) different rootLines.
     */
    public function __construct(Context $context = null, int $pageId = null, array $rootLine = null, array $fullRootLine = null)
    {
        trigger_error(
            'The FE condition matcher has been deprecated and will be removed with TYPO3 v13. This logic' .
            ' has been integrated into the new TypoScript parser structure, see IncludeTreeConditionMatcherVisitor',
            E_USER_DEPRECATED
        );
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->pageId = $pageId;
        // @todo: Accessing $GLOBALS['TSFE']->config['rootLine'] is rather useless here since it typically
        //        isn't set at this point in time in the FE processing chain. Use setRootline() instead.
        $this->rootline = $rootLine ?? $GLOBALS['TSFE']->config['rootLine'] ?? [];
        $this->fullRootLine = $fullRootLine ?? $GLOBALS['TSFE']->rootLine ?? [];
        $this->initializeExpressionLanguageResolver();
    }

    protected function updateExpressionLanguageVariables(): void
    {
        $page = $GLOBALS['TSFE']?->page ?? [];

        $tree = new \stdClass();
        $tree->level = $this->rootline ? count($this->rootline) - 1 : 0;
        $tree->rootLine = $this->rootline;
        $tree->rootLineIds = array_column($this->rootline, 'uid');
        $tree->rootLineParentIds = array_slice(array_column($this->rootline, 'pid'), 1);
        // We're feeding the "full" RootLine here, not the "local" one that stops at sys_template record having 'root' set.
        // This is to be in-line with backend here: A 'backend_layout_next_level' on a page above sys_template 'root' page should
        // still be considered. Additionally, $this->fullRootLine is "deepest page first, then up" for getLayoutForPage() to find
        // the 'nearest' parent.
        $tree->pagelayout = GeneralUtility::makeInstance(PageLayoutResolver::class)->getLayoutForPage($page, $this->fullRootLine);

        $frontendUserAspect = $this->context->getAspect('frontend.user');
        $frontend = new \stdClass();
        $frontend->user = new \stdClass();
        $frontend->user->isLoggedIn = $frontendUserAspect->get('isLoggedIn');
        $frontend->user->userId = $frontendUserAspect->get('id');
        $frontend->user->userGroupList = implode(',', $frontendUserAspect->get('groupIds'));
        $frontend->user->userGroupIds = $frontendUserAspect->get('groupIds');

        $backendUserAspect = $this->context->getAspect('backend.user');
        $backend = new \stdClass();
        $backend->user = new \stdClass();
        $backend->user->isAdmin = $backendUserAspect->get('isAdmin');
        $backend->user->isLoggedIn = $backendUserAspect->get('isLoggedIn');
        $backend->user->userId = $backendUserAspect->get('id');
        $backend->user->userGroupList = implode(',', $backendUserAspect->get('groupIds'));
        $backend->user->userGroupIds = $backendUserAspect->get('groupIds');

        $workspaceAspect = $this->context->getAspect('workspace');
        $workspace = new \stdClass();
        $workspace->workspaceId = $workspaceAspect->get('id');
        $workspace->isLive = $workspaceAspect->get('isLive');
        $workspace->isOffline = $workspaceAspect->get('isOffline');

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $site = null;
        $siteLanguage = null;
        if ($request instanceof ServerRequestInterface) {
            $site = $request->getAttribute('site');
            $siteLanguage = $request->getAttribute('language');
        }
        $this->expressionLanguageResolverVariables = [
            'tree' => $tree,
            'frontend' => $frontend,
            'backend' => $backend,
            'workspace' => $workspace,
            'page' => $page,
            'request' => new RequestWrapper($request),
            'date' => GeneralUtility::makeInstance(Context::class)->getAspect('date'),
            'site' => $site,
            'siteLanguage' => $siteLanguage,
            'tsfe' => $GLOBALS['TSFE'] ?? null,
        ];
    }
}
