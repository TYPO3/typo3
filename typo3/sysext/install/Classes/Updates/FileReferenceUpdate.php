<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Migrate file references that are stored in a wrong way to correct scheme
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class FileReferenceUpdate implements UpgradeWizardInterface
{
    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'fileReferenceUpdate';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Migrate file references that are stored in a wrong way to correct scheme';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'File references were saved in a wrong way and references are not shown correctly in file list module.';
    }

    /**
     * @return bool True if there are records to update
     */
    public function updateNecessary(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        return (bool)$queryBuilder->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('_FILE', \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('typolink_tag', \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Performs the update
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_refindex');
        $queryBuilder = $connection->createQueryBuilder();
        $statement = $queryBuilder->select('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('_FILE', \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('typolink_tag', \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->execute();
        while ($record = $statement->fetch()) {
            $fileReference = 0;
            if (MathUtility::canBeInterpretedAsInteger($record['ref_string'])) {
                $fileReference = $record['ref_string'];
            } else {
                try {
                    $fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($record['ref_string']);
                    if ($fileObject instanceof File) {
                        $fileReference = $fileObject->getUid();
                    }
                } catch (Exception $e) {
                    // Silently catch if there is no file object
                }
            }

            $updateQueryBuilder = $connection->createQueryBuilder();
            $updateQueryBuilder->update('sys_refindex')
                ->where(
                    $updateQueryBuilder->expr()->eq(
                        'hash',
                        $updateQueryBuilder->createNamedParameter($record['hash'], \PDO::PARAM_STR)
                    )
                );

            if ($fileReference) {
                $updateQueryBuilder->set('ref_table', 'sys_file')
                    ->set('ref_uid', $fileReference)
                    ->set('ref_string', '');
            } else {
                $updateQueryBuilder->set('deleted', 1);
            }

            $updateQueryBuilder->execute();
        }

        return true;
    }
}
