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
 * Class to setup values in localconf.php and verify the TYPO3 DB tables/fields
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


/**
 * Class to setup values in localconf.php and verify the TYPO3 DB tables/fields
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_install {


		// External, Static
	var $updateIdentity = ''; // Set to string which identifies the script using this class.

	var $dbUpdateCheckboxPrefix = 'TYPO3_INSTALL[database_update]'; // Prefix for checkbox fields when updating database.
	var $localconf_addLinesOnly = 0; // If this is set, modifications to localconf.php is done by adding new lines to the array only. If unset, existing values are recognized and changed.
	var $localconf_editPointToken = 'INSTALL SCRIPT EDIT POINT TOKEN - all lines after this points may be changed by the install script!'; // If set and addLinesOnly is disabled, lines will be change only if they are after this token (on a single line!) in the file
	var $allowUpdateLocalConf = 0; // If TRUE, this class will allow the user to update the localconf.php file. Is set TRUE in the init.php file.
	var $backPath = '../'; // Backpath (used for icons etc.)

		// Internal, dynamic:
	var $setLocalconf = 0; // Used to indicate that a value is change in the line-array of localconf and that it should be written.
	var $messages = array(); // Used to set (error)messages from the executing functions like mail-sending, writing Localconf and such
	var $touchedLine = 0; // updated with line in localconf.php file that was changed.

	/**
	 * @var t3lib_install_Sql Instance of SQL handler
	 */
	protected $sqlHandler = NULL;

	/**
	 * Constructor function
	 */
	public function __construct() {
		$this->sqlHandler = t3lib_div::makeInstance('t3lib_install_Sql');
	}


	/**************************************
	 *
	 * Writing to localconf.php
	 *

	 **************************************/

	/**
	 * This functions takes an array with lines from localconf.php, finds a variable and inserts the new value.
	 *
	 * @param	array		$line_array	the localconf.php file exploded into an array by linebreaks. (see writeToLocalconf_control())
	 * @param	string		$variable	The variable name to find and substitute. This string must match the first part of a trimmed line in the line-array. Matching is done backwards so the last appearing line will be substituted.
	 * @param	string		$value		Is the value to be insert for the variable
	 * @param	boolean		$quoteValue	Whether the given value should be quoted before being written
	 * @return	void
	 * @see writeToLocalconf_control()
	 */
	public function setValueInLocalconfFile(&$line_array, $variable, $value, $quoteValue = TRUE) {
		if (!$this->checkForBadString($value)) {
			return 0;
		}

			// Initialize:
		$found = 0;
		$this->touchedLine = '';
		$commentKey = '## ';
		$inArray = in_array($commentKey . $this->localconf_editPointToken, $line_array);
		$tokenSet = ($this->localconf_editPointToken && !$inArray); // Flag is set if the token should be set but is not yet...
		$stopAtToken = ($this->localconf_editPointToken && $inArray);
		$comment = ' Modified or inserted by ' . $this->updateIdentity . '.';
		$replace = array('["', '"]');
		$search = array('[\'', '\']');
		$varDoubleQuotes = str_replace($search, $replace, $variable);

			// Search for variable name:
		if (!$this->localconf_addLinesOnly && !$tokenSet) {
			$line_array = array_reverse($line_array);
			foreach ($line_array as $k => $v) {
				$v2 = trim($v);
				if ($stopAtToken && !strcmp($v2, $commentKey . $this->localconf_editPointToken)) {
					break;
				} // If stopAtToken and token found, break out of the loop..
				if (!strcmp(substr($v2, 0, strlen($variable . ' ')), $variable . ' ')) {
					$mainparts = explode($variable, $v, 2);
					if (count($mainparts) == 2) { // should ALWAYS be....
						$subparts = explode('//', $mainparts[1], 2);
						if ($quoteValue) {
							$value = '\'' . $this->slashValueForSingleDashes($value) . '\'';
						}
						$line_array[$k] = $mainparts[0] . $variable . " = " . $value . ";	" . ('//' . $comment . str_replace($comment, '', $subparts[1]));
						$this->touchedLine = count($line_array) - $k - 1;
						$found = 1;
						break;
					}
				} elseif (!strcmp(substr($v2, 0, strlen($varDoubleQuotes . ' ')), $varDoubleQuotes . ' ')) {
						// Due to a bug in the update wizard (fixed in TYPO3 4.1.7) it is possible
						// that $TYPO3_CONF_VARS['SYS']['compat_version'] was enclosed by "" (double
						// quotes) instead of the expected '' (single quotes) when is was written to
						// localconf.php. The following code was added to make sure that values with
						// double quotes are updated, too.
					$mainparts = explode($varDoubleQuotes, $v, 2);
					if (count($mainparts) == 2) { // should ALWAYS be....
						$subparts = explode('//', $mainparts[1], 2);
						if ($quoteValue) {
							$value = '\'' . $this->slashValueForSingleDashes($value) . '\'';
						}
						$line_array[$k] = $mainparts[0] . $variable . " = " . $value . ";	" . ('//' . $comment . str_replace($comment, '', $subparts[1]));
						$this->touchedLine = count($line_array) - $k - 1;
						$found = 1;
						break;
					}
				}
			}
			$line_array = array_reverse($line_array);
		}
		if (!$found) {
			if ($tokenSet) {
				$line_array[] = $commentKey . $this->localconf_editPointToken;
				$line_array[] = '';
			}
			if ($quoteValue) {
				$value = '\'' . $this->slashValueForSingleDashes($value) . '\'';
			}
			$line_array[] = $variable . " = " . $value . ";	// " . $comment;
			$this->touchedLine = -1;
		}
		if ($variable == '$typo_db_password') {
			$this->messages[] = 'Updated ' . $variable;
		} else {
			$this->messages[] = $variable . " = " . htmlspecialchars($value);
		}
		$this->setLocalconf = 1;
	}

	/**
	 * Takes an array with lines from localconf.php, finds a variable and inserts the new array value.
	 *
	 * @param array $lines the localconf.php file exploded into an array by line breaks. {@see writeToLocalconf_control()}
	 * @param string $variable the variable name to find and substitute. This string must match the first part of a trimmed line in the line-array. Matching is done backwards so the last appearing line will be substituted.
	 * @param array $value value to be assigned to the variable
	 * @return void
	 * @see writeToLocalconf_control()
	 */
	public function setArrayValueInLocalconfFile(array &$lines, $variable, array $value) {
		$commentKey = '## ';
		$inArray = in_array($commentKey . $this->localconf_editPointToken, $lines);
		$tokenSet = $this->localconf_editPointToken && !$inArray; // Flag is set if the token should be set but is not yet
		$stopAtToken = $this->localconf_editPointToken && $inArray;
		$comment = 'Modified or inserted by ' . $this->updateIdentity . '.';
		$format = "%s = %s;\t// " . $comment;

		$insertPos = count($lines);
		$startPos = 0;
		if (!($this->localconf_addLinesOnly || $tokenSet)) {
			for ($i = count($lines) - 1; $i > 0; $i--) {
				$line = trim($lines[$i]);
				if ($stopAtToken && t3lib_div::isFirstPartOfStr($line, $this->localconf_editPointToken)) {
					break;
				}
				if (t3lib_div::isFirstPartOfStr($line, '?>')) {
					$insertPos = $i;
				}
				if (t3lib_div::isFirstPartOfStr($line, $variable)) {
					$startPos = $i;
					break;
				}
			}
		}
		if ($startPos) {
			$this->touchedLine = $startPos;
			$endPos = $startPos;
			for ($i = $startPos; $i < count($lines); $i++) {
				$line = trim($lines[$i]);
				if (t3lib_div::isFirstPartOfStr($line, ');')) {
					$endPos = $i;
					break;
				}
			}

			$startLines = array_slice($lines, 0, $startPos);
			$endLines = array_slice($lines, $endPos + 1);

			$lines = $startLines;
			$definition = $this->array_export($value);
			$lines[] = sprintf($format, $variable, $definition);
			foreach ($endLines as $line) {
				$lines[] = $line;
			}
		} else {
			$lines[$insertPos] = sprintf($format, $variable, $this->array_export($value));
			$lines[] = '?>';
			$this->touchedLine = -1;
		}
	}

	/**
	 * Returns a parsable string representation of an array variable. This methods enhances
	 * standard method var_export from PHP to take TYPO3's CGL into account.
	 *
	 * @param array $variable
	 * @return string
	 */
	protected function array_export(array $variable) {
		$lines = explode("\n", var_export($variable, TRUE));
		$out = 'array(';

		for ($i = 1; $i < count($lines); $i++) {
			$out .= "\n";
				// Make the space-indented declaration tab-indented instead
			while (substr($lines[$i], 0, 2) === '  ') {
				$out .= "\t";
				$lines[$i] = substr($lines[$i], 2);
			}
			$out .= $lines[$i];
				// Array declaration should be next to the assignment and no space between
				// "array" and its opening parenthesis should exist
			if (preg_match('/\s=>\s$/', $lines[$i])) {
				$out .= preg_replace('/^\s*array \(/', 'array(', $lines[$i + 1]);
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
	 */
	function writeToLocalconf_control($inlines = '', $absFullPath = '') {
		$tmpExt = '.TMP.php';
		$writeToLocalconf_dat = array();
		$writeToLocalconf_dat['file'] = $absFullPath ? $absFullPath : PATH_typo3conf . 'localconf.php';
		$writeToLocalconf_dat['tmpfile'] = $writeToLocalconf_dat['file'] . $tmpExt;

			// Checking write state of localconf.php:
		if (!$this->allowUpdateLocalConf) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: ->allowUpdateLocalConf flag in the install object is not set and therefore "localconf.php" cannot be altered.',
				1270853915
			);
		}
		if (!@is_writable($writeToLocalconf_dat['file'])) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: ' . $writeToLocalconf_dat['file'] . ' is not writable!',
				1270853916
			);
		}

			// Splitting localconf.php file into lines:
		$lines = explode(LF, str_replace(CR, '', trim(t3lib_div::getUrl($writeToLocalconf_dat['file']))));
		$writeToLocalconf_dat['endLine'] = array_pop($lines); // Getting "? >" ending.

			// Checking if "updated" line was set by this tool - if so remove old line.
		$updatedLine = array_pop($lines);
		$writeToLocalconf_dat['updatedText'] = '// Updated by ' . $this->updateIdentity . ' ';

		if (!strstr($updatedLine, $writeToLocalconf_dat['updatedText'])) {
			array_push($lines, $updatedLine);
		}

		if (is_array($inlines)) { // Setting a line and write:
				// Setting configuration
			$updatedLine = $writeToLocalconf_dat['updatedText'] . date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' H:i:s');
			array_push($inlines, $updatedLine);
			array_push($inlines, $writeToLocalconf_dat['endLine']);

			if ($this->setLocalconf) {
				$success = $this->writeToLocalconf($inlines, $absFullPath);

				if ($success) {
					return 'continue';
				} else {
					return 'nochange';
				}
			} else {
				return 'nochange';
			}
		} else { // Return lines found in localconf.php
			return $lines;
		}
	}

	/**
	 * Writes lines to localconf.php.
	 *
	 * @param array $lines Array of lines to write back to localconf.php
	 * @param string $absFullPath Absolute path of alternative file to use (Notice: this path is not validated in terms of being inside 'TYPO3 space')
	 * @return boolean TRUE if method succeeded, otherwise FALSE
	 */
	public function writeToLocalconf(array $lines, $absFullPath = '') {
		$tmpExt = '.TMP.php';
		$writeToLocalconf_dat = array();
		$writeToLocalconf_dat['file'] = $absFullPath ? $absFullPath : PATH_typo3conf . 'localconf.php';
		$writeToLocalconf_dat['tmpfile'] = $writeToLocalconf_dat['file'] . $tmpExt;

			// Checking write state of localconf.php:
		if (!$this->allowUpdateLocalConf) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: ->allowUpdateLocalConf flag in the install object is not set and therefore "localconf.php" cannot be altered.',
				1270853915
			);
		}
		if (!@is_writable($writeToLocalconf_dat['file'])) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: ' . $writeToLocalconf_dat['file'] . ' is not writable!',
				1270853916
			);
		}

		$writeToLocalconf_dat['endLine'] = array_pop($lines); // Getting "? >" ending.
		if (!strstr('?' . '>', $writeToLocalconf_dat['endLine'])) {
			$lines[] = $writeToLocalconf_dat['endLine'];
			$writeToLocalconf_dat['endLine'] = '?' . '>';
		}
			// Checking if "updated" line was set by this tool - if so remove old line.
		$updatedLine = array_pop($lines);
		$writeToLocalconf_dat['updatedText'] = '// Updated by ' . $this->updateIdentity . ' ';

		if (!strstr($updatedLine, $writeToLocalconf_dat['updatedText'])) {
			$lines[] = $updatedLine;
		}

		$updatedLine = $writeToLocalconf_dat['updatedText'] . date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' H:i:s');
		$lines[] = $updatedLine;
		$lines[] = $writeToLocalconf_dat['endLine'];

		$success = FALSE;
		if (!t3lib_div::writeFile($writeToLocalconf_dat['tmpfile'], implode(LF, $lines))) {
			$msg = 'typo3conf/localconf.php' . $tmpExt . ' could not be written - maybe a write access problem?';
		}
		elseif (strcmp(t3lib_div::getUrl($writeToLocalconf_dat['tmpfile']), implode(LF, $lines))) {
			@unlink($writeToLocalconf_dat['tmpfile']);
			$msg = 'typo3conf/localconf.php' . $tmpExt . ' was NOT written properly (written content didn\'t match file content) - maybe a disk space problem?';
		}
		elseif (!@copy($writeToLocalconf_dat['tmpfile'], $writeToLocalconf_dat['file'])) {
			$msg = 'typo3conf/localconf.php could not be replaced by typo3conf/localconf.php' . $tmpExt . ' - maybe a write access problem?';
		}
		else {
			@unlink($writeToLocalconf_dat['tmpfile']);
			$success = TRUE;
			$msg = 'Configuration written to typo3conf/localconf.php';
		}
		$this->messages[] = $msg;

		if (!$success) {
			t3lib_div::sysLog($msg, 'Core', 3);
		}

		return $success;
	}

	/**
	 * Checking for linebreaks in the string
	 *
	 * @param	string		String to test
	 * @return	boolean		Returns TRUE if string is OK
	 * @see setValueInLocalconfFile()
	 */
	function checkForBadString($string) {
		return preg_match('/[' . LF . CR . ']/', $string) ? FALSE : TRUE;
	}

	/**
	 * Replaces ' with \' and \ with \\
	 *
	 * @param string $value Input value
	 * @return string Output value
	 * @see setValueInLocalconfFile()
	 */
	function slashValueForSingleDashes($value) {
		$value = str_replace("'.LF.'", '###INSTALL_TOOL_LINEBREAK###', $value);
		$value = str_replace("'", "\'", str_replace('\\', '\\\\', $value));
		$value = str_replace('###INSTALL_TOOL_LINEBREAK###', "'.LF.'", $value);

		return $value;
	}


	/*************************************
	 *
	 * SQL
	 *
	 *************************************/

	/**
	 * Reads the field definitions for the input SQL-file string
	 *
	 * @param $fileContent string Should be a string read from an SQL-file made with 'mysqldump [database_name] -d'
	 * @return array Array with information about table.
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function getFieldDefinitions_fileContent($fileContent) {
		t3lib_div::logDeprecatedFunction();
		return $this->sqlHandler->getFieldDefinitions_fileContent($fileContent);
	}

	/**
	 * Multiplies varchars/tinytext fields in size according to $this->multiplySize
	 * Useful if you want to use UTF-8 in the database and needs to extend the field sizes in the database so UTF-8 chars are not discarded. For most charsets available as single byte sets, multiplication with 2 should be enough. For chinese, use 3.
	 *
	 * @param array $total Total array (from getFieldDefinitions_fileContent())
	 * @return	void
	 * @access private
	 * @see getFieldDefinitions_fileContent()
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8
	 */
	function getFieldDefinitions_sqlContent_parseTypes(&$total) {
			// This method is protected in t3lib_install_Sql
		t3lib_div::logDeprecatedFunction();
	}

	/**
	 * Look up the default collation for specified character set based on "SHOW CHARACTER SET" output
	 *
	 * @param string $charset Character set
	 * @return string Corresponding default collation
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function getCollationForCharset($charset) {
		t3lib_div::logDeprecatedFunction();
		return $this->sqlHandler->getCollationForCharset($charset);
	}

	/**
	 * Reads the field definitions for the current database
	 *
	 * @return	array Array with information about table.
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function getFieldDefinitions_database() {
		t3lib_div::logDeprecatedFunction();
		return $this->sqlHandler->getFieldDefinitions_database();
	}

	/**
	 * Compares two arrays with field information and returns information about fields that are MISSING and fields that have CHANGED.
	 * FDsrc and FDcomp can be switched if you want the list of stuff to remove rather than update.
	 *
	 * @param array $FDsrc Field definitions, source (from getFieldDefinitions_fileContent())
	 * @param array $FDcomp Field definitions, comparison. (from getFieldDefinitions_database())
	 * @param string $onlyTableList Table names (in list) which is the ONLY one observed.
	 * @param boolean $ignoreNotNullWhenComparing If set, this function ignores NOT NULL statements of the SQL file field definition when comparing current field definition from database with field definition from SQL file. This way, NOT NULL statements will be executed when the field is initially created, but the SQL parser will never complain about missing NOT NULL statements afterwards.
	 * @return array Returns an array with 1) all elements from $FDsrc that is not in $FDcomp (in key 'extra') and 2) all elements from $FDsrc that is different from the ones in $FDcomp
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function getDatabaseExtra($FDsrc, $FDcomp, $onlyTableList = '', $ignoreNotNullWhenComparing = TRUE) {
		t3lib_div::logDeprecatedFunction();
		return $this->sqlHandler->getDatabaseExtra($FDsrc, $FDcomp, $onlyTableList, $ignoreNotNullWhenComparing);
	}

	/**
	 * Returns an array with SQL-statements that is needed to update according to the diff-array
	 *
	 * @param array $diffArr Array with differences of current and needed DB settings. (from getDatabaseExtra())
	 * @param string $keyList List of fields in diff array to take notice of.
	 * @return array Array of SQL statements (organized in keys depending on type)
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function getUpdateSuggestions($diffArr, $keyList = 'extra,diff') {
		t3lib_div::logDeprecatedFunction();
		return $this->sqlHandler->getUpdateSuggestions($diffArr, $keyList);
	}

	/**
	 * Converts a result row with field information into the SQL field definition string
	 *
	 * @param array $row MySQL result row
	 * @return string Field definition
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function assembleFieldDefinition($row) {
		t3lib_div::logDeprecatedFunction();
		return $this->sqlHandler->assembleFieldDefinition($row);
	}

	/**
	 * Returns an array where every entry is a single SQL-statement. Input must be formatted like an ordinary MySQL-dump files.
	 *
	 * @param string $sqlcode The SQL-file content. Provided that 1) every query in the input is ended with ';' and that a line in the file contains only one query or a part of a query.
	 * @param boolean $removeNonSQL If set, non-SQL content (like comments and blank lines) is not included in the final output
	 * @param string $query_regex Regex to filter SQL lines to include
	 * @return array Array of SQL statements
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function getStatementArray($sqlcode, $removeNonSQL = FALSE, $query_regex = '') {
		t3lib_div::logDeprecatedFunction();
		return $this->sqlHandler->getStatementArray($sqlcode, $removeNonSQL, $query_regex);
	}

	/**
	 * Returns tables to create and how many records in each
	 *
	 * @param array $statements Array of SQL statements to analyse.
	 * @param boolean $insertCountFlag If set, will count number of INSERT INTO statements following that table definition
	 * @return array Array with table definitions in index 0 and count in index 1
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function getCreateTables($statements, $insertCountFlag = FALSE) {
		t3lib_div::logDeprecatedFunction();
		return $this->sqlHandler->getCreateTables($statements, $insertCountFlag);
	}

	/**
	 * Extracts all insert statements from $statement array where content is inserted into $table
	 *
	 * @param array $statements Array of SQL statements
	 * @param string $table Table name
	 * @return array Array of INSERT INTO statements where table match $table
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function getTableInsertStatements($statements, $table) {
		t3lib_div::logDeprecatedFunction();
		$this->sqlHandler->getTableInsertStatements($statements, $table);
	}

	/**
	 * Performs the queries passed from the input array.
	 *
	 * @param array $arr Array of SQL queries to execute.
	 * @param array $keyArr Array with keys that must match keys in $arr. Only where a key in this array is set and TRUE will the query be executed (meant to be passed from a form checkbox)
	 * @return mixed Array with error message from database if any occured. Otherwise TRUE if everything was executed successfully.
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function performUpdateQueries($arr, $keyArr) {
		t3lib_div::logDeprecatedFunction();
		$this->sqlHandler->performUpdateQueries($arr, $keyArr);
	}

	/**
	 * Returns list of tables in the database
	 *
	 * @return	array		List of tables.
	 * @see t3lib_db::admin_get_tables()
	 * @deprecated Since TYPO3 4.6, will be removed in 4.8, use method from t3lib_install_Sql instead
	 */
	function getListOfTables() {
		t3lib_div::logDeprecatedFunction();
		$this->sqlHandler->getListOfTables();
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
	 */
	function generateUpdateDatabaseForm_checkboxes($arr, $label, $checked = TRUE, $iconDis = FALSE, $currentValue = array(), $cVfullMsg = FALSE) {
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
						<td nowrap="nowrap"><label for="db-' . $key . '">' . nl2br($ico . htmlspecialchars($string)) . '</label></td>
					</tr>';
				if (isset($currentValue[$key])) {
					$out[] = '
					<tr>
						<td valign="top"></td>
						<td nowrap="nowrap" style="color:#666666;">' . nl2br((!$cVfullMsg ? "Current value: " : "") . '<em>' . $currentValue[$key] . '</em>') . '</td>
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

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_install.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_install.php']);
}

?>