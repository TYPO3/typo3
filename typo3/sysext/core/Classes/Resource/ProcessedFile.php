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

use TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
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
    public const CONTEXT_IMAGEPREVIEW = 'Image.Preview';
    /**
     * Standard processing context for the frontend, that was previously
     * in \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource which only takes cropping, masking and scaling
     * into account
     */
    public const CONTEXT_IMAGECROPSCALEMASK = 'Image.CropScaleMask';

    /**
     * Processing context, i.e. the type of processing done
     */
    protected string $taskType;

    protected ?Processing\TaskInterface $task = null;
    protected Processing\TaskTypeRegistry $taskTypeRegistry;

    /**
     * Processing configuration
     */
    protected ?array $processingConfiguration;

    /**
     * Reference to the original file this processed file has been created from.
     */
    protected File $originalFile;

    /**
     * The SHA1 hash of the original file this processed version has been created for.
     * Is used for detecting changes if the original file has been changed and thus
     * we have to recreate this processed file.
     */
    protected ?string $originalFileSha1;

    /**
     * A flag that shows if this object has been updated during its lifetime, i.e. the file has been
     * replaced with a new one.
     */
    protected bool $updated = false;

    /**
     * If this is set, this URL is used as public URL
     * This MUST be a fully qualified URL including host
     */
    protected string $processingUrl;

    protected string $identifier = '';

    /**
     * Constructor for a processed file object. Should normally not be used
     * directly, use the corresponding factory methods instead.
     */
    public function __construct(File $originalFile, string $taskType, array $processingConfiguration, ?array $databaseRow = null)
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
     */
    protected function reconstituteFromDatabaseRecord(array $databaseRow): void
    {
        $this->taskType = $this->taskType ?: $databaseRow['task_type'];
        // @todo In case the original configuration contained file objects the reconstitution fails. See ConfigurationService->serialize()
        $this->processingConfiguration = $this->processingConfiguration ?: (array)unserialize($databaseRow['configuration'] ?? '');

        $this->originalFileSha1 = $databaseRow['originalfilesha1'];
        $this->identifier = (string)$databaseRow['identifier'];
        $this->name = (string)$databaseRow['name'];
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
     */
    protected function calculateChecksum(): string
    {
        return $this->getTask()->getConfigurationChecksum();
    }

    /*******************
     * CONTENTS RELATED
     *******************/
    /**
     * Replace the current file contents with the given string
     *
     * @throws \BadMethodCallException
     */
    public function setContents(string $contents): self
    {
        throw new \BadMethodCallException('Setting contents not possible for processed file.', 1305438528);
    }

    /**
     * Injects a local file, which is a processing result into the object.
     *
     * @param string $filePath
     * @throws \RuntimeException
     */
    public function updateWithLocalFile(string $filePath): void
    {
        if (empty($this->identifier)) {
            throw new \RuntimeException('Cannot update original file!', 1350582054);
        }
        $processingFolder = $this->originalFile->getStorage()->getProcessingFolder($this->originalFile);
        $addedFile = $this->storage->updateProcessedFile($filePath, $this, $processingFolder);

        // Update some related properties
        $this->identifier = $addedFile->getIdentifier();
        $this->originalFileSha1 = $this->originalFile->getSha1();
        $this->updateProperties($addedFile->getProperties());
        $this->deleted = false;
        $this->updated = true;
    }

    /*****************************************
     * STORAGE AND MANAGEMENT RELATED METHODS
     *****************************************/
    /**
     * Returns TRUE if this file is indexed
     */
    public function isIndexed(): false
    {
        // Processed files are never indexed; instead you might be looking for isPersisted()
        return false;
    }

    /**
     * Checks whether the ProcessedFile already has an entry in sys_file_processedfile table
     */
    public function isPersisted(): bool
    {
        return array_key_exists('uid', $this->properties) && $this->properties['uid'] > 0;
    }

    /**
     * Checks whether the ProcessedFile Object is newly created
     */
    public function isNew(): bool
    {
        return !$this->isPersisted();
    }

    /**
     * Checks whether the object since last reconstitution, and therefore
     * needs persistence again
     */
    public function isUpdated(): bool
    {
        return $this->updated;
    }

    /**
     * Sets a new file name
     */
    public function setName(string $name): void
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
    public function exists(): bool
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
     */
    public function isProcessed(): bool
    {
        return $this->updated || ($this->isPersisted() && !$this->needsReprocessing());
    }

    /**
     * Getter for the Original, unprocessed File
     */
    public function getOriginalFile(): File
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
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return (!$this->usesOriginalFile()) ? $this->identifier : $this->getOriginalFile()->getIdentifier();
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * Get the name of the file
     *
     * If there is no processed file in the file system (as the original file did not have to be modified e.g.
     * when the original image is in the boundaries of the maxW/maxH stuff)
     * then just return the name of the original file
     *
     * @return non-empty-string
     */
    public function getName(): string
    {
        if ($this->usesOriginalFile()) {
            return $this->originalFile->getName();
        }
        return $this->name;
    }

    /**
     * Updates properties of this object. Do not use this to reconstitute an object from the database; use
     * reconstituteFromDatabaseRecord() instead!
     */
    public function updateProperties(array $properties): void
    {
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
     * @return array<non-empty-string, mixed>
     */
    public function toArray(): array
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

        $properties['configuration'] = (new ConfigurationService())->serialize($this->processingConfiguration);

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
     */
    protected function isUnchanged(): bool
    {
        return !($this->properties['width'] ?? false) && $this->usesOriginalFile();
    }

    /**
     * Defines that the original file should be used.
     */
    public function setUsesOriginalFile(): void
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

    public function usesOriginalFile(): bool
    {
        return empty($this->identifier) || $this->identifier === $this->originalFile->getIdentifier();
    }

    /**
     * Returns TRUE if the original file of this file changed and the file should be processed again.
     */
    public function isOutdated(): bool
    {
        return $this->needsReprocessing();
    }

    /**
     * Delete processed file
     */
    public function delete(bool $force = false): bool
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
     * @param non-empty-string $key
     */
    public function getProperty(string $key): mixed
    {
        // The uid always (!) has to come from this file and never the original file (see getOriginalFile() to get this)
        if ($this->isUnchanged() && $key !== 'uid') {
            return $this->originalFile->getProperty($key);
        }
        return $this->properties[$key] ?? null;
    }

    /**
     * Get the MIME type of this file
     *
     * @throws \RuntimeException
     * @return non-empty-string mime type
     */
    public function getMimeType(): string
    {
        if ($this->usesOriginalFile()) {
            return $this->getOriginalFile()->getMimeType();
        }
        return parent::getMimeType();
    }

    /**
     * @throws \RuntimeException
     * @return int<0, max>
     */
    public function getSize(): int
    {
        if ($this->usesOriginalFile()) {
            return $this->getOriginalFile()->getSize();
        }
        return parent::getSize();
    }

    /**
     * Returns the uid of this file
     */
    public function getUid(): int
    {
        return (int)($this->properties['uid'] ?? 0);
    }

    /**
     * Checks if the ProcessedFile needs reprocessing
     */
    public function needsReprocessing(): bool
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
     */
    public function getProcessingConfiguration(): array
    {
        return $this->processingConfiguration;
    }

    /**
     * Getter for the task identifier.
     */
    public function getTaskIdentifier(): string
    {
        return $this->taskType;
    }

    /**
     * Returns the task object associated with this processed file.
     */
    public function getTask(): Processing\TaskInterface
    {
        if ($this->task === null) {
            $this->task = $this->taskTypeRegistry->getTaskForType($this->taskType, $this, $this->processingConfiguration);
        }

        return $this->task;
    }

    /**
     * Generate the name of the new File
     */
    public function generateProcessedFileNameWithoutExtension(): string
    {
        $name = $this->originalFile->getNameWithoutExtension();
        $name .= '_' . $this->originalFile->getUid();
        $name .= '_' . $this->calculateChecksum();

        return $name;
    }

    /**
     * Returns a publicly accessible URL for this file
     *
     * @return non-empty-string|null NULL if file is deleted, the generated URL otherwise
     */
    public function getPublicUrl(): ?string
    {
        if (isset($this->processingUrl) && $this->processingUrl !== '') {
            return $this->processingUrl;
        }
        if ($this->deleted) {
            return null;
        }
        if ($this->usesOriginalFile()) {
            return $this->getOriginalFile()->getPublicUrl();
        }
        return $this->getStorage()->getPublicUrl($this);
    }
}
