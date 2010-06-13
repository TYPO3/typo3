<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Export Preset listing
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   63: class tx_impexp_modfunc1 extends mod_user_task
 *   72:     function overview_main()
 *  109:     function main()
 *
 *              SECTION: Helper functions
 *  192:     function getPresets()
 *  209:     function userTempFolder()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



t3lib_extMgm::isLoaded('impexp',1);


/**
 * Export Preset listing for the task center
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_impexp
 */
class tx_impexp_modfunc1 extends mod_user_task {

	/**
	 * Create preset overview for task center overview.
	 *
	 * @param	object		Parent object
	 * @return	string		HTML for the task center overview listing.
	 */
	function overview_main()	{
		global $LANG;
			// Create preset links:
		$presets = $this->getPresets();
		$opt = array();
		if (is_array($presets))	{
			foreach($presets as $presetCfg)	{
				$title = strlen($presetCfg['title']) ? $presetCfg['title'] : '['.$presetCfg['uid'].']';
				$opt[] = '
					<tr class="bgColor4">
						<td nowrap="nowrap"><a href="index.php?SET[function]=tx_impexp&display='.$presetCfg['uid'].'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($title,30)).'</a>&nbsp;</td>
						<td>'.($presetCfg['item_uid'] ? $presetCfg['item_uid'] : '&nbsp;').'</td>
						<td>'.($presetCfg['public'] ? '[Public]' : '&nbsp;').'</td>
						<td>'.($presetCfg['user_uid']===$GLOBALS['BE_USER']->user['uid'] ? '[Own]' : '&nbsp;').'</td>
					</tr>';
			}
			if(sizeof($opt)>0) {
				$presets = '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding">'.implode('',$opt).'</table>';
				$presets .= '<a href="index.php?SET[function]=tx_impexp"><em>'.$LANG->getLL('link_allRecs').'</em></a>';
			} else {
				$presets = '';
			}

			$icon = '<img src="'.$this->backPath.t3lib_extMgm::extRelPath('impexp').'export.gif" width="18" height="16" class="absmiddle" alt="" />';
			$config= $this->mkMenuConfig($icon.$this->headLink('tx_impexp_modfunc1',1),'',$presets);
		}

		return $config;
	}



	/**
	 * Main Task center module
	 *
	 * @return	string		HTML content.
	 */
	function main() {
			if($id = t3lib_div::_GP('display')) {
				return $this->urlInIframe($this->backPath.t3lib_extMgm::extRelPath('impexp').'app/index.php?tx_impexp[action]=export&preset[load]=1&preset[select]='.$id,1);
			} else {
				// Thumbnail folder and files:
				$tempDir = $this->userTempFolder();
				if ($tempDir)	{
					$thumbnails = t3lib_div::getFilesInDir($tempDir,'png,gif,jpg',1);
				}

				$clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
				$usernames = t3lib_BEfunc::getUserNames();

				// Create preset links:
				$presets = $this->getPresets();
				$opt = array();
				$opt[] = '
			<tr class="bgColor5 tableheader">
				<td>Icon:</td>
				<td>Preset Title:</td>
				<td>Public</td>
				<td>Owner:</td>
				<td>Page:</td>
				<td>Path:</td>
				<td>Meta data:</td>
			</tr>';
				if (is_array($presets))	{
					foreach($presets as $presetCfg)	{
						$configuration = unserialize($presetCfg['preset_data']);
						$thumbnailFile = $thumbnails[$configuration['meta']['thumbnail']];
						$title = strlen($presetCfg['title']) ? $presetCfg['title'] : '['.$presetCfg['uid'].']';

						$opt[] = '
					<tr class="bgColor4">
						<td>' . ($thumbnailFile ? '<img src="' . $this->backPath . '../' . substr($tempDir, strlen(PATH_site)) . basename($thumbnailFile) . '" hspace="2" width="70" style="border: solid black 1px;" alt="" /><br />' : '&nbsp;') . '</td>
						<td nowrap="nowrap"><a href="index.php?SET[function]=tx_impexp&display='.$presetCfg['uid'].'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($title,30)).'</a>&nbsp;</td>
						<td>'.($presetCfg['public'] ? 'Yes' : '&nbsp;').'</td>
						<td>'.($presetCfg['user_uid']===$GLOBALS['BE_USER']->user['uid'] ? 'Own' : '['.$usernames[$presetCfg['user_uid']]['username'].']').'</td>
						<td>'.($configuration['pagetree']['id'] ? $configuration['pagetree']['id'] : '&nbsp;').'</td>
						<td>'.htmlspecialchars($configuration['pagetree']['id'] ? t3lib_BEfunc::getRecordPath($configuration['pagetree']['id'],$clause,20) : '[Single Records]').'</td>
						<td>
							<strong>' . htmlspecialchars($configuration['meta']['title']) . '</strong><br />' .
							htmlspecialchars($configuration['meta']['description']).
							($configuration['meta']['notes'] ? '<br /><br /><strong>Notes:</strong> <em>' . htmlspecialchars($configuration['meta']['notes']) . '</em>' : '') .
							'
						</td>
					</tr>';
					}
					$content = '<table border="0" cellpadding="0" cellspacing="1" class="lrPadding">'.implode('',$opt).'</table>';
				}
			}

			// Output:
		$theOutput.= $this->pObj->doc->spacer(5);
		$theOutput.= $this->pObj->doc->section('Export presets',$content,0,1);

		return $theOutput;
	}














	/*****************************
	 *
	 * Helper functions
	 *
	 *****************************/

	/**
	 * Select presets for this user
	 *
	 * @return	array		Array of preset records
	 */
	function getPresets()	{
		$presets = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'tx_impexp_presets',
				'(public>0 OR user_uid='.intval($GLOBALS['BE_USER']->user['uid']).')',
				'',
				'item_uid DESC, title'
			);

		return $presets;
	}

	/**
	 * Returns first temporary folder of the user account (from $FILEMOUNTS)
	 *
	 * @return	string		Absolute path to first "_temp_" folder of the current user, otherwise blank.
	 */
	function userTempFolder()	{
		global $FILEMOUNTS;

		foreach($FILEMOUNTS as $filePathInfo)	{
			$tempFolder = $filePathInfo['path'].'_temp_/';
			if (@is_dir($tempFolder))	{
				return $tempFolder;
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/modfunc1/class.tx_impexp_modfunc1.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/modfunc1/class.tx_impexp_modfunc1.php']);
}
?>