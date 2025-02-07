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

namespace TYPO3\CMS\Frontend\Resource;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Object to collect files from various sources during runtime.
 * Sources can be file references, file collections or folders.
 *
 * Use in FILES Content Object or for a Fluid Data Processor.
 *
 * Is not persisted, use only in FE.
 *
 * @internal this is an internal TYPO3 implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 *
 * @todo The file collector is used for intermediate collection in scoped places. Therefore, the collector
 *       can't be shared. Evaluate if the collector can be build scope aware and made sharable again.
 */
#[Autoconfigure(public: true, shared: false)]
class FileCollector implements \Countable, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * The collected File or FileReference objects
     *
     * @var FileInterface[]
     */
    protected array $files = [];

    public function __construct(
        protected readonly ResourceFactory $resourceFactory,
        protected readonly FileCollectionRepository $fileCollectionRepository,
        protected readonly FileRepository $fileRepository,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Add files by UID
     */
    public function addFiles(array $fileUids = []): void
    {
        foreach ($fileUids as $fileUid) {
            try {
                $this->addFileObject($this->resourceFactory->getFileObject($fileUid));
            } catch (Exception $e) {
                $this->logger->warning(
                    'The file with uid  "' . $fileUid
                    . '" could not be found and won\'t be included in frontend output',
                    ['exception' => $e]
                );
            }
        }
    }

    /**
     * Add files to the collection from a relation.
     *
     * @param string $relationTable The table of the relation (e.g. tt_content or pages)
     * @param string $relationField The field which holds the files (e.g. media or images)
     * @param array $referenceRecord the record which is referencing the files
     */
    public function addFilesFromRelation(string $relationTable, string $relationField, array $referenceRecord): void
    {
        $fileReferences = $this->getFileReferences($relationTable, $relationField, $referenceRecord);
        if (!empty($fileReferences)) {
            $this->addFileObjects($fileReferences);
        }
    }

    /**
     * Add files from UIDs of a reference.
     */
    public function addFileReferences(array $fileReferenceUids = []): void
    {
        foreach ($fileReferenceUids as $fileReferenceUid) {
            $fileObject = $this->resourceFactory->getFileReferenceObject((int)$fileReferenceUid);
            if (!$fileObject instanceof FileInterface) {
                continue;
            }
            $this->addFileObject($fileObject);
        }
    }

    /**
     * Add files to the collection from multiple file collections.
     */
    public function addFilesFromFileCollections(array $fileCollectionUids = []): void
    {
        foreach ($fileCollectionUids as $fileCollectionUid) {
            $this->addFilesFromFileCollection((int)$fileCollectionUid);
        }
    }

    /**
     * Add files to the collection from one single file collection.
     */
    public function addFilesFromFileCollection(int $fileCollectionUid): void
    {
        if ($fileCollectionUid <= 0) {
            return;
        }
        try {
            $fileCollection = $this->fileCollectionRepository->findByUid($fileCollectionUid);
            if ($fileCollection instanceof AbstractFileCollection) {
                $fileCollection->loadContents();
                $files = $fileCollection->getItems();
                $this->addFileObjects($files);
            }
        } catch (Exception $e) {
            $this->logger->warning(
                'The file-collection with uid  "' . $fileCollectionUid
                . '" could not be found or contents could not be loaded and won\'t be included in frontend output.',
                ['exception' => $e]
            );
        }
    }

    /**
     * Add files to the collection from multiple folders.
     *
     * @param array $folderIdentifiers The folder identifiers
     * @param bool $recursive Add files recursive from given folders
     */
    public function addFilesFromFolders(array $folderIdentifiers, bool $recursive = false): void
    {
        foreach ($folderIdentifiers as $folderIdentifier) {
            $this->addFilesFromFolder($folderIdentifier, $recursive);
        }
    }

    /**
     * Add files to the collection from one single folder.
     *
     * @param string $folderIdentifier The folder identifier
     * @param bool $recursive Add files recursive from given folders
     */
    public function addFilesFromFolder(string $folderIdentifier, bool $recursive = false): void
    {
        if ($folderIdentifier) {
            try {
                if (str_starts_with($folderIdentifier, 't3://folder')) {
                    // a t3://folder link to a folder in FAL
                    $linkService = GeneralUtility::makeInstance(LinkService::class);
                    $data = $linkService->resolveByStringRepresentation($folderIdentifier);
                    $folder = $data['folder'];
                } else {
                    $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($folderIdentifier);
                }
                if ($folder instanceof Folder) {
                    $files = $folder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive);
                    $this->addFileObjects(array_values($files));
                }
            } catch (Exception $e) {
                $this->logger->warning('The folder with identifier "{folder}" could not be found and won\'t be included in frontend output', [
                    'folder' => $folderIdentifier,
                    'exception' => $e,
                ]);
            }
        }
    }

    /**
     * Sort the file objects based on a property.
     *
     * @param string $sortingProperty The sorting property
     * @param 'ascending'|'descending'|'random' $sortingOrder The sorting order
     */
    public function sort(string $sortingProperty = '', string $sortingOrder = 'ascending'): void
    {
        if ($sortingProperty !== '' && count($this->files) > 1) {
            @usort(
                $this->files,
                static function (
                    FileInterface $a,
                    FileInterface $b
                ) use ($sortingProperty) {
                    if ($a->hasProperty($sortingProperty) && $b->hasProperty($sortingProperty)) {
                        return strnatcasecmp((string)$a->getProperty($sortingProperty), (string)$b->getProperty($sortingProperty));
                    }
                    return 0;
                }
            );

            switch (strtolower($sortingOrder)) {
                case 'descending':
                case 'desc':
                    $this->files = array_reverse($this->files);
                    break;
                case 'random':
                case 'rand':
                    shuffle($this->files);
                    break;
            }
        }
    }

    /**
     * Add a file object to the collection.
     */
    public function addFileObject(FileInterface $file): void
    {
        $this->files[] = $file;
    }

    /**
     * Add multiple file objects to the collection.
     *
     * @param FileInterface[] $files The file objects
     */
    public function addFileObjects(array $files): void
    {
        $this->files = array_merge($this->files, $files);
    }

    /**
     * Final getter method to fetch the accumulated data.
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function count(): int
    {
        return count($this->files);
    }

    /**
     * Gets file references for a given record field, also deal with translated elements,
     * where file references could be attached.
     *
     * @param string $tableName Name of the table
     * @param string $fieldName Name of the field
     * @param array $element The parent element referencing to files
     */
    protected function getFileReferences(string $tableName, string $fieldName, array $element): array
    {
        $currentId = !empty($element['uid']) ? (int)$element['uid'] : 0;

        // Fetch the references of the default element
        try {
            $references = $this->fileRepository->findByRelation($tableName, $fieldName, $currentId);
        } catch (FileDoesNotExistException $e) {
            /**
             * We just catch the exception here
             * Reasoning: There is nothing an editor or even admin could do
             */
            return [];
        } catch (\InvalidArgumentException $e) {
            /**
             * The storage does not exist anymore
             * Log the exception message for admins as they maybe can restore the storage
             */
            $this->logger->error('{exception_message}: table: {table}, field: {field}, currentId: {current_id}', [
                'table' => $tableName,
                'field' => $fieldName,
                'currentId' => $currentId,
                'exception' => $e,
            ]);
            return [];
        }

        $localizedId = $element['_LOCALIZED_UID'] ?? null;

        if ($localizedId !== null && $this->tcaSchemaFactory->get($tableName)->isLanguageAware()) {
            $localizedReferences = $this->fileRepository->findByRelation($tableName, $fieldName, (int)$localizedId);
            $references = $localizedReferences;
        }

        return $references;
    }
}
