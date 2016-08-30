<?php
namespace TYPO3\CMS\Dbal\Database\SqlCompilers;

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
use TYPO3\CMS\Dbal\Database\Specifics;
use TYPO3\CMS\Dbal\Database\SqlParser;

/**
 * SQL Compiler for ADOdb connections
 */
class Adodb extends AbstractCompiler
{
    /**
     * Compiles an INSERT statement from components array
     *
     * @param array Array of SQL query components
     * @return string SQL INSERT query / array
     * @see parseINSERT()
     */
    protected function compileINSERT($components)
    {
        $values = [];
        if (isset($components['VALUES_ONLY']) && is_array($components['VALUES_ONLY'])) {
            $valuesComponents = $components['EXTENDED'] === '1' ? $components['VALUES_ONLY'] : [$components['VALUES_ONLY']];
            $tableFields = array_keys($this->databaseConnection->cache_fieldType[$components['TABLE']]);
        } else {
            $valuesComponents = $components['EXTENDED'] === '1' ? $components['FIELDS'] : [$components['FIELDS']];
            $tableFields = array_keys($valuesComponents[0]);
        }
        foreach ($valuesComponents as $valuesComponent) {
            $fields = [];
            $fc = 0;
            foreach ($valuesComponent as $fV) {
                $fields[$tableFields[$fc++]] = $fV[0];
            }
            $values[] = $fields;
        }
        return count($values) === 1 ? $values[0] : $values;
    }

    /**
     * Compiles a CREATE TABLE statement from components array
     *
     * @param array $components Array of SQL query components
     * @return array array with SQL CREATE TABLE/INDEX command(s)
     * @see parseCREATETABLE()
     */
    protected function compileCREATETABLE($components)
    {
        // Create fields and keys:
        $fieldsKeys = [];
        $indexKeys = [];
        foreach ($components['FIELDS'] as $fN => $fCfg) {
            $handlerKey = $this->databaseConnection->handler_getFromTableList($components['TABLE']);
            $fieldsKeys[$fN] = $this->databaseConnection->quoteName($fN, $handlerKey, true) . ' ' . $this->compileFieldCfg($fCfg['definition']);
        }
        if (isset($components['KEYS']) && is_array($components['KEYS'])) {
            foreach ($components['KEYS'] as $kN => $kCfg) {
                if ($kN === 'PRIMARYKEY') {
                    foreach ($kCfg as $field) {
                        $fieldsKeys[$field] .= ' PRIMARY';
                    }
                } elseif ($kN === 'UNIQUE') {
                    foreach ($kCfg as $n => $field) {
                        $indexKeys = array_merge($indexKeys, $this->compileCREATEINDEX($n, $components['TABLE'], $field, ['UNIQUE']));
                    }
                } else {
                    $indexKeys = array_merge($indexKeys, $this->compileCREATEINDEX($kN, $components['TABLE'], $kCfg));
                }
            }
        }
        // Generally create without OID on PostgreSQL
        $tableOptions = ['postgres' => 'WITHOUT OIDS'];
        // Fetch table/index generation query:
        $tableName = $this->databaseConnection->quoteName($components['TABLE'], null, true);
        $query = array_merge($this->databaseConnection->handlerInstance[$this->databaseConnection->lastHandlerKey]->DataDictionary->CreateTableSQL($tableName, implode(',' . LF, $fieldsKeys), $tableOptions), $indexKeys);
        return $query;
    }

    /**
     * Compiles an ALTER TABLE statement from components array
     *
     * @param array Array of SQL query components
     * @return string SQL ALTER TABLE query
     * @see parseALTERTABLE()
     */
    protected function compileALTERTABLE($components)
    {
        $query = '';
        $tableName = $this->databaseConnection->quoteName($components['TABLE'], null, true);
        $fieldName = $this->databaseConnection->quoteName($components['FIELD'], null, true);
        switch (strtoupper(str_replace([' ', "\n", "\r", "\t"], '', $components['action']))) {
            case 'ADD':
                $query = $this->databaseConnection->handlerInstance[$this->databaseConnection->lastHandlerKey]->DataDictionary->AddColumnSQL($tableName, $fieldName . ' ' . $this->compileFieldCfg($components['definition']));
                break;
            case 'CHANGE':
                $query = $this->databaseConnection->handlerInstance[$this->databaseConnection->lastHandlerKey]->DataDictionary->AlterColumnSQL($tableName, $fieldName . ' ' . $this->compileFieldCfg($components['definition']));
                break;
            case 'RENAME':
                $query = $this->databaseConnection->handlerInstance[$this->databaseConnection->lastHandlerKey]->DataDictionary->RenameTableSQL($tableName, $fieldName);
                break;
            case 'DROP':

            case 'DROPKEY':
                $query = $this->compileDROPINDEX($components['KEY'], $components['TABLE']);
                break;

            case 'ADDKEY':
                $query = $this->compileCREATEINDEX($components['KEY'], $components['TABLE'], $components['fields']);
                break;
            case 'ADDUNIQUE':
                $query = $this->compileCREATEINDEX($components['KEY'], $components['TABLE'], $components['fields'], ['UNIQUE']);
                break;
            case 'ADDPRIMARYKEY':
                // @todo ???
                break;
            case 'DEFAULTCHARACTERSET':

            case 'ENGINE':
                // @todo ???
                break;
        }
        return $query;
    }

