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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\SqlErrorException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * A Storage backend
 */
class Typo3DbBackend implements BackendInterface, SingletonInterface
{
    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

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
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

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
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
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
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Constructor. takes the database handle from $GLOBALS['TYPO3_DB']
     */
    public function __construct()
    {
        $this->connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * Adds a row to the storage
     *
     * @param string $tableName The database table name
     * @param array $fieldValues The row to be inserted
     * @param bool $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
     * @return int The uid of the inserted row
     * @throws SqlErrorException
     */
    public function addRow($tableName, array $fieldValues, $isRelation = false)
    {
        if (isset($fieldValues['uid'])) {
            unset($fieldValues['uid']);
        }
        try {
            $connection = $this->connectionPool->getConnectionForTable($tableName);
            $connection->insert($tableName, $fieldValues);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230766);
        }

        $uid = $connection->lastInsertId($tableName);

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
     * @throws SqlErrorException
     * @return bool
     */
    public function updateRow($tableName, array $fieldValues, $isRelation = false)
    {
        if (!isset($fieldValues['uid'])) {
            throw new \InvalidArgumentException('The given row must contain a value for "uid".', 1476045164);
        }

        $uid = (int)$fieldValues['uid'];
        unset($fieldValues['uid']);

        try {
            $this->connectionPool->getConnectionForTable($tableName)
                ->update($tableName, $fieldValues, ['uid' => $uid]);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230767);
        }

        if (!$isRelation) {
            $this->clearPageCache($tableName, $uid);
        }

        // always returns true
        return true;
    }

    /**
     * Updates a relation row in the storage.
     *
     * @param string $tableName The database relation table name
     * @param array $fieldValues The row to be updated
     * @throws \InvalidArgumentException
     * @return bool
     * @throws SqlErrorException
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

        try {
            $this->connectionPool->getConnectionForTable($tableName)
                ->update($tableName, $fieldValues, $where);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230768);
        }

        // always returns true
        return true;
    }

    /**
     * Deletes a row in the storage
     *
     * @param string $tableName The database table name
     * @param array $where An array of where array('fieldname' => value).
     * @param bool $isRelation TRUE if we are currently manipulating a relation table, FALSE by default
     * @return bool
     * @throws SqlErrorException
     */
    public function removeRow($tableName, array $where, $isRelation = false)
    {
        try {
            $this->connectionPool->getConnectionForTable($tableName)->delete($tableName, $where);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230769);
        }

        if (!$isRelation && isset($where['uid'])) {
            $this->clearPageCache($tableName, $where['uid']);
        }

