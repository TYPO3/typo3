<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing;

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

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Routing\Enhancer\DecoratingEnhancerInterface;
use TYPO3\CMS\Core\Routing\Enhancer\EnhancerFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides possible pages (from the database) that _could_ match a certain URL path,
 * but also works for fetching the best "slug" value for multi-lingual pages with a specific language requested.
 *
 * @internal as this API might change and a possible interface is given at some point.
 */
class PageSlugCandidateProvider
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var EnhancerFactory
     */
    protected $enhancerFactory;

    public function __construct(Context $context, Site $site, ?EnhancerFactory $enhancerFactory)
    {
        $this->context = $context;
        $this->site = $site;
        $this->enhancerFactory = $enhancerFactory ?? GeneralUtility::makeInstance(EnhancerFactory::class);
    }

    /**
     * Fetches an array of possible URLs that match the current site + language (incl. fallbacks)
     *
     * @param string $urlPath
     * @param SiteLanguage $language
     * @return string[]
     */
    public function getCandidatesForPath(string $urlPath, SiteLanguage $language): array
    {
        $slugCandidates = $this->getCandidateSlugsFromRoutePath($urlPath ?: '/');
        $pageCandidates = [];
        $languages = [$language->getLanguageId()];
        if (!empty($language->getFallbackLanguageIds())) {
            $languages = array_merge($languages, $language->getFallbackLanguageIds());
        }
        // Iterate all defined languages in their configured order to get matching page candidates somewhere in the language fallback chain
        foreach ($languages as $languageId) {
            $pageCandidatesFromSlugsAndLanguage = $this->getPagesFromDatabaseForCandidates($slugCandidates, $languageId);
            // Determine whether fetched page candidates qualify for the request. The incoming URL is checked against all
            // pages found for the current URL and language.
            foreach ($pageCandidatesFromSlugsAndLanguage as $candidate) {
                $slugCandidate = '/' . trim($candidate['slug'], '/');
                if ($slugCandidate === '/' || strpos($urlPath, $slugCandidate) === 0) {
                    // The slug is a subpart of the requested URL, so it's a possible candidate
                    if ($urlPath === $slugCandidate) {
                        // The requested URL matches exactly the found slug. We can't find a better match,
                        // so use that page candidate and stop any further querying.
                        $pageCandidates = [$candidate];
                        break 2;
                    }

                    $pageCandidates[] = $candidate;
                }
            }
        }
        return $pageCandidates;
    }

    /**
     * Fetches the page without any language or other hidden/enable fields, but only takes
     * "deleted" and "workspace" into account, as all other things will be evaluated later.
     *
     * This is only needed for resolving the ACTUAL Page Id when index.php?id=13 was given
     *
     * Should be rebuilt to return the actual Page ID considering the online ID of the page.
     *
     * @param int $pageId
     * @return int|null
     */
    public function getRealPageIdForPageIdAsPossibleCandidate(int $pageId): ?int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(FrontendWorkspaceRestriction::class));

        $statement = $queryBuilder
            ->select('uid', 'l10n_parent')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->execute();

        $page = $statement->fetch();
        if (empty($page)) {
            return null;
        }
        return (int)($page['l10n_parent'] ?: $page['uid']);
    }

    /**
     * Gets all patterns that can be used to redecorate (undecorate) a
     * potential previously decorated route path.
     *
     * @return string regular expression pattern capable of redecorating
     */
    protected function getRoutePathRedecorationPattern(): string
    {
        $decoratingEnhancers = $this->getDecoratingEnhancers();
        if (empty($decoratingEnhancers)) {
            return '';
        }
        $redecorationPatterns = array_map(
            function (DecoratingEnhancerInterface $decorationEnhancers) {
                $pattern = $decorationEnhancers->getRoutePathRedecorationPattern();
                return '(?:' . $pattern . ')';
            },
            $decoratingEnhancers
        );
        return '(?P<decoration>' . implode('|', $redecorationPatterns) . ')';
    }

    /**
     * Resolves decorating enhancers without having aspects assigned. These
     * instances are used to pre-process URL path and MUST NOT be used for
     * actually resolving or generating URL parameters.
     *
     * @return DecoratingEnhancerInterface[]
     */
    protected function getDecoratingEnhancers(): array
    {
        $enhancers = [];
        foreach ($this->site->getConfiguration()['routeEnhancers'] ?? [] as $enhancerConfiguration) {
            $enhancerType = $enhancerConfiguration['type'] ?? '';
            $enhancer = $this->enhancerFactory->create($enhancerType, $enhancerConfiguration);
            if ($enhancer instanceof DecoratingEnhancerInterface) {
                $enhancers[] = $enhancer;
            }
        }
        return $enhancers;
    }

    /**
     * Check for records in the database which matches one of the slug candidates.
     *
     * @param array $slugCandidates
     * @param int $languageId
     * @return array[]|array
     */
    protected function getPagesFromDatabaseForCandidates(array $slugCandidates, int $languageId): array
    {
        $searchLiveRecordsOnly = $this->context->getPropertyFromAspect('workspace', 'isLive');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(FrontendWorkspaceRestriction::class, null, null, $searchLiveRecordsOnly));

        $statement = $queryBuilder
            ->select('uid', 'l10n_parent', 'pid', 'slug')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->in(
                    'slug',
                    $queryBuilder->createNamedParameter(
                        $slugCandidates,
                        Connection::PARAM_STR_ARRAY
                    )
                )
            )
            // Exact match will be first, that's important
            ->orderBy('slug', 'desc')
            ->execute();

        $pages = [];
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        while ($row = $statement->fetch()) {
            $pageRepository->fixVersioningPid('pages', $row);
            $pageIdInDefaultLanguage = (int)($languageId > 0 ? $row['l10n_parent'] : $row['uid']);
            try {
                if ($siteFinder->getSiteByPageId($pageIdInDefaultLanguage)->getRootPageId() === $this->site->getRootPageId()) {
                    $pages[] = $row;
                }
            } catch (SiteNotFoundException $e) {
                // Page is not in a site, so it's not considered
            }
        }
        return $pages;
    }

    /**
     * Returns possible URL parts for a string like /home/about-us/offices/ or /home/about-us/offices.json
     * to return.
     *
     * /home/about-us/offices/
     * /home/about-us/offices.json
     * /home/about-us/offices
     * /home/about-us/
     * /home/about-us
     * /home/
     * /home
     * /
     *
     * @param string $routePath
     * @return string[]
     */
    protected function getCandidateSlugsFromRoutePath(string $routePath): array
    {
        $redecorationPattern = $this->getRoutePathRedecorationPattern();
        if (!empty($redecorationPattern) && preg_match('#' . $redecorationPattern . '#', $routePath, $matches)) {
            $decoration = $matches['decoration'];
            $decorationPattern = preg_quote($decoration, '#');
            $routePath = preg_replace('#' . $decorationPattern . '$#', '', $routePath);
        }

        $candidatePathParts = [];
        $pathParts = GeneralUtility::trimExplode('/', $routePath, true);
        if (empty($pathParts)) {
            return ['/'];
        }

        while (!empty($pathParts)) {
            $prefix = '/' . implode('/', $pathParts);
            $candidatePathParts[] = $prefix . '/';
            $candidatePathParts[] = $prefix;
            array_pop($pathParts);
        }
        $candidatePathParts[] = '/';
        return $candidatePathParts;
    }
}
