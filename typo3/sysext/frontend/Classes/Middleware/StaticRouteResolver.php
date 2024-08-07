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
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

/**
 * Resolves static routes - can return configured content directly or load content from file / urls
 */
class StaticRouteResolver implements MiddlewareInterface
{
    public function __construct(
        protected readonly RequestFactory $requestFactory,
        protected readonly LinkService $linkService,
        protected readonly FilePathSanitizer $filePathSanitizer,
    ) {}

    /**
     * Checks if there is a valid site with route configuration.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($site = $request->getAttribute('site')) instanceof Site &&
            ($configuration = $site->getConfiguration()['routes'] ?? null)
        ) {
            $path = ltrim($request->getUri()->getPath(), '/');
            $routeConfig = $this->getApplicableStaticRoute($configuration, $site, $path);
            if (is_array($routeConfig)) {
                try {
                    [$content, $contentType] = $this->resolveByType($request, $site, $routeConfig['type'], $routeConfig);
                } catch (InvalidRouteArgumentsException $e) {
                    return new Response('Invalid route', 404, ['Content-Type' => 'text/plain']);
                }

                return new HtmlResponse($content, 200, ['Content-Type' => $contentType]);
            }
        }
        return $handler->handle($request);
    }

    /**
     * Find the proper configuration for the static route in the static route configuration. Mainly:
     * - needs to have a valid "route" property
     * - needs to have a "type"
     *
     * @param array $staticRouteConfiguration the "routes" part of the site configuration
     * @param Site $site the current site where the configuration is based on
     * @param string $uriPath the path of the current request - used to match the "route" value of a single static route
     * @return array|null the configuration for the static route that matches, or null if no route is given
     */
    protected function getApplicableStaticRoute(array $staticRouteConfiguration, Site $site, string $uriPath): ?array
    {
        $routeNames = array_map(static function (?string $route) use ($site) {
            if ($route === null || $route === '') {
                return null;
            }
            return ltrim(trim($site->getBase()->getPath(), '/') . '/' . ltrim($route, '/'), '/');
        }, array_column($staticRouteConfiguration, 'route'));
        // Remove empty routes which would throw an error (could happen within creating a false route in the GUI)
        $routeNames = array_filter($routeNames);

        if (in_array($uriPath, $routeNames, true)) {
            $key = array_search($uriPath, $routeNames, true);
            // Only allow routes with a type "given"
            if (isset($staticRouteConfiguration[$key]['type'])) {
                return $staticRouteConfiguration[$key];
            }
        }
        return null;
    }

    protected function getFromFile(File $file): array
    {
        $content = $file->getContents();
        $contentType = $file->getMimeType();
        return [$content, $contentType];
    }

    protected function getFromUri(string $uri): array
    {
        $response = $this->requestFactory->request($uri);
        $contentType = 'text/plain; charset=utf-8';
        $content = '';
        if ($response->getStatusCode() === 200) {
            $content = $response->getBody()->getContents();
            $contentType = $response->getHeader('Content-Type');
        }

        return [$content, $contentType];
    }

    protected function getPageUri(ServerRequestInterface $request, Site $site, array $urlParams): string
    {
        $parameters = [];
        // Add additional parameters, if set via TypoLink
        if (isset($urlParams['parameters'])) {
            parse_str($urlParams['parameters'], $parameters);
        }
        $parameters['type'] = $urlParams['pagetype'] ?? 0;
        $parameters['_language'] = $request->getAttribute('language', null);
        $uri = $site->getRouter()->generateUri(
            (int)($urlParams['pageuid'] ?? 0),
            $parameters,
            '',
            RouterInterface::ABSOLUTE_URL
        );
        return (string)$uri;
    }

    /**
     * @throws InvalidRouteArgumentsException
     */
    protected function resolveByType(ServerRequestInterface $request, Site $site, string $type, array $routeConfig): array
    {
        switch ($type) {
            case 'staticText':
                if (!isset($routeConfig['content']) || !is_string($routeConfig['content'])) {
                    throw new \InvalidArgumentException('A static route of type "staticText" must have a content defined.', 1704704705);
                }
                $content = $routeConfig['content'];
                $contentType = 'text/plain; charset=utf-8';
                break;
            case 'uri':
                $urlParams = $this->linkService->resolve($routeConfig['source']);
                if ($urlParams['type'] === 'url' || $urlParams['type'] === 'page') {
                    $uri = $urlParams['url'] ?? $this->getPageUri($request, $site, $urlParams);
                    [$content, $contentType] = $this->getFromUri($uri);
                } elseif ($urlParams['type'] === 'file') {
                    [$content, $contentType] = $this->getFromFile($urlParams['file']);
                } else {
                    throw new \InvalidArgumentException('Can only handle URIs of type page, url or file.', 1537348076);
                }

                break;
            case 'asset':
                if (!($routeConfig['asset'] ?? null)) {
                    throw new \InvalidArgumentException('A static route of type "asset" must have an asset defined.', 1721134959);
                }

                try {
                    $path = $this->filePathSanitizer->sanitize($routeConfig['asset']);
                } catch (InvalidFileNameException|InvalidPathException|FileDoesNotExistException|InvalidFileException) {
                    // We provide our own custom exception at this point
                    $path = '';
                }

                if ($path === '') {
                    throw new \InvalidArgumentException(sprintf('The asset "%s" (resolved to "%s") was invalid.', $routeConfig['asset'], $path), 1721134960);
                }

                $content = file_get_contents($path);
                /** @var FileInfo $fileInfo */
                $fileInfo = GeneralUtility::makeInstance(FileInfo::class, $path);
                $contentType = $fileInfo->getMimeType();
                break;
            default:
                throw new \InvalidArgumentException(
                    'Can only handle static file configurations with type uri, staticText or asset',
                    1537348083
                );
        }
        return [$content, $contentType];
    }
}
