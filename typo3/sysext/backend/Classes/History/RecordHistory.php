<?php

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

namespace TYPO3\CMS\Backend\History;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for fetching the history entries of a record (and if it is a page, its sub elements
 * as well)
 */
class RecordHistory
{
    /**
     * Maximum number of sys_history steps to show.
     */
    protected int $maxSteps = 20;

    /**
     * On a pages table - show sub elements as well.
     */
    protected bool $showSubElements = true;

    /**
     * Element reference, syntax [tablename]:[uid]
     */
    protected string $element = '';

    /**
     * sys_history uid which is selected
     */
    protected int $lastHistoryEntry = 0;

    /**
     * Internal cache
     */
    protected array $pageAccessCache = [];

    /**
     * Constructor to define which element to work on - can be overridden with "setLastHistoryEntryNumber"
     *
     * @param string $element in the form of "tablename:uid"
     */
    public function __construct($element = '')
    {
        $this->element = $this->sanitizeElementValue((string)$element);
    }

    /**
     * If a specific history entry is selected, then the relevant element is resolved for that.
     */
    public function setLastHistoryEntryNumber(int $lastHistoryEntry): void
    {
        $this->lastHistoryEntry = $lastHistoryEntry;
        $this->updateCurrentElement();
    }

    public function getLastHistoryEntryNumber(): int
    {
        return $this->lastHistoryEntry;
    }

    /**
     * Define the maximum amount of history entries to be shown. Beware of side-effects when using
     * "showSubElements" as well.
     */
    public function setMaxSteps(int $maxSteps): void
    {
        $this->maxSteps = $maxSteps;
    }

    /**
     * Defines to show the history of a specific record or its subelements (when it's a page)
     * as well.
     */
    public function setShowSubElements(bool $showSubElements): void
    {
        $this->showSubElements = $showSubElements;
    }

    /**
     * Creates change log including sub-elements
     */
    public function getChangeLog(): array
    {
        if (!empty($this->element)) {
            [$table, $recordUid] = explode(':', $this->element);
            return $this->getHistoryData($table, (int)$recordUid, $this->showSubElements, $this->lastHistoryEntry);
        }
        return [];
    }

    /**
     * An array (0 = tablename, 1 = uid) or empty array if no element is set
     */
    public function getElementInformation(): array
    {
        return !empty($this->element) ? explode(':', $this->element) : [];
    }

