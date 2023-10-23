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

namespace TYPO3\CMS\SysNote\Renderer;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository;

/**
 * Renders notes for the current backend user
 *
 * @internal
 */
class NoteRenderer
{
    protected array $pagePermissionCache = [];

    public function __construct(
        protected readonly SysNoteRepository $sysNoteRepository,
        protected readonly BackendViewFactory $backendViewFactory,
    ) {}

    /**
     * Render notes by single PID
     *
     * @param ServerRequestInterface $request Incoming request
     * @param int $pid The page id notes should be rendered for
     * @param int|null $position null for no restriction, integer for defined position
     * @param string $returnUrl Url to return to when editing and closing a notes record again
     */
    public function renderList(ServerRequestInterface $request, int $pid, int $position = null, string $returnUrl = ''): string
    {
        $backendUser = $this->getBackendUser();
        if ($pid <= 0
            || empty($backendUser->user[$backendUser->userid_column])
            || !$backendUser->check('tables_select', 'sys_note')
        ) {
            return '';
        }

        $notes = $this->sysNoteRepository->findByPidAndAuthorId($pid, (int)$backendUser->user[$backendUser->userid_column], $position);
        if (!$notes) {
            return '';
        }
        $view = $this->backendViewFactory->create($request, ['typo3/cms-sys-note']);
        $view->assignMultiple([
            'notes' => $this->enrichWithEditPermissions($notes),
            'returnUrl' => $returnUrl,
        ]);
        return $view->render('List');
    }

    protected function enrichWithEditPermissions(array $notes): array
    {
        $backendUser = $this->getBackendUser();
        $hasEditAccess = $backendUser->isAdmin() || $backendUser->check('tables_modify', 'sys_note');

        foreach ($notes as &$note) {
            if (!$hasEditAccess) {
                // If no edit access, disable edit and delete options for all notes
                $note['canBeEdited'] = false;
                $note['canBeDeleted'] = false;
                continue;
            }
            // Check content edit permissions for the note
            $pid = (int)($note['pid'] ?? 0);
            if (!isset($this->pagePermissionCache[$pid])) {
                // Calculate and cache the content edit permissions for this $pid
                $permissionClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
                $pageRow = BackendUtility::readPageAccess($pid, $permissionClause) ?: [];
                $this->pagePermissionCache[$pid] = $backendUser->doesUserHaveAccess($pageRow, Permission::CONTENT_EDIT);
            }
            $note['canBeEdited'] = $this->pagePermissionCache[$pid];
            // For delete, also take user TSconfig into account
            $note['canBeDeleted'] = $this->pagePermissionCache[$pid]
                && !(bool)trim($backendUser->getTSConfig()['options.']['disableDelete.']['sys_note'] ?? $backendUser->getTSConfig()['options.']['disableDelete'] ?? '');
        }

        return $notes;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
