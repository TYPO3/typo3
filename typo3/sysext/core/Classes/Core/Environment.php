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

namespace TYPO3\CMS\Core\Core;

use Composer\InstalledVersions;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This class is initialized once in the SystemEnvironmentBuilder, and can then
 * be used throughout the application to access common variables
 * related to path-resolving and OS-/PHP-application specific information.
 *
 * It's main design goal is to remove any access to constants within TYPO3 code and to provide a static,
 * for TYPO3 core and extensions non-changeable information.
 *
 * This class does not contain any HTTP related information, as this is handled in NormalizedParams functionality.
 *
 * All path-related methods do return the realpath to the paths without (!) the trailing slash.
 *
 * This class only defines what is configured through the environment, does not do any checks if paths exist
 * etc. This should be part of the application or the SystemEnvironmentBuilder.
 *
 * In your application, use it like this:
 *
 * Instead of writing "TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI" call "Environment::isCli()"
 */
class Environment
{
    /**
     * A list of supported CGI server APIs
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
     * @var bool
     */
    protected static $cli;

    /**
     * @var bool
     */
    protected static $composerMode;

    /**
     * @var ApplicationContext
     */
    protected static $context;

    /**
     * @var string
     */
    protected static $projectPath;

    /**
     * @var string
     */
    protected static $composerRootPath;

    /**
     * @var string
     */
    protected static $publicPath;

    /**
     * @var string
     */
    protected static $currentScript;

    /**
     * @var string
     */
    protected static $os;

    /**
     * @var string
     */
    protected static $varPath;

    /**
     * @var string
     */
    protected static $configPath;

    /**
     * Sets up the Environment. Please note that this is not public API and only used within the very early
     * Set up of TYPO3, or to be used within tests. If you ever call this method in your extension, you're probably
     * doing something wrong. Never call this method! Never rely on it!
     *
     * @param ApplicationContext $context
     * @param bool $cli
     * @param bool $composerMode
     * @param string $projectPath
     * @param string $publicPath
     * @param string $varPath
     * @param string $configPath
     * @param string $currentScript
     * @param string $os
     * @internal
     */
    public static function initialize(
        ApplicationContext $context,
        bool $cli,
        bool $composerMode,
        string $projectPath,
        string $publicPath,
        string $varPath,
        string $configPath,
        string $currentScript,
        string $os
    ) {
        self::$cli = $cli;
        self::$composerMode = $composerMode;
        self::$context = $context;
        self::$projectPath = $projectPath;
        self::$composerRootPath = $composerMode ? PathUtility::getCanonicalPath(InstalledVersions::getRootPackage()['install_path']) : '';
        self::$publicPath = $publicPath;
        self::$varPath = $varPath;
        self::$configPath = $configPath;
        self::$currentScript = $currentScript;
        self::$os = $os;
    }

    /**
     * Delivers the ApplicationContext object, usually defined in TYPO3_CONTEXT environment variables.
     * This is something like "Production", "Testing", or "Development" or any additional information
     * "Production/Staging".
     *
     * @return ApplicationContext
     */
    public static function getContext(): ApplicationContext
    {
        return self::$context;
    }

    /**
     * Informs whether TYPO3 has been installed via composer or not. Typically this is useful inside the
     * Maintenance Modules, or the Extension Manager.
     *
     * @return bool
     */
    public static function isComposerMode(): bool
    {
        return self::$composerMode;
    }

    /**
     * Whether the current PHP request is handled by a CLI SAPI module or not.
     *
     * @return bool
     */
    public static function isCli(): bool
    {
        return self::$cli;
    }

    /**
     * The root path to the project. For installations set up via composer, this is the path where your
     * composer.json file is stored. For non-composer-setups, this is (due to legacy reasons) the public web folder
     * where the TYPO3 installation has been unzipped (something like htdocs/ or public/ on your webfolder).
     * However, non-composer-mode installations define an environment variable called "TYPO3_PATH_APP"
     * to define a different folder (usually a parent folder) to allow TYPO3 to access and store data outside
     * of the public web folder.
     *
     * @return string The absolute path to the project without the trailing slash
     */
    public static function getProjectPath(): string
    {
        return self::$projectPath;
    }

    /**
     * In most cases in composer-mode setups this is the same as project path.
     * However since the project path is configurable, the paths may differ.
     * In future versions this configurability will go away and this method will be removed.
     * This path is only required for some internal path handling regarding package paths until then.
     * @internal
     *
     * @return string The absolute path to the composer root directory without the trailing slash
     */
    public static function getComposerRootPath(): string
    {
        if (self::$composerMode === false) {
            throw new \BadMethodCallException('Composer root path is only available in Composer mode', 1631700480);
        }

        return self::$composerRootPath;
    }

    /**
     * The public web folder where index.php (= the frontend application) is put, without trailing slash.
     * For non-composer installations, the project path = the public path.
     *
     * @return string
     */
    public static function getPublicPath(): string
    {
        return self::$publicPath;
    }

    /**
     * The folder where variable data like logs, sessions, locks, and cache files can be stored.
     * When project path = public path, then this folder is usually typo3temp/var/, otherwise it's set to
     * $project_path/var.
     *
     * @return string
     */
    public static function getVarPath(): string
    {
        return self::$varPath;
    }

    /**
     * The folder where all global (= installation-wide) configuration like
     * - LocalConfiguration.php,
     * - AdditionalConfiguration.php, and
     * - PackageStates.php
     * is put.
     * This folder usually has to be writable for TYPO3 in order to work.
     *
     * When project path = public path, then this folder is usually typo3conf/, otherwise it's set to
     * $project_path/config.
     *
     * @return string
     */
    public static function getConfigPath(): string
    {
        return self::$configPath;
    }

    /**
     * The path + filename to the current PHP script.
     *
     * @return string
     */
    public static function getCurrentScript(): string
    {
        return self::$currentScript;
    }

    /**
     * Helper methods to easily find occurrences, however as these properties are not computed
     * it is very possible that these methods will become obsolete in the near future.
     */

    /**
     * Previously found under typo3conf/l10n/
     * Please note that this might be gone at some point
     *
     * @return string
     */
    public static function getLabelsPath(): string
    {
        if (self::$publicPath === self::$projectPath) {
            return self::getPublicPath() . '/typo3conf/l10n';
        }
        return self::getVarPath() . '/labels';
    }

    /**
     * Previously known as PATH_typo3
     * Please note that this might be gone at some point
     *
     * @return string
     */
    public static function getBackendPath(): string
    {
        return self::getPublicPath() . '/typo3';
    }

    /**
     * Previously known as PATH_typo3 . 'sysext/'
     * Please note that this might be gone at some point
     *
     * @return string
     */
    public static function getFrameworkBasePath(): string
    {
        return self::getPublicPath() . '/typo3/sysext';
    }

    /**
     * Please note that this might be gone at some point
     *
     * @return string
     */
    public static function getExtensionsPath(): string
    {
        return self::getPublicPath() . '/typo3conf/ext';
    }

    /**
     * Previously known as PATH_typo3conf
     * Please note that this might be gone at some point
     *
     * @return string
     */
    public static function getLegacyConfigPath(): string
    {
        return self::getPublicPath() . '/typo3conf';
    }

    /**
     * Whether this TYPO3 installation runs on windows
     *
     * @return bool
     */
    public static function isWindows(): bool
    {
        return self::$os === 'WINDOWS';
    }

    /**
     * Whether this TYPO3 installation runs on unix (= non-windows machines)
     *
     * @return bool
     */
    public static function isUnix(): bool
    {
        return self::$os === 'UNIX';
    }

    /**
     * Returns true if the server is running on a list of supported CGI server APIs.
     *
     * @return bool
     */
    public static function isRunningOnCgiServer(): bool
    {
        return in_array(PHP_SAPI, self::$supportedCgiServerApis, true);
    }

    public static function usesCgiFixPathInfo(): bool
    {
        return !empty(ini_get('cgi.fix_pathinfo'));
    }

    /**
     * Returns the currently configured Environment information as array.
     *
     * @return array
     */
    public static function toArray(): array
    {
        return [
            'context' => (string)self::getContext(),
            'cli' => self::isCli(),
            'projectPath' => self::getProjectPath(),
            'publicPath' => self::getPublicPath(),
            'varPath' => self::getVarPath(),
            'configPath' => self::getConfigPath(),
            'currentScript' => self::getCurrentScript(),
            'os' => self::isWindows() ? 'WINDOWS' : 'UNIX',
        ];
    }
}
