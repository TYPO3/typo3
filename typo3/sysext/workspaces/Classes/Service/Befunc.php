<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
	public function preProcess($pageUid, $backPath, $rootLine, $anchorSection, &$viewScript, $additionalGetVars, $switchFocus) {
		if ($GLOBALS['BE_USER']->workspace !== 0 && !$GLOBALS['BE_USER']->user['workspace_preview']) {
			$ctrl = t3lib_div::makeInstance('Tx_Workspaces_Controller_PreviewController', true);
			$uriBuilder = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Routing_UriBuilder');
			/**
			 *  @todo BACK_PATH is not available be still needed when used during AJAX request
			 *  @todo make sure this would work in local extension installation too
			 */
			$backPath = isset($GLOBALS['BACK_PATH']) ? $GLOBALS['BACK_PATH'] :  '../../../' . TYPO3_mainDir;
				// @todo why do we need these additional params? the URIBuilder should add the controller, but he doesn't :(
			$additionalParams = '&tx_workspaces_web_workspacesworkspaces%5Bcontroller%5D=Preview&M=web_WorkspacesWorkspaces&id=';

			$viewScript = '/' . $backPath . $uriBuilder->uriFor('index', array(), $ctrl, 'workspaces', 'web_workspacesworkspaces') . $additionalParams;
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Befunc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Befunc.php']);
}
?>