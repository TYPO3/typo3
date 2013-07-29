<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Storage;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * A Storage backend
 */
class Typo3DbBackend implements \TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface, \TYPO3\CMS\Core\SingletonInterface {

	const OPERATOR_EQUAL_TO_NULL = 'operatorEqualToNull';
	const OPERATOR_NOT_EQUAL_TO_NULL = 'operatorNotEqualToNull';

	/**
	 * The TYPO3 database object
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseHandle;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;

	/**
	 * The TYPO3 page repository. Used for language and workspace overlay
	 *
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected $pageRepository;

	/**
	 * A first-level TypoScript configuration cache
	 *
	 * @var array
	 */
	protected $pageTSConfigCache = array();

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\CacheService
	 */
	protected $cacheService;

	/**
	 * @var \TYPO3\CMS\Core\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
	 */
	protected $tableColumnCache;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
	 */
	protected $environmentService;

	/**
	 * Constructor. takes the database handle from $GLOBALS['TYPO3_DB']
	 */
	public function __construct() {
		$this->databaseHandle = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param \TYPO3\CMS\Core\Cache\CacheManager $cacheManager
	 */
	public function injectCacheManager(\TYPO3\CMS\Core\Cache\CacheManager $cacheManager) {
		$this->cacheManager = $cacheManager;
	}

	/**
	 * Lifecycle method
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->tableColumnCache = $this->cacheManager->getCache('extbase_typo3dbbackend_tablecolumns');
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\CacheService $cacheService
	 * @return void
	 */
	public function injectCacheService(\TYPO3\CMS\Extbase\Service\CacheService $cacheService) {
		$this->cacheService = $cacheService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
	 * @return void
	 */
	public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService) {
		$this->environmentService = $environmentService;
	}

	/**
	 * Adds a row to the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to be inserted
	 * @param boolean $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
	 * @return int The uid of the inserted row
	 */
	public function addRow($tableName, array $row, $isRelation = FALSE) {
		$fields = array();
		$values = array();
		$parameters = array();
		if (isset($row['uid'])) {
			unset($row['uid']);
		}
		foreach ($row as $columnName => $value) {
			$fields[] = $columnName;
			$values[] = '?';
			$parameters[] = $value;
		}
		$sqlString = 'INSERT INTO ' . $tableName . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
		$this->replacePlaceholders($sqlString, $parameters, $tableName);
		// debug($sqlString,-2);
		$this->databaseHandle->sql_query($sqlString);
		$this->checkSqlErrors($sqlString);
		$uid = $this->databaseHandle->sql_insert_id();
		if (!$isRelation) {
			$this->clearPageCache($tableName, $uid);
		}
		return (integer) $uid;
	}

	/**
	 * Updates a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to be updated
	 * @param boolean $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public function updateRow($tableName, array $row, $isRelation = FALSE) {
		if (!isset($row['uid'])) {
			throw new \InvalidArgumentException('The given row must contain a value for "uid".');
		}
		$uid = (integer) $row['uid'];
		unset($row['uid']);
		$fields = array();
		$parameters = array();
		foreach ($row as $columnName => $value) {
			$fields[] = $columnName . '=?';
			$parameters[] = $value;
		}
		$parameters[] = $uid;
		$sqlString = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $fields) . ' WHERE uid=?';
		$this->replacePlaceholders($sqlString, $parameters, $tableName);
		// debug($sqlString,-2);
		$returnValue = $this->databaseHandle->sql_query($sqlString);
		$this->checkSqlErrors($sqlString);
		if (!$isRelation) {
			$this->clearPageCache($tableName, $uid);
		}
		return $returnValue;
	}

	/**
	 * Updates a relation row in the storage.
	 *
	 * @param string $tableName The database relation table name
	 * @param array $row The row to be updated
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public function updateRelationTableRow($tableName, array $row) {
		if (!isset($row['uid_local']) && !isset($row['uid_foreign'])) {
			throw new \InvalidArgumentException(
				'The given row must contain a value for "uid_local" and "uid_foreign".', 1360500126
			);
		}
		$uidLocal = (int) $row['uid_local'];
		$uidForeign = (int) $row['uid_foreign'];
		unset($row['uid_local']);
		unset($row['uid_foreign']);
		$fields = array();
		$parameters = array();
		foreach ($row as $columnName => $value) {
			$fields[] = $columnName . '=?';
			$parameters[] = $value;
		}
		$parameters[] = $uidLocal;
		$parameters[] = $uidForeign;

		$sqlString = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $fields) . ' WHERE uid_local=? AND uid_foreign=?';
		$this->replacePlaceholders($sqlString, $parameters);

		$returnValue = $this->databaseHandle->sql_query($sqlString);
		$this->checkSqlErrors($sqlString);

		return $returnValue;
	}

	/**
	 * Deletes a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $identifier An array of identifier array('fieldname' => value). This array will be transformed to a WHERE clause
	 * @param boolean $isRelation TRUE if we are currently manipulating a relation table, FALSE by default
	 * @return bool
	 */
	public function removeRow($tableName, array $identifier, $isRelation = FALSE) {
		$statement = 'DELETE FROM ' . $tableName . ' WHERE ' . $this->parseIdentifier($identifier);
		$this->replacePlaceholders($statement, $identifier, $tableName);
		if (!$isRelation && isset($identifier['uid'])) {
			$this->clearPageCache($tableName, $identifier['uid'], $isRelation);
		}
		// debug($statement, -2);
		$returnValue = $this->databaseHandle->sql_query($statement);
		$this->checkSqlErrors($statement);
		return $returnValue;
	}

	/**
	 * Fetches maximal value for given table column from database.
	 *
	 * @param string $tableName The database table name
	 * @param array $identifier An array of identifier array('fieldname' => value). This array will be transformed to a WHERE clause
	 * @param string $columnName column name to get the max value from
	 * @return mixed the max value
	 */
	public function getMaxValueFromTable($tableName, $identifier, $columnName) {
		$sqlString = 'SELECT ' . $columnName . ' FROM ' . $tableName . ' WHERE ' . $this->parseIdentifier($identifier) . ' ORDER BY  ' . $columnName . ' DESC LIMIT 1';
		$this->replacePlaceholders($sqlString, $identifier);

		$result = $this->databaseHandle->sql_query($sqlString);
		$row = $this->databaseHandle->sql_fetch_assoc($result);
		$this->checkSqlErrors($sqlString);
		return $row[$columnName];
	}

	/**
	 * Fetches row data from the database
	 *
	 * @param string $tableName
	 * @param array $identifier The Identifier of the row to fetch
	 * @return array|boolean
	 */
	public function getRowByIdentifier($tableName, array $identifier) {
		$statement = 'SELECT * FROM ' . $tableName . ' WHERE ' . $this->parseIdentifier($identifier);
		$this->replacePlaceholders($statement, $identifier, $tableName);
		// debug($statement,-2);
		$res = $this->databaseHandle->sql_query($statement);
		$this->checkSqlErrors($statement);
		$row = $this->databaseHandle->sql_fetch_assoc($res);
		if ($row !== FALSE) {
			return $row;
		} else {
			return FALSE;
		}
	}

	/**
	 * @param array $identifier
	 * @return string
	 */
	protected function parseIdentifier(array $identifier) {
		$fieldNames = array_keys($identifier);
		$suffixedFieldNames = array();
		foreach ($fieldNames as $fieldName) {
			$suffixedFieldNames[] = $fieldName . '=?';
		}
		return implode(' AND ', $suffixedFieldNames);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @return array
	 */
	public function getObjectDataByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query) {
		$statement = $query->getStatement();
		if ($statement instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement) {
			$sql = $statement->getStatement();
			$parameters = $statement->getBoundVariables();
		} else {
			$parameters = array();
			$statementParts = $this->parseQuery($query, $parameters);
			$sql = $this->buildQuery($statementParts, $parameters);
		}
		$tableName = 'foo';
		if (is_array($statementParts) && !empty($statementParts['tables'][0])) {
			$tableName = $statementParts['tables'][0];
		}
		$this->replacePlaceholders($sql, $parameters, $tableName);
		// debug($sql,-2);
		$result = $this->databaseHandle->sql_query($sql);
		$this->checkSqlErrors($sql);
		$rows = $this->getRowsFromResult($result);
		$this->databaseHandle->sql_free_result($result);
		// Get language uid from querySettings.
		// Ensure the backend handling is not broken (fallback to Get parameter 'L' if needed)
		$rows = $this->doLanguageAndWorkspaceOverlay($query->getSource(), $rows, $query->getQuerySettings());
		// TODO: implement $objectData = $this->processObjectRecords($statementHandle);
		return $rows;
	}

	/**
	 * Returns the number of tuples matching the query.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @throws Exception\BadConstraintException
	 * @return integer The number of matching tuples
	 */
	public function getObjectCountByQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query) {
		$constraint = $query->getConstraint();
		if ($constraint instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException('Could not execute count on queries with a constraint of type TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\StatementInterface', 1256661045);
		}
		$parameters = array();
		$statementParts = $this->parseQuery($query, $parameters);
		// Reset $statementParts for valid table return
		reset($statementParts);
		// if limit is set, we need to count the rows "manually" as COUNT(*) ignores LIMIT constraints
		if (!empty($statementParts['limit'])) {
			$statement = $this->buildQuery($statementParts, $parameters);
			$this->replacePlaceholders($statement, $parameters, current($statementParts['tables']));
			$result = $this->databaseHandle->sql_query($statement);
			$this->checkSqlErrors($statement);
			$count = $this->databaseHandle->sql_num_rows($result);
		} else {
			$statementParts['fields'] = array('COUNT(*)');
			// having orderings without grouping is not compatible with non-MySQL DBMS
			$statementParts['orderings'] = array();
			if (isset($statementParts['keywords']['distinct'])) {
				unset($statementParts['keywords']['distinct']);
				$statementParts['fields'] = array('COUNT(DISTINCT ' . reset($statementParts['tables']) . '.uid)');
			}
			$statement = $this->buildQuery($statementParts, $parameters);
			$this->replacePlaceholders($statement, $parameters, current($statementParts['tables']));
			$result = $this->databaseHandle->sql_query($statement);
			$this->checkSqlErrors($statement);
			$rows = $this->getRowsFromResult($result);
			$count = current(current($rows));
		}
		$this->databaseHandle->sql_free_result($result);
		return (integer) $count;
	}

