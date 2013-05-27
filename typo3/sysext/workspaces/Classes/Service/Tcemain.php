<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 * @package Workspaces
 * @subpackage Service
 */
class Tx_Workspaces_Service_Tcemain {

	/**
	 * In case a sys_workspace_stage record is deleted we do a hard reset
	 * for all existing records in that stage to avoid that any of these end up
	 * as orphan records.
	 *
	 * @param string $command
	 * @param string $table
	 * @param string $id
	 * @param string $value
	 * @param t3lib_TCEmain $tcemain
	 * @return void
	 */
	public function processCmdmap_postProcess($command, $table, $id, $value, t3lib_TCEmain $tcemain) {
		if ($command === 'delete') {
			if ($table === Tx_Workspaces_Service_Stages::TABLE_STAGE) {
				$this->resetStageOfElements($id);
			} elseif ($table === Tx_Workspaces_Service_Workspaces::TABLE_WORKSPACE) {
				$this->flushWorkspaceElements($id);
			}
		}
	}

	/**
	 * hook that is called AFTER all commands of the commandmap was 
	 * executed
	 *
	 * @param t3lib_TCEmain $tcemainObj reference to the main tcemain object
	 * @return	void
	 */
	public function processCmdmap_afterFinish(t3lib_TCEmain $tcemainObj) {
		if (TYPO3_UseCachingFramework) {
			$this->flushWorkspaceCacheEntriesByWorkspaceId($tcemainObj->BE_USER->workspace);
		}
	}

	/**
	 * In case a sys_workspace_stage record is deleted we do a hard reset
	 * for all existing records in that stage to avoid that any of these end up
	 * as orphan records.
	 *
	 * @param integer $stageId Elements with this stage are resetted
	 * @return void
	 */
	protected function resetStageOfElements($stageId) {
		$fields = array('t3ver_stage' => Tx_Workspaces_Service_Stages::STAGE_EDIT_ID);

		foreach ($this->getTcaTables() as $tcaTable) {
			if (t3lib_BEfunc::isTableWorkspaceEnabled($tcaTable)) {

				$where = 't3ver_stage = ' . intval($stageId);
				$where .= ' AND t3ver_wsid > 0 AND pid=-1';
				$where .= t3lib_BEfunc::deleteClause($tcaTable);

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tcaTable, $where, $fields);
			}
		}
	}

	/**
	 * Flushes elements of a particular workspace to avoid orphan records.
	 *
	 * @param integer $workspaceId The workspace to be flushed
	 * @return void
	 */
	protected function flushWorkspaceElements($workspaceId) {
		$command = array();

		foreach ($this->getTcaTables() as $tcaTable) {
			if (t3lib_BEfunc::isTableWorkspaceEnabled($tcaTable)) {
				$where = '1=1';
				$where .= t3lib_BEfunc::getWorkspaceWhereClause($tcaTable, $workspaceId);
				$where .= t3lib_BEfunc::deleteClause($tcaTable);

				$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $tcaTable, $where, '', '', '', 'uid');
				if (is_array($records)) {
					foreach (array_keys($records) as $recordId) {
						$command[$tcaTable][$recordId]['version']['action'] = 'flush';
					}
				}
			}
		}

		if (count($command)) {
			$tceMain = $this->getTceMain();
			$tceMain->start(array(), $command);
			$tceMain->process_cmdmap();
		}
	}

	/**
	 * Gets all defined TCA tables.
	 *
	 * @return array
	 */
	protected function getTcaTables() {
		return array_keys($GLOBALS['TCA']);
	}

	/**
	 * Gets a new instance of t3lib_TCEmain.
	 *
	 * @return t3lib_TCEmain
	 */
	protected function getTceMain() {
		$tceMain = t3lib_div::makeInstance('t3lib_TCEmain');
		$tceMain->stripslashes_values = 0;
		return $tceMain;
	}

	/**
	 * Flushes the workspace cache for current workspace and for the virtual "all workspaces" too.
	 * 
	 * @param integer $workspaceId The workspace to be flushed in cache
	 * @return void
	 */
	protected function flushWorkspaceCacheEntriesByWorkspaceId($workspaceId) {
		$workspacesCache = $GLOBALS['typo3CacheManager']->getCache('workspaces_cache');
		$workspacesCache->flushByTag($workspaceId);
		$workspacesCache->flushByTag(Tx_Workspaces_Service_Workspaces::SELECT_ALL_WORKSPACES);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Tcemain.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Tcemain.php']);
}
?>