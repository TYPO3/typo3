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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Compatibility\LegacyDomainResolver;
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
     * Resolve the site/language information by checking the page ID or the URL.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $site = null;
        $language = null;

        $pageId = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0;
        $languageId = $request->getQueryParams()['L'] ?? $request->getParsedBody()['L'] ?? null;

        // 1. Check if we have a _GET/_POST parameter for "id", then a site information can be resolved based.
        if ($pageId > 0 && $languageId !== null) {
            // Loop over the whole rootline without permissions to get the actual site information
            try {
                $site = $this->finder->getSiteByPageId((int)$pageId);
                $language = $site->getLanguageById((int)$languageId);
                // language is hidden but also not visible to the BE user, this needs to fail
                if ($language && !$this->isLanguageEnabled($language, $GLOBALS['BE_USER'] ?? null)) {
                    $request = $request->withAttribute('site', $site);
                    return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'Page is not available in the requested language.',
                        ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE]
                    );
                }
            } catch (SiteNotFoundException $e) {
                // No site found by ID
            }
        }

        // 2. Check if there is a site language, if not, do "Site Routing"
        if (!($language instanceof SiteLanguage)) {
            $collection = $this->finder->getRouteCollectionForAllSites();
            // This part will likely be extracted into a separate class that builds a context out of a PSR-7 request
            // something like $result = SiteRouter->matchRequest($psr7Request);
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
                $site = $result['site'];
                $language = $result['language'];
                // language is found, and hidden but also not visible to the BE user, this needs to fail
                if ($language && !$this->isLanguageEnabled($language, $GLOBALS['BE_USER'] ?? null)) {
                    $request = $request->withAttribute('site', $site);
                    return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'Page is not available in the requested language.',
                        ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE]
                    );
                }
            } catch (ResourceNotFoundException $e) {
                // No site found
            }
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
            // At this point, we later get further route modifiers
            // for bw-compat we update $GLOBALS[TYPO3_REQUEST] to be used later in TSFE.
            $GLOBALS['TYPO3_REQUEST'] = $request;
        }

        // Now resolve the root page of the site, the page_id of the current domain
        if ($site instanceof Site) {
            $GLOBALS['TSFE']->domainStartPage = $site->getRootPageId();
        } else {
            $GLOBALS['TSFE']->domainStartPage = GeneralUtility::makeInstance(LegacyDomainResolver::class)
                ->matchRequest($request);
        }

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
