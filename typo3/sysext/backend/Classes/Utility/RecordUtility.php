<?php
namespace TYPO3\CMS\Backend\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Oliver Hader <oliver.hader@typo3.org>
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
 * Utility class for checking record states.
 */
class RecordUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Contains pages per workspace that have
	 * been processed already combined with the
	 * result, whether there are versions or not.
	 *
	 * @var array
	 */
	protected $versionsPerPage = array();

	/**
	 * Contains tables that have been processed
	 * already for a particular workspace.
	 *
	 * @var array
	 */
	protected $versionsTables = array();

	/**
	 * Gets an instance of this singleton.
	 *
	 * @return \TYPO3\CMS\Backend\Utility\RecordUtility
	 */
	static public function getInstance() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Backend\\Utility\\RecordUtility'
		);
	}

	/**
	 * Determines whether a page in a particular workspace
	 * has versions. If no workspace is given, the current
	 * active workspace will be used.
	 *
	 * @param integer $pageId
	 * @param integer $workspaceId
	 * @return boolean
	 */
	public function hasVersions($pageId, $workspaceId = NULL) {
		if ($workspaceId === NULL) {
			$workspaceId = $this->getBackendUser()->workspace;
		}

		$pageId = (int) $pageId;
		$workspaceId = (int) $workspaceId;

		if ($workspaceId === 0) {
			return FALSE;
		}

		if (isset($this->versionsPerPage[$pageId][$workspaceId])) {
			return $this->versionsPerPage[$pageId][$workspaceId];
		}

		foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
			if (empty($tableConfiguration['ctrl']['versioningWS']) || $tableName === 'pages' ||
				isset($this->versionsTables[$workspaceId]) && in_array($tableName, $this->versionsTables[$workspaceId])
			) {
				continue;
			}

			if (!isset($this->versionsTables[$workspaceId])) {
				$this->versionsTables[$workspaceId] = array();
			}

			$this->versionsTables[] = $tableName;

			$records = $this->getDatabase()->exec_SELECTgetRows(
				'B.uid as live_uid, B.pid as live_pid, A.uid as offline_uid',
				$tableName . ' A,' . $tableName . ' B',
				'A.pid=-1' . ' AND A.t3ver_wsid=' . $workspaceId . ' AND A.t3ver_oid=B.uid' .
					BackendUtility::deleteClause($tableName, 'A') . BackendUtility::deleteClause($tableName, 'B'),
				'live_pid'
			);

			if (is_array($records)) {
				foreach ($records as $record) {
					$recordPageId = $record['live_pid'];
					$this->versionsPerPage[$recordPageId][$workspaceId] = TRUE;
				}
			}

			if (!empty($this->versionsPerPage[$pageId][$workspaceId])) {
				return TRUE;
			}
		}

		$this->versionsPerPage[$pageId][$workspaceId] = FALSE;
		return FALSE;
	}

	/**
	 * Gets the current backend user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Get the current database connection.
	 *
	 * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection
	 */
	protected function getDatabase() {
		return $GLOBALS['TYPO3_DB'];
	}

}
?>