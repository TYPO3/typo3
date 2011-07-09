<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains class for display of backend log
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


/**
 * This class holds some functions used to display the sys_log table-content.
 * Used in the status-scripts and the log-module.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see tx_belog_webinfo, SC_mod_tools_log_index
 */
class t3lib_BEDisplayLog {
	var $lastTimeLabel = '';
	var $lastUserLabel = '';
	var $lastTypeLabel = '';
	var $lastActionLabel = '';

	var $detailsOn = 1; // If detailsOn, %s is substituted with values from the data-array (see getDetails())
	var $stripPath = 1; // This strips the path from any value in the data-array when the data-array is parsed through stripPath()
	var $wsArray = array(
		0 => 'LIVE',
		-1 => 'Draft',
	);

	var $be_user_Array = array(); // Username array (set externally)

	/**
	 * Initialize the log table array with header labels.
	 *
	 * @return	array
	 */
	function initArray() {
		$codeArr = array();
		$codeArr[0][] = 'Time'; // Time
		$codeArr[0][] = 'User';
		$codeArr[0][] = 'Type';
		$codeArr[0][] = 'Error';
		$codeArr[0][] = 'Action';
		$codeArr[0][] = 'Details';
		return $codeArr;
	}

	/**
	 * Get time label for log listing
	 *
	 * @param	integer		Timestamp to display
	 * @return	string		If the timestamp was also shown last time, then "." is returned. Otherwise the new timestamp formatted with ->doc->formatTime()
	 */
	function getTimeLabel($code) {
		#$t=$GLOBALS['SOBE']->doc->formatTime($code,1);
		$t = date('H:i:s', $code);

		if ($this->lastTimeLabel != $t) {
			$this->lastTimeLabel = $t;
			return $t;
		} else {
			return '.';
		}

	}

	/**
	 * Get user name label for log listing
	 *
	 * @param	integer		be_user uid
	 * @param	integer		Workspace ID
	 * @return	string		If username is different from last username then the username, otherwise "."
	 */
	function getUserLabel($code, $workspace = 0) {
		if ($this->lastUserLabel != $code . '_' . $workspace) {
			$this->lastUserLabel = $code . '_' . $workspace;
			$label = $this->be_user_Array[$code]['username'];
			$ws = $this->wsArray[$workspace];
			return ($label ? htmlspecialchars($label) : '[' . $code . ']') . '@' . ($ws ? $ws : $workspace);
		} else {
			return '.';
		}
	}

	/**
	 * Get type label for log listing
	 *
	 * @param	string		Key for the type label in locallang
	 * @return	string		If labe is different from last type label then the label is returned, otherwise "."
	 */
	function getTypeLabel($code) {
		if ($this->lastTypeLabel != $code) {
			$this->lastTypeLabel = $code;
			$label = $GLOBALS['LANG']->getLL('type_' . $code);
			return $label ? $label : '[' . $code . ']';
		} else {
			return '.';
		}
	}

	/**
	 * Get action label for log listing
	 *
	 * @param	string		Key for the action label in locallang
	 * @return	string		If label is different from last action label then the label is returned, otherwise "."
	 */
	function getActionLabel($code) {
		if ($this->lastActionLabel != $code) {
			$this->lastActionLabel = $code;
			$label = $GLOBALS['LANG']->getLL('action_' . $code);
			return $label ? htmlspecialchars($label) : '[' . $code . ']';
		} else {
			return '.';
		}
	}

	/**
	 * Get details for the log entry
	 *
	 * @param	string		Suffix to "msg_" to get label from locallang.
	 * @param	string		Details text
	 * @param	array		Data array
	 * @param	integer		sys_log uid number
	 * @return	string		Text string
	 * @see formatDetailsForList()
	 */
	function getDetails($code, $text, $data, $sys_log_uid = 0) {
			// $code is used later on to substitute errormessages with language-corrected values...
		if (is_array($data)) {
			if ($this->detailsOn) {
				if (is_object($GLOBALS['LANG'])) {
					#					$label = $GLOBALS['LANG']->getLL('msg_'.$code);
				} else {
					list($label) = explode(',', $text);
				}
				if ($label) {
					$text = $label;
				}
				$text = sprintf($text, htmlspecialchars($data[0]), htmlspecialchars($data[1]), htmlspecialchars($data[2]), htmlspecialchars($data[3]), htmlspecialchars($data[4]));
			} else {
				$text = str_replace('%s', '', $text);
			}
		}
		$text = htmlspecialchars($text);

			// Finding the history for the record
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,fieldlist', 'sys_history', 'sys_log_uid=' . intval($sys_log_uid));
		$newRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		if (is_array($newRow)) {
			$text .= ' ' . sprintf($GLOBALS['LANG']->getLL('changesInFields'), '<em>' . $newRow['fieldlist'] . '</em>');
			$text .= ' <a href="' . htmlspecialchars($GLOBALS['BACK_PATH'] . 'show_rechis.php?sh_uid=' . $newRow['uid'] .
					'&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))) . '">' .
					t3lib_iconWorks::getSpriteIcon(
						'actions-document-history-open',
						array('title' => $GLOBALS['LANG']->getLL('showHistory'))
					) .
					'</a>';
		}

		return $text;
	}

	/**
	 * Reset all internal "last..." variables to blank string.
	 *
	 * @return	void
	 */
	function reset() {
		$this->lastTimeLabel = '';
		$this->lastUserLabel = '';
		$this->lastTypeLabel = '';
		$this->lastActionLabel = '';
	}

	/**
	 * Formats input string in red-colored font tags
	 *
	 * @param int $error
	 * @return string Input wrapped in red font-tag and bold
	 */
	function getErrorFormatting($error = 0) {
		return $GLOBALS['SOBE']->doc->icons($error >= 2 ? 3 : 2);
	}

	/**
	 * Formatting details text for the sys_log row inputted
	 *
	 * @param	array		sys_log row
	 * @return	string		Details string
	 */
	function formatDetailsForList($row) {
		$data = unserialize($row['log_data']);
		if ($row['type'] == 2) {
			$data = $this->stripPath($data);
		}

		return $this->getDetails($row['type'] . '_' . $row['action'] . '_' . $row['details_nr'], $row['details'], $data, $row['uid']) . ($row['details_nr'] > 0 ? ' (msg#' . $row['type'] . '.' . $row['action'] . '.' . $row['details_nr'] . ')' : '');
	}

	/**
	 * For all entries in the $inArray (expected to be filepaths) the basename is extracted and set as value (if $this->stripPath is set)
	 * This is done for log-entries from the FILE modules
	 *
	 * @param	array		Array of file paths
	 * @return	array
	 * @see formatDetailsForList()
	 */
	function stripPath($inArr) {
		if ($this->stripPath && is_array($inArr)) {
			foreach ($inArr as $key => $val) {
				$inArr[$key] = basename($val);
			}
		}
		return $inArr;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_bedisplaylog.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_bedisplaylog.php']);
}
?>