<?php
namespace TYPO3\CMS\Core\Resource;

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
     * @var array
     */
    protected $properties;

    /**
     * The storage this file is located in
     *
     * @var ResourceStorage
     */
    protected $storage;

    /**
     * The identifier of this file to identify it on the storage.
     * On some drivers, this is the path to the file, but drivers could also just
     * provide any other unique identifier for this file on the specific storage.
     *
     * @var string
     */
    protected $identifier;

    /**
     * The file name of this file
     *
     * @var string
     */
    protected $name;

    /**
     * If set to true, this file is regarded as being deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * any other file
     */
    const FILETYPE_UNKNOWN = 0;

    /**
     * Any kind of text
     * @see http://www.iana.org/assignments/media-types/text
     */
    const FILETYPE_TEXT = 1;

    /**
     * Any kind of image
     * @see http://www.iana.org/assignments/media-types/image
     */
    const FILETYPE_IMAGE = 2;

    /**
     * Any kind of audio file
     * @see http://www.iana.org/assignments/media-types/audio
     */
    const FILETYPE_AUDIO = 3;

    /**
     * Any kind of video
     * @see http://www.iana.org/assignments/media-types/video
     */
    const FILETYPE_VIDEO = 4;

    /**
     * Any kind of application
     * @see http://www.iana.org/assignments/media-types/application
     */
    const FILETYPE_APPLICATION = 5;

    /******************
     * VARIOUS FILE PROPERTY GETTERS
     ******************/
    /**
     * Returns true if the given property key exists for this file.
     *
     * @param string $key
     * @return bool
     */
    public function hasProperty($key)
    {
        return array_key_exists($key, $this->properties);
    }

    /**
     * Returns a property value
     *
     * @param string $key
     * @return mixed Property value
     */
    public function getProperty($key)
    {
        if ($this->hasProperty($key)) {
            return $this->properties[$key];
        }
        return null;
    }

    /**
     * Returns the properties of this object.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Returns the identifier of this file
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Get hashed identifier
     *
     * @return string
     */
    public function getHashedIdentifier()
    {
        return $this->properties['identifier_hash'];
    }

    /**
     * Returns the name of this file
     *
     * @return string
     */
    public function getName()
    {
        // Do not check if file has been deleted because we might need the
        // name for undeleting it.
        return $this->name;
    }

    /**
     * Returns the basename (the name without extension) of this file.
     *
     * @return string
     */
    public function getNameWithoutExtension()
    {
        return PathUtility::pathinfo($this->getName(), PATHINFO_FILENAME);
    }

    /**
     * Returns the size of this file
     *
     * @throws \RuntimeException
     * @return int|null Returns null if size is not available for the file
     */
    public function getSize()
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821480);
        }
        if (empty($this->properties['size'])) {
            $size = array_pop($this->getStorage()->getFileInfoByIdentifier($this->getIdentifier(), ['size']));
        } else {
            $size = $this->properties['size'];
        }
        return $size ? (int)$size : null;
    }

    /**
     * Returns the uid of this file
     *
     * @return int
     */
    public function getUid()
    {
        return (int)$this->getProperty('uid');
    }

    /**
     * Returns the Sha1 of this file
     *
     * @throws \RuntimeException
     * @return string
     */
    public function getSha1()
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
     * @return int
     */
    public function getCreationTime()
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
     * @return int
     */
    public function getModificationTime()
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821488);
        }
        return (int)$this->getProperty('modification_date');
    }

    /**
     * Get the extension of this file in a lower-case variant
     *
     * @return string The file extension
     */
    public function getExtension()
    {
        $pathinfo = PathUtility::pathinfo($this->getName());

        $extension = strtolower($pathinfo['extension'] ?? '');

        return $extension;
    }

    /**
     * Get the MIME type of this file
     *
     * @return string mime type
     */
    public function getMimeType()
    {
        return $this->properties['mime_type'] ?: array_pop($this->getStorage()->getFileInfoByIdentifier($this->getIdentifier(), ['mimetype']));
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
     * see the constants in this class
     *
     * @return int $fileType
     */
    public function getType()
    {
        // this basically extracts the mimetype and guess the filetype based
        // on the first part of the mimetype works for 99% of all cases, and
        // we don't need to make an SQL statement like EXT:media does currently
        if (!$this->properties['type']) {
            $mimeType = $this->getMimeType();
            list($fileType) = explode('/', $mimeType);
            switch (strtolower($fileType)) {
                case 'text':
                    $this->properties['type'] = self::FILETYPE_TEXT;
                    break;
                case 'image':
                    $this->properties['type'] = self::FILETYPE_IMAGE;
                    break;
                case 'audio':
                    $this->properties['type'] = self::FILETYPE_AUDIO;
                    break;
                case 'video':
                    $this->properties['type'] = self::FILETYPE_VIDEO;
                    break;
                case 'application':

                case 'software':
                    $this->properties['type'] = self::FILETYPE_APPLICATION;
                    break;
                default:
                    $this->properties['type'] = self::FILETYPE_UNKNOWN;
            }
        }
        return (int)$this->properties['type'];
    }

    /******************
     * CONTENTS RELATED
     ******************/
    /**
     * Get the contents of this file
     *
     * @throws \RuntimeException
     * @return string File contents
     */
    public function getContents()
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821479);
        }
        return $this->getStorage()->getFileContents($this);
    }

    /**
     * Replace the current file contents with the given string
     *
     * @param string $contents The contents to write to the file.
     *
     * @throws \RuntimeException
     * @return File The file object (allows chaining).
     */
    public function setContents($contents)
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821478);
        }
        $this->getStorage()->setFileContents($this, $contents);
        return $this;
    }

    /****************************************
     * STORAGE AND MANAGEMENT RELATED METHDOS
     ****************************************/

    /**
     * Get the storage this file is located in
     *
     * @return ResourceStorage
     * @throws \RuntimeException
     */
    public function getStorage()
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
    public function exists()
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
     * @param ResourceStorage $storage
     * @return File
     */
    public function setStorage(ResourceStorage $storage)
    {
        $this->storage = $storage;
        $this->properties['storage'] = $storage->getUid();
        return $this;
    }

    /**
     * Set the identifier of this file
     *
     * @internal Should only be used by other parts of the File API (e.g. drivers after moving a file)
     * @param string $identifier
     * @return File
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Returns a combined identifier of this file, i.e. the storage UID and the
     * folder identifier separated by a colon ":".
     *
     * @return string Combined storage and file identifier, e.g. StorageUID:path/and/fileName.png
     */
    public function getCombinedIdentifier()
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
     *
     * @return bool TRUE if deletion succeeded
     */
    public function delete()
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
    public function setDeleted()
    {
        $this->deleted = true;
    }

    /**
     * Returns TRUE if this file has been deleted
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Renames this file.
     *
     * @param string $newName The new file name
     *
     * @param string $conflictMode
     * @return FileInterface
     */
    public function rename($newName, $conflictMode = DuplicationBehavior::RENAME)
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821482);
        }
        return $this->getStorage()->renameFile($this, $newName, $conflictMode);
    }

    /**
     * Copies this file into a target folder
     *
     * @param Folder $targetFolder Folder to copy file into.
     * @param string $targetFileName an optional destination fileName
     * @param string $conflictMode a value of the \TYPO3\CMS\Core\Resource\DuplicationBehavior enumeration
     *
     * @throws \RuntimeException
     * @return File The new (copied) file.
     */
    public function copyTo(Folder $targetFolder, $targetFileName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821483);
        }
        return $targetFolder->getStorage()->copyFile($this, $targetFolder, $targetFileName, $conflictMode);
    }

    /**
     * Moves the file into the target folder
     *
     * @param Folder $targetFolder Folder to move file into.
     * @param string $targetFileName an optional destination fileName
     * @param string $conflictMode a value of the \TYPO3\CMS\Core\Resource\DuplicationBehavior enumeration
     *
     * @throws \RuntimeException
     * @return File This file object, with updated properties.
     */
    public function moveTo(Folder $targetFolder, $targetFileName = null, $conflictMode = DuplicationBehavior::RENAME)
    {
        if ($this->deleted) {
            throw new \RuntimeException('File has been deleted.', 1329821484);
        }
        return $targetFolder->getStorage()->moveFile($this, $targetFolder, $targetFileName, $conflictMode);
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
     * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
     * @return string|null NULL if file is deleted, the generated URL otherwise
     */
    public function getPublicUrl($relativeToCurrentScript = false)
    {
        if ($this->deleted) {
            return null;
        }
        return $this->getStorage()->getPublicUrl($this, $relativeToCurrentScript);
    }

    /**
     * Returns a path to a local version of this file to process it locally (e.g. with some system tool).
     * If the file is normally located on a remote storages, this creates a local copy.
     * If the file is already on the local system, this only makes a new copy if $writable is set to TRUE.
     *
     * @param bool $writable Set this to FALSE if you only want to do read operations on the file.
     *
     * @throws \RuntimeException
     * @return string
     */
    public function getForLocalProcessing($writable = true)
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
     * database into this object after being intantiated.
     *
     * @param array $properties
     */
    abstract public function updateProperties(array $properties);

    /**
     * Returns the parent folder.
     *
     * @return FolderInterface
     */
    public function getParentFolder()
    {
        return $this->getStorage()->getFolder($this->getStorage()->getFolderIdentifierFromFileIdentifier($this->getIdentifier()));
    }
}
