<?php
namespace TYPO3\CMS\Backend\Module;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This document provides a class that loads the modules for the TYPO3 interface.
 *
 * Load Backend Interface modules
 *
 * Typically instantiated like this:
 * $this->loadModules = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Module\ModuleLoader::class);
 * $this->loadModules->load($TBE_MODULES);
 * @internal
 */
class ModuleLoader
{
    /**
     * After the init() function this array will contain the structure of available modules for the backend user.
     *
     * @var array
     */
    public $modules = [];

    /**
     * Array with paths pointing to the location of modules from extensions
     *
     * @var array
     */
    public $absPathArray = [];

    /**
     * This array will hold the elements that should go into the select-list of modules for groups...
     *
     * @var array
     */
    public $modListGroup = [];

    /**
     * This array will hold the elements that should go into the select-list of modules for users...
     *
     * @var array
     */
    public $modListUser = [];

    /**
     * The backend user for use internally
     *
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    public $BE_USER;

    /**
     * If set TRUE, workspace "permissions" will be observed so non-allowed modules will not be included in the array of modules.
     *
     * @var bool
     */
    public $observeWorkspaces = false;

    /**
     * Contains the registered navigation components
     *
     * @var array
     */
    protected $navigationComponents = [];

    /**
     * Init.
     * The outcome of the load() function will be a $this->modules array populated with the backend module structure available to the BE_USER
     * Further the global var $LANG will have labels and images for the modules loaded in an internal array.
     *
     * @param array $modulesArray Should be the global var $TBE_MODULES, $BE_USER can optionally be set to an alternative Backend user object than the global var $BE_USER (which is the currently logged in user)
     * @param BackendUserAuthentication $beUser Optional backend user object to use. If not set, the global BE_USER object is used.
     * @return void
     */
    public function load($modulesArray, BackendUserAuthentication $beUser = null)
    {
        // Setting the backend user for use internally
        $this->BE_USER = $beUser ?: $GLOBALS['BE_USER'];

        /*$modulesArray might look like this when entering this function.
        Notice the two modules added by extensions - they have a path attachedArray
        (
        [web] => list,info,perm,func
        [file] => list
        [user] =>
        [tools] => em,install,txphpmyadmin
        [help] => about
        [_PATHS] => Array
        (
        [system_install] => /www/htdocs/typo3/32/coreinstall/typo3/ext/install/mod/
        [tools_txphpmyadmin] => /www/htdocs/typo3/32/coreinstall/typo3/ext/phpmyadmin/modsub/
        ))
         */
        $this->absPathArray = $modulesArray['_PATHS'];
        unset($modulesArray['_PATHS']);
        // Unset the array for calling external backend module dispatchers in typo3/index.php
        // (unused in Core, but in case some extension still sets this, we unset that)
        unset($modulesArray['_dispatcher']);
        // Unset the array for calling backend modules based on external backend module dispatchers in typo3/index.php
        unset($modulesArray['_configuration']);
        $this->navigationComponents = $modulesArray['_navigationComponents'];
        unset($modulesArray['_navigationComponents']);
        $theMods = $this->parseModulesArray($modulesArray);
        // Originally modules were found in typo3/mod/
        // User defined modules were found in ../typo3conf/
        // Today almost all modules reside in extensions and they are found by the _PATHS array of the incoming $TBE_MODULES array
        // Setting paths for 1) core modules (old concept from mod/) and 2) user-defined modules (from ../typo3conf)
        $paths = [];
        // Path of static modules
        $paths['defMods'] = PATH_typo3 . 'mod/';
        // Local modules (maybe frontend specific)
        $paths['userMods'] = PATH_typo3 . '../typo3conf/';
        // Traverses the module setup and creates the internal array $this->modules
        foreach ($theMods as $mods => $subMod) {
            $path = null;
            $extModRelPath = $this->checkExtensionModule($mods);
            // EXTENSION module:
            if ($extModRelPath) {
                $theMainMod = $this->checkMod($mods, PATH_site . $extModRelPath);
                if (is_array($theMainMod) || $theMainMod != 'notFound') {
                    // ... just so it goes on... submodules cannot be within this path!
                    $path = 1;
                }
            } else {
                // 'CLASSIC' module
                // Checking for typo3/mod/ module existence...
                $theMainMod = $this->checkMod($mods, $paths['defMods'] . $mods);
                if (is_array($theMainMod) || $theMainMod != 'notFound') {
                    $path = $paths['defMods'];
                } else {
                    // If not typo3/mod/ then it could be user-defined in typo3conf/ ...?
                    $theMainMod = $this->checkMod($mods, $paths['userMods'] . $mods);
                    if (is_array($theMainMod) || $theMainMod != 'notFound') {
                        $path = $paths['userMods'];
                    }
                }
            }
            // If $theMainMod is not set (FALSE) there is no access to the module !(?)
            if ($theMainMod && !is_null($path)) {
                $this->modules[$mods] = $theMainMod;
                // SUBMODULES - if any - are loaded
                if (is_array($subMod)) {
                    foreach ($subMod as $valsub) {
                        $extModRelPath = $this->checkExtensionModule($mods . '_' . $valsub);
                        if ($extModRelPath) {
                            // EXTENSION submodule:
                            $theTempSubMod = $this->checkMod($mods . '_' . $valsub, PATH_site . $extModRelPath);
                            // Default sub-module in either main-module-path, be it the default or the userdefined.
                            if (is_array($theTempSubMod)) {
                                $this->modules[$mods]['sub'][$valsub] = $theTempSubMod;
                            }
                        } else {
                            // 'CLASSIC' submodule
                            // Checking for typo3/mod/xxx/ module existence...
                            // @todo what about $path = 1; from above and using $path as string here?
                            $theTempSubMod = $this->checkMod($mods . '_' . $valsub, $path . $mods . '/' . $valsub);
                            // Default sub-module in either main-module-path, be it the default or the userdefined.
                            if (is_array($theTempSubMod)) {
                                $this->modules[$mods]['sub'][$valsub] = $theTempSubMod;
                            } elseif ($path == $paths['defMods']) {
                                // If the submodule did not exist in the default module path, then check if there is a submodule in the submodule path!
                                $theTempSubMod = $this->checkMod($mods . '_' . $valsub, $paths['userMods'] . $mods . '/' . $valsub);
                                if (is_array($theTempSubMod)) {
                                    $this->modules[$mods]['sub'][$valsub] = $theTempSubMod;
                                }
                            }
                        }
                    }
                }
            } else {
                // This must be done in order to fill out the select-lists for modules correctly!!
                if (is_array($subMod)) {
                    foreach ($subMod as $valsub) {
                        // @todo path can only be NULL here, or not?
                        $this->checkMod($mods . '_' . $valsub, $path . $mods . '/' . $valsub);
                    }
                }
            }
        }
    }

