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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\Uri;

/**
 * This class helps to resolve the virtual path to the main entry point of the TYPO3 Backend.
 */
#[Autoconfigure(public: true)]
class BackendEntryPointResolver
{
    protected string $entryPoint = '/typo3';

    /**
     * Returns a prefix such as /typo3/ or /mysubdir/typo3/ to the TYPO3 Backend with trailing slash.
     */
    public function getPathFromRequest(ServerRequestInterface $request): string
    {
        $entryPoint = $this->getEntryPoint($request);
        if (str_contains($entryPoint, '//')) {
            $entryPointParts = parse_url($entryPoint);
            /* Remove trailing slash unless, the string is '/' itself */
            $entryPoint = rtrim('/' . trim($entryPointParts['path'] ?? '', '/'), '/');
        }
        return $entryPoint . '/';
    }

    /**
     * Returns a full URL to the main URL of the TYPO3 Backend.
     */
    public function getUriFromRequest(ServerRequestInterface $request, string $additionalPathPart = ''): UriInterface
    {
        $entryPoint = $this->getEntryPointConfiguration();
        if (str_starts_with($entryPoint, 'https://') || str_starts_with($entryPoint, 'http://')) {
            // fqdn, early return as all required information are available.
            return new Uri($entryPoint . '/' . ltrim($additionalPathPart, '/'));
        }
        if ($request->getAttribute('normalizedParams') instanceof NormalizedParams) {
            $normalizedParams = $request->getAttribute('normalizedParams');
        } else {
            $normalizedParams = NormalizedParams::createFromRequest($request);
        }
        if (str_starts_with($entryPoint, '//')) {
            // Browser supports uri starting with `//` and uses the current request scheme for the link. Do avoid issue
            // for example checking the url at some point we prefix it with the current request protocol.
            return new Uri(($normalizedParams->isHttps() ? 'https:' : 'http:') . $entryPoint . '/' . ltrim($additionalPathPart, '/'));
        }
        return new Uri($normalizedParams->getSiteUrl() . $entryPoint . '/' . ltrim($additionalPathPart, '/'));
    }

    public function isBackendRoute(ServerRequestInterface $request): bool
    {
        return $this->getBackendRoutePath($request) !== null;
    }

    public function getBackendRoutePath(ServerRequestInterface $request): ?string
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $entryPoint = $this->getEntryPoint($request);

        if (str_contains($entryPoint, '//')) {
            $entryPointParts = parse_url($entryPoint);
            if ($uri->getHost() !== $entryPointParts['host']) {
                return null;
            }
            /* Remove trailing slash unless, the string is '/' itself */
            $entryPoint = rtrim('/' . trim($entryPointParts['path'] ?? '', '/'), '/');
        }

        if ($path === $entryPoint) {
            return '';
        }
        if (str_starts_with($path, $entryPoint . '/')) {
            return substr($path, strlen($entryPoint));
        }
        return null;
    }

    /**
     * Returns a prefix such as /typo3 or /mysubdir/typo3 to the TYPO3 Backend *without* trailing slash.
     */
    protected function getEntryPoint(ServerRequestInterface $request): string
    {
        $entryPoint = $this->getEntryPointConfiguration();
        if (str_contains($entryPoint, '//')) {
            return $entryPoint;
        }
        if ($request->getAttribute('normalizedParams') instanceof NormalizedParams) {
            $normalizedParams = $request->getAttribute('normalizedParams');
        } else {
            $normalizedParams = NormalizedParams::createFromRequest($request);
        }
        return $normalizedParams->getSitePath() . $entryPoint;
    }

    protected function getEntryPointConfiguration(): string
    {
        $entryPoint = $GLOBALS['TYPO3_CONF_VARS']['BE']['entryPoint'] ?? $this->entryPoint;
        if (str_starts_with($entryPoint, 'https://')
            || str_starts_with($entryPoint, 'http://')
            || str_starts_with($entryPoint, '//')
        ) {
            $uri = new Uri(rtrim($entryPoint, '/'));
            $uri = $uri->withPath($this->removeMultipleSlashes($uri->getPath()));
            return (string)$uri;
        }
        return $this->removeMultipleSlashes(trim($entryPoint, '/'));
    }

    private function removeMultipleSlashes(string $value): string
    {
        return preg_replace('/(\/+)/', '/', $value);
    }
}
