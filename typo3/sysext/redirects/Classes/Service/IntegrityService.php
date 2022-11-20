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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\Entity\Site;
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
    public function findConflictingRedirects(?string $siteIdentifier = null): \Generator
    {
        foreach ($this->getSites($siteIdentifier) as $site) {
            // Collect page urls for all pages and languages for $site.
            $urls = $this->getAllPageUrlsForSite($site);
            foreach ($urls as $url) {
                $uri = new Uri($url);
                $matchingRedirect = $this->getMatchingRedirectByUri($uri);
                if ($matchingRedirect !== null) {
                    // @todo Returning information should be improved in future to give more useful information in
                    //       command output and report output, for example redirect uid, page/language details, which would
                    //       make the life easier for using the command and finding the conflicts.
                    yield [
                        'uri' => (string)$uri,
                        'redirect' => [
                            'source_host' => $matchingRedirect['source_host'],
                            'source_path' => $matchingRedirect['source_path'],
                        ],
                    ];
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
     * Generates a list of all slugs used in a site
     */
    private function getAllPageUrlsForSite(Site $site): array
    {
        $pageUrls = [];

        // language bases - redirects would be nasty, but should be checked also. We do not need to add site base
        // here, as there is always at least one default language.
        foreach ($site->getLanguages() as $siteLanguage) {
            $pageUrls[] = rtrim((string)$siteLanguage->getBase(), '/') . '/';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages')
            ->select('slug', $this->getPagesLanguageFieldName())
            ->from('pages');

        $queryBuilder->where(
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($site->getRootPageId(), Connection::PARAM_INT)),
                $queryBuilder->expr()->eq($this->getPagesLanguageParentFieldName(), $queryBuilder->createNamedParameter($site->getRootPageId(), Connection::PARAM_INT)),
            )
        );
        $result = $queryBuilder->executeQuery();

        while ($row = $result->fetchAssociative()) {
            // @todo Considering only page slug is not complete, as it does not match redirects with file extension,
            //       for ex. if PageTypeSuffix routeEnhancer are used and redirects are created based on that.
            $slug = ltrim(($row['slug'] ?? ''), '/');
            $lang = (int)($row[$this->getPagesLanguageFieldName()] ?? 0);
            $siteLanguage = $site->getLanguageById($lang);

            // empty slug root pages has been already handled with language bases above, thus skip them here.
            if (empty($slug)) {
                continue;
            }

            $pageUrls[] = rtrim((string)$siteLanguage->getBase(), '/') . '/' . $slug;
        }

        $subPageUrls = $this->getSlugsOfSubPages($site->getRootPageId(), $site);
        $pageUrls = array_merge($pageUrls, $subPageUrls);
        return array_unique($pageUrls);
    }

    /**
     * Resolves the sub tree of a page and returns its slugs for language
     * $languageId
     */
    private function getSlugsOfSubPages(int $pageId, Site $site): array
    {
        $pageUrls = [[]];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages')
            ->select('uid', 'slug', $this->getPagesLanguageFieldName())
            ->from('pages');

        $queryBuilder->where(
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
        );
        $result = $queryBuilder->executeQuery();

        while ($row = $result->fetchAssociative()) {
            // @todo Considering only page slug is not complete, as it does not matches redirects with file extension,
            //       for ex. if PageTypeSuffix routeEnhancer are used and redirects are created based on that.
            $slug = ltrim($row['slug'] ?? '', '/');
            $lang = (int)($row[$this->getPagesLanguageFieldName()] ?? 0);
            $siteLanguage = $site->getLanguageById($lang);

            // empty slugs should to occur here, but to be sure we skip them here, as they were already handled.
            if (empty($slug)) {
                continue;
            }

            $pageUrls[] = [rtrim((string)$siteLanguage->getBase(), '/') . '/' . $slug];

            // only traverse for pages of default language (as even translated pages contain pid of parent in default language)
            if ($lang === 0) {
                $pageUrls[] = $this->getSlugsOfSubPages((int)$row['uid'], $site);
            }
        }
        return array_merge(...$pageUrls);
    }

    private function getPagesLanguageFieldName(): string
    {
        return $GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? 'sys_language_uid';
    }

    private function getPagesLanguageParentFieldName(): string
    {
        return $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? 'l10n_parent';
    }
}
