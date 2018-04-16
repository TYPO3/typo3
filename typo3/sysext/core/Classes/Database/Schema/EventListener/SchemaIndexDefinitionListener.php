<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Schema\EventListener;

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

use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Schema\Index;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Event listener to handle additional processing for index definitions to integrate
 * MySQL index sub parts.
 */
class SchemaIndexDefinitionListener
{
    /**
     * Listener for index definition events. This intercepts definitions
     * for indexes and builds the appropriate Index Object taking the sub
     * part length into account when a MySQL platform has been detected.
     *
     * @param \Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs $event
     * @throws \Doctrine\DBAL\DBALException
     * @throws \InvalidArgumentException
     */
    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $event)
    {
        if (strpos($event->getConnection()->getServerVersion(), 'MySQL') !== 0) {
            return;
        }

        $connection = $event->getConnection();
        $indexName = $event->getTableIndex()['name'];
        $sql = $event->getDatabasePlatform()->getListTableIndexesSQL(
            $event->getTable(),
            $event->getConnection()->getDatabase()
        );
        $sql .= ' AND ' . $connection->quoteIdentifier('INDEX_NAME') . ' = ' . $connection->quote($indexName);
        $tableIndexes = $event->getConnection()->fetchAll($sql);

        $subPartColumns = array_filter(
            $tableIndexes,
            function ($column) {
                return $column['Sub_Part'];
            }
        );

        if (!empty($subPartColumns)) {
            $event->setIndex($this->buildIndex($tableIndexes));
            $event->preventDefault();
        }
    }

    /**
     * Build a Doctrine Index Object based on the information
     * gathered from the MySQL information schema.
     *
     * @param array $tableIndexRows
     * @return \Doctrine\DBAL\Schema\Index
     * @throws \InvalidArgumentException
     */
    protected function buildIndex(array $tableIndexRows): Index
    {
        $data = null;
        foreach ($tableIndexRows as $tableIndex) {
            $tableIndex = array_change_key_case($tableIndex, CASE_LOWER);

            $tableIndex['primary'] = $tableIndex['key_name'] === 'PRIMARY';

            if (strpos($tableIndex['index_type'], 'FULLTEXT') !== false) {
                $tableIndex['flags'] = ['FULLTEXT'];
            } elseif (strpos($tableIndex['index_type'], 'SPATIAL') !== false) {
                $tableIndex['flags'] = ['SPATIAL'];
            }

            $indexName = $tableIndex['key_name'];
            $columnName = $tableIndex['column_name'];

            if ($tableIndex['sub_part'] !== null) {
                $columnName .= '(' . $tableIndex['sub_part'] . ')';
            }

            if ($data === null) {
                $data = [
                    'name' => $indexName,
                    'columns' => [$columnName],
                    'unique' => !$tableIndex['non_unique'],
                    'primary' => $tableIndex['primary'],
                    'flags' => $tableIndex['flags'] ?? [],
                    'options' => isset($tableIndex['where']) ? ['where' => $tableIndex['where']] : [],
                ];
            } else {
                $data['columns'][] = $columnName;
            }
        }

        $index = GeneralUtility::makeInstance(
            Index::class,
            $data['name'],
            $data['columns'],
            $data['unique'],
            $data['primary'],
            $data['flags'],
            $data['options']
        );

        return $index;
    }
}
