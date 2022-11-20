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
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for record localizations
 *
 * @internal
 */
class LocalizationRepository
{
    /**
     * @var TranslationConfigurationProvider
     */
    protected $translationConfigurationProvider;

    public function __construct(TranslationConfigurationProvider $translationConfigurationProvider = null)
    {
        $this->translationConfigurationProvider = $translationConfigurationProvider ?? GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
    }

    /**
     * Fetch the language from which the records in a certain language were initially localized
     */
    public function fetchOriginLanguage(int $pageId, int $localizedLanguage): array
    {
        $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');

        $queryBuilder->select('tt_content_orig.sys_language_uid')
            ->from('tt_content')
            ->join(
                'tt_content',
                'tt_content',
                'tt_content_orig',
                $queryBuilder->expr()->eq(
                    'tt_content.l10n_source',
                    $queryBuilder->quoteIdentifier('tt_content_orig.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'tt_content.pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tt_content.sys_language_uid',
                    $queryBuilder->createNamedParameter($localizedLanguage, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'tt_content_orig.sys_language_uid',
                    $queryBuilder->createNamedParameter(-1, Connection::PARAM_INT)
                )
            )
            ->groupBy('tt_content_orig.sys_language_uid');
        $this->getAllowedLanguageConstraintsForBackendUser($pageId, $queryBuilder, $this->getBackendUser(), 'tt_content_orig');

        return $queryBuilder->executeQuery()->fetchAssociative() ?: [];
    }

    /**
     * Returns number of localized records in given page and language
     * Records which were added to the language directly (not through translation) are not counted.
     */
    public function getLocalizedRecordCount(int $pageId, int $languageId): int
    {
        $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');

        $rowCount = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'l10n_source',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$rowCount;
    }

    /**
     * Fetch all available languages
     */
    public function fetchAvailableLanguages(int $pageId, int $languageId): array
    {
        $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');
        $queryBuilder->select('sys_language_uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                )
            )
            ->groupBy('sys_language_uid');

        $this->getAllowedLanguageConstraintsForBackendUser($pageId, $queryBuilder, $this->getBackendUser());
        $languages = $queryBuilder->executeQuery()->fetchAllAssociative();
        return $languages ?: [];
    }

    /**
     * Builds additional query constraints to exclude hidden languages and
     * limit a backend user to its allowed languages (unless the user is an admin)
     */
    protected function getAllowedLanguageConstraintsForBackendUser(int $pageId, QueryBuilder $queryBuilder, BackendUserAuthentication $backendUser, string $alias = ''): void
    {
        if ($backendUser->isAdmin()) {
            return;
        }
        // This always includes default language
        $allowedLanguages = $this->translationConfigurationProvider->getSystemLanguages($pageId);
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                ($alias === '' ? '' : ($alias . '.')) . 'sys_language_uid',
                $queryBuilder->createNamedParameter(array_keys($allowedLanguages), Connection::PARAM_INT_ARRAY)
            )
        );
    }

    /**
     * Get records for copy process
     *
     * @return Result
     */
    public function getRecordsToCopyDatabaseResult(int $pageId, int $destLanguageId, int $languageId, string $fields = '*')
    {
        $originalUids = [];

        // Get original uid of existing elements triggered language
        $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');

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

        $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields, true))
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

        if (!empty($originalUids)) {
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

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get a QueryBuilder for the given table with preconfigured restrictions
     * to not retrieve workspace placeholders or deleted records.
     */
    protected function getQueryBuilderWithWorkspaceRestriction(string $tableName): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        return $queryBuilder;
    }
}
