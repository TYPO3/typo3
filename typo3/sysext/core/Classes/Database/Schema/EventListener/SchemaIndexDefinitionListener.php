<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Database\Schema\EventListener;

use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;
use Doctrine\DBAL\Platforms\MySqlPlatform;
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
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     */
    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $event)
    {
        // Early  return for non-MySQL-compatible platforms
        if (!($event->getConnection()->getDatabasePlatform() instanceof MySqlPlatform)) {
            return;
        }

        $connection = $event->getConnection();
        $indexName = $event->getTableIndex()['name'];
        $sql = $event->getConnection()->getDatabasePlatform()->getListTableIndexesSQL(
            $event->getTable(),
            $event->getConnection()->getDatabase()
        );

        // check whether ORDER BY is available in SQL
        // and place the part 'AND INDEX_NAME = "SOME_INDEX_NAME"' before that
        if (str_contains($sql, 'ORDER BY')) {
            $posOfOrderBy = (int)strpos($sql, 'ORDER BY');
            $tmpSql = substr($sql, 0, $posOfOrderBy);
            $tmpSql .= ' AND ' . $connection->quoteIdentifier('INDEX_NAME') . ' = ' . $connection->quote($indexName);
            $tmpSql .= ' ' . substr($sql, $posOfOrderBy);
            $sql = $tmpSql;
            unset($tmpSql);
        } else {
            $sql .= ' AND ' . $connection->quoteIdentifier('INDEX_NAME') . ' = ' . $connection->quote($indexName);
        }

        $tableIndexes = $event->getConnection()->fetchAllAssociative($sql);

        $subPartColumns = array_filter(
            $tableIndexes,
            static function ($column) {
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

            if (str_contains($tableIndex['index_type'], 'FULLTEXT')) {
                $tableIndex['flags'] = ['FULLTEXT'];
            } elseif (str_contains($tableIndex['index_type'], 'SPATIAL')) {
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
