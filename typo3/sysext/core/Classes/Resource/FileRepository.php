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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Repository for accessing files
 * it also serves as the public API for the indexing part of files in general
 */
class FileRepository extends AbstractRepository
{
    /**
     * The main object type of this class. In some cases (fileReference) this
     * repository can also return FileReference objects, implementing the
     * common FileInterface.
     *
     * @var string
     */
    protected $objectType = File::class;

    /**
     * Main File object storage table. Note that this repository also works on
     * the sys_file_reference table when returning FileReference objects.
     *
     * @var string
     */
    protected $table = 'sys_file';

    /**
     * Creates an object managed by this repository.
     *
     * @param array $databaseRow
     * @return File
     */
    protected function createDomainObject(array $databaseRow)
    {
        return $this->factory->getFileObject($databaseRow['uid'], $databaseRow);
    }

    /**
     * Find FileReference objects by relation to other records
     *
     * @param string $tableName Table name of the related record
     * @param string $fieldName Field name of the related record
     * @param int $uid The UID of the related record (needs to be the localized uid, as translated IRRE elements relate to them)
     * @return array An array of objects, empty if no objects found
     * @throws \InvalidArgumentException
     */
    public function findByRelation($tableName, $fieldName, $uid)
    {
        $itemList = [];
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            throw new \InvalidArgumentException(
                'UID of related record has to be an integer. UID given: "' . $uid . '"',
                1316789798
            );
        }
        $referenceUids = [];
        if ($this->getEnvironmentMode() === 'FE') {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_reference');

            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            $res = $queryBuilder
                ->select('uid')
                ->from('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter($tableName, \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter($fieldName, \PDO::PARAM_STR)
                    )
                )
                ->orderBy('sorting_foreign')
                ->execute();

            while ($row = $res->fetch()) {
                $referenceUids[] = $row['uid'];
            }
        } else {
            /** @var RelationHandler $relationHandler */
            $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
            $relationHandler->start(
                '',
                'sys_file_reference',
                '',
                $uid,
                $tableName,
                BackendUtility::getTcaFieldConfiguration($tableName, $fieldName)
            );
            if (!empty($relationHandler->tableArray['sys_file_reference'])) {
                $referenceUids = $relationHandler->tableArray['sys_file_reference'];
            }
        }
        if (!empty($referenceUids)) {
            foreach ($referenceUids as $referenceUid) {
                try {
                    // Just passing the reference uid, the factory is doing workspace
                    // overlays automatically depending on the current environment
                    $itemList[] = $this->factory->getFileReferenceObject($referenceUid);
                } catch (ResourceDoesNotExistException $exception) {
                    // No handling, just omit the invalid reference uid
                }
            }
        }

        return $itemList;
    }

    /**
     * Find FileReference objects by uid
     *
     * @param int $uid The UID of the sys_file_reference record
     * @return FileReference|bool
     * @throws \InvalidArgumentException
     */
    public function findFileReferenceByUid($uid)
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            throw new \InvalidArgumentException('The UID of record has to be an integer. UID given: "' . $uid . '"', 1316889798);
        }
        try {
            $fileReferenceObject = $this->factory->getFileReferenceObject($uid);
        } catch (\InvalidArgumentException $exception) {
            $fileReferenceObject = false;
        }
        return $fileReferenceObject;
    }

    /**
     * Search for files by name in a given folder
     *
     * @param Folder $folder
     * @param string $fileName
     * @return File[]
     * @internal
     */
    public function searchByName(Folder $folder, $fileName)
    {
        /** @var ResourceFactory $fileFactory */
        $fileFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $storage = $folder->getStorage();
        $folders = $storage->getFoldersInFolder($folder, 0, 0, true, true);
        $folders[$folder->getIdentifier()] = $folder;

        $fileRecords = $this->getFileIndexRepository()->findByFolders($folders, false, $fileName);
        $fileRecords = array_merge($fileRecords, $this->getFileIndexRepository()->findBySearchWordInMetaData($fileName));

        $files = [];
        foreach ($fileRecords as $fileRecord) {
            try {
                $file = $fileFactory->getFileObject($fileRecord['uid'], $fileRecord);
                if ($storage->checkFileAndFolderNameFilters($file)) {
                    $files[] = $file;
                }
            } catch (Exception\FileDoesNotExistException $ignoredException) {
                continue;
            }
        }

        return $files;
    }

    /**
     * Return a file index repository
     *
     * @return FileIndexRepository
     */
    protected function getFileIndexRepository()
    {
        return FileIndexRepository::getInstance();
    }
}
