<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * backend post construct hook
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id:  $
 */


class tx_fal_backendhook {
	/**
	 * adds a part of the extjs filelist module to the global
	 * framework if fal is loaded to be used in fal references
	 * tce forms upload dialog
	 * @param unknown_type $params
	 * @param TYPO3backend $parentObject
	 */
	function constructPostProcess($params, TYPO3backend $parentObject) {
		global $TYPO3_CONF_VARS;
		$falPath = t3lib_extMgm::extRelPath('fal');
		/** @var t3lib_PageRenderer $pageRenderer */
		$pageRenderer = $GLOBALS['TBE_TEMPLATE']->getPageRenderer();
		$pageRenderer->addJsFile('ajax.php?ajaxID=ExtDirect::getAPI&namespace=TYPO3.FILELIST', NULL, FALSE);
		$parentObject->addJavascriptFile($falPath . 'contrib/plupload/js/gears_init.js');
		$parentObject->addJavascriptFile($falPath . 'contrib/plupload/js/plupload.full.min.js');
		$parentObject->addJavascriptFile($falPath . 'res/js/plupload/ext.ux.plupload.js');
		$parentObject->addCssFile('ext.ux.plupload', $falPath . 'res/js/plupload/ext.ux.plupload.css');
		$parentObject->addJavascriptFile($falPath . 'res/js/plupload/plupload.js');

		tx_fal_list_Registry::addEbExtDirectNamespacesToPage($pageRenderer);
		tx_fal_list_Registry::addEbJsComponentsToPage($pageRenderer);
		tx_fal_list_Registry::addEbCssComponentsToPage($pageRenderer);

	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tx_fal_backendhook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tx_fal_backendhook.php']);
}
?>