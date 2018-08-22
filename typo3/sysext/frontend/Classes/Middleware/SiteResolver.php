<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\PseudoSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Identifies if a site is configured for the request, based on "id" and "L" GET/POST parameters, or the requested
 * string.
 *
 * If a site is found, the request is populated with the found language+site objects. If none is found, the main magic
 * is handled by the PageResolver middleware.
 *
 * In addition to that, TSFE gets the $domainStartPage information resolved and added.
 */
class SiteResolver implements MiddlewareInterface
{
    /**
     * @var SiteMatcher
     */
    protected $matcher;

    public function __construct(SiteMatcher $matcher = null)
    {
        $this->matcher = $matcher ?? GeneralUtility::makeInstance(
            SiteMatcher::class,
            GeneralUtility::makeInstance(SiteFinder::class)
        );
    }

    /**
     * Resolve the site/language information by checking the page ID or the URL.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $this->matcher->matchRequest($request);
        $site = $routeResult['site'] ?? null;
        $language = $routeResult['language'] ?? null;
        $routePath = $routeResult['next'] ?? '';

        // language is found, and hidden but also not visible to the BE user, this needs to fail
        if ($language instanceof SiteLanguage && !$this->isLanguageEnabled($language, $GLOBALS['BE_USER'] ?? null)) {
            $request = $request->withAttribute('site', $site);
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page is not available in the requested language.',
                ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE]
            );
        }

        // Add language+site information to the PSR-7 request object.
        if ($language instanceof SiteLanguage && $site instanceof Site) {
            $request = $request->withAttribute('site', $site);
            $request = $request->withAttribute('language', $language);
            $queryParams = $request->getQueryParams();
            // necessary to calculate the proper hash base
            $queryParams['L'] = $language->getLanguageId();
            $request = $request->withQueryParams($queryParams);
            $_GET['L'] = $queryParams['L'];

            $routePath = ltrim($routePath, '/');
            if (!empty($routePath)) {
                // Check for the route
                $routeResult = $this->getPageRouter()
                    ->matchRoute($request, $routePath, $site, $language);
                if (is_array($routeResult)) {
                    $page = $routeResult['page'];
                    $pageId = (int)($page['l10n_parent'] > 0 ? $page['l10n_parent'] : $page['uid']);
                    // @todo: we could move the middleware earlier which will make A LOT OF things easier
                    $GLOBALS['TSFE']->id = $pageId;
                    $_GET['id'] = $pageId;
                    $queryParams = $request->getQueryParams();
                    $queryParams['id'] = $pageId;
                    $request = $request->withQueryParams($queryParams);
                    if (!empty($routeResult['next'] ?? '')) {
                        if ($routeResult['next'] === '/') {
                            // a URL was called via "/mysite/" but the page is actually called "/mysite"
                            // let's do a redirect
                            $uri = $request->getUri();
                            $path = rtrim($uri->getPath(), '/');
                            $uri = $uri->withPath($path);
                            return new RedirectResponse($uri, 301);
                        }
                        // @todo: kick in the resolvers for the RouteEnhancers at this point
                        return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                            $request,
                            'The requested page does not exist',
                            ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                        );
                    }
                } else {
                    return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'The requested page does not exist',
                        ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                    );
                }
            }
        } elseif ($site instanceof PseudoSite) {
            $request = $request->withAttribute('site', $site);
        }
        // At this point, we later get further route modifiers
        // for bw-compat we update $GLOBALS[TYPO3_REQUEST] to be used later in TSFE.
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return $handler->handle($request);
    }

    /**
     * Checks if the language is allowed in Frontend, if not, check if there is valid BE user
     *
     * @param SiteLanguage|null $language
     * @param BackendUserAuthentication|null $user
     * @return bool
     */
    protected function isLanguageEnabled(SiteLanguage $language, BackendUserAuthentication $user = null): bool
    {
        // language is hidden, check if a possible backend user is allowed to access the language
        if ($language->enabled() || ($user instanceof BackendUserAuthentication && $user->checkLanguageAccess($language->getLanguageId()))) {
            return true;
        }
        return false;
    }

    /**
     * @return PageRouter
     */
    protected function getPageRouter(): PageRouter
    {
        return GeneralUtility::makeInstance(PageRouter::class);
    }
}
