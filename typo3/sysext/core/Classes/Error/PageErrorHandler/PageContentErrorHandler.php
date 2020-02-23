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
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\MiddlewareDispatcher;
use TYPO3\CMS\Core\Http\MiddlewareStackResolver;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Http\RequestHandler;

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
     */
    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface
    {
        $linkService = GeneralUtility::makeInstance(LinkService::class);
        $urlParams = $linkService->resolve((string)$this->errorHandlerConfiguration['errorContentSource']);

        if ($urlParams['type'] !== 'page' && $urlParams['type'] !== 'url') {
            throw new \InvalidArgumentException('PageContentErrorHandler can only handle TYPO3 urls of types "page" or "url"', 1522826609);
        }

        if ($urlParams['type'] === 'page') {
            $response = $this->buildSubRequest($request, (int)$urlParams['pageuid']);
            return $response->withStatus($this->statusCode);
        }

        $resolvedUrl = $urlParams['url'];
        try {
            $content = null;
            $report = [];

            if ($resolvedUrl !== (string)$request->getUri()) {
                $content = GeneralUtility::getUrl($resolvedUrl, 0, null, $report);
                if ($content === false && ((int)$report['error'] === -1 || (int)$report['error'] > 200)) {
                    throw new \RuntimeException('Error handler could not fetch error page "' . $resolvedUrl . '", reason: ' . $report['message'], 1544172838);
                }
            }
        } catch (InvalidRouteArgumentsException | SiteNotFoundException $e) {
            $content = 'Invalid error handler configuration: ' . $this->errorHandlerConfiguration['errorContentSource'];
        }

        return new HtmlResponse($content, $this->statusCode);
    }

    /**
     * @param ServerRequestInterface $request
     * @param int $pageId
     * @return ResponseInterface
     * @throws SiteNotFoundException
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \RuntimeException
     */
    protected function buildSubRequest(ServerRequestInterface $request, int $pageId): ResponseInterface
    {
        $site = $request->getAttribute('site', null);
        if (!$site instanceof Site) {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
            $request = $request->withAttribute('site', $site);
        }

        if (!$this->pageExistsAndInRootline($pageId, $site->getRootPageId())) {
            throw new \RuntimeException('Page does not exist or is not in rootline.', 1582448967);
        }

        $request = $request->withQueryParams(['id' => $pageId]);
        $dispatcher = $this->buildDispatcher();
        return $dispatcher->handle($request);
    }

    /**
     * @return MiddlewareDispatcher
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function buildDispatcher()
    {
        $requestHandler = GeneralUtility::makeInstance(RequestHandler::class);
        $resolver = new MiddlewareStackResolver(
            GeneralUtility::makeInstance(FailsafeContainer::class),
            GeneralUtility::makeInstance(DependencyOrderingService::class),
            GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core')
        );

        $middlewares = $resolver->resolve('frontend');
        return new MiddlewareDispatcher($requestHandler, $middlewares);
    }

    /**
     * @param int $pageId
     * @param int $rootPageId
     * @return bool
     */
    protected function pageExistsAndInRootline(int $pageId, int $rootPageId): bool
    {
        try {
            return GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId)->getRootPageId() === $rootPageId;
        } catch (SiteNotFoundException $e) {
            return false;
        }
    }
}
