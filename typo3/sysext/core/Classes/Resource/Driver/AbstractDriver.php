<?php
namespace TYPO3\CMS\Core\Resource\Driver;

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
     * The capabilities of this driver. See Storage::CAPABILITY_* constants for possible values. This value should be set
     * in the constructor of derived classes.
     *
     * @var int
     */
    protected $capabilities = 0;

    /**
     * The storage uid the driver was instantiated for
     *
     * @var int
     */
    protected $storageUid;

    /**
     * A list of all supported hash algorithms, written all lower case and
     * without any dashes etc. (e.g. sha1 instead of SHA-1)
     * Be sure to set this in inherited classes!
     *
     * @var array
     */
    protected $supportedHashAlgorithms = [];

    /**
     * The configuration of this driver
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * Creates this object.
     *
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
    }

    /**
     * Checks a fileName for validity. This could be overidden in concrete
     * drivers if they have different file naming rules.
     *
     * @param string $fileName
     * @return bool TRUE if file name is valid
     */
    protected function isValidFilename($fileName)
    {
        if (strpos($fileName, '/') !== false) {
            return false;
        }
        if (!preg_match('/^[\\pL\\d[:blank:]._-]*$/u', $fileName)) {
            return false;
        }
        return true;
    }

    /**
     * Sets the storage uid the driver belongs to
     *
     * @param int $storageUid
     */
    public function setStorageUid($storageUid)
    {
        $this->storageUid = $storageUid;
    }

    /**
     * Returns the capabilities of this driver.
     *
     * @return int
     * @see Storage::CAPABILITY_* constants
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }

    /**
     * Returns TRUE if this driver has the given capability.
     *
     * @param int $capability A capability, as defined in a CAPABILITY_* constant
     * @return bool
     */
    public function hasCapability($capability)
    {
        return $this->capabilities & $capability == $capability;
    }

    /*******************
     * FILE FUNCTIONS
     *******************/

    /**
     * Returns a temporary path for a given file, including the file extension.
     *
     * @param string $fileIdentifier
     * @return string
     */
    protected function getTemporaryPathForFile($fileIdentifier)
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('fal-tempfile-', '.' . PathUtility::pathinfo($fileIdentifier, PATHINFO_EXTENSION));
    }

    /**
     * Hashes a file identifier, taking the case sensitivity of the file system
     * into account. This helps mitigating problems with case-insensitive
     * databases.
     *
     * @param string $identifier
     * @return string
     */
    public function hashIdentifier($identifier)
    {
        $identifier = $this->canonicalizeAndCheckFileIdentifier($identifier);
        return sha1($identifier);
    }

    /**
     * Basic implementation of the method that does directly return the
     * file name as is.
     *
     * @param string $fileName Input string, typically the body of a fileName
     * @param string $charset Charset of the a fileName (defaults to current charset; depending on context)
     * @return string Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_' and trailing dots removed
     */
    public function sanitizeFileName($fileName, $charset = '')
    {
        return $fileName;
    }

    /**
     * Returns TRUE if this driver uses case-sensitive identifiers. NOTE: This
     * is a configurable setting, but the setting does not change the way the
     * underlying file system treats the identifiers; the setting should
     * therefore always reflect the file system and not try to change its
     * behaviour
     *
     * @return bool
     */
    public function isCaseSensitiveFileSystem()
    {
        if (isset($this->configuration['caseSensitive'])) {
            return (bool)$this->configuration['caseSensitive'];
        }
        return true;
    }

    /**
     * Makes sure the path given as parameter is valid
     *
     * @param string $filePath The file path (most times filePath)
     * @return string
     */
    abstract protected function canonicalizeAndCheckFilePath($filePath);

    /**
     * Makes sure the identifier given as parameter is valid
     *
     * @param string $fileIdentifier The file Identifier
     * @return string
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
     */
    abstract protected function canonicalizeAndCheckFileIdentifier($fileIdentifier);

    /**
     * Makes sure the identifier given as parameter is valid
     *
     * @param string $folderIdentifier The folder identifier
     * @return string
     */
    abstract protected function canonicalizeAndCheckFolderIdentifier($folderIdentifier);
}
