<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Ingo Renner <ingo@typo3.org>
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
 * Extending class to render the menu for the cache clearing actions, and adding Clear RTE cache option
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @author	Steffen kamper <info@sk-typo3.de>
 * @package	TYPO3
 */

require_once (PATH_typo3.'interfaces/interface.backend_cacheActionsHook.php');

class tx_rtehtmlarea_clearcachemenu implements backend_cacheActionsHook {
	/**
	 * modifies CacheMenuItems array
	 *
	 * @param	array	array of CacheMenuItems
	 * @param	array	array of AccessConfigurations-identifiers (typically  used by userTS with options.clearCache.identifier)
	 * @return	void
	 */
	 public function manipulateCacheActions(&$cacheActions, &$optionValues) {
	 	if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.clearRTECache')) {
				// Add new cache menu item
			$title = $GLOBALS['LANG']->sL('LLL:EXT:rtehtmlarea/hooks/clearrtecache/locallang.xml:title');
			$cacheActions[] = array(
				'id'    => 'clearRTECache',
				'title' => $title,
				'href'  => $GLOBALS['BACK_PATH'] . 'ajax.php?ajaxID=rtehtmlarea::clearTempDir',
				'icon'  => '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'sysext/rtehtmlarea/hooks/clearrtecache/clearrtecache.png', 'width="16" height="16"').' title="'.$title.'" alt="'.$title.'" />'
				//'icon'  => '<img src="' . t3lib_extMgm::extRelPath('rtehtmlarea') . 'hooks/clearrtecache/clearrtecache.png" width="16" height="16" title="'.htmlspecialchars($title).'" alt="" />'
			);
			$optionValues[] = 'clearRTECache';
		}
	 }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/hooks/clearrtecache/class.tx_rtehtmlarea_clearcachemenu.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/hooks/clearrtecache/class.tx_rtehtmlarea_clearcachemenu.php']);
}
?>
