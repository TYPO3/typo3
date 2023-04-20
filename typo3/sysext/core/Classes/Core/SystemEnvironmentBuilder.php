<?php

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

namespace TYPO3\CMS\Core\Core;

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
 * missing system/settings.php this script will be successful.
 *
 * The script aborts execution with an error message if
 * some part fails or conditions are not met.
 *
 * @internal This script is internal code and subject to change.
 */
class SystemEnvironmentBuilder
{
    /** @internal */
    public const REQUESTTYPE_FE = 1;
    /** @internal */
    public const REQUESTTYPE_BE = 2;
    /** @internal */
    public const REQUESTTYPE_CLI = 4;
    /** @internal */
    public const REQUESTTYPE_AJAX = 8;
    /** @internal */
    public const REQUESTTYPE_INSTALL = 16;

    /**
     * Run base setup.
     * This entry method is used in all scopes (FE, BE, Install Tool and CLI)
     *
     * @internal This method should not be used by 3rd party code. It will change without further notice.
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root
     */
    public static function run(int $entryPointLevel = 0, int $requestType = self::REQUESTTYPE_FE)
    {
        self::defineBaseConstants();
        $scriptPath = self::calculateScriptPath($entryPointLevel, $requestType);
        $rootPath = self::calculateRootPath($entryPointLevel, $requestType);

        self::initializeGlobalVariables();
        self::initializeGlobalTimeTrackingVariables();
        self::initializeEnvironment($requestType, $scriptPath, $rootPath);
    }

    /**
     * Some notes:
     *
     * HTTP_TYPO3_CONTEXT -> used with Apache suexec support
     * REDIRECT_TYPO3_CONTEXT -> used under some circumstances when value is set in the webserver and proxying the values to FPM
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
        // A linefeed, a carriage return, a CR-LF combination
        defined('LF') ?: define('LF', chr(10));
        defined('CR') ?: define('CR', chr(13));
        defined('CRLF') ?: define('CRLF', CR . LF);

        // A generic constant to state we are in TYPO3 scope. This is especially used in script files
        // like ext_localconf.php that run in global scope without class encapsulation: "defined('TYPO3') or die();"
        // This is a security measure to prevent script output if those files are located within document root and
        // called directly without bootstrap and error handling setup.
        defined('TYPO3') ?: define('TYPO3', true);

        // Relative path from document root to typo3/ directory, hardcoded to "typo3/"
        // @deprecated: will be removed in TYPO3 v13.0
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
     * @return string Absolute path to entry script
     */
    protected static function calculateScriptPath(int $entryPointLevel, int $requestType): string
    {
        $isCli = self::isCliRequestType($requestType);
        // Absolute path of the entry script that was called
        $scriptPath = GeneralUtility::fixWindowsFilePath((string)self::getPathThisScript($isCli));
        $rootPath = self::getRootPathFromScriptPath($scriptPath, $entryPointLevel);
        // Check if the root path has been set in the environment (e.g. by the composer installer)
        $rootPathFromEnvironment = self::getDefinedPathRoot();
        if ($rootPathFromEnvironment) {
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
            $rootPath = rtrim(GeneralUtility::fixWindowsFilePath($rootPathFromEnvironment), '/');
            $scriptPath = $rootPath . '/' . $scriptName;
        }
        return $scriptPath;
    }

    /**
     * Absolute path to the "classic" site root of the TYPO3 application.
     * This semantically refers to the directory where executable server-side code, configuration
     * and runtime files are located (e.g. typo3conf/ext, typo3/sysext, typo3temp/var).
     * In practice this is always identical to the public web document root path which contains
     * files that are served by the webserver directly (fileadmin/ and public resources).
     *
     * This is not to be confused with the app-path that is used in composer-mode installations (by default).
     * Resources in app-path are located outside the document root.
     *
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root
     * @param int $requestType
     * @return string Absolute path without trailing slash
     */
    protected static function calculateRootPath(int $entryPointLevel, int $requestType): string
    {
        // Check if the root path has been set in the environment (e.g. by the composer installer)
        $pathRoot = self::getDefinedPathRoot();
        if ($pathRoot) {
            return rtrim(GeneralUtility::fixWindowsFilePath($pathRoot), '/');
        }
        $isCli = self::isCliRequestType($requestType);
        // Absolute path of the entry script that was called
        $scriptPath = GeneralUtility::fixWindowsFilePath((string)self::getPathThisScript($isCli));
        return self::getRootPathFromScriptPath($scriptPath, $entryPointLevel);
    }

