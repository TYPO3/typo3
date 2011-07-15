<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains base class for TYPO3 cli scripts
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


/**
 * TYPO3 cli script basis
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_cli {

	var $cli_args = array(); // Command line arguments, exploded into key => value-array pairs
	var $cli_options = array(
		array('-s', 'Silent operation, will only output errors and important messages.'),
		array('--silent', 'Same as -s'),
		array('-ss', 'Super silent, will not even output errors or important messages.'),
	);
	var $cli_help = array(
		'name' => 'CLI base class (overwrite this...)',
		'synopsis' => '###OPTIONS###',
		'description' => 'Class with basic functionality for CLI scripts (overwrite this...)',
		'examples' => 'Give examples...',
		'options' => '',
		'license' => 'GNU GPL - free software!',
		'author' => '[Author name]',
	);
	var $stdin = NULL;


	/**
	 * Constructor
	 * Make sure child classes also call this!
	 *
	 * @return	void
	 */
	function __construct() {
			// Loads the cli_args array with command line arguments
		$this->cli_args = $this->cli_getArgIndex();
	}

	/**
	 * Compatibility constructor.
	 *
	 * @deprecated since TYPO3 4.6 and will be removed in TYPO3 4.8. Use __construct() instead.
	 */
	public function t3lib_cli() {
		t3lib_div::logDeprecatedFunction();
			// Note: we cannot call $this->__construct() here because it would call the derived class constructor and cause recursion
			// This code uses official PHP behavior (http://www.php.net/manual/en/language.oop5.basic.php) when $this in the
			// statically called non-static method inherits $this from the caller's scope.
		t3lib_cli::__construct();
	}

	/**
	 * Finds the arg token (like "-s") in argv and returns the rest of argv from that point on.
	 * This should only be used in special cases since this->cli_args should already be prepared with an index of values!
	 *
	 * @param	string		Option string, eg. "-s"
	 * @param	array		Input argv array
	 * @return	array		Output argv array with all options AFTER the found option.
	 */
	function cli_getArgArray($option, $argv) {
		while (count($argv) && strcmp($argv[0], $option)) {
			array_shift($argv);
		}

		if (!strcmp($argv[0], $option)) {
			array_shift($argv);
			return count($argv) ? $argv : array('');
		}
	}

	/**
	 * Return TRUE if option is found
	 *
	 * @param	string		Option string, eg. "-s"
	 * @return	boolean		TRUE if option found
	 */
	function cli_isArg($option) {
		return isset($this->cli_args[$option]);
	}

	/**
	 * Return argument value
	 *
	 * @param	string		Option string, eg. "-s"
	 * @param	integer		Value index, default is 0 (zero) = the first one...
	 * @return	boolean		TRUE if option found
	 */
	function cli_argValue($option, $idx = 0) {
		return is_array($this->cli_args[$option]) ? $this->cli_args[$option][$idx] : '';
	}

	/**
	 * Will parse "_SERVER[argv]" into an index of options and values
	 * Argument names (eg. "-s") will be keys and values after (eg. "-s value1 value2 ..." or "-s=value1") will be in the array.
	 * Array is empty if no values
	 *
	 * @return	array
	 */
	function cli_getArgIndex() {
		$cli_options = array();
		$index = '_DEFAULT';
		foreach ($_SERVER['argv'] as $token) {
			if ($token{0} === '-' && !t3lib_utility_Math::canBeInterpretedAsInteger($token{1})) { // Options starting with a number is invalid - they could be negative values... !
				list($index, $opt) = explode('=', $token, 2);
				if (isset($cli_options[$index])) {
					echo 'ERROR: Option ' . $index . ' was used twice!' . LF;
					exit;
				}
				$cli_options[$index] = array();
				if (isset($opt)) {
					$cli_options[$index][] = $opt;
				}
			} else {
				$cli_options[$index][] = $token;
			}
		}

		return $cli_options;
	}

	/**
	 * Validates if the input arguments in this->cli_args are all listed in this->cli_options and if not, will exit with an error.
	 */
	function cli_validateArgs() {
		$cli_args_copy = $this->cli_args;
		unset($cli_args_copy['_DEFAULT']);
		$allOptions = array();

		foreach ($this->cli_options as $cfg) {
			$allOptions[] = $cfg[0];
			$argSplit = t3lib_div::trimExplode(' ', $cfg[0], 1);
			if (isset($cli_args_copy[$argSplit[0]])) {

				foreach ($argSplit as $i => $v) {
					$ii = $i;
					if ($i > 0) {
						if (!isset($cli_args_copy[$argSplit[0]][$i - 1]) && $v{0} != '[') { // Using "[]" around a paramter makes it optional
							echo 'ERROR: Option "' . $argSplit[0] . '" requires a value ("' . $v . '") on position ' . $i . LF;
							exit;
						}
					}
				}

				$ii++;
				if (isset($cli_args_copy[$argSplit[0]][$ii - 1])) {
					echo 'ERROR: Option "' . $argSplit[0] . '" does not support a value on position ' . $ii . LF;
					exit;
				}

				unset($cli_args_copy[$argSplit[0]]);
			}
		}

		if (count($cli_args_copy)) {
			echo wordwrap('ERROR: Option ' . implode(',', array_keys($cli_args_copy)) . ' was unknown to this script!' . LF . '(Options are: ' . implode(', ', $allOptions) . ')' . LF);
			exit;
		}
	}

	/**
	 * Asks stdin for keyboard input and returns the line (after enter is pressed)
	 *
	 * @return	string
	 */
	function cli_keyboardInput() {

			// Have to open the stdin stream only ONCE! otherwise I cannot read multiple lines from it... :
		if (!$this->stdin) {
			$this->stdin = fopen('php://stdin', 'r');
		}

		while (FALSE == ($line = fgets($this->stdin, 1000))) {
		}

		return trim($line);
	}

	/**
	 * Asks for Yes/No from shell and returns TRUE if "y" or "yes" is found as input.
	 *
	 * @param	string		String to ask before...
	 * @return	boolean		TRUE if "y" or "yes" is the input (case insensitive)
	 */
	function cli_keyboardInput_yes($msg = '') {
		echo $msg . ' (Yes/No + return): '; // ONLY makes sense to echo it out since we are awaiting keyboard input - that cannot be silenced...

		return t3lib_div::inList('y,yes', strtolower($this->cli_keyboardInput()));
	}

	/**
	 * Echos strings to shell, but respective silent-modes
	 *
	 * @param	string		The string
	 * @param	boolean		If string should be written even if -s is set (-ss will subdue it!)
	 * @return	boolean		Returns TRUE if string was outputted.
	 */
	function cli_echo($string = '', $force = FALSE) {
		if (isset($this->cli_args['-ss'])) {
			// Nothing to do...
		} elseif (isset($this->cli_args['-s']) || isset($this->cli_args['--silent'])) {
			if ($force) {
				echo $string;
				return TRUE;
			}
		} else {
			echo $string;
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Prints help-output from ->cli_help array
	 *
	 * @return	void
	 */
	function cli_help() {
		foreach ($this->cli_help as $key => $value) {
			$this->cli_echo(strtoupper($key) . ":\n");
			switch ($key) {
				case 'synopsis':
					$optStr = '';
					foreach ($this->cli_options as $v) {
						$optStr .= ' [' . $v[0] . ']';
					}
					$this->cli_echo($this->cli_indent(str_replace('###OPTIONS###', trim($optStr), $value), 4) . "\n\n");
					break;
				case 'options':
					$this->cli_echo($this->cli_indent($value, 4) . LF);

					$maxLen = 0;
					foreach ($this->cli_options as $v) {
						if (strlen($v[0]) > $maxLen) {
							$maxLen = strlen($v[0]);
						}
					}

					foreach ($this->cli_options as $v) {
						$this->cli_echo($v[0] . substr($this->cli_indent(rtrim($v[1] . LF . $v[2]), $maxLen + 4), strlen($v[0])) . LF);
					}
					$this->cli_echo(LF);
					break;
				default:
					$this->cli_echo($this->cli_indent($value, 4) . "\n\n");
					break;
			}
		}
	}

	/**
	 * Indentation function for 75 char wide lines.
	 *
	 * @param	string		String to break and indent.
	 * @param	integer		Number of space chars to indent.
	 * @return	string		Result
	 */
	function cli_indent($str, $indent) {
		$lines = explode(LF, wordwrap($str, 75 - $indent));
		$indentStr = str_pad('', $indent, ' ');
		foreach ($lines as $k => $v) {
			$lines[$k] = $indentStr . $lines[$k];
		}

		return implode(LF, $lines);
	}
}

?>