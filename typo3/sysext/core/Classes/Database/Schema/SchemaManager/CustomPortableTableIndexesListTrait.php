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

namespace TYPO3\CMS\Core\Database\Schema\SchemaManager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Schema\Index;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Provide custom portable index listing for MySQL and MariDB platforms using the custom MySQLSchemaManager.
 *
 * @internal only for use in extended SchemaManager classes and not part of public core API.
 */
trait CustomPortableTableIndexesListTrait
{
    /**
     * @param array<string, Index> $tableIndexesList
     * @param array<int, array<string, mixed>> $tableIndexes
     *
     * @return array<string, Index>
     */
    protected function customGetPortableTableIndexesList(array $tableIndexesList, array $tableIndexes, string $tableName, Connection $connection): array
    {
        $platform = $connection->getDatabasePlatform();
        if (!($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform)) {
            return $tableIndexesList;
        }

        foreach ($tableIndexesList as &$index) {
            $indexName = $index->getName();
            $sql = $this->getListTableIndexesSQL($platform, $tableName, $connection->getDatabase());

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
            $customTableIndexes = $connection->fetchAllAssociative($sql);

            $subPartColumns = array_filter(
                $tableIndexes,
                static fn(array $column): bool => $column['Sub_Part'] !== null && MathUtility::canBeInterpretedAsInteger($column['Sub_Part'])
            );

            if (!empty($subPartColumns)) {
                $index = $this->buildIndex($customTableIndexes);
            }
        }

        return $tableIndexesList;
    }

    /**
     * Build a Doctrine Index Object based on the information
     * gathered from the MySQL information schema.
     *
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

    /**
     * Copied from `doctrine/dbal 3.x` \Doctrine\DBAL\Platforms\AbstractMySQLPlatform because it has been deprecated
     * and will be removed in `doctrine/dbal 4.x`.
     */
    protected function getListTableIndexesSQL(AbstractPlatform $platform, string $table, string $database = null): string
    {
        if ($database !== null) {
            return 'SELECT NON_UNIQUE AS Non_Unique, INDEX_NAME AS Key_name, COLUMN_NAME AS Column_Name,' .
                ' SUB_PART AS Sub_Part, INDEX_TYPE AS Index_Type' .
                ' FROM information_schema.STATISTICS WHERE TABLE_NAME = ' . $platform->quoteStringLiteral($table) .
                ' AND TABLE_SCHEMA = ' . $platform->quoteStringLiteral($database) .
                ' ORDER BY SEQ_IN_INDEX ASC';
        }

        return 'SHOW INDEX FROM ' . $table;
    }
}
