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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\Aspect\AspectFactory;
use TYPO3\CMS\Core\Routing\Aspect\MappableProcessor;
use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;
use TYPO3\CMS\Core\Routing\Enhancer\DecoratingEnhancerInterface;
use TYPO3\CMS\Core\Routing\Enhancer\EnhancerFactory;
use TYPO3\CMS\Core\Routing\Enhancer\EnhancerInterface;
use TYPO3\CMS\Core\Routing\Enhancer\InflatableEnhancerInterface;
use TYPO3\CMS\Core\Routing\Enhancer\ResultingInterface;
use TYPO3\CMS\Core\Routing\Enhancer\RoutingEnhancerInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Page Router - responsible for a page based on a request, by looking up the slug of the page path.
 * Is also used for generating URLs for pages.
 *
 * Resolving is done via the "Route Candidate" pattern.
 *
 * Example:
 * - /about-us/team/management/
 *
 * will look for all pages that have
 * - /about-us
 * - /about-us/
 * - /about-us/team
 * - /about-us/team/
 * - /about-us/team/management
 * - /about-us/team/management/
 *
 * And create route candidates for that.
 *
 * Please note: PageRouter does not restrict the HTTP method or is bound to any domain constraints,
 * as the SiteMatcher has done that already.
 *
 * The concept of the PageRouter is to *resolve*, and to *generate* URIs. On top, it is a facade to hide the
 * dependency to symfony and to not expose its logic.
 */
