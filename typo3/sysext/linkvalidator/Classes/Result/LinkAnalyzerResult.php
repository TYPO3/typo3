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
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;

/**
 * Used to work with LinkAnalyzer results
 *
 * @internal
 */
class LinkAnalyzerResult
{
    protected array $brokenLinks = [];
    protected array $newBrokenLinkCounts = [];
    protected array $oldBrokenLinkCounts = [];
    protected bool $differentToLastResult = false;

    /**
     * Save localized pages to reduce database requests
     *
     * @var array<string, int>
     */
    protected array $localizedPages = [];

    public function __construct(
        private readonly LinkAnalyzer $linkAnalyzer,
        private readonly BrokenLinkRepository $brokenLinkRepository,
        private readonly ConnectionPool $connectionPool,
        private readonly PagesRepository $pagesRepository
    ) {}

    /**
     * Call LinkAnalyzer with provided task configuration and process result values
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
        $rootLineHidden = $this->pagesRepository->doesRootLineContainHiddenPages($pageRow);
        $checkHidden = $modTSconfig['checkhidden'] === 1;

        if ($rootLineHidden && !$checkHidden) {
            return $this;
        }

        $pageIds = $this->pagesRepository->getAllSubpagesForPage(
            $page,
            $depth,
            '',
            $checkHidden
        );

        if ($pageRow['hidden'] === 0 || $checkHidden) {
            $pageIds[] = $page;
        }

        if (empty($pageIds)) {
            return $this;
        }

        $languageIds = GeneralUtility::intExplode(',', $languages, true);
        $pageTranslations = $this->pagesRepository->getTranslationForPage(
            $page,
            '',
            $checkHidden,
            $languageIds
        );

        $pageIds = array_merge($pageIds, $pageTranslations);

        $this->linkAnalyzer->init($searchFields, $pageIds, $modTSconfig);
        $this->oldBrokenLinkCounts = $this->linkAnalyzer->getLinkCounts();

        $this->linkAnalyzer->getLinkStatistics($linkTypes, $checkHidden);
        $this->newBrokenLinkCounts = $this->linkAnalyzer->getLinkCounts();

        $this->brokenLinks = $this->brokenLinkRepository->getAllBrokenLinksForPages(
            $pageIds,
            $linkTypes,
            $searchFields,
            $languageIds
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
     */
    protected function processLinkCounts(array $linkTypes): self
    {
        foreach ($linkTypes as $linkType) {
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
     */
    protected function processBrokenLinks(): self
    {
        foreach ($this->brokenLinks as &$brokenLink) {
            $fullRecord = BackendUtility::getRecord($brokenLink['table_name'], $brokenLink['record_uid']);

            if ($fullRecord !== null) {
                $brokenLink['full_record'] = $fullRecord;
                $brokenLink['record_title'] = BackendUtility::getRecordTitle($brokenLink['table_name'], $fullRecord);
            }

            $brokenLink['real_pid'] = ((int)($brokenLink['language'] ?? 0) > 0 && (string)($brokenLink['table_name'] ?? '') !== 'pages')
                ? $this->getLocalizedPageId((int)$brokenLink['record_pid'], (int)$brokenLink['language'])
                : $brokenLink['record_pid'];
            $pageRecord = BackendUtility::getRecord('pages', $brokenLink['real_pid']);

            try {
                $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId((int)$brokenLink['real_pid']);
                $languageCode = $site->getLanguageById((int)$brokenLink['language'])->getLocale()->getLanguageCode();
            } catch (SiteNotFoundException | \InvalidArgumentException $e) {
                $languageCode = 'default';
            }
            if ($pageRecord !== null) {
                $brokenLink['page_record'] = $pageRecord;
            }

            $brokenLink['record_type'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$brokenLink['table_name']]['ctrl']['title'] ?? '');
            $brokenLink['target'] = (((string)($brokenLink['link_type'] ?? '') === 'db') ? 'id:' : '') . $brokenLink['url'];
            $brokenLink['language_code'] = (string)$languageCode;
        }

        return $this;
    }

    /**
     * Get localized page id and store it in the local property localizedPages
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
                    $queryBuilder->createNamedParameter($parentId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative()['uid'] ?: 0;

        if ($localizedPageId) {
            $this->localizedPages[$identifier] = $localizedPageId;
            return $localizedPageId;
        }

        return $parentId;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