	/**
	 * Parses the query and returns the SQL statement parts.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query The query
	 * @param array &$parameters
	 * @return array The SQL statement parts
	 */
	public function parseQuery(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query, array &$parameters) {
		$sql = array();
		$sql['keywords'] = array();
		$sql['tables'] = array();
		$sql['unions'] = array();
		$sql['fields'] = array();
		$sql['where'] = array();
		$sql['additionalWhereClause'] = array();
		$sql['orderings'] = array();
		$sql['limit'] = array();
		$source = $query->getSource();
		$this->parseSource($source, $sql);
		$this->parseConstraint($query->getConstraint(), $source, $sql, $parameters);
		$this->parseOrderings($query->getOrderings(), $source, $sql);
		$this->parseLimitAndOffset($query->getLimit(), $query->getOffset(), $sql);
		$tableNames = array_unique(array_keys($sql['tables'] + $sql['unions']));
		foreach ($tableNames as $tableName) {
			if (is_string($tableName) && strlen($tableName) > 0) {
				$this->addAdditionalWhereClause($query->getQuerySettings(), $tableName, $sql);
			}
		}
		return $sql;
	}

	/**
	 * Returns the statement, ready to be executed.
	 *
	 * @param array $sql The SQL statement parts
	 * @return string The SQL statement
	 */
	public function buildQuery(array $sql) {
		$statement = 'SELECT ' . implode(' ', $sql['keywords']) . ' ' . implode(',', $sql['fields']) . ' FROM ' . implode(' ', $sql['tables']) . ' ' . implode(' ', $sql['unions']);
		if (!empty($sql['where'])) {
			$statement .= ' WHERE ' . implode('', $sql['where']);
			if (!empty($sql['additionalWhereClause'])) {
				$statement .= ' AND ' . implode(' AND ', $sql['additionalWhereClause']);
			}
		} elseif (!empty($sql['additionalWhereClause'])) {
			$statement .= ' WHERE ' . implode(' AND ', $sql['additionalWhereClause']);
		}
		if (!empty($sql['orderings'])) {
			$statement .= ' ORDER BY ' . implode(', ', $sql['orderings']);
		}
		if (!empty($sql['limit'])) {
			$statement .= ' LIMIT ' . $sql['limit'];
		}
		return $statement;
	}

