<?php
namespace TYPO3\CMS\Core\Core;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class to encapsulate base setup of bootstrap.
 *
 * This class contains all code that must be executed by every entry script.
 *
 * It sets up all basic paths, constants, global variables and checks
 * the basic environment TYPO3 runs in.
 *
 * This class does not use any TYPO3 instance specific configuration, it only
 * sets up things based on the server environment and core code. Even with a
 * missing typo3conf/localconf.php this script will be successful.
 *
 * The script aborts execution with an error message if
 * some part fails or conditions are not met.
 *
 * This script is internal code and subject to change.
 * DO NOT use it in own code, or be prepared your code might
 * break in future versions of the core.
 */
class SystemEnvironmentBuilder
{
    /** @internal */
    const REQUESTTYPE_FE = 1;
    /** @internal */
    const REQUESTTYPE_BE = 2;
    /** @internal */
    const REQUESTTYPE_CLI = 4;
    /** @internal */
    const REQUESTTYPE_AJAX = 8;
    /** @internal */
    const REQUESTTYPE_INSTALL = 16;

    /**
     * A list of supported CGI server APIs
     * NOTICE: This is a duplicate of the SAME array in GeneralUtility!
     *         It is duplicated here as this information is needed early in bootstrap
     *         and GeneralUtility is not available yet.
     * @var array
     */
    protected static $supportedCgiServerApis = [
        'fpm-fcgi',
        'cgi',
        'isapi',
        'cgi-fcgi',
        'srv', // HHVM with fastcgi
    ];

    /**
     * An array of disabled methods
     *
     * @var string[]
     */
    protected static $disabledFunctions;

    /**
     * Run base setup.
     * This entry method is used in all scopes (FE, BE, eid, ajax, ...)
     *
     * @internal This method should not be used by 3rd party code. It will change without further notice.
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root
     * @param int $requestType
     */
    public static function run(int $entryPointLevel = 0, int $requestType = self::REQUESTTYPE_FE)
    {
        self::defineBaseConstants();
        self::defineTypo3RequestTypes();
        self::setRequestType($requestType | ($requestType === self::REQUESTTYPE_BE && strpos($_REQUEST['route'] ?? '', '/ajax/') === 0 ? TYPO3_REQUESTTYPE_AJAX : 0));
        self::defineLegacyConstants($requestType === self::REQUESTTYPE_FE ? 'FE' : 'BE');
        $scriptPath = self::calculateScriptPath($entryPointLevel, $requestType);
        $rootPath = self::calculateRootPath($entryPointLevel, $requestType);

        self::initializeGlobalVariables();
        self::initializeGlobalTimeTrackingVariables();
        self::initializeBasicErrorReporting();

        $applicationContext = static::createApplicationContext();
        self::initializeEnvironment($applicationContext, $requestType, $scriptPath, $rootPath);
        GeneralUtility::presetApplicationContext($applicationContext);
    }

    protected static function createApplicationContext(): ApplicationContext
    {
        $applicationContext = getenv('TYPO3_CONTEXT') ?: (getenv('REDIRECT_TYPO3_CONTEXT') ?: 'Production');

        return new ApplicationContext($applicationContext);
    }

    /**
     * Define all simple constants that have no dependency to local configuration
     */
    protected static function defineBaseConstants()
    {
        // Check one of the constants and return early if already defined,
        // needed if multiple requests are handled in one process, for instance in functional testing.
        if (defined('TYPO3_version')) {
            return;
        }

        // This version, branch and copyright
        define('TYPO3_version', '10.1.0');
        define('TYPO3_branch', '10.1');
        define('TYPO3_copyright_year', '1998-' . date('Y'));

        // TYPO3 external links
        define('TYPO3_URL_GENERAL', 'https://typo3.org/');
        define('TYPO3_URL_LICENSE', 'https://typo3.org/typo3-cms/overview/licenses/');
        define('TYPO3_URL_EXCEPTION', 'https://typo3.org/go/exception/CMS/');
        define('TYPO3_URL_DONATE', 'https://typo3.org/community/contribute/donate/');
        define('TYPO3_URL_WIKI_OPCODECACHE', 'https://wiki.typo3.org/Opcode_Cache');

        // A linefeed, a carriage return, a CR-LF combination
        defined('LF') ?: define('LF', chr(10));
        defined('CR') ?: define('CR', chr(13));
        defined('CRLF') ?: define('CRLF', CR . LF);

        // Security related constant: Default value of fileDenyPattern
        define('FILE_DENY_PATTERN_DEFAULT', '\\.(php[3-7]?|phpsh|phtml|pht|phar|shtml|cgi)(\\..*)?$|\\.pl$|^\\.htaccess$');
        // Security related constant: List of file extensions that should be registered as php script file extensions
        define('PHP_EXTENSIONS_DEFAULT', 'php,php3,php4,php5,php6,php7,phpsh,inc,phtml,pht,phar');

        // Relative path from document root to typo3/ directory, hardcoded to "typo3/"
        if (!defined('TYPO3_mainDir')) {
            define('TYPO3_mainDir', 'typo3/');
        }
    }

