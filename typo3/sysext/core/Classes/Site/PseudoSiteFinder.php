<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Site;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\PseudoSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Compatibility\LegacyDomainResolver;

/**
 * Methods related to "pseudo-sites" = sites that do not have a configuration yet.
 * @internal this class will likely be removed in TYPO3 v10.0. Please use SiteMatcher and not the PseudoSiteFinder directly to make use of caching etc.
 */
class PseudoSiteFinder
{
    /**
     * @var string
     */
    protected $cacheIdentifier = 'pseudo-sites';

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @var PseudoSite[]
     */
    protected $pseudoSites = [];

    public function __construct()
    {
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core');
    }

    /**
     * Fetches all site root pages, all sys_language and sys_domain records and forms pseudo-sites,
     * but only for the pagetree's that do not have a site configuration available.
     */
    protected function populate(bool $allowCaching = true)
    {
        $data = $this->cache->get($this->cacheIdentifier);
        if (empty($data) || $allowCaching === false) {
            $allLanguages = $this->getAllLanguageRecords();
            $groupedDomains = GeneralUtility::makeInstance(LegacyDomainResolver::class)->getGroupedDomainsPerPage();
            $availablePages = $this->getAllRootPagesWithoutSiteConfiguration();
            $this->cache->set($this->cacheIdentifier, json_encode([$allLanguages, $groupedDomains, $availablePages]));
        } else {
            // Due to the nature of PhpFrontend, the `<?php` and `#` wraps have to be removed
            $data = preg_replace('/^<\?php\s*|\s*#$/', '', $data);
            list($allLanguages, $groupedDomains, $availablePages) = json_decode($data, true);
        }

        $this->pseudoSites = [];
        foreach ($availablePages as $row) {
            $rootPageId = (int)$row['uid'];
            $site = new PseudoSite($rootPageId, [
                'domains' => $groupedDomains[$rootPageId] ?? [],
                'languages' => $allLanguages
            ]);
            unset($groupedDomains[$rootPageId]);
            $this->pseudoSites[$rootPageId] = $site;
        }

        // Now add the records where there is a sys_domain record but not configured as root page
        foreach ($groupedDomains as $rootPageId => $domainRecords) {
            $site = new PseudoSite((int)$rootPageId, [
                'domains' => $domainRecords,
                'languages' => $allLanguages
            ]);
            $this->pseudoSites[(int)$rootPageId] = $site;
        }

        // Now lets an empty Pseudo-Site for visiting things on pid=0
        $this->pseudoSites[0] = new NullSite($allLanguages);
    }

    /**
     * Returns all pseudo sites, including one for "pid=0".
     *
     * @return PseudoSite[]
     */
    public function findAll(): array
    {
        if (empty($this->pseudoSites)) {
            $this->populate();
        }
        return $this->pseudoSites;
    }

    /**
     * Rebuild the cache information from the database information.
     *
     * @internal
     */
    public function refresh()
    {
        $this->populate(false);
    }

    /**
     * Fetches all "sys_language" records.
     *
     * @return array
     */
    protected function getAllLanguageRecords(): array
    {
        $languageRecords = [
            0 => [
                'languageId' => 0,
                'title' => 'Default',
                'flag' => '',
            ],
        ];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $statement = $queryBuilder
            ->select('*')
            ->from('sys_language')
            ->orderBy('sorting')
            ->execute();
        while ($row = $statement->fetch()) {
            $uid = (int)$row['uid'];
            $languageRecords[$uid] = [
                'languageId' => $uid,
                'title' => $row['title'],
                'iso-639-1' => $row['language_isocode'] ?? '',
                'flag' => 'flags-' . $row['flag'],
                'enabled' => !$row['hidden'],
            ];
        }

        return $languageRecords;
    }

    /**
     * Traverses the rootline of a page up until a PseudoSite was found.
     * The main use-case here is in the TYPO3 Backend when the middleware tries to detect
     * a PseudoSite
     *
     * @param int $pageId
     * @param array $rootLine
     * @return SiteInterface
     * @throws SiteNotFoundException
     */
    public function getSiteByPageId(int $pageId, array $rootLine = null): SiteInterface
    {
        $this->findAll();
        if (isset($this->pseudoSites[$pageId])) {
            return $this->pseudoSites[$pageId];
        }
        if (!is_array($rootLine)) {
            try {
                $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
            } catch (PageNotFoundException $e) {
                $rootLine = [];
            }
        }
        foreach ($rootLine as $pageInRootLine) {
            if (isset($this->pseudoSites[(int)$pageInRootLine['uid']])) {
                return $this->pseudoSites[(int)$pageInRootLine['uid']];
            }
        }
        throw new SiteNotFoundException('No pseudo-site found in root line of page ' . $pageId, 1534710048);
    }

    /**
     * Find a site by given root page id
     *
     * @param int $rootPageId the page ID (default language)
     * @return SiteInterface
     * @throws SiteNotFoundException
     */
    public function getSiteByRootPageId(int $rootPageId): SiteInterface
    {
        if (empty($this->pseudoSites)) {
            $this->populate();
        }
        if (isset($this->pseudoSites[$rootPageId])) {
            return $this->pseudoSites[$rootPageId];
        }
        throw new SiteNotFoundException('No pseudo-site found for root page id ' . $rootPageId, 1521668982);
    }

    /**
     * Loads all sites with a configuration, and takes their rootPageId.
     *
     * @return array
     */
    protected function getExistingSiteConfigurationRootPageIds(): array
    {
        $usedPageIds = [];
        $finder = GeneralUtility::makeInstance(SiteFinder::class);
        $sites = $finder->getAllSites();
        foreach ($sites as $site) {
            $usedPageIds[] = $site->getRootPageId();
        }
        return $usedPageIds;
    }

    /**
     * Do a SQL query for root pages (pid=0 or is_siteroot=1) that do not have a site configuration
     * @return array
     */
    protected function getAllRootPagesWithoutSiteConfiguration(): array
    {
        $usedPageIds = $this->getExistingSiteConfigurationRootPageIds();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(FrontendWorkspaceRestriction::class, 0, false));
        $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('pid', 0),
                    $queryBuilder->expr()->eq('is_siteroot', 1)
                )
            )
            ->orderBy('pid')
            ->addOrderBy('sorting');

        if (!empty($usedPageIds)) {
            $queryBuilder->andWhere($queryBuilder->expr()->notIn('uid', $usedPageIds));
        }
        $availablePages = $queryBuilder->execute()->fetchAll();
        return is_array($availablePages) ? $availablePages : [];
    }
}
