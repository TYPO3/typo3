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

namespace TYPO3\CMS\Core\Package;

use TYPO3\CMS\Core\Package\Resource\ResourceCollectionInterface;

/**
 * Interface for a TYPO3 Package class
 */
interface PackageInterface
{
    /**
     * See https://github.com/composer/composer/blob/2.1.6/src/Composer/Command/InitCommand.php#L100
     */
    public const PATTERN_MATCH_COMPOSER_NAME = '{^[a-z0-9_.-]+/[a-z0-9_.-]+$}D';

    public const PATTERN_MATCH_PACKAGEKEY = '/^[a-z0-9]+\.(?:[a-z0-9][\.a-z0-9]*)+$/i';

    public const PATTERN_MATCH_EXTENSIONKEY = '/^[0-9a-z_-]+$/i';

    /**
     * @internal
     */
    public function getResources(): ResourceCollectionInterface;

    /**
     * @internal
     */
    public function getPackageReplacementKeys(): array;

    /**
     * Tells if the package is part of the default factory configuration
     * and therefor activated at first installation.
     *
     * @internal
     */
    public function isPartOfFactoryDefault(): bool;

    /**
     * Tells if the package is required for a minimal usable (backend) system
     * and therefor activated if PackageStates is created from scratch for
     * whatever reason.
     *
     * @internal
     */
    public function isPartOfMinimalUsableSystem(): bool;

    /**
     * Returns contents of Composer manifest - or part there of if a key is given.
     *
     * @param string|null $key Optional. Only return the part of the manifest indexed by 'key'
     * @see json_decode for return values
     * @internal
     */
    public function getValueFromComposerManifest(?string $key = null): mixed;

    /**
     * Returns the package meta object of this package.
     *
     * @internal
     */
    public function getPackageMetaData(): MetaData;

    /**
     * Returns the package key of this package.
     *
     * @return string
     */
    public function getPackageKey(): string;

    /**
     * Tells if this package is protected and therefore cannot be deactivated or deleted
     *
     * @return bool
     */
    public function isProtected(): bool;

    /**
     * Sets the protection flag of the package
     *
     * @param bool $protected TRUE if the package should be protected, otherwise FALSE
     */
    public function setProtected(bool $protected): void;

    /**
     * Returns the full path to this package's main directory
     *
     * @return string Path to this package's main directory
     */
    public function getPackagePath(): string;

    /**
     * Find package icon location relative to the package path or null if nothing was found.
     */
    public function getPackageIcon(): ?string;

}