	/**
	 * Checks if a Value Object equal to the given Object exists in the data base
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object The Value Object
	 * @return mixed The matching uid if an object was found, else FALSE
	 */
	public function getUidOfAlreadyPersistedValueObject(\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object) {
		$fields = array();
		$parameters = array();
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			// FIXME We couple the Backend to the Entity implementation (uid, isClone); changes there breaks this method
			if ($dataMap->isPersistableProperty($propertyName) && $propertyName !== 'uid' && $propertyName !== 'pid' && $propertyName !== 'isClone') {
				if ($propertyValue === NULL) {
					$fields[] = $dataMap->getColumnMap($propertyName)->getColumnName() . ' IS NULL';
				} else {
					$fields[] = $dataMap->getColumnMap($propertyName)->getColumnName() . '=?';
					$parameters[] = $this->getPlainValue($propertyValue);
				}
			}
		}
		$sql = array();
		$sql['additionalWhereClause'] = array();
		$tableName = $dataMap->getTableName();
		$this->addVisibilityConstraintStatement(new \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings(), $tableName, $sql);
		$statement = 'SELECT * FROM ' . $tableName;
		$statement .= ' WHERE ' . implode(' AND ', $fields);
		if (!empty($sql['additionalWhereClause'])) {
			$statement .= ' AND ' . implode(' AND ', $sql['additionalWhereClause']);
		}
		$this->replacePlaceholders($statement, $parameters, $tableName);
		// debug($statement,-2);
		$res = $this->databaseHandle->sql_query($statement);
		$this->checkSqlErrors($statement);
		$row = $this->databaseHandle->sql_fetch_assoc($res);
		if ($row !== FALSE) {
			return (integer) $row['uid'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Transforms a Query Source into SQL and parameter arrays
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source The source
	 * @param array &$sql
	 * @return void
	 */
	protected function parseSource(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source, array &$sql) {
		if ($source instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface) {
			$className = $source->getNodeTypeName();
			$tableName = $this->dataMapper->getDataMap($className)->getTableName();
			$this->addRecordTypeConstraint($className, $sql);
			$sql['fields'][$tableName] = $tableName . '.*';
			$sql['tables'][$tableName] = $tableName;
		} elseif ($source instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface) {
			$this->parseJoin($source, $sql);
		}
	}

	/**
	 * Add a constraint to ensure that the record type of the returned tuples is matching the data type of the repository.
	 *
	 * @param string $className The class name
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function addRecordTypeConstraint($className, &$sql) {
		if ($className !== NULL) {
			$dataMap = $this->dataMapper->getDataMap($className);
			if ($dataMap->getRecordTypeColumnName() !== NULL) {
				$recordTypes = array();
				if ($dataMap->getRecordType() !== NULL) {
					$recordTypes[] = $dataMap->getRecordType();
				}
				foreach ($dataMap->getSubclasses() as $subclassName) {
					$subclassDataMap = $this->dataMapper->getDataMap($subclassName);
					if ($subclassDataMap->getRecordType() !== NULL) {
						$recordTypes[] = $subclassDataMap->getRecordType();
					}
				}
				if (count($recordTypes) > 0) {
					$recordTypeStatements = array();
					foreach ($recordTypes as $recordType) {
						$tableName = $dataMap->getTableName();
						$recordTypeStatements[] = $tableName . '.' . $dataMap->getRecordTypeColumnName() . '=' . $this->databaseHandle->fullQuoteStr($recordType, $tableName);
					}
					$sql['additionalWhereClause'][] = '(' . implode(' OR ', $recordTypeStatements) . ')';
				}
			}
		}
	}

	/**
	 * Transforms a Join into SQL and parameter arrays
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface $join The join
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function parseJoin(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface $join, array &$sql) {
		$leftSource = $join->getLeft();
		$leftClassName = $leftSource->getNodeTypeName();
		$this->addRecordTypeConstraint($leftClassName, $sql);
		$leftTableName = $leftSource->getSelectorName();
		// $sql['fields'][$leftTableName] = $leftTableName . '.*';
		$rightSource = $join->getRight();
		if ($rightSource instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface) {
			$rightClassName = $rightSource->getLeft()->getNodeTypeName();
			$rightTableName = $rightSource->getLeft()->getSelectorName();
		} else {
			$rightClassName = $rightSource->getNodeTypeName();
			$rightTableName = $rightSource->getSelectorName();
			$sql['fields'][$leftTableName] = $rightTableName . '.*';
		}
		$this->addRecordTypeConstraint($rightClassName, $sql);
		$sql['tables'][$leftTableName] = $leftTableName;
		$sql['unions'][$rightTableName] = 'LEFT JOIN ' . $rightTableName;
		$joinCondition = $join->getJoinCondition();
		if ($joinCondition instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\EquiJoinCondition) {
			$column1Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty1Name(), $leftClassName);
			$column2Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty2Name(), $rightClassName);
			$sql['unions'][$rightTableName] .= ' ON ' . $joinCondition->getSelector1Name() . '.' . $column1Name . ' = ' . $joinCondition->getSelector2Name() . '.' . $column2Name;
		}
		if ($rightSource instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface) {
			$this->parseJoin($rightSource, $sql);
		}
	}

	/**
	 * Transforms a constraint into SQL and parameter arrays
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint The constraint
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @param array &$parameters The parameters that will replace the markers
	 * @return void
	 */
	protected function parseConstraint(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint = NULL, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source, array &$sql, array &$parameters) {
		if ($constraint instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $source, $sql, $parameters);
			$sql['where'][] = ' AND ';
			$this->parseConstraint($constraint->getConstraint2(), $source, $sql, $parameters);
			$sql['where'][] = ')';
		} elseif ($constraint instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $source, $sql, $parameters);
			$sql['where'][] = ' OR ';
			$this->parseConstraint($constraint->getConstraint2(), $source, $sql, $parameters);
			$sql['where'][] = ')';
		} elseif ($constraint instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface) {
			$sql['where'][] = 'NOT (';
			$this->parseConstraint($constraint->getConstraint(), $source, $sql, $parameters);
			$sql['where'][] = ')';
		} elseif ($constraint instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface) {
			$this->parseComparison($constraint, $source, $sql, $parameters);
		}
	}

