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

$LANG->includeLLFile('EXT:topapps/cache/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_topmenubase.php');

require_once ('class.alt_menu_functions.inc');

/**
 * Main script class for the cache clearing functions
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_topapps
 */
class SC_topapps_cache extends t3lib_topmenubase {

	/**
	 * Main function
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_MODULES,$TBE_TEMPLATE,$MCONF,$LANG;
		
		switch((string)t3lib_div::_GET('cmd'))	{
			case 'menuitem':

				echo '<img src="gfx/clear_cache.gif" hspace="1" />';
				
				echo'
					<script>
						getElementContent("'.$MCONF['name'].'-layer", 10, "mod.php?M='.$MCONF['name'].'&cmd=content")
					</script>
				
				';
				echo $this->simpleLayer('Fetching...',$MCONF['name'].'-layer');
			break;
			case 'content':
				$mObj = t3lib_div::makeInstance('alt_menu_functions');
				$functions = $mObj->adminFunctions('');
				$functions[] = array(
					'title' => 'Clear page cache',
					'href' => 'tce_db.php?vC='.$GLOBALS['BE_USER']->veriCode().'&redirect='.rawurlencode(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT')).'&cacheCmd=pages',
					'id' => 'page'
				);
				
				$items = array();
				foreach($functions as $cfg)	{
					
					switch($cfg['id'])	{
						case 'temp_CACHED':
							$cacheFiles = t3lib_extMgm::currentCacheFiles();
							$cfg['title'].= ' ['.($cacheFiles[0] ? t3lib_BEfunc::calcAge(time()-filemtime($cacheFiles[0])) : 'NONE').']';
							$cfg['icon'] = array('gfx/clear_cache_files_in_typo3c.gif','width="21" height="18"');
						break;
						case 'all':
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('count(*)','cache_hash','');
							$cfg['title'].= ' ['.$res[0]['count(*)'].']';
							$cfg['icon'] = array('gfx/clear_all_cache.gif','width="21" height="18"');
						break;
						case 'page':
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('count(*)','cache_pages','');
							$cfg['title'].= ' ['.$res[0]['count(*)'].']';
						break;
					}
					
					
					$items[] = array(
						'title' => $cfg['title'],
						'icon' => $cfg['icon'],
						'onclick' => "new Ajax.Request('".$cfg['href']."');"
					);
				}

				echo $this->menuItems($items);
			break;
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/cache/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/cache/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_topapps_cache');
$SOBE->main();
?>