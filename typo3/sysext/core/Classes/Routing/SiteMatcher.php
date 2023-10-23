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
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * Returns a site based on a given request.
 *
 * The main usage is the ->matchRequest() functionality, which receives a request object and boots up
 * Symfony Routing to find the proper route with its defaults / attributes.
 *
 * On top, this is also commonly used throughout TYPO3 to fetch a site by a given pageId.
 * ->matchPageId().
 *
 * The concept of the SiteMatcher is to *resolve*, and not build URIs. On top, it is a facade to hide the
 * dependency to symfony and to not expose its logic.
 *
 * @internal Please note that the site matcher will be probably cease to exist and adapted to the SiteFinder concept when Pseudo-Site handling will be removed.
 */
class SiteMatcher implements SingletonInterface
{
    public function __construct(
        protected readonly Features $features,
        protected readonly SiteFinder $finder,
        protected readonly RequestContextFactory $requestContextFactory
    ) {}

    /**
     * Only used when a page is moved but the pseudo site caches has this information hard-coded, so the caches
     * need to be flushed.
     *
     * @internal
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    public function refresh()
    {
        /** Ensure root line caches are flushed */
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->getCache('runtime')->flushByTag(RootlineUtility::RUNTIME_CACHE_TAG);
        $cacheManager->getCache('rootline')->flush();
    }

    /**
     * First, it is checked, if a "id" GET/POST parameter is found.
     * If it is, we check for a valid site mounted there.
     *
     * If it isn't the quest continues by validating the whole request URL and validating against
     * all available site records (and their language prefixes).
     *
     * @param ServerRequestInterface $request
     */
    public function matchRequest(ServerRequestInterface $request): RouteResultInterface
    {
        // Remove script file name (index.php) from request uri
        $uri = $this->canonicalizeUri($request->getUri(), $request);
        $pageId = $this->resolvePageIdQueryParam($request);
        $languageId = $this->resolveLanguageIdQueryParam($request);

        $routeResult = $this->matchSiteByUri($uri, $request);

        // Allow insecure pageId based site resolution if explicitly enabled and only if both, ?id= and ?L= are defined
        // (pageId based site resolution without L parameter has always been prohibited, so we do not support that)
        if (
            $this->features->isFeatureEnabled('security.frontend.allowInsecureSiteResolutionByQueryParameters') &&
            $pageId !== null && $languageId !== null
        ) {
            return $this->matchSiteByQueryParams($pageId, $languageId, $routeResult, $uri);
        }

        // Allow the default language to be resolved in case all languages use a prefix
        // and therefore did not match based on path if an explicit pageId is given,
        // (example "https://www.example.com/?id=.." was entered, but all languages have "https://www.example.com/lang-key/")
        // @todo remove this fallback, in order for SiteBaseRedirectResolver to produce a redirect instead (requires functionals to be adapted)
        if ($pageId !== null && $routeResult->getLanguage() === null) {
            $routeResult = $routeResult->withLanguage($routeResult->getSite()->getDefaultLanguage());
        }

        // adjust the language aspect if it was given by query param `&L` (and ?id is given)
        // @todo remove, this is added for backwards (and functional tests) compatibility reasons
        if ($languageId !== null && $pageId !== null) {
            try {
                // override/set language by `&L=` query param
                $routeResult = $routeResult->withLanguage($routeResult->getSite()->getLanguageById($languageId));
            } catch (\InvalidArgumentException) {
                // ignore; language id not available
            }
        }

        return $routeResult;
    }

    /**
     * If a given page ID is handed in, a Site/NullSite is returned.
     *
     * @param int $pageId uid of a page in default language
     * @param array|null $rootLine an alternative root line, if already at and.
     */
    public function matchByPageId(int $pageId, array $rootLine = null): SiteInterface
    {
        try {
            return $this->finder->getSiteByPageId($pageId, $rootLine);
        } catch (SiteNotFoundException) {
            return new NullSite();
        }
    }

    /**
     * Returns a Symfony RouteCollection containing all routes to all sites.
     */
    protected function getRouteCollectionForAllSites(): RouteCollection
    {
        $collection = new RouteCollection();
        foreach ($this->finder->getAllSites() as $site) {
            // Add the site as entrypoint
            // @todo Find a way to test only this basic route against chinese characters, as site languages kicking
            //       always in. Do the rawurldecode() here to to be consistent with language preparations.

            $uri = $site->getBase();
            $route = new Route(
                (rawurldecode($uri->getPath()) ?: '/') . '{tail}',
                ['site' => $site, 'language' => null, 'tail' => ''],
                array_filter(['tail' => '.*', 'port' => (string)$uri->getPort()]),
                ['utf8' => true, 'fallback' => true],
                // @todo Verify if host should here covered with idn_to_ascii() to be consistent with preparation for languages.
                $uri->getHost() ?: '',
                $uri->getScheme() === '' ? [] : [$uri->getScheme()]
            );
            $identifier = 'site_' . $site->getIdentifier();
            $collection->add($identifier, $route);

            // Add all languages
            foreach ($site->getAllLanguages() as $siteLanguage) {
                $uri = $siteLanguage->getBase();
                $route = new Route(
                    (rawurldecode($uri->getPath()) ?: '/') . '{tail}',
                    ['site' => $site, 'language' => $siteLanguage, 'tail' => ''],
                    array_filter(['tail' => '.*', 'port' => (string)$uri->getPort()]),
                    ['utf8' => true],
                    (string)idn_to_ascii($uri->getHost()),
                    $uri->getScheme() === '' ? [] : [$uri->getScheme()]
                );
                $identifier = 'site_' . $site->getIdentifier() . '_' . $siteLanguage->getLanguageId();
                $collection->add($identifier, $route);
            }
        }
        return $collection;
    }

    /**
     * @return ?positive-int
     */
    protected function resolvePageIdQueryParam(ServerRequestInterface $request): ?int
    {
        $pageId = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? null;
        if ($pageId === null) {
            return null;
        }
        return (int)$pageId <= 0 ? null : (int)$pageId;
    }

    /**
     * @return ?positive-int
     */
    protected function resolveLanguageIdQueryParam(ServerRequestInterface $request): ?int
    {
        $languageId = $request->getQueryParams()['L'] ?? $request->getParsedBody()['L'] ?? null;
        if ($languageId === null) {
            return null;
        }
        return (int)$languageId < 0 ? null : (int)$languageId;
    }

    /**
     * Remove script file name (index.php) from request uri
     */
    protected function canonicalizeUri(UriInterface $uri, ServerRequestInterface $request): UriInterface
    {
        if ($uri->getPath() === '') {
            return $uri;
        }

        $normalizedParams = $request->getAttribute('normalizedParams');
        if (!$normalizedParams instanceof NormalizedParams) {
            return $uri;
        }

        $urlPath = ltrim($uri->getPath(), '/');
        $scriptName = ltrim($normalizedParams->getScriptName(), '/');
        $scriptPath = ltrim($normalizedParams->getSitePath(), '/');
        if ($scriptName !== '' && str_starts_with($urlPath, $scriptName)) {
            $urlPath = '/' . $scriptPath . substr($urlPath, mb_strlen($scriptName));
            $uri = $uri->withPath($urlPath);
        }

        return $uri;
    }

    protected function matchSiteByUri(UriInterface $uri, ServerRequestInterface $request): SiteRouteResult
    {
        $collection = $this->getRouteCollectionForAllSites();
        $requestContext = $this->requestContextFactory->fromUri($uri, $request->getMethod());
        $matcher = new BestUrlMatcher($collection, $requestContext);
        try {
            /** @var array{site: SiteInterface, language: ?SiteLanguage, tail: string} $match */
            $match = $matcher->match($uri->getPath());
            return new SiteRouteResult(
                $uri,
                $match['site'],
                $match['language'],
                $match['tail']
            );
        } catch (NoConfigurationException | ResourceNotFoundException) {
            return new SiteRouteResult($uri, new NullSite(), null, '');
        }
    }

    protected function matchSiteByQueryParams(
        int $pageId,
        int $languageId,
        SiteRouteResult $fallback,
        UriInterface $uri,
    ): SiteRouteResult {
        try {
            $site = $this->finder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException) {
            return $fallback;
        }

        try {
            // override/set language by `&L=` query param
            $language = $site->getLanguageById($languageId);
        } catch (\InvalidArgumentException) {
            return $fallback;
        }

        return new SiteRouteResult($uri, $site, $language);
    }
}
