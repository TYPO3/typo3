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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Change install tool password
 */
class ChangeInstallToolPassword extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $password = $this->postValues['password'];
        $passwordCheck = $this->postValues['passwordCheck'];
        $messageQueue = new FlashMessageQueue('install');

        if ($password !== $passwordCheck) {
            $messageQueue->enqueue(new FlashMessage(
                'Install tool password not changed. Given passwords do not match.',
                '',
                FlashMessage::ERROR
            ));
        } elseif (strlen($password) < 8) {
            $messageQueue->enqueue(new FlashMessage(
                'Install tool password not changed. Given password must be at least eight characters long.',
                '',
                FlashMessage::ERROR
            ));
        } else {
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
            $configurationManager->setLocalConfigurationValueByPath(
                'BE/installToolPassword',
                $this->getHashedPassword($password)
            );
            $messageQueue->enqueue(new FlashMessage('Install tool password changed'));
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messageQueue,
        ]);
        return $this->view->render();
    }
}
