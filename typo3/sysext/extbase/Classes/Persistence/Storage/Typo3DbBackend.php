<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * A Storage backend
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: $
 * @scope prototype
 */
class Tx_Extbase_Persistence_Storage_Typo3DbBackend implements Tx_Extbase_Persistence_Storage_BackendInterface, t3lib_Singleton {

	/**
	 * The TYPO3 database object
	 *
	 * @var t3lib_db
	 */
	protected $databaseHandle;

	/**
	 * The TYPO3 page select object. Used for language and workspace overlay
	 *
	 * @var t3lib_pageSelect
	 */
	protected $pageSelectObject;

	/**
	 * Constructs this Storage Backend instance
	 */
	public function __construct() {
		$this->databaseHandle = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Adds a row to the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to be inserted
	 * @return int The uid of the inserted row
	 */
	public function addRow($tableName, array $row) {
		$fields = array();
		$values = array();
		$parameters = array();
		unset($row['uid']); // TODO Check if the offset exists
		foreach ($row as $columnName => $value) {
			$fields[] = $columnName;
			$values[] = '?';
			$parameters[] = $value;
		}

		$sqlString = 'INSERT INTO ' . $tableName . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
		$this->replacePlaceholders($sqlString, $parameters);

		$this->databaseHandle->sql_query($sqlString);
		return $this->databaseHandle->sql_insert_id();
	}

	/**
	 * Updates a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to be updated
	 * @return void
	 */
	public function updateRow($tableName, array $row) {
		if (!isset($row['uid'])) throw new InvalidArgumentException('The given row must contain a value for "uid".');
		$uid = (int)$row['uid'];
		unset($row['uid']);
		$fields = array();
		$parameters = array();
		foreach ($row as $columnName => $value) {
			$fields[] = $columnName . '=?';
			$parameters[] = $value;
		}
		$parameters[] = $uid;

		$sqlString = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $fields) . ' WHERE uid=?';
		$this->replacePlaceholders($sqlString, $parameters);

		return $this->databaseHandle->sql_query($sqlString);
	}

	/**
	 * Deletes a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $uid The uid of the row to be deleted
	 * @return void
	 */
	public function removeRow($tableName, $uid) {
		$sqlString = 'DELETE FROM ' . $tableName . ' WHERE uid=?';
		$this->replacePlaceholders($sqlString, array((int)$uid));
		return $this->databaseHandle->sql_query($sqlString);
	}

	/**
	 * Returns an array with tuples matching the query.
	 *
	 * @param Tx_Extbase_Persistence_QOM_QueryObjectModelInterface $query
	 * @return array The matching tuples
	 */
	public function getRows(Tx_Extbase_Persistence_QOM_QueryObjectModelInterface $query) {
		$sql = array();
		$parameters = array();
		$tuples = array();


		$this->parseSource($query, $sql, $parameters);
		$this->parseConstraint($query->getConstraint(), $sql, $parameters, $query->getBoundVariableValues());
		$this->parseOrderings($query->getOrderings(), $sql, $parameters, $query->getBoundVariableValues());

		$sqlString = 'SELECT ' . implode(',', $sql['fields']) . ' FROM ' . implode(' ', $sql['tables']);
		if (!empty($sql['where'])) {
			$sqlString .= ' WHERE ' . implode(' AND ', $sql['where']);
		}
		if (!empty($sql['orderings'])) {
			$sqlString .= ' ORDER BY ' . implode(', ', $sql['orderings']);
		}
		$this->replacePlaceholders($sqlString, $parameters);
		$result = $this->databaseHandle->sql_query($sqlString);
		if ($result) {
			$tuples = $this->getRowsFromResult($query->getSelectorName(), $result);
		}
		return $tuples;
	}

