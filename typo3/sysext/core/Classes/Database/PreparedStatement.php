<?php
namespace TYPO3\CMS\Core\Database;

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
 * TYPO3 prepared statement for DatabaseConnection
 *
 * USE:
 * In all TYPO3 scripts when you need to create a prepared query:
 * <code>
 * $statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', 'pages', 'uid = :uid');
 * $statement->execute(array(':uid' => 2));
 * while (($row = $statement->fetch()) !== FALSE) {
 * ...
 * }
 * $statement->free();
 * </code>
 */
class PreparedStatement
{
    /**
     * Represents the SQL NULL data type.
     *
     * @var int
     */
    const PARAM_NULL = 0;

    /**
     * Represents the SQL INTEGER data type.
     *
     * @var int
     */
    const PARAM_INT = 1;

    /**
     * Represents the SQL CHAR, VARCHAR, or other string data type.
     *
     * @var int
     */
    const PARAM_STR = 2;

    /**
     * Represents a boolean data type.
     *
     * @var int
     */
    const PARAM_BOOL = 3;

    /**
     * Automatically detects underlying type
     *
     * @var int
     */
    const PARAM_AUTOTYPE = 4;

    /**
     * Specifies that the fetch method shall return each row as an array indexed by
     * column name as returned in the corresponding result set. If the result set
     * contains multiple columns with the same name, \TYPO3\CMS\Core\Database\PreparedStatement::FETCH_ASSOC
     * returns only a single value per column name.
     *
     * @var int
     */
    const FETCH_ASSOC = 2;

    /**
     * Specifies that the fetch method shall return each row as an array indexed by
     * column number as returned in the corresponding result set, starting at column 0.
     *
     * @var int
     */
    const FETCH_NUM = 3;

    /**
     * Query to be executed.
     *
     * @var string
     */
    protected $query;

    /**
     * Components of the query to be executed.
     *
     * @var array
     */
    protected $precompiledQueryParts;

    /**
     * Table (used to call $GLOBALS['TYPO3_DB']->fullQuoteStr().
     *
     * @var string
     */
    protected $table;

    /**
     * Binding parameters.
     *
     * @var array
     */
    protected $parameters;

    /**
     * Default fetch mode.
     *
     * @var int
     */
    protected $defaultFetchMode = self::FETCH_ASSOC;

    /**
     * MySQLi statement object / DBAL object
     *
     * @var \mysqli_stmt|object
     */
    protected $statement;

    /**
     * @var array
     */
    protected $fields;

    /**
     * @var array
     */
    protected $buffer;

    /**
     * Random token which is wrapped around the markers
     * that will be replaced by user input.
     *
     * @var string
     */
    protected $parameterWrapToken;

    /**
     * Creates a new PreparedStatement. Either $query or $queryComponents
     * should be used. Typically $query will be used by native MySQL TYPO3_DB
     * on a ready-to-be-executed query. On the other hand, DBAL will have
     * parse the query and will be able to safely know where parameters are used
     * and will use $queryComponents instead.
     *
     * This constructor may only be used by \TYPO3\CMS\Core\Database\DatabaseConnection
     *
     * @param string $query SQL query to be executed
     * @param string $table FROM table, used to call $GLOBALS['TYPO3_DB']->fullQuoteStr().
     * @param array $precompiledQueryParts Components of the query to be executed
     * @access private
     */
    public function __construct($query, $table, array $precompiledQueryParts = [])
    {
        $this->query = $query;
        $this->precompiledQueryParts = $precompiledQueryParts;
        $this->table = $table;
        $this->parameters = [];

        // Test if named placeholders are used
        if ($this->hasNamedPlaceholders($query) || !empty($precompiledQueryParts)) {
            $this->statement = null;
        } else {
            // Only question mark placeholders are used
            $this->statement = $GLOBALS['TYPO3_DB']->prepare_PREPAREDquery($this->query, $this->precompiledQueryParts);
        }

        $this->parameterWrapToken = $this->generateParameterWrapToken();
    }

