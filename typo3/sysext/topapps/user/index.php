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
 * Main script class for the user listing
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_topapps
 */
class SC_topapps_user extends t3lib_topmenubase {

	/**
	 * Main function
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_MODULES,$TBE_TEMPLATE,$MCONF,$LANG;
		
		switch((string)t3lib_div::_GET('cmd'))	{
			case 'menuitem':

				echo '<img src="'.t3lib_extMgm::extRelPath('topapps').'user/be_users.gif" hspace="1" alt=""/>';
				
				$itemArray = array();
				$users = t3lib_BEfunc::getUserNames();
				foreach($users as $uid => $dat)	{
					$userRec = t3lib_BEfunc::getRecord('be_users',$uid);
					$itemArray[] = array(
						'title' => $userRec['username'].' - '.$userRec['realName'].' ('.($userRec['lastlogin'] ? t3lib_BEfunc::calcAge(time()-$userRec['lastlogin']) : 'never').')',
						'icon' => array(t3lib_iconWorks::getIcon('be_users',$userRec),'width="18" height="16"'),
						'state' => $GLOBALS['BE_USER']->user['uid']==$uid ? 'checked' : '',
						'onclick' => 'top.document.location="mod.php?M=tools_beuser&SwitchUser='.$uid.'&switchBackUser=1";',
					);
				}
				$itemArray[] = array(
					'title' => '--div--',
				);
				$itemArray[] = array(
					'title' => 'Edit profile',
					'onclick' => 'top.goToModule("user_setup")'
				);
				
				echo $this->menuLayer($itemArray);
			break;
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/user/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/user/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_topapps_user');
$SOBE->main();
?>