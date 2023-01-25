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

namespace TYPO3\CMS\Backend\Module;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is the central point to retrieve modules from the ModuleRegistry, while
 * performing the necessary access checks, which ModuleRegistry does not deal with.
 */
class ModuleProvider
{
    public function __construct(protected readonly ModuleRegistry $moduleRegistry)
    {
    }

    /**
     * Simple wrapper for the registry, which just checks if a
     * module is registered. Does NOT perform any access checks.
     */
    public function isModuleRegistered(string $identifier): bool
    {
        return $this->moduleRegistry->hasModule($identifier);
    }

    /**
     * Returns a Module for the given identifier. In case a user is given, also access checks are performed.
     */
    public function getModule(
        string $identifier,
        ?BackendUserAuthentication $user = null,
        bool $respectWorkspaceRestrictions = true
    ): ?ModuleInterface {
        if (($user === null && $this->moduleRegistry->hasModule($identifier))
            || $this->accessGranted($identifier, $user, $respectWorkspaceRestrictions)
        ) {
            $module = $this->moduleRegistry->getModule($identifier);
            if ($module->hasSubModules()) {
                foreach ($module->getSubModules() as $subModuleIdentifier => $subModule) {
                    if ($user !== null && !$this->accessGranted($subModuleIdentifier, $user, $respectWorkspaceRestrictions)) {
                        $module->removeSubModule($subModuleIdentifier);
                    }
                }
            }
            return $module;
        }

        return null;
    }

    /**
     * Returns all modules either grouped by main modules or flat.
     * In case a user is given, also access checks are performed.
     *
     * @return ModuleInterface[]
     */
    public function getModules(
        ?BackendUserAuthentication $user = null,
        bool $respectWorkspaceRestrictions = true,
        bool $grouped = true
    ): array {
        if (!$grouped) {
            return array_filter(
                $this->moduleRegistry->getModules(),
                fn ($module) => $user === null || $this->accessGranted($module->getIdentifier(), $user, $respectWorkspaceRestrictions)
            );
        }

        $availableModules = array_filter($this->moduleRegistry->getModules(), static fn ($module) => !$module->hasParentModule());

        foreach ($availableModules as $identifier => $module) {
            if ($user !== null && !$this->accessGranted($identifier, $user, $respectWorkspaceRestrictions)) {
                unset($availableModules[$identifier]);
                continue;
            }
            foreach ($module->getSubModules() as $subModuleIdentifier => $subModule) {
                if ($user !== null && !$this->accessGranted($subModuleIdentifier, $user, $respectWorkspaceRestrictions)) {
                    $module->removeSubModule($subModuleIdentifier);
                }
            }
        }

        return $availableModules;
    }

    /**
     * Return the requested (main) module if exist and allowed, prepared
     * for menu generation or similar structured output (nested). Takes
     * TSConfig into account. Does not respect "appearance[renderInModuleMenu]".
     */
    public function getModuleForMenu(
        string $identifier,
        BackendUserAuthentication $user,
        bool $respectWorkspaceRestrictions = true
    ): ?MenuModule {
        $module = $this->getModule($identifier, $user, $respectWorkspaceRestrictions);
        if ($module === null) {
            return null;
        }
        // Before preparing the module for the menu, check if it is defined ad hidden in TSconfig
        $hideModules = GeneralUtility::trimExplode(',', $user->getTSConfig()['options.']['hideModules'] ?? '', true);
        if (in_array($identifier, $hideModules, true)) {
            return null;
        }
        $menuItem = new MenuModule(clone $module);
        if ($menuItem->isStandalone()) {
            return $menuItem;
        }
        foreach ($module->getSubModules() as $subModuleIdentifier => $subModule) {
            if (in_array($subModuleIdentifier, $hideModules, true)) {
                continue;
            }
            $subMenuItem = new MenuModule(clone $subModule);
            $menuItem->addSubModule($subMenuItem);
        }
        if (!$menuItem->hasSubModules()) {
            // In case the main module does not have any submodules, unset it again
            return null;
        }
        return $menuItem;
    }

    /**
     * Returns all allowed modules for the current user, prepared
     * for module menu generation or similar structured output (nested).
     * Takes TSConfig and "appearance[renderInModuleMenu]" into account.
     *
     * @return MenuModule[]
     */
    public function getModulesForModuleMenu(
        BackendUserAuthentication $user,
        bool $respectWorkspaceRestrictions = true
    ): array {
        $moduleMenuItems = [];
        $moduleMenuState = json_decode($user->uc['modulemenu'] ?? '{}', true);

        // Before preparing the modules for the menu, check if we need to hide some of them (defined in TSconfig)
        $hideModules = GeneralUtility::trimExplode(',', $user->getTSConfig()['options.']['hideModules'] ?? '', true);
        foreach ($this->getModules($user, $respectWorkspaceRestrictions) as $identifier => $module) {
            if (in_array($identifier, $hideModules, true)
                || !($module->getAppearance()['renderInModuleMenu'] ?? true)
            ) {
                continue;
            }
            // Only use main modules for this first level
            if ($module->hasParentModule()) {
                continue;
            }
            $menuItem = new MenuModule(clone $module, isset($moduleMenuState[$identifier]));
            $moduleMenuItems[$identifier] = $menuItem;
            if ($menuItem->isStandalone()) {
                continue;
            }
            foreach ($module->getSubModules() as $subModuleIdentifier => $subModule) {
                if (in_array($subModuleIdentifier, $hideModules, true)
                    || !($subModule->getAppearance()['renderInModuleMenu'] ?? true)
                ) {
                    continue;
                }
                $subMenuItem = new MenuModule(clone $subModule);
                $menuItem->addSubModule($subMenuItem);
            }
            if (!$menuItem->hasSubModules()) {
                // In case the main module does not have any submodules, unset it again
                unset($moduleMenuItems[$identifier]);
            }
        }
        return $moduleMenuItems;
    }

    /**
     * Check access of a module for a given user
     */
    public function accessGranted(
        string $identifier,
        BackendUserAuthentication $user,
        bool $respectWorkspaceRestrictions = true
    ): bool {
        if (!$this->moduleRegistry->hasModule($identifier)) {
            return false;
        }

        $module = $this->moduleRegistry->getModule($identifier);

        if ($respectWorkspaceRestrictions && ExtensionManagementUtility::isLoaded('workspaces')) {
            $workspaceAccess = $module->getWorkspaceAccess();
            if ($workspaceAccess !== '' && $workspaceAccess !== '*') {
                if (($workspaceAccess === 'live' && $user->workspace !== 0)
                    || ($workspaceAccess === 'offline' && $user->workspace === 0)
                ) {
                    return false;
                }
            } elseif ($user->workspace === -99) {
                return false;
            }
        }

        $moduleAccess = $module->getAccess();
        if ($moduleAccess === '') {
            // Early return since this module does not have any access permissions set
            return true;
        }

        // Check if this module is only allowed by system maintainers (= admins who are in the list of system maintainers)
        if ($moduleAccess === BackendUserAuthentication::ROLE_SYSTEMMAINTAINER) {
            return $user->isSystemMaintainer();
        }

        // Check if this module is only allowed by admins
        if ($moduleAccess === 'admin') {
            return $user->isAdmin();
        }

        // This checks if a user is permitted to access the module, being
        // either admin or having necessary module access permissions set.
        if ($user->isAdmin() || $user->check('modules', $identifier)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the first module, accessible for the given user.
     * This will only return submodules or standalone modules.
     *
     * @internal not part of TYPO3's API. Only for use in TYPO3 Core.
     */
    public function getFirstAccessibleModule(BackendUserAuthentication $user): ?ModuleInterface
    {
        $modules = array_filter($this->moduleRegistry->getModules(), function ($module) use ($user) {
            return $this->accessGranted($module->getIdentifier(), $user)
                && ($module->isStandalone() || $module->hasParentModule());
        });

        return reset($modules) ?: null;
    }

    /**
     * Get all modules with access=user, to be selected in the user/group records
     *
     * @return ModuleInterface[]
     * @internal not part of TYPO3's API. Only for use in TYPO3 Core.
     */
    public function getUserModules(): array
    {
        return array_filter($this->moduleRegistry->getModules(), static fn ($module) => $module->getAccess() === 'user');
    }
}
