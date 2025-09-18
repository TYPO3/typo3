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

namespace TYPO3\CMS\Core\SystemResource;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Resource\Exception as FalException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotResolvePublicResourceException;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotResolveSystemResourceException;
use TYPO3\CMS\Core\SystemResource\Exception\InvalidSystemResourceIdentifierException;
use TYPO3\CMS\Core\SystemResource\Identifier\FalResourceIdentifier;
use TYPO3\CMS\Core\SystemResource\Identifier\PackageResourceIdentifier;
use TYPO3\CMS\Core\SystemResource\Identifier\SystemResourceIdentifierFactory;
use TYPO3\CMS\Core\SystemResource\Package\VirtualAppPackage;
use TYPO3\CMS\Core\SystemResource\Type\PackageResource;
use TYPO3\CMS\Core\SystemResource\Type\PublicPackageFile;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\StaticResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\UriResource;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This is the heart of system resource handling and
 * the most important API to be used in userland code
 * and throughout the core.
 */
#[Autoconfigure(public: true)]
readonly class SystemResourceFactory
{
    public function __construct(
        private SystemResourceIdentifierFactory $identifierFactory,
        private PackageManager $packageManager,
        private ?StorageRepository $storageRepository,
        private ?ResourceFactory $resourceFactory,
    ) {}

    /**
     * Use this method to obtain a resource that is public,
     * which means, that a URI can be generated for it.
     *
     * Always use it when the goal is to generate a URI,
     * as it checks, whether the resolved resource can/ is
     * indeed published or not and throws an exception otherwise.
     *
     * @throws CanNotResolveSystemResourceException
     * @throws CanNotResolvePublicResourceException
     */
    public function createPublicResource(string $resourceString): PublicResourceInterface
    {
        $resource = $this->createResource($resourceString);
        if (!$resource instanceof PublicResourceInterface || !$resource->isPublished()) {
            throw new CanNotResolvePublicResourceException(sprintf('Resolved resource "%s" is not a public resource. Given resource identifier: "%s"', $resource, $resourceString), 1758098512);
        }
        return $resource;
    }

    /**
     * Use this method, when generating a URI is not required
     * for the resource and only e.g. file contents like for templates
     * is required.
     *
     * @throws CanNotResolveSystemResourceException
     */
    public function createResource(string $resourceString): StaticResourceInterface
    {
        try {
            return $this->createFromIdentifier($resourceString);
        } catch (InvalidSystemResourceIdentifierException $e) {
            if (str_starts_with($resourceString, Environment::getProjectPath())
                || (PathUtility::isAbsolutePath($resourceString) && file_exists($resourceString))
            ) {
                throw new CanNotResolveSystemResourceException('Absolute paths are not allowed as resource identifiers', 1760618195);
            }
            $resourceString = ltrim($resourceString, '/');
            $falResource = $this->createFromLegacyFalPath($resourceString, $e);
            return $falResource ?? $this->createResourceFromRelativePublicPath($resourceString);
        }
    }

    /**
     * @throws CanNotResolveSystemResourceException
     */
    private function createResourceFromRelativePublicPath(string $relativePublicPath): SystemResourceInterface
    {
        $absoluteResourcePath = Environment::getPublicPath() . '/' . $relativePublicPath;
        // The file must actually exist, only then we can be sure our
        // following string manipulation is correct. Otherwise, it could be some unexpected
        // string and the manipulation will lead to unexpected results
        // Strip potentially available query string and fragment from the path before checking, though
        try {
            $strippedAbsolutePath = (new Uri($absoluteResourcePath))->getPath();
        } catch (\InvalidArgumentException) {
            $strippedAbsolutePath = null;
        }
        if (!file_exists($strippedAbsolutePath ?? $absoluteResourcePath)) {
            throw new CanNotResolveSystemResourceException(sprintf('Can not resolve relative public path "%s" to a system resource', $relativePublicPath), 1759740281);
        }
        $packageIdentifier = $this->identifierFactory->createFromPackagePath(
            VirtualAppPackage::APP_PACKAGE_KEY,
            substr($absoluteResourcePath, strlen(Environment::getProjectPath()) + 1),
            $relativePublicPath,
        );
        return $this->createFromPackageIdentifier($packageIdentifier);
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    private function createFromIdentifier(string $potentialIdentifier): StaticResourceInterface
    {
        if (str_starts_with($potentialIdentifier, 'http') && PathUtility::hasProtocolAndScheme($potentialIdentifier)) {
            return new UriResource($potentialIdentifier);
        }
        $identifier = $this->identifierFactory->create($potentialIdentifier);
        return match (get_class($identifier)) {
            PackageResourceIdentifier::class => $this->createFromPackageIdentifier($identifier),
            FalResourceIdentifier::class => $this->createFromFalIdentifier($identifier),
            default => throw new InvalidSystemResourceIdentifierException(sprintf('Can not resolve "%s" to a system resource. Unknown SystemResourceIdentifier', $potentialIdentifier), 1759393674),
        };
    }

    /**
     * @throws CanNotResolveSystemResourceException
     */
    private function createFromLegacyFalPath(string $resourceString, \Throwable $e): ?StaticResourceInterface
    {
        if (!str_starts_with($resourceString, $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'])) {
            // For legacy resolving, we do not even try resolving any other path than
            // one starting with configured $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']
            return null;
        }
        try {
            $potentialFalPath = $resourceString;
            $storageUid = $this->storageRepository?->findBestMatchingStorageByLocalPath($potentialFalPath);
        } catch (\Throwable) {
            $storageUid = 0;
        }
        if ($storageUid <= 0) {
            return null;
        }
        $storage = $this->storageRepository?->findByUid($storageUid);
        if ($storage === null) {
            throw new CanNotResolveSystemResourceException(sprintf('Can not resolve "%s" to a system resource, storage %d does not exist', $resourceString, $storageUid), 1758627596, $e);
        }
        $file = null;
        try {
            $file = $storage->getFile($potentialFalPath);
        } catch (\Throwable) {
        }
        $this->ensureValidFalResource($file, $resourceString);
        return $file;
    }

    private function createFromFalIdentifier(FalResourceIdentifier $resourceUri): PublicResourceInterface
    {
        try {
            $file = $this->resourceFactory?->retrieveFileOrFolderObject($resourceUri->getIdentifier());
            $this->ensureValidFalResource($file, (string)$resourceUri);
            return $file;
        } catch (FalException $e) {
            throw new CanNotResolveSystemResourceException(sprintf('Can not resolve "%s" to a system resource', $resourceUri), 1759397430, $e);
        }
    }

    /**
     * @throws CanNotResolveSystemResourceException
     */
    private function ensureValidFalResource(ProcessedFile|File|Folder|null $falResource, string $resourceUri): void
    {
        if (!$falResource instanceof File || $falResource->getStorage()->getUid() === 0) {
            throw new CanNotResolveSystemResourceException(sprintf('Can not resolve file with uri "%s"', $resourceUri), 1758700078);
        }
    }

    /**
     * @throws CanNotResolveSystemResourceException
     */
    private function createFromPackageIdentifier(PackageResourceIdentifier $packageIdentifier): SystemResourceInterface
    {
        $packageKey = $packageIdentifier->getPackageKey();
        $relativePath = $packageIdentifier->getRelativePath();
        try {
            $package = $this->getPackageAndValidatePath($packageKey, $relativePath);
            if ($package->getResources()->isPublicPath($relativePath)) {
                return new PublicPackageFile($package, $relativePath, $packageIdentifier);
            }
            return new PackageResource($package, $relativePath, $packageIdentifier);
        } catch (UnknownPackageException $e) {
            throw new CanNotResolveSystemResourceException(sprintf('Can not resolve "%s" of extension "%s" to a system resource', $relativePath, $packageKey), 1759397437, $e);
        }
    }

    /**
     * @throws CanNotResolveSystemResourceException
     * @throws UnknownPackageException
     */
    private function getPackageAndValidatePath(string $packageKey, string $relativePath): PackageInterface
    {
        if ($packageKey !== VirtualAppPackage::APP_PACKAGE_KEY) {
            return $this->packageManager->getPackage($packageKey);
        }
        $package = new VirtualAppPackage();
        if (!$package->getResources()->isValidPath($relativePath)) {
            throw new CanNotResolveSystemResourceException(sprintf('Project path "%s" is not allowed', $relativePath), 1759742867);
        }
        return $package;
    }
}