	/**
	 * Checks if a Value Object equal to the given Object exists in the data base
	 *
	 * @param array $properties The properties of the Value Object
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The Data Map
	 * @return array The matching tuples
	 */
	public function hasValueObject(array $properties, Tx_Extbase_Persistence_Mapper_DataMap $dataMap) {
		$fields = array();
		$parameters = array();
		foreach ($properties as $propertyName => $propertyValue) {
			if ($dataMap->isPersistableProperty($propertyName) && ($propertyName !== 'uid')) {
				$fields[] = $dataMap->getColumnMap($propertyName)->getColumnName() . '=?';
				$parameters[] = $dataMap->convertPropertyValueToFieldValue($propertyValue);
			}
		}

		$sqlString = 'SELECT * FROM ' . $dataMap->getTableName() .  ' WHERE ' . implode(' AND ', $fields);
		$this->replacePlaceholders($sqlString, $parameters);
		$res = $this->databaseHandle->sql_query($sqlString);
		$row = $this->databaseHandle->sql_fetch_assoc($res);
		if ($row !== FALSE) {
			return $row['uid'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Transforms a Query Source into SQL and parameter arrays
	 *
	 * @param Tx_Extbase_Persistence_QOM_QueryObjectModel $query
	 * @param array &$sql
	 * @param array &$parameters
	 * @return void
	 */
	protected function parseSource(Tx_Extbase_Persistence_QOM_QueryObjectModel $query, array &$sql, array &$parameters) {
		$source = $query->getSource();
		$sql['where'] = array();
		if ($source instanceof Tx_Extbase_Persistence_QOM_SelectorInterface) {
			$selectorName = $source->getSelectorName();
			$sql['fields'][] = $selectorName . '.*';
			$sql['tables'][] = $selectorName;
			// TODO Should we make the usage of enableFields configurable? And how? Because the Query object and even the QOM should be abstracted from the storage backend.
			$this->addEnableFieldsStatement($selectorName, $sql);
		} elseif ($source instanceof Tx_Extbase_Persistence_QOM_JoinInterface) {
			$this->parseJoin($source, $sql, $parameters);
		}
	}

	/**
	 * Transforms a Join into SQL and parameter arrays
	 *
	 * @param Tx_Extbase_Persistence_QOM_JoinInterface $join
	 * @param array &$sql
	 * @param array &$parameters
	 * @return void
	 */
	protected function parseJoin(Tx_Extbase_Persistence_QOM_JoinInterface $join, array &$sql, array &$parameters) {
		$leftSelectorName = $join->getLeft()->getSelectorName();
		$rightSelectorName = $join->getRight()->getSelectorName();

		$sql['fields'][] = $leftSelectorName . '.*';
		$sql['fields'][] = $rightSelectorName . '.*';

		// TODO Implement support for different join types and nested joins
		$sql['tables'][] = $leftSelectorName . ' INNER JOIN ' . $rightSelectorName;

		$joinCondition = $join->getJoinCondition();
		// TODO Check the parsing of the join
		if ($joinCondition instanceof Tx_Extbase_Persistence_QOM_EquiJoinCondition) {
			$sql['tables'][] = 'ON ' . $joinCondition->getSelector1Name() . '.' . $joinCondition->getProperty1Name() . ' = ' . $joinCondition->getSelector2Name() . '.' . $joinCondition->getProperty2Name();
		}
		// TODO Implement childtableWhere
	}

	/**
	 * Transforms a constraint into SQL and parameter arrays
	 *
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint
	 * @param array &$sql
	 * @param array &$parameters
	 * @param array $boundVariableValues
	 * @return void
	 */
	protected function parseConstraint(Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint = NULL, array &$sql, array &$parameters, array $boundVariableValues) {
		if ($constraint instanceof Tx_Extbase_Persistence_QOM_AndInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ' AND ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof Tx_Extbase_Persistence_QOM_OrInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ' OR ';
			$this->parseConstraint($constraint->getConstraint2(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof Tx_Extbase_Persistence_QOM_NotInterface) {
			$sql['where'][] = '(NOT ';
			$this->parseConstraint($constraint->getConstraint(), $sql, $parameters, $boundVariableValues);
			$sql['where'][] = ') ';
		} elseif ($constraint instanceof Tx_Extbase_Persistence_QOM_ComparisonInterface) {
			$this->parseComparison($constraint, $sql, $parameters, $boundVariableValues);
		} elseif ($constraint instanceof Tx_Extbase_Persistence_QOM_RelatedInterface) {
			$this->parseRelated($constraint, $sql, $parameters, $boundVariableValues);
		}
	}

	/**
	 * Parse a Comparison into SQL and parameter arrays.
	 *
	 * @param Tx_Extbase_Persistence_QOM_ComparisonInterface $comparison The comparison to parse
	 * @param array &$sql SQL query parts to add to
	 * @param array &$parameters Parameters to bind to the SQL
	 * @param array $boundVariableValues The bound variables in the query and their values
	 * @return void
	 */
	protected function parseComparison(Tx_Extbase_Persistence_QOM_ComparisonInterface $comparison, array &$sql, array &$parameters, array $boundVariableValues) {
		$this->parseDynamicOperand($comparison->getOperand1(), $comparison->getOperator(), $sql, $parameters);

		if ($comparison->getOperand2() instanceof Tx_Extbase_Persistence_QOM_BindVariableValueInterface) {
			$parameters[] = $boundVariableValues[$comparison->getOperand2()->getBindVariableName()];
		}
	}

	/**
	 * Parse a DynamicOperand into SQL and parameter arrays.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @param array $boundVariableValues
	 * @param array &$parameters
	 * @param string $valueFunction an aoptional SQL function to apply to the operand value
	 * @return void
	 */
	protected function parseDynamicOperand(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand, $operator, array &$sql, array &$parameters, $valueFunction = NULL) {
		if ($operand instanceof Tx_Extbase_Persistence_QOM_LowerCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'LOWER');
		} elseif ($operand instanceof Tx_Extbase_Persistence_QOM_UpperCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $sql, $parameters, 'UPPER');
		} elseif ($operand instanceof Tx_Extbase_Persistence_QOM_PropertyValueInterface) {
			$selectorName = $operand->getSelectorName();
			$operator = $this->resolveOperator($operator);

			$constraintSQL = '(';
			if ($valueFunction === NULL) {
				$constraintSQL .= (!empty($selectorName) ? $selectorName . '.' : '') . $operand->getPropertyName() .  ' ' . $operator . ' ?';
			} else {
				$constraintSQL .= $valueFunction . '(' . (!empty($selectorName) ? $selectorName . '.' : '') . $operand->getPropertyName() .  ' ' . $operator . ' ?';
			}
			$constraintSQL .= ') ';

			$sql['where'][] = $constraintSQL;
		}
	}

	/**
	 * Returns the SQL operator for the given JCR operator type.
	 *
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @return string an SQL operator
	 */
	protected function resolveOperator($operator) {
		switch ($operator) {
			case Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO:
				$operator = '=';
				break;
			case Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_NOT_EQUAL_TO:
				$operator = '!=';
				break;
			case Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN:
				$operator = '<';
				break;
			case Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO:
				$operator = '<=';
				break;
			case Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN:
				$operator = '>';
				break;
			case Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO:
				$operator = '>=';
				break;
			case Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE:
				$operator = 'LIKE';
				break;
			default:
				throw new Tx_Extbase_Persistence_Exception('Unsupported operator encountered.', 1242816073);
		}

		return $operator;
	}

	/**
	 * Replace query placeholders in a query part by the given
	 * parameters.
	 *
	 * @param string $queryPart The query part with placeholders
	 * @param array $parameters The parameters
	 * @return string The query part with replaced placeholders
	 */
	protected function replacePlaceholders(&$sqlString, array $parameters) {
		if (substr_count($sqlString, '?') !== count($parameters)) throw new Tx_Extbase_Persistence_Exception('The number of question marks to replace must be equal to the number of parameters.', 1242816074);
		foreach ($parameters as $parameter) {
			$markPosition = strpos($sqlString, '?');
			if ($markPosition !== FALSE) {
				$sqlString = substr($sqlString, 0, $markPosition) . '"' . $parameter . '"' . substr($sqlString, $markPosition + 1);
			}
		}
	}

	/**
	 * Returns the enable fields part of a WHERE query
	 * @param string $selectorName The selector name (= database table name)
	 * @param array &$sql The query parts
	 *
	 * @return void
	 */
	protected function addEnableFieldsStatement($selectorName, array &$sql) {
		// TODO We have to call the appropriate API method if we are in TYPO3BE mode
		$statement = substr($GLOBALS['TSFE']->sys_page->enableFields($selectorName), 4);
		if (!empty($statement)) {
			$sql['where'][] = $statement;
		}
	}

	/**
	 * Transforms orderings into SQL
	 *
	 * @param array $orderings
	 * @param array &$sql
	 * @param array &$parameters
	 * @param array $boundVariableValues
	 * @return void
	 */
	protected function parseOrderings(array $orderings = NULL, array &$sql, array &$parameters, array $boundVariableValues) {
		if (is_array($orderings)) {
			foreach ($orderings as $propertyName => $ordering) {
				// TODO Implement
			}
		}
	}

	/**
	 * Transforms a Resource from a database query to an array of rows. Performs the language and
	 * workspace overlay before.
	 *
	 * @return array The result as an array of rows (tuples)
	 */
	protected function getRowsFromResult($tableName, $res) {
		$rows = array();
		while ($row = $this->databaseHandle->sql_fetch_assoc($res)) {
			$row = $this->doLanguageAndWorkspaceOverlay($tableName, $row);
			if (is_array($row)) {
				// TODO Check if this is necessary, maybe the last line is enough
				$arrayKeys = range(0,count($row));
				array_fill_keys($arrayKeys, $row);
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
	 * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap
	 * @param array $row The row array (as reference)
	 * @param string $languageUid The language id
	 * @param string $workspaceUidUid The workspace id
	 * @return void
	 */
	protected function doLanguageAndWorkspaceOverlay($tableName, array $row, $languageUid = NULL, $workspaceUid = NULL) {
		if (!($this->pageSelectObject instanceof t3lib_pageSelect)) {
			if (TYPO3_MODE == 'FE') {
				if (is_object($GLOBALS['TSFE'])) {
					$this->pageSelectObject = $GLOBALS['TSFE']->sys_page;
					if ($languageUid === NULL) {
						$languageUid = $GLOBALS['TSFE']->sys_language_content;
					}
				} else {
					require_once(PATH_t3lib . 'class.t3lib_page.php');
					$this->pageSelectObject = t3lib_div::makeInstance('t3lib_pageSelect');
					if ($languageUid === NULL) {
						$languageUid = intval(t3lib_div::_GP('L'));
					}
				}
				if ($workspaceUid !== NULL) {
					$this->pageSelectObject->versioningWorkspaceId = $workspaceUid;
				}
			} else {
				require_once(PATH_t3lib . 'class.t3lib_page.php');
				$this->pageSelectObject = t3lib_div::makeInstance( 't3lib_pageSelect' );
				//$this->pageSelectObject->versioningPreview =  TRUE;
				if ($workspaceUid === NULL) {
					$workspaceUid = $GLOBALS['BE_USER']->workspace;
				}
				$this->pageSelectObject->versioningWorkspaceId = $workspaceUid;
			}
		}

		$this->pageSelectObject->versionOL($tableName, $row, TRUE);
		$row = $this->pageSelectObject->getRecordOverlay($tableName, $row, $languageUid, ''); //'hideNonTranslated'
		// TODO Skip if empty languageoverlay (languagevisibility)
		return $row;
	}

}

?>