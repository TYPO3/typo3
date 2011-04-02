<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Web > Functions module plugin for cleaning up.
 *
 * XHTML compliant
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   65: class tx_lowlevel_cleaner extends t3lib_extobjbase
 *   74:     function modMenu()
 *  109:     function main()
 *  130:     function createMenu()
 *  145:     function moduleContent()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(t3lib_extMgm::extPath('lowlevel').'class.tx_lowlevel_cleaner_core.php');

die('Not developed...');





/**
 * Web > Functions module plugin for cleaning up.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class tx_lowlevel_cleaner extends t3lib_extobjbase {


	/**
	 * Modifies parent objects internal MOD_MENU array, adding items this module needs.
	 *
	 * @return	array		Items merged with the parent objects.
	 * @see t3lib_extobjbase::init()
	 */
	function modMenu()	{
		global $LANG;

		$modMenuAdd = array(
			'tx_lowlevel_cleaner' => array(
#				'delete_flush' => 'Flush "deleted" records',		// ID/depth related
#				'delete_restore' => 'Restore "deleted" records',	// ID/depth related
#				'versions_flush' => 'Flush published versions',	// ID/depth related

#				'clean_flexform_xml' => 'Clean up FlexForm XML',	// ID/depth related
			# tt_content element removal: templavoila plugin!		// ID/depth related
			# Check for various: uid>0, pid>-1	, lost swapping operations,
#				'l10n_duplicates' => 'Localization errors',
# Find TCA/FlexForm fields which should probably have a soft reference parser attached!

			# TemplaVoila plugs in to display mapping issues.
			# Overview of http://  and emails 						// UPDATE index!

				'missing_files' => 'Missing files',				// UPDATE index!
				'missing_relations' => 'Missing relations',		// UPDATE index!
#				'lost_records' => 'Orphan records',
				'lost_files' => 'Orphan files (from uploads/)',	// UPDATE index!
				'RTEmagic_files' => 'RTE Magic Images',			// UPDATE index!
				'double_files' => 'Double file references', 		// UPDATE index!
			)
		);

		return $modMenuAdd;
	}

	/**
	 * Creation of the main content.
	 *
	 * @return	string		The content
	 */
	function main()	{
		$content = '';

		if ($GLOBALS['BE_USER']->isAdmin())	{
			$content.= $this->pObj->doc->spacer(5);
			$content.= $this->createMenu().'<hr/>';
			$content.= $this->moduleContent();
			$content.= $this->quickDBlookUp();
		} else {
			$content.= $this->pObj->doc->spacer(5);
			$content.= 'Only access for admin users, sorry.';
		}
		return $content;
	}

	/**
	 * Creates HTML menu for the module.
	 *
	 * @return	string		HTML code for menu
	 */
	function createMenu()	{
		if (is_array($this->pObj->MOD_MENU['tx_lowlevel_cleaner']))	{
			$menu = '';
			foreach($this->pObj->MOD_MENU['tx_lowlevel_cleaner'] as $key => $value)	{
				$menu .= '<a href="index.php?id=' . intval(t3lib_div::_GP('id')) . '&tx_lowlevel_cleaner=' . $key . '">' . htmlspecialchars($value) . '</a><br />';
			}
			return $menu;
		}
	}

	/**
	 * Branching out to the specified module functionality.
	 *
	 * @return	string		HTML
	 */
	function moduleContent()	{
		$cleanerObj = t3lib_div::makeInstance('tx_lowlevel_cleaner_core');
		$silent = FALSE;
		$filter = 0;

		switch(t3lib_div::_GP('tx_lowlevel_cleaner'))	{
			case 'lost_files':
				$res = $cleanerObj->clean_lost_files_analyze();
				$output = $cleanerObj->html_printInfo('clean_lost_files_analyze()',$res,$silent,$filter);
			break;
			case 'RTEmagic_files':
				$res = $cleanerObj->RTEmagic_files_analyze();
				$output = $cleanerObj->html_printInfo('RTEmagic_files_analyze()',$res,$silent,$filter);
			break;
			case 'double_files':
				$res = $cleanerObj->double_files_analyze();
				$output = $cleanerObj->html_printInfo('double_files_analyze()',$res,$silent,$filter);
			break;
			case 'missing_files':
				$res = $cleanerObj->missing_files_analyze();
				$output = $cleanerObj->html_printInfo('missing_files_analyze()',$res,$silent,$filter);
			break;
			case 'missing_relations':
				$res = $cleanerObj->missing_relations_analyze();
				$output = $cleanerObj->html_printInfo('missing_relations_analyze()',$res,$silent,$filter);
			break;
		}

/*
// TEST of how we can get the used Content Elements on a TemplaVoila page:
require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_api.php');
$apiObj = t3lib_div::makeInstance('tx_templavoila_api', 'pages');
$contentTreeData = $apiObj->getContentTree('pages', t3lib_BEfunc::getRecordWSOL('pages',33),FALSE);
debug($contentTreeData);
*/
		return $output;
	}

	function quickDBlookUp()	{
		$output = 'Enter [table]:[uid]:[fieldlist (optional)] <input name="table_uid" value="'.htmlspecialchars(t3lib_div::_POST('table_uid')).'" />';
		$output .= '<input type="submit" name="_" value="REFRESH" /><br />';

			// Show record:
		if (t3lib_div::_POST('table_uid'))	{
			list($table,$uid,$fieldName) = t3lib_div::trimExplode(':',t3lib_div::_POST('table_uid'),1);
			if ($GLOBALS['TCA'][$table])	{
				$rec = t3lib_BEfunc::getRecordRaw($table,'uid='.intval($uid),$fieldName?$fieldName:'*');

				if (count($rec))	{
					if (t3lib_div::_POST('_EDIT'))	{
						$output .= '<hr />Edit:<br /><br />';
						foreach($rec as $field => $value)	{
							$output .= htmlspecialchars($field) . '<br /><input name="record[' . $table . '][' . $uid . '][' . $field . ']" value="' . htmlspecialchars($value) . '" /><br />';
						}
						$output.='<input type="submit" name="_SAVE" value="SAVE" />';
					} elseif (t3lib_div::_POST('_SAVE'))	{
						$incomingData = t3lib_div::_POST('record');
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table,'uid='.intval($uid),$incomingData[$table][$uid]);
						$output .= '<br />Updated ' . $table . ':' . $uid . '...';
						$this->updateRefIndex($table,$uid);
					} else if (t3lib_div::_POST('_DELETE'))	{
						$GLOBALS['TYPO3_DB']->exec_DELETEquery($table,'uid='.intval($uid));
						$output .= '<br />Deleted ' . $table . ':' . $uid . '...';
						$this->updateRefIndex($table,$uid);
					} else {
						$output.='<input type="submit" name="_EDIT" value="EDIT" />';
						$output.='<input type="submit" name="_DELETE" value="DELETE" onclick="return confirm(\'Are you sure you wish to delete?\');" />';
						$output.= t3lib_utility_Debug::viewArray($rec);
						$output.=md5(implode($rec));
					}
				} else {
					$output.='No record existed!';
				}
			}
		}

		return $output;
	}


	/**
	 * Update Reference Index (sys_refindex) for a record
	 *
	 * @param	string		Table name
	 * @param	integer		Record UID
	 * @return	void
	 */
	function updateRefIndex($table,$id)	{
		$refIndexObj = t3lib_div::makeInstance('t3lib_refindex');
		$result = $refIndexObj->updateRefIndexTable($table,$id);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lowlevel/class.tx_lowlevel_cleaner.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lowlevel/class.tx_lowlevel_cleaner.php']);
}
?>