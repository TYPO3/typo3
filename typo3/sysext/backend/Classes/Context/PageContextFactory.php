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

namespace TYPO3\CMS\Backend\Context;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Service\PageLanguageInformationService;
use TYPO3\CMS\Backend\User\SharedUserPreferences;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory for creating PageContext instances.
 *
 * This is the SINGLE entry point for creating page contexts across all backend modules.
 * It centralizes the logic for:
 * - Resolving language selection with fallback chain
 * - Validating languages against available languages
 * - Permission checks
 * - Fetching language information
 *
 * @internal
 */
final readonly class PageContextFactory
{
    public function __construct(
        private SharedUserPreferences $sharedPreferences,
        private PageLanguageInformationService $languageService,
    ) {}

    /**
     * Create PageContext from request and page ID.
     *
     * This method:
     * 1. Validates page access (returns context with null pageRecord if no access)
     * 2. Fetches language information for the page
     * 3. Resolves selected languages with fallback chain
     * 4. Validates selected languages against existing translations on this page
     * 5. Falls back to default language if no valid languages selected
     * 6. Stores preference if explicitly changed via request (preserves across pages)
     * 7. Creates and returns the PageContext
     *
     * Language validation ensures that only languages with actual translations on
     * the current page are included in selectedLanguageIds. This guarantees that
     * getPrimaryLanguageId() always returns a valid language for the current page.
     *
     * User preferences are preserved: selecting L=1 on PageA stores the preference,
     * navigating to PageB without L=1 shows L=0, returning to PageA restores L=1.
     *
     * Access Handling:
     * If the user has no access to the requested page or pid=0, a PageContext is still returned,
     * while pageRecord mit be null if no access. Controllers should check isAccessible().
     *
     * @param int $pageId Page ID to create context for
     */
    public function createFromRequest(
        ServerRequestInterface $request,
        int $pageId,
        BackendUserAuthentication $backendUser
    ): PageContext {
        $site = $request->getAttribute('site');
        if (!$site instanceof SiteInterface) {
            throw new SiteNotFoundException('No site found in request', 1731234567);
        }

        // Check page access
        $pageRecord = BackendUtility::readPageAccess($pageId, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)) ?: null;
        if ($pageId === 0 || !$pageRecord) {
            // Either root page (pid=0) which has no real page record or no access.
            // Return context with preserved pageId.
            // pageRecord might be ['path' => '/'] for admins or NULL if no access or non-admin
            // Still calculate permissions (admins have access to pid=0, editors don't).
            return new PageContext(
                pageId: $pageId,
                pageRecord: $pageRecord,
                site: $site,
                rootLine: [],
                pageTsConfig: GeneralUtility::removeDotsFromTS(BackendUtility::getPagesTSconfig(0)),
                selectedLanguageIds: [0],
                languageInformation: $this->languageService->getLanguageInformationForPage(0, $site, $backendUser),
                pagePermissions: new Permission($backendUser->calcPerms($pageRecord ?: ['uid' => 0])),
            );
        }

        // Get language information FIRST (needed for validation)
        $languageInformation = $this->languageService->getLanguageInformationForPage($pageId, $site, $backendUser);

        // Resolve languages with fallback chain
        $languagesFromRequest = $request->getQueryParams()['languages'] ?? $request->getParsedBody()['languages'] ?? null;

        // Extract ModuleData languages (with backward compat for old 'language' parameter)
        $moduleData = $request->getAttribute('moduleData');
        $moduleDataLanguages = null;
        if ($moduleData instanceof ModuleData) {
            $moduleDataLanguages = $moduleData->get('languages');
            // Backward compatibility: convert old 'language' (single int) to 'languages' (array)
            if ($moduleDataLanguages === null) {
                $oldLanguage = $moduleData->get('language');
                if ($oldLanguage !== null) {
                    $moduleDataLanguages = [(int)$oldLanguage];
                }
            }
        }

        // Use SharedUserPreferences fallback chain (page-specific > ModuleData > default)
        // This ensures page-specific preferences are shared across modules
        $resolvedLanguages = $this->sharedPreferences->resolveLanguages(
            $backendUser,
            $languagesFromRequest,
            $pageId,
            $moduleDataLanguages
        );

        // Validate against existing translations on this page (ensures getPrimaryLanguageId() is valid)
        // Preference is preserved across navigation (only stored when explicitly changed via request)
        $existingLanguageIds = $languageInformation->getAllExistingLanguageIds();
        $validLanguages = array_intersect($resolvedLanguages, $existingLanguageIds);

        // Ensure at least default language if none are valid
        if (empty($validLanguages)) {
            $validLanguages = [0];
        }

        $validLanguages = array_values($validLanguages);

        // Store preference in SharedUserPreferences when explicitly changed via request
        if ($languagesFromRequest !== null) {
            $this->sharedPreferences->setPageLanguages($backendUser, $pageId, $validLanguages);
        }

        // Also update ModuleData if present (for backward compatibility and UI state)
        if ($moduleData instanceof ModuleData) {
            $moduleData->set('languages', $validLanguages);
        }

        // Create full PageContext for resolved page record
        return new PageContext(
            pageId: $pageId,
            pageRecord: $pageRecord,
            site: $site,
            rootLine: BackendUtility::BEgetRootLine($pageId),
            pageTsConfig: GeneralUtility::removeDotsFromTS(BackendUtility::getPagesTSconfig($pageId)),
            selectedLanguageIds: $validLanguages,
            languageInformation: $languageInformation,
            pagePermissions: new Permission($backendUser->calcPerms($pageRecord)),
        );
    }

    /**
     * Create PageContext with specific languages (no fallback resolution).
     *
     * This is useful for testing or to explicitly set languages
     * without going through the fallback chain.
     *
     * Access Handling:
     * If the user has no access to the requested page or pid=0, a PageContext is still returned,
     * while pageRecord mit be null if no access. Controllers should check isAccessible().
     */
    public function createWithLanguages(
        ServerRequestInterface $request,
        int $pageId,
        array $languageIds,
        BackendUserAuthentication $backendUser
    ): PageContext {
        $site = $request->getAttribute('site');
        if (!$site instanceof SiteInterface) {
            throw new SiteNotFoundException('No site found in request', 1731234569);
        }

        $pageRecord = BackendUtility::readPageAccess($pageId, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)) ?: null;
        if ($pageId === 0 || !$pageRecord) {
            // Either root page (pid=0) which has no real page record or no access.
            // Return context with preserved pageId.
            // pageRecord might be ['path' => '/'] for admins or NULL if no access or non-admin
            // Still calculate permissions (admins have access to pid=0, editors don't).
            return new PageContext(
                pageId: $pageId,
                pageRecord: $pageRecord,
                site: $site,
                rootLine: [],
                pageTsConfig: GeneralUtility::removeDotsFromTS(BackendUtility::getPagesTSconfig(0)),
                selectedLanguageIds: array_map('intval', $languageIds),
                languageInformation: $this->languageService->getLanguageInformationForPage(0, $site, $backendUser),
                pagePermissions: new Permission($backendUser->calcPerms($pageRecord ?: ['uid' => 0])),
            );
        }

        // Create full PageContext for resolved page record
        return new PageContext(
            pageId: $pageId,
            pageRecord: $pageRecord,
            site: $site,
            rootLine: BackendUtility::BEgetRootLine($pageId),
            pageTsConfig: GeneralUtility::removeDotsFromTS(BackendUtility::getPagesTSconfig($pageId)),
            selectedLanguageIds: array_map('intval', $languageIds),
            languageInformation: $this->languageService->getLanguageInformationForPage($pageId, $site, $backendUser),
            pagePermissions: new Permission($backendUser->calcPerms($pageRecord)),
        );
    }
}
