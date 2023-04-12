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
 * The basic backend module, to be extended by more detailed implementations (e.g. ExtbaseModule)
 *
 * @internal
 */
abstract class BaseModule
{
    protected string $identifier;
    protected string $packageName = '';
    protected string $absolutePackagePath = '';
    protected string $path = '';
    protected string $iconIdentifier = '';
    protected string $title = '';
    protected string $description = '';
    protected string $shortDescription = '';
    protected array $position = [];
    protected array $appearance = [];
    protected string $access = '';
    protected string $workspaceAccess = '*';
    protected string $parent = '';
    protected ?ModuleInterface $parentModule = null;
    /** @var array<string, ModuleInterface> */
    protected array $subModules = [];
    protected bool $standalone = false;
    protected string $component = '@typo3/backend/module/iframe';
    protected string $navigationComponent = '';
    protected array $defaultModuleData = [];
    protected array $aliases = [];
    protected bool $inheritNavigationComponent = true;
    protected array $routeOptions = [];

    final protected function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function isStandalone(): bool
    {
        return $this->standalone;
    }

    public function getNavigationComponent(): string
    {
        if ($this->inheritNavigationComponent && $this->hasParentModule()) {
            // Use parent navigation component if inheritance is enabled.
            // Fallback if parent does not define a navigation component.
            return $this->getParentModule()->getNavigationComponent() ?: $this->navigationComponent;
        }
        return $this->navigationComponent;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function getPosition(): array
    {
        return $this->position;
    }

    public function getAccess(): string
    {
        return $this->access;
    }

    public function getWorkspaceAccess(): string
    {
        return $this->workspaceAccess;
    }

    public function getParentIdentifier(): string
    {
        return $this->parent;
    }

    public function setParentModule(ModuleInterface $module): void
    {
        $this->parentModule = $module;
    }

    public function getParentModule(): ?ModuleInterface
    {
        return $this->parentModule;
    }

    public function hasParentModule(): bool
    {
        return $this->parentModule !== null;
    }

    public function addSubModule(ModuleInterface $module): void
    {
        $this->subModules[$module->getIdentifier()] = $module;
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

    public function removeSubModule(string $identifier): void
    {
        unset($this->subModules[$identifier]);
    }

    /**
     * @return array<string, ModuleInterface>
     */
    public function getSubModules(): array
    {
        return $this->subModules;
    }

    public function getAppearance(): array
    {
        return $this->appearance;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    abstract public function getDefaultRouteOptions(): array;

    public function getDefaultModuleData(): array
    {
        return $this->defaultModuleData;
    }

    public static function createFromConfiguration(string $identifier, array $configuration): static
    {
        $obj = new static($identifier);
        $obj->packageName = (string)($configuration['packageName'] ?? '');
        $obj->absolutePackagePath = (string)($configuration['absolutePackagePath'] ?? '');
        $obj->path = '/' . ltrim((string)$configuration['path'], '/');
        $obj->standalone = (bool)($configuration['standalone'] ?? false);

        if ($configuration['parent'] ?? false) {
            $obj->parent = (string)$configuration['parent'];
        }
        if ($configuration['iconIdentifier'] ?? false) {
            $obj->iconIdentifier = (string)$configuration['iconIdentifier'];
        }
        if ($configuration['access'] ?? false) {
            $obj->access = (string)$configuration['access'];
        }
        if ($configuration['workspaces'] ?? false) {
            $obj->workspaceAccess = (string)$configuration['workspaces'];
        }
        if ($configuration['component'] ?? false) {
            $obj->component = (string)$configuration['component'];
        }

        if (is_array($configuration['labels'] ?? null)) {
            $obj->title = (string)($configuration['labels']['title'] ?? '');
            $obj->description = (string)($configuration['labels']['description'] ?? '');
            $obj->shortDescription = (string)($configuration['labels']['shortDescription'] ?? '');
        } elseif (str_starts_with((string)($configuration['labels'] ?? ''), 'LLL:')) {
            $labelsFile = $configuration['labels'];
            $obj->title = $labelsFile . ':mlang_tabs_tab';
            $obj->description = $labelsFile . ':mlang_labels_tabdescr';
            $obj->shortDescription = $labelsFile . ':mlang_labels_tablabel';
        }

        if (is_array($configuration['position'] ?? false)) {
            if (in_array('top', $configuration['position'], true)) {
                $configuration['position'] = [
                    'before' => '*',
                ];
            }
            if (in_array('bottom', $configuration['position'], true)) {
                $configuration['position'] = [
                    'after' => '*',
                ];
            }
            $obj->position = $configuration['position'];
        }
        if (is_array($configuration['appearance'] ?? false)) {
            $obj->appearance = $configuration['appearance'];
        }
        if (is_array($configuration['moduleData'] ?? false)) {
            $obj->defaultModuleData = $configuration['moduleData'];
        }
        if (is_array($configuration['aliases'] ?? false)) {
            $obj->aliases = $configuration['aliases'];
        }

        if (isset($configuration['inheritNavigationComponentFromMainModule'])) {
            $obj->inheritNavigationComponent = (bool)$configuration['inheritNavigationComponentFromMainModule'];
        }

        if (isset($configuration['navigationComponent'])) {
            $obj->navigationComponent = (string)$configuration['navigationComponent'];
        } elseif (isset($configuration['navigationComponentId'])) {
            $obj->navigationComponent = (string)$configuration['navigationComponentId'];
        }
        if (is_array($configuration['routeOptions'] ?? null)) {
            $obj->routeOptions = $configuration['routeOptions'];
        }

        return $obj;
    }
}
