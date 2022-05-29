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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
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
     * @param ?int $workspaceId
     * @return array An array of objects, empty if no objects found
     * @throws \InvalidArgumentException
     */
    public function findByRelation($tableName, $fieldName, $uid, int $workspaceId = null)
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
                ->executeQuery();

            while ($row = $res->fetchAssociative()) {
                $referenceUids[] = $row['uid'];
            }
        } else {
            $workspaceId ??= GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id', 0);
            $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
            $relationHandler->setWorkspaceId($workspaceId);
            $relationHandler->start(
                '',
                'sys_file_reference',
                '',
                $uid,
                $tableName,
                BackendUtility::getTcaFieldConfiguration($tableName, $fieldName)
            );
            if (!empty($relationHandler->tableArray['sys_file_reference'])) {
                $relationHandler->processDeletePlaceholder();
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
            $itemList = $this->reapplySorting($itemList);
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
     * As sorting might have changed due to workspace overlays, PHP does the sorting again.
     *
     * @param array $itemList
     */
    protected function reapplySorting(array $itemList): array
    {
        uasort(
            $itemList,
            static function (FileReference $a, FileReference $b) {
                $sortA = (int)$a->getReferenceProperty('sorting_foreign');
                $sortB = (int)$b->getReferenceProperty('sorting_foreign');

                if ($sortA === $sortB) {
                    return 0;
                }

                return ($sortA < $sortB) ? -1 : 1;
            }
        );
        return $itemList;
    }
}
