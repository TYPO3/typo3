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
 * Class to setup values in localconf.php and verify the TYPO3 DB tables/fields
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Class to setup values in localconf.php and verify the TYPO3 DB tables/fields
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @deprecated since 6.0, will be removed with 6.2
 */
class t3lib_install {

	// External, Static
	// Set to string which identifies the script using this class.
	/**
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public $updateIdentity = '';

	// Prefix for checkbox fields when updating database.
	/**
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public $dbUpdateCheckboxPrefix = 'TYPO3_INSTALL[database_update]';

	// If this is set, modifications to localconf.php is done by adding new lines to the array only. If unset, existing values are recognized and changed.
	/**
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public $localconf_addLinesOnly = 0;

	// If set and addLinesOnly is disabled, lines will be change only if they are after this token (on a single line!) in the file
	protected $localconf_startEditPointToken = '## INSTALL SCRIPT EDIT POINT TOKEN - all lines after this points may be changed by the install script!';

	protected $localconf_endEditPointToken = '## INSTALL SCRIPT EDIT END POINT TOKEN - all lines before this points may be changed by the install script!';

	// If TRUE, this class will allow the user to update the localconf.php file. Is set TRUE in the init.php file.
	/**
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public $allowUpdateLocalConf = 0;

	// Backpath (used for icons etc.)
	/**
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public $backPath = '../';

	// Internal, dynamic:
	// Used to indicate that a value is change in the line-array of localconf and that it should be written.
	/**
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public $setLocalconf = 0;

	// Used to set (error)messages from the executing functions like mail-sending, writing Localconf and such
	/**
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public $messages = array();

	// Updated with line in localconf.php file that was changed.
	/**
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public $touchedLine = 0;

	/**
	 * @var \TYPO3\CMS\Install\Sql\SchemaMigrator Instance of SQL handler
	 */
	protected $sqlHandler = NULL;

