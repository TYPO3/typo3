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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Package\Exception\PackageAssetsPublishingFailedException;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\Resource\ResourceCollectionInterface;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotGenerateUriException;
use TYPO3\CMS\Core\SystemResource\Publishing\FileSystem\FileSystemPublisherInterface;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    private const PUBLISHING_DIRECTORY_INSTALL = '_assets_install/';

    /**
     * @var FileSystemPublisherInterface[]
     */
    private array $fileSystemPublishers;

    private string $publishingDirectory;

    public function __construct(
        array $fileSystemPublishers = [],
        bool $failsafe = false,
    ) {
        $this->fileSystemPublishers = $fileSystemPublishers;
        $this->publishingDirectory = $failsafe ? self::PUBLISHING_DIRECTORY_INSTALL : self::PUBLISHING_DIRECTORY;
    }

    public function publishResources(
        PackageInterface $package,
    ): FlashMessageQueue {
        $queue = new FlashMessageQueue('asset:publish');
        $fileSystemResourcesPath = $package->getPackagePath() . ResourceCollectionInterface::PACKAGE_DEFAULT_PUBLIC_DIR;
        if (str_starts_with($fileSystemResourcesPath, Environment::getPublicPath())) {
            $queue->addMessage(new FlashMessage(
                sprintf(
                    'Skipping asset publishing for extension "%s",'
                    . chr(10)
                    . 'because its public resources directory is already public.',
                    $package->getPackageKey(),
                ),
                $package->getPackageKey(),
                ContextualFeedbackSeverity::NOTICE,
            ));
            return $queue;
        }
        $relativePath = substr($fileSystemResourcesPath, strlen(Environment::getProjectPath()));
        if (!file_exists($fileSystemResourcesPath)) {
            $queue->addMessage(new FlashMessage(
                sprintf(
                    'Skipping assets publishing for extension "%s",'
                    . chr(10)
                    . 'because its public resources directory "%s" does not exist.',
                    $package->getPackageKey(),
                    '.' . $relativePath,
                ),
                $package->getPackageKey(),
                ContextualFeedbackSeverity::NOTICE,
            ));
            return $queue;
        }
        [$relativePrefix] = explode(ResourceCollectionInterface::PACKAGE_DEFAULT_PUBLIC_DIR, $relativePath);
        $publicResourcesPath = Environment::getPublicPath() . '/' . $this->publishingDirectory . md5($relativePrefix);
        GeneralUtility::mkdir(dirname($publicResourcesPath));
        try {
            foreach ($this->fileSystemPublishers as $publisher) {
                if (!$publisher->canPublish($fileSystemResourcesPath, $publicResourcesPath)) {
                    continue;
                }
                $publisher->publishFolder($fileSystemResourcesPath, $publicResourcesPath);
                break;
            }
        } catch (PackageAssetsPublishingFailedException $e) {
            $queue->addMessage(new FlashMessage(
                sprintf(
                    'Could not publish public resources for extension "%s" by using the "%s" strategy.'
                    . chr(10)
                    . 'Check whether the target directory "%s" already exists'
                    . chr(10)
                    . 'and TYPO3 has permissions to write inside the "_assets" directory.',
                    $package->getPackageKey(),
                    $e->publishingStrategy,
                    '.' . substr($publicResourcesPath, strlen(Environment::getProjectPath())),
                ),
                $package->getPackageKey(),
                ContextualFeedbackSeverity::ERROR,
            ));
        }

        return $queue;
    }

    /**
     * @throws CanNotGenerateUriException
     */
    public function generateUri(PublicResourceInterface $publicResource, ?ServerRequestInterface $request, ?UriGenerationOptions $options = null): UriInterface
    {
        if (!$publicResource->isPublished()) {
            throw new CanNotGenerateUriException(sprintf('Can not generate Uri for an unpublished resource %s', $publicResource), 1761211273);
        }
        $request ??= $GLOBALS['TYPO3_REQUEST'] ?? null;
        $options ??= new UriGenerationOptions();
        return $publicResource->getPublicUri(
            new DefaultSystemResourceUriGenerator(
                $this->publishingDirectory,
                $this->extractPublicPrefixFromRequest($request, $options->uriPrefix),
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