    /**
     * Compiles CREATE INDEX statements from component information
     *
     * MySQL only needs uniqueness of index names per table, but many DBMS require uniqueness of index names per schema.
     * The table name is hashed and prepended to the index name to make sure index names are unique.
     *
     * @param string $indexName
     * @param string $tableName
     * @param array $indexFields
     * @param array $indexOptions
     * @return array
     * @see compileALTERTABLE()
     */
    protected function compileCREATEINDEX($indexName, $tableName, $indexFields, $indexOptions = [])
    {
        $indexIdentifier = $this->databaseConnection->quoteName(hash('crc32b', $tableName) . '_' . $indexName, null, true);
        $dbmsSpecifics = $this->databaseConnection->getSpecifics();
        $keepFieldLengths = $dbmsSpecifics->specificExists(Specifics\AbstractSpecifics::PARTIAL_STRING_INDEX) && $dbmsSpecifics->getSpecific(Specifics\AbstractSpecifics::PARTIAL_STRING_INDEX);

        foreach ($indexFields as $key => $fieldName) {
            if (!$keepFieldLengths) {
                $fieldName = preg_replace('/\A([^\(]+)(\(\d+\))/', '\\1', $fieldName);
            }
            // Quote the fieldName in backticks with escaping, ADOdb will replace the backticks with the correct quoting
            $indexFields[$key] = '`' . str_replace('`', '``', $fieldName) . '`';
        }

        return $this->databaseConnection->handlerInstance[$this->databaseConnection->handler_getFromTableList($tableName)]->DataDictionary->CreateIndexSQL(
            $indexIdentifier, $this->databaseConnection->quoteName($tableName, null, true), $indexFields, $indexOptions
        );
    }

    /**
     * Compiles DROP INDEX statements from component information
     *
     * MySQL only needs uniqueness of index names per table, but many DBMS require uniqueness of index names per schema.
     * The table name is hashed and prepended to the index name to make sure index names are unique.
     *
     * @param $indexName
     * @param $tableName
     * @return array
     * @see compileALTERTABLE()
     */
    protected function compileDROPINDEX($indexName, $tableName)
    {
        $indexIdentifier = $this->databaseConnection->quoteName(hash('crc32b', $tableName) . '_' . $indexName, null, true);

        return $this->databaseConnection->handlerInstance[$this->databaseConnection->handler_getFromTableList($tableName)]->DataDictionary->DropIndexSQL(
            $indexIdentifier, $this->databaseConnection->quoteName($tableName)
        );
    }

    /**
     * Compiles a "SELECT [output] FROM..:" field list based on input array (made with ->parseFieldList())
     * Can also compile field lists for ORDER BY and GROUP BY.
     *
     * @param array $selectFields Array of select fields, (made with ->parseFieldList())
     * @param bool $compileComments Whether comments should be compiled
     * @param bool $functionMapping Whether function mapping should take place
     * @return string Select field string
     * @see parseFieldList()
     */
    public function compileFieldList($selectFields, $compileComments = true, $functionMapping = true)
    {
        $output = '';
        // Traverse the selectFields if any:
        if (is_array($selectFields)) {
            $outputParts = [];
            foreach ($selectFields as $k => $v) {
                // Detecting type:
                switch ($v['type']) {
                    case 'function':
                        $outputParts[$k] = $v['function'] . '(' . $v['func_content'] . ')';
                        break;
                    case 'flow-control':
                        if ($v['flow-control']['type'] === 'CASE') {
                            $outputParts[$k] = $this->compileCaseStatement($v['flow-control'], $functionMapping);
                        }
                        break;
                    case 'field':
                        $outputParts[$k] = ($v['distinct'] ? $v['distinct'] : '') . ($v['table'] ? $v['table'] . '.' : '') . $v['field'];
                        break;
                }
                // Alias:
                if ($v['as']) {
                    $outputParts[$k] .= ' ' . $v['as_keyword'] . ' ' . $v['as'];
                }
                // Specifically for ORDER BY and GROUP BY field lists:
                if ($v['sortDir']) {
                    $outputParts[$k] .= ' ' . $v['sortDir'];
                }
            }
            // @todo Handle SQL hints in comments according to current DBMS
            if (false && $selectFields[0]['comments']) {
                $output = $selectFields[0]['comments'] . ' ';
            }
            $output .= implode(', ', $outputParts);
        }
        return $output;
    }

