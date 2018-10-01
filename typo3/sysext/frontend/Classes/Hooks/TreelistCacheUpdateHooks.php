<?php
namespace TYPO3\CMS\Frontend\Hooks;

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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class that hooks into DataHandler and listens for updates to pages to update the
 * treelist cache
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class TreelistCacheUpdateHooks
{
    /**
     * Should not be manipulated from others except through the
     * configuration provided @see __construct()
     *
     * @var array
     */
    private $updateRequiringFields = [
        'pid',
        'php_tree_stop',
        'extendToSubpages'
    ];

    /**
     * Constructor, adds update requiring fields to the default ones
     */
    public function __construct()
    {
        // As enableFields can be set dynamically we add them here
        $pagesEnableFields = $GLOBALS['TCA']['pages']['ctrl']['enablecolumns'];
        foreach ($pagesEnableFields as $pagesEnableField) {
            $this->updateRequiringFields[] = $pagesEnableField;
        }
        $this->updateRequiringFields[] = $GLOBALS['TCA']['pages']['ctrl']['delete'];
        // Extension can add fields to the pages table that require an
        // update of the treelist cache, too; so we also add those
        // example: $TYPO3_CONF_VARS['BE']['additionalTreelistUpdateFields'] .= ',my_field';
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['BE']['additionalTreelistUpdateFields'])) {
            $additionalTreelistUpdateFields = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['BE']['additionalTreelistUpdateFields'], true);
            $this->updateRequiringFields = array_merge($this->updateRequiringFields, $additionalTreelistUpdateFields);
        }
    }

    /**
     * waits for DataHandler commands and looks for changed pages, if found further
     * changes take place to determine whether the cache needs to be updated
     *
     * @param string $status DataHandler operation status, either 'new' or 'update'
     * @param string $table The DB table the operation was carried out on
     * @param mixed $recordId The record's uid for update records, a string to look the record's uid up after it has been created
     * @param array $updatedFields Array of changed fields and their new values
     * @param DataHandler $dataHandler DataHandler parent object
     */
    public function processDatamap_afterDatabaseOperations($status, $table, $recordId, array $updatedFields, DataHandler $dataHandler)
    {
        if ($table === 'pages' && $this->requiresUpdate($updatedFields)) {
            $affectedPagePid = 0;
            $affectedPageUid = 0;
            if ($status === 'new') {
                // Detect new pages
                // Resolve the uid
                $affectedPageUid = $dataHandler->substNEWwithIDs[$recordId];
                $affectedPagePid = $updatedFields['pid'];
            } elseif ($status === 'update') {
                // Detect updated pages
                $affectedPageUid = $recordId;
                // When updating a page the pid is not directly available so we
                // need to retrieve it ourselves.
                $fullPageRecord = BackendUtility::getRecord($table, $recordId);
                $affectedPagePid = $fullPageRecord['pid'];
            }
            $clearCacheActions = $this->determineClearCacheActions($status, $updatedFields);
            $this->processClearCacheActions($affectedPageUid, $affectedPagePid, $updatedFields, $clearCacheActions);
        }
    }

    /**
     * Waits for DataHandler commands and looks for deleted pages or swapped pages, if found
     * further changes take place to determine whether the cache needs to be updated
     *
     * @param string $command The TCE command
     * @param string $table The record's table
     * @param int $recordId The record's uid
     * @param array $commandValue The commands value, typically an array with more detailed command information
     * @param DataHandler $dataHandler The DataHandler parent object
     */
    public function processCmdmap_postProcess($command, $table, $recordId, $commandValue, DataHandler $dataHandler)
    {
        $action = (is_array($commandValue) && isset($commandValue['action'])) ? (string)$commandValue['action'] : '';
        if ($table === 'pages' && ($command === 'delete' || ($command === 'version' && $action === 'swap'))) {
            $affectedRecord = BackendUtility::getRecord($table, $recordId, '*', '', false);
            $affectedPageUid = $affectedRecord['uid'];
            $affectedPagePid = $affectedRecord['pid'];

            // Faking the updated fields
            $updatedFields = [];
            if ($command === 'delete') {
                $updatedFields['deleted'] = 1;
            } else {
                // page was published to live (swapped)
                $updatedFields['t3ver_wsid'] = 0;
            }
            $clearCacheActions = $this->determineClearCacheActions(
                'update',
                $updatedFields
            );

            $this->processClearCacheActions($affectedPageUid, $affectedPagePid, $updatedFields, $clearCacheActions);
        }
    }

    /**
     * waits for DataHandler commands and looks for moved pages, if found further
     * changes take place to determine whether the cache needs to be updated
     *
     * @param string $table Table name of the moved record
     * @param int $recordId The record's uid
     * @param int $destinationPid The record's destination page id
     * @param array $movedRecord The record that moved
     * @param array $updatedFields Array of changed fields
     * @param DataHandler $dataHandler DataHandler parent object
     */
    public function moveRecord_firstElementPostProcess($table, $recordId, $destinationPid, array $movedRecord, array $updatedFields, DataHandler $dataHandler)
    {
        if ($table === 'pages' && $this->requiresUpdate($updatedFields)) {
            $affectedPageUid = $recordId;
            $affectedPageOldPid = $movedRecord['pid'];
            $affectedPageNewPid = $updatedFields['pid'];
            $clearCacheActions = $this->determineClearCacheActions('update', $updatedFields);
            // Clear treelist entries for old parent page
            $this->processClearCacheActions($affectedPageUid, $affectedPageOldPid, $updatedFields, $clearCacheActions);
            // Clear treelist entries for new parent page
            $this->processClearCacheActions($affectedPageUid, $affectedPageNewPid, $updatedFields, $clearCacheActions);
        }
    }

    /**
     * Waits for DataHandler commands and looks for moved pages, if found further
     * changes take place to determine whether the cache needs to be updated
     *
     * @param string $table Table name of the moved record
     * @param int $recordId The record's uid
     * @param int $destinationPid The record's destination page id
     * @param int $originalDestinationPid (negative) page id th page has been moved after
     * @param array $movedRecord The record that moved
     * @param array $updatedFields Array of changed fields
     * @param DataHandler $dataHandler DataHandler parent object
     */
    public function moveRecord_afterAnotherElementPostProcess($table, $recordId, $destinationPid, $originalDestinationPid, array $movedRecord, array $updatedFields, DataHandler $dataHandler)
    {
        if ($table === 'pages' && $this->requiresUpdate($updatedFields)) {
            $affectedPageUid = $recordId;
            $affectedPageOldPid = $movedRecord['pid'];
            $affectedPageNewPid = $updatedFields['pid'];
            $clearCacheActions = $this->determineClearCacheActions('update', $updatedFields);
            // Clear treelist entries for old parent page
            $this->processClearCacheActions($affectedPageUid, $affectedPageOldPid, $updatedFields, $clearCacheActions);
            // Clear treelist entries for new parent page
            $this->processClearCacheActions($affectedPageUid, $affectedPageNewPid, $updatedFields, $clearCacheActions);
        }
    }

    /**
     * Checks whether the change requires an update of the treelist cache
     *
     * @param array $updatedFields Array of changed fields
     * @return bool TRUE if the treelist cache needs to be updated, FALSE if no update to the cache is required
     */
    protected function requiresUpdate(array $updatedFields)
    {
        $requiresUpdate = false;
        $updatedFieldNames = array_keys($updatedFields);
        foreach ($updatedFieldNames as $updatedFieldName) {
            if (in_array($updatedFieldName, $this->updateRequiringFields, true)) {
                $requiresUpdate = true;
                break;
            }
        }
        return $requiresUpdate;
    }

    /**
     * Calls the cache maintenance functions according to the determined actions
     *
     * @param int $affectedPage uid of the affected page
     * @param int $affectedParentPage parent uid of the affected page
     * @param array $updatedFields Array of updated fields and their new values
     * @param array $actions Array of actions to carry out
     */
    protected function processClearCacheActions($affectedPage, $affectedParentPage, $updatedFields, array $actions)
    {
        $actionNames = array_keys($actions);
        foreach ($actionNames as $actionName) {
            switch ($actionName) {
                case 'allParents':
                    $this->clearCacheForAllParents($affectedParentPage);
                    break;
                case 'setExpiration':
                    // Only used when setting an end time for a page
                    $expirationTime = $updatedFields['endtime'];
                    $this->setCacheExpiration($affectedPage, $expirationTime);
                    break;
                case 'uidInTreelist':
                    $this->clearCacheWhereUidInTreelist($affectedPage);
                    break;
            }
        }
        // From time to time clean the cache from expired entries
        // (theoretically every 1000 calls)
        $randomNumber = rand(1, 1000);
        if ($randomNumber === 500) {
            $this->removeExpiredCacheEntries();
        }
    }

    /**
     * Clears the treelist cache for all parents of a changed page.
     * gets called after creating a new page and after moving a page
     *
     * @param int $affectedParentPage Parent page id of the changed page, the page to start clearing from
     */
    protected function clearCacheForAllParents($affectedParentPage)
    {
        $rootLine = BackendUtility::BEgetRootLine($affectedParentPage);
        $rootLineIds = [];
        foreach ($rootLine as $page) {
            if ($page['uid'] != 0) {
                $rootLineIds[] = $page['uid'];
            }
        }
        if (!empty($rootLineIds)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('cache_treelist');
            $queryBuilder
                ->delete('cache_treelist')
                ->where(
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($rootLineIds, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->execute();
        }
    }

    /**
     * Clears the treelist cache for all pages where the affected page is found
     * in the treelist
     *
     * @param int $affectedPage ID of the changed page
     */
    protected function clearCacheWhereUidInTreelist($affectedPage)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('cache_treelist');
        $queryBuilder
            ->delete('cache_treelist')
            ->where(
                $queryBuilder->expr()->inSet('treelist', $queryBuilder->quote($affectedPage))
            )
            ->execute();
    }

    /**
     * Sets an expiration time for all cache entries having the changed page in
     * the treelist.
     *
     * @param int $affectedPage Uid of the changed page
     * @param int $expirationTime
     */
    protected function setCacheExpiration($affectedPage, $expirationTime)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('cache_treelist');
        $queryBuilder
            ->update('cache_treelist')
            ->where(
                $queryBuilder->expr()->inSet('treelist', $queryBuilder->quote($affectedPage))
            )
            ->set('expires', $expirationTime)
            ->execute();
    }

    /**
     * Removes all expired treelist cache entries
     */
    protected function removeExpiredCacheEntries()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('cache_treelist');
        $queryBuilder
            ->delete('cache_treelist')
            ->where(
                $queryBuilder->expr()->lte(
                    'expires',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                )
            )
            ->execute();
    }

    /**
     * Determines what happened to the page record, this is necessary to clear
     * as less cache entries as needed later
     *
     * @param string $status DataHandler operation status, either 'new' or 'update'
     * @param array $updatedFields Array of updated fields
     * @return array List of actions that happened to the page record
     */
    protected function determineClearCacheActions($status, $updatedFields): array
    {
        $actions = [];
        if ($status === 'new') {
            // New page
            $actions['allParents'] = true;
        } elseif ($status === 'update') {
            $updatedFieldNames = array_keys($updatedFields);
            foreach ($updatedFieldNames as $updatedFieldName) {
                switch ($updatedFieldName) {
                    case 'pid':

                    case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['disabled']:

                    case $GLOBALS['TCA']['pages']['ctrl']['delete']:

                    case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['starttime']:

                    case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['fe_group']:

                    case 'extendToSubpages':

                    case 't3ver_wsid':

                    case 'php_tree_stop':
                        // php_tree_stop
                        $actions['allParents'] = true;
                        $actions['uidInTreelist'] = true;
                        break;
                    case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['endtime']:
                        // end time set/unset
                        // When setting an end time the cache entry needs an
                        // expiration time. When unsetting the end time the
                        // page must become listed in the treelist again.
                        if ($updatedFields['endtime'] > 0) {
                            $actions['setExpiration'] = true;
                        } else {
                            $actions['uidInTreelist'] = true;
                        }
                        break;
                    default:
                        if (in_array($updatedFieldName, $this->updateRequiringFields, true)) {
                            $actions['uidInTreelist'] = true;
                        }
                }
            }
        }
        return $actions;
    }
}
