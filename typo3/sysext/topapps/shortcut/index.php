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

/**
 * Main script class for the shortcut display
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_topapps
 */
class SC_topapps_shortcut extends t3lib_topmenubase {

	/**
	 * Main function
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_MODULES,$TBE_TEMPLATE,$MCONF,$LANG,$BE_USER;
		
		switch((string)t3lib_div::_GET('cmd'))	{
			case 'menuitem':
				echo '
<div id="shortcut_icons">
<a onclick="Element.toggle(\'shortcut_iconpossibilities\');return false;">C</a>
				<a onclick="top.goToModule(\'web_list\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/module.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
				<a onclick="top.goToModule(\'web_layout\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/addedit.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
				<a onclick="top.goToModule(\'file_list\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/mediamanager.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
				<a onclick="top.goToModule(\'tools_beuser\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/user.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
				<a onclick="top.goToModule(\'user_setup\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/config.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
</div>

<div id="shortcut_iconpossibilities" style="background-color: #666666; position:absolute; width: 300px; display: none;">
<a onclick="top.goToModule(\'web_list\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/module.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'web_layout\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/addedit.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'file_list\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/mediamanager.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'tools_beuser\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/user.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'user_setup\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/config.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<hr>
<a onclick="top.goToModule(\'web_list\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/module.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'web_layout\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/addedit.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'file_list\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/mediamanager.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'tools_beuser\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/user.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'user_setup\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/config.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'file_list\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/mediamanager.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'tools_beuser\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/user.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'user_setup\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/config.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'file_list\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/mediamanager.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'tools_beuser\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/user.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
<a onclick="top.goToModule(\'user_setup\'); event.stopPropagation(); return false;"><img src="'.t3lib_extMgm::extRelPath('topapps').'shortcut/config.png" height="32" style="padding-left: 4px; cursor: hand;" vspace="4" /></a>
</div>
				 <script type="text/javascript">
				 // <![CDATA[
				   Sortable.create("shortcut_icons",
				     {
						dropOnEmpty:true,
						containment:["shortcut_icons","shortcut_iconpossibilities"],
						constraint:false,
						tag: \'a\',
						overlap: \'horizontal\',
						onUpdate: function (id){}
					});


				   Sortable.create("shortcut_iconpossibilities",
				     {
						dropOnEmpty:true,
						containment:["shortcut_icons","shortcut_iconpossibilities"],
						constraint:false,
						tag: \'a\',
						overlap: \'horizontal\',
						onUpdate: function (id){}
					});
				 // ]]>
				 </script>
				';
			break;
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/shortcut/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/shortcut/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_topapps_shortcut');
$SOBE->main();
?>