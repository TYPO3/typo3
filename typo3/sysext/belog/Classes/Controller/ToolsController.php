<?php
namespace TYPO3\CMS\Belog\Controller;

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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Show log entries from table sys_log
 */
class ToolsController extends \TYPO3\CMS\Belog\Controller\AbstractController
{
    /**
     * Delete all log entries that share the same message with the log entry given
     * in $errorUid
     *
     * @param int $errorUid
     */
    public function deleteMessageAction(int $errorUid)
    {
        /** @var \TYPO3\CMS\Belog\Domain\Model\LogEntry $logEntry */
        $logEntry = $this->logEntryRepository->findByUid($errorUid);
        if (!$logEntry) {
            $this->addFlashMessage(LocalizationUtility::translate('actions.delete.noRowFound', 'belog'), '', AbstractMessage::WARNING);
            $this->redirect('index');
        }
        $numberOfDeletedRows = $this->logEntryRepository->deleteByMessageDetails($logEntry);
        $this->addFlashMessage(sprintf(LocalizationUtility::translate('actions.delete.message', 'belog'), $numberOfDeletedRows));
        $this->redirect('index');
    }
}
