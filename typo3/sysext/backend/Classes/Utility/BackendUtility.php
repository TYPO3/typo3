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

namespace TYPO3\CMS\Backend\Utility;

use Doctrine\DBAL\Statement;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Backend\Domain\Model\Element\ImmediateActionElement;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader;
use TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Routing\InvalidRouteArgumentsException;
use TYPO3\CMS\Core\Routing\RouterInterface;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Standard functions available for the TYPO3 backend.
 * You are encouraged to use this class in your own applications (Backend Modules)
 * Don't instantiate - call functions with "\TYPO3\CMS\Backend\Utility\BackendUtility::" prefixed the function name.
 *
 * Call ALL methods without making an object!
 * Eg. to get a page-record 51 do this: '\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages',51)'
 */
class BackendUtility
{
    /*******************************************
     *
     * SQL-related, selecting records, searching
     *
     *******************************************/
    /**
     * Gets record with uid = $uid from $table
     * You can set $field to a list of fields (default is '*')
     * Additional WHERE clauses can be added by $where (fx. ' AND some_field = 1')
     * Will automatically check if records has been deleted and if so, not return anything.
     * $table must be found in $GLOBALS['TCA']
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @param int|string $uid UID of record
     * @param string $fields List of fields to select
     * @param string $where Additional WHERE clause, eg. ' AND some_field = 0'
     * @param bool $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
     * @return array|null Returns the row if found, otherwise NULL
     */
    public static function getRecord($table, $uid, $fields = '*', $where = '', $useDeleteClause = true): ?array
    {
        // Ensure we have a valid uid (not 0 and not NEWxxxx) and a valid TCA
        if ((int)$uid && !empty($GLOBALS['TCA'][$table])) {
            $queryBuilder = static::getQueryBuilderForTable($table);

            // do not use enabled fields here
            $queryBuilder->getRestrictions()->removeAll();

            // should the delete clause be used
            if ($useDeleteClause) {
                $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            }

            // set table and where clause
            $queryBuilder
                ->select(...GeneralUtility::trimExplode(',', $fields, true))
                ->from($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$uid, \PDO::PARAM_INT)));

