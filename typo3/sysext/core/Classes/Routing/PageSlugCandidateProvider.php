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

namespace TYPO3\CMS\Core\Routing;

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Routing\Enhancer\DecoratingEnhancerInterface;
use TYPO3\CMS\Core\Routing\Enhancer\EnhancerFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

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
     * @return array<int,array<string,mixed>>
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
            ->executeQuery();

        $page = $statement->fetchAssociative();
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
            static function (DecoratingEnhancerInterface $decorationEnhancers) {
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
     * @param array $excludeUids when called recursively this is the mountpoint parameter of the original prefix
     * @return array[]|array
     * @throws SiteNotFoundException
     */
    protected function getPagesFromDatabaseForCandidates(array $slugCandidates, int $languageId, array $excludeUids = []): array
    {
        $workspaceId = (int)$this->context->getPropertyFromAspect('workspace', 'id');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId));

        $statement = $queryBuilder
            ->select('uid', 'l10n_parent', 'pid', 'slug', 'mount_pid', 'mount_pid_ol', 't3ver_state', 'doktype', 't3ver_wsid', 't3ver_oid')
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
            // Sort pages that are not MountPoint pages before mount points
            ->addOrderBy('mount_pid_ol', 'asc')
            ->addOrderBy('mount_pid', 'asc')
            ->executeQuery();

        $pages = [];
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        $isRecursiveCall = !empty($excludeUids);

        while ($row = $statement->fetchAssociative()) {
            $mountPageInformation = null;
            $pageIdInDefaultLanguage = (int)($languageId > 0 ? $row['l10n_parent'] : $row['uid']);
            // When this page was added before via recursion, this page should be skipped
            if (in_array($pageIdInDefaultLanguage, $excludeUids, true)) {
                continue;
            }

            try {
                $isOnSameSite = $siteFinder->getSiteByPageId($pageIdInDefaultLanguage)->getRootPageId() === $this->site->getRootPageId();
            } catch (SiteNotFoundException $e) {
                // Page is not in a site, so it's not considered
                $isOnSameSite = false;
            }

            // If a MountPoint is found on the current site, and it hasn't been added yet by some other iteration
            // (see below "findPageCandidatesOfMountPoint"), then let's resolve the MountPoint information now
            if (!$isOnSameSite && $isRecursiveCall) {
                // Not in the same site, and called recursive, should be skipped
                continue;
            }
            $mountPageInformation = $pageRepository->getMountPointInfo($pageIdInDefaultLanguage, $row);

            // Mount Point Pages which are not on the same site (when not called on the first level) should be skipped
            // As they just clutter up the queries.
            if (!$isOnSameSite && !$isRecursiveCall && $mountPageInformation) {
                continue;
            }

            $mountedPage = null;
            if ($mountPageInformation) {
                // Add the MPvar to the row, so it can be used later-on in the PageRouter / PageArguments
                $row['MPvar'] = $mountPageInformation['MPvar'];
                $mountedPage = $pageRepository->getPage_noCheck($mountPageInformation['mount_pid_rec']['uid']);
                // Ensure to fetch the slug in the translated page
                $mountedPage = $pageRepository->getPageOverlay($mountedPage, $languageId);
                // Mount wasn't connected properly, so it is skipped
                if (!$mountedPage) {
                    continue;
                }
                // If the page is a MountPoint which should be overlaid with the contents of the mounted page,
                // it must never be accessible directly, but only in the MountPoint context. Therefore we change
                // the current ID and slug.
                // This needs to happen before the regular case, as the $pageToAdd contains the MPvar information
                if ((int)$row['doktype'] === PageRepository::DOKTYPE_MOUNTPOINT && $row['mount_pid_ol']) {
                    // If the mounted page was already added from above, this should not be added again (to include
                    // the mount point parameter).
                    if (in_array((int)$mountedPage['uid'], $excludeUids, true)) {
                        continue;
                    }
                    $pageToAdd = $mountedPage;
                    // Make sure target page "/about-us" is replaced by "/global-site/about-us" so router works
                    $pageToAdd['MPvar'] = $mountPageInformation['MPvar'];
                    $pageToAdd['slug'] = $row['slug'];
                    $pages[] = $pageToAdd;
                    $excludeUids[] = (int)$pageToAdd['uid'];
                    $excludeUids[] = $pageIdInDefaultLanguage;
                }
            }

            // This is the regular "non-MountPoint page" case (must happen after the if condition so MountPoint
            // pages that have been replaced by the Mounted Page will not be added again.
            if ($isOnSameSite && !in_array($pageIdInDefaultLanguage, $excludeUids, true)) {
                $pages[] = $row;
                $excludeUids[] = $pageIdInDefaultLanguage;
            }

            // Add possible sub-pages prepended with the MountPoint page slug
            if ($mountPageInformation) {
                /** @var array $mountedPage */
                $siteOfMountedPage = $siteFinder->getSiteByPageId((int)$mountedPage['uid']);
                $morePageCandidates = $this->findPageCandidatesOfMountPoint(
                    $row,
                    $mountedPage,
                    $siteOfMountedPage,
                    $languageId,
                    $slugCandidates
                );
                foreach ($morePageCandidates as $candidate) {
                    // When called previously this MountPoint page should be skipped
                    if (in_array((int)$candidate['uid'], $excludeUids, true)) {
                        continue;
                    }
                    $pages[] = $candidate;
                }
            }
        }
        return $pages;
    }

    /**
     * Check if the page candidate is a mount point, if so, we need to
     * re-start the slug candidates procedure with the mount point as a prefix (= context of the subpage).
     *
     * Before doing the slugCandidates are adapted to remove the slug of the mount point (actively moving the pointer
     * of the path to strip away the existing prefix), then checking for more pages.
     *
     * Once possible candidates are found, the slug prefix needs to be re-added so the PageRouter finds the page,
     * with an additional 'MPvar' attribute.
     * However, all page candidates needs to be checked if they are connected in the proper mount page.
     *
     * @param array $mountPointPage the page with doktype=7
     * @param array $mountedPage the target page where the mountpoint is pointing to
     * @param Site $siteOfMountedPage the site of the target page, which could be different from the current page
     * @param int $languageId the current language id
     * @param array $slugCandidates the existing slug candidates that were looked for previously
     * @return array more candidates
     */
    protected function findPageCandidatesOfMountPoint(
        array $mountPointPage,
        array $mountedPage,
        Site $siteOfMountedPage,
        int $languageId,
        array $slugCandidates
    ): array {
        $pages = [];
        $slugOfMountPoint = $mountPointPage['slug'] ?? '';
        $commonSlugPrefixOfMountedPage = rtrim($mountedPage['slug'] ?? '', '/');
        $narrowedDownSlugPrefixes = [];
        foreach ($slugCandidates as $slugCandidate) {
            // Remove the mount point prefix (that we just found) from the slug candidates
            if (strpos($slugCandidate, $slugOfMountPoint) === 0) {
                // Find pages without the common prefix
                $narrowedDownSlugPrefix = '/' . trim(substr($slugCandidate, strlen($slugOfMountPoint)), '/');
                $narrowedDownSlugPrefixes[] = $narrowedDownSlugPrefix;
                $narrowedDownSlugPrefixes[] = $narrowedDownSlugPrefix . '/';
                // Find pages with the prefix of the mounted page as well
                if ($commonSlugPrefixOfMountedPage) {
                    $narrowedDownSlugPrefix = $commonSlugPrefixOfMountedPage . $narrowedDownSlugPrefix;
                    $narrowedDownSlugPrefixes[] = $narrowedDownSlugPrefix;
                    $narrowedDownSlugPrefixes[] = $narrowedDownSlugPrefix . '/';
                }
            }
        }
        $trimmedSlugPrefixes = [];
        $narrowedDownSlugPrefixes = array_unique($narrowedDownSlugPrefixes);
        foreach ($narrowedDownSlugPrefixes as $narrowedDownSlugPrefix) {
            $narrowedDownSlugPrefix = trim($narrowedDownSlugPrefix, '/');
            $trimmedSlugPrefixes[] = '/' . $narrowedDownSlugPrefix;
            if (!empty($narrowedDownSlugPrefix)) {
                $trimmedSlugPrefixes[] = '/' . $narrowedDownSlugPrefix . '/';
            }
        }
        $trimmedSlugPrefixes = array_unique($trimmedSlugPrefixes);
        rsort($trimmedSlugPrefixes);

        $slugProviderForMountPage = GeneralUtility::makeInstance(static::class, $this->context, $siteOfMountedPage, $this->enhancerFactory);
        // Find the right pages for which have been matched
        $excludedPageIds = [(int)$mountPointPage['uid']];
        $pageCandidates = $slugProviderForMountPage->getPagesFromDatabaseForCandidates(
            $trimmedSlugPrefixes,
            $languageId,
            $excludedPageIds
        );
        // Depending on the "mount_pid_ol" parameter, the mountedPage or the mounted page is in the rootline
        $pageWhichMustBeInRootLine = (int)($mountPointPage['mount_pid_ol'] ? $mountedPage['uid'] : $mountPointPage['uid']);
        foreach ($pageCandidates as $pageCandidate) {
            if (!$pageCandidate['mount_pid_ol']) {
                $pageCandidate['MPvar'] = !empty($pageCandidate['MPvar'])
                    ? $mountPointPage['MPvar'] . ',' . $pageCandidate['MPvar']
                    : $mountPointPage['MPvar'];
            }
            // In order to avoid the possibility that any random page like /about-us which is not connected to the mount
            // point is not possible to be called via /my-mount-point/about-us, let's check the
            $pageCandidateIsConnectedInMountPoint = false;
            $rootLine = GeneralUtility::makeInstance(
                RootlineUtility::class,
                $pageCandidate['uid'],
                $pageCandidate['MPvar'],
                $this->context
            )->get();
            foreach ($rootLine as $pageInRootLine) {
                if ((int)$pageInRootLine['uid'] === $pageWhichMustBeInRootLine) {
                    $pageCandidateIsConnectedInMountPoint = true;
                    break;
                }
            }
            if ($pageCandidateIsConnectedInMountPoint === false) {
                continue;
            }
            // Rewrite the slug of the subpage to match the PageRouter matching again
            // This is done by first removing the "common" prefix possibly provided by the Mounted Page
            // But more importantly adding the $slugOfMountPoint of the MountPoint Page
            $slugOfSubpage = $pageCandidate['slug'];
            if ($commonSlugPrefixOfMountedPage && strpos($slugOfSubpage, $commonSlugPrefixOfMountedPage) === 0) {
                $slugOfSubpage = substr($slugOfSubpage, strlen($commonSlugPrefixOfMountedPage));
            }
            $pageCandidate['slug'] = $slugOfMountPoint . (($slugOfSubpage && $slugOfSubpage !== '/') ? '/' . trim($slugOfSubpage, '/') : '');
            $pages[] = $pageCandidate;
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
            $routePath = preg_replace('#' . $decorationPattern . '$#', '', $routePath) ?? '';
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
