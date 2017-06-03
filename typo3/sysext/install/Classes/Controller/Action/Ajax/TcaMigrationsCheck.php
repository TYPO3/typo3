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

use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\LoadTcaService;
use TYPO3\CMS\Install\Status\NoticeStatus;

/**
 * Checks whether the current TCA needs migrations and displays applied migrations.
 */
class TcaMigrationsCheck extends AbstractAjaxAction
{
    /**
     * Load all TCA Migrations and return if there are any todos
     *
     * @return array TCA status messages
     */
    protected function executeAction(): array
    {
        $statusMessages = [];
        $tcaMessages = $this->checkTcaMigrations();

        foreach ($tcaMessages as $tcaMessage) {
            $message = new NoticeStatus();
            $message->setMessage($tcaMessage);
            $statusMessages[] = $message;
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $statusMessages,
        ]);
        return $this->view->render();
    }

    /**
     * "TCA migration" action
     *
     * @return array The TCA migration messages
     */
    protected function checkTcaMigrations(): array
    {
        GeneralUtility::makeInstance(LoadTcaService::class)->loadExtensionTablesWithoutMigration();
        $tcaMigration = GeneralUtility::makeInstance(TcaMigration::class);
        $GLOBALS['TCA'] = $tcaMigration->migrate($GLOBALS['TCA']);
        return $tcaMigration->getMessages();
    }
}
