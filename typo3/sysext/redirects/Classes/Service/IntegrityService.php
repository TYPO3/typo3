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

namespace TYPO3\CMS\Redirects\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Checks for redirects that conflict with existing pages
 */
class IntegrityService
{
    /**
     * @var RedirectService
     */
    private $redirectService;

    /**
     * @var SiteFinder
     */
    private $siteFinder;

    public function __construct(RedirectService $redirectService = null, SiteFinder $siteFinder = null)
    {
        $this->siteFinder = $siteFinder ?? GeneralUtility::makeInstance(SiteFinder::class);
        $this->redirectService = $redirectService ?? GeneralUtility::makeInstance(
            RedirectService::class,
            GeneralUtility::makeInstance(RedirectCacheService::class),
            GeneralUtility::makeInstance(LinkService::class),
            $this->siteFinder
        );
    }

    /**
     * Resolves all conflicting redirects
     */
    public function findConflictingRedirects(string $siteIdentifier = null): \Generator
    {
        foreach ($this->getSites($siteIdentifier) as $site) {
            $entryPoints = $this->getAllEntryPointsForSite($site);
            $pages = $this->getAllSlugsForSite($site);

            foreach ($entryPoints as $entryPoint) {
                foreach ($pages as $slug) {
                    if ($slug !== null) {
                        $uri = new Uri(rtrim($entryPoint, '/') . '/' . ltrim($slug, '/'));
                        $matchingRedirect = $this->getMatchingRedirectByUri($uri);
                        if ($matchingRedirect !== null) {
                            yield [
                                'uri' => (string)$uri,
                                'redirect' => [
                                    'source_host' => $matchingRedirect['source_host'],
                                    'source_path' => $matchingRedirect['source_path'],
                                ]
                            ];
                        }
                    }
                }
            }
        }
    }

    private function getMatchingRedirectByUri(Uri $uri): ?array
    {
        $port = $uri->getPort();
        $domain = $uri->getHost() . ($port ? ':' . $port : '');
        return $this->redirectService->matchRedirect($domain, $uri->getPath());
    }

    /**
     * @param string|null $siteIdentifier
     * @return Site[]
     */
    private function getSites(?string $siteIdentifier): array
    {
        if ($siteIdentifier !== null) {
            return [$this->siteFinder->getSiteByIdentifier($siteIdentifier)];
        }

        return $this->siteFinder->getAllSites();
    }

    /**
     * Returns a list of all entry points for a site which is a combination of the site's base including its language bases
     */
    private function getAllEntryPointsForSite(Site $site): array
    {
        return array_map(static function (SiteLanguage $language): string {
            return (string)$language->getBase();
        }, $site->getLanguages());
    }

    /**
     * Generates a list of all slugs used in a site
     */
    private function getAllSlugsForSite(Site $site): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages')
            ->select('slug')
            ->from('pages');

        $queryBuilder->where(
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($site->getRootPageId(), \PDO::PARAM_INT))
        );
        $row = $queryBuilder->execute()->fetch();

        $subPages = $this->getSlugsOfSubPages($site->getRootPageId());
        $pages = array_merge([$site->getBase()->getPath() . '/', $row['slug']], $subPages);
        return array_unique($pages);
    }

    /**
     * Resolves the sub tree of a page and returns its slugs
     */
    private function getSlugsOfSubPages(int $pageId): array
    {
        $pages = [[]];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages')
            ->select('uid', 'slug')
            ->from('pages');

        $queryBuilder->where(
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT))
        );
        $result = $queryBuilder->execute();

        while ($row = $result->fetch()) {
            $pages[] = [$row['slug']];
            $pages[] = $this->getSlugsOfSubPages((int)$row['uid']);
        }

        return array_merge(...$pages);
    }
}