class PageRouter implements RouterInterface
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var EnhancerFactory
     */
    protected $enhancerFactory;

    /**
     * @var AspectFactory
     */
    protected $aspectFactory;

    /**
     * @var CacheHashCalculator
     */
    protected $cacheHashCalculator;

    /**
     * @var \TYPO3\CMS\Core\Context\Context|null
     */
    protected $context;

    /**
     * A page router is always bound to a specific site.
     *
     * @param Site $site
     * @param \TYPO3\CMS\Core\Context\Context|null $context
     */
    public function __construct(Site $site, Context $context = null)
    {
        $this->site = $site;
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->enhancerFactory = GeneralUtility::makeInstance(EnhancerFactory::class);
        $this->aspectFactory = GeneralUtility::makeInstance(AspectFactory::class, $this->context);
        $this->cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
    }

    /**
     * Finds a RouteResult based on the given request.
     *
     * @param ServerRequestInterface $request
     * @param RouteResultInterface|SiteRouteResult|null $previousResult
     * @return SiteRouteResult
     * @throws RouteNotFoundException
     */
    public function matchRequest(ServerRequestInterface $request, RouteResultInterface $previousResult = null): RouteResultInterface
    {
        if (!($previousResult instanceof RouteResultInterface)) {
            throw new RouteNotFoundException('No previous result given. Cannot find a page for an empty route part', 1555303496);
        }
        $urlPath = $previousResult->getTail();
        // Remove the script name (e.g. index.php), if given
        if (!empty($urlPath)) {
            $normalizedParams = $request->getAttribute('normalizedParams');
            if ($normalizedParams instanceof NormalizedParams) {
                $scriptName = ltrim($normalizedParams->getScriptName(), '/');
                if ($scriptName !== '' && strpos($urlPath, $scriptName) !== false) {
                    $urlPath = str_replace($scriptName, '', $urlPath);
                }
            }
        }

        $prefixedUrlPath = '/' . trim($urlPath, '/');
        $slugCandidates = $this->getCandidateSlugsFromRoutePath($urlPath ?: '/');
        $pageCandidates = [];
        $language = $previousResult->getLanguage();
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
                if ($slugCandidate === '/' || strpos($prefixedUrlPath, $slugCandidate) === 0) {
                    // The slug is a subpart of the requested URL, so it's a possible candidate
                    if ($prefixedUrlPath === $slugCandidate) {
                        // The requested URL matches exactly the found slug. We can't find a better match,
                        // so use that page candidate and stop any further querying.
                        $pageCandidates = [$candidate];
                        break 2;
                    }

                    $pageCandidates[] = $candidate;
                }
            }
        }

        // Stop if there are no candidates
        if (empty($pageCandidates)) {
            throw new RouteNotFoundException('No page candidates found for path "' . $urlPath . '"', 1538389999);
        }

        $fullCollection = new RouteCollection();
        foreach ($pageCandidates ?? [] as $page) {
            $pageIdForDefaultLanguage = (int)($page['l10n_parent'] ?: $page['uid']);
            $pagePath = $page['slug'];
            $pageCollection = new RouteCollection();
            $defaultRouteForPage = new Route(
                $pagePath,
                [],
                [],
                ['utf8' => true, '_page' => $page]
            );
            $pageCollection->add('default', $defaultRouteForPage);
            $enhancers = $this->getEnhancersForPage($pageIdForDefaultLanguage, $language);
            foreach ($enhancers as $enhancer) {
                if ($enhancer instanceof DecoratingEnhancerInterface) {
                    $enhancer->decorateForMatching($pageCollection, $urlPath);
                }
            }
            foreach ($enhancers as $enhancer) {
                if ($enhancer instanceof RoutingEnhancerInterface) {
                    $enhancer->enhanceForMatching($pageCollection);
                }
            }

            $collectionPrefix = 'page_' . $page['uid'];
            // Pages with a MountPoint Parameter means that they have a different context, and should be treated
            // as a separate instance
            if (isset($page['MPvar'])) {
                $collectionPrefix .= '_MP_' . str_replace(',', '', $page['MPvar']);
            }
            $pageCollection->addNamePrefix($collectionPrefix . '_');
            $fullCollection->addCollection($pageCollection);
            // set default route flag after all routes have been processed
            $defaultRouteForPage->setOption('_isDefault', true);
        }

        $matcher = new PageUriMatcher($fullCollection);
        try {
            $result = $matcher->match($prefixedUrlPath);
            /** @var Route $matchedRoute */
            $matchedRoute = $fullCollection->get($result['_route']);
            return $this->buildPageArguments($matchedRoute, $result, $request->getQueryParams());
        } catch (ResourceNotFoundException $e) {
            // Do nothing
        }
        throw new RouteNotFoundException('No route found for path "' . $urlPath . '"', 1538389998);
    }

    /**
     * API for generating a page where the $route parameter is typically an array (page record) or the page ID
     *
     * @param array|string $route
     * @param array $parameters an array of query parameters which can be built into the URI path, also consider the special handling of "_language"
     * @param string $fragment additional #my-fragment part
     * @param string $type see the RouterInterface for possible types
     * @return UriInterface
     * @throws InvalidRouteArgumentsException
     */
    public function generateUri($route, array $parameters = [], string $fragment = '', string $type = ''): UriInterface
    {
        // Resolve language
        $language = null;
        $languageOption = $parameters['_language'] ?? null;
        unset($parameters['_language']);
        if ($languageOption instanceof SiteLanguage) {
            $language = $languageOption;
        } elseif ($languageOption !== null) {
            $language = $this->site->getLanguageById((int)$languageOption);
        }
        if ($language === null) {
            $language = $this->site->getDefaultLanguage();
        }

        $pageId = 0;
        if (is_array($route)) {
            $pageId = (int)$route['uid'];
        } elseif (is_scalar($route)) {
            $pageId = (int)$route;
        }

        $context = clone $this->context;
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($language));
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        $page = $pageRepository->getPage($pageId, true);
        $pagePath = $page['slug'] ?? '';

        if ($parameters['MP'] ?? false) {
            $mountPointPairs = explode(',', $parameters['MP']);
            $pagePath = $this->resolveMountPointParameterIntoPageSlug(
                $pageId,
                $pagePath,
                $mountPointPairs,
                $pageRepository
            );

            // If the MountPoint page has a different site, the link needs to be generated
            // with the base of the MountPoint page, this is especially relevant for cross-domain linking
            // Because the language contains the full base, it is retrieved in this case.
            try {
                [, $mountPointPage] = explode('-', reset($mountPointPairs));
                $site = GeneralUtility::makeInstance(SiteMatcher::class)
                    ->matchByPageId((int)$mountPointPage);
                $language = $site->getLanguageById($language->getLanguageId());
            } catch (SiteNotFoundException $e) {
                // No alternative site found, use the existing one
            }
            // Store the MP parameter in the page record, so it could be used for any enhancers
            $page['MPvar'] = $parameters['MP'];
            unset($parameters['MP']);
        }

        $originalParameters = $parameters;
        $collection = new RouteCollection();
        $defaultRouteForPage = new Route(
            '/' . ltrim($pagePath, '/'),
            [],
            [],
            ['utf8' => true, '_page' => $page]
        );
        $collection->add('default', $defaultRouteForPage);

        // cHash is never considered because cHash is built by this very method.
        unset($originalParameters['cHash']);
        $enhancers = $this->getEnhancersForPage($pageId, $language);
        foreach ($enhancers as $enhancer) {
            if ($enhancer instanceof RoutingEnhancerInterface) {
                $enhancer->enhanceForGeneration($collection, $originalParameters);
            }
        }
        foreach ($enhancers as $enhancer) {
            if ($enhancer instanceof DecoratingEnhancerInterface) {
                $enhancer->decorateForGeneration($collection, $originalParameters);
            }
        }

        $scheme = $language->getBase()->getScheme();
        $mappableProcessor = new MappableProcessor();
        $context = new RequestContext(
            // page segment (slug & enhanced part) is supposed to start with '/'
            rtrim($language->getBase()->getPath(), '/'),
            'GET',
            $language->getBase()->getHost(),
            $scheme ?: 'http',
            $scheme === 'http' ? $language->getBase()->getPort() ?? 80 : 80,
            $scheme === 'https' ? $language->getBase()->getPort() ?? 443 : 443
        );
        $generator = new UrlGenerator($collection, $context);
        $generator->injectMappableProcessor($mappableProcessor);
        // set default route flag after all routes have been processed
        $defaultRouteForPage->setOption('_isDefault', true);
        $allRoutes = GeneralUtility::makeInstance(RouteSorter::class)
            ->withRoutes($collection->all())
            ->withOriginalParameters($originalParameters)
            ->sortRoutesForGeneration()
            ->getRoutes();
        $matchedRoute = null;
        $pageRouteResult = null;
        $uri = null;
        // map our reference type to symfony's custom paths
        $referenceType = $type === static::ABSOLUTE_PATH ? UrlGenerator::ABSOLUTE_PATH : UrlGenerator::ABSOLUTE_URL;
        /**
         * @var string $routeName
         * @var Route $route
         */
        foreach ($allRoutes as $routeName => $route) {
            try {
                $parameters = $originalParameters;
                if ($route->hasOption('deflatedParameters')) {
                    $parameters = $route->getOption('deflatedParameters');
                }
                $mappableProcessor->generate($route, $parameters);
                // ABSOLUTE_URL is used as default fallback
                $urlAsString = $generator->generate($routeName, $parameters, $referenceType);
                $uri = new Uri($urlAsString);
                /** @var Route $matchedRoute */
                $matchedRoute = $collection->get($routeName);
                // fetch potential applied defaults for later cHash generation
                // (even if not applied in route, it will be exposed during resolving)
                $appliedDefaults = $matchedRoute->getOption('_appliedDefaults') ?? [];
                parse_str($uri->getQuery() ?? '', $remainingQueryParameters);
                $enhancer = $route->getEnhancer();
                if ($enhancer instanceof InflatableEnhancerInterface) {
                    $remainingQueryParameters = $enhancer->inflateParameters($remainingQueryParameters);
                }
                $pageRouteResult = $this->buildPageArguments($route, array_merge($appliedDefaults, $parameters), $remainingQueryParameters);
                break;
            } catch (MissingMandatoryParametersException $e) {
                // no match
            }
        }

        if (!$uri instanceof UriInterface) {
            throw new InvalidRouteArgumentsException('Uri could not be built for page "' . $pageId . '"', 1538390230);
        }

        if ($pageRouteResult && $pageRouteResult->areDirty()) {
            // for generating URLs this should(!) never happen
            // if it does happen, generator logic has flaws
            throw new InvalidRouteArgumentsException('Route arguments are dirty', 1537613247);
        }

        if ($matchedRoute && $pageRouteResult && !empty($pageRouteResult->getDynamicArguments())) {
            $cacheHash = $this->generateCacheHash($pageId, $pageRouteResult);

            $queryArguments = $pageRouteResult->getQueryArguments();
            if (!empty($cacheHash)) {
                $queryArguments['cHash'] = $cacheHash;
            }
            $uri = $uri->withQuery(http_build_query($queryArguments, '', '&', PHP_QUERY_RFC3986));
        }
        if ($fragment) {
            $uri = $uri->withFragment($fragment);
        }
        return $uri;
    }

    /**
     * Check for records in the database which matches one of the slug candidates.
     *
     * @param array $slugCandidates
     * @param int $languageId
     * @param array $excludeUids when called recursively this is the mountpoint parameter of the original prefix
     * @return array
     * @throws SiteNotFoundException
     */
    protected function getPagesFromDatabaseForCandidates(array $slugCandidates, int $languageId, array $excludeUids = []): array
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $searchLiveRecordsOnly = $context->getPropertyFromAspect('workspace', 'isLive');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(FrontendWorkspaceRestriction::class, null, null, $searchLiveRecordsOnly));

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
            ->execute();
        $isRecursiveCall = !empty($excludeUids);

        $pages = [];
        $siteMatcher = GeneralUtility::makeInstance(SiteMatcher::class);
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);

        while ($row = $statement->fetch()) {
            $mountPageInformation = null;
            $pageRepository->fixVersioningPid('pages', $row);
            $pageIdInDefaultLanguage = (int)($languageId > 0 ? $row['l10n_parent'] : $row['uid']);
            // When this page was added before via recursion, this page should be skipped
            if (in_array($pageIdInDefaultLanguage, $excludeUids, true)) {
                continue;
            }
            try {
                $isOnSameSite = $siteMatcher->matchByPageId($pageIdInDefaultLanguage)->getRootPageId() === $this->site->getRootPageId();
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
                if (PageRepository::DOKTYPE_MOUNTPOINT === (int)$row['doktype'] && $row['mount_pid_ol']) {
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
                $siteOfMountedPage = $siteMatcher->matchByPageId((int)$mountedPage['uid']);
                if ($siteOfMountedPage instanceof Site) {
                    $morePageCandidates = $this->findPageCandidatesOfMountPoint(
                        $row,
                        $mountedPage,
                        $siteOfMountedPage,
                        $languageId,
                        $slugCandidates,
                        $context
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
     * @param Context $context
     * @return array more candidates
     */
    protected function findPageCandidatesOfMountPoint(
        array $mountPointPage,
        array $mountedPage,
        Site $siteOfMountedPage,
        int $languageId,
        array $slugCandidates,
        Context $context
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
        $routerForSite = GeneralUtility::makeInstance(static::class, $siteOfMountedPage);
        // Find the right pages for which have been matched
        $excludedPageIds = [(int)$mountPointPage['uid']];
        $pageCandidates = $routerForSite->getPagesFromDatabaseForCandidates(
            $trimmedSlugPrefixes,
            $languageId,
            $excludedPageIds
        );
        // Depending on the "mount_pid_ol" parameter, the mountedPage or the mounted page is in the rootline
        $pageWhichMustBeInRootLine = (int)($mountPointPage['mount_pid_ol'] ? $mountedPage['uid'] : $mountPointPage['uid']);
        foreach ($pageCandidates as $pageCandidate) {
            if (!$pageCandidate['mount_pid_ol']) {
                $pageCandidate['MPvar'] = $mountPointPage['MPvar'] . ($pageCandidate['MPvar'] ? ',' . $pageCandidate['MPvar'] : '');
            }
            // In order to avoid the possibility that any random page like /about-us which is not connected to the mount
            // point is not possible to be called via /my-mount-point/about-us, let's check the
            $pageCandidateIsConnectedInMountPoint = false;
            $rootLine = GeneralUtility::makeInstance(
                RootlineUtility::class,
                $pageCandidate['uid'],
                $pageCandidate['MPvar'],
                $context
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
     * When a MP parameter is given, the mount point parameter is resolved, and the slug of the new page
     * is added while the same parts of the original pagePath is removed (before).
     * This way, the subpage to a mounted page has now a different "base" (= prefixed with the slug of the
     * mount point).
     *
     * This is done recursively when multiple mount point parameter pairs
     *
     * @param int $pageId
     * @param string $pagePath the original path of the page
     * @param array $mountPointPairs an array with MP pairs (like ['13-3', '4-2'] for recursive mount points)
     * @param PageRepository $pageRepository
     * @return string
     */
    protected function resolveMountPointParameterIntoPageSlug(
        int $pageId,
        string $pagePath,
        array $mountPointPairs,
        PageRepository $pageRepository
    ): string {
        // Handle recursive mount points
        $prefixesToRemove = [];
        $slugPrefixesToAdd = [];
        foreach ($mountPointPairs as $mountPointPair) {
            [$mountRoot, $mountedPage] = GeneralUtility::intExplode('-', $mountPointPair);
            $mountPageInformation = $pageRepository->getMountPointInfo($mountedPage);
            if ($mountPageInformation) {
                if ($pageId === $mountedPage) {
                    continue;
                }
                // Get slugs in the translated page
                $mountedPage = $pageRepository->getPage($mountedPage);
                $mountRoot = $pageRepository->getPage($mountRoot);
                $slugPrefix = $mountedPage['slug'] ?? '';
                if ($slugPrefix === '/') {
                    $slugPrefix = '';
                }
                $prefixToRemove = $mountRoot['slug'] ?? '';
                if ($prefixToRemove === '/') {
                    $prefixToRemove = '';
                }
                $prefixesToRemove[] = $prefixToRemove;
                $slugPrefixesToAdd[] = $slugPrefix;
            }
        }
        $slugPrefixesToAdd = array_reverse($slugPrefixesToAdd);
        $prefixesToRemove = array_reverse($prefixesToRemove);
        foreach ($prefixesToRemove as $prefixToRemove) {
            // Slug prefixes are taken from the beginning of the array, where as the parts to be removed
            // Are taken from the end.
            $replacement = array_shift($slugPrefixesToAdd);
            if ($prefixToRemove !== '' && strpos($pagePath, $prefixToRemove) === 0) {
                $pagePath = substr($pagePath, strlen($prefixToRemove));
            }
            $pagePath = $replacement . ($pagePath !== '/' ? '/' . ltrim($pagePath, '/') : '');
        }
        return $pagePath;
    }

    /**
     * Fetch possible enhancers + aspects based on the current page configuration and the site configuration put
     * into "routeEnhancers"
     *
     * @param int $pageId
     * @param SiteLanguage $language
     * @return EnhancerInterface[]
     */
    protected function getEnhancersForPage(int $pageId, SiteLanguage $language): array
    {
        $enhancers = [];
        foreach ($this->site->getConfiguration()['routeEnhancers'] ?? [] as $enhancerConfiguration) {
            // Check if there is a restriction to page Ids.
            if (is_array($enhancerConfiguration['limitToPages'] ?? null) && !in_array($pageId, $enhancerConfiguration['limitToPages'])) {
                continue;
            }
            $enhancerType = $enhancerConfiguration['type'] ?? '';
            $enhancer = $this->enhancerFactory->create($enhancerType, $enhancerConfiguration);
            if (!empty($enhancerConfiguration['aspects'] ?? null)) {
                $aspects = $this->aspectFactory->createAspects(
                    $enhancerConfiguration['aspects'],
                    $language,
                    $this->site
                );
                $enhancer->setAspects($aspects);
            }
            $enhancers[] = $enhancer;
        }
        return $enhancers;
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
     * @return array
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

    /**
     * @param int $pageId
     * @param PageArguments $arguments
     * @return string
     */
    protected function generateCacheHash(int $pageId, PageArguments $arguments): string
    {
        return $this->cacheHashCalculator->calculateCacheHash(
            $this->getCacheHashParameters($pageId, $arguments)
        );
    }

    /**
     * @param int $pageId
     * @param PageArguments $arguments
     * @return array
     */
    protected function getCacheHashParameters(int $pageId, PageArguments $arguments): array
    {
        $hashParameters = $arguments->getDynamicArguments();
        $hashParameters['id'] = $pageId;
        $uri = http_build_query($hashParameters, '', '&', PHP_QUERY_RFC3986);
        return $this->cacheHashCalculator->getRelevantParameters($uri);
    }

    /**
     * Builds route arguments. The important part here is to distinguish between
     * static and dynamic arguments. Per default all arguments are dynamic until
     * aspects can be used to really consider them as static (= 1:1 mapping between
     * route value and resulting arguments).
     *
     * Besides that, internal arguments (_route, _controller, _custom, ..) have
     * to be separated since those values are not meant to be used for later
     * processing. Not separating those values might result in invalid cHash.
     *
     * This method is used during resolving and generation of URLs.
     *
     * @param Route $route
     * @param array $results
     * @param array $remainingQueryParameters
     * @return PageArguments
     */
    protected function buildPageArguments(Route $route, array $results, array $remainingQueryParameters = []): PageArguments
    {
        // only use parameters that actually have been processed
        // (thus stripping internals like _route, _controller, ...)
        $routeArguments = $this->filterProcessedParameters($route, $results);
        // assert amount of "static" mappers is not too "dynamic"
        $this->assertMaximumStaticMappableAmount($route, array_keys($routeArguments));
        // delegate result handling to enhancer
        $enhancer = $route->getEnhancer();
        if ($enhancer instanceof ResultingInterface) {
            // forward complete(!) results, not just filtered parameters
            return $enhancer->buildResult($route, $results, $remainingQueryParameters);
        }
        $page = $route->getOption('_page');
        $pageId = (int)($page['l10n_parent'] > 0 ? $page['l10n_parent'] : $page['uid']);
        $type = $this->resolveType($route, $remainingQueryParameters);
        // See PageSlugCandidateProvider where this is added.
        if ($page['MPvar'] ?? '') {
            $routeArguments['MP'] = $page['MPvar'];
        }
        return new PageArguments($pageId, $type, $routeArguments, [], $remainingQueryParameters);
    }

    /**
     * Retrieves type from processed route and modifies remaining query parameters.
     *
     * @param Route $route
     * @param array $remainingQueryParameters reference to remaining query parameters
     * @return string
     */
    protected function resolveType(Route $route, array &$remainingQueryParameters): string
    {
        $type = $remainingQueryParameters['type'] ?? 0;
        $decoratedParameters = $route->getOption('_decoratedParameters');
        if (isset($decoratedParameters['type'])) {
            $type = $decoratedParameters['type'];
            unset($decoratedParameters['type']);
            $remainingQueryParameters = array_replace_recursive(
                $remainingQueryParameters,
                $decoratedParameters
            );
        }
        return (string)$type;
    }

    /**
     * Asserts that possible amount of items in all static and countable mappers
     * (such as StaticRangeMapper) is limited to 10000 in order to avoid
     * brute-force scenarios and the risk of cache-flooding.
     *
     * @param Route $route
     * @param array $variableNames
     * @throws \OverflowException
     */
    protected function assertMaximumStaticMappableAmount(Route $route, array $variableNames = [])
    {
        // empty when only values of route defaults where used
        if (empty($variableNames)) {
            return;
        }
        $mappers = $route->filterAspects(
            [StaticMappableAspectInterface::class, \Countable::class],
            $variableNames
        );
        if (empty($mappers)) {
            return;
        }

        $multipliers = array_map('count', $mappers);
        $product = array_product($multipliers);
        if ($product > 10000) {
            throw new \OverflowException(
                'Possible range of all mappers is larger than 10000 items',
                1537696772
            );
        }
    }

    /**
     * Determine parameters that have been processed.
     *
     * @param Route $route
     * @param array $results
     * @return array
     */
    protected function filterProcessedParameters(Route $route, $results): array
    {
        return array_intersect_key(
            $results,
            array_flip($route->compile()->getPathVariables())
        );
    }
}