	/**
	 * Constructor function
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function __construct() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		$this->sqlHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Sql\\SchemaMigrator');
	}

	/**************************************
	 *
	 * Writing to localconf.php
	 ***************************************/
	/**
	 * This functions takes an array with lines from localconf.php, finds a variable and inserts the new value.
	 *
	 * @param array $line_array The localconf.php file exploded into an array by linebreaks. (see writeToLocalconf_control())
	 * @param string $variable The variable name to find and substitute. This string must match the first part of a trimmed line in the line-array. Matching is done backwards so the last appearing line will be substituted.
	 * @param string $value Is the value to be insert for the variable
	 * @param boolean $quoteValue Whether the given value should be quoted before being written
	 * @return void
	 * @see writeToLocalconf_control()
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function setValueInLocalconfFile(&$line_array, $variable, $value, $quoteValue = TRUE) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return;
	}

	/**
	 * Takes an array with lines from localconf.php, finds a variable and inserts the new array value.
	 *
	 * @param array $lines the localconf.php file exploded into an array by line breaks. {@see writeToLocalconf_control()}
	 * @param string $variable the variable name to find and substitute. This string must match the first part of a trimmed line in the line-array. Matching is done backwards so the last appearing line will be substituted.
	 * @param array $value value to be assigned to the variable
	 * @return void
	 * @see writeToLocalconf_control()
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function setArrayValueInLocalconfFile(array &$lines, $variable, array $value) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return;
	}

	/**
	 * Returns a parsable string representation of an array variable. This methods enhances
	 * standard method var_export from PHP to take TYPO3's CGL into account.
	 *
	 * @param array $variable
	 * @return string
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	protected function array_export(array $variable) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		$lines = explode('
', var_export($variable, TRUE));
		$out = 'array(';
		$lineCount = count($lines);
		for ($i = 1; $i < $lineCount; $i++) {
			$out .= '
';
			// Make the space-indented declaration tab-indented instead
			while (substr($lines[$i], 0, 2) === '  ') {
				$out .= '	';
				$lines[$i] = substr($lines[$i], 2);
			}
			$out .= $lines[$i];
			// Array declaration should be next to the assignment and no space between
			// "array" and its opening parenthesis should exist
			if (preg_match('/\\s=>\\s$/', $lines[$i])) {
				$out .= preg_replace('/^\\s*array \\(/', 'array(', $lines[$i + 1]);
				$i++;
			}
		}
		return $out;
	}

	/**
	 * Writes or returns lines from localconf.php
	 *
	 * @param mixed $inlines Array of lines to write back to localconf.php. Possibly
	 * @param string $absFullPath Absolute path of alternative file to use (Notice: this path is not validated in terms of being inside 'TYPO3 space')
	 * @return mixed If $inlines is not an array it will return an array with the lines from localconf.php. Otherwise it will return a status string, either "continue" (updated) or "nochange" (not updated)
	 * @see setValueInLocalconfFile()
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function writeToLocalconf_control($inlines = '', $absFullPath = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return 'nochange';
	}

	/**
	 * Writes lines to localconf.php.
	 *
	 * @param array $lines Array of lines to write back to localconf.php
	 * @param string $absFullPath Absolute path of alternative file to use (Notice: this path is not validated in terms of being inside 'TYPO3 space')
	 * @return boolean TRUE if method succeeded, otherwise FALSE
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function writeToLocalconf(array $lines, $absFullPath = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return FALSE;
	}

	/**
	 * Checking for linebreaks in the string
	 *
	 * @param string $string String to test
	 * @return boolean Returns TRUE if string is OK
	 * @see setValueInLocalconfFile()
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function checkForBadString($string) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		return preg_match('/[' . LF . CR . ']/', $string) ? FALSE : TRUE;
	}

	/**
	 * Replaces ' with \' and \ with \\
	 *
	 * @param string $value Input value
	 * @return string Output value
	 * @see setValueInLocalconfFile()
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function slashValueForSingleDashes($value) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		$value = str_replace('\'.LF.\'', '###INSTALL_TOOL_LINEBREAK###', $value);
		$value = str_replace('\'', '\\\'', str_replace('\\', '\\\\', $value));
		$value = str_replace('###INSTALL_TOOL_LINEBREAK###', '\'.LF.\'', $value);
		return $value;
	}

	/**
	 * Creates a table which checkboxes for updating database.
	 *
	 * @param array $arr Array of statements (key / value pairs where key is used for the checkboxes)
	 * @param string $label Label for the table.
	 * @param boolean $checked If set, then checkboxes are set by default.
	 * @param boolean $iconDis If set, then icons are shown.
	 * @param array $currentValue Array of "current values" for each key/value pair in $arr. Shown if given.
	 * @param boolean $cVfullMsg If set, will show the prefix "Current value" if $currentValue is given.
	 * @return string HTML table with checkboxes for update. Must be wrapped in a form.
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function generateUpdateDatabaseForm_checkboxes($arr, $label, $checked = TRUE, $iconDis = FALSE, $currentValue = array(), $cVfullMsg = FALSE) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		$out = array();
		if (is_array($arr)) {
			$tableId = uniqid('table');
			if (count($arr) > 1) {
				$out[] = '
					<tr class="update-db-fields-batch">
						<td valign="top">
							<input type="checkbox" id="' . $tableId . '-checkbox"' . ($checked ? ' checked="checked"' : '') . '
							onclick="$(\'' . $tableId . '\').select(\'input[type=checkbox]\').invoke(\'setValue\', $(this).checked);" />
						</td>
						<td nowrap="nowrap"><label for="' . $tableId . '-checkbox" style="cursor:pointer"><strong>select/deselect all</strong></label></td>
					</tr>';
			}
			foreach ($arr as $key => $string) {
				$ico = '';
				$warnings = array();
				if ($iconDis) {
					if (preg_match('/^TRUNCATE/i', $string)) {
						$ico .= '<img src="' . $this->backPath . 'gfx/icon_warning.gif" width="18" height="16" align="top" alt="" /><strong> </strong>';
						$warnings['clear_table_info'] = 'Clearing the table is sometimes neccessary when adding new keys. In case of cache_* tables this should not hurt at all. However, use it with care.';
					} elseif (stristr($string, ' user_')) {
						$ico .= '<img src="' . $this->backPath . 'gfx/icon_warning.gif" width="18" height="16" align="top" alt="" /><strong>(USER) </strong>';
					} elseif (stristr($string, ' app_')) {
						$ico .= '<img src="' . $this->backPath . 'gfx/icon_warning.gif" width="18" height="16" align="top" alt="" /><strong>(APP) </strong>';
					} elseif (stristr($string, ' ttx_') || stristr($string, ' tx_')) {
						$ico .= '<img src="' . $this->backPath . 'gfx/icon_warning.gif" width="18" height="16" align="top" alt="" /><strong>(EXT) </strong>';
					}
				}
				$out[] = '
					<tr>
						<td valign="top"><input type="checkbox" id="db-' . $key . '" name="' . $this->dbUpdateCheckboxPrefix . '[' . $key . ']" value="1"' . ($checked ? ' checked="checked"' : '') . ' /></td>
						<td nowrap="nowrap"><label for="db-' . $key . '">' . nl2br(($ico . htmlspecialchars($string))) . '</label></td>
					</tr>';
				if (isset($currentValue[$key])) {
					$out[] = '
					<tr>
						<td valign="top"></td>
						<td nowrap="nowrap" style="color:#666666;">' . nl2br(((!$cVfullMsg ? 'Current value: ' : '') . '<em>' . $currentValue[$key] . '</em>')) . '</td>
					</tr>';
				}
			}
			if (count($warnings)) {
				$out[] = '
					<tr>
						<td valign="top"></td>
						<td style="color:#666666;"><em>' . implode('<br />', $warnings) . '</em></td>
					</tr>';
			}
			// Compile rows:
			$content = '
				<!-- Update database fields / tables -->
				<h3>' . $label . '</h3>
				<table border="0" cellpadding="2" cellspacing="2" id="' . $tableId . '" class="update-db-fields">' . implode('', $out) . '
				</table>';
		}
		return $content;
	}

}

?>