    /**
     * Add slashes function used for compiling queries
     * This method overrides the method from \TYPO3\CMS\Dbal\Database\NativeSqlParser because
     * the input string is already properly escaped.
     *
     * @param string $str Input string
     * @return string Output string
     */
    protected function compileAddslashes($str)
    {
        return $str;
    }

    /**
     * Compile field definition
     *
     * @param array $fieldCfg Field definition parts
     * @return string Field definition string
     */
    protected function compileFieldCfg($fieldCfg)
    {
        // Set type:
        $type = $this->databaseConnection->getSpecifics()->getMetaFieldType($fieldCfg['fieldType']);
        $cfg = $type;
        // Add value, if any:
        if ((string)$fieldCfg['value'] !== '' && in_array($type, ['C', 'C2'])) {
            $cfg .= ' ' . $fieldCfg['value'];
        } elseif (!isset($fieldCfg['value']) && in_array($type, ['C', 'C2'])) {
            $cfg .= ' 255';
        }
        // Add additional features:
        $noQuote = true;
        if (is_array($fieldCfg['featureIndex'])) {
            // MySQL assigns DEFAULT value automatically if NOT NULL, fake this here
            // numeric fields get 0 as default, other fields an empty string
            if (isset($fieldCfg['featureIndex']['NOTNULL']) && !isset($fieldCfg['featureIndex']['DEFAULT']) && !isset($fieldCfg['featureIndex']['AUTO_INCREMENT'])) {
                switch ($type) {
                    case 'I8':

                    case 'F':

                    case 'N':
                        $fieldCfg['featureIndex']['DEFAULT'] = ['keyword' => 'DEFAULT', 'value' => ['0', '']];
                        break;
                    default:
                        $fieldCfg['featureIndex']['DEFAULT'] = ['keyword' => 'DEFAULT', 'value' => ['', '\'']];
                }
            }
            foreach ($fieldCfg['featureIndex'] as $feature => $featureDef) {
                switch (true) {
                    case $feature === 'UNSIGNED' && !$this->databaseConnection->runningADOdbDriver('mysql'):
                    case $feature === 'NOTNULL' && $this->databaseConnection->runningADOdbDriver('oci8'):
                        continue;
                    case $feature === 'AUTO_INCREMENT':
                        $cfg .= ' AUTOINCREMENT';
                        break;
                    case $feature === 'NOTNULL':
                        $cfg .= ' NOTNULL';
                        break;
                    default:
                        $cfg .= ' ' . $featureDef['keyword'];
                }
                // Add value if found:
                if (is_array($featureDef['value'])) {
                    if ($featureDef['value'][0] === '') {
                        $cfg .= ' "\'\'"';
                    } else {
                        $cfg .= ' ' . $featureDef['value'][1] . $this->compileAddslashes($featureDef['value'][0]) . $featureDef['value'][1];
                        if (!is_numeric($featureDef['value'][0])) {
                            $noQuote = false;
                        }
                    }
                }
            }
        }
        if ($noQuote) {
            $cfg .= ' NOQUOTE';
        }
        // Return field definition string:
        return $cfg;
    }

