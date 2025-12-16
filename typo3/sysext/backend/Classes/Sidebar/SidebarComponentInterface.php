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
 * Interface for backend sidebar components.
 *
 * Use the AsSidebarComponent attribute to register sidebar components
 * and configure their identifier and ordering.
 *
 * @internal
 */
interface SidebarComponentInterface
{
    /**
     * Check if the current user has access to this sidebar component.
     */
    public function hasAccess(SidebarComponentContext $context): bool;

    /**
     * Render the sidebar component HTML.
     */
    public function getResult(SidebarComponentContext $context): SidebarComponentResult;
}
