<?php
namespace TYPO3\CMS\Reports\Report\Status;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Performs several checks about the FAL status
 */
class FalStatus implements StatusProviderInterface
{
    /**
     * Determines the status of the FAL index.
     *
     * @return array List of statuses
     */
    public function getStatus()
    {
        $statuses = [
            'MissingFiles' => $this->getMissingFilesStatus(),
        ];
        return $statuses;
    }

    /**
     * Checks if there are files marked as missed.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether there are files marked as missed or not
     */
    protected function getMissingFilesStatus()
    {
        $value = $this->getLanguageService()->getLL('status_none');
        $count = 0;
        $maxFilesToShow = 100;
        $message = '';
        $severity = ReportStatus::OK;

        /** @var StorageRepository $storageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storageObjects = $storageRepository->findAll();
        $storages = [];

        /** @var \TYPO3\CMS\Core\Resource\ResourceStorage $storageObject */
        foreach ($storageObjects as $storageObject) {

            // We only check missing files for storages that are online
            if ($storageObject->isOnline()) {
                $storages[$storageObject->getUid()] = $storageObject;
            }
        }

        if (!empty($storages)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');

            $count = $queryBuilder
                ->count('*')
                ->from('sys_file')
                ->where(
                    $queryBuilder->expr()->eq(
                        'missing',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        'storage',
                        $queryBuilder->createNamedParameter(array_keys($storages), Connection::PARAM_INT_ARRAY)
                    )
                )
                ->execute()
                ->fetchColumn(0);
        }

        if ($count) {
            $value = sprintf($this->getLanguageService()->getLL('status_missingFilesCount'), $count);
            $severity = ReportStatus::WARNING;

            $queryBuilder->resetQueryParts();
            $files = $queryBuilder
                ->select('identifier', 'storage')
                ->from('sys_file')
                ->where(
                    $queryBuilder->expr()->eq(
                        'missing',
                        $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        'storage',
                        $queryBuilder->createNamedParameter(array_keys($storages), Connection::PARAM_INT_ARRAY)
                    )
                )
                ->setMaxResults($maxFilesToShow)
                ->execute()
                ->fetchAll();

            $message = '<p>' . $this->getLanguageService()->getLL('status_missingFilesMessage') . '</p>';
            foreach ($files as $file) {
                $message .= $storages[$file['storage']]->getName() . ' ' . $file['identifier'] . '<br />';
            }

            if ($count > $maxFilesToShow) {
                $message .= '...<br />';
            }
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->getLL('status_missingFiles'), $value, $message, $severity);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
