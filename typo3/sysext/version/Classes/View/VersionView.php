<?php
namespace TYPO3\CMS\Version\View;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Contains some parts for staging, versioning and workspaces
 * to interact with the TYPO3 Core Engine
 */
class VersionView {

	/**
	 * Creates the version selector for the page id inputted.
	 * Moved out of the core file \TYPO3\CMS\Backend\Template\DocumentTemplate
	 *
	 * @param integer $id Page id to create selector for.
	 * @param boolean $noAction If set, there will be no button for swapping page.
	 * @return void
	 * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
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
				$selectorLabel = '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xlf:versionSelect.label', TRUE) . '</strong>';
				// Create selector box entries:
				$opt = array();
				foreach ($versions as $vRow) {
					if ($vRow['uid'] == $onlineId) {
						// Live version
						$label = '[' . $GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xlf:versionSelect.live', TRUE) . ']';
					} else {
						$label = $vRow['t3ver_label'] . ' (' . $GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xlf:versionId', TRUE) . ' ' . $vRow['t3ver_id'] . ($vRow['t3ver_wsid'] != 0 ? ' ' . $GLOBALS['LANG']->sL('LLL:EXT:version/locallang.xlf:workspaceId', TRUE) . ' ' . $vRow['t3ver_wsid'] : '') . ')';
					}
					$opt[] = '<option value="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('id' => $vRow['uid']))) . '"' . ($id == $vRow['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>';
				}
				// Add management link:
				$management = '<input type="button" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:ver.mgm', TRUE) . '" onclick="window.location.href=\'' . htmlspecialchars($GLOBALS['BACK_PATH'] . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txversionM1', array('table' => 'pages', 'uid' => $onlineId))) . '\';" />';
				// Create onchange handler:
				$onChange = 'window.location.href=this.options[this.selectedIndex].value;';
				// Controls:
				if ($id == $onlineId) {
					$controls .= '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/blinkarrow_left.gif', 'width="5" height="9"') . ' class="absmiddle" alt="" /> <strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:ver.online', TRUE) . '</strong>';
				} elseif (!$noAction) {
					$controls .= '<a href="' . $GLOBALS['TBE_TEMPLATE']->issueCommand(('&cmd[pages][' . $onlineId . '][version][swapWith]=' . $id . '&cmd[pages][' . $onlineId . '][version][action]=swap'), \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('id' => $onlineId))) . '" class="nobr">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-version-swap-version', array(
						'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:ver.swapPage', TRUE),
						'style' => 'margin-left:5px;vertical-align:bottom;'
					)) . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:ver.swap', TRUE) . '</strong></a>';
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
