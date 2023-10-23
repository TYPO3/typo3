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

namespace TYPO3\CMS\Core\Error\PageErrorHandler;

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Http\Application;

/**
 * Renders the content of a page to be displayed (also in relation to language etc)
 * This is typically configured via the "Sites configuration" module in the backend.
 */
class PageContentErrorHandler implements PageErrorHandlerInterface
{
    protected int $statusCode;

    protected array $errorHandlerConfiguration;

    protected int $pageUid = 0;

    protected Application $application;

    protected RequestFactory $requestFactory;

    protected ResponseFactoryInterface $responseFactory;

    protected SiteFinder $siteFinder;

    protected LinkService $link;

    protected FrontendInterface $cache;

    protected bool $useSubrequest;

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

        // @todo Convert this to DI once this class can be injected properly.
        $container = GeneralUtility::getContainer();
        $this->application = $container->get(Application::class);
        $this->requestFactory = $container->get(RequestFactory::class);
        $this->responseFactory = $container->get(ResponseFactoryInterface::class);
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $this->link = $container->get(LinkService::class);
        $this->cache = $container->get(CacheManager::class)->getCache('pages');
        $this->useSubrequest = GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('subrequestPageErrors');
    }

    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        try {
            $urlParams = $this->link->resolve($this->errorHandlerConfiguration['errorContentSource']);
            $this->pageUid = $urlParams['pageuid'] = (int)($urlParams['pageuid'] ?? 0);
            $resolvedUrl = $this->resolveUrl($request, $urlParams);

            // avoid denial-of-service amplification scenario
            if ($resolvedUrl === (string)$request->getUri()) {
                return new HtmlResponse(
                    'The error page could not be resolved, as the error page itself is not accessible',
                    $this->statusCode
                );
            }
            if ($this->useSubrequest) {
                // Create a sub-request and do not take any special query parameters into account
                $subRequest = $request->withQueryParams([])->withUri(new Uri($resolvedUrl))->withMethod('GET');
                $subResponse = $this->stashEnvironment(fn(): ResponseInterface => $this->sendSubRequest($subRequest, $this->pageUid, $request));
            } else {
                $cacheIdentifier = 'errorPage_' . md5($resolvedUrl);
                try {
                    $subResponse = $this->cachePageRequest(
                        $this->pageUid,
                        fn() => $this->sendRawRequest($resolvedUrl),
                        $cacheIdentifier
                    );
                } catch (\Exception $e) {
                    throw new \RuntimeException(sprintf('Error handler could not fetch error page "%s", reason: %s', $resolvedUrl, $e->getMessage()), 1544172838, $e);
                }
                // Ensure that 503 status code is kept, and not changed to 500.
                if ($subResponse->getStatusCode() === 503) {
                    return $this->responseFactory->createResponse($subResponse->getStatusCode())
                        ->withHeader('content-type', $subResponse->getHeader('content-type'))
                        ->withBody($subResponse->getBody());
                }
            }

            if ($subResponse->getStatusCode() >= 300) {
                throw new \RuntimeException(sprintf('Error handler could not fetch error page "%s", status code: %s', $resolvedUrl, $subResponse->getStatusCode()), 1544172839);
            }

            return $this->responseFactory->createResponse($this->statusCode)
                ->withHeader('content-type', $subResponse->getHeader('content-type'))
                ->withBody($subResponse->getBody());
        } catch (InvalidRouteArgumentsException | SiteNotFoundException $e) {
            return new HtmlResponse('Invalid error handler configuration: ' . $this->errorHandlerConfiguration['errorContentSource']);
        }
    }

    /**
     * Stash and restore portions of the global environment around a subreqest callable.
     */
    protected function stashEnvironment(callable $fetcher): ResponseInterface
    {
        $parkedTsfe = $GLOBALS['TSFE'] ?? null;
        $GLOBALS['TSFE'] = null;

        $result = $fetcher();

        $GLOBALS['TSFE'] = $parkedTsfe;

        return $result;
    }

    /**
     * Caches a subrequest fetch.
     */
    protected function cachePageRequest(int $pageId, callable $fetcher, string $cacheIdentifier): ResponseInterface
    {
        $responseData = $this->cache->get($cacheIdentifier);
        if (is_array($responseData) && $responseData !== []) {
            return $this->createCachedPageRequestResponse($responseData);
        }
        $cacheTags = [];
        $cacheTags[] = 'errorPage';
        if ($pageId > 0) {
            // Cache Tag "pageId_" ensures, cache is purged when content of 404 page changes
            $cacheTags[] = 'pageId_' . $pageId;
        }
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $lock = $lockFactory->createLocker(
            $cacheIdentifier,
            LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
        );
        try {
            $locked = $lock->acquire(
                LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
            );
            if (!$locked) {
                return $this->createGenericErrorResponse('Lock could not be acquired.');
            }
            /** @var ResponseInterface $response */
            $response = $fetcher();
            if ($response->getStatusCode() !== 200) {
                // External request lead to an error. Create a generic error response,
                // cache and use that instead of the external error response.
                $response = $this->createGenericErrorResponse('External error page could not be retrieved.');
            }
            $responseData = [
                'statuscode' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody()->getContents(),
                'reasonPhrase' => $response->getReasonPhrase(),
            ];
            $this->cache->set($cacheIdentifier, $responseData, $cacheTags);
            $lock->release();
        } catch (ClientException $e) {
            $response = $this->createGenericErrorResponse('External error page could not be retrieved. ' . $e->getMessage());
            $responseData = [
                'statuscode' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody()->getContents(),
                'reasonPhrase' => $response->getReasonPhrase(),
            ];
            $this->cache->set($cacheIdentifier, $responseData, $cacheTags);
        } catch (LockAcquireWouldBlockException $e) {
            // Currently a lock is active, thus returning a generic error directly to avoid
            // long wait times and thus consuming too much php worker processes. Caching is
            // not done here, as we do not know if the error page can be retrieved or not.
            $lock->release();
            return $this->createGenericErrorResponse('Lock could not be acquired. ' . $e->getMessage());
        } catch (\Throwable $e) {
            // Any other error happened
            $lock->release();
            return $this->createGenericErrorResponse('Error page could not be retrieved' . $e->getMessage());
        }
        $lock->release();
        return $this->createCachedPageRequestResponse($responseData);
    }

    protected function createGenericErrorResponse(string $message = ''): ResponseInterface
    {
        $content = GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
            'Page Not Found',
            $message ?: 'Error page is being generated',
            AbstractMessage::ERROR,
            0,
            503
        );
        return new HtmlResponse($content, 503);
    }

    protected function createCachedPageRequestResponse(array $responseData): ResponseInterface
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($responseData['body'] ?? '');
        $body->rewind();
        $response = new Response(
            $body,
            $responseData['statuscode'] ?? 200,
            $responseData['headers'] ?? [],
            $responseData['reasonPhrase'] ?? ''
        );
        return $response;
    }

    /**
     * Sends a full HTTP request to the specified URL.
     */
    protected function sendRawRequest(string $resolvedUrl): ResponseInterface
    {
        return $this->requestFactory->request($resolvedUrl, 'GET', $this->getSubRequestOptions());
    }

    /**
     * Sends an in-process subrequest.
     *
     * The $pageId is used to ensure the correct site is accessed.
     */
    protected function sendSubRequest(ServerRequestInterface $request, int $pageId, ServerRequestInterface $originalRequest): ResponseInterface
    {
        $site = $request->getAttribute('site', null);
        if (!$site instanceof Site) {
            $site = $this->siteFinder->getSiteByPageId($pageId);
            $request = $request->withAttribute('site', $site);
        }

        $request = $request->withAttribute('originalRequest', $originalRequest);

        return $this->application->handle($request);
    }

    /**
     * Returns request options for the subrequest
     *
     * @return array|int[]
     */
    protected function getSubRequestOptions(): array
    {
        $options = [];
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout'] === 0) {
            $options = [
                'timeout' => 10,
            ];
        }
        return $options;
    }

    /**
     * Resolve the URL (currently only page and external URL are supported)
     */
    protected function resolveUrl(ServerRequestInterface $request, array $urlParams): string
    {
        if (!in_array($urlParams['type'], ['page', 'url'])) {
            throw new \InvalidArgumentException('PageContentErrorHandler can only handle TYPO3 urls of types "page" or "url"', 1522826609);
        }
        if ($urlParams['type'] === 'url') {
            return $urlParams['url'];
        }

        // Get the site related to the configured error page
        $site = $this->siteFinder->getSiteByPageId($urlParams['pageuid']);
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

        // Requested language or default language is disabled in current site => Fetch first "enabled" language
        if (!$language->isEnabled()) {
            $enabledLanguages = $site->getLanguages();
            if ($enabledLanguages === []) {
                throw new \RuntimeException(
                    'Site ' . $site->getIdentifier() . ' does not define any enabled language.',
                    1674487171
                );
            }
            $language = reset($enabledLanguages);
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
