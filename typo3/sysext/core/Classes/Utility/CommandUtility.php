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

/**
 * Class to handle system commands.
 * finds executables (programs) on Unix and Windows without knowing where they are
 *
 * returns exec command for a program
 * or FALSE
 *
 * This class is meant to be used without instance:
 * $cmd = CommandUtility::getCommand ('awstats','perl');
 *
 * The data of this class is cached.
 * That means if a program is found once it don't have to be searched again.
 *
 * user functions:
 *
 * addPaths() could be used to extend the search paths
 * getCommand() get a command string
 * checkCommand() returns TRUE if a command is available
 *
 * Search paths that are included:
 * $TYPO3_CONF_VARS['GFX']['im_path_lzw'] or $TYPO3_CONF_VARS['GFX']['im_path']
 * $TYPO3_CONF_VARS['SYS']['binPath']
 * $GLOBALS['_SERVER']['PATH']
 * '/usr/bin/,/usr/local/bin/' on Unix
 *
 * binaries can be preconfigured with
 * $TYPO3_CONF_VARS['SYS']['binSetup']
 */
class CommandUtility
{
    /**
     * Tells if object is already initialized
     *
     * @var bool
     */
    protected static $initialized = false;

    /**
     * Contains application list. This is an array with the following structure:
     * - app => file name to the application (like 'tar' or 'bzip2')
     * - path => full path to the application without application name (like '/usr/bin/' for '/usr/bin/tar')
     * - valid => TRUE or FALSE
     * Array key is identical to 'app'.
     *
     * @var array
     */
    protected static $applications = [];

    /**
     * Paths where to search for applications
     *
     * @var array
     */
    protected static $paths = null;

    /**
     * Wrapper function for php exec function
     * Needs to be central to have better control and possible fix for issues
     *
     * @static
     * @param string $command
     * @param NULL|array $output
     * @param int $returnValue
     * @return NULL|array
     */
    public static function exec($command, &$output = null, &$returnValue = 0)
    {
        $lastLine = exec($command, $output, $returnValue);
        return $lastLine;
    }

    /**
     * Compile the command for running ImageMagick/GraphicsMagick.
     *
     * @param string $command Command to be run: identify, convert or combine/composite
     * @param string $parameters The parameters string
     * @param string $path Override the default path (e.g. used by the install tool)
     * @return string Compiled command that deals with IM6 & GraphicsMagick
     */
    public static function imageMagickCommand($command, $parameters, $path = '')
    {
        $gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        $isExt = TYPO3_OS == 'WIN' ? '.exe' : '';
        $switchCompositeParameters = false;
        if (!$path) {
            $path = $gfxConf['im_path'];
        }
        $path = GeneralUtility::fixWindowsFilePath($path);
        $im_version = strtolower($gfxConf['im_version_5']);
        // This is only used internally, has no effect outside
        if ($command === 'combine') {
            $command = 'composite';
        }
        // Compile the path & command
        if ($im_version === 'gm') {
            $switchCompositeParameters = true;
            $path = self::escapeShellArgument($path . 'gm' . $isExt) . ' ' . self::escapeShellArgument($command);
        } else {
            if ($im_version === 'im6') {
                $switchCompositeParameters = true;
            }
            $path = self::escapeShellArgument($path . ($command == 'composite' ? 'composite' : $command) . $isExt);
        }
        // strip profile information for thumbnails and reduce their size
        if ($parameters && $command != 'identify' && $gfxConf['im_useStripProfileByDefault'] && $gfxConf['im_stripProfileCommand'] != '') {
            if (strpos($parameters, $gfxConf['im_stripProfileCommand']) === false) {
                // Determine whether the strip profile action has be disabled by TypoScript:
                if ($parameters !== '-version' && strpos($parameters, '###SkipStripProfile###') === false) {
                    $parameters = $gfxConf['im_stripProfileCommand'] . ' ' . $parameters;
                } else {
                    $parameters = str_replace('###SkipStripProfile###', '', $parameters);
                }
            }
        }
        $cmdLine = $path . ' ' . $parameters;
        // Because of some weird incompatibilities between ImageMagick 4 and 6 (plus GraphicsMagick),
        // it is needed to change the parameters order under some preconditions
        if ($command == 'composite' && $switchCompositeParameters) {
            $paramsArr = GeneralUtility::unQuoteFilenames($parameters);
            // The mask image has been specified => swap the parameters
            $paramsArrCount = count($paramsArr);
            if ($paramsArrCount > 5) {
                $tmp = $paramsArr[$paramsArrCount - 3];
                $paramsArr[$paramsArrCount - 3] = $paramsArr[$paramsArrCount - 4];
                $paramsArr[$paramsArrCount - 4] = $tmp;
            }
            $cmdLine = $path . ' ' . implode(' ', $paramsArr);
        }
        return $cmdLine;
    }

