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
 * File Abtraction Layer hook for class.t3lib_extfilefunc.php
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id $
 */

class tx_fal_hooks_ExtFileFuncHook implements t3lib_extFileFunctions_processDataHook {
	/**
	 * Post-process a file action.
	 *
	 * @param string The action
	 * @param array The parameter sent to the action handler
	 * @param array The results of all calls to the action handler
	 * @param t3lib_extFileFunctions Parent t3lib_extFileFunctions object
	 * @return void
	 */
	public function processData_postProcessAction($action, array $cmdArr, array $result, t3lib_extFileFunctions $parentObject) {
//		t3lib_div::devLog('processData_postProcessAction', 'tx_fal');
		switch($action) {
			case 'upload':
				foreach ($result as $filePath) {
					//if ($fileName) {
						t3lib_div::devLog('processData_postProcessAction[upload]: ' . $filePath, 'tx_fal');
						$mount = tx_fal_Helper::getMountFromFilePath($filePath);
						tx_fal_Indexer::addFileToIndex($mount, $filePath);
					//}
				}
		}
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tx_fal_hooks_extfilefunchook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/hooks/class.tx_fal_hooks_extfilefunchook.php']);
}
?>