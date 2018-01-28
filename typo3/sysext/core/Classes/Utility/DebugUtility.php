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
     * @var bool
     */
    protected static $plainTextOutput = true;

    /**
     * @var bool
     */
    protected static $ansiColorUsage = true;

    /**
     * Debug
     *
     * Directly echos out debug information as HTML (or plain in CLI context)
     *
     * @param string $var
     * @param string $header
     * @param string $group
     */
    public static function debug($var = '', $header = 'Debug', $group = 'Debug')
    {
        // buffer the output of debug if no buffering started before
        if (ob_get_level() === 0) {
            ob_start();
        }

        if (TYPO3_MODE === 'BE' && !self::isCommandLine()) {
            $debug = self::renderDump($var);
            $debugPlain = PHP_EOL . self::renderDump($var, '', true, false);
            $script = '
				(function debug() {
					var message = ' . GeneralUtility::quoteJSvalue($debug) . ',
						messagePlain = ' . GeneralUtility::quoteJSvalue($debugPlain) . ',
						header = ' . GeneralUtility::quoteJSvalue($header) . ',
						group = ' . GeneralUtility::quoteJSvalue($group) . ';
					if (top.TYPO3 && top.TYPO3.DebugConsole) {
						top.TYPO3.DebugConsole.add(message, header, group);
					} else {
						var consoleMessage = [group, header, messagePlain].join(" | ");
						if (typeof console === "object" && typeof console.log === "function") {
							console.log(consoleMessage);
						}
					};
				})();
			';
            echo GeneralUtility::wrapJS($script);
        } else {
            echo self::renderDump($var, $header);
        }
    }

    /**
     * Converts a variable to a string
     *
     * @param mixed $variable
     * @return string plain, not HTML encoded string
     */
    public static function convertVariableToString($variable)
    {
        $string = self::renderDump($variable, '', true, false);
        return $string === '' ? '| debug |' : $string;
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
        $debugString = self::renderDump($debugVariable, sprintf('%s (%s)', $header, $group));
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
     * @param bool $prependFileNames If set to true file names are added to the output
     * @return string plain, not HTML encoded string
     */
    public static function debugTrail($prependFileNames = false)
    {
        $trail = debug_backtrace(0);
        $trail = array_reverse($trail);
        array_pop($trail);
        $path = [];
        foreach ($trail as $dat) {
            $fileInformation = $prependFileNames && !empty($dat['file']) ? $dat['file'] . ':' : '';
            $pathFragment = $fileInformation . $dat['class'] . $dat['type'] . $dat['function'];
            // add the path of the included file
            if (in_array($dat['function'], ['require', 'include', 'require_once', 'include_once'])) {
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
     */
    public static function debugRows($rows, $header = '')
    {
        self::debug($rows, $header);
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
        return self::renderDump($array_in);
    }

    /**
     * Prints an array
     *
     * @param mixed $array_in Array to print visually (in a table).
     * @see viewArray()
     */
    public static function printArray($array_in)
    {
        echo self::renderDump($array_in);
    }

    /**
     * Renders the dump according to the context, either for command line or as HTML output
     *
     * @param mixed $variable
     * @param string $title
     * @param bool|null $plainText
     * @param bool|null $ansiColors
     * @return string
     */
    protected static function renderDump($variable, $title = '', $plainText = null, $ansiColors = null)
    {
        $plainText = $plainText === null ? self::isCommandLine() && self::$plainTextOutput : $plainText;
        $ansiColors = $ansiColors === null ? self::isCommandLine() && self::$ansiColorUsage : $ansiColors;
        return trim(DebuggerUtility::var_dump($variable, $title, 8, $plainText, $ansiColors, true));
    }

    /**
     * Checks some constants to determine if we are in CLI context
     *
     * @return bool
     */
    protected static function isCommandLine()
    {
        return (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) || PHP_SAPI === 'cli';
    }

    /**
     * Preset plaintext output
     *
     * Warning:
     * This is NOT a public API method and must not be used in own extensions!
     * This method is usually only used in tests to preset the output behaviour
     *
     * @internal
     * @param bool $plainTextOutput
     */
    public static function usePlainTextOutput($plainTextOutput)
    {
        static::$plainTextOutput = $plainTextOutput;
    }

    /**
     * Preset ansi color usage
     *
     * Warning:
     * This is NOT a public API method and must not be used in own extensions!
     * This method is usually only used in tests to preset the ansi color usage
     *
     * @internal
     * @param bool $ansiColorUsage
     */
    public static function useAnsiColor($ansiColorUsage)
    {
        static::$ansiColorUsage = $ansiColorUsage;
    }
}