    /**
     * Checks if a command is valid or not, updates global variables
     *
     * @param string $cmd The command that should be executed. eg: "convert"
     * @param string $handler Executer for the command. eg: "perl"
     * @return bool FALSE if cmd is not found, or -1 if the handler is not found
     */
    public static function checkCommand($cmd, $handler = '')
    {
        if (!self::init()) {
            return false;
        }

        if ($handler && !self::checkCommand($handler)) {
            return -1;
        }
            // Already checked and valid
        if (self::$applications[$cmd]['valid']) {
            return true;
        }
            // Is set but was (above) not TRUE
        if (isset(self::$applications[$cmd]['valid'])) {
            return false;
        }

        foreach (self::$paths as $path => $validPath) {
            // Ignore invalid (FALSE) paths
            if ($validPath) {
                if (TYPO3_OS == 'WIN') {
                    // Windows OS
                        // @todo Why is_executable() is not called here?
                    if (@is_file($path . $cmd)) {
                        self::$applications[$cmd]['app'] = $cmd;
                        self::$applications[$cmd]['path'] = $path;
                        self::$applications[$cmd]['valid'] = true;
                        return true;
                    }
                    if (@is_file($path . $cmd . '.exe')) {
                        self::$applications[$cmd]['app'] = $cmd . '.exe';
                        self::$applications[$cmd]['path'] = $path;
                        self::$applications[$cmd]['valid'] = true;
                        return true;
                    }
                } else {
                    // Unix-like OS
                    $filePath = realpath($path . $cmd);
                    if ($filePath && @is_executable($filePath)) {
                        self::$applications[$cmd]['app'] = $cmd;
                        self::$applications[$cmd]['path'] = $path;
                        self::$applications[$cmd]['valid'] = true;
                        return true;
                    }
                }
            }
        }

            // Try to get the executable with the command 'which'.
            // It does the same like already done, but maybe on other paths
        if (TYPO3_OS != 'WIN') {
            $cmd = @self::exec('which ' . $cmd);
            if (@is_executable($cmd)) {
                self::$applications[$cmd]['app'] = $cmd;
                self::$applications[$cmd]['path'] = dirname($cmd) . '/';
                self::$applications[$cmd]['valid'] = true;
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a command string for exec(), system()
     *
     * @param string $cmd The command that should be executed. eg: "convert"
     * @param string $handler Handler (executor) for the command. eg: "perl"
     * @param string $handlerOpt Options for the handler, like '-w' for "perl"
     * @return mixed Returns command string, or FALSE if cmd is not found, or -1 if the handler is not found
     */
    public static function getCommand($cmd, $handler = '', $handlerOpt = '')
    {
        if (!self::init()) {
            return false;
        }

            // Handler
        if ($handler) {
            $handler = self::getCommand($handler);

            if (!$handler) {
                return -1;
            }
            $handler .= ' ' . $handlerOpt . ' ';
        }

            // Command
        if (!self::checkCommand($cmd)) {
            return false;
        }
        $cmd = self::$applications[$cmd]['path'] . self::$applications[$cmd]['app'] . ' ';

        return trim($handler . $cmd);
    }

    /**
     * Extend the preset paths. This way an extension can install an executable and provide the path to \TYPO3\CMS\Core\Utility\CommandUtility
     *
     * @param string $paths Comma separated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with site root path (PATH_site).
     * @return void
     */
    public static function addPaths($paths)
    {
        self::initPaths($paths);
    }

    /**
     * Returns an array of search paths
     *
     * @param bool $addInvalid If set the array contains invalid path too. Then the key is the path and the value is empty
     * @return array Array of search paths (empty if exec is disabled)
     */
    public static function getPaths($addInvalid = false)
    {
        if (!self::init()) {
            return [];
        }

        $paths = self::$paths;

        if (!$addInvalid) {
            foreach ($paths as $path => $validPath) {
                if (!$validPath) {
                    unset($paths[$path]);
                }
            }
        }
        return $paths;
    }

    /**
     * Initializes this class
     *
     * @return bool
     */
    protected static function init()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function']) {
            return false;
        }
        if (!self::$initialized) {
            self::initPaths();
            self::$applications = self::getConfiguredApps();
            self::$initialized = true;
        }
        return true;
    }

    /**
     * Initializes and extends the preset paths with own
     *
     * @param string $paths Comma separated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with site root path (PATH_site).
     * @return void
     */
    protected static function initPaths($paths = '')
    {
        $doCheck = false;

            // Init global paths array if not already done
        if (!is_array(self::$paths)) {
            self::$paths = self::getPathsInternal();
            $doCheck = true;
        }
            // Merge the submitted paths array to the global
        if ($paths) {
            $paths = GeneralUtility::trimExplode(',', $paths, true);
            if (is_array($paths)) {
                foreach ($paths as $path) {
                    // Make absolute path of relative
                    if (!preg_match('#^/#', $path)) {
                        $path = PATH_site . $path;
                    }
                    if (!isset(self::$paths[$path])) {
                        if (@is_dir($path)) {
                            self::$paths[$path] = $path;
                        } else {
                            self::$paths[$path] = false;
                        }
                    }
                }
            }
        }
            // Check if new paths are invalid
        if ($doCheck) {
            foreach (self::$paths as $path => $valid) {
                // Ignore invalid (FALSE) paths
                if ($valid and !@is_dir($path)) {
                    self::$paths[$path] = false;
                }
            }
        }
    }

    /**
     * Processes and returns the paths from $GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup']
     *
     * @return array Array of commands and path
     */
    protected static function getConfiguredApps()
    {
        $cmdArr = [];

        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup']) {
            $binSetup = str_replace(['\'.chr(10).\'', '\' . LF . \''], LF, $GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup']);
            $pathSetup = preg_split('/[\n,]+/', $binSetup);
            foreach ($pathSetup as $val) {
                if (trim($val) === '') {
                    continue;
                }
                list($cmd, $cmdPath) = GeneralUtility::trimExplode('=', $val, true, 2);
                $cmdArr[$cmd]['app'] = basename($cmdPath);
                $cmdArr[$cmd]['path'] = dirname($cmdPath) . '/';
                $cmdArr[$cmd]['valid'] = true;
            }
        }

        return $cmdArr;
    }

    /**
     * Sets the search paths from different sources, internal
     *
     * @return array Array of absolute paths (keys and values are equal)
     */
    protected static function getPathsInternal()
    {
        $pathsArr = [];
        $sysPathArr = [];

            // Image magick paths first
            // im_path_lzw take precedence over im_path
        if (($imPath = ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'] ?: $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path']))) {
            $imPath = self::fixPath($imPath);
            $pathsArr[$imPath] = $imPath;
        }

