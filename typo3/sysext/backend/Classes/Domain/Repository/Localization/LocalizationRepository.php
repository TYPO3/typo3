<?php
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for record localizations
 */
class LocalizationRepository
{
    /**
     * Fetch the language from which the records of a colPos in a certain language were initially localized
     *
     * @param int $pageId
     * @param int $colPos
     * @param int $localizedLanguage
     * @return array|false
     */
    public function fetchOriginLanguage($pageId, $colPos, $localizedLanguage)
    {
        $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');

        $constraints = [
            $queryBuilder->expr()->eq(
                'tt_content.colPos',
                $queryBuilder->createNamedParameter($colPos, \PDO::PARAM_INT)
            ),
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
            ->join(
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

        return $queryBuilder->execute()->fetch();
    }

    /**
     * Returns number of localized records in given page, colPos and language
     * Records which were added to the language directly (not through translation) are not counted.
     *
     * @param int $pageId
     * @param int $colPos
     * @param int $languageId
     * @return int
     */
    public function getLocalizedRecordCount($pageId, $colPos, $languageId)
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
                    'tt_content.colPos',
                    $queryBuilder->createNamedParameter($colPos, \PDO::PARAM_INT)
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
     * @param int $colPos
     * @param int $languageId
     * @return array
     */
    public function fetchAvailableLanguages($pageId, $colPos, $languageId)
    {
        $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');

        $constraints = [
            $queryBuilder->expr()->eq(
                'tt_content.sys_language_uid',
                $queryBuilder->quoteIdentifier('sys_language.uid')
            ),
            $queryBuilder->expr()->eq(
                'tt_content.colPos',
                $queryBuilder->createNamedParameter($colPos, \PDO::PARAM_INT)
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
     * Builds an additional where clause to exclude deleted records and setting the versioning placeholders
     *
     * @return string
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getExcludeQueryPart()
    {
        GeneralUtility::logDeprecatedFunction();

        return BackendUtility::deleteClause('tt_content') . BackendUtility::versioningPlaceholderClause('tt_content');
    }

    /**
     * Builds an additional where clause to exclude hidden languages and limit a backend user to its allowed languages,
     * if the user is not an admin.
     *
     * @return string
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getAllowedLanguagesForBackendUser()
    {
        GeneralUtility::logDeprecatedFunction();

        $backendUser = $this->getBackendUser();
        $additionalWhere = '';
        if (!$backendUser->isAdmin()) {
            $additionalWhere .= ' AND sys_language.hidden=0';

            if (!empty($backendUser->user['allowed_languages'])) {
                $additionalWhere .= ' AND sys_language.uid IN(' . implode(',', GeneralUtility::intExplode(',', $backendUser->user['allowed_languages'])) . ')';
            }
        }

        return $additionalWhere;
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
     * @param int $colPos
     * @param int $destLanguageId
     * @param int $languageId
     * @param string $fields
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function getRecordsToCopyDatabaseResult($pageId, $colPos, $destLanguageId, $languageId, $fields = '*')
    {
        $originalUids = [];

        // Get original uid of existing elements triggered language / colpos
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
                    'tt_content.colPos',
                    $queryBuilder->createNamedParameter($colPos, \PDO::PARAM_INT)
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
                    'tt_content.colPos',
                    $queryBuilder->createNamedParameter($colPos, \PDO::PARAM_INT)
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
     * Fetches the localization for a given record.
     *
     * @FIXME: This method is a clone of BackendUtility::getRecordLocalization, using origUid instead of transOrigPointerField
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @param int $uid The uid of the record
     * @param int $language The uid of the language record in sys_language
     * @param string $andWhereClause Optional additional WHERE clause (default: '')
     * @return mixed Multidimensional array with selected records; if none exist, FALSE is returned
     *
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getRecordLocalization($table, $uid, $language, $andWhereClause = '')
    {
        GeneralUtility::logDeprecatedFunction();
        $recordLocalization = false;

        // Pages still stores translations in the pages_language_overlay table, all other tables store in themself
        if ($table === 'pages') {
            $table = 'pages_language_overlay';
        }

        if (BackendUtility::isTableLocalizable($table)) {
            $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

            if (isset($tcaCtrl['origUid'])) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($table);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                    ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

                $queryBuilder->select('*')
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->eq(
                            $tcaCtrl['origUid'],
                            $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            $tcaCtrl['languageField'],
                            $queryBuilder->createNamedParameter((int)$language, \PDO::PARAM_INT)
                        )
                    )
                    ->setMaxResults(1);

                if ($andWhereClause) {
                    $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($andWhereClause));
                }

                $recordLocalization = $queryBuilder->execute()->fetchAll();
            }
        }
        return $recordLocalization;
    }

    /**
     * Returning uid of previous localized record, if any, for tables with a "sortby" column
     * Used when new localized records are created so that localized records are sorted in the same order as the default language records
     *
     * @FIXME: This method is a clone of DataHandler::getPreviousLocalizedRecordUid which is protected there and uses
     * BackendUtility::getRecordLocalization which we also needed to clone in this class. Also, this method takes two
     * language arguments.
     *
     * @param string $table Table name
     * @param int $uid Uid of default language record
     * @param int $pid Pid of default language record
     * @param int $sourceLanguage Language of origin
     * @param int $destinationLanguage Language of localization
     * @return int uid of record after which the localized record should be inserted
     *
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function getPreviousLocalizedRecordUid($table, $uid, $pid, $sourceLanguage, $destinationLanguage)
    {
        GeneralUtility::logDeprecatedFunction();
        $previousLocalizedRecordUid = $uid;
        if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
            $sortRow = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
            $select = [$sortRow, 'pid', 'uid'];
            // For content elements, we also need the colPos
            if ($table === 'tt_content') {
                $select[] = 'colPos';
            }
            // Get the sort value of the default language record
            $row = BackendUtility::getRecord($table, $uid, implode(',', $select));
            if (is_array($row)) {
                $queryBuilder = $this->getQueryBuilderWithWorkspaceRestriction('tt_content');

                $queryBuilder->select(...$select)
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)),
                        $queryBuilder->expr()->eq(
                            'sys_language_uid',
                            $queryBuilder->createNamedParameter($sourceLanguage, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->lt(
                            $sortRow,
                            $queryBuilder->createNamedParameter($row[$sortRow], \PDO::PARAM_INT)
                        )
                    );

                // Respect the colPos for content elements
                if ($table === 'tt_content') {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->eq(
                            'colPos',
                            $queryBuilder->createNamedParameter($row['colPos'], \PDO::PARAM_INT)
                        )
                    );
                }

                $previousRow = $queryBuilder->orderBy($sortRow, 'DESC')->execute()->fetch();

                // If there is an element, find its localized record in specified localization language
                if ($previousRow !== false) {
                    $previousLocalizedRecord = $this->getRecordLocalization(
                        $table,
                        $previousRow['uid'],
                        $destinationLanguage
                    );
                    if (is_array($previousLocalizedRecord[0])) {
                        $previousLocalizedRecordUid = $previousLocalizedRecord[0]['uid'];
                    }
                }
            }
        }

        return $previousLocalizedRecordUid;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
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
