<?php
namespace TYPO3\CMS\Frontend\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Ingo Renner (ingo@typo3.org)
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
 * Class that hooks into TCEmain and listens for updates to pages to update the
 * treelist cache
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class TreelistCacheUpdateHooks {

	// Should not be manipulated from others except through the
	// configuration provided @see __construct()
	private $updateRequiringFields = array(
		'pid',
		'php_tree_stop',
		'extendToSubpages'
	);

	/**
	 * Constructor, adds update requiring fields to the default ones
	 */
	public function __construct() {
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
			$additionalTreelistUpdateFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['BE']['additionalTreelistUpdateFields'], TRUE);
			$this->updateRequiringFields += $additionalTreelistUpdateFields;
		}
	}

	/**
	 * waits for TCEmain commands and looks for changed pages, if found further
	 * changes take place to determine whether the cache needs to be updated
	 *
	 * @param string $status TCEmain operation status, either 'new' or 'update'
	 * @param string $table The DB table the operation was carried out on
	 * @param mixed $recordId The record's uid for update records, a string to look the record's uid up after it has been created
	 * @param array $updatedFields Array of changed fiels and their new values
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain TCEmain parent object
	 * @return void
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $recordId, array $updatedFields, \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain) {
		if ($table == 'pages' && $this->requiresUpdate($updatedFields)) {
			$affectedPagePid = 0;
			$affectedPageUid = 0;
			if ($status == 'new') {
				// Detect new pages
				// Resolve the uid
				$affectedPageUid = $tceMain->substNEWwithIDs[$recordId];
				$affectedPagePid = $updatedFields['pid'];
			} elseif ($status == 'update') {
				// Detect updated pages
				$affectedPageUid = $recordId;
				// When updating a page the pid is not directly available so we
				// need to retrieve it ourselves.
				$fullPageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $recordId);
				$affectedPagePid = $fullPageRecord['pid'];
			}
			$clearCacheActions = $this->determineClearCacheActions($status, $updatedFields);
			$this->processClearCacheActions($affectedPageUid, $affectedPagePid, $updatedFields, $clearCacheActions);
		}
	}

	/**
	 * waits for TCEmain commands and looks for deleted pages, if found further
	 * changes take place to determine whether the cache needs to be updated
	 *
	 * @param string $command The TCE command
	 * @param string $table The record's table
	 * @param integer $recordId The record's uid
	 * @param array $commandValue The commands value, typically an array with more detailed command information
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain The TCEmain parent object
	 * @return void
	 */
	public function processCmdmap_postProcess($command, $table, $recordId, $commandValue, \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain) {
		if ($table == 'pages' && $command == 'delete') {
			$deletedRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $recordId, '*', '', FALSE);
			$affectedPageUid = $deletedRecord['uid'];
			$affectedPagePid = $deletedRecord['pid'];
			// Faking the updated fields
			$updatedFields = array('deleted' => 1);
			$clearCacheActions = $this->determineClearCacheActions('update', $updatedFields);
			$this->processClearCacheActions($affectedPageUid, $affectedPagePid, $updatedFields, $clearCacheActions);
		}
	}

	/**
	 * waits for TCEmain commands and looks for moved pages, if found further
	 * changes take place to determine whether the cache needs to be updated
	 *
	 * @param string $table Table name of the moved record
	 * @param integer $recordId The record's uid
	 * @param integer $destinationPid The record's destination page id
	 * @param array $movedRecord The record that moved
	 * @param array $updatedFields Array of changed fields
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain TCEmain parent object
	 * @return void
	 */
	public function moveRecord_firstElementPostProcess($table, $recordId, $destinationPid, array $movedRecord, array $updatedFields, \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain) {
		if ($table == 'pages' && $this->requiresUpdate($updatedFields)) {
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
	 * Waits for TCEmain commands and looks for moved pages, if found further
	 * changes take place to determine whether the cache needs to be updated
	 *
	 * @param string $table Table name of the moved record
	 * @param integer $recordId The record's uid
	 * @param integer $destinationPid The record's destination page id
	 * @param integer $originalDestinationPid (negative) page id th page has been moved after
	 * @param array $movedRecord The record that moved
	 * @param array $updatedFields Array of changed fields
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain TCEmain parent object
	 * @return void
	 */
	public function moveRecord_afterAnotherElementPostProcess($table, $recordId, $destinationPid, $originalDestinationPid, array $movedRecord, array $updatedFields, \TYPO3\CMS\Core\DataHandling\DataHandler $tceMain) {
		if ($table == 'pages' && $this->requiresUpdate($updatedFields)) {
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
	 * @return boolean TRUE if the treelist cache needs to be updated, FALSE if no update to the cache is required
	 */
	protected function requiresUpdate(array $updatedFields) {
		$requiresUpdate = FALSE;
		$updatedFieldNames = array_keys($updatedFields);
		foreach ($updatedFieldNames as $updatedFieldName) {
			if (in_array($updatedFieldName, $this->updateRequiringFields)) {
				$requiresUpdate = TRUE;
				break;
			}
		}
		return $requiresUpdate;
	}

	/**
	 * Calls the cache maintainance functions according to the determined actions
	 *
	 * @param integer $affectedPage uid of the affected page
	 * @param integer $affectedParentPage parent uid of the affected page
	 * @param array $updatedFields Array of updated fields and their new values
	 * @param array $actions Array of actions to carry out
	 * @return void
	 */
	protected function processClearCacheActions($affectedPage, $affectedParentPage, $updatedFields, array $actions) {
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
		if ($randomNumber == 500) {
			$this->removeExpiredCacheEntries();
		}
	}

	/**
	 * Clears the treelist cache for all parents of a changed page.
	 * gets called after creating a new page and after moving a page
	 *
	 * @param integer $affectedParentPage Parent page id of the changed page, the page to start clearing from
	 * @return void
	 */
	protected function clearCacheForAllParents($affectedParentPage) {
		$rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($affectedParentPage);
		$rootlineIds = array();
		foreach ($rootline as $page) {
			if ($page['uid'] != 0) {
				$rootlineIds[] = $page['uid'];
			}
		}
		if (!empty($rootlineIds)) {
			$rootlineIdsImploded = implode(',', $rootlineIds);
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_treelist', 'pid IN(' . $rootlineIdsImploded . ')');
		}
	}

	/**
	 * Clears the treelist cache for all pages where the affected page is found
	 * in the treelist
	 *
	 * @param integer $affectedPage ID of the changed page
	 * @return void
	 */
	protected function clearCacheWhereUidInTreelist($affectedPage) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_treelist', $GLOBALS['TYPO3_DB']->listQuery('treelist', $affectedPage, 'cache_treelist'));
	}

	/**
	 * Sets an expiration time for all cache entries having the changed page in
	 * the treelist.
	 *
	 * @param integer $affectedPage Uid of the changed page
	 * @param integer $expirationTime
	 * @return void
	 */
	protected function setCacheExpiration($affectedPage, $expirationTime) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cache_treelist', $GLOBALS['TYPO3_DB']->listQuery('treelist', $affectedPage, 'cache_treelist'), array(
			'expires' => $expirationTime
		));
	}

	/**
	 * Removes all expired treelist cache entries
	 *
	 * @return void
	 */
	protected function removeExpiredCacheEntries() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_treelist', 'expires <= ' . $GLOBALS['EXEC_TIME']);
	}

	/**
	 * Determines what happened to the page record, this is necessary to clear
	 * as less cache entries as needed later
	 *
	 * @param string $status TCEmain operation status, either 'new' or 'update'
	 * @param array $updatedFields Array of updated fields
	 * @return string List of actions that happened to the page record
	 */
	protected function determineClearCacheActions($status, $updatedFields) {
		$actions = array();
		if ($status == 'new') {
			// New page
			$actions['allParents'] = TRUE;
		} elseif ($status == 'update') {
			$updatedFieldNames = array_keys($updatedFields);
			foreach ($updatedFieldNames as $updatedFieldName) {
				switch ($updatedFieldName) {
				case 'pid':

				case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['disabled']:

				case $GLOBALS['TCA']['pages']['ctrl']['delete']:

				case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['starttime']:

				case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['fe_group']:

				case 'extendToSubpages':

				case 'php_tree_stop':
					// php_tree_stop
					$actions['allParents'] = TRUE;
					$actions['uidInTreelist'] = TRUE;
					break;
				case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['endtime']:
					// end time set/unset
					// When setting an end time the cache entry needs an
					// expiration time. When unsetting the end time the
					// page must become listed in the treelist again.
					if ($updatedFields['endtime'] > 0) {
						$actions['setExpiration'] = TRUE;
					} else {
						$actions['uidInTreelist'] = TRUE;
					}
					break;
				default:
					if (in_array($updatedFieldName, $this->updateRequiringFields)) {
						$actions['uidInTreelist'] = TRUE;
					}
				}
			}
		}
		return $actions;
	}

}


?>