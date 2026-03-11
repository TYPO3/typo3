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

namespace TYPO3\CMS\Backend\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Domain\Model\Language\LanguageItem;
use TYPO3\CMS\Backend\Domain\Model\Language\LanguageStatus;
use TYPO3\CMS\Backend\Domain\Model\Language\PageLanguageInformation;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\LanguageMarker;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Central service for gathering page language information.
 *
 * This service provides the SINGLE source of truth for:
 * - Which languages exist (have translations)
 * - Which languages can be created (permissions + missing)
 * - Which languages are unavailable (no permission)
 * - Translation records with workspace overlay
 * - UI properties (labels, flags, icons)
 *
 * Note: Languages marked as "Unavailable" are included in the result
 * for potential future use (e.g., showing "No Access" indicators),
 * but controllers currently filter them out before displaying the UI.
 *
 * @internal
 */
final readonly class PageLanguageInformationService
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
        private ConnectionPool $connectionPool,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
    ) {}

    /**
     * Get complete language information for a page.
     *
     * This method fetches and analyzes all language-related data for a page,
     * including existing translations, permissions, and available languages.
     */
    public function getLanguageInformationForPage(
        int $pageId,
        SiteInterface $site,
        BackendUserAuthentication $backendUser
    ): PageLanguageInformation {
        // Build cache key: page ID + workspace + user ID (for permissions)
        $cacheKey = $this->getCacheKeyForPage($pageId, $backendUser);

        // Check runtime cache
        $cachedInfo = $this->runtimeCache->get($cacheKey);
        if ($cachedInfo instanceof PageLanguageInformation) {
            return $cachedInfo;
        }

        // Get ALL enabled languages (without permission filtering)
        // We need to do permission checks ourselves to properly classify as Unavailable
        if ($site instanceof Site) {
            // For Site we use getAllLanguages(), because getLanguages() would remove "disabled" languages
            $allLanguages = $site->getAllLanguages();
        } else {
            $allLanguages = $site->getLanguages();
        }

        // For NullSite (page 0), respect mod.SHARED.disableLanguages TSconfig
        $disabledLanguages = [];
        if ($site instanceof NullSite) {
            $pageTs = BackendUtility::getPagesTSconfig($pageId);
            $pageTs = $pageTs['mod.']['SHARED.'] ?? [];
            $disabledLanguages = GeneralUtility::intExplode(',', (string)($pageTs['disableLanguages'] ?? ''), true);
        }

        $existingTranslations = $this->fetchExistingTranslations($pageId, $backendUser);
        $canUserCreateTranslations = $this->canUserCreatePageTranslations($backendUser, $pageId);

        $availableLanguages = [];
        $languageStatuses = [];
        $creatableLanguageIds = [];
        foreach ($allLanguages as $siteLanguage) {
            $languageId = $siteLanguage->getLanguageId();

            if (in_array($languageId, $disabledLanguages, true)) {
                // For NullSite: skip languages disabled via TSconfig
                continue;
            }

            // Check if user has access to this specific language
            if (!$backendUser->checkLanguageAccess($languageId)) {
                // Language exists in site config but user has no access
                $languageStatuses[$languageId] = LanguageStatus::Unavailable;
                // Include in availableLanguages for potential future use (e.g., "No Access" indicators)
                // Note: Controllers currently filter out Unavailable languages before display
                $availableLanguages[$languageId] = $siteLanguage;
                continue;
            }

            // User has access to this language
            $availableLanguages[$languageId] = $siteLanguage;

            if ($languageId === LanguageMarker::DEFAULT_LANGUAGE || isset($existingTranslations[$languageId])) {
                // Default language or existing translation
                $languageStatuses[$languageId] = LanguageStatus::Existing;
            } elseif ($canUserCreateTranslations) {
                // Can be created (user has page edit permission AND language access)
                $languageStatuses[$languageId] = LanguageStatus::Creatable;
                $creatableLanguageIds[] = $languageId;
            } else {
                // Cannot be created (no page edit permission, but has language access)
                $languageStatuses[$languageId] = LanguageStatus::Unavailable;
            }
        }

        // Filter existingTranslations to only include languages that are available.
        // This ensures consistency: a language with a translation in the database but whose
        // language does not exist in site configuration should not appear as "existing" in the UI.
        $existingTranslations = array_intersect_key($existingTranslations, $availableLanguages);

        $languageInformation = new PageLanguageInformation(
            pageId: $pageId,
            availableLanguages: $availableLanguages,
            languageStatuses: $languageStatuses,
            existingTranslations: $existingTranslations,
            creatableLanguageIds: $creatableLanguageIds,
            canUserCreateTranslations: $canUserCreateTranslations,
            languageItems: $this->getLanguageItems($languageStatuses, $availableLanguages),
        );

        // Store in runtime cache for this request
        $this->runtimeCache->set($cacheKey, $languageInformation);

        return $languageInformation;
    }

    /**
     * Get language items ready for UI rendering. Includes all UI properties (labels, flags, etc.)
     *
     * @return LanguageItem[] Array of language items
     */
    private function getLanguageItems(array $languageStatuses, array $availableLanguages): array
    {
        $languageItems = [];
        foreach ($availableLanguages as $siteLanguage) {
            $languageId = $siteLanguage->getLanguageId();
            $languageItems[] = new LanguageItem(
                siteLanguage: $siteLanguage,
                status: $languageStatuses[$languageId] ?? LanguageStatus::Unavailable,
            );
        }
        return $languageItems;
    }

    /**
     * Fetch existing page translations with workspace overlay.
     *
     * This is the centralized logic that was previously duplicated across
     * PageLayoutController and RecordListController.
     *
     * @param int $pageId Page ID
     * @param BackendUserAuthentication $backendUser Current backend user
     * @return array<int, array> Translation records keyed by language ID
     */
    private function fetchExistingTranslations(
        int $pageId,
        BackendUserAuthentication $backendUser
    ): array {
        $schema = $this->tcaSchemaFactory->get('pages');
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $languageField = $languageCapability->getLanguageField()->getName();
        $translationOriginField = $languageCapability->getTranslationOriginPointerField()->getName();

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(new DeletedRestriction())
            ->add(new WorkspaceRestriction($backendUser->workspace));

        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $translationOriginField,
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    $languageField,
                    LanguageMarker::DEFAULT_LANGUAGE
                )
            );

        $existingTranslations = [];
        $statement = $queryBuilder->executeQuery();

        while ($row = $statement->fetchAssociative()) {
            BackendUtility::workspaceOL('pages', $row, $backendUser->workspace);
            if ($row && VersionState::tryFrom($row['t3ver_state'] ?? 0) !== VersionState::DELETE_PLACEHOLDER) {
                $existingTranslations[(int)$row[$languageField]] = $row;
            }
        }

        return $existingTranslations;
    }

    /**
     * Get the languages of records on the page which cannot be selected in the language
     * menu, because the page has no translation into them or the site does not configure
     * them. Languages the user has no access to are left out. The root level has no page
     * translations and is therefore not checked.
     *
     * Not part of getLanguageInformationForPage(), since it queries every language-aware
     * table and only the modules listing these records need it.
     *
     * @return int[] Sorted language IDs
     */
    public function getOrphanedLanguageIds(
        PageLanguageInformation $languageInformation,
        BackendUserAuthentication $backendUser
    ): array {
        $pageId = $languageInformation->pageId;
        if ($pageId === 0) {
            return [];
        }
        $cacheKey = 'PageLanguageInformation_orphaned_' . $pageId . '_' . $backendUser->workspace . '_' . $backendUser->getUserId();
        $cachedLanguageIds = $this->runtimeCache->get($cacheKey);
        if (is_array($cachedLanguageIds)) {
            return $cachedLanguageIds;
        }
        $selectableLanguageIds = $languageInformation->getAllExistingLanguageIds();
        $recordLanguageIds = array_unique([
            ...array_keys($this->fetchExistingTranslations($pageId, $backendUser)),
            ...$this->fetchRecordLanguageIds($pageId, $selectableLanguageIds, $backendUser),
        ]);
        $orphanedLanguageIds = array_values(array_filter(
            $recordLanguageIds,
            static fn(int $languageId): bool => !in_array($languageId, $selectableLanguageIds, true)
                && $backendUser->checkLanguageAccess($languageId)
        ));
        sort($orphanedLanguageIds);
        $this->runtimeCache->set($cacheKey, $orphanedLanguageIds);
        return $orphanedLanguageIds;
    }

    /**
     * Describe orphaned languages for editors: the titles of site languages the page
     * has not been translated into, and a general note for languages the site does
     * not configure, whose IDs carry no meaning for editors.
     *
     * @param int[] $orphanedLanguageIds
     */
    public function getOrphanedLanguageMessage(
        PageLanguageInformation $languageInformation,
        array $orphanedLanguageIds,
        LanguageService $languageService
    ): string {
        $untranslatedLanguageTitles = [];
        $hasUnconfiguredLanguages = false;
        foreach ($orphanedLanguageIds as $languageId) {
            if (isset($languageInformation->availableLanguages[$languageId])) {
                $untranslatedLanguageTitles[] = $languageInformation->availableLanguages[$languageId]->getTitle();
            } else {
                $hasUnconfiguredLanguages = true;
            }
        }
        $sentences = [];
        if ($untranslatedLanguageTitles !== []) {
            $sentences[] = sprintf(
                $languageService->sL('backend.layout:orphanedLanguageWarning.untranslated'),
                implode(', ', $untranslatedLanguageTitles)
            );
        }
        if ($hasUnconfiguredLanguages) {
            $sentences[] = $languageService->sL('backend.layout:orphanedLanguageWarning.notConfigured');
        }
        return implode(' ', $sentences);
    }

    /**
     * Distinct language IDs of translated records on the page, which are not selectable,
     * limited to the records the Records module lists: tables the user may list and which
     * are not hidden (TCA, mod.web_list.hideTables, mod.web_list.table.<table>.hideTable),
     * records which are not moved away in the current workspace, and translations only if
     * they are not hidden by mod.web_list.hideTranslations and are listed below their
     * parent, which is the case if the parent is on the same page.
     *
     * @param int[] $selectableLanguageIds
     * @return int[]
     */
    private function fetchRecordLanguageIds(int $pageId, array $selectableLanguageIds, BackendUserAuthentication $backendUser): array
    {
        $listModuleTsConfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_list.'] ?? [];
        $hiddenTables = GeneralUtility::trimExplode(',', (string)($listModuleTsConfig['hideTables'] ?? ''), true);
        $hideTranslations = (string)($listModuleTsConfig['hideTranslations'] ?? '');
        $languageIds = [];
        foreach ($this->tcaSchemaFactory->all() as $schema) {
            $tableName = $schema->getName();
            if ($tableName === 'pages'
                || !$schema->isLanguageAware()
                || !$schema->getCapability(TcaSchemaCapability::RestrictionRootLevel)->canExistOnPages()
                || !$backendUser->check('tables_select', $tableName)
            ) {
                continue;
            }
            // Same as DatabaseRecordList::getTablesToRender()
            $hideTable = $schema->hasCapability(TcaSchemaCapability::HideInUi)
                || in_array($tableName, $hiddenTables, true)
                || in_array('*', $hiddenTables, true);
            if ((bool)($listModuleTsConfig['table.'][$tableName . '.']['hideTable'] ?? $hideTable)) {
                continue;
            }
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $languageField = $languageCapability->getLanguageField()->getName();
            $translationParentField = $languageCapability->getTranslationOriginPointerField()->getName();
            $queryBuilder = $this->getListedRecordsQueryBuilder($tableName, $pageId, $backendUser, [$languageField, $translationParentField]);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gt($languageField, $queryBuilder->createNamedParameter(LanguageMarker::DEFAULT_LANGUAGE, Connection::PARAM_INT))
            );
            if ($selectableLanguageIds !== []) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->notIn($languageField, $queryBuilder->createNamedParameter($selectableLanguageIds, Connection::PARAM_INT_ARRAY))
                );
            }
            if ($hideTranslations === '*' || GeneralUtility::inList($hideTranslations, $tableName)) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($translationParentField, 0));
            }
            $translationParentIds = [];
            foreach ($this->fetchListedRecords($tableName, $queryBuilder, $backendUser) as $row) {
                $languageId = (int)$row[$languageField];
                $translationParentId = (int)$row[$translationParentField];
                if ($translationParentId === 0) {
                    $languageIds[$languageId] = $languageId;
                } else {
                    $translationParentIds[$languageId][] = $translationParentId;
                }
            }
            $translationParentIds = array_diff_key($translationParentIds, $languageIds);
            if ($translationParentIds === []) {
                continue;
            }
            // Translations are listed below their parent, if it is a record in default language on the same page
            $queryBuilder = $this->getListedRecordsQueryBuilder($tableName, $pageId, $backendUser, [$languageField, $translationParentField]);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter(array_unique(array_merge(...array_values($translationParentIds))), Connection::PARAM_INT_ARRAY)),
                $queryBuilder->expr()->eq($languageField, $queryBuilder->createNamedParameter(LanguageMarker::DEFAULT_LANGUAGE, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq($translationParentField, 0)
            );
            $listedParentIds = array_column($this->fetchListedRecords($tableName, $queryBuilder, $backendUser), 'uid');
            foreach ($translationParentIds as $languageId => $parentIds) {
                if (array_intersect($parentIds, $listedParentIds) !== []) {
                    $languageIds[$languageId] = $languageId;
                }
            }
        }
        return array_values($languageIds);
    }

    /**
     * @param string[] $fields
     */
    private function getListedRecordsQueryBuilder(string $tableName, int $pageId, BackendUserAuthentication $backendUser, array $fields): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll()
            ->add(new DeletedRestriction())
            ->add(new WorkspaceRestriction($backendUser->workspace));
        if ($this->tcaSchemaFactory->get($tableName)->isWorkspaceAware()) {
            $fields = [...$fields, 't3ver_oid', 't3ver_state', 't3ver_wsid'];
        }
        return $queryBuilder
            ->select('uid', 'pid', ...$fields)
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            );
    }

    /**
     * Fetch the records with workspace overlay, leaving out records moved away in the
     * workspace, like DatabaseRecordList does.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchListedRecords(string $tableName, QueryBuilder $queryBuilder, BackendUserAuthentication $backendUser): array
    {
        $rows = [];
        foreach ($queryBuilder->executeQuery()->fetchAllAssociative() as $row) {
            BackendUtility::workspaceOL($tableName, $row, $backendUser->workspace, true);
            if (is_array($row)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Check if user can create page translations.
     *
     * @param BackendUserAuthentication $backendUser Current backend user
     * @param int $pageId Page ID
     * @return bool True if user can create translations
     */
    private function canUserCreatePageTranslations(
        BackendUserAuthentication $backendUser,
        int $pageId
    ): bool {
        // Check table permission
        if (!$backendUser->check('tables_modify', 'pages')) {
            return false;
        }

        // Check page permission
        $pageInfo = BackendUtility::readPageAccess($pageId, $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        if (!$pageInfo) {
            return false;
        }

        return $backendUser->doesUserHaveAccess($pageInfo, Permission::PAGE_EDIT);
    }

    private function getCacheKeyForPage(int $pageId, BackendUserAuthentication $backendUser): string
    {
        return 'PageLanguageInformation_' . $pageId . '_' . $backendUser->workspace . '_' . $backendUser->getUserId();
    }
}