    /**
     * If the module name ($name) is a module from an extension (has path in $this->absPathArray)
     * then that path is returned relative to PATH_site
     *
     * @param string $name Module name
     * @return string If found, the relative path from PATH_site
     */
    public function checkExtensionModule($name)
    {
        if (isset($this->absPathArray[$name])) {
            return rtrim(PathUtility::stripPathSitePrefix($this->absPathArray[$name]), '/');
        }
        return '';
    }

    /**
     * Here we check for the module.
     *
     * Return values:
     * 'notFound':	If the module was not found in the path (no "conf.php" file)
     * FALSE:		If no access to the module (access check failed)
     * array():	    Configuration array, in case a valid module where access IS granted exists.
     *
     * @param string $name Module name
     * @param string $fullPath Absolute path to module
     * @return string|bool|array See description of function
     */
    public function checkMod($name, $fullPath)
    {
        if ($name === 'user_ws' && !ExtensionManagementUtility::isLoaded('version')) {
            return false;
        }
        // Check for own way of configuring module
        if (is_array($GLOBALS['TBE_MODULES']['_configuration'][$name]['configureModuleFunction'])) {
            $obj = $GLOBALS['TBE_MODULES']['_configuration'][$name]['configureModuleFunction'];
            if (is_callable($obj)) {
                $MCONF = call_user_func($obj, $name, $fullPath);
                if ($this->checkModAccess($name, $MCONF) !== true) {
                    return false;
                }
                return $MCONF;
            }
        }

        // merges $MCONF and $MLANG from conf.php and the additional configuration of the module
        $setupInformation = $this->getModuleSetupInformation($name, $fullPath);

        // Because 'path/../path' does not work
        // clean up the configuration part
        if (empty($setupInformation['configuration'])) {
            return 'notFound';
        }
        if (
            $setupInformation['configuration']['shy']
            || !$this->checkModAccess($name, $setupInformation['configuration'])
            || !$this->checkModWorkspace($name, $setupInformation['configuration'])
        ) {
            return false;
        }
        $finalModuleConfiguration = $setupInformation['configuration'];
        $finalModuleConfiguration['name'] = $name;
        // Language processing. This will add module labels and image reference to the internal ->moduleLabels array of the LANG object.
        $lang = $this->getLanguageService();
        if (is_object($lang)) {
            // $setupInformation['labels']['default']['tabs_images']['tab'] is for modules the reference
            // to the module icon.
            $defaultLabels = $setupInformation['labels']['default'];

            // Here the path is transformed to an absolute reference.
            if ($defaultLabels['tabs_images']['tab']) {
                // Initializing search for alternative icon:
                // Alternative icon key (might have an alternative set in $TBE_STYLES['skinImg']
                $altIconKey = 'MOD:' . $name . '/' . $defaultLabels['tabs_images']['tab'];
                $altIconAbsPath = is_array($GLOBALS['TBE_STYLES']['skinImg'][$altIconKey]) ? GeneralUtility::resolveBackPath(PATH_typo3 . $GLOBALS['TBE_STYLES']['skinImg'][$altIconKey][0]) : '';
                // Setting icon, either default or alternative:
                if ($altIconAbsPath && @is_file($altIconAbsPath)) {
                    $defaultLabels['tabs_images']['tab'] = $altIconAbsPath;
                } else {
                    if (\TYPO3\CMS\Core\Utility\StringUtility::beginsWith($defaultLabels['tabs_images']['tab'], 'EXT:')) {
                        list($extensionKey, $relativePath) = explode('/', substr($defaultLabels['tabs_images']['tab'], 4), 2);
                        $defaultLabels['tabs_images']['tab'] = ExtensionManagementUtility::extPath($extensionKey) . $relativePath;
                    } else {
                        $defaultLabels['tabs_images']['tab'] = $fullPath . '/' . $defaultLabels['tabs_images']['tab'];
                    }
                }

                $defaultLabels['tabs_images']['tab'] = $this->getRelativePath(PATH_typo3, $defaultLabels['tabs_images']['tab']);

                // Finally, setting the icon with correct path:
                if (substr($defaultLabels['tabs_images']['tab'], 0, 3) === '../') {
                    $defaultLabels['tabs_images']['tab'] = PATH_site . substr($defaultLabels['tabs_images']['tab'], 3);
                } else {
                    $defaultLabels['tabs_images']['tab'] = PATH_typo3 . $defaultLabels['tabs_images']['tab'];
                }
            }

            // If LOCAL_LANG references are used for labels of the module:
            if ($defaultLabels['ll_ref']) {
                // Now the 'default' key is loaded with the CURRENT language - not the english translation...
                $defaultLabels['labels']['tablabel'] = $lang->sL($defaultLabels['ll_ref'] . ':mlang_labels_tablabel');
                $defaultLabels['labels']['tabdescr'] = $lang->sL($defaultLabels['ll_ref'] . ':mlang_labels_tabdescr');
                $defaultLabels['tabs']['tab'] = $lang->sL($defaultLabels['ll_ref'] . ':mlang_tabs_tab');
                $lang->addModuleLabels($defaultLabels, $name . '_');
            } else {
                // ... otherwise use the old way:
                $lang->addModuleLabels($defaultLabels, $name . '_');
                $lang->addModuleLabels($setupInformation['labels'][$lang->lang], $name . '_');
            }
        }

        // Default script setup
        if ($setupInformation['configuration']['script'] === '_DISPATCH' || isset($setupInformation['configuration']['routeTarget'])) {
            if ($setupInformation['configuration']['extbase']) {
                $finalModuleConfiguration['script'] = BackendUtility::getModuleUrl('Tx_' . $name);
            } else {
                // just go through BackendModuleRequestHandler where the routeTarget is resolved
                $finalModuleConfiguration['script'] = BackendUtility::getModuleUrl($name);
            }
        } elseif ($setupInformation['configuration']['script'] && file_exists($setupInformation['path'] . '/' . $setupInformation['configuration']['script'])) {
            GeneralUtility::deprecationLog('Loading module "' . $name . '" as a standalone script. Script-based modules are deprecated since TYPO3 CMS 7. Support will be removed with TYPO3 CMS 8, use the "routeTarget" option or dispatched modules instead.');
            $finalModuleConfiguration['script'] = $this->getRelativePath(PATH_typo3, $fullPath . '/' . $setupInformation['configuration']['script']);
        } else {
            $finalModuleConfiguration['script'] = BackendUtility::getModuleUrl('dummy');
        }

        if (!empty($setupInformation['configuration']['navigationFrameModule'])) {
            $finalModuleConfiguration['navFrameScript'] = BackendUtility::getModuleUrl(
                $setupInformation['configuration']['navigationFrameModule'],
                !empty($setupInformation['configuration']['navigationFrameModuleParameters'])
                    ? $setupInformation['configuration']['navigationFrameModuleParameters']
                    : []
            );
        } elseif (!empty($setupInformation['configuration']['navFrameScript'])) {
            GeneralUtility::deprecationLog('Loading navFrameScript "' . $setupInformation['configuration']['navFrameScript'] . '" as a standalone script. Script-based navigation frames are deprecated since TYPO3 CMS 7. Support will be removed with TYPO3 CMS 8, use "navigationFrameModule" option or the "navigationComponentId" option instead.');
            // Navigation Frame Script (GET params could be added)
            $navFrameScript = explode('?', $setupInformation['configuration']['navFrameScript']);
            $navFrameScript = $navFrameScript[0];
            if (file_exists($setupInformation['path'] . '/' . $navFrameScript)) {
                $finalModuleConfiguration['navFrameScript'] = $this->getRelativePath(PATH_typo3, $fullPath . '/' . $setupInformation['configuration']['navFrameScript']);
            }
            // Additional params for Navigation Frame Script: "&anyParam=value&moreParam=1"
            if ($setupInformation['configuration']['navFrameScriptParam']) {
                $finalModuleConfiguration['navFrameScriptParam'] = $setupInformation['configuration']['navFrameScriptParam'];
            }
        }

        // Check if this is a submodule
        $mainModule = '';
        if (strpos($name, '_') !== false) {
            list($mainModule, ) = explode('_', $name, 2);
        }

        // check if there is a navigation component (like the pagetree)
        if (is_array($this->navigationComponents[$name])) {
            $finalModuleConfiguration['navigationComponentId'] = $this->navigationComponents[$name]['componentId'];
        // navigation component can be overriden by the main module component
        } elseif ($mainModule && is_array($this->navigationComponents[$mainModule]) && $setupInformation['configuration']['inheritNavigationComponentFromMainModule'] !== false) {
            $finalModuleConfiguration['navigationComponentId'] = $this->navigationComponents[$mainModule]['componentId'];
        }
        return $finalModuleConfiguration;
    }

    /**
     * fetches the conf.php file of a certain module, and also merges that with
     * some additional configuration
     *
     * @param \string $moduleName the combined name of the module, can be "web", "web_info", or "tools_log"
     * @param \string $pathToModuleDirectory the path where the module data is put, used for the conf.php or the modScript
     * @return array an array with subarrays, named "configuration" (aka $MCONF), "labels" (previously known as $MLANG) and the stripped path
     */
    protected function getModuleSetupInformation($moduleName, $pathToModuleDirectory)
    {

        // Because 'path/../path' does not work
        $path = preg_replace('/\\/[^\\/.]+\\/\\.\\.\\//', '/', $pathToModuleDirectory);

        $moduleSetupInformation = [
            'configuration' => [],
            'labels' => [],
            'path' => $path
        ];

        if (@is_dir($path) && file_exists($path . '/conf.php')) {
            $MCONF = [];
            $MLANG = [];

            // The conf-file is included. This must be valid PHP.
            include $path . '/conf.php';

            // Move the global variables defined in conf.php into the local method
            if (is_array($MCONF)) {
                $moduleSetupInformation['configuration'] = $MCONF;
            } else {
                $moduleSetupInformation['configuration'] = [];
            }
            $moduleSetupInformation['labels'] = $MLANG;
        }

        $moduleConfiguration = !empty($GLOBALS['TBE_MODULES']['_configuration'][$moduleName])
            ? $GLOBALS['TBE_MODULES']['_configuration'][$moduleName]
            : null;
        if ($moduleConfiguration !== null) {
            // Overlay setup with additional labels
            if (!empty($moduleConfiguration['labels']) && is_array($moduleConfiguration['labels'])) {
                if (empty($moduleSetupInformation['labels']['default']) || !is_array($moduleSetupInformation['labels']['default'])) {
                    $moduleSetupInformation['labels']['default'] = $moduleConfiguration['labels'];
                } else {
                    $moduleSetupInformation['labels']['default'] = array_replace_recursive($moduleSetupInformation['labels']['default'], $moduleConfiguration['labels']);
                }
                unset($moduleConfiguration['labels']);
            }
            // Overlay setup with additional configuration
            if (is_array($moduleConfiguration)) {
                $moduleSetupInformation['configuration'] = array_replace_recursive($moduleSetupInformation['configuration'], $moduleConfiguration);
            }
        }

        // Add some default configuration
        if (!isset($moduleSetupInformation['configuration']['inheritNavigationComponentFromMainModule'])) {
            $moduleSetupInformation['configuration']['inheritNavigationComponentFromMainModule'] = true;
        }

        return $moduleSetupInformation;
    }

    /**
     * Returns TRUE if the internal BE_USER has access to the module $name with $MCONF (based on security level set for that module)
     *
     * @param string $name Module name
     * @param array $MCONF MCONF array (module configuration array) from the modules conf.php file (contains settings about what access level the module has)
     * @return bool TRUE if access is granted for $this->BE_USER
     */
    public function checkModAccess($name, $MCONF)
    {
        if (empty($MCONF['access'])) {
            return true;
        }
        $access = strtolower($MCONF['access']);
        // Checking if admin-access is required
        // If admin-permissions is required then return TRUE if user is admin
        if (strpos($access, 'admin') !== false && $this->BE_USER->isAdmin()) {
            return true;
        }
        // This will add modules to the select-lists of user and groups
        if (strpos($access, 'user') !== false) {
            $this->modListUser[] = $name;
        }
        if (strpos($access, 'group') !== false) {
            $this->modListGroup[] = $name;
        }
        // This checks if a user is permitted to access the module
        if ($this->BE_USER->isAdmin() || $this->BE_USER->check('modules', $name)) {
            return true;
        }
        return false;
    }

    /**
     * Check if a module is allowed inside the current workspace for be user
     * Processing happens only if $this->observeWorkspaces is TRUE
     *
     * @param string $name Module name (unused)
     * @param array $MCONF MCONF array (module configuration array) from the modules conf.php file (contains settings about workspace restrictions)
     * @return bool TRUE if access is granted for $this->BE_USER
     */
    public function checkModWorkspace($name, $MCONF)
    {
        if (!$this->observeWorkspaces) {
            return true;
        }
        $status = true;
        if (!empty($MCONF['workspaces'])) {
            $status = $this->BE_USER->workspace === 0 && GeneralUtility::inList($MCONF['workspaces'], 'online')
                || $this->BE_USER->workspace === -1 && GeneralUtility::inList($MCONF['workspaces'], 'offline')
                || $this->BE_USER->workspace > 0 && GeneralUtility::inList($MCONF['workspaces'], 'custom');
        } elseif ($this->BE_USER->workspace === -99) {
            $status = false;
        }
        return $status;
    }

