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
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Core\Cache\CacheManager;
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
    /**
     * @var SiteFinder
     */
    protected $finder;

    /**
     * Injects necessary objects.
     *
     * @param SiteFinder|null $finder
     */
    public function __construct(SiteFinder $finder = null)
    {
        $this->finder = $finder ?? GeneralUtility::makeInstance(SiteFinder::class);
    }

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
        RootlineUtility::purgeCaches();
        GeneralUtility::makeInstance(CacheManager::class)->getCache('rootline')->flush();
    }

    /**
     * First, it is checked, if a "id" GET/POST parameter is found.
     * If it is, we check for a valid site mounted there.
     *
     * If it isn't the quest continues by validating the whole request URL and validating against
     * all available site records (and their language prefixes).
     *
     * @param ServerRequestInterface $request
     * @return RouteResultInterface
     */
    public function matchRequest(ServerRequestInterface $request): RouteResultInterface
    {
        $site = new NullSite();
        $language = null;
        $defaultLanguage = null;

        $pageId = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0;

        // First, check if we have a _GET/_POST parameter for "id", then a site information can be resolved based.
        if ($pageId > 0) {
            // Loop over the whole rootline without permissions to get the actual site information
            try {
                $site = $this->finder->getSiteByPageId((int)$pageId);
                // If a "L" parameter is given, we take that one into account.
                $languageId = $request->getQueryParams()['L'] ?? $request->getParsedBody()['L'] ?? null;
                if ($languageId !== null) {
                    $language = $site->getLanguageById((int)$languageId);
                } else {
                    // Use this later below
                    $defaultLanguage = $site->getDefaultLanguage();
                }
            } catch (SiteNotFoundException $e) {
                // No site found by the given page
            } catch (\InvalidArgumentException $e) {
                // The language fetched by getLanguageById() was not available, now the PSR-15 middleware
                // redirects to the default page.
            }
        }

        $uri = $request->getUri();
        if (!empty($uri->getPath())) {
            $normalizedParams = $request->getAttribute('normalizedParams');
            if ($normalizedParams instanceof NormalizedParams) {
                $urlPath = ltrim($uri->getPath(), '/');
                $scriptName = ltrim($normalizedParams->getScriptName(), '/');
                $scriptPath = ltrim($normalizedParams->getSitePath(), '/');
                if ($scriptName !== '' && str_starts_with($urlPath, $scriptName)) {
                    $urlPath = '/' . $scriptPath . substr($urlPath, mb_strlen($scriptName));
                    $uri = $uri->withPath($urlPath);
                }
            }
        }

        // No language found at this point means that the URL was not used with a valid "?id=1&L=2" parameter
        // which resulted in a site / language combination that was found. Now, the matching is done
        // on the incoming URL.
        if (!($language instanceof SiteLanguage)) {
            $collection = $this->getRouteCollectionForAllSites();
            $context = new RequestContext(
                '',
                $request->getMethod(),
                (string)idn_to_ascii($uri->getHost()),
                $uri->getScheme(),
                // Ports are only necessary for URL generation in Symfony which is not used by TYPO3
                80,
                443,
                $uri->getPath()
            );
            $matcher = new UrlMatcher($collection, $context);
            try {
                $result = $matcher->match($uri->getPath());
                return new SiteRouteResult(
                    $uri,
                    $result['site'],
                    // if no language is found, this usually results due to "/" called instead of "/fr/"
                    // but it could also be the reason that "/index.php?id=23" was called, so the default
                    // language is used as a fallback here then.
                    $result['language'] ?? $defaultLanguage,
                    $result['tail']
                );
            } catch (NoConfigurationException | ResourceNotFoundException $e) {
                // At this point we discard a possible found site via ?id=123
                // Because ?id=123 _can_ only work if the actual domain/site base works
                // so www.domain-without-site-configuration/index.php?id=123 (where 123 is a page referring
                // to a page within a site configuration will never be resolved here) properly
                $site = new NullSite();
            }
        }

        return new SiteRouteResult($uri, $site, $language);
    }

    /**
     * If a given page ID is handed in, a Site/NullSite is returned.
     *
     * @param int $pageId uid of a page in default language
     * @param array|null $rootLine an alternative root line, if already at and.
     * @return SiteInterface
     * @throws SiteNotFoundException
     */
    public function matchByPageId(int $pageId, array $rootLine = null): SiteInterface
    {
        try {
            return $this->finder->getSiteByPageId($pageId, $rootLine);
        } catch (SiteNotFoundException $e) {
            return new NullSite();
        }
    }

    /**
     * Returns a Symfony RouteCollection containing all routes to all sites.
     *
     * @return RouteCollection
     */
    protected function getRouteCollectionForAllSites(): RouteCollection
    {
        $groupedRoutes = [];
        foreach ($this->finder->getAllSites() as $site) {
            // Add the site as entrypoint
            // @todo Find a way to test only this basic route against chinese characters, as site languages kicking
            //       always in. Do the rawurldecode() here to to be consistent with language preparations.
            $uri = $site->getBase();
            $route = new Route(
                (rawurldecode($uri->getPath()) ?: '/') . '{tail}',
                ['site' => $site, 'language' => null, 'tail' => ''],
                array_filter(['tail' => '.*', 'port' => (string)$uri->getPort()]),
                ['utf8' => true],
                // @todo Verify if host should here covered with idn_to_ascii() to be consistent with preparation for languages.
                $uri->getHost() ?: '',
                $uri->getScheme() === '' ? [] : [$uri->getScheme()]
            );
            $identifier = 'site_' . $site->getIdentifier();
            $groupedRoutes[($uri->getScheme() ?: '-') . ($uri->getHost() ?: '-')][$uri->getPath() ?: '/'][$identifier] = $route;
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
                $groupedRoutes[($uri->getScheme() ?: '-') . ($uri->getHost() ?: '-')][$uri->getPath() ?: '/'][$identifier] = $route;
            }
        }
        return $this->createRouteCollectionFromGroupedRoutes($groupedRoutes);
    }

    /**
     * As the {tail} parameter is greedy, it needs to be ensured that the one with the
     * most specific part matches first.
     *
     * @param array $groupedRoutes
     * @return RouteCollection
     */
    protected function createRouteCollectionFromGroupedRoutes(array $groupedRoutes): RouteCollection
    {
        $collection = new RouteCollection();
        // Ensure more generic routes containing '-' in host identifier, processed at last
        krsort($groupedRoutes);
        foreach ($groupedRoutes as $groupedRoutesPerHost) {
            krsort($groupedRoutesPerHost);
            foreach ($groupedRoutesPerHost as $groupedRoutesPerPath) {
                krsort($groupedRoutesPerPath);
                foreach ($groupedRoutesPerPath as $identifier => $route) {
                    $collection->add($identifier, $route);
                }
            }
        }
        return $collection;
    }
}
