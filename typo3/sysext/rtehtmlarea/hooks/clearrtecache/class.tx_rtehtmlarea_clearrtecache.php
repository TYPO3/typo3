<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Steffen Kamper <info@sk-typo3.de>
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
 * Class to clear temp files of htmlArea RTE
 *
 * @author	Steffen kamper <info@sk-typo3.de>
 * @package TYPO3
 */
class tx_rtehtmlarea_clearrtecache {
	public static function clearTempDir() {
			// Delete all files in typo3temp/rtehtmlarea
		$tempPath = t3lib_div::resolveBackPath(PATH_typo3.'../typo3temp/rtehtmlarea/');
		$handle = @opendir($tempPath);
		if ($handle !== FALSE) {
			while (($file = readdir($handle)) !== FALSE) {
				if ($file != '.' && $file != '..') {
					$tempFile = $tempPath . $file;
					if (is_file($tempFile)) {
						unlink($tempFile);
					}
				}
			}
			closedir($handle);
		}
			// Delete all files in typo3temp/compressor with names that start with "htmlarea"
		$tempPath = t3lib_div::resolveBackPath(PATH_typo3.'../typo3temp/compressor/');
		$handle = @opendir($tempPath);
		if ($handle !== FALSE) {
			while (($file = readdir($handle)) !== FALSE) {
				if (substr($file, 0, 8) === 'htmlarea') {
					$tempFile = $tempPath . $file;
					if (is_file($tempFile)) {
						unlink($tempFile);
					}
				}
			}
			closedir($handle);
		}
			// Log the action
		$GLOBALS['BE_USER']->writelog(3, 1, 0, 0, 'htmlArea RTE: User %s has cleared the RTE cache', array($GLOBALS['BE_USER']->user['username']));
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/hooks/clearrtecache/class.tx_rtehtmlarea_clearrtecache.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/hooks/clearrtecache/class.tx_rtehtmlarea_clearrtecache.php']);
}
?>