<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Storage;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/**
 * QueryParser, converting the qom to string representation
 */
class Typo3DbQueryParser implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * The TYPO3 database object
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseHandle;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 * @inject
	 */
	protected $dataMapper;

	/**
	 * The TYPO3 page repository. Used for language and workspace overlay
	 *
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected $pageRepository;

	/**
	 * @var \TYPO3\CMS\Core\Cache\CacheManager
	 * @inject
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
	 */
	protected $tableColumnCache;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
	 * @inject
	 */
	protected $environmentService;

	/**
	 * Constructor. takes the database handle from $GLOBALS['TYPO3_DB']
	 */
	public function __construct() {
		$this->databaseHandle = $GLOBALS['TYPO3_DB'];
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
	 * Preparses the query and returns the query's hash and the parameters
	 *
	 * @param QueryInterface $query The query
	 * @return array the hash and the parameters
	 */
	public function preparseQuery(QueryInterface $query) {
		list($parameters, $operators) = $this->preparseComparison($query->getConstraint());
		$hashPartials = array(
			$query->getQuerySettings(),
			$query->getSource(),
			array_keys($parameters),
			$operators,
			$query->getOrderings(),
		);
		$hash = md5(serialize($hashPartials));

		return array($hash, $parameters);
	}

	/**
	 * Walks through the qom's constraints and extracts the properties and values.
	 *
	 * In the qom the query structure and values are glued together. This walks through the
	 * qom and only extracts the parts necessary for generating the hash and filling the
	 * statement. It leaves out the actual statement generation, as it is the most time
	 * consuming.
	 *
	 * @param Qom\ConstraintInterface $comparison The constraint. Could be And-, Or-, Not- or ComparisonInterface
	 * @param string $qomPath current position of the child in the qom
	 * @return array Array of parameters and operators
	 * @throws \Exception
	 */
	protected function preparseComparison($comparison, $qomPath = '') {
		$parameters = array();
		$operators = array();
		$objectsToParse = array();

		$delimiter = '';
		if ($comparison instanceof Qom\AndInterface) {
			$delimiter = 'AND';
			$objectsToParse = array($comparison->getConstraint1(), $comparison->getConstraint2());
		} elseif ($comparison instanceof Qom\OrInterface) {
			$delimiter = 'OR';
			$objectsToParse = array($comparison->getConstraint1(), $comparison->getConstraint2());
		} elseif ($comparison instanceof Qom\NotInterface) {
			$delimiter = 'NOT';
			$objectsToParse = array($comparison->getConstraint());
		} elseif ($comparison instanceof Qom\ComparisonInterface) {
			$operand1 = $comparison->getOperand1();
			$parameterIdentifier = $this->normalizeParameterIdentifier($qomPath . $operand1->getPropertyName());
			$comparison->setParameterIdentifier($parameterIdentifier);
			$operator = $comparison->getOperator();
			$operand2 = $comparison->getOperand2();
			if ($operator === QueryInterface::OPERATOR_IN) {
				$items = array();
				foreach ($operand2 as $value) {
					$value = $this->getPlainValue($value);
					if ($value !== NULL) {
						$items[] = $value;
					}
				}
				$parameters[$parameterIdentifier] = $items;
			} else {
				$parameters[$parameterIdentifier] = $operand2;
			}
			$operators[] = $operator;
		} elseif (!is_object($comparison)) {
			$parameters = array(array(), $comparison);
			return array($parameters, $operators);
		} else {
			throw new \Exception('Can not hash Query Component "' . get_class($comparison) . '".', 1392840462);
		}

		$childObjectIterator = 0;
		foreach ($objectsToParse as $objectToParse) {
			list($preparsedParameters, $preparsedOperators) = $this->preparseComparison($objectToParse, $qomPath . $delimiter . $childObjectIterator++);
			if (!empty($preparsedParameters)) {
				$parameters = array_merge($parameters, $preparsedParameters);
			}
			if (!empty($preparsedOperators)) {
				$operators = array_merge($operators, $preparsedOperators);
			}
		}

		return array($parameters, $operators);
	}

	/**
	 * normalizes the parameter's identifier
	 *
	 * @param string $identifier
	 * @return string
	 * @todo come on, clean up that method!
	 */
	public function normalizeParameterIdentifier($identifier) {
		return ':' . preg_replace('/[^A-Za-z0-9]/', '', $identifier);
	}

	/**
	 * Parses the query and returns the SQL statement parts.
	 *
	 * @param QueryInterface $query The query
	 * @return array The SQL statement parts
	 */
	public function parseQuery(QueryInterface $query) {
		$sql = array();
		$sql['keywords'] = array();
		$sql['tables'] = array();
		$sql['unions'] = array();
		$sql['fields'] = array();
		$sql['where'] = array();
		$sql['additionalWhereClause'] = array();
		$sql['orderings'] = array();
		$sql['limit'] = ((int)$query->getLimit() ?: NULL);
		$sql['offset'] = ((int)$query->getOffset() ?: NULL);
		$source = $query->getSource();
		$this->parseSource($source, $sql);
		$this->parseConstraint($query->getConstraint(), $source, $sql);
		$this->parseOrderings($query->getOrderings(), $source, $sql);

		$tableNames = array_unique(array_keys($sql['tables'] + $sql['unions']));
		foreach ($tableNames as $tableName) {
			if (is_string($tableName) && !empty($tableName)) {
				$this->addAdditionalWhereClause($query->getQuerySettings(), $tableName, $sql);
			}
		}

		return $sql;
	}

	/**
	 * Add query parts that MUST NOT be cached.
	 * Call this function for any query
	 *
	 * @param QuerySettingsInterface $querySettings
	 * @param array $sql
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public function addDynamicQueryParts(QuerySettingsInterface $querySettings, array &$sql) {
		if (!isset($sql['additionalWhereClause'])) {
			throw new \InvalidArgumentException('Invalid statement given.', 1399512421);
		}
		$tableNames = array_unique(array_keys($sql['tables'] + $sql['unions']));
		foreach ($tableNames as $tableName) {
			if (is_string($tableName) && !empty($tableName)) {
				$this->addVisibilityConstraintStatement($querySettings, $tableName, $sql);
			}
		}
	}

	/**
	 * Transforms a Query Source into SQL and parameter arrays
	 *
	 * @param Qom\SourceInterface $source The source
	 * @param array &$sql
	 * @return void
	 */
	protected function parseSource(Qom\SourceInterface $source, array &$sql) {
		if ($source instanceof Qom\SelectorInterface) {
			$className = $source->getNodeTypeName();
			$tableName = $this->dataMapper->getDataMap($className)->getTableName();
			$this->addRecordTypeConstraint($className, $sql);
			$sql['fields'][$tableName] = $tableName . '.*';
			$sql['tables'][$tableName] = $tableName;
		} elseif ($source instanceof Qom\JoinInterface) {
			$this->parseJoin($source, $sql);
		}
	}

	/**
	 * Transforms a constraint into SQL and parameter arrays
	 *
	 * @param Qom\ConstraintInterface $constraint The constraint
	 * @param Qom\SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function parseConstraint(Qom\ConstraintInterface $constraint = NULL, Qom\SourceInterface $source, array &$sql) {
		if ($constraint instanceof Qom\AndInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $source, $sql);
			$sql['where'][] = ' AND ';
			$this->parseConstraint($constraint->getConstraint2(), $source, $sql);
			$sql['where'][] = ')';
		} elseif ($constraint instanceof Qom\OrInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $source, $sql);
			$sql['where'][] = ' OR ';
			$this->parseConstraint($constraint->getConstraint2(), $source, $sql);
			$sql['where'][] = ')';
		} elseif ($constraint instanceof Qom\NotInterface) {
			$sql['where'][] = 'NOT (';
			$this->parseConstraint($constraint->getConstraint(), $source, $sql);
			$sql['where'][] = ')';
		} elseif ($constraint instanceof Qom\ComparisonInterface) {
			$this->parseComparison($constraint, $source, $sql);
		}
	}

	/**
	 * Transforms orderings into SQL.
	 *
	 * @param array $orderings An array of orderings (Qom\Ordering)
	 * @param Qom\SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException
	 * @return void
	 */
	protected function parseOrderings(array $orderings, Qom\SourceInterface $source, array &$sql) {
		foreach ($orderings as $propertyName => $order) {
			switch ($order) {
				case Qom\QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING:

				case QueryInterface::ORDER_ASCENDING:
					$order = 'ASC';
					break;
				case Qom\QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING:

				case QueryInterface::ORDER_DESCENDING:
					$order = 'DESC';
					break;
				default:
					throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException('Unsupported order encountered.', 1242816074);
			}
			$className = '';
			$tableName = '';
			if ($source instanceof Qom\SelectorInterface) {
				$className = $source->getNodeTypeName();
				$tableName = $this->dataMapper->convertClassNameToTableName($className);
				while (strpos($propertyName, '.') !== FALSE) {
					$this->addUnionStatement($className, $tableName, $propertyName, $sql);
				}
			} elseif ($source instanceof Qom\JoinInterface) {
				$tableName = $source->getLeft()->getSelectorName();
			}
			$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
			if ($tableName !== '') {
				$sql['orderings'][] = $tableName . '.' . $columnName . ' ' . $order;
			} else {
				$sql['orderings'][] = $columnName . ' ' . $order;
			}
		}
	}

	/**
	 * Parse a Comparison into SQL and parameter arrays.
	 *
	 * @param Qom\ComparisonInterface $comparison The comparison to parse
	 * @param Qom\SourceInterface $source The source
	 * @param array &$sql SQL query parts to add to
	 * @throws \RuntimeException
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException
	 * @return void
	 */
	protected function parseComparison(Qom\ComparisonInterface $comparison, Qom\SourceInterface $source, array &$sql) {
		$parameterIdentifier = $this->normalizeParameterIdentifier($comparison->getParameterIdentifier());

		$operator = $comparison->getOperator();
		$operand2 = $comparison->getOperand2();
		if ($operator === QueryInterface::OPERATOR_IN) {
			$hasValue = FALSE;
			foreach ($operand2 as $value) {
				$value = $this->getPlainValue($value);
				if ($value !== NULL) {
					$parameters[] = $value;
					$hasValue = TRUE;
				}
			}
			if ($hasValue === FALSE) {
				$sql['where'][] = '1<>1';
			} else {
				$this->parseDynamicOperand($comparison, $source, $sql);
			}
		} elseif ($operator === QueryInterface::OPERATOR_CONTAINS) {
			if ($operand2 === NULL) {
				$sql['where'][] = '1<>1';
			} else {
				if (!$source instanceof Qom\SelectorInterface) {
					throw new \RuntimeException('Source is not of type "SelectorInterface"', 1395362539);
				}
				$className = $source->getNodeTypeName();
				$tableName = $this->dataMapper->convertClassNameToTableName($className);
				$operand1 = $comparison->getOperand1();
				$propertyName = $operand1->getPropertyName();
				while (strpos($propertyName, '.') !== FALSE) {
					$this->addUnionStatement($className, $tableName, $propertyName, $sql);
				}
				$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
				$dataMap = $this->dataMapper->getDataMap($className);
				$columnMap = $dataMap->getColumnMap($propertyName);
				$typeOfRelation = $columnMap instanceof ColumnMap ? $columnMap->getTypeOfRelation() : NULL;
				if ($typeOfRelation === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
					$relationTableName = $columnMap->getRelationTableName();
					$relationTableMatchFields = $columnMap->getRelationTableMatchFields();
					if (is_array($relationTableMatchFields)) {
						$additionalWhere = array();
						foreach ($relationTableMatchFields as $fieldName => $value) {
							$additionalWhere[] = $fieldName . ' = ' . $this->databaseHandle->fullQuoteStr($value, $relationTableName);
						}
						$additionalWhereForMatchFields = ' AND ' . implode(' AND ', $additionalWhere);
					} else {
						$additionalWhereForMatchFields = '';
					}
					$sql['where'][] = $tableName . '.uid IN (SELECT ' . $columnMap->getParentKeyFieldName() . ' FROM ' . $relationTableName . ' WHERE ' . $columnMap->getChildKeyFieldName() . '=' . $parameterIdentifier . $additionalWhereForMatchFields . ')';
				} elseif ($typeOfRelation === ColumnMap::RELATION_HAS_MANY) {
					$parentKeyFieldName = $columnMap->getParentKeyFieldName();
					if (isset($parentKeyFieldName)) {
						$childTableName = $columnMap->getChildTableName();
						$sql['where'][] = $tableName . '.uid=(SELECT ' . $childTableName . '.' . $parentKeyFieldName . ' FROM ' . $childTableName . ' WHERE ' . $childTableName . '.uid=' . $parameterIdentifier . ')';
					} else {
						$sql['where'][] = 'FIND_IN_SET(' . $parameterIdentifier . ', ' . $tableName . '.' . $columnName . ')';
					}
				} else {
					throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException('Unsupported or non-existing property name "' . $propertyName . '" used in relation matching.', 1327065745);
				}
			}
		} else {
			$this->parseDynamicOperand($comparison, $source, $sql);
		}
	}

	/**
	 * Parse a DynamicOperand into SQL and parameter arrays.
	 *
	 * @param Qom\ComparisonInterface $comparison
	 * @param Qom\SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function parseDynamicOperand(Qom\ComparisonInterface $comparison, Qom\SourceInterface $source, array &$sql) {
		$operator = $this->resolveOperator($comparison->getOperator());
		$operand = $comparison->getOperand1();

		$constraintSQL = $this->parseOperand($operand, $source, $sql) . ' ' . $operator . ' ';

		$parameterIdentifier = $this->normalizeParameterIdentifier($comparison->getParameterIdentifier());
		if ($operator === 'IN') {
			$parameterIdentifier = '(' . $parameterIdentifier . ')';
		}
		$constraintSQL .= $parameterIdentifier;

		$sql['where'][] = $constraintSQL;
	}

	/**
	 * @param Qom\DynamicOperandInterface $operand
	 * @param Qom\SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function parseOperand(Qom\DynamicOperandInterface $operand, Qom\SourceInterface $source, array &$sql) {
		if ($operand instanceof Qom\LowerCaseInterface) {
			$constraintSQL = 'LOWER(' . $this->parseOperand($operand->getOperand(), $source, $sql) . ')';
		} elseif ($operand instanceof Qom\UpperCaseInterface) {
			$constraintSQL = 'UPPER(' . $this->parseOperand($operand->getOperand(), $source, $sql) . ')';
		} elseif ($operand instanceof Qom\PropertyValueInterface) {
			$propertyName = $operand->getPropertyName();
			$className = '';
			if ($source instanceof Qom\SelectorInterface) {
				// FIXME Only necessary to differ from  Join
				$className = $source->getNodeTypeName();
				$tableName = $this->dataMapper->convertClassNameToTableName($className);
				while (strpos($propertyName, '.') !== FALSE) {
					$this->addUnionStatement($className, $tableName, $propertyName, $sql);
				}
			} elseif ($source instanceof Qom\JoinInterface) {
				$tableName = $source->getJoinCondition()->getSelector1Name();
			}
			$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
			$constraintSQL = (!empty($tableName) ? $tableName . '.' : '') . $columnName;
		} else {
			throw new \InvalidArgumentException('Given operand has invalid type "' . get_class($operand) . '".', 1395710211);
		}
		return $constraintSQL;
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
				if (!empty($recordTypes)) {
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
	 * Adds additional WHERE statements according to the query settings.
	 *
	 * @param QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
	 * @param string $tableName The table name to add the additional where clause for
	 * @param string &$sql
	 * @return void
	 */
	protected function addAdditionalWhereClause(QuerySettingsInterface $querySettings, $tableName, &$sql) {
		if ($querySettings->getRespectSysLanguage()) {
			$this->addSysLanguageStatement($tableName, $sql, $querySettings);
		}
		if ($querySettings->getRespectStoragePage()) {
			$this->addPageIdStatement($tableName, $sql, $querySettings->getStoragePageIds());
		}
	}

	/**
	 * Adds enableFields and deletedClause to the query if necessary
	 *
	 * @param QuerySettingsInterface $querySettings
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function addVisibilityConstraintStatement(QuerySettingsInterface $querySettings, $tableName, array &$sql) {
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
	 * @param bool $ignoreEnableFields A flag indicating whether the enable fields should be ignored
	 * @param array $enableFieldsToBeIgnored If $ignoreEnableFields is true, this array specifies enable fields to be ignored. If it is NULL or an empty array (default) all enable fields are ignored.
	 * @param bool $includeDeleted A flag indicating whether deleted records should be included
	 * @return string
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException
	 */
	protected function getFrontendConstraintStatement($tableName, $ignoreEnableFields, array $enableFieldsToBeIgnored = array(), $includeDeleted) {
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
	 * @param bool $ignoreEnableFields A flag indicating whether the enable fields should be ignored
	 * @param bool $includeDeleted A flag indicating whether deleted records should be included
	 * @return string
	 */
	protected function getBackendConstraintStatement($tableName, $ignoreEnableFields, $includeDeleted) {
		$statement = '';
		if (!$ignoreEnableFields) {
			$statement .= BackendUtility::BEenableFields($tableName);
		}
		if (!$includeDeleted) {
			$statement .= BackendUtility::deleteClause($tableName);
		}
		return $statement;
	}

	/**
	 * Builds the language field statement
	 *
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @param QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
	 * @return void
	 */
	protected function addSysLanguageStatement($tableName, array &$sql, $querySettings) {
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl'])) {
			if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])) {
				// Select all entries for the current language
				$additionalWhereClause = $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . ' IN (' . (int)$querySettings->getLanguageUid() . ',-1)';
				// If any language is set -> get those entries which are not translated yet
				// They will be removed by t3lib_page::getRecordOverlay if not matching overlay mode
				if (isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
					&& $querySettings->getLanguageUid() > 0
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
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException
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
	 * Transforms a Join into SQL and parameter arrays
	 *
	 * @param Qom\JoinInterface $join The join
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function parseJoin(Qom\JoinInterface $join, array &$sql) {
		$leftSource = $join->getLeft();
		$leftClassName = $leftSource->getNodeTypeName();
		$leftTableName = $leftSource->getSelectorName();
		$this->addRecordTypeConstraint($leftClassName, $sql);
		$rightSource = $join->getRight();
		if ($rightSource instanceof Qom\JoinInterface) {
			$left = $rightSource->getLeft();
			$rightClassName = $left->getNodeTypeName();
			$rightTableName = $left->getSelectorName();
		} else {
			$rightClassName = $rightSource->getNodeTypeName();
			$rightTableName = $rightSource->getSelectorName();
			$sql['fields'][$leftTableName] = $rightTableName . '.*';
		}
		$this->addRecordTypeConstraint($rightClassName, $sql);
		$sql['tables'][$leftTableName] = $leftTableName;
		$sql['unions'][$rightTableName] = 'LEFT JOIN ' . $rightTableName;
		$joinCondition = $join->getJoinCondition();
		if ($joinCondition instanceof Qom\EquiJoinCondition) {
			$column1Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty1Name(), $leftClassName);
			$column2Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty2Name(), $rightClassName);
			$sql['unions'][$rightTableName] .= ' ON ' . $joinCondition->getSelector1Name() . '.' . $column1Name . ' = ' . $joinCondition->getSelector2Name() . '.' . $column2Name;
		}
		if ($rightSource instanceof Qom\JoinInterface) {
			$this->parseJoin($rightSource, $sql);
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
		} elseif (TypeHandlingUtility::isCoreType($input)) {
			return (string) $input;
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
	 * adds a union statement to the query, mostly for tables referenced in the where condition.
	 *
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

		if ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_ONE) {
			if (isset($parentKeyFieldName)) {
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $tableName . '.uid=' . $childTableName . '.' . $parentKeyFieldName;
			} else {
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $tableName . '.' . $columnName . '=' . $childTableName . '.uid';
			}
			$className = $this->dataMapper->getType($className, $propertyName);
		} elseif ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_MANY) {
			if (isset($parentKeyFieldName)) {
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $tableName . '.uid=' . $childTableName . '.' . $parentKeyFieldName;
			} else {
				$onStatement = '(FIND_IN_SET(' . $childTableName . '.uid, ' . $tableName . '.' . $columnName . '))';
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $onStatement;
			}
			$className = $this->dataMapper->getType($className, $propertyName);
		} elseif ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
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
			case QueryInterface::OPERATOR_IN:
				$operator = 'IN';
				break;
			case QueryInterface::OPERATOR_EQUAL_TO:
				$operator = '=';
				break;
			case QueryInterface::OPERATOR_EQUAL_TO_NULL:
				$operator = 'IS';
				break;
			case QueryInterface::OPERATOR_NOT_EQUAL_TO:
				$operator = '!=';
				break;
			case QueryInterface::OPERATOR_NOT_EQUAL_TO_NULL:
				$operator = 'IS NOT';
				break;
			case QueryInterface::OPERATOR_LESS_THAN:
				$operator = '<';
				break;
			case QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO:
				$operator = '<=';
				break;
			case QueryInterface::OPERATOR_GREATER_THAN:
				$operator = '>';
				break;
			case QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO:
				$operator = '>=';
				break;
			case QueryInterface::OPERATOR_LIKE:
				$operator = 'LIKE';
				break;
			default:
				throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception('Unsupported operator encountered.', 1242816073);
		}
		return $operator;
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
}
