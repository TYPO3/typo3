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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Page Router looking up the slug of the page path.
 *
 * This is done via the "Route Candidate" pattern.
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
 * The concept of the PageRouter is to *resolve*, and not build URIs. On top, it is a facade to hide the
 * dependency to symfony and to not expose its logic.

 * @internal This API is not public yet and might change in the future, until TYPO3 v9 or TYPO3 v10.
 */
class PageRouter
{
    /**
     * @param ServerRequestInterface $request
     * @param string $routePathTail
     * @param SiteInterface $site
     * @param SiteLanguage $language
     * @return RouteResult
     */
    public function matchRoute(ServerRequestInterface $request, string $routePathTail, SiteInterface $site, SiteLanguage $language): RouteResult
    {
        $slugCandidates = $this->getCandidateSlugsFromRoutePath($routePathTail);
        if (empty($slugCandidates)) {
            return null;
        }
        $pageCandidates = $this->getPagesFromDatabaseForCandidates($slugCandidates, $site, $language->getLanguageId());
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
            $result = $matcher->match('/' . $routePathTail);
            return new RouteResult($request->getUri(), $site, $language, $result['tail'], $result);
        } catch (ResourceNotFoundException $e) {
            // do nothing
        }
        return new RouteResult($request->getUri(), $site, $language);
    }

    /**
     * Check for records in the database which matches one of the slug candidates.
     *
     * @param array $slugCandidates
     * @param SiteInterface $site
     * @param int $languageId
     * @return array
     */
    protected function getPagesFromDatabaseForCandidates(array $slugCandidates, SiteInterface $site, int $languageId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

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
            if ($siteMatcher->matchByPageId($pageIdInDefaultLanguage)->getRootPageId() === $site->getRootPageId()) {
                $pages[] = $row;
            }
        }
        return $pages;
    }

    /**
     * Returns possible URL parts for a string like /home/about-us/offices/
     * to return
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
