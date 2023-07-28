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

namespace TYPO3\CMS\Core\Resource\Driver;

use TYPO3\CMS\Core\Resource\Capabilities;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * An abstract implementation of a storage driver.
 */
abstract class AbstractDriver implements DriverInterface
{
    /*******************
     * CAPABILITIES
     *******************/
    /**
     * The capabilities of this driver. This value should be set in the constructor of derived classes.
     */
    protected Capabilities $capabilities;

    /**
     * The storage uid the driver was instantiated for
     */
    protected ?int $storageUid = null;

    /**
     * A list of all supported hash algorithms, written all lower case and
     * without any dashes etc. (e.g. sha1 instead of SHA-1)
     * Be sure to set this in inherited classes!
     *
     * @phpstan-var list<string>
     *
     * @todo: Remove this from this class. Properties of abstract classes MUST NOT be api. If all drivers
     *        need to implement this, consider creating a new method stub in the DriverInterface or consider
     *        creating a new SupportedHashAlgorithmsAwareInterface that demands implementations to provide said
     *        information. Inside this abstract class, this property is useless, however.
     */
    protected array $supportedHashAlgorithms = [];

    /**
     * The configuration of this driver
     */
    protected array $configuration = [];

    /**
     * Creates this object.
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
    }

    /**
     * Checks a fileName for validity. This could be overridden in concrete
     * drivers if they have different file naming rules.
     */
    protected function isValidFilename(string $fileName): bool
    {
        if (str_contains($fileName, '/')) {
            return false;
        }
        if (!preg_match('/^[\\pL\\d[:blank:]._-]*$/u', $fileName)) {
            return false;
        }
        return true;
    }

    /**
     * Sets the storage uid the driver belongs to
     */
    public function setStorageUid(int $storageUid): void
    {
        $this->storageUid = $storageUid;
    }

    /**
     * Returns the capabilities of this driver.
     */
    public function getCapabilities(): Capabilities
    {
        return $this->capabilities;
    }

    /**
     * Returns TRUE if this driver has the given capability.
     *
     * @phpstan-param Capabilities::CAPABILITY_* $capability
     */
    public function hasCapability(int $capability): bool
    {
        return $this->getCapabilities()->hasCapability($capability);
    }

    /*******************
     * FILE FUNCTIONS
     *******************/

    /**
     * Returns a temporary path for a given file, including the file extension.
     *
     * @phpstan-param non-empty-string $fileIdentifier
     * @phpstan-return non-empty-string
     */
    protected function getTemporaryPathForFile(string $fileIdentifier): string
    {
        return GeneralUtility::tempnam('fal-tempfile-', '.' . PathUtility::pathinfo($fileIdentifier, PATHINFO_EXTENSION));
    }

    /**
     * Hashes a file identifier, taking the case sensitivity of the file system
     * into account. This helps mitigating problems with case-insensitive
     * databases.
     *
     * @phpstan-param non-empty-string $identifier
     * @phpstan-return non-empty-string
     */
    public function hashIdentifier(string $identifier): string
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        return sha1($identifier);
    }

    /**
     * Returns TRUE if this driver uses case-sensitive identifiers. NOTE: This
     * is a configurable setting, but the setting does not change the way the
     * underlying file system treats the identifiers; the setting should
     * therefore always reflect the file system and not try to change its
     * behaviour
     */
    public function isCaseSensitiveFileSystem(): bool
    {
        if (isset($this->configuration['caseSensitive'])) {
            return (bool)$this->configuration['caseSensitive'];
        }
        return true;
    }

    /**
     * Makes sure the path given as parameter is valid
     *
     * @phpstan-param non-empty-string $filePath The file path (most times filePath)
     * @phpstan-return non-empty-string
     */
    abstract protected function canonicalizeAndCheckFilePath(string $filePath): string;

    /**
     * Makes sure the identifier given as parameter is valid
     *
     * @phpstan-param non-empty-string $fileIdentifier The file Identifier
     * @phpstan-return non-empty-string
     */
    abstract protected function canonicalizeAndCheckFileIdentifier(string $fileIdentifier): string;

    /**
     * Makes sure the identifier given as parameter is valid
     *
     * @phpstan-param non-empty-string $folderIdentifier The folder identifier
     * @phpstan-return non-empty-string
     */
    abstract protected function canonicalizeAndCheckFolderIdentifier(string $folderIdentifier): string;
}