    /**
     * Calculate script path. This is the absolute path to the entry script.
     * Can be something like '.../public/index.php' or '.../public/typo3/index.php' for
     * web calls, or '.../bin/typo3' or similar for cli calls.
     *
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root
     * @param int $requestType
     * @return string Absolute path to entry script
     */
    protected static function calculateScriptPath(int $entryPointLevel, int $requestType): string
    {
        $isCli = self::isCliRequestType($requestType);
        // Absolute path of the entry script that was called
        $scriptPath = GeneralUtility::fixWindowsFilePath(self::getPathThisScript($isCli));
        $rootPath = self::getRootPathFromScriptPath($scriptPath, $entryPointLevel);
        // Check if the root path has been set in the environment (e.g. by the composer installer)
        if (getenv('TYPO3_PATH_ROOT')) {
            if ($isCli && self::usesComposerClassLoading()) {
                // $scriptPath is used for various path calculations based on the document root
                // Therefore we assume it is always a subdirectory of the document root, which is not the case
                // in composer mode on cli, as the binary is in the composer bin directory.
                // Because of that, we enforce the document root path of this binary to be set
                $scriptName = 'typo3/sysext/core/bin/typo3';
            } else {
                // Base the script path on the path taken from the environment
                // to make relative path calculations work in case only one of both is symlinked
                // or has the real path
                $scriptName = ltrim(substr($scriptPath, strlen($rootPath)), '/');
            }
            $rootPath = rtrim(GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_ROOT')), '/');
            $scriptPath = $rootPath . '/' . $scriptName;
        }
        return $scriptPath;
    }

    /**
     * Absolute path to the root of the typo3 instance. This is often identical to the web document root path (eg. .../public),
     * but may be different. For instance helhum/typo3-secure-web uses this: Then, rootPath TYPO3_PATH_ROOT is the absolute path to
     * the private directory where code and runtime files are located (currently typo3/ext, typo3/sysext, fileadmin, typo3temp),
     * while TYPO3_PATH_WEB is the public/ web document folder that gets assets like filedamin and Resources/Public folders
     * from extensions linked in.
     *
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root
     * @param int $requestType
     * @return string Absolute path without trailing slash
     */
    protected static function calculateRootPath(int $entryPointLevel, int $requestType): string
    {
        // Check if the root path has been set in the environment (e.g. by the composer installer)
        if (getenv('TYPO3_PATH_ROOT')) {
            return rtrim(GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_ROOT')), '/');
        }
        $isCli = self::isCliRequestType($requestType);
        // Absolute path of the entry script that was called
        $scriptPath = GeneralUtility::fixWindowsFilePath(self::getPathThisScript($isCli));
        return self::getRootPathFromScriptPath($scriptPath, $entryPointLevel);
    }

    /**
     * Set up / initialize several globals variables
     */
    protected static function initializeGlobalVariables()
    {
        // Unset variable(s) in global scope (security issue #13959)
        $GLOBALS['T3_SERVICES'] = [];
    }

    /**
     * Initialize global time tracking variables.
     * These are helpers to for example output script parsetime at the end of a script.
     */
    protected static function initializeGlobalTimeTrackingVariables()
    {
        // EXEC_TIME is set so that the rest of the script has a common value for the script execution time
        $GLOBALS['EXEC_TIME'] = time();
        // $ACCESS_TIME is a common time in minutes for access control
        $GLOBALS['ACCESS_TIME'] = $GLOBALS['EXEC_TIME'] - $GLOBALS['EXEC_TIME'] % 60;
        // $SIM_EXEC_TIME is set to $EXEC_TIME but can be altered later in the script if we want to
        // simulate another execution-time when selecting from eg. a database
        $GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
        // If $SIM_EXEC_TIME is changed this value must be set accordingly
        $GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];
    }

    /**
     * Initialize the Environment class
     *
     * @param ApplicationContext $context
     * @param int $requestType
     * @param string $scriptPath
     * @param string $sitePath
     */
    protected static function initializeEnvironment(ApplicationContext $context, int $requestType, string $scriptPath, string $sitePath)
    {
        if (getenv('TYPO3_PATH_ROOT')) {
            $rootPathFromEnvironment = rtrim(GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_ROOT')), '/');
            if ($sitePath !== $rootPathFromEnvironment) {
                // This means, that we re-initialized the environment during a single request
                // This currently only happens in custom code or during functional testing
                // Once the constants are removed, we might be able to remove this code here as well and directly pass an environment to the application
                $scriptPath = $rootPathFromEnvironment . substr($scriptPath, strlen($sitePath));
                $sitePath = $rootPathFromEnvironment;
            }
        }

        $projectRootPath = GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_APP'));
        $isDifferentRootPath = ($projectRootPath && $projectRootPath !== $sitePath);
        Environment::initialize(
            $context,
            self::isCliRequestType($requestType),
            self::usesComposerClassLoading(),
            $isDifferentRootPath ? $projectRootPath : $sitePath,
            $sitePath,
            $isDifferentRootPath ? $projectRootPath . '/var'    : $sitePath . '/typo3temp/var',
            $isDifferentRootPath ? $projectRootPath . '/config' : $sitePath . '/typo3conf',
            $scriptPath,
            self::getTypo3Os() === 'WIN' ? 'WINDOWS' : 'UNIX'
        );
    }

    /**
     * Initialize basic error reporting.
     *
     * There are a lot of extensions that have no strict / notice / deprecated free
     * ext_localconf or ext_tables. Since the final error reporting must be set up
     * after those extension files are read, a default configuration is needed to
     * suppress error reporting meanwhile during further bootstrap.
     */
    protected static function initializeBasicErrorReporting()
    {
        // Core should be notice free at least until this point ...
        error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));
    }

    /**
     * Determine the operating system TYPO3 is running on.
     *
     * @return string Either 'WIN' if running on Windows, else empty string
     */
    protected static function getTypo3Os()
    {
        $typoOs = '';
        if (stripos(PHP_OS, 'darwin') === false && stripos(PHP_OS, 'cygwin') === false && stripos(PHP_OS, 'win') !== false) {
            $typoOs = 'WIN';
        }
        return $typoOs;
    }

    /**
     * Calculate script path.
     *
     * First step in path calculation: Goal is to find the absolute path of the entry script
     * that was called without resolving any links. This is important since the TYPO3 entry
     * points are often linked to a central core location, so we can not use the php magic
     * __FILE__ here, but resolve the called script path from given server environments.
     *
     * This path is important to calculate the document root. The strategy is to
     * find out the script name that was called in the first place and to subtract the local
     * part from it to find the document root.
     *
     * @param bool $isCli
     * @return string Absolute path to entry script
     */
    protected static function getPathThisScript(bool $isCli)
    {
        if ($isCli) {
            return self::getPathThisScriptCli();
        }
        return self::getPathThisScriptNonCli();
    }

    /**
     * Calculate path to entry script if not in cli mode.
     *
     * Depending on the environment, the script path is found in different $_SERVER variables.
     *
     * @return string Absolute path to entry script
     */
    protected static function getPathThisScriptNonCli()
    {
        $cgiPath = '';
        if (isset($_SERVER['ORIG_PATH_TRANSLATED'])) {
            $cgiPath = $_SERVER['ORIG_PATH_TRANSLATED'];
        } elseif (isset($_SERVER['PATH_TRANSLATED'])) {
            $cgiPath = $_SERVER['PATH_TRANSLATED'];
        }
        if ($cgiPath && in_array(PHP_SAPI, self::$supportedCgiServerApis, true)) {
            $scriptPath = $cgiPath;
        } else {
            if (isset($_SERVER['ORIG_SCRIPT_FILENAME'])) {
                $scriptPath = $_SERVER['ORIG_SCRIPT_FILENAME'];
            } else {
                $scriptPath = $_SERVER['SCRIPT_FILENAME'];
            }
        }
        return $scriptPath;
    }

    /**
     * Calculate path to entry script if in cli mode.
     *
     * First argument of a cli script is the path to the script that was called. If the script does not start
     * with / (or A:\ for Windows), the path is not absolute yet, and the current working directory is added.
     *
     * @return string Absolute path to entry script
     */
    protected static function getPathThisScriptCli()
    {
        // Possible relative path of the called script
        if (isset($_SERVER['argv'][0])) {
            $scriptPath = $_SERVER['argv'][0];
        } elseif (isset($_ENV['_'])) {
            $scriptPath = $_ENV['_'];
        } else {
            $scriptPath = $_SERVER['_'];
        }
        // Find out if path is relative or not
        $isRelativePath = false;
        if (self::getTypo3Os() === 'WIN') {
            if (!preg_match('/^([a-zA-Z]:)?\\\\/', $scriptPath)) {
                $isRelativePath = true;
            }
        } else {
            if ($scriptPath[0] !== '/') {
                $isRelativePath = true;
            }
        }
        // Concatenate path to current working directory with relative path and remove "/./" constructs
        if ($isRelativePath) {
            if (isset($_SERVER['PWD'])) {
                $workingDirectory = $_SERVER['PWD'];
            } else {
                $workingDirectory = getcwd();
            }
            $scriptPath = $workingDirectory . '/' . preg_replace('/\\.\\//', '', $scriptPath);
        }
        return $scriptPath;
    }

