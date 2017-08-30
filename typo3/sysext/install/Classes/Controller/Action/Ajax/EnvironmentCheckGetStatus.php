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
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck;
use TYPO3\CMS\Install\SystemEnvironment\SetupCheck;

/**
 * Get environment status
 */
class EnvironmentCheckGetStatus extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $messageQueue = new FlashMessageQueue('install');
        $checkMessages = (new Check())->getStatus();
        foreach ($checkMessages as $message) {
            $messageQueue->enqueue($message);
        }
        $setupMessages = (new SetupCheck())->getStatus();
        foreach ($setupMessages as $message) {
            $messageQueue->enqueue($message);
        }
        $databaseMessages = (new DatabaseCheck())->getStatus();
        foreach ($databaseMessages as $message) {
            $messageQueue->enqueue($message);
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => [
                'error' => $messageQueue->getAllMessages(FlashMessage::ERROR),
                'warning' => $messageQueue->getAllMessages(FlashMessage::WARNING),
                'ok' => $messageQueue->getAllMessages(FlashMessage::OK),
                'information' => $messageQueue->getAllMessages(FlashMessage::INFO),
                'notice' => $messageQueue->getAllMessages(FlashMessage::NOTICE),
            ],
        ]);
        return $this->view->render();
    }
}
