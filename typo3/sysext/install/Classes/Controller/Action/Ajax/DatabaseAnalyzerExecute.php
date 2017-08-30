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

use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Execute database analyzer "execute" action to apply
 * a set of DB changes.
 */
class DatabaseAnalyzerExecute extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $this->loadExtLocalconfDatabaseAndExtTables();

        $messageQueue = new FlashMessageQueue('install');
        if (empty($this->postValues['hashes'])) {
            $messageQueue->enqueue(new FlashMessage(
                '',
                'No database changes selected',
                FlashMessage::WARNING
            ));
        } else {
            $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
            $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
            $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
            $statementHashesToPerform = array_flip($this->postValues['hashes']);
            $results = $schemaMigrationService->migrate($sqlStatements, $statementHashesToPerform);
            // Create error flash messages if any
            foreach ($results as $errorMessage) {
                $messageQueue->enqueue(new FlashMessage(
                    'Error: ' . $errorMessage,
                    'Database update failed',
                    FlashMessage::ERROR
                ));
            }
            $messageQueue->enqueue(new FlashMessage(
                '',
                'Executed database updates'
            ));
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messageQueue,
        ]);
        return $this->view->render();
    }
}
