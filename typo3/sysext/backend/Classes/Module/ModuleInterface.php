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

/**
 * An interface representing a TYPO3 Backend module.
 */
interface ModuleInterface
{
    /**
     * The internal name of the module, used for referencing in permissions etc
     */
    public function getIdentifier(): string;

    /**
     * Return the main route path
     */
    public function getPath(): string;

    /**
     * The icon identifier for the module
     */
    public function getIconIdentifier(): string;

    /**
     * The title of the module, used in the menu
     */
    public function getTitle(): string;

    /**
     * A longer description, common for the "About" section with a long explanation
     */
    public function getDescription(): string;

    /**
     * A shorter description, used when hovering over a module in the menu as title attribute
     */
    public function getShortDescription(): string;

    /**
     * Useful for main modules that are also "clickable" such as the dashboard module
     */
    public function isStandalone(): bool;

    /**
     * Returns the view component responsible for rendering the module (iFrame or name of the web component)
     */
    public function getComponent(): string;

    /**
     * The web component to be rendering the navigation area
     */
    public function getNavigationComponent(): string;

    /**
     * The position of the module, such as [top] or [bottom] or [after => anotherModule] or [before => anotherModule]
     */
    public function getPosition(): array;

    /**
     * Returns a modules appearance options, e.g. used for module menu
     */
    public function getAppearance(): array;

    /**
     * Can be user (editor permissions), admin, or systemMaintainer
     */
    public function getAccess(): string;

    /**
     * Can be "*" (= empty) or "live" or "offline"
     */
    public function getWorkspaceAccess(): string;

    /**
     * The identifier of the parent module during registration
     */
    public function getParentIdentifier(): string;

    /**
     * Set a reference to the next upper menu item
     *
     * @internal Might vanish soon
     */
    public function setParentModule(ModuleInterface $module): void;

    /**
     * Get the reference to the next upper menu item
     */
    public function getParentModule(): ?ModuleInterface;

    /**
     * Can be checked if the module is a "main module"
     */
    public function hasParentModule(): bool;

    /**
     * Used to set another module as part of the parent module
     *
     * @internal Might vanish soon
     */
    public function addSubModule(ModuleInterface $module): void;

    /**
     * Remove a submodule
     *
     * @internal Might vanish soon
     */
    public function removeSubModule(string $identifier): void;

    /**
     * Checks whether this module has a submodule with the given identifier
     */
    public function hasSubModule(string $identifier): bool;

    /**
     * Checks if this module has further submodules
     */
    public function hasSubModules(): bool;

    /**
     * Return a submodule given by its full identifier
     */
    public function getSubModule(string $identifier): ?ModuleInterface;

    /**
     * Return all direct descendants of this module
     * @return ModuleInterface[]
     */
    public function getSubModules(): array;

    /**
     * Returns module related route options - used for the router
     */
    public function getDefaultRouteOptions(): array;

    /**
     * Get allowed and available module data properties and their default values.
     */
    public function getDefaultModuleData(): array;

    /**
     * Return a list of identifiers that are aliases to this module
     */
    public function getAliases(): array;
}
