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

namespace TYPO3\CMS\Backend\Tree\Repository;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Page tree filter implementation providing search functionality for the backend page tree.
 *
 * This class implements page tree filtering through multiple event listeners that work together
 * to provide comprehensive search capabilities including:
 *
 * - Numeric UID search (direct page ID lookup)
 * - Wildcard text search in title/nav_title fields
 * - Optional search in translated page titles
 * - Visual labels indicating how pages were matched
 *
 * Overview
 * ========
 *
 * The filtering process consists of two main phases:
 *
 * 1. Query Building Phase (BeforePageTreeIsFilteredEvent)
 *    - addUidsFromSearchPhrase: Extracts numeric UIDs from search phrase
 *    - addWildCardAliasFilter: Adds LIKE queries for title/nav_title
 *    - addTranslatedPagesFilter: Queries translated pages (if enabled)
 *
 * 2. Label Attachment Phase (AfterPageTreeItemsPreparedEvent)
 *    - attachSearchResultLabel: Adds "Search result" label to directly matched pages
 *    - attachTranslationInfoLabel: Adds translation info labels
 *
 * Runtime Cache Usage
 * ===================
 *
 * Translation matches are stored in runtime cache with the structure:
 * [
 *   pageUid => [languageUid1, languageUid2, ...]
 * ]
 *
 * This allows the label attachment phase to know which translations matched,
 * enabling informative labels like "Found in translation: German".
 *
 * The cache is populated during query building and consumed during label attachment.
 * Cache key: 'pageTree_translationMatches'
 *
 * User Configuration
 * ==================
 *
 * Translation search can be controlled via:
 * - TSConfig: options.pageTree.searchInTranslatedPages (default: true)
 * - User Preference: pageTree_searchInTranslatedPages
 *
 * Language restrictions from user groups are respected automatically.
 *
 * @internal
 */
final class PageTreeFilter
{
    /**
     * Color for "Search result" labels on directly matched pages
     */
    private const SEARCH_RESULT_LABEL_COLOR = '#F5A770';

    /**
     * Runtime cache identifier for storing translation match information
     */
    private const CACHE_IDENTIFIER = 'pageTree_translationMatches';

    public function __construct(
        private readonly SiteFinder $siteFinder,
        #[Autowire(service: 'cache.runtime')]
        private readonly FrontendInterface $runtimeCache,
    ) {}

