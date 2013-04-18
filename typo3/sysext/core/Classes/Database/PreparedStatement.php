<?php
namespace TYPO3\CMS\Core\Database;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <typo3@perseguers.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 */
class PreparedStatement {

	/**
	 * Represents the SQL NULL data type.
	 *
	 * @var integer
	 */
	const PARAM_NULL = 0;
	/**
	 * Represents the SQL INTEGER data type.
	 *
	 * @var integer
	 */
	const PARAM_INT = 1;
	/**
	 * Represents the SQL CHAR, VARCHAR, or other string data type.
	 *
	 * @var integer
	 */
	const PARAM_STR = 2;
	/**
	 * Represents a boolean data type.
	 *
	 * @var integer
	 */
	const PARAM_BOOL = 3;
	/**
	 * Automatically detects underlying type
	 *
	 * @var integer
	 */
	const PARAM_AUTOTYPE = 4;
	/**
	 * Specifies that the fetch method shall return each row as an array indexed by
	 * column name as returned in the corresponding result set. If the result set
	 * contains multiple columns with the same name, \TYPO3\CMS\Core\Database\PreparedStatement::FETCH_ASSOC
	 * returns only a single value per column name.
	 *
	 * @var integer
	 */
	const FETCH_ASSOC = 2;
	/**
	 * Specifies that the fetch method shall return each row as an array indexed by
	 * column number as returned in the corresponding result set, starting at column 0.
	 *
	 * @var integer
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
	 * @var integer
	 */
	protected $defaultFetchMode = self::FETCH_ASSOC;

