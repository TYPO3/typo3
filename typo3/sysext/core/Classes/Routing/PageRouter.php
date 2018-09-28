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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * A page router is always bound to a specific site.
     *
     * @param Site $site
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
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
        $slugCandidates = $this->getCandidateSlugsFromRoutePath($previousResult->getTail());
        if (empty($slugCandidates)) {
            return null;
        }
        $language = $previousResult->getLanguage();
        $pageCandidates = $this->getPagesFromDatabaseForCandidates($slugCandidates, $language->getLanguageId());
        // Stop if there are no candidates
        if (empty($pageCandidates)) {
            return null;
        }

        $fullCollection = new RouteCollection();
        foreach ($pageCandidates ?? [] as $page) {
            $pagePath = $page['slug'];
            $defaultRouteForPage = new Route(
                $pagePath . '{tail}',
                ['tail' => ''],
                ['tail' => '.*'],
                ['utf8' => true, '_page' => $page]
            );
            $fullCollection->add('page_' . $page['uid'], $defaultRouteForPage);
        }

        $context = new RequestContext('/', $request->getMethod(), $request->getUri()->getHost());
        $matcher = new UrlMatcher($fullCollection, $context);
        try {
            $result = $matcher->match('/' . ltrim($previousResult->getTail(), '/'));
            /** @var Route $matchedRoute */
            $matchedRoute = $fullCollection->get($result['_route']);
            unset($result['_route']);
            return $this->buildRouteResult($request, $language, $matchedRoute, $result);
        } catch (ResourceNotFoundException $e) {
            // return nothing
        }
        return null;
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

        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect($language->getLanguageId()));
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        $page = $pageRepository->getPage($pageId, true);
        $pagePath = ltrim($page['slug'] ?? '', '/');

        $prefix = (string)$language->getBase();
        $prefix = rtrim($prefix, '/') . '/' . $pagePath;

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
        if ($type === RouterInterface::ABSOLUTE_PATH) {
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

    /**
     * @param ServerRequestInterface $request
     * @param SiteLanguage|null $language
     * @param Route|null $route
     * @param array $results
     * @return RouteResult
     */
    protected function buildRouteResult(ServerRequestInterface $request, SiteLanguage $language, Route $route, array $results = []): RouteResult
    {
        $data = [];
        // page record the route has been applied for
        if ($route->hasOption('_page')) {
            $data['page'] = $route->getOption('_page');
        }
        $tail = $results['tail'] ?? '';
        return new RouteResult($request->getUri(), $this->site, $language, $tail, $data);
    }
}
