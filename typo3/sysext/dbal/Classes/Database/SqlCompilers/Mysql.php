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

use TYPO3\CMS\Dbal\Database\SqlParser;

/**
 * SQL Compiler for native MySQL connections
 */
class Mysql extends AbstractCompiler
{
    /**
     * Compiles an INSERT statement from components array
     *
     * @param array $components Array of SQL query components
     * @return string SQL INSERT query
     * @see parseINSERT()
     */
    protected function compileINSERT($components)
    {
        $values = [];
        if (isset($components['VALUES_ONLY']) && is_array($components['VALUES_ONLY'])) {
            $valuesComponents = $components['EXTENDED'] === '1' ? $components['VALUES_ONLY'] : [$components['VALUES_ONLY']];
            $tableFields = [];
        } else {
            $valuesComponents = $components['EXTENDED'] === '1' ? $components['FIELDS'] : [$components['FIELDS']];
            $tableFields = array_keys($valuesComponents[0]);
        }
        foreach ($valuesComponents as $valuesComponent) {
            $fields = [];
            foreach ($valuesComponent as $fV) {
                $fields[] = $fV[1] . $this->compileAddslashes($fV[0]) . $fV[1];
            }
            $values[] = '(' . implode(',', $fields) . ')';
        }
        // Make query:
        $query = 'INSERT INTO ' . $components['TABLE'];
        if (!empty($tableFields)) {
            $query .= ' (' . implode(',', $tableFields) . ')';
        }
        $query .= ' VALUES ' . implode(',', $values);

        return $query;
    }

    /**
     * Compiles a CREATE TABLE statement from components array
     *
     * @param array $components Array of SQL query components
     * @return string SQL CREATE TABLE query
     * @see parseCREATETABLE()
     */
    protected function compileCREATETABLE($components)
    {
        // Create fields and keys:
        $fieldsKeys = [];
        foreach ($components['FIELDS'] as $fN => $fCfg) {
            $fieldsKeys[] = $fN . ' ' . $this->compileFieldCfg($fCfg['definition']);
        }
        if ($components['KEYS']) {
            foreach ($components['KEYS'] as $kN => $kCfg) {
                if ($kN === 'PRIMARYKEY') {
                    $fieldsKeys[] = 'PRIMARY KEY (' . implode(',', $kCfg) . ')';
                } elseif ($kN === 'UNIQUE') {
                    $key = key($kCfg);
                    $fields = current($kCfg);
                    $fieldsKeys[] = 'UNIQUE KEY ' . $key . ' (' . implode(',', $fields) . ')';
                } else {
                    $fieldsKeys[] = 'KEY ' . $kN . ' (' . implode(',', $kCfg) . ')';
                }
            }
        }
        // Make query:
        $query = 'CREATE TABLE ' . $components['TABLE'] . ' (' .
            implode(',', $fieldsKeys) . ')' .
            ($components['engine'] ? ' ENGINE=' . $components['engine'] : '');

        return $query;
    }

    /**
     * Compiles an ALTER TABLE statement from components array
     *
     * @param array $components Array of SQL query components
     * @return string SQL ALTER TABLE query
     * @see parseALTERTABLE()
     */
    protected function compileALTERTABLE($components)
    {
        // Make query:
        $query = 'ALTER TABLE ' . $components['TABLE'] . ' ' . $components['action'] . ' ' . ($components['FIELD'] ?: $components['KEY']);
        // Based on action, add the final part:
        switch (SqlParser::normalizeKeyword($components['action'])) {
            case 'ADD':
                $query .= ' ' . $this->compileFieldCfg($components['definition']);
                break;
            case 'CHANGE':
                $query .= ' ' . $components['newField'] . ' ' . $this->compileFieldCfg($components['definition']);
                break;
            case 'DROP':
            case 'DROPKEY':
                break;
            case 'ADDKEY':
            case 'ADDPRIMARYKEY':
            case 'ADDUNIQUE':
                $query .= ' (' . implode(',', $components['fields']) . ')';
                break;
            case 'DEFAULTCHARACTERSET':
                $query .= $components['charset'];
                break;
            case 'ENGINE':
                $query .= '= ' . $components['engine'];
                break;
        }
        // Return query
        return $query;
    }

    /**
     * Compiles a "SELECT [output] FROM..:" field list based on input array (made with ->parseFieldList())
     * Can also compile field lists for ORDER BY and GROUP BY.
     *
     * @param array $selectFields Array of select fields, (made with ->parseFieldList())
     * @param bool $compileComments Whether comments should be compiled
     * @param bool $functionMapping
     * @return string Select field string
     * @see parseFieldList()
     */
    public function compileFieldList($selectFields, $compileComments = true, $functionMapping = true)
    {
        // Prepare buffer variable:
        $fields = '';
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
                            $outputParts[$k] = $this->compileCaseStatement($v['flow-control']);
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
            if ($compileComments && $selectFields[0]['comments']) {
                $fields = $selectFields[0]['comments'] . ' ';
            }
            $fields .= implode(', ', $outputParts);
        }
        return $fields;
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
        $search = ['\\', '\'', '"', "\x00", "\x0a", "\x0d", "\x1a"];
        $replace = ['\\\\', '\\\'', '\\"', '\0', '\n', '\r', '\Z'];

        return str_replace($search, $replace, $str);
    }

    /**
     * Compile field definition
     *
     * @param array $fieldCfg Field definition parts
     * @return string Field definition string
     */
    public function compileFieldCfg($fieldCfg)
    {
        // Set type:
        $cfg = $fieldCfg['fieldType'];
        // Add value, if any:
        if ((string)$fieldCfg['value'] !== '') {
            $cfg .= '(' . $fieldCfg['value'] . ')';
        }
        // Add additional features:
        if (is_array($fieldCfg['featureIndex'])) {
            foreach ($fieldCfg['featureIndex'] as $featureDef) {
                $cfg .= ' ' . $featureDef['keyword'];
                // Add value if found:
                if (is_array($featureDef['value'])) {
                    $cfg .= ' ' . $featureDef['value'][1] . $this->compileAddslashes($featureDef['value'][0]) . $featureDef['value'][1];
                }
            }
        }
        // Return field definition string:
        return $cfg;
    }

    /**
     * Implodes an array of WHERE clause configuration into a WHERE clause.
     *
     * @param array $clauseArray WHERE clause configuration
     * @param bool $functionMapping
     * @return string WHERE clause as string.
     * @see explodeWhereClause()
     */
    public function compileWhereClause($clauseArray, $functionMapping = true)
    {
        // Prepare buffer variable:
        $output = '';
        // Traverse clause array:
        if (is_array($clauseArray)) {
            foreach ($clauseArray as $k => $v) {
                // Set operator:
                $output .= $v['operator'] ? ' ' . $v['operator'] : '';
                // Look for sublevel:
                if (is_array($v['sub'])) {
                    $output .= ' (' . trim($this->compileWhereClause($v['sub'])) . ')';
                } elseif (isset($v['func']) && $v['func']['type'] === 'EXISTS') {
                    $output .= ' ' . trim($v['modifier']) . ' EXISTS (' . $this->compileSELECT($v['func']['subquery']) . ')';
                } else {
                    if (isset($v['func']) && $v['func']['type'] === 'LOCATE') {
                        $output .= ' ' . trim($v['modifier']) . ' LOCATE(';
                        $output .= $v['func']['substr'][1] . $v['func']['substr'][0] . $v['func']['substr'][1];
                        $output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                        $output .= isset($v['func']['pos']) ? ', ' . $v['func']['pos'][0] : '';
                        $output .= ')';
                    } elseif (isset($v['func']) && $v['func']['type'] === 'IFNULL') {
                        $output .= ' ' . trim($v['modifier']) . ' IFNULL(';
                        $output .= ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                        $output .= ', ' . $v['func']['default'][1] . $this->compileAddslashes($v['func']['default'][0]) . $v['func']['default'][1];
                        $output .= ')';
                    } elseif (isset($v['func']) && $v['func']['type'] === 'CAST') {
                        $output .= ' ' . trim($v['modifier']) . ' CAST(';
                        $output .= ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                        $output .= ' AS ' . $v['func']['datatype'][0];
                        $output .= ')';
                    } elseif (isset($v['func']) && $v['func']['type'] === 'FIND_IN_SET') {
                        $output .= ' ' . trim($v['modifier']) . ' FIND_IN_SET(';
                        $output .= $v['func']['str'][1] . $v['func']['str'][0] . $v['func']['str'][1];
                        $output .= ', ' . ($v['func']['table'] ? $v['func']['table'] . '.' : '') . $v['func']['field'];
                        $output .= ')';
                    } else {
                        // Set field/table with modifying prefix if any:
                        $output .= ' ' . trim($v['modifier'] . ' ' . ($v['table'] ? $v['table'] . '.' : '') . $v['field']);
                        // Set calculation, if any:
                        if ($v['calc']) {
                            $output .= $v['calc'] . $v['calc_value'][1] . $this->compileAddslashes($v['calc_value'][0]) . $v['calc_value'][1];
                        }
                    }
                    // Set comparator:
                    if ($v['comparator']) {
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
                                $output .= ' (' . trim(implode(',', $valueBuffer)) . ')';
                            }
                        } elseif ($comparator === 'BETWEEN' || $comparator === 'NOTBETWEEN') {
                            $lbound = $v['values'][0];
                            $ubound = $v['values'][1];
                            $output .= ' ' . $lbound[1] . $this->compileAddslashes($lbound[0]) . $lbound[1];
                            $output .= ' AND ';
                            $output .= $ubound[1] . $this->compileAddslashes($ubound[0]) . $ubound[1];
                        } else {
                            if (isset($v['value']['operator'])) {
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
        // Return output buffer:
        return $output;
    }
}
