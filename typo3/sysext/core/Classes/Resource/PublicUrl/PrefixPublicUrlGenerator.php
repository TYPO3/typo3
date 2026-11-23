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

namespace TYPO3\CMS\Core\Resource\PublicUrl;

use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Publishes the files of a storage below a base URI, with each path segment
 * URL-encoded. The base URI is either absolute, absolute to the host, or
 * relative to the path TYPO3 is installed in, like "fileadmin/".
 */
final readonly class PrefixPublicUrlGenerator implements PublicUrlGenerator, PublicUrlResolverInterface
{
    private string $baseUri;

    public function __construct(string $baseUri)
    {
        $this->baseUri = $baseUri === '' ? '' : rtrim($baseUri, '/') . '/';
    }

    public function publicUrl(string $path, Config $config): string
    {
        $path = ltrim($path, '/');
        if ($path === '') {
            return $this->baseUri;
        }
        return $this->baseUri . implode('/', array_map(rawurlencode(...), explode('/', $path)));
    }

    public function resolvePath(UriInterface $uri, string $sitePath): ?string
    {
        $pathPrefix = $this->getPathPrefix($uri, $sitePath);
        if ($pathPrefix === null || !str_starts_with($uri->getPath(), $pathPrefix)) {
            return null;
        }
        return $this->decodePath(substr($uri->getPath(), strlen($pathPrefix)));
    }

    /**
     * Returns the encoded URI path below which the files are published, with
     * a trailing slash, or null if they are not published on the host of the
     * given URI, or the prefix would cover every path of the host.
     */
    private function getPathPrefix(UriInterface $uri, string $sitePath): ?string
    {
        if ($this->baseUri === '') {
            return null;
        }
        if (str_starts_with($this->baseUri, '//') || str_contains($this->baseUri, '://')) {
            $baseUri = new Uri(str_starts_with($this->baseUri, '//') ? 'https:' . $this->baseUri : $this->baseUri);
            if (strtolower($baseUri->getHost()) !== strtolower($uri->getHost())
                || $baseUri->getPort() !== $uri->getPort()
            ) {
                return null;
            }
            $path = $baseUri->getPath();
        } elseif (str_starts_with($this->baseUri, '/')) {
            $path = $this->baseUri;
        } else {
            $path = rtrim($sitePath, '/') . '/' . $this->baseUri;
        }
        $path = rtrim($path, '/') . '/';
        return $path === '/' ? null : $path;
    }

    /**
     * Decodes the URI path below the prefix, paths that cannot be canonical
     * paths of a file or folder yield null.
     */
    private function decodePath(string $encodedPath): ?string
    {
        $segments = explode('/', $encodedPath);
        $lastIndex = array_key_last($segments);
        foreach ($segments as $index => $segment) {
            $segment = rawurldecode($segment);
            if (($segment === '' && $index !== $lastIndex)
                || $segment === '.'
                || $segment === '..'
                || strpbrk($segment, "/\\\0") !== false
            ) {
                return null;
            }
            $segments[$index] = $segment;
        }
        return implode('/', $segments);
    }
}
