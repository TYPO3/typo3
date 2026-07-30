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

namespace TYPO3\CMS\Seo\XmlSitemap;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to generate a XML sitemap for pages
 * @internal this class is not part of TYPO3's Core API.
 */
class PagesXmlSitemapDataProvider implements XmlSitemapDataProviderInterface
{
    public function __construct(
        protected readonly Context $context,
        protected readonly ConnectionPool $connectionPool,
        protected readonly PageRepository $pageRepository,
        protected readonly RecordFactory $recordFactory,
    ) {}

    public function getSitemap(XmlSitemapRequest $sitemapRequest): XmlSitemap
    {
        return XmlSitemap::forPage(
            $this->generateItems($sitemapRequest),
            $sitemapRequest->page,
            fn(array $item): array => $this->defineUrl($item, $sitemapRequest),
        );
    }

    protected function generateItems(XmlSitemapRequest $sitemapRequest): array
    {
        $items = [];
        $pages = $this->pageRepository->getPagesOverlay($this->getPages($sitemapRequest));
        $languageAspect = $this->getCurrentLanguageAspect();
        foreach ($pages as $page) {
            if (!$this->pageRepository->isPageSuitableForLanguage($page, $languageAspect)) {
                continue;
            }

            $items[] = $page + [
                'lastMod' => (int)($page['SYS_LASTCHANGED'] ?: $page['tstamp']),
                'changefreq' => $page['sitemap_changefreq'],
                'priority' => (float)$page['sitemap_priority'],
            ];
        }
        return $items;
    }

    protected function getPages(XmlSitemapRequest $sitemapRequest): array
    {
        $configuration = $sitemapRequest->configuration;
        if (!empty($configuration['rootPage'])) {
            $rootPageId = (int)$configuration['rootPage'];
        } else {
            $site = $sitemapRequest->request->getAttribute('site');
            $rootPageId = $site->getRootPageId();
        }

        $excludePagesRecursive = GeneralUtility::intExplode(',', (string)($configuration['excludePagesRecursive'] ?? ''), true);

        $pageIds = $this->pageRepository->getDescendantPageIdsRecursive($rootPageId, 99, 0, $excludePagesRecursive);
        $pageIds = array_merge([$rootPageId], $pageIds);

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');

        $constraints = [
            $queryBuilder->expr()->in('uid', $pageIds),
        ];

        if (!empty($configuration['additionalWhere'])) {
            $constraints[] = QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), QueryHelper::stripLogicalOperatorPrefix($configuration['additionalWhere']));
        }

        if (!empty($configuration['excludedDoktypes'])) {
            $excludedDoktypes = GeneralUtility::intExplode(',', (string)$configuration['excludedDoktypes']);
            if (!empty($excludedDoktypes)) {
                $constraints[] = $queryBuilder->expr()->notIn('doktype', implode(',', $excludedDoktypes));
            }
        }
        $pages = $queryBuilder->select('*')
            ->from('pages')
            ->where(...$constraints)
            ->orderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return $pages;
    }

    protected function getCurrentLanguageAspect(): LanguageAspect
    {
        return $this->context->getAspect('language');
    }

    protected function defineUrl(array $data, XmlSitemapRequest $sitemapRequest): array
    {
        $typoLinkConfig = [
            'page' => $this->recordFactory->createFromDatabaseRow('pages', $data),
            'parameter' => $data['uid'],
            'forceAbsoluteUrl' => 1,
        ];

        $data['loc'] = $sitemapRequest->contentObjectRenderer->createUrl($typoLinkConfig);
        return $data;
    }
}
