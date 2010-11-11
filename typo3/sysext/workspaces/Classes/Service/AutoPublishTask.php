<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 François Suter <francois@typo3.org>
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
 * This class provides a wrapper around the autopublication
 * mechanism of workspaces, as a Scheduler task
 *
 * @author		François Suter <francois@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_version
 *
 * $Id: class.tx_scheduler_sleeptask.php 7905 2010-06-13 14:42:33Z ohader $
 */
class tx_Workspaces_Service_AutoPublishTask extends tx_scheduler_Task {

	/**
	 * Method executed from the Scheduler.
	 * Call on the workspace logic to publish workspaces whose publication date
	 * is in the past
	 *
	 * @return	void
	 */
	public function execute() {
		$autopubObj = t3lib_div::makeInstance('tx_Workspaces_Service_AutoPublish');
			// Publish the workspaces that need to be
		$autopubObj->autoPublishWorkspaces();
			// There's no feedback from the publishing process,
			// so there can't be any failure.
			// TODO: This could certainly be improved.
		return TRUE;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/AutoPublishTask.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/AutoPublishTask.php']);
}
?>