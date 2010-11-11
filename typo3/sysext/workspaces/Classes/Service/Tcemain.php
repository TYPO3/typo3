<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Tolleiv Nietsch (nietsch@aoemedia.de)
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
 *
 * @author Tolleiv Nietsch (nietsch@aoemedia.de)
 *
 */
class tx_Workspaces_Service_Tcemain {

	/**
	 * In case a sys_workspace_stage record is deleted we do a hard reset
	 * for all existing records in that stage to avoid that any of these end up
	 * as orphan records.
	 *
	 * @param string $command
	 * @param string $table
	 * @param string $id
	 * @param string $value
	 * @param object $tcemain
	 * @return void
	 */
	public function processCmdmap_postProcess($command, $table, $id, $value, $tcemain) {

		if (strcmp($command, 'delete') || strcmp($table, Tx_Workspaces_Service_Stages::TABLE_STAGE)) {
			return;
		}

		$service = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
			// @todo: remove the encode/decode functionality
		$pseudoStageId = $service->encodeStageUid($id);

		$fields = array('t3ver_stage' => Tx_Workspaces_Service_Stages::STAGE_EDIT_ID);

		foreach($GLOBALS['TCA'] as $tcaTable => $cfg)	{
			if ($GLOBALS['TCA'][$tcaTable]['ctrl']['versioningWS'])	{

				$where  = 't3ver_stage = ' . intval($pseudoStageId);
				$where .= ' AND t3ver_wsid > 0 AND pid=-1';
				$where .= t3lib_BEfunc::deleteClause($tcaTable);

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tcaTable, $where, $fields);
			}
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Tcemain.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Tcemain.php']);
}
?>