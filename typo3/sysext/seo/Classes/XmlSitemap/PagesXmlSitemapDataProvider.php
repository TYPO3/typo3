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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class to generate a XML sitemap for pages
 * @internal this class is not part of TYPO3's Core API.
 */
class PagesXmlSitemapDataProvider extends AbstractXmlSitemapDataProvider
{
    public function __construct(ServerRequestInterface $request, string $key, array $config = [], ?ContentObjectRenderer $cObj = null)
    {
        parent::__construct($request, $key, $config, $cObj);

        $this->generateItems();
    }

    protected function generateItems(): void
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pages = $pageRepository->getPagesOverlay($this->getPages());
        $languageAspect = $this->getCurrentLanguageAspect();
        foreach ($pages as $page) {
            if (!$pageRepository->isPageSuitableForLanguage($page, $languageAspect)) {
                continue;
            }

            $this->items[] = $page + [
                'lastMod' => (int)($page['SYS_LASTCHANGED'] ?: $page['tstamp']),
                'changefreq' => $page['sitemap_changefreq'],
                'priority' => (float)$page['sitemap_priority'],
            ];
        }
    }

    protected function getPages(): array
    {
        if (!empty($this->config['rootPage'])) {
            $rootPageId = (int)$this->config['rootPage'];
        } else {
            $site = $this->request->getAttribute('site');
            $rootPageId = $site->getRootPageId();
        }

        $excludePagesRecursive = GeneralUtility::intExplode(',', (string)($this->config['excludePagesRecursive'] ?? ''), true);

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pageIds = $pageRepository->getDescendantPageIdsRecursive($rootPageId, 99, 0, $excludePagesRecursive);
        $pageIds = array_merge([$rootPageId], $pageIds);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');

        $constraints = [
            $queryBuilder->expr()->in('uid', $pageIds),
        ];

        if (!empty($this->config['additionalWhere'])) {
            $constraints[] = QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), QueryHelper::stripLogicalOperatorPrefix($this->config['additionalWhere']));
        }

        if (!empty($this->config['excludedDoktypes'])) {
            $excludedDoktypes = GeneralUtility::intExplode(',', (string)$this->config['excludedDoktypes']);
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
        return GeneralUtility::makeInstance(Context::class)->getAspect('language');
    }

    protected function defineUrl(array $data): array
    {
        $typoLinkConfig = [
            'page' => new Page($data),
            'parameter' => $data['uid'],
            'forceAbsoluteUrl' => 1,
        ];

        $data['loc'] = $this->cObj->createUrl($typoLinkConfig);
        return $data;
    }
}
