<?php
namespace TYPO3\CMS\Dbal\Database;

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

/**
 * PHP SQL engine / server
 */
class SqlParser
{
    /**
     * Parsing error string
     *
     * @var string
     */
    public $parse_error = '';

    /**
     * Last stop keyword used.
     *
     * @var string
     */
    public $lastStopKeyWord = '';

    /**
     * Find "comparator"
     *
     * @var array
     */
    protected static $comparatorPatterns = [
        '<=',
        '>=',
        '<>',
        '<',
        '>',
        '=',
        '!=',
        'NOT[[:space:]]+IN',
        'IN',
        'NOT[[:space:]]+LIKE[[:space:]]+BINARY',
        'LIKE[[:space:]]+BINARY',
        'NOT[[:space:]]+LIKE',
        'LIKE',
        'IS[[:space:]]+NOT',
        'IS',
        'BETWEEN',
        'NOT[[:space]]+BETWEEN'
    ];

    /**
     * Whitespaces in a query
     *
     * @var array
     */
    protected static $interQueryWhitespaces = [' ', TAB, CR, LF];

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var SqlCompilers\Mysql
     */
    protected $nativeSqlCompiler;

    /**
     * @var SqlCompilers\Adodb
     */
    protected $sqlCompiler;

    /**
     * @param DatabaseConnection $databaseConnection
     */
    public function __construct(DatabaseConnection $databaseConnection = null)
    {
        $this->databaseConnection = $databaseConnection ?: $GLOBALS['TYPO3_DB'];
        $this->sqlCompiler = GeneralUtility::makeInstance(SqlCompilers\Adodb::class, $this->databaseConnection);
        $this->nativeSqlCompiler = GeneralUtility::makeInstance(SqlCompilers\Mysql::class, $this->databaseConnection);
    }

    /**
     * Gets value in quotes from $parseString.
     *
     * @param string $parseString String from which to find value in quotes. Notice that $parseString is passed by reference and is shortened by the output of this function.
     * @param string $quote The quote used; input either " or '
     * @return string The value, passed through parseStripslashes()!
     */
    protected function getValueInQuotes(&$parseString, $quote)
    {
        switch ((string)$this->databaseConnection->handlerCfg[$this->databaseConnection->lastHandlerKey]['type']) {
            case 'adodb':
                if ($this->databaseConnection->runningADOdbDriver('mssql')) {
                    $value = $this->getValueInQuotesMssql($parseString, $quote);
                } else {
                    $value = $this->getValueInQuotesGeneric($parseString, $quote);
                }
                break;
            default:
                $value = $this->getValueInQuotesGeneric($parseString, $quote);
        }
        return $value;
    }

    /**
     * Get value in quotes from $parseString.
     * NOTICE: If a query being parsed was prepared for another database than MySQL this function should probably be changed
     *
     * @param string $parseString String from which to find value in quotes. Notice that $parseString is passed by reference and is shortend by the output of this function.
     * @param string $quote The quote used; input either " or '
     * @return string The value, passed through stripslashes() !
     */
    protected function getValueInQuotesGeneric(&$parseString, $quote)
    {
        $parts = explode($quote, substr($parseString, 1));
        $buffer = '';
        foreach ($parts as $k => $v) {
            $buffer .= $v;
            $reg = [];
            preg_match('/\\\\$/', $v, $reg);
            if ($reg && strlen($reg[0]) % 2) {
                $buffer .= $quote;
            } else {
                $parseString = ltrim(substr($parseString, strlen($buffer) + 2));
                return $this->parseStripslashes($buffer);
            }
        }
    }

    /**
     * Gets value in quotes from $parseString. This method targets MSSQL exclusively.
     *
     * @param string $parseString String from which to find value in quotes. Notice that $parseString is passed by reference and is shortened by the output of this function.
     * @param string $quote The quote used; input either " or '
     * @return string
     */
    protected function getValueInQuotesMssql(&$parseString, $quote)
    {
        $previousIsQuote = false;
        $inQuote = false;
        // Go through the whole string
        for ($c = 0; $c < strlen($parseString); $c++) {
            // If the parsed string character is the quote string
            if ($parseString[$c] === $quote) {
                // If we are already in a quote
                if ($inQuote) {
                    // Was the previous a quote?
                    if ($previousIsQuote) {
                        // If yes, replace it by a \
                        $parseString[$c - 1] = '\\';
                    }
                    // Invert the state
                    $previousIsQuote = !$previousIsQuote;
                } else {
                    // So we are in a quote since now
                    $inQuote = true;
                }
            } elseif ($inQuote && $previousIsQuote) {
                $inQuote = false;
                $previousIsQuote = false;
            } else {
                $previousIsQuote = false;
            }
        }
        $parts = explode($quote, substr($parseString, 1));
        $buffer = '';
        foreach ($parts as $v) {
            $buffer .= $v;
            $reg = [];
            preg_match('/\\\\$/', $v, $reg);
            if ($reg && strlen($reg[0]) % 2) {
                $buffer .= $quote;
            } else {
                $parseString = ltrim(substr($parseString, strlen($buffer) + 2));
                return $this->parseStripslashes($buffer);
            }
        }
        return '';
    }

    /*************************************
     *
     * SQL Parsing, full queries
     *
     **************************************/
    /**
     * Parses any single SQL query
     *
     * @param string $parseString SQL query
     * @return array Result array with all the parts in - or error message string
     * @see compileSQL(), debug_testSQL()
     */
    public function parseSQL($parseString)
    {
        // Prepare variables:
        $parseString = $this->trimSQL($parseString);
        $this->parse_error = '';
        $result = [];
        // Finding starting keyword of string:
        $_parseString = $parseString;
        // Protecting original string...
        $keyword = $this->nextPart($_parseString, '^(SELECT|UPDATE|INSERT[[:space:]]+INTO|DELETE[[:space:]]+FROM|EXPLAIN|(DROP|CREATE|ALTER|TRUNCATE)[[:space:]]+TABLE|CREATE[[:space:]]+DATABASE)[[:space:]]+');
        $keyword = $this->normalizeKeyword($keyword);
        switch ($keyword) {
            case 'SELECT':
                // Parsing SELECT query:
                $result = $this->parseSELECT($parseString);
                break;
            case 'UPDATE':
                // Parsing UPDATE query:
                $result = $this->parseUPDATE($parseString);
                break;
            case 'INSERTINTO':
                // Parsing INSERT query:
                $result = $this->parseINSERT($parseString);
                break;
            case 'DELETEFROM':
                // Parsing DELETE query:
                $result = $this->parseDELETE($parseString);
                break;
            case 'EXPLAIN':
                // Parsing EXPLAIN SELECT query:
                $result = $this->parseEXPLAIN($parseString);
                break;
            case 'DROPTABLE':
                // Parsing DROP TABLE query:
                $result = $this->parseDROPTABLE($parseString);
                break;
            case 'ALTERTABLE':
                // Parsing ALTER TABLE query:
                $result = $this->parseALTERTABLE($parseString);
                break;
            case 'CREATETABLE':
                // Parsing CREATE TABLE query:
                $result = $this->parseCREATETABLE($parseString);
                break;
            case 'CREATEDATABASE':
                // Parsing CREATE DATABASE query:
                $result = $this->parseCREATEDATABASE($parseString);
                break;
            case 'TRUNCATETABLE':
                // Parsing TRUNCATE TABLE query:
                $result = $this->parseTRUNCATETABLE($parseString);
                break;
            default:
                $result = $this->parseError('"' . $keyword . '" is not a keyword', $parseString);
        }
        return $result;
    }