        // always returns true
        return true;
    }

    /**
     * Fetches maximal value for given table column from database.
     *
     * @param string $tableName The database table name
     * @param array $where An array of where array('fieldname' => value).
     * @param string $columnName column name to get the max value from
     * @return mixed the max value
     * @throws SqlErrorException
     */
    public function getMaxValueFromTable($tableName, array $where, $columnName)
    {
        try {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->select($columnName)
                ->from($tableName)
                ->orderBy($columnName, 'DESC')
                ->setMaxResults(1);

            foreach ($where as $fieldName => $value) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq($fieldName, $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR))
                );
            }

            $result = $queryBuilder->execute()->fetchColumn(0);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230770);
        }
        return $result;
    }

    /**
     * Fetches row data from the database
     *
     * @param string $tableName
     * @param array $where An array of where array('fieldname' => value).
     * @return array|bool
     * @throws SqlErrorException
     */
    public function getRowByIdentifier($tableName, array $where)
    {
        try {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder
                ->select('*')
                ->from($tableName);

            foreach ($where as $fieldName => $value) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq($fieldName, $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR))
                );
            }

            $row = $queryBuilder->execute()->fetch();
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470230771);
        }
        return $row ?: false;
    }

    /**
     * Returns the object data matching the $query.
     *
     * @param QueryInterface $query
     * @return array
     * @throws SqlErrorException
     */
    public function getObjectDataByQuery(QueryInterface $query)
    {
        $statement = $query->getStatement();
        if ($statement instanceof Qom\Statement) {
            $rows = $this->getObjectDataByRawQuery($statement);
        } else {
            $queryBuilder = $this->objectManager->get(Typo3DbQueryParser::class)
                    ->convertQueryToDoctrineQueryBuilder($query);
            if ($query->getOffset()) {
                $queryBuilder->setFirstResult($query->getOffset());
            }
            if ($query->getLimit()) {
                $queryBuilder->setMaxResults($query->getLimit());
            }
            try {
                $rows = $queryBuilder->execute()->fetchAll();
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1472074485);
            }
        }

        $rows = $this->doLanguageAndWorkspaceOverlay($query->getSource(), $rows, $query->getQuerySettings());
        return $rows;
    }

    /**
     * Returns the object data using a custom statement
     *
     * @param Qom\Statement $statement
     * @return array
     * @throws SqlErrorException when the raw SQL statement fails in the database
     */
    protected function getObjectDataByRawQuery(Qom\Statement $statement)
    {
        $realStatement = $statement->getStatement();
        $parameters = $statement->getBoundVariables();

        // The real statement is an instance of the Doctrine DBAL QueryBuilder, so fetching
        // this directly is possible
        if ($realStatement instanceof QueryBuilder) {
            try {
                $result = $realStatement->execute();
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1472064721);
            }
            $rows = $result->fetchAll();
        } elseif ($realStatement instanceof \Doctrine\DBAL\Statement) {
            try {
                $result = $realStatement->execute($parameters);
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1481281404);
            }
            $rows = $result->fetchAll();
        } elseif ($realStatement instanceof \TYPO3\CMS\Core\Database\PreparedStatement) {
            $realStatement->execute($parameters);
            $rows = $realStatement->fetchAll();

            $realStatement->free();
        } else {
            // Do a real raw query. This is very stupid, as it does not allow to use DBAL's real power if
            // several tables are on different databases, so this is used with caution and could be removed
            // in the future
            try {
                $connection = $this->connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
                $statement = $connection->executeQuery($realStatement);
            } catch (DBALException $e) {
                throw new SqlErrorException($e->getPrevious()->getMessage(), 1472064775);
            }

            $rows = $statement->fetchAll();
        }

        return $rows;
    }

    /**
     * Returns the number of tuples matching the query.
     *
     * @param QueryInterface $query
     * @throws Exception\BadConstraintException
     * @return int The number of matching tuples
     * @throws SqlErrorException
     */
    public function getObjectCountByQuery(QueryInterface $query)
    {
        if ($query->getConstraint() instanceof Qom\Statement) {
            throw new \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException('Could not execute count on queries with a constraint of type TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\Statement', 1256661045);
        }

        $queryBuilder = $this->objectManager->get(Typo3DbQueryParser::class)
                ->convertQueryToDoctrineQueryBuilder($query);
        try {
            $count = $queryBuilder->count('*')->execute()->fetchColumn(0);
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1472074379);
        }
        if ($query->getOffset()) {
            $count -= $query->getOffset();
        }
        if ($query->getLimit()) {
            $count = min($count, $query->getLimit());
        }
        return (int)max(0, $count);
    }

    /**
     * Checks if a Value Object equal to the given Object exists in the database
     *
     * @param AbstractValueObject $object The Value Object
     * @return mixed The matching uid if an object was found, else FALSE
     * @throws SqlErrorException
     */
    public function getUidOfAlreadyPersistedValueObject(AbstractValueObject $object)
    {
        $dataMap = $this->dataMapper->getDataMap(get_class($object));
        $tableName = $dataMap->getTableName();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }
        $whereClause = [];
        // loop over all properties of the object to exactly set the values of each database field
        $properties = $object->_getProperties();
        foreach ($properties as $propertyName => $propertyValue) {
            // @todo We couple the Backend to the Entity implementation (uid, isClone); changes there breaks this method
            if ($dataMap->isPersistableProperty($propertyName) && $propertyName !== 'uid' && $propertyName !== 'pid' && $propertyName !== 'isClone') {
                $fieldName = $dataMap->getColumnMap($propertyName)->getColumnName();
                if ($propertyValue === null) {
                    $whereClause[] = $queryBuilder->expr()->isNull($fieldName);
                } else {
                    $whereClause[] = $queryBuilder->expr()->eq($fieldName, $queryBuilder->createNamedParameter($this->dataMapper->getPlainValue($propertyValue)));
                }
            }
        }
        $queryBuilder
            ->select('uid')
            ->from($tableName)
            ->where(...$whereClause);

        try {
            $uid = (int)$queryBuilder
                ->execute()
                ->fetchColumn(0);
            if ($uid > 0) {
                return $uid;
            } else {
                return false;
            }
        } catch (DBALException $e) {
            throw new SqlErrorException($e->getPrevious()->getMessage(), 1470231748);
        }
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
            $versionId = $pageRepository->versioningWorkspaceId;
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $movePlaceholder = $queryBuilder->select($tableName . '.*')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq('t3ver_state', $queryBuilder->createNamedParameter(3, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter($versionId, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('t3ver_move_id', $queryBuilder->createNamedParameter($rows[0]['uid'], \PDO::PARAM_INT))
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();
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
                && $tableName !== 'pages_language_overlay'
            ) {
                if (isset($row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']])
                    && $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] > 0
                ) {
                    $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
                    $queryBuilder->getRestrictions()->removeAll();
                    $row = $queryBuilder->select($tableName . '.*')
                        ->from($tableName)
                        ->where(
                            $queryBuilder->expr()->eq(
                                $tableName . '.uid',
                                $queryBuilder->createNamedParameter(
                                    $row[$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']],
                                    \PDO::PARAM_INT
                                )
                            ),
                            $queryBuilder->expr()->eq(
                                $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'],
                                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                            )
                        )
                        ->setMaxResults(1)
                        ->execute()
                        ->fetch();
                }
            }
            $pageRepository->versionOL($tableName, $row, true);
            if ($tableName == 'pages') {
                $row = $pageRepository->getPageOverlay($row, $querySettings->getLanguageUid());
            } elseif (isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
                      && $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] !== ''
                      && $tableName !== 'pages_language_overlay'
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
                $this->pageRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Page\PageRepository::class);
            }
        }

        return $this->pageRepository;
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
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (isset($frameworkConfiguration['persistence']['enableAutomaticCacheClearing']) && $frameworkConfiguration['persistence']['enableAutomaticCacheClearing'] === '1') {
        } else {
            // if disabled, return
            return;
        }
        $pageIdsToClear = [];
        $storagePage = null;
        $columns = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($tableName)
            ->getSchemaManager()
            ->listTableColumns($tableName);
        if (array_key_exists('pid', $columns)) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $result = $queryBuilder
                ->select('pid')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->execute();
            if ($row = $result->fetch()) {
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
            $clearCacheCommands = GeneralUtility::trimExplode(',', strtolower($this->pageTSConfigCache[$storagePage]['TCEMAIN.']['clearCacheCmd']), true);
            $clearCacheCommands = array_unique($clearCacheCommands);
            foreach ($clearCacheCommands as $clearCacheCommand) {
                if (MathUtility::canBeInterpretedAsInteger($clearCacheCommand)) {
                    $pageIdsToClear[] = $clearCacheCommand;
                }
            }
        }

        foreach ($pageIdsToClear as $pageIdToClear) {
            $this->cacheService->getPageIdStack()->push($pageIdToClear);
        }
    }
}
