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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Exception\NoConfigurationException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\PseudoSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\PseudoSiteFinder;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Returns a site or pseudo-site (with sys_domain records) based on a given request.
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
     * @var PseudoSiteFinder
     */
    protected $pseudoSiteFinder;

    /**
     * Injects necessary objects. PseudoSiteFinder is not injectable as this will be become obsolete in the future.
     *
     * @param SiteFinder|null $finder
     */
    public function __construct(SiteFinder $finder = null)
    {
        $this->finder = $finder ?? GeneralUtility::makeInstance(SiteFinder::class);
        $this->pseudoSiteFinder = GeneralUtility::makeInstance(PseudoSiteFinder::class);
    }

    /**
     * First, it is checked, if a "id" GET/POST parameter is found.
     * If it is, we check for a valid site mounted there.
     *
     * If it isn't the quest continues by validating the whole request URL and validating against
     * all available site records (and their language prefixes).
     *
     * If none is found, the "legacy" handling is checked for - checking for all pseudo-sites with
     * a sys_domain record, and match against them.
     *
     * @param ServerRequestInterface $request
     * @return RouteResultInterface
     */
    public function matchRequest(ServerRequestInterface $request): RouteResultInterface
    {
        $site = null;
        $language = null;

        $pageId = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0;

        if (!empty($pageId) && !MathUtility::canBeInterpretedAsInteger($pageId)) {
            $pageId = (int)GeneralUtility::makeInstance(PageRepository::class)->getPageIdFromAlias($pageId);
        }
        // First, check if we have a _GET/_POST parameter for "id", then a site information can be resolved based.
        if ($pageId > 0) {
            // Loop over the whole rootline without permissions to get the actual site information
            try {
                $site = $this->finder->getSiteByPageId((int)$pageId);
                // If a "L" parameter is given, we take that one into account.
                $languageId = $request->getQueryParams()['L'] ?? $request->getParsedBody()['L'] ?? null;
                if ($languageId !== null) {
                    $language = $site->getLanguageById((int)$languageId);
                }
            } catch (SiteNotFoundException $e) {
                // No site found by the given page
            } catch (\InvalidArgumentException $e) {
                // The language fetched by getLanguageById() was not available, now the PSR-15 middleware
                // redirects to the default page.
            }
        }

        // No language found at this point means that the URL was not used with a valid "?id" parameter
        // which resulted in a site / language combination that was found. Now, the matching is done
        // on the incoming URL
        if (!($language instanceof SiteLanguage)) {
            $collection = $this->getRouteCollectionForAllSites();
            $context = new RequestContext(
                '',
                $request->getMethod(),
                $request->getUri()->getHost(),
                $request->getUri()->getScheme(),
                // Ports are only necessary for URL generation in Symfony which is not used by TYPO3
                80,
                443,
                $request->getUri()->getPath()
            );
            $matcher = new UrlMatcher($collection, $context);
            try {
                $result = $matcher->match($request->getUri()->getPath());
                return new SiteRouteResult($request->getUri(), $result['site'], $result['language'], $result['tail']);
            } catch (NoConfigurationException | ResourceNotFoundException $e) {
                // No site+language combination found so far
            }
            // At this point we discard a possible found site via ?id=123
            // Because ?id=123 _can_ only work if the actual domain/site base works
            // so www.domain-without-site-configuration/index.php?id=123 (where 123 is a page referring
            // to a page within a site configuration will never be resolved here) properly
            $site = null;
        }

        // Check against any sys_domain records
        $collection = $this->getRouteCollectionForVisibleSysDomains();
        $context = new RequestContext('/', $request->getMethod(), $request->getUri()->getHost());
        $matcher = new UrlMatcher($collection, $context);
        if ((bool)$GLOBALS['TYPO3_CONF_VARS']['SYS']['recursiveDomainSearch']) {
            $host = explode('.', $request->getUri()->getHost());
            while (count($host)) {
                $context->setHost(implode('.', $host));
                try {
                    $result = $matcher->match($request->getUri()->getPath());
                    return new SiteRouteResult($request->getUri(), $result['site'], $result['language'], $result['tail']);
                } catch (NoConfigurationException | ResourceNotFoundException $e) {
                    array_shift($host);
                }
            }
        } else {
            try {
                $result = $matcher->match($request->getUri()->getPath());
                return new SiteRouteResult($request->getUri(), $result['site'], $result['language'], $result['tail']);
            } catch (NoConfigurationException | ResourceNotFoundException $e) {
                // No domain record found
            }
        }
        // No domain record found, try resolving "pseudo-site" again
        if ($site == null) {
            try {
                // use the matching "pseudo-site" for $pageId
                $site = $this->pseudoSiteFinder->getSiteByPageId((int)$pageId);
            } catch (SiteNotFoundException $exception) {
                // use the first "pseudo-site" found
                $allPseudoSites = $this->pseudoSiteFinder->findAll();
                $site = reset($allPseudoSites);
            }
        }
        return new SiteRouteResult($request->getUri(), $site, $language);
    }

    /**
     * If a given page ID is handed in, a Site/PseudoSite/NullSite is returned.
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
            // Check for a pseudo / null site
            return $this->pseudoSiteFinder->getSiteByPageId($pageId, $rootLine);
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
            $uri = $site->getBase();
            $route = new Route(
                ($uri->getPath() ?: '/') . '{tail}',
                ['site' => $site, 'language' => null, 'tail' => ''],
                array_filter(['tail' => '.*', 'port' => (string)$uri->getPort()]),
                ['utf8' => true],
                $uri->getHost() ?: '',
                $uri->getScheme()
            );
            $identifier = 'site_' . $site->getIdentifier();
            $groupedRoutes[($uri->getScheme() ?: '-') . ($uri->getHost() ?: '-')][$uri->getPath() ?: '/'][$identifier] = $route;
            // Add all languages
            foreach ($site->getAllLanguages() as $siteLanguage) {
                $uri = $siteLanguage->getBase();
                $route = new Route(
                    ($uri->getPath() ?: '/') . '{tail}',
                    ['site' => $site, 'language' => $siteLanguage, 'tail' => ''],
                    array_filter(['tail' => '.*', 'port' => (string)$uri->getPort()]),
                    ['utf8' => true],
                    $uri->getHost() ?: '',
                    $uri->getScheme()
                );
                $identifier = 'site_' . $site->getIdentifier() . '_' . $siteLanguage->getLanguageId();
                $groupedRoutes[($uri->getScheme() ?: '-') . ($uri->getHost() ?: '-')][$uri->getPath() ?: '/'][$identifier] = $route;
            }
        }
        return $this->createRouteCollectionFromGroupedRoutes($groupedRoutes);
    }

    /**
     * Return the page ID (pid) of a sys_domain record, based on a request object, does the infamous
     * "recursive domain search", to also detect if the domain is like "abc.def.example.com" even if the
     * sys_domain entry is "example.com".
     *
     * @return RouteCollection
     */
    protected function getRouteCollectionForVisibleSysDomains(): RouteCollection
    {
        $sites = $this->pseudoSiteFinder->findAll();
        $groupedRoutes = [];
        foreach ($sites as $site) {
            if (!$site instanceof PseudoSite) {
                continue;
            }
            foreach ($site->getEntryPoints() as $uri) {
                // Site has no sys_domain record, it is not valid for a routing entrypoint, but only available
                // via "id" GET parameter which is handled separately
                if (!$uri->getHost()) {
                    continue;
                }
                $route = new Route(
                    ($uri->getPath() ?: '/') . '{tail}',
                    ['site' => $site, 'language' => null, 'tail' => ''],
                    array_filter(['tail' => '.*', 'port' => (string)$uri->getPort()]),
                    ['utf8' => true],
                    $uri->getHost(),
                    $uri->getScheme()
                );
                $identifier = 'site_' . $site->getIdentifier() . '_' . (string)$uri;
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