            // add custom where clause
            if ($where) {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($where));
            }

            $row = $queryBuilder->executeQuery()->fetchAssociative();
            if ($row) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Like getRecord(), but overlays workspace version if any.
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @param int $uid UID of record
     * @param string $fields List of fields to select
     * @param string $where Additional WHERE clause, eg. ' AND some_field = 0'
     * @param bool $useDeleteClause Use the deleteClause to check if a record is deleted (default TRUE)
     * @param bool $unsetMovePointers If TRUE the function does not return a "pointer" row for moved records in a workspace
     * @return array|null Returns the row if found, else NULL
     */
    public static function getRecordWSOL(
        $table,
        $uid,
        $fields = '*',
        $where = '',
        $useDeleteClause = true,
        $unsetMovePointers = false
    ): ?array {
        if ($fields !== '*') {
            $internalFields = StringUtility::uniqueList($fields . ',uid,pid');
            $row = self::getRecord($table, $uid, $internalFields, $where, $useDeleteClause);
            self::workspaceOL($table, $row, -99, $unsetMovePointers);
            if (is_array($row)) {
                foreach ($row as $key => $_) {
                    if (!GeneralUtility::inList($fields, $key) && $key[0] !== '_') {
                        unset($row[$key]);
                    }
                }
            }
        } else {
            $row = self::getRecord($table, $uid, $fields, $where, $useDeleteClause);
            self::workspaceOL($table, $row, -99, $unsetMovePointers);
        }
        return $row;
    }

    /**
     * Purges computed properties starting with underscore character ('_').
     *
     * @param array<string,mixed> $record
     * @return array<string,mixed>
     * @internal should only be used from within TYPO3 Core
     */
    public static function purgeComputedPropertiesFromRecord(array $record): array
    {
        return array_filter(
            $record,
            static function (string $propertyName): bool {
                return $propertyName[0] !== '_';
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Purges computed property names starting with underscore character ('_').
     *
     * @param array $propertyNames
     * @return array
     * @internal should only be used from within TYPO3 Core
     */
    public static function purgeComputedPropertyNames(array $propertyNames): array
    {
        return array_filter(
            $propertyNames,
            static function (string $propertyName): bool {
                return $propertyName[0] !== '_';
            }
        );
    }

    /**
     * Makes a backwards explode on the $str and returns an array with ($table, $uid).
     * Example: tt_content_45 => ['tt_content', 45]
     *
     * @param string $str [tablename]_[uid] string to explode
     * @return array
     * @internal should only be used from within TYPO3 Core
     */
    public static function splitTable_Uid($str)
    {
        $split = explode('_', strrev($str), 2);
        $uid = $split[0];
        $table = $split[1] ?? '';
        return [strrev($table), strrev($uid)];
    }

    /**
     * Backend implementation of enableFields()
     * Notice that "fe_groups" is not selected for - only disabled, starttime and endtime.
     * Notice that deleted-fields are NOT filtered - you must ALSO call deleteClause in addition.
     * $GLOBALS["SIM_ACCESS_TIME"] is used for date.
     *
     * @param string $table The table from which to return enableFields WHERE clause. Table name must have a 'ctrl' section in $GLOBALS['TCA'].
     * @param bool $inv Means that the query will select all records NOT VISIBLE records (inverted selection)
     * @return string WHERE clause part
     * @internal should only be used from within TYPO3 Core, but DefaultRestrictionHandler is recommended as alternative
     */
    public static function BEenableFields($table, $inv = false)
    {
        $ctrl = $GLOBALS['TCA'][$table]['ctrl'] ?? [];
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->getExpressionBuilder();
        $query = $expressionBuilder->andX();
        $invQuery = $expressionBuilder->orX();

        $ctrl += [
            'enablecolumns' => [],
        ];

        if (is_array($ctrl)) {
            if ($ctrl['enablecolumns']['disabled'] ?? false) {
                $field = $table . '.' . $ctrl['enablecolumns']['disabled'];
                $query->add($expressionBuilder->eq($field, 0));
                $invQuery->add($expressionBuilder->neq($field, 0));
            }
            if ($ctrl['enablecolumns']['starttime'] ?? false) {
                $field = $table . '.' . $ctrl['enablecolumns']['starttime'];
                $query->add($expressionBuilder->lte($field, (int)$GLOBALS['SIM_ACCESS_TIME']));
                $invQuery->add(
                    $expressionBuilder->andX(
                        $expressionBuilder->neq($field, 0),
                        $expressionBuilder->gt($field, (int)$GLOBALS['SIM_ACCESS_TIME'])
                    )
                );
            }
            if ($ctrl['enablecolumns']['endtime'] ?? false) {
                $field = $table . '.' . $ctrl['enablecolumns']['endtime'];
                $query->add(
                    $expressionBuilder->orX(
                        $expressionBuilder->eq($field, 0),
                        $expressionBuilder->gt($field, (int)$GLOBALS['SIM_ACCESS_TIME'])
                    )
                );
                $invQuery->add(
                    $expressionBuilder->andX(
                        $expressionBuilder->neq($field, 0),
                        $expressionBuilder->lte($field, (int)$GLOBALS['SIM_ACCESS_TIME'])
                    )
                );
            }
        }

        if ($query->count() === 0) {
            return '';
        }

        return ' AND ' . ($inv ? $invQuery : $query);
    }

    /**
     * Fetches the localization for a given record.
     *
     * @param string $table Table name present in $GLOBALS['TCA']
     * @param int $uid The uid of the record
     * @param int $language The uid of the language record in sys_language
     * @param string $andWhereClause Optional additional WHERE clause (default: '')
     * @return mixed Multidimensional array with selected records, empty array if none exists and FALSE if table is not localizable
     */
    public static function getRecordLocalization($table, $uid, $language, $andWhereClause = '')
    {
        $recordLocalization = false;

        if (self::isTableLocalizable($table)) {
            $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, static::getBackendUserAuthentication()->workspace ?? 0));

            $queryBuilder->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $tcaCtrl['translationSource'] ?? $tcaCtrl['transOrigPointerField'],
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

            $recordLocalization = $queryBuilder->executeQuery()->fetchAllAssociative();
        }

        return $recordLocalization;
    }

    /*******************************************
     *
     * Page tree, TCA related
     *
     *******************************************/
    /**
     * Returns what is called the 'RootLine'. That is an array with information about the page records from a page id
     * ($uid) and back to the root.
     * By default deleted pages are filtered.
     * This RootLine will follow the tree all the way to the root. This is opposite to another kind of root line known
     * from the frontend where the rootline stops when a root-template is found.
     *
     * @param int $uid Page id for which to create the root line.
     * @param string $clause Clause can be used to select other criteria. It would typically be where-clauses that
     *          stops the process if we meet a page, the user has no reading access to.
     * @param bool $workspaceOL If TRUE, version overlay is applied. This must be requested specifically because it is
     *          usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
     * @param string[] $additionalFields Additional Fields to select for rootline records
     * @return array Root line array, all the way to the page tree root uid=0 (or as far as $clause allows!), including the page given as $uid
     */
    public static function BEgetRootLine($uid, $clause = '', $workspaceOL = false, array $additionalFields = [])
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $beGetRootLineCache = $runtimeCache->get('backendUtilityBeGetRootLine') ?: [];
        $output = [];
        $pid = $uid;
        $ident = $pid . '-' . $clause . '-' . $workspaceOL . ($additionalFields ? '-' . md5(implode(',', $additionalFields)) : '');
        if (is_array($beGetRootLineCache[$ident] ?? false)) {
            $output = $beGetRootLineCache[$ident];
        } else {
            $loopCheck = 100;
            $theRowArray = [];
            while ($uid != 0 && $loopCheck) {
                $loopCheck--;
                $row = self::getPageForRootline($uid, $clause, $workspaceOL, $additionalFields);
                if (is_array($row)) {
                    $uid = $row['pid'];
                    $theRowArray[] = $row;
                } else {
                    break;
                }
            }
            $fields = [
                'uid',
                'pid',
                'title',
                'doktype',
                'slug',
                'tsconfig_includes',
                'TSconfig',
                'is_siteroot',
                't3ver_oid',
                't3ver_wsid',
                't3ver_state',
                't3ver_stage',
                'backend_layout_next_level',
                'hidden',
                'starttime',
                'endtime',
                'fe_group',
                'nav_hide',
                'content_from_pid',
                'module',
                'extendToSubpages',
            ];
            $fields = array_merge($fields, $additionalFields);
            $rootPage = array_fill_keys($fields, null);
            if ($uid == 0) {
                $rootPage['uid'] = 0;
                $theRowArray[] = $rootPage;
            }
            $c = count($theRowArray);
            foreach ($theRowArray as $val) {
                $c--;
                $output[$c] = array_intersect_key($val, $rootPage);
                if (isset($val['_ORIG_pid'])) {
                    $output[$c]['_ORIG_pid'] = $val['_ORIG_pid'];
                }
            }
            $beGetRootLineCache[$ident] = $output;
            $runtimeCache->set('backendUtilityBeGetRootLine', $beGetRootLineCache);
        }
        return $output;
    }

    /**
     * Gets the cached page record for the rootline
     *
     * @param int $uid Page id for which to create the root line.
     * @param string $clause Clause can be used to select other criteria. It would typically be where-clauses that stops the process if we meet a page, the user has no reading access to.
     * @param bool $workspaceOL If TRUE, version overlay is applied. This must be requested specifically because it is usually only wanted when the rootline is used for visual output while for permission checking you want the raw thing!
     * @param string[] $additionalFields AdditionalFields to fetch from the root line
     * @return array Cached page record for the rootline
     * @see BEgetRootLine
     */
    protected static function getPageForRootline($uid, $clause, $workspaceOL, array $additionalFields = [])
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $pageForRootlineCache = $runtimeCache->get('backendUtilityPageForRootLine') ?: [];
        $statementCacheIdent = md5($clause . ($additionalFields ? '-' . implode(',', $additionalFields) : ''));
        $ident = $uid . '-' . $workspaceOL . '-' . $statementCacheIdent;
        if (is_array($pageForRootlineCache[$ident] ?? false)) {
            $row = $pageForRootlineCache[$ident];
        } else {
            /** @var Statement $statement */
            $statement = $runtimeCache->get('getPageForRootlineStatement-' . $statementCacheIdent);
            if (!$statement) {
                $queryBuilder = static::getQueryBuilderForTable('pages');
                $queryBuilder->getRestrictions()
                             ->removeAll()
                             ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

                $queryBuilder
                    ->select(
                        'pid',
                        'uid',
                        'title',
                        'doktype',
                        'slug',
                        'tsconfig_includes',
                        'TSconfig',
                        'is_siteroot',
                        't3ver_oid',
                        't3ver_wsid',
                        't3ver_state',
                        't3ver_stage',
                        'backend_layout_next_level',
                        'hidden',
                        'starttime',
                        'endtime',
                        'fe_group',
                        'nav_hide',
                        'content_from_pid',
                        'module',
                        'extendToSubpages',
                        ...$additionalFields
                    )
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createPositionalParameter($uid, \PDO::PARAM_INT)),
                        QueryHelper::stripLogicalOperatorPrefix($clause)
                    );
                $statement = $queryBuilder->prepare();
                $runtimeCache->set('getPageForRootlineStatement-' . $statementCacheIdent, $statement);
            }
            $row = $statement->fetchAssociative();
            $statement->free();

            $statement->bindValue(1, (int)$uid, \PDO::PARAM_INT);
            $result = $statement->executeQuery();
            $row = $result->fetchAssociative();
            $result->free();

            if ($row) {
                if ($workspaceOL) {
                    self::workspaceOL('pages', $row);
                }
                if (is_array($row)) {
                    $pageForRootlineCache[$ident] = $row;
                    $runtimeCache->set('backendUtilityPageForRootLine', $pageForRootlineCache);
                }
            }
        }
        return $row;
    }

    /**
     * Opens the page tree to the specified page id
     *
     * @param int $pid Page id.
     * @param bool $clearExpansion If set, then other open branches are closed.
     * @internal should only be used from within TYPO3 Core
     */
    public static function openPageTree($pid, $clearExpansion)
    {
        $beUser = static::getBackendUserAuthentication();
        // Get current expansion data:
        if ($clearExpansion) {
            $expandedPages = [];
        } else {
            $expandedPages = $beUser->uc['BackendComponents']['States']['Pagetree']['stateHash'] ?? [];
        }
        // Get rootline:
        $rL = self::BEgetRootLine($pid);
        // First, find out what mount index to use (if more than one DB mount exists):
        $mountIndex = 0;
        $mountKeys = $beUser->returnWebmounts();

        foreach ($rL as $rLDat) {
            if (isset($mountKeys[$rLDat['uid']])) {
                $mountIndex = $mountKeys[$rLDat['uid']];
                break;
            }
        }
        // Traverse rootline and open paths:
        foreach ($rL as $rLDat) {
            $expandedPages[$mountIndex . '_' . $rLDat['uid']] = '1';
        }
        // Write back:
        $beUser->uc['BackendComponents']['States']['Pagetree']['stateHash'] = $expandedPages;
        $beUser->writeUC();
    }

    /**
     * Returns the path (visually) of a page $uid, fx. "/First page/Second page/Another subpage"
     * Each part of the path will be limited to $titleLimit characters
     * Deleted pages are filtered out.
     *
     * @param int $uid Page uid for which to create record path
     * @param string $clause Clause is additional where clauses, eg.
     * @param int $titleLimit Title limit
     * @param int $fullTitleLimit Title limit of Full title (typ. set to 1000 or so)
     * @return mixed Path of record (string) OR array with short/long title if $fullTitleLimit is set.
     */
    public static function getRecordPath($uid, $clause, $titleLimit, $fullTitleLimit = 0)
    {
        if (!$titleLimit) {
            $titleLimit = 1000;
        }
        $output = $fullOutput = '/';
        $clause = trim($clause);
        if ($clause !== '' && strpos($clause, 'AND') !== 0) {
            $clause = 'AND ' . $clause;
        }
        $data = self::BEgetRootLine($uid, $clause, true);
        foreach ($data as $record) {
            if ($record['uid'] === 0) {
                continue;
            }
            $output = '/' . GeneralUtility::fixed_lgd_cs(strip_tags($record['title']), $titleLimit) . $output;
            if ($fullTitleLimit) {
                $fullOutput = '/' . GeneralUtility::fixed_lgd_cs(strip_tags($record['title']), $fullTitleLimit) . $fullOutput;
            }
        }
        if ($fullTitleLimit) {
            return [$output, $fullOutput];
        }
        return $output;
    }

    /**
     * Determines whether a table is localizable and has the languageField and transOrigPointerField set in $GLOBALS['TCA'].
     *
     * @param string $table The table to check
     * @return bool Whether a table is localizable
     */
    public static function isTableLocalizable($table)
    {
        $isLocalizable = false;
        if (isset($GLOBALS['TCA'][$table]['ctrl']) && is_array($GLOBALS['TCA'][$table]['ctrl'])) {
            $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
            $isLocalizable = isset($tcaCtrl['languageField']) && $tcaCtrl['languageField'] && isset($tcaCtrl['transOrigPointerField']) && $tcaCtrl['transOrigPointerField'];
        }
        return $isLocalizable;
    }

    /**
     * Returns a page record (of page with $id) with an extra field "_thePath" set to the record path IF the WHERE clause, $perms_clause, selects the record. Thus is works as an access check that returns a page record if access was granted, otherwise not.
     * If $id is zero a pseudo root-page with "_thePath" set is returned IF the current BE_USER is admin.
     * In any case ->isInWebMount must return TRUE for the user (regardless of $perms_clause)
     *
     * @param int $id Page uid for which to check read-access
     * @param string $perms_clause This is typically a value generated with static::getBackendUserAuthentication()->getPagePermsClause(1);
     * @return array|false Returns page record if OK, otherwise FALSE.
     */
    public static function readPageAccess($id, $perms_clause)
    {
        if ((string)$id !== '') {
            $id = (int)$id;
            if (!$id) {
                if (static::getBackendUserAuthentication()->isAdmin()) {
                    return ['_thePath' => '/'];
                }
            } else {
                $pageinfo = self::getRecord('pages', $id, '*', $perms_clause);
                if (($pageinfo['uid'] ?? false) && static::getBackendUserAuthentication()->isInWebMount($pageinfo, $perms_clause)) {
                    self::workspaceOL('pages', $pageinfo);
                    if (is_array($pageinfo)) {
                        [$pageinfo['_thePath'], $pageinfo['_thePathFull']] = self::getRecordPath((int)$pageinfo['uid'], $perms_clause, 15, 1000);
                        return $pageinfo;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Returns the "type" value of $rec from $table which can be used to look up the correct "types" rendering section in $GLOBALS['TCA']
     * If no "type" field is configured in the "ctrl"-section of the $GLOBALS['TCA'] for the table, zero is used.
     * If zero is not an index in the "types" section of $GLOBALS['TCA'] for the table, then the $fieldValue returned will default to 1 (no matter if that is an index or not)
     *
     * Note: This method is very similar to the type determination of FormDataProvider/DatabaseRecordTypeValue,
     * however, it has two differences:
     * 1) The method in TCEForms also takes care of localization (which is difficult to do here as the whole infrastructure for language overlays is only in TCEforms).
     * 2) The $row array looks different in TCEForms, as in there it's not the raw record but the prepared data from other providers is handled, which changes e.g. how "select"
     * and "group" field values are stored, which makes different processing of the "foreign pointer field" type field variant necessary.
     *
     * @param string $table Table name present in TCA
     * @param array $row Record from $table
     * @throws \RuntimeException
     * @return string Field value
     */
    public static function getTCAtypeValue($table, $row)
    {
        $typeNum = 0;
        if ($GLOBALS['TCA'][$table] ?? false) {
            $field = $GLOBALS['TCA'][$table]['ctrl']['type'] ?? '';
            if (str_contains($field, ':')) {
                [$pointerField, $foreignTableTypeField] = explode(':', $field);
                // Check if the record has been persisted already
                $foreignUid = 0;
                if (isset($row['uid'])) {
                    // Get field value from database if field is not in the $row array
                    if (!isset($row[$pointerField])) {
                        $localRow = self::getRecord($table, $row['uid'], $pointerField);
                        $foreignUid = $localRow[$pointerField];
                    } else {
                        $foreignUid = $row[$pointerField];
                    }
                }
                if ($foreignUid) {
                    $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$pointerField]['config'];
                    $relationType = $fieldConfig['type'];
                    if ($relationType === 'select' || $relationType === 'category') {
                        $foreignTable = $fieldConfig['foreign_table'];
                    } elseif ($relationType === 'group') {
                        $allowedTables = explode(',', $fieldConfig['allowed']);
                        $foreignTable = $allowedTables[0];
                    } else {
                        throw new \RuntimeException(
                            'TCA foreign field pointer fields are only allowed to be used with group or select field types.',
                            1325862240
                        );
                    }
                    $foreignRow = self::getRecord($foreignTable, $foreignUid, $foreignTableTypeField);
                    if ($foreignRow[$foreignTableTypeField] ?? false) {
                        $typeNum = $foreignRow[$foreignTableTypeField];
                    }
                }
            } else {
                $typeNum = $row[$field] ?? 0;
            }
            // If that value is an empty string, set it to "0" (zero)
            if (empty($typeNum)) {
                $typeNum = 0;
            }
        }
        // If current typeNum doesn't exist, set it to 0 (or to 1 for historical reasons, if 0 doesn't exist)
        if (!isset($GLOBALS['TCA'][$table]['types'][$typeNum]) || !$GLOBALS['TCA'][$table]['types'][$typeNum]) {
            $typeNum = isset($GLOBALS['TCA'][$table]['types']['0']) ? 0 : 1;
        }
        // Force to string. Necessary for eg '-1' to be recognized as a type value.
        $typeNum = (string)$typeNum;
        return $typeNum;
    }

    /*******************************************
     *
     * TypoScript related
     *
     *******************************************/
    /**
     * Returns the Page TSconfig for page with id, $id
     *
     * @param int $id Page uid for which to create Page TSconfig
     * @return array Page TSconfig
     * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
     */
    public static function getPagesTSconfig($id)
    {
        $id = (int)$id;

        $cache = self::getRuntimeCache();
        $pagesTsConfigIdToHash = $cache->get('pagesTsConfigIdToHash' . $id);
        if ($pagesTsConfigIdToHash !== false) {
            return $cache->get('pagesTsConfigHashToContent' . $pagesTsConfigIdToHash);
        }

        $rootLine = self::BEgetRootLine($id, '', true);
        // Order correctly
        ksort($rootLine);

        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($id);
        } catch (SiteNotFoundException $exception) {
            $site = null;
        }

        // Load PageTS from all pages of the rootLine
        $pageTs = GeneralUtility::makeInstance(PageTsConfigLoader::class)->load($rootLine);

        // Parse the PageTS into an array, also applying conditions
        $parser = GeneralUtility::makeInstance(
            PageTsConfigParser::class,
            GeneralUtility::makeInstance(TypoScriptParser::class),
            GeneralUtility::makeInstance(CacheManager::class)->getCache('hash')
        );
        $matcher = GeneralUtility::makeInstance(ConditionMatcher::class, null, $id, $rootLine);
        $tsConfig = $parser->parse($pageTs, $matcher, $site);
        $cacheHash = md5((string)json_encode($tsConfig));

        // Get User TSconfig overlay, if no backend user is logged-in, this needs to be checked as well
        if (static::getBackendUserAuthentication()) {
            $userTSconfig = static::getBackendUserAuthentication()->getTSConfig() ?? [];
        } else {
            $userTSconfig = [];
        }

        if (is_array($userTSconfig['page.'] ?? null)) {
            // Override page TSconfig with user TSconfig
            ArrayUtility::mergeRecursiveWithOverrule($tsConfig, $userTSconfig['page.']);
            $cacheHash .= '_user' . static::getBackendUserAuthentication()->user['uid'];
        }

        // Many pages end up with the same ts config. To reduce memory usage, the cache
        // entries are a linked list: One or more pids point to content hashes which then
        // contain the cached content.
        $cache->set('pagesTsConfigHashToContent' . $cacheHash, $tsConfig, ['pagesTsConfig']);
        $cache->set('pagesTsConfigIdToHash' . $id, $cacheHash, ['pagesTsConfig']);

        return $tsConfig;
    }

    /*******************************************
     *
     * Users / Groups related
     *
     *******************************************/
    /**
     * Returns an array with be_users records of all user NOT DELETED sorted by their username
     * Keys in the array is the be_users uid
     *
     * @param string $fields Optional $fields list (default: username,usergroup,uid) can be used to set the selected fields
     * @param string $where Optional $where clause (fx. "AND username='pete'") can be used to limit query
     * @return array
     * @internal should only be used from within TYPO3 Core, use a direct SQL query instead to ensure proper DBAL where statements
     */
    public static function getUserNames($fields = 'username,usergroup,uid', $where = '')
    {
        return self::getRecordsSortedByTitle(
            GeneralUtility::trimExplode(',', $fields, true),
            'be_users',
            'username',
            'AND pid=0 ' . $where
        );
    }

    /**
     * Returns an array with be_groups records (title, uid) of all groups NOT DELETED sorted by their title
     *
     * @param string $fields Field list
     * @param string $where WHERE clause
     * @return array
     * @internal should only be used from within TYPO3 Core, use a direct SQL query instead to ensure proper DBAL where statements
     */
    public static function getGroupNames($fields = 'title,uid', $where = '')
    {
        return self::getRecordsSortedByTitle(
            GeneralUtility::trimExplode(',', $fields, true),
            'be_groups',
            'title',
            'AND pid=0 ' . $where
        );
    }

    /**
     * Returns an array of all non-deleted records of a table sorted by a given title field.
     * The value of the title field will be replaced by the return value
     * of self::getRecordTitle() before the sorting is performed.
     *
     * @param array $fields Fields to select
     * @param string $table Table name
     * @param string $titleField Field that will contain the record title
     * @param string $where Additional where clause
     * @return array Array of sorted records
     */
    protected static function getRecordsSortedByTitle(array $fields, $table, $titleField, $where = '')
    {
        $fieldsIndex = array_flip($fields);
        // Make sure the titleField is amongst the fields when getting sorted
        $fieldsIndex[$titleField] = 1;

        $result = [];

        $queryBuilder = static::getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $res = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(QueryHelper::stripLogicalOperatorPrefix($where))
            ->executeQuery();

        while ($record = $res->fetchAssociative()) {
            // store the uid, because it might be unset if it's not among the requested $fields
            $recordId = $record['uid'];
            $record[$titleField] = self::getRecordTitle($table, $record);

            // include only the requested fields in the result
            $result[$recordId] = array_intersect_key($record, $fieldsIndex);
        }

        // sort records by $sortField. This is not done in the query because the title might have been overwritten by
        // self::getRecordTitle();
        return ArrayUtility::sortArraysByKey($result, $titleField);
    }

    /*******************************************
     *
     * Output related
     *
     *******************************************/
    /**
     * Returns the difference in days between input $tstamp and $EXEC_TIME
     *
     * @param int $tstamp Time stamp, seconds
     * @return int
     */
    public static function daysUntil($tstamp)
    {
        $delta_t = $tstamp - $GLOBALS['EXEC_TIME'];
        return ceil($delta_t / (3600 * 24));
    }

    /**
     * Returns $tstamp formatted as "ddmmyy" (According to $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'])
     *
     * @param int $tstamp Time stamp, seconds
     * @return string Formatted time
     */
    public static function date($tstamp)
    {
        return date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], (int)$tstamp);
    }

    /**
     * Returns $tstamp formatted as "ddmmyy hhmm" (According to $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] AND $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'])
     *
     * @param int $value Time stamp, seconds
     * @return string Formatted time
     */
    public static function datetime($value)
    {
        return date(
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
            $value
        );
    }

    /**
     * Returns $value (in seconds) formatted as hh:mm:ss
     * For instance $value = 3600 + 60*2 + 3 should return "01:02:03"
     *
     * @param int $value Time stamp, seconds
     * @param bool $withSeconds Output hh:mm:ss. If FALSE: hh:mm
     * @return string Formatted time
     */
    public static function time($value, $withSeconds = true)
    {
        return gmdate('H:i' . ($withSeconds ? ':s' : ''), (int)$value);
    }

    /**
     * Returns the "age" in minutes / hours / days / years of the number of $seconds inputted.
     *
     * @param int $seconds Seconds could be the difference of a certain timestamp and time()
     * @param string $labels Labels should be something like ' min| hrs| days| yrs| min| hour| day| year'. This value is typically delivered by this function call: $GLOBALS["LANG"]->sL("LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears")
     * @return string Formatted time
     */
    public static function calcAge($seconds, $labels = 'min|hrs|days|yrs|min|hour|day|year')
    {
        $labelArr = GeneralUtility::trimExplode('|', $labels, true);
        $absSeconds = abs($seconds);
        $sign = $seconds < 0 ? -1 : 1;
        if ($absSeconds < 3600) {
            $val = round($absSeconds / 60);
            $seconds = $sign * $val . ' ' . ($val == 1 ? $labelArr[4] : $labelArr[0]);
        } elseif ($absSeconds < 24 * 3600) {
            $val = round($absSeconds / 3600);
            $seconds = $sign * $val . ' ' . ($val == 1 ? $labelArr[5] : $labelArr[1]);
        } elseif ($absSeconds < 365 * 24 * 3600) {
            $val = round($absSeconds / (24 * 3600));
            $seconds = $sign * $val . ' ' . ($val == 1 ? $labelArr[6] : $labelArr[2]);
        } else {
            $val = round($absSeconds / (365 * 24 * 3600));
            $seconds = $sign * $val . ' ' . ($val == 1 ? $labelArr[7] : $labelArr[3]);
        }
        return $seconds;
    }

    /**
     * Returns a formatted timestamp if $tstamp is set.
     * The date/datetime will be followed by the age in parenthesis.
     *
     * @param int $tstamp Time stamp, seconds
     * @param int $prefix 1/-1 depending on polarity of age.
     * @param string $date $date=="date" will yield "dd:mm:yy" formatting, otherwise "dd:mm:yy hh:mm
     * @return string
     */
    public static function dateTimeAge($tstamp, $prefix = 1, $date = '')
    {
        if (!$tstamp) {
            return '';
        }
        $label = static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears');
        $age = ' (' . self::calcAge($prefix * ($GLOBALS['EXEC_TIME'] - $tstamp), $label) . ')';
        return ($date === 'date' ? self::date($tstamp) : self::datetime($tstamp)) . $age;
    }

    /**
     * Resolves file references for a given record.
     *
     * @param string $tableName Name of the table of the record
     * @param string $fieldName Name of the field of the record
     * @param array $element Record data
     * @param int|null $workspaceId Workspace to fetch data for
     * @return \TYPO3\CMS\Core\Resource\FileReference[]|null
     */
    public static function resolveFileReferences($tableName, $fieldName, $element, $workspaceId = null)
    {
        if (empty($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'])) {
            return null;
        }
        $configuration = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
        if (empty($configuration['type']) || $configuration['type'] !== 'inline'
            || empty($configuration['foreign_table']) || $configuration['foreign_table'] !== 'sys_file_reference'
        ) {
            return null;
        }

        $fileReferences = [];
        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        if ($workspaceId !== null) {
            $relationHandler->setWorkspaceId($workspaceId);
        }
        $relationHandler->start(
            $element[$fieldName],
            $configuration['foreign_table'],
            $configuration['MM'] ?? '',
            $element['uid'],
            $tableName,
            $configuration
        );
        $relationHandler->processDeletePlaceholder();
        $referenceUids = $relationHandler->tableArray[$configuration['foreign_table']];

        foreach ($referenceUids as $referenceUid) {
            try {
                $fileReference = GeneralUtility::makeInstance(ResourceFactory::class)->getFileReferenceObject(
                    $referenceUid,
                    [],
                    $workspaceId === 0
                );
                $fileReferences[$fileReference->getUid()] = $fileReference;
            } catch (FileDoesNotExistException $e) {
                /**
                 * We just catch the exception here
                 * Reasoning: There is nothing an editor or even admin could do
                 */
            } catch (\InvalidArgumentException $e) {
                /**
                 * The storage does not exist anymore
                 * Log the exception message for admins as they maybe can restore the storage
                 */
                self::getLogger()->error($e->getMessage(), [
                    'table' => $tableName,
                    'fieldName' => $fieldName,
                    'referenceUid' => $referenceUid,
                    'exception' => $e,
                ]);
            }
        }

        return $fileReferences;
    }

    /**
     * Returns a linked image-tag for thumbnail(s)/fileicons/truetype-font-previews from a database row with sys_file_references
     * All $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] extension are made to thumbnails + ttf file (renders font-example)
     * Thumbnails are linked to ShowItemController (/thumbnails route)
     *
     * @param array $row Row is the database row from the table, $table.
     * @param string $table Table name for $row (present in TCA)
     * @param string $field Field is pointing to the connecting field of sys_file_references
     * @param string $backPath Back path prefix for image tag src="" field
     * @param string $thumbScript UNUSED since FAL
     * @param string $uploaddir UNUSED since FAL
     * @param int $abs UNUSED
     * @param string $tparams Optional: $tparams is additional attributes for the image tags
     * @param int|string $size Optional: $size is [w]x[h] of the thumbnail. 64 is default.
     * @param bool $linkInfoPopup Whether to wrap with a link opening the info popup
     * @return string Thumbnail image tag.
     */
    public static function thumbCode(
        $row,
        $table,
        $field,
        $backPath = '',
        $thumbScript = '',
        $uploaddir = null,
        $abs = 0,
        $tparams = '',
        $size = '',
        $linkInfoPopup = true
    ) {
        $size = (int)(trim((string)$size) ?: 64);
        $targetDimension = new ImageDimension($size, $size);
        $thumbData = '';
        $fileReferences = static::resolveFileReferences($table, $field, $row);
        // FAL references
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if ($fileReferences !== null) {
            foreach ($fileReferences as $fileReferenceObject) {
                // Do not show previews of hidden references
                if ($fileReferenceObject->getProperty('hidden')) {
                    continue;
                }
                $fileObject = $fileReferenceObject->getOriginalFile();

                if ($fileObject->isMissing()) {
                    $thumbData .= '<span class="label label-danger">'
                        . htmlspecialchars(
                            static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing')
                        )
                        . '</span>&nbsp;' . htmlspecialchars($fileObject->getName()) . '<br />';
                    continue;
                }

                // Preview web image or media elements
                if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails']
                    && $fileReferenceObject->getOriginalFile()->isImage()
                ) {
                    $cropVariantCollection = CropVariantCollection::create((string)$fileReferenceObject->getProperty('crop'));
                    $cropArea = $cropVariantCollection->getCropArea();
                    $taskType = ProcessedFile::CONTEXT_IMAGEPREVIEW;
                    $processingConfiguration = [
                        'width' => $targetDimension->getWidth(),
                        'height' => $targetDimension->getHeight(),
                    ];
                    if (!$cropArea->isEmpty()) {
                        $taskType = ProcessedFile::CONTEXT_IMAGECROPSCALEMASK;
                        $processingConfiguration = [
                            'maxWidth' => $targetDimension->getWidth(),
                            'maxHeight' => $targetDimension->getHeight(),
                            'crop' => $cropArea->makeAbsoluteBasedOnFile($fileReferenceObject),
                        ];
                    }
                    $processedImage = $fileObject->process($taskType, $processingConfiguration);
                    $attributes = [
                        'src' => $processedImage->getPublicUrl() ?? '',
                        'width' => $processedImage->getProperty('width'),
                        'height' => $processedImage->getProperty('height'),
                        'alt' => $fileReferenceObject->getName(),
                    ];
                    $imgTag = '<img ' . GeneralUtility::implodeAttributes($attributes, true) . $tparams . '/>';
                } else {
                    // Icon
                    $imgTag = '<span title="' . htmlspecialchars($fileObject->getName()) . '">'
                        . $iconFactory->getIconForResource($fileObject, Icon::SIZE_SMALL)->render()
                        . '</span>';
                }
                if ($linkInfoPopup) {
                    // relies on module 'TYPO3/CMS/Backend/ActionDispatcher'
                    $attributes = GeneralUtility::implodeAttributes([
                        'data-dispatch-action' => 'TYPO3.InfoWindow.showItem',
                        'data-dispatch-args-list' => '_FILE,' . (int)$fileObject->getUid(),
                    ], true);
                    $thumbData .= '<a href="#" ' . $attributes . '>' . $imgTag . '</a> ';
                } else {
                    $thumbData .= $imgTag;
                }
            }
        }
        return $thumbData;
    }

    /**
     * @param int $fileId
     * @param array $configuration
     * @return string
     */
    public static function getThumbnailUrl(int $fileId, array $configuration): string
    {
        $taskType = $configuration['_context'] ?? ProcessedFile::CONTEXT_IMAGEPREVIEW;
        unset($configuration['_context']);

        return GeneralUtility::makeInstance(ResourceFactory::class)
                ->getFileObject($fileId)
                ->process($taskType, $configuration)
                ->getPublicUrl(true);
    }

    /**
     * Returns title-attribute information for a page-record informing about id, doktype, hidden, starttime, endtime, fe_group etc.
     *
     * @param array $row Input must be a page row ($row) with the proper fields set (be sure - send the full range of fields for the table)
     * @param string $perms_clause This is used to get the record path of the shortcut page, if any (and doktype==4)
     * @param bool $includeAttrib If $includeAttrib is set, then the 'title=""' attribute is wrapped about the return value, which is in any case htmlspecialchar()'ed already
     * @return string
     */
    public static function titleAttribForPages($row, $perms_clause = '', $includeAttrib = true)
    {
        $lang = static::getLanguageService();
        $parts = [];
        $parts[] = 'id=' . $row['uid'];
        if ($row['uid'] === 0) {
            $out = htmlspecialchars($parts[0]);
            return $includeAttrib ? 'title="' . $out . '"' : $out;
        }
        switch (VersionState::cast($row['t3ver_state'])) {
            case new VersionState(VersionState::DELETE_PLACEHOLDER):
                $parts[] = 'Deleted element!';
                break;
            case new VersionState(VersionState::MOVE_POINTER):
                $parts[] = 'NEW LOCATION (Move-to Pointer) WSID#' . $row['t3ver_wsid'];
                break;
            case new VersionState(VersionState::NEW_PLACEHOLDER):
                $parts[] = 'New element!';
                break;
        }
        if ($row['doktype'] == PageRepository::DOKTYPE_LINK) {
            $parts[] = $lang->sL($GLOBALS['TCA']['pages']['columns']['url']['label'] ?? '') . ' ' . ($row['url'] ?? '');
        } elseif ($row['doktype'] == PageRepository::DOKTYPE_SHORTCUT) {
            if ($perms_clause) {
                $label = self::getRecordPath((int)$row['shortcut'], $perms_clause, 20);
            } else {
                $row['shortcut'] = (int)($row['shortcut'] ?? 0);
                $lRec = self::getRecordWSOL('pages', $row['shortcut'], 'title');
                $label = ($lRec === null ? '' : $lRec['title']) . ' (id=' . $row['shortcut'] . ')';
            }
            if (($row['shortcut_mode'] ?? 0) != PageRepository::SHORTCUT_MODE_NONE) {
                $label .= ', ' . $lang->sL($GLOBALS['TCA']['pages']['columns']['shortcut_mode']['label']) . ' '
                    . $lang->sL(self::getLabelFromItemlist('pages', 'shortcut_mode', $row['shortcut_mode']));
            }
            $parts[] = $lang->sL($GLOBALS['TCA']['pages']['columns']['shortcut']['label']) . ' ' . $label;
        } elseif ($row['doktype'] == PageRepository::DOKTYPE_MOUNTPOINT) {
            if ((int)$row['mount_pid'] > 0) {
                if ($perms_clause) {
                    $label = self::getRecordPath((int)$row['mount_pid'], $perms_clause, 20);
                } else {
                    $lRec = self::getRecordWSOL('pages', (int)$row['mount_pid'], 'title');
                    $label = $lRec['title'] . ' (id=' . $row['mount_pid'] . ')';
                }
                $parts[] = $lang->sL($GLOBALS['TCA']['pages']['columns']['mount_pid']['label']) . ' ' . $label;
                if ($row['mount_pid_ol'] ?? 0) {
                    $parts[] = $lang->sL($GLOBALS['TCA']['pages']['columns']['mount_pid_ol']['label']);
                }
            } else {
                $parts[] = $lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:no_mount_pid');
            }
        }
        if ($row['nav_hide']) {
            $parts[] = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages.nav_hide');
        }
        if ($row['hidden']) {
            $parts[] = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.hidden');
        }
        if ($row['starttime']) {
            $parts[] = $lang->sL($GLOBALS['TCA']['pages']['columns']['starttime']['label'])
                . ' ' . self::dateTimeAge($row['starttime'], -1, 'date');
        }
        if ($row['endtime']) {
            $parts[] = $lang->sL($GLOBALS['TCA']['pages']['columns']['endtime']['label']) . ' '
                . self::dateTimeAge($row['endtime'], -1, 'date');
        }
        if ($row['fe_group']) {
            $fe_groups = [];
            foreach (GeneralUtility::intExplode(',', $row['fe_group']) as $fe_group) {
                if ($fe_group < 0) {
                    $fe_groups[] = $lang->sL(self::getLabelFromItemlist('pages', 'fe_group', (string)$fe_group));
                } else {
                    $lRec = self::getRecordWSOL('fe_groups', $fe_group, 'title');
                    $fe_groups[] = $lRec['title'];
                }
            }
            $label = implode(', ', $fe_groups);
            $parts[] = $lang->sL($GLOBALS['TCA']['pages']['columns']['fe_group']['label']) . ' ' . $label;
        }
        $out = htmlspecialchars(implode(' - ', $parts));
        return $includeAttrib ? 'title="' . $out . '"' : $out;
    }

    /**
     * Returns the combined markup for Bootstraps tooltips
     *
     * @param array $row
     * @param string $table
     * @return string
     */
    public static function getRecordToolTip(array $row, $table = 'pages')
    {
        $toolTipText = self::getRecordIconAltText($row, $table);
        $toolTipCode = 'data-bs-toggle="tooltip" title=" '
            . str_replace(' - ', '<br>', $toolTipText)
            . '" data-bs-html="true" data-bs-placement="right"';
        return $toolTipCode;
    }

    /**
     * Returns title-attribute information for ANY record (from a table defined in TCA of course)
     * The included information depends on features of the table, but if hidden, starttime, endtime and fe_group fields are configured for, information about the record status in regard to these features are is included.
     * "pages" table can be used as well and will return the result of ->titleAttribForPages() for that page.
     *
     * @param array $row Table row; $row is a row from the table, $table
     * @param string $table Table name
     * @return string
     */
    public static function getRecordIconAltText($row, $table = 'pages')
    {
        if ($table === 'pages') {
            $out = self::titleAttribForPages($row, '', false);
        } else {
            $out = !empty(trim($GLOBALS['TCA'][$table]['ctrl']['descriptionColumn'] ?? ''))
                ? ($row[$GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']] ?? '') . ' '
                : '';
            $ctrl = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'] ?? [];
            // Uid is added
            $out .= 'id=' . ($row['uid'] ?? 0);
            if (static::isTableWorkspaceEnabled($table)) {
                switch (VersionState::cast($row['t3ver_state'] ?? null)) {
                    case new VersionState(VersionState::DELETE_PLACEHOLDER):
                        $out .= ' - Deleted element!';
                        break;
                    case new VersionState(VersionState::MOVE_POINTER):
                        $out .= ' - NEW LOCATION (Move-to Pointer) WSID#' . $row['t3ver_wsid'];
                        break;
                    case new VersionState(VersionState::NEW_PLACEHOLDER):
                        $out .= ' - New element!';
                        break;
                }
            }
            // Hidden
            $lang = static::getLanguageService();
            if ($ctrl['disabled'] ?? false) {
                $out .= ($row[$ctrl['disabled']] ?? false) ? ' - ' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.hidden') : '';
            }
            if (($ctrl['starttime'] ?? false) && ($row[$ctrl['starttime']] ?? 0) > $GLOBALS['EXEC_TIME']) {
                $out .= ' - ' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.starttime') . ':' . self::date($row[$ctrl['starttime']]) . ' (' . self::daysUntil($row[$ctrl['starttime']]) . ' ' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.days') . ')';
            }
            if (($ctrl['endtime'] ?? false) && ($row[$ctrl['endtime']] ?? false)) {
                $out .= ' - ' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.endtime') . ': ' . self::date($row[$ctrl['endtime']]) . ' (' . self::daysUntil($row[$ctrl['endtime']]) . ' ' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.days') . ')';
            }
        }
        return htmlspecialchars($out);
    }

    /**
     * Returns the label of the first found entry in an "items" array from $GLOBALS['TCA'] (tablename = $table/fieldname = $col) where the value is $key
     *
     * @param string $table Table name, present in $GLOBALS['TCA']
     * @param string $col Field name, present in $GLOBALS['TCA']
     * @param string $key items-array value to match
     * @return string Label for item entry
     */
    public static function getLabelFromItemlist($table, $col, $key)
    {
        // Check, if there is an "items" array:
        if (is_array($GLOBALS['TCA'][$table]['columns'][$col]['config']['items'] ?? false)) {
            // Traverse the items-array...
            foreach ($GLOBALS['TCA'][$table]['columns'][$col]['config']['items'] as $v) {
                // ... and return the first found label where the value was equal to $key
                if ((string)$v[1] === (string)$key) {
                    return $v[0];
                }
            }
        }
        return '';
    }

    /**
     * Return the label of a field by additionally checking TsConfig values
     *
     * @param int $pageId Page id
     * @param string $table Table name
     * @param string $column Field Name
     * @param string $key item value
     * @return string Label for item entry
     */
    public static function getLabelFromItemListMerged($pageId, $table, $column, $key)
    {
        $pageTsConfig = static::getPagesTSconfig($pageId);
        $label = '';
        if (isset($pageTsConfig['TCEFORM.'])
            && is_array($pageTsConfig['TCEFORM.'] ?? null)
            && is_array($pageTsConfig['TCEFORM.'][$table . '.'] ?? null)
            && is_array($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.'] ?? null)
        ) {
            if (is_array($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['addItems.'] ?? null)
                && isset($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['addItems.'][$key])
            ) {
                $label = $pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['addItems.'][$key];
            } elseif (is_array($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['altLabels.'] ?? null)
                && isset($pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['altLabels.'][$key])
            ) {
                $label = $pageTsConfig['TCEFORM.'][$table . '.'][$column . '.']['altLabels.'][$key];
            }
        }
        if (empty($label)) {
            $tcaValue = self::getLabelFromItemlist($table, $column, $key);
            if (!empty($tcaValue)) {
                $label = $tcaValue;
            }
        }
        return $label;
    }

    /**
     * Splits the given key with commas and returns the list of all the localized items labels, separated by a comma.
     * NOTE: this does not take itemsProcFunc into account
     *
     * @param string $table Table name, present in TCA
     * @param string $column Field name
     * @param string $keyList Key or comma-separated list of keys.
     * @param array $columnTsConfig page TSConfig for $column (TCEMAIN.<table>.<column>)
     * @return string Comma-separated list of localized labels
     */
    public static function getLabelsFromItemsList($table, $column, $keyList, array $columnTsConfig = [])
    {
        // Check if there is an "items" array
        if (
            !isset($GLOBALS['TCA'][$table]['columns'][$column]['config']['items'])
            || !is_array($GLOBALS['TCA'][$table]['columns'][$column]['config']['items'])
            || $keyList === ''
        ) {
            return '';
        }

        $keys = GeneralUtility::trimExplode(',', $keyList, true);
        $labels = [];
        // Loop on all selected values
        foreach ($keys as $key) {
            $label = null;
            if ($columnTsConfig) {
                // Check if label has been defined or redefined via pageTsConfig
                if (isset($columnTsConfig['addItems.'][$key])) {
                    $label = $columnTsConfig['addItems.'][$key];
                } elseif (isset($columnTsConfig['altLabels.'][$key])) {
                    $label = $columnTsConfig['altLabels.'][$key];
                }
            }
            if ($label === null) {
                // Otherwise lookup the label in TCA items list
                foreach ($GLOBALS['TCA'][$table]['columns'][$column]['config']['items'] as $itemConfiguration) {
                    [$currentLabel, $currentKey] = $itemConfiguration;
                    if ((string)$key === (string)$currentKey) {
                        $label = $currentLabel;
                        break;
                    }
                }
            }
            if ($label !== null) {
                $labels[] = static::getLanguageService()->sL($label);
            }
        }
        return implode(', ', $labels);
    }

    /**
     * Returns the label-value for fieldname $col in table, $table
     * If $printAllWrap is set (to a "wrap") then it's wrapped around the $col value IF THE COLUMN $col DID NOT EXIST in TCA!, eg. $printAllWrap = '<strong>|</strong>' and the fieldname was 'not_found_field' then the return value would be '<strong>not_found_field</strong>'
     *
     * @param string $table Table name, present in $GLOBALS['TCA']
     * @param string $col Field name
     * @return string or NULL if $col is not found in the TCA table
     */
    public static function getItemLabel($table, $col)
    {
        return $GLOBALS['TCA'][$table]['columns'][$col]['label'] ?? null;
    }

    /**
     * Returns the "title"-value in record, $row, from table, $table
     * The field(s) from which the value is taken is determined by the "ctrl"-entries 'label', 'label_alt' and 'label_alt_force'
     *
     * @param string $table Table name, present in TCA
     * @param array $row Row from table
     * @param bool $prep If set, result is prepared for output: The output is cropped to a limited length (depending on BE_USER->uc['titleLen']) and if no value is found for the title, '<em>[No title]</em>' is returned (localized). Further, the output is htmlspecialchars()'ed
     * @param bool $forceResult If set, the function always returns an output. If no value is found for the title, '[No title]' is returned (localized).
     * @return string
     */
    public static function getRecordTitle($table, $row, $prep = false, $forceResult = true)
    {
        $params = [];
        $recordTitle = '';
        if (isset($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table])) {
            // If configured, call userFunc
            if (!empty($GLOBALS['TCA'][$table]['ctrl']['label_userFunc'])) {
                $params['table'] = $table;
                $params['row'] = $row;
                $params['title'] = '';
                $params['options'] = $GLOBALS['TCA'][$table]['ctrl']['label_userFunc_options'] ?? [];

                // Create NULL-reference
                $null = null;
                GeneralUtility::callUserFunction($GLOBALS['TCA'][$table]['ctrl']['label_userFunc'], $params, $null);
                // Ensure that result of called userFunc still have title set, and it is a string.
                $recordTitle = (string)($params['title'] ?? '');
            } else {
                // No userFunc: Build label
                $ctrlLabel = $GLOBALS['TCA'][$table]['ctrl']['label'] ?? '';
                $recordTitle = self::getProcessedValue(
                    $table,
                    $ctrlLabel,
                    (string)($row[$ctrlLabel] ?? ''),
                    0,
                    false,
                    false,
                    $row['uid'] ?? null,
                    $forceResult
                ) ?? '';
                if (!empty($GLOBALS['TCA'][$table]['ctrl']['label_alt'])
                    && (!empty($GLOBALS['TCA'][$table]['ctrl']['label_alt_force']) || $recordTitle === '')
                ) {
                    $altFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], true);
                    $tA = [];
                    if (!empty($recordTitle)) {
                        $tA[] = $recordTitle;
                    }
                    foreach ($altFields as $fN) {
                        $recordTitle = trim(strip_tags((string)($row[$fN] ?? '')));
                        if ($recordTitle !== '') {
                            $recordTitle = self::getProcessedValue($table, $fN, $recordTitle, 0, false, false, $row['uid'] ?? 0);
                            if (!($GLOBALS['TCA'][$table]['ctrl']['label_alt_force'] ?? false)) {
                                break;
                            }
                            $tA[] = $recordTitle;
                        }
                    }
                    if ($GLOBALS['TCA'][$table]['ctrl']['label_alt_force'] ?? false) {
                        $recordTitle = implode(', ', $tA);
                    }
                }
            }
            // If the current result is empty, set it to '[No title]' (localized) and prepare for output if requested
            if ($prep || $forceResult) {
                if ($prep) {
                    $recordTitle = self::getRecordTitlePrep($recordTitle);
                }
                if (trim($recordTitle) === '') {
                    $recordTitle = self::getNoRecordTitle($prep);
                }
            }
        }

        return $recordTitle;
    }

    /**
     * Crops a title string to a limited length and if it really was cropped, wrap it in a <span title="...">|</span>,
     * which offers a tooltip with the original title when moving mouse over it.
     *
     * @param string $title The title string to be cropped
     * @param int $titleLength Crop title after this length - if not set, BE_USER->uc['titleLen'] is used
     * @return string The processed title string, wrapped in <span title="...">|</span> if cropped
     */
    public static function getRecordTitlePrep($title, $titleLength = 0)
    {
        // If $titleLength is not a valid positive integer, use BE_USER->uc['titleLen']:
        if (!$titleLength || !MathUtility::canBeInterpretedAsInteger($titleLength) || $titleLength < 0) {
            $titleLength = static::getBackendUserAuthentication()->uc['titleLen'];
        }
        $titleOrig = htmlspecialchars($title);
        $title = htmlspecialchars(GeneralUtility::fixed_lgd_cs($title, $titleLength));
        // If title was cropped, offer a tooltip:
        if ($titleOrig != $title) {
            $title = '<span title="' . $titleOrig . '">' . $title . '</span>';
        }
        return $title;
    }

    /**
     * Get a localized [No title] string, wrapped in <em>|</em> if $prep is TRUE.
     *
     * @param bool $prep Wrap result in <em>|</em>
     * @return string Localized [No title] string
     */
    public static function getNoRecordTitle($prep = false)
    {
        $noTitle = '[' .
            htmlspecialchars(static::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title'))
            . ']';
        if ($prep) {
            $noTitle = '<em>' . $noTitle . '</em>';
        }
        return $noTitle;
    }

    /**
     * Returns a human readable output of a value from a record
     * For instance a database record relation would be looked up to display the title-value of that record. A checkbox with a "1" value would be "Yes", etc.
     * $table/$col is tablename and fieldname
     * REMEMBER to pass the output through htmlspecialchars() if you output it to the browser! (To protect it from XSS attacks and be XHTML compliant)
     *
     * @param string $table Table name, present in TCA
     * @param string $col Field name, present in TCA
     * @param string $value The value of that field from a selected record
     * @param int $fixed_lgd_chars The max amount of characters the value may occupy
     * @param bool $defaultPassthrough Flag means that values for columns that has no conversion will just be pass through directly (otherwise cropped to 200 chars or returned as "N/A")
     * @param bool $noRecordLookup If set, no records will be looked up, UIDs are just shown.
     * @param int $uid Uid of the current record
     * @param bool $forceResult If BackendUtility::getRecordTitle is used to process the value, this parameter is forwarded.
     * @param int $pid Optional page uid is used to evaluate page TSConfig for the given field
     * @throws \InvalidArgumentException
     * @return string|null
     */
    public static function getProcessedValue(
        $table,
        $col,
        $value,
        $fixed_lgd_chars = 0,
        $defaultPassthrough = false,
        $noRecordLookup = false,
        $uid = 0,
        $forceResult = true,
        $pid = 0
    ) {
        if ($col === 'uid') {
            // uid is not in TCA-array
            return $value;
        }
        // Check if table and field is configured
        if (!isset($GLOBALS['TCA'][$table]['columns'][$col]) || !is_array($GLOBALS['TCA'][$table]['columns'][$col])) {
            return null;
        }
        // Depending on the fields configuration, make a meaningful output value.
        $theColConf = $GLOBALS['TCA'][$table]['columns'][$col]['config'] ?? [];
        /*****************
         *HOOK: pre-processing the human readable output from a record
         ****************/
        $referenceObject = new \stdClass();
        $referenceObject->table = $table;
        $referenceObject->fieldName = $col;
        $referenceObject->uid = $uid;
        $referenceObject->value = &$value;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['preProcessValue'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $theColConf, $referenceObject);
        }

        $l = '';
        $lang = static::getLanguageService();
        switch ((string)($theColConf['type'] ?? '')) {
            case 'radio':
                $l = self::getLabelFromItemlist($table, $col, $value);
                $l = $lang->sL($l);
                break;
            case 'inline':
                if ($uid) {
                    $finalValues = static::resolveRelationLabels($theColConf, $table, $uid, $value, $noRecordLookup);
                    $l = implode(', ', $finalValues);
                } else {
                    $l = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:notAvailableAbbreviation');
                }
                break;
            case 'select':
            case 'category':
                if (!empty($theColConf['MM'])) {
                    if ($uid) {
                        $finalValues = static::resolveRelationLabels($theColConf, $table, $uid, $value, $noRecordLookup);
                        $l = implode(', ', $finalValues);
                    } else {
                        $l = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:notAvailableAbbreviation');
                    }
                } else {
                    $columnTsConfig = [];
                    if ($pid) {
                        $pageTsConfig = self::getPagesTSconfig($pid);
                        if (isset($pageTsConfig['TCEFORM.'][$table . '.'][$col . '.']) && is_array($pageTsConfig['TCEFORM.'][$table . '.'][$col . '.'])) {
                            $columnTsConfig = $pageTsConfig['TCEFORM.'][$table . '.'][$col . '.'];
                        }
                    }
                    $l = self::getLabelsFromItemsList($table, $col, $value, $columnTsConfig);
                    if (!empty($theColConf['foreign_table']) && !$l && !empty($GLOBALS['TCA'][$theColConf['foreign_table']])) {
                        if ($noRecordLookup) {
                            $l = $value;
                        } else {
                            $finalValues = [];
                            if ($uid) {
                                $finalValues = static::resolveRelationLabels($theColConf, $table, $uid, $value, $noRecordLookup);
                            }
                            $l = implode(', ', $finalValues);
                        }
                    }
                    if (empty($l) && !empty($value)) {
                        // Use plain database value when label is empty
                        $l = $value;
                    }
                }
                break;
            case 'group':
                if (($theColConf['internal_type'] ?? '') === 'folder') {
                    $l = implode(', ', GeneralUtility::trimExplode(',', $value, true));
                } else {
                    // resolve titles of DB records
                    $finalValues = static::resolveRelationLabels($theColConf, $table, $uid, $value, $noRecordLookup);
                    if ($finalValues !== []) {
                        $l = implode(', ', $finalValues);
                    } else {
                        $l = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:notAvailableAbbreviation');
                    }
                }
                break;
            case 'check':
                if (!is_array($theColConf['items'] ?? null)) {
                    $l = $value ? $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes') : $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no');
                } elseif (count($theColConf['items']) === 1) {
                    reset($theColConf['items']);
                    $invertStateDisplay = current($theColConf['items'])['invertStateDisplay'] ?? false;
                    if ($invertStateDisplay) {
                        $value = !$value;
                    }
                    $l = $value ? $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes') : $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no');
                } else {
                    $lA = [];
                    foreach ($theColConf['items'] as $key => $val) {
                        if ((int)$value & 2 ** $key) {
                            $lA[] = $lang->sL($val[0]);
                        }
                    }
                    $l = implode(', ', $lA);
                }
                break;
            case 'input':
                // Hide value 0 for dates, but show it for everything else
                // todo: phpstan states that $value always exists and is not nullable. At the moment, this is a false
                //       positive as null can be passed into this method via $value. As soon as more strict types are
                //       used, this isset check must be replaced with a more appropriate check.
                if (isset($value)) {
                    $dateTimeFormats = QueryHelper::getDateTimeFormats();

                    if (GeneralUtility::inList($theColConf['eval'] ?? '', 'date')) {
                        // Handle native date field
                        if (isset($theColConf['dbType']) && $theColConf['dbType'] === 'date') {
                            $value = $value === $dateTimeFormats['date']['empty'] ? 0 : (int)strtotime($value);
                        } else {
                            $value = (int)$value;
                        }
                        if (!empty($value)) {
                            $ageSuffix = '';
                            $dateColumnConfiguration = $GLOBALS['TCA'][$table]['columns'][$col]['config'];
                            $ageDisplayKey = 'disableAgeDisplay';

                            // generate age suffix as long as not explicitly suppressed
                            if (!isset($dateColumnConfiguration[$ageDisplayKey])
                                // non typesafe comparison on intention
                                || $dateColumnConfiguration[$ageDisplayKey] == false
                            ) {
                                $ageSuffix = ' (' . ($GLOBALS['EXEC_TIME'] - $value > 0 ? '-' : '')
                                    . self::calcAge(
                                        (int)abs($GLOBALS['EXEC_TIME'] - $value),
                                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                                    )
                                    . ')';
                            }

                            $l = self::date($value) . $ageSuffix;
                        }
                    } elseif (GeneralUtility::inList($theColConf['eval'] ?? '', 'time')) {
                        // Handle native time field
                        if (isset($theColConf['dbType']) && $theColConf['dbType'] === 'time') {
                            $value = $value === $dateTimeFormats['time']['empty'] ? 0 : (int)strtotime('1970-01-01 ' . $value . ' UTC');
                        } else {
                            $value = (int)$value;
                        }
                        if (!empty($value)) {
                            $l = gmdate('H:i', (int)$value);
                        }
                    } elseif (GeneralUtility::inList($theColConf['eval'] ?? '', 'timesec')) {
                        // Handle native time field
                        if (isset($theColConf['dbType']) && $theColConf['dbType'] === 'time') {
                            $value = $value === $dateTimeFormats['time']['empty'] ? 0 : (int)strtotime('1970-01-01 ' . $value . ' UTC');
                        } else {
                            $value = (int)$value;
                        }
                        if (!empty($value)) {
                            $l = gmdate('H:i:s', (int)$value);
                        }
                    } elseif (GeneralUtility::inList($theColConf['eval'] ?? '', 'datetime')) {
                        // Handle native datetime field
                        if (isset($theColConf['dbType']) && $theColConf['dbType'] === 'datetime') {
                            $value = $value === $dateTimeFormats['datetime']['empty'] ? 0 : (int)strtotime($value);
                        } else {
                            $value = (int)$value;
                        }
                        if (!empty($value)) {
                            $l = self::datetime($value);
                        }
                    } else {
                        $l = $value;
                    }
                }
                break;
            case 'flex':
                $l = strip_tags($value);
                break;
            case 'language':
                $l = $value;
                if ($uid) {
                    $pageId = (int)($table === 'pages' ? $uid : (static::getRecordWSOL($table, (int)$uid, 'pid')['pid'] ?? 0));
                    $languageTitle = GeneralUtility::makeInstance(TranslationConfigurationProvider::class)
                        ->getSystemLanguages($pageId)[(int)$value]['title'] ?? '';
                    if ($languageTitle !== '') {
                        $l = $languageTitle;
                    }
                }
                break;
            default:
                if ($defaultPassthrough) {
                    $l = $value;
                } elseif (isset($theColConf['MM'])) {
                    $l = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:notAvailableAbbreviation');
                } elseif ($value) {
                    $l = GeneralUtility::fixed_lgd_cs(strip_tags($value), 200);
                }
        }
        // If this field is a password field, then hide the password by changing it to a random number of asterisk (*)
        if (!empty($theColConf['eval']) && stripos($theColConf['eval'], 'password') !== false) {
            $l = '';
            $randomNumber = random_int(5, 12);
            for ($i = 0; $i < $randomNumber; $i++) {
                $l .= '*';
            }
        }
        /*****************
         *HOOK: post-processing the human readable output from a record
         ****************/
        $null = null;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['postProcessValue'] ?? [] as $_funcRef) {
            $params = [
                'value' => $l,
                'colConf' => $theColConf,
            ];
            $l = GeneralUtility::callUserFunction($_funcRef, $params, $null);
        }
        if ($fixed_lgd_chars && $l) {
            return GeneralUtility::fixed_lgd_cs((string)$l, $fixed_lgd_chars);
        }
        return $l;
    }

    /**
     * Helper method to fetch all labels for all relations of processed Values.
     *
     * @param array $theColConf
     * @param string $table
     * @param string|int|null $recordId
     * @param string|int $value
     * @param bool $noRecordLookup
     * @return array
     */
    protected static function resolveRelationLabels(array $theColConf, string $table, $recordId, $value, bool $noRecordLookup): array
    {
        $finalValues = [];

        $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
        $relationHandler->registerNonTableValues = (bool)($theColConf['allowNonIdValues'] ?? false);
        $relationHandler->start(
            $value,
            $theColConf['allowed'] ?? $theColConf['foreign_table'] ?? '',
            $theColConf['MM'] ?? '',
            $recordId,
            $table,
            $theColConf
        );

        if ($noRecordLookup) {
            $finalValues = array_column($relationHandler->itemArray, 'id');
        } else {
            $relationHandler->getFromDB();
            foreach ($relationHandler->getResolvedItemArray() as $item) {
                $relationRecord = $item['record'];
                static::workspaceOL($item['table'], $relationRecord);
                if (!is_array($relationRecord)) {
                    $finalValues[] = '[' . $item['uid'] . ']';
                } else {
                    $title = static::getRecordTitle($item['table'], $relationRecord);
                    if ($theColConf['foreign_table_prefix'] ?? null) {
                        $title = static::getLanguageService()->sL($theColConf['foreign_table_prefix']) . $title;
                    }
                    $finalValues[] = $title;
                }
            }
        }

        return $finalValues;
    }

    /**
     * Same as ->getProcessedValue() but will go easy on fields like "tstamp" and "pid" which are not configured in TCA - they will be formatted by this function instead.
     *
     * @param string $table Table name, present in TCA
     * @param string $fN Field name
     * @param string $fV Field value
     * @param int $fixed_lgd_chars The max amount of characters the value may occupy
     * @param int $uid Uid of the current record
     * @param bool $forceResult If BackendUtility::getRecordTitle is used to process the value, this parameter is forwarded.
     * @param int $pid Optional page uid is used to evaluate page TSConfig for the given field
     * @return string
     * @see getProcessedValue()
     */
    public static function getProcessedValueExtra(
        $table,
        $fN,
        $fV,
        $fixed_lgd_chars = 0,
        $uid = 0,
        $forceResult = true,
        $pid = 0
    ) {
        $fVnew = self::getProcessedValue($table, $fN, $fV, $fixed_lgd_chars, true, false, $uid, $forceResult, $pid);
        if (!isset($fVnew)) {
            if (is_array($GLOBALS['TCA'][$table])) {
                if ($fN == ($GLOBALS['TCA'][$table]['ctrl']['tstamp'] ?? 0) || $fN == ($GLOBALS['TCA'][$table]['ctrl']['crdate'] ?? 0)) {
                    $fVnew = self::datetime((int)$fV);
                } elseif ($fN === 'pid') {
                    // Fetches the path with no regard to the users permissions to select pages.
                    $fVnew = self::getRecordPath((int)$fV, '1=1', 20);
                } else {
                    $fVnew = $fV;
                }
            }
        }
        return $fVnew;
    }

    /**
     * Returns fields for a table, $table, which would typically be interesting to select
     * This includes uid, the fields defined for title, icon-field.
     * Returned as a list ready for query ($prefix can be set to eg. "pages." if you are selecting from the pages table and want the table name prefixed)
     *
     * @param string $table Table name, present in $GLOBALS['TCA']
     * @param string $prefix Table prefix
     * @param array $fields Preset fields (must include prefix if that is used)
     * @return string List of fields.
     * @internal should only be used from within TYPO3 Core
     */
    public static function getCommonSelectFields($table, $prefix = '', $fields = [])
    {
        $fields[] = $prefix . 'uid';
        if (isset($GLOBALS['TCA'][$table]['ctrl']['label']) && $GLOBALS['TCA'][$table]['ctrl']['label'] != '') {
            $fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['label'];
        }
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['label_alt'])) {
            $secondFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], true);
            foreach ($secondFields as $fieldN) {
                $fields[] = $prefix . $fieldN;
            }
        }
        if (static::isTableWorkspaceEnabled($table)) {
            $fields[] = $prefix . 't3ver_state';
            $fields[] = $prefix . 't3ver_wsid';
        }
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['selicon_field'])) {
            $fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['selicon_field'];
        }
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['typeicon_column'])) {
            $fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
        }
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'])) {
            $fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
        }
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'])) {
            $fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['starttime'];
        }
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime'])) {
            $fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['endtime'];
        }
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group'])) {
            $fields[] = $prefix . $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['fe_group'];
        }
        return implode(',', array_unique($fields));
    }

    /*******************************************
     *
     * Backend Modules API functions
     *
     *******************************************/

    /**
     * Returns CSH help text (description), if configured for, as an array (title, description)
     *
     * @param string $table Table name
     * @param string $field Field name
     * @return array With keys 'description' (raw, as available in locallang), 'title' (optional), 'moreInfo'
     * @internal should only be used from within TYPO3 Core
     */
    public static function helpTextArray($table, $field)
    {
        if (!isset($GLOBALS['TCA_DESCR'][$table]['columns'])) {
            static::getLanguageService()->loadSingleTableDescription($table);
        }
        $output = [
            'description' => null,
            'title' => null,
            'moreInfo' => false,
        ];
        if (isset($GLOBALS['TCA_DESCR'][$table]['columns'][$field]) && is_array($GLOBALS['TCA_DESCR'][$table]['columns'][$field])) {
            $data = $GLOBALS['TCA_DESCR'][$table]['columns'][$field];
            // Add alternative title, if defined
            if ($data['alttitle'] ?? false) {
                $output['title'] = $data['alttitle'];
            }
            // If we have more information to show and access to the cshmanual
            // This is effectively a long list of ORs, but also allows for any to be unset. The first one set and truthy
            // will evaluate the whole chain to true.
            if (($data['image_descr'] ?? $data['seeAlso'] ?? $data['details'] ?? $data['syntax'] ?? false)
                && static::getBackendUserAuthentication()->check('modules', 'help_cshmanual')
            ) {
                $output['moreInfo'] = true;
            }
            // Add description
            if ($data['description'] ?? null) {
                $output['description'] = $data['description'];
            }
        }
        return $output;
    }

    /**
     * Returns CSH help text
     *
     * @param string $table Table name
     * @param string $field Field name
     * @return string HTML content for help text
     * @see cshItem()
     * @internal should only be used from within TYPO3 Core
     */
    public static function helpText($table, $field)
    {
        $helpTextArray = self::helpTextArray($table, $field);
        $output = '';
        $arrow = '';
        // Put header before the rest of the text
        if ($helpTextArray['title'] !== null) {
            $output .= '<h2>' . $helpTextArray['title'] . '</h2>';
        }
        // Add see also arrow if we have more info
        if ($helpTextArray['moreInfo']) {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $arrow = $iconFactory->getIcon('actions-view-go-forward', Icon::SIZE_SMALL)->render();
        }
        // Wrap description and arrow in p tag
        if ($helpTextArray['description'] !== null || $arrow) {
            $output .= '<p class="help-short">' . nl2br(htmlspecialchars((string)$helpTextArray['description'])) . $arrow . '</p>';
        }
        return $output;
    }

    /**
     * API function that wraps the text / html in help text, so if a user hovers over it
     * the help text will show up
     *
     * @param string $table The table name for which the help should be shown
     * @param string $field The field name for which the help should be shown
     * @param string $text The text which should be wrapped with the help text
     * @param array $overloadHelpText Array with text to overload help text
     * @return string the HTML code ready to render
     * @internal should only be used from within TYPO3 Core
     */
    public static function wrapInHelp($table, $field, $text = '', array $overloadHelpText = [])
    {
        // Initialize some variables
        $helpText = '';
        $abbrClassAdd = '';
        $hasHelpTextOverload = !empty($overloadHelpText);
        // Get the help text that should be shown on hover
        if (!$hasHelpTextOverload) {
            $helpText = self::helpText($table, $field);
        }
        // If there's a help text or some overload information, proceed with preparing an output
        if (!empty($helpText) || $hasHelpTextOverload) {
            // If no text was given, just use the regular help icon
            if ($text == '') {
                $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
                $text = $iconFactory->getIcon('actions-system-help-open', Icon::SIZE_SMALL)->render();
                $abbrClassAdd = ' help-teaser-icon';
            }
            $text = '<abbr class="help-teaser' . $abbrClassAdd . '">' . $text . '</abbr>';
            $wrappedText = '<span class="help-link" data-table="' . $table . '" data-field="' . $field . '" data-bs-content="<p></p>"';
            // The overload array may provide a title and a description
            // If either one is defined, add them to the "data" attributes
            if ($hasHelpTextOverload) {
                if (isset($overloadHelpText['title'])) {
                    $wrappedText .= ' data-title="' . htmlspecialchars($overloadHelpText['title']) . '"';
                }
                if (isset($overloadHelpText['description'])) {
                    $wrappedText .= ' data-description="' . htmlspecialchars($overloadHelpText['description']) . '"';
                }
            }
            $wrappedText .= '>' . $text . '</span>';
            return $wrappedText;
        }
        return $text;
    }

    /**
     * API for getting CSH icons/text for use in backend modules.
     * TCA_DESCR will be loaded if it isn't already
     *
     * @param string $table Table name ('_MOD_'+module name)
     * @param string $field Field name (CSH locallang main key)
     * @param string $_ (unused)
     * @param string $wrap Wrap code for icon-mode, splitted by "|". Not used for full-text mode.
     * @return string HTML content for help text
     */
    public static function cshItem($table, $field, $_ = '', $wrap = '')
    {
        static::getLanguageService()->loadSingleTableDescription($table);
        if (is_array($GLOBALS['TCA_DESCR'][$table] ?? null)
            && is_array($GLOBALS['TCA_DESCR'][$table]['columns'][$field] ?? null)
        ) {
            // Creating short description
            $output = self::wrapInHelp($table, $field);
            if ($output && $wrap) {
                $wrParts = explode('|', $wrap);
                $output = $wrParts[0] . $output . $wrParts[1];
            }
            return $output;
        }
        return '';
    }

    /**
     * Returns a JavaScript string for viewing the page id, $id
     * It will re-use any window already open.
     *
     * @param int $pageUid Page UID
     * @param string $backPath Must point back to TYPO3_mainDir (where the site is assumed to be one level above)
     * @param array|null $rootLine If root line is supplied the function will look for the first found domain record and use that URL instead (if found)
     * @param string $anchorSection Optional anchor to the URL
     * @param string $alternativeUrl An alternative URL that, if set, will ignore other parameters except $switchFocus: It will return the window.open command wrapped around this URL!
     * @param string $additionalGetVars Additional GET variables.
     * @param bool $switchFocus If TRUE, then the preview window will gain the focus.
     * @return string
     * @deprecated Use TYPO3\CMS\Backend\Routing\PreviewUriBuilder instead, will be removed in TYPO3 v12.0
     */
    public static function viewOnClick(
        $pageUid,
        $backPath = '',
        $rootLine = null,
        $anchorSection = '',
        $alternativeUrl = '',
        $additionalGetVars = '',
        $switchFocus = true
    ) {
        trigger_error(
            'Using BackendUtility::viewOnClick() is deprecated, use TYPO3\CMS\Backend\Routing\PreviewUriBuilder instead.',
            E_USER_DEPRECATED
        );
        try {
            $previewUrl = self::getPreviewUrl(
                $pageUid,
                $backPath,
                $rootLine,
                $anchorSection,
                $alternativeUrl,
                $additionalGetVars,
                $switchFocus
            );
        } catch (UnableToLinkToPageException $e) {
            return '';
        }

        $onclickCode = 'var previewWin = window.open(' . GeneralUtility::quoteJSvalue($previewUrl) . ',\'newTYPO3frontendWindow\');'
            . ($switchFocus ? 'previewWin.focus();' : '') . LF
            . 'if (previewWin.location.href === ' . GeneralUtility::quoteJSvalue($previewUrl) . ') { previewWin.location.reload(); };';

        return $onclickCode;
    }

    /**
     * Returns the preview url
     *
     * It will detect the correct domain name if needed and provide the link with the right back path.
     *
     * @param int $pageUid Page UID
     * @param string $backPath Must point back to TYPO3_mainDir (where the site is assumed to be one level above)
     * @param array|null $rootLine If root line is supplied the function will look for the first found domain record and use that URL instead (if found)
     * @param string $anchorSection Optional anchor to the URL
     * @param string $alternativeUrl An alternative URL that, if set, will ignore other parameters except $switchFocus: It will return the window.open command wrapped around this URL!
     * @param string $additionalGetVars Additional GET variables.
     * @param bool $switchFocus If TRUE, then the preview window will gain the focus.
     * @return string
     */
    public static function getPreviewUrl(
        $pageUid,
        $backPath = '',
        $rootLine = null,
        $anchorSection = '',
        $alternativeUrl = '',
        $additionalGetVars = '',
        &$switchFocus = true
    ): string {
        $viewScript = '/index.php?id=';
        if ($alternativeUrl) {
            $viewScript = $alternativeUrl;
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'preProcess')) {
                $hookObj->preProcess(
                    $pageUid,
                    $backPath,
                    $rootLine,
                    $anchorSection,
                    $viewScript,
                    $additionalGetVars,
                    $switchFocus
                );
            }
        }

        // If there is an alternative URL or the URL has been modified by a hook, use that one.
        if ($alternativeUrl || $viewScript !== '/index.php?id=') {
            $previewUrl = $viewScript;
        } else {
            $permissionClause = $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW);
            $pageInfo = self::readPageAccess($pageUid, $permissionClause) ?: [];
            // prepare custom context for link generation (to allow for example time based previews)
            $context = clone GeneralUtility::makeInstance(Context::class);
            $additionalGetVars .= self::ADMCMD_previewCmds($pageInfo, $context);

            // Build the URL with a site as prefix, if configured
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            // Check if the page (= its rootline) has a site attached, otherwise just keep the URL as is
            $rootLine = $rootLine ?? BackendUtility::BEgetRootLine($pageUid);
            try {
                $site = $siteFinder->getSiteByPageId((int)$pageUid, $rootLine);
            } catch (SiteNotFoundException $e) {
                throw new UnableToLinkToPageException('The page ' . $pageUid . ' had no proper connection to a site, no link could be built.', 1559794919);
            }
            // Create a multi-dimensional array out of the additional get vars
            $additionalQueryParams = [];
            parse_str($additionalGetVars, $additionalQueryParams);
            if (isset($additionalQueryParams['L'])) {
                $additionalQueryParams['_language'] = $additionalQueryParams['_language'] ?? $additionalQueryParams['L'];
                unset($additionalQueryParams['L']);
            }
            try {
                $previewUrl = (string)$site->getRouter($context)->generateUri(
                    $pageUid,
                    $additionalQueryParams,
                    $anchorSection,
                    RouterInterface::ABSOLUTE_URL
                );
            } catch (\InvalidArgumentException | InvalidRouteArgumentsException $e) {
                throw new UnableToLinkToPageException(sprintf('The link to the page with ID "%d" could not be generated: %s', $pageUid, $e->getMessage()), 1559794914, $e);
            }
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'postProcess')) {
                $previewUrl = $hookObj->postProcess(
                    $previewUrl,
                    $pageUid,
                    $rootLine,
                    $anchorSection,
                    $viewScript,
                    $additionalGetVars,
                    $switchFocus
                );
            }
        }

        return $previewUrl;
    }

    /**
     * Makes click menu link (context sensitive menu)
     *
     * Returns $str wrapped in a link which will activate the context sensitive
     * menu for the record ($table/$uid) or file ($table = file)
     * The link will load the top frame with the parameter "&item" which is the table, uid
     * and context arguments imploded by "|": rawurlencode($table.'|'.$uid.'|'.$context)
     *
     * @param string $content String to be wrapped in link, typ. image tag.
     * @param string $table Table name/File path. If the icon is for a database
     * record, enter the tablename from $GLOBALS['TCA']. If a file then enter
     * the absolute filepath
     * @param int|string $uid If icon is for database record this is the UID for the
     * record from $table or identifier for sys_file record
     * @param string $context Set tree if menu is called from tree view
     * @param string $_addParams NOT IN USE Deprecated since TYPO3 11, will be removed in TYPO3 12.
     * @param string $_enDisItems NOT IN USE Deprecated since TYPO3 11, will be removed in TYPO3 12.
     * @param bool $returnTagParameters If set, will return only the onclick
     * JavaScript, not the whole link. Deprecated since TYPO3 11, will be removed in TYPO3 12.
     *
     * @return string|array The link wrapped input string.
     */
    public static function wrapClickMenuOnIcon(
        $content,
        $table,
        $uid = 0,
        $context = '',
        $_addParams = '',
        $_enDisItems = '',
        $returnTagParameters = false
    ) {
        $tagParameters = self::getClickMenuOnIconTagParameters((string)$table, $uid, (string)$context);

        if ($_addParams !== '') {
            trigger_error('Calling BackendUtility::wrapClickMenuOnIcon() with unused 5th parameter is deprecated and will be removed in v12.', E_USER_DEPRECATED);
        }
        if ($_enDisItems !== '') {
            trigger_error('Calling BackendUtility::wrapClickMenuOnIcon() with unused 6th parameter is deprecated and will be removed in v12.', E_USER_DEPRECATED);
        }
        if ($returnTagParameters) {
            trigger_error('Calling BackendUtility::wrapClickMenuOnIcon() with 7th parameter set to true is deprecated and will be removed in v12. Please use BackendUtility::getClickMenuOnIconTagParameters() instead.', E_USER_DEPRECATED);
            return $tagParameters;
        }
        return '<a href="#" ' . GeneralUtility::implodeAttributes($tagParameters, true) . '>' . $content . '</a>';
    }

    /**
     * @param string $table Table name/File path. If the icon is for a database
     * record, enter the tablename from $GLOBALS['TCA']. If a file then enter
     * the absolute filepath
     * @param int|string $uid If icon is for database record this is the UID for the
     * record from $table or identifier for sys_file record
     * @param string $context Set tree if menu is called from tree view
     * @return array
     */
    public static function getClickMenuOnIconTagParameters(string $table, $uid = 0, string $context = ''): array
    {
        return [
            'class' => 't3js-contextmenutrigger',
            'data-table' => $table,
            'data-uid' => (string)$uid,
            'data-context' => $context,
        ];
    }

    /**
     * Returns a URL with a command to TYPO3 Datahandler
     *
     * @param string $parameters Set of GET params to send. Example: "&cmd[tt_content][123][move]=456" or "&data[tt_content][123][hidden]=1&data[tt_content][123][title]=Hello%20World
     * @param string $redirectUrl Redirect URL, default is to use $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri()
     * @return string
     */
    public static function getLinkToDataHandlerAction($parameters, $redirectUrl = '')
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $url = (string)$uriBuilder->buildUriFromRoute('tce_db') . $parameters . '&redirect=';
        $url .= rawurlencode($redirectUrl ?: $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri());
        return $url;
    }

    /**
     * Returns a selector box "function menu" for a module
     * See Inside TYPO3 for details about how to use / make Function menus
     *
     * @param mixed $mainParams The "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
     * @param string $elementName The form elements name, probably something like "SET[...]
     * @param string $currentValue The value to be selected currently.
     * @param array $menuItems An array with the menu items for the selector box
     * @param string $script The script to send the &id to, if empty it's automatically found
     * @param string $addParams Additional parameters to pass to the script.
     * @return string HTML code for selector box
     */
    public static function getFuncMenu(
        $mainParams,
        $elementName,
        $currentValue,
        $menuItems,
        $script = '',
        $addParams = ''
    ): string {
        if (!is_array($menuItems) || count($menuItems) <= 1) {
            return '';
        }
        $scriptUrl = self::buildScriptUrl($mainParams, $addParams, $script);
        $options = [];
        foreach ($menuItems as $value => $label) {
            $options[] = '<option value="'
                . htmlspecialchars($value) . '"'
                . ((string)$currentValue === (string)$value ? ' selected="selected"' : '') . '>'
                . htmlspecialchars($label, ENT_COMPAT, 'UTF-8', false) . '</option>';
        }
        $dataMenuIdentifier = str_replace(['SET[', ']'], '', $elementName);
        $dataMenuIdentifier = GeneralUtility::camelCaseToLowerCaseUnderscored($dataMenuIdentifier);
        $dataMenuIdentifier = str_replace('_', '-', $dataMenuIdentifier);
        if (!empty($options)) {
            // relies on module 'TYPO3/CMS/Backend/ActionDispatcher'
            $attributes = GeneralUtility::implodeAttributes([
                'name' => $elementName,
                'class' => 'form-select mb-3',
                'data-menu-identifier' => $dataMenuIdentifier,
                'data-global-event' => 'change',
                'data-action-navigate' => '$data=~s/$value/',
                'data-navigate-value' => $scriptUrl . '&' . $elementName . '=${value}',
            ], true);
            return sprintf(
                '<select %s>%s</select>',
                $attributes,
                implode('', $options)
            );
        }
        return '';
    }

    /**
     * Returns a selector box to switch the view
     * Based on BackendUtility::getFuncMenu() but done as new function because it has another purpose.
     * Mingling with getFuncMenu would harm the docHeader Menu.
     *
     * @param mixed $mainParams The "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
     * @param string $elementName The form elements name, probably something like "SET[...]
     * @param string $currentValue The value to be selected currently.
     * @param array $menuItems An array with the menu items for the selector box
     * @param string $script The script to send the &id to, if empty it's automatically found
     * @param string $addParams Additional parameters to pass to the script.
     * @return string HTML code for selector box
     */
    public static function getDropdownMenu(
        $mainParams,
        $elementName,
        $currentValue,
        $menuItems,
        $script = '',
        $addParams = ''
    ) {
        if (!is_array($menuItems) || count($menuItems) <= 1) {
            return '';
        }
        $scriptUrl = self::buildScriptUrl($mainParams, $addParams, $script);
        $options = [];
        foreach ($menuItems as $value => $label) {
            $options[] = '<option value="'
                . htmlspecialchars($value) . '"'
                . ((string)$currentValue === (string)$value ? ' selected="selected"' : '') . '>'
                . htmlspecialchars($label, ENT_COMPAT, 'UTF-8', false) . '</option>';
        }
        $dataMenuIdentifier = str_replace(['SET[', ']'], '', $elementName);
        $dataMenuIdentifier = GeneralUtility::camelCaseToLowerCaseUnderscored($dataMenuIdentifier);
        $dataMenuIdentifier = str_replace('_', '-', $dataMenuIdentifier);
        if (!empty($options)) {
            // relies on module 'TYPO3/CMS/Backend/ActionDispatcher'
            $attributes = GeneralUtility::implodeAttributes([
                'name' => $elementName,
                'data-menu-identifier' => $dataMenuIdentifier,
                'data-global-event' => 'change',
                'data-action-navigate' => '$data=~s/$value/',
                'data-navigate-value' => $scriptUrl . '&' . $elementName . '=${value}',
            ], true);
            return '
			<div class="input-group">
				<!-- Function Menu of module -->
				<select class="form-select" ' . $attributes . '>
					' . implode(LF, $options) . '
				</select>
			</div>
						';
        }
        return '';
    }

    /**
     * Checkbox function menu.
     * Works like ->getFuncMenu() but takes no $menuItem array since this is a simple checkbox.
     *
     * @param mixed $mainParams $id is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
     * @param string $elementName The form elements name, probably something like "SET[...]
     * @param string|bool $currentValue The value to be selected currently.
     * @param string $script The script to send the &id to, if empty it's automatically found
     * @param string $addParams Additional parameters to pass to the script.
     * @param string $tagParams Additional attributes for the checkbox input tag
     * @return string HTML code for checkbox
     * @see getFuncMenu()
     */
    public static function getFuncCheck(
        $mainParams,
        $elementName,
        $currentValue,
        $script = '',
        $addParams = '',
        $tagParams = ''
    ) {
        // relies on module 'TYPO3/CMS/Backend/ActionDispatcher'
        $scriptUrl = self::buildScriptUrl($mainParams, $addParams, $script);
        $attributes = GeneralUtility::implodeAttributes([
            'type' => 'checkbox',
            'class' => 'form-check-input',
            'name' => $elementName,
            'value' => '1',
            'data-global-event' => 'change',
            'data-action-navigate' => '$data=~s/$value/',
            'data-navigate-value' => sprintf('%s&%s=${value}', $scriptUrl, $elementName),
            'data-empty-value' => '0',
        ], true);
        return
            '<input ' . $attributes .
            ($currentValue ? ' checked="checked"' : '') .
            ($tagParams ? ' ' . $tagParams : '') .
            ' />';
    }

    /**
     * Input field function menu
     * Works like ->getFuncMenu() / ->getFuncCheck() but displays an input field instead which updates the script "onchange"
     *
     * @param mixed $mainParams $id is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
     * @param string $elementName The form elements name, probably something like "SET[...]
     * @param string $currentValue The value to be selected currently.
     * @param int $size Relative size of input field, max is 48
     * @param string $script The script to send the &id to, if empty it's automatically found
     * @param string $addParams Additional parameters to pass to the script.
     * @return string HTML code for input text field.
     * @see getFuncMenu()
     * @todo not used at least since TYPO3 v9, drop in TYPO3 v12.0
     */
    public static function getFuncInput(
        $mainParams,
        $elementName,
        $currentValue,
        $size = 10,
        $script = '',
        $addParams = ''
    ) {
        $scriptUrl = self::buildScriptUrl($mainParams, $addParams, $script);
        $onChange = 'window.location.href = ' . GeneralUtility::quoteJSvalue($scriptUrl . '&' . $elementName . '=') . '+escape(this.value);';
        return '<input type="text" class="form-control" name="' . $elementName . '" value="' . htmlspecialchars($currentValue) . '" onchange="' . htmlspecialchars($onChange) . '" />';
    }

    /**
     * Builds the URL to the current script with given arguments
     *
     * @param mixed $mainParams $id is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
     * @param string $addParams Additional parameters to pass to the script.
     * @param string $script The script to send the &id to, if empty it's automatically found
     * @return string The complete script URL
     * @todo Check if this can be removed or replaced by routing
     */
    protected static function buildScriptUrl($mainParams, $addParams, $script = '')
    {
        if (!is_array($mainParams)) {
            $mainParams = ['id' => $mainParams];
        }

        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ($route = $GLOBALS['TYPO3_REQUEST']->getAttribute('route')) instanceof Route
        ) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $scriptUrl = (string)$uriBuilder->buildUriFromRoute($route->getOption('_identifier'), $mainParams);
            $scriptUrl .= $addParams;
        } else {
            if (!$script) {
                $script = PathUtility::basename(Environment::getCurrentScript());
            }
            $scriptUrl = $script . HttpUtility::buildQueryString($mainParams, '?') . $addParams;
        }

        return $scriptUrl;
    }

    /**
     * Call to update the page tree frame (or something else..?) after
     * use 'updatePageTree' as a first parameter will set the page tree to be updated.
     *
     * @param string $set Key to set the update signal. When setting, this value contains strings telling WHAT to set. At this point it seems that the value "updatePageTree" is the only one it makes sense to set. If empty, all update signals will be removed.
     * @param mixed $params Additional information for the update signal, used to only refresh a branch of the tree
     * @see BackendUtility::getUpdateSignalDetails()
     */
    public static function setUpdateSignal($set = '', $params = '')
    {
        $beUser = static::getBackendUserAuthentication();
        $modData = $beUser->getModuleData(
            BackendUtility::class . '::getUpdateSignal',
            'ses'
        );
        if ($set) {
            $modData[$set] = [
                'set' => $set,
                'parameter' => $params,
            ];
        } else {
            // clear the module data
            $modData = [];
        }
        $beUser->pushModuleData(BackendUtility::class . '::getUpdateSignal', $modData);
    }

    /**
     * Call to update the page tree frame (or something else..?) if this is set by the function
     * setUpdateSignal(). It will return some JavaScript that does the update
     *
     * @return string HTML javascript code
     * @see BackendUtility::setUpdateSignal()
     * @internal use getUpdateSignalDetails() instead, will be deprecated in TYPO3 v12.0
     */
    public static function getUpdateSignalCode()
    {
        $signals = [];
        $modData = static::getBackendUserAuthentication()->getModuleData(
            BackendUtility::class . '::getUpdateSignal',
            'ses'
        );
        if (empty($modData)) {
            return '';
        }
        // Hook: Allows to let TYPO3 execute your JS code
        $updateSignals = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook'] ?? [];
        // Loop through all setUpdateSignals and get the JS code
        foreach ($modData as $set => $val) {
            if (isset($updateSignals[$set])) {
                $params = ['set' => $set, 'parameter' => $val['parameter'], 'JScode' => ''];
                $ref = null;
                GeneralUtility::callUserFunction($updateSignals[$set], $params, $ref);
                $signals[] = $params['JScode'];
            } else {
                switch ($set) {
                    case 'updatePageTree':
                        $signals[] = '
								if (top) {
									top.document.dispatchEvent(new CustomEvent("typo3:pagetree:refresh"));
								}';
                        break;
                    case 'updateFolderTree':
                        $signals[] = '
								if (top) {
									top.document.dispatchEvent(new CustomEvent("typo3:filestoragetree:refresh"));
								}';
                        break;
                    case 'updateModuleMenu':
                        $signals[] = '
								if (top && top.TYPO3.ModuleMenu && top.TYPO3.ModuleMenu.App) {
									top.TYPO3.ModuleMenu.App.refreshMenu();
								}';
                        break;
                    case 'updateTopbar':
                        $signals[] = '
								if (top && top.TYPO3.Backend && top.TYPO3.Backend.Topbar) {
									top.TYPO3.Backend.Topbar.refresh();
								}';
                        break;
                }
            }
        }
        $content = implode(LF, $signals);
        // For backwards compatibility, should be replaced
        self::setUpdateSignal();
        return $content;
    }

    /**
     * Gets instructions for update signals (e.g. page tree shall be refreshed,
     * since some page title has been modified during the current HTTP request).
     *
     * @return array{html: list<string>, script: list<string>}
     * @see BackendUtility::setUpdateSignal()
     */
    public static function getUpdateSignalDetails(): array
    {
        $details = [
            'html' => [],
            // @todo deprecate inline JavaScript in TYPO3 v12.0
            'script' => [],
        ];
        $modData = static::getBackendUserAuthentication()->getModuleData(
            BackendUtility::class . '::getUpdateSignal',
            'ses'
        );
        if (empty($modData)) {
            return $details;
        }
        // Hook: Allows to let TYPO3 execute your JS code
        $updateSignals = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook'] ?? [];
        // Loop through all setUpdateSignals and get the JS code
        foreach ($modData as $set => $val) {
            if (isset($updateSignals[$set])) {
                $params = ['set' => $set, 'parameter' => $val['parameter'], 'JScode' => '', 'html' => ''];
                $ref = null;
                GeneralUtility::callUserFunction($updateSignals[$set], $params, $ref);
                // @todo verify and adjust documentation
                if (!empty($params['html'])) {
                    $details['html'][] = $params['html'];
                } elseif (!empty($params['JScode'])) {
                    // @todo deprecate in TYPO3 v12.0, avoid inline JavaScript
                    $details['script'][] = $params['JScode'];
                }
            } else {
                switch ($set) {
                    case 'updatePageTree':
                        $details['html'][] = ImmediateActionElement::dispatchCustomEvent(
                            'typo3:pagetree:refresh',
                            null,
                            true
                        );
                        break;
                    case 'updateFolderTree':
                        $details['html'][] = ImmediateActionElement::dispatchCustomEvent(
                            'typo3:filestoragetree:refresh',
                            null,
                            true
                        );
                        break;
                    case 'updateModuleMenu':
                        $details['html'][] = ImmediateActionElement::forAction(
                            'TYPO3.ModuleMenu.App.refreshMenu',
                        );
                        break;
                    case 'updateTopbar':
                        $details['html'][] = ImmediateActionElement::forAction(
                            'TYPO3.Backend.Topbar.refresh'
                        );
                        break;
                }
            }
        }
        // reset update signals
        self::setUpdateSignal();
        return $details;
    }

    /**
     * Returns an array which is most backend modules becomes MOD_SETTINGS containing values from function menus etc. determining the function of the module.
     * This is kind of session variable management framework for the backend users.
     * If a key from MOD_MENU is set in the CHANGED_SETTINGS array (eg. a value is passed to the script from the outside), this value is put into the settings-array
     * Ultimately, see Inside TYPO3 for how to use this function in relation to your modules.
     *
     * @param array $MOD_MENU MOD_MENU is an array that defines the options in menus.
     * @param array $CHANGED_SETTINGS CHANGED_SETTINGS represents the array used when passing values to the script from the menus.
     * @param string $modName modName is the name of this module. Used to get the correct module data.
     * @param string $type If type is 'ses' then the data is stored as session-lasting data. This means that it'll be wiped out the next time the user logs in.
     * @param string $dontValidateList dontValidateList can be used to list variables that should not be checked if their value is found in the MOD_MENU array. Used for dynamically generated menus.
     * @param string $setDefaultList List of default values from $MOD_MENU to set in the output array (only if the values from MOD_MENU are not arrays)
     * @throws \RuntimeException
     * @return array The array $settings, which holds a key for each MOD_MENU key and the values of each key will be within the range of values for each menuitem
     */
    public static function getModuleData(
        $MOD_MENU,
        $CHANGED_SETTINGS,
        $modName,
        $type = '',
        $dontValidateList = '',
        $setDefaultList = ''
    ) {
        if ($modName && is_string($modName)) {
            // Getting stored user-data from this module:
            $beUser = static::getBackendUserAuthentication();
            $settings = $beUser->getModuleData($modName, $type);
            $changed = 0;
            if (!is_array($settings)) {
                $changed = 1;
                $settings = [
                    'function' => null,
                    'language' => null,
                    'constant_editor_cat' => null,
                ];
            }
            if (is_array($MOD_MENU)) {
                foreach ($MOD_MENU as $key => $var) {
                    // If a global var is set before entering here. eg if submitted, then it's substituting the current value the array.
                    if (is_array($CHANGED_SETTINGS) && isset($CHANGED_SETTINGS[$key])) {
                        if (is_array($CHANGED_SETTINGS[$key])) {
                            $serializedSettings = serialize($CHANGED_SETTINGS[$key]);
                            if ((string)$settings[$key] !== $serializedSettings) {
                                $settings[$key] = $serializedSettings;
                                $changed = 1;
                            }
                        } else {
                            if ((string)($settings[$key] ?? '') !== (string)($CHANGED_SETTINGS[$key] ?? '')) {
                                $settings[$key] = $CHANGED_SETTINGS[$key];
                                $changed = 1;
                            }
                        }
                    }
                    // If the $var is an array, which denotes the existence of a menu, we check if the value is permitted
                    if (is_array($var) && (!$dontValidateList || !GeneralUtility::inList($dontValidateList, $key))) {
                        // If the setting is an array or not present in the menu-array, MOD_MENU, then the default value is inserted.
                        if (is_array($settings[$key] ?? null) || !isset($MOD_MENU[$key][$settings[$key] ?? null])) {
                            $settings[$key] = (string)key($var);
                            $changed = 1;
                        }
                    }
                    // Sets default values (only strings/checkboxes, not menus)
                    if ($setDefaultList && !is_array($var)) {
                        if (GeneralUtility::inList($setDefaultList, $key) && !isset($settings[$key])) {
                            $settings[$key] = (string)$var;
                        }
                    }
                }
            } else {
                throw new \RuntimeException('No menu', 1568119229);
            }
            if ($changed) {
                $beUser->pushModuleData($modName, $settings);
            }
            return $settings;
        }
        throw new \RuntimeException('Wrong module name "' . $modName . '"', 1568119221);
    }

    /*******************************************
     *
     * Core
     *
     *******************************************/
    /**
     * Unlock or Lock a record from $table with $uid
     * If $table and $uid is not set, then all locking for the current BE_USER is removed!
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int $pid Record pid
     * @internal
     */
    public static function lockRecords($table = '', $uid = 0, $pid = 0)
    {
        $beUser = static::getBackendUserAuthentication();
        if (isset($beUser->user['uid'])) {
            $userId = (int)$beUser->user['uid'];
            if ($table && $uid) {
                $fieldsValues = [
                    'userid' => $userId,
                    'feuserid' => 0,
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'record_table' => $table,
                    'record_uid' => $uid,
                    'username' => $beUser->user['username'],
                    'record_pid' => $pid,
                ];
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('sys_lockedrecords')
                    ->insert(
                        'sys_lockedrecords',
                        $fieldsValues
                    );
            } else {
                GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('sys_lockedrecords')
                    ->delete(
                        'sys_lockedrecords',
                        ['userid' => (int)$userId]
                    );
            }
        }
    }

    /**
     * Returns information about whether the record from table, $table, with uid, $uid is currently locked
     * (edited by another user - which should issue a warning).
     * Notice: Locking is not strictly carried out since locking is abandoned when other backend scripts
     * are activated - which means that a user CAN have a record "open" without having it locked.
     * So this just serves as a warning that counts well in 90% of the cases, which should be sufficient.
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @return array|bool
     * @internal
     */
    public static function isRecordLocked($table, $uid)
    {
        $runtimeCache = self::getRuntimeCache();
        $cacheId = 'backend-recordLocked';
        $recordLockedCache = $runtimeCache->get($cacheId);
        if ($recordLockedCache !== false) {
            $lockedRecords = $recordLockedCache;
        } else {
            $lockedRecords = [];

            $queryBuilder = static::getQueryBuilderForTable('sys_lockedrecords');
            $result = $queryBuilder
                ->select('*')
                ->from('sys_lockedrecords')
                ->where(
                    $queryBuilder->expr()->neq(
                        'sys_lockedrecords.userid',
                        $queryBuilder->createNamedParameter(
                            static::getBackendUserAuthentication()->user['uid'],
                            \PDO::PARAM_INT
                        )
                    ),
                    $queryBuilder->expr()->gt(
                        'sys_lockedrecords.tstamp',
                        $queryBuilder->createNamedParameter(
                            $GLOBALS['EXEC_TIME'] - 2 * 3600,
                            \PDO::PARAM_INT
                        )
                    )
                )
                ->executeQuery();

            $lang = static::getLanguageService();
            while ($row = $result->fetchAssociative()) {
                $row += [
                    'userid' => 0,
                    'record_pid' => 0,
                    'feuserid' => 0,
                    'username' => '',
                    'record_table' => '',
                    'record_uid' => 0,

                ];
                // Get the type of the user that locked this record:
                if ($row['userid']) {
                    $userTypeLabel = 'beUser';
                } elseif ($row['feuserid']) {
                    $userTypeLabel = 'feUser';
                } else {
                    $userTypeLabel = 'user';
                }
                $userType = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.' . $userTypeLabel);
                // Get the username (if available):
                $userName = ($row['username'] ?? '') ?: $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.unknownUser');

                $lockedRecords[$row['record_table'] . ':' . $row['record_uid']] = $row;
                $lockedRecords[$row['record_table'] . ':' . $row['record_uid']]['msg'] = sprintf(
                    $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.lockedRecordUser'),
                    $userType,
                    $userName,
                    self::calcAge(
                        $GLOBALS['EXEC_TIME'] - $row['tstamp'],
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                    )
                );
                if ($row['record_pid'] && !isset($lockedRecords[$row['record_table'] . ':' . $row['record_pid']])) {
                    $lockedRecords['pages:' . ($row['record_pid'] ?? '')]['msg'] = sprintf(
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.lockedRecordUser_content'),
                        $userType,
                        $userName,
                        self::calcAge(
                            $GLOBALS['EXEC_TIME'] - $row['tstamp'],
                            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                        )
                    );
                }
            }
            $runtimeCache->set($cacheId, $lockedRecords);
        }

        return $lockedRecords[$table . ':' . $uid] ?? false;
    }

    /**
     * Returns TSConfig for the TCEFORM object in Page TSconfig.
     * Used in TCEFORMs
     *
     * @param string $table Table name present in TCA
     * @param array $row Row from table
     * @return array
     */
    public static function getTCEFORM_TSconfig($table, $row)
    {
        $res = [];
        // Get main config for the table
        [$TScID, $cPid] = self::getTSCpid($table, $row['uid'] ?? 0, $row['pid'] ?? 0);
        if ($TScID >= 0) {
            $tsConfig = static::getPagesTSconfig($TScID)['TCEFORM.'][$table . '.'] ?? [];
            $typeVal = self::getTCAtypeValue($table, $row);
            foreach ($tsConfig as $key => $val) {
                if (is_array($val)) {
                    $fieldN = substr($key, 0, -1);
                    $res[$fieldN] = $val;
                    unset($res[$fieldN]['types.']);
                    if ((string)$typeVal !== '' && is_array($val['types.'][$typeVal . '.'] ?? false)) {
                        ArrayUtility::mergeRecursiveWithOverrule($res[$fieldN], $val['types.'][$typeVal . '.']);
                    }
                }
            }
        }
        $res['_CURRENT_PID'] = $cPid;
        $res['_THIS_UID'] = $row['uid'] ?? 0;
        // So the row will be passed to foreign_table_where_query()
        $res['_THIS_ROW'] = $row;
        return $res;
    }

    /**
     * Find the real PID of the record (with $uid from $table).
     * This MAY be impossible if the pid is set as a reference to the former record or a page (if two records are created at one time).
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int|string $pid Record pid, could be negative then pointing to a record from same table whose pid to find and return
     * @return int|null
     * @internal
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::copyRecord()
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::getTSCpid()
     */
    public static function getTSconfig_pidValue($table, $uid, $pid)
    {
        // If pid is an integer this takes precedence in our lookup.
        if (MathUtility::canBeInterpretedAsInteger($pid)) {
            $thePidValue = (int)$pid;
            // If ref to another record, look that record up.
            if ($thePidValue < 0) {
                $pidRec = self::getRecord($table, abs($thePidValue), 'pid');
                $thePidValue = is_array($pidRec) ? $pidRec['pid'] : -2;
            }
        } else {
            // Try to fetch the record pid from uid. If the uid is 'NEW...' then this will of course return nothing
            $rr = self::getRecord($table, $uid);
            $thePidValue = null;
            if (is_array($rr)) {
                // First check if the t3ver_oid value is greater 0, which means
                // it is a workspace element. If so, get the "real" record:
                if ((int)($rr['t3ver_oid'] ?? 0) > 0) {
                    $rr = self::getRecord($table, $rr['t3ver_oid'], 'pid');
                    if (is_array($rr)) {
                        $thePidValue = $rr['pid'];
                    }
                } else {
                    // Returning the "pid" of the record
                    $thePidValue = $rr['pid'];
                }
            }
            if (!$thePidValue) {
                // Returns -1 if the record with this pid was not found.
                $thePidValue = -1;
            }
        }
        return $thePidValue;
    }

    /**
     * Return the real pid of a record and caches the result.
     * The non-cached method needs database queries to do the job, so this method
     * can be used if code sometimes calls the same record multiple times to save
     * some queries. This should not be done if the calling code may change the
     * same record meanwhile.
     *
     * @param string $table Tablename
     * @param string $uid UID value
     * @param string $pid PID value
     * @return array Array of two integers; first is the real PID of a record, second is the PID value for TSconfig.
     */
    public static function getTSCpidCached($table, $uid, $pid)
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $firstLevelCache = $runtimeCache->get('backendUtilityTscPidCached') ?: [];
        $key = $table . ':' . $uid . ':' . $pid;
        if (!isset($firstLevelCache[$key])) {
            $firstLevelCache[$key] = static::getTSCpid($table, (int)$uid, (int)$pid);
            $runtimeCache->set('backendUtilityTscPidCached', $firstLevelCache);
        }
        return $firstLevelCache[$key];
    }

    /**
     * Returns the REAL pid of the record, if possible. If both $uid and $pid is strings, then pid=-1 is returned as an error indication.
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int|string $pid Record pid
     * @return array Array of two integers; first is the REAL PID of a record and if its a new record negative values are resolved to the true PID,
     * second value is the PID value for TSconfig (uid if table is pages, otherwise the pid)
     * @internal
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::setHistory()
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::process_datamap()
     */
    public static function getTSCpid($table, $uid, $pid)
    {
        // If pid is negative (referring to another record) the pid of the other record is fetched and returned.
        $cPid = self::getTSconfig_pidValue($table, $uid, $pid);
        // $TScID is the id of $table = pages, else it's the pid of the record.
        $TScID = $table === 'pages' && MathUtility::canBeInterpretedAsInteger($uid) ? $uid : $cPid;
        return [$TScID, $cPid];
    }

    /**
     * Returns soft-reference parser for the softRef processing type
     * Usage: $softRefObj = BackendUtility::softRefParserObj('[parser key]');
     *
     * @param string $spKey softRef parser key
     * @return mixed If available, returns Soft link parser object, otherwise false.
     * @internal should only be used from within TYPO3 Core
     * @deprecated will be removed in TYPO3 v12.0. Use SoftReferenceParserFactory->getSoftReferenceParser instead.
     */
    public static function softRefParserObj($spKey)
    {
        trigger_error(
            'BackendUtility::softRefParserObj will be removed in TYPO3 v12.0, use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory->getSoftReferenceParser instead.',
            E_USER_DEPRECATED
        );

        $className = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser'][$spKey] ?? false;
        $obj = null;
        if ($className) {
            $obj = GeneralUtility::makeInstance($className);
        }
        if (!$obj) {
            try {
                $obj = GeneralUtility::makeInstance(SoftReferenceParserFactory::class)->getSoftReferenceParser($spKey);
            } catch (\OutOfBoundsException|\InvalidArgumentException $e) {
                return false;
            }
        }
        // Build a wrapper to call the API with the legacy findRef() call.
        if ($obj) {
            if ($obj instanceof SoftReferenceParserInterface && !method_exists($obj, 'findRef')) {
                // Build a temporary class acting as a wrapper to call findRef() with the new API.
                return new class($obj) {
                    private SoftReferenceParserInterface $parser;

                    public function __construct(SoftReferenceParserInterface $obj)
                    {
                        $this->parser = $obj;
                    }

                    public function findRef($table, $field, $uid, $content, $parserKey, $spParams, $structurePath)
                    {
                        $this->parser->setParserKey($parserKey, $spParams);
                        return $this->parser->parse($table, $field, $uid, $content, $structurePath)->toNullableArray();
                    }
                };
            }
            return $obj;
        }
        return false;
    }

    /**
     * Gets an instance of the runtime cache.
     *
     * @return FrontendInterface
     */
    protected static function getRuntimeCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }

    /**
     * Returns array of soft parser references
     *
     * @param string $parserList softRef parser list
     * @return array|bool Array where the parser key is the key and the value is the parameter string, FALSE if no parsers were found
     * @throws \InvalidArgumentException
     * @internal should only be used from within TYPO3 Core
     * @deprecated will be removed in TYPO3 v12.0. Use SoftReferenceParserFactory->getParsersBySoftRefParserList instead.
     */
    public static function explodeSoftRefParserList($parserList)
    {
        trigger_error(
            'BackendUtility::explodeSoftRefParserList will be removed in TYPO3 v12.0, use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory->getParsersBySoftRefParserList instead.',
            E_USER_DEPRECATED
        );

        // Return immediately if list is blank:
        if ((string)$parserList === '') {
            return false;
        }

        $runtimeCache = self::getRuntimeCache();
        $cacheId = 'backend-softRefList-' . md5($parserList);
        $parserListCache = $runtimeCache->get($cacheId);
        if ($parserListCache !== false) {
            return $parserListCache;
        }

        // Otherwise parse the list:
        $keyList = GeneralUtility::trimExplode(',', $parserList, true);
        $output = [];
        foreach ($keyList as $val) {
            $reg = [];
            if (preg_match('/^([[:alnum:]_-]+)\\[(.*)\\]$/', $val, $reg)) {
                $output[$reg[1]] = GeneralUtility::trimExplode(';', $reg[2], true);
            } else {
                $output[$val] = '';
            }
        }
        $runtimeCache->set($cacheId, $output);
        return $output;
    }

    /**
     * Returns TRUE if $modName is set and is found as a main- or submodule in $TBE_MODULES array
     *
     * @param string $modName Module name
     * @return bool
     */
    public static function isModuleSetInTBE_MODULES($modName)
    {
        $loaded = [];
        foreach ($GLOBALS['TBE_MODULES'] as $mkey => $list) {
            $loaded[$mkey] = 1;
            if (!is_array($list) && trim($list)) {
                $subList = GeneralUtility::trimExplode(',', $list, true);
                foreach ($subList as $skey) {
                    $loaded[$mkey . '_' . $skey] = 1;
                }
            }
        }
        return $modName && isset($loaded[$modName]);
    }

    /**
     * Counting references to a record/file
     *
     * @param string $table Table name (or "_FILE" if its a file)
     * @param string $ref Reference: If table, then int-uid, if _FILE, then file reference (relative to Environment::getPublicPath())
     * @param string $msg Message with %s, eg. "There were %s records pointing to this file!
     * @param string|int|null $count Reference count
     * @return string|int Output string (or int count value if no msg string specified)
     */
    public static function referenceCount($table, $ref, $msg = '', $count = null)
    {
        if ($count === null) {

            // Build base query
            $queryBuilder = static::getQueryBuilderForTable('sys_refindex');
            $queryBuilder
                ->count('*')
                ->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR))
                );

            // Look up the path:
            if ($table === '_FILE') {
                if (!str_starts_with($ref, Environment::getPublicPath())) {
                    return '';
                }

                $ref = PathUtility::stripPathSitePrefix($ref);
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq('ref_string', $queryBuilder->createNamedParameter($ref, \PDO::PARAM_STR))
                );
            } else {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($ref, \PDO::PARAM_INT))
                );
                if ($table === 'sys_file') {
                    $queryBuilder->andWhere($queryBuilder->expr()->neq('tablename', $queryBuilder->quote('sys_file_metadata')));
                }
            }

            $count = $queryBuilder->executeQuery()->fetchOne();
        }

        if ($count) {
            return $msg ? sprintf($msg, $count) : $count;
        }
        return $msg ? '' : 0;
    }

    /**
     * Counting translations of records
     *
     * @param string $table Table name
     * @param string $ref Reference: the record's uid
     * @param string $msg Message with %s, eg. "This record has %s translation(s) which will be deleted, too!
     * @return string Output string (or int count value if no msg string specified)
     */
    public static function translationCount($table, $ref, $msg = '')
    {
        $count = 0;
        if (($GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null)
            && ($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null)
        ) {
            $queryBuilder = static::getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $count = (int)$queryBuilder
                ->count('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($ref, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        $GLOBALS['TCA'][$table]['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchOne();
        }

        if ($count > 0) {
            return $msg ? sprintf($msg, $count) : (string)$count;
        }
        return $msg ? '' : '0';
    }

    /*******************************************
     *
     * Workspaces / Versioning
     *
     *******************************************/
    /**
     * Select all versions of a record, ordered by latest created version (uid DESC)
     *
     * @param string $table Table name to select from
     * @param int $uid Record uid for which to find versions.
     * @param string $fields Field list to select
     * @param int|null $workspace Search in workspace ID and Live WS, if 0 search only in LiveWS, if NULL search in all WS.
     * @param bool $includeDeletedRecords If set, deleted-flagged versions are included! (Only for clean-up script!)
     * @param array $row The current record
     * @return array|null Array of versions of table/uid
     * @internal should only be used from within TYPO3 Core
     */
    public static function selectVersionsOfRecord(
        $table,
        $uid,
        $fields = '*',
        $workspace = 0,
        $includeDeletedRecords = false,
        $row = null
    ) {
        if (!static::isTableWorkspaceEnabled($table)) {
            return null;
        }

        $outputRows = [];
        if (is_array($row) && !$includeDeletedRecords) {
            $row['_CURRENT_VERSION'] = true;
            $outputRows[] = $row;
        } else {
            // Select UID version:
            $row = self::getRecord($table, $uid, $fields, '', !$includeDeletedRecords);
            // Add rows to output array:
            if ($row) {
                $row['_CURRENT_VERSION'] = true;
                $outputRows[] = $row;
            }
        }

        $queryBuilder = static::getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        // build fields to select
        $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields));

        $queryBuilder
            ->from($table)
            ->where(
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            )
            ->orderBy('uid', 'DESC');

        if (!$includeDeletedRecords) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }

        if ($workspace === 0) {
            // Only in Live WS
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            );
        } elseif ($workspace !== null) {
            // In Live WS and Workspace with given ID
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter([0, (int)$workspace], Connection::PARAM_INT_ARRAY)
                )
            );
        }

        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        // Add rows to output array:
        if (is_array($rows)) {
            $outputRows = array_merge($outputRows, $rows);
        }
        return $outputRows;
    }

    /**
     * Find page-tree PID for versionized record
     * Used whenever you are tracking something back, like making the root line.
     * Will only translate if the workspace of the input record matches that of the current user (unless flag set)
     * Principle; Record offline! => Find online?
     *
     * If the record had its pid corrected to the online versions pid,
     * then "_ORIG_pid" is set for moved records to the PID of the moved location.
     *
     * @param string $table Table name
     * @param array $rr Record array passed by reference. As minimum, "pid" and "uid" fields must exist! "t3ver_oid", "t3ver_state" and "t3ver_wsid" is nice and will save you a DB query.
     * @param bool $ignoreWorkspaceMatch Ignore workspace match
     * @see PageRepository::fixVersioningPid()
     * @internal should only be used from within TYPO3 Core
     * @deprecated will be removed in TYPO3 v12, use workspaceOL() or getRecordWSOL() directly to achieve the same result.
     */
    public static function fixVersioningPid($table, &$rr, $ignoreWorkspaceMatch = false)
    {
        trigger_error('BackendUtility::fixVersioningPid() will be removed in TYPO3 v12, use BackendUtility::workspaceOL() or BackendUtility::getRecordWSOL() directly.', E_USER_DEPRECATED);
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            return;
        }
        if (!static::isTableWorkspaceEnabled($table)) {
            return;
        }
        // Check that the input record is an offline version from a table that supports versioning
        if (!is_array($rr)) {
            return;
        }
        $incomingPid = $rr['pid'] ?? null;
        // Check values for t3ver_oid and t3ver_wsid:
        if (isset($rr['t3ver_oid']) && isset($rr['t3ver_wsid']) && isset($rr['t3ver_state'])) {
            // If "t3ver_oid" is already a field, just set this:
            $oid = $rr['t3ver_oid'];
            $workspaceId = (int)$rr['t3ver_wsid'];
            $versionState = (int)$rr['t3ver_state'];
        } else {
            $oid = 0;
            $workspaceId = 0;
            $versionState = 0;
            // Otherwise we have to expect "uid" to be in the record and look up based on this:
            $newPidRec = self::getRecord($table, $rr['uid'], 'pid,t3ver_oid,t3ver_wsid,t3ver_state');
            if (is_array($newPidRec)) {
                $incomingPid = $newPidRec['pid'];
                $oid = $newPidRec['t3ver_oid'];
                $workspaceId = $newPidRec['t3ver_wsid'];
                $versionState = $newPidRec['t3ver_state'];
            }
        }
        if ($oid && ($ignoreWorkspaceMatch || (static::getBackendUserAuthentication() instanceof BackendUserAuthentication && $workspaceId === (int)static::getBackendUserAuthentication()->workspace))) {
            // Use moved PID in case of move pointer
            if ($versionState === VersionState::MOVE_POINTER) {
                if ($incomingPid !== null) {
                    $movedPageIdInWorkspace = $incomingPid;
                } else {
                    $versionedMovePointer = self::getRecord($table, $rr['uid'], 'pid');
                    $movedPageIdInWorkspace = $versionedMovePointer['pid'];
                }
                $rr['_ORIG_pid'] = $incomingPid;
                $rr['pid'] = $movedPageIdInWorkspace;
            }
        }
    }

    /**
     * Workspace Preview Overlay.
     *
     * Generally ALWAYS used when records are selected based on uid or pid.
     * Principle; Record online! => Find offline?
     * The function MAY set $row to FALSE. This happens if a moved record is given and
     * $unsetMovePointers is set to true. In other words, you should check if the input record
     * is still an array afterwards when using this function.
     *
     * If the versioned record is a moved record the "pid" value will then contain the newly moved location
     * and "ORIG_pid" will contain the live pid.
     *
     * @param string $table Table name
     * @param array $row Record by reference. At least "uid", "pid", "t3ver_oid" and "t3ver_state" must be set. Keys not prefixed with '_' are used as field names in SQL.
     * @param int $wsid Workspace ID, if not specified will use static::getBackendUserAuthentication()->workspace
     * @param bool $unsetMovePointers If TRUE the function does not return a "pointer" row for moved records in a workspace
     * @see fixVersioningPid()
     */
    public static function workspaceOL($table, &$row, $wsid = -99, $unsetMovePointers = false)
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces') || !is_array($row) || !static::isTableWorkspaceEnabled($table)) {
            return;
        }

        // Initialize workspace ID
        $wsid = (int)$wsid;
        if ($wsid === -99 && static::getBackendUserAuthentication() instanceof BackendUserAuthentication) {
            $wsid = (int)static::getBackendUserAuthentication()->workspace;
        }
        if ($wsid === 0) {
            // Return early if in live workspace
            return;
        }

        // Check if input record is a moved record
        $incomingRecordIsAMoveVersion = false;
        if (isset($row['t3ver_oid'], $row['t3ver_state'])
            && $row['t3ver_oid'] > 0
            && (int)$row['t3ver_state'] === VersionState::MOVE_POINTER
        ) {
            // @todo: This handling needs a review, together with the 4th param $unsetMovePointers
            $incomingRecordIsAMoveVersion = true;
        }

        $wsAlt = self::getWorkspaceVersionOfRecord(
            $wsid,
            $table,
            $row['uid'],
            implode(',', static::purgeComputedPropertyNames(array_keys($row)))
        );

        // If version was found, swap the default record with that one.
        if (is_array($wsAlt)) {
            // If t3ver_state is not found, then find it... (but we like best if it is here...)
            if (!isset($wsAlt['t3ver_state'])) {
                $stateRec = self::getRecord($table, $wsAlt['uid'], 't3ver_state');
                $versionState = VersionState::cast($stateRec['t3ver_state']);
            } else {
                $versionState = VersionState::cast($wsAlt['t3ver_state']);
            }
            // Check if this is in move-state
            if ($versionState->equals(VersionState::MOVE_POINTER)) {
                // @todo Same problem as frontend in versionOL(). See TODO point there and todo above.
                if (!$incomingRecordIsAMoveVersion && $unsetMovePointers) {
                    $row = false;
                    return;
                }
                // When a moved record is found the "PID" value contains the newly moved location
                // Whereas the _ORIG_pid field contains the PID of the live version
                $wsAlt['_ORIG_pid'] = $row['pid'];
            }
            // Swap UID
            if (!$versionState->equals(VersionState::NEW_PLACEHOLDER)) {
                $wsAlt['_ORIG_uid'] = $wsAlt['uid'];
                $wsAlt['uid'] = $row['uid'];
            }
            // Backend css class:
            $wsAlt['_CSSCLASS'] = 'ver-element';
            // Changing input record to the workspace version alternative:
            $row = $wsAlt;
        }
    }

    /**
     * Select the workspace version of a record, if exists
     *
     * @param int $workspace Workspace ID
     * @param string $table Table name to select from
     * @param int $uid Record uid for which to find workspace version.
     * @param string $fields Field list to select
     * @return array|bool If found, return record, otherwise false
     */
    public static function getWorkspaceVersionOfRecord($workspace, $table, $uid, $fields = '*')
    {
        if (ExtensionManagementUtility::isLoaded('workspaces')) {
            if ($workspace !== 0 && self::isTableWorkspaceEnabled($table)) {

                // Select workspace version of record:
                $queryBuilder = static::getQueryBuilderForTable($table);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

                // build fields to select
                $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields));

                $row = $queryBuilder
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->eq(
                            't3ver_wsid',
                            $queryBuilder->createNamedParameter($workspace, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->orX(
                            // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                            $queryBuilder->expr()->andX(
                                $queryBuilder->expr()->eq(
                                    'uid',
                                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                                ),
                                $queryBuilder->expr()->eq(
                                    't3ver_state',
                                    $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER, \PDO::PARAM_INT)
                                )
                            ),
                            $queryBuilder->expr()->eq(
                                't3ver_oid',
                                $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                            )
                        )
                    )
                    ->executeQuery()
                    ->fetchAssociative();

                return $row;
            }
        }
        return false;
    }

    /**
     * Returns live version of record
     *
     * @param string $table Table name
     * @param int|string $uid Record UID of draft, offline version
     * @param string $fields Field list, default is *
     * @return array|null If found, the record, otherwise NULL
     */
    public static function getLiveVersionOfRecord($table, $uid, $fields = '*')
    {
        $liveVersionId = self::getLiveVersionIdOfRecord($table, $uid);
        if ($liveVersionId !== null) {
            return self::getRecord($table, $liveVersionId, $fields);
        }
        return null;
    }

    /**
     * Gets the id of the live version of a record.
     *
     * @param string $table Name of the table
     * @param int|string $uid Uid of the offline/draft record
     * @return int|null The id of the live version of the record (or NULL if nothing was found)
     * @internal should only be used from within TYPO3 Core
     */
    public static function getLiveVersionIdOfRecord($table, $uid)
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            return null;
        }
        $liveVersionId = null;
        if (self::isTableWorkspaceEnabled($table)) {
            $currentRecord = self::getRecord($table, $uid, 'pid,t3ver_oid,t3ver_state');
            if (is_array($currentRecord)) {
                if ((int)$currentRecord['t3ver_oid'] > 0) {
                    $liveVersionId = $currentRecord['t3ver_oid'];
                } elseif ((int)($currentRecord['t3ver_state']) === VersionState::NEW_PLACEHOLDER) {
                    // New versions do not have a live counterpart
                    $liveVersionId = (int)$uid;
                }
            }
        }
        return $liveVersionId;
    }

    /**
     * Performs mapping of new uids to new versions UID in case of import inside a workspace.
     *
     * @param string $table Table name
     * @param int $uid Record uid (of live record placeholder)
     * @return int Uid of offline version if any, otherwise live uid.
     * @internal should only be used from within TYPO3 Core
     */
    public static function wsMapId($table, $uid)
    {
        $wsRec = null;
        if (static::getBackendUserAuthentication() instanceof BackendUserAuthentication) {
            $wsRec = self::getWorkspaceVersionOfRecord(
                static::getBackendUserAuthentication()->workspace,
                $table,
                $uid,
                'uid'
            );
        }
        return is_array($wsRec) ? $wsRec['uid'] : $uid;
    }

    /*******************************************
     *
     * Miscellaneous
     *
     *******************************************/

    /**
     * Creates ADMCMD parameters for the "viewpage" extension / frontend
     *
     * @param array $pageInfo Page record
     * @param \TYPO3\CMS\Core\Context\Context $context
     * @return string Query-parameters
     * @internal
     */
    public static function ADMCMD_previewCmds($pageInfo, Context $context)
    {
        if ($pageInfo === []) {
            return '';
        }
        // Initialize access restriction values from current page
        $access = [
            'fe_group' => (string)($pageInfo['fe_group'] ?? ''),
            'starttime' => (int)($pageInfo['starttime'] ?? 0),
            'endtime' => (int)($pageInfo['endtime'] ?? 0),
        ];
        // Only check rootline if the current page has not set extendToSubpages itself
        if (!(bool)($pageInfo['extendToSubpages'] ?? false)) {
            $rootline = self::BEgetRootLine((int)($pageInfo['uid'] ?? 0));
            // remove the current page from the rootline
            array_shift($rootline);
            foreach ($rootline as $page) {
                // Skip root node, invalid pages and pages which do not define extendToSubpages
                if ((int)($page['uid'] ?? 0) <= 0 || !(bool)($page['extendToSubpages'] ?? false)) {
                    continue;
                }
                $access['fe_group'] = (string)($page['fe_group'] ?? '');
                $access['starttime'] = (int)($page['starttime'] ?? 0);
                $access['endtime'] = (int)($page['endtime'] ?? 0);
                // Stop as soon as a page in the rootline has extendToSubpages set
                break;
            }
        }
        $simUser = '';
        $simTime = '';
        if ((int)$access['fe_group'] === -2) {
            // -2 means "show at any login". We simulate first available fe_group.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('fe_groups');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(HiddenRestriction::class));

            $activeFeGroupId = $queryBuilder->select('uid')
                ->from('fe_groups')
                ->executeQuery()
                ->fetchOne();

            if ($activeFeGroupId) {
                $simUser = '&ADMCMD_simUser=' . $activeFeGroupId;
            }
        } elseif (!empty($access['fe_group'])) {
            $simUser = '&ADMCMD_simUser=' . $access['fe_group'];
        }
        if ($access['starttime'] > $GLOBALS['EXEC_TIME']) {
            // simulate access time to ensure PageRepository will find the page and in turn PageRouter will generate
            // an URL for it
            $dateAspect = GeneralUtility::makeInstance(DateTimeAspect::class, new \DateTimeImmutable('@' . $access['starttime']));
            $context->setAspect('date', $dateAspect);
            $simTime = '&ADMCMD_simTime=' . $access['starttime'];
        }
        if ($access['endtime'] < $GLOBALS['EXEC_TIME'] && $access['endtime'] !== 0) {
            // Set access time to page's endtime subtracted one second to ensure PageRepository will find the page and
            // in turn PageRouter will generate an URL for it
            $dateAspect = GeneralUtility::makeInstance(
                DateTimeAspect::class,
                new \DateTimeImmutable('@' . ($access['endtime'] - 1))
            );
            $context->setAspect('date', $dateAspect);
            $simTime = '&ADMCMD_simTime=' . ($access['endtime'] - 1);
        }
        return $simUser . $simTime;
    }

    /**
     * Determines whether a table is enabled for workspaces.
     *
     * @param string $table Name of the table to be checked
     * @return bool
     */
    public static function isTableWorkspaceEnabled($table)
    {
        return !empty($GLOBALS['TCA'][$table]['ctrl']['versioningWS']);
    }

    /**
     * Gets the TCA configuration of a field.
     *
     * @param string $table Name of the table
     * @param string $field Name of the field
     * @return array
     */
    public static function getTcaFieldConfiguration($table, $field)
    {
        $configuration = [];
        if (isset($GLOBALS['TCA'][$table]['columns'][$field]['config'])) {
            $configuration = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        }
        return $configuration;
    }

    /**
     * Whether to ignore restrictions on a web-mount of a table.
     * The regular behaviour is that records to be accessed need to be
     * in a valid user's web-mount.
     *
     * @param string $table Name of the table
     * @return bool
     */
    public static function isWebMountRestrictionIgnored($table)
    {
        return !empty($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreWebMountRestriction']);
    }

    /**
     * Whether to ignore restrictions on root-level records.
     * The regular behaviour is that records on the root-level (page-id 0)
     * only can be accessed by admin users.
     *
     * @param string $table Name of the table
     * @return bool
     */
    public static function isRootLevelRestrictionIgnored($table)
    {
        return !empty($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreRootLevelRestriction']);
    }

    /**
     * Get all fields of a table, which are allowed for the current user
     *
     * @param string $table Table name
     * @param bool $checkUserAccess If set, users access to the field (non-exclude-fields) is checked.
     * @return string[] Array, where values are fieldnames
     * @internal should only be used from within TYPO3 Core
     */
    public static function getAllowedFieldsForTable(string $table, bool $checkUserAccess = true): array
    {
        if (!is_array($GLOBALS['TCA'][$table]['columns'] ?? null)) {
            self::getLogger()->error('TCA is broken for the table "' . $table . '": no required "columns" entry in TCA.');
            return [];
        }

        $fieldList = [];
        $backendUser = self::getBackendUserAuthentication();

        // Traverse configured columns and add them to field array, if available for user.
        foreach ($GLOBALS['TCA'][$table]['columns'] as $fieldName => $fieldValue) {
            if (($fieldValue['config']['type'] ?? '') === 'none') {
                // Never render or fetch type=none fields from db
                continue;
            }
            if (!$checkUserAccess
                || (
                    (
                        !($fieldValue['exclude'] ?? null)
                        || $backendUser->check('non_exclude_fields', $table . ':' . $fieldName)
                    )
                    && ($fieldValue['config']['type'] ?? '') !== 'passthrough'
                )
            ) {
                $fieldList[] = $fieldName;
            }
        }

        $fieldList[] = 'uid';
        $fieldList[] = 'pid';

        // Add more special fields (e.g. date fields) if user should not be checked or is admin
        if (!$checkUserAccess || $backendUser->isAdmin()) {
            if ($GLOBALS['TCA'][$table]['ctrl']['tstamp'] ?? false) {
                $fieldList[] = $GLOBALS['TCA'][$table]['ctrl']['tstamp'];
            }
            if ($GLOBALS['TCA'][$table]['ctrl']['crdate'] ?? false) {
                $fieldList[] = $GLOBALS['TCA'][$table]['ctrl']['crdate'];
            }
            if ($GLOBALS['TCA'][$table]['ctrl']['cruser_id'] ?? false) {
                $fieldList[] = $GLOBALS['TCA'][$table]['ctrl']['cruser_id'];
            }
            if ($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? false) {
                $fieldList[] = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
            }
            if (self::isTableWorkspaceEnabled($table)) {
                $fieldList[] = 't3ver_state';
                $fieldList[] = 't3ver_wsid';
                $fieldList[] = 't3ver_oid';
            }
        }

        // Return unique field list
        return array_values(array_unique($fieldList));
    }

    /**
     * @param string $table
     * @return Connection
     */
    protected static function getConnectionForTable($table)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    protected static function getQueryBuilderForTable($table)
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
    }

    /**
     * @return LoggerInterface
     */
    protected static function getLogger()
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * @return LanguageService
     */
    protected static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication|null
     */
    protected static function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
