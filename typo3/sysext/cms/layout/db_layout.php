<?php
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
 * Module: Web>Page
 *
 * This module lets you view a page in a more Content Management like style than the ordinary record-list
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
unset($MCONF);
require 'conf.php';
require $BACK_PATH . 'init.php';
$LANG->includeLLFile('EXT:cms/layout/locallang.xml');
require_once 'class.tx_cms_layout.php';
$BE_USER->modAccess($MCONF, 1);
// Will open up records locked by current user. It's assumed that the locking should end if this script is hit.
\TYPO3\CMS\Backend\Utility\BackendUtility::lockRecords();
// Exits if 'cms' extension is not loaded:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms', 1);
/**
 * Local extension of position map class
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ext_posMap extends \TYPO3\CMS\Backend\Tree\View\PagePositionMap {

	/**
	 * @todo Define visibility
	 */
	public $dontPrintPageInsertIcons = 1;

	/**
	 * @todo Define visibility
	 */
	public $l_insertNewRecordHere = 'newContentElement';

	/**
	 * Wrapping the title of the record.
	 *
	 * @param string $str The title value.
	 * @param array $row The record row.
	 * @return string Wrapped title string.
	 * @todo Define visibility
	 */
	public function wrapRecordTitle($str, $row) {
		$aOnClick = 'jumpToUrl(\'' . $GLOBALS['SOBE']->local_linkThisScript(array('edit_record' => ('tt_content:' . $row['uid']))) . '\');return false;';
		return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $str . '</a>';
	}

	/**
	 * Wrapping the column header
	 *
	 * @param string $str Header value
	 * @param string $vv Column info.
	 * @return string
	 * @see printRecordMap()
	 * @todo Define visibility
	 */
	public function wrapColumnHeader($str, $vv) {
		$aOnClick = 'jumpToUrl(\'' . $GLOBALS['SOBE']->local_linkThisScript(array('edit_record' => ('_EDIT_COL:' . $vv))) . '\');return false;';
		return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $str . '</a>';
	}

	/**
	 * Create on-click event value.
	 *
	 * @param array $row The record.
	 * @param string $vv Column position value.
	 * @param integer $moveUid Move uid
	 * @param integer $pid PID value.
	 * @param integer $sys_lang System language
	 * @return string
	 * @todo Define visibility
	 */
	public function onClickInsertRecord($row, $vv, $moveUid, $pid, $sys_lang = 0) {
		if (is_array($row)) {
			$location = $GLOBALS['SOBE']->local_linkThisScript(array('edit_record' => 'tt_content:new/-' . $row['uid'] . '/' . $row['colPos']));
		} else {
			$location = $GLOBALS['SOBE']->local_linkThisScript(array('edit_record' => 'tt_content:new/' . $pid . '/' . $vv));
		}
		return 'jumpToUrl(\'' . $location . '\');return false;';
	}

	/**
	 * Wrapping the record header  (from getRecordHeader())
	 *
	 * @param string $str HTML content
	 * @param array $row Record array.
	 * @return string HTML content
	 * @todo Define visibility
	 */
	public function wrapRecordHeader($str, $row) {
		if ($row['uid'] == $this->moveUid) {
			return '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/content_client.gif', 'width="7" height="10"') . ' alt="" />' . $str;
		} else {
			return $str;
		}
	}

}

/*
 * @deprecated since 6.0, the classname SC_db_layout and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/Controller/PageLayoutController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/Controller/PageLayoutController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\PageLayoutController');
$SOBE->init();
// Include files?
foreach ($SOBE->include_once as $INC_FILE) {
	include_once $INC_FILE;
}
$SOBE->clearCache();
$SOBE->main();
$SOBE->printContent();
?>