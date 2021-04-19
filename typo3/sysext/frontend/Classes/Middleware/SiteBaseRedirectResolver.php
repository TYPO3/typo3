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

namespace TYPO3\CMS\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Resolves redirects of site if base is not /
 * Can be replaced or extended by extensions if GeoIP-based or user-agent based language redirects need to happen.
 *
 * Please note that the redirect usually does not contain the Query Parameters, as special query parameters
 * like "id", "L" and "cHash" could then result in an error loop.
 * One special case (adding a "/") is keeping the query parameters though.
 */
class SiteBaseRedirectResolver implements MiddlewareInterface
{
    /**
     * Redirect to default language if required
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = $request->getAttribute('site', null);
        $language = $request->getAttribute('language', null);
        $routeResult = $request->getAttribute('routing', null);

        // Usually called when "https://www.example.com" was entered, but all sites have "https://www.example.com/lang-key/"
        // So a redirect to the first possible language is done.
        if ($site instanceof Site && !($language instanceof SiteLanguage)) {
            if ($routeResult instanceof SiteRouteResult && $routeResult->getTail() === '') {
                $language = $site->getDefaultLanguage();
                if ($language->isEnabled()) {
                    return new RedirectResponse($language->getBase(), 307);
                }
                // Default language is disabled, check for the first (enabled) language in list to redirect to that
                foreach ($site->getLanguages() as $language) {
                    return new RedirectResponse($language->getBase(), 307);
                }
            }
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'The requested page does not exist',
                ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
            );
        }

        // Language is found, and hidden but also not visible to the BE user, this needs to fail
        if ($language instanceof SiteLanguage && !$this->isLanguageEnabled($language, $GLOBALS['BE_USER'] ?? null)) {
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page is not available in the requested language.',
                ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE]
            );
        }

        if ($language instanceof SiteLanguage && $routeResult instanceof SiteRouteResult) {
            $requestedUri = $request->getUri();
            $tail = $routeResult->getTail();
            // a URL was called via "/fr-FR/" but the page is actually called "/fr-FR", let's do a redirect
            if ($tail === '/') {
                $uri = $requestedUri->withPath(rtrim($requestedUri->getPath(), '/'));
                return new RedirectResponse($uri, 307);
            }
        }
        return $handler->handle($request);
    }

    /**
     * Checks if the language is allowed in Frontend, if not, check if there is valid BE user
     *
     * @param SiteLanguage $language
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
