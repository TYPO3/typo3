<?php
namespace TYPO3\CMS\Dbal\Database\Specifics;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * This class contains the specifics for PostgreSQL DBMS.
 * Any logic is in AbstractSpecifics.
 */
class PostgresSpecifics extends AbstractSpecifics
{
    /**
     * Contains the specifics that need to be taken care of for PostgreSQL DBMS.
     *
     * @var array
     */
    protected $specificProperties = [
        self::CAST_FIND_IN_SET => true
    ];

    /**
     * Contains the DBMS specific mapping overrides for native MySQL to ADOdb meta field types
     */
    protected $nativeToMetaFieldTypeOverrides = [
        'TINYBLOB' => 'B',
        'INT' => 'I4',
        'INTEGER' => 'I4',
        'TINYINT' => 'I2',
        'SMALLINT' => 'I2',
        'MEDIUMINT' => 'I4'
    ];

    /**
     * Contains the DBMS specific mapping information for ADOdb meta field types to MySQL native field types
     *
     * @var array
     */
    protected $metaToNativeFieldTypeOverrides = [
        'R' => 'INT',
        'I' => 'INT',
        'I1' => 'SMALLINT',
        'I2' => 'SMALLINT',
        'I4' => 'INT',
    ];

    /**
     * Determine the native field length information for a table field.
     *
     * @param string $mysqlType
     * @param int $maxLength
     * @return string
     */
    public function getNativeFieldLength($mysqlType, $maxLength)
    {
        if ($maxLength === -1) {
            return '';
        }
        switch ($mysqlType) {
            case 'DOUBLE':
                return '';
            case 'TINYINT':
                return '(4)';
            case 'SMALLINT':
                return '(6)';
            case 'MEDIUMINT':
                return '(9)';
            case 'INT':
                return '(11)';
            case 'BIGINT':
                return '(20)';
            default:
                return '(' . $maxLength . ')';
        }
    }

    /**
     * Return the default value of a field formatted to match the native MySQL SQL dialect
     *
     * @param array $fieldDefinition
     * @return mixed
     */
    protected function getNativeDefaultValue($fieldDefinition)
    {
        if (!$fieldDefinition['has_default']) {
            $returnValue = null;
        } elseif ($fieldDefinition['type'] === 'SERIAL' && substr($fieldDefinition['default_value'], 0, 7) === 'nextval') {
            $returnValue = null;
        } elseif ($fieldDefinition['type'] === 'varchar') {
            // Strip character class and unquote string
            if (StringUtility::beginsWith($fieldDefinition['default_value'], 'NULL::')) {
                $returnValue = null;
            } else {
                $returnValue = str_replace("\\'", "'", preg_replace('/\'(.*)\'(::(?:character\svarying|varchar|character|char|text)(?:\(\d+\))?)?\z/', '\\1', $fieldDefinition['default_value']));
            }
        } elseif (substr($fieldDefinition['type'], 0, 3) === 'int') {
            $returnValue = (int)preg_replace('/^\(?(\-?\d+)\)?$/', '\\1', $fieldDefinition['default_value']);
        } else {
            $returnValue = $fieldDefinition['default_value'];
        }
        return $returnValue;
    }

    /**
     * Return the MySQL native key type indicator - https://dev.mysql.com/doc/refman/5.5/en/show-columns.html
     * PRI - the column is a PRIMARY KEY or is one of the columns in a multiple-column PRIMARY KEY
     * UNI - the column is the first column of a UNIQUE index
     * MUL - the column is the first column of a nonunique index
     * If more than one of the values applies return the one with the highest priority, in the order PRI, UNI, MUL
     * If none applies return empty value.
     *
     * @param array $fieldDefinition
     * @return string
     */
    protected function getNativeKeyForField($fieldDefinition)
    {
        if (isset($fieldDefinition['primary_key']) && (bool)$fieldDefinition['primary_key']) {
            $returnValue = 'PRI';
        } elseif (isset($fieldDefinition['unique']) && (bool)$fieldDefinition['unique']) {
            $returnValue = 'UNI';
        } else {
            $returnValue = '';
        }
        return $returnValue;
    }

    /**
     * Return the MySQL native extra field information - https://dev.mysql.com/doc/refman/5.5/en/show-columns.html
     * auto_increment for columns that have the AUTO_INCREMENT attribute
     * on update CURRENT_TIMESTAMP for TIMESTAMP columns that have the ON UPDATE CURRENT_TIMESTAMP attribute.
     *
     * @param array $fieldDefinition
     * @return string
     */
    protected function getNativeExtraFieldAttributes($fieldDefinition)
    {
        if ($fieldDefinition['type'] === 'SERIAL' || substr($fieldDefinition['default_value'], 0, 7) === 'nextval') {
            return 'auto_increment';
        }
        return '';
    }

    /**
     * Adjust query parts for various DBMS
     *
     * @param string $select_fields
     * @param string $from_table
     * @param string $where_clause
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     * @return void
     */
    public function transformQueryParts(&$select_fields, &$from_table, &$where_clause, &$groupBy = '', &$orderBy = '', &$limit = '')
    {
        // Strip orderBy part if select statement is a count
        if (preg_match_all('/count\(([^)]*)\)/i', $select_fields, $matches)) {
            $orderByFields = GeneralUtility::trimExplode(',', $orderBy);
            $groupByFields = GeneralUtility::trimExplode(',', $groupBy);
            foreach ($matches[1] as $matchedField) {
                $field = $matchedField;
                // Lookup if the field in COUNT() statement is used in GROUP BY statement
                $index = array_search($field, $groupByFields, true);
                if ($index !== false) {
                    // field is used in GROUP BY, continue with next field
                    continue;
                }
                // If that field isn't used in GROUP BY statement, drop the ordering for compatibility reason
                $index = array_search($field, $orderByFields, true);
                if ($index !== false) {
                    unset($orderByFields[$index]);
                }
            }
            $orderBy = implode(', ', $orderByFields);
        }
    }
}
