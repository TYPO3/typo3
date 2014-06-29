<?php
namespace TYPO3\CMS\Frontend\Controller;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class for displaying page information (records, page record properties)
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class PageInformationController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * Returns the menu array
	 *
	 * @return 	array
	 * @todo Define visibility
	 */
	public function modMenu() {
		global $LANG;
		return array(
			'pages' => array(
				0 => $LANG->sL('LLL:EXT:cms/web_info/locallang.xlf:pages_0'),
				2 => $LANG->sL('LLL:EXT:cms/web_info/locallang.xlf:pages_2'),
				1 => $LANG->sL('LLL:EXT:cms/web_info/locallang.xlf:pages_1')
			),
			'stat_type' => array(
				0 => $LANG->sL('LLL:EXT:cms/web_info/locallang.xlf:stat_type_0'),
				1 => $LANG->sL('LLL:EXT:cms/web_info/locallang.xlf:stat_type_1'),
				2 => $LANG->sL('LLL:EXT:cms/web_info/locallang.xlf:stat_type_2')
			),
			'depth' => array(
				0 => $LANG->getLL('depth_0'),
				1 => $LANG->getLL('depth_1'),
				2 => $LANG->getLL('depth_2'),
				3 => $LANG->getLL('depth_3'),
				999 => $LANG->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
			)
		);
	}

	/**
	 * MAIN function for page information display
	 *
	 * @return string Output HTML for the module.
	 * @todo Define visibility
	 */
	public function main() {
		global $BACK_PATH, $LANG, $SOBE;
		$dblist = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\View\\PageLayoutView');
		$dblist->descrTable = '_MOD_' . $GLOBALS['MCONF']['name'];
		$dblist->backPath = $BACK_PATH;
		$dblist->thumbs = 0;
		$dblist->script = BackendUtility::getModuleUrl('web_info');
		$dblist->showIcon = 0;
		$dblist->setLMargin = 0;
		$dblist->agePrefixes = $LANG->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears');
		$dblist->pI_showUser = 1;
		// PAGES:
		$this->pObj->MOD_SETTINGS['pages_levels'] = $this->pObj->MOD_SETTINGS['depth'];
		// ONLY for the sake of dblist module which uses this value.
		$h_func = BackendUtility::getFuncMenu($this->pObj->id, 'SET[depth]', $this->pObj->MOD_SETTINGS['depth'], $this->pObj->MOD_MENU['depth']);
		$h_func .= BackendUtility::getFuncMenu($this->pObj->id, 'SET[pages]', $this->pObj->MOD_SETTINGS['pages'], $this->pObj->MOD_MENU['pages']);
		$dblist->start($this->pObj->id, 'pages', 0);
		$dblist->generateList();
		// CSH
		$theOutput = $this->pObj->doc->header($LANG->sL('LLL:EXT:cms/web_info/locallang.xlf:page_title'));
		$theOutput .= $this->pObj->doc->section('', BackendUtility::cshItem($dblist->descrTable, 'pagetree_overview', $GLOBALS['BACK_PATH'], '|<br />') . $h_func . $dblist->HTMLcode, 0, 1);
		// Additional footer content
		$footerContentHook = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook'];
		if (is_array($footerContentHook)) {
			foreach ($footerContentHook as $hook) {
				$params = array();
				$theOutput .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $params, $this);
			}
		}
		return $theOutput;
	}

}
