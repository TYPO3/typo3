<?php
namespace TYPO3\CMS\Backend\History;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for fetching the history entries of a record (and if it is a page, its subelements
 * as well)
 */
class RecordHistory
{
    /**
     * Maximum number of sys_history steps to show.
     *
     * @var int
     */
    protected $maxSteps = 20;

    /**
     * On a pages table - show sub elements as well.
     *
     * @var int
     */
    protected $showSubElements = 1;

    /**
     * Element reference, syntax [tablename]:[uid]
     *
     * @var string
     */
    protected $element;

    /**
     * sys_history uid which is selected
     *
     * @var int
     */
    public $lastHistoryEntry;

    /**
     * @var array
     */
    public $changeLog = [];

    /**
     * Internal cache
     * @var array
     */
    protected $pageAccessCache = [];

    /**
     * Either "table:uid" or "table:uid:field" to know which data should be rolled back
     * @var string
     */
    protected $rollbackFields = '';

    /**
     * Constructor to define which element to work on - can be overriden with "setLastHistoryEntry"
     *
     * @param string $element in the form of "tablename:uid"
     * @param string $rollbackFields
     */
    public function __construct($element = '', $rollbackFields = '')
    {
        $this->element = $this->sanitizeElementValue($element);
        $this->rollbackFields = $this->sanitizeRollbackFieldsValue($rollbackFields);
    }

    /**
     * If a specific history entry is selected, then the relevant element is resolved for that.
     *
     * @param int $lastHistoryEntry
     */
    public function setLastHistoryEntry(int $lastHistoryEntry)
    {
        if ($lastHistoryEntry) {
            $elementData = $this->getHistoryEntry($lastHistoryEntry);
            $this->lastHistoryEntry = $lastHistoryEntry;
            if (!empty($elementData) && empty($this->element)) {
                $this->element = $elementData['tablename'] . ':' . $elementData['recuid'];
            }
        }
    }

    /**
     * Define the maximum amount of history entries to be shown. Beware of side-effects when using
     * "showSubElements" as well.
     *
     * @param int $maxSteps
     */
    public function setMaxSteps(int $maxSteps)
    {
        $this->maxSteps = $maxSteps;
    }

    /**
     * Defines to show the history of a specific record or its subelements (when it's a page)
     * as well.
     *
     * @param bool $showSubElements
     */
    public function setShowSubElements(bool $showSubElements)
    {
        $this->showSubElements = $showSubElements;
    }

    /**
     * Creates change log including sub-elements, filling $this->changeLog
     */
    public function createChangeLog()
    {
        if (!empty($this->element)) {
            list($table, $recordUid) = explode(':', $this->element);
            $this->changeLog = $this->getHistoryData($table, $recordUid, $this->showSubElements, $this->lastHistoryEntry);
        }
    }

    /**
     * Whether rollback mode is on
     * @return bool
     */
    public function shouldPerformRollback()
    {
        return !empty($this->rollbackFields);
    }

    /**
     * An array (0 = tablename, 1 = uid) or false if no element is set
     *
     * @return array|bool
     */
    public function getElementData()
    {
        return !empty($this->element) ? explode(':', $this->element) : false;
    }

    /**
     * @return string named "tablename:uid"
     */
    public function getElementString(): string
    {
        return (string)$this->element;
    }

