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

namespace TYPO3\CMS\Core\SystemResource\Identifier;

use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackagePathException;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotResolveSystemResourceIdentifierException;
use TYPO3\CMS\Core\SystemResource\Exception\InvalidSystemResourceIdentifierException;
use TYPO3\CMS\Core\SystemResource\Package\VirtualAppPackage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This is subject to change during v14 development. Do not use.
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace,
 *           with three exceptions due to deprecated legacy code @see Uri\ResourceViewHelper, PageRendererBackendSetupTrait and ExtensionManagementUtility::resolvePackagePath())
 */
final readonly class SystemResourceIdentifierFactory
{
    public function __construct(private PackageManager $packageManager) {}

    /**
     * @throws InvalidSystemResourceIdentifierException
     * @throws CanNotResolveSystemResourceIdentifierException
     */
    public function create(string $resourceIdentifier): SystemResourceIdentifier
    {
        $givenIdentifier = $resourceIdentifier;
        if (PathUtility::hasProtocolAndScheme($resourceIdentifier)) {
            $resourceIdentifier = sprintf('%s:%s', UriResourceIdentifier::TYPE, $resourceIdentifier);
        }
        [$identifierType] = explode(':', $resourceIdentifier, 2);
        return match ($identifierType) {
            PackageResourceIdentifier::LEGACY_TYPE => $this->createPackageResourceIdentifier($this->convertExtensionPathToPackageResourceIdentifier($resourceIdentifier), $resourceIdentifier),
            PackageResourceIdentifier::TYPE => $this->createPackageResourceIdentifier($resourceIdentifier),
            FalResourceIdentifier::TYPE => $this->createFalResourceIdentifier($resourceIdentifier),
            UriResourceIdentifier::TYPE => $this->createUriResourceIdentifier($givenIdentifier),
            default => throw new CanNotResolveSystemResourceIdentifierException(sprintf('Can not resolve system resource identifier "%s".', $resourceIdentifier), 1758700314),
        };
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    public function createFromPackagePath(string $packageKey, string $relativePath, string $givenIdentifier): PackageResourceIdentifier
    {
        return new PackageResourceIdentifier(
            $this->getPackageAndValidatePath($packageKey, $relativePath, $givenIdentifier),
            $relativePath,
            $givenIdentifier
        );
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    private function createPackageResourceIdentifier(string $resourceIdentifier, ?string $originalIdentifier = null): PackageResourceIdentifier
    {
        [,$packageKey, $relativePath] = $this->parseIdentifier($resourceIdentifier);
        return $this->createFromPackagePath($packageKey, $relativePath, $originalIdentifier ?? $resourceIdentifier);
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    private function createFalResourceIdentifier(string $resourceIdentifier): FalResourceIdentifier
    {
        [,$storageId, $falIdentifier] = $this->parseIdentifier($resourceIdentifier);
        return new FalResourceIdentifier($storageId, $falIdentifier, $resourceIdentifier);
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    private function createUriResourceIdentifier(string $resourceIdentifier): UriResourceIdentifier
    {
        try {
            return new UriResourceIdentifier($resourceIdentifier);
        } catch (\Throwable $e) {
            throw new InvalidSystemResourceIdentifierException(sprintf('Can not resolve system resource identifier "%s". Invalid URI.', $resourceIdentifier), 1761732010, $e);
        }
    }

    /**
     * @return array{string, string, string}
     * @throws InvalidSystemResourceIdentifierException
     */
    private function parseIdentifier(string $resourceIdentifier): array
    {
        $identifierParts = explode(':', $resourceIdentifier);
        if (count($identifierParts) !== 3) {
            throw new InvalidSystemResourceIdentifierException(sprintf('Given system resource identifier "%s" is invalid. An identifier consists of three parts, separated by a colon (":").', $resourceIdentifier), 1760386146);
        }
        return $identifierParts;
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    private function convertExtensionPathToPackageResourceIdentifier(string $extensionPath): string
    {
        try {
            $packageKey = $this->packageManager->extractPackageKeyFromPackagePath($extensionPath);
        } catch (UnknownPackageException | UnknownPackagePathException $e) {
            throw new InvalidSystemResourceIdentifierException(sprintf('Can not create system resource identifier from "%s".', $extensionPath), 1758884297, $e);
        }
        return sprintf(
            '%s:%s:%s',
            PackageResourceIdentifier::TYPE,
            $packageKey,
            substr($extensionPath, strlen($packageKey) + 5),
        );
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    private function getPackageAndValidatePath(string $packageKey, string $relativePath, string $givenIdentifier): PackageInterface
    {
        if ($relativePath === ''
            || str_starts_with($relativePath, '/')
            || !GeneralUtility::validPathStr($relativePath)
        ) {
            throw new InvalidSystemResourceIdentifierException(sprintf('Relative package path "%s" must not be empty, must not start with a slash ("/") and must not contain invalid characters (e.g. ../ back path). (Given identifier "%s")', $relativePath, $givenIdentifier), 1763381514);
        }
        try {
            if ($packageKey !== VirtualAppPackage::APP_PACKAGE_KEY) {
                return $this->packageManager->getPackage($packageKey);
            }
        } catch (UnknownPackageException $e) {
            throw new InvalidSystemResourceIdentifierException(sprintf('Package with key "%s" does not exist. (Given identifier "%s")', $relativePath, $givenIdentifier), 1763381504, $e);
        }
        $package = new VirtualAppPackage();
        if (!$package->getResources()->isValidPath($relativePath)) {
            throw new InvalidSystemResourceIdentifierException(sprintf('Project path "%s" is not allowed', $relativePath), 1763381519);
        }
        return $package;
    }
}
