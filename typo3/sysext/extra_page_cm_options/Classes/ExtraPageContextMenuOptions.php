<?php
namespace TYPO3\CMS\ExtraPageCmOptions;

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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * Class to add extra context menu options
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ExtraPageContextMenuOptions {

	/**
	 * Adding various standard options to the context menu.
	 * This includes both first and second level.
	 *
	 * @param object $backRef The calling object. Value by reference.
	 * @param array $menuItems Array with the currently collected menu items to show.
	 * @param string $table Table name of clicked item.
	 * @param integer $uid UID of clicked item.
	 * @return array Modified $menuItems array
	 */
	public function main(&$backRef, $menuItems, $table, $uid) {
		// Accumulation of local items.
		$localItems = array();
		$subname = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('subname');
		// Detecting menu level
		// LEVEL: Primary menu.
		if (!in_array('moreoptions', $backRef->disabledItems) && !$backRef->cmLevel) {
			// Creating menu items here:
			if ($backRef->editOK) {
				$localLanguage = $GLOBALS['LANG']->includeLLFile('EXT:extra_page_cm_options/locallang.xlf', FALSE);
				$localItems[] = 'spacer';
				$localItems['moreoptions'] = $backRef->linkItem(
					$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->getLLL('label', $localLanguage)),
					$backRef->excludeIcon(''),
					'top.loadTopMenu(\'' . \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript() . '&cmLevel=1&subname=moreoptions\');return false;',
					0,
					1
				);
				$menuItemHideUnhideAllowed = FALSE;
				$hiddenField = '';
				// Check if column for disabled is defined
				if (isset($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']) && isset($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'])) {
					$hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
					if (
						$hiddenField !== '' && !empty($GLOBALS['TCA'][$table]['columns'][$hiddenField])
						&& (!empty($GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude'])
							&& $GLOBALS['BE_USER']->check('non_exclude_fields', $table . ':' . $hiddenField))
					) {
						$menuItemHideUnhideAllowed = TRUE;
					}
				}
				if ($menuItemHideUnhideAllowed && !in_array('hide', $backRef->disabledItems)) {
					$localItems['hide'] = $backRef->DB_hideUnhide($table, $backRef->rec, $hiddenField);
				}
				$anyEnableColumnsFieldAllowed = FALSE;
				// Check if columns are defined
				if (isset($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
					$columnsToCheck = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'];
					if ($table === 'pages' && !empty($columnsToCheck)) {
						$columnsToCheck[] = 'extendToSubpages';
					}
					foreach ($columnsToCheck as $currentColumn) {
						if (
							isset($GLOBALS['TCA'][$table]['columns'][$currentColumn])
							&& (!empty($GLOBALS['TCA'][$table]['columns'][$currentColumn]['exclude'])
								&& $GLOBALS['BE_USER']->check('non_exclude_fields', $table . ':' . $currentColumn))
						) {
							$anyEnableColumnsFieldAllowed = TRUE;
						}
					}
				}
				if ($anyEnableColumnsFieldAllowed && !in_array('edit_access', $backRef->disabledItems)) {
					$localItems['edit_access'] = $backRef->DB_editAccess($table, $uid);
				}
				if ($table === 'pages' && $backRef->editPageIconSet && !in_array('edit_pageproperties', $backRef->disabledItems)) {
					$localItems['edit_pageproperties'] = $backRef->DB_editPageProperties($uid);
				}
			}
			// Find delete element among the input menu items and insert the local items just before that:
			$c = 0;
			$deleteFound = FALSE;
			foreach ($menuItems as $key => $value) {
				$c++;
				if ($key === 'delete') {
					$deleteFound = TRUE;
					break;
				}
			}
			if ($deleteFound) {
				// .. subtract two... (delete item + its spacer element...)
				$c -= 2;
				// and insert the items just before the delete element.
				array_splice($menuItems, $c, 0, $localItems);
			} else {
				$menuItems = array_merge($menuItems, $localItems);
			}
		} elseif ($subname === 'moreoptions') {
			// If the page can be edited, then show this:
			if ($backRef->editOK) {
				if (($table === 'pages' || $table === 'tt_content') && !in_array('move_wizard', $backRef->disabledItems)) {
					$localItems['move_wizard'] = $backRef->DB_moveWizard($table, $uid, $backRef->rec);
				}
				if (($table === 'pages' || $table === 'tt_content') && !in_array('new_wizard', $backRef->disabledItems)) {
					$localItems['new_wizard'] = $backRef->DB_newWizard($table, $uid, $backRef->rec);
				}
				if ($table === 'pages' && !in_array('perms', $backRef->disabledItems) && $GLOBALS['BE_USER']->check('modules', 'web_perm')) {
					$localItems['perms'] = $backRef->DB_perms($table, $uid, $backRef->rec);
				}
				if (!in_array('db_list', $backRef->disabledItems) && $GLOBALS['BE_USER']->check('modules', 'web_list')) {
					$localItems['db_list'] = $backRef->DB_db_list($table, $uid, $backRef->rec);
				}
			}
			// Temporary mount point item:
			if ($table === 'pages') {
				$localItems['temp_mount_point'] = $backRef->DB_tempMountPoint($uid);
			}
			// Merge the locally made items into the current menu items passed to this function.
			$menuItems = array_merge($menuItems, $localItems);
		}
		return $menuItems;
	}

}
