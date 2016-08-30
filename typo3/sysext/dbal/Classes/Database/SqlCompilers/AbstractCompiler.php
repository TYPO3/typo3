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

use TYPO3\CMS\Dbal\Database\DatabaseConnection;

/**
 * Abstract base class for SQL compilers
 */
abstract class AbstractCompiler
{
    /**
     * @var \TYPO3\CMS\Dbal\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @param \TYPO3\CMS\Dbal\Database\DatabaseConnection $databaseConnection
     */
    public function __construct(DatabaseConnection $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * Compiles an SQL query from components
     *
     * @param array $components Array of SQL query components
     * @return string SQL query
     * @see parseSQL()
     */
    public function compileSQL($components)
    {
        $query = '';
        switch ($components['type']) {
            case 'SELECT':
                $query = $this->compileSELECT($components);
                break;
            case 'UPDATE':
                $query = $this->compileUPDATE($components);
                break;
            case 'INSERT':
                $query = $this->compileINSERT($components);
                break;
            case 'DELETE':
                $query = $this->compileDELETE($components);
                break;
            case 'EXPLAIN':
                $query = 'EXPLAIN ' . $this->compileSELECT($components);
                break;
            case 'DROPTABLE':
                $query = 'DROP TABLE' . ($components['ifExists'] ? ' IF EXISTS' : '') . ' ' . $components['TABLE'];
                break;
            case 'CREATETABLE':
                $query = $this->compileCREATETABLE($components);
                break;
            case 'ALTERTABLE':
                $query = $this->compileALTERTABLE($components);
                break;
            case 'TRUNCATETABLE':
                $query = $this->compileTRUNCATETABLE($components);
                break;
        }
        return $query;
    }

    /**
     * Compiles a SELECT statement from components array
     *
     * @param array $components Array of SQL query components
     * @return string SQL SELECT query
     * @see parseSELECT()
     */
    protected function compileSELECT($components)
    {
        // Initialize:
        $where = $this->compileWhereClause($components['WHERE']);
        $groupBy = $this->compileFieldList($components['GROUPBY']);
        $orderBy = $this->compileFieldList($components['ORDERBY']);
        $limit = $components['LIMIT'];
        // Make query:
        $query = 'SELECT ' . ($components['STRAIGHT_JOIN'] ?: '') . ' ' .
            $this->compileFieldList($components['SELECT']) .
            ' FROM ' . $this->compileFromTables($components['FROM']) . ($where !== '' ?
                ' WHERE ' . $where : '') . ($groupBy !== '' ?
                ' GROUP BY ' . $groupBy : '') . ($orderBy !== '' ?
                ' ORDER BY ' . $orderBy : '') . ((string)$limit !== '' ?
                ' LIMIT ' . $limit : '');
        return $query;
    }

    /**
     * Compiles an UPDATE statement from components array
     *
     * @param array $components Array of SQL query components
     * @return string SQL UPDATE query
     * @see parseUPDATE()
     */
    protected function compileUPDATE($components)
    {
        // Where clause:
        $where = $this->compileWhereClause($components['WHERE']);
        // Fields
        $fields = [];
        foreach ($components['FIELDS'] as $fN => $fV) {
            $fields[] = $fN . '=' . $fV[1] . $this->compileAddslashes($fV[0]) . $fV[1];
        }
        // Make query:
        $query = 'UPDATE ' . $components['TABLE'] . ' SET ' . implode(',', $fields) .
            ($where !== '' ? ' WHERE ' . $where : '');

        return $query;
    }

    /**
     * Compiles an INSERT statement from components array
     *
     * @param array $components Array of SQL query components
     * @return string SQL INSERT query
     * @see parseINSERT()
     */
    abstract protected function compileINSERT($components);

    /**
     * Compiles an DELETE statement from components array
     *
     * @param array $components Array of SQL query components
     * @return string SQL DELETE query
     * @see parseDELETE()
     */
    protected function compileDELETE($components)
    {
        // Where clause:
        $where = $this->compileWhereClause($components['WHERE']);
        // Make query:
        $query = 'DELETE FROM ' . $components['TABLE'] . ($where !== '' ? ' WHERE ' . $where : '');

        return $query;
    }

    /**
     * Compiles a CREATE TABLE statement from components array
     *
     * @param array $components Array of SQL query components
     * @return array array with SQL CREATE TABLE/INDEX command(s)
     * @see parseCREATETABLE()
     */
    abstract protected function compileCREATETABLE($components);

    /**
     * Compiles an ALTER TABLE statement from components array
     *
     * @param array Array of SQL query components
     * @return string SQL ALTER TABLE query
     * @see parseALTERTABLE()
     */
    abstract protected function compileALTERTABLE($components);

    /**
     * Compiles a TRUNCATE TABLE statement from components array
     *
     * @param array $components Array of SQL query components
     * @return string SQL TRUNCATE TABLE query
     * @see parseTRUNCATETABLE()
     */
    protected function compileTRUNCATETABLE(array $components)
    {
        // Make query:
        $query = 'TRUNCATE TABLE ' . $components['TABLE'];
        // Return query
        return $query;
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
    abstract public function compileFieldList($selectFields, $compileComments = true, $functionMapping = true);

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
    abstract public function compileWhereClause($clauseArray, $functionMapping = true);

    /**
     * Add slashes function used for compiling queries
     * This method overrides the method from \TYPO3\CMS\Dbal\Database\NativeSqlParser because
     * the input string is already properly escaped.
     *
     * @param string $str Input string
     * @return string Output string
     */
    abstract protected function compileAddslashes($str);

    /**
     * Compile a "JOIN table ON [output] = ..." identifier
     *
     * @param array $identifierParts Array of identifier parts
     * @return string
     * @see parseCastStatement()
     * @see parseFromTables()
     */
    protected function compileJoinIdentifier($identifierParts)
    {
        if ($identifierParts['type'] === 'cast') {
            return sprintf('CAST(%s AS %s)',
                $identifierParts['table'] ? $identifierParts['table'] . '.' . $identifierParts['field'] : $identifierParts['field'],
                $identifierParts['datatype'][0]
            );
        } else {
            return $identifierParts['table'] ? $identifierParts['table'] . '.' . $identifierParts['field'] : $identifierParts['field'];
        }
    }

    /**
     * Compiles a "FROM [output] WHERE..:" table list based on input array (made with ->parseFromTables())
     *
     * @param array $tablesArray Array of table names, (made with ->parseFromTables())
     * @return string Table name string
     * @see parseFromTables()
     */
    public function compileFromTables($tablesArray)
    {
        // Prepare buffer variable:
        $outputParts = [];
        // Traverse the table names:
        if (is_array($tablesArray)) {
            foreach ($tablesArray as $k => $v) {
                // Set table name:
                $outputParts[$k] = $v['table'];
                // Add alias AS if there:
                if ($v['as']) {
                    $outputParts[$k] .= ' ' . $v['as_keyword'] . ' ' . $v['as'];
                }
                if (is_array($v['JOIN'])) {
                    foreach ($v['JOIN'] as $join) {
                        $outputParts[$k] .= ' ' . $join['type'] . ' ' . $join['withTable'];
                        // Add alias AS if there:
                        if (isset($join['as']) && $join['as']) {
                            $outputParts[$k] .= ' ' . $join['as_keyword'] . ' ' . $join['as'];
                        }
                        $outputParts[$k] .= ' ON ';
                        foreach ($join['ON'] as $condition) {
                            if ($condition['operator'] !== '') {
                                $outputParts[$k] .= ' ' . $condition['operator'] . ' ';
                            }
                            $outputParts[$k] .= $this->compileJoinIdentifier($condition['left']);
                            $outputParts[$k] .= $condition['comparator'];
                            if (!empty($condition['right']['value'])) {
                                $value = $condition['right']['value'];
                                $outputParts[$k] .= $value[1] . $this->compileAddslashes($value[0]) . $value[1];
                            } else {
                                $outputParts[$k] .= $this->compileJoinIdentifier($condition['right']);
                            }
                        }
                    }
                }
            }
        }
        // Return imploded buffer:
        return implode(', ', $outputParts);
    }

    /**
     * Compiles a CASE ... WHEN flow-control construct based on input array (made with ->parseCaseStatement())
     *
     * @param array $components Array of case components, (made with ->parseCaseStatement())
     * @param bool $functionMapping Whether function mapping should take place
     * @return string case when string
     * @see parseCaseStatement()
     */
    protected function compileCaseStatement(array $components, $functionMapping = true)
    {
        $statement = 'CASE';
        if (isset($components['case_field'])) {
            $statement .= ' ' . $components['case_field'];
        } elseif (isset($components['case_value'])) {
            $statement .= ' ' . $components['case_value'][1] . $components['case_value'][0] . $components['case_value'][1];
        }
        foreach ($components['when'] as $when) {
            $statement .= ' WHEN ';
            $statement .= $this->compileWhereClause($when['when_value'], $functionMapping);
            $statement .= ' THEN ';
            $statement .= $when['then_value'][1] . $when['then_value'][0] . $when['then_value'][1];
        }
        if (isset($components['else'])) {
            $statement .= ' ELSE ';
            $statement .= $components['else'][1] . $components['else'][0] . $components['else'][1];
        }
        $statement .= ' END';
        return $statement;
    }
}
