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
use TYPO3\CMS\Extbase\Persistence\Generic\Qom;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * A Storage backend
 */
class Typo3DbBackend implements BackendInterface, \TYPO3\CMS\Core\SingletonInterface
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
     * A first-level TypoScript configuration cache
     *
     * @var array
     */
    protected $pageTSConfigCache = [];

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
    protected $queryCache;

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser
     */
    protected $queryParser;

    /**
     * A first level cache for queries during runtime
     *
     * @var array
     */
    protected $queryRuntimeCache = [];

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
     */
    public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\CacheService $cacheService
     */
    public function injectCacheService(\TYPO3\CMS\Extbase\Service\CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @param \TYPO3\CMS\Core\Cache\CacheManager $cacheManager
     */
    public function injectCacheManager(\TYPO3\CMS\Core\Cache\CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser $queryParser
     */
    public function injectQueryParser(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser $queryParser)
    {
        $this->queryParser = $queryParser;
    }

    /**
     * Constructor. takes the database handle from $GLOBALS['TYPO3_DB']
     */
    public function __construct()
    {
        $this->databaseHandle = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Lifecycle method
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->queryCache = $this->cacheManager->getCache('extbase_typo3dbbackend_queries');
    }

    /**
     * Adds a row to the storage
     *
     * @param string $tableName The database table name
     * @param array $fieldValues The row to be inserted
     * @param bool $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
     * @return int The uid of the inserted row
     */
    public function addRow($tableName, array $fieldValues, $isRelation = false)
    {
        if (isset($fieldValues['uid'])) {
            unset($fieldValues['uid']);
        }

        $this->databaseHandle->exec_INSERTquery($tableName, $fieldValues);
        $this->checkSqlErrors();
        $uid = $this->databaseHandle->sql_insert_id();

        if (!$isRelation) {
            $this->clearPageCache($tableName, $uid);
        }
        return (int)$uid;
    }

    /**
     * Updates a row in the storage
     *
     * @param string $tableName The database table name
     * @param array $fieldValues The row to be updated
     * @param bool $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function updateRow($tableName, array $fieldValues, $isRelation = false)
    {
        if (!isset($fieldValues['uid'])) {
            throw new \InvalidArgumentException('The given row must contain a value for "uid".');
        }

        $uid = (int)$fieldValues['uid'];
        unset($fieldValues['uid']);

        $updateSuccessful = $this->databaseHandle->exec_UPDATEquery($tableName, 'uid = ' . $uid, $fieldValues);
        $this->checkSqlErrors();

        if (!$isRelation) {
            $this->clearPageCache($tableName, $uid);
        }

        return $updateSuccessful;
    }

    /**
     * Updates a relation row in the storage.
     *
     * @param string $tableName The database relation table name
     * @param array $fieldValues The row to be updated
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function updateRelationTableRow($tableName, array $fieldValues)
    {
        if (!isset($fieldValues['uid_local']) && !isset($fieldValues['uid_foreign'])) {
            throw new \InvalidArgumentException(
                'The given fieldValues must contain a value for "uid_local" and "uid_foreign".', 1360500126
            );
        }

        $where['uid_local'] = (int)$fieldValues['uid_local'];
        $where['uid_foreign'] = (int)$fieldValues['uid_foreign'];
        unset($fieldValues['uid_local']);
        unset($fieldValues['uid_foreign']);

        if (!empty($fieldValues['tablenames'])) {
            $where['tablenames'] = $fieldValues['tablenames'];
            unset($fieldValues['tablenames']);
        }
        if (!empty($fieldValues['fieldname'])) {
            $where['fieldname'] = $fieldValues['fieldname'];
            unset($fieldValues['fieldname']);
        }

        $updateSuccessful = $this->databaseHandle->exec_UPDATEquery(
            $tableName,
            $this->resolveWhereStatement($where, $tableName),
            $fieldValues
        );
        $this->checkSqlErrors();

        return $updateSuccessful;
    }

    /**
     * Deletes a row in the storage
     *
     * @param string $tableName The database table name
     * @param array $where An array of where array('fieldname' => value).
     * @param bool $isRelation TRUE if we are currently manipulating a relation table, FALSE by default
     * @return bool
     */
    public function removeRow($tableName, array $where, $isRelation = false)
    {
        $deleteSuccessful = $this->databaseHandle->exec_DELETEquery(
            $tableName,
            $this->resolveWhereStatement($where, $tableName)
        );
        $this->checkSqlErrors();

        if (!$isRelation && isset($where['uid'])) {
            $this->clearPageCache($tableName, $where['uid']);
        }

        return $deleteSuccessful;
    }

    /**
     * Fetches maximal value for given table column from database.
     *
     * @param string $tableName The database table name
     * @param array $where An array of where array('fieldname' => value).
     * @param string $columnName column name to get the max value from
     * @return mixed the max value
     */
    public function getMaxValueFromTable($tableName, array $where, $columnName)
    {
        $result = $this->databaseHandle->exec_SELECTgetSingleRow(
            $columnName,
            $tableName,
            $this->resolveWhereStatement($where, $tableName),
            '',
            $columnName . ' DESC',
            true
        );
        $this->checkSqlErrors();

        return $result[0];
    }

    /**
     * Fetches row data from the database
     *
     * @param string $tableName
     * @param array $where An array of where array('fieldname' => value).
     * @return array|bool
     */
    public function getRowByIdentifier($tableName, array $where)
    {
        $row = $this->databaseHandle->exec_SELECTgetSingleRow(
            '*',
            $tableName,
            $this->resolveWhereStatement($where, $tableName)
        );
        $this->checkSqlErrors();

        return $row ?: false;
    }

    /**
     * Converts an array to an AND concatenated where statement
     *
     * @param array $where array('fieldName' => 'fieldValue')
     * @param string $tableName table to use for escaping config
     *
     * @return string
     */
    protected function resolveWhereStatement(array $where, $tableName = 'foo')
    {
        $whereStatement = [];

        foreach ($where as $fieldName => $fieldValue) {
            $whereStatement[] = $fieldName . ' = ' . $this->databaseHandle->fullQuoteStr($fieldValue, $tableName);
        }

        return implode(' AND ', $whereStatement);
    }

    /**
     * Returns the object data matching the $query.
     *
     * @param QueryInterface $query
     * @return array
     */
    public function getObjectDataByQuery(QueryInterface $query)
    {
        $statement = $query->getStatement();
        if ($statement instanceof Qom\Statement) {
            $rows = $this->getObjectDataByRawQuery($statement);
        } else {
            $rows = $this->getRowsByStatementParts($query);
        }

        $rows = $this->doLanguageAndWorkspaceOverlay($query->getSource(), $rows, $query->getQuerySettings());
        return $rows;
    }

    /**
     * Creates the parameters for the query methods of the database methods in the TYPO3 core, from an array
     * that came from a parsed query.
     *
     * @param array $statementParts
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function createQueryCommandParametersFromStatementParts(array $statementParts)
    {
        if (isset($statementParts['offset']) && !isset($statementParts['limit'])) {
            throw new \InvalidArgumentException(
                'Trying to make query with offset and no limit, the offset would become a limit. You have to set a limit to use offset. To retrieve all rows from a certain offset up to the end of the result set, you can use some large number for the limit.',
                1465223252
            );
        }
        return [
            'selectFields' => implode(' ', $statementParts['keywords']) . ' ' . implode(',', $statementParts['fields']),
            'fromTable'    => implode(' ', $statementParts['tables']) . ' ' . implode(' ', $statementParts['unions']),
            'whereClause'  => (!empty($statementParts['where']) ? implode('', $statementParts['where']) : '1=1')
                . (!empty($statementParts['additionalWhereClause'])
                    ? ' AND ' . implode(' AND ', $statementParts['additionalWhereClause'])
                    : ''
            ),
            'orderBy'      => (!empty($statementParts['orderings']) ? implode(', ', $statementParts['orderings']) : ''),
            'limit'        => ($statementParts['offset'] ? $statementParts['offset'] . ', ' : '')
                . ($statementParts['limit'] ? $statementParts['limit'] : '')
        ];
    }

    /**
     * Determines whether to use prepared statement or not and returns the rows from the corresponding method
     *
     * @param QueryInterface $query
     * @return array
     */
    protected function getRowsByStatementParts(QueryInterface $query)
    {
        if ($query->getQuerySettings()->getUsePreparedStatement()) {
            list($statementParts, $parameters) = $this->getStatementParts($query, false);
            $rows = $this->getRowsFromPreparedDatabase($statementParts, $parameters);
        } else {
            list($statementParts) = $this->getStatementParts($query);
            $rows = $this->getRowsFromDatabase($statementParts);
        }

        return $rows;
    }

    /**
     * Fetches the rows directly from the database, not using prepared statement
     *
     * @param array $statementParts
     * @return array the result
     */
    protected function getRowsFromDatabase(array $statementParts)
    {
        $queryCommandParameters = $this->createQueryCommandParametersFromStatementParts($statementParts);
        $rows = $this->databaseHandle->exec_SELECTgetRows(
            $queryCommandParameters['selectFields'],
            $queryCommandParameters['fromTable'],
            $queryCommandParameters['whereClause'],
            '',
            $queryCommandParameters['orderBy'],
            $queryCommandParameters['limit']
        );
        $this->checkSqlErrors();

        return $rows;
    }

    /**
     * Fetches the rows from the database, using prepared statement
     *
     * @param array $statementParts
     * @param array $parameters
     * @return array the result
     */
    protected function getRowsFromPreparedDatabase(array $statementParts, array $parameters)
    {
        $queryCommandParameters = $this->createQueryCommandParametersFromStatementParts($statementParts);
        $preparedStatement = $this->databaseHandle->prepare_SELECTquery(
            $queryCommandParameters['selectFields'],
            $queryCommandParameters['fromTable'],
            $queryCommandParameters['whereClause'],
            '',
            $queryCommandParameters['orderBy'],
            $queryCommandParameters['limit']
        );

        $preparedStatement->execute($parameters);
        $rows = $preparedStatement->fetchAll();

        $preparedStatement->free();
        return $rows;
    }

    /**
     * Returns the object data using a custom statement
     *
     * @param Qom\Statement $statement
     * @return array
     */
    protected function getObjectDataByRawQuery(Qom\Statement $statement)
    {
        $realStatement = $statement->getStatement();
        $parameters = $statement->getBoundVariables();

        if ($realStatement instanceof \TYPO3\CMS\Core\Database\PreparedStatement) {
            $realStatement->execute($parameters);
            $rows = $realStatement->fetchAll();

            $realStatement->free();
        } else {
            $result = $this->databaseHandle->sql_query($realStatement);
            $this->checkSqlErrors();

            $rows = [];
            while ($row = $this->databaseHandle->sql_fetch_assoc($result)) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
            $this->databaseHandle->sql_free_result($result);
        }

        return $rows;
    }

    /**
     * Returns the number of tuples matching the query.
     *
     * @param QueryInterface $query
     * @throws Exception\BadConstraintException
     * @return int The number of matching tuples
     */
    public function getObjectCountByQuery(QueryInterface $query)
    {
        if ($query->getConstraint() instanceof Qom\Statement) {
            throw new \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException('Could not execute count on queries with a constraint of type TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Statement', 1256661045);
        }

        list($statementParts) = $this->getStatementParts($query);

        $fields = '*';
        if (isset($statementParts['keywords']['distinct'])) {
            $fields = 'DISTINCT ' . reset($statementParts['tables']) . '.uid';
        }

        $queryCommandParameters = $this->createQueryCommandParametersFromStatementParts($statementParts);
        $count = $this->databaseHandle->exec_SELECTcountRows(
            $fields,
            $queryCommandParameters['fromTable'],
            $queryCommandParameters['whereClause']
        );
        $this->checkSqlErrors();

        if ($statementParts['offset']) {
            $count -= $statementParts['offset'];
        }

        if ($statementParts['limit']) {
            $count = min($count, $statementParts['limit']);
        }

        return (int)max(0, $count);
    }

    /**
     * Looks for the query in cache or builds it up otherwise
     *
     * @param QueryInterface $query
     * @param bool $resolveParameterPlaceholders whether to resolve the parameters or leave the placeholders
     * @return array
     * @throws \RuntimeException
     */
    protected function getStatementParts($query, $resolveParameterPlaceholders = true)
    {
        /**
         * The queryParser will preparse the query to get the query's hash and parameters.
         * If the hash is found in the cache and useQueryCaching is enabled, extbase will
         * then take the string representation from cache and build a prepared query with
         * the parameters found.
         *
         * Otherwise extbase will parse the complete query, build the string representation
         * and run a usual query.
         */
        list($queryHash, $parameters) = $this->queryParser->preparseQuery($query);

        if ($query->getQuerySettings()->getUseQueryCache()) {
            $statementParts = $this->getQueryCacheEntry($queryHash);
            if ($queryHash && !$statementParts) {
                $statementParts = $this->queryParser->parseQuery($query);
                $this->setQueryCacheEntry($queryHash, $statementParts);
            }
        } else {
            $statementParts = $this->queryParser->parseQuery($query);
        }

        if (!$statementParts) {
            throw new \RuntimeException('Your query could not be built.', 1394453197);
        }

        $this->queryParser->addDynamicQueryParts($query->getQuerySettings(), $statementParts);

        // Limit and offset are not cached to allow caching of pagebrowser queries.
        $statementParts['limit'] = ((int)$query->getLimit() ?: null);
        $statementParts['offset'] = ((int)$query->getOffset() ?: null);

        if ($resolveParameterPlaceholders === true) {
            $statementParts = $this->resolveParameterPlaceholders($statementParts, $parameters);
        }

        return [$statementParts, $parameters];
    }

    /**
     * Replaces the parameters in the queryStructure with given values
     *
     * @param array $statementParts
     * @param array $parameters
     * @return array
     */
    protected function resolveParameterPlaceholders(array $statementParts, array $parameters)
    {
        $tableName = reset($statementParts['tables']) ?: 'foo';

        foreach ($parameters as $parameterPlaceholder => $parameter) {
            $parameter = $this->dataMapper->getPlainValue($parameter, null, [$this, 'quoteTextValueCallback'], ['tablename' => $tableName]);
            $statementParts['where'] = str_replace($parameterPlaceholder, $parameter, $statementParts['where']);
        }

        return $statementParts;
    }

    /**
     * Will be called by the data mapper to quote string values.
     *
     * @param string $value The value to be quoted.
     * @param array $parameters Additional parameters array currently containing the "tablename" key.
     * @return string The quoted string.
     */
    public function quoteTextValueCallback($value, $parameters)
    {
        return $this->databaseHandle->fullQuoteStr($value, $parameters['tablename']);
    }

    /**
     * Checks if a Value Object equal to the given Object exists in the data base
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object The Value Object
     * @return mixed The matching uid if an object was found, else FALSE
     * @todo this is the last monster in this persistence series. refactor!
     */
    public function getUidOfAlreadyPersistedValueObject(\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject $object)
    {
        $fields = [];
        $parameters = [];
        $dataMap = $this->dataMapper->getDataMap(get_class($object));
        $properties = $object->_getProperties();
        foreach ($properties as $propertyName => $propertyValue) {
            // @todo We couple the Backend to the Entity implementation (uid, isClone); changes there breaks this method
            if ($dataMap->isPersistableProperty($propertyName) && $propertyName !== 'uid' && $propertyName !== 'pid' && $propertyName !== 'isClone') {
                if ($propertyValue === null) {
                    $fields[] = $dataMap->getColumnMap($propertyName)->getColumnName() . ' IS NULL';
                } else {
                    $fields[] = $dataMap->getColumnMap($propertyName)->getColumnName() . '=?';
                    $parameters[] = $this->dataMapper->getPlainValue($propertyValue);
                }
            }
        }
        $sql = [];
        $sql['additionalWhereClause'] = [];
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
        if ($row !== false) {
            return (int)$row['uid'];
        } else {
            return false;
        }
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
     * @deprecated since 6.2, will be removed two versions later
     * @todo add deprecation notice after getUidOfAlreadyPersistedValueObject is adjusted
     */
    protected function replacePlaceholders(&$sqlString, array $parameters, $tableName = 'foo')
    {
        // @todo profile this method again
        if (substr_count($sqlString, '?') !== count($parameters)) {
            throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception('The number of question marks to replace must be equal to the number of parameters.', 1242816074);
        }
        $offset = 0;
        foreach ($parameters as $parameter) {
            $markPosition = strpos($sqlString, '?', $offset);
            if ($markPosition !== false) {
                if ($parameter === null) {
                    $parameter = 'NULL';
                } elseif (is_array($parameter) || $parameter instanceof \ArrayAccess || $parameter instanceof \Traversable) {
                    $items = [];
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
     * Adds enableFields and deletedClause to the query if necessary
     *
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings
     * @param string $tableName The database table name
     * @param array &$sql The query parts
     * @return void
     * @todo remove after getUidOfAlreadyPersistedValueObject is adjusted, this was moved to queryParser
     */
    protected function addVisibilityConstraintStatement(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings, $tableName, array &$sql)
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
     * @todo remove after getUidOfAlreadyPersistedValueObject is adjusted, this was moved to queryParser
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
     * @todo remove after getUidOfAlreadyPersistedValueObject is adjusted, this was moved to queryParser
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
     * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
     * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
     *
     * @param Qom\SourceInterface $source The source (selector od join)
     * @param array $rows
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
     * @param null|int $workspaceUid
     * @return array
     */
    protected function doLanguageAndWorkspaceOverlay(Qom\SourceInterface $source, array $rows, \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings, $workspaceUid = null)
    {
        if ($source instanceof Qom\SelectorInterface) {
            $tableName = $source->getSelectorName();
        } elseif ($source instanceof Qom\JoinInterface) {
            $tableName = $source->getRight()->getSelectorName();
        } else {
            // No proper source, so we do not have a table name here
            // we cannot do an overlay and return the original rows instead.
            return $rows;
        }

        $pageRepository = $this->getPageRepository();
        if (is_object($GLOBALS['TSFE'])) {
            if ($workspaceUid !== null) {
                $pageRepository->versioningWorkspaceId = $workspaceUid;
            }
        } else {
            if ($workspaceUid === null) {
                $workspaceUid = $GLOBALS['BE_USER']->workspace;
            }
            $pageRepository->versioningWorkspaceId = $workspaceUid;
        }

        // Fetches the move-placeholder in case it is supported
        // by the table and if there's only one row in the result set
        // (applying this to all rows does not work, since the sorting
        // order would be destroyed and possible limits not met anymore)
        if (!empty($pageRepository->versioningWorkspaceId)
            && BackendUtility::isTableWorkspaceEnabled($tableName)
            && count($rows) === 1
        ) {
            $movePlaceholder = $this->databaseHandle->exec_SELECTgetSingleRow(
                $tableName . '.*',
                $tableName,
                't3ver_state=3 AND t3ver_wsid=' . $pageRepository->versioningWorkspaceId
                    . ' AND t3ver_move_id=' . $rows[0]['uid']
            );
            if (!empty($movePlaceholder)) {
                $rows = [$movePlaceholder];
            }
        }

        $overlaidRows = [];
        foreach ($rows as $row) {
            // If current row is a translation select its parent
            if (isset($tableName) && isset($GLOBALS['TCA'][$tableName])
                && isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
                && isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
                && !isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerTable'])
            ) {
                if (isset($row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']])
                    && $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] > 0
                ) {
                    $row = $this->databaseHandle->exec_SELECTgetSingleRow(
                        $tableName . '.*',
                        $tableName,
                        $tableName . '.uid=' . (int)$row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] .
                            ' AND ' . $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . '=0'
                    );
                }
            }
            $pageRepository->versionOL($tableName, $row, true);
            if ($tableName == 'pages') {
                $row = $pageRepository->getPageOverlay($row, $querySettings->getLanguageUid());
            } elseif (isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
                && $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] !== ''
                && !isset($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerTable'])
            ) {
                if (in_array($row[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']], [-1, 0])) {
                    $overlayMode = $querySettings->getLanguageMode() === 'strict' ? 'hideNonTranslated' : '';
                    $row = $pageRepository->getRecordOverlay($tableName, $row, $querySettings->getLanguageUid(), $overlayMode);
                }
            }
            if ($row !== null && is_array($row)) {
                $overlaidRows[] = $row;
            }
        }
        return $overlaidRows;
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

    /**
     * Checks if there are SQL errors in the last query, and if yes, throw an exception.
     *
     * @return void
     * @param string $sql The SQL statement
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException
     */
    protected function checkSqlErrors($sql = '')
    {
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
     * Much of this functionality is taken from DataHandler::clear_cache() which unfortunately only works with logged-in BE user.
     *
     * @param string $tableName Table name of the record
     * @param int $uid UID of the record
     * @return void
     */
    protected function clearPageCache($tableName, $uid)
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (isset($frameworkConfiguration['persistence']['enableAutomaticCacheClearing']) && $frameworkConfiguration['persistence']['enableAutomaticCacheClearing'] === '1') {
        } else {
            // if disabled, return
            return;
        }
        $pageIdsToClear = [];
        $storagePage = null;
        $columns = $this->databaseHandle->admin_get_fields($tableName);
        if (array_key_exists('pid', $columns)) {
            $result = $this->databaseHandle->exec_SELECTquery('pid', $tableName, 'uid=' . (int)$uid);
            if ($row = $this->databaseHandle->sql_fetch_assoc($result)) {
                $storagePage = $row['pid'];
                $pageIdsToClear[] = $storagePage;
            }
        } elseif (isset($GLOBALS['TSFE'])) {
            // No PID column - we can do a best-effort to clear the cache of the current page if in FE
            $storagePage = $GLOBALS['TSFE']->id;
            $pageIdsToClear[] = $storagePage;
        }
        if ($storagePage === null) {
            return;
        }
        if (!isset($this->pageTSConfigCache[$storagePage])) {
            $this->pageTSConfigCache[$storagePage] = BackendUtility::getPagesTSconfig($storagePage);
        }
        if (isset($this->pageTSConfigCache[$storagePage]['TCEMAIN.']['clearCacheCmd'])) {
            $clearCacheCommands = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', strtolower($this->pageTSConfigCache[$storagePage]['TCEMAIN.']['clearCacheCmd']), true);
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

    /**
     * Finds and returns a variable value from the query cache.
     *
     * @param string $entryIdentifier Identifier of the cache entry to fetch
     * @return mixed The value
     */
    protected function getQueryCacheEntry($entryIdentifier)
    {
        if (!isset($this->queryRuntimeCache[$entryIdentifier])) {
            $this->queryRuntimeCache[$entryIdentifier] = $this->queryCache->get($entryIdentifier);
        }
        return $this->queryRuntimeCache[$entryIdentifier];
    }

    /**
     * Saves the value of a PHP variable in the query cache.
     *
     * @param string $entryIdentifier An identifier used for this cache entry
     * @param mixed $variable The query to cache
     * @return void
     */
    protected function setQueryCacheEntry($entryIdentifier, $variable)
    {
        $this->queryRuntimeCache[$entryIdentifier] = $variable;
        $this->queryCache->set($entryIdentifier, $variable, [], 0);
    }
}
