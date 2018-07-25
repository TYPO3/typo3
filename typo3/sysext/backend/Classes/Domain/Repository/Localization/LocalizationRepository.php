<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\Domain\Repository\Localization;

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

use Doctrine\DBAL\Driver\Statement;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for record localizations
 *
 * @internal
 */
class LocalizationRepository
{
    /**
     * Fetch the language from which the records in a certain language were initially localized
     *
     * @param int $pageId
     * @param int $localizedLanguage
     * @return array
     */
    public function fetchOriginLanguage(int $pageId, int $localizedLanguage): array
    {
        $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');

        $constraints = [
            $queryBuilder->expr()->eq(
                'tt_content.pid',
                $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
            ),
            $queryBuilder->expr()->eq(
                'tt_content.sys_language_uid',
                $queryBuilder->createNamedParameter($localizedLanguage, \PDO::PARAM_INT)
            ),
        ];
        $constraints += $this->getAllowedLanguageConstraintsForBackendUser();

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
            ->leftJoin(
                'tt_content_orig',
                'sys_language',
                'sys_language',
                $queryBuilder->expr()->eq(
                    'tt_content_orig.sys_language_uid',
                    $queryBuilder->quoteIdentifier('sys_language.uid')
                )
            )
            ->where(...$constraints)
            ->groupBy('tt_content_orig.sys_language_uid');

        return $queryBuilder->execute()->fetch() ?: [];
    }

    /**
     * Returns number of localized records in given page and language
     * Records which were added to the language directly (not through translation) are not counted.
     *
     * @param int $pageId
     * @param int $languageId
     * @return int
     */
    public function getLocalizedRecordCount(int $pageId, int $languageId): int
    {
        $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');

        $rowCount = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'tt_content.sys_language_uid',
                    $queryBuilder->createNamedParameter($languageId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tt_content.pid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'tt_content.l10n_source',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn(0);

        return (int)$rowCount;
    }

    /**
     * Fetch all available languages
     *
     * @param int $pageId
     * @param int $languageId
     * @return array
     */
    public function fetchAvailableLanguages(int $pageId, int $languageId): array
    {
        $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');

        $constraints = [
            $queryBuilder->expr()->eq(
                'tt_content.sys_language_uid',
                $queryBuilder->quoteIdentifier('sys_language.uid')
            ),
            $queryBuilder->expr()->eq(
                'tt_content.pid',
                $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
            ),
            $queryBuilder->expr()->neq(
                'sys_language.uid',
                $queryBuilder->createNamedParameter($languageId, \PDO::PARAM_INT)
            )
        ];
        $constraints += $this->getAllowedLanguageConstraintsForBackendUser();

        $queryBuilder->select('sys_language.uid')
            ->from('tt_content')
            ->from('sys_language')
            ->where(...$constraints)
            ->groupBy('sys_language.uid', 'sys_language.sorting')
            ->orderBy('sys_language.sorting');

        $result = $queryBuilder->execute()->fetchAll();

        return $result;
    }

    /**
     * Builds additional query constraints to exclude hidden languages and
     * limit a backend user to its allowed languages (unless the user is an admin)
     *
     * @return array
     */
    protected function getAllowedLanguageConstraintsForBackendUser(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $constraints = [];

        $backendUser = $this->getBackendUser();
        if (!$backendUser->isAdmin()) {
            if (!empty($GLOBALS['TCA']['sys_language']['ctrl']['enablecolumns']['disabled'])) {
                $constraints[] = $queryBuilder->expr()->eq(
                    'sys_language.' . $GLOBALS['TCA']['sys_language']['ctrl']['enablecolumns']['disabled'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                );
            }

            if (!empty($backendUser->user['allowed_languages'])) {
                $constraints[] = $queryBuilder->expr()->in(
                    'sys_language.uid',
                    $queryBuilder->createNamedParameter(
                        GeneralUtility::intExplode(',', $backendUser->user['allowed_languages'], true),
                        Connection::PARAM_INT_ARRAY
                    )
                );
            }
        }

        return $constraints;
    }

    /**
     * Get records for copy process
     *
     * @param int $pageId
     * @param int $destLanguageId
     * @param int $languageId
     * @param string $fields
     * @return Statement
     */
    public function getRecordsToCopyDatabaseResult(int $pageId, int $destLanguageId, int $languageId, string $fields = '*'): Statement
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
                    $queryBuilder->createNamedParameter($destLanguageId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tt_content.pid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->execute();

        while ($origUid = $originalUidsStatement->fetchColumn(0)) {
            $originalUids[] = (int)$origUid;
        }

        $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields, true))
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'tt_content.sys_language_uid',
                    $queryBuilder->createNamedParameter($languageId, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'tt_content.pid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->orderBy('tt_content.sorting');

        if (!empty($originalUids)) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->notIn(
                        'tt_content.uid',
                        $queryBuilder->createNamedParameter($originalUids, Connection::PARAM_INT_ARRAY)
                    )
                );
        }

        return $queryBuilder->execute();
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get a QueryBuilder for the given table with preconfigured restrictions
     * to not retrieve workspace placeholders or deleted records.
     *
     * @param string $tableName
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected function getQueryBuilderWithWorkspaceRestriction(string $tableName): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        return $queryBuilder;
    }
}
