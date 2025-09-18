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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackagePathException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SystemResource\Exception\InvalidSystemResourceIdentifierException;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This is subject to change during v14 development. Do not use.
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace (with two exceptions due to deprecated legacy code @see Uri\ResourceViewHelper and PageRendererBackendSetupTrait)
 * This only needs to be a public service due to a required usage in aforementioned Trait
 */
#[Autoconfigure(public: true)]
final readonly class SystemResourceIdentifierFactory
{
    public function __construct(private PackageManager $packageManager) {}

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    public function create(string $resourceIdentifier): SystemResourceIdentifier
    {
        if (PathUtility::isExtensionPath($resourceIdentifier)) {
            return $this->createPackageResourceIdentifier($this->convertExtensionPathToPackageResourceIdentifier($resourceIdentifier), $resourceIdentifier);
        }
        if (str_starts_with($resourceIdentifier, PackageResourceIdentifier::TYPE)) {
            return $this->createPackageResourceIdentifier($resourceIdentifier);
        }
        if (str_starts_with($resourceIdentifier, FalResourceIdentifier::TYPE)) {
            return $this->createFalResourceIdentifier($resourceIdentifier);
        }
        throw new InvalidSystemResourceIdentifierException(sprintf('Can not resolve uri %s', $resourceIdentifier), 1758700314);
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    public function createFromPackagePath(string $packageKey, string $relativePath, string $givenIdentifier): PackageResourceIdentifier
    {
        return new PackageResourceIdentifier($this->getComposerName($packageKey), $relativePath, $givenIdentifier);
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    private function createPackageResourceIdentifier(string $resourceIdentifier, ?string $originalIdentifier = null): PackageResourceIdentifier
    {
        [,$packageKey, $path] = $this->parseIdentifier($resourceIdentifier);
        return new PackageResourceIdentifier(
            $this->getComposerName($packageKey),
            $path,
            $originalIdentifier ?? $resourceIdentifier,
        );
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
     * @return array{string, string, string}
     * @throws InvalidSystemResourceIdentifierException
     */
    private function parseIdentifier(string $resourceIdentifier): array
    {
        $identifierParts = explode(':', $resourceIdentifier);
        if (count($identifierParts) !== 3) {
            throw new InvalidSystemResourceIdentifierException(sprintf('Given identifier "%s" is invalid. An identifier consists of three parts, separated by a colon (":")', $resourceIdentifier), 1760386146);
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
            throw new InvalidSystemResourceIdentifierException(sprintf('Can not a create system uri from "%s"', $extensionPath), 1758884297, $e);
        }
        return sprintf(
            '%s:%s:%s',
            PackageResourceIdentifier::TYPE,
            $this->getComposerName($packageKey),
            substr($extensionPath, strlen($packageKey) + 5),
        );
    }

    /**
     * @throws InvalidSystemResourceIdentifierException
     */
    private function getComposerName(string $packageKey): string
    {
        if (str_contains($packageKey, '/')) {
            return $packageKey;
        }
        try {
            return $this->packageManager->getPackage($packageKey)->getValueFromComposerManifest('name') ?? $packageKey;
        } catch (UnknownPackageException $e) {
            throw new InvalidSystemResourceIdentifierException(sprintf('Can not a create system uri. Unknown package "%s"', $packageKey), 1760989723, $e);
        }
    }
}
