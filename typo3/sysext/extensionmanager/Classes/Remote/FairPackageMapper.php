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

namespace TYPO3\CMS\Extensionmanager\Remote;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Remote\Dto\FairPackage;
use TYPO3\CMS\Extensionmanager\Remote\Dto\FairRelease;

/**
 * Turns one release of a FAIR metadata document into the extension model the
 * rest of the Extension Manager works with.
 *
 * A FAIR document knows neither state, category, owner nor download counts,
 * so those stay at their defaults - resolving a package for download does not
 * need them, browsing does and reads the catalogue instead.
 *
 * @internal This class is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
readonly class FairPackageMapper
{
    private const PACKAGE_ID_PREFIX = 'did:web:extensions.typo3.org:';

    /**
     * The remote identifier is kept so downloading still resolves the registered
     * remote for the package, which is what fetches and verifies the archive.
     */
    public function map(FairPackage $package, FairRelease $release, string $remoteIdentifier = 'ter'): Extension
    {
        $extension = new Extension();
        $extension->extensionKey = $package->extensionKey;
        $extension->remote = $remoteIdentifier;
        $extension->resolvedFromRemote = true;
        $extension->composerName = $package->composerName;
        $extension->documentationLink = $package->documentation;
        $extension->title = $package->name;
        $extension->description = $package->description;
        $extension->authorName = $package->authorName;
        $extension->authorEmail = $package->authorEmail;
        $extension->version = $release->version;
        $extension->integerVersion = VersionNumberUtility::convertVersionNumberToInteger($release->version);
        $extension->lastUpdated = $release->date;
        $extension->artifactSha256 = $release->getArtifactSha256();
        $extension->currentVersion = $release === $package->getNewestRelease();
        $extension->setDependencies($this->buildDependencies($release));
        return $extension;
    }

    /**
     * @return \SplObjectStorage<Dependency>
     */
    private function buildDependencies(FairRelease $release): \SplObjectStorage
    {
        $storage = new \SplObjectStorage();
        foreach (['depends' => $release->requires, 'suggests' => $release->suggests] as $type => $constraints) {
            foreach ($constraints as $requirement => $constraint) {
                $identifier = $this->toExtensionKey((string)$requirement);
                if ($identifier === null) {
                    continue;
                }
                $storage->offsetSet(Dependency::createFromEmConf($identifier, VersionConstraintConverter::toEmConfNotation($constraint), $type));
            }
        }
        return $storage;
    }

    /**
     * FAIR names a requirement by package DID or by environment key, the
     * dependency check works with extension keys. A requirement of neither
     * kind, "env:php-intl" say, is left out rather than blocking installation.
     */
    private function toExtensionKey(string $requirement): ?string
    {
        if ($requirement === 'env:php' || $requirement === 'env:typo3') {
            return substr($requirement, 4);
        }
        if (str_starts_with($requirement, 'env:typo3:')) {
            return substr($requirement, 10);
        }
        if (str_starts_with($requirement, self::PACKAGE_ID_PREFIX)) {
            return substr($requirement, strlen(self::PACKAGE_ID_PREFIX));
        }
        return null;
    }
}
