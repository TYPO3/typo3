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
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotGenerateUriException;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;

/**
 * This implementation publishes (when implemented) public assets from extension
 * packages to the public _assets directory using a hash as directory name.
 * Subsequently, it can also generate URIs to public resource objects within that _assets folder
 *
 * @internal Never use or reference it directly, use SystemResourcePublisherInterface to inject it (or a proper replacement).
 */
#[Autoconfigure(public: true), AsAlias(SystemResourcePublisherInterface::class, public: true)]
final readonly class DefaultSystemResourcePublisher implements SystemResourcePublisherInterface
{
    private const PUBLISHING_DIRECTORY = '_assets/';

    public function publishResources(PackageInterface $package): void
    {
        // TODO: Implement and make use of publishResources() method.
    }

    /**
     * @throws CanNotGenerateUriException
     */
    public function generateUri(PublicResourceInterface $publicResource, ?ServerRequestInterface $request, ?UriGenerationOptions $options = null): UriInterface
    {
        if ($publicResource instanceof UriInterface) {
            return $publicResource;
        }
        if (!$publicResource->isPublished()) {
            throw new CanNotGenerateUriException(sprintf('Can not generate Uri for an unpublished resource %s', $publicResource), 1761211273);
        }
        $request ??= $GLOBALS['TYPO3_REQUEST'] ?? null;
        return $publicResource->getPublicUri(
            new DefaultSystemResourceUriGenerator(
                self::PUBLISHING_DIRECTORY,
                $this->extractPublicPrefixFromRequest($request, $options?->uriPrefix),
                $request,
                $options,
            )
        );
    }

    private function extractPublicPrefixFromRequest(?ServerRequestInterface $request, ?string $publicPrefix): string
    {
        if ($publicPrefix !== null) {
            return $publicPrefix;
        }
        if ($request === null) {
            return '/';
        }
        $normalizedParams = $request->getAttribute('normalizedParams');
        return $this->getFrontendUrlPrefix($request->getAttribute('frontend.typoscript')?->getConfigArray(), $normalizedParams)
            ?? $normalizedParams->getSitePath();
    }

    private function getFrontendUrlPrefix(?array $typoScriptConfigArray, NormalizedParams $normalizedParams): ?string
    {
        if ($typoScriptConfigArray === null) {
            return null;
        }
        if ($typoScriptConfigArray['forceAbsoluteUrls'] ?? false) {
            return $normalizedParams->getSiteUrl();
        }
        $absRefPrefix = trim($typoScriptConfigArray['absRefPrefix'] ?? '');
        return $absRefPrefix === 'auto' ? $normalizedParams->getSitePath() : $absRefPrefix;
    }
}
