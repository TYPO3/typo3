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
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\SiteMatcher;
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
        $site = $routeResult->getSite();
        $language = $routeResult->getLanguage();

        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('routing', $routeResult);

        // Usually called when "https://www.example.com" was entered, but all sites have "https://www.example.com/lang-key/"
        // So a redirect to the first possible language is done.
        if ($site instanceof Site && !($language instanceof SiteLanguage)) {
            $language = $site->getDefaultLanguage();
            $uri = new Uri($language->getBase());
            return new RedirectResponse($uri, 307);
        }
        // language is found, and hidden but also not visible to the BE user, this needs to fail
        if ($language instanceof SiteLanguage) {
            if (!$this->isLanguageEnabled($language, $GLOBALS['BE_USER'] ?? null)) {
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'Page is not available in the requested language.',
                    ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE]
                );
            }
            $requestedUri = $request->getUri();
            $tail = $routeResult->getTail();
            // a URL was called via "/fr-FR/" but the page is actually called "/fr-FR", let's do a redirect
            if ($tail === '/') {
                $uri = $requestedUri->withPath(rtrim($requestedUri->getPath(), '/'));
                return new RedirectResponse($uri, 307);
            }
            // Request was "/fr-FR" but the site is actually called "/fr-FR/", let's do a redirect
            if ($tail === '' && (string)(new Uri($language->getBase()))->getPath() !== (string)$requestedUri->getPath()) {
                $uri = $requestedUri->withPath($requestedUri->getPath() . '/');
                return new RedirectResponse($uri, 307);
            }
            $request = $request->withAttribute('language', $language);
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
}
