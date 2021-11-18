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

namespace TYPO3\CMS\SysNote\Controller;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository;

/**
 * Renders notes for the current backend user
 *
 * @internal
 */
class NoteController
{
    /**
     * @var SysNoteRepository
     */
    protected $notesRepository;

    protected array $pagePermissionCache = [];

    public function __construct()
    {
        $this->notesRepository = GeneralUtility::makeInstance(SysNoteRepository::class);
    }

    /**
     * Render notes by single PID or PID list
     *
     * @param string $pids Single PID or comma separated list of PIDs
     * @param int|null $position null for no restriction, integer for defined position
     * @return string
     */
    public function listAction($pids, int $position = null): string
    {
        $backendUser = $this->getBackendUser();
        if (empty($pids)
            || empty($backendUser->user[$backendUser->userid_column])
            || !$backendUser->check('tables_select', 'sys_note')
        ) {
            return '';
        }

        $notes = $this->notesRepository->findByPidsAndAuthorId($pids, (int)$backendUser->user[$backendUser->userid_column], $position);
        if (!$notes) {
            return '';
        }
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:sys_note/Resources/Private/Templates/Note/List.html'
        ));
        $view->setLayoutRootPaths(['EXT:sys_note/Resources/Private/Layouts']);
        $view->getRequest()->setControllerExtensionName('SysNote');
        $view->assign('notes', $this->enrichWithEditPermissions($notes));
        return $view->render();
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