    /**
     * Set up / initialize several globals variables
     */
    protected static function initializeGlobalVariables()
    {
        // Unset variable(s) in global scope (security issue #13959)
        $GLOBALS['T3_SERVICES'] = [];
        /**
         * $TBE_STYLES configures backend styles and colors; Basically this contains
         * all the values that can be used to create new skins for TYPO3.
         * For information about making skins to TYPO3 you should consult the
         * documentation found at https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Configuration/GlobalVariables.html#confval-TBE_STYLES
         * However, $TBE_STYLES should be avoided in favor of $GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets'] since TYPO3 v12.3, as
         * $TBE_STYLES will be removed in TYPO3 v13.0.
         */
        $GLOBALS['TBE_STYLES'] = [];
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

    protected static function getDefinedPathRoot(): string
    {
        return getenv('TYPO3_PATH_ROOT') ?: getenv('REDIRECT_TYPO3_PATH_ROOT') ?: '';
    }

    /**
     * Initialize the Environment class
     */
    protected static function initializeEnvironment(int $requestType, string $scriptPath, string $sitePath)
    {
        $pathRoot = self::getDefinedPathRoot();
        if ($pathRoot) {
            $rootPathFromEnvironment = rtrim(GeneralUtility::fixWindowsFilePath($pathRoot), '/');
            if ($sitePath !== $rootPathFromEnvironment) {
                // This means, that we re-initialized the environment during a single request
                // This currently only happens in custom code or during functional testing
                // Once the constants are removed, we might be able to remove this code here as well and directly pass an environment to the application
                $scriptPath = $rootPathFromEnvironment . substr($scriptPath, strlen($sitePath));
                $sitePath = $rootPathFromEnvironment;
            }
        }

        $projectRootPath = (string)(getenv('TYPO3_PATH_APP') ?: getenv('REDIRECT_TYPO3_PATH_APP') ?: '');
        $projectRootPath = GeneralUtility::fixWindowsFilePath($projectRootPath);
        $isDifferentRootPath = ($projectRootPath && $projectRootPath !== $sitePath);
        Environment::initialize(
            static::createApplicationContext(),
            self::isCliRequestType($requestType),
            static::usesComposerClassLoading(),
            $isDifferentRootPath ? $projectRootPath : $sitePath,
            $sitePath,
            $isDifferentRootPath ? $projectRootPath . '/var' : $sitePath . '/typo3temp/var',
            $isDifferentRootPath ? $projectRootPath . '/config' : $sitePath . '/typo3conf',
            $scriptPath,
            self::isRunningOnWindows() ? 'WINDOWS' : 'UNIX'
        );
    }

    /**
     * Determine if the operating system TYPO3 is running on is windows.
     */
    protected static function isRunningOnWindows(): bool
    {
        return stripos(PHP_OS, 'darwin') === false
            && stripos(PHP_OS, 'cygwin') === false
            && stripos(PHP_OS, 'win') !== false;
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
     * Return path to entry script if not in cli mode.
     *
     * @return string Absolute path to entry script
     */
    protected static function getPathThisScriptNonCli()
    {
        if (Environment::isRunningOnCgiServer() && !Environment::usesCgiFixPathInfo()) {
            throw new \Exception('TYPO3 does only support being used with cgi.fix_pathinfo=1 on CGI server APIs.', 1675108421);
        }

        return $_SERVER['SCRIPT_FILENAME'];
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
        $scriptPath = $_SERVER['argv'][0] ?? $_ENV['_'] ?? $_SERVER['_'];
        // Find out if path is relative or not
        $isRelativePath = false;
        if (self::isRunningOnWindows()) {
            if (!preg_match('/^([a-zA-Z]:)?\\\\/', $scriptPath)) {
                $isRelativePath = true;
            }
        } elseif ($scriptPath[0] !== '/') {
            $isRelativePath = true;
        }
        // Concatenate path to current working directory with relative path and remove "/./" constructs
        if ($isRelativePath) {
            $workingDirectory = $_SERVER['PWD'] ?? getcwd();
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
            [$rootPath] = GeneralUtility::revExplode('/', $entryScriptDirectory, $entryPointLevel + 1);
        } else {
            $rootPath = $entryScriptDirectory;
        }
        return $rootPath;
    }

    protected static function usesComposerClassLoading(): bool
    {
        return defined('TYPO3_COMPOSER_MODE') && TYPO3_COMPOSER_MODE;
    }

    /**
     * Checks if request type is cli.
     * Falls back to check PHP_SAPI in case request type is not provided
     */
    protected static function isCliRequestType(?int $requestType): bool
    {
        if ($requestType === null) {
            $requestType = PHP_SAPI === 'cli' ? self::REQUESTTYPE_CLI : self::REQUESTTYPE_FE;
        }

        return ($requestType & self::REQUESTTYPE_CLI) === self::REQUESTTYPE_CLI;
    }
}
