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

use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\OkStatus;

/**
 * Execute database analyzer "analyze" / "show" status action
 */
class DatabaseAnalyzerAnalyze extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $this->loadExtLocalconfDatabaseAndExtTables();

        $suggestions = [];
        try {
            $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
            $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
            $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
            $addCreateChange = $schemaMigrationService->getUpdateSuggestions($sqlStatements);

            // Aggregate the per-connection statements into one flat array
            $addCreateChange = array_merge_recursive(...array_values($addCreateChange));
            if (!empty($addCreateChange['create_table'])) {
                $suggestion = [
                    'key' => 'addTable',
                    'label' => 'Add tables',
                    'enabled' => true,
                    'children' => [],
                ];
                foreach ($addCreateChange['create_table'] as $hash => $statement) {
                    $suggestion['children'][] = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($addCreateChange['add'])) {
                $suggestion = [
                    'key' => 'addField',
                    'label' => 'Add fields to tables',
                    'enabled' => true,
                    'children' => [],
                ];
                foreach ($addCreateChange['add'] as $hash => $statement) {
                    $suggestion['children'][] = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($addCreateChange['change'])) {
                $suggestion = [
                    'key' => 'change',
                    'label' => 'Change fields',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($addCreateChange['change'] as $hash => $statement) {
                    $child = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                    if (isset($addCreateChange['change_currentValue'][$hash])) {
                        $child['current'] = $addCreateChange['change_currentValue'][$hash];
                    }
                    $suggestion['children'][] = $child;
                }
                $suggestions[] = $suggestion;
            }

            // Difference from current to expected
            $dropRename = $schemaMigrationService->getUpdateSuggestions($sqlStatements, true);

            // Aggregate the per-connection statements into one flat array
            $dropRename = array_merge_recursive(...array_values($dropRename));
            if (!empty($dropRename['change_table'])) {
                $suggestion = [
                    'key' => 'renameTableToUnused',
                    'label' => 'Remove tables (rename with prefix)',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($dropRename['change_table'] as $hash => $statement) {
                    $child = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                    if (!empty($dropRename['tables_count'][$hash])) {
                        $child['rowCount'] = $dropRename['tables_count'][$hash];
                    }
                    $suggestion['children'][] = $child;
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($dropRename['change'])) {
                $suggestion = [
                    'key' => 'renameTableFieldToUnused',
                    'label' => 'Remove unused fields (rename with prefix)',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($dropRename['change'] as $hash => $statement) {
                    $suggestion['children'][] = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($dropRename['drop'])) {
                $suggestion = [
                    'key' => 'deleteField',
                    'label' => 'Drop fields (really!)',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($dropRename['drop'] as $hash => $statement) {
                    $suggestion['children'][] = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                }
                $suggestions[] = $suggestion;
            }
            if (!empty($dropRename['drop_table'])) {
                $suggestion = [
                    'key' => 'deleteTable',
                    'label' => 'Drop tables (really!)',
                    'enabled' => false,
                    'children' => [],
                ];
                foreach ($dropRename['drop_table'] as $hash => $statement) {
                    $child = [
                        'hash' => $hash,
                        'statement' => $statement,
                    ];
                    if (!empty($dropRename['tables_count'][$hash])) {
                        $child['rowCount'] = $dropRename['tables_count'][$hash];
                    }
                    $suggestion['children'][] = $child;
                }
                $suggestions[] = $suggestion;
            }

            $message = new OkStatus();
            $message->setTitle('Analyzed current database');
            $messages[] = $message;
        } catch (StatementException $e) {
            $message = new ErrorStatus();
            $message->setTitle('Database analysis failed');
            $message->setMessage($e->getMessage());
            $messages[] = $message;
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
            'suggestions' => $suggestions,
        ]);
        return $this->view->render();
    }
}