    /**
     * Parses the moduleArray ($TBE_MODULES) into an internally useful structure.
     * Returns an array where the keys are names of the module and the values may be TRUE (only module) or an array (of submodules)
     *
     * @param array $arr ModuleArray ($TBE_MODULES)
     * @return array Output structure with available modules
     */
    public function parseModulesArray($arr)
    {
        $theMods = [];
        if (is_array($arr)) {
            foreach ($arr as $mod => $subs) {
                // Clean module name to alphanum
                $mod = $this->cleanName($mod);
                if ($mod) {
                    if ($subs) {
                        $subsArr = GeneralUtility::trimExplode(',', $subs);
                        foreach ($subsArr as $subMod) {
                            $subMod = $this->cleanName($subMod);
                            if ($subMod) {
                                $theMods[$mod][] = $subMod;
                            }
                        }
                    } else {
                        $theMods[$mod] = 1;
                    }
                }
            }
        }
        return $theMods;
    }

    /**
     * The $str is cleaned so that it contains alphanumerical characters only.
     * Module names must only consist of these characters
     *
     * @param string $str String to clean up
     * @return string
     */
    public function cleanName($str)
    {
        return preg_replace('/[^a-z0-9]/i', '', $str);
    }

    /**
     * Get relative path for $destDir compared to $baseDir
     *
     * @param string $baseDir Base directory
     * @param string $destDir Destination directory
     * @return string The relative path of destination compared to base.
     */
    public function getRelativePath($baseDir, $destDir)
    {
        // A special case, the dirs are equal
        if ($baseDir === $destDir) {
            return './';
        }
        // Remove beginning
        $baseDir = ltrim($baseDir, '/');
        $destDir = ltrim($destDir, '/');
        $found = true;
        do {
            $slash_pos = strpos($destDir, '/');
            if ($slash_pos !== false && substr($destDir, 0, $slash_pos) == substr($baseDir, 0, $slash_pos)) {
                $baseDir = substr($baseDir, $slash_pos + 1);
                $destDir = substr($destDir, $slash_pos + 1);
            } else {
                $found = false;
            }
        } while ($found);
        $slashes = strlen($baseDir) - strlen(str_replace('/', '', $baseDir));
        for ($i = 0; $i < $slashes; $i++) {
            $destDir = '../' . $destDir;
        }
        return GeneralUtility::resolveBackPath($destDir);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