    /**
     * Extracts numeric page UIDs from the search phrase and adds them to the query.
     *
     * When a user searches for "123", this method:
     * 1. Extracts the UID (123) and adds it to event->searchUids
     * 2. If translation search is enabled, checks if 123 is a translated page
     * 3. If yes, also adds the l10n_parent UID to show the default language page
     *
     * Example: Searching for UID 456 where 456 is a German translation of page 123
     * will result in page 123 being shown with a "Found in translation: German" label.
     *
     * Supports comma-separated UIDs: "123,456,789"
     */
    #[AsEventListener('page-tree-uid-provider')]
    public function addUidsFromSearchPhrase(BeforePageTreeIsFilteredEvent $event): void
    {
        // Extract true integers from search string
        $searchPhrases = GeneralUtility::trimExplode(',', $event->searchPhrase, true);
        $numericUids = [];
        foreach ($searchPhrases as $searchPhrase) {
            if (MathUtility::canBeInterpretedAsInteger($searchPhrase) && $searchPhrase > 0) {
                $uid = (int)$searchPhrase;
                $event->searchUids[] = $uid;
                $numericUids[] = $uid;
            }
        }
        $event->searchUids = array_unique($event->searchUids);

        // Check if any numeric UIDs match translated pages and add their l10n_parent
        if ($numericUids !== [] && $this->isTranslatedPagesSearchEnabled()) {
            $queryBuilder = $this->createPreparedPagesQueryBuilder();
            $whereConditions = [
                $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($numericUids, Connection::PARAM_INT_ARRAY)),
            ];
            $translatedPages = $this->fetchTranslatedPages($queryBuilder, $whereConditions);
            $this->processTranslatedPages($event, $translatedPages);
        }
    }

    /**
     * Adds wildcard search conditions for title and nav_title fields.
     *
     * Creates a LIKE query that searches in both the 'title' and
     * 'nav_title' fields of default language pages.
     *
     * Example: Searching for "Home" will find pages with:
     * - title = "Homepage"
     * - nav_title = "Home Navigation"
     */
    #[AsEventListener('page-tree-wildcard-alias-filter')]
    public function addWildCardAliasFilter(BeforePageTreeIsFilteredEvent $event): void
    {
        $searchFilterWildcard = '%' . $event->queryBuilder->escapeLikeWildcards($event->searchPhrase) . '%';
        $searchWhereAlias = $event->queryBuilder->expr()->or(
            $event->queryBuilder->expr()->like(
                'nav_title',
                $event->queryBuilder->createNamedParameter($searchFilterWildcard)
            ),
            $event->queryBuilder->expr()->like(
                'title',
                $event->queryBuilder->createNamedParameter($searchFilterWildcard)
            )
        );
        $event->searchParts = $event->searchParts->with($searchWhereAlias);
    }

    /**
     * Searches in translated page titles if translation search is enabled.
     *
     * Performs a separate query to find translated pages (sys_language_uid > 0)
     * whose `title` or `nav_title` matches the search phrase. When matches are found,
     * the `l10n_parent` pages are added to search results with language information
     * stored in runtime cache.
     *
     * This allows finding pages like:
     * - Default page "Products" with German translation "Produkte"
     * - Searching for "Produkte" shows "Products" with label "Found in translation: German"
     *
     * Respects:
     * - User's language permissions (allowed_languages from user groups)
     * - TSConfig setting options.pageTree.searchInTranslatedPages
     * - User preference pageTree_searchInTranslatedPages
     */
    #[AsEventListener('page-tree-translated-pages-filter')]
    public function addTranslatedPagesFilter(BeforePageTreeIsFilteredEvent $event): void
    {
        if (!$this->isTranslatedPagesSearchEnabled()) {
            return;
        }

        $queryBuilder = $this->createPreparedPagesQueryBuilder();
        $searchFilterWildcard = '%' . $event->queryBuilder->escapeLikeWildcards($event->searchPhrase) . '%';

        $whereConditions = [
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->like('title', $queryBuilder->createNamedParameter($searchFilterWildcard)),
                $queryBuilder->expr()->like('nav_title', $queryBuilder->createNamedParameter($searchFilterWildcard))
            ),
        ];

        $translatedPages = $this->fetchTranslatedPages($queryBuilder, $whereConditions);
        $this->processTranslatedPages($event, $translatedPages);
    }

    /**
     * Attaches "Search result" labels to pages that directly matched the search.
     *
     * A page "directly matched" if its language is 0 and:
     * - Its UID equals the numeric search phrase
     * - Its title or nav_title contains the search phrase (case-insensitive)
     *
     * Pages that matched via translations do NOT get this label - they
     * get the translation info label instead.
     */
    #[AsEventListener('page-tree-add-search-result-label')]
    public function attachSearchResultLabel(AfterPageTreeItemsPreparedEvent $event): void
    {
        $searchPhrase = $event->getRequest()->getQueryParams()['q'] ?? '';
        if (trim($searchPhrase) === '') {
            return;
        }

        $label = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:pageTree.searchResult') ?: 'Search result';
        $items = $event->getItems();
        $searchPhraseLower = mb_strtolower($searchPhrase);

        foreach ($items as &$item) {
            $page = $item['_page'] ?? [];
            if (!is_array($page)) {
                continue;
            }

            $matchedDirectly = false;

            // Check if search phrase is numeric and matches the page UID
            if (MathUtility::canBeInterpretedAsInteger($searchPhrase) && (int)$searchPhrase === (int)($page['uid'] ?? 0)) {
                $matchedDirectly = true;
            }

            // Check if page title or nav_title contains the search phrase
            if (!$matchedDirectly) {
                $title = mb_strtolower((string)($page['title'] ?? ''));
                $navTitle = mb_strtolower((string)($page['nav_title'] ?? ''));

                if (str_contains($title, $searchPhraseLower) || str_contains($navTitle, $searchPhraseLower)) {
                    $matchedDirectly = true;
                }
            }

            if ($matchedDirectly) {
                if (!isset($item['labels'])) {
                    $item['labels'] = [];
                }
                $item['labels'][] = new Label(
                    label: $label,
                    color: self::SEARCH_RESULT_LABEL_COLOR,
                    inheritByChildren: false,
                );
            }
        }
        unset($item);
        $event->setItems($items);
    }

    /**
     * Attaches translation info labels to pages found via translated uid / content.
     *
     * Reads translation match information from runtime cache (populated during
     * the query building phase) and creates informative labels:
     *
     * - Single translation: "Found in translation: German"
     * - Multiple translations: "Found in multiple translations"
     *
     * The language name is resolved from the site configuration when possible.
     *
     * Priority: 1 (shown before regular search result labels)
     */
    #[AsEventListener('page-tree-add-translation-status')]
    public function attachTranslationInfoLabel(AfterPageTreeItemsPreparedEvent $event): void
    {
        $searchPhrase = $event->getRequest()->getQueryParams()['q'] ?? '';
        if (trim($searchPhrase) === '') {
            return;
        }

        $items = $event->getItems();
        foreach ($items as &$item) {
            $translationLanguageUids = $item['_translationLanguageUids'] ?? [];

            if (empty($translationLanguageUids)) {
                continue;
            }

            $page = $item['_page'] ?? [];
            if (!is_array($page) || !isset($page['uid'])) {
                continue;
            }

            $pageUid = (int)$page['uid'];

            // Determine label based on number of translations found
            $translationCount = count($translationLanguageUids);
            if ($translationCount === 1) {
                // Single translation - show language name
                $languageUid = $translationLanguageUids[0];
                $languageName = $this->getLanguageName($pageUid, $languageUid);
                if ($languageName === '') {
                    $label = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:pageTree.found_translation') ?: 'Found translation';
                } else {
                    $label = sprintf(
                        $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:pageTree.found_in_translation') ?: 'Found in translation: %s',
                        $languageName
                    );
                }
            } else {
                // Multiple translations
                $label = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:pageTree.found_in_multiple_translations') ?: 'Found in multiple translations';
            }

            // Add a label to highlight pages found via translation
            if (!isset($item['labels'])) {
                $item['labels'] = [];
            }
            $item['labels'][] = new Label(
                label: $label,
                color: self::SEARCH_RESULT_LABEL_COLOR,
                priority: 1,
                inheritByChildren: false,
            );
        }
        unset($item);
        $event->setItems($items);
    }

    protected function createPreparedPagesQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder;
    }

    /**
     * Fetches translated pages with common base conditions plus additional WHERE clauses.
     *
     * Applies standard conditions that all translation queries need:
     * - sys_language_uid > 0 (only translated pages)
     * - l10n_parent > 0 (must have a parent page)
     * - Workspace conditions (respects current workspace)
     * - Language restrictions (from user group permissions)
     *
     * @param array $additionalConditions Extra WHERE conditions (e.g., UID match or title LIKE)
     */
    protected function fetchTranslatedPages(QueryBuilder $queryBuilder, array $additionalConditions): array
    {
        $allowedLanguages = $this->getAllowedLanguagesForCurrentUser();
        $workspace = $this->getBackendUser()->workspace;

        $workspaceCondition = $workspace === 0
            ? $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            : $queryBuilder->expr()->in('t3ver_wsid', $queryBuilder->createNamedParameter([0, $workspace], Connection::PARAM_INT_ARRAY));

        $whereConditions = [
            $queryBuilder->expr()->gt('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            $queryBuilder->expr()->gt('l10n_parent', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            $workspaceCondition,
            ...$additionalConditions,
        ];

        if ($allowedLanguages !== []) {
            $whereConditions[] = $queryBuilder->expr()->in(
                'sys_language_uid',
                $queryBuilder->createNamedParameter($allowedLanguages, Connection::PARAM_INT_ARRAY)
            );
        }

        return $queryBuilder
            ->select('l10n_parent', 'sys_language_uid')
            ->from('pages')
            ->where(...$whereConditions)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Processes translated page query results: adds parent UIDs and updates cache.
     *
     * For each translated page found:
     * 1. Adds the l10n_parent UID to event->searchUids (avoiding duplicates)
     * 2. Stores the language UID in runtime cache for later label attachment
     *
     * The runtime cache structure is:
     * [pageUid => [languageUid1, languageUid2, ...]]
     *
     * @param array $translatedPages Query results with l10n_parent and sys_language_uid
     */
    protected function processTranslatedPages(BeforePageTreeIsFilteredEvent $event, array $translatedPages): void
    {
        $translationMatches = $this->runtimeCache->get(self::CACHE_IDENTIFIER) ?: [];
        $addedParents = [];

        foreach ($translatedPages as $translatedPage) {
            $l10nParent = (int)$translatedPage['l10n_parent'];
            $languageUid = (int)$translatedPage['sys_language_uid'];

            // Add parent UID to search results (avoiding duplicates)
            if (!isset($addedParents[$l10nParent])) {
                $event->searchUids[] = $l10nParent;
                $addedParents[$l10nParent] = true;
            }

            // Store translation match in runtime cache
            if (!isset($translationMatches[$l10nParent])) {
                $translationMatches[$l10nParent] = [];
            }
            if (!in_array($languageUid, $translationMatches[$l10nParent], true)) {
                $translationMatches[$l10nParent][] = $languageUid;
            }
        }

        $this->runtimeCache->set(self::CACHE_IDENTIFIER, $translationMatches);
    }

    protected function getLanguageName(int $pageUid, int $languageUid): string
    {
        try {
            $site = $this->siteFinder->getSiteByPageId($pageUid);
            return $site->getLanguageById($languageUid)->getTitle();
        } catch (\Exception) {
            return '';
        }
    }

    /**
     * Checks if translation search is enabled for the current user.
     *
     * Checks:
     * - TSConfig options.pageTree.searchInTranslatedPages
     * - User preference pageTree_searchInTranslatedPages
     */
    protected function isTranslatedPagesSearchEnabled(): bool
    {
        $backendUser = $this->getBackendUser();

        // If feature is disabled, always return false
        $translationSearchAvailable = (bool)($backendUser->getTSConfig()['options.']['pageTree.']['searchInTranslatedPages'] ?? true);
        if (!$translationSearchAvailable) {
            return false;
        }

        // If feature is available, check user preference
        if (isset($backendUser->uc['pageTree_searchInTranslatedPages'])) {
            return (bool)$backendUser->uc['pageTree_searchInTranslatedPages'];
        }

        return true;
    }

    protected function getAllowedLanguagesForCurrentUser(): array
    {
        $allowedLanguages = trim($this->getBackendUser()->groupData['allowed_languages'] ?? '');
        return $allowedLanguages !== '' ? GeneralUtility::intExplode(',', $allowedLanguages) : [];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
