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

/**
 * This class handles the specifics of the active DBMS. Inheriting classes
 * are intended to define their own specifics.
 */
abstract class AbstractSpecifics
{
    /**
     * Constants used as identifiers in $specificProperties.
     */
    const TABLE_MAXLENGTH = 'table_maxlength';
    const FIELD_MAXLENGTH = 'field_maxlength';
    const LIST_MAXEXPRESSIONS = 'list_maxexpressions';
    const PARTIAL_STRING_INDEX = 'partial_string_index';
    const CAST_FIND_IN_SET = 'cast_find_in_set';

    /**
     * Contains the specifics of a DBMS.
     * This is intended to be overridden by inheriting classes.
     *
     * @var array
     */
    protected $specificProperties = [];

    /**
     * Contains the DBMS specific mapping information for native MySQL to ADOdb meta field types
     *
     * @var array
     */
    protected $nativeToMetaFieldTypeMap = [
        'STRING' => 'C',
        'CHAR' => 'C',
        'VARCHAR' => 'C',
        'TINYBLOB' => 'C',
        'TINYTEXT' => 'C',
        'ENUM' => 'C',
        'SET' => 'C',
        'TEXT' => 'XL',
        'LONGTEXT' => 'XL',
        'MEDIUMTEXT' => 'XL',
        'IMAGE' => 'B',
        'LONGBLOB' => 'B',
        'BLOB' => 'B',
        'MEDIUMBLOB' => 'B',
        'YEAR' => 'D',
        'DATE' => 'D',
        'TIME' => 'T',
        'DATETIME' => 'T',
        'TIMESTAMP' => 'T',
        'FLOAT' => 'F',
        'DOUBLE' => 'F',
        'INT' => 'I8',
        'INTEGER' => 'I8',
        'TINYINT' => 'I8',
        'SMALLINT' => 'I8',
        'MEDIUMINT' => 'I8',
        'BIGINT' => 'I8',
    ];

    /**
     * Contains the DBMS specific mapping overrides for native MySQL to ADOdb meta field types
     */
    protected $nativeToMetaFieldTypeOverrides = [];

    /**
     * Contains the default mapping information for ADOdb meta to MySQL native field types
     *
     * @var array
     */
    protected $metaToNativeFieldTypeMap = [
        'C' => 'VARCHAR',
        'C2' => 'VARCHAR',
        'X' => 'LONGTEXT',
        'XL' => 'LONGTEXT',
        'X2' => 'LONGTEXT',
        'B' => 'LONGBLOB',
        'D' => 'DATE',
        'T' => 'DATETIME',
        'L' => 'TINYINT',
        'I' => 'BIGINT',
        'I1' => 'BIGINT',
        'I2' => 'BIGINT',
        'I4' => 'BIGINT',
        'I8' => 'BIGINT',
        'F' => 'DOUBLE',
        'N' => 'NUMERIC'
    ];

