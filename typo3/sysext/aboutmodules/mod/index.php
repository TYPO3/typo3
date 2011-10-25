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
 * 'About modules' script - the default start-up module.
 * Will display the list of main- and sub-modules available to the user.
 * Each module will be show with description and a link to the module.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage aboutmodules
 */
class SC_mod_help_aboutmodules_index {

	/**
	 * Default constructor
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_alt_intro.xml');
	}

	/**
	 * Render the module
	 *
	 * @return void
	 */
	public function render() {
		$GLOBALS['TBE_TEMPLATE']->divClass = $GLOBALS['TBE_TEMPLATE']->bodyTagId;
		$GLOBALS['TBE_TEMPLATE']->backPath = $GLOBALS['BACK_PATH'];

		$content = array();
		$content[] = '<div id="typo3-docheader"><div id="typo3-docheader-row1">&nbsp;</div></div>';
		$content[] = '<div id="typo3-alt-intro-php-sub">';

		$content[] = '<h1>TYPO3 ' . TYPO3_version . '<br />' . $GLOBALS['LANG']->getLL('introtext') . '</h1>';
		$content[] = '<p>' . t3lib_BEfunc::TYPO3_copyRightNotice() . '</p>';

		$content[] = t3lib_BEfunc::displayWarningMessages();
		$content[] = '<h3>' . $GLOBALS['LANG']->getLL('introtext2') . '</h3>';

			/** @var $loadModules t3lib_loadModules */
		$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$loadModules->observeWorkspaces = TRUE;
			// Load available backend modules to create the description overview.
		$loadModules->load($GLOBALS['TBE_MODULES']);
		$content[] = $this->renderModules($loadModules->modules);
		$content[] = '<br />';

			// End text: 'Features may vary depending on your website and permissions'
		$content[] = '<p class="c-features"><em>(' . $GLOBALS['LANG']->getLL('endText') . ')</em></p>';

		$content[] = '<br />';
		$content[] = '</div>';

		echo $GLOBALS['TBE_TEMPLATE']->render(
			'About modules',
			implode(LF, $content),
			TRUE
		);
	}

	/**
	 * Render an overview of modules and its sub modules
	 *
	 * @param array $modules is the output from load_modules class
	 * @return string Module list HTML
	 */
	protected function renderModules($modules) {
		$moduleHtml = array();

			// Traverse array with modules
		foreach ($modules as $moduleName => $moduleInfo) {
			$moduleKey = $moduleName . '_tab';

				// Create image icon
			$icon = @getimagesize($GLOBALS['LANG']->moduleLabels['tabs_images'][$moduleKey]);
			$iconHtml = '';
			if ($icon) {
				$iconPath = '../' . substr($GLOBALS['LANG']->moduleLabels['tabs_images'][$moduleKey], strlen(PATH_site));
				$iconHtml = '<img src="' . $iconPath . '" ' . $icon[3] . ' alt="" />';
			}

			$label = htmlspecialchars($GLOBALS['LANG']->moduleLabels['tabs'][$moduleKey]);
				// Creating main module link, if there are no sub modules
			if (!is_array($moduleInfo['sub'])) {
				$label = '<a href="#" onclick="top.goToModule(\'' . $moduleName . '\');return false;">' . $label . '</a>';
			}
			$label = '&nbsp;<strong>' . $label . '</strong>&nbsp;';

			$moduleHtml[] = '<tr class="c-mainitem"><td colspan="3">' . $iconHtml . $label . '</td></tr>';

				// Traverse sub modules
			$subHtml = array();
			if (is_array($moduleInfo['sub'])) {
				$subCount = 0;
				foreach ($moduleInfo['sub'] as $subName => $subInfo) {
					$subCount ++;
					if ($subCount === 1) {
						$subHtml[] = '
							<tr class="c-first">
								<td colspan="3"></td>
							</tr>
						';
					}

					$subKey = $moduleName . '_' . $subName . '_tab';

						// Create image icon
					$icon = @getimagesize($GLOBALS['LANG']->moduleLabels['tabs_images'][$subKey]);
					$iconHtml = '';
					if ($icon) {
						$iconPath = '../' . substr($GLOBALS['LANG']->moduleLabels['tabs_images'][$subKey], strlen(PATH_site));
						$iconHtml = '<img src="' . $iconPath . '" ' . $icon[3] . ' title="' . htmlspecialchars($GLOBALS['LANG']->moduleLabels['labels'][$subKey . 'label']) . '" alt="" />';
					}

						// Label for sub module
					$label = $GLOBALS['LANG']->moduleLabels['tabs'][$subKey];
					$labelDescription = ' title="' . htmlspecialchars($GLOBALS['LANG']->moduleLabels['labels'][$subKey . 'label']) . '"';
					$onClickString = htmlspecialchars('top.goToModule(\'' . $moduleName . '_' . $subName . '\');return false;');
					$linkedLabel = '<a href="#" onclick="' . $onClickString . '"' . $labelDescription . '>' . htmlspecialchars($label) . '</a>';

					$moduleLabel = htmlspecialchars($GLOBALS['LANG']->moduleLabels['labels'][$subKey . 'label']);
					$moduleLabelHtml = !empty($moduleLabel) ? '<strong>' . $moduleLabel . '</strong><br />' : '';
					$moduleDescription = $GLOBALS['LANG']->moduleLabels['labels'][$subKey . 'descr'];

					$subHtml[] = '<tr class="c-subitem-row">';
					$subHtml[] = '<td align="center">' . $iconHtml . '</td>';
					$subHtml[] = '<td>' . $linkedLabel . '&nbsp;&nbsp;</td>';

					if (!empty($moduleLabel) || !empty($moduleDescription)) {
						$subHtml[] = '<td class="module-description">' . $moduleLabelHtml . $moduleDescription . '</td>';
					} else {
						$subHtml[] = '<td>&nbsp;</td>';
					}

					$subHtml[] = '</tr>';
				}
			}

			if (count($subHtml) > 0) {
				$moduleHtml[] = implode(LF, $subHtml);
				$moduleHtml[] = '<tr class="c-endrow"><td colspan="3"></td></tr>';
			}
		}

		return '
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-about-modules">
				' . implode(LF, $moduleHtml) . '
			</table>
		';
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/aboutmodules/mod/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/aboutmodules/mod/index.php']);
}

t3lib_div::makeInstance('SC_mod_help_aboutmodules_index')->render();
?>