            // Add configured paths
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['binPath']) {
            $sysPath = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['binPath'], true);
            foreach ($sysPath as $val) {
                $val = self::fixPath($val);
                $sysPathArr[$val] = $val;
            }
        }

            // Add path from environment
            // @todo how does this work for WIN
        if ($GLOBALS['_SERVER']['PATH']) {
            $sep = (TYPO3_OS == 'WIN' ? ';' : ':');
            $envPath = GeneralUtility::trimExplode($sep, $GLOBALS['_SERVER']['PATH'], true);
            foreach ($envPath as $val) {
                $val = self::fixPath($val);
                $sysPathArr[$val] = $val;
            }
        }

            // Set common paths for Unix (only)
        if (TYPO3_OS !== 'WIN') {
            $sysPathArr = array_merge($sysPathArr, [
                '/usr/bin/' => '/usr/bin/',
                '/usr/local/bin/' => '/usr/local/bin/',
            ]);
        }

        $pathsArr = array_merge($pathsArr, $sysPathArr);

        return $pathsArr;
    }

    /**
     * Set a path to the right format
     *
     * @param string $path Input path
     * @return string Output path
     */
    protected static function fixPath($path)
    {
        return str_replace('//', '/', $path . '/');
    }

    /**
     * Escape shell arguments (for example filenames) to be used on the local system.
     *
     * The setting UTF8filesystem will be taken into account.
     *
     * @param string[] $input Input arguments to be escaped
     * @return string[] Escaped shell arguments
     */
    public static function escapeShellArguments(array $input)
    {
        $isUTF8Filesystem = !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']);
        if ($isUTF8Filesystem) {
            $currentLocale = setlocale(LC_CTYPE, 0);
            setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
        }

        $output = array_map('escapeshellarg', $input);

        if ($isUTF8Filesystem) {
            setlocale(LC_CTYPE, $currentLocale);
        }

        return $output;
    }

    /**
     * Escape a shell argument (for example a filename) to be used on the local system.
     *
     * The setting UTF8filesystem will be taken into account.
     *
     * @param string $input Input-argument to be escaped
     * @return string Escaped shell argument
     */
    public static function escapeShellArgument($input)
    {
        return self::escapeShellArguments([$input])[0];
    }
}
