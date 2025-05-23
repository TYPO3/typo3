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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
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
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;

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
    protected Site $site;
    protected EnhancerFactory $enhancerFactory;
    protected AspectFactory $aspectFactory;
    protected CacheHashCalculator $cacheHashCalculator;
    protected Context $context;
    protected RequestContextFactory $requestContextFactory;

    /**
     * A page router is always bound to a specific site.
     */
    public function __construct(Site $site, ?Context $context = null)
    {
        $this->site = $site;
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->enhancerFactory = GeneralUtility::makeInstance(EnhancerFactory::class);
        $this->aspectFactory = GeneralUtility::makeInstance(AspectFactory::class, $this->context);
        $this->cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
        $this->requestContextFactory = GeneralUtility::makeInstance(RequestContextFactory::class);
    }

    /**
     * Finds a RouteResult based on the given request.
     *
     * @param RouteResultInterface|SiteRouteResult|null $previousResult
     * @return RouteResultInterface|PageArguments
     * @throws RouteNotFoundException
     */
    public function matchRequest(ServerRequestInterface $request, ?RouteResultInterface $previousResult = null): RouteResultInterface
    {
        if ($previousResult === null) {
            throw new RouteNotFoundException('No previous result given. Cannot find a page for an empty route part', 1555303496);
        }

        $candidateProvider = $this->getSlugCandidateProvider($this->context);

        // Legacy URIs (?id=12345) takes precedence, no matter if a route is given
        $requestId = ($request->getQueryParams()['id'] ?? null);
        $type = '0';
        if (isset($request->getQueryParams()['type']) && is_scalar($request->getQueryParams()['type'])) {
            $type = (string)$request->getQueryParams()['type'];
        }
        if ($requestId !== null) {
            if (MathUtility::canBeInterpretedAsInteger($requestId)
                && (int)$requestId > 0
                && !empty($pageId = $candidateProvider->getRealPageIdForPageIdAsPossibleCandidate((int)$requestId))
            ) {
                return new PageArguments((int)$pageId, $type, [], [], $request->getQueryParams());
            }
            throw new RouteNotFoundException('The requested page does not exist.', 1557839801);
        }

        $urlPath = $previousResult->getTail();
        $language = $previousResult->getLanguage();
        // Keep possible existing "/" at the end (no trim, just ltrim), even though the page slug might not
        // contain a "/" at the end. This way we find page candidates where pages MIGHT have a trailing slash
        // and pages with slugs that do not have a trailing slash
        // $pageCandidates will contain more records than expected, which is important here, as the ->match() method
        // will handle this then.
        // The prepended slash will ensure that the root page of the site tree will also be fetched
        $prefixedUrlPath = '/' . ltrim($urlPath, '/');

        $pageCandidates = $candidateProvider->getCandidatesForPath($prefixedUrlPath, $language);

        // Stop if there are no candidates
        if (empty($pageCandidates)) {
            throw new RouteNotFoundException('No page candidates found for path "' . $prefixedUrlPath . '"', 1538389999);
        }

        /** @var RouteCollection<string, Route> $fullCollection */
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
            // Only use route if page language variant matches current language, otherwise handle it as route not found.
            if ($this->isRouteReallyValidForLanguage($matchedRoute, $language)) {
                return $this->buildPageArguments($matchedRoute, $result, $request->getQueryParams());
            }
        } catch (ResourceNotFoundException $e) {
            if (str_ends_with($prefixedUrlPath, '/')) {
                // Second try, look for /my-page even though the request was called via /my-page/ and the slash
                // was not part of the slug, but let's then check again
                try {
                    $result = $matcher->match(rtrim($prefixedUrlPath, '/'));
                    /** @var Route $matchedRoute */
                    $matchedRoute = $fullCollection->get($result['_route']);
                    // Only use route if page language variant matches current language, otherwise
                    // handle it as route not found.
                    if ($this->isRouteReallyValidForLanguage($matchedRoute, $language)) {
                        return $this->buildPageArguments($matchedRoute, $result, $request->getQueryParams());
                    }
                } catch (ResourceNotFoundException $e) {
                    // Do nothing
                }
            } else {
                // Second try, look for /my-page/ even though the request was called via /my-page and the slash
                // was part of the slug, but let's then check again
                try {
                    $result = $matcher->match($prefixedUrlPath . '/');
                    /** @var Route $matchedRoute */
                    $matchedRoute = $fullCollection->get($result['_route']);
                    // Only use route if page language variant matches current language, otherwise
                    // handle it as route not found.
                    if ($this->isRouteReallyValidForLanguage($matchedRoute, $language)) {
                        return $this->buildPageArguments($matchedRoute, $result, $request->getQueryParams());
                    }
                } catch (ResourceNotFoundException $e) {
                    // Do nothing
                }
            }
        }
        throw new RouteNotFoundException('No route found for path "' . $urlPath . '"', 1538389998);
    }

    /**
     * API for generating a page uri where the $route parameter is typically an array (a page record) or the page ID
     *
     * @param array|string|int|Page $route
     * @param array $parameters an array of query parameters which can be built into the URI path, also consider the special handling of "_language"
     * @param string $fragment additional #my-fragment part
     * @param string $type see the RouterInterface for possible types
     * @throws InvalidRouteArgumentsException
     */
    public function generateUri($route, array $parameters = [], string $fragment = '', string $type = ''): UriInterface
    {
        // sanitize superfluous page-id from additional parameters
        // (even if `$parameters['id']` is different to `$pageId`, it will be removed)
        unset($parameters['id']);
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
        if ($route instanceof Page) {
            $pageId = $route->getPageId();
        } elseif (is_array($route)) {
            $pageId = (int)$route['uid'];
        } elseif (is_scalar($route)) {
            $pageId = (int)$route;
        }

        $context = clone $this->context;
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($language));
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);

        if ($route instanceof Page) {
            $page = $route->toArray();
        } elseif (is_array($route)
            // Check 3rd party input $route for basic requirements
            && isset($route['uid'], $route['sys_language_uid'], $route['l10n_parent'], $route['slug'])
            && (int)$route['sys_language_uid'] === $language->getLanguageId()
            && ((int)$route['l10n_parent'] === 0 || isset($route['_LOCALIZED_UID']))
        ) {
            $page = $route;
        } else {
            $page = $pageRepository->getPage($pageId, true);
        }
        $pagePath = $page['slug'] ?? '';

        if ($parameters['MP'] ?? '') {
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
                [, $mountPointPage] = explode('-', (string)reset($mountPointPairs));
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

        $mappableProcessor = new MappableProcessor();
        $requestContext = $this->requestContextFactory->fromSiteLanguage($language);
        $generator = new UrlGenerator($collection, $requestContext);
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
                // skip the route, in case any aspect of it could not be mapped to a value
                if ($mappableProcessor->generate($route, $parameters) === false) {
                    continue;
                }
                // ABSOLUTE_URL is used as default fallback
                $urlAsString = $generator->generate($routeName, $parameters, $referenceType);
                $uri = new Uri($urlAsString);
                /** @var Route $matchedRoute */
                $matchedRoute = $collection->get($routeName);
                // fetch potential applied defaults for later cHash generation
                // (even if not applied in route, it will be exposed during resolving)
                $appliedDefaults = $matchedRoute->getOption('_appliedDefaults') ?? [];
                parse_str($uri->getQuery(), $remainingQueryParameters);
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
            [$mountRoot, $mountedPage] = GeneralUtility::intExplode('-', (string)$mountPointPair);
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
            if ($prefixToRemove !== '' && str_starts_with($pagePath, $prefixToRemove)) {
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

    protected function generateCacheHash(int $pageId, PageArguments $arguments): string
    {
        return $this->cacheHashCalculator->calculateCacheHash(
            $this->getCacheHashParameters($pageId, $arguments)
        );
    }

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
        if ((int)($page['l10n_parent'] ?? 0) > 0) {
            $pageId = (int)$page['l10n_parent'];
        } elseif ((int)($page['t3ver_oid'] ?? 0) > 0) {
            $pageId = (int)$page['t3ver_oid'];
        } else {
            $pageId = (int)($page['uid'] ?? 0);
        }
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
     * @param array $remainingQueryParameters reference to remaining query parameters
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
        if (is_scalar($type)) {
            return (string)$type;
        }
        return '0';
    }

    /**
     * Asserts that possible amount of items in all static and countable mappers
     * (such as StaticRangeMapper) is limited to 10000 in order to avoid
     * brute-force scenarios and the risk of cache-flooding.
     *
     * @throws \OverflowException
     * @todo with having `static` route variables, this restriction should be configurable & optional
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
     * @param array $results
     */
    protected function filterProcessedParameters(Route $route, $results): array
    {
        return array_intersect_key(
            $results,
            array_flip($route->compile()->getPathVariables())
        );
    }

    protected function getSlugCandidateProvider(Context $context): PageSlugCandidateProvider
    {
        return GeneralUtility::makeInstance(
            PageSlugCandidateProvider::class,
            $context,
            $this->site,
            $this->enhancerFactory
        );
    }

    /**
     * Request may have been made with default page slug, also we are dealing with a site language variant. To avoid
     * duplicate content, we need to revalidate that the eventually matched language route is really the available
     * page language variant for the current lange. We do this at this late point to minimize the needed database
     * queries instead of checking it for all build page candidates.
     *
     * This is safe, as we can simply drop the route and having a correct page not found action delivered.
     */
    protected function isRouteReallyValidForLanguage(Route $route, SiteLanguage $siteLanguage): bool
    {
        $page = $route->getOption('_page');
        $schema = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('pages');
        if (!$schema->isLanguageAware()) {
            return true;
        }
        $languageIdField = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
        $languageId = (int)($page[$languageIdField] ?? 0);
        if ($siteLanguage->getLanguageId() === 0 || $siteLanguage->getLanguageId() === $languageId) {
            // default language site request or if page record is same language then siteLanguage, page record
            // is valid to use as page resolving candidate and need no further overlay checks.
            return true;
        }
        $pageIdInDefaultLanguage = (int)($languageId > 0 ? $page['l10n_parent'] : $page['uid']);
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        $localizedPage = $pageRepository->getPageOverlay($pageIdInDefaultLanguage, $siteLanguage->getLanguageId());
        if (!$localizedPage) {
            // no page language overlay found, which means that either language page is not published and no logged
            // in backend user OR there is no language overlay for that page at all. Thus using page record to build
            // as page resolving candidate is valid.
            return true;
        }
        // we found a valid page overlay, which means that current record is not the valid page for the current
        // siteLanguage. To avoid resolving page with multiple slugs for a siteLanguage path, we flag this invalid.
        return false;
    }
}
