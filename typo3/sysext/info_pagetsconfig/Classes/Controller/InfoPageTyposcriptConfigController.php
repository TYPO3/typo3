<?php
namespace TYPO3\CMS\InfoPagetsconfig\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains class for Page TSconfig wizard
 *
 * Revised for TYPO3 3.7 June/2004 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Page TSconfig viewer
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class InfoPageTyposcriptConfigController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * Function menu initialization
	 *
	 * @return array Menu array
	 * @todo Define visibility
	 */
	public function modMenu() {
		global $LANG;
		$modMenuAdd = array(
			'tsconf_parts' => array(
				0 => $LANG->getLL('tsconf_parts_0'),
				1 => $LANG->getLL('tsconf_parts_1'),
				'1a' => $LANG->getLL('tsconf_parts_1a'),
				'1b' => $LANG->getLL('tsconf_parts_1b'),
				'1c' => $LANG->getLL('tsconf_parts_1c'),
				'1d' => $LANG->getLL('tsconf_parts_1d'),
				'1e' => $LANG->getLL('tsconf_parts_1e'),
				'1f' => $LANG->getLL('tsconf_parts_1f'),
				'1g' => $LANG->getLL('tsconf_parts_1g'),
				2 => 'RTE.',
				5 => 'TCEFORM.',
				6 => 'TCEMAIN.',
				3 => 'TSFE.',
				4 => 'user.',
				99 => $LANG->getLL('tsconf_configFields')
			),
			'tsconf_alphaSort' => '1'
		);
		if (!$GLOBALS['BE_USER']->isAdmin()) {
			unset($modMenuAdd['tsconf_parts'][99]);
		}
		return $modMenuAdd;
	}

	/**
	 * Main function of class
	 *
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function main() {
		global $LANG;
		$menu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->pObj->id, 'SET[tsconf_parts]', $this->pObj->MOD_SETTINGS['tsconf_parts'], $this->pObj->MOD_MENU['tsconf_parts']);
		$menu .= '<br /><label for="checkTsconf_alphaSort">' . $GLOBALS['LANG']->getLL('sort_alphabetic', TRUE) . '</label> ' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->pObj->id, 'SET[tsconf_alphaSort]', $this->pObj->MOD_SETTINGS['tsconf_alphaSort'], '', '', 'id="checkTsconf_alphaSort"');
		$menu .= '<br /><br />';
		$theOutput = $this->pObj->doc->header($LANG->getLL('tsconf_title'));
		if ($this->pObj->MOD_SETTINGS['tsconf_parts'] == 99) {
			$TSparts = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id, '', 1);
			$lines = array();
			$pUids = array();
			foreach ($TSparts as $k => $v) {
				if ($k != 'uid_0') {
					if ($k == 'defaultPageTSconfig') {
						$pTitle = '<strong>' . $GLOBALS['LANG']->getLL('editTSconfig_default', 1) . '</strong>';
						$editIcon = '';
					} else {
						$pUids[] = substr($k, 4);
						$row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', substr($k, 4));
						$pTitle = $this->pObj->doc->getHeader('pages', $row, '', 0);
						$editIdList = substr($k, 4);
						$params = '&edit[pages][' . $editIdList . ']=edit&columnsOnly=TSconfig';
						$onclickUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'], '');
						$editIcon = '<a href="#" onclick="' . htmlspecialchars($onclickUrl) . '" title="' . $GLOBALS['LANG']->getLL('editTSconfig', 1) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
					}
					$TScontent = nl2br(htmlspecialchars(trim($v) . chr(10)));
					$tsparser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
					$tsparser->lineNumberOffset = 0;
					$TScontent = $tsparser->doSyntaxHighlight(trim($v) . LF, '', 0);
					$lines[] = '
						<tr><td nowrap="nowrap" class="bgColor5">' . $pTitle . '</td></tr>
						<tr><td nowrap="nowrap" class="bgColor4">' . $TScontent . $editIcon . '</td></tr>
						<tr><td>&nbsp;</td></tr>
					';
				}
			}
			if (count($pUids)) {
				$params = '&edit[pages][' . implode(',', $pUids) . ']=edit&columnsOnly=TSconfig';
				$onclickUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick($params, $GLOBALS['BACK_PATH'], '');
				$editIcon = '<a href="#" onclick="' . htmlspecialchars($onclickUrl) . '" title="' . $GLOBALS['LANG']->getLL('editTSconfig_all', 1) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '<strong>' . $GLOBALS['LANG']->getLL('editTSconfig_all', 1) . '</strong>' . '</a>';
			} else {
				$editIcon = '';
			}
			$theOutput .= $this->pObj->doc->section('', \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem(('_MOD_' . $GLOBALS['MCONF']['name']), 'tsconfig_edit', $GLOBALS['BACK_PATH'], '|<br />') . $menu . '
					<br /><br />

					<!-- Edit fields: -->
					<table border="0" cellpadding="0" cellspacing="1">' . implode('', $lines) . '</table><br />' . $editIcon, 0, 1);
		} else {
			// Defined global here!
			$tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\ExtendedTemplateService');
			// Do not log time-performance information
			$tmpl->tt_track = 0;
			$tmpl->fixedLgd = 0;
			$tmpl->linkObjects = 0;
			$tmpl->bType = '';
			$tmpl->ext_expandAllNotes = 1;
			$tmpl->ext_noPMicons = 1;
			switch ($this->pObj->MOD_SETTINGS['tsconf_parts']) {
			case '1':
				$modTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($this->pObj->id, 'mod');
				break;
			case '1a':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('mod.web_layout', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '1b':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('mod.web_view', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '1c':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('mod.web_modules', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '1d':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('mod.web_list', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '1e':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('mod.web_info', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '1f':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('mod.web_func', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '1g':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('mod.web_ts', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '2':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('RTE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '5':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('TCEFORM', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '6':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('TCEMAIN', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '3':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('TSFE', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			case '4':
				$modTSconfig = $GLOBALS['BE_USER']->getTSConfig('user', \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id));
				break;
			default:
				$modTSconfig['properties'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($this->pObj->id);
				break;
			}
			$modTSconfig = $modTSconfig['properties'];
			if (!is_array($modTSconfig)) {
				$modTSconfig = array();
			}
			$theOutput .= $this->pObj->doc->section('', \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem(('_MOD_' . $GLOBALS['MCONF']['name']), 'tsconfig_hierarchy', $GLOBALS['BACK_PATH'], '|<br />') . $menu . '

					<!-- Page TSconfig Tree: -->
					<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td nowrap="nowrap">' . $tmpl->ext_getObjTree($modTSconfig, '', '', '', '', $this->pObj->MOD_SETTINGS['tsconf_alphaSort']) . '</td>
						</tr>
					</table>', 0, 1);
		}
		// Return output:
		return $theOutput;
	}

}


?>