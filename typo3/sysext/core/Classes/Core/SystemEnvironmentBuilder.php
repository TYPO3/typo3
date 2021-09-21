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
use TYPO3\CMS\Core\Utility\StringUtility;

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
        self::definePaths($entryPointLevel, $requestType);
        self::checkMainPathsExist();
        self::initializeGlobalVariables();
        self::initializeGlobalTimeTrackingVariables();
        self::initializeBasicErrorReporting();

        $applicationContext = static::createApplicationContext();
        self::initializeEnvironment($applicationContext, $requestType);
        GeneralUtility::presetApplicationContext($applicationContext);
    }

    /**
     * Some notes:
     *
     * HTTP_TYPO3_CONTEXT -> used with Apache suexec support
     * REDIRECT_TYPO3_CONTEXT -> used under some circumstances when value is set in the webserver and proxying the values to FPM
     * @return ApplicationContext
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected static function createApplicationContext(): ApplicationContext
    {
        $applicationContext = getenv('TYPO3_CONTEXT') ?: (getenv('REDIRECT_TYPO3_CONTEXT') ?: (getenv('HTTP_TYPO3_CONTEXT') ?: 'Production'));

        return new ApplicationContext($applicationContext);
    }

    /**
     * Define all simple constants that have no dependency to local configuration
     */
    protected static function defineBaseConstants()
    {
        // Check one of the constants and return early if defined already
        if (defined('TYPO3_version')) {
            return;
        }

        // This version, branch and copyright
        define('TYPO3_version', '9.5.31');
        define('TYPO3_branch', '9.5');
        define('TYPO3_copyright_year', '1998-' . date('Y'));

        // TYPO3 external links
        define('TYPO3_URL_GENERAL', 'https://typo3.org/');
        define('TYPO3_URL_LICENSE', 'https://typo3.org/project/licenses/');
        define('TYPO3_URL_EXCEPTION', 'https://typo3.org/go/exception/CMS/');
        define('TYPO3_URL_DONATE', 'https://typo3.org/community/contribute/donate/');
        define('TYPO3_URL_WIKI_OPCODECACHE', 'https://docs.typo3.org/m/typo3/guide-installation/master/en-us/Troubleshooting/Index.html#opcode-cache-messages');

        // @deprecated since TYPO3 v9.4 and will be removed in TYPO3 v10.0
        define('TYPO3_URL_MAILINGLISTS', 'http://lists.typo3.org/cgi-bin/mailman/listinfo');
        define('TYPO3_URL_DOCUMENTATION', 'https://typo3.org/documentation/');
        define('TYPO3_URL_DOCUMENTATION_TSREF', 'https://docs.typo3.org/typo3cms/TyposcriptReference/');
        define('TYPO3_URL_DOCUMENTATION_TSCONFIG', 'https://docs.typo3.org/typo3cms/TSconfigReference/');
        define('TYPO3_URL_CONSULTANCY', 'https://typo3.org/support/professional-services/');
        define('TYPO3_URL_CONTRIBUTE', 'https://typo3.org/contribute/');
        define('TYPO3_URL_SECURITY', 'https://typo3.org/teams/security/');
        define('TYPO3_URL_DOWNLOAD', 'https://typo3.org/download/');
        define('TYPO3_URL_SYSTEMREQUIREMENTS', 'https://typo3.org/typo3-cms/overview/requirements/');

        // A linefeed, a carriage return, a CR-LF combination
        defined('LF') ?: define('LF', chr(10));
        defined('CR') ?: define('CR', chr(13));
        defined('CRLF') ?: define('CRLF', CR . LF);

        // @deprecated since TYPO3 v9.4 and will be removed in TYPO3 v10.0
        defined('NUL') ?: define('NUL', "\0");
        defined('TAB') ?: define('TAB', "\t");
        defined('SUB') ?: define('SUB', chr(26));

        // Security related constant: Default value of fileDenyPattern
        define('FILE_DENY_PATTERN_DEFAULT', '\\.(php[3-8]?|phpsh|phtml|pht|phar|shtml|cgi)(\\..*)?$|\\.pl$|^\\.htaccess$');
        // Security related constant: List of file extensions that should be registered as php script file extensions
        define('PHP_EXTENSIONS_DEFAULT', 'php,php3,php4,php5,php6,php7,php8,phpsh,inc,phtml,pht,phar');

        // Operating system identifier
        // Either "WIN" or empty string
        defined('TYPO3_OS') ?: define('TYPO3_OS', self::getTypo3Os());

        // Service error constants
        // @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0, use the class constants in AbstractService instead.
        // General error - something went wrong
        define('T3_ERR_SV_GENERAL', -1);
        // During execution it showed that the service is not available and should be ignored. The service itself should call $this->setNonAvailable()
        define('T3_ERR_SV_NOT_AVAIL', -2);
        // Passed subtype is not possible with this service
        define('T3_ERR_SV_WRONG_SUBTYPE', -3);
        // Passed subtype is not possible with this service
        define('T3_ERR_SV_NO_INPUT', -4);
        // File not found which the service should process
        define('T3_ERR_SV_FILE_NOT_FOUND', -20);
        // File not readable
        define('T3_ERR_SV_FILE_READ', -21);
        // File not writable
        define('T3_ERR_SV_FILE_WRITE', -22);
        // Passed subtype is not possible with this service
        define('T3_ERR_SV_PROG_NOT_FOUND', -40);
        // Passed subtype is not possible with this service
        define('T3_ERR_SV_PROG_FAILED', -41);
    }

    /**
     * Calculate all required base paths and set as constants.
     *
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root
     * @param int $requestType
     */
    protected static function definePaths(int $entryPointLevel, int $requestType)
    {
        $isCli = self::isCliRequestType($requestType);
        // Absolute path of the entry script that was called
        $scriptPath = GeneralUtility::fixWindowsFilePath(self::getPathThisScript($isCli));
        $rootPath = self::getRootPathFromScriptPath($scriptPath, $entryPointLevel);
        // Check if the root path has been set in the environment (e.g. by the composer installer)
        if (getenv('TYPO3_PATH_ROOT')) {
            if ($isCli && self::usesComposerClassLoading() && StringUtility::endsWith($scriptPath, 'typo3')) {
                // PATH_thisScript is used for various path calculations based on the document root
                // Therefore we assume it is always a subdirectory of the document root, which is not the case
                // in composer mode on cli, as the binary is in the composer bin directory.
                // Because of that, we enforce the document root path of this binary to be set
                $scriptName = '/typo3/sysext/core/bin/typo3';
            } else {
                // Base the script path on the path taken from the environment
                // to make relative path calculations work in case only one of both is symlinked
                // or has the real path
                $scriptName = substr($scriptPath, strlen($rootPath));
            }
            $rootPath = GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_ROOT'));
            $scriptPath = $rootPath . $scriptName;
        }

        if (!defined('PATH_thisScript')) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
            define('PATH_thisScript', $scriptPath);
        }
        // Absolute path of the document root of the instance with trailing slash
        if (!defined('PATH_site')) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
            define('PATH_site', $rootPath . '/');
        }
        // Relative path from document root to typo3/ directory
        // Hardcoded to "typo3/"
        if (!defined('TYPO3_mainDir')) {
            define('TYPO3_mainDir', 'typo3/');
        }
        // Absolute path of the typo3 directory of the instance with trailing slash
        // Example "/var/www/instance-name/htdocs/typo3/"
        if (!defined('PATH_typo3')) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
            define('PATH_typo3', PATH_site . TYPO3_mainDir);
        }
        // Absolute path to the typo3conf directory with trailing slash
        // Example "/var/www/instance-name/htdocs/typo3conf/"
        if (!defined('PATH_typo3conf')) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
            define('PATH_typo3conf', PATH_site . 'typo3conf/');
        }
    }

    /**
     * Check if path and script file name calculation was successful, exit if not.
     */
    protected static function checkMainPathsExist()
    {
        if (!is_file(PATH_thisScript)) {
            static::exitWithMessage('Unable to determine path to entry script.');
        }
    }

    /**
     * Set up / initialize several globals variables
     */
    protected static function initializeGlobalVariables()
    {
        // Unset variable(s) in global scope (security issue #13959)
        $GLOBALS['TYPO3_MISC'] = [];
        $GLOBALS['T3_VAR'] = [];
        $GLOBALS['T3_SERVICES'] = [];
    }

    /**
     * Initialize global time tracking variables.
     * These are helpers to for example output script parsetime at the end of a script.
     */
    protected static function initializeGlobalTimeTrackingVariables()
    {
        // Microtime of (nearly) script start
        $GLOBALS['TYPO3_MISC']['microtime_start'] = microtime(true);
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
     * @param int|null $requestType
     */
    public static function initializeEnvironment(ApplicationContext $context, int $requestType = null)
    {
        // Absolute path of the entry script that was called
        $scriptPath = PATH_thisScript;
        $sitePath = rtrim(PATH_site, '/');

        if (getenv('TYPO3_PATH_ROOT')) {
            $rootPathFromEnvironment = GeneralUtility::fixWindowsFilePath(getenv('TYPO3_PATH_ROOT'));
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
        if (!stristr(PHP_OS, 'darwin') && !stristr(PHP_OS, 'cygwin') && stristr(PHP_OS, 'win')) {
            $typoOs = 'WIN';
        }
        return $typoOs;
    }

    /**
     * Calculate PATH_thisScript
     *
     * First step in path calculation: Goal is to find the absolute path of the entry script
     * that was called without resolving any links. This is important since the TYPO3 entry
     * points are often linked to a central core location, so we can not use the php magic
     * __FILE__ here, but resolve the called script path from given server environments.
     *
     * This path is important to calculate the document root (PATH_site). The strategy is to
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
     * Calculate the document root part to the instance from PATH_thisScript.
     * This is based on the amount of subdirectories "under" PATH_site where PATH_thisScript is located.
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
     * @return string Absolute path to document root of installation
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
        if (defined('TYPO3_REQUESTTYPE_FE')) { // @todo remove once Bootstrap::getInstance() is dropped
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
        if (defined('TYPO3_REQUESTTYPE')) { // @todo remove once Bootstrap::getInstance() is dropped
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
        if (defined('TYPO3_MODE')) { // @todo remove once Bootstrap::getInstance() is dropped
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