	/**
	 * Parse a Comparison into SQL and parameter arrays.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface $comparison The comparison to parse
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source The source
	 * @param array &$sql SQL query parts to add to
	 * @param array &$parameters Parameters to bind to the SQL
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException
	 * @return void
	 */
	protected function parseComparison(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface $comparison, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source, array &$sql, array &$parameters) {
		$operand1 = $comparison->getOperand1();
		$operator = $comparison->getOperator();
		$operand2 = $comparison->getOperand2();
		if ($operator === \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_IN) {
			$items = array();
			$hasValue = FALSE;
			foreach ($operand2 as $value) {
				$value = $this->getPlainValue($value);
				if ($value !== NULL) {
					$items[] = $value;
					$hasValue = TRUE;
				}
			}
			if ($hasValue === FALSE) {
				$sql['where'][] = '1<>1';
			} else {
				$this->parseDynamicOperand($operand1, $operator, $source, $sql, $parameters, NULL, $operand2);
				$parameters[] = $items;
			}
		} elseif ($operator === \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_CONTAINS) {
			if ($operand2 === NULL) {
				$sql['where'][] = '1<>1';
			} else {
				$className = $source->getNodeTypeName();
				$tableName = $this->dataMapper->convertClassNameToTableName($className);
				$propertyName = $operand1->getPropertyName();
				while (strpos($propertyName, '.') !== FALSE) {
					$this->addUnionStatement($className, $tableName, $propertyName, $sql);
				}
				$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
				$dataMap = $this->dataMapper->getDataMap($className);
				$columnMap = $dataMap->getColumnMap($propertyName);
				$typeOfRelation = $columnMap instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap ? $columnMap->getTypeOfRelation() : NULL;
				if ($typeOfRelation === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
					$relationTableName = $columnMap->getRelationTableName();
					$sql['where'][] = $tableName . '.uid IN (SELECT ' . $columnMap->getParentKeyFieldName() . ' FROM ' . $relationTableName . ' WHERE ' . $columnMap->getChildKeyFieldName() . '=?)';
					$parameters[] = intval($this->getPlainValue($operand2));
				} elseif ($typeOfRelation === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
					$parentKeyFieldName = $columnMap->getParentKeyFieldName();
					if (isset($parentKeyFieldName)) {
						$childTableName = $columnMap->getChildTableName();
						$sql['where'][] = $tableName . '.uid=(SELECT ' . $childTableName . '.' . $parentKeyFieldName . ' FROM ' . $childTableName . ' WHERE ' . $childTableName . '.uid=?)';
						$parameters[] = intval($this->getPlainValue($operand2));
					} else {
						$sql['where'][] = 'FIND_IN_SET(?,' . $tableName . '.' . $columnName . ')';
						$parameters[] = intval($this->getPlainValue($operand2));
					}
				} else {
					throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException('Unsupported or non-existing property name "' . $propertyName . '" used in relation matching.', 1327065745);
				}
			}
		} else {
			if ($operand2 === NULL) {
				if ($operator === \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_EQUAL_TO) {
					$operator = self::OPERATOR_EQUAL_TO_NULL;
				} elseif ($operator === \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_NOT_EQUAL_TO) {
					$operator = self::OPERATOR_NOT_EQUAL_TO_NULL;
				}
			}
			$this->parseDynamicOperand($operand1, $operator, $source, $sql, $parameters);
			$parameters[] = $this->getPlainValue($operand2);
		}
	}

