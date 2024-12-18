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

namespace TYPO3\CMS\Core\Routing;

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Utilizes the SiteMatcher to resolve a URL and return its UID.
 *
 * @internal
 */
final readonly class SiteUrlResolver
{
    public function __construct(
        private SiteMatcher $siteMatcher,
    ) {}

    /**
     * Searches the page UID by the full URI
     */
    public function resolvePageUidBySiteUrl(string $fullUri): ?int
    {
        $request = new ServerRequest($fullUri);
        /** @var SiteRouteResult $siteMatch */
        $siteMatch = $this->siteMatcher->matchRequest($request);
        $site = $siteMatch->getSite();
        $siteId = $site->getIdentifier();
        $languageId = $siteMatch->getLanguage()?->getLanguageId();
        if ($siteId === '' || $languageId === null || !($site instanceof Site)) {
            // Not a valid site.
            return null;
        }

        try {
            $route = $site->getRouter()->matchRequest($request, $siteMatch);
        } catch (RouteNotFoundException) {
            return null;
        }

        if ($route instanceof PageArguments && !$route->areDirty()) {
            return $route->getPageId();
        }

        return null;
    }

    /**
     * Searches the page UID by the full URI and returns the page UID including its language.
     *
     * @return ?array{uid: int, languageUid: int, languageName: string}
     */
    public function resolvePageUidAndLanguageBySiteUrl(string $fullUri): ?array
    {
        $request = new ServerRequest($fullUri);
        /** @var SiteRouteResult $siteMatch */
        $siteMatch = $this->siteMatcher->matchRequest($request);
        $site = $siteMatch->getSite();
        $siteId = $site->getIdentifier();
        $languageId = $siteMatch->getLanguage()?->getLanguageId();
        if ($siteId === '' || $languageId === null || !($site instanceof Site)) {
            // Not a valid site.
            return null;
        }

        try {
            $route = $site->getRouter()->matchRequest($request, $siteMatch);
        } catch (RouteNotFoundException) {
            return null;
        }

        if ($route instanceof PageArguments && !$route->areDirty()) {
            return [
                'uid' => $route->getPageId(),
                'languageUid' => $languageId,
                'languageName' => $site->getLanguageById($languageId)->getTitle(),
            ];
        }

        return null;
    }
}
