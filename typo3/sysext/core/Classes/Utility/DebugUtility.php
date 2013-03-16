<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Class to handle debug
 *
 * @author Steffen Kamper <steffen@typo3.org>
 */
class DebugUtility {

	/**
	 * Template for debug output
	 *
	 * @var string
	 */
	const DEBUG_TABLE_TEMPLATE = '
	<table class="typo3-debug" border="0" cellpadding="0" cellspacing="0" bgcolor="white" style="border:0px; margin-top:3px; margin-bottom:3px;">
		<tr>
			<td style="background-color:#bbbbbb; font-family: verdana,arial; font-weight: bold; font-size: 10px;">%s</td>
		</tr>
		<tr>
			<td>
			%s
			</td>
		</tr>
	</table>
	';

	/**
	 * @var \TYPO3\CMS\Core\Encoder\JavaScriptEncoder
	 */
	static protected $javaScriptEncoder;

	/**
	 * Debug
	 *
	 * @param string $var
	 * @param string $header
	 * @param string $group
	 * @return void
	 */
	static public function debug($var = '', $header = '', $group = 'Debug') {
		// buffer the output of debug if no buffering started before
		if (ob_get_level() == 0) {
			ob_start();
		}
		$debug = self::convertVariableToString($var);
		if ($header) {
			$debug = sprintf(self::DEBUG_TABLE_TEMPLATE, htmlspecialchars((string) $header), $debug);
		}
		if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
			$tabHeader = $header ?: 'Debug';
			$script = '
				(function debug() {
					var debugMessage = "' . static::getJavaScriptEncoder()->encode($debug) . '";
					var header = "' . static::getJavaScriptEncoder()->encode($tabHeader) . '";
					var group = "' . static::getJavaScriptEncoder()->encode($group) . '";

					if (typeof Ext !== "object" && (top && typeof top.Ext !== "object")) {
						document.write(debugMessage);
						return;
					}

					if (top && typeof Ext !== "object") {
						Ext = top.Ext;
					}

					Ext.onReady(function() {
						var TYPO3ViewportInstance = null;

						if (top && top.TYPO3 && typeof top.TYPO3.Backend === "object") {
							TYPO3ViewportInstance = top.TYPO3.Backend;
						} else if (typeof TYPO3 === "object" && typeof TYPO3.Backend === "object") {
							TYPO3ViewportInstance = TYPO3.Backend;
						}

						if (TYPO3ViewportInstance !== null) {
							TYPO3ViewportInstance.DebugConsole.addTab(debugMessage, header, group);
						} else {
							document.write(debugMessage);
						}
					});
				})();
			';
			echo \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS($script);
		} else {
			echo $debug;
		}
	}

	/**
	 * Replaces special characters for the usage inside javascript
	 *
	 * @param string $string
	 * @param boolean $asObject
	 * @return string
	 * @deprecated since 6.0 will be removed with 6.2
	 */
	static public function prepareVariableForJavascript($string, $asObject) {
		GeneralUtility::logDeprecatedFunction();
		return self::getJavaScriptEncoder()->encode($string);
	}

	/**
	 * Converts a variable to a string
	 *
	 * @param mixed $variable
	 * @return string
	 */
	static public function convertVariableToString($variable) {
		$string = '';
		if (is_array($variable)) {
			$string = self::viewArray($variable);
		} elseif (is_object($variable)) {
			$string = '<strong>|Object:<pre>';
			$string .= print_r($variable, TRUE);
			$string .= '</pre>|</strong>';
		} elseif ((string) $variable !== '') {
			$string = '<strong>|' . htmlspecialchars((string) $variable) . '|</strong>';
		} else {
			$string = '<strong>| debug |</strong>';
		}
		return $string;
	}

	/**
	 * Opens a debug message inside a popup window
	 *
	 * @param mixed $debugVariable
	 * @param string $header
	 * @param string $group
	 */
	static public function debugInPopUpWindow($debugVariable, $header = 'Debug', $group = 'Debug') {
		$debugString = self::convertVariableToString($debugVariable);
		$script = '
			(function debug() {
				var debugMessage = "' . static::getJavaScriptEncoder()->encode($debugString) . '",
					header = "' . static::getJavaScriptEncoder()->encode($header) . '",
					group = "' . static::getJavaScriptEncoder()->encode($group) . '",

					browserWindow = function(debug, header, group) {
						var newWindow = window.open("", "TYPO3DebugWindow_" + group,
							"width=600,height=400,menubar=0,toolbar=1,status=0,scrollbars=1,resizable=1"
						);
						if (newWindow.document.body.innerHTML) {
							newWindow.document.body.innerHTML = newWindow.document.body.innerHTML +
								"<hr />" + debugMessage;
						} else {
							newWindow.document.writeln(
								"<html><head><title>Debug: " + header + "(" + group + ")</title></head>"
								+ "<body onload=\\"self.focus()\\">"
								+ debugMessage
								+ "</body></html>"
							);
						}
					}

				if (!top.Ext) {
					browserWindow(debugMessage, header, group);
				} else {
					top.Ext.onReady(function() {
						if (top && top.TYPO3 && top.TYPO3.Backend) {
							top.TYPO3.Backend.DebugConsole.openBrowserWindow(header, debugMessage, group);
						} else {
							browserWindow(debugMessage, header, group);
						}
					});
				}
			})();
		';
		echo \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS($script);
	}

	/**
	 * Displays the "path" of the function call stack in a string, using debug_backtrace
	 *
	 * @return string
	 */
	static public function debugTrail() {
		$trail = debug_backtrace();
		$trail = array_reverse($trail);
		array_pop($trail);
		$path = array();
		foreach ($trail as $dat) {
			$pathFragment = $dat['class'] . $dat['type'] . $dat['function'];
			// add the path of the included file
			if (in_array($dat['function'], array('require', 'include', 'require_once', 'include_once'))) {
				$pathFragment .= '(' . substr($dat['args'][0], strlen(PATH_site)) . '),' . substr($dat['file'], strlen(PATH_site));
			}
			$path[] = $pathFragment . '#' . $dat['line'];
		}
		return implode(' // ', $path);
	}

	/**
	 * Displays an array as rows in a table. Useful to debug output like an array of database records.
	 *
	 * @param mixed $rows Array of arrays with similar keys
	 * @param string $header Table header
	 * @param boolean $returnHTML If TRUE, will return content instead of echo'ing out.
	 * @return void Outputs to browser.
	 */
	static public function debugRows($rows, $header = '', $returnHTML = FALSE) {
		if (is_array($rows)) {
			$firstEl = reset($rows);
			if (is_array($firstEl)) {
				$headerColumns = array_keys($firstEl);
				$tRows = array();
				// Header:
				$tRows[] = '<tr><td colspan="' . count($headerColumns) . '" style="background-color:#bbbbbb; font-family: verdana,arial; font-weight: bold; font-size: 10px;"><strong>' . htmlspecialchars($header) . '</strong></td></tr>';
				$tCells = array();
				foreach ($headerColumns as $key) {
					$tCells[] = '
							<td><font face="Verdana,Arial" size="1"><strong>' . htmlspecialchars($key) . '</strong></font></td>';
				}
				$tRows[] = '
						<tr>' . implode('', $tCells) . '
						</tr>';
				// Rows:
				foreach ($rows as $singleRow) {
					$tCells = array();
					foreach ($headerColumns as $key) {
						$tCells[] = '
							<td><font face="Verdana,Arial" size="1">' . (is_array($singleRow[$key]) ? self::debugRows($singleRow[$key], '', TRUE) : htmlspecialchars($singleRow[$key])) . '</font></td>';
					}
					$tRows[] = '
						<tr>' . implode('', $tCells) . '
						</tr>';
				}
				$table = '
					<table border="1" cellpadding="1" cellspacing="0" bgcolor="white">' . implode('', $tRows) . '
					</table>';
				if ($returnHTML) {
					return $table;
				} else {
					echo $table;
				}
			} else {
				debug('Empty array of rows', $header);
			}
		} else {
			debug('No array of rows', $header);
		}
	}

	/**
	 * Returns a string with a list of ascii-values for the first $characters characters in $string
	 *
	 * @param string $string String to show ASCII value for
	 * @param integer $characters Number of characters to show
	 * @return string The string with ASCII values in separated by a space char.
	 */
	static public function ordinalValue($string, $characters = 100) {
		if (strlen($string) < $characters) {
			$characters = strlen($string);
		}
		$valuestring = '';
		for ($i = 0; $i < $characters; $i++) {
			$valuestring .= ' ' . ord(substr($string, $i, 1));
		}
		return trim($valuestring);
	}

	/**
	 * Returns HTML-code, which is a visual representation of a multidimensional array
	 * use \TYPO3\CMS\Core\Utility\GeneralUtility::print_array() in order to print an array
	 * Returns FALSE if $array_in is not an array
	 *
	 * @param mixed $array_in Array to view
	 * @return string HTML output
	 */
	static public function viewArray($array_in) {
		if (is_array($array_in)) {
			$result = '
			<table border="1" cellpadding="1" cellspacing="0" bgcolor="white">';
			if (count($array_in) == 0) {
				$result .= '<tr><td><font face="Verdana,Arial" size="1"><strong>EMPTY!</strong></font></td></tr>';
			} else {
				foreach ($array_in as $key => $val) {
					$result .= '<tr>
						<td valign="top"><font face="Verdana,Arial" size="1">' . htmlspecialchars((string) $key) . '</font></td>
						<td>';
					if (is_array($val)) {
						$result .= self::viewArray($val);
					} elseif (is_object($val)) {
						$string = '';
						if (method_exists($val, '__toString')) {
							$string .= get_class($val) . ': ' . (string) $val;
						} else {
							$string .= print_r($val, TRUE);
						}
						$result .= '<font face="Verdana,Arial" size="1" color="red">' . nl2br(htmlspecialchars($string)) . '<br /></font>';
					} else {
						if (gettype($val) == 'object') {
							$string = 'Unknown object';
						} else {
							$string = (string) $val;
						}
						$result .= '<font face="Verdana,Arial" size="1" color="red">' . nl2br(htmlspecialchars($string)) . '<br /></font>';
					}
					$result .= '</td>
					</tr>';
				}
			}
			$result .= '</table>';
		} else {
			$result = '<table border="1" cellpadding="1" cellspacing="0" bgcolor="white">
				<tr>
					<td><font face="Verdana,Arial" size="1" color="red">' . nl2br(htmlspecialchars((string) $array_in)) . '<br /></font></td>
				</tr>
			</table>';
		}
		// Output it as a string.
		return $result;
	}

	/**
	 * Prints an array
	 *
	 * @param mixed $array_in Array to print visually (in a table).
	 * @return void
	 * @see viewArray()
	 */
	static public function printArray($array_in) {
		echo self::viewArray($array_in);
	}

	/**
	 * @return \TYPO3\CMS\Core\Encoder\JavaScriptEncoder
	 */
	static protected function getJavaScriptEncoder() {
		if (empty(self::$javaScriptEncoder)) {
			self::$javaScriptEncoder = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Encoder\\JavaScriptEncoder');
		}

		return self::$javaScriptEncoder;
	}
}


?>