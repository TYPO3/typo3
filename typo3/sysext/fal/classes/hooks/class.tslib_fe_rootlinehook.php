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
 * File Abtraction Layer: hook for modifying the rootline array
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id:  $
 */
class tx_fal_tslibfe_rootlinehook {

	/**
	 * modifies the rootline to get the uid of a language overlay record
	 *
	 *
	 * @param array $params 	not used
	 * @param string $pObj	the parent object
	 * @return void
	 */
	function modifyRootline(&$params, $pObj) {
		if (is_array($pObj->tmpl->rootLine)) {
			$repo = t3lib_div::makeInstance('tx_fal_Repository');
			foreach ($pObj->tmpl->rootLine as $rLk => $value) {
				// check if language overlay exists
				if (isset($value['_PAGES_OVERLAY_UID'])) {
					$referenceUid = $value['_PAGES_OVERLAY_UID'];
					$referenceTable = 'pages_language_overlay';
				} else {
					$referenceUid = $value['uid'];
					$referenceTable = 'pages';
				}
				if (intval($referenceUid) > 0) {
					$references = $repo->getFilesFromRelation('media_rel', $referenceTable, $referenceUid);
					$value['media'] = tx_fal_Helper::createCsvListOfFilepaths($references);
				}
				$pObj->tmpl->rootLine[$rLk] = $value;
			}
		}
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tslib_fe_rootlinehook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tslib_fe_rootlinehook.php']);
}
?>