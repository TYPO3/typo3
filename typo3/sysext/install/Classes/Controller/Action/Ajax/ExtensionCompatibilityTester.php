<?php
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Load Extensions
 *
 * The idea is to load ext_localconf and ext_tables of extensions one-by-one
 * until one of those files throws a fatal. The javascript will then recognise
 * the fatal and initiates another run that will leave out the fataling extension
 * to check the rest.
 */
class ExtensionCompatibilityTester extends AbstractAjaxAction
{
    /**
     * Store extension loading protocol
     *
     * @var string
     */
    protected $protocolFile = '';

    /**
     * Store errors that occurred during checks.
     *
     * @var string
     */
    protected $errorProtocolFile = '';

    /**
     * Define whether to log errors to file or not.
     *
     * @var bool
     */
    protected $logError = false;

    /**
     * Construct this class
     * set default protocol file location
     */
    public function __construct()
    {
        $this->protocolFile = PATH_site . 'typo3temp/assets/ExtensionCompatibilityTester.txt';
        $this->errorProtocolFile = PATH_site . 'typo3temp/assets/ExtensionCompatibilityTesterErrors.json';
    }

    /**
     * Main entry point for checking extensions to load,
     * setting up the checks (deleting protocol), and returning
     * OK if process run through without errors
     *
     * @return string "OK" if process ran through without errors
     */
    protected function executeAction()
    {
        register_shutdown_function([$this, 'logError']);
        $getVars = GeneralUtility::_GET('install');
        if (isset($getVars['extensionCompatibilityTester']) && isset($getVars['extensionCompatibilityTester']['forceCheck']) && ($getVars['extensionCompatibilityTester']['forceCheck'] == 1)) {
            $this->deleteProtocolFile();
        }
        $this->tryToLoadExtLocalconfAndExtTablesOfExtensions($this->getExtensionsToLoad());
        return 'OK';
    }

    /**
     * Delete the protocol files if they exist
     */
    protected function deleteProtocolFile()
    {
        if (file_exists($this->protocolFile)) {
            unlink($this->protocolFile);
        }
        if (file_exists($this->errorProtocolFile)) {
            unlink($this->errorProtocolFile);
        }
    }

    /**
     * Get extensions that should be loaded.
     * Fills the TYPO3_LOADED_EXT array.
     * Only considers local extensions
     *
     * @return array
     */
    protected function getExtensionsToLoad()
    {
        $extensionsToLoad = [];
        $extensionsToExclude = $this->getExtensionsToExclude();
        foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $key => $extension) {
            if (!in_array($key, $extensionsToExclude)) {
                $extensionsToLoad[$key] = $extension;
            }
        }
        return $extensionsToLoad;
    }

    /**
     * Gets extensions already known to be incompatible
     * This class is recursively called, and this method is needed
     * to not run into the same errors twice.
     *
     * @return array
     */
    protected function getExtensionsToExclude()
    {
        if (is_file($this->protocolFile)) {
            $exclude = (string)file_get_contents($this->protocolFile);
            return GeneralUtility::trimExplode(',', $exclude);
        }
        return [];
    }

    /**
     * Tries to load the ext_localconf and ext_tables files of all non-core extensions
     * Writes current extension name to file and deletes it again when inclusion was
     * successful.
     *
     * @param array $extensions
     */
    protected function tryToLoadExtLocalconfAndExtTablesOfExtensions(array $extensions)
    {
        foreach ($extensions as $extensionKey => $extension) {
            $this->writeCurrentExtensionToFile($extensionKey);
            $this->loadExtLocalconfForExtension($extensionKey, $extension);
            $this->removeCurrentExtensionFromFile($extensionKey);
        }
        ExtensionManagementUtility::loadBaseTca(false);
        foreach ($extensions as $extensionKey => $extension) {
            $this->writeCurrentExtensionToFile($extensionKey);
            $this->loadExtTablesForExtension($extensionKey, $extension);
            $this->removeCurrentExtensionFromFile($extensionKey);
        }
    }

    /**
     * Loads ext_tables.php for a single extension. Method is a modified copy of
     * the original bootstrap method.
     *
     * @param string $extensionKey
     * @param \ArrayAccess $extension
     */
    protected function loadExtTablesForExtension($extensionKey, array $extension)
    {
        // In general it is recommended to not rely on it to be globally defined in that
        // scope, but we can not prohibit this without breaking backwards compatibility
        global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
        global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
        global $PAGES_TYPES, $TBE_STYLES;
        global $_EXTKEY;
        // Load each ext_tables.php file of loaded extensions
        $_EXTKEY = $extensionKey;
        if (isset($extension['ext_tables.php']) && $extension['ext_tables.php']) {
            // $_EXTKEY and $_EXTCONF are available in ext_tables.php
            // and are explicitly set in cached file as well
            $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
            require $extension['ext_tables.php'];
        }
    }

    /**
     * Loads ext_localconf.php for a single extension. Method is a modified copy of
     * the original bootstrap method.
     *
     * @param string $extensionKey
     * @param \ArrayAccess $extension
     */
    protected function loadExtLocalconfForExtension($extensionKey, array $extension)
    {
        // This is the main array meant to be manipulated in the ext_localconf.php files
        // In general it is recommended to not rely on it to be globally defined in that
        // scope but to use $GLOBALS['TYPO3_CONF_VARS'] instead.
        // Nevertheless we define it here as global for backwards compatibility.
        global $TYPO3_CONF_VARS;
        $_EXTKEY = $extensionKey;
        if (isset($extension['ext_localconf.php']) && $extension['ext_localconf.php']) {
            // $_EXTKEY and $_EXTCONF are available in ext_localconf.php
            // and are explicitly set in cached file as well
            $_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
            require $extension['ext_localconf.php'];
        }
    }

    /**
     * Writes $extensionKey to the protocol file by adding it comma separated at
     * the end of the file.
     *
     * @param string $extensionKey
     */
    protected function writeCurrentExtensionToFile($extensionKey)
    {
        $incompatibleExtensions = $this->getExtensionsToExclude();
        $incompatibleExtensions = array_filter($incompatibleExtensions);
        $incompatibleExtensions = array_merge($incompatibleExtensions, [$extensionKey]);
        GeneralUtility::writeFileToTypo3tempDir($this->protocolFile, implode(', ', $incompatibleExtensions));
        $this->logError = true;
    }

    /**
     * Removes $extensionKey from protocol file.
     *
     * @param string $extensionKey
     */
    protected function removeCurrentExtensionFromFile($extensionKey)
    {
        $extensionsInFile = $this->getExtensionsToExclude();
        $extensionsInFile = array_filter($extensionsInFile);
        $extensionsByKey = array_flip($extensionsInFile);
        unset($extensionsByKey[$extensionKey]);
        $extensionsForFile = array_flip($extensionsByKey);
        GeneralUtility::writeFile($this->protocolFile, implode(', ', $extensionsForFile));
        $this->logError = false;
    }

    /**
     * Log last occurred error for logging.
     */
    public function logError()
    {
        $errors = [];

        // Logging is disabled.
        if (!$this->logError) {
            // Create an empty file to avoid 404 errors
            if (!is_file($this->errorProtocolFile)) {
                GeneralUtility::writeFileToTypo3tempDir($this->errorProtocolFile, json_encode($errors));
            }
            return;
        }

        // Fetch existing errors, add last one and write to file again.
        $lastError = error_get_last();

        if (file_exists($this->errorProtocolFile)) {
            $errors = json_decode(file_get_contents($this->errorProtocolFile));
        }
        switch ($lastError['type']) {
            case E_ERROR:
                $lastError['type'] = 'E_ERROR';
                break;
            case E_WARNING:
                $lastError['type'] = 'E_WARNING';
                break;
            case E_PARSE:
                $lastError['type'] = 'E_PARSE';
                break;
            case E_NOTICE:
                $lastError['type'] = 'E_NOTICE';
                break;
        }
        $errors[] = $lastError;

        GeneralUtility::writeFileToTypo3tempDir($this->errorProtocolFile, json_encode($errors));
    }
}
