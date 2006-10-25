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

$LANG->includeLLFile('EXT:topapps/search/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_topmenubase.php');
require_once(PATH_typo3.'sysext/indexed_search/class.lexer.php');


/**
 * Main script class for the search box
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_topapps
 */
class SC_topapps_search extends t3lib_topmenubase {

	/**
	 * Main function
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_MODULES,$TBE_TEMPLATE,$MCONF,$LANG;
		
		switch((string)t3lib_div::_GET('cmd'))	{
			case 'menuitem':
				
				echo '<img src="'.t3lib_extMgm::extRelPath('topapps').'search/x_search.png" hspace="5" alt=""/>
				<script>
					menuItemObjects[\''.$MCONF['name'].'\'] = {
						onActivate: function() {
						},
						search: function (str) {
							if (str.length>2)	{
								var pars = "sword="+str;
								var myAjax = new Ajax.Updater(
									"'.$MCONF['name'].'-result", 
									"mod.php?M=xMOD_txtopapps_search&cmd=search", 
									{
										method: "get", 
										parameters: pars,
										evalScripts: true
									});
							}
						}
					}					
				</script>
				
				';
				
				$layerContent = '<div style="background: #d0e7b1; width: 200px; white-space: nowrap; padding: 5 5 5 5;" onclick="event.stopPropagation();">'.$LANG->getLL('searchFor').
					' <input type="text" value="" name="" onkeyup="menuItemObjects[\''.$MCONF['name'].'\'].search(this.value);" /></div>
				<div id="'.$MCONF['name'].'-result" style="border-top: 1px solid black;"></div>
				';
				
				echo $this->simpleLayer($layerContent);
			break;
			case 'search':
				$sw = t3lib_div::_GET('sword');
				$lexer = t3lib_div::makeInstance('tx_indexedsearch_lexer');
				$words = $lexer->split2Words($sw);
				
				$widArray = array(0);
				$c=0;
				foreach($words as $k => $v)	{
					$c++;
					$widArray[] = t3lib_div::md5int($c==count($words) ? substr($v,0,3) : $v);
				}
				
				
				
				// TODO: substr(3) will not work with utf-8!! (also problem in indexer!)
				
				
				$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'sys_refindex_res.*, count(*) as numberOfWordMatches',
					'sys_refindex_words,sys_refindex_rel,sys_refindex_res',
					'sys_refindex_words.wid IN ('.implode(',',$widArray).')'.
						' AND sys_refindex_words.wid=sys_refindex_rel.wid'.
						' AND sys_refindex_rel.rid=sys_refindex_res.rid',
					'sys_refindex_res.rid',
					'numberOfWordMatches DESC',
					'10'
				);
				
				foreach($rows as $k => $row)	{
					if ($row['numberOfWordMatches']!=count($words))	{
						unset($rows[$k]);
					}
				}
				
				echo 'Searching for "'.implode('" AND "', $words).'":<hr/>';
				$c=0;
				foreach($rows as $k => $row)	{
					$rec = t3lib_BEfunc::getRecord($row['tablename'],$row['recuid']);
					if (is_array($rec))	{
						if ($c>=10)	{
							echo '... and some more...';
							break;
						} else {
							echo t3lib_iconWorks::getIconImage($row['tablename'],$rec,'','class="absmiddle"'). 
								t3lib_BEfunc::getRecordTitle($row['tablename'],$rec,TRUE).'<br/>';
							$c++;
						}
					}
				}
			break;
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/search/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/topapps/search/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_topapps_search');
$SOBE->main();
?>