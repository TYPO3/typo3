<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 */

require_once(PATH_t3lib.'class.t3lib_topmenubase.php');

require_once ('class.alt_menu_functions.inc');

/**
 * Main script class for the workspace selector
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_topapps
 */
class SC_topapps_workspace extends t3lib_topmenubase {

	/**
	 * Main function
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_MODULES,$TBE_TEMPLATE,$MCONF,$LANG,$BE_USER,$TYPO3_DB;
		
		switch((string)t3lib_div::_GET('cmd'))	{
			case 'menuitem':

				echo '<img src="'.t3lib_extMgm::extRelPath('topapps').'workspaces/sys_workspace.png" hspace="1" alt=""/>';


					// Create options array:
				$itemArray = array();
				if ($BE_USER->checkWorkspace(array('uid' => 0)))	{
					$itemArray[] = array(
						'title' => '[Live Workspace]',
						'onclick' => 'top.document.location="mod.php?M='.$MCONF['name'].'&cmd=switch&wid=0"',
						'state' => $BE_USER->workspace==0
					);
				}
				if ($BE_USER->checkWorkspace(array('uid' => -1)))	{
					$itemArray[] = array(
						'title' => '[Draft Workspace]',
						'onclick' => 'top.document.location="mod.php?M='.$MCONF['name'].'&cmd=switch&wid=-1"',
						'state' => $BE_USER->workspace==-1
					);
				}

					// Add custom workspaces (selecting all, filtering by BE_USER check):
				$workspaces = $TYPO3_DB->exec_SELECTgetRows('uid,title,adminusers,members,reviewers','sys_workspace','pid=0'.t3lib_BEfunc::deleteClause('sys_workspace'),'','title');
				if (count($workspaces))	{
					foreach ($workspaces as $rec)	{
						if ($BE_USER->checkWorkspace($rec))	{
							$itemArray[] = array(
								'title' => $rec['uid'].': '.$rec['title'],
								'onclick' => 'top.document.location="mod.php?M='.$MCONF['name'].'&cmd=switch&wid='.$rec['uid'].'"',
								'state' => $BE_USER->workspace==$rec['uid']
							);
						}
					}
				}
				
				$itemArray[] = array(
					'title' => '--div--'
				);
				$itemArray[] = array(
					'title' => 'Workspace module',
					'onclick' => 'top.goToModule("user_ws");'
				);
				$itemArray[] = array(
					'title' => 'Frontend Preview',
					'state' => $BE_USER->user['workspace_preview'] ? 'checked' : '',
					'onclick' => 'new Ajax.Request(
							"mod.php?M='.$MCONF['name'].'&cmd=toggleFEPreview",
							{onComplete: function(){
								getElementContent("'.$MCONF['name'].'", 0, "mod.php?M='.$MCONF['name'].'&cmd=menuitem");
							}}
						);'
				);
				
				echo $this->menuLayer($itemArray);
			break;
			case 'toggleFEPreview':
				$BE_USER->setWorkspacePreview(!$BE_USER->user['workspace_preview']);
			break;
			case 'switch':
				$BE_USER->setWorkspace(t3lib_div::_GET("wid"));
				header('Location: '.t3lib_div::locationHeaderUrl('alt_main_new.php'));
				exit;
			break;
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/workspace/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/workspace/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_topapps_workspace');
$SOBE->main();
?>