<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Preparations;

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
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Prepare TCA. Used in bootstrap and Flex Form Data Structures.
 *
 * @internal Class and API may change any time.
 */
class TcaPreparation
{

    /**
     * Prepare TCA
     *
     * This class is typically called within bootstrap with empty caches after all TCA
     * files from extensions have been loaded. The preparation is then applied and
     * the prepared result is cached.
     * For flex form TCA, this class is called dynamically if opening a record in the backend.
     *
     * See unit tests for details.
     *
     * @param array $tca
     * @return array
     */
    public function prepare(array $tca): array
    {
        $tca = $this->prepareQuotingOfTableNamesAndColumnNames($tca);
        return $tca;
    }

    /**
     * Quote all table and field names in definitions known to possibly have quoted identifiers like '{#tablename}.{#columnname}='
     *
     * @param array $tca Incoming TCA
     * @return array Prepared TCA
     */
    protected function prepareQuotingOfTableNamesAndColumnNames(array $tca): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $newTca = $tca;
        $configToPrepareQuoting = [
            'foreign_table_where',
            'MM_table_where',
            'search' => 'andWhere'
        ];
        foreach ($tca as $table => $tableDefinition) {
            if (!isset($tableDefinition['columns']) || !is_array($tableDefinition['columns'])) {
                continue;
            }

            foreach ($tableDefinition['columns'] as $columnName => $columnConfig) {
                foreach ($configToPrepareQuoting as $level => $value) {
                    if (is_string($level)) {
                        $sqlQueryPartToPrepareQuotingIn = $columnConfig['config'][$level][$value] ?? '';
                    } else {
                        $sqlQueryPartToPrepareQuotingIn = $columnConfig['config'][$value] ?? '';
                    }
                    if (mb_strpos($sqlQueryPartToPrepareQuotingIn, '{#') !== false) {
                        $quoted = QueryHelper::quoteDatabaseIdentifiers(
                            $connectionPool->getConnectionForTable($table),
                            $sqlQueryPartToPrepareQuotingIn
                        );
                        if (is_string($level)) {
                            $newTca[$table]['columns'][$columnName]['config'][$level][$value] = $quoted;
                        } else {
                            $newTca[$table]['columns'][$columnName]['config'][$value] = $quoted;
                        }
                    }
                }
            }
        }

        return $newTca;
    }
}
