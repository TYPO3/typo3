<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Julian Kleinhans <typo3@kj187.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Helper class for the 'recycler' extension.
 *
 * @author	Julian Kleinhans <typo3@kj187.de>
 * @package	TYPO3
 * @subpackage	tx_recycler
 * @version $Id$
 */
class tx_recycler_helper {

	/************************************************************
	 * USER ACCESS
	 *
	 *
	 ************************************************************/

	/**
	 * Checks the page access rights (Code for access check mostly taken from alt_doc.php)
	 * as well as the table access rights of the user.
	 *
	 * @param	string		$cmd: The command that sould be performed ('new' or 'edit')
	 * @param	string		$table: The table to check access for
	 * @param	string		$theUid: The record uid of the table
	 * @return	boolean		Returns true is the user has access, or false if not
	 */
	public static function checkAccess($table, $row) {
		// Checking if the user has permissions? (Only working as a precaution, because the final permission check is always down in TCE. But it's good to notify the user on beforehand...)
		// First, resetting flags.
		$hasAccess = 0;
		$deniedAccessReason = '';

		$calcPRec = $row;
		t3lib_BEfunc::fixVersioningPid($table,$calcPRec);
		if (is_array($calcPRec)) {
			if ($table=='pages') {	// If pages:
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms($calcPRec);
				$hasAccess = $CALC_PERMS & 2 ? 1 : 0;
			} else {
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms(t3lib_BEfunc::getRecord('pages',$calcPRec['pid']));	// Fetching pid-record first.
				$hasAccess = $CALC_PERMS & 16 ? 1 : 0;
			}
					// Check internals regarding access:
			if ($hasAccess) {
				$hasAccess = $GLOBALS['BE_USER']->recordEditAccessInternals($table, $calcPRec);
			}
		}


		if (!$GLOBALS['BE_USER']->check('tables_modify', $table)) {
			$hasAccess = 0;
		}

		if (!$hasAccess) {
			$deniedAccessReason = $GLOBALS['BE_USER']->errorMsg;
			if ($deniedAccessReason) {
				//fb($deniedAccessReason);
			}
		}

		return $hasAccess ? true : false;
	}

	/**
	 * Returns the path (visually) of a page $uid, fx. "/First page/Second page/Another subpage"
	 * Each part of the path will be limited to $titleLimit characters
	 * Deleted pages are filtered out.
	 *
	 * @param	integer		Page uid for which to create record path
	 * @param	string		$clause is additional where clauses, eg. "
	 * @param	integer		Title limit
	 * @param	integer		Title limit of Full title (typ. set to 1000 or so)
	 * @return	mixed		Path of record (string) OR array with short/long title if $fullTitleLimit is set.
	 */
	public static function getRecordPath($uid, $clause='', $titleLimit=1000, $fullTitleLimit = 0) {
		$loopCheck = 100;
		$output = $fullOutput = '/';

		while ($uid != 0 && $loopCheck > 0) {
			$loopCheck--;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid,pid,title,deleted,t3ver_oid,t3ver_wsid,t3ver_swapmode',
				'pages',
				'uid=' . intval($uid) . (strlen(trim($clause)) ? ' AND ' . $clause : '')
			);

			if (is_resource($res)) {
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$GLOBALS['TYPO3_DB']->sql_free_result($res);

				t3lib_BEfunc::workspaceOL('pages', $row);
				if (is_array($row)) {
					t3lib_BEfunc::fixVersioningPid('pages', $row);

					if ($row['_ORIG_pid'] && $row['t3ver_swapmode'] > 0) {	// Branch points
						$output = ' [#VEP#]' . $output;		// Adding visual token - Versioning Entry Point - that tells that THIS position was where the versionized branch got connected to the main tree. I will have to find a better name or something...
					}
					$uid = $row['pid'];
					$output = '/' . t3lib_div::fixed_lgd_cs(strip_tags($row['title']), $titleLimit) . $output;

					if ($row['deleted']) {
						$output = '<span class="deletedPath">' . $output . '</span>';
					}

					if ($fullTitleLimit) $fullOutput = '/' . t3lib_div::fixed_lgd_cs(strip_tags($row['title']), $fullTitleLimit) . $fullOutput;
				} else {
					break;
				}
			} else {
				break;
			}
		}

		if ($fullTitleLimit) {
			return array($output, $fullOutput);
		} else {
			return $output;
		}
	}

	/**
	 * Gets the name of the field with the information whether a record is deleted.
	 *
	 * @param	string		$tableName: Name of the table to get the deleted field for
	 * @return	string		Name of the field with the information whether a record is deleted
	 */
	public static function getDeletedField($tableName) {
		$TCA = self::getTableTCA($tableName);
		if ($TCA && isset($TCA['ctrl']['delete']) && $TCA['ctrl']['delete']) {
			return $TCA['ctrl']['delete'];
		}
	}

	/**
	 * Gets the TCA of the table used in the current context.
	 *
	 * @param	string		$tableName: Name of the table to get TCA for
	 * @return	mixed		TCA of the table used in the current context (array)
	 *						or false (boolean) if something went wrong
	 */
	public static function getTableTCA($tableName) {
		$TCA = false;
		if (isset($GLOBALS['TCA'][$tableName])) {
			$TCA = $GLOBALS['TCA'][$tableName];
		}
		return $TCA;
	}

	/**
	 * Gets the current backend charset.
	 *
	 * @return	string		The current backend charset
	 */
	public static function getCurrentCharset() {
		return $GLOBALS['LANG']->csConvObj->parse_charset($GLOBALS['LANG']->charSet);
	}

	/**
	 * Determines whether the current charset is not UTF-8
	 *
	 * @return	boolean		Whether the current charset is not UTF-8
	 */
	public static function isNotUtf8Charset() {
		return (self::getCurrentCharset() !== 'utf-8');
	}

	/**
	 * Gets an UTF-8 encoded string (only if the current charset is not UTF-8!).
	 *
	 * @param	string		$string: String to be converted to UTF-8 if required
	 * @return	string		UTF-8 encoded string
	 */
	public static function getUtf8String($string) {
		if (self::isNotUtf8Charset()) {
			$string = $GLOBALS['LANG']->csConvObj->utf8_encode(
				$string,
				self::getCurrentCharset()
			);
		}
		return $string;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/helper/class.tx_recycler_helper.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/recycler/classes/helper/class.tx_recycler_helper.php']);
}

?>