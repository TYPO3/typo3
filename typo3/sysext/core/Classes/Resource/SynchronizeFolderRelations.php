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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Event\AfterFolderRenamedEvent;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Event listeners to synchronize folder relations after some
 * action like renaming or moving of a folder, took place.
 *
 * @internal
 */
class SynchronizeFolderRelations
{
    protected ConnectionPool $connectionPool;
    protected FlashMessageService $flashMessageService;

    public function __construct(ConnectionPool $connectionPool, FlashMessageService $flashMessageService)
    {
        $this->connectionPool = $connectionPool;
        $this->flashMessageService = $flashMessageService;
    }

    /**
     * Synchronize file collection relations after a folder was renamed
     *
     *
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function synchronizeFileCollectionsAfterRename(AfterFolderRenamedEvent $event): void
    {
        $sourceIdentifier = $event->getSourceFolder()->getCombinedIdentifier();
        $targetIdentifier = $event->getFolder()->getCombinedIdentifier();

        $synchronized = 0;
        $queryBuilder = $this->getPreparedQueryBuilder('sys_file_collection');
        $statement = $queryBuilder
            ->select('uid', 'folder_identifier')
            ->from('sys_file_collection')
            ->where(
                $queryBuilder->expr()->like('folder_identifier', $queryBuilder->quote($sourceIdentifier . '%')),
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter('folder'))
            )
            ->executeQuery();

        while ($row = $statement->fetchAssociative()) {
            $folder = preg_replace(sprintf('/^%s/', preg_quote($sourceIdentifier, '/')), $targetIdentifier, $row['folder_identifier']) ?? '';
            if ($folder !== '') {
                $queryBuilder = $this->getPreparedQueryBuilder('sys_file_collection');
                $synchronized += (int)$queryBuilder
                    ->update('sys_file_collection')
                    ->set('folder_identifier', $folder)
                    ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$row['uid'], Connection::PARAM_INT)))
                    ->executeStatement();
            }
        }

        if ($synchronized) {
            $this->addFlashMessage((int)$synchronized, 'sys_file_collection', 'afterFolderRenamed');
        }
    }

    /**
     * Synchronize file mount relations after a folder was renamed
     *
     *
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function synchronizeFilemountsAfterRename(AfterFolderRenamedEvent $event): void
    {
        $storageId = $event->getSourceFolder()->getStorage()->getUid();
        $sourceIdentifier = $event->getSourceFolder()->getIdentifier();
        $targetIdentifier = $event->getFolder()->getIdentifier();

        $synchronized = 0;
        $queryBuilder = $this->getPreparedQueryBuilder('sys_filemounts');
        $statement = $queryBuilder
            ->select('uid', 'identifier')
            ->from('sys_filemounts')
            ->where(
                $queryBuilder->expr()->like('identifier', $queryBuilder->quote($storageId . ':' . $sourceIdentifier . '%'))
            )
            ->executeQuery();

        while ($row = $statement->fetchAssociative()) {
            [$base, $path] = GeneralUtility::trimExplode(':', $row['identifier'], false, 2);
            $path = preg_replace(sprintf('/^%s/', preg_quote($sourceIdentifier, '/')), $targetIdentifier, $path) ?? '';
            if ($path !== '') {
                $queryBuilder = $this->getPreparedQueryBuilder('sys_filemounts');
                $synchronized += (int)$queryBuilder
                    ->update('sys_filemounts')
                    ->set('identifier', $base . ':' . $path)
                    ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$row['uid'], Connection::PARAM_INT)))
                    ->executeStatement();
            }
        }

        if ($synchronized) {
            $this->addFlashMessage((int)$synchronized, 'sys_filemounts', 'afterFolderRenamed');
        }
    }

    /**
     * Add a flash message for a successfully performed synchronization
     *
     * @param int $updatedRelationsCount The amount of relations synchronized
     * @param string $table The relation table the synchronization was performed on
     * @param string $event The event after which the synchronization was performed
     *
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function addFlashMessage(int $updatedRelationsCount, string $table, string $event): void
    {
        $languageService = $this->getLanguageServcie();
        $message = sprintf(
            $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:synchronizeFolderRelations.' . $event),
            $updatedRelationsCount,
            $languageService->sL($GLOBALS['TCA'][$table]['ctrl']['title']),
        );

        $this->flashMessageService
            ->getMessageQueueByIdentifier()
            ->enqueue(GeneralUtility::makeInstance(FlashMessage::class, $message, '', ContextualFeedbackSeverity::OK, true));
    }

    protected function getPreparedQueryBuilder(string $table): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }

    protected function getLanguageServcie(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
