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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Redirects\Event\AfterPageUrlsForSiteForRedirectIntegrityHaveBeenCollectedEvent;
use TYPO3\CMS\Redirects\Utility\RedirectConflict;

/**
 * Checks for redirects that conflict with existing pages
 */
readonly class IntegrityService
{
    public function __construct(
        private RedirectService $redirectService,
        private SiteFinder $siteFinder,
        private ConnectionPool $connectionPool,
        private EventDispatcherInterface $eventDispatcher,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

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
                            'integrity_status' => RedirectConflict::SELF_REFERENCE,
                            'source_host' => $matchingRedirect['source_host'],
                            'source_path' => $matchingRedirect['source_path'],
                            'uid' => $matchingRedirect['uid'],
                        ],
                    ];
                }
            }
        }
    }

    public function setIntegrityStatus(array $redirect): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $queryBuilder
            ->update('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($redirect['uid'], Connection::PARAM_INT))
            )
            ->set('integrity_status', $redirect['integrity_status'])
            ->executeStatement();
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
        $schema = $this->tcaSchemaFactory->get('pages');
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $pageUrls = [];

        // language bases - redirects would be nasty, but should be checked also. We do not need to add site base
        // here, as there is always at least one default language.
        foreach ($site->getLanguages() as $siteLanguage) {
            $pageUrls[] = rtrim((string)$siteLanguage->getBase(), '/') . '/';
        }

        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('pages')
            ->select('slug', $languageCapability->getLanguageField()->getName())
            ->from('pages');

        $queryBuilder->where(
            $queryBuilder->expr()->or(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($site->getRootPageId(), Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $languageCapability->getTranslationOriginPointerField()->getName(),
                    $queryBuilder->createNamedParameter($site->getRootPageId(), Connection::PARAM_INT)
                ),
            )
        );
        $result = $queryBuilder->executeQuery();

        while ($row = $result->fetchAssociative()) {
            // @todo Considering only page slug is not complete, as it does not match redirects with file extension,
            //       for ex. if PageTypeSuffix routeEnhancer are used and redirects are created based on that.
            $slug = ltrim(($row['slug'] ?? ''), '/');
            $language = $row[$languageCapability->getLanguageField()->getName()];
            try {
                $siteLanguage = $site->getLanguageById($language);
            } catch (\InvalidArgumentException) {
                // skip invalid languages which might occur due to previous changes in site configuration
                continue;
            }

            // empty slug root pages has been already handled with language bases above, thus skip them here.
            if ($slug === '') {
                continue;
            }

            $pageUrls[] = rtrim((string)$siteLanguage->getBase(), '/') . '/' . $slug;
        }

        $pageUrls = $this->eventDispatcher->dispatch(
            new AfterPageUrlsForSiteForRedirectIntegrityHaveBeenCollectedEvent($site, $pageUrls)
        )->getPageUrls();

        return array_unique($pageUrls);
    }
}
