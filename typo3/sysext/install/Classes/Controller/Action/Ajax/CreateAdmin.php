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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Create an administrator from given username and password
 */
class CreateAdmin extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     * @throws \RuntimeException
     */
    protected function executeAction(): array
    {
        $username = preg_replace('/\\s/i', '', $this->postValues['userName']);
        $password = $this->postValues['userPassword'];
        $passwordCheck = $this->postValues['userPasswordCheck'];
        $messages = new FlashMessageQueue('install');
        if (strlen($username) < 1) {
            $messages->enqueue(new FlashMessage(
                'No valid username given.',
                'Administrator user not created',
                FlashMessage::ERROR
            ));
        } elseif ($password !== $passwordCheck) {
            $messages->enqueue(new FlashMessage(
                'Passwords do not match.',
                'Administrator user not created',
                FlashMessage::ERROR
            ));
        } elseif (strlen($password) < 8) {
            $messages->enqueue(new FlashMessage(
                'Password must be at least eight characters long.',
                'Administrator user not created',
                FlashMessage::ERROR
            ));
        } else {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $userExists = $connectionPool->getConnectionForTable('be_users')
                ->count(
                    'uid',
                    'be_users',
                    ['username' => $username]
                );
            if ($userExists) {
                $messages->enqueue(new FlashMessage(
                    'A user with username "' . $username . '" exists already.',
                    'Administrator user not created',
                    FlashMessage::ERROR
                ));
            } else {
                $hashedPassword = $this->getHashedPassword($password);
                $adminUserFields = [
                    'username' => $username,
                    'password' => $hashedPassword,
                    'admin' => 1,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'crdate' => $GLOBALS['EXEC_TIME']
                ];
                $connectionPool->getConnectionForTable('be_users')->insert('be_users', $adminUserFields);
                $messages->enqueue(new FlashMessage(
                    '',
                    'Administrator created with username "' . $username . '".'
                ));
            }
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