    /**
     * Implodes an array of WHERE clause configuration into a WHERE clause.
     *
     * DBAL-specific: The only(!) handled "calc" operators supported by parseWhereClause() are:
     * - the bitwise logical and (&)
     * - the addition (+)
     * - the substraction (-)
     * - the multiplication (*)
     * - the division (/)
     * - the modulo (%)
     *
     * @param array $clauseArray
     * @param bool $functionMapping
     * @return string WHERE clause as string.
     * @see \TYPO3\CMS\Core\Database\SqlParser::parseWhereClause()
     */
    public function compileWhereClause($clauseArray, $functionMapping = true)
    {
        // Prepare buffer variable:
        $output = '';
        // Traverse clause array:
        if (is_array($clauseArray)) {
            foreach ($clauseArray as $v) {
                // Set operator:
                $output .= $v['operator'] ? ' ' . $v['operator'] : '';
                // Look for sublevel:
                if (is_array($v['sub'])) {
                    $output .= ' (' . trim($this->compileWhereClause($v['sub'], $functionMapping)) . ')';
                } elseif (isset($v['func']) && $v['func']['type'] === 'EXISTS') {
                    $output .= ' ' . trim($v['modifier']) . ' EXISTS (' . $this->compileSELECT($v['func']['subquery']) . ')';
                } else {
                    if (isset($v['func']) && $v['func']['type'] === 'CAST') {
                        $output .= ' ' . trim($v['modifier']);
                        $output .= ' CAST(';
                        $output .= ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                        $output .= ' AS ' . $v['func']['datatype'][0] . ')';
                    } elseif (isset($v['func']) && $v['func']['type'] === 'LOCATE') {
                        $output .= ' ' . trim($v['modifier']);
                        switch (true) {
                            case $this->databaseConnection->runningADOdbDriver('mssql') && $functionMapping:
                                $output .= ' CHARINDEX(';
                                $output .= $v['func']['substr'][1] . $v['func']['substr'][0] . $v['func']['substr'][1];
                                $output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                                $output .= isset($v['func']['pos']) ? ', ' . $v['func']['pos'][0] : '';
                                $output .= ')';
                                break;
                            case $this->databaseConnection->runningADOdbDriver('oci8') && $functionMapping:
                                $output .= ' INSTR(';
                                $output .= ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                                $output .= ', ' . $v['func']['substr'][1] . $v['func']['substr'][0] . $v['func']['substr'][1];
                                $output .= isset($v['func']['pos']) ? ', ' . $v['func']['pos'][0] : '';
                                $output .= ')';
                                break;
                            default:
                                $output .= ' LOCATE(';
                                $output .= $v['func']['substr'][1] . $v['func']['substr'][0] . $v['func']['substr'][1];
                                $output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                                $output .= isset($v['func']['pos']) ? ', ' . $v['func']['pos'][0] : '';
                                $output .= ')';
                        }
                    } elseif (isset($v['func']) && $v['func']['type'] === 'IFNULL') {
                        $output .= ' ' . trim($v['modifier']) . ' ';
                        switch (true) {
                            case $this->databaseConnection->runningADOdbDriver('mssql') && $functionMapping:
                                $output .= 'ISNULL';
                                break;
                            case $this->databaseConnection->runningADOdbDriver('oci8') && $functionMapping:
                                $output .= 'NVL';
                                break;
                            default:
                                $output .= 'IFNULL';
                        }
                        $output .= '(';
                        $output .= ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                        $output .= ', ' . $v['func']['default'][1] . $this->compileAddslashes($v['func']['default'][0]) . $v['func']['default'][1];
                        $output .= ')';
                    } elseif (isset($v['func']) && $v['func']['type'] === 'FIND_IN_SET') {
                        $output .= ' ' . trim($v['modifier']) . ' ';
                        if ($functionMapping) {
                            switch (true) {
                                case $this->databaseConnection->runningADOdbDriver('mssql'):
                                    $field = ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                                    if (!isset($v['func']['str_like'])) {
                                        $v['func']['str_like'] = $v['func']['str'][0];
                                    }
                                    $output .= '\',\'+' . $field . '+\',\' LIKE \'%,' . $v['func']['str_like'] . ',%\'';
                                    break;
                                case $this->databaseConnection->runningADOdbDriver('oci8'):
                                    $field = ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                                    if (!isset($v['func']['str_like'])) {
                                        $v['func']['str_like'] = $v['func']['str'][0];
                                    }
                                    $output .= '\',\'||' . $field . '||\',\' LIKE \'%,' . $v['func']['str_like'] . ',%\'';
                                    break;
                                case $this->databaseConnection->runningADOdbDriver('postgres'):
                                    $output .= ' FIND_IN_SET(';
                                    $output .= $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1];
                                    $output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                                    $output .= ') != 0';
                                    break;
                                default:
                                    $field = ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                                    if (!isset($v['func']['str_like'])) {
                                        $v['func']['str_like'] = $v['func']['str'][0];
                                    }
                                    $output .= '(' . $field . ' LIKE \'%,' . $v['func']['str_like'] . ',%\'' . ' OR ' . $field . ' LIKE \'' . $v['func']['str_like'] . ',%\'' . ' OR ' . $field . ' LIKE \'%,' . $v['func']['str_like'] . '\'' . ' OR ' . $field . '= ' . $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1] . ')';
                            }
                        } else {
                            switch (true) {
                                case $this->databaseConnection->runningADOdbDriver('mssql'):

                                case $this->databaseConnection->runningADOdbDriver('oci8'):

                                case $this->databaseConnection->runningADOdbDriver('postgres'):
                                    $output .= ' FIND_IN_SET(';
                                    $output .= $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1];
                                    $output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                                    $output .= ')';
                                    break;
                                default:
                                    $field = ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                                    if (!isset($v['func']['str_like'])) {
                                        $v['func']['str_like'] = $v['func']['str'][0];
                                    }
                                    $output .= '(' . $field . ' LIKE \'%,' . $v['func']['str_like'] . ',%\'' . ' OR ' . $field . ' LIKE \'' . $v['func']['str_like'] . ',%\'' . ' OR ' . $field . ' LIKE \'%,' . $v['func']['str_like'] . '\'' . ' OR ' . $field . '= ' . $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1] . ')';
                            }
                        }
                    } else {
                        // Set field/table with modifying prefix if any:
                        $output .= ' ' . trim($v['modifier']) . ' ';
                        // DBAL-specific: Set calculation, if any:
                        if ($v['calc'] === '&' && $functionMapping) {
                            switch (true) {
                                case $this->databaseConnection->runningADOdbDriver('oci8'):
                                    // Oracle only knows BITAND(x,y) - sigh
                                    $output .= 'BITAND(' . trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . ',' . $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1] . ')';
                                    break;
                                default:
                                    // MySQL, MS SQL Server, PostgreSQL support the &-syntax
                                    $output .= trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . $v['calc'] . $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1];
                            }
                        } elseif ($v['calc']) {
                            $output .= trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . $v['calc'];
                            if (isset($v['calc_table'])) {
                                $output .= trim(($v['calc_table'] ? $v['calc_table'] . '.' : '') . $v['calc_field']);
                            } else {
                                $output .= $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1];
                            }
                        } elseif (!($this->databaseConnection->runningADOdbDriver('oci8') && preg_match('/(NOT )?LIKE( BINARY)?/', $v['comparator']) && $functionMapping)) {
                            $output .= trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']);
                        }
                    }
                    // Set comparator:
                    if ($v['comparator']) {
                        $isLikeOperator = preg_match('/(NOT )?LIKE( BINARY)?/', $v['comparator']);
                        switch (true) {
                            case $this->databaseConnection->runningADOdbDriver('oci8') && $isLikeOperator && $functionMapping:
                                // Oracle cannot handle LIKE on CLOB fields - sigh
                                if (isset($v['value']['operator'])) {
                                    $values = [];
                                    foreach ($v['value']['args'] as $fieldDef) {
                                        $values[] = ($fieldDef['table'] ? $fieldDef['table'] . '.' : '') . $fieldDef['field'];
                                    }
                                    $compareValue = ' ' . $v['value']['operator'] . '(' . implode(',', $values) . ')';
                                } else {
                                    $compareValue = $v['value'][1] . $this->compileAddslashes(trim($v['value'][0], '%')) . $v['value'][1];
                                }
                                if (GeneralUtility::isFirstPartOfStr($v['comparator'], 'NOT')) {
                                    $output .= 'NOT ';
                                }
                                // To be on the safe side
                                $isLob = true;
                                if ($v['table']) {
                                    // Table and field names are quoted:
                                    $tableName = substr($v['table'], 1, strlen($v['table']) - 2);
                                    $fieldName = substr($v['field'], 1, strlen($v['field']) - 2);
                                    $fieldType = $this->databaseConnection->sql_field_metatype($tableName, $fieldName);
                                    $isLob = $fieldType === 'B' || $fieldType === 'XL';
                                }
                                if (strtoupper(substr($v['comparator'], -6)) === 'BINARY') {
                                    if ($isLob) {
                                        $output .= '(dbms_lob.instr(' . trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . ', ' . $compareValue . ',1,1) > 0)';
                                    } else {
                                        $output .= '(instr(' . trim((($v['table'] ? $v['table'] . '.' : '') . $v['field'])) . ', ' . $compareValue . ',1,1) > 0)';
                                    }
                                } else {
                                    if ($isLob) {
                                        $output .= '(dbms_lob.instr(LOWER(' . trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . '), ' . GeneralUtility::strtolower($compareValue) . ',1,1) > 0)';
                                    } else {
                                        $output .= '(instr(LOWER(' . trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']) . '), ' . GeneralUtility::strtolower($compareValue) . ',1,1) > 0)';
                                    }
                                }
                                break;
                            default:
                                if ($isLikeOperator && $functionMapping) {
                                    if ($this->databaseConnection->runningADOdbDriver('postgres') || $this->databaseConnection->runningADOdbDriver('postgres64') || $this->databaseConnection->runningADOdbDriver('postgres7') || $this->databaseConnection->runningADOdbDriver('postgres8')) {
                                        // Remap (NOT)? LIKE to (NOT)? ILIKE
                                        // and (NOT)? LIKE BINARY to (NOT)? LIKE
                                        switch ($v['comparator']) {
                                            case 'LIKE':
                                                $v['comparator'] = 'ILIKE';
                                                break;
                                            case 'NOT LIKE':
                                                $v['comparator'] = 'NOT ILIKE';
                                                break;
                                            default:
                                                $v['comparator'] = str_replace(' BINARY', '', $v['comparator']);
                                        }
                                    } else {
                                        // No more BINARY operator
                                        $v['comparator'] = str_replace(' BINARY', '', $v['comparator']);
                                    }
                                }
                                $output .= ' ' . $v['comparator'];
                                // Detecting value type; list or plain:
                                $comparator = SqlParser::normalizeKeyword($v['comparator']);
                                if ($comparator === 'NOTIN' || $comparator === 'IN') {
                                    if (isset($v['subquery'])) {
                                        $output .= ' (' . $this->compileSELECT($v['subquery']) . ')';
                                    } else {
                                        $valueBuffer = [];
                                        foreach ($v['value'] as $realValue) {
                                            $valueBuffer[] = $realValue[1] . $this->compileAddslashes($realValue[0]) . $realValue[1];
                                        }

                                        $dbmsSpecifics = $this->databaseConnection->getSpecifics();
                                        if ($dbmsSpecifics === null) {
                                            $output .= ' (' . trim(implode(',', $valueBuffer)) . ')';
                                        } else {
                                            $chunkedList = $dbmsSpecifics->splitMaxExpressions($valueBuffer);
                                            $chunkCount = count($chunkedList);

                                            if ($chunkCount === 1) {
                                                $output .= ' (' . trim(implode(',', $valueBuffer)) . ')';
                                            } else {
                                                $listExpressions = [];
                                                $field = trim(($v['table'] ? $v['table'] . '.' : '') . $v['field']);

                                                switch ($comparator) {
                                                    case 'IN':
                                                        $operator = 'OR';
                                                        break;
                                                    case 'NOTIN':
                                                        $operator = 'AND';
                                                        break;
                                                    default:
                                                        $operator = '';
                                                }

                                                for ($i = 0; $i < $chunkCount; ++$i) {
                                                    $listPart = trim(implode(',', $chunkedList[$i]));
                                                    $listExpressions[] = ' (' . $listPart . ')';
                                                }

                                                $implodeString = ' ' . $operator . ' ' . $field . ' ' . $v['comparator'];

                                                // add opening brace before field
                                                $lastFieldPos = strrpos($output, $field);
                                                $output = substr_replace($output, '(', $lastFieldPos, 0);
                                                $output .= implode($implodeString, $listExpressions) . ')';
                                            }
                                        }
                                    }
                                } elseif ($comparator === 'BETWEEN' || $comparator === 'NOTBETWEEN') {
                                    $lbound = $v['values'][0];
                                    $ubound = $v['values'][1];
                                    $output .= ' ' . $lbound[1] . $this->compileAddslashes($lbound[0]) . $lbound[1];
                                    $output .= ' AND ';
                                    $output .= $ubound[1] . $this->compileAddslashes($ubound[0]) . $ubound[1];
                                } elseif (isset($v['value']['operator'])) {
                                    $values = [];
                                    foreach ($v['value']['args'] as $fieldDef) {
                                        $values[] = ($fieldDef['table'] ? $fieldDef['table'] . '.' : '') . $fieldDef['field'];
                                    }
                                    $output .= ' ' . $v['value']['operator'] . '(' . implode(',', $values) . ')';
                                } else {
                                    $output .= ' ' . $v['value'][1] . $this->compileAddslashes($v['value'][0]) . $v['value'][1];
                                }
                        }
                    }
                }
            }
        }
        return $output;
    }
}
