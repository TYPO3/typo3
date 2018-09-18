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
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Routing\PageUriBuilder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolves static routes - can return configured content directly or load content from file / urls
 */
class StaticRouteResolver implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (($site = $request->getAttribute('site', null)) instanceof Site &&
            ($configuration = $site->getConfiguration()['routes'] ?? null)
        ) {
            $path = ltrim($request->getUri()->getPath(), '/');
            $routeNames = array_map(function (string $route) use ($site) {
                return ltrim(trim($site->getBase()->getPath(), '/') . '/' . ltrim($route, '/'), '/');
            }, array_column($configuration, 'route'));
            if (in_array($path, $routeNames, true)) {
                $key = array_search($path, $routeNames, true);
                $routeConfig = $configuration[$key];
                [$content, $contentType] = $this->resolveByType($request, $routeConfig['type'], $routeConfig);
                return new HtmlResponse($content, 200, ['Content-Type' => $contentType]);
            }
        }
        return $handler->handle($request);
    }

    private function getFromFile(File $file): array
    {
        $content = $file->getContents();
        $contentType = $file->getMimeType();
        return [$content, $contentType];
    }

    private function getFromUri(string $uri): array
    {
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        $response = $requestFactory->request($uri);
        $contentType = 'text/plain; charset=utf-8';
        $content = '';
        if ($response->getStatusCode() === 200) {
            $content = $response->getBody()->getContents();
            $contentType = $response->getHeader('Content-Type');
        }

        return [$content, $contentType];
    }

    private function getPageUri(ServerRequestInterface $request, array $urlParams): string
    {
        $uriBuilder = GeneralUtility::makeInstance(PageUriBuilder::class);
        $uri = (string)$uriBuilder->buildUri(
            (int)$urlParams['pageuid'],
            ['type' => $urlParams['pagetype'] ?? 0],
            null,
            ['language' => $request->getAttribute('language', null)],
            PageUriBuilder::ABSOLUTE_URL
        );
        return $uri;
    }

    private function resolveByType(ServerRequestInterface $request, string $type, array $routeConfig): array
    {
        switch ($type) {
            case 'staticText':
                $content = $routeConfig['content'];
                $contentType = 'text/plain; charset=utf-8';
                break;
            case 'uri':
                $linkService = GeneralUtility::makeInstance(LinkService::class);
                $urlParams = $linkService->resolve($routeConfig['source']);
                if ($urlParams['type'] === 'url' || $urlParams['type'] === 'page') {
                    $uri = $urlParams['url'] ?? $this->getPageUri($request, $urlParams);
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
