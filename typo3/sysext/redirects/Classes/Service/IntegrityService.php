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
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Event\AfterPageUrlsForSiteForRedirectIntegrityHaveBeenCollectedEvent;
use TYPO3\CMS\Redirects\Event\RedirectIntegrityCheckEvent;
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
                            'target' => $matchingRedirect['target'],
                            'uid' => $matchingRedirect['uid'],
                        ],
                    ];
                }
            }
        }
    }

    /**
     * Resolves all redirects which can never match, because another redirect with the same source
     * is matched first whenever the redirect itself would be active.
     */
    public function findShadowedRedirects(): \Generator
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class))
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder
            ->select('uid', 'source_host', 'source_path', 'is_regexp', 'respect_query_parameters', 'starttime', 'endtime', 'target')
            ->from('sys_redirect')
            ->orderBy('uid', 'asc')
            ->executeQuery()
            ->fetchAllAssociative();

        $groups = [];
        foreach ($rows as $row) {
            [$matchKey, $priority] = $this->getMatchKeyAndPriority($row);
            $groups[$matchKey][$priority][] = $row;
        }

        foreach ($groups as $groupedByPriority) {
            ksort($groupedByPriority);
            $redirectsInMatchingOrder = array_merge(...$groupedByPriority);
            foreach ($redirectsInMatchingOrder as $index => $redirect) {
                for ($previousIndex = 0; $previousIndex < $index; $previousIndex++) {
                    $previousRedirect = $redirectsInMatchingOrder[$previousIndex];
                    if ($this->isActiveWhenever($previousRedirect, $redirect)) {
                        yield [
                            'uri' => $redirect['target'],
                            'redirect' => [
                                'integrity_status' => RedirectConflict::SHADOWED,
                                'source_host' => $redirect['source_host'],
                                'source_path' => $redirect['source_path'],
                                'target' => $redirect['target'],
                                'uid' => (int)$redirect['uid'],
                                'shadowed_by' => (int)$previousRedirect['uid'],
                            ],
                        ];
                        break;
                    }
                }
            }
        }
    }

    /**
     * Checks all redirects  by dispatching a PSR-14 event for each record,
     * allowing listeners to validate targets and flag broken redirects.
     */
    public function checkRedirectIntegrity(): \Generator
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $result = $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $event = $this->eventDispatcher->dispatch(
                new RedirectIntegrityCheckEvent($row)
            );
            if ($event->getIntegrityStatus() !== null
                && $event->getIntegrityStatus() !== RedirectConflict::NO_CONFLICT
            ) {
                yield [
                    'uri' => $row['target'] ?? '',
                    'redirect' => [
                        'integrity_status' => $event->getIntegrityStatus(),
                        'source_host' => $row['source_host'],
                        'source_path' => $row['source_path'],
                        'uid' => $row['uid'],
                    ],
                ];
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

    /**
     * Resets the integrity status of all redirects which are not listed in $conflictingUids,
     * except those with a status listed in $statusesToKeep.
     *
     * @param int[] $conflictingUids
     * @param string[] $statusesToKeep
     */
    public function resetIntegrityStatus(array $conflictingUids, array $statusesToKeep = []): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder
            ->select('uid', 'integrity_status')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->neq('integrity_status', $queryBuilder->createNamedParameter(RedirectConflict::NO_CONFLICT))
            )
            ->executeQuery()
            ->fetchAllAssociative();
        foreach ($rows as $row) {
            if (in_array((int)$row['uid'], $conflictingUids, true)
                || in_array($row['integrity_status'], $statusesToKeep, true)
            ) {
                continue;
            }
            $this->setIntegrityStatus([
                'uid' => (int)$row['uid'],
                'integrity_status' => RedirectConflict::NO_CONFLICT,
            ]);
        }
    }

    /**
     * Redirects with the same match key compete for the same requests in {@see RedirectService::matchRedirect()},
     * where redirects with a lower priority are checked first, followed by the lower uid.
     *
     * @return array{0: string, 1: int}
     */
    private function getMatchKeyAndPriority(array $redirect): array
    {
        $sourceHost = $redirect['source_host'] === '' ? '*' : $redirect['source_host'];
        $sourcePath = (string)$redirect['source_path'];
        $respectQueryParameters = (bool)$redirect['respect_query_parameters'];
        if ($redirect['is_regexp']) {
            return [$sourceHost . '|regexp|' . (int)$respectQueryParameters . '|' . $sourcePath, 0];
        }
        if (!$respectQueryParameters) {
            return [$sourceHost . '|flat|' . rtrim($sourcePath, '/') . '/', 0];
        }
        if (!str_contains($sourcePath, '?')) {
            return [$sourceHost . '|query|' . $sourcePath, 0];
        }
        // A source path with a slash before the query is only checked if none without the slash exists.
        [$path, $query] = explode('?', $sourcePath, 2);
        $trimmedPath = rtrim($path, '/');
        return [$sourceHost . '|query|' . $trimmedPath . '?' . $query, $trimmedPath === $path ? 0 : 1];
    }

    /**
     * Whether $redirect is active whenever $otherRedirect is active, considering starttime and endtime.
     */
    private function isActiveWhenever(array $redirect, array $otherRedirect): bool
    {
        if ((int)$redirect['starttime'] > (int)$otherRedirect['starttime']) {
            return false;
        }
        if ((int)$redirect['endtime'] === 0) {
            return true;
        }
        return (int)$otherRedirect['endtime'] !== 0 && (int)$redirect['endtime'] >= (int)$otherRedirect['endtime'];
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