	/**
	 * Returns a plain value, i.e. objects are flattened out if possible.
	 *
	 * @param mixed $input
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException
	 * @return mixed
	 */
	protected function getPlainValue($input) {
		if (is_array($input)) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException('An array could not be converted to a plain value.', 1274799932);
		}
		if ($input instanceof \DateTime) {
			return $input->format('U');
		} elseif (is_object($input)) {
			if ($input instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
				$realInput = $input->_loadRealInstance();
			} else {
				$realInput = $input;
			}
			if ($realInput instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface) {
				return $realInput->getUid();
			} else {
				throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException('An object of class "' . get_class($realInput) . '" could not be converted to a plain value.', 1274799934);
			}
		} elseif (is_bool($input)) {
			return $input === TRUE ? 1 : 0;
		} else {
			return $input;
		}
	}

	/**
	 * Parse a DynamicOperand into SQL and parameter arrays.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @param array &$parameters The parameters that will replace the markers
	 * @param string $valueFunction an optional SQL function to apply to the operand value
	 * @param null $operand2
	 * @return void
	 */
	protected function parseDynamicOperand(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface $operand, $operator, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source, array &$sql, array &$parameters, $valueFunction = NULL, $operand2 = NULL) {
		if ($operand instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\LowerCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $source, $sql, $parameters, 'LOWER');
		} elseif ($operand instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\UpperCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $source, $sql, $parameters, 'UPPER');
		} elseif ($operand instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\PropertyValueInterface) {
			$propertyName = $operand->getPropertyName();
			if ($source instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface) {
				// FIXME Only necessary to differ from  Join
				$className = $source->getNodeTypeName();
				$tableName = $this->dataMapper->convertClassNameToTableName($className);
				while (strpos($propertyName, '.') !== FALSE) {
					$this->addUnionStatement($className, $tableName, $propertyName, $sql);
				}
			} elseif ($source instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface) {
				$tableName = $source->getJoinCondition()->getSelector1Name();
			}
			$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
			$operator = $this->resolveOperator($operator);
			$constraintSQL = '';
			if ($valueFunction === NULL) {
				$constraintSQL .= (!empty($tableName) ? $tableName . '.' : '') . $columnName . ' ' . $operator . ' ?';
			} else {
				$constraintSQL .= $valueFunction . '(' . (!empty($tableName) ? $tableName . '.' : '') . $columnName . ') ' . $operator . ' ?';
			}
			$sql['where'][] = $constraintSQL;
		}
	}

	/**
	 * @param string &$className
	 * @param string &$tableName
	 * @param array &$propertyPath
	 * @param array &$sql
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidRelationConfigurationException
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\MissingColumnMapException
	 */
	protected function addUnionStatement(&$className, &$tableName, &$propertyPath, array &$sql) {
		$explodedPropertyPath = explode('.', $propertyPath, 2);
		$propertyName = $explodedPropertyPath[0];
		$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
		$tableName = $this->dataMapper->convertClassNameToTableName($className);
		$columnMap = $this->dataMapper->getDataMap($className)->getColumnMap($propertyName);

		if ($columnMap === NULL) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\MissingColumnMapException('The ColumnMap for property "' . $propertyName . '" of class "' . $className . '" is missing.', 1355142232);
		}

		$parentKeyFieldName = $columnMap->getParentKeyFieldName();
		$childTableName = $columnMap->getChildTableName();

		if ($childTableName === NULL) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidRelationConfigurationException('The relation information for property "' . $propertyName . '" of class "' . $className . '" is missing.', 1353170925);
		}

		if ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_ONE) {
			if (isset($parentKeyFieldName)) {
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $tableName . '.uid=' . $childTableName . '.' . $parentKeyFieldName;
			} else {
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $tableName . '.' . $columnName . '=' . $childTableName . '.uid';
			}
			$className = $this->dataMapper->getType($className, $propertyName);
		} elseif ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_MANY) {
			if (isset($parentKeyFieldName)) {
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $tableName . '.uid=' . $childTableName . '.' . $parentKeyFieldName;
			} else {
				$onStatement = '(FIND_IN_SET(' . $childTableName . '.uid, ' . $tableName . '.' . $columnName . '))';
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $onStatement;
			}
			$className = $this->dataMapper->getType($className, $propertyName);
		} elseif ($columnMap->getTypeOfRelation() === \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$relationTableName = $columnMap->getRelationTableName();
			$sql['unions'][$relationTableName] = 'LEFT JOIN ' . $relationTableName . ' ON ' . $tableName . '.uid=' . $relationTableName . '.' . $columnMap->getParentKeyFieldName();
			$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $relationTableName . '.' . $columnMap->getChildKeyFieldName() . '=' . $childTableName . '.uid';
			$className = $this->dataMapper->getType($className, $propertyName);
		} else {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception('Could not determine type of relation.', 1252502725);
		}
		// TODO check if there is another solution for this
		$sql['keywords']['distinct'] = 'DISTINCT';
		$propertyPath = $explodedPropertyPath[1];
		$tableName = $childTableName;
	}

	/**
	 * Returns the SQL operator for the given JCR operator type.
	 *
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
	 * @return string an SQL operator
	 */
	protected function resolveOperator($operator) {
		switch ($operator) {
			case self::OPERATOR_EQUAL_TO_NULL:
				$operator = 'IS';
				break;
			case self::OPERATOR_NOT_EQUAL_TO_NULL:
				$operator = 'IS NOT';
				break;
			case \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_IN:
				$operator = 'IN';
				break;
			case \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_EQUAL_TO:
				$operator = '=';
				break;
			case \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_NOT_EQUAL_TO:
				$operator = '!=';
				break;
			case \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_LESS_THAN:
				$operator = '<';
				break;
			case \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO:
				$operator = '<=';
				break;
			case \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_GREATER_THAN:
				$operator = '>';
				break;
			case \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO:
				$operator = '>=';
				break;
			case \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_LIKE:
				$operator = 'LIKE';
				break;
			default:
				throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception('Unsupported operator encountered.', 1242816073);
		}
		return $operator;
	}

	/**
	 * Replace query placeholders in a query part by the given
	 * parameters.
	 *
	 * @param string &$sqlString The query part with placeholders
	 * @param array $parameters The parameters
	 * @param string $tableName
	 *
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
	 */
	protected function replacePlaceholders(&$sqlString, array $parameters, $tableName = 'foo') {
		// TODO profile this method again
		if (substr_count($sqlString, '?') !== count($parameters)) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception('The number of question marks to replace must be equal to the number of parameters.', 1242816074);
		}
		$offset = 0;
		foreach ($parameters as $parameter) {
			$markPosition = strpos($sqlString, '?', $offset);
			if ($markPosition !== FALSE) {
				if ($parameter === NULL) {
					$parameter = 'NULL';
				} elseif (is_array($parameter) || $parameter instanceof \ArrayAccess || $parameter instanceof \Traversable) {
					$items = array();
					foreach ($parameter as $item) {
						$items[] = $this->databaseHandle->fullQuoteStr($item, $tableName);
					}
					$parameter = '(' . implode(',', $items) . ')';
				} else {
					$parameter = $this->databaseHandle->fullQuoteStr($parameter, $tableName);
				}
				$sqlString = substr($sqlString, 0, $markPosition) . $parameter . substr($sqlString, ($markPosition + 1));
			}
			$offset = $markPosition + strlen($parameter);
		}
	}

	/**
	 * Adds additional WHERE statements according to the query settings.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
	 * @param string $tableName The table name to add the additional where clause for
	 * @param string &$sql
	 * @return void
	 */
	protected function addAdditionalWhereClause(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings, $tableName, &$sql) {
		$this->addVisibilityConstraintStatement($querySettings, $tableName, $sql);
		if ($querySettings->getRespectSysLanguage()) {
			$this->addSysLanguageStatement($tableName, $sql, $querySettings);
		}
		if ($querySettings->getRespectStoragePage()) {
			$this->addPageIdStatement($tableName, $sql, $querySettings->getStoragePageIds());
		}
	}

	/**
	 * Builds the enable fields statement
	 *
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @return void
	 * @deprecated since Extbase 6.0, will be removed in Extbase 6.2.
	 */
	protected function addEnableFieldsStatement($tableName, array &$sql) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl'])) {
			if ($this->environmentService->isEnvironmentInFrontendMode()) {
				$statement = $this->getPageRepository()->enableFields($tableName);
			} else {
				// TYPO3_MODE === 'BE'
				$statement = \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName);
				$statement .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($tableName);
			}
			if (!empty($statement)) {
				$statement = substr($statement, 5);
				$sql['additionalWhereClause'][] = $statement;
			}
		}
	}

	/**
	 * Adds enableFields and deletedClause to the query if necessary
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function addVisibilityConstraintStatement(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings, $tableName, array &$sql) {
		$statement = '';
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl'])) {
			$ignoreEnableFields = $querySettings->getIgnoreEnableFields();
			$enableFieldsToBeIgnored = $querySettings->getEnableFieldsToBeIgnored();
			$includeDeleted = $querySettings->getIncludeDeleted();
			if ($this->environmentService->isEnvironmentInFrontendMode()) {
				$statement .= $this->getFrontendConstraintStatement($tableName, $ignoreEnableFields, $enableFieldsToBeIgnored, $includeDeleted);
			} else {
				// TYPO3_MODE === 'BE'
				$statement .= $this->getBackendConstraintStatement($tableName, $ignoreEnableFields, $includeDeleted);
			}
			if (!empty($statement)) {
				$statement = strtolower(substr($statement, 1, 3)) === 'and' ? substr($statement, 5) : $statement;
				$sql['additionalWhereClause'][] = $statement;
			}
		}
	}

	/**
	 * Returns constraint statement for frontend context
	 *
	 * @param string $tableName
	 * @param boolean $ignoreEnableFields A flag indicating whether the enable fields should be ignored
	 * @param array $enableFieldsToBeIgnored If $ignoreEnableFields is true, this array specifies enable fields to be ignored. If it is NULL or an empty array (default) all enable fields are ignored.
	 * @param boolean $includeDeleted A flag indicating whether deleted records should be included
	 * @return string
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException
	 */
	protected function getFrontendConstraintStatement($tableName, $ignoreEnableFields, $enableFieldsToBeIgnored = array(), $includeDeleted) {
		$statement = '';
		if ($ignoreEnableFields && !$includeDeleted) {
			if (count($enableFieldsToBeIgnored)) {
				// array_combine() is necessary because of the way \TYPO3\CMS\Frontend\Page\PageRepository::enableFields() is implemented
				$statement .= $this->getPageRepository()->enableFields($tableName, -1, array_combine($enableFieldsToBeIgnored, $enableFieldsToBeIgnored));
			} else {
				$statement .= $this->getPageRepository()->deleteClause($tableName);
			}
		} elseif (!$ignoreEnableFields && !$includeDeleted) {
			$statement .= $this->getPageRepository()->enableFields($tableName);
		} elseif (!$ignoreEnableFields && $includeDeleted) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException('Query setting "ignoreEnableFields=FALSE" can not be used together with "includeDeleted=TRUE" in frontend context.', 1327678173);
		}
		return $statement;
	}

	/**
	 * Returns constraint statement for backend context
	 *
	 * @param string $tableName
	 * @param boolean $ignoreEnableFields A flag indicating whether the enable fields should be ignored
	 * @param boolean $includeDeleted A flag indicating whether deleted records should be included
	 * @return string
	 */
	protected function getBackendConstraintStatement($tableName, $ignoreEnableFields, $includeDeleted) {
		$statement = '';
		if (!$ignoreEnableFields) {
			$statement .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($tableName);
		}
		if (!$includeDeleted) {
			$statement .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName);
		}
		return $statement;
	}

	/**
	 * Builds the language field statement
	 *
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
	 * @return void
	 */
	protected function addSysLanguageStatement($tableName, array &$sql, $querySettings) {
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl'])) {
			if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])) {
				// Select all entries for the current language
				$additionalWhereClause = $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . ' IN (' . intval($querySettings->getSysLanguageUid()) . ',-1)';
				// If any language is set -> get those entries which are not translated yet
				// They will be removed by t3lib_page::getRecordOverlay if not matching overlay mode
				if (isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
					&& $querySettings->getSysLanguageUid() > 0
				) {
					$additionalWhereClause .= ' OR (' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '=0' .
						' AND ' . $tableName . '.uid NOT IN (SELECT ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] .
						' FROM ' . $tableName .
						' WHERE ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] . '>0' .
						' AND ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '>0';

					// Add delete clause to ensure all entries are loaded
					if (isset($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
						$additionalWhereClause .= ' AND ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['delete'] . '=0';
					}
					$additionalWhereClause .= '))';
				}
				$sql['additionalWhereClause'][] = '(' . $additionalWhereClause . ')';
			}
		}
	}

	/**
	 * Builds the page ID checking statement
	 *
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @param array $storagePageIds list of storage page ids
	 * @return void
	 */
	protected function addPageIdStatement($tableName, array &$sql, array $storagePageIds) {
		$tableColumns = $this->tableColumnCache->get($tableName);
		if ($tableColumns === FALSE) {
			$tableColumns = $this->databaseHandle->admin_get_fields($tableName);
			$this->tableColumnCache->set($tableName, $tableColumns);
		}
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl']) && array_key_exists('pid', $tableColumns)) {
			$rootLevel = (int)$GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'];
			if ($rootLevel) {
				if ($rootLevel === 1) {
					$sql['additionalWhereClause'][] = $tableName . '.pid = 0';
				}
			} else {
				if (empty($storagePageIds)) {
					throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException('Missing storage page ids.', 1365779762);
				}
				$sql['additionalWhereClause'][] = $tableName . '.pid IN (' . implode(', ', $storagePageIds) . ')';
			}
		}
	}

	/**
	 * Transforms orderings into SQL.
	 *
	 * @param array $orderings An array of orderings (Tx_Extbase_Persistence_QOM_Ordering)
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException
	 * @return void
	 */
	protected function parseOrderings(array $orderings, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source, array &$sql) {
		foreach ($orderings as $propertyName => $order) {
			switch ($order) {
				case \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING:

				case \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING:
					$order = 'ASC';
					break;
				case \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING:

				case \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING:
					$order = 'DESC';
					break;
				default:
					throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException('Unsupported order encountered.', 1242816074);
			}
			$className = '';
			$tableName = '';
			if ($source instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface) {
				$className = $source->getNodeTypeName();
				$tableName = $this->dataMapper->convertClassNameToTableName($className);
				while (strpos($propertyName, '.') !== FALSE) {
					$this->addUnionStatement($className, $tableName, $propertyName, $sql);
				}
			} elseif ($source instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface) {
				$tableName = $source->getLeft()->getSelectorName();
			}
			$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
			if (strlen($tableName) > 0) {
				$sql['orderings'][] = $tableName . '.' . $columnName . ' ' . $order;
			} else {
				$sql['orderings'][] = $columnName . ' ' . $order;
			}
		}
	}

	/**
	 * Transforms limit and offset into SQL
	 *
	 * @param integer $limit
	 * @param integer $offset
	 * @param array &$sql
	 * @return void
	 */
	protected function parseLimitAndOffset($limit, $offset, array &$sql) {
		if ($limit !== NULL && $offset !== NULL) {
			$sql['limit'] = intval($offset) . ', ' . intval($limit);
		} elseif ($limit !== NULL) {
			$sql['limit'] = intval($limit);
		}
	}

	/**
	 * Transforms a Resource from a database query to an array of rows.
	 *
	 * @param resource $result The result
	 * @return array The result as an array of rows (tuples)
	 */
	protected function getRowsFromResult($result) {
		$rows = array();
		while ($row = $this->databaseHandle->sql_fetch_assoc($result)) {
			if (is_array($row)) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
	 * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source The source (selector od join)
	 * @param array $rows
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
	 * @param null|integer $workspaceUid
	 * @return array
	 */
	protected function doLanguageAndWorkspaceOverlay(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source, array $rows, $querySettings, $workspaceUid = NULL) {
		if ($source instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface) {
			$tableName = $source->getSelectorName();
		} elseif ($source instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface) {
			$tableName = $source->getRight()->getSelectorName();
		}
		// If we do not have a table name here, we cannot do an overlay and return the original rows instead.
		if (isset($tableName)) {
			$pageRepository = $this->getPageRepository();
			if (is_object($GLOBALS['TSFE'])) {
				$languageMode = $GLOBALS['TSFE']->sys_language_mode;
				if ($workspaceUid !== NULL) {
					$pageRepository->versioningWorkspaceId = $workspaceUid;
				}
			} else {
				$languageMode = '';
				if ($workspaceUid === NULL) {
					$workspaceUid = $GLOBALS['BE_USER']->workspace;
				}
				$pageRepository->versioningWorkspaceId = $workspaceUid;
			}

			$overlayedRows = array();
			foreach ($rows as $row) {
				// If current row is a translation select its parent
				if (isset($tableName) && isset($GLOBALS['TCA'][$tableName])
					&& isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
					&& isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
				) {
					if (isset($row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']])
						&& $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] > 0
					) {
						$row = $this->databaseHandle->exec_SELECTgetSingleRow(
							$tableName . '.*',
							$tableName,
							$tableName . '.uid=' . (integer) $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] .
								' AND ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '=0'
						);
					}
				}
				$pageRepository->versionOL($tableName, $row, TRUE);
				if ($pageRepository->versioningPreview && isset($row['_ORIG_uid'])) {
					$row['uid'] = $row['_ORIG_uid'];
				}
				if ($tableName == 'pages') {
					$row = $pageRepository->getPageOverlay($row, $querySettings->getSysLanguageUid());
				} elseif (isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
					&& $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] !== ''
				) {
					if (in_array($row[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']], array(-1, 0))) {
						$overlayMode = $languageMode === 'strict' ? 'hideNonTranslated' : '';
						$row = $pageRepository->getRecordOverlay($tableName, $row, $querySettings->getSysLanguageUid(), $overlayMode);
					}
				}
				if ($row !== NULL && is_array($row)) {
					$overlayedRows[] = $row;
				}
			}
		} else {
			$overlayedRows = $rows;
		}
		return $overlayedRows;
	}

	/**
	 * @return \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected function getPageRepository() {
		if (!$this->pageRepository instanceof \TYPO3\CMS\Frontend\Page\PageRepository) {
			if ($this->environmentService->isEnvironmentInFrontendMode() && is_object($GLOBALS['TSFE'])) {
				$this->pageRepository = $GLOBALS['TSFE']->sys_page;
			} else {
				$this->pageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			}
		}

		return $this->pageRepository;
	}

	/**
	 * Checks if there are SQL errors in the last query, and if yes, throw an exception.
	 *
	 * @return void
	 * @param string $sql The SQL statement
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException
	 */
	protected function checkSqlErrors($sql = '') {
		$error = $this->databaseHandle->sql_error();
		if ($error !== '') {
			$error .= $sql ? ': ' . $sql : '';
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException($error, 1247602160);
		}
	}

	/**
	 * Clear the TYPO3 page cache for the given record.
	 * If the record lies on a page, then we clear the cache of this page.
	 * If the record has no PID column, we clear the cache of the current page as best-effort.
	 *
	 * Much of this functionality is taken from t3lib_tcemain::clear_cache() which unfortunately only works with logged-in BE user.
	 *
	 * @param string $tableName Table name of the record
	 * @param integer $uid UID of the record
	 * @return void
	 */
	protected function clearPageCache($tableName, $uid) {
		$frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (isset($frameworkConfiguration['persistence']['enableAutomaticCacheClearing']) && $frameworkConfiguration['persistence']['enableAutomaticCacheClearing'] === '1') {
		} else {
			// if disabled, return
			return;
		}
		$pageIdsToClear = array();
		$storagePage = NULL;
		$columns = $this->databaseHandle->admin_get_fields($tableName);
		if (array_key_exists('pid', $columns)) {
			$result = $this->databaseHandle->exec_SELECTquery('pid', $tableName, 'uid=' . intval($uid));
			if ($row = $this->databaseHandle->sql_fetch_assoc($result)) {
				$storagePage = $row['pid'];
				$pageIdsToClear[] = $storagePage;
			}
		} elseif (isset($GLOBALS['TSFE'])) {
			// No PID column - we can do a best-effort to clear the cache of the current page if in FE
			$storagePage = $GLOBALS['TSFE']->id;
			$pageIdsToClear[] = $storagePage;
		}
		if ($storagePage === NULL) {
			return;
		}
		if (!isset($this->pageTSConfigCache[$storagePage])) {
			$this->pageTSConfigCache[$storagePage] = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($storagePage);
		}
		if (isset($this->pageTSConfigCache[$storagePage]['TCEMAIN.']['clearCacheCmd'])) {
			$clearCacheCommands = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($this->pageTSConfigCache[$storagePage]['TCEMAIN.']['clearCacheCmd']), 1);
			$clearCacheCommands = array_unique($clearCacheCommands);
			foreach ($clearCacheCommands as $clearCacheCommand) {
				if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($clearCacheCommand)) {
					$pageIdsToClear[] = $clearCacheCommand;
				}
			}
		}

		foreach ($pageIdsToClear as $pageIdToClear) {
			$this->cacheService->getPageIdStack()->push($pageIdToClear);
		}
	}
}

?>