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
 * A representation for a module used for the menu generation.
 *
 * @internal only to be used by TYPO3 Core.
 */
class MenuModule implements ModuleInterface
{
    /**
     * @var ModuleInterface[]
     */
    protected array $subModules = [];

    public function __construct(protected readonly ModuleInterface $module, protected $isCollapsed = false) {}

    public function getIdentifier(): string
    {
        return $this->module->getIdentifier();
    }

    public function getIconIdentifier(): string
    {
        return $this->module->getIconIdentifier();
    }

    public function getTitle(): string
    {
        return $this->module->getTitle();
    }

    public function getDescription(): string
    {
        return $this->module->getDescription();
    }

    public function getShortDescription(): string
    {
        return $this->module->getShortDescription();
    }

    public function isStandalone(): bool
    {
        return $this->module->isStandalone();
    }

    public function getComponent(): string
    {
        return $this->module->getComponent();
    }

    public function getNavigationComponent(): string
    {
        return $this->module->getNavigationComponent();
    }

    public function getPosition(): array
    {
        return $this->module->getPosition();
    }

    public function getAppearance(): array
    {
        return $this->module->getAppearance();
    }

    public function getAccess(): string
    {
        return $this->module->getAccess();
    }

    public function getWorkspaceAccess(): string
    {
        return $this->module->getWorkspaceAccess();
    }

    public function getParentIdentifier(): string
    {
        return $this->module->getParentIdentifier();
    }

    public function setParentModule(ModuleInterface $module): void
    {
        $this->module->setParentModule($module);
    }

    public function getParentModule(): ?ModuleInterface
    {
        return $this->module->getParentModule();
    }

    public function hasParentModule(): bool
    {
        return $this->module->hasParentModule();
    }

    public function addSubModule(ModuleInterface $module): void
    {
        $this->subModules[$module->getIdentifier()] = $module;
    }

    public function removeSubModule(string $identifier): void
    {
        unset($this->subModules[$identifier]);
    }

    public function hasSubModule(string $identifier): bool
    {
        return isset($this->subModules[$identifier]);
    }

    public function hasSubModules(): bool
    {
        return $this->subModules !== [];
    }

    public function getSubModule(string $identifier): ?ModuleInterface
    {
        return $this->subModules[$identifier] ?? null;
    }

    /**
     * @return ModuleInterface[]
     */
    public function getSubModules(): array
    {
        return $this->subModules;
    }

    public function getPath(): string
    {
        return $this->module->getPath();
    }

    public function getDefaultRouteOptions(): array
    {
        return $this->module->getDefaultRouteOptions();
    }

    public function getDefaultModuleData(): array
    {
        return $this->module->getDefaultModuleData();
    }

    public function getAliases(): array
    {
        return $this->module->getAliases();
    }

    public function isCollapsed(): bool
    {
        return $this->isCollapsed;
    }

    public function getIsCollapsed(): bool
    {
        return $this->isCollapsed();
    }

    public function getShouldBeLinked(): bool
    {
        if ($this->module->isStandalone()) {
            return true;
        }
        if ($this->module->hasParentModule()) {
            return true;
        }
        return false;
    }
}