    /**
     * Binds an array of values to corresponding named or question mark placeholders in the SQL
     * statement that was use to prepare the statement.
     *
     * Example 1:
     * <code>
     * $statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', 'bugs', 'reported_by = ? AND bug_status = ?');
     * $statement->bindValues(array('goofy', 'FIXED'));
     * </code>
     *
     * Example 2:
     * <code>
     * $statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', 'bugs', 'reported_by = :nickname AND bug_status = :status');
     * $statement->bindValues(array(':nickname' => 'goofy', ':status' => 'FIXED'));
     * </code>
     *
     * @param array $values The values to bind to the parameter. The PHP type of each array value will be used to decide which PARAM_* type to use (int, string, boolean, NULL), so make sure your variables are properly casted, if needed.
     * @return \TYPO3\CMS\Core\Database\PreparedStatement The current prepared statement to allow method chaining
     * @api
     */
    public function bindValues(array $values)
    {
        foreach ($values as $parameter => $value) {
            $key = is_int($parameter) ? $parameter + 1 : $parameter;
            $this->bindValue($key, $value, self::PARAM_AUTOTYPE);
        }
        return $this;
    }

    /**
     * Binds a value to a corresponding named or question mark placeholder in the SQL
     * statement that was use to prepare the statement.
     *
     * Example 1:
     * <code>
     * $statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', 'bugs', 'reported_by = ? AND bug_status = ?');
     * $statement->bindValue(1, 'goofy');
     * $statement->bindValue(2, 'FIXED');
     * </code>
     *
     * Example 2:
     * <code>
     * $statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', 'bugs', 'reported_by = :nickname AND bug_status = :status');
     * $statement->bindValue(':nickname', 'goofy');
     * $statement->bindValue(':status', 'FIXED');
     * </code>
     *
     * @param mixed $parameter Parameter identifier. For a prepared statement using named placeholders, this will be a parameter name of the form :name. For a prepared statement using question mark placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter.
     * @param int $data_type Explicit data type for the parameter using the \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_* constants. If not given, the PHP type of the value will be used instead (int, string, boolean).
     * @return \TYPO3\CMS\Core\Database\PreparedStatement The current prepared statement to allow method chaining
     * @api
     */
    public function bindValue($parameter, $value, $data_type = self::PARAM_AUTOTYPE)
    {
        switch ($data_type) {
            case self::PARAM_INT:
                if (!is_int($value)) {
                    throw new \InvalidArgumentException('$value is not an integer as expected: ' . $value, 1281868686);
                }
                break;
            case self::PARAM_BOOL:
                if (!is_bool($value)) {
                    throw new \InvalidArgumentException('$value is not a boolean as expected: ' . $value, 1281868687);
                }
                break;
            case self::PARAM_NULL:
                if (!is_null($value)) {
                    throw new \InvalidArgumentException('$value is not NULL as expected: ' . $value, 1282489834);
                }
                break;
        }
        if (!is_int($parameter) && !preg_match('/^:[\\w]+$/', $parameter)) {
            throw new \InvalidArgumentException('Parameter names must start with ":" followed by an arbitrary number of alphanumerical characters.', 1395055513);
        }
        $key = is_int($parameter) ? $parameter - 1 : $parameter;
        $this->parameters[$key] = [
            'value' => $value,
            'type' => $data_type == self::PARAM_AUTOTYPE ? $this->guessValueType($value) : $data_type
        ];
        return $this;
    }

