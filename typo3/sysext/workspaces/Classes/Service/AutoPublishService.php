<?php
namespace TYPO3\CMS\Workspaces\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Automatic publishing of workspaces.
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class AutoPublishService {

	/**
	 * This method is called by the Scheduler task that triggers
	 * the autopublication process
	 * It searches for workspaces whose publication date is in the past
	 * and publishes them
	 *
	 * @return 	void
	 */
	public function autoPublishWorkspaces() {
		global $TYPO3_CONF_VARS;
		// Temporarily set admin rights
		// FIXME: once workspaces are cleaned up a better solution should be implemented
		$currentAdminStatus = $GLOBALS['BE_USER']->user['admin'];
		$GLOBALS['BE_USER']->user['admin'] = 1;
		// Select all workspaces that needs to be published / unpublished:
		$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,swap_modes,publish_time,unpublish_time', 'sys_workspace', 'pid=0
				AND
				((publish_time!=0 AND publish_time<=' . intval($GLOBALS['EXEC_TIME']) . ')
				OR (publish_time=0 AND unpublish_time!=0 AND unpublish_time<=' . intval($GLOBALS['EXEC_TIME']) . '))' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_workspace'));
		$workspaceService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		foreach ($workspaces as $rec) {
			// First, clear start/end time so it doesn't get select once again:
			$fieldArray = $rec['publish_time'] != 0 ? array('publish_time' => 0) : array('unpublish_time' => 0);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_workspace', 'uid=' . intval($rec['uid']), $fieldArray);
			// Get CMD array:
			$cmd = $workspaceService->getCmdArrayForPublishWS($rec['uid'], $rec['swap_modes'] == 1);
			// $rec['swap_modes']==1 means that auto-publishing will swap versions, not just publish and empty the workspace.
			// Execute CMD array:
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = 0;
			$tce->start(array(), $cmd);
			$tce->process_cmdmap();
		}
		// Restore admin status
		$GLOBALS['BE_USER']->user['admin'] = $currentAdminStatus;
	}

}


?>