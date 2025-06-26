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

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
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
    protected ResponseFactoryInterface $responseFactory;
    protected SiteFinder $siteFinder;
    protected LinkService $link;
    protected RequestFactoryInterface $requestFactory;
    protected GuzzleClientFactory $guzzleClientFactory;

    /**
     * PageContentErrorHandler constructor.
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
        $this->responseFactory = $container->get(ResponseFactoryInterface::class);
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $this->link = $container->get(LinkService::class);
        $this->requestFactory = $container->get(RequestFactoryInterface::class);
        $this->guzzleClientFactory = $container->get(GuzzleClientFactory::class);
    }

    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        try {
            $urlParams = $this->link->resolve($this->errorHandlerConfiguration['errorContentSource']);
            $urlParams['pageuid'] = (int)($urlParams['pageuid'] ?? 0);
            $urlType = $urlParams['type'] ?? LinkService::TYPE_UNKNOWN;
            $resolvedUrl = $this->resolveUrl($request, $urlParams);

            // avoid denial-of-service amplification scenario
            if ($resolvedUrl === (string)$request->getUri()) {
                return new HtmlResponse(
                    'The error page could not be resolved, as the error page itself is not accessible',
                    $this->statusCode
                );
            }
            // External URL most likely pointing to additional hosts or pages not contained in the current instance,
            // and using internal sub requests would never receive a valid page. Send an external request instead.
            if ($urlType === LinkService::TYPE_URL) {
                return $this->sendExternalRequest($resolvedUrl, $request);
            }
            // Create a sub-request and do not take any special query parameters into account
            $subRequest = $request->withQueryParams([])->withUri(new Uri($resolvedUrl))->withMethod('GET');
            $subResponse = $this->stashEnvironment(fn(): ResponseInterface => $this->sendSubRequest($subRequest, $urlParams['pageuid'], $request));

            if ($subResponse->getStatusCode() >= 300) {
                throw new \RuntimeException(sprintf('Error handler could not fetch error page "%s", status code: %s', $resolvedUrl, $subResponse->getStatusCode()), 1544172839);
            }

            $response = $this->responseFactory->createResponse($this->statusCode)
                ->withHeader('content-type', $subResponse->getHeader('content-type'))
                ->withBody($subResponse->getBody());

            foreach (['Content-Security-Policy', 'Content-Security-Policy-Report-Only'] as $header) {
                if ($subResponse->hasHeader($header)) {
                    $response = $response->withHeader($header, $subResponse->getHeader($header));
                }
            }
            return $response;
        } catch (InvalidRouteArgumentsException | SiteNotFoundException $e) {
            return new HtmlResponse('Invalid error handler configuration: ' . $this->errorHandlerConfiguration['errorContentSource']);
        }
    }

    /**
     * Stash and restore portions of the global environment around a subrequest callable.
     */
    protected function stashEnvironment(callable $fetcher): ResponseInterface
    {
        $parkedTsfe = $GLOBALS['TSFE'] ?? null;
        $GLOBALS['TSFE'] = null;
        try {
            return $fetcher();
        } finally {
            $GLOBALS['TSFE'] = $parkedTsfe;
        }
    }

    /**
     * Sends an in-process subrequest.
     *
     * The $pageId is used to ensure the correct site is accessed.
     */
    protected function sendSubRequest(ServerRequestInterface $request, int $pageId, ServerRequestInterface $originalRequest): ResponseInterface
    {
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            $site = $this->siteFinder->getSiteByPageId($pageId);
            $request = $request->withAttribute('site', $site);
        }

        $request = $request->withAttribute('originalRequest', $originalRequest);

        return $this->application->handle($request);
    }

    /**
     * Sends an external request to fetch the error page from a remote resource.
     *
     * A custom header is added and checked to mitigate request loops, which
     * indicates additional configuration error in the error handler config.
     */
    protected function sendExternalRequest(string $url, ServerRequestInterface $originalRequest): ResponseInterface
    {
        if ($originalRequest->hasHeader('Requested-By')
            && in_array('TYPO3 Error Handler', $originalRequest->getHeader('Requested-By'), true)
        ) {
            // If the header is set here, it is a recursive call within the same instance where an
            // outer error handler called a page that results in another error handler call. To break
            // the loop, we except here.
            return new HtmlResponse(
                'The error page could not be resolved, the error page itself is not accessible',
                $this->statusCode
            );
        }
        try {
            $request = $this->requestFactory->createRequest('GET', $url)
                ->withHeader('Content-Type', 'text/html')
                ->withHeader('Requested-By', 'TYPO3 Error Handler');
            $response = $this->guzzleClientFactory->getClient()->send($request);
            // In case global guzzle configuration has been changed to not throw an exception
            // for error status codes, the response status code is checked here.
            if ($response->getStatusCode() >= 300) {
                return new HtmlResponse(
                    'The error page could not be resolved, as the error page itself is not accessible',
                    $this->statusCode
                );
            }
            return $this->responseFactory
                ->createResponse($this->statusCode)
                ->withHeader('Content-Type', $response->getHeader('Content-Type'))
                ->withBody($response->getBody());
        } catch (GuzzleException) {
            return new HtmlResponse(
                'The error page could not be resolved, the error page itself is not accessible',
                $this->statusCode
            );
        }
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
