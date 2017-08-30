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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;

/**
 * Get folder structure status
 */
class FolderStructureGetStatus extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure();

        $structureMessages = $structureFacade->getStatus();
        $errorQueue = new FlashMessageQueue('install');
        $okQueue = new FlashMessageQueue('install');
        foreach ($structureMessages as $message) {
            if ($message->getSeverity() === FlashMessage::ERROR
                || $message->getSeverity() === FlashMessage::WARNING
            ) {
                $errorQueue->enqueue($message);
            } else {
                $okQueue->enqueue($message);
            }
        }

        $this->view->assignMultiple([
            'success' => true,
            'errorStatus' => $errorQueue,
            'okStatus' => $okQueue,
        ]);
        return $this->view->render();
    }
}
