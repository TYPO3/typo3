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

namespace TYPO3\CMS\Backend\Sidebar;

/**
 * Registry for sidebar components.
 *
 * Components are ordered by before/after dependencies during container compilation.
 *
 * @internal
 */
class SidebarComponentsRegistry
{
    /**
     * All registered components in their ordered positions.
     *
     * @var array<string, SidebarComponentInterface>
     */
    private array $components = [];

    /**
     * @param array<string, SidebarComponentInterface> $sidebarComponents Pre-ordered components (injected by SidebarComponentsPass)
     */
    public function __construct(array $sidebarComponents = [])
    {
        foreach ($sidebarComponents as $identifier => $component) {
            $this->components[$identifier] = $component;
        }
    }

    /**
     * Get all sidebar components.
     *
     * @return array<string, SidebarComponentInterface>
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function hasComponent(string $identifier): bool
    {
        return isset($this->components[$identifier]);
    }

    /**
     * @throws \InvalidArgumentException if component does not exist
     */
    public function getComponent(string $identifier): SidebarComponentInterface
    {
        if (!$this->hasComponent($identifier)) {
            throw new \InvalidArgumentException(
                sprintf('Sidebar component with identifier "%s" is not registered', $identifier),
                1765923035
            );
        }

        return $this->components[$identifier];
    }
}
