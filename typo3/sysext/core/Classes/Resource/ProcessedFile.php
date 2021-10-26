<?php

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

use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Representation of a specific processed version of a file. These are created by the FileProcessingService,
 * which in turn uses helper classes for doing the actual file processing. See there for a detailed description.
 *
 * Objects of this class may be freshly created during runtime or being fetched from the database. The latter
 * indicates that the file has been processed earlier and was then cached.
 *
 * Each processed file—besides belonging to one file—has been created for a certain task (context) and
 * configuration. All these won't change during the lifetime of a processed file; the only thing
 * that can change is the original file, or rather it's contents. In that case, the processed file has to
 * be processed again. Detecting this is done via comparing the current SHA1 hash of the original file against
 * the one it had at the time the file was processed.
 * The configuration of a processed file indicates what should be done to the original file to create the
 * processed version. This may include things like cropping, scaling, rotating, flipping or using some special
 * magic.
 * A file may also meet the expectations set in the configuration without any processing. In that case, the
 * ProcessedFile object still exists, but there is no physical file directly linked to it. Instead, it then
 * redirects most method calls to the original file object. The data of these objects are also stored in the
 * database, to indicate that no processing is required. With such files, the identifier and name fields in the
 * database are empty to show this.
 */
class ProcessedFile extends AbstractFile
{
    /*********************************************
     * FILE PROCESSING CONTEXTS
     *********************************************/
    /**
     * Basic processing context to get a processed image with smaller
     * width/height to render a preview
     */
    const CONTEXT_IMAGEPREVIEW = 'Image.Preview';
    /**
     * Standard processing context for the frontend, that was previously
     * in \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource which only takes cropping, masking and scaling
     * into account
     */
    const CONTEXT_IMAGECROPSCALEMASK = 'Image.CropScaleMask';

    /**
     * Processing context, i.e. the type of processing done
     *
     * @var string
     */
    protected $taskType;

    /**
     * @var Processing\TaskInterface
     */
    protected $task;

    /**
     * @var Processing\TaskTypeRegistry
     */
    protected $taskTypeRegistry;

    /**
     * Processing configuration
     *
     * @var array
     */
    protected $processingConfiguration;

    /**
     * Reference to the original file this processed file has been created from.
     *
     * @var File
     */
    protected $originalFile;

    /**
     * The SHA1 hash of the original file this processed version has been created for.
     * Is used for detecting changes if the original file has been changed and thus
     * we have to recreate this processed file.
     *
     * @var string
     */
    protected $originalFileSha1;

    /**
     * A flag that shows if this object has been updated during its lifetime, i.e. the file has been
     * replaced with a new one.
     *
     * @var bool
     */
    protected $updated = false;

    /**
     * If this is set, this URL is used as public URL
     * This MUST be a fully qualified URL including host
     *
     * @var string
     */
    protected $processingUrl = '';

    /**
     * Constructor for a processed file object. Should normally not be used
     * directly, use the corresponding factory methods instead.
     *
     * @param File $originalFile
     * @param string $taskType
     * @param array $processingConfiguration
     * @param array $databaseRow
     */
    public function __construct(File $originalFile, $taskType, array $processingConfiguration, array $databaseRow = null)
    {
        $this->originalFile = $originalFile;
        $this->originalFileSha1 = $this->originalFile->getSha1();
        $this->storage = $originalFile->getStorage()->getProcessingFolder()->getStorage();
        $this->taskType = $taskType;
        $this->processingConfiguration = $processingConfiguration;
        if (is_array($databaseRow)) {
            $this->reconstituteFromDatabaseRecord($databaseRow);
        }
        $this->taskTypeRegistry = GeneralUtility::makeInstance(TaskTypeRegistry::class);
    }

    /**
     * Creates a ProcessedFile object from a database record.
     *
     * @param array $databaseRow
     */
    protected function reconstituteFromDatabaseRecord(array $databaseRow)
    {
        $this->taskType = $this->taskType ?: $databaseRow['task_type'];
        $this->processingConfiguration = $this->processingConfiguration ?: unserialize($databaseRow['configuration'] ?? '');

        $this->originalFileSha1 = $databaseRow['originalfilesha1'];
        $this->identifier = $databaseRow['identifier'];
        $this->name = $databaseRow['name'];
        $this->properties = $databaseRow;
        $this->processingUrl = $databaseRow['processing_url'] ?? '';

        if (!empty($databaseRow['storage']) && (int)$this->storage->getUid() !== (int)$databaseRow['storage']) {
            $this->storage = GeneralUtility::makeInstance(StorageRepository::class)->findByUid($databaseRow['storage']);
        }
    }

    /********************************
     * VARIOUS FILE PROPERTY GETTERS
     ********************************/

    /**
     * Returns a unique checksum for this file's processing configuration and original file.
     *
     * @return string
     */
    // @todo replace these usages with direct calls to the task object
    public function calculateChecksum()
    {
        return $this->getTask()->getConfigurationChecksum();
    }

    /*******************
     * CONTENTS RELATED
     *******************/
    /**
     * Replace the current file contents with the given string
     *
     * @param string $contents The contents to write to the file.
     * @throws \BadMethodCallException
     */
    public function setContents($contents)
    {
        throw new \BadMethodCallException('Setting contents not possible for processed file.', 1305438528);
    }

    /**
     * Injects a local file, which is a processing result into the object.
     *
     * @param string $filePath
     * @throws \RuntimeException
     */
    public function updateWithLocalFile($filePath)
    {
        if (empty($this->identifier)) {
            throw new \RuntimeException('Cannot update original file!', 1350582054);
        }
        $processingFolder = $this->originalFile->getStorage()->getProcessingFolder($this->originalFile);
        $addedFile = $this->storage->updateProcessedFile($filePath, $this, $processingFolder);

        // Update some related properties
        $this->identifier = $addedFile->getIdentifier();
        $this->originalFileSha1 = $this->originalFile->getSha1();
        if ($addedFile instanceof AbstractFile) {
            $this->updateProperties($addedFile->getProperties());
        }
        $this->deleted = false;
        $this->updated = true;
    }

    /*****************************************
     * STORAGE AND MANAGEMENT RELATED METHODS
     *****************************************/
    /**
     * Returns TRUE if this file is indexed
     *
     * @return bool
     */
    public function isIndexed()
    {
        // Processed files are never indexed; instead you might be looking for isPersisted()
        return false;
    }

    /**
     * Checks whether the ProcessedFile already has an entry in sys_file_processedfile table
     *
     * @return bool
     */
    public function isPersisted()
    {
        return is_array($this->properties) && array_key_exists('uid', $this->properties) && $this->properties['uid'] > 0;
    }

    /**
     * Checks whether the ProcessedFile Object is newly created
     *
     * @return bool
     */
    public function isNew()
    {
        return !$this->isPersisted();
    }

    /**
     * Checks whether the object since last reconstitution, and therefore
     * needs persistence again
     *
     * @return bool
     */
    public function isUpdated()
    {
        return $this->updated;
    }

    /**
     * Sets a new file name
     *
     * @param string $name
     */
    public function setName($name)
    {
        // Remove the existing file, but only we actually have a name or the name has changed
        if (!empty($this->name) && $this->name !== $name && $this->exists()) {
            $this->delete();
        }

        $this->name = $name;
        // @todo this is a *weird* hack that will fail if the storage is non-hierarchical!
        $this->identifier = $this->storage->getProcessingFolder($this->originalFile)->getIdentifier() . $this->name;

        $this->updated = true;
    }

    /**
     * Checks if this file exists.
     * Since the original file may reside in a different storage
     * we ask the original file if it exists in case the processed is representing it
     *
     * @return bool TRUE if this file physically exists
     */
    public function exists()
    {
        if ($this->usesOriginalFile()) {
            return $this->originalFile->exists();
        }

        return parent::exists();
    }

    /******************
     * SPECIAL METHODS
     ******************/

    /**
     * Returns TRUE if this file is already processed.
     *
     * @return bool
     */
    public function isProcessed()
    {
        return $this->updated || ($this->isPersisted() && !$this->needsReprocessing());
    }

    /**
     * Getter for the Original, unprocessed File
     *
     * @return File
     */
    public function getOriginalFile()
    {
        return $this->originalFile;
    }

    /**
     * Get the identifier of the file
     *
     * If there is no processed file in the file system  (as the original file did not have to be modified e.g.
     * when the original image is in the boundaries of the maxW/maxH stuff), then just return the identifier of
     * the original file
     *
     * @return string
     */
    public function getIdentifier()
    {
        return (!$this->usesOriginalFile()) ? $this->identifier : $this->getOriginalFile()->getIdentifier();
    }

    /**
     * Get the name of the file
     *
     * If there is no processed file in the file system (as the original file did not have to be modified e.g.
     * when the original image is in the boundaries of the maxW/maxH stuff)
     * then just return the name of the original file
     *
     * @return string
     */
    public function getName()
    {
        if ($this->usesOriginalFile()) {
            return $this->originalFile->getName();
        }
        return $this->name;
    }

    /**
     * Updates properties of this object. Do not use this to reconstitute an object from the database; use
     * reconstituteFromDatabaseRecord() instead!
     *
     * @param array $properties
     */
    public function updateProperties(array $properties)
    {
        if (!is_array($this->properties)) {
            $this->properties = [];
        }

        if (array_key_exists('uid', $properties) && MathUtility::canBeInterpretedAsInteger($properties['uid'])) {
            $this->properties['uid'] = $properties['uid'];
        }
        if (isset($properties['processing_url'])) {
            $this->processingUrl = $properties['processing_url'];
        }

        // @todo we should have a blacklist of properties that might not be updated
        $this->properties = array_merge($this->properties, $properties);

        // @todo when should this update be done?
        if (!$this->isUnchanged() && $this->exists()) {
            $storage = $this->storage;
            if ($this->usesOriginalFile()) {
                $storage = $this->originalFile->getStorage();
            }
            $this->properties = array_merge($this->properties, $storage->getFileInfo($this));
        }
    }

    /**
     * Basic array function for the DB update
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->usesOriginalFile()) {
            $properties = $this->originalFile->getProperties();
            unset($properties['uid']);
            $properties['identifier'] = '';
            $properties['name'] = null;
            $properties['processing_url'] = '';

            // Use width + height set in processed file
            $properties['width'] = $this->properties['width'] ?? 0;
            $properties['height'] = $this->properties['height'] ?? 0;
        } else {
            $properties = $this->properties;
            $properties['identifier'] = $this->getIdentifier();
            $properties['name'] = $this->getName();
        }

        $properties['configuration'] = serialize($this->processingConfiguration);

        return array_merge($properties, [
            'storage' => $this->getStorage()->getUid(),
            'checksum' => $this->calculateChecksum(),
            'task_type' => $this->taskType,
            'configurationsha1' => sha1($properties['configuration']),
            'original' => $this->originalFile->getUid(),
            'originalfilesha1' => $this->originalFileSha1,
        ]);
    }

    /**
     * Returns TRUE if this file has not been changed during processing (i.e., we just deliver the original file)
     *
     * @return bool
     */
    protected function isUnchanged()
    {
        return !($this->properties['width'] ?? false) && $this->usesOriginalFile();
    }

    /**
     * Defines that the original file should be used.
     */
    public function setUsesOriginalFile()
    {
        // @todo check if some of these properties can/should be set in a generic update method
        $this->identifier = $this->originalFile->getIdentifier();
        $this->updated = true;
        $this->processingUrl = '';
        $this->originalFileSha1 = $this->originalFile->getSha1();
    }

