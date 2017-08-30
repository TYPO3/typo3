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

/**
 * Check if a younger version is available
 */
class CoreUpdateIsUpdateAvailable extends CoreUpdateAbstract
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $messageQueue = new FlashMessageQueue('install');
        if ($this->coreVersionService->isInstalledVersionAReleasedVersion()) {
            $isDevelopmentUpdateAvailable = $this->coreVersionService->isYoungerPatchDevelopmentReleaseAvailable();
            $isUpdateAvailable = $this->coreVersionService->isYoungerPatchReleaseAvailable();
            $isUpdateSecurityRelevant = $this->coreVersionService->isUpdateSecurityRelevant();
            if (!$isUpdateAvailable && !$isDevelopmentUpdateAvailable) {
                $messageQueue->enqueue(new FlashMessage(
                    '',
                    'No regular update available',
                    FlashMessage::NOTICE
                ));
            } elseif ($isUpdateAvailable) {
                $newVersion = $this->coreVersionService->getYoungestPatchRelease();
                if ($isUpdateSecurityRelevant) {
                    $messageQueue->enqueue(new FlashMessage(
                        '',
                        'Update to security relevant released version ' . $newVersion . ' is available!',
                        FlashMessage::WARNING
                    ));
                    $action = $this->getAction('Update now', 'updateRegular');
                } else {
                    $messageQueue->enqueue(new FlashMessage(
                        '',
                        'Update to regular released version ' . $newVersion . ' is available!',
                        FlashMessage::INFO
                    ));
                    $action = $this->getAction('Update now', 'updateRegular');
                }
            } elseif ($isDevelopmentUpdateAvailable) {
                $newVersion = $this->coreVersionService->getYoungestPatchDevelopmentRelease();
                $messageQueue->enqueue(new FlashMessage(
                    '',
                    'Update to development release ' . $newVersion . ' is available!',
                    FlashMessage::INFO
                ));
                $action = $this->getAction('Update now', 'updateDevelopment');
            }
        } else {
            $messageQueue->enqueue(new FlashMessage(
                '',
                'Current version is a development version and can not be updated',
                FlashMessage::WARNING
            ));
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messageQueue,
        ]);
        if (isset($action)) {
            $this->view->assign('action', $action);
        }
        return $this->view->render();
    }

    /**
     * @param string $title
     * @param string $action
     * @return array
     */
    protected function getAction($title, $action): array
    {
        return [
            'title' => $title,
            'action' => $action,
        ];
    }
}
