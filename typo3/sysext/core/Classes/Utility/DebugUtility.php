<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class to handle debug
 */
class DebugUtility
{
    protected static bool $plainTextOutput = true;

    protected static bool $ansiColorUsage = true;

    /**
     * Debug
     *
     * Directly echos out debug information as HTML (or plain in CLI context)
     */
    public static function debug(mixed $var = '', string $header = 'Debug'): void
    {
        // buffer the output of debug if no buffering started before
        if (ob_get_level() === 0) {
            ob_start();
        }

        echo self::renderDump($var, $header);
    }

    /**
     * Converts a variable to a string
     *
     * @return string Plain, not HTML encoded string
     */
    public static function convertVariableToString(mixed $variable): string
    {
        $string = self::renderDump($variable, '', true, false);
        return $string === '' ? '| debug |' : $string;
    }

    /**
     * Opens a debug message inside a popup window
     * @deprecated since v12, will be removed in v13.
     */
    public static function debugInPopUpWindow(mixed $debugVariable, string $header = 'Debug', string $group = 'Debug'): void
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13.', E_USER_DEPRECATED);
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
							newWindow.document.body.innerHTML = newWindow.document.body.innerHTML + debugMessage;
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
						document.createRange().createContextualFragment(debugMessage),
						top.TYPO3.Severity.notice
					);
				} else {
					browserWindow(debugMessage, header, group);
				}
			})();
		';
        echo GeneralUtility::wrapJS($script, ['nonce' => self::resolveNonceValue()]);
    }

    /**
     * Displays the "path" of the function call stack in a string, using debug_backtrace
     *
     * @param bool $prependFileNames If set to true file names are added to the output
     * @return string Plain, not HTML encoded string
     */
    public static function debugTrail(bool $prependFileNames = false): string
    {
        $trail = debug_backtrace(0);
        $trail = array_reverse($trail);
        array_pop($trail);
        $path = [];
        foreach ($trail as $dat) {
            $fileInformation = $prependFileNames && !empty($dat['file']) ? $dat['file'] . ':' : '';
            $pathFragment = $fileInformation . ($dat['class'] ?? '') . ($dat['type'] ?? '') . $dat['function'];
            // add the path of the included file
            if (in_array($dat['function'], ['require', 'include', 'require_once', 'include_once'])) {
                $pathFragment .= '(' . PathUtility::stripPathSitePrefix($dat['args'][0]) . '),' . PathUtility::stripPathSitePrefix($dat['file']);
            }
            if (array_key_exists('line', $dat)) {
                $path[] = $pathFragment . '#' . $dat['line'];
            } else {
                $path[] = $pathFragment;
            }
        }
        return implode(' // ', $path);
    }

    /**
     * Displays an array as rows in a table. Useful to debug output like an array of database records.
     *
     * @param array $rows Array of arrays with similar keys
     * @param string $header Table header
     * @deprecated since v12, will be removed in v13.
     */
    public static function debugRows(array $rows, string $header = ''): void
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v12 in favor of ' . __CLASS__ . '::debug and will be removed with v13.', E_USER_DEPRECATED);
        self::debug($rows, $header);
    }

    /**
     * Returns a string with a list of ascii-values for the first $characters characters in $string
     *
     * @param string $string String to show ASCII value for
     * @param int $characters Number of characters to show
     * @return string The string with ASCII values in separated by a space char.
     */
    public static function ordinalValue(string $string, int $characters = 100): string
    {
        if (strlen($string) < $characters) {
            $characters = strlen($string);
        }
        $valuestring = '';
        for ($i = 0; $i < $characters; $i++) {
            $valuestring .= ' ' . ord($string[$i]);
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
    public static function viewArray(mixed $array_in): string
    {
        return self::renderDump($array_in);
    }

    /**
     * Prints an array
     *
     * @param mixed $array_in Array to print visually (in a table).
     * @see viewArray()
     * @deprecated since v12, will be removed in v13.
     */
    public static function printArray(mixed $array_in): void
    {
        trigger_error('Method ' . __METHOD__ . ' has been deprecated in v12 in favor of ' . __CLASS__ . '::viewArray and will be removed with v13.', E_USER_DEPRECATED);
        echo self::renderDump($array_in);
    }

    /**
     * Renders the dump according to the context, either for command line or as HTML output
     *
     * @param bool|null $plainText Omit or pass null to use the current default.
     * @param bool|null $ansiColors Omit or pass null to use the current default.
     */
    protected static function renderDump(mixed $variable, string $title = '', ?bool $plainText = null, ?bool $ansiColors = null): string
    {
        $plainText = $plainText ?? Environment::isCli() && self::$plainTextOutput;
        $ansiColors = $ansiColors ?? Environment::isCli() && self::$ansiColorUsage;
        return trim(DebuggerUtility::var_dump($variable, $title, 8, $plainText, $ansiColors, true));
    }

    /**
     * Preset plaintext output
     *
     * Warning:
     * This is NOT a public API method and must not be used in own extensions!
     * This method is usually only used in tests to preset the output behaviour
     *
     * @internal
     */
    public static function usePlainTextOutput(bool $plainTextOutput): void
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
     */
    public static function useAnsiColor(bool $ansiColorUsage): void
    {
        static::$ansiColorUsage = $ansiColorUsage;
    }

    protected static function resolveNonceValue(): string
    {
        return GeneralUtility::makeInstance(RequestId::class)->nonce->b64;
    }
}
