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
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Identify the current request and resolve the site to it.
 * After that middleware, TSFE should be populated with
 * - language configuration
 * - site configuration
 *
 * Properties like config.sys_language_uid and config.language is then set for TypoScript.
 */
class SiteResolver implements MiddlewareInterface
{
    /**
     * Resolve the site/language information by checking the page ID or the URL.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $finder = GeneralUtility::makeInstance(SiteFinder::class);

        $site = null;
        $language = null;

        $pageId = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0;
        $languageId = $request->getQueryParams()['L'] ?? $request->getParsedBody()['L'] ?? null;

        // 1. Check if we have a _GET/_POST parameter for "id", then a site information can be resolved based.
        if ($pageId > 0 && $languageId !== null) {
            // Loop over the whole rootline without permissions to get the actual site information
            try {
                $site = $finder->getSiteByPageId((int)$pageId);
                $language = $site->getLanguageById($languageId);
            } catch (SiteNotFoundException $e) {
            }
        }
        if (!($language instanceof SiteLanguage)) {
            // 2. Check if there is a site language, if not, just don't do anything
            $language = $finder->getSiteLanguageByBase((string)$request->getUri());
            // @todo: use exception for getSiteLanguageByBase
            if ($language) {
                $site = $language->getSite();
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
        return $handler->handle($request);
    }
}
