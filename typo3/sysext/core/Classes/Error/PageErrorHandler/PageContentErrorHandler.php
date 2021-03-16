<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Error\PageErrorHandler;

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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the content of a page to be displayed (also in relation to language etc)
 * This is typically configured via the "Sites configuration" module in the backend.
 */
class PageContentErrorHandler implements PageErrorHandlerInterface
{

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var array
     */
    protected $errorHandlerConfiguration;

    /**
     * @var int
     */
    protected $pageUid = 0;

    /**
     * PageContentErrorHandler constructor.
     * @param int $statusCode
     * @param array $configuration
     * @throws \InvalidArgumentException
     */
    public function __construct(int $statusCode, array $configuration)
    {
        $this->statusCode = $statusCode;
        if (empty($configuration['errorContentSource'])) {
            throw new \InvalidArgumentException('PageContentErrorHandler needs to have a proper link set.', 1522826413);
        }
        $this->errorHandlerConfiguration = $configuration;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $message
     * @param array $reasons
     * @return ResponseInterface
     * @throws \RuntimeException
     * @throws NoSuchCacheException
     */
    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        try {
            $resolvedUrl = $this->resolveUrl($request, $this->errorHandlerConfiguration['errorContentSource']);

            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_pages');
            $cacheIdentifier = 'errorPage_' . md5($resolvedUrl);
            $cacheContent = $cache->get($cacheIdentifier);

            if (!$cacheContent && $resolvedUrl !== (string)$request->getUri()) {
                try {
                    $subResponse = GeneralUtility::makeInstance(RequestFactory::class)
                        ->request($resolvedUrl, 'GET', $this->getSubRequestOptions());
                } catch (\Exception $e) {
                    throw new \RuntimeException('Error handler could not fetch error page "' . $resolvedUrl . '", reason: ' . $e->getMessage(), 1544172838);
                }
                if ($subResponse->getStatusCode() >= 300) {
                    throw new \RuntimeException('Error handler could not fetch error page "' . $resolvedUrl . '", status code: ' . $subResponse->getStatusCode(), 1544172839);
                }

                $body = $subResponse->getBody()->getContents();
                $contentType = $subResponse->getHeader('Content-Type');

                // Cache body and content-type if sub-response returned a HTTP status 200
                if ($subResponse->getStatusCode() === 200) {
                    $cacheTags = ['errorPage'];
                    if ($this->pageUid > 0) {
                        // Cache Tag "pageId_" ensures, cache is purged when content of 404 page changes
                        $cacheTags[] = 'pageId_' . $this->pageUid;
                    }
                    $cacheContent = [
                        'body' => $body,
                        'headers' => ['Content-Type' => $contentType],
                    ];
                    $cache->set($cacheIdentifier, $cacheContent, $cacheTags);
                }
            }
            if ($cacheContent && $cacheContent['body'] && $cacheContent['headers']) {
                // We use a HtmlResponse here, since no Stream is available for cached response content
                return new HtmlResponse($cacheContent['body'], $this->statusCode, $cacheContent['headers']);
            }
        } catch (InvalidRouteArgumentsException | SiteNotFoundException $e) {
            $content = 'Invalid error handler configuration: ' . $this->errorHandlerConfiguration['errorContentSource'];
        }
        return new HtmlResponse($content, $this->statusCode);
    }

    /**
     * Returns request options for the subrequest and ensures, that a reasoneable timeout is present
     *
     * @return array|int[]
     */
    protected function getSubRequestOptions(): array
    {
        $options = [];
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout'] === 0) {
            $options = [
                'timeout' => 30
            ];
        }
        return $options;
    }

    /**
     * Resolve the URL (currently only page and external URL are supported)
     *
     * @param ServerRequestInterface $request
     * @param string $typoLinkUrl
     * @return string
     * @throws SiteNotFoundException
     * @throws InvalidRouteArgumentsException
     */
    protected function resolveUrl(ServerRequestInterface $request, string $typoLinkUrl): string
    {
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        $urlParams = $linkService->resolve($typoLinkUrl);
        if ($urlParams['type'] !== 'page' && $urlParams['type'] !== 'url') {
            throw new \InvalidArgumentException('PageContentErrorHandler can only handle TYPO3 urls of types "page" or "url"', 1522826609);
        }
        if ($urlParams['type'] === 'url') {
            return $urlParams['url'];
        }

        $this->pageUid = (int)$urlParams['pageuid'];

        // Get the site related to the configured error page
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($this->pageUid);
        // Fall back to current request for the site
        if (!$site instanceof Site) {
            $site = $request->getAttribute('site', null);
        }
        /** @var SiteLanguage $requestLanguage */
        $requestLanguage = $request->getAttribute('language', null);
        // Try to get the current request language from the site that was found above
        if ($requestLanguage instanceof SiteLanguage && $requestLanguage->isEnabled()) {
            try {
                $language = $site->getLanguageById($requestLanguage->getLanguageId());
            } catch (\InvalidArgumentException $e) {
                $language = $site->getDefaultLanguage();
            }
        } else {
            $language = $site->getDefaultLanguage();
        }

        // Build Url
        $uri = $site->getRouter()->generateUri(
            (int)$urlParams['pageuid'],
            ['_language' => $language]
        );

        // Fallback to the current URL if the site is not having a proper scheme and host
        $currentUri = $request->getUri();
        if (empty($uri->getScheme())) {
            $uri = $uri->withScheme($currentUri->getScheme());
        }
        if (empty($uri->getUserInfo())) {
            $uri = $uri->withUserInfo($currentUri->getUserInfo());
        }
        if (empty($uri->getHost())) {
            $uri = $uri->withHost($currentUri->getHost());
        }
        if ($uri->getPort() === null) {
            $uri = $uri->withPort($currentUri->getPort());
        }

        return (string)$uri;
    }
}
