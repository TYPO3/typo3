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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\OkStatus;
use TYPO3\CMS\Install\Status\WarningStatus;

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

        $messages = [];
        if (empty($this->postValues['hashes'])) {
            $message = new WarningStatus();
            $message->setTitle('No database changes selected');
            $messages[] = $message;
        } else {
            $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
            $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
            $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
            $statementHashesToPerform = array_flip($this->postValues['hashes']);
            $results = $schemaMigrationService->migrate($sqlStatements, $statementHashesToPerform);
            // Create error flash messages if any
            foreach ($results as $errorMessage) {
                $message = new ErrorStatus();
                $message->setTitle('Database update failed');
                $message->setMessage('Error: ' . $errorMessage);
                $messages[] = $message;
            }
            $message = new OkStatus();
            $message->setTitle('Executed database updates');
            $messages[] = $message;
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
