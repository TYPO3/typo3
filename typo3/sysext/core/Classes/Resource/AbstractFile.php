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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Abstract file representation in the file abstraction layer.
 */
abstract class AbstractFile implements FileInterface
{
    /**
     * Various file properties
     *
     * Note that all properties, which only the persisted (indexed) files have are stored in this
     * overall properties array only. The only properties which really exist as object properties of
     * the file object are the storage, the identifier, the fileName and the indexing status.
     *
     * @var array<non-empty-string, mixed>
     */
    protected array $properties = [];

    /**
     * The storage this file is located in
     */
    protected ?ResourceStorage $storage = null;

    /**
     * The file name of this file
     */
    protected string $name = '';

    /**
     * If set to true, this file is regarded as being deleted.
     */
    protected bool $deleted = false;

    /******************
     * VARIOUS FILE PROPERTY GETTERS
     ******************/
    /**
     * Returns true if the given property key exists for this file.
     *
     * @param non-empty-string $key
     */
    public function hasProperty(string $key): bool
    {
        return array_key_exists($key, $this->properties);
    }

    /**
     * Returns a property value
     *
     * @param non-empty-string $key
     */
    public function getProperty(string $key): mixed
    {
        if ($this->hasProperty($key)) {
            return $this->properties[$key];
        }
        return null;
    }

    /**
     * Returns the properties of this object.
     *
     * @return array<non-empty-string, mixed>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return non-empty-string
     */
    public function getHashedIdentifier(): string
    {
        return $this->properties['identifier_hash'];
    }

    public function getName(): string
    {
        // Do not check if file has been deleted because we might need the
        // name for undeleting it.
        return $this->name;
    }

    /**
     * Returns the basename (the name without extension) of this file.
     */
    public function getNameWithoutExtension(): string
    {
        return PathUtility::pathinfo($this->getName(), PATHINFO_FILENAME);
    }

    /**
     * @throws \RuntimeException
     * @return int<0, max>
     */
    public function getSize(): int
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821480);
        }
        if (empty($this->properties['size'])) {
            $fileInfo = $this->getStorage()->getFileInfoByIdentifier($this->getIdentifier(), ['size']);
            $size = array_pop($fileInfo);
        } else {
            $size = $this->properties['size'];
        }
        return MathUtility::canBeInterpretedAsInteger($size) ? (int)$size : 0;
    }

    /**
     * Returns the uid of this file
     */
    public function getUid(): int
    {
        return (int)$this->getProperty('uid');
    }

    /**
     * Returns the Sha1 of this file
     *
     * @throws \RuntimeException
     * @return non-empty-string
     */
    public function getSha1(): string
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821481);
        }
        return $this->getStorage()->hashFile($this, 'sha1');
    }

    /**
     * Returns the creation time of the file as Unix timestamp
     *
     * @throws \RuntimeException
     */
    public function getCreationTime(): int
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821487);
        }
        return (int)$this->getProperty('creation_date');
    }

    /**
     * Returns the date (as UNIX timestamp) the file was last modified.
     *
     * @throws \RuntimeException
     */
    public function getModificationTime(): int
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821488);
        }
        return (int)$this->getProperty('modification_date');
    }

    /**
     * Get the extension of this file in a lower-case variant
     */
    public function getExtension(): string
    {
        $pathinfo = PathUtility::pathinfo($this->getName());
        return strtolower($pathinfo['extension'] ?? '');
    }

    /**
     * Get the MIME type of this file
     *
     * @return non-empty-string mime type
     */
    public function getMimeType(): string
    {
        if ($this->properties['mime_type'] ?? false) {
            return $this->properties['mime_type'];
        }
        $fileInfo = $this->getStorage()->getFileInfoByIdentifier($this->getIdentifier(), ['mimetype']);
        return array_pop($fileInfo);
    }

    /**
     * Returns the fileType of this file
     * basically there are only five main "file types"
     * "audio"
     * "image"
     * "software"
     * "text"
     * "video"
     * "other"
     * see FileType enum
     */
    public function getType(): int
    {
        return $this->getFileType()->value;
    }

    public function isType(FileType $fileType): bool
    {
        return $this->getFileType() === $fileType;
    }

    /**
     * Returns the fileType of this file
     * basically there are only five main "file types"
     * "audio"
     * "image"
     * "software"
     * "text"
     * "video"
     * "other"
     * see FileType enum
     */
    public function getFileType(): FileType
    {
        // this basically extracts the mimetype and guess the filetype based
        // on the first part of the mimetype works for 99% of all cases, and
        // we don't need to make an SQL statement like EXT:media does currently
        if (!($this->properties['type'] ?? false)) {
            $this->properties['type'] = FileType::tryFromMimeType($this->getMimeType())->value;
        }
        return $this->properties['type'] instanceof FileType ? $this->properties['type'] : FileType::from($this->properties['type']);
    }

    /**
     * Useful to find out if this file can be previewed or resized as image.
     * @return bool true if File has an image-extension according to $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
     */
    public function isImage(): bool
    {
        return GeneralUtility::inList(strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] ?? ''), $this->getExtension()) && $this->getSize() > 0;
    }

    /**
     * Useful to find out if this file has a file extension based on any of the registered media extensions
     * @return bool true if File is a media-extension according to $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
     */
    public function isMediaFile(): bool
    {
        return GeneralUtility::inList(strtolower($GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] ?? ''), $this->getExtension()) && $this->getSize() > 0;
    }

    /**
     * Useful to find out if this file can be edited.
     *
     * @return bool true if File is a text-based file extension according to $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext']
     */
    public function isTextFile(): bool
    {
        return GeneralUtility::inList(strtolower($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] ?? ''), $this->getExtension());
    }
    /******************
     * CONTENTS RELATED
     ******************/
    /**
     * Get the contents of this file
     *
     * @throws \RuntimeException
     */
    public function getContents(): string
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821479);
        }
        return $this->getStorage()->getFileContents($this);
    }

    /**
     * Replace the current file contents with the given string
     *
     * @throws \RuntimeException
     * @return $this
     */
    public function setContents(string $contents): self
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821478);
        }
        $this->getStorage()->setFileContents($this, $contents);
        return $this;
    }

    /****************************************
     * STORAGE AND MANAGEMENT RELATED METHODS
     ****************************************/

    /**
     * @throws \RuntimeException
     */
    public function getStorage(): ResourceStorage
    {
        if ($this->storage === null) {
            throw new \RuntimeException('You\'re using fileObjects without a storage.', 1381570091);
        }
        return $this->storage;
    }

    /**
     * Checks if this file exists. This should normally always return TRUE;
     * it might only return FALSE when this object has been created from an
     * index record without checking for.
     *
     * @return bool TRUE if this file physically exists
     */
    public function exists(): bool
    {
        if ($this->deleted) {
            return false;
        }
        return $this->storage->hasFile($this->getIdentifier());
    }

    /**
     * Sets the storage this file is located in. This is only meant for
     * \TYPO3\CMS\Core\Resource-internal usage; don't use it to move files.
     *
     * @internal Should only be used by other parts of the File API (e.g. drivers after moving a file)
     *
     * @return $this
     */
    public function setStorage(ResourceStorage $storage): self
    {
        $this->storage = $storage;
        $this->properties['storage'] = $storage->getUid();
        return $this;
    }

    /**
     * Returns a combined identifier of this file, i.e. the storage UID and the
     * folder identifier separated by a colon ":".
     *
     * @return string Combined storage and file identifier, e.g. StorageUID:path/and/fileName.png
     */
    public function getCombinedIdentifier(): string
    {
        if (!empty($this->properties['storage']) && MathUtility::canBeInterpretedAsInteger($this->properties['storage'])) {
            $combinedIdentifier = $this->properties['storage'] . ':' . $this->getIdentifier();
        } else {
            $combinedIdentifier = $this->getStorage()->getUid() . ':' . $this->getIdentifier();
        }
        return $combinedIdentifier;
    }

    /**
     * Deletes this file from its storage. This also means that this object becomes useless.
     */
    public function delete(): bool
    {
        // The storage will mark this file as deleted
        $wasDeleted = $this->getStorage()->deleteFile($this);

        // Unset all properties when deleting the file, as they will be stale anyway
        // This needs to happen AFTER the storage deleted the file, because the storage
        // emits a signal, which passes the file object to the slots, which may need
        // all file properties of the deleted file.
        $this->properties = [];

        return $wasDeleted;
    }

    /**
     * Marks this file as deleted. This should only be used inside the
     * File Abstraction Layer, as it is a low-level API method.
     */
    public function setDeleted(): void
    {
        $this->deleted = true;
    }

    /**
     * Returns TRUE if this file has been deleted
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /*****************
     * SPECIAL METHODS
     *****************/
    /**
     * Returns a publicly accessible URL for this file
     *
     * WARNING: Access to the file may be restricted by further means, e.g. some
     * web-based authentication. You have to take care of this yourself.
     *
     * @return string|null NULL if file is deleted, the generated URL otherwise
     */
    public function getPublicUrl(): ?string
    {
        if ($this->deleted) {
            return null;
        }
        return $this->getStorage()->getPublicUrl($this);
    }

    /**
     * Returns a path to a local version of this file to process it locally (e.g. with some system tool).
     * If the file is normally located on a remote storages, this creates a local copy.
     * If the file is already on the local system, this only makes a new copy if $writable is set to TRUE.
     *
     * @param bool $writable Set this to FALSE if you only want to do read operations on the file.
     *
     * @throws \RuntimeException
     * @return non-empty-string
     */
    public function getForLocalProcessing(bool $writable = true): string
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821486);
        }
        return $this->getStorage()->getFileForLocalProcessing($this, $writable);
    }

    /***********************
     * INDEX RELATED METHODS
     ***********************/
    /**
     * Updates properties of this object.
     * This method is used to reconstitute settings from the
     * database into this object after being instantiated.
     */
    abstract public function updateProperties(array $properties);

    public function getParentFolder(): Folder
    {
        return $this->getStorage()->getFolder($this->getStorage()->getFolderIdentifierFromFileIdentifier($this->getIdentifier()));
    }
}