    /**
     * Calculate the document root part to the instance from $scriptPath.
     * This is based on the amount of subdirectories "under" root path where $scriptPath is located.
     *
     * The following main scenarios for entry points exist by default in the TYPO3 core:
     * - Directly called documentRoot/index.php (-> FE call or eiD include): index.php is located in the same directory
     * as the main project. The document root is identical to the directory the script is located at.
     * - The install tool, located under typo3/install.php.
     * - A Backend script: This is the case for the typo3/index.php dispatcher and other entry scripts like 'typo3/sysext/core/bin/typo3'
     * or 'typo3/index.php' that are located inside typo3/ directly.
     *
     * @param string $scriptPath Calculated path to the entry script
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root
     * @return string Absolute path to document root of installation without trailing slash
     */
    protected static function getRootPathFromScriptPath($scriptPath, $entryPointLevel)
    {
        $entryScriptDirectory = PathUtility::dirnameDuringBootstrap($scriptPath);
        if ($entryPointLevel > 0) {
            list($rootPath) = GeneralUtility::revExplode('/', $entryScriptDirectory, $entryPointLevel + 1);
        } else {
            $rootPath = $entryScriptDirectory;
        }
        return $rootPath;
    }

    /**
     * Send http headers, echo out a text message and exit with error code
     *
     * @param string $message
     */
    protected static function exitWithMessage($message)
    {
        $headers = [
            \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_500,
            'Content-Type: text/plain'
        ];
        if (!headers_sent()) {
            foreach ($headers as $header) {
                header($header);
            }
        }
        echo $message . LF;
        exit(1);
    }

    /**
     * Check if the given function is disabled in the system
     *
     * @param string $function
     * @return bool
     */
    public static function isFunctionDisabled($function)
    {
        if (static::$disabledFunctions === null) {
            static::$disabledFunctions = GeneralUtility::trimExplode(',', ini_get('disable_functions'));
        }
        if (!empty(static::$disabledFunctions)) {
            return in_array($function, static::$disabledFunctions, true);
        }

        return false;
    }

    /**
     * @return bool
     */
    protected static function usesComposerClassLoading(): bool
    {
        return defined('TYPO3_COMPOSER_MODE') && TYPO3_COMPOSER_MODE;
    }

    /**
     * Define TYPO3_REQUESTTYPE* constants that can be used for developers to see if any context has been hit
     * also see setRequestType(). Is done at the very beginning so these parameters are always available.
     */
    protected static function defineTypo3RequestTypes()
    {
        // Check one of the constants and return early if already defined,
        // needed if multiple requests are handled in one process, for instance in functional testing.
        if (defined('TYPO3_REQUESTTYPE_FE')) {
            return;
        }
        define('TYPO3_REQUESTTYPE_FE', self::REQUESTTYPE_FE);
        define('TYPO3_REQUESTTYPE_BE', self::REQUESTTYPE_BE);
        define('TYPO3_REQUESTTYPE_CLI', self::REQUESTTYPE_CLI);
        define('TYPO3_REQUESTTYPE_AJAX', self::REQUESTTYPE_AJAX);
        define('TYPO3_REQUESTTYPE_INSTALL', self::REQUESTTYPE_INSTALL);
    }

    /**
     * Defines the TYPO3_REQUESTTYPE constant so the environment knows which context the request is running.
     *
     * @param int $requestType
     */
    protected static function setRequestType(int $requestType)
    {
        // Return early if already defined,
        // needed if multiple requests are handled in one process, for instance in functional testing.
        if (defined('TYPO3_REQUESTTYPE')) {
            return;
        }
        define('TYPO3_REQUESTTYPE', $requestType);
    }

    /**
     * Define constants and variables
     *
     * @param string
     */
    protected static function defineLegacyConstants(string $mode)
    {
        // Return early if already defined,
        // needed if multiple requests are handled in one process, for instance in functional testing.
        if (defined('TYPO3_MODE')) {
            return;
        }
        define('TYPO3_MODE', $mode);
    }

    /**
     * Checks if request type is cli.
     * Falls back to check PHP_SAPI in case request type is not provided
     *
     * @param int|null $requestType
     * @return bool
     */
    protected static function isCliRequestType(?int $requestType): bool
    {
        if ($requestType === null) {
            $requestType = PHP_SAPI === 'cli' ? self::REQUESTTYPE_CLI : self::REQUESTTYPE_FE;
        }

        return ($requestType & self::REQUESTTYPE_CLI) === self::REQUESTTYPE_CLI;
    }
}
