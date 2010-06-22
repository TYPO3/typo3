<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
*  (c) 2010 Georg Ringer (typo3@ringerge.org)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * This class provides a textarea to save personal notes
 *
 * @author		Kasper Skaarhoj <kasper@typo3.com>
 * @author		Georg Ringer <typo3@ringerge.org>
 * @package		TYPO3
 * @subpackage	impexp
 *
 */
class tx_impexp_task implements tx_taskcenter_Task {
	/**
	 * Back-reference to the calling reports module
	 *
	 * @var	tx_reports_Module	$taskObject
	 */
	protected $taskObject;

	/**
	 * Constructor
	 */
	public function __construct(SC_mod_user_task_index $taskObject) {
		$this->taskObject = $taskObject;
		$GLOBALS['LANG']->includeLLFile('EXT:impexp/locallang_csh.xml');
	}

	/**
	 * This method renders the report
	 *
	 * @return	string	The status report as HTML
	 */
	public function getTask() {
		return $this->main();
	}

	/**
	 * Render an optional additional information for the 1st view in taskcenter.
	 * Empty for this task
	 *
	 * @return	string		Overview as HTML
	 */
	public function getOverview() {
		return '';
	}

	/**
	 * Main Task center module
	 *
	 * @return	string		HTML content.
	 */
	public function main() {
		$content = '';
		$id = intval(t3lib_div::_GP('display'));

			// if a preset is found, it is rendered using an iframe
		if($id > 0) {
			$url = $GLOBALS['BACK_PATH'] . t3lib_extMgm::extRelPath('impexp') . 'app/index.php?tx_impexp[action]=export&preset[load]=1&preset[select]=' . $id;
			return $this->taskObject->urlInIframe($url, 1);
		} else {
				// header
			$content .= $this->taskObject->description(
				$GLOBALS['LANG']->getLL('.alttitle'),
				$GLOBALS['LANG']->getLL('.description')
			);

			$thumbnails = $lines = array();

				// Thumbnail folder and files:
			$tempDir = $this->userTempFolder();
			if ($tempDir) {
				$thumbnails = t3lib_div::getFilesInDir($tempDir, 'png,gif,jpg', 1);
			}

			$clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$usernames = t3lib_BEfunc::getUserNames();

				// Create preset links:
			$presets = $this->getPresets();

				// if any presets found
			if (is_array($presets)) {
				foreach($presets as $key => $presetCfg) {
					$configuration = unserialize($presetCfg['preset_data']);
					$thumbnailFile = $thumbnails[$configuration['meta']['thumbnail']];
					$title = strlen($presetCfg['title']) ? $presetCfg['title'] : '[' . $presetCfg['uid'] . ']';
					$icon = 'EXT:impexp/export.gif';

					$description = array();

						// is public?
					if ($presetCfg['public']) {
						$description[] = $GLOBALS['LANG']->getLL('task.public') . ': ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:yes');
					}

						// owner
					$description[] = $GLOBALS['LANG']->getLL('task.owner') . ': ' . (($presetCfg['user_uid'] === $GLOBALS['BE_USER']->user['uid']) ? $GLOBALS['LANG']->getLL('task.own') : '[' . htmlspecialchars($usernames[$presetCfg['user_uid']]['username']) . ']');

						// page & path
					if ($configuration['pagetree']['id']) {
						$description[] = $GLOBALS['LANG']->getLL('task.page') . ': ' . $configuration['pagetree']['id'];
						$description[] = $GLOBALS['LANG']->getLL('task.path') . ': ' . htmlspecialchars(t3lib_BEfunc::getRecordPath($configuration['pagetree']['id'], $clause, 20));
					} else {
						$description[] = $GLOBALS['LANG']->getLL('single-record');
					}

						// Meta information
					if ($configuration['meta']['title'] || $configuration['meta']['description'] || $configuration['meta']['notes']) {
						$metaInformation = '';
						if ($configuration['meta']['title']) {
							$metaInformation .= '<strong>' . htmlspecialchars($configuration['meta']['title']) . '</strong><br />';
						}
						if ($configuration['meta']['description']) {
							$metaInformation .= htmlspecialchars($configuration['meta']['description']);
						}
						if ($configuration['meta']['notes']) {
							$metaInformation .= '<br /><br />
												<strong>' . $GLOBALS['LANG']->getLL('notes') . ': </strong>
												<em>' . htmlspecialchars($configuration['meta']['notes']) . '</em>';
						}

						$description[] = '<br />' . $metaInformation;
					}

						// collect all preset information
					$lines[$key] = array(
						'icon'				=> $icon,
						'title'				=> $title,
						'descriptionHtml'	=> implode('<br />', $description),
						'link'				=> 'mod.php?M=user_task&SET[function]=impexp.tx_impexp_task&display=' . $presetCfg['uid']
					);

				}

					// render preset list
				$content .= $this->taskObject->renderListMenu($lines);
			} else {
					// no presets found
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->getLL('no-presets'),
					'',
					t3lib_FlashMessage::NOTICE
				);
				$content .= $flashMessage->render();
			}
		}

		return $content;
	}


	/**
	 * Select presets for this user
	 *
	 * @return	array		Array of preset records
	 */
	protected function getPresets() {
		$presets = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_impexp_presets',
			'(public > 0 OR user_uid=' . $GLOBALS['BE_USER']->user['uid'] . ')',
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
	protected function userTempFolder() {
		foreach($GLOBALS['FILEMOUNTS'] as $filePathInfo) {
			$tempFolder = $filePathInfo['path'] . '_temp_/';
			if (@is_dir($tempFolder)) {
				return $tempFolder;
			}
		}

		return '';
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/task/class.tx_impexp_task.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/task/class.tx_impexp_task.php']);
}

?>