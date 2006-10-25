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

$LANG->includeLLFile('EXT:topapps/clock/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_topmenubase.php');

require_once ('class.alt_menu_functions.inc');

/**
 * Main script class for the clock display
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_topapps
 */
class SC_topapps_clock extends t3lib_topmenubase {

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
					<script>
//						Element.update("'.$MCONF['name'].'-time","test");
						getElementContent("'.$MCONF['name'].'", 10, "mod.php?M='.$MCONF['name'].'&cmd=time");
					</script>				
				';
			break;
			case 'time':
			
				$config = $BE_USER->uc['moduleData'][$MCONF['name']];

				echo '&nbsp;'.strftime(($config['day']?'%a, ':'').($config['date']?'%d %b %Y ':'').'%H:%M'.($config['timezone']?' %z':'')).'&nbsp;';

				$menuItems = array(
					array(
						'title' => 'Show day',
						'state' => $config['day'] ? 'checked' : '',
						'onclick' => $this->toggleOnclick('day')
					),
					array(
						'title' => 'Show date',
						'state' => $config['date'] ? 'checked' : '',
						'onclick' => $this->toggleOnclick('date')
					),
					array(
						'title' => 'Show timezone',
						'state' => $config['timezone'] ? 'checked' : '',
						'onclick' => $this->toggleOnclick('timezone')
					),
				);
				echo $this->menuLayer($menuItems);
			break;
			case 'toggle':
				$index = t3lib_div::_GET('index');
				if (t3lib_div::inList('day,date,timezone',$index))	{
					$BE_USER->uc['moduleData'][$MCONF['name']][$index] = !$BE_USER->uc['moduleData'][$MCONF['name']][$index];
					$BE_USER->writeUC($BE_USER->uc);
				}
			break;
		}
	}
	function toggleOnclick($index)	{
		global $MCONF;
		return 'new Ajax.Request("mod.php?M='.$MCONF['name'].'&cmd=toggle&index='.$index.'",{onComplete: function(){getElementContent("'.$MCONF['name'].'", 0, "mod.php?M='.$MCONF['name'].'&cmd=time");}});';
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/clock/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/clock/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_topapps_clock');
$SOBE->main();
?>