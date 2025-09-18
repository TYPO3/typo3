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

namespace TYPO3\CMS\Core\SystemResource\Publishing;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotGenerateUriException;
use TYPO3\CMS\Core\SystemResource\Http\CacheBustingUri;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;

/**
 * This is tightly coupled to DefaultSystemResourcePublisher and acts
 * as a helper to actually generate the URI for a public resource.
 * This helper and its interface only exists to not expose the absolute
 * path from the system resource objects directly.
 *
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
readonly class DefaultSystemResourceUriGenerator implements SystemResourceUriGeneratorInterface
{
    public function __construct(
        private string $publishingDirectory,
        private string $prefix,
        private ?ServerRequestInterface $request,
        private ?UriGenerationOptions $options = null,
    ) {}

    public function generateForPublicResourceBasedOnAbsolutePath(
        PublicResourceInterface $resource,
        string $absoluteResourcePath,
    ): UriInterface {
        $uri = $this->makeAbsolute(new Uri($this->calculateUriPath($absoluteResourcePath)));
        return CacheBustingUri::fromFileSystemPath(
            $absoluteResourcePath,
            $uri,
            $this->request ? ApplicationType::fromRequest($this->request) : null
        );
    }

    public function generateForFile(File $file): UriInterface
    {
        $publicUrl = $file->getPublicUrl();
        if ($publicUrl === null) {
            throw new CanNotGenerateUriException(sprintf('Can not create a public Uri for a file %s', $file), 1758619473);
        }
        return CacheBustingUri::fromFile(
            $file,
            $this->makeAbsolute(new Uri($publicUrl)),
        );
    }

    private function makeAbsolute(UriInterface $uri): UriInterface
    {
        if ($this->request === null || !$this->options?->absoluteUri) {
            return $uri;
        }
        return $uri->withHost($uri->getHost() ?: $this->request->getUri()->getHost())
                   ->withScheme($uri->getScheme() ?: $this->request->getUri()->getScheme());
    }

    private function calculateUriPath(string $absoluteResourcePath): string
    {
        if (str_starts_with($absoluteResourcePath, Environment::getPublicPath())) {
            return $this->prefix . substr($absoluteResourcePath, strlen(Environment::getPublicPath()) + 1);
        }
        [$relativePrefix, $relativeAssetPath] = explode(
            'Resources/Public',
            substr($absoluteResourcePath, strlen(Environment::getProjectPath())),
            2
        );
        return $this->prefix . $this->publishingDirectory . md5($relativePrefix) . $relativeAssetPath;
    }
}
