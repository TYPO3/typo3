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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * The concept of the PageRouter is to *resolve*, and to build URIs. On top, it is a facade to hide the
 * dependency to symfony and to not expose its logic.
 */
class PageRouter implements RouterInterface
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * A page router is always bound to a specific site.
     *
     * @param Site $site
     * @param array $configuration
     */
    public function __construct(Site $site, array $configuration)
    {
        $this->site = $site;
        $this->configuration = $configuration;
    }

    /**
     * Finds a RouteResult based on the given request.
     *
     * @param ServerRequestInterface $request
     * @param RouteResultInterface|RouteResult|null $previousResult
     * @return RouteResult
     */
    public function matchRequest(ServerRequestInterface $request, RouteResultInterface $previousResult = null): ?RouteResultInterface
    {
        $routePathTail = $previousResult ? $previousResult->getTail() : '';
        $language = $previousResult ? $previousResult->getLanguage() : null;
        $slugCandidates = $this->getCandidateSlugsFromRoutePath($routePathTail);
        if (empty($slugCandidates)) {
            return null;
        }
        $pageCandidates = $this->getPagesFromDatabaseForCandidates($slugCandidates, $language->getLanguageId());
        // Stop if there are no candidates
        if (empty($pageCandidates)) {
            return null;
        }

        $collection = new RouteCollection();
        foreach ($pageCandidates ?? [] as $page) {
            $path = $page['slug'];
            $route = new Route(
                $path . '{tail}',
                ['page' => $page, 'tail' => ''],
                ['tail' => '.*'],
                ['utf8' => true]
            );
            $collection->add('page_' . $page['uid'], $route);
        }

        $context = new RequestContext('/', $request->getMethod(), $request->getUri()->getHost());
        $matcher = new UrlMatcher($collection, $context);
        try {
            $result = $matcher->match('/' . ltrim($routePathTail, '/'));
            unset($result['_route']);
            return new RouteResult($request->getUri(), $this->site, $language, $result['tail'], $result);
        } catch (ResourceNotFoundException $e) {
            // do nothing
        }
        return new RouteResult($request->getUri(), $this->site, $language);
    }

    /**
     * API for generating a page where the $route parameter is typically an array (page record) or the page ID
     *
     * @param array|string $route
     * @param array $parameters an array of query parameters which can be built into the URI path, also consider the special handling of "_language"
     * @param string $fragment additional #my-fragment part
     * @param string $type see the RouterInterface for possible types
     * @return UriInterface
     */
    public function generateUri($route, array $parameters = [], string $fragment = '', string $type = ''): UriInterface
    {
        // Resolve site
        $siteLanguage = null;
        $languageOption = $parameters['_language'] ?? null;
        if ($languageOption instanceof SiteLanguage) {
            $siteLanguage = $languageOption;
            unset($parameters['_language']);
        }
        if ($siteLanguage === null) {
            $siteLanguage = $this->site->getDefaultLanguage();
        }

        $pageId = 0;
        if (is_array($route)) {
            $pageId = (int)$route['uid'];
        } elseif (is_scalar($route)) {
            $pageId = (int)$route;
        }
        $pageRecord = BackendUtility::getRecord('pages', $pageId);
        if ($siteLanguage->getLanguageId() > 0) {
            $pageLocalizations = BackendUtility::getRecordLocalization('pages', $pageId, $siteLanguage->getLanguageId());
            $pageRecord = $pageLocalizations[0] ?? $pageRecord;
        }
        $prefix = (string)$siteLanguage->getBase();
        $prefix = rtrim($prefix, '/') . '/' . ltrim($pageRecord['slug'] ?? '', '/');

        // Add the query parameters as string
        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $prefix = rtrim($prefix, '?');
        if (!empty($queryString)) {
            if (strpos($prefix, '?') === false) {
                $prefix .= '?';
            } else {
                $prefix .= '&';
            }
        }
        $uri = new Uri($prefix . $queryString);
        if ($fragment) {
            $uri = $uri->withFragment($fragment);
        }
        if ($type === self::ABSOLUTE_PATH) {
            $uri = $uri->withScheme('')->withHost('')->withPort(null);
        }
        return $uri;
    }

    /**
     * Check for records in the database which matches one of the slug candidates.
     *
     * @param array $slugCandidates
     * @param int $languageId
     * @return array
     */
    protected function getPagesFromDatabaseForCandidates(array $slugCandidates, int $languageId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(FrontendWorkspaceRestriction::class));

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
        $siteMatcher = GeneralUtility::makeInstance(SiteMatcher::class);
        while ($row = $statement->fetch()) {
            $pageIdInDefaultLanguage = (int)($languageId > 0 ? $row['l10n_parent'] : $row['uid']);
            if ($siteMatcher->matchByPageId($pageIdInDefaultLanguage)->getRootPageId() === $this->site->getRootPageId()) {
                $pages[] = $row;
            }
        }
        return $pages;
    }

    /**
     * Returns possible URL parts for a string like /home/about-us/offices/
     * to return.
     *
     * /home/about-us/offices/
     * /home/about-us/offices
     * /home/about-us/
     * /home/about-us
     * /home/
     * /home
     *
     * @param string $routePath
     * @return array
     */
    protected function getCandidateSlugsFromRoutePath(string $routePath): array
    {
        $candidatePathParts = [];
        $pathParts = GeneralUtility::trimExplode('/', $routePath, true);
        while (!empty($pathParts)) {
            $prefix = '/' . implode('/', $pathParts);
            $candidatePathParts[] = $prefix . '/';
            $candidatePathParts[] = $prefix;
            array_pop($pathParts);
        }
        return $candidatePathParts;
    }
}