    public function updateProcessingUrl(string $url): void
    {
        $this->updated = true;
        $this->processingUrl = $url;
    }

    /**
     * @return bool
     */
    public function usesOriginalFile()
    {
        return empty($this->identifier) || $this->identifier === $this->originalFile->getIdentifier();
    }

    /**
     * Returns TRUE if the original file of this file changed and the file should be processed again.
     *
     * @return bool
     */
    public function isOutdated()
    {
        return $this->needsReprocessing();
    }

    /**
     * Delete processed file
     *
     * @param bool $force
     * @return bool
     */
    public function delete($force = false)
    {
        if (!$force && $this->isUnchanged()) {
            return false;
        }
        // Only delete file when original isn't used
        if (!$this->usesOriginalFile()) {
            return parent::delete();
        }
        return true;
    }

    /**
     * Getter for file-properties
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getProperty($key)
    {
        // The uid always (!) has to come from this file and never the original file (see getOriginalFile() to get this)
        if ($this->isUnchanged() && $key !== 'uid') {
            return $this->originalFile->getProperty($key);
        }
        return $this->properties[$key];
    }

    /**
     * Returns the uid of this file
     *
     * @return int
     */
    public function getUid()
    {
        return $this->properties['uid'] ?? 0;
    }

    /**
     * Checks if the ProcessedFile needs reprocessing
     *
     * @return bool
     */
    public function needsReprocessing()
    {
        $fileMustBeRecreated = false;

        // if original is missing we can not reprocess the file
        if ($this->originalFile->isMissing()) {
            return false;
        }

        // processedFile does not exist
        if (!$this->usesOriginalFile() && !$this->exists()) {
            $fileMustBeRecreated = true;
        }

        // hash does not match
        if (array_key_exists('checksum', $this->properties) && $this->calculateChecksum() !== $this->properties['checksum']) {
            $fileMustBeRecreated = true;
        }

        // original file changed
        if ($this->originalFile->getSha1() !== $this->originalFileSha1) {
            $fileMustBeRecreated = true;
        }

        if (!array_key_exists('uid', $this->properties)) {
            $fileMustBeRecreated = true;
        }

        // remove outdated file
        if ($fileMustBeRecreated && $this->exists()) {
            $this->delete();
        }
        return $fileMustBeRecreated;
    }

    /**
     * Returns the processing information
     *
     * @return array
     */
    public function getProcessingConfiguration()
    {
        return $this->processingConfiguration;
    }

    /**
     * Getter for the task identifier.
     *
     * @return string
     */
    public function getTaskIdentifier()
    {
        return $this->taskType;
    }

    /**
     * Returns the task object associated with this processed file.
     *
     * @return Processing\TaskInterface
     * @throws \RuntimeException
     */
    public function getTask(): Processing\TaskInterface
    {
        if ($this->task === null) {
            $this->task = $this->taskTypeRegistry->getTaskForType($this->taskType, $this, $this->processingConfiguration);
        }

        return $this->task;
    }

    /**
     * Generate the name of of the new File
     *
     * @return string
     */
    public function generateProcessedFileNameWithoutExtension()
    {
        $name = $this->originalFile->getNameWithoutExtension();
        $name .= '_' . $this->originalFile->getUid();
        $name .= '_' . $this->calculateChecksum();

        return $name;
    }

    /**
     * Returns a publicly accessible URL for this file
     *
     * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all. Deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     * @return string|null NULL if file is deleted, the generated URL otherwise
     */
    public function getPublicUrl($relativeToCurrentScript = false)
    {
        if ($this->processingUrl) {
            return $this->processingUrl;
        }
        if ($this->deleted) {
            return null;
        }
        // @deprecated $relativeToCurrentScript since v11, will be removed in TYPO3 v12.0
        if ($this->usesOriginalFile()) {
            return $this->getOriginalFile()->getPublicUrl($relativeToCurrentScript);
        }
        return $this->getStorage()->getPublicUrl($this, $relativeToCurrentScript);
    }
}
