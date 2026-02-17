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

namespace TYPO3\CMS\Workspaces\Sidebar;

use TYPO3\CMS\Backend\Attribute\AsSidebarComponent;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentContext;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentInterface;
use TYPO3\CMS\Backend\Sidebar\SidebarComponentResult;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Workspace selector sidebar component.
 *
 * Renders the workspace selector in the sidebar, allowing users
 * to switch between different workspaces.
 *
 * The web component fetches workspace data from the workspace_info
 * AJAX endpoint and handles switching via workspace_switch endpoint.
 *
 * @internal
 */
#[AsSidebarComponent(
    identifier: 'workspace-selector',
    before: ['module-menu'],
)]
final readonly class WorkspaceSelectorSidebarComponent implements SidebarComponentInterface
{
    public function __construct(
        private WorkspaceService $workspaceService,
    ) {}

    public function hasAccess(SidebarComponentContext $context): bool
    {
        return $this->workspaceService->hasAccessToWorkspaces()
            && $this->workspaceService->canSwitchWorkspaces();
    }

    public function getResult(SidebarComponentContext $context): SidebarComponentResult
    {
        return new SidebarComponentResult(
            identifier: 'workspace-selector',
            html: '<typo3-backend-workspace-selector></typo3-backend-workspace-selector>',
            module: '@typo3/workspaces/element/workspace-selector-element.js',
        );
    }
}
