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
 * Move element wizard:
 * Moving pages or content elements (tt_content) around in the system via a page tree navigation.
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compatible.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
$BACK_PATH = '';
require 'init.php';
// Include local language labels:
$LANG->includeLLFile('EXT:lang/locallang_misc.xlf');
/**
 * Local extension of the page tree class
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class localPageTree extends \TYPO3\CMS\Backend\Tree\View\PageTreeView {

	/**
	 * Inserting uid-information in title-text for an icon
	 *
	 * @param string $icon Icon image
	 * @param array $row Item row
	 * @return string Wrapping icon image.
	 * @todo Define visibility
	 */
	public function wrapIcon($icon, $row) {
		return $this->addTagAttributes($icon, ' title="id=' . htmlspecialchars($row['uid']) . '"');
	}

}

/**
 * Extension of position map for pages
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ext_posMap_pages extends \TYPO3\CMS\Backend\Tree\View\PagePositionMap {

	/**
	 * @todo Define visibility
	 */
	public $l_insertNewPageHere = 'movePageToHere';

	/**
	 * Creates the onclick event for the insert-icons.
	 *
	 * @param integer $pid The pid.
	 * @param integer $newPagePID New page id.
	 * @return string Onclick attribute content
	 * @todo Define visibility
	 */
	public function onClickEvent($pid, $newPagePID) {
		return 'window.location.href=\'tce_db.php?cmd[pages][' . $GLOBALS['SOBE']->moveUid . '][' . $this->moveOrCopy . ']=' . $pid . '&redirect=' . rawurlencode($this->R_URI) . '&prErr=1&uPT=1&vC=' . $GLOBALS['BE_USER']->veriCode() . \TYPO3\CMS\Backend\Utility\BackendUtility::getUrlToken('tceAction') . '\';return false;';
	}

	/**
	 * Wrapping page title.
	 *
	 * @param string $str Page title.
	 * @param array $rec Page record (?)
	 * @return string Wrapped title.
	 * @todo Define visibility
	 */
	public function linkPageTitle($str, $rec) {
		$url = \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('uid' => intval($rec['uid']), 'moveUid' => $GLOBALS['SOBE']->moveUid));
		return '<a href="' . htmlspecialchars($url) . '">' . $str . '</a>';
	}

	/**
	 * Wrap $t_code in bold IF the $dat uid matches $id
	 *
	 * @param string $t_code Title string
	 * @param array $dat Infomation array with record array inside.
	 * @param integer $id The current id.
	 * @return string The title string.
	 * @todo Define visibility
	 */
	public function boldTitle($t_code, $dat, $id) {
		return parent::boldTitle($t_code, $dat, $GLOBALS['SOBE']->moveUid);
	}

}

/**
 * Extension of position map for content elements
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ext_posMap_tt_content extends \TYPO3\CMS\Backend\Tree\View\PagePositionMap {

	/**
	 * @todo Define visibility
	 */
	public $dontPrintPageInsertIcons = 1;

	/**
	 * Wrapping page title.
	 *
	 * @param string $str Page title.
	 * @param array $rec Page record (?)
	 * @return string Wrapped title.
	 * @todo Define visibility
	 */
	public function linkPageTitle($str, $rec) {
		$url = \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript(array('uid' => intval($rec['uid']), 'moveUid' => $GLOBALS['SOBE']->moveUid));
		return '<a href="' . htmlspecialchars($url) . '">' . $str . '</a>';
	}

	/**
	 * Wrapping the title of the record.
	 *
	 * @param string $str The title value.
	 * @param array $row The record row.
	 * @return string Wrapped title string.
	 * @todo Define visibility
	 */
	public function wrapRecordTitle($str, $row) {
		if ($GLOBALS['SOBE']->moveUid == $row['uid']) {
			$str = '<strong>' . $str . '</strong>';
		}
		return parent::wrapRecordTitle($str, $row);
	}

}

/*
 * @deprecated since 6.0, the classname SC_move_el and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/Controller/ContentElement/MoveElementController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/Controller/ContentElement/MoveElementController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\ContentElement\\MoveElementController');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>