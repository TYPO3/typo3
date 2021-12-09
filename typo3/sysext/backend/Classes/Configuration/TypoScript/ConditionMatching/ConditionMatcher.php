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

namespace TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageLayoutResolver;

/**
 * Matching TypoScript conditions for backend disposal.
 *
 * Used with the TypoScript parser.
 * Matches browserinfo, IP numbers for use with templates
 */
class ConditionMatcher extends AbstractConditionMatcher
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context = null, int $pageId = null, array $rootLine = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->pageId = $pageId ?? $this->determinePageId();
        if ($rootLine === null) {
            $rootLine = BackendUtility::BEgetRootLine($this->pageId, '', true);
            ksort($rootLine);
        }
        $this->rootline = $rootLine;
        $this->initializeExpressionLanguageResolver();
    }

    protected function updateExpressionLanguageVariables(): void
    {
        $page = BackendUtility::getRecord('pages', $this->pageId ?? $this->determinePageId()) ?: [];

        $treeLevel = $this->rootline ? count($this->rootline) - 1 : 0;
        $tree = new \stdClass();
        $tree->level = $treeLevel;
        $tree->rootLine = $this->rootline;
        $tree->rootLineIds = array_column($this->rootline, 'uid');
        $tree->rootLineParentIds = array_slice(array_column($this->rootline, 'pid'), 2);
        $tree->pagelayout = GeneralUtility::makeInstance(PageLayoutResolver::class)->getLayoutForPage($page, $this->rootline);

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

        $this->expressionLanguageResolverVariables = [
            'tree' => $tree,
            'backend' => $backend,
            'workspace' => $workspace,
            'page' => $page,
        ];
    }

    /**
     * Tries to determine the ID of the page currently processed.
     * When User/Group TS-Config is parsed when no specific page is handled
     * (i.e. in the Extension Manager, etc.) this function will return "0", so that
     * the accordant conditions (e.g. PIDinRootline) will return "FALSE"
     *
     * @return int The determined page id or otherwise 0
     */
    private function determinePageId(): int
    {
        $pageId = 0;
        $editStatement = GeneralUtility::_GP('edit');
        $commandStatement = GeneralUtility::_GP('cmd');
        // Determine id from module that was called with an id:
        if ($id = (int)GeneralUtility::_GP('id')) {
            $pageId = $id;
        } elseif (is_array($editStatement)) {
            $table = key($editStatement);
            $uidAndAction = current($editStatement);
            $uid = (int)key($uidAndAction);
            $action = current($uidAndAction);
            if ($action === 'edit') {
                $pageId = $this->getPageIdByRecord($table, $uid);
            } elseif ($action === 'new') {
                $pageId = $this->getPageIdByRecord($table, $uid, true);
            }
        } elseif (is_array($commandStatement)) {
            $table = key($commandStatement);
            $uidActionAndTarget = current($commandStatement);
            $uid = (int)key($uidActionAndTarget);
            $actionAndTarget = current($uidActionAndTarget);
            $action = key($actionAndTarget);
            $target = current($actionAndTarget);
            if ($action === 'delete') {
                $pageId = $this->getPageIdByRecord($table, $uid);
            } elseif ($action === 'copy' || $action === 'move') {
                $pageId = $this->getPageIdByRecord($table, (int)($target['target'] ?? $target), true);
            }
        }
        return $pageId;
    }

    /**
     * Gets the page id by a record.
     *
     * @param string $table Name of the table
     * @param int $id Id of the accordant record
     * @param bool $ignoreTable Whether to ignore the page, if TRUE a positive
     * @return int Id of the page the record is persisted on
     */
    private function getPageIdByRecord(string $table, int $id, bool $ignoreTable = false): int
    {
        $pageId = 0;
        if ($table && $id) {
            if (($ignoreTable || $table === 'pages') && $id >= 0) {
                $pageId = $id;
            } else {
                $record = BackendUtility::getRecordWSOL($table, abs($id), '*', '', false);
                $pageId = (int)$record['pid'];
            }
        }
        return $pageId;
    }
}
