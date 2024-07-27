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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Repository for accessing file objects.
 * It also serves as the public API for the indexing part of files in general.
 *
 * It is however recommended to use the ResourceFactory instead of this class,
 * as it is more flexible.
 */
#[Autoconfigure(public: true)]
readonly class FileRepository
{
    public function __construct(
        protected ResourceFactory $factory,
        protected TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Finds a File matching the given uid, regardless of the storage.
     */
    public function findByUid(int $uid): File
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        if ($this->isFrontend()) {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        }
        $row = $queryBuilder
            ->select('*')
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        if (!is_array($row)) {
            throw new \RuntimeException('Could not find row with UID "' . $uid . '" in table "sys_file"', 1314354065);
        }
        return $this->createDomainObject($row);
    }

    /**
     * Creates an object managed by this repository.
     */
    protected function createDomainObject(array $databaseRow): File
    {
        return $this->factory->getFileObject((int)$databaseRow['uid'], $databaseRow);
    }

    /**
     * Find FileReference objects by relation to other records
     *
     * @param string $tableName Table name of the related record
     * @param string $fieldName Field name of the related record
     * @param int $uid The UID of the related record (needs to be the localized uid, as translated IRRE elements relate to them)
     * @param int|null $workspaceId
     * @return FileReference[] An array of file references, empty if no objects found
     */
    public function findByRelation(string $tableName, string $fieldName, int $uid, ?int $workspaceId = null): array
    {
        $itemList = [];
        $referenceUids = [];
        if ($this->isFrontend()) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_reference');

            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            $res = $queryBuilder
                ->select('uid')
                ->from('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'tablenames',
                        $queryBuilder->createNamedParameter($tableName)
                    ),
                    $queryBuilder->expr()->eq(
                        'fieldname',
                        $queryBuilder->createNamedParameter($fieldName)
                    )
                )
                ->orderBy('sorting_foreign')
                ->executeQuery();

            while ($row = $res->fetchAssociative()) {
                $referenceUids[] = $row['uid'];
            }
        } else {
            $schema = $this->tcaSchemaFactory->get($tableName);
            $workspaceId ??= GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'id', 0);
            $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
            $relationHandler->setWorkspaceId($workspaceId);
            $relationHandler->initializeForField(
                $tableName,
                $schema->getField($fieldName),
                $uid
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
                } catch (ResourceDoesNotExistException) {
                    // No handling, just omit the invalid reference uid
                }
            }
            $itemList = $this->reapplySorting($itemList);
        }

        return $itemList;
    }

    /**
     * As sorting might have changed due to workspace overlays, PHP does the sorting again.
     *
     * @param FileReference[] $itemList
     * @return FileReference[]
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

    /**
     * Function to return the current application type based on $GLOBALS['TSFE'].
     * This function can be mocked in unit tests to be able to test frontend behaviour.
     */
    protected function isFrontend(): bool
    {
        return ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController;
    }
}
