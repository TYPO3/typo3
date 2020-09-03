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

namespace TYPO3\CMS\Linkvalidator\Result;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;

/**
 * Used to work with LinkAnalyzer results
 *
 * @internal
 */
class LinkAnalyzerResult
{
    /**
     * @var LinkAnalyzer
     */
    protected $linkAnalyzer;

    /**
     * @var BrokenLinkRepository
     */
    protected $brokenLinkRepository;

    /**
     * @var ConnectionPool
     */
    protected $connectionPool;

    /**
     * @var array
     */
    protected $brokenLinks = [];

    /**
     * @var array
     */
    protected $newBrokenLinkCounts = [];

    /**
     * @var array
     */
    protected $oldBrokenLinkCounts = [];

    /**
     * @var bool
     */
    protected $differentToLastResult = false;

    /**
     * Save language codes to reduce database requests
     *
     * @var array<int, string>
     */
    protected $languageCodes = ['default'];

    /**
     * Save localized pages to reduce database requests
     *
     * @var array<string, int>
     */
    protected $localizedPages = [];

    public function __construct(
        LinkAnalyzer $linkAnalyzer,
        BrokenLinkRepository $brokenLinkRepository,
        ConnectionPool $connectionPool
    ) {
        $this->linkAnalyzer = $linkAnalyzer;
        $this->brokenLinkRepository = $brokenLinkRepository;
        $this->connectionPool = $connectionPool;
    }

    /**
     * Call LinkAnalyzer with provided task configuration and process result values
     *
     * @param int    $page
     * @param int    $depth
     * @param array  $pageRow
     * @param array  $modTSconfig
     * @param array  $searchFields
     * @param array  $linkTypes
     * @param string $languages
     *
     * @return $this
     */
    public function getResultForTask(
        int $page,
        int $depth,
        array $pageRow,
        array $modTSconfig,
        array $searchFields = [],
        array $linkTypes = [],
        string $languages = ''
    ): self {
        $rootLineHidden = $this->linkAnalyzer->getRootLineIsHidden($pageRow);
        $checkHidden = $modTSconfig['checkhidden'] === 1;

        if ($rootLineHidden && !$checkHidden) {
            return $this;
        }

        $treeList = $this->linkAnalyzer->extGetTreeList($page, $depth, 0, '1=1', $modTSconfig['checkhidden']);

        if ($pageRow['hidden'] === 0 || $checkHidden) {
            $treeList .= $page;
        }

        if ($treeList === '') {
            return $this;
        }

        $pageList = $this->addPageTranslationsToPageList(
            $treeList,
            $page,
            (bool)$modTSconfig['checkhidden'],
            $languages
        );

        $this->linkAnalyzer->init($searchFields, $pageList, $modTSconfig);
        $this->oldBrokenLinkCounts = $this->linkAnalyzer->getLinkCounts();

        $this->linkAnalyzer->getLinkStatistics($linkTypes, $modTSconfig['checkhidden']);
        $this->newBrokenLinkCounts = $this->linkAnalyzer->getLinkCounts();

        $this->brokenLinks = $this->brokenLinkRepository->getAllBrokenLinksForPages(
            GeneralUtility::intExplode(',', $pageList, true),
            array_keys($linkTypes),
            $searchFields,
            GeneralUtility::intExplode(',', $languages, true)
        );

        $this
            ->processLinkCounts($linkTypes)
            ->processBrokenLinks();

        return $this;
    }

    public function setBrokenLinks(array $brokenLinks): void
    {
        $this->brokenLinks = $brokenLinks;
    }

    public function getBrokenLinks(): array
    {
        return $this->brokenLinks;
    }

    public function setNewBrokenLinkCounts(array $newBrokenLinkCounts): void
    {
        $this->newBrokenLinkCounts = $newBrokenLinkCounts;
    }

    public function getNewBrokenLinkCounts(): array
    {
        return $this->newBrokenLinkCounts;
    }

    public function setOldBrokenLinkCounts(array $oldBrokenLinkCounts): void
    {
        $this->oldBrokenLinkCounts = $oldBrokenLinkCounts;
    }

    public function getOldBrokenLinkCounts(): array
    {
        return $this->oldBrokenLinkCounts;
    }

    public function getTotalBrokenLinksCount(): int
    {
        return $this->newBrokenLinkCounts['total'] ?? 0;
    }

    public function isDifferentToLastResult(): bool
    {
        return $this->differentToLastResult;
    }

    /**
     * Process the link counts (old and new) and ensures that all link types are available in the array
     *
     * @param array<int, string> $linkTypes list of link types
     * @return LinkAnalyzerResult
     */
    protected function processLinkCounts(array $linkTypes): self
    {
        foreach (array_keys($linkTypes) as $linkType) {
            if (!isset($this->newBrokenLinkCounts[$linkType])) {
                $this->newBrokenLinkCounts[$linkType] = 0;
            }
            if (!isset($this->oldBrokenLinkCounts[$linkType])) {
                $this->oldBrokenLinkCounts[$linkType] = 0;
            }
            if ($this->newBrokenLinkCounts[$linkType] !== $this->oldBrokenLinkCounts[$linkType]) {
                $this->differentToLastResult = true;
            }
        }

        return $this;
    }

    /**
     * Process broken link values and assign them to new variables which are used in the templates
     * shipped by the core but can also be used in custom templates. The raw data is untouched and
     * can also still be used in custom templates.
     *
     * @return LinkAnalyzerResult
     */
    protected function processBrokenLinks(): self
    {
        foreach ($this->brokenLinks as $key => &$brokenLink) {
            $fullRecord = BackendUtility::getRecord($brokenLink['table_name'], $brokenLink['record_uid']);

            if ($fullRecord !== null) {
                $brokenLink['full_record'] = $fullRecord;
                $brokenLink['record_title'] = BackendUtility::getRecordTitle($brokenLink['table_name'], $fullRecord);
            }

            $brokenLink['real_pid'] = ((int)($brokenLink['language'] ?? 0) > 0 && (string)($brokenLink['table_name'] ?? '') !== 'pages')
                ? $this->getLocalizedPageId((int)$brokenLink['record_pid'], (int)$brokenLink['language'])
                : $brokenLink['record_pid'];
            $pageRecord = BackendUtility::getRecord('pages', $brokenLink['real_pid']);

            if ($pageRecord !== null) {
                $brokenLink['page_record'] = $pageRecord;
            }

            $brokenLink['record_type'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$brokenLink['table_name']]['ctrl']['title'] ?? '');
            $brokenLink['target'] = (((string)($brokenLink['link_type'] ?? '') === 'db') ? 'id:' : '') . $brokenLink['url'];
            $brokenLink['language_code'] = $this->getLanguageCode((int)$brokenLink['language']);
        }

        return $this;
    }

    /**
     * Add localized page ids to the list of pages to get broken links from
     *
     * @param string $pageList
     * @param int $page
     * @param bool $checkHidden
     * @param string $languages
     * @return string
     */
    protected function addPageTranslationsToPageList(
        string $pageList,
        int $page,
        bool $checkHidden,
        string $languages = ''
    ): string {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $constraints[] = $queryBuilder->expr()->eq(
            'l10n_parent',
            $queryBuilder->createNamedParameter($page, Connection::PARAM_INT)
        );

        if ($languages !== '') {
            $constraints[] = $queryBuilder->expr()->in(
                'sys_language_uid',
                $queryBuilder->createNamedParameter(
                    GeneralUtility::intExplode(',', $languages, true),
                    Connection::PARAM_INT_ARRAY
                )
            );
        }

        $result = $queryBuilder
            ->select('uid', 'hidden')
            ->from('pages')
            ->where(...$constraints)
            ->execute();

        while ($row = $result->fetch()) {
            if ($row['hidden'] === 0 || $checkHidden) {
                $pageList .= ',' . $row['uid'];
            }
        }

        return $pageList;
    }

    /**
     * Get language iso code and store it in the local property languageCodes
     *
     * @param int $languageId
     * @return string
     */
    protected function getLanguageCode(int $languageId): string
    {
        if ((bool)($this->languageCodes[$languageId] ?? false)) {
            return $this->languageCodes[$languageId];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_language');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $langauge = $queryBuilder
            ->select('uid', 'language_isocode')
            ->from('sys_language')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($languageId, \PDO::PARAM_INT)))
            ->setMaxResults(1)
            ->execute()
            ->fetch() ?: [];

        if (is_array($langauge) && $langauge !== []) {
            $this->languageCodes[(int)$langauge['uid']] = $langauge['language_isocode'];
            return $langauge['language_isocode'];
        }

        return '';
    }

    /**
     * Get localized page id and store it in the local property localizedPages
     *
     * @param int $parentId
     * @param int $languageId
     * @return int
     */
    protected function getLocalizedPageId(int $parentId, int $languageId): int
    {
        $identifier = $parentId . '-' . $languageId;

        if ((bool)($this->localizedPages[$identifier] ?? false)) {
            return $this->localizedPages[$identifier];
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $localizedPageId = (int)$queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($parentId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter($languageId, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch()['uid'] ?: 0;

        if ($localizedPageId) {
            $this->localizedPages[$identifier] = $localizedPageId;
            return $localizedPageId;
        }

        return $parentId;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
