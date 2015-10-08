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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class with utility functions for module menu
 */
class ModuleController
{
    /**
     * @var \TYPO3\CMS\Backend\Module\ModuleStorage
     */
    protected $moduleMenu;

    /**
     * @var \TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository
     */
    protected $moduleMenuRepository;

    /**
     * Constructor
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, not in use, as everything can be done via the ModuleMenuRepository directly
     */
    public function __construct()
    {
        GeneralUtility::logDeprecatedFunction();
        $this->moduleMenu = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Module\ModuleStorage::class);
        $this->moduleMenuRepository = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository::class);
    }

    /**
     * This method creates the module menu if necessary
     * afterwards you only need an instance of \TYPO3\CMS\Backend\Module\ModuleStorage
     * to get the menu
     *
     * @return void
     */
    public function createModuleMenu()
    {
        if (empty($this->moduleMenu->getEntries())) {
            /** @var $moduleMenu \TYPO3\CMS\Backend\View\ModuleMenuView */
            $moduleMenu = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\View\ModuleMenuView::class);
            $rawData = $moduleMenu->getRawModuleData();
            $this->convertRawModuleDataToModuleMenuObject($rawData);
            $this->createMenuEntriesForTbeModulesExt();
        }
    }

    /**
     * Creates the module menu object structure from the raw data array
     *
     * @param array $rawModuleData
     * @see class.modulemenu.php getRawModuleData()
     * @return void
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
            $this->moduleMenu->attachEntry($entry);
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
        /** @var $entry \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule */
        $entry = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Domain\Model\Module\BackendModule::class);
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
        }
        if (empty($module['link']) && !empty($module['path']) && is_string($module['path'])) {
            $entry->setLink($module['path']);
        }
        if (!empty($module['description']) && is_string($module['description'])) {
            $entry->setDescription($module['description']);
        }
        if (!empty($module['icon']) && is_array($module['icon'])) {
            $entry->setIcon($module['icon']);
        }
        if (!empty($module['navigationComponentId']) && is_string($module['navigationComponentId'])) {
            $entry->setNavigationComponentId($module['navigationComponentId']);
        }
        return $entry;
    }

    /**
     * Creates the "third level" menu entries (submodules for the info module for
     * example) from the TBE_MODULES_EXT array
     *
     * @return void
     */
    protected function createMenuEntriesForTbeModulesExt()
    {
        foreach ($GLOBALS['TBE_MODULES_EXT'] as $mainModule => $tbeModuleExt) {
            list($main) = explode('_', $mainModule);
            $mainEntry = $this->moduleMenuRepository->findByModuleName($main);
            if ($mainEntry !== false) {
                $subEntries = $mainEntry->getChildren();
                if (!empty($subEntries)) {
                    $matchingSubEntry = $this->moduleMenuRepository->findByModuleName($mainModule);
                    if ($matchingSubEntry !== false) {
                        if (array_key_exists('MOD_MENU', $tbeModuleExt) && array_key_exists('function', $tbeModuleExt['MOD_MENU'])) {
                            foreach ($tbeModuleExt['MOD_MENU']['function'] as $subModule) {
                                $entry = $this->createEntryFromRawData($subModule);
                                $matchingSubEntry->addChild($entry);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Return language service instance
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
