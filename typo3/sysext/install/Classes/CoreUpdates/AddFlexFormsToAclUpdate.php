<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Kai Vogel (kai.vogel(at)speedprogs.de)
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
 * Contains the update for group access lists, adds all excludeable FlexForm fields. Used by the update wizard in the install tool.
 *
 * @author Kai Vogel <kai.vogel(at)speedprogs.de>
 */
class AddFlexFormsToAclUpdate extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	protected $title = 'Add Excludable FlexForm Fields to Group Access Lists';

	/**
	 * Checks if FlexForm fields are missing in group access lists.
	 *
	 * @param string &$description The description for the update
	 * @return boolean Whether an update is required (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$description = '
			<br />TYPO3 4.5 introduced the possibility to exclude FlexForm fields like normal fields in group access control lists (ACL).
			All excludeable fields will be hidden for non-admins if you do not add them to the ACL of each user group manually or with
			this update wizard.
		';
		// Check access lists
		if (!$this->getGroupAddFields()) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Get user confirmation
	 *
	 * @param string $inputPrefix The input prefix, all names of form fields have to start with this
	 * @return string HTML output
	 */
	public function getUserInput($inputPrefix) {
		$description = '
			<br />You are about to update group access control lists to include excludable FlexForm fields. Each backend group will be checked
			and only those that already have entries in the access control lists will be updated.
		';
		return $description;
	}

	/**
	 * Performs the action of the UpdateManager
	 *
	 * @param array &$dbQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean Whether update was successful or not
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		// Get additional FlexForm fields for group access lists
		$addFields = $this->getGroupAddFields();
		if (empty($addFields)) {
			$customMessages = 'No missing FlexForm fields found!';
			return FALSE;
		}
		return $this->updateGroupAccessLists($addFields, $dbQueries, $customMessages);
	}

	/**
	 * Get all FlexForm fields which must be added to group access lists
	 *
	 * @return array Additional FlexForm fields for ACL
	 */
	protected function getGroupAddFields() {
		$addFields = array();
		$contentTable = !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable']) ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['contentTable'] : 'tt_content';
		// Initialize TCA if not loaded yet
		if (empty($GLOBALS['TCA'])) {
			$this->pObj->includeTCA();
		}
		// Get all access lists from groups which are allowed to select or modify the content-table
		$search = $GLOBALS['TYPO3_DB']->escapeStrForLike($contentTable, 'be_groups');
		$where = 'deleted = 0 AND non_exclude_fields IS NOT NULL AND non_exclude_fields != ""';
		$where .= ' AND (tables_select LIKE "%' . $search . '%" OR tables_modify LIKE "%' . $search . '%")';
		$accessLists = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, non_exclude_fields', 'be_groups', $where);
		if (empty($accessLists)) {
			return array();
		}
		// Get all excludeable FlexForm fields from content-table
		$flexExcludeFields = array();
		$flexFormArray = \TYPO3\CMS\Backend\Utility\BackendUtility::getRegisteredFlexForms($contentTable);
		if (!empty($flexFormArray) && is_array($flexFormArray)) {
			foreach ($flexFormArray as $tableField => $flexForms) {
				// Get all sheets
				foreach ($flexForms as $flexFormIdentifier => $flexFormConfig) {
					// Get all excludeable fields in sheet
					foreach ($flexFormConfig['ds']['sheets'] as $sheetName => $sheet) {
						if (empty($sheet['ROOT']['el']) || !is_array($sheet['ROOT']['el'])) {
							continue;
						}
						foreach ($sheet['ROOT']['el'] as $fieldName => $field) {
							if (empty($field['TCEforms']['exclude'])) {
								continue;
							}
							$flexExcludeFields[] = $contentTable . ':' . $tableField . ';' . $flexFormIdentifier . ';' . $sheetName . ';' . $fieldName;
						}
					}
				}
			}
		}
		if (empty($flexExcludeFields)) {
			return array();
		}
		// Get FlexForm fields from access lists
		foreach ($accessLists as $accessList) {
			$nonExcludeFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $accessList['non_exclude_fields']);
			if (empty($nonExcludeFields)) {
				continue;
			}
			// Add FlexForm fields only if the field was not already selected by a user
			$nonExcludeFields = array_diff($flexExcludeFields, $nonExcludeFields);
			if (!empty($nonExcludeFields) && $nonExcludeFields == $flexExcludeFields) {
				$addFields[$accessList['uid']] = $nonExcludeFields;
			}
		}
		return $addFields;
	}

	/**
	 * Update Backend user groups, add FlexForm fields to access list
	 *
	 * @param array $addFields All missing FlexForm fields by groups
	 * @param array &$dbQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean Whether update was successful or not
	 */
	protected function updateGroupAccessLists(array $addFields, array &$dbQueries, &$customMessages) {
		foreach ($addFields as $groupUID => $flexExcludeFields) {
			// First get current fields
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('non_exclude_fields', 'be_groups', 'uid=' . (int) $groupUID);
			if (!isset($result['non_exclude_fields'])) {
				continue;
			}
			$nonExcludeFields = $result['non_exclude_fields'];
			// Now add new ones
			$flexExcludeFields = implode(',', $flexExcludeFields);
			$nonExcludeFields = trim($nonExcludeFields . ',' . $flexExcludeFields, ', ');
			// Finally override with new fields
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_groups', 'uid=' . (int) $groupUID, array('non_exclude_fields' => $nonExcludeFields));
			// Get last executed query
			$dbQueries[] = str_replace(chr(10), ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);
			// Check for errors
			if ($GLOBALS['TYPO3_DB']->sql_error()) {
				$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
				return FALSE;
			}
		}
		return TRUE;
	}

}


?>