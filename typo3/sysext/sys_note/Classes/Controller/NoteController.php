<?php
namespace TYPO3\CMS\SysNote\Controller;

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

/**
 * Note controller
 */
class NoteController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository
     */
    protected $sysNoteRepository;

    /**
     * @var \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository
     */
    protected $backendUserRepository;

    /**
     * @param \TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository $sysNoteRepository
     */
    public function injectSysNoteRepository(\TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository $sysNoteRepository)
    {
        $this->sysNoteRepository = $sysNoteRepository;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository $backendUserRepository
     */
    public function injectBackendUserRepository(\TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository $backendUserRepository)
    {
        $this->backendUserRepository = $backendUserRepository;
    }

    /**
     * Render notes by single PID or PID list
     *
     * @param string $pids Single PID or comma separated list of PIDs
     * @return string
     * @ignorevalidation $pids
     */
    public function listAction($pids)
    {
        if (empty($pids) || empty($GLOBALS['BE_USER']->user['uid'])) {
            return '';
        }
        $author = $this->backendUserRepository->findByUid($GLOBALS['BE_USER']->user['uid']);
        $notes = $this->sysNoteRepository->findByPidsAndAuthor($pids, $author);
        $this->view->assign('notes', $notes);
    }
}
