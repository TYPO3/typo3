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
 * missing typo3conf/LocalConfiguration.php this script will be successful.
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
     * Run base setup.
     * This entry method is used in all scopes (FE, BE, Install Tool and CLI)
     *
     * @internal This method should not be used by 3rd party code. It will change without further notice.
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root
     * @param int $requestType
     */
    public static function run(int $entryPointLevel = 0, int $requestType = self::REQUESTTYPE_FE)
    {
        self::defineBaseConstants();
        self::defineTypo3RequestTypes();
        self::setRequestType($requestType | ($requestType === self::REQUESTTYPE_BE && (str_contains($_SERVER['REQUEST_URI'] ?? '', '/typo3/ajax/') || strpos($_REQUEST['route'] ?? '', '/ajax/') === 0) ? TYPO3_REQUESTTYPE_AJAX : 0));
        self::defineLegacyConstants($requestType === self::REQUESTTYPE_FE ? 'FE' : 'BE');
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
            $rootPath = rtrim(GeneralUtility::fixWindowsFilePath((string)getenv('TYPO3_PATH_ROOT')), '/');
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
        if (getenv('TYPO3_PATH_ROOT')) {
            return rtrim(GeneralUtility::fixWindowsFilePath((string)getenv('TYPO3_PATH_ROOT')), '/');
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
     * @param int $requestType
     * @param string $scriptPath
     * @param string $sitePath
     */
    protected static function initializeEnvironment(int $requestType, string $scriptPath, string $sitePath)
    {
        if (getenv('TYPO3_PATH_ROOT')) {
            $rootPathFromEnvironment = rtrim(GeneralUtility::fixWindowsFilePath((string)getenv('TYPO3_PATH_ROOT')), '/');
            if ($sitePath !== $rootPathFromEnvironment) {
                // This means, that we re-initialized the environment during a single request
                // This currently only happens in custom code or during functional testing
                // Once the constants are removed, we might be able to remove this code here as well and directly pass an environment to the application
                $scriptPath = $rootPathFromEnvironment . substr($scriptPath, strlen($sitePath));
                $sitePath = $rootPathFromEnvironment;
            }
        }

        $projectRootPath = GeneralUtility::fixWindowsFilePath((string)getenv('TYPO3_PATH_APP'));
        $isDifferentRootPath = ($projectRootPath && $projectRootPath !== $sitePath);
        Environment::initialize(
            static::createApplicationContext(),
            self::isCliRequestType($requestType),
            static::usesComposerClassLoading(),
            $isDifferentRootPath ? $projectRootPath : $sitePath,
            $sitePath,
            $isDifferentRootPath ? $projectRootPath . '/var'    : $sitePath . '/typo3temp/var',
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
     * Calculate path to entry script if not in cli mode.
     *
     * Depending on the environment, the script path is found in different $_SERVER variables.
     *
     * @return string Absolute path to entry script
     */
    protected static function getPathThisScriptNonCli()
    {
        $isCgi = Environment::isRunningOnCgiServer();
        if ($isCgi && Environment::usesCgiFixPathInfo()) {
            return $_SERVER['SCRIPT_FILENAME'];
        }
        $cgiPath = $_SERVER['ORIG_PATH_TRANSLATED'] ?? $_SERVER['PATH_TRANSLATED'] ?? '';
        if ($cgiPath && $isCgi) {
            return $cgiPath;
        }
        return $_SERVER['ORIG_SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_FILENAME'];
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
     *
     * @deprecated since v11, method can be removed in v12
     */
    protected static function defineTypo3RequestTypes()
    {
        // Check one of the constants and return early if already defined,
        // needed if multiple requests are handled in one process, for instance in functional testing.
        if (defined('TYPO3_REQUESTTYPE_FE')) {
            return;
        }
        /** @deprecated since v11, will be removed in v12. */
        define('TYPO3_REQUESTTYPE_FE', self::REQUESTTYPE_FE);
        /** @deprecated since v11, will be removed in v12. */
        define('TYPO3_REQUESTTYPE_BE', self::REQUESTTYPE_BE);
        /** @deprecated since v11, will be removed in v12. */
        define('TYPO3_REQUESTTYPE_CLI', self::REQUESTTYPE_CLI);
        /** @deprecated since v11, will be removed in v12. */
        define('TYPO3_REQUESTTYPE_AJAX', self::REQUESTTYPE_AJAX);
        /** @deprecated since v11, will be removed in v12. */
        define('TYPO3_REQUESTTYPE_INSTALL', self::REQUESTTYPE_INSTALL);
    }

    /**
     * Defines the TYPO3_REQUESTTYPE constant so the environment knows which context the request is running.
     *
     * @param int $requestType
     * @deprecated since v11, method can be removed in v12
     */
    protected static function setRequestType(int $requestType)
    {
        // Return early if already defined,
        // needed if multiple requests are handled in one process, for instance in functional testing.
        if (defined('TYPO3_REQUESTTYPE')) {
            return;
        }
        /** @deprecated since v11, will be removed in v12. Use Core\Http\ApplicationType API or $request->getAttribute('applicationType') instead */
        define('TYPO3_REQUESTTYPE', $requestType);
    }

    /**
     * Define constants and variables
     *
     * @param string $mode
     * @deprecated since v11, method can be removed in v12
     */
    protected static function defineLegacyConstants(string $mode)
    {
        // Return early if already defined,
        // needed if multiple requests are handled in one process, for instance in functional testing.
        if (defined('TYPO3_MODE')) {
            return;
        }
        /** @deprecated since v11, will be removed in v12. Use Core\Http\ApplicationType API instead */
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
