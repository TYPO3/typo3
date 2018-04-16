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
 */
class FileReferenceUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Migrate file references that are stored in a wrong way to correct scheme';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $count = $queryBuilder->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('_FILE', \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('typolink_tag', \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->execute()->fetchColumn(0);

        if ($count) {
            $description = 'File references were saved in a wrong way and references aren\'t shown correctly in file list module.';
        }

        return (bool)$count;
    }

    /**
     * Performs the database update
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
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

            $databaseQueries[] = $updateQueryBuilder->getSQL();
            $updateQueryBuilder->execute();
        }

        $this->markWizardAsDone();

        return true;
    }
}