	/**
	 * MySQLi result object / DBAL object
	 *
	 * @var boolean|\mysqli_result|object
	 */
	protected $resource;

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
	public function __construct($query, $table, array $precompiledQueryParts = array()) {
		$this->query = $query;
		$this->precompiledQueryParts = $precompiledQueryParts;
		$this->table = $table;
		$this->parameters = array();
		$this->resource = NULL;
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
	public function bindValues(array $values) {
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
	 * @param integer $data_type Explicit data type for the parameter using the \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_* constants. If not given, the PHP type of the value will be used instead (int, string, boolean).
	 * @return \TYPO3\CMS\Core\Database\PreparedStatement The current prepared statement to allow method chaining
	 * @api
	 */
	public function bindValue($parameter, $value, $data_type = self::PARAM_AUTOTYPE) {
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
		$key = is_int($parameter) ? $parameter - 1 : $parameter;
		$this->parameters[$key] = array(
			'value' => $value,
			'type' => $data_type == self::PARAM_AUTOTYPE ? $this->guessValueType($value) : $data_type
		);
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
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 * @api
	 */
	public function execute(array $input_parameters = array()) {
		$query = $this->query;
		$precompiledQueryParts = $this->precompiledQueryParts;
		$parameterValues = $this->parameters;
		if (count($input_parameters) > 0) {
			$parameterValues = array();
			foreach ($input_parameters as $key => $value) {
				$parameterValues[$key] = array(
					'value' => $value,
					'type' => $this->guessValueType($value)
				);
			}
		}
		$this->replaceValuesInQuery($query, $precompiledQueryParts, $parameterValues);
		if (count($precompiledQueryParts) > 0) {
			$query = implode('', $precompiledQueryParts['queryParts']);
		}
		$this->resource = $GLOBALS['TYPO3_DB']->exec_PREPAREDquery($query, $precompiledQueryParts);
		// Empty binding parameters
		$this->parameters = array();
		// Return the success flag
		return $this->resource ? TRUE : FALSE;
	}

	/**
	 * Fetches a row from a result set associated with a \TYPO3\CMS\Core\Database\PreparedStatement object.
	 *
	 * @param integer $fetch_style Controls how the next row will be returned to the caller. This value must be one of the \TYPO3\CMS\Core\Database\PreparedStatement::FETCH_* constants. If omitted, default fetch mode for this prepared query will be used.
	 * @return array Array of rows or FALSE if there are no more rows.
	 * @api
	 */
	public function fetch($fetch_style = 0) {
		if ($fetch_style == 0) {
			$fetch_style = $this->defaultFetchMode;
		}
		switch ($fetch_style) {
		case self::FETCH_ASSOC:
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->resource);
			break;
		case self::FETCH_NUM:
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($this->resource);
			break;
		default:
			throw new \InvalidArgumentException('$fetch_style must be either TYPO3\\CMS\\Core\\Database\\PreparedStatement::FETCH_ASSOC or TYPO3\\CMS\\Core\\Database\\PreparedStatement::FETCH_NUM', 1281646455);
		}
		return $row;
	}

	/**
	 * Moves internal result pointer.
	 *
	 * @param integer $rowNumber Where to place the result pointer (0 = start)
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 * @api
	 */
	public function seek($rowNumber) {
		return $GLOBALS['TYPO3_DB']->sql_data_seek($this->resource, intval($rowNumber));
	}

	/**
	 * Returns an array containing all of the result set rows.
	 *
	 * @param integer $fetch_style Controls the contents of the returned array as documented in {@link \TYPO3\CMS\Core\Database\PreparedStatement::fetch()}.
	 * @return array Array of rows.
	 * @api
	 */
	public function fetchAll($fetch_style = 0) {
		$rows = array();
		while (($row = $this->fetch($fetch_style)) !== FALSE) {
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
	public function free() {
		$GLOBALS['TYPO3_DB']->sql_free_result($this->resource);
	}

	/**
	 * Returns the number of rows affected by the last SQL statement.
	 *
	 * @return integer The number of rows.
	 * @api
	 */
	public function rowCount() {
		return $GLOBALS['TYPO3_DB']->sql_num_rows($this->resource);
	}

	/**
	 * Returns the error number on the last execute() call.
	 *
	 * @return integer Driver specific error code.
	 * @api
	 */
	public function errorCode() {
		return $GLOBALS['TYPO3_DB']->sql_errno();
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
	public function errorInfo() {
		return array(
			$GLOBALS['TYPO3_DB']->sql_errno(),
			$GLOBALS['TYPO3_DB']->sql_error()
		);
	}

	/**
	 * Sets the default fetch mode for this prepared query.
	 *
	 * @param integer $mode One of the \TYPO3\CMS\Core\Database\PreparedStatement::FETCH_* constants
	 * @return void
	 * @api
	 */
	public function setFetchMode($mode) {
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
	 * @return integer One of the \TYPO3\CMS\Core\Database\PreparedStatement::PARAM_* constants
	 */
	protected function guessValueType($value) {
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
	 * Replaces values for each parameter in a query.
	 *
	 * @param string $query
	 * @param array $precompiledQueryParts
	 * @param array $parameterValues
	 * @return void
	 */
	protected function replaceValuesInQuery(&$query, array &$precompiledQueryParts, array $parameterValues) {
		if (count($precompiledQueryParts['queryParts']) === 0) {
			$query = $this->tokenizeQueryParameterMarkers($query, $parameterValues);
		}
		foreach ($parameterValues as $key => $typeValue) {
			switch ($typeValue['type']) {
			case self::PARAM_NULL:
				$value = 'NULL';
				break;
			case self::PARAM_INT:
				$value = intval($typeValue['value']);
				break;
			case self::PARAM_STR:
				$value = $GLOBALS['TYPO3_DB']->fullQuoteStr($typeValue['value'], $this->table);
				break;
			case self::PARAM_BOOL:
				$value = $typeValue['value'] ? 1 : 0;
				break;
			default:
				throw new \InvalidArgumentException(sprintf('Unknown type %s used for parameter %s.', $typeValue['type'], $key), 1281859196);
			}
			if (is_int($key)) {
				if (count($precompiledQueryParts['queryParts']) > 0) {
					$precompiledQueryParts['queryParts'][2 * $key + 1] = $value;
				} else {
					$parts = explode($this->parameterWrapToken . '?' . $this->parameterWrapToken, $query, 2);
					$parts[0] .= $value;
					$query = implode('', $parts);
				}
			} else {
				$queryPartsCount = count($precompiledQueryParts['queryParts']);
				for ($i = 1; $i < $queryPartsCount; $i++) {
					if ($precompiledQueryParts['queryParts'][$i] === $key) {
						$precompiledQueryParts['queryParts'][$i] = $value;
					}
				}
				$query = str_replace($this->parameterWrapToken . $key . $this->parameterWrapToken, $value, $query);
			}
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
	protected function tokenizeQueryParameterMarkers($query, array $parameterValues) {
		$unnamedParameterCount = 0;
		foreach ($parameterValues as $key => $typeValue) {
			if (!is_int($key)) {
				if (!preg_match('/^:[\\w]+$/', $key)) {
					throw new \InvalidArgumentException('Parameter names must start with ":" followed by an arbitrary number of alphanumerical characters.', 1282348825);
				}
				// Replace the marker (not preceeded by a word character or a ':' but
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
	protected function generateParameterWrapToken() {
		return '__' . \TYPO3\CMS\Core\Utility\GeneralUtility::getRandomHexString(16) . '__';
	}

}


?>
