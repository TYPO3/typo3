<?php
namespace TYPO3\CMS\Version\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  (c) 2010-2013 Benjamin Mack (benni@typo3.org)
 *
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
 * Contains some parts for staging, versioning and workspaces
 * to interact with the TYPO3 Core Engine
 */
class VersionView {

	/**
	 * Creates the version selector for the page id inputted.
	 * Moved out of the core file typo3/template.php
	 *
	 * @param 	integer		Page id to create selector for.
	 * @param 	boolean		If set, there will be no button for swapping page.
	 * @return 	void
	 */
	public function getVersionSelector($id, $noAction = FALSE) {
		if ($id <= 0) {
			return;
		}
		if ($GLOBALS['BE_USER']->workspace == 0) {
			// Get Current page record:
			$curPage = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $id);
			// If the selected page is not online, find the right ID
			$onlineId = $curPage['pid'] == -1 ? $curPage['t3ver_oid'] : $id;
			// Select all versions of online version:
			$versions = \TYPO3\CMS\Backend\Utility\BackendUtility::selectVersionsOfRecord('pages', $onlineId, 'uid,pid,t3ver_label,t3ver_oid,t3ver_wsid,t3ver_id');
			// If more than one was found...:
			if (count($versions) > 1) {
				$selectorLabel = '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xml:versionSelect.label', TRUE) . '</strong>';
				// Create selector box entries:
				$opt = array();
				foreach ($versions as $vRow) {
					if ($vRow['uid'] == $onlineId) {
						// Live version
						$label = '[' . $GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xml:versionSelect.live', TRUE) . ']';
					} else {
						$label = $vRow['t3ver_label'] . ' (' . $GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xml:versionId', TRUE) . ' ' . $vRow['t3ver_id'] . ($vRow['t3ver_wsid'] != 0 ? ' ' . $GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xml:workspaceId', TRUE) . ' ' . $vRow['t3ver_wsid'] : '') . ')';
					}
					$opt[] = '<option value="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('id' => $vRow['uid']))) . '"' . ($id == $vRow['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>';
				}
				// Add management link:
				$management = '<input type="button" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:ver.mgm', TRUE) . '" onclick="window.location.href=\'' . htmlspecialchars(($GLOBALS['BACK_PATH'] . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('version') . 'cm1/index.php?table=pages&uid=' . $onlineId)) . '\';" />';
				// Create onchange handler:
				$onChange = 'window.location.href=this.options[this.selectedIndex].value;';
				// Controls:
				if ($id == $onlineId) {
					$controls .= '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_left.gif', 'width="5" height="9"') . ' class="absmiddle" alt="" /> <strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:ver.online', TRUE) . '</strong>';
				} elseif (!$noAction) {
					$controls .= '<a href="' . $GLOBALS['TBE_TEMPLATE']->issueCommand(('&cmd[pages][' . $onlineId . '][version][swapWith]=' . $id . '&cmd[pages][' . $onlineId . '][version][action]=swap'), \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('id' => $onlineId))) . '" class="nobr">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-version-swap-version', array(
						'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:ver.swapPage', TRUE),
						'style' => 'margin-left:5px;vertical-align:bottom;'
					)) . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:ver.swap', TRUE) . '</strong></a>';
				}
				// Write out HTML code:
				return '
					<!--
						Version selector:
					-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-versionSelector">
						<tr>
							<td>' . $selectorLabel . '</td>
							<td>
								<select onchange="' . htmlspecialchars($onChange) . '">
									' . implode('', $opt) . '
								</select></td>
							<td>' . $controls . '</td>
							<td>' . $management . '</td>
						</tr>
					</table>
				';
			}
		}
	}
}

?>
