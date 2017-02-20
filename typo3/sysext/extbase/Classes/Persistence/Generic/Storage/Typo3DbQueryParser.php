<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Storage;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * QueryParser, converting the qom to string representation
 */
class Typo3DbQueryParser implements \TYPO3\CMS\Core\SingletonInterface
{
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
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
     */
    public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * Maps domain model properties to their corresponding table aliases that are used in the query, e.g.:
     *
     * 'property1' => 'tableName',
     * 'property1.property2' => 'tableName1',
     *
     * @var array
     */
    protected $tablePropertyMap = [];

    /**
     * Constructor. takes the database handle from $GLOBALS['TYPO3_DB']
     */
    public function __construct()
    {
        $this->databaseHandle = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Preparses the query and returns the query's hash and the parameters
     *
     * @param QueryInterface $query The query
     * @return array the hash and the parameters
     */
    public function preparseQuery(QueryInterface $query)
    {
        list($parameters, $operators) = $this->preparseComparison($query->getConstraint());
        $hashPartials = [
            $query->getQuerySettings(),
            $query->getSource(),
            array_keys($parameters),
            $operators,
            $query->getOrderings(),
        ];
        $hash = md5(serialize($hashPartials));

        return [$hash, $parameters];
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
    protected function preparseComparison($comparison, $qomPath = '')
    {
        $parameters = [];
        $operators = [];
        $objectsToParse = [];

        $delimiter = '';
        if ($comparison instanceof Qom\AndInterface) {
            $delimiter = 'AND';
            $objectsToParse = [$comparison->getConstraint1(), $comparison->getConstraint2()];
        } elseif ($comparison instanceof Qom\OrInterface) {
            $delimiter = 'OR';
            $objectsToParse = [$comparison->getConstraint1(), $comparison->getConstraint2()];
        } elseif ($comparison instanceof Qom\NotInterface) {
            $delimiter = 'NOT';
            $objectsToParse = [$comparison->getConstraint()];
        } elseif ($comparison instanceof Qom\ComparisonInterface) {
            $operand1 = $comparison->getOperand1();
            $parameterIdentifier = $this->normalizeParameterIdentifier($qomPath . $operand1->getPropertyName());
            $comparison->setParameterIdentifier($parameterIdentifier);
            $operator = $comparison->getOperator();
            $operand2 = $comparison->getOperand2();
            if ($operator === QueryInterface::OPERATOR_IN) {
                $items = [];
                foreach ($operand2 as $value) {
                    $value = $this->dataMapper->getPlainValue($value);
                    if ($value !== null) {
                        $items[] = $value;
                    }
                }
                $parameters[$parameterIdentifier] = $items;
            } else {
                $parameters[$parameterIdentifier] = $operand2;
            }
            $operators[] = $operator;
        } elseif (!is_object($comparison)) {
            $parameters = [[], $comparison];
            return [$parameters, $operators];
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

        return [$parameters, $operators];
    }

    /**
     * normalizes the parameter's identifier
     *
     * @param string $identifier
     * @return string
     * @todo come on, clean up that method!
     */
    public function normalizeParameterIdentifier($identifier)
    {
        return ':' . preg_replace('/[^A-Za-z0-9]/', '', $identifier);
    }

    /**
     * Parses the query and returns the SQL statement parts.
     *
     * @param QueryInterface $query The query
     * @return array The SQL statement parts
     */
    public function parseQuery(QueryInterface $query)
    {
        $this->tablePropertyMap = [];
        $sql = [];
        $sql['keywords'] = [];
        $sql['tables'] = [];
        $sql['unions'] = [];
        $sql['fields'] = [];
        $sql['where'] = [];
        $sql['additionalWhereClause'] = [];
        $sql['orderings'] = [];
        $sql['limit'] = ((int)$query->getLimit() ?: null);
        $sql['offset'] = ((int)$query->getOffset() ?: null);
        $sql['tableAliasMap'] = [];
        $source = $query->getSource();
        $this->parseSource($source, $sql);
        $this->parseConstraint($query->getConstraint(), $source, $sql);
        $this->parseOrderings($query->getOrderings(), $source, $sql);

        foreach ($sql['tableAliasMap'] as $tableAlias => $tableName) {
            $additionalWhereClause = $this->getAdditionalWhereClause($query->getQuerySettings(), $tableName, $tableAlias);
            if ($additionalWhereClause !== '') {
                $additionalWhereClause = $this->addNullConditionToStatementIfRequired($sql, $additionalWhereClause, $tableAlias);
                $sql['additionalWhereClause'][] = $additionalWhereClause;
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
    public function addDynamicQueryParts(QuerySettingsInterface $querySettings, array &$sql)
    {
        if (!isset($sql['additionalWhereClause'])) {
            throw new \InvalidArgumentException('Invalid statement given.', 1399512421);
        }
        foreach ($sql['tableAliasMap'] as $tableAlias => $tableName) {
            $statement = $this->getVisibilityConstraintStatement($querySettings, $tableName, $tableAlias);
            if ($statement !== '') {
                $statement = $this->addNullConditionToStatementIfRequired($sql, $statement, $tableAlias);
                $sql['additionalWhereClause'][] = $statement;
            }
        }
    }

    /**
     * If the given table alias is used in a UNION statement it is required to
     * add an additional condition that allows the fields of the joined table
     * to be NULL. Otherwise the condition would be too strict and filter out
     * records that are actually valid.
     *
     * @param array $sql The current SQL query parts.
     * @param string $statement The SQL statement to which the NULL condition should be added.
     * @param string $tableAlias The table alias used in the SQL statement.
     * @return string The statement including the NULL condition or the original statement.
     */
    protected function addNullConditionToStatementIfRequired(array $sql, $statement, $tableAlias)
    {
        if (isset($sql['unions'][$tableAlias])) {
            $statement = '((' . $statement . ') OR ' . $tableAlias . '.uid' . ' IS NULL)';
        }

        return $statement;
    }

    /**
     * Transforms a Query Source into SQL and parameter arrays
     *
     * @param Qom\SourceInterface $source The source
     * @param array &$sql
     * @return void
     */
    protected function parseSource(Qom\SourceInterface $source, array &$sql)
    {
        if ($source instanceof Qom\SelectorInterface) {
            $className = $source->getNodeTypeName();
            $tableName = $this->dataMapper->getDataMap($className)->getTableName();
            $this->addRecordTypeConstraint($className, $sql);
            $tableName = $this->getUniqueAlias($sql, $tableName);
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
    protected function parseConstraint(Qom\ConstraintInterface $constraint = null, Qom\SourceInterface $source, array &$sql)
    {
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
    protected function parseOrderings(array $orderings, Qom\SourceInterface $source, array &$sql)
    {
        foreach ($orderings as $propertyName => $order) {
            switch ($order) {
                case QueryInterface::ORDER_ASCENDING:
                    $order = 'ASC';
                    break;
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
                $fullPropertyPath = '';
                while (strpos($propertyName, '.') !== false) {
                    $this->addUnionStatement($className, $tableName, $propertyName, $sql, $fullPropertyPath);
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
    protected function parseComparison(Qom\ComparisonInterface $comparison, Qom\SourceInterface $source, array &$sql)
    {
        $parameterIdentifier = $this->normalizeParameterIdentifier($comparison->getParameterIdentifier());

        $operator = $comparison->getOperator();
        $operand2 = $comparison->getOperand2();
        if ($operator === QueryInterface::OPERATOR_IN) {
            $hasValue = false;
            foreach ($operand2 as $value) {
                $value = $this->dataMapper->getPlainValue($value);
                if ($value !== null) {
                    $parameters[] = $value;
                    $hasValue = true;
                }
            }
            if ($hasValue === false) {
                $sql['where'][] = '1<>1';
            } else {
                $this->parseDynamicOperand($comparison, $source, $sql);
            }
        } elseif ($operator === QueryInterface::OPERATOR_CONTAINS) {
            if ($operand2 === null) {
                $sql['where'][] = '1<>1';
            } else {
                if (!$source instanceof Qom\SelectorInterface) {
                    throw new \RuntimeException('Source is not of type "SelectorInterface"', 1395362539);
                }
                $className = $source->getNodeTypeName();
                $tableName = $this->dataMapper->convertClassNameToTableName($className);
                $operand1 = $comparison->getOperand1();
                $propertyName = $operand1->getPropertyName();
                $fullPropertyPath = '';
                while (strpos($propertyName, '.') !== false) {
                    $this->addUnionStatement($className, $tableName, $propertyName, $sql, $fullPropertyPath);
                }
                $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
                $dataMap = $this->dataMapper->getDataMap($className);
                $columnMap = $dataMap->getColumnMap($propertyName);
                $typeOfRelation = $columnMap instanceof ColumnMap ? $columnMap->getTypeOfRelation() : null;
                if ($typeOfRelation === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
                    $relationTableName = $columnMap->getRelationTableName();
                    $additionalWhereForMatchFields = $this->getAdditionalMatchFieldsStatement($columnMap, $relationTableName, $relationTableName);
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
    protected function parseDynamicOperand(Qom\ComparisonInterface $comparison, Qom\SourceInterface $source, array &$sql)
    {
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
    protected function parseOperand(Qom\DynamicOperandInterface $operand, Qom\SourceInterface $source, array &$sql)
    {
        if ($operand instanceof Qom\LowerCaseInterface) {
            $constraintSQL = 'LOWER(' . $this->parseOperand($operand->getOperand(), $source, $sql) . ')';
        } elseif ($operand instanceof Qom\UpperCaseInterface) {
            $constraintSQL = 'UPPER(' . $this->parseOperand($operand->getOperand(), $source, $sql) . ')';
        } elseif ($operand instanceof Qom\PropertyValueInterface) {
            $propertyName = $operand->getPropertyName();
            $className = '';
            if ($source instanceof Qom\SelectorInterface) {
                // @todo Only necessary to differ from  Join
                $className = $source->getNodeTypeName();
                $tableName = $this->dataMapper->convertClassNameToTableName($className);
                $fullPropertyPath = '';
                while (strpos($propertyName, '.') !== false) {
                    $this->addUnionStatement($className, $tableName, $propertyName, $sql, $fullPropertyPath);
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
    protected function addRecordTypeConstraint($className, &$sql)
    {
        if ($className !== null) {
            $dataMap = $this->dataMapper->getDataMap($className);
            if ($dataMap->getRecordTypeColumnName() !== null) {
                $recordTypes = [];
                if ($dataMap->getRecordType() !== null) {
                    $recordTypes[] = $dataMap->getRecordType();
                }
                foreach ($dataMap->getSubclasses() as $subclassName) {
                    $subclassDataMap = $this->dataMapper->getDataMap($subclassName);
                    if ($subclassDataMap->getRecordType() !== null) {
                        $recordTypes[] = $subclassDataMap->getRecordType();
                    }
                }
                if (!empty($recordTypes)) {
                    $recordTypeStatements = [];
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
     * Builds a condition for filtering records by the configured match field,
     * e.g. MM_match_fields, foreign_match_fields or foreign_table_field.
     *
     * @param ColumnMap $columnMap The column man for which the condition should be build.
     * @param string $childTableName The real name of the child record table.
     * @param string $childTableAlias The alias of the child record table used in the query.
     * @param string $parentTable The real name of the parent table (used for building the foreign_table_field condition).
     * @return string The match field conditions or an empty string.
     */
    protected function getAdditionalMatchFieldsStatement($columnMap, $childTableName, $childTableAlias, $parentTable = null)
    {
        $additionalWhereForMatchFields = '';

        $relationTableMatchFields = $columnMap->getRelationTableMatchFields();
        if (is_array($relationTableMatchFields) && !empty($relationTableMatchFields)) {
            $additionalWhere = [];
            foreach ($relationTableMatchFields as $fieldName => $value) {
                $additionalWhere[] = $childTableAlias . '.' . $fieldName . ' = ' . $this->databaseHandle->fullQuoteStr($value, $childTableName);
            }
            $additionalWhereForMatchFields .= ' AND ' . implode(' AND ', $additionalWhere);
        }

        if (isset($parentTable)) {
            $parentTableFieldName = $columnMap->getParentTableFieldName();
            if (isset($parentTableFieldName) && $parentTableFieldName !== '') {
                $additionalWhereForMatchFields .= ' AND ' . $childTableAlias . '.' . $parentTableFieldName . ' = ' . $this->databaseHandle->fullQuoteStr($parentTable, $childTableAlias);
            }
        }

        return $additionalWhereForMatchFields;
    }

    /**
     * Adds additional WHERE statements according to the query settings.
     *
     * @param QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
     * @param string $tableName The table name to add the additional where clause for
     * @param string $tableAlias The table alias used in the query.
     * @return string
     */
    protected function getAdditionalWhereClause(QuerySettingsInterface $querySettings, $tableName, $tableAlias = null)
    {
        $sysLanguageStatement = '';
        if ($querySettings->getRespectSysLanguage()) {
            $sysLanguageStatement = $this->getSysLanguageStatement($tableName, $tableAlias, $querySettings);
        }

        $pageIdStatement = '';
        if ($querySettings->getRespectStoragePage()) {
            $pageIdStatement = $this->getPageIdStatement($tableName, $tableAlias, $querySettings->getStoragePageIds());
        }

        if ($sysLanguageStatement !== '' && $pageIdStatement !== '') {
            $whereClause = $sysLanguageStatement . ' AND ' . $pageIdStatement;
        } elseif ($sysLanguageStatement !== '') {
            $whereClause = $sysLanguageStatement;
        } else {
            $whereClause = $pageIdStatement;
        }

        return $whereClause;
    }

    /**
     * Adds enableFields and deletedClause to the query if necessary
     *
     * @param QuerySettingsInterface $querySettings
     * @param string $tableName The database table name
     * @param string $tableAlias
     * @return string
     */
    protected function getVisibilityConstraintStatement(QuerySettingsInterface $querySettings, $tableName, $tableAlias)
    {
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
                $statement = $this->replaceTableNameWithAlias($statement, $tableName, $tableAlias);
                $statement = strtolower(substr($statement, 1, 3)) === 'and' ? substr($statement, 5) : $statement;
            }
        }
        return $statement;
    }

    /**
     * Returns constraint statement for frontend context
     *
     * @param string $tableName
     * @param bool $ignoreEnableFields A flag indicating whether the enable fields should be ignored
     * @param array $enableFieldsToBeIgnored If $ignoreEnableFields is true, this array specifies enable fields to be ignored. If it is NULL or an empty array (default) all enable fields are ignored.
     * @param bool $includeDeleted A flag indicating whether deleted records should be included
     * @return string
     * @throws InconsistentQuerySettingsException
     */
    protected function getFrontendConstraintStatement($tableName, $ignoreEnableFields, array $enableFieldsToBeIgnored = [], $includeDeleted)
    {
        $statement = '';
        if ($ignoreEnableFields && !$includeDeleted) {
            if (!empty($enableFieldsToBeIgnored)) {
                // array_combine() is necessary because of the way \TYPO3\CMS\Frontend\Page\PageRepository::enableFields() is implemented
                $statement .= $this->getPageRepository()->enableFields($tableName, -1, array_combine($enableFieldsToBeIgnored, $enableFieldsToBeIgnored));
            } else {
                $statement .= $this->getPageRepository()->deleteClause($tableName);
            }
        } elseif (!$ignoreEnableFields && !$includeDeleted) {
            $statement .= $this->getPageRepository()->enableFields($tableName);
        } elseif (!$ignoreEnableFields && $includeDeleted) {
            throw new InconsistentQuerySettingsException('Query setting "ignoreEnableFields=FALSE" can not be used together with "includeDeleted=TRUE" in frontend context.', 1327678173);
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
    protected function getBackendConstraintStatement($tableName, $ignoreEnableFields, $includeDeleted)
    {
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
     * @param string $tableAlias The table alias used in the query.
     * @param QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
     * @return string
     */
    protected function getSysLanguageStatement($tableName, $tableAlias, $querySettings)
    {
        $sysLanguageStatement = '';
        if (is_array($GLOBALS['TCA'][$tableName]['ctrl'])) {
            if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])) {
                // Select all entries for the current language
                $additionalWhereClause = $tableAlias . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . ' IN (' . (int)$querySettings->getLanguageUid() . ',-1)';
                // If any language is set -> get those entries which are not translated yet
                // They will be removed by \TYPO3\CMS\Frontend\Page\PageRepository::getRecordOverlay if not matching overlay mode
                if (isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
                    && $querySettings->getLanguageUid() > 0
                ) {
                    $mode = $querySettings->getLanguageMode();
                    if ($mode === 'strict') {
                        $additionalWhereClause = $tableAlias . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '=-1' .
                            ' OR (' . $tableAlias . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . ' = ' . (int)$querySettings->getLanguageUid() .
                            ' AND ' . $tableAlias . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] . '=0' .
                            ') OR (' . $tableAlias . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '=0' .
                            ' AND ' . $tableAlias . '.uid IN (SELECT ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] .
                            ' FROM ' . $tableName .
                            ' WHERE ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] . '>0' .
                            ' AND ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '=' . (int)$querySettings->getLanguageUid();
                    } else {
                        $additionalWhereClause .= ' OR (' . $tableAlias . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '=0' .
                            ' AND ' . $tableAlias . '.uid NOT IN (SELECT ' . $tableAlias . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] .
                            ' FROM ' . $tableName .
                            ' WHERE ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] . '>0' .
                            ' AND ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '=' . (int)$querySettings->getLanguageUid();
                    }

                    // Add delete clause to ensure all entries are loaded
                    if (isset($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
                        $additionalWhereClause .= ' AND ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['delete'] . '=0';
                    }
                    $additionalWhereClause .= '))';
                }
                $sysLanguageStatement = '(' . $additionalWhereClause . ')';
            }
        }
        return $sysLanguageStatement;
    }

    /**
     * Builds the page ID checking statement
     *
     * @param string $tableName The database table name
     * @param string $tableAlias The table alias used in the query.
     * @param array $storagePageIds list of storage page ids
     * @throws InconsistentQuerySettingsException
     * @return string
     */
    protected function getPageIdStatement($tableName, $tableAlias, array $storagePageIds)
    {
        if (!isset($GLOBALS['TCA'][$tableName]['ctrl'])) {
            return '';
        }

        $rootLevel = (int)$GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'];
        switch ($rootLevel) {
            // Only in pid 0
            case 1:
                return $tableAlias . '.pid = 0';
            // Pid 0 and pagetree
            case -1:
                if (empty($storagePageIds)) {
                    return $tableAlias . '.pid = 0';
                }
                $storagePageIds[] = 0;
                break;
            // Only pagetree or not set
            case 0:
                if (empty($storagePageIds)) {
                    throw new InconsistentQuerySettingsException('Missing storage page ids.', 1365779762);
                }
                break;
            // Invalid configuration
            default:
                return '';
        }
        return $tableAlias . '.pid IN (' . implode(',', $this->databaseHandle->cleanIntArray($storagePageIds)) . ')';
    }

    /**
     * Transforms a Join into SQL and parameter arrays
     *
     * @param Qom\JoinInterface $join The join
     * @param array &$sql The query parts
     * @return void
     */
    protected function parseJoin(Qom\JoinInterface $join, array &$sql)
    {
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
        $leftTableName = $this->getUniqueAlias($sql, $leftTableName);
        $sql['tables'][$leftTableName] = $leftTableName;
        $rightTableName = $this->getUniqueAlias($sql, $rightTableName);
        $sql['unions'][$rightTableName] = 'LEFT JOIN ' . $rightTableName;
        $joinCondition = $join->getJoinCondition();
        if ($joinCondition instanceof Qom\EquiJoinCondition) {
            $column1Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty1Name(), $leftClassName);
            $column2Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty2Name(), $rightClassName);
            $sql['unions'][$rightTableName] .= ' ON ' . $leftTableName . '.' . $column1Name . ' = ' . $rightTableName . '.' . $column2Name;
        }
        if ($rightSource instanceof Qom\JoinInterface) {
            $this->parseJoin($rightSource, $sql);
        }
    }

    /**
     * Generates a unique alias for the given table and the given property path.
     * The property path will be mapped to the generated alias in the tablePropertyMap.
     *
     * @param array $sql The SQL satement parts, will be filled with the tableAliasMap.
     * @param string $tableName The name of the table for which the alias should be generated.
     * @param string $fullPropertyPath The full property path that is related to the given table.
     * @return string The generated table alias.
     */
    protected function getUniqueAlias(array &$sql, $tableName, $fullPropertyPath = null)
    {
        if (isset($fullPropertyPath) && isset($this->tablePropertyMap[$fullPropertyPath])) {
            return $this->tablePropertyMap[$fullPropertyPath];
        }

        $alias = $tableName;
        $i = 0;
        while (isset($sql['tableAliasMap'][$alias])) {
            $alias = $tableName . $i;
            $i++;
        }

        $sql['tableAliasMap'][$alias] = $tableName;

        if (isset($fullPropertyPath)) {
            $this->tablePropertyMap[$fullPropertyPath] = $alias;
        }

        return $alias;
    }

    /**
     * adds a union statement to the query, mostly for tables referenced in the where condition.
     * The property for which the union statement is generated will be appended.
     *
     * @param string &$className The name of the parent class, will be set to the child class after processing.
     * @param string &$tableName The name of the parent table, will be set to the table alias that is used in the union statement.
     * @param array &$propertyPath The remaining property path, will be cut of by one part during the process.
     * @param array &$sql The SQL statement parts, will be filled with the union statements.
     * @param string $fullPropertyPath The full path the the current property, will be used to make table names unique.
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidRelationConfigurationException
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\MissingColumnMapException
     */
    protected function addUnionStatement(&$className, &$tableName, &$propertyPath, array &$sql, &$fullPropertyPath)
    {
        $explodedPropertyPath = explode('.', $propertyPath, 2);
        $propertyName = $explodedPropertyPath[0];
        $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
        $realTableName = $this->dataMapper->convertClassNameToTableName($className);
        $tableName = isset($this->tablePropertyMap[$fullPropertyPath]) ? $this->tablePropertyMap[$fullPropertyPath] : $realTableName;
        $columnMap = $this->dataMapper->getDataMap($className)->getColumnMap($propertyName);

        if ($columnMap === null) {
            throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\MissingColumnMapException('The ColumnMap for property "' . $propertyName . '" of class "' . $className . '" is missing.', 1355142232);
        }

        $parentKeyFieldName = $columnMap->getParentKeyFieldName();
        $childTableName = $columnMap->getChildTableName();

        if ($childTableName === null) {
            throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidRelationConfigurationException('The relation information for property "' . $propertyName . '" of class "' . $className . '" is missing.', 1353170925);
        }

        $fullPropertyPath .= ($fullPropertyPath === '') ? $propertyName : '.' . $propertyName;
        $childTableAlias = $this->getUniqueAlias($sql, $childTableName, $fullPropertyPath);

        // If there is already exists a union with the current identifier we do not need to build it again and exit early.
        if (isset($sql['unions'][$childTableAlias])) {
            $propertyPath = $explodedPropertyPath[1];
            $tableName = $childTableAlias;
            $className = $this->dataMapper->getType($className, $propertyName);
            return;
        }

        if ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_ONE) {
            if (isset($parentKeyFieldName)) {
                $sql['unions'][$childTableAlias] = 'LEFT JOIN ' . $childTableName . ' AS ' . $childTableAlias . ' ON ' . $tableName . '.uid=' . $childTableAlias . '.' . $parentKeyFieldName;
            } else {
                $sql['unions'][$childTableAlias] = 'LEFT JOIN ' . $childTableName . ' AS ' . $childTableAlias . ' ON ' . $tableName . '.' . $columnName . '=' . $childTableAlias . '.uid';
            }
            $sql['unions'][$childTableAlias] .= $this->getAdditionalMatchFieldsStatement($columnMap, $childTableName, $childTableAlias, $realTableName);
        } elseif ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_MANY) {
            if (isset($parentKeyFieldName)) {
                $sql['unions'][$childTableAlias] = 'LEFT JOIN ' . $childTableName . ' AS ' . $childTableAlias . ' ON ' . $tableName . '.uid=' . $childTableAlias . '.' . $parentKeyFieldName;
            } else {
                $onStatement = '(FIND_IN_SET(' . $childTableAlias . '.uid, ' . $tableName . '.' . $columnName . '))';
                $sql['unions'][$childTableAlias] = 'LEFT JOIN ' . $childTableName . ' AS ' . $childTableAlias . ' ON ' . $onStatement;
            }
            $sql['unions'][$childTableAlias] .= $this->getAdditionalMatchFieldsStatement($columnMap, $childTableName, $childTableAlias, $realTableName);
        } elseif ($columnMap->getTypeOfRelation() === ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
            $relationTableName = $columnMap->getRelationTableName();
            $relationTableAlias = $relationTableAlias = $this->getUniqueAlias($sql, $relationTableName, $fullPropertyPath . '_mm');
            $sql['unions'][$relationTableAlias] = 'LEFT JOIN ' . $relationTableName . ' AS ' . $relationTableAlias . ' ON ' . $tableName . '.uid=' . $relationTableAlias . '.' . $columnMap->getParentKeyFieldName();
            $sql['unions'][$relationTableAlias] .= $this->getAdditionalMatchFieldsStatement($columnMap, $relationTableName, $relationTableAlias, $realTableName);
            $sql['unions'][$childTableAlias] = 'LEFT JOIN ' . $childTableName . ' AS ' . $childTableAlias . ' ON ' . $relationTableAlias . '.' . $columnMap->getChildKeyFieldName() . '=' . $childTableAlias . '.uid';
        } else {
            throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception('Could not determine type of relation.', 1252502725);
        }
        // @todo check if there is another solution for this
        $sql['keywords']['distinct'] = 'DISTINCT';
        $propertyPath = $explodedPropertyPath[1];
        $tableName = $childTableAlias;
        $className = $this->dataMapper->getType($className, $propertyName);
    }

    /**
     * If the table name does not match the table alias all occurrences of
     * "tableName." are replaced with "tableAlias." in the given SQL statement.
     *
     * @param string $statement The SQL statement in which the values are replaced.
     * @param string $tableName The table name that is replaced.
     * @param string $tableAlias The table alias that replaced the table name.
     * @return string The modified SQL statement.
     */
    protected function replaceTableNameWithAlias($statement, $tableName, $tableAlias)
    {
        if ($tableAlias !== $tableName) {
            $statement = str_replace($tableName . '.', $tableAlias . '.', $statement);
        }

        return $statement;
    }

    /**
     * Returns the SQL operator for the given JCR operator type.
     *
     * @param string $operator One of the JCR_OPERATOR_* constants
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     * @return string an SQL operator
     */
    protected function resolveOperator($operator)
    {
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
    protected function getPageRepository()
    {
        if (!$this->pageRepository instanceof \TYPO3\CMS\Frontend\Page\PageRepository) {
            if ($this->environmentService->isEnvironmentInFrontendMode() && is_object($GLOBALS['TSFE'])) {
                $this->pageRepository = $GLOBALS['TSFE']->sys_page;
            } else {
                $this->pageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
            }
        }

        return $this->pageRepository;
    }
}