    /**
     * Perform rollback via DataHandler
     */
    public function performRollback()
    {
        if (!$this->shouldPerformRollback()) {
            return;
        }
        $rollbackData = explode(':', $this->rollbackFields);
        $diff = $this->createMultipleDiff();
        // PROCESS INSERTS AND DELETES
        // rewrite inserts and deletes
        $cmdmapArray = [];
        $data = [];
        if ($diff['insertsDeletes']) {
            switch (count($rollbackData)) {
                case 1:
                    // all tables
                    $data = $diff['insertsDeletes'];
                    break;
                case 2:
                    // one record
                    if ($diff['insertsDeletes'][$this->rollbackFields]) {
                        $data[$this->rollbackFields] = $diff['insertsDeletes'][$this->rollbackFields];
                    }
                    break;
                case 3:
                    // one field in one record -- ignore!
                    break;
            }
            if (!empty($data)) {
                foreach ($data as $key => $action) {
                    $elParts = explode(':', $key);
                    if ((int)$action === 1) {
                        // inserted records should be deleted
                        $cmdmapArray[$elParts[0]][$elParts[1]]['delete'] = 1;
                        // When the record is deleted, the contents of the record do not need to be updated
                        unset($diff['oldData'][$key]);
                        unset($diff['newData'][$key]);
                    } elseif ((int)$action === -1) {
                        // deleted records should be inserted again
                        $cmdmapArray[$elParts[0]][$elParts[1]]['undelete'] = 1;
                    }
                }
            }
        }
        // Writes the data:
        if ($cmdmapArray) {
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->dontProcessTransformations = true;
            $tce->start([], $cmdmapArray);
            $tce->process_cmdmap();
            unset($tce);
        }
        if (!$diff['insertsDeletes']) {
            // PROCESS CHANGES
            // create an array for process_datamap
            $diffModified = [];
            foreach ($diff['oldData'] as $key => $value) {
                $splitKey = explode(':', $key);
                $diffModified[$splitKey[0]][$splitKey[1]] = $value;
            }
            switch (count($rollbackData)) {
                case 1:
                    // all tables
                    $data = $diffModified;
                    break;
                case 2:
                    // one record
                    $data[$rollbackData[0]][$rollbackData[1]] = $diffModified[$rollbackData[0]][$rollbackData[1]];
                    break;
                case 3:
                    // one field in one record
                    $data[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]] = $diffModified[$rollbackData[0]][$rollbackData[1]][$rollbackData[2]];
                    break;
            }
            // Removing fields:
            $data = $this->removeFilefields($rollbackData[0], $data);
            // Writes the data:
            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->dontProcessTransformations = true;
            $tce->start($data, []);
            $tce->process_datamap();
            unset($tce);
        }
        // Return to normal operation
        $this->lastHistoryEntry = false;
        $this->rollbackFields = '';
        $this->createChangeLog();
        if (isset($data['pages']) || isset($cmdmapArray['pages'])) {
            BackendUtility::setUpdateSignal('updatePageTree');
        }
    }

    /*******************************
     *
     * build up history
     *
     *******************************/

    /**
     * Creates a diff between the current version of the records and the selected version
     *
     * @return array Diff for many elements
     */
    public function createMultipleDiff(): array
    {
        $insertsDeletes = [];
        $newArr = [];
        $differences = [];
        // traverse changelog array
        foreach ($this->changeLog as $value) {
            $field = $value['tablename'] . ':' . $value['recuid'];
            // inserts / deletes
            if ((int)$value['actiontype'] !== RecordHistoryStore::ACTION_MODIFY) {
                if (!$insertsDeletes[$field]) {
                    $insertsDeletes[$field] = 0;
                }
                if ($value['action'] === 'insert') {
                    $insertsDeletes[$field]++;
                } else {
                    $insertsDeletes[$field]--;
                }
                // unset not needed fields
                if ($insertsDeletes[$field] === 0) {
                    unset($insertsDeletes[$field]);
                }
            } else {
                // update fields
                // first row of field
                if (!isset($newArr[$field])) {
                    $newArr[$field] = $value['newRecord'];
                    $differences[$field] = $value['oldRecord'];
                } else {
                    // standard
                    $differences[$field] = array_merge($differences[$field], $value['oldRecord']);
                }
            }
        }
        // remove entries where there were no changes effectively
        foreach ($newArr as $record => $value) {
            foreach ($value as $key => $innerVal) {
                if ($newArr[$record][$key] == $differences[$record][$key]) {
                    unset($newArr[$record][$key]);
                    unset($differences[$record][$key]);
                }
            }
            if (empty($newArr[$record]) && empty($differences[$record])) {
                unset($newArr[$record]);
                unset($differences[$record]);
            }
        }
        return [
            'newData' => $newArr,
            'oldData' => $differences,
            'insertsDeletes' => $insertsDeletes
        ];
    }

    /**
     * Fetches the history data of a record + includes subelements if this is from a page
     *
     * @param string $table
     * @param int $uid
     * @param bool $includeSubentries
     * @param int $lastHistoryEntry the highest entry to be evaluated
     * @return array
     */
    public function getHistoryData(string $table, int $uid, bool $includeSubentries = null, int $lastHistoryEntry = null): array
    {
        $changeLog = $this->getHistoryDataForRecord($table, $uid, $lastHistoryEntry);
        // get history of tables of this page and merge it into changelog
        if ($table === 'pages' && $includeSubentries && $this->hasPageAccess('pages', $uid)) {
            foreach ($GLOBALS['TCA'] as $tablename => $value) {
                // check if there are records on the page
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tablename);
                $queryBuilder->getRestrictions()->removeAll();

                $rows = $queryBuilder
                    ->select('uid')
                    ->from($tablename)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
                $rowCount = (int)$queryBuilder->count('uid')->execute()->fetchColumn(0);
                if ($rowCount === 0) {
                    continue;
                }
                foreach ($rows as $row) {
                    // if there is history data available, merge it into changelog
                    $newChangeLog = $this->getHistoryDataForRecord($tablename, $row['uid'], $lastHistoryEntry);
                    if (is_array($newChangeLog) && !empty($newChangeLog)) {
                        foreach ($newChangeLog as $key => $newChangeLogEntry) {
                            $changeLog[$key] = $newChangeLogEntry;
                        }
                    }
                }
            }
        }
        usort($changeLog, function ($a, $b) {
            if ($a['tstamp'] < $b['tstamp']) {
                return 1;
            }
            if ($a['tstamp'] > $b['tstamp']) {
                return -1;
            }
            return 0;
        });
        return $changeLog;
    }

    /**
     * Gets history and delete/insert data from sys_log and sys_history
     *
     * @param string $table DB table name
     * @param int $uid UID of record
     * @param int $lastHistoryEntry the highest entry to be fetched
     * @return array Array of history data of the record
     */
    public function getHistoryDataForRecord(string $table, int $uid, int $lastHistoryEntry = null): array
    {
        if (empty($GLOBALS['TCA'][$table]) || !$this->hasTableAccess($table) || !$this->hasPageAccess($table, $uid)) {
            return [];
        }

        $uid = $this->resolveElement($table, $uid);
        return $this->findEventsForRecord($table, $uid, ($this->maxSteps ?: null), $lastHistoryEntry);
    }

    /*******************************
     *
     * Various helper functions
     *
     *******************************/

    /**
     * Will traverse the field names in $dataArray and look in $GLOBALS['TCA'] if the fields are of types which cannot
     * be handled by the sys_history (that is currently group types with internal_type set to "file")
     *
     * @param string $table Table name
     * @param array $dataArray The data array
     * @return array The modified data array
     * @internal
     */
    protected function removeFilefields($table, $dataArray)
    {
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
        if ($GLOBALS['TCA'][$table]) {
            foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $config) {
                if ($config['config']['type'] === 'group' && $config['config']['internal_type'] === 'file') {
                    unset($dataArray[$field]);
                }
            }
        }
        return $dataArray;
    }

    /**
     * Convert input element reference to workspace version if any.
     *
     * @param string $table Table of input element
     * @param int $uid UID of record
     * @return int converted UID of record
     */
    protected function resolveElement(string $table, int $uid): int
    {
        if (isset($GLOBALS['TCA'][$table])
            && $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($this->getBackendUser()->workspace, $table, $uid, 'uid')) {
            $uid = $workspaceVersion['uid'];
        }
        return $uid;
    }

    /**
     * Resolve tablename + record uid from sys_history UID
     *
     * @param int $lastHistoryEntry
     * @return array
     */
    public function getHistoryEntry(int $lastHistoryEntry): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $record = $queryBuilder
            ->select('uid', 'tablename', 'recuid')
            ->from('sys_history')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($lastHistoryEntry, \PDO::PARAM_INT)))
            ->execute()
            ->fetch();

        if (empty($record)) {
            return [];
        }

        return $record;
    }

    /**
     * Queries the DB and prepares the results
     * Resolving a WSOL of the UID and checking permissions is explicitly not part of this method
     *
     * @param string $table
     * @param int $uid
     * @param int $limit
     * @param int $minimumUid
     * @return array
     */
    public function findEventsForRecord(string $table, int $uid, int $limit = 0, int $minimumUid = null): array
    {
        $events = [];
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            );

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($minimumUid) {
            $queryBuilder->andWhere($queryBuilder->expr()->gte('uid', $queryBuilder->createNamedParameter($minimumUid, \PDO::PARAM_INT)));
        }

        $result = $queryBuilder->orderBy('tstamp', 'DESC')->execute();
        while ($row = $result->fetch()) {
            $identifier = (int)$row['uid'];
            if ((int)$row['actiontype'] === RecordHistoryStore::ACTION_ADD || (int)$row['actiontype'] === RecordHistoryStore::ACTION_UNDELETE) {
                $row['action'] = 'insert';
            }
            if ((int)$row['actiontype'] === RecordHistoryStore::ACTION_DELETE) {
                $row['action'] = 'delete';
            }
            if (strpos($row['history_data'], 'a') === 0) {
                // legacy code
                $row['history_data'] = unserialize($row['history_data'], ['allowed_classes' => false]);
            } else {
                $row['history_data'] = json_decode($row['history_data'], true);
            }
            if (isset($row['history_data']['newRecord'])) {
                $row['newRecord'] = $row['history_data']['newRecord'];
            }
            if (isset($row['history_data']['oldRecord'])) {
                $row['oldRecord'] = $row['history_data']['oldRecord'];
            }
            $events[$identifier] = $row;
        }
        krsort($events);
        return $events;
    }

    /**
     * Determines whether user has access to a page.
     *
     * @param string $table
     * @param int $uid
     * @return bool
     */
    protected function hasPageAccess($table, $uid)
    {
        $uid = (int)$uid;

        if ($table === 'pages') {
            $pageId = $uid;
        } else {
            $record = BackendUtility::getRecord($table, $uid, '*', '', false);
            $pageId = $record['pid'];
        }

        if (!isset($this->pageAccessCache[$pageId])) {
            $isDeletedPage = false;
            if (isset($GLOBALS['TCA']['pages']['ctrl']['delete'])) {
                $deletedField = $GLOBALS['TCA']['pages']['ctrl']['delete'];
                $fields = 'pid,' . $deletedField;
                $pageRecord = BackendUtility::getRecord('pages', $pageId, $fields, '', false);
                $isDeletedPage = (bool)$pageRecord[$deletedField];
            }
            if ($isDeletedPage) {
                // The page is deleted, so we fake its uid to be the one of the parent page.
                // By doing so, the following API will use this id to traverse the rootline
                // and check whether it is in the users' web mounts.
                // We check however if the user has (or better had) access to the deleted page itself.
                // Since the only way we got here is by requesting the history of the parent page
                // we can be sure this parent page actually exists.
                $pageRecord['uid'] = $pageRecord['pid'];
                $this->pageAccessCache[$pageId] = $this->getBackendUser()->doesUserHaveAccess($pageRecord, Permission::PAGE_SHOW);
            } else {
                $this->pageAccessCache[$pageId] = BackendUtility::readPageAccess(
                    $pageId,
                    $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                );
            }
        }

        return $this->pageAccessCache[$pageId] !== false;
    }

    /**
     * Fetches GET/POST arguments and sanitizes the values for
     * the expected disposal. Invalid values will be converted
     * to an empty string.
     *
     * @param string $value the value of the element value
     * @return array|string|int
     */
    protected function sanitizeElementValue($value)
    {
        if ($value !== '' && !preg_match('#^[a-z0-9_.]+:[0-9]+$#i', $value)) {
            return '';
        }
        return $value;
    }

    /**
     * Evaluates if the rollback field is correct
     *
     * @param string $value
     * @return string
     */
    protected function sanitizeRollbackFieldsValue($value)
    {
        if ($value !== '' && !preg_match('#^[a-z0-9_.]+(:[0-9]+(:[a-z0-9_.]+)?)?$#i', $value)) {
            return '';
        }
        return $value;
    }

    /**
     * Determines whether user has access to a table.
     *
     * @param string $table
     * @return bool
     */
    protected function hasTableAccess($table)
    {
        return $this->getBackendUser()->check('tables_select', $table);
    }

    /**
     * Gets the current backend user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_history');
    }
}
