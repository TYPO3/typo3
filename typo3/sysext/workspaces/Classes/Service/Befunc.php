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
class tx_Workspaces_Service_Befunc {

	/**
	 * Hooks into the t3lib_beFunc::viewOnClick and redirects to the workspace preview
	 * only if we're in a workspace and if the frontend-preview is disabled.
	 *
	 * @param  $pageUid
	 * @param  $backPath
	 * @param  $rootLine
	 * @param  $anchorSection
	 * @param  $viewScript
	 * @param  $additionalGetVars
	 * @param  $switchFocus
	 * @return void
	 */
	public function preProcess(&$pageUid, $backPath, $rootLine, $anchorSection, &$viewScript, $additionalGetVars, $switchFocus) {
		if ($GLOBALS['BE_USER']->workspace !== 0) {
			$viewScript = $this->getWorkspaceService()->generateWorkspaceSplittedPreviewLink($pageUid);
		}
	}

	/**
	 * Find the Live-Uid for a given page,
	 * the results are cached at run-time to avoid too many database-queries
	 *
	 * @throws InvalidArgumentException
	 * @param integer $uid
	 * @return integer
	 * @deprecated since TYPO3 4.6 - use tx_Workspaces_Service_Workspaces::getLivePageUid() instead
	 */
	protected function getLivePageUid($uid) {
		t3lib_div::deprecationLog(__METHOD__ . ' is deprected since TYPO3 4.6 - use tx_Workspaces_Service_Workspaces::getLivePageUid() instead');
		return $this->getWorkspaceService()->getLivePageUid($uid);
	}

	/**
	 * Gets an instance of the workspaces service.
	 *
	 * @return tx_Workspaces_Service_Workspaces
	 */
	protected function getWorkspaceService() {
		return t3lib_div::makeInstance('tx_Workspaces_Service_Workspaces');
	}

	/**
	 * Use that hook to show a info message in case someone starts editing
	 * a staged element
	 *
	 * @param  $params
	 * @param  $form
	 * @return boolean
	 */
	public function makeEditForm_accessCheck($params, &$form) {
		if ($GLOBALS['BE_USER']->workspace !== 0 && $GLOBALS['TCA'][$params['table']]['ctrl']['versioningWS']) {
			$record = t3lib_BEfunc::getRecordWSOL($params['table'], $params['uid']);
			if (abs($record['t3ver_stage']) > Tx_Workspaces_Service_Stages::STAGE_EDIT_ID) {
				$stages = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
				$stageName = $stages->getStageTitle($record['t3ver_stage']);
				$editingName = $stages->getStageTitle(Tx_Workspaces_Service_Stages::STAGE_EDIT_ID);
				$message = $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:info.elementAlreadyModified');
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					sprintf($message, $stageName, $editingName),
					'',
					t3lib_FlashMessage::INFO,
					TRUE
				);
				t3lib_FlashMessageQueue::addMessage($flashMessage);
			}
		}
		return $params['hasAccess'];
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Befunc.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Befunc.php']);
}
?>