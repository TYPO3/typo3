<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

use TYPO3\CMS\Install\Service\Typo3tempFileService;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\OkStatus;

/**
 * Clear Processed Files
 *
 * This is an ajax wrapper for clearing processed files.
 */
class ClearTypo3tempFiles extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $typo3tempFileService = new Typo3tempFileService();
        if ($this->postValues['folder'] === '_processed_') {
            $failedDeletions = $typo3tempFileService->clearProcessedFiles();
            if ($failedDeletions) {
                $message = new ErrorStatus();
                $message->setTitle('Failed to delete ' . $failedDeletions . ' processed files. See TYPO3 log (by default typo3temp/var/logs/typo3_*.log)');
            } else {
                $message = new OkStatus();
                $message->setTitle('Cleared processed files');
            }
        } else {
            $typo3tempFileService->clearAssetsFolder($this->postValues['folder']);
            $message = new OkStatus();
            $message->setTitle('Cleared files in "' . $this->postValues['folder'] . '" folder');
        }
        $statusMessages[] = $message;

        $this->view->assignMultiple([
            'success' => true,
            'status' => $statusMessages,
        ]);
        return $this->view->render();
    }
}
