<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Ingo Renner (ingo@typo3.org)
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
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tslib
 */
class tx_cms_treelistCacheUpdate {

		// should not be manipulated from others except through the
		// configuration provided @see __construct()
	private $updateRequiringFields = array(
		'pid',
		'php_tree_stop',
		'extendToSubpages'
	);

	/**
	 * constructor, adds update requiring fields to the default ones
	 *
	 */
	public function __construct() {

			// as enableFields can be set dynamically we add them here
		$pagesEnableFields = $GLOBALS['TCA']['pages']['ctrl']['enablecolumns'];
		foreach ($pagesEnableFields as $pagesEnableField) {
			$this->updateRequiringFields[] = $pagesEnableField;
		}
		$this->updateRequiringFields[] = $GLOBALS['TCA']['pages']['ctrl']['delete'];

			// extension can add fields to the pages table that require an
			// update of the treelist cache, too; so we also add those
			// example: $TYPO3_CONF_VARS['BE']['additionalTreelistUpdateFields'] .= ',my_field';
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['BE']['additionalTreelistUpdateFields'])) {
			$additionalTreelistUpdateFields = t3lib_div::trimExplode(
				',',
				$GLOBALS['TYPO3_CONF_VARS']['BE']['additionalTreelistUpdateFields'],
				true
			);

			$this->updateRequiringFields += $additionalTreelistUpdateFields;
		}

	}

	/**
	 * waits for TCEmain commands and looks for changed pages, if found further
	 * changes take place to determine whether the cache needs to be updated
	 *
	 * @param	string	TCEmain operation status, either 'new' or 'update'
	 * @param	string	the DB table the operation was carried out on
	 * @param	mixed	the record's uid for update records, a string to look the record's uid up after it has been created
	 * @param	array	array of changed fiels and their new values
	 * @param	t3lib_TCEmain	TCEmain parent object
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $recordId, array $updatedFields, t3lib_TCEmain $tceMain) {

		if ($table == 'pages' && $this->requiresUpdate($updatedFields)) {
			$affectedPagePid = 0;
			$affectedPageUid = 0;

			if ($status == 'new') {
					// detect new pages

					// resolve the uid
				$affectedPageUid = $tceMain->substNEWwithIDs[$recordId];
				$affectedPagePid = $updatedFields['pid'];
			} elseif ($status == 'update') {
					// detect updated pages

				$affectedPageUid = $recordId;

				/*
				 when updating a page the pid is not directly available so we
				 need to retrieve it ourselves.
				*/
				$fullPageRecord  = t3lib_BEfunc::getRecord($table, $recordId);
				$affectedPagePid = $fullPageRecord['pid'];
			}

			$clearCacheActions = $this->determineClearCacheActions(
				$status,
				$updatedFields
			);

			$this->processClearCacheActions(
				$affectedPageUid,
				$affectedPagePid,
				$updatedFields,
				$clearCacheActions
			);
		}
	}

	/**
	 * waits for TCEmain commands and looks for deleted pages, if found further
	 * changes take place to determine whether the cache needs to be updated
	 *
	 * @param	string	the TCE command
	 * @param	string	the record's table
	 * @param	integer	the record's uid
	 * @param	array	the commands value, typically an array with more detailed command information
	 * @param	t3lib_TCEmain	the TCEmain parent object
	 */
	public function processCmdmap_postProcess($command, $table, $recordId, $commandValue, t3lib_TCEmain $tceMain) {

		if ($table == 'pages' && $command == 'delete') {

			$deletedRecord = t3lib_BEfunc::getRecord(
				$table,
				$recordId,
				'*',
				'',
				false
			);

			$affectedPageUid = $deletedRecord['uid'];
			$affectedPagePid = $deletedRecord['pid'];
				// faking the updated fields
			$updatedFields   = array('deleted' => 1);

			$clearCacheActions = $this->determineClearCacheActions(
				'update',
				$updatedFields
			);

			$this->processClearCacheActions(
				$affectedPageUid,
				$affectedPagePid,
				$updatedFields,
				$clearCacheActions
			);
		}
	}

	/**
	 * waits for TCEmain commands and looks for moved pages, if found further
	 * changes take place to determine whether the cache needs to be updated
	 *
	 * @param	string	table name of the moved record
	 * @param	integer	the record's uid
	 * @param	integer	the record's destination page id
	 * @param	array	the record that moved
	 * @param	array	array of changed fields
	 * @param	t3lib_TCEmain	TCEmain parent object
	 */
	public function moveRecord_firstElementPostProcess($table, $recordId, $destinationPid, array $movedRecord, array $updatedFields, t3lib_TCEmain $tceMain) {

		if ($table == 'pages' && $this->requiresUpdate($updatedFields)) {

			$affectedPageUid    = $recordId;
			$affectedPageOldPid = $movedRecord['pid'];
			$affectedPageNewPid = $updatedFields['pid'];

			$clearCacheActions = $this->determineClearCacheActions(
				'update',
				$updatedFields
			);

				// clear treelist entries for old parent page
			$this->processClearCacheActions(
				$affectedPageUid,
				$affectedPageOldPid,
				$updatedFields,
				$clearCacheActions
			);
				// clear treelist entries for new parent page
			$this->processClearCacheActions(
				$affectedPageUid,
				$affectedPageNewPid,
				$updatedFields,
				$clearCacheActions
			);
		}
	}

	/**
	 * waits for TCEmain commands and looks for moved pages, if found further
	 * changes take place to determine whether the cache needs to be updated
	 *
	 * @param	string	table name of the moved record
	 * @param	integer	the record's uid
	 * @param	integer	the record's destination page id
	 * @param	integer	(negative) page id th page has been moved after
	 * @param	array	the record that moved
	 * @param	array	array of changed fields
	 * @param	t3lib_TCEmain	TCEmain parent object
	 */
	public function moveRecord_afterAnotherElementPostProcess($table, $recordId, $destinationPid, $originalDestinationPid, array $movedRecord, array $updatedFields, t3lib_TCEmain $tceMain) {

		if ($table == 'pages' && $this->requiresUpdate($updatedFields)) {

			$affectedPageUid    = $recordId;
			$affectedPageOldPid = $movedRecord['pid'];
			$affectedPageNewPid = $updatedFields['pid'];

			$clearCacheActions = $this->determineClearCacheActions(
				'update',
				$updatedFields
			);

				// clear treelist entries for old parent page
			$this->processClearCacheActions(
				$affectedPageUid,
				$affectedPageOldPid,
				$updatedFields,
				$clearCacheActions
			);
				// clear treelist entries for new parent page
			$this->processClearCacheActions(
				$affectedPageUid,
				$affectedPageNewPid,
				$updatedFields,
				$clearCacheActions
			);
		}
	}

	/**
	 * checks whether the change requires an update of the treelist cache
	 *
	 * @param	array	array of changed fields
	 * @return	boolean	true if the treelist cache needs to be updated, false if no update to the cache is required
	 */
	protected function requiresUpdate(array $updatedFields) {
		$requiresUpdate = false;

		$updatedFieldNames = array_keys($updatedFields);
		foreach ($updatedFieldNames as $updatedFieldName) {
			if (in_array($updatedFieldName, $this->updateRequiringFields)) {
				$requiresUpdate = true;
				break;
			}
		}

		return	$requiresUpdate;
	}

	/**
	 * calls the cache maintainance functions according to the determined actions
	 *
	 * @param	integer	uid of the affected page
	 * @param	integer	parent uid of the affected page
	 * @param	array	array of updated fields and their new values
	 * @param	array	array of actions to carry out
	 */
	protected function processClearCacheActions($affectedPage, $affectedParentPage, $updatedFields, array $actions) {
		$actionNames = array_keys($actions);
		foreach ($actionNames as $actionName) {
			switch ($actionName) {
				case 'allParents':
					$this->clearCacheForAllParents($affectedParentPage);
					break;
				case 'setExpiration':
						// only used when setting an end time for a page
					$expirationTime = $updatedFields['endtime'];
					$this->setCacheExpiration($affectedPage, $expirationTime);
					break;
				case 'uidInTreelist':
					$this->clearCacheWhereUidInTreelist($affectedPage);
					break;
			}
		}

			// from time to time clean the cache from expired entries
			// (theoretically every 1000 calls)
		$randomNumber = rand(1, 1000);
		if ($randomNumber == 500) {
			$this->removeExpiredCacheEntries();
		}
	}

	/**
	 * clears the treelist cache for all parents of a changed page.
	 * gets called after creating a new page and after moving a page
	 *
	 * @param	integer	parent page id of the changed page, the page to start clearing from
	 */
	protected function clearCacheForAllParents($affectedParentPage) {
		$rootline = t3lib_BEfunc::BEgetRootLine($affectedParentPage);

		$rootlineIds = array();
		foreach ($rootline as $page) {
			if($page['uid'] != 0) {
				$rootlineIds[] = $page['uid'];
			}
		}

		if (!empty($rootlineIds)) {
			$rootlineIdsImploded = implode(',', $rootlineIds);

			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				'cache_treelist',
				'pid IN(' . $rootlineIdsImploded . ')'
			);
		}
	}

	/**
	 * clears the treelist cache for all pages where the affected page is found
	 * in the treelist
	 *
	 * @param	integer	Id of the changed page
	 */
	protected function clearCacheWhereUidInTreelist($affectedPage) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'cache_treelist',
			$GLOBALS['TYPO3_DB']->listQuery(
				'treelist',
				$affectedPage,
				'cache_treelist'
			)
		);
	}

	/**
	 * sets an expiration time for all cache entries having the changed page in
	 * the treelist.
	 *
	 * @param	integer	uid of the changed page
	 */
	protected function setCacheExpiration($affectedPage, $expirationTime) {

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'cache_treelist',
			$GLOBALS['TYPO3_DB']->listQuery(
				'treelist',
				$affectedPage,
				'cache_treelist'
			),
			array(
				'expires' => $expirationTime
			)
		);
	}

	/**
	 * removes all expired treelist cache entries
	 *
	 */
	protected function removeExpiredCacheEntries() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'cache_treelist',
			'expires <= ' . $GLOBALS['EXEC_TIME']
		);
	}

	/**
	 * determines what happened to the page record, this is necessary to clear
	 * as less cache entries as needed later
	 *
	 * @param	string	TCEmain operation status, either 'new' or 'update'
	 * @param	array	array of updated fields
	 * @return	string	list of actions that happened to the page record
	 */
	protected function determineClearCacheActions($status, $updatedFields) {
		$actions = array();

		if ($status == 'new') {
				// new page
			$actions['allParents'] = true;
		} elseif ($status == 'update') {
			$updatedFieldNames = array_keys($updatedFields);

			foreach ($updatedFieldNames as $updatedFieldName) {
				switch ($updatedFieldName) {
					case 'pid':
							// page moved
					case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['disabled']:
							// page hidden / unhidden
					case $GLOBALS['TCA']['pages']['ctrl']['delete']:
							// page deleted / undeleted
					case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['starttime']:
							/*
							 start time set/unset
							 Doesn't matter whether it was set or unset, in both
							 cases the cache needs to be cleared. When setting a
							 start time the page must be removed from the
							 treelist. When unsetting the start time it must
							 become listed in the tree list again.
							*/
					case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['fe_group']:
							// changes to FE user group
					case 'extendToSubpages':
							// extendToSubpages set (apply FE access restrictions to subpages)
					case 'php_tree_stop':
							// php_tree_stop
						$actions['allParents'] = TRUE;
						$actions['uidInTreelist'] = true;
						break;
					case $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['endtime']:
							/*
						 	 end time set/unset
							 When setting an end time the cache entry needs an
							 expiration time. When unsetting the end time the
							 page must become listed in the treelist again.
							*/
						if($updatedFields['endtime'] > 0) {
							$actions['setExpiration'] = true;
						} else {
							$actions['uidInTreelist'] = true;
						}
						break;
					default:
						if (in_array($updatedFieldName, $this->updateRequiringFields)) {
							$actions['uidInTreelist'] = true;
						}
				}
			}
		}

		return $actions;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/hooks/class.tx_cms_treelistcacheupdate.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/hooks/class.tx_cms_treelistcacheupdate.php']);
}

?>