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

namespace TYPO3\CMS\Backend\User;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Manages language selection preferences across modules.
 * Provides fallback chain for language resolution.
 *
 * Language preferences are stored in be_users.uc['pageLanguages'] as:
 * [pageId => languageIds[]]
 *
 * Page-specific preferences are shared across all modules working with that page.
 *
 * @internal
 */
final class SharedUserPreferences
{
    /**
     * Resolve selected languages with fallback chain.
     *
     * Priority order:
     * 1. Explicit request parameter (not stored, just for current request)
     * 2. Page-specific stored preference (shared across modules)
     * 3. ModuleData from request (for backward compat)
     * 4. Default [0]
     *
     * @param array|null $requestLanguages Languages from request parameter
     * @param array|null $moduleDataLanguages Languages from ModuleData (for backward compat)
     * @return int[] Resolved language IDs
     */
    public function resolveLanguages(
        BackendUserAuthentication $backendUser,
        ?array $requestLanguages,
        int $pageId,
        ?array $moduleDataLanguages = null
    ): array {
        // 1. Explicit request parameter (highest priority)
        if (!empty($requestLanguages)) {
            return array_unique(array_map(intval(...), $requestLanguages));
        }

        // 2. Page-specific preference (shared across modules)
        $pageSpecific = $backendUser->uc['pageLanguages'][$pageId] ?? null;
        if (is_array($pageSpecific)) {
            return $pageSpecific;
        }

        // 3. ModuleData from request (for backward compat)
        if (!empty($moduleDataLanguages)) {
            return array_unique(array_map(intval(...), $moduleDataLanguages));
        }

        // 4. Default
        return [0];
    }

    /**
     * Store language selection for a page (shared across modules).
     *
     * This preference will be used by ALL modules working with this page,
     * ensuring consistent language selection across Page Module, Records module, etc.
     */
    public function setPageLanguages(BackendUserAuthentication $backendUser, int $pageId, array $languageIds): void
    {
        if (!isset($backendUser->uc['pageLanguages'])) {
            $backendUser->uc['pageLanguages'] = [];
        }
        $backendUser->uc['pageLanguages'][$pageId] = $languageIds;
        $backendUser->writeUC();
    }
}
