<?php
namespace TYPO3\CMS\Backend\Domain\Repository\Module;

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

use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for backend module menu
 * compiles all data from $GLOBALS[TBE_MODULES]
 */
class BackendModuleRepository implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Backend\Module\ModuleStorage
     */
    protected $moduleStorage;

    /**
     * Constructs the module menu and gets the Singleton instance of the menu
     */
    public function __construct()
    {
        $this->moduleStorage = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Module\ModuleStorage::class);

        $rawData = $this->getRawModuleMenuData();

        $this->convertRawModuleDataToModuleMenuObject($rawData);
        $this->createMenuEntriesForTbeModulesExt();
    }

    /**
     * loads all module information in the module storage
     *
     * @param array $excludeGroupNames
     * @return \SplObjectStorage
     */
    public function loadAllowedModules(array $excludeGroupNames = [])
    {
        if (empty($excludeGroupNames)) {
            return $this->moduleStorage->getEntries();
        }

        $modules = new \SplObjectStorage();
        foreach ($this->moduleStorage->getEntries() as $moduleGroup) {
            if (!in_array($moduleGroup->getName(), $excludeGroupNames, true)) {
                if ($moduleGroup->getChildren()->count() > 0) {
                    $modules->attach($moduleGroup);
                }
            }
        }

        return $modules;
    }

    /**
     * @param string $groupName
     * @return \SplObjectStorage|false
     **/
    public function findByGroupName($groupName = '')
    {
        foreach ($this->moduleStorage->getEntries() as $moduleGroup) {
            if ($moduleGroup->getName() === $groupName) {
                return $moduleGroup;
            }
        }

        return false;
    }

    /**
     * Finds a module menu entry by name
     *
     * @param string $name
     * @return \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule|bool
     */
    public function findByModuleName($name)
    {
        $entries = $this->moduleStorage->getEntries();
        $entry = $this->findByModuleNameInGivenEntries($name, $entries);
        return $entry;
    }

    /**
     * Finds a module menu entry by name in a given storage
     *
     * @param string $name
     * @param \SplObjectStorage $entries
     * @return \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule|bool
     */
    public function findByModuleNameInGivenEntries($name, \SplObjectStorage $entries)
    {
        foreach ($entries as $entry) {
            if ($entry->getName() === $name) {
                return $entry;
            }
            $children = $entry->getChildren();
            if (!empty($children)) {
                $childRecord = $this->findByModuleNameInGivenEntries($name, $children);
                if ($childRecord !== false) {
                    return $childRecord;
                }
            }
        }
        return false;
    }

    /**
     * Creates the module menu object structure from the raw data array
     *
     * @param array $rawModuleData
     */
    protected function convertRawModuleDataToModuleMenuObject(array $rawModuleData)
    {
        foreach ($rawModuleData as $module) {
            $entry = $this->createEntryFromRawData($module);
            if (isset($module['subitems']) && !empty($module['subitems'])) {
                foreach ($module['subitems'] as $subitem) {
                    $subEntry = $this->createEntryFromRawData($subitem);
                    $entry->addChild($subEntry);
                }
            }
            $this->moduleStorage->attachEntry($entry);
        }
    }

    /**
     * Creates a menu entry object from an array
     *
     * @param array $module
     * @return \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule
     */
    protected function createEntryFromRawData(array $module)
    {
        /** @var \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $entry */
        $entry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Domain\Model\Module\BackendModule::class);
        if (!empty($module['name']) && is_string($module['name'])) {
            $entry->setName($module['name']);
        }
        if (!empty($module['title']) && is_string($module['title'])) {
            $entry->setTitle($this->getLanguageService()->sL($module['title']));
        }
        if (!empty($module['onclick']) && is_string($module['onclick'])) {
            $entry->setOnClick($module['onclick']);
        }
        if (!empty($module['link']) && is_string($module['link'])) {
            $entry->setLink($module['link']);
        } elseif (empty($module['link']) && !empty($module['path']) && is_string($module['path'])) {
            $entry->setLink($module['path']);
        }
        if (!empty($module['description']) && is_string($module['description'])) {
            $entry->setDescription($this->getLanguageService()->sL($module['description']));
        }
        if (!empty($module['icon'])) {
            $entry->setIcon($module['icon']);
        }
        if (!empty($module['navigationComponentId']) && is_string($module['navigationComponentId'])) {
            $entry->setNavigationComponentId($module['navigationComponentId']);
        }
        if (!empty($module['navigationFrameScript']) && is_string($module['navigationFrameScript'])) {
            $entry->setNavigationFrameScript($module['navigationFrameScript']);
        } elseif (!empty($module['parentNavigationFrameScript']) && is_string($module['parentNavigationFrameScript'])) {
            $entry->setNavigationFrameScript($module['parentNavigationFrameScript']);
        }
        if (!empty($module['navigationFrameScriptParam']) && is_string($module['navigationFrameScriptParam'])) {
            $entry->setNavigationFrameScriptParameters($module['navigationFrameScriptParam']);
        }
        return $entry;
    }

    /**
     * Creates the "third level" menu entries (submodules for the info module for
     * example) from the TBE_MODULES_EXT array
     */
    protected function createMenuEntriesForTbeModulesExt()
    {
        foreach ($GLOBALS['TBE_MODULES_EXT'] ?? [] as $mainModule => $tbeModuleExt) {
            list($main) = explode('_', $mainModule);
            $mainEntry = $this->findByModuleName($main);
            if ($mainEntry === false) {
                continue;
            }

            $subEntries = $mainEntry->getChildren();
            if (empty($subEntries)) {
                continue;
            }
            $matchingSubEntry = $this->findByModuleName($mainModule);
            if ($matchingSubEntry !== false) {
                if (isset($tbeModuleExt['MOD_MENU']) && isset($tbeModuleExt['MOD_MENU']['function'])) {
                    foreach ($tbeModuleExt['MOD_MENU']['function'] as $subModule) {
                        $entry = $this->createEntryFromRawData($subModule);
                        $matchingSubEntry->addChild($entry);
                    }
                }
            }
        }
    }

    /**
     * loads the module menu from the moduleloader based on $GLOBALS['TBE_MODULES']
     * and compiles an array with all the data needed for menu etc.
     *
     * @return array
     */
    public function getRawModuleMenuData()
    {
        // Loads the backend modules available for the logged in user.
        /** @var ModuleLoader $moduleLoader */
        $moduleLoader = GeneralUtility::makeInstance(ModuleLoader::class);
        $moduleLoader->observeWorkspaces = true;
        $moduleLoader->load($GLOBALS['TBE_MODULES']);
        $loadedModules = $moduleLoader->modules;

        $modules = [];

        // Unset modules that are meant to be hidden from the menu.
        $loadedModules = $this->removeHiddenModules($loadedModules);
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $dummyScript = (string)$uriBuilder->buildUriFromRoute('dummy');
        foreach ($loadedModules as $moduleName => $moduleData) {
            $moduleLink = '';
            if (!is_array($moduleData['sub'])) {
                $moduleLink = $moduleData['script'];
            }
            $moduleLink = GeneralUtility::resolveBackPath($moduleLink);
            $moduleLabels = $moduleLoader->getLabelsForModule($moduleName);
            $moduleKey = 'modmenu_' . $moduleName;
            $modules[$moduleKey] = [
                'name' => $moduleName,
                'title' => $moduleLabels['title'],
                'onclick' => 'top.goToModule(' . GeneralUtility::quoteJSvalue($moduleName) . ');',
                'icon' => $this->getModuleIcon($moduleKey, $moduleData),
                'link' => $moduleLink,
                'description' => $moduleLabels['shortdescription']
            ];
            if (!is_array($moduleData['sub']) && $moduleData['script'] !== $dummyScript) {
                // Work around for modules with own main entry, but being self the only submodule
                $modules[$moduleKey]['subitems'][$moduleKey] = [
                    'name' => $moduleName,
                    'title' => $moduleLabels['title'],
                    'onclick' => 'top.goToModule(' . GeneralUtility::quoteJSvalue($moduleName) . ');',
                    'icon' => $this->getModuleIcon($moduleKey, $moduleData),
                    'link' => $moduleLink,
                    'originalLink' => $moduleLink,
                    'description' => $moduleLabels['shortdescription'],
                    'navigationFrameScript' => null,
                    'navigationFrameScriptParam' => null,
                    'navigationComponentId' => null
                ];
            } elseif (is_array($moduleData['sub'])) {
                foreach ($moduleData['sub'] as $submoduleName => $submoduleData) {
                    if (isset($submoduleData['script'])) {
                        $submoduleLink = GeneralUtility::resolveBackPath($submoduleData['script']);
                    } else {
                        $submoduleLink = (string)$uriBuilder->buildUriFromRoute($submoduleData['name']);
                    }
                    $submoduleKey = $moduleName . '_' . $submoduleName;
                    $submoduleLabels = $moduleLoader->getLabelsForModule($submoduleKey);
                    $submoduleDescription = $submoduleLabels['shortdescription'];
                    $originalLink = $submoduleLink;
                    $navigationFrameScript = $submoduleData['navFrameScript'];
                    $modules[$moduleKey]['subitems'][$submoduleKey] = [
                        'name' => $moduleName . '_' . $submoduleName,
                        'title' => $submoduleLabels['title'],
                        'onclick' => 'top.goToModule(' . GeneralUtility::quoteJSvalue($moduleName . '_' . $submoduleName) . ');',
                        'icon' => $this->getModuleIcon($moduleKey, $submoduleData),
                        'link' => $submoduleLink,
                        'originalLink' => $originalLink,
                        'description' => $submoduleDescription,
                        'navigationFrameScript' => $navigationFrameScript,
                        'navigationFrameScriptParam' => $submoduleData['navFrameScriptParam'],
                        'navigationComponentId' => $submoduleData['navigationComponentId']
                    ];
                    // if the main module has a navframe script, inherit to the submodule,
                    // but only if it is not disabled explicitly (option is set to FALSE)
                    if ($moduleData['navFrameScript'] && $submoduleData['inheritNavigationComponentFromMainModule'] !== false) {
                        $modules[$moduleKey]['subitems'][$submoduleKey]['parentNavigationFrameScript'] = $moduleData['navFrameScript'];
                    }
                }
            }
        }
        return $modules;
    }

    /**
     * Reads User configuration from options.hideModules and removes
     * modules accordingly.
     *
     * @param array $loadedModules
     * @return array
     */
    protected function removeHiddenModules($loadedModules)
    {
        $userTsConfig = $this->getBackendUser()->getTSConfig();

        // Hide modules if set in userTS.
        $hiddenMainModules = GeneralUtility::trimExplode(',', $userTsConfig['options.']['hideModules'] ?? '', true);
        foreach ($hiddenMainModules as $hiddenMainModule) {
            unset($loadedModules[$hiddenMainModule]);
        }

        // Hide sub-modules if set in userTS.
        $hiddenModules = $userTsConfig['options.']['hideModules.'] ?? [];
        if (is_array($hiddenModules)) {
            foreach ($hiddenModules as $mainModuleName => $subModules) {
                $hiddenSubModules = GeneralUtility::trimExplode(',', $subModules, true);
                foreach ($hiddenSubModules as $hiddenSubModule) {
                    unset($loadedModules[$mainModuleName]['sub'][$hiddenSubModule]);
                }
            }
        }

        return $loadedModules;
    }

    /**
     * gets the module icon
     *
     * @param string $moduleKey Module key
     * @param array $moduleData the compiled data associated with it
     * @return string Icon data, either sprite or <img> tag
     */
    protected function getModuleIcon($moduleKey, $moduleData)
    {
        $iconIdentifier = !empty($moduleData['iconIdentifier'])
            ? $moduleData['iconIdentifier']
            : 'module-icon-' . $moduleKey;
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        if ($iconRegistry->isRegistered($iconIdentifier)) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            return $iconFactory->getIcon($iconIdentifier)->render();
        }
        return '';
    }

    /**
     * Return language service instance
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
