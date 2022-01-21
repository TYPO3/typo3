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

namespace TYPO3\CMS\Redirects\Service;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

/**
 * Creates a proper URL to redirect from a matched redirect of a request
 *
 * @internal due to some possible refactorings in TYPO3 v9
 */
class RedirectService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RedirectCacheService
     */
    protected $redirectCacheService;

    /**
     * @var LinkService
     */
    protected $linkService;

    /**
     * @var SiteFinder
     */
    protected $siteFinder;

    public function __construct(RedirectCacheService $redirectCacheService, LinkService $linkService, SiteFinder $siteFinder)
    {
        $this->redirectCacheService = $redirectCacheService;
        $this->linkService = $linkService;
        $this->siteFinder = $siteFinder;
    }

    /**
     * Checks against all available redirects "flat" or "regexp", and against starttime/endtime
     */
    public function matchRedirect(string $domain, string $path, string $query = ''): ?array
    {
        $path = rawurldecode($path);
        // Check if the domain matches, or if there is a
        // redirect fitting for any domain
        foreach ([$domain, '*'] as $domainName) {
            $redirects = $this->fetchRedirects($domainName);
            if (empty($redirects)) {
                continue;
            }

            // check if a flat redirect matches with the Query applied
            if (!empty($query)) {
                $pathWithQuery = rtrim($path, '/') . '?' . ltrim($query, '?');
                if (!empty($redirects['respect_query_parameters'][$pathWithQuery])) {
                    if ($matchedRedirect = $this->getFirstActiveRedirectFromPossibleRedirects($redirects['respect_query_parameters'][$pathWithQuery])) {
                        return $matchedRedirect;
                    }
                } else {
                    $pathWithQueryAndSlash = rtrim($path, '/') . '/?' . ltrim($query, '?');
                    if (!empty($redirects['respect_query_parameters'][$pathWithQueryAndSlash])) {
                        if ($matchedRedirect = $this->getFirstActiveRedirectFromPossibleRedirects($redirects['respect_query_parameters'][$pathWithQueryAndSlash])) {
                            return $matchedRedirect;
                        }
                    }
                }
            }

            // check if a flat redirect matches
            if (!empty($redirects['flat'][rtrim($path, '/') . '/'])) {
                if ($matchedRedirect = $this->getFirstActiveRedirectFromPossibleRedirects($redirects['flat'][rtrim($path, '/') . '/'])) {
                    return $matchedRedirect;
                }
            }

            // check all redirects that are registered as regex
            if (!empty($redirects['regexp'])) {
                $allRegexps = array_keys($redirects['regexp']);
                $regExpPath = $path;
                if (!empty($query)) {
                    $regExpPath .= '?' . ltrim($query, '?');
                }
                foreach ($allRegexps as $regexp) {
                    $matchResult = @preg_match((string)$regexp, $regExpPath);
                    if ($matchResult > 0) {
                        if ($matchedRedirect = $this->getFirstActiveRedirectFromPossibleRedirects($redirects['regexp'][$regexp])) {
                            return $matchedRedirect;
                        }
                        continue;
                    }

                    // Log invalid regular expression
                    if ($matchResult === false) {
                        $this->logger->warning('Invalid regex in redirect', ['regex' => $regexp]);
                        continue;
                    }

                    // We need a second match run to evaluate against path only, even when query parameters where
                    // provided to ensure regexp without query parameters in mind are still processed.
                    // We need to do this only if there are query parameters in the request, otherwise first
                    // preg_match would have found it.
                    if (!empty($query)) {
                        $matchResult = preg_match((string)$regexp, $path);
                        if ($matchResult > 0) {
                            if ($matchedRedirect = $this->getFirstActiveRedirectFromPossibleRedirects($redirects['regexp'][$regexp])) {
                                return $matchedRedirect;
                            }
                            continue;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if a redirect record matches the starttime and endtime and disable restrictions
     *
     * @param array $redirectRecord
     * @return bool whether the redirect is active and should be used for redirecting the current request
     */
    protected function isRedirectActive(array $redirectRecord): bool
    {
        return !$redirectRecord['disabled'] && $redirectRecord['starttime'] <= $GLOBALS['SIM_ACCESS_TIME'] &&
               (!$redirectRecord['endtime'] || $redirectRecord['endtime'] >= $GLOBALS['SIM_ACCESS_TIME']);
    }

    /**
     * Fetches all redirects from cache, with fallback to rebuild cache from the DB if caches was empty,
     * grouped by the domain does NOT take starttime/endtime into account, as it is cached.
     */
    protected function fetchRedirects(string $sourceHost): array
    {
        return $this->redirectCacheService->getRedirects($sourceHost);
    }

    /**
     * Check if the current request is actually a redirect, and then process the redirect.
     *
     * @param string $redirectTarget
     * @return array the link details from the linkService
     */
    protected function resolveLinkDetailsFromLinkTarget(string $redirectTarget): array
    {
        try {
            $linkDetails = $this->linkService->resolve($redirectTarget);
            switch ($linkDetails['type']) {
                case LinkService::TYPE_URL:
                    // all set up, nothing to do
                    break;
                case LinkService::TYPE_FILE:
                    /** @var File $file */
                    $file = $linkDetails['file'];
                    if ($file instanceof File) {
                        $linkDetails['url'] = $file->getPublicUrl();
                    }
                    break;
                case LinkService::TYPE_FOLDER:
                    /** @var Folder $folder */
                    $folder = $linkDetails['folder'];
                    if ($folder instanceof Folder) {
                        $linkDetails['url'] = $folder->getPublicUrl();
                    }
                    break;
                case LinkService::TYPE_UNKNOWN:
                    // If $redirectTarget could not be resolved, we can only assume $redirectTarget with leading '/'
                    // as relative redirect and try to resolve it with enriched information from current request.
                    // That ensures that regexp redirects ending in replaceRegExpCaptureGroup(), but also ensures
                    // that relative urls are not left as unknown file here.
                    if (str_starts_with($redirectTarget, '/')) {
                        $linkDetails = [
                            'type' => LinkService::TYPE_URL,
                            'url' => $redirectTarget,
                        ];
                    }
                    break;
                default:
                    // we have to return the link details without having a "URL" parameter
            }
        } catch (InvalidPathException $e) {
            return [];
        }

        return $linkDetails;
    }

    public function getTargetUrl(array $matchedRedirect, ServerRequestInterface $request): ?UriInterface
    {
        $site = $request->getAttribute('site');
        $uri = $request->getUri();
        $queryParams = $request->getQueryParams();
        $this->logger->debug('Found a redirect to process', ['redirect' => $matchedRedirect]);
        $linkParameterParts = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode((string)$matchedRedirect['target']);
        $redirectTarget = $linkParameterParts['url'];
        $linkDetails = $this->resolveLinkDetailsFromLinkTarget($redirectTarget);
        $this->logger->debug('Resolved link details for redirect', ['details' => $linkDetails]);
        if (!empty($linkParameterParts['additionalParams']) && $matchedRedirect['keep_query_parameters']) {
            $params = GeneralUtility::explodeUrl2Array($linkParameterParts['additionalParams']);
            foreach ($params as $key => $value) {
                $queryParams[$key] = $value;
            }
        }
        // Do this for files, folders, external URLs or relative urls
        if (!empty($linkDetails['url'])) {
            if ($matchedRedirect['is_regexp'] ?? false) {
                $linkDetails = $this->replaceRegExpCaptureGroup($matchedRedirect, $uri, $linkDetails);
            }

            $url = new Uri($linkDetails['url']);
            if ($matchedRedirect['force_https']) {
                $url = $url->withScheme('https');
            }
            if ($matchedRedirect['keep_query_parameters']) {
                $url = $this->addQueryParams($queryParams, $url);
            }
            return $url;
        }
        $site = $this->resolveSite($linkDetails, $site);
        // If it's a record or page, then boot up TSFE and use typolink
        return $this->getUriFromCustomLinkDetails(
            $matchedRedirect,
            $site,
            $linkDetails,
            $queryParams,
            $request
        );
    }

    /**
     * If no site is given, try to find a valid site for the target page
     */
    protected function resolveSite(array $linkDetails, ?SiteInterface $site): ?SiteInterface
    {
        if (($site === null || $site instanceof NullSite) && ($linkDetails['type'] ?? '') === 'page') {
            try {
                return $this->siteFinder->getSiteByPageId((int)$linkDetails['pageuid']);
            } catch (SiteNotFoundException $e) {
                return new NullSite();
            }
        }
        return $site;
    }

    /**
     * Adds query parameters to a Uri object
     */
    protected function addQueryParams(array $queryParams, Uri $url): Uri
    {
        // New query parameters overrule the ones that should be kept
        $newQueryParamString = $url->getQuery();
        if (!empty($newQueryParamString)) {
            $newQueryParams = [];
            parse_str($newQueryParamString, $newQueryParams);
            $queryParams = array_replace_recursive($queryParams, $newQueryParams);
        }
        $query = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        if ($query) {
            $url = $url->withQuery($query);
        }
        return $url;
    }

    /**
     * Called when TypoScript/TSFE is available, so typolink is used to generate the URL
     */
    protected function getUriFromCustomLinkDetails(array $redirectRecord, ?SiteInterface $site, array $linkDetails, array $queryParams, ServerRequestInterface $originalRequest): ?UriInterface
    {
        if (!isset($linkDetails['type'], $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder'][$linkDetails['type']])) {
            return null;
        }
        if ($site === null || $site instanceof NullSite) {
            return null;
        }
        $controller = $this->bootFrontendController($site, $queryParams, $originalRequest);
        /** @var AbstractTypolinkBuilder $linkBuilder */
        $linkBuilder = GeneralUtility::makeInstance(
            $GLOBALS['TYPO3_CONF_VARS']['FE']['typolinkBuilder'][$linkDetails['type']],
            $controller->cObj,
            $controller
        );
        try {
            $configuration = [
                'parameter' => (string)$redirectRecord['target'],
                'forceAbsoluteUrl' => true,
                'linkAccessRestrictedPages' => true,
            ];
            if ($redirectRecord['force_https']) {
                $configuration['forceAbsoluteUrl.']['scheme'] = 'https';
            }
            if ($redirectRecord['keep_query_parameters']) {
                $configuration['additionalParams'] = HttpUtility::buildQueryString($queryParams, '&');
            }
            $result = $linkBuilder->build($linkDetails, '', '', $configuration);
            if (is_array($result)) {
                return new Uri($result[0] ?? '');
            }
            if ($result instanceof LinkResultInterface) {
                return new Uri($result->getUrl());
            }
            return null;
        } catch (UnableToLinkException $e) {
            // This exception is also thrown by the DatabaseRecordTypolinkBuilder
            $url = $controller->cObj->lastTypoLinkUrl;
            if (!empty($url)) {
                return new Uri($url);
            }
            return null;
        }
    }

    /**
     * Finishing booting up TSFE, after that the following properties are available.
     *
     * Instantiating is done by the middleware stack (see Configuration/RequestMiddlewares.php)
     *
     * - TSFE->fe_user
     * - TSFE->sys_page
     * - TSFE->tmpl
     * - TSFE->config
     * - TSFE->cObj
     *
     * So a link to a page can be generated.
     */
    protected function bootFrontendController(SiteInterface $site, array $queryParams, ServerRequestInterface $originalRequest): TypoScriptFrontendController
    {
        $controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $site,
            $site->getDefaultLanguage(),
            new PageArguments($site->getRootPageId(), '0', []),
            $originalRequest->getAttribute('frontend.user')
        );
        $controller->determineId($originalRequest);
        $controller->calculateLinkVars($queryParams);
        $controller->getConfigArray();
        $controller->newCObj($originalRequest);
        if (!isset($GLOBALS['TSFE']) || !$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            $GLOBALS['TSFE'] = $controller;
        }
        if (!$GLOBALS['TSFE']->sys_page instanceof PageRepository) {
            $GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        }
        return $controller;
    }

    protected function replaceRegExpCaptureGroup(array $matchedRedirect, UriInterface $uri, array $linkDetails): array
    {
        $uriToCheck = rawurldecode($uri->getPath());
        if (($matchedRedirect['respect_query_parameters'] ?? false) && $uri->getQuery()) {
            $uriToCheck .= '?' . rawurldecode($uri->getQuery());
        }
        $matchResult = preg_match($matchedRedirect['source_path'], $uriToCheck, $matches);
        if ($matchResult > 0) {
            foreach ($matches as $key => $val) {
                // Unsafe regexp captching group may lead to adding query parameters to result url, which we need
                // to prevent here, thus throwing everything beginning with ? away
                if (strpos($val, '?') !== false) {
                    $val = explode('?', $val, 2)[0] ?? '';
                    $this->logger->warning(
                        sprintf(
                            'Unsafe captching group regex in redirect #%s, including query parameters in matched group',
                            $matchedRedirect['uid'] ?? 0
                        ),
                        ['regex' => $matchedRedirect['source_path']]
                    );
                }
                $linkDetails['url'] = str_replace('$' . $key, $val, $linkDetails['url']);
            }
        }
        return $linkDetails;
    }

    /**
     * Checks all possible redirects and return the first possible and active redirect if available.
     */
    protected function getFirstActiveRedirectFromPossibleRedirects(array $possibleRedirects): ?array
    {
        foreach ($possibleRedirects as $possibleRedirect) {
            if ($this->isRedirectActive($possibleRedirect)) {
                return $possibleRedirect;
            }
        }

        return null;
    }
}
