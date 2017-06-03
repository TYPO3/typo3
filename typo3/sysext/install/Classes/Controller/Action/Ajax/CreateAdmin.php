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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\OkStatus;

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
        $messages = [];
        if (strlen($username) < 1) {
            $message = new ErrorStatus();
            $message->setTitle('Administrator user not created');
            $message->setMessage('No valid username given.');
            $messages[] = $message;
        } elseif ($password !== $passwordCheck) {
            $message = new ErrorStatus();
            $message->setTitle('Administrator user not created');
            $message->setMessage('Passwords do not match.');
            $messages[] = $message;
        } elseif (strlen($password) < 8) {
            $message = new ErrorStatus();
            $message->setTitle('Administrator user not created');
            $message->setMessage('Password must be at least eight characters long.');
            $messages[] = $message;
        } else {
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $userExists = $connectionPool->getConnectionForTable('be_users')
                ->count(
                    'uid',
                    'be_users',
                    ['username' => $username]
                );
            if ($userExists) {
                $message = new ErrorStatus();
                $message->setTitle('Administrator user not created');
                $message->setMessage('A user with username "' . $username . '" exists already.');
                $messages[] = $message;
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
                $message = new OkStatus();
                $message->setTitle('Administrator created with username "' . $username . '".');
                $messages[] = $message;
            }
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
