<?php
namespace TYPO3\CMS\Core\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class to handle debug
 */
class DebugUtility
{
    /**
     * Debug
     *
     * @param string $var
     * @param string $header
     * @param string $group
     * @return void
     */
    public static function debug($var = '', $header = '', $group = 'Debug')
    {
        // buffer the output of debug if no buffering started before
        if (ob_get_level() == 0) {
            ob_start();
        }
        $debug = self::convertVariableToString($var);
        if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
            $tabHeader = $header ?: 'Debug';
            $script = '
				(function debug() {
					var message = ' . GeneralUtility::quoteJSvalue($debug) . ',
						header = ' . GeneralUtility::quoteJSvalue($header) . ',
						group = ' . GeneralUtility::quoteJSvalue($group) . ';
					if (top.TYPO3.DebugConsole) {
						top.TYPO3.DebugConsole.add(message, header, group);
					} else {
						var consoleMessage = [group, header, message].join(" | ");
						if (typeof console === "object" && typeof console.log === "function") {
							console.log(consoleMessage);
						}
					};
				})();
			';
            echo GeneralUtility::wrapJS($script);
        } else {
            echo $debug;
        }
    }

    /**
     * Converts a variable to a string
     *
     * @param mixed $variable
     * @return string
     */
    public static function convertVariableToString($variable)
    {
        if (is_array($variable)) {
            $string = self::viewArray($variable);
        } elseif (is_object($variable)) {
            $string = json_encode($variable, true);
        } elseif ((string)$variable !== '') {
            $string = htmlspecialchars((string)$variable);
        } else {
            $string = '| debug |';
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
    public static function debugInPopUpWindow($debugVariable, $header = 'Debug', $group = 'Debug')
    {
        $debugString = self::convertVariableToString($debugVariable);
        $script = '
			(function debug() {
				var debugMessage = ' . GeneralUtility::quoteJSvalue($debugString) . ',
					header = ' . GeneralUtility::quoteJSvalue($header) . ',
					group = ' . GeneralUtility::quoteJSvalue($group) . ',

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
					};

				if (top && typeof top.TYPO3 !== "undefined" && typeof top.TYPO3.Modal !== "undefined") {
					top.TYPO3.Modal.show(
						"Debug: " + header + " (" + group + ")",
						debugMessage,
						top.TYPO3.Severity.notice
					);
				} else {
					browserWindow(debugMessage, header, group);
				}
			})();
		';
        echo GeneralUtility::wrapJS($script);
    }

    /**
     * Displays the "path" of the function call stack in a string, using debug_backtrace
     *
     * @return string
     */
    public static function debugTrail()
    {
        $trail = debug_backtrace(0);
        $trail = array_reverse($trail);
        array_pop($trail);
        $path = array();
        foreach ($trail as $dat) {
            $pathFragment = $dat['class'] . $dat['type'] . $dat['function'];
            // add the path of the included file
            if (in_array($dat['function'], array('require', 'include', 'require_once', 'include_once'))) {
                $pathFragment .= '(' . PathUtility::stripPathSitePrefix($dat['args'][0]) . '),' . PathUtility::stripPathSitePrefix($dat['file']);
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
     * @param bool $returnHTML If TRUE, will return content instead of echo'ing out. Deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     * @return void Outputs to browser.
     */
    public static function debugRows($rows, $header = '', $returnHTML = false)
    {
        if ($returnHTML !== false) {
            GeneralUtility::deprecationLog('Setting the parameter $returnHTML is deprecated since TYPO3 CMS 7 and will be removed in TYPO3 CMS 8.');
        }
        self::debug('<pre>' . DebuggerUtility::var_dump($rows, $header, 8, true, false, true), $header . '</pre>');
    }

    /**
     * Returns a string with a list of ascii-values for the first $characters characters in $string
     *
     * @param string $string String to show ASCII value for
     * @param int $characters Number of characters to show
     * @return string The string with ASCII values in separated by a space char.
     */
    public static function ordinalValue($string, $characters = 100)
    {
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
    public static function viewArray($array_in)
    {
        return '<pre>' . DebuggerUtility::var_dump($array_in, '', 8, true, false, true) . '</pre>';
    }

    /**
     * Prints an array
     *
     * @param mixed $array_in Array to print visually (in a table).
     * @return void
     * @see viewArray()
     */
    public static function printArray($array_in)
    {
        echo self::viewArray($array_in);
    }
}
