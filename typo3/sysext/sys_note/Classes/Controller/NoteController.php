<?php
declare(strict_types = 1);
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
        if (empty($pids) || empty($GLOBALS['BE_USER']->user['uid'])) {
            return '';
        }

        $notes = $this->notesRepository->findByPidsAndAuthorId($pids, (int)$GLOBALS['BE_USER']->user['uid'], $position);
        if ($notes) {
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
                'EXT:sys_note/Resources/Private/Templates/Note/List.html'
            ));
            $view->setLayoutRootPaths(['EXT:sys_note/Resources/Private/Layouts']);
            $view->getRequest()->setControllerExtensionName('SysNote');
            $view->assign('notes', $notes);
            return $view->render();
        }

        return '';
    }
}
