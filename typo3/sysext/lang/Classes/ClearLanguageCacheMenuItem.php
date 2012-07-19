<?php
namespace TYPO3\CMS\Lang;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Dominique Feyer <dominique.feyer@reelpeek.net>
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
 * Extending class to render the menu for the cache clearing actions, and adding Clear lang/l10n cache option
 *
 * @author Dominique Feyer <dominique.feyer@reelpeek.net>
 */
class ClearLanguageCacheMenuItem implements \TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface {

	/**
	 * Add cache menu item
	 *
	 * @param array $cacheActions
	 * @param array $optionValues
	 * @return void
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues) {
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.clearLangCache')) {
			// Add new cache menu item
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/hooks/clearcache/locallang.xlf:title');
			$cacheActions[] = array(
				'id' => 'clearLangCache',
				'title' => $title,
				'href' => $GLOBALS['BACK_PATH'] . 'ajax.php?ajaxID=lang::clearCache',
				'icon' => '<span class="t3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-cache-clear-impact-low"></span>'
			);
			$optionValues[] = 'clearLangCache';
		}
	}

}


?>