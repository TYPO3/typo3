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

namespace TYPO3\CMS\Extensionmanager\Domain;

use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Model\PackageIdentifier;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionNotFoundException;
use TYPO3\CMS\Extensionmanager\Remote\Dto\FairPackage;
use TYPO3\CMS\Extensionmanager\Remote\FairPackageClient;
use TYPO3\CMS\Extensionmanager\Remote\FairPackageMapper;
use TYPO3\CMS\Extensionmanager\Remote\UnsupportedVersionConstraintException;

/**
 * Resolves packages from the FAIR metadata documents TER serves: one request
 * per extension lists its releases with their requirements, artifact URL and
 * checksum, which answers which package a version of an extension is.
 *
 * The extensions handed out keep the "ter" remote identifier, so downloading
 * one still goes through the registered remote for it, which fetches the
 * archive and verifies it against the checksum the document named.
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
readonly class FairPackageResolver implements PackageResolverInterface
{
    public function __construct(
        private FairPackageClient $client,
        private FairPackageMapper $mapper,
        private string $remoteIdentifier = 'ter',
    ) {}

    public function getByPackageIdentifier(PackageIdentifier $packageIdentifier): Extension
    {
        $extension = $packageIdentifier->remote === $this->remoteIdentifier
            ? $this->findOneByExtensionKeyAndVersion($packageIdentifier->packageKey, $packageIdentifier->version)
            : null;
        if ($extension === null) {
            throw new ExtensionNotFoundException(
                'The FAIR metadata document of remote "' . $packageIdentifier->remote . '" lists no package "'
                    . $packageIdentifier->packageKey . '" in version "' . $packageIdentifier->version . '".',
                1792929601
            );
        }
        return $extension;
    }

    /**
     * A release without a sha256 checksum is not resolved: downloading would
     * skip verifying the archive, while the imported list carries a checksum.
     *
     * @throws UnsupportedVersionConstraintException
     */
    public function findOneByExtensionKeyAndVersion(string $extensionKey, string $version): ?Extension
    {
        $package = $this->getPackage($extensionKey);
        $release = $package?->getRelease($version);
        if ($release === null || $release->getArtifactSha256() === '') {
            return null;
        }
        return $this->mapper->map($package, $release, $this->remoteIdentifier);
    }

    /**
     * A FAIR document names neither the review state of a release nor its upload
     * comment. Which version is offered for an update - never an insecure one -
     * is therefore left to another resolver.
     */
    public function findHighestAvailableVersion(string $extensionKey): ?Extension
    {
        return null;
    }

    /**
     * Left to another resolver, see {@see findHighestAvailableVersion()}.
     */
    public function findByVersionRangeAndExtensionKeyOrderedByVersion(string $extensionKey, int $lowestVersion = 0, int $highestVersion = 0, bool $includeCurrentVersion = true): array
    {
        return [];
    }

    /**
     * A FAIR document is addressed by extension key. A composer package name
     * cannot be looked up this way, so it is an unknown package here.
     */
    private function getPackage(string $extensionKey): ?FairPackage
    {
        if ($extensionKey === '' || str_contains($extensionKey, '/')) {
            return null;
        }
        return $this->client->getPackage($extensionKey);
    }
}