    /**
     * @return string named "tablename:uid"
     */
    public function getElementString(): string
    {
        return $this->element;
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
    public function getDiff(array $changeLog): array
    {
        $insertsDeletes = [];
        $newArr = [];
        $differences = [];
        // traverse changelog array
        foreach ($changeLog as $value) {
            $field = $value['tablename'] . ':' . $value['recuid'];
            // inserts / deletes
            if ((int)$value['actiontype'] !== RecordHistoryStore::ACTION_MODIFY) {
                if (!isset($insertsDeletes[$field])) {
                    $insertsDeletes[$field] = 0;
                }
                ($value['action'] ?? '') === 'insert' ? $insertsDeletes[$field]++ : $insertsDeletes[$field]--;
                // unset not needed fields
                if ($insertsDeletes[$field] === 0) {
                    unset($insertsDeletes[$field]);
                }
            } elseif (!isset($newArr[$field])) {
                $newArr[$field] = $value['newRecord'];
                $differences[$field] = $value['oldRecord'];
            } else {
                $differences[$field] = array_merge($differences[$field], $value['oldRecord']);
            }
        }
        // remove entries where there were no changes effectively
        foreach ($newArr as $record => $value) {
            foreach ($value as $key => $innerVal) {
                if ($newArr[$record][$key] === $differences[$record][$key]) {
                    unset($newArr[$record][$key], $differences[$record][$key]);
                }
            }
            if (empty($newArr[$record]) && empty($differences[$record])) {
                unset($newArr[$record], $differences[$record]);
            }
        }
        return [
            'newData' => $newArr,
            'oldData' => $differences,
            'insertsDeletes' => $insertsDeletes,
        ];
    }

    /**
     * Fetches the history data of a record + includes subelements if this is from a page
     *
     * @param int $lastHistoryEntry the highest entry to be evaluated
     */
    protected function getHistoryData(string $table, int $uid, ?bool $includeSubEntries = null, ?int $lastHistoryEntry = null): array
    {
        $historyDataForRecord = $this->getHistoryDataForRecord($table, $uid, $lastHistoryEntry);
        // get history of tables of this page and merge it into changelog
        if ($table === 'pages' && $includeSubEntries && $this->hasPageAccess('pages', $uid)) {
            foreach ($this->getTcaSchemaFactory()->all()->getNames() as $tablename) {
                // check if there are records on the page
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tablename);
                $queryBuilder->getRestrictions()->removeAll();

                $result = $queryBuilder
                    ->select('uid')
                    ->from($tablename)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                        )
                    )
                    ->executeQuery();
                while ($row = $result->fetchAssociative()) {
                    // if there is history data available, merge it into changelog
                    $newChangeLog = $this->getHistoryDataForRecord($tablename, $row['uid'], $lastHistoryEntry);
                    foreach ($newChangeLog as $key => $newChangeLogEntry) {
                        $historyDataForRecord[$key] = $newChangeLogEntry;
                    }
                }
            }
        }
        usort($historyDataForRecord, static function (array $a, array $b): int {
            if ($a['tstamp'] < $b['tstamp']) {
                return 1;
            }
            if ($a['tstamp'] > $b['tstamp']) {
                return -1;
            }
            return 0;
        });
        return $historyDataForRecord;
    }

    /**
     * Gets history and delete/insert data from sys_log and sys_history
     *
     * @param string $table DB table name
     * @param int $uid UID of record
     * @param int|null $lastHistoryEntry the highest entry to be fetched
     * @return array Array of history data of the record
     * @internal
     */
    public function getHistoryDataForRecord(string $table, int $uid, ?int $lastHistoryEntry = null): array
    {
        if (!$this->getTcaSchemaFactory()->has($table) || !$this->hasTableAccess($table) || !$this->hasPageAccess($table, $uid)) {
            return [];
        }

        $uid = $this->resolveElement($table, $uid);
        return $this->findEventsForRecord($table, $uid, ($this->maxSteps ?: 0), $lastHistoryEntry);
    }

    /**
     * Get the user uid of the user who deleted the record for the given table
     */
    public function getUserIdFromDeleteActionForRecord(string $table, int $uid): int
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('userid')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($table)
                ),
                $queryBuilder->expr()->eq(
                    'usertype',
                    $queryBuilder->createNamedParameter('BE')
                ),
                $queryBuilder->expr()->eq(
                    'recuid',
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'actiontype',
                    $queryBuilder->createNamedParameter(RecordHistoryStore::ACTION_DELETE, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        return (int)$queryBuilder->executeQuery()->fetchOne();
    }

    /*******************************
     *
     * Various helper functions
     *
     *******************************/

    /**
     * Convert input element reference to workspace version if any.
     *
     * @param string $table Table of input element
     * @param int $uid UID of record
     * @return int converted UID of record
     */
    protected function resolveElement(string $table, int $uid): int
    {
        if (!$this->getTcaSchemaFactory()->has($table)) {
            return $uid;
        }
        $workspaceVersion = BackendUtility::getWorkspaceVersionOfRecord($this->getBackendUser()->workspace, $table, $uid, 'uid');
        if ($workspaceVersion) {
            $uid = $workspaceVersion['uid'];
        }
        return $uid;
    }

    /**
     * Resolve tablename + record uid from sys_history UID
     */
    protected function getHistoryEntry(int $lastHistoryEntry): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $record = $queryBuilder
            ->select('uid', 'tablename', 'recuid')
            ->from('sys_history')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($lastHistoryEntry, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        if (empty($record)) {
            return [];
        }

        return $record;
    }

    /**
     * Fetches the history entry for an ADD/creation action for a specific record.
     */
    public function getCreationInformationForRecord(string $table, array $record): ?array
    {
        $queryBuilder = $this->getQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($record['uid'], Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('actiontype', $queryBuilder->createNamedParameter(RecordHistoryStore::ACTION_ADD, Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        return $result ?: null;
    }

    /**
     * Queries the DB and prepares the results
     * Resolving a WSOL of the UID and checking permissions is explicitly not part of this method
     */
    public function findEventsForRecord(string $table, int $uid, int $limit = 0, ?int $minimumUid = null): array
    {
        $backendUser = $this->getBackendUser();
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter($table)),
                $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            );
        if ($backendUser->workspace === 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('workspace', 0)
            );
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('workspace', 0),
                    $queryBuilder->expr()->eq('workspace', $queryBuilder->createNamedParameter($backendUser->workspace, Connection::PARAM_INT))
                )
            );
        }
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($minimumUid) {
            $queryBuilder->andWhere($queryBuilder->expr()->gte('uid', $queryBuilder->createNamedParameter($minimumUid, Connection::PARAM_INT)));
        }

        return $this->prepareEventDataFromQueryBuilder($queryBuilder);
    }

    public function findEventsForCorrelation(string $correlationId): array
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('sys_history')
            ->where($queryBuilder->expr()->eq('correlation_id', $queryBuilder->createNamedParameter($correlationId)));

        return $this->prepareEventDataFromQueryBuilder($queryBuilder);
    }

    protected function prepareEventDataFromQueryBuilder(QueryBuilder $queryBuilder): array
    {
        $events = [];
        $result = $queryBuilder->orderBy('tstamp', 'DESC')->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $identifier = (int)$row['uid'];
            $actionType = (int)$row['actiontype'];
            if ($actionType === RecordHistoryStore::ACTION_ADD || $actionType === RecordHistoryStore::ACTION_UNDELETE) {
                $row['action'] = 'insert';
            }
            if ($actionType === RecordHistoryStore::ACTION_DELETE) {
                $row['action'] = 'delete';
            }
            if ($row['history_data'] === null) {
                $events[$identifier] = $row;
                continue;
            }
            if (str_starts_with($row['history_data'], 'a')) {
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
     */
    protected function hasPageAccess(string $table, int $uid): bool
    {
        $pageRecord = null;

        if ($table === 'pages') {
            $pageId = $uid;
        } else {
            $record = BackendUtility::getRecord($table, $uid, '*', '', false);
            $pageId = ($record['pid'] ?? 0);
        }

        $schema = $this->getTcaSchema($table);

        if ($pageId === 0 && ($schema?->getCapability(TcaSchemaCapability::RestrictionRootLevel)->shallIgnoreRootLevelRestriction() ?? false)) {
            return true;
        }

        if (!isset($this->pageAccessCache[$pageId])) {
            $isDeletedPage = false;
            $pageSchema = $this->getTcaSchemaFactory()->get('pages');
            if ($pageSchema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                $deletedField = $pageSchema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
                $pageRecord = BackendUtility::getRecord('pages', $pageId, '*', '', false);
                $isDeletedPage = (bool)($pageRecord[$deletedField] ?? false);
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
     * Sanitizes the values for the expected disposal.
     * Invalid values will be converted to an empty string.
     *
     * @param string $value the value of the element value
     */
    protected function sanitizeElementValue(string $value): string
    {
        if ($value !== '' && !preg_match('#^[a-z\d_.]+:[\d]+$#i', $value)) {
            return '';
        }
        return $value;
    }

    /**
     * Determines whether user has access to a table.
     */
    protected function hasTableAccess(string $table): bool
    {
        return $this->getBackendUser()->check('tables_select', $table);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_history');
    }

    protected function getTcaSchemaFactory(): TcaSchemaFactory
    {
        return GeneralUtility::makeInstance(TcaSchemaFactory::class);
    }

    protected function getTcaSchema(string $table): ?TcaSchema
    {
        $schemaFactory = $this->getTcaSchemaFactory();
        if ($schemaFactory->has($table)) {
            return $schemaFactory->get($table);
        }
        return null;
    }

    protected function updateCurrentElement(): void
    {
        if ($this->lastHistoryEntry) {
            $elementData = $this->getHistoryEntry($this->lastHistoryEntry);
            if (!empty($elementData) && empty($this->element)) {
                $this->element = $elementData['tablename'] . ':' . $elementData['recuid'];
            }
        }
    }
}