    /**
     * Parsing SELECT query
     *
     * @param string $parseString SQL string with SELECT query to parse
     * @param array $parameterReferences Array holding references to either named (:name) or question mark (?) parameters found
     * @return mixed Returns array with components of SELECT query on success, otherwise an error message string.
     * @see compileSELECT()
     */
    protected function parseSELECT($parseString, &$parameterReferences = null)
    {
        // Removing SELECT:
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr($parseString, 6));
        // Init output variable:
        $result = [];
        if ($parameterReferences === null) {
            $result['parameters'] = [];
            $parameterReferences = &$result['parameters'];
        }
        $result['type'] = 'SELECT';
        // Looking for STRAIGHT_JOIN keyword:
        $result['STRAIGHT_JOIN'] = $this->nextPart($parseString, '^(STRAIGHT_JOIN)[[:space:]]+');
        // Select fields:
        $result['SELECT'] = $this->parseFieldList($parseString, '^(FROM)[[:space:]]+');
        if ($this->parse_error) {
            return $this->parse_error;
        }
        // Continue if string is not ended:
        if ($parseString) {
            // Get table list:
            $result['FROM'] = $this->parseFromTables($parseString, '^(WHERE)[[:space:]]+');
            if ($this->parse_error) {
                return $this->parse_error;
            }
            // If there are more than just the tables (a WHERE clause that would be...)
            if ($parseString) {
                // Get WHERE clause:
                $result['WHERE'] = $this->parseWhereClause($parseString, '^((GROUP|ORDER)[[:space:]]+BY|LIMIT)[[:space:]]+', $parameterReferences);
                if ($this->parse_error) {
                    return $this->parse_error;
                }
                // If the WHERE clause parsing was stopped by GROUP BY, ORDER BY or LIMIT, then proceed with parsing:
                if ($this->lastStopKeyWord) {
                    // GROUP BY parsing:
                    if ($this->lastStopKeyWord === 'GROUPBY') {
                        $result['GROUPBY'] = $this->parseFieldList($parseString, '^(ORDER[[:space:]]+BY|LIMIT)[[:space:]]+');
                        if ($this->parse_error) {
                            return $this->parse_error;
                        }
                    }
                    // ORDER BY parsing:
                    if ($this->lastStopKeyWord === 'ORDERBY') {
                        $result['ORDERBY'] = $this->parseFieldList($parseString, '^(LIMIT)[[:space:]]+');
                        if ($this->parse_error) {
                            return $this->parse_error;
                        }
                    }
                    // LIMIT parsing:
                    if ($this->lastStopKeyWord === 'LIMIT') {
                        if (preg_match('/^([0-9]+|[0-9]+[[:space:]]*,[[:space:]]*[0-9]+)$/', trim($parseString))) {
                            $result['LIMIT'] = $parseString;
                        } else {
                            return $this->parseError('No value for limit!', $parseString);
                        }
                    }
                }
            }
        } else {
            return $this->parseError('No table to select from!', $parseString);
        }
        // Store current parseString in the result array for possible further processing (e.g., subquery support by DBAL)
        $result['parseString'] = $parseString;
        // Return result:
        return $result;
    }

    /**
     * Parsing UPDATE query
     *
     * @param string $parseString SQL string with UPDATE query to parse
     * @return mixed Returns array with components of UPDATE query on success, otherwise an error message string.
     * @see compileUPDATE()
     */
    protected function parseUPDATE($parseString)
    {
        // Removing UPDATE
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr($parseString, 6));
        // Init output variable:
        $result = [];
        $result['type'] = 'UPDATE';
        // Get table:
        $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
        // Continue if string is not ended:
        if ($result['TABLE']) {
            if ($parseString && $this->nextPart($parseString, '^(SET)[[:space:]]+')) {
                $comma = true;
                // Get field/value pairs:
                while ($comma) {
                    if ($fieldName = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]*=')) {
                        // Strip off "=" sign.
                        $this->nextPart($parseString, '^(=)');
                        $value = $this->getValue($parseString);
                        $result['FIELDS'][$fieldName] = $value;
                    } else {
                        return $this->parseError('No fieldname found', $parseString);
                    }
                    $comma = $this->nextPart($parseString, '^(,)');
                }
                // WHERE
                if ($this->nextPart($parseString, '^(WHERE)')) {
                    $result['WHERE'] = $this->parseWhereClause($parseString);
                    if ($this->parse_error) {
                        return $this->parse_error;
                    }
                }
            } else {
                return $this->parseError('Query missing SET...', $parseString);
            }
        } else {
            return $this->parseError('No table found!', $parseString);
        }
        // Should be no more content now:
        if ($parseString) {
            return $this->parseError('Still content in clause after parsing!', $parseString);
        }
        // Return result:
        return $result;
    }

    /**
     * Parsing INSERT query
     *
     * @param string $parseString SQL string with INSERT query to parse
     * @return mixed Returns array with components of INSERT query on success, otherwise an error message string.
     * @see compileINSERT()
     */
    protected function parseINSERT($parseString)
    {
        // Removing INSERT
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr(ltrim(substr($parseString, 6)), 4));
        // Init output variable:
        $result = [];
        $result['type'] = 'INSERT';
        // Get table:
        $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\\()');
        if ($result['TABLE']) {
            // In this case there are no field names mentioned in the SQL!
            if ($this->nextPart($parseString, '^(VALUES)([[:space:]]+|\\()')) {
                // Get values/fieldnames (depending...)
                $result['VALUES_ONLY'] = $this->getValue($parseString, 'IN');
                if ($this->parse_error) {
                    return $this->parse_error;
                }
                if (preg_match('/^,/', $parseString)) {
                    $result['VALUES_ONLY'] = [$result['VALUES_ONLY']];
                    $result['EXTENDED'] = '1';
                    while ($this->nextPart($parseString, '^(,)') === ',') {
                        $result['VALUES_ONLY'][] = $this->getValue($parseString, 'IN');
                        if ($this->parse_error) {
                            return $this->parse_error;
                        }
                    }
                }
            } else {
                // There are apparently fieldnames listed:
                $fieldNames = $this->getValue($parseString, '_LIST');
                if ($this->parse_error) {
                    return $this->parse_error;
                }
                // "VALUES" keyword binds the fieldnames to values:
                if ($this->nextPart($parseString, '^(VALUES)([[:space:]]+|\\()')) {
                    $result['FIELDS'] = [];
                    do {
                        // Using the "getValue" function to get the field list...
                        $values = $this->getValue($parseString, 'IN');
                        if ($this->parse_error) {
                            return $this->parse_error;
                        }
                        $insertValues = [];
                        foreach ($fieldNames as $k => $fN) {
                            if (preg_match('/^[[:alnum:]_]+$/', $fN)) {
                                if (isset($values[$k])) {
                                    if (!isset($insertValues[$fN])) {
                                        $insertValues[$fN] = $values[$k];
                                    } else {
                                        return $this->parseError('Fieldname ("' . $fN . '") already found in list!', $parseString);
                                    }
                                } else {
                                    return $this->parseError('No value set!', $parseString);
                                }
                            } else {
                                return $this->parseError('Invalid fieldname ("' . $fN . '")', $parseString);
                            }
                        }
                        if (isset($values[$k + 1])) {
                            return $this->parseError('Too many values in list!', $parseString);
                        }
                        $result['FIELDS'][] = $insertValues;
                    } while ($this->nextPart($parseString, '^(,)') === ',');
                    if (count($result['FIELDS']) === 1) {
                        $result['FIELDS'] = $result['FIELDS'][0];
                    } else {
                        $result['EXTENDED'] = '1';
                    }
                } else {
                    return $this->parseError('VALUES keyword expected', $parseString);
                }
            }
        } else {
            return $this->parseError('No table found!', $parseString);
        }
        // Should be no more content now:
        if ($parseString) {
            return $this->parseError('Still content after parsing!', $parseString);
        }
        // Return result
        return $result;
    }

    /**
     * Parsing DELETE query
     *
     * @param string $parseString SQL string with DELETE query to parse
     * @return mixed Returns array with components of DELETE query on success, otherwise an error message string.
     * @see compileDELETE()
     */
    protected function parseDELETE($parseString)
    {
        // Removing DELETE
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr(ltrim(substr($parseString, 6)), 4));
        // Init output variable:
        $result = [];
        $result['type'] = 'DELETE';
        // Get table:
        $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
        if ($result['TABLE']) {
            // WHERE
            if ($this->nextPart($parseString, '^(WHERE)')) {
                $result['WHERE'] = $this->parseWhereClause($parseString);
                if ($this->parse_error) {
                    return $this->parse_error;
                }
            }
        } else {
            return $this->parseError('No table found!', $parseString);
        }
        // Should be no more content now:
        if ($parseString) {
            return $this->parseError('Still content in clause after parsing!', $parseString);
        }
        // Return result:
        return $result;
    }

    /**
     * Parsing EXPLAIN query
     *
     * @param string $parseString SQL string with EXPLAIN query to parse
     * @return mixed Returns array with components of EXPLAIN query on success, otherwise an error message string.
     * @see parseSELECT()
     */
    protected function parseEXPLAIN($parseString)
    {
        // Removing EXPLAIN
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr($parseString, 6));
        // Init output variable:
        $result = $this->parseSELECT($parseString);
        if (is_array($result)) {
            $result['type'] = 'EXPLAIN';
        }
        return $result;
    }

    /**
     * Parsing CREATE TABLE query
     *
     * @param string $parseString SQL string starting with CREATE TABLE
     * @return mixed Returns array with components of CREATE TABLE query on success, otherwise an error message string.
     * @see compileCREATETABLE()
     */
    protected function parseCREATETABLE($parseString)
    {
        // Removing CREATE TABLE
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr(ltrim(substr($parseString, 6)), 5));
        // Init output variable:
        $result = [];
        $result['type'] = 'CREATETABLE';
        // Get table:
        $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]*\\(', true);
        if ($result['TABLE']) {
            // While the parseString is not yet empty:
            while ($parseString !== '') {
                // Getting key
                if ($key = $this->nextPart($parseString, '^(KEY|PRIMARY KEY|UNIQUE KEY|UNIQUE)([[:space:]]+|\\()')) {
                    $key = $this->normalizeKeyword($key);
                    switch ($key) {
                        case 'PRIMARYKEY':
                            $result['KEYS']['PRIMARYKEY'] = $this->getValue($parseString, '_LIST');
                            if ($this->parse_error) {
                                return $this->parse_error;
                            }
                            break;
                        case 'UNIQUE':

                        case 'UNIQUEKEY':
                            if ($keyName = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\\()')) {
                                $result['KEYS']['UNIQUE'] = [$keyName => $this->getValue($parseString, '_LIST')];
                                if ($this->parse_error) {
                                    return $this->parse_error;
                                }
                            } else {
                                return $this->parseError('No keyname found', $parseString);
                            }
                            break;
                        case 'KEY':
                            if ($keyName = $this->nextPart($parseString, '^([[:alnum:]_]+)([[:space:]]+|\\()')) {
                                $result['KEYS'][$keyName] = $this->getValue($parseString, '_LIST', 'INDEX');
                                if ($this->parse_error) {
                                    return $this->parse_error;
                                }
                            } else {
                                return $this->parseError('No keyname found', $parseString);
                            }
                            break;
                    }
                } elseif ($fieldName = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+')) {
                    // Getting field:
                    $result['FIELDS'][$fieldName]['definition'] = $this->parseFieldDef($parseString);
                    if ($this->parse_error) {
                        return $this->parse_error;
                    }
                }
                // Finding delimiter:
                $delim = $this->nextPart($parseString, '^(,|\\))');
                if (!$delim) {
                    return $this->parseError('No delimiter found', $parseString);
                } elseif ($delim === ')') {
                    break;
                }
            }
            // Finding what is after the table definition - table type in MySQL
            if ($delim === ')') {
                if ($this->nextPart($parseString, '^((ENGINE|TYPE)[[:space:]]*=)')) {
                    $result['engine'] = $parseString;
                    $parseString = '';
                }
            } else {
                return $this->parseError('No fieldname found!', $parseString);
            }
        } else {
            return $this->parseError('No table found!', $parseString);
        }
        // Should be no more content now:
        if ($parseString) {
            return $this->parseError('Still content in clause after parsing!', $parseString);
        }
        return $result;
    }

    /**
     * Parsing ALTER TABLE query
     *
     * @param string $parseString SQL string starting with ALTER TABLE
     * @return mixed Returns array with components of ALTER TABLE query on success, otherwise an error message string.
     * @see compileALTERTABLE()
     */
    protected function parseALTERTABLE($parseString)
    {
        // Removing ALTER TABLE
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr(ltrim(substr($parseString, 5)), 5));
        // Init output variable:
        $result = [];
        $result['type'] = 'ALTERTABLE';
        // Get table:
        $hasBackquote = $this->nextPart($parseString, '^(`)') === '`';
        $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)' . ($hasBackquote ? '`' : '') . '[[:space:]]+');
        if ($hasBackquote && $this->nextPart($parseString, '^(`)') !== '`') {
            return $this->parseError('No end backquote found!', $parseString);
        }
        if ($result['TABLE']) {
            if ($result['action'] = $this->nextPart($parseString, '^(CHANGE|DROP[[:space:]]+KEY|DROP[[:space:]]+PRIMARY[[:space:]]+KEY|ADD[[:space:]]+KEY|ADD[[:space:]]+PRIMARY[[:space:]]+KEY|ADD[[:space:]]+UNIQUE|DROP|ADD|RENAME|DEFAULT[[:space:]]+CHARACTER[[:space:]]+SET|ENGINE)([[:space:]]+|\\(|=)')) {
                $actionKey = $this->normalizeKeyword($result['action']);
                // Getting field:
                if ($actionKey === 'ADDPRIMARYKEY' || $actionKey === 'DROPPRIMARYKEY' || $actionKey === 'ENGINE' || ($fieldKey = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+'))) {
                    switch ($actionKey) {
                        case 'ADD':
                            $result['FIELD'] = $fieldKey;
                            $result['definition'] = $this->parseFieldDef($parseString);
                            if ($this->parse_error) {
                                return $this->parse_error;
                            }
                            break;
                        case 'DROP':
                        case 'RENAME':
                            $result['FIELD'] = $fieldKey;
                            break;
                        case 'CHANGE':
                            $result['FIELD'] = $fieldKey;
                            if ($result['newField'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+')) {
                                $result['definition'] = $this->parseFieldDef($parseString);
                                if ($this->parse_error) {
                                    return $this->parse_error;
                                }
                            } else {
                                return $this->parseError('No NEW field name found', $parseString);
                            }
                            break;
                        case 'ADDKEY':
                        case 'ADDPRIMARYKEY':
                        case 'ADDUNIQUE':
                            $result['KEY'] = $fieldKey;
                            $result['fields'] = $this->getValue($parseString, '_LIST', 'INDEX');
                            if ($this->parse_error) {
                                return $this->parse_error;
                            }
                            break;
                        case 'DROPKEY':
                            $result['KEY'] = $fieldKey;
                            break;
                        case 'DROPPRIMARYKEY':
                            // @todo ???
                            break;
                        case 'DEFAULTCHARACTERSET':
                            $result['charset'] = $fieldKey;
                            break;
                        case 'ENGINE':
                            $result['engine'] = $this->nextPart($parseString, '^=[[:space:]]*([[:alnum:]]+)[[:space:]]+', true);
                            break;
                    }
                } else {
                    return $this->parseError('No field name found', $parseString);
                }
            } else {
                return $this->parseError('No action CHANGE, DROP or ADD found!', $parseString);
            }
        } else {
            return $this->parseError('No table found!', $parseString);
        }
        // Should be no more content now:
        if ($parseString) {
            return $this->parseError('Still content in clause after parsing!', $parseString);
        }
        return $result;
    }

    /**
     * Parsing DROP TABLE query
     *
     * @param string $parseString SQL string starting with DROP TABLE
     * @return mixed Returns array with components of DROP TABLE query on success, otherwise an error message string.
     */
    protected function parseDROPTABLE($parseString)
    {
        // Removing DROP TABLE
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr(ltrim(substr($parseString, 4)), 5));
        // Init output variable:
        $result = [];
        $result['type'] = 'DROPTABLE';
        // IF EXISTS
        $result['ifExists'] = $this->nextPart($parseString, '^(IF[[:space:]]+EXISTS[[:space:]]+)');
        // Get table:
        $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
        if ($result['TABLE']) {
            // Should be no more content now:
            if ($parseString) {
                return $this->parseError('Still content in clause after parsing!', $parseString);
            }
            return $result;
        } else {
            return $this->parseError('No table found!', $parseString);
        }
    }

    /**
     * Parsing CREATE DATABASE query
     *
     * @param string $parseString SQL string starting with CREATE DATABASE
     * @return mixed Returns array with components of CREATE DATABASE query on success, otherwise an error message string.
     */
    protected function parseCREATEDATABASE($parseString)
    {
        // Removing CREATE DATABASE
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr(ltrim(substr($parseString, 6)), 8));
        // Init output variable:
        $result = [];
        $result['type'] = 'CREATEDATABASE';
        // Get table:
        $result['DATABASE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
        if ($result['DATABASE']) {
            // Should be no more content now:
            if ($parseString) {
                return $this->parseError('Still content in clause after parsing!', $parseString);
            }
            return $result;
        } else {
            return $this->parseError('No database found!', $parseString);
        }
    }

    /**
     * Parsing TRUNCATE TABLE query
     *
     * @param string $parseString SQL string starting with TRUNCATE TABLE
     * @return mixed Returns array with components of TRUNCATE TABLE query on success, otherwise an error message string.
     */
    protected function parseTRUNCATETABLE($parseString)
    {
        // Removing TRUNCATE TABLE
        $parseString = $this->trimSQL($parseString);
        $parseString = ltrim(substr(ltrim(substr($parseString, 8)), 5));
        // Init output variable:
        $result = [];
        $result['type'] = 'TRUNCATETABLE';
        // Get table:
        $result['TABLE'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
        if ($result['TABLE']) {
            // Should be no more content now:
            if ($parseString) {
                return $this->parseError('Still content in clause after parsing!', $parseString);
            }
            return $result;
        } else {
            return $this->parseError('No table found!', $parseString);
        }
    }

    /**************************************
     *
     * SQL Parsing, helper functions for parts of queries
     *
     **************************************/
    /**
     * Parsing the fields in the "SELECT [$selectFields] FROM" part of a query into an array.
     * The output from this function can be compiled back into a field list with ->compileFieldList()
     * Will detect the keywords "DESC" and "ASC" after the table name; thus is can be used for parsing the more simply ORDER BY and GROUP BY field lists as well!
     *
     * @param string $parseString The string with fieldnames, eg. "title, uid AS myUid, max(tstamp), count(*)" etc. NOTICE: passed by reference!
     * @param string $stopRegex Regular expressing to STOP parsing, eg. '^(FROM)([[:space:]]*)'
     * @return array If successful parsing, returns an array, otherwise an error string.
     * @see compileFieldList()
     */
    public function parseFieldList(&$parseString, $stopRegex = '')
    {
        $stack = [];
        // Contains the parsed content
        if ($parseString === '') {
            return $stack;
        }
        // @todo - should never happen, why does it?
        // Pointer to positions in $stack
        $pnt = 0;
        // Indicates the parenthesis level we are at.
        $level = 0;
        // Recursivity brake.
        $loopExit = 0;
        // Prepare variables:
        $parseString = $this->trimSQL($parseString);
        $this->lastStopKeyWord = '';
        $this->parse_error = '';
        // Parse any SQL hint / comments
        $stack[$pnt]['comments'] = $this->nextPart($parseString, '^(\\/\\*.*\\*\\/)');
        // $parseString is continuously shortened by the process and we keep parsing it till it is zero:
        while ($parseString !== '') {
            // Checking if we are inside / outside parenthesis (in case of a function like count(), max(), min() etc...):
            // Inside parenthesis here (does NOT detect if values in quotes are used, the only token is ")" or "("):
            if ($level > 0) {
                // Accumulate function content until next () parenthesis:
                $funcContent = $this->nextPart($parseString, '^([^()]*.)');
                $stack[$pnt]['func_content.'][] = [
                    'level' => $level,
                    'func_content' => substr($funcContent, 0, -1)
                ];
                $stack[$pnt]['func_content'] .= $funcContent;
                // Detecting ( or )
                switch (substr($stack[$pnt]['func_content'], -1)) {
                    case '(':
                        $level++;
                        break;
                    case ')':
                        $level--;
                        // If this was the last parenthesis:
                        if (!$level) {
                            $stack[$pnt]['func_content'] = substr($stack[$pnt]['func_content'], 0, -1);
                            // Remove any whitespace after the parenthesis.
                            $parseString = ltrim($parseString);
                        }
                        break;
                }
            } else {
                // Outside parenthesis, looking for next field:
                // Looking for a flow-control construct (only known constructs supported)
                if (preg_match('/^case([[:space:]][[:alnum:]\\*._]+)?[[:space:]]when/i', $parseString)) {
                    $stack[$pnt]['type'] = 'flow-control';
                    $stack[$pnt]['flow-control'] = $this->parseCaseStatement($parseString);
                    // Looking for "AS" alias:
                    if ($as = $this->nextPart($parseString, '^(AS)[[:space:]]+')) {
                        $stack[$pnt]['as'] = $this->nextPart($parseString, '^([[:alnum:]_]+)(,|[[:space:]]+)');
                        $stack[$pnt]['as_keyword'] = $as;
                    }
                } else {
                    // Looking for a known function (only known functions supported)
                    $func = $this->nextPart($parseString, '^(count|max|min|floor|sum|avg)[[:space:]]*\\(');
                    if ($func) {
                        // Strip off "("
                        $parseString = trim(substr($parseString, 1));
                        $stack[$pnt]['type'] = 'function';
                        $stack[$pnt]['function'] = $func;
                        // increse parenthesis level counter.
                        $level++;
                    } else {
                        $stack[$pnt]['distinct'] = $this->nextPart($parseString, '^(distinct[[:space:]]+)');
                        // Otherwise, look for regular fieldname:
                        if (($fieldName = $this->nextPart($parseString, '^([[:alnum:]\\*._]+)(,|[[:space:]]+)')) !== '') {
                            $stack[$pnt]['type'] = 'field';
                            // Explode fieldname into field and table:
                            $tableField = explode('.', $fieldName, 2);
                            if (count($tableField) === 2) {
                                $stack[$pnt]['table'] = $tableField[0];
                                $stack[$pnt]['field'] = $tableField[1];
                            } else {
                                $stack[$pnt]['table'] = '';
                                $stack[$pnt]['field'] = $tableField[0];
                            }
                        } else {
                            return $this->parseError('No field name found as expected in parseFieldList()', $parseString);
                        }
                    }
                }
            }
            // After a function or field we look for "AS" alias and a comma to separate to the next field in the list:
            if (!$level) {
                // Looking for "AS" alias:
                if ($as = $this->nextPart($parseString, '^(AS)[[:space:]]+')) {
                    $stack[$pnt]['as'] = $this->nextPart($parseString, '^([[:alnum:]_]+)(,|[[:space:]]+)');
                    $stack[$pnt]['as_keyword'] = $as;
                }
                // Looking for "ASC" or "DESC" keywords (for ORDER BY)
                if ($sDir = $this->nextPart($parseString, '^(ASC|DESC)([[:space:]]+|,)')) {
                    $stack[$pnt]['sortDir'] = $sDir;
                }
                // Looking for stop-keywords:
                if ($stopRegex && ($this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))) {
                    $this->lastStopKeyWord = $this->normalizeKeyword($this->lastStopKeyWord);
                    return $stack;
                }
                // Looking for comma (since the stop-keyword did not trigger a return...)
                if ($parseString !== '' && !$this->nextPart($parseString, '^(,)')) {
                    return $this->parseError('No comma found as expected in parseFieldList()', $parseString);
                }
                // Increasing pointer:
                $pnt++;
            }
            // Check recursivity brake:
            $loopExit++;
            if ($loopExit > 500) {
                return $this->parseError('More than 500 loops, exiting prematurely in parseFieldList()...', $parseString);
            }
        }
        // Return result array:
        return $stack;
    }

    /**
     * Parsing a CASE ... WHEN flow-control construct.
     * The output from this function can be compiled back with ->compileCaseStatement()
     *
     * @param string $parseString The string with the CASE ... WHEN construct, eg. "CASE field WHEN 1 THEN 0 ELSE ..." etc. NOTICE: passed by reference!
     * @return array If successful parsing, returns an array, otherwise an error string.
     * @see compileCaseConstruct()
     */
    protected function parseCaseStatement(&$parseString)
    {
        $result = [];
        $result['type'] = $this->nextPart($parseString, '^(case)[[:space:]]+');
        if (!preg_match('/^when[[:space:]]+/i', $parseString)) {
            $value = $this->getValue($parseString);
            if (!(isset($value[1]) || is_numeric($value[0]))) {
                $result['case_field'] = $value[0];
            } else {
                $result['case_value'] = $value;
            }
        }
        $result['when'] = [];
        while ($this->nextPart($parseString, '^(when)[[:space:]]')) {
            $when = [];
            $when['when_value'] = $this->parseWhereClause($parseString, '^(then)[[:space:]]+');
            $when['then_value'] = $this->getValue($parseString);
            $result['when'][] = $when;
        }
        if ($this->nextPart($parseString, '^(else)[[:space:]]+')) {
            $result['else'] = $this->getValue($parseString);
        }
        if (!$this->nextPart($parseString, '^(end)[[:space:]]+')) {
            return $this->parseError('No "end" keyword found as expected in parseCaseStatement()', $parseString);
        }
        return $result;
    }

    /**
     * Parsing a CAST definition in the "JOIN [$parseString] ..." part of a query into an array.
     * The success of this parsing determines if that part of the query is supported by TYPO3.
     *
     * @param string $parseString JOIN clause to parse. NOTICE: passed by reference!
     * @return mixed If successful parsing, returns an array, otherwise an error string.
     */
    protected function parseCastStatement(&$parseString)
    {
        $this->nextPart($parseString, '^(CAST)[[:space:]]*');
        $parseString = trim(substr($parseString, 1));
        $castDefinition = ['type' => 'cast'];
        // Strip off "("
        if ($fieldName = $this->nextPart($parseString, '^([[:alnum:]\\*._]+)[[:space:]]*')) {
            // Parse field name into field and table:
            $tableField = explode('.', $fieldName, 2);
            if (count($tableField) === 2) {
                $castDefinition['table'] = $tableField[0];
                $castDefinition['field'] = $tableField[1];
            } else {
                $castDefinition['table'] = '';
                $castDefinition['field'] = $tableField[0];
            }
        } else {
            return $this->parseError('No casted join field found in parseCastStatement()!', $parseString);
        }
        if ($this->nextPart($parseString, '^([[:space:]]*AS[[:space:]]*)')) {
            $castDefinition['datatype'] = $this->getValue($parseString);
        }
        if (!$this->nextPart($parseString, '^([)])')) {
            return $this->parseError('No end parenthesis at end of CAST function', $parseString);
        }
        return $castDefinition;
    }

    /**
     * Parsing the tablenames in the "FROM [$parseString] WHERE" part of a query into an array.
     * The success of this parsing determines if that part of the query is supported by TYPO3.
     *
     * @param string $parseString List of tables, eg. "pages, tt_content" or "pages A, pages B". NOTICE: passed by reference!
     * @param string $stopRegex Regular expressing to STOP parsing, eg. '^(WHERE)([[:space:]]*)'
     * @return array If successful parsing, returns an array, otherwise an error string.
     * @see compileFromTables()
     */
    public function parseFromTables(&$parseString, $stopRegex = '')
    {
        // Prepare variables:
        $parseString = $this->trimSQL($parseString);
        $this->lastStopKeyWord = '';
        $this->parse_error = '';
        // Contains the parsed content
        $stack = [];
        // Pointer to positions in $stack
        $pnt = 0;
        // Recursivity brake.
        $loopExit = 0;
        // $parseString is continously shortend by the process and we keep parsing it till it is zero:
        while ($parseString !== '') {
            // Looking for the table:
            if ($stack[$pnt]['table'] = $this->nextPart($parseString, '^([[:alnum:]_]+)(,|[[:space:]]+)')) {
                // Looking for stop-keywords before fetching potential table alias:
                if ($stopRegex && ($this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))) {
                    $this->lastStopKeyWord = $this->normalizeKeyword($this->lastStopKeyWord);
                    return $stack;
                }
                if (!preg_match('/^(LEFT|RIGHT|JOIN|INNER)[[:space:]]+/i', $parseString)) {
                    $stack[$pnt]['as_keyword'] = $this->nextPart($parseString, '^(AS[[:space:]]+)');
                    $stack[$pnt]['as'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]*');
                }
            } else {
                return $this->parseError('No table name found as expected in parseFromTables()!', $parseString);
            }
            // Looking for JOIN
            $joinCnt = 0;
            while ($join = $this->nextPart($parseString, '^(((INNER|(LEFT|RIGHT)([[:space:]]+OUTER)?)[[:space:]]+)?JOIN)[[:space:]]+')) {
                $stack[$pnt]['JOIN'][$joinCnt]['type'] = $join;
                if ($stack[$pnt]['JOIN'][$joinCnt]['withTable'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+', 1)) {
                    if (!preg_match('/^ON[[:space:]]+/i', $parseString)) {
                        $stack[$pnt]['JOIN'][$joinCnt]['as_keyword'] = $this->nextPart($parseString, '^(AS[[:space:]]+)');
                        $stack[$pnt]['JOIN'][$joinCnt]['as'] = $this->nextPart($parseString, '^([[:alnum:]_]+)[[:space:]]+');
                    }
                    if (!$this->nextPart($parseString, '^(ON[[:space:]]+)')) {
                        return $this->parseError('No join condition found in parseFromTables()!', $parseString);
                    }
                    $stack[$pnt]['JOIN'][$joinCnt]['ON'] = [];
                    $condition = ['operator' => ''];
                    $parseCondition = true;
                    while ($parseCondition) {
                        if (($fieldName = $this->nextPart($parseString, '^([[:alnum:]._]+)[[:space:]]*(<=|>=|<|>|=|!=)')) !== '') {
                            // Parse field name into field and table:
                            $tableField = explode('.', $fieldName, 2);
                            $condition['left'] = [];
                            if (count($tableField) === 2) {
                                $condition['left']['table'] = $tableField[0];
                                $condition['left']['field'] = $tableField[1];
                            } else {
                                $condition['left']['table'] = '';
                                $condition['left']['field'] = $tableField[0];
                            }
                        } elseif (preg_match('/^CAST[[:space:]]*[(]/i', $parseString)) {
                            $condition['left'] = $this->parseCastStatement($parseString);
                            // Return the parse error
                            if (!is_array($condition['left'])) {
                                return $condition['left'];
                            }
                        } else {
                            return $this->parseError('No join field found in parseFromTables()!', $parseString);
                        }
                        // Find "comparator":
                        $condition['comparator'] = $this->nextPart($parseString, '^(<=|>=|<|>|=|!=)');
                        if (preg_match('/^CAST[[:space:]]*[(]/i', $parseString)) {
                            $condition['right'] = $this->parseCastStatement($parseString);
                            // Return the parse error
                            if (!is_array($condition['right'])) {
                                return $condition['right'];
                            }
                        } elseif (($fieldName = $this->nextPart($parseString, '^([[:alnum:]._]+)')) !== '') {
                            // Parse field name into field and table:
                            $tableField = explode('.', $fieldName, 2);
                            $condition['right'] = [];
                            if (count($tableField) === 2) {
                                $condition['right']['table'] = $tableField[0];
                                $condition['right']['field'] = $tableField[1];
                            } else {
                                $condition['right']['table'] = '';
                                $condition['right']['field'] = $tableField[0];
                            }
                        } elseif ($value = $this->getValue($parseString)) {
                            $condition['right']['value'] = $value;
                        } else {
                            return $this->parseError('No join field found in parseFromTables()!', $parseString);
                        }
                        $stack[$pnt]['JOIN'][$joinCnt]['ON'][] = $condition;
                        if (($operator = $this->nextPart($parseString, '^(AND|OR)')) !== '') {
                            $condition = ['operator' => $operator];
                        } else {
                            $parseCondition = false;
                        }
                    }
                    $joinCnt++;
                } else {
                    return $this->parseError('No join table found in parseFromTables()!', $parseString);
                }
            }
            // Looking for stop-keywords:
            if ($stopRegex && ($this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))) {
                $this->lastStopKeyWord = $this->normalizeKeyword($this->lastStopKeyWord);
                return $stack;
            }
            // Looking for comma:
            if ($parseString !== '' && !$this->nextPart($parseString, '^(,)')) {
                return $this->parseError('No comma found as expected in parseFromTables()', $parseString);
            }
            // Increasing pointer:
            $pnt++;
            // Check recursivity brake:
            $loopExit++;
            if ($loopExit > 500) {
                return $this->parseError('More than 500 loops, exiting prematurely in parseFromTables()...', $parseString);
            }
        }
        // Return result array:
        return $stack;
    }

    /**
     * Parsing the WHERE clause fields in the "WHERE [$parseString] ..." part of a query into a multidimensional array.
     * The success of this parsing determines if that part of the query is supported by TYPO3.
     *
     * @param string $parseString WHERE clause to parse. NOTICE: passed by reference!
     * @param string $stopRegex Regular expressing to STOP parsing, eg. '^(GROUP BY|ORDER BY|LIMIT)([[:space:]]*)'
     * @param array $parameterReferences Array holding references to either named (:name) or question mark (?) parameters found
     * @return mixed If successful parsing, returns an array, otherwise an error string.
     */
    public function parseWhereClause(&$parseString, $stopRegex = '', array &$parameterReferences = [])
    {
        // Prepare variables:
        $parseString = $this->trimSQL($parseString);
        $this->lastStopKeyWord = '';
        $this->parse_error = '';
        // Contains the parsed content
        $stack = [0 => []];
        // Pointer to positions in $stack
        $pnt = [0 => 0];
        // Determines parenthesis level
        $level = 0;
        // Recursivity brake.
        $loopExit = 0;
        // $parseString is continuously shortened by the process and we keep parsing it till it is zero:
        while ($parseString !== '') {
            // Look for next parenthesis level:
            $newLevel = $this->nextPart($parseString, '^([(])');
            // If new level is started, manage stack/pointers:
            if ($newLevel === '(') {
                // Increase level
                $level++;
                // Reset pointer for this level
                $pnt[$level] = 0;
                // Reset stack for this level
                $stack[$level] = [];
            } else {
                // If no new level is started, just parse the current level:
                // Find "modifier", eg. "NOT or !"
                $stack[$level][$pnt[$level]]['modifier'] = trim($this->nextPart($parseString, '^(!|NOT[[:space:]]+)'));
                // See if condition is EXISTS with a subquery
                if (preg_match('/^EXISTS[[:space:]]*[(]/i', $parseString)) {
                    $stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(EXISTS)[[:space:]]*');
                    // Strip off "("
                    $parseString = trim(substr($parseString, 1));
                    $stack[$level][$pnt[$level]]['func']['subquery'] = $this->parseSELECT($parseString, $parameterReferences);
                    // Seek to new position in parseString after parsing of the subquery
                    $parseString = $stack[$level][$pnt[$level]]['func']['subquery']['parseString'];
                    unset($stack[$level][$pnt[$level]]['func']['subquery']['parseString']);
                    if (!$this->nextPart($parseString, '^([)])')) {
                        return 'No ) parenthesis at end of subquery';
                    }
                } else {
                    // See if LOCATE function is found
                    if (preg_match('/^LOCATE[[:space:]]*[(]/i', $parseString)) {
                        $stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(LOCATE)[[:space:]]*');
                        // Strip off "("
                        $parseString = trim(substr($parseString, 1));
                        $stack[$level][$pnt[$level]]['func']['substr'] = $this->getValue($parseString);
                        if (!$this->nextPart($parseString, '^(,)')) {
                            return $this->parseError('No comma found as expected in parseWhereClause()', $parseString);
                        }
                        if ($fieldName = $this->nextPart($parseString, '^([[:alnum:]\\*._]+)[[:space:]]*')) {
                            // Parse field name into field and table:
                            $tableField = explode('.', $fieldName, 2);
                            if (count($tableField) === 2) {
                                $stack[$level][$pnt[$level]]['func']['table'] = $tableField[0];
                                $stack[$level][$pnt[$level]]['func']['field'] = $tableField[1];
                            } else {
                                $stack[$level][$pnt[$level]]['func']['table'] = '';
                                $stack[$level][$pnt[$level]]['func']['field'] = $tableField[0];
                            }
                        } else {
                            return $this->parseError('No field name found as expected in parseWhereClause()', $parseString);
                        }
                        if ($this->nextPart($parseString, '^(,)')) {
                            $stack[$level][$pnt[$level]]['func']['pos'] = $this->getValue($parseString);
                        }
                        if (!$this->nextPart($parseString, '^([)])')) {
                            return $this->parseError('No ) parenthesis at end of function', $parseString);
                        }
                    } elseif (preg_match('/^IFNULL[[:space:]]*[(]/i', $parseString)) {
                        $stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(IFNULL)[[:space:]]*');
                        $parseString = trim(substr($parseString, 1));
                        // Strip off "("
                        if ($fieldName = $this->nextPart($parseString, '^([[:alnum:]\\*._]+)[[:space:]]*')) {
                            // Parse field name into field and table:
                            $tableField = explode('.', $fieldName, 2);
                            if (count($tableField) === 2) {
                                $stack[$level][$pnt[$level]]['func']['table'] = $tableField[0];
                                $stack[$level][$pnt[$level]]['func']['field'] = $tableField[1];
                            } else {
                                $stack[$level][$pnt[$level]]['func']['table'] = '';
                                $stack[$level][$pnt[$level]]['func']['field'] = $tableField[0];
                            }
                        } else {
                            return $this->parseError('No field name found as expected in parseWhereClause()', $parseString);
                        }
                        if ($this->nextPart($parseString, '^(,)')) {
                            $stack[$level][$pnt[$level]]['func']['default'] = $this->getValue($parseString);
                        }
                        if (!$this->nextPart($parseString, '^([)])')) {
                            return $this->parseError('No ) parenthesis at end of function', $parseString);
                        }
                    } elseif (preg_match('/^CAST[[:space:]]*[(]/i', $parseString)) {
                        $stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(CAST)[[:space:]]*');
                        $parseString = trim(substr($parseString, 1));
                        // Strip off "("
                        if ($fieldName = $this->nextPart($parseString, '^([[:alnum:]\\*._]+)[[:space:]]*')) {
                            // Parse field name into field and table:
                            $tableField = explode('.', $fieldName, 2);
                            if (count($tableField) === 2) {
                                $stack[$level][$pnt[$level]]['func']['table'] = $tableField[0];
                                $stack[$level][$pnt[$level]]['func']['field'] = $tableField[1];
                            } else {
                                $stack[$level][$pnt[$level]]['func']['table'] = '';
                                $stack[$level][$pnt[$level]]['func']['field'] = $tableField[0];
                            }
                        } else {
                            return $this->parseError('No field name found as expected in parseWhereClause()', $parseString);
                        }
                        if ($this->nextPart($parseString, '^([[:space:]]*AS[[:space:]]*)')) {
                            $stack[$level][$pnt[$level]]['func']['datatype'] = $this->getValue($parseString);
                        }
                        if (!$this->nextPart($parseString, '^([)])')) {
                            return $this->parseError('No ) parenthesis at end of function', $parseString);
                        }
                    } elseif (preg_match('/^FIND_IN_SET[[:space:]]*[(]/i', $parseString)) {
                        $stack[$level][$pnt[$level]]['func']['type'] = $this->nextPart($parseString, '^(FIND_IN_SET)[[:space:]]*');
                        // Strip off "("
                        $parseString = trim(substr($parseString, 1));
                        if ($str = $this->getValue($parseString)) {
                            $stack[$level][$pnt[$level]]['func']['str'] = $str;
                            if ($fieldName = $this->nextPart($parseString, '^,[[:space:]]*([[:alnum:]._]+)[[:space:]]*', true)) {
                                // Parse field name into field and table:
                                $tableField = explode('.', $fieldName, 2);
                                if (count($tableField) === 2) {
                                    $stack[$level][$pnt[$level]]['func']['table'] = $tableField[0];
                                    $stack[$level][$pnt[$level]]['func']['field'] = $tableField[1];
                                } else {
                                    $stack[$level][$pnt[$level]]['func']['table'] = '';
                                    $stack[$level][$pnt[$level]]['func']['field'] = $tableField[0];
                                }
                            } else {
                                return $this->parseError('No field name found as expected in parseWhereClause()', $parseString);
                            }
                            if (!$this->nextPart($parseString, '^([)])')) {
                                return $this->parseError('No ) parenthesis at end of function', $parseString);
                            }
                        } else {
                            return $this->parseError('No item to look for found as expected in parseWhereClause()', $parseString);
                        }
                    } else {
                        // Support calculated value only for:
                        // - "&" (boolean AND)
                        // - "+" (addition)
                        // - "-" (substraction)
                        // - "*" (multiplication)
                        // - "/" (division)
                        // - "%" (modulo)
                        $calcOperators = '&|\\+|-|\\*|\\/|%';
                        // Fieldname:
                        if (($fieldName = $this->nextPart($parseString, '^([[:alnum:]._]+)([[:space:]]+|' . $calcOperators . '|<=|>=|<|>|=|!=|IS)')) !== '') {
                            // Parse field name into field and table:
                            $tableField = explode('.', $fieldName, 2);
                            if (count($tableField) === 2) {
                                $stack[$level][$pnt[$level]]['table'] = $tableField[0];
                                $stack[$level][$pnt[$level]]['field'] = $tableField[1];
                            } else {
                                $stack[$level][$pnt[$level]]['table'] = '';
                                $stack[$level][$pnt[$level]]['field'] = $tableField[0];
                            }
                        } else {
                            return $this->parseError('No field name found as expected in parseWhereClause()', $parseString);
                        }
                        // See if the value is calculated:
                        $stack[$level][$pnt[$level]]['calc'] = $this->nextPart($parseString, '^(' . $calcOperators . ')');
                        if ((string)$stack[$level][$pnt[$level]]['calc'] !== '') {
                            // Finding value for calculation:
                            $calc_value = $this->getValue($parseString);
                            $stack[$level][$pnt[$level]]['calc_value'] = $calc_value;
                            if (count($calc_value) === 1 && is_string($calc_value[0])) {
                                // Value is a field, store it to allow DBAL to post-process it (quoting, remapping)
                                $tableField = explode('.', $calc_value[0], 2);
                                if (count($tableField) === 2) {
                                    $stack[$level][$pnt[$level]]['calc_table'] = $tableField[0];
                                    $stack[$level][$pnt[$level]]['calc_field'] = $tableField[1];
                                } else {
                                    $stack[$level][$pnt[$level]]['calc_table'] = '';
                                    $stack[$level][$pnt[$level]]['calc_field'] = $tableField[0];
                                }
                            }
                        }
                    }
                    $stack[$level][$pnt[$level]]['comparator'] = $this->nextPart($parseString, '^(' . implode('|', self::$comparatorPatterns) . ')');
                    if ($stack[$level][$pnt[$level]]['comparator'] !== '') {
                        if (preg_match('/^CONCAT[[:space:]]*\\(/', $parseString)) {
                            $this->nextPart($parseString, '^(CONCAT[[:space:]]?[(])');
                            $values = [
                                'operator' => 'CONCAT',
                                'args' => []
                            ];
                            $cnt = 0;
                            while ($fieldName = $this->nextPart($parseString, '^([[:alnum:]._]+)')) {
                                // Parse field name into field and table:
                                $tableField = explode('.', $fieldName, 2);
                                if (count($tableField) === 2) {
                                    $values['args'][$cnt]['table'] = $tableField[0];
                                    $values['args'][$cnt]['field'] = $tableField[1];
                                } else {
                                    $values['args'][$cnt]['table'] = '';
                                    $values['args'][$cnt]['field'] = $tableField[0];
                                }
                                // Looking for comma:
                                $this->nextPart($parseString, '^(,)');
                                $cnt++;
                            }
                            // Look for ending parenthesis:
                            $this->nextPart($parseString, '([)])');
                            $stack[$level][$pnt[$level]]['value'] = $values;
                        } else {
                            $comparator = $this->normalizeKeyword($stack[$level][$pnt[$level]]['comparator']);
                            if (($comparator === 'IN' || $comparator == 'NOT IN') && preg_match('/^[(][[:space:]]*SELECT[[:space:]]+/', $parseString)) {
                                $this->nextPart($parseString, '^([(])');
                                $stack[$level][$pnt[$level]]['subquery'] = $this->parseSELECT($parseString, $parameterReferences);
                                // Seek to new position in parseString after parsing of the subquery
                                if (!empty($stack[$level][$pnt[$level]]['subquery']['parseString'])) {
                                    $parseString = $stack[$level][$pnt[$level]]['subquery']['parseString'];
                                    unset($stack[$level][$pnt[$level]]['subquery']['parseString']);
                                }
                                if (!$this->nextPart($parseString, '^([)])')) {
                                    return 'No ) parenthesis at end of subquery';
                                }
                            } elseif ($comparator === 'BETWEEN' || $comparator === 'NOT BETWEEN') {
                                $stack[$level][$pnt[$level]]['values'] = [];
                                $stack[$level][$pnt[$level]]['values'][0] = $this->getValue($parseString);
                                if (!$this->nextPart($parseString, '^(AND)')) {
                                    return $this->parseError('No AND operator found as expected in parseWhereClause()', $parseString);
                                }
                                $stack[$level][$pnt[$level]]['values'][1] = $this->getValue($parseString);
                            } else {
                                // Finding value for comparator:
                                $stack[$level][$pnt[$level]]['value'] = &$this->getValueOrParameter($parseString, $stack[$level][$pnt[$level]]['comparator'], '', $parameterReferences);
                                if ($this->parse_error) {
                                    return $this->parse_error;
                                }
                            }
                        }
                    }
                }
                // Finished, increase pointer:
                $pnt[$level]++;
                // Checking if we are back to level 0 and we should still decrease level,
                // meaning we were probably parsing as subquery and should return here:
                if ($level === 0 && preg_match('/^[)]/', $parseString)) {
                    // Return the stacks lowest level:
                    return $stack[0];
                }
                // Checking if we are back to level 0 and we should still decrease level,
                // meaning we were probably parsing a subquery and should return here:
                if ($level === 0 && preg_match('/^[)]/', $parseString)) {
                    // Return the stacks lowest level:
                    return $stack[0];
                }
                // Checking if the current level is ended, in that case do stack management:
                while ($this->nextPart($parseString, '^([)])')) {
                    $level--;
                    // Decrease level:
                    // Copy stack
                    $stack[$level][$pnt[$level]]['sub'] = $stack[$level + 1];
                    // Increase pointer of the new level
                    $pnt[$level]++;
                    // Make recursivity check:
                    $loopExit++;
                    if ($loopExit > 500) {
                        return $this->parseError('More than 500 loops (in search for exit parenthesis), exiting prematurely in parseWhereClause()...', $parseString);
                    }
                }
                // Detecting the operator for the next level:
                $op = $this->nextPart($parseString, '^(AND[[:space:]]+NOT|&&[[:space:]]+NOT|OR[[:space:]]+NOT|OR[[:space:]]+NOT|\\|\\|[[:space:]]+NOT|AND|&&|OR|\\|\\|)(\\(|[[:space:]]+)');
                if ($op) {
                    // Normalize boolean operator
                    $op = str_replace(['&&', '||'], ['AND', 'OR'], $op);
                    $stack[$level][$pnt[$level]]['operator'] = $op;
                } elseif ($parseString !== '') {
                    // Looking for stop-keywords:
                    if ($stopRegex && ($this->lastStopKeyWord = $this->nextPart($parseString, $stopRegex))) {
                        $this->lastStopKeyWord = $this->normalizeKeyword($this->lastStopKeyWord);
                        return $stack[0];
                    } else {
                        return $this->parseError('No operator, but parsing not finished in parseWhereClause().', $parseString);
                    }
                }
            }
            // Make recursivity check:
            $loopExit++;
            if ($loopExit > 500) {
                return $this->parseError('More than 500 loops, exiting prematurely in parseWhereClause()...', $parseString);
            }
        }
        // Return the stacks lowest level:
        return $stack[0];
    }

    /**
     * Parsing the WHERE clause fields in the "WHERE [$parseString] ..." part of a query into a multidimensional array.
     * The success of this parsing determines if that part of the query is supported by TYPO3.
     *
     * @param string $parseString WHERE clause to parse. NOTICE: passed by reference!
     * @param string $stopRegex Regular expressing to STOP parsing, eg. '^(GROUP BY|ORDER BY|LIMIT)([[:space:]]*)'
     * @return mixed If successful parsing, returns an array, otherwise an error string.
     */
    public function parseFieldDef(&$parseString, $stopRegex = '')
    {
        // Prepare variables:
        $parseString = $this->trimSQL($parseString);
        $this->lastStopKeyWord = '';
        $this->parse_error = '';
        $result = [];
        // Field type:
        if ($result['fieldType'] = $this->nextPart($parseString, '^(int|smallint|tinyint|mediumint|bigint|double|numeric|decimal|float|varchar|char|text|tinytext|mediumtext|longtext|blob|tinyblob|mediumblob|longblob|date|datetime|time|year|timestamp)([[:space:],]+|\\()')) {
            // Looking for value:
            if ($parseString[0] === '(') {
                $parseString = substr($parseString, 1);
                if ($result['value'] = $this->nextPart($parseString, '^([^)]*)')) {
                    $parseString = ltrim(substr($parseString, 1));
                } else {
                    return $this->parseError('No end-parenthesis for value found in parseFieldDef()!', $parseString);
                }
            }
            // Looking for keywords
            while ($keyword = $this->nextPart($parseString, '^(DEFAULT|NOT[[:space:]]+NULL|AUTO_INCREMENT|UNSIGNED)([[:space:]]+|,|\\))')) {
                $keywordCmp = $this->normalizeKeyword($keyword);
                $result['featureIndex'][$keywordCmp]['keyword'] = $keyword;
                switch ($keywordCmp) {
                    case 'DEFAULT':
                        $result['featureIndex'][$keywordCmp]['value'] = $this->getValue($parseString);
                        break;
                }
            }
        } else {
            return $this->parseError('Field type unknown in parseFieldDef()!', $parseString);
        }
        return $result;
    }

    /**
     * Checks if the submitted feature index contains a default value definition and the default value
     *
     * @param array $featureIndex A feature index as produced by parseFieldDef()
     * @return bool
     * @see \TYPO3\CMS\Core\Database\SqlParser::parseFieldDef()
     */
    public function checkEmptyDefaultValue($featureIndex)
    {
        if (!is_array($featureIndex['DEFAULT']['value'])) {
            return true;
        }
        return !is_numeric($featureIndex['DEFAULT']['value'][0]) && empty($featureIndex['DEFAULT']['value'][0]);
    }

    /************************************
     *
     * Parsing: Helper functions
     *
     ************************************/
    /**
     * Strips off a part of the parseString and returns the matching part.
     * Helper function for the parsing methods.
     *
     * @param string $parseString Parse string; if $regex finds anything the value of the first () level will be stripped of the string in the beginning. Further $parseString is left-trimmed (on success). Notice; parsestring is passed by reference.
     * @param string $regex Regex to find a matching part in the beginning of the string. Rules: You MUST start the regex with "^" (finding stuff in the beginning of string) and the result of the first parenthesis is what will be returned to you (and stripped of the string). Eg. '^(AND|OR|&&)[[:space:]]+' will return AND, OR or && if found and having one of more whitespaces after it, plus shorten $parseString with that match and any space after (by ltrim())
     * @param bool $trimAll If set the full match of the regex is stripped of the beginning of the string!
     * @return string The value of the first parenthesis level of the REGEX.
     */
    protected function nextPart(&$parseString, $regex, $trimAll = false)
    {
        $reg = [];
        // Adding space char because [[:space:]]+ is often a requirement in regex's
        if (preg_match('/' . $regex . '/i', $parseString . ' ', $reg)) {
            $parseString = ltrim(substr($parseString, strlen($reg[$trimAll ? 0 : 1])));
            return $reg[1];
        }
        // No match found
        return '';
    }

    /**
     * Normalizes the keyword by removing any separator and changing to uppercase
     *
     * @param string $keyword The keyword being normalized
     * @return string
     */
    public static function normalizeKeyword($keyword)
    {
        return strtoupper(str_replace(self::$interQueryWhitespaces, '', $keyword));
    }

    /**
     * Finds value or either named (:name) or question mark (?) parameter markers at the beginning
     * of $parseString, returns result and strips it of parseString.
     * This method returns a pointer to the parameter or value that was found. In case of a parameter
     * the pointer is a reference to the corresponding item in array $parameterReferences.
     *
     * @param string $parseString The parseString
     * @param string $comparator The comparator used before.
     * @param string $mode The mode, e.g., "INDEX
     * @param mixed $parameterReferences The value (string/integer) or parameter (:name/?). Otherwise an array with error message in first key (0)
     * @return mixed
     */
    protected function &getValueOrParameter(&$parseString, $comparator = '', $mode = '', array &$parameterReferences = [])
    {
        $parameter = $this->nextPart($parseString, '^(\\:[[:alnum:]_]+|\\?)');
        if ($parameter === '?') {
            if (!isset($parameterReferences['?'])) {
                $parameterReferences['?'] = [];
            }
            $value = ['?'];
            $parameterReferences['?'][] = &$value;
        } elseif ($parameter !== '') {
            // named parameter
            if (isset($parameterReferences[$parameter])) {
                // Use the same reference as last time we encountered this parameter
                $value = &$parameterReferences[$parameter];
            } else {
                $value = [$parameter];
                $parameterReferences[$parameter] = &$value;
            }
        } else {
            $value = $this->getValue($parseString, $comparator, $mode);
        }
        return $value;
    }

    /**
     * Finds value in beginning of $parseString, returns result and strips it of parseString
     *
     * @param string $parseString The parseString, eg. "(0,1,2,3) ..." or "('asdf','qwer') ..." or "1234 ..." or "'My string value here' ...
     * @param string $comparator The comparator used before. If "NOT IN" or "IN" then the value is expected to be a list of values. Otherwise just an integer (un-quoted) or string (quoted)
     * @param string $mode The mode, eg. "INDEX
     * @return mixed The value (string/integer). Otherwise an array with error message in first key (0)
     */
    protected function getValue(&$parseString, $comparator = '', $mode = '')
    {
        $value = '';
        $comparator = $this->normalizeKeyword($comparator);
        if ($comparator === 'NOTIN' || $comparator === 'IN' || $comparator === '_LIST') {
            // List of values:
            if ($this->nextPart($parseString, '^([(])')) {
                $listValues = [];
                $comma = ',';
                while ($comma === ',') {
                    $listValues[] = $this->getValue($parseString);
                    if ($mode === 'INDEX') {
                        // Remove any length restriction on INDEX definition
                        $this->nextPart($parseString, '^([(]\\d+[)])');
                    }
                    $comma = $this->nextPart($parseString, '^([,])');
                }
                $out = $this->nextPart($parseString, '^([)])');
                if ($out) {
                    if ($comparator === '_LIST') {
                        $kVals = [];
                        foreach ($listValues as $vArr) {
                            $kVals[] = $vArr[0];
                        }
                        return $kVals;
                    } else {
                        return $listValues;
                    }
                } else {
                    return [$this->parseError('No ) parenthesis in list', $parseString)];
                }
            } else {
                return [$this->parseError('No ( parenthesis starting the list', $parseString)];
            }
        } else {
            // Just plain string value, in quotes or not:
            // Quote?
            $firstChar = $parseString[0];
            switch ($firstChar) {
                case '"':
                    $value = [$this->getValueInQuotes($parseString, '"'), '"'];
                    break;
                case '\'':
                    $value = [$this->getValueInQuotes($parseString, '\''), '\''];
                    break;
                default:
                    $reg = [];
                    if (preg_match('/^([[:alnum:]._-]+(?:\\([0-9]+\\))?)/i', $parseString, $reg)) {
                        $parseString = ltrim(substr($parseString, strlen($reg[0])));
                        $value = [$reg[1]];
                    }
            }
        }
        return $value;
    }

    /**
     * Strip slashes function used for parsing
     * NOTICE: If a query being parsed was prepared for another database than MySQL this function should probably be changed
     *
     * @param string $str Input string
     * @return string Output string
     */
    protected function parseStripslashes($str)
    {
        $search = ['\\\\', '\\\'', '\\"', '\0', '\n', '\r', '\Z'];
        $replace = ['\\', '\'', '"', "\x00", "\x0a", "\x0d", "\x1a"];

        return str_replace($search, $replace, $str);
    }

    /**
     * Setting the internal error message value, $this->parse_error and returns that value.
     *
     * @param string $msg Input error message
     * @param string $restQuery Remaining query to parse.
     * @return string Error message.
     */
    protected function parseError($msg, $restQuery)
    {
        $this->parse_error = 'SQL engine parse ERROR: ' . $msg . ': near "' . substr($restQuery, 0, 50) . '"';
        return $this->parse_error;
    }

    /**
     * Trimming SQL as preparation for parsing.
     * ";" in the end is stripped off.
     * White space is trimmed away around the value
     * A single space-char is added in the end
     *
     * @param string $str Input string
     * @return string Output string
     */
    protected function trimSQL($str)
    {
        return rtrim(rtrim(trim($str), ';')) . ' ';
    }

    /*************************
     *
     * Compiling queries
     *
     *************************/

    /**
     * Compiles an SQL query from components
     *
     * @param array $components Array of SQL query components
     * @return string SQL query
     * @see parseSQL()
     */
    public function compileSQL($components)
    {
        return $this->getSqlCompiler()->compileSQL($components);
    }

    /**
     * Compiles a "SELECT [output] FROM..:" field list based on input array (made with ->parseFieldList())
     * Can also compile field lists for ORDER BY and GROUP BY.
     *
     * @param array $selectFields Array of select fields, (made with ->parseFieldList())
     * @param bool $compileComments Whether comments should be compiled
     * @return string Select field string
     * @see parseFieldList()
     */
    public function compileFieldList($selectFields, $compileComments = true)
    {
        return $this->getSqlCompiler()->compileFieldList($selectFields, $compileComments);
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
        return $this->getSqlCompiler()->compileFromTables($tablesArray);
    }

    /**
     * Compile field definition
     *
     * @param array $fieldCfg Field definition parts
     * @return string Field definition string
     */
    public function compileFieldCfg($fieldCfg)
    {
        return $this->getSqlCompiler()->compileFieldCfg($fieldCfg);
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
        return $this->getSqlCompiler()->compileWhereClause($clauseArray, $functionMapping);
    }

    /**
     * @return \TYPO3\CMS\Dbal\Database\SqlCompilers\Adodb|\TYPO3\CMS\Dbal\Database\SqlCompilers\Mysql
     */
    protected function getSqlCompiler()
    {
        if ((string)$this->databaseConnection->handlerCfg[$this->databaseConnection->lastHandlerKey]['type'] === 'native') {
            return $this->nativeSqlCompiler;
        } else {
            return $this->sqlCompiler;
        }
    }

    /*************************
     *
     * Debugging
     *
     *************************/
    /**
     * Performs the ultimate test of the parser: Direct a SQL query in; You will get it back (through the parsed and re-compiled) if no problems, otherwise the script will print the error and exit
     *
     * @param string $SQLquery SQL query
     * @return string Query if all is well, otherwise exit.
     */
    public function debug_testSQL($SQLquery)
    {
        // Getting result array:
        $parseResult = $this->parseSQL($SQLquery);
        // If result array was returned, proceed. Otherwise show error and exit.
        if (is_array($parseResult)) {
            // Re-compile query:
            $newQuery = $this->compileSQL($parseResult);
            // TEST the new query:
            $testResult = $this->debug_parseSQLpartCompare($SQLquery, $newQuery);
            // Return new query if OK, otherwise show error and exit:
            if (!is_array($testResult)) {
                return $newQuery;
            } else {
                debug(['ERROR MESSAGE' => 'Input query did not match the parsed and recompiled query exactly (not observing whitespace)', 'TEST result' => $testResult], 'SQL parsing failed:');
                die;
            }
        } else {
            debug(['query' => $SQLquery, 'ERROR MESSAGE' => $parseResult], 'SQL parsing failed:');
            die;
        }
    }

    /**
     * Check parsability of input SQL part string; Will parse and re-compile after which it is compared
     *
     * @param string $part Part definition of string; "SELECT" = fieldlist (also ORDER BY and GROUP BY), "FROM" = table list, "WHERE" = Where clause.
     * @param string $str SQL string to verify parsability of
     * @return mixed Returns array with string 1 and 2 if error, otherwise FALSE
     */
    public function debug_parseSQLpart($part, $str)
    {
        $retVal = false;
        switch ($part) {
            case 'SELECT':
                $retVal = $this->debug_parseSQLpartCompare($str, $this->compileFieldList($this->parseFieldList($str)));
                break;
            case 'FROM':
                $retVal = $this->debug_parseSQLpartCompare($str, $this->getSqlCompiler()->compileFromTables($this->parseFromTables($str)));
                break;
            case 'WHERE':
                $retVal = $this->debug_parseSQLpartCompare($str, $this->getSqlCompiler()->compileWhereClause($this->parseWhereClause($str)));
                break;
        }
        return $retVal;
    }

    /**
     * Compare two query strings by stripping away whitespace.
     *
     * @param string $str SQL String 1
     * @param string $newStr SQL string 2
     * @param bool $caseInsensitive If TRUE, the strings are compared insensitive to case
     * @return mixed Returns array with string 1 and 2 if error, otherwise FALSE
     */
    public function debug_parseSQLpartCompare($str, $newStr, $caseInsensitive = false)
    {
        if ($caseInsensitive) {
            $str1 = strtoupper($str);
            $str2 = strtoupper($newStr);
        } else {
            $str1 = $str;
            $str2 = $newStr;
        }

        // Fixing escaped chars:
        $search = [NUL, LF, CR, SUB];
        $replace = ["\x00", "\x0a", "\x0d", "\x1a"];
        $str1 = str_replace($search, $replace, $str1);
        $str2 = str_replace($search, $replace, $str2);

        $search = self::$interQueryWhitespaces;
        if (str_replace($search, '', $this->trimSQL($str1)) !== str_replace($search, '', $this->trimSQL($str2))) {
            return [
                str_replace($search, ' ', $str),
                str_replace($search, ' ', $newStr),
            ];
        }
    }
}
