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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Resolves static routes - can return configured content directly or load content from file / urls
 */
class StaticRouteResolver implements MiddlewareInterface
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var LinkService
     */
    protected $linkService;

    public function __construct(
        RequestFactory $requestFactory,
        LinkService $linkService
    ) {
        $this->requestFactory = $requestFactory;
        $this->linkService = $linkService;
    }

    /**
     * Checks if there is a valid site with route configuration.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($site = $request->getAttribute('site', null)) instanceof Site &&
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

    /**
     * @param File $file
     * @return array
     */
    protected function getFromFile(File $file): array
    {
        $content = $file->getContents();
        $contentType = $file->getMimeType();
        return [$content, $contentType];
    }

    /**
     * @param string $uri
     * @return array
     */
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

    /**
     * @param ServerRequestInterface $request
     * @param Site $site
     * @param array $urlParams
     * @return string
     * @throws InvalidRouteArgumentsException
     */
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
            (int)$urlParams['pageuid'],
            $parameters,
            '',
            RouterInterface::ABSOLUTE_URL
        );
        return (string)$uri;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Site $site
     * @param string $type
     * @param array $routeConfig
     * @return array
     * @throws InvalidRouteArgumentsException
     */
    protected function resolveByType(ServerRequestInterface $request, Site $site, string $type, array $routeConfig): array
    {
        switch ($type) {
            case 'staticText':
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
            default:
                throw new \InvalidArgumentException(
                    'Can only handle static file configurations with type uri or staticText.',
                    1537348083
                );
        }
        return [$content, $contentType];
    }
}
