<?php
namespace TYPO3\CMS\Core\Controller;

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

/**
 * TYPO3 cli script basis
 */
class CommandLineController
{
    /**
     * Command line arguments, exploded into key => value-array pairs
     *
     * @var array
     */
    public $cli_args = [];

    /**
     * @var array
     */
    public $cli_options = [
        ['-s', 'Silent operation, will only output errors and important messages.'],
        ['--silent', 'Same as -s'],
        ['-ss', 'Super silent, will not even output errors or important messages.']
    ];

    /**
     * @var array
     */
    public $cli_help = [
        'name' => 'CLI base class (overwrite this...)',
        'synopsis' => '###OPTIONS###',
        'description' => 'Class with basic functionality for CLI scripts (overwrite this...)',
        'examples' => 'Give examples...',
        'options' => '',
        'license' => 'GNU GPL - free software!',
        'author' => '[Author name]'
    ];

    /**
     * @var resource
     */
    public $stdin = null;

    /**
     * Constructor
     * Make sure child classes also call this!
     *
     * @return void
     */
    public function __construct()
    {
        // Loads the cli_args array with command line arguments
        $this->cli_setArguments($_SERVER['argv']);
    }

    /**
     * Finds the arg token (like "-s") in argv and returns the rest of argv from that point on.
     * This should only be used in special cases since this->cli_args should already be prepared with an index of values!
     *
     * @param string $option Option string, eg. "-s
     * @param array $argv Input argv array
     * @return array Output argv array with all options AFTER the found option.
     */
    public function cli_getArgArray($option, $argv)
    {
        while (count($argv) && (string)$argv[0] !== (string)$option) {
            array_shift($argv);
        }
        if ((string)$argv[0] === (string)$option) {
            array_shift($argv);
            return !empty($argv) ? $argv : [''];
        }
    }

    /**
     * Return TRUE if option is found
     *
     * @param string $option Option string, eg. "-s
     * @return bool TRUE if option found
     */
    public function cli_isArg($option)
    {
        return isset($this->cli_args[$option]);
    }

    /**
     * Return argument value
     *
     * @param string $option Option string, eg. "-s
     * @param int $idx Value index, default is 0 (zero) = the first one...
     * @return bool TRUE if option found
     */
    public function cli_argValue($option, $idx = 0)
    {
        return is_array($this->cli_args[$option]) ? $this->cli_args[$option][$idx] : '';
    }

    /**
     * Will parse "_SERVER[argv]" into an index of options and values
     * Argument names (eg. "-s") will be keys and values after (eg. "-s value1 value2 ..." or "-s=value1") will be in the array.
     * Array is empty if no values
     *
     * @param array $argv Configuration options
     * @return array
     */
    public function cli_getArgIndex(array $argv = [])
    {
        $cli_options = [];
        $index = '_DEFAULT';
        foreach ($argv as $token) {
            // Options starting with a number is invalid - they could be negative values!
            if ($token[0] === '-' && !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($token[1])) {
                list($index, $opt) = explode('=', $token, 2);
                if (isset($cli_options[$index])) {
                    echo 'ERROR: Option ' . $index . ' was used twice!' . LF;
                    die;
                }
                $cli_options[$index] = [];
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
     * Validates if the input arguments in this->cli_args are all listed in this->cli_options and if not,
     * will exit with an error.
     *
     */
    public function cli_validateArgs()
    {
        $cli_args_copy = $this->cli_args;
        unset($cli_args_copy['_DEFAULT']);
        $allOptions = [];
        foreach ($this->cli_options as $cfg) {
            $allOptions[] = $cfg[0];
            $argSplit = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $cfg[0], true);
            if (isset($cli_args_copy[$argSplit[0]])) {
                foreach ($argSplit as $i => $v) {
                    $ii = $i;
                    if ($i > 0) {
                        if (!isset($cli_args_copy[$argSplit[0]][$i - 1]) && $v[0] != '[') {
                            // Using "[]" around a parameter makes it optional
                            echo 'ERROR: Option "' . $argSplit[0] . '" requires a value ("' . $v . '") on position ' . $i . LF;
                            die;
                        }
                    }
                }
                $ii++;
                if (isset($cli_args_copy[$argSplit[0]][$ii - 1])) {
                    echo 'ERROR: Option "' . $argSplit[0] . '" does not support a value on position ' . $ii . LF;
                    die;
                }
                unset($cli_args_copy[$argSplit[0]]);
            }
        }
        if (!empty($cli_args_copy)) {
            echo wordwrap('ERROR: Option ' . implode(',', array_keys($cli_args_copy)) . ' was unknown to this script!' . LF . '(Options are: ' . implode(', ', $allOptions) . ')' . LF);
            die;
        }
    }

    /**
     * Set environment array to $cli_args
     *
     * @param array $argv Configuration options
     * @return void
     */
    public function cli_setArguments(array $argv = [])
    {
        $this->cli_args = $this->cli_getArgIndex($argv);
    }

    /**
     * Asks stdin for keyboard input and returns the line (after enter is pressed)
     *
     * @return string
     */
    public function cli_keyboardInput()
    {
        // Have to open the stdin stream only ONCE! otherwise I cannot read multiple lines from it... :
        if (!$this->stdin) {
            $this->stdin = fopen('php://stdin', 'r');
        }
        while (false == ($line = fgets($this->stdin, 1000))) {
        }
        return trim($line);
    }

    /**
     * Asks for Yes/No from shell and returns TRUE if "y" or "yes" is found as input.
     *
     * @param string $msg String to ask before...
     * @return bool TRUE if "y" or "yes" is the input (case insensitive)
     */
    public function cli_keyboardInput_yes($msg = '')
    {
        // ONLY makes sense to echo it out since we are awaiting keyboard input - that cannot be silenced
        echo $msg . ' (Yes/No + return): ';
        $input = strtolower($this->cli_keyboardInput());
        return $input === 'y' || $input === 'yes';
    }

    /**
     * Echos strings to shell, but respective silent-modes
     *
     * @param string $string The string
     * @param bool $force If string should be written even if -s is set (-ss will subdue it!)
     * @return bool Returns TRUE if string was outputted.
     */
    public function cli_echo($string = '', $force = false)
    {
        if (isset($this->cli_args['-ss'])) {
        } elseif (isset($this->cli_args['-s']) || isset($this->cli_args['--silent'])) {
            if ($force) {
                echo $string;
                return true;
            }
        } else {
            echo $string;
            return true;
        }
        return false;
    }

    /**
     * Prints help-output from ->cli_help array
     *
     * @return void
     */
    public function cli_help()
    {
        foreach ($this->cli_help as $key => $value) {
            $this->cli_echo(strtoupper($key) . ':
');
            switch ($key) {
                case 'synopsis':
                    $optStr = '';
                    foreach ($this->cli_options as $v) {
                        $optStr .= ' [' . $v[0] . ']';
                    }
                    $this->cli_echo($this->cli_indent(str_replace('###OPTIONS###', trim($optStr), $value), 4) . '

');
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
                    $this->cli_echo($this->cli_indent($value, 4) . '

');
            }
        }
    }

    /**
     * Indentation function for 75 char wide lines.
     *
     * @param string $str String to break and indent.
     * @param int $indent Number of space chars to indent.
     * @return string Result
     */
    public function cli_indent($str, $indent)
    {
        $lines = explode(LF, wordwrap($str, 75 - $indent));
        $indentStr = str_pad('', $indent, ' ');
        foreach ($lines as $k => $v) {
            $lines[$k] = $indentStr . $lines[$k];
        }
        return implode(LF, $lines);
    }
}
