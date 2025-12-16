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
 * Factory to create request-scoped Sidebar instances.
 *
 * @internal
 */
final readonly class SidebarFactory
{
    public function __construct(
        private SidebarComponentsRegistry $registry,
    ) {}

    public function create(SidebarComponentContext $context): Sidebar
    {
        $components = [];
        foreach ($this->registry->getComponents() as $identifier => $component) {
            if ($component->hasAccess($context)) {
                $components[$identifier] = $component;
            }
        }
        return new Sidebar($components, $context);
    }
}
