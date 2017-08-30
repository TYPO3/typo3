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
 * Ajax wrapper to reset backend user preferences
 */
class ResetBackendUserUc extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     * @throws \InvalidArgumentException
     */
    protected function executeAction(): array
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users')
            ->update('be_users')
            ->set('uc', '')
            ->execute();

        $messageQueue = new FlashMessageQueue('install');
        $messageQueue->enqueue(new FlashMessage(
            '',
            'Reset all backend users preferences'
        ));

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messageQueue
        ]);
        return $this->view->render();
    }
}
