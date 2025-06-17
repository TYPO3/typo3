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

namespace TYPO3\CMS\Reports\Report\Status;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Service\ResourceConsistencyService;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Validation\ResultException;
use TYPO3\CMS\Core\Validation\ResultRenderingTrait;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\Status as ReportStatus;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Performs several checks about the FAL status
 */
class FalStatus implements StatusProviderInterface
{
    use ResultRenderingTrait;

    public function __construct(private readonly ResourceConsistencyService $resourceConsistencyService) {}

    /**
     * Determines the status of the FAL index.
     *
     * @return Status[] List of statuses
     */
    public function getStatus(): array
    {
        return [
            'MissingFiles' => $this->getMissingFilesStatus(),
            'ConsistencyCheck' => $this->getConsistencyCheckStatus(),
        ];
    }

    public function getDetailedStatus(): array
    {
        return [
            'ConsistencyCheck' => $this->getConsistencyCheckStatus(),
        ];
    }

    public function getLabel(): string
    {
        return 'fal';
    }

    /**
     * Checks if there are files marked as missed.
     *
     * @return \TYPO3\CMS\Reports\Status An object representing whether there are files marked as missed or not
     */
    protected function getMissingFilesStatus()
    {
        $value = $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_none');
        $count = 0;
        $maxFilesToShow = 100;
        $message = '';
        $severity = ContextualFeedbackSeverity::OK;

        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storageObjects = $storageRepository->findAll();
        $storages = [];

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
                        $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        'storage',
                        $queryBuilder->createNamedParameter(array_keys($storages), Connection::PARAM_INT_ARRAY)
                    )
                )
                ->executeQuery()
                ->fetchOne();
        }

        if ($count) {
            $value = sprintf($this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_missingFilesCount'), $count);
            $severity = ContextualFeedbackSeverity::WARNING;

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
            $files = $queryBuilder
                ->select('identifier', 'storage')
                ->from('sys_file')
                ->where(
                    $queryBuilder->expr()->eq(
                        'missing',
                        $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        'storage',
                        $queryBuilder->createNamedParameter(array_keys($storages), Connection::PARAM_INT_ARRAY)
                    )
                )
                ->setMaxResults($maxFilesToShow)
                ->executeQuery()
                ->fetchAllAssociative();

            $message = '<p>' . $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_missingFilesMessage') . '</p>';
            foreach ($files as $file) {
                $message .= $storages[$file['storage']]->getName() . ' ' . $file['identifier'] . '<br />';
            }

            if ($count > $maxFilesToShow) {
                $message .= '...<br />';
            }
        }

        return GeneralUtility::makeInstance(ReportStatus::class, $this->getLanguageService()->sL('LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_missingFiles'), $value, $message, $severity);
    }

    protected function getConsistencyCheckStatus(): ReportStatus
    {
        // @todo for performance reasons, consider using this only in CLI context as `ExtendedStatusProviderInterface`

        $storages = array_filter(
            GeneralUtility::makeInstance(StorageRepository::class)->findAll(),
            static fn(ResourceStorage $storage): bool => $storage->isOnline()
        );
        $inconsistenciesMessage = '';
        foreach ($storages as $storage) {
            $inconsistencies = $this->checkFolderConsistency($storage->getRootLevelFolder());
            if ($inconsistencies !== []) {
                $inconsistenciesMessage .= sprintf(
                    '<h5>%s</h5>%s',
                    htmlspecialchars(sprintf(
                        'Storage "%s" (id:%d)',
                        $storage->getName(),
                        $storage->getUid()
                    )),
                    $this->wrapInHtmlUnorderedList($inconsistencies)
                );
            }
        }
        if ($inconsistenciesMessage === '') {
            return GeneralUtility::makeInstance(
                ReportStatus::class,
                'Consistency check',
                'No inconsistencies found in these storages',
                $this->wrapInHtmlUnorderedList(array_map(
                    static fn(ResourceStorage $storage): string => sprintf(
                        '%s (id: %d)',
                        $storage->getName(),
                        $storage->getUid()
                    ),
                    $storages,
                )),
                ContextualFeedbackSeverity::OK,
            );
        }
        return GeneralUtility::makeInstance(
            ReportStatus::class,
            'Consistency Status',
            'Inconsistent files have been found',
            $inconsistenciesMessage,
            ContextualFeedbackSeverity::ERROR,
        );
    }

    private function checkFolderConsistency(FolderInterface $folder): array
    {
        $inconsistencies = [];
        foreach ($folder->getFiles() as $file) {
            if (!$file instanceof File) {
                continue;
            }
            try {
                $this->resourceConsistencyService->validate($file->getStorage(), $file);
            } catch (ResultException $exception) {
                $inconsistencies[$file->getCombinedIdentifier()] = $this->compileResultMessages(
                    $exception->messages,
                    $this->getLanguageService()
                );
            }
        }
        foreach ($folder->getSubFolders() as $subFolder) {
            $inconsistencies = [...$inconsistencies, ...$this->checkFolderConsistency($subFolder)];
        }
        return $inconsistencies;
    }

    /**
     * @param list<string>|array<string, list<string>> $items
     */
    protected function wrapInHtmlUnorderedList(array $items): string
    {
        if (array_is_list($items)) {
            return sprintf(
                '<ul>%s</ul>',
                implode('', array_map(
                    static fn(string $item): string => '<li>' . htmlspecialchars($item) . '</li>',
                    $items
                ))
            );
        }
        return sprintf(
            '<ul>%s</ul>',
            implode('', array_map(
                fn(string $key, array $values): string => sprintf(
                    '<li>%s%s</li>',
                    htmlspecialchars($key),
                    $this->wrapInHtmlUnorderedList($values)
                ),
                array_keys($items),
                array_values($items)
            ))
        );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
