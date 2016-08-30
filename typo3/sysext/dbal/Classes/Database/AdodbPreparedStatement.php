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
 * MySQLi Prepared Statement-compatible implementation for ADOdb.
 *
 * Notice: Extends \TYPO3\CMS\Dbal\Database\DatabaseConnection to be able to access
 * protected properties solely (thus would be a "friend" class in C++).
 */
class AdodbPreparedStatement extends \TYPO3\CMS\Dbal\Database\DatabaseConnection
{
    /**
     * @var \TYPO3\CMS\Dbal\Database\DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var array
     */
    protected $queryComponents;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var \ADORecordSet_array
     */
    protected $recordSet;

    /**
     * Default constructor.
     *
     * @param string $query
     * @param array $queryComponents
     * @param \TYPO3\CMS\Dbal\Database\DatabaseConnection $databaseConnection
     */
    public function __construct($query, array $queryComponents, \TYPO3\CMS\Dbal\Database\DatabaseConnection $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
        $this->query = $query;
        $this->queryComponents = $queryComponents;
        $this->parameters = [];
    }

    /**
     * Prepares an SQL statement for execution.
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function prepare()
    {
        // @todo actually prepare the query with ADOdb, if supported by the underlying DBMS
        // see: http://phplens.com/lens/adodb/docs-adodb.htm#prepare
        return true;
    }

    /**
     * Transfers a result set from a prepared statement.
     *
     * @return TRUE on success or FALSE on failure
     */
    public function store_result()
    {
        return true;
    }

    /**
     * Binds variables to a prepared statement as parameters.
     *
     * @param string $types
     * @param mixed $var1 The number of variables and length of string types must match the parameters in the statement.
     * @param mixed $_ [optional]
     * @return bool TRUE on success or FALSE on failure
     * @see \mysqli_stmt::bind_param()
     */
    public function bind_param($types, $var1, $_ = null)
    {
        $numberOfVariables = strlen($types);
        if (func_num_args() !== $numberOfVariables + 1) {
            return false;
        }

        $this->parameters = [
            [
                'type' => $types{0},
                'value' => $var1
            ],
        ];
        for ($i = 1; $i < $numberOfVariables; $i++) {
            $this->parameters[] = [
                'type' => $types{$i},
                'value' => func_get_arg($i + 1),
            ];
        }

        return true;
    }

    /**
     * Resets a prepared statement.
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function reset()
    {
        return true;
    }

    /**
     * Executes a prepared query.
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function execute()
    {
        $queryParts = $this->queryComponents['queryParts'];
        $numberOfParameters = count($this->parameters);
        for ($i = 0; $i < $numberOfParameters; $i++) {
            $value = $this->parameters[$i]['value'];
            switch ($this->parameters[$i]['type']) {
                case 's':
                    if ($value !== null) {
                        $value = $this->databaseConnection->fullQuoteStr($value, $this->queryComponents['ORIG_tableName']);
                    }
                    break;
                case 'i':
                    $value = (int)$value;
                    break;
                default:
                    // Same error as in \TYPO3\CMS\Core\Database\PreparedStatement::execute()
                    throw new \InvalidArgumentException(sprintf('Unknown type %s used for parameter %s.', $this->parameters[$i]['type'], $i + 1), 1281859196);
            }

            $queryParts[$i * 2 + 1] = $value;
        }

        // Standard query from now on
        $query = implode('', $queryParts);

        $limit = $this->queryComponents['LIMIT'];
        if ($this->databaseConnection->runningADOdbDriver('postgres')) {
            // Possibly rewrite the LIMIT to be PostgreSQL-compatible
            $splitLimit = GeneralUtility::intExplode(',', $limit);
            // Splitting the limit values:
            if ($splitLimit[1]) {
                // If there are two parameters, do mapping differently than otherwise:
                $numRows = $splitLimit[1];
                $offset = $splitLimit[0];
                $limit = $numRows . ' OFFSET ' . $offset;
            }
        }
        if ($limit !== '') {
            $splitLimit = GeneralUtility::intExplode(',', $limit);
            // Splitting the limit values:
            if ($splitLimit[1]) {
                // If there are two parameters, do mapping differently than otherwise:
                $numRows = $splitLimit[1];
                $offset = $splitLimit[0];
            } else {
                $numRows = $splitLimit[0];
                $offset = 0;
            }
            $this->recordSet = $this->databaseConnection->handlerInstance[$this->databaseConnection->lastHandlerKey]->SelectLimit($query, $numRows, $offset);
            $this->databaseConnection->lastQuery = $this->recordSet->sql;
        } else {
            $this->databaseConnection->lastQuery = $query;
            $this->recordSet = $this->databaseConnection->handlerInstance[$this->databaseConnection->lastHandlerKey]->_Execute($this->databaseConnection->lastQuery);
        }

        if ($this->recordSet !== false) {
            $success = true;
            $this->recordSet->TYPO3_DBAL_handlerType = 'adodb';
            // Setting handler type in result object (for later recognition!)
            //$this->recordSet->TYPO3_DBAL_tableList = $queryComponents['ORIG_tableName'];
        } else {
            $success = false;
        }

        return $success;
    }

    /**
     * Returns an array of objects representing the fields in a result set.
     *
     * @return array
     */
    public function fetch_fields()
    {
        return $this->recordSet !== false ? $this->recordSet->_fieldobjects : [];
    }

    /**
     * Fetches a row from the underlying result set.
     *
     * @return array Array of rows or FALSE if there are no more rows.
     */
    public function fetch()
    {
        $row = $this->databaseConnection->sql_fetch_assoc($this->recordSet);
        return $row;
    }

    /**
     * Seeks to an arbitrary row in statement result set.
     *
     * @param int $offset Must be between zero and the total number of rows minus one
     * @return bool TRUE on success or FALSE on failure
     */
    public function data_seek($offset)
    {
        return $this->databaseConnection->sql_data_seek($this->recordSet, $offset);
    }

    /**
     * Closes a prepared statement.
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function close()
    {
        return $this->databaseConnection->sql_free_result($this->recordSet);
    }

    /**
     * Magic getter for public properties of \mysqli_stmt access
     * by \TYPO3\CMS\Core\Database\PreparedStatement.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'errno':
                $output = $this->databaseConnection->sql_errno();
                break;
            case 'error':
                $output = $this->databaseConnection->sql_error();
                break;
            case 'num_rows':
                $output = $this->databaseConnection->sql_num_rows($this->recordSet);
                break;
            default:
                throw new \RuntimeException('Cannot access property ' . $name, 1394631927);
        }
        return $output;
    }
}
