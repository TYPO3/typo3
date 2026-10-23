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
use TYPO3\CMS\Extensionmanager\Remote\ApiRequestFailedException;
use TYPO3\CMS\Extensionmanager\Remote\UnsupportedVersionConstraintException;

/**
 * Pins down which exact package a download, an update or a dependency means.
 *
 * This is the counterpart to {@see ExtensionCatalogueInterface}: browsing shows
 * what exists, resolving answers "and which version of it do I install now".
 * The two are kept apart because their callers are - the list views never
 * resolve, and the download and dependency code never browses.
 *
 * An implementation reading a remote live throws ApiRequestFailedException
 * from any method when the remote could not answer at all, and
 * UnsupportedVersionConstraintException when it sent a requirement that
 * cannot be converted.
 *
 * @internal This interface is a specific ExtensionManager implementation and is not part of the Public TYPO3 API.
 */
interface PackageResolverInterface
{
    /**
     * The package a full remote identity addresses.
     *
     * @throws ExtensionNotFoundException if nothing is known under that identity
     * @throws ApiRequestFailedException if a remote could not be asked
     * @throws UnsupportedVersionConstraintException if a remote sent a requirement that cannot be converted
     */
    public function getByPackageIdentifier(PackageIdentifier $packageIdentifier): Extension;

    /**
     * A single version of an extension when the remote it came from is unknown,
     * or null if no remote offers it.
     *
     * @param string $version (example: 4.3.10)
     */
    public function findOneByExtensionKeyAndVersion(string $extensionKey, string $version): ?Extension;

    /**
     * The newest version offered for an extension, or null if none is.
     */
    public function findHighestAvailableVersion(string $extensionKey): ?Extension;

    /**
     * The versions of an extension within a range, highest version first. This is
     * what turns a dependency constraint into something installable.
     *
     * @return Extension[]
     */
    public function findByVersionRangeAndExtensionKeyOrderedByVersion(string $extensionKey, int $lowestVersion = 0, int $highestVersion = 0, bool $includeCurrentVersion = true): array;
}