    /**
     * Contains the DBMS specific mapping information for ADOdb meta field types to MySQL native field types
     *
     * @var array
     */
    protected $metaToNativeFieldTypeOverrides = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->nativeToMetaFieldTypeMap = array_merge($this->nativeToMetaFieldTypeMap, $this->nativeToMetaFieldTypeOverrides);
        $this->metaToNativeFieldTypeMap = array_merge($this->metaToNativeFieldTypeMap, $this->metaToNativeFieldTypeOverrides);
    }

    /**
     * Checks if a specific is defined for the used DBMS.
     *
     * @param string $specific
     * @return bool
     */
    public function specificExists($specific)
    {
        return isset($this->specificProperties[$specific]);
    }

    /**
     * Gets the specific value.
     *
     * @param string $specific
     * @return mixed
     */
    public function getSpecific($specific)
    {
        return $this->specificProperties[$specific];
    }

    /**
     * Splits $expressionList into multiple chunks.
     *
     * @param array $expressionList
     * @param bool $preserveArrayKeys If TRUE, array keys are preserved in array_chunk()
     * @return array
     */
    public function splitMaxExpressions($expressionList, $preserveArrayKeys = false)
    {
        if (!$this->specificExists(self::LIST_MAXEXPRESSIONS)) {
            return [$expressionList];
        }

        return array_chunk($expressionList, $this->getSpecific(self::LIST_MAXEXPRESSIONS), $preserveArrayKeys);
    }

    /**
     * Truncates the name of the identifier.
     * Based on TYPO3.FLOWs' FlowAnnotationDriver::truncateIdentifier()
     *
     * @param string $identifier
     * @param string $specific
     * @return string
     */
    public function truncateIdentifier($identifier, $specific)
    {
        if (!$this->specificExists($specific)) {
            return $identifier;
        }

        $maxLength = $this->getSpecific($specific);
        if (strlen($identifier) > $maxLength) {
            $truncateChars = 10;
            $identifier = substr($identifier, 0, $maxLength - $truncateChars) . '_' . substr(sha1($identifier), 0, $truncateChars - 1);
        }

        return $identifier;
    }

    /**
     * Adjust query parts for DBMS
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
    }

    /**
     * Transforms a database specific representation of field information and translates it
     * as close as possible to the MySQL standard.
     *
     * @param array $fieldRow
     * @param string $metaType
     * @return array
     */
    public function transformFieldRowToMySQL($fieldRow, $metaType)
    {
        $mysqlType = $this->getNativeFieldType($metaType);
        $mysqlType .= $this->getNativeFieldLength($mysqlType, $fieldRow['max_length']);

        $fieldRow['Field'] = $fieldRow['name'];
        $fieldRow['Type'] = strtolower($mysqlType);
        $fieldRow['Null'] = $this->getNativeNotNull($fieldRow['not_null']);
        $fieldRow['Key'] = $this->getNativeKeyForField($fieldRow);
        $fieldRow['Default'] = $this->getNativeDefaultValue($fieldRow);
        $fieldRow['Extra'] = $this->getNativeExtraFieldAttributes($fieldRow);

        return $fieldRow;
    }

    /**
     * Return actual MySQL type for meta field type
     *
     * @param string $metaType Meta type (currenly ADOdb syntax only, http://phplens.com/lens/adodb/docs-adodb.htm#metatype)
     * @return string Native type as reported as in mysqldump files, uppercase
     */
    public function getNativeFieldType($metaType)
    {
        $metaType = strtoupper($metaType);
        return empty($this->metaToNativeFieldTypeMap[$metaType]) ? $metaType : $this->metaToNativeFieldTypeMap[$metaType];
    }

    /**
     * Return MetaType for native MySQL field type
     *
     * @param string $nativeType native type as reported as in mysqldump files
     * @return string Meta type (currently ADOdb syntax only, http://phplens.com/lens/adodb/docs-adodb.htm#metatype)
     */
    public function getMetaFieldType($nativeType)
    {
        $nativeType = strtoupper($nativeType);
        return empty($this->nativeToMetaFieldTypeMap[$nativeType]) ? 'N' : $this->nativeToMetaFieldTypeMap[$nativeType];
    }

    /**
     * Determine the native field length information for a table field.
     *
     * @param string  $mysqlType
     * @param int $maxLength
     * @return string
     */
    public function getNativeFieldLength($mysqlType, $maxLength)
    {
        if ($maxLength === -1) {
            return '';
        }
        switch ($mysqlType) {
            case 'INT':
                return '(11)';
            default:
                return '(' . $maxLength . ')';
        }
    }

    /**
     * Return the MySQL native representation of the NOT NULL setting
     *
     * @param mixed $notNull
     * @return string
     */
    protected function getNativeNotNull($notNull)
    {
        return (bool)$notNull ? 'NO' : 'YES';
    }

    /**
     * Return the default value of a field formatted to match the native MySQL SQL dialect
     *
     * @param array $fieldDefinition
     * @return mixed
     */
    protected function getNativeDefaultValue($fieldDefinition)
    {
        return $fieldDefinition['default_value'];
    }

    /**
     * Return the MySQL native key type indicator - https://dev.mysql.com/doc/refman/5.5/en/show-columns.html
     * PRI - the column is a PRIMARY KEY or is one of the columns in a multiple-column PRIMARY KEY
     * UNI - the column is the first column of a UNIQUE index
     * MUL - the column is the first column of a nonunique index
     * If more than one of the values applies return the one with the highest priority, in the order PRI, UNI, MUL
     * If none applies return empty value.
     *
     * @param array $fieldRow
     * @return string
     */
    protected function getNativeKeyForField($fieldRow)
    {
        return '';
    }

    /**
     * Return the MySQL native extra field information - https://dev.mysql.com/doc/refman/5.5/en/show-columns.html
     * auto_increment for columns that have the AUTO_INCREMENT attribute
     * on update CURRENT_TIMESTAMP for TIMESTAMP columns that have the ON UPDATE CURRENT_TIMESTAMP attribute.
     *
     * @param array $fieldRow
     * @return string
     */
    protected function getNativeExtraFieldAttributes($fieldRow)
    {
        return '';
    }
}
