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
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
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
 */
class SiteMatcher
{
    /**
     * @var SiteFinder
     */
    protected $finder;

    /**
     * Injects necessary objects
     * @param SiteFinder|null $finder
     */
    public function __construct(SiteFinder $finder = null)
    {
        $this->finder = $finder ?? GeneralUtility::makeInstance(SiteFinder::class);
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
     * @return array
     */
    public function matchRequest(ServerRequestInterface $request): array
    {
        $site = null;
        $language = null;

        $pageId = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0;
        $languageId = $request->getQueryParams()['L'] ?? $request->getParsedBody()['L'] ?? null;

        if (!empty($pageId) && !MathUtility::canBeInterpretedAsInteger($pageId)) {
            $pageId = (int)GeneralUtility::makeInstance(PageRepository::class)->getPageIdFromAlias($pageId);
        }
        // First, check if we have a _GET/_POST parameter for "id", then a site information can be resolved based.
        if ($pageId > 0 && $languageId !== null) {
            // Loop over the whole rootline without permissions to get the actual site information
            try {
                $site = $this->finder->getSiteByPageId((int)$pageId);
                // If a "L" parameter is given, we take that one into account.
                if ($languageId !== null) {
                    $language = $site->getLanguageById((int)$languageId);
                } else {
                    $allLanguages = $site->getLanguages();
                    $language = reset($allLanguages);
                }
            } catch (SiteNotFoundException $e) {
                // No site found by ID
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
                return $matcher->match($request->getUri()->getPath());
            } catch (NoConfigurationException | ResourceNotFoundException $e) {
                // No site found
            }
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
                    return $matcher->match($request->getUri()->getPath());
                } catch (NoConfigurationException | ResourceNotFoundException $e) {
                    array_shift($host);
                }
            }
        } else {
            try {
                return $matcher->match($request->getUri()->getPath());
            } catch (NoConfigurationException | ResourceNotFoundException $e) {
                // No domain record found
            }
        }
        return ['site' => $site, 'language' => $language];
    }

    /**
     * Returns a Symfony RouteCollection containing all routes to all sites.
     *
     * {next} is not evaluated yet, but set as suffix and will change in the future.
     *
     * @return RouteCollection
     */
    protected function getRouteCollectionForAllSites(): RouteCollection
    {
        $groupedRoutes = [];
        foreach ($this->finder->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $siteLanguage) {
                $urlParts = parse_url($siteLanguage->getBase());
                $route = new Route(
                    ($urlParts['path'] ?? '/') . '{next}',
                    ['site' => $site, 'language' => $siteLanguage, 'next' => ''],
                    array_filter(['next' => '.*', 'port' => (string)($urlParts['port'] ?? '')]),
                    ['utf8' => true],
                    $urlParts['host'] ?? '',
                    !empty($urlParts['scheme']) ? [$urlParts['scheme']] : null
                );
                $identifier = 'site_' . $site->getIdentifier() . '_' . $siteLanguage->getLanguageId();
                $groupedRoutes[($urlParts['scheme'] ?? '-') . ($urlParts['host'] ?? '-')][$urlParts['path'] ?? '/'][$identifier] = $route;
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
        $sites = GeneralUtility::makeInstance(PseudoSiteFinder::class)->findAll();
        $groupedRoutes = [];
        foreach ($sites as $site) {
            foreach ($site->getEntryPoints() as $domainName) {
                // Site has no sys_domain record, it is not valid for a routing entrypoint, but only available
                // via "id" GET parameter which is handled before
                if ($domainName === '/') {
                    continue;
                }
                $urlParts = parse_url($domainName);
                $route = new Route(
                    ($urlParts['path'] ?? '/') . '{next}',
                    ['site' => $site, 'language' => null, 'next' => ''],
                    array_filter(['next' => '.*', 'port' => (string)($urlParts['port'] ?? '')]),
                    ['utf8' => true],
                    $urlParts['host'] ?? '',
                    !empty($urlParts['scheme']) ? [$urlParts['scheme']] : null
                );
                $identifier = 'site_' . $site->getIdentifier() . '_' . $domainName;
                $groupedRoutes[($urlParts['scheme'] ?? '-') . ($urlParts['host'] ?? '-')][$urlParts['path'] ?? '/'][$identifier] = $route;
            }
        }
        return $this->createRouteCollectionFromGroupedRoutes($groupedRoutes);
    }

    /**
     * As the {next} parameter is greedy, it needs to be ensured that the one with the
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
