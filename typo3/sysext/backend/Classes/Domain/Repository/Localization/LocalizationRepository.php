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

namespace TYPO3\CMS\Backend\Domain\Repository\Localization;

use Doctrine\DBAL\Result;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Repository for having low-level logic fetching "record translation" dealing with
 * for the purpose of the TYPO3 Backend!
 *
 * A few rules for this class:
 * - It only returns translated records (having l10n_parent / l10n_source > 0)
 * - It only deals with RawRecord (not other Records) as we are usually interested in the raw values
 * - It has no dependency on $GLOBALS['BE_USER']
 */
#[Autoconfigure(public: true)]
class LocalizationRepository
{
    public function __construct(
        protected ?TcaSchemaFactory $tcaSchemaFactory,
        protected ?RecordFactory $recordFactory,
    ) {}

    /**
     * Get records for copy process
     */
    public function getRecordsToCopyDatabaseResult(int $pageId, int $destLanguageId, int $languageId, int $workspaceId = 0): Result
    {
        $originalUids = [];

        // Get original uid of existing elements triggered language
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction())
            ->add(new WorkspaceRestriction($workspaceId));

        $originalUidsStatement = $queryBuilder
            ->select('l10n_source')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($destLanguageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        while ($origUid = $originalUidsStatement->fetchOne()) {
            $originalUids[] = (int)$origUid;
        }

        $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                )
            )
            ->orderBy('sorting');

        if ($originalUids !== []) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->notIn(
                        'uid',
                        $queryBuilder->createNamedParameter($originalUids, Connection::PARAM_INT_ARRAY)
                    )
                );
        }

        return $queryBuilder->executeQuery();
    }

    /**
     * Fetches the translated version of a record.
     * It automatically applies workspace overlay and filters out DELETE_PLACEHOLDER records.
     *
     * @param string|TcaSchema $tableOrSchema The table name or TCA schema
     * @param int|array|RecordInterface $recordOrUid The record UID, or the full record (array or RecordInterface).
     *        When passing the full record, the pid is automatically used for filtering.
     * @param int|LanguageAspect $language The target language ID or LanguageAspect
     * @return RawRecord|null The translated record or null if not found
     */
    public function getRecordTranslation(
        string|TcaSchema $tableOrSchema,
        int|array|RecordInterface $recordOrUid,
        int|LanguageAspect $language,
        int $workspaceId = 0,
        bool $includeDeletedRecords = false,
    ): ?RawRecord {
        // Resolve table name and schema
        if ($tableOrSchema instanceof TcaSchema) {
            $table = $tableOrSchema->getName();
            $schema = $tableOrSchema;
        } else {
            $table = $tableOrSchema;
            if (!$this->tcaSchemaFactory->has($table)) {
                return null;
            }
            $schema = $this->tcaSchemaFactory->get($table);
        }

        if (!$schema->isLanguageAware()) {
            return null;
        }

        // Resolve uid and optional pid from record
        if ($recordOrUid instanceof RecordInterface) {
            $uid = $recordOrUid->getUid();
            $pid = $recordOrUid->getPid();
        } elseif (is_array($recordOrUid)) {
            $uid = (int)($recordOrUid['uid'] ?? 0);
            $pid = isset($recordOrUid['pid']) ? (int)$recordOrUid['pid'] : null;
        } else {
            $uid = $recordOrUid;
            $pid = null;
        }

        if ($uid === 0) {
            return null;
        }

        // Resolve language ID
        $languageId = $language instanceof LanguageAspect ? $language->getId() : $language;

        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        if (!$includeDeletedRecords) {
            $queryBuilder->getRestrictions()->add(new DeletedRestriction());
        }
        $queryBuilder->getRestrictions()->add(new WorkspaceRestriction($workspaceId));

        // Prefer translationSourceField (l10n_source) over transOrigPointerField (l10n_parent)
        $parentPointerField = $languageCapability->hasTranslationSourceField()
            ? $languageCapability->getTranslationSourceField()->getName()
            : $languageCapability->getTranslationOriginPointerField()->getName();

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentPointerField,
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $languageCapability->getLanguageField()->getName(),
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1);

        // When a full record is provided, automatically filter by pid
        if ($pid !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT))
            );
        }

        $row = $queryBuilder->executeQuery()->fetchAssociative();

        if ($row === false) {
            return null;
        }

        // Apply workspace overlay
        BackendUtility::workspaceOL($table, $row, $workspaceId);
        if (!is_array($row) || VersionState::tryFrom($row['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER) {
            return null;
        }

        return $this->recordFactory->createRawRecord($table, $row);
    }

    /**
     * Fetches all translations of a record.
     * It automatically applies workspace overlay and filters out DELETE_PLACEHOLDER records.
     *
     * @param string|TcaSchema $tableOrSchema The table name or TCA schema
     * @param int|array|RecordInterface $recordOrUid The record UID, or the full record (array or RecordInterface).
     *        When passing the full record, the pid is automatically used for filtering.
     * @param array<int> $limitToLanguageIds Optional list of language IDs to filter by
     * @return RawRecord[] Array of translated records indexed by language ID
     */
    public function getRecordTranslations(
        string|TcaSchema $tableOrSchema,
        int|array|RecordInterface $recordOrUid,
        array $limitToLanguageIds = [],
        int $workspaceId = 0,
        bool $includeDeletedRecords = false,
    ): array {
        // Resolve table name and schema
        if ($tableOrSchema instanceof TcaSchema) {
            $table = $tableOrSchema->getName();
            $schema = $tableOrSchema;
        } else {
            $table = $tableOrSchema;
            if (!$this->tcaSchemaFactory->has($table)) {
                return [];
            }
            $schema = $this->tcaSchemaFactory->get($table);
        }

        if (!$schema->isLanguageAware()) {
            return [];
        }

        // Resolve uid and optional pid from record
        if ($recordOrUid instanceof RecordInterface) {
            $uid = $recordOrUid->getUid();
            $pid = $recordOrUid->getPid();
        } elseif (is_array($recordOrUid)) {
            $uid = (int)($recordOrUid['uid'] ?? 0);
            $pid = isset($recordOrUid['pid']) ? (int)$recordOrUid['pid'] : null;
        } else {
            $uid = $recordOrUid;
            $pid = null;
        }

        if ($uid === 0) {
            return [];
        }

        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $languageFieldName = $languageCapability->getLanguageField()->getName();

        // Prefer translationSourceField (l10n_source) over transOrigPointerField (l10n_parent)
        $parentPointerField = $languageCapability->hasTranslationSourceField()
            ? $languageCapability->getTranslationSourceField()->getName()
            : $languageCapability->getTranslationOriginPointerField()->getName();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        if (!$includeDeletedRecords) {
            $queryBuilder->getRestrictions()->add(new DeletedRestriction());
        }
        $queryBuilder->getRestrictions()->add(new WorkspaceRestriction($workspaceId));

        $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentPointerField,
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    $languageFieldName,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            );

        // When a full record is provided, automatically filter by pid
        if ($pid !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT))
            );
        }

        if ($limitToLanguageIds !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $languageFieldName,
                    $queryBuilder->createNamedParameter($limitToLanguageIds, Connection::PARAM_INT_ARRAY)
                )
            );
        }

        $result = $queryBuilder->executeQuery();

        $records = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL($table, $row, $workspaceId);
            if (is_array($row) && VersionState::tryFrom($row['t3ver_state'] ?? 0) !== VersionState::DELETE_PLACEHOLDER) {
                $records[(int)$row[$languageFieldName]] = $this->recordFactory->createRawRecord($table, $row);
            }
        }

        return $records;
    }

    /**
     * Fetches all existing page translations for a given page.
     * It automatically applies workspace overlay and filters out DELETE_PLACEHOLDER records.
     *
     * @param int $pageUid The UID of the default language page
     * @param array<int> $limitToLanguageIds Optional list of language IDs to filter by
     * @return RawRecord[] Array of page translation records indexed by language ID
     */
    public function getPageTranslations(
        int $pageUid,
        array $limitToLanguageIds = [],
        int $workspaceId = 0,
        bool $includeDeletedRecords = false,
    ): array {
        if ($pageUid === 0) {
            return [];
        }

        if (!$this->tcaSchemaFactory->has('pages')) {
            return [];
        }

        $schema = $this->tcaSchemaFactory->get('pages');
        if (!$schema->isLanguageAware()) {
            return [];
        }

        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $languageFieldName = $languageCapability->getLanguageField()->getName();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        if (!$includeDeletedRecords) {
            $queryBuilder->getRestrictions()->add(new DeletedRestriction());
        }
        $queryBuilder->getRestrictions()->add(new WorkspaceRestriction($workspaceId));

        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $languageCapability->getTranslationOriginPointerField()->getName(),
                    $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)
                )
            );

        if ($limitToLanguageIds !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $languageFieldName,
                    $queryBuilder->createNamedParameter($limitToLanguageIds, Connection::PARAM_INT_ARRAY)
                )
            );
        }

        $result = $queryBuilder->executeQuery();

        $records = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('pages', $row, $workspaceId);
            if (is_array($row) && VersionState::tryFrom($row['t3ver_state'] ?? 0) !== VersionState::DELETE_PLACEHOLDER) {
                $records[(int)$row[$languageFieldName]] = $this->recordFactory->createRawRecord('pages', $row);
            }
        }

        return $records;
    }
}
