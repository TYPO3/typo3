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
 * Renders the complete backend sidebar with all registered components.
 *
 * @internal
 */
final readonly class Sidebar
{
    /**
     * @param array<string, SidebarComponentInterface> $components Components keyed by identifier
     */
    public function __construct(
        private array $components,
        private SidebarComponentContext $context,
    ) {}

    /**
     * @return array{
     *   html: string,
     *   modules: list<string>,
     *   accessible: bool,
     *   expanded: bool
     * }
     */
    public function render(): array
    {
        $html = [];
        $modules = [];
        foreach ($this->components as $identifier => $component) {
            $result = $component->getResult($this->context);
            $html[] = '<div class="sidebar-component" data-identifier="' . htmlspecialchars($identifier) . '">' . $result->html . '</div>';
            if ($result->module) {
                $modules[] = $result->module;
            }
        }

        return [
            'html' => $html === [] ? '' : '<div class="sidebar-container">' . implode(LF, $html) . '</div>',
            'modules' => $modules,
            'accessible' => $this->isAccessible(),
            'expanded' => $this->isExpanded(),
        ];
    }

    public function isAccessible(): bool
    {
        return $this->components !== [];
    }

    public function isExpanded(): bool
    {
        $collapseState = $this->context->user->uc['BackendComponents']['States']['typo3-sidebar']['collapsed'] ?? false;
        return $collapseState !== true && $collapseState !== 'true';
    }

    public function getComponentByIdentifier(string $identifier): ?SidebarComponentInterface
    {
        return $this->components[$identifier] ?? null;
    }
}
