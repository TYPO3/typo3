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
 * New content elements wizard
 * (Part of the 'cms' extension)
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compatible.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
unset($MCONF);
require 'conf.php';
require $BACK_PATH . 'init.php';
// Unset MCONF/MLANG since all we wanted was back path etc. for this particular script.
unset($MCONF);
unset($MLANG);
// Merging locallang files/arrays:
$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_misc.xlf');
$LOCAL_LANG_orig = $LOCAL_LANG;
$LANG->includeLLFile('EXT:cms/layout/locallang_db_new_content_el.xml');
$LOCAL_LANG = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($LOCAL_LANG_orig, $LOCAL_LANG);
// Exits if 'cms' extension is not loaded:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cms', 1);
/**
 * Local position map class
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ext_posMap extends \TYPO3\CMS\Backend\Tree\View\PagePositionMap {

	/**
	 * @todo Define visibility
	 */
	public $dontPrintPageInsertIcons = 1;

	/**
	 * Wrapping the title of the record - here we just return it.
	 *
	 * @param string $str The title value.
	 * @param array $row The record row.
	 * @return string Wrapped title string.
	 * @todo Define visibility
	 */
	public function wrapRecordTitle($str, $row) {
		return $str;
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
		$table = 'tt_content';
		$location = $this->backPath . 'alt_doc.php?edit[tt_content][' . (is_array($row) ? -$row['uid'] : $pid) . ']=new&defVals[tt_content][colPos]=' . $vv . '&defVals[tt_content][sys_language_uid]=' . $sys_lang . '&returnUrl=' . rawurlencode($GLOBALS['SOBE']->R_URI);
		return 'window.location.href=\'' . $location . '\'+document.editForm.defValues.value; return false;';
	}

}

/*
 * @deprecated since 6.0, the classname SC_db_new_content_el and this file is obsolete
 * and will be removed with 6.2. The class was renamed and is now located at:
 * typo3/sysext/backend/Classes/Controller/ContentElement/NewContentElementController.php
 */
require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/Controller/ContentElement/NewContentElementController.php';
// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\ContentElement\\NewContentElementController');
$SOBE->init();
// Include files?
foreach ($SOBE->include_once as $INC_FILE) {
	include_once $INC_FILE;
}
$SOBE->main();
$SOBE->printContent();
?>