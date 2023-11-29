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

namespace TYPO3\CMS\Workspaces\Authorization;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Checks if a user is allowed to publish a record in a workspace
 *
 * @internal Not part of TYPO3 Core API.
 */
class WorkspacePublishGate
{
    /**
     * Returns TRUE if the user has access to publish content from the workspace ID given.
     * Admin-users are always granted access to do this.
     * If the workspace ID is 0 (live) all users have access also
     * For custom workspaces it depends on whether the user is owner OR like with
     * draft workspace if the user has access to Live workspace.
     */
    public function isGranted(BackendUserAuthentication $user, ...$conditions): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        [$workspaceId] = $conditions;
        $wsAccess = $user->checkWorkspace($workspaceId);
        // If no access to workspace, of course you cannot publish!
        if ($wsAccess === false) {
            return false;
        }
        if ((int)$wsAccess['uid'] === 0) {
            // If access to Live workspace, no problem.
            return true;
        }
        // Custom workspaces
        // 1. Owners can always publish
        if ($wsAccess['_ACCESS'] === 'owner') {
            return true;
        }
        // 2. User has access to online workspace which is OK as well as long as publishing
        // access is not limited by workspace option.
        return $user->checkWorkspace(WorkspaceService::LIVE_WORKSPACE_ID) && !($wsAccess['publish_access'] & WorkspaceService::PUBLISH_ACCESS_ONLY_WORKSPACE_OWNERS);
    }
}
