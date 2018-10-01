<?php
declare(strict_types = 1);
namespace TYPO3\CMS\SysNote\Hook;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\SysNote\Controller\NoteController;
use TYPO3\CMS\SysNote\Domain\Repository\SysNoteRepository;

/**
 * Hook for the list module
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class RecordListHook
{
    /**
     * Add sys_notes as additional content to the header of the list module
     *
     * @param array $params
     * @return string
     */
    public function renderInHeader(array $params): string
    {
        /** @var ServerRequestInterface $request */
        $request = $params['request'];
        $id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $controller = GeneralUtility::makeInstance(NoteController::class);
        return $controller->listAction($id, SysNoteRepository::SYS_NOTE_POSITION_TOP);
    }

    /**
     * Add sys_notes as additional content to the footer of the list module
     *
     * @param array $params
     * @return string
     */
    public function renderInFooter(array $params): string
    {
        /** @var ServerRequestInterface $request */
        $request = $params['request'];
        $id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $controller = GeneralUtility::makeInstance(NoteController::class);
        return $controller->listAction($id, SysNoteRepository::SYS_NOTE_POSITION_BOTTOM);
    }
}
