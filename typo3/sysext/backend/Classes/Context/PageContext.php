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

use TYPO3\CMS\Backend\Domain\Model\Language\PageLanguageInformation;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Generic page context for all backend modules working with pages ("id" parameter and page-tree navigation component).
 *
 * This context is added to the backend request by the PSR-15 "PageContextInitialization" middleware.
 * Replaces the module-specific duplication of language handling, page information, and site context.
 *
 * Contains shared data needed across Page Module, List Module, etc.
 * Does NOT contain module-specific rendering configuration.
 *
 * This is a DOMAIN object and should NOT contain HTTP infrastructure concerns like ServerRequestInterface.
 *
 * Access Handling:
 * If the user has no access to the requested page, pageRecord will be null.
 * Controllers should check $pageContext->isAccessible() before processing.
 *
 * Usage:
 *     $pageContext = $request->getAttribute('pageContext');
 *     if (!$pageContext->isAccessible()) {
 *         // Show no access page
 *         return $view->renderResponse('NoAccess');
 *     }
 *     $selectedLanguages = $pageContext->selectedLanguageIds;
 *     $languageInfo = $pageContext->languageInformation;
 *     $rootLine = $pageContext->rootLine;
 *     $pageTsConfig = $pageContext->pageTsConfig;
 *     $moduleTsConfig = $pageContext->getModuleTsConfig('web_layout');
 *
 * @internal
 */
final readonly class PageContext
{
    /**
     * @param int $pageId Page ID (always preserved, even if no access)
     * @param ?array $pageRecord Page record from readPageAccess (null if no access)
     * @param int[] $selectedLanguageIds Selected language IDs (resolved and validated)
     * @param PageLanguageInformation $languageInformation Complete language information for this page
     * @param array $rootLine Page rootline including the page itself (empty array if no access)
     * @param array $pageTsConfig PageTSconfig array (dots removed, overlaid by user permissions, falls back to page 0 if no access)
     * @param Permission $pagePermissions User's permissions for this page (calculated from backendUser->calcPerms)
     */
    public function __construct(
        public int $pageId,
        public ?array $pageRecord,
        public SiteInterface $site,
        public array $rootLine,
        public array $pageTsConfig,
        public array $selectedLanguageIds,
        public PageLanguageInformation $languageInformation,
        public Permission $pagePermissions,
    ) {}

    /**
     * Check if user has access to the page.
     *
     * Returns false if user has no access to the requested page.
     * Controllers should check this before processing page-specific operations.
     */
    public function isAccessible(): bool
    {
        return $this->pageRecord !== null && $this->pagePermissions->showPagePermissionIsGranted();
    }

    /**
     * Get primary selected language for single-language views.
     *
     * Logic:
     * - If exactly 1 non-default language is selected → use that translation
     * - If 0 or 2+ non-default languages are selected → use default (0)
     *
     * This ensures that when switching from multi-language to single-language view,
     * the user's focused translation is preserved (when they had one selected).
     *
     * @return int Primary language ID
     */
    public function getPrimaryLanguageId(): int
    {
        $nonDefaultLanguages = array_filter($this->selectedLanguageIds, static fn(int $id): bool => $id > 0);
        if (count($nonDefaultLanguages) === 1) {
            return reset($nonDefaultLanguages);
        }
        return 0;
    }

    /**
     * Check if multiple languages are currently selected.
     *
     * This is useful for determining if comparison/multi-column view should be shown.
     */
    public function hasMultipleLanguagesSelected(): bool
    {
        return count($this->selectedLanguageIds) > 1;
    }

    public function isLanguageSelected(int $languageId): bool
    {
        return in_array($languageId, $this->selectedLanguageIds, true);
    }

    public function isDefaultLanguageSelected(): bool
    {
        return $this->isLanguageSelected(0);
    }

    /**
     * Get page title (localized if translation exists).
     *
     * @param int|null $languageId Language ID (null = primary selected language)
     */
    public function getPageTitle(?int $languageId = null): string
    {
        $languageId ??= $this->getPrimaryLanguageId();

        if ($languageId === 0) {
            return $this->pageRecord['title'] ?? '';
        }

        $translation = $this->languageInformation->getTranslationRecord($languageId);
        return $translation['title'] ?? $this->pageRecord['title'] ?? '';
    }

    /**
     * This is a convenience method to easily access mod.{module}.* configuration.
     */
    public function getModuleTsConfig(string $module): array
    {
        return is_array($this->pageTsConfig['mod'][$module] ?? false) ? $this->pageTsConfig['mod'][$module] : [];
    }
}
