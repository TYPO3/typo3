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
        private UriGenerationOptions $options,
    ) {}

    public function generateForPackageResource(
        ResourceUriBuildingContext $context,
    ): UriInterface {
        $uri = $this->makeAbsolute(new Uri($this->calculateUriPath($context)));
        if (!$this->options->cacheBusting) {
            return $uri;
        }
        return CacheBustingUri::fromFileSystemPath(
            $context->absoluteResourcePath,
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
        if (Environment::isCli() && !str_starts_with($publicUrl, '/') && !str_contains($publicUrl, '://')) {
            // On CLI FAL public URLs of storages below the public directory are
            // relative to it, so we apply the prefix here, which is likely a "/"
            // only, unless calling code properly faked a request.
            $publicUrl = $this->prefix . $publicUrl;
        }
        $uri = $this->makeAbsolute(new Uri($publicUrl));
        if (!$this->options->cacheBusting) {
            return $uri;
        }
        return CacheBustingUri::fromFile(
            $file,
            $uri,
        );
    }

    private function makeAbsolute(UriInterface $uri): UriInterface
    {
        if ($this->request === null || !$this->options->absoluteUri) {
            return $uri;
        }
        if ($uri->getHost() !== '') {
            return $uri;
        }
        $siteUri = new Uri($this->request->getAttribute('normalizedParams')->getSiteUrl());
        return $uri->withScheme($siteUri->getScheme())
            ->withUserInfo($siteUri->getUserInfo())
            ->withHost($siteUri->getHost())
            ->withPort($siteUri->getPort());
    }

    private function calculateUriPath(ResourceUriBuildingContext $context): string
    {
        if ($context->isSourcePublic) {
            return $this->prefix . substr($context->absoluteResourcePath, strlen(Environment::getPublicPath()) + 1);
        }
        return $this->prefix . $this->publishingDirectory . $context->uriPath;
    }
}