    /**
     * Executes the prepared statement. If the prepared statement included parameter
     * markers, you must either:
     * <ul>
     * <li>call {@link \TYPO3\CMS\Core\Database\PreparedStatement::bindParam()} to bind PHP variables
     * to the parameter markers: bound variables pass their value as input</li>
     * <li>or pass an array of input-only parameter values</li>
     * </ul>
     *
     * $input_parameters behave as in {@link \TYPO3\CMS\Core\Database\PreparedStatement::bindParams()}
     * and work for both named parameters and question mark parameters.
     *
     * Example 1:
     * <code>
     * $statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', 'bugs', 'reported_by = ? AND bug_status = ?');
     * $statement->execute(array('goofy', 'FIXED'));
     * </code>
     *
     * Example 2:
     * <code>
     * $statement = $GLOBALS['TYPO3_DB']->prepare_SELECTquery('*', 'bugs', 'reported_by = :nickname AND bug_status = :status');
     * $statement->execute(array(':nickname' => 'goofy', ':status' => 'FIXED'));
     * </code>
     *
     * @param array $input_parameters An array of values with as many elements as there are bound parameters in the SQL statement being executed. The PHP type of each array value will be used to decide which PARAM_* type to use (int, string, boolean, NULL), so make sure your variables are properly casted, if needed.
     * @return bool Returns TRUE on success or FALSE on failure.
     * @throws \InvalidArgumentException
     * @api
     */
    public function execute(array $input_parameters = [])
    {
        $parameterValues = $this->parameters;
        if (!empty($input_parameters)) {
            $parameterValues = [];
            foreach ($input_parameters as $key => $value) {
                $parameterValues[$key] = [
                    'value' => $value,
                    'type' => $this->guessValueType($value)
                ];
            }
        }

        if ($this->statement !== null) {
            // The statement has already been executed, we try to reset it
            // for current run but will set it to NULL if it fails for some
            // reason, just as if it were the first run
            if (!@$this->statement->reset()) {
                $this->statement = null;
            }
        }
        if ($this->statement === null) {
            // The statement has never been executed so we prepare it and
            // store it for further reuse
            $query = $this->query;
            $precompiledQueryParts = $this->precompiledQueryParts;

            $this->convertNamedPlaceholdersToQuestionMarks($query, $parameterValues, $precompiledQueryParts);
            if (!empty($precompiledQueryParts)) {
                $query = implode('', $precompiledQueryParts['queryParts']);
            }
            $this->statement = $GLOBALS['TYPO3_DB']->prepare_PREPAREDquery($query, $precompiledQueryParts);
            if ($this->statement === null) {
                return false;
            }
        }

        $combinedTypes = '';
        $values = [];
        foreach ($parameterValues as $parameterValue) {
            switch ($parameterValue['type']) {
                case self::PARAM_NULL:
                    $type = 's';
                    $value = null;
                    break;
                case self::PARAM_INT:
                    $type = 'i';
                    $value = (int)$parameterValue['value'];
                    break;
                case self::PARAM_STR:
                    $type = 's';
                    $value = $parameterValue['value'];
                    break;
                case self::PARAM_BOOL:
                    $type = 'i';
                    $value = $parameterValue['value'] ? 1 : 0;
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown type %s used for parameter %s.', $parameterValue['type'], $key), 1281859196);
            }

            $combinedTypes .= $type;
            $values[] = $value;
        }

        // ->bind_param requires second up to last arguments as references
        if (!empty($combinedTypes)) {
            $bindParamArguments = [];
            $bindParamArguments[] = $combinedTypes;
            $numberOfExtraParamArguments = count($values);
            for ($i = 0; $i < $numberOfExtraParamArguments; $i++) {
                $bindParamArguments[] = &$values[$i];
            }

            call_user_func_array([$this->statement, 'bind_param'], $bindParamArguments);
        }

        $success = $this->statement->execute();

        // Store result
        if (!$success || $this->statement->store_result() === false) {
            return false;
        }

        if (empty($this->fields)) {
            // Store the list of fields
            if ($this->statement instanceof \mysqli_stmt) {
                $result = $this->statement->result_metadata();
                if ($result instanceof \mysqli_result) {
                    $fields = $result->fetch_fields();
                    $result->close();
                }
            } else {
                $fields = $this->statement->fetch_fields();
            }
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    $this->fields[] = $field->name;
                }
            }
        }

        // New result set available
        $this->buffer = null;

        // Empty binding parameters
        $this->parameters = [];

        // Return the success flag
        return $success;
    }

    /**
     * Fetches a row from a result set associated with a \TYPO3\CMS\Core\Database\PreparedStatement object.
     *
     * @param int $fetch_style Controls how the next row will be returned to the caller. This value must be one of the \TYPO3\CMS\Core\Database\PreparedStatement::FETCH_* constants. If omitted, default fetch mode for this prepared query will be used.
     * @return array Array of rows or FALSE if there are no more rows.
     * @api
     */
    public function fetch($fetch_style = 0)
    {
        if ($fetch_style == 0) {
            $fetch_style = $this->defaultFetchMode;
        }

        if ($this->statement instanceof \mysqli_stmt) {
            if ($this->buffer === null) {
                $variables = [];
                $this->buffer = [];
                foreach ($this->fields as $field) {
                    $this->buffer[$field] = null;
                    $variables[] = &$this->buffer[$field];
                }

                call_user_func_array([$this->statement, 'bind_result'], $variables);
            }
            $success = $this->statement->fetch();
            $columns = $this->buffer;
        } else {
            $columns = $this->statement->fetch();
            $success = is_array($columns);
        }

        if ($success) {
            $row = [];
            foreach ($columns as $key => $value) {
                switch ($fetch_style) {
                    case self::FETCH_ASSOC:
                        $row[$key] = $value;
                        break;
                    case self::FETCH_NUM:
                        $row[] = $value;
                        break;
                    default:
                        throw new \InvalidArgumentException('$fetch_style must be either TYPO3\\CMS\\Core\\Database\\PreparedStatement::FETCH_ASSOC or TYPO3\\CMS\\Core\\Database\\PreparedStatement::FETCH_NUM', 1281646455);
                }
            }
        } else {
            $row = false;
        }

        return $row;
    }

    /**
     * Moves internal result pointer.
     *
     * @param int $rowNumber Where to place the result pointer (0 = start)
     * @return bool Returns TRUE on success or FALSE on failure.
     * @api
     */
    public function seek($rowNumber)
    {
        $success = $this->statement->data_seek((int)$rowNumber);
        if ($this->statement instanceof \mysqli_stmt) {
            // data_seek() does not return anything
            $success = true;
        }
        return $success;
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param int $fetch_style Controls the contents of the returned array as documented in {@link \TYPO3\CMS\Core\Database\PreparedStatement::fetch()}.
     * @return array Array of rows.
     * @api
     */
    public function fetchAll($fetch_style = 0)
    {
        $rows = [];
        while (($row = $this->fetch($fetch_style)) !== false) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Releases the cursor. Should always be call after having fetched rows from
     * a query execution.
     *
     * @return void
     * @api
     */
    public function free()
    {
        $this->statement->close();
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @return int The number of rows.
     * @api
     */
    public function rowCount()
    {
        return $this->statement->num_rows;
    }

    /**
     * Returns the error number on the last execute() call.
     *
     * @return int Driver specific error code.
     * @api
     */
    public function errorCode()
    {
        return $this->statement->errno;
    }

    /**
     * Returns an array of error information about the last operation performed by this statement handle.
     * The array consists of the following fields:
     * <ol start="0">
     * <li>Driver specific error code.</li>
     * <li>Driver specific error message</li>
     * </ol>
     *
     * @return array Array of error information.
     */
    public function errorInfo()
    {
        return [
            $this->statement->errno,
            $this->statement->error
        ];
    }

    /**
     * Sets the default fetch mode for this prepared query.
     *
     * @param int $mode One of the \TYPO3\CMS\Core\Database\PreparedStatement::FETCH_* constants
     * @return void
     * @api
     */
    public function setFetchMode($mode)
    {
        switch ($mode) {
            case self::FETCH_ASSOC:
            case self::FETCH_NUM:
                $this->defaultFetchMode = $mode;
                break;
            default:
                throw new \InvalidArgumentException('$mode must be either TYPO3\\CMS\\Core\\Database\\PreparedStatement::FETCH_ASSOC or TYPO3\\CMS\\Core\\Database\\PreparedStatement::FETCH_NUM', 1281875340);
        }
    }

    /**
     * Guesses the type of a given value.
     *
     * @param mixed $value
     * @return int One of the \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_* constants
     */
    protected function guessValueType($value)
    {
        if (is_bool($value)) {
            $type = self::PARAM_BOOL;
        } elseif (is_int($value)) {
            $type = self::PARAM_INT;
        } elseif (is_null($value)) {
            $type = self::PARAM_NULL;
        } else {
            $type = self::PARAM_STR;
        }
        return $type;
    }

    /**
     * Returns TRUE if named placeholers are used in a query.
     *
     * @param string $query
     * @return bool
     */
    protected function hasNamedPlaceholders($query)
    {
        $matches = preg_match('/(?<![\\w:]):[\\w]+\\b/', $query);
        return $matches > 0;
    }

    /**
     * Converts named placeholders into question mark placeholders in a query.
     *
     * @param string $query
     * @param array $parameterValues
     * @param array $precompiledQueryParts
     * @return void
     */
    protected function convertNamedPlaceholdersToQuestionMarks(&$query, array &$parameterValues, array &$precompiledQueryParts)
    {
        $queryPartsCount = count($precompiledQueryParts['queryParts']);
        $newParameterValues = [];
        $hasNamedPlaceholders = false;

        if ($queryPartsCount === 0) {
            $hasNamedPlaceholders = $this->hasNamedPlaceholders($query);
            if ($hasNamedPlaceholders) {
                $query = $this->tokenizeQueryParameterMarkers($query, $parameterValues);
            }
        } elseif (!empty($parameterValues)) {
            $hasNamedPlaceholders = !is_int(key($parameterValues));
            if ($hasNamedPlaceholders) {
                for ($i = 1; $i < $queryPartsCount; $i += 2) {
                    $key = $precompiledQueryParts['queryParts'][$i];
                    $precompiledQueryParts['queryParts'][$i] = '?';
                    $newParameterValues[] = $parameterValues[$key];
                }
            }
        }

        if ($hasNamedPlaceholders) {
            if ($queryPartsCount === 0) {
                // Convert named placeholders to standard question mark placeholders
                $quotedParamWrapToken = preg_quote($this->parameterWrapToken, '/');
                while (preg_match(
                    '/' . $quotedParamWrapToken . '(.*?)' . $quotedParamWrapToken . '/',
                    $query,
                    $matches
                )) {
                    $key = $matches[1];

                    $newParameterValues[] = $parameterValues[$key];
                    $query = preg_replace(
                        '/' . $quotedParamWrapToken . $key . $quotedParamWrapToken . '/',
                        '?',
                        $query,
                        1
                    );
                }
            }

            $parameterValues = $newParameterValues;
        }
    }

    /**
     * Replace the markers with unpredictable token markers.
     *
     * @param string $query
     * @param array $parameterValues
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function tokenizeQueryParameterMarkers($query, array $parameterValues)
    {
        $unnamedParameterCount = 0;
        foreach ($parameterValues as $key => $typeValue) {
            if (!is_int($key)) {
                if (!preg_match('/^:[\\w]+$/', $key)) {
                    throw new \InvalidArgumentException('Parameter names must start with ":" followed by an arbitrary number of alphanumerical characters.', 1282348825);
                }
                // Replace the marker (not preceded by a word character or a ':' but
                // followed by a word boundary)
                $query = preg_replace('/(?<![\\w:])' . preg_quote($key, '/') . '\\b/', $this->parameterWrapToken . $key . $this->parameterWrapToken, $query);
            } else {
                $unnamedParameterCount++;
            }
        }
        $parts = explode('?', $query, $unnamedParameterCount + 1);
        $query = implode($this->parameterWrapToken . '?' . $this->parameterWrapToken, $parts);
        return $query;
    }

    /**
     * Generate a random token that is used to wrap the query markers
     *
     * @return string
     */
    protected function generateParameterWrapToken()
    {
        return '__' . \TYPO3\CMS\Core\Utility\GeneralUtility::getRandomHexString(16) . '__';
    }
}
