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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\OkStatus;

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
        $messages = [];
        if ($password !== $passwordCheck) {
            $message = new ErrorStatus();
            $message->setTitle('Install tool password not changed');
            $message->setMessage('Given passwords do not match.');
            $messages[] = $message;
        } elseif (strlen($password) < 8) {
            $message = new ErrorStatus();
            $message->setTitle('Install tool password not changed');
            $message->setMessage('Given password must be at least eight characters long.');
            $messages[] = $message;
        } else {
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
            $configurationManager->setLocalConfigurationValueByPath(
                'BE/installToolPassword',
                $this->getHashedPassword($password)
            );
            $message = new OkStatus();
            $message->setTitle('Install tool password changed');
            $messages[] = $message;
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
