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

namespace TYPO3\CMS\Core\Domain\Repository;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Error\Http\ShortcutTargetPageNotFoundException;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Page functions, a lot of sql/pages-related functions
 *
 * Mainly used in the frontend but also in some cases in the backend. It's
 * important to set the right $where_hid_del in the object so that the
 * functions operate properly
 * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::fetch_the_id()
 */
class PageRepository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * This is not the final clauses. There will normally be conditions for the
     * hidden, starttime and endtime fields as well. You MUST initialize the object
     * by the init() function
     *
     * @var string
     */
    public $where_hid_del = ' AND pages.deleted=0';

    /**
     * Clause for fe_group access
     *
     * @var string
     */
    public $where_groupAccess = '';

    /**
     * Can be migrated away later to use context API directly.
     *
     * @var int
     */
    protected $sys_language_uid = 0;

    /**
     * Can be migrated away later to use context API directly.
     * Workspace ID for preview
     * If > 0, versioning preview of other record versions is allowed. THIS MUST
     * ONLY BE SET IF the page is not cached and truly previewed by a backend
     * user!
     *
     * @var int
     */
    protected $versioningWorkspaceId = 0;

    /**
     * Computed properties that are added to database rows.
     *
     * @var array
     */
    protected $computedPropertyNames = [
        '_LOCALIZED_UID',
        '_MP_PARAM',
        '_ORIG_uid',
        '_ORIG_pid',
        '_SHORTCUT_ORIGINAL_PAGE_UID',
        '_PAGES_OVERLAY',
        '_PAGES_OVERLAY_UID',
        '_PAGES_OVERLAY_LANGUAGE',
        '_PAGES_OVERLAY_REQUESTEDLANGUAGE',
    ];

    /**
     * Named constants for "magic numbers" of the field doktype
     */
    const DOKTYPE_DEFAULT = 1;
    const DOKTYPE_LINK = 3;
    const DOKTYPE_SHORTCUT = 4;
    const DOKTYPE_BE_USER_SECTION = 6;
    const DOKTYPE_MOUNTPOINT = 7;
    const DOKTYPE_SPACER = 199;
    const DOKTYPE_SYSFOLDER = 254;
    const DOKTYPE_RECYCLER = 255;

    /**
     * Named constants for "magic numbers" of the field shortcut_mode
     */
    const SHORTCUT_MODE_NONE = 0;
    const SHORTCUT_MODE_FIRST_SUBPAGE = 1;
    const SHORTCUT_MODE_RANDOM_SUBPAGE = 2;
    const SHORTCUT_MODE_PARENT_PAGE = 3;

    /**
     * @var Context
     */
    protected $context;

    /**
     * PageRepository constructor to set the base context, this will effectively remove the necessity for
     * setting properties from the outside.
     *
     * @param Context $context
     */
    public function __construct(Context $context = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->versioningWorkspaceId = $this->context->getPropertyFromAspect('workspace', 'id');
        // Only set up the where clauses for pages when TCA is set. This usually happens only in tests.
        // Once all tests are written very well, this can be removed again
        if (isset($GLOBALS['TCA']['pages'])) {
            $this->init($this->context->getPropertyFromAspect('visibility', 'includeHiddenPages'));
            $this->where_groupAccess = $this->getMultipleGroupsWhereClause('pages.fe_group', 'pages');
            $this->sys_language_uid = (int)$this->context->getPropertyFromAspect('language', 'id', 0);
        }
    }

    /**
     * init() MUST be run directly after creating a new template-object
     * This sets the internal variable $this->where_hid_del to the correct where
     * clause for page records taking deleted/hidden/starttime/endtime/t3ver_state
     * into account
     *
     * @param bool $show_hidden If $show_hidden is TRUE, the hidden-field is ignored!! Normally this should be FALSE. Is used for previewing.
     * @internal
     */
    protected function init($show_hidden)
    {
        $this->where_groupAccess = '';
        // As PageRepository may be used multiple times during the frontend request, and may
        // actually be used before the usergroups have been resolved, self::getMultipleGroupsWhereClause()
        // and the hook in ->enableFields() need to be reconsidered when the usergroup state changes.
        // When something changes in the context, a second runtime cache entry is built.
        // However, the PageRepository is generally in use for generating e.g. hundreds of links, so they would all use
        // the same cache identifier.
        $userAspect = $this->context->getAspect('frontend.user');
        $frontendUserIdentifier = 'user_' . (int)$userAspect->get('id') . '_groups_' . md5(implode(',', $userAspect->getGroupIds()));

        // We need to respect the date aspect as we might have subrequests with a different time (e.g. backend preview links)
        $dateTimeIdentifier = $this->context->getAspect('date')->get('timestamp');

        $cache = $this->getRuntimeCache();
        $cacheIdentifier = 'PageRepository_hidDelWhere' . ($show_hidden ? 'ShowHidden' : '') . '_' . (int)$this->versioningWorkspaceId . '_' . $frontendUserIdentifier . '_' . $dateTimeIdentifier;
        $cacheEntry = $cache->get($cacheIdentifier);
        if ($cacheEntry) {
            $this->where_hid_del = $cacheEntry;
        } else {
            $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages')
                ->expr();
            if ($this->versioningWorkspaceId > 0) {
                // For version previewing, make sure that enable-fields are not
                // de-selecting hidden pages - we need versionOL() to unset them only
                // if the overlay record instructs us to.
                // Clear where_hid_del and restrict to live and current workspaces
                $this->where_hid_del = ' AND ' . $expressionBuilder->andX(
                    $expressionBuilder->eq('pages.deleted', 0),
                    $expressionBuilder->orX(
                        $expressionBuilder->eq('pages.t3ver_wsid', 0),
                        $expressionBuilder->eq('pages.t3ver_wsid', (int)$this->versioningWorkspaceId)
                    ),
                    $expressionBuilder->neq('pages.doktype', self::DOKTYPE_RECYCLER)
                );
            } else {
                // add starttime / endtime, and check for hidden/deleted
                // Filter out new/deleted place-holder pages in case we are NOT in a
                // versioning preview (that means we are online!)
                $this->where_hid_del = ' AND ' . (string)$expressionBuilder->andX(
                    QueryHelper::stripLogicalOperatorPrefix(
                        $this->enableFields('pages', (int)$show_hidden, ['fe_group' => true])
                    ),
                    $expressionBuilder->neq('pages.doktype', self::DOKTYPE_RECYCLER)
                );
            }
            $cache->set($cacheIdentifier, $this->where_hid_del);
        }

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['init'] ?? false)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['init'] as $classRef) {
                $hookObject = GeneralUtility::makeInstance($classRef);
                if (!$hookObject instanceof PageRepositoryInitHookInterface) {
                    throw new \UnexpectedValueException($classRef . ' must implement interface ' . PageRepositoryInitHookInterface::class, 1379579812);
                }
                $hookObject->init_postProcess($this);
            }
        }
    }

    /**************************
     *
     * Selecting page records
     *
     **************************/

    /**
     * Loads the full page record for the given page ID.
     *
     * The page record is either served from a first-level cache or loaded from the
     * database. If no page can be found, an empty array is returned.
     *
     * Language overlay and versioning overlay are applied. Mount Point
     * handling is not done, an overlaid Mount Point is not replaced.
     *
     * The result is conditioned by the public properties where_groupAccess
     * and where_hid_del that are preset by the init() method.
     *
     * @see PageRepository::where_groupAccess
     * @see PageRepository::where_hid_del
     *
     * By default the usergroup access check is enabled. Use the second method argument
     * to disable the usergroup access check.
     *
     * The given UID can be preprocessed by registering a hook class that is
     * implementing the PageRepositoryGetPageHookInterface into the configuration array
     * $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage'].
     *
     * @param int $uid The page id to look up
     * @param bool $disableGroupAccessCheck set to true to disable group access check
     * @return array The resulting page record with overlays or empty array
     * @throws \UnexpectedValueException
     * @see PageRepository::getPage_noCheck()
     */
    public function getPage($uid, $disableGroupAccessCheck = false)
    {
        // Hook to manipulate the page uid for special overlay handling
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof PageRepositoryGetPageHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . PageRepositoryGetPageHookInterface::class, 1251476766);
            }
            $hookObject->getPage_preProcess($uid, $disableGroupAccessCheck, $this);
        }
        $cacheIdentifier = 'PageRepository_getPage_' . md5(
            implode(
                '-',
                [
                    $uid,
                    $disableGroupAccessCheck ? '' : $this->where_groupAccess,
                    $this->where_hid_del,
                    $this->sys_language_uid,
                ]
            )
        );
        $cache = $this->getRuntimeCache();
        $cacheEntry = $cache->get($cacheIdentifier);
        if (is_array($cacheEntry)) {
            return $cacheEntry;
        }
        $result = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', (int)$uid),
                QueryHelper::stripLogicalOperatorPrefix($this->where_hid_del)
            );

        $originalWhereGroupAccess = '';
        if (!$disableGroupAccessCheck) {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($this->where_groupAccess));
        } else {
            $originalWhereGroupAccess = $this->where_groupAccess;
            $this->where_groupAccess = '';
        }

        $row = $queryBuilder->executeQuery()->fetchAssociative();
        if ($row) {
            $this->versionOL('pages', $row);
            if (is_array($row)) {
                $result = $this->getPageOverlay($row);
            }
        }

        if ($disableGroupAccessCheck) {
            $this->where_groupAccess = $originalWhereGroupAccess;
        }

        $cache->set($cacheIdentifier, $result);
        return $result;
    }

    /**
     * Return the $row for the page with uid = $uid WITHOUT checking for
     * ->where_hid_del (start- and endtime or hidden). Only "deleted" is checked!
     *
     * @param int $uid The page id to look up
     * @return array The page row with overlaid localized fields. Empty array if no page.
     * @see getPage()
     */
    public function getPage_noCheck($uid)
    {
        $cache = $this->getRuntimeCache();
        $cacheIdentifier = 'PageRepository_getPage_noCheck_' . $uid . '_' . $this->sys_language_uid . '_' . $this->versioningWorkspaceId;
        $cacheEntry = $cache->get($cacheIdentifier);
        if ($cacheEntry !== false) {
            return $cacheEntry;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $row = $queryBuilder->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', (int)$uid))
            ->executeQuery()
            ->fetchAssociative();

        $result = [];
        if ($row) {
            $this->versionOL('pages', $row);
            if (is_array($row)) {
                $result = $this->getPageOverlay($row);
            }
        }
        $cache->set($cacheIdentifier, $result);
        return $result;
    }

    /**
     * Master helper method to overlay a record to a language.
     *
     * Be aware that for pages the languageId is taken, and for all other records the contentId.
     * This might change through a feature switch in the future.
     *
     * @param string $table the name of the table, should be a TCA table with localization enabled
     * @param array $row the current (full-fletched) record.
     * @return array|null
     */
    public function getLanguageOverlay(string $table, array $row)
    {
        // table is not localizable, so return directly
        if (!isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])) {
            return $row;
        }
        try {
            /** @var LanguageAspect $languageAspect */
            $languageAspect = $this->context->getAspect('language');
            if ($languageAspect->doOverlays()) {
                if ($table === 'pages') {
                    return $this->getPageOverlay($row, $languageAspect->getId());
                }
                return $this->getRecordOverlay(
                    $table,
                    $row,
                    $languageAspect
                );
            }
        } catch (AspectNotFoundException $e) {
            // no overlays
        }
        return $row;
    }

    /**
     * Returns the relevant page overlay record fields
     *
     * @param mixed $pageInput If $pageInput is an integer, it's the pid of the pageOverlay record and thus the page overlay record is returned. If $pageInput is an array, it's a page-record and based on this page record the language record is found and OVERLAID before the page record is returned.
     * @param int $languageUid Language UID if you want to set an alternative value to $this->sys_language_uid which is default. Should be >=0
     * @throws \UnexpectedValueException
     * @return array Page row which is overlaid with language_overlay record (or the overlay record alone)
     */
    public function getPageOverlay($pageInput, $languageUid = null)
    {
        $rows = $this->getPagesOverlay([$pageInput], $languageUid);
        // Always an array in return
        return $rows[0] ?? [];
    }

    /**
     * Returns the relevant page overlay record fields
     *
     * @param array $pagesInput Array of integers or array of arrays. If each value is an integer, it's the pids of the pageOverlay records and thus the page overlay records are returned. If each value is an array, it's page-records and based on this page records the language records are found and OVERLAID before the page records are returned.
     * @param int $languageUid Language UID if you want to set an alternative value to $this->sys_language_uid which is default. Should be >=0
     * @throws \UnexpectedValueException
     * @return array Page rows which are overlaid with language_overlay record.
     *               If the input was an array of integers, missing records are not
     *               included. If the input were page rows, untranslated pages
     *               are returned.
     */
    public function getPagesOverlay(array $pagesInput, $languageUid = null)
    {
        if (empty($pagesInput)) {
            return [];
        }
        if ($languageUid === null) {
            $languageUid = $this->sys_language_uid;
        }
        foreach ($pagesInput as &$origPage) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPageOverlay'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof PageRepositoryGetPageOverlayHookInterface) {
                    throw new \UnexpectedValueException($className . ' must implement interface ' . PageRepositoryGetPageOverlayHookInterface::class, 1269878881);
                }
                $hookObject->getPageOverlay_preProcess($origPage, $languageUid, $this);
            }
        }
        unset($origPage);

        $overlays = [];
        // If language UID is different from zero, do overlay:
        if ($languageUid) {
            $languageUids = array_merge([$languageUid], $this->getLanguageFallbackChain(null));

            $pageIds = [];
            foreach ($pagesInput as $origPage) {
                if (is_array($origPage)) {
                    // Was the whole record
                    $pageIds[] = (int)($origPage['uid'] ?? 0);
                } else {
                    // Was the id
                    $pageIds[] = (int)$origPage;
                }
            }
            $overlays = $this->getPageOverlaysForLanguageUids($pageIds, $languageUids);
        }

        // Create output:
        $pagesOutput = [];
        foreach ($pagesInput as $key => $origPage) {
            if (is_array($origPage)) {
                $pagesOutput[$key] = $origPage;
                if (isset($origPage['uid'], $overlays[$origPage['uid']])) {
                    // Overwrite the original field with the overlay
                    foreach ($overlays[$origPage['uid']] as $fieldName => $fieldValue) {
                        if ($fieldName !== 'uid' && $fieldName !== 'pid') {
                            $pagesOutput[$key][$fieldName] = $fieldValue;
                        }
                    }
                }
            } else {
                if (isset($overlays[$origPage])) {
                    $pagesOutput[$key] = $overlays[$origPage];
                }
            }
        }
        return $pagesOutput;
    }

    /**
     * Checks whether the passed (translated or default language) page is accessible with the given language settings.
     *
     * @param array $page the page translation record or the page in the default language
     * @param LanguageAspect $languageAspect
     * @return bool true if the given page translation record is suited for the given language ID
     * @internal
     */
    public function isPageSuitableForLanguage(array $page, LanguageAspect $languageAspect): bool
    {
        $languageUid = $languageAspect->getId();
        // Checks if the default language version can be shown
        // Block page is set, if l18n_cfg allows plus: 1) Either default language or 2) another language but NO overlay record set for page!
        $pageTranslationVisibility = new PageTranslationVisibility((int)($page['l18n_cfg'] ?? 0));
        if ((!$languageUid || !($page['_PAGES_OVERLAY'] ?? false))
            && $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage()
        ) {
            return false;
        }
        if ($languageUid > 0 && $pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()) {
            if (!($page['_PAGES_OVERLAY'] ?? false) || (int)($page['_PAGES_OVERLAY_LANGUAGE'] ?? 0) !== $languageUid) {
                return false;
            }
        } elseif ($languageUid > 0) {
            $languageUids = array_merge([$languageUid], $this->getLanguageFallbackChain($languageAspect));
            return in_array((int)($page['sys_language_uid'] ?? 0), $languageUids, true);
        }
        return true;
    }

    /**
     * Returns the cleaned fallback chain from the current language aspect, if there is one.
     *
     * @param LanguageAspect|null $languageAspect
     * @return int[]
     */
    protected function getLanguageFallbackChain(?LanguageAspect $languageAspect): array
    {
        $languageAspect = $languageAspect ?? $this->context->getAspect('language');
        return array_filter($languageAspect->getFallbackChain(), static function ($item) {
            return MathUtility::canBeInterpretedAsInteger($item);
        });
    }

    /**
     * Returns the first match of overlays for pages in the passed languages.
     *
     * NOTE regarding the query restrictions:
     * Currently the visibility aspect within the FrontendRestrictionContainer will allow
     * page translation records to be selected as they are child-records of a page.
     * However you may argue that the visibility flag should determine this.
     * But that's not how it's done right now.
     *
     * @param array $pageUids
     * @param array $languageUids uid of sys_language, please note that the order is important here.
     * @return array
     */
    protected function getPageOverlaysForLanguageUids(array $pageUids, array $languageUids): array
    {
        // Remove default language ("0")
        $languageUids = array_filter($languageUids);
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $overlays = [];

        foreach ($pageUids as $pageId) {
            // Create a map based on the order of values in $languageUids. Those entries reflect the order of the language + fallback chain.
            // We can't work with database ordering since there is no common SQL clause to order by e.g. [7, 1, 2].
            $orderedListByLanguages = array_flip($languageUids);

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context));
            // Because "fe_group" is an exclude field, so it is synced between overlays, the group restriction is removed for language overlays of pages
            $queryBuilder->getRestrictions()->removeByType(FrontendGroupRestriction::class);
            $result = $queryBuilder->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($languageUids, Connection::PARAM_INT_ARRAY)
                    )
                )
                ->executeQuery();

            // Create a list of rows ordered by values in $languageUids
            while ($row = $result->fetchAssociative()) {
                $orderedListByLanguages[$row[$languageField]] = $row;
            }

            foreach ($orderedListByLanguages as $languageUid => $row) {
                if (!is_array($row)) {
                    continue;
                }

                // Found a result for the current language id
                $this->versionOL('pages', $row);
                if (is_array($row)) {
                    $row['_PAGES_OVERLAY'] = true;
                    $row['_PAGES_OVERLAY_UID'] = $row['uid'];
                    $row['_PAGES_OVERLAY_LANGUAGE'] = $languageUid;
                    $row['_PAGES_OVERLAY_REQUESTEDLANGUAGE'] = $languageUids[0];
                    // Unset vital fields that are NOT allowed to be overlaid:
                    unset($row['uid'], $row['pid']);
                    $overlays[$pageId] = $row;

                    // Language fallback found, stop querying further languages
                    break;
                }
            }
        }

        return $overlays;
    }

    /**
     * Creates language-overlay for records in general (where translation is found
     * in records from the same table)
     *
     * @param string $table Table name
     * @param array $row Record to overlay. Must contain uid, pid and $table]['ctrl']['languageField']
     * @param LanguageAspect|int|null $sys_language_content Pointer to the sys_language uid for content on the site.
     * @param string $OLmode Overlay mode. If "hideNonTranslated" then records without translation will not be returned  un-translated but unset (and return value is NULL)
     * @throws \UnexpectedValueException
     * @return mixed Returns the input record, possibly overlaid with a translation.  But if $OLmode is "hideNonTranslated" then it will return NULL if no translation is found.
     */
    public function getRecordOverlay($table, $row, $sys_language_content = null, $OLmode = '')
    {
        if ($sys_language_content === null) {
            $sys_language_content = $this->context->getAspect('language');
        }
        if ($sys_language_content instanceof LanguageAspect) {
            // Early return when no overlays are needed
            if ($sys_language_content->getOverlayType() === $sys_language_content::OVERLAYS_OFF) {
                return $row;
            }
            $OLmode = $sys_language_content->getOverlayType() === $sys_language_content::OVERLAYS_MIXED ? '1' : 'hideNonTranslated';
            $sys_language_content = $sys_language_content->getContentId();
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getRecordOverlay'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof PageRepositoryGetRecordOverlayHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . PageRepositoryGetRecordOverlayHookInterface::class, 1269881658);
            }
            $hookObject->getRecordOverlay_preProcess($table, $row, $sys_language_content, $OLmode, $this);
        }

        $tableControl = $GLOBALS['TCA'][$table]['ctrl'] ?? [];

        if (!empty($tableControl['languageField'])
            // Return record for ALL languages untouched
            // @todo: Fix call stack to prevent this situation in the first place
            && (int)$row[$tableControl['languageField']] !== -1
            && !empty($tableControl['transOrigPointerField'])
            && $row['uid'] > 0
            && ($row['pid'] > 0 || in_array($tableControl['rootLevel'] ?? false, [true, 1, -1], true))) {
            // Will try to overlay a record only if the sys_language_content value is larger than zero.
            if ($sys_language_content > 0) {
                // Must be default language, otherwise no overlaying
                if ((int)$row[$tableControl['languageField']] === 0) {
                    // Select overlay record:
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable($table);
                    $queryBuilder->setRestrictions(
                        GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context)
                    );
                    if ($this->versioningWorkspaceId > 0) {
                        // If not in live workspace, remove query based "enable fields" checks, it will be done in versionOL()
                        // @see functional workspace test createLocalizedNotHiddenWorkspaceContentHiddenInLive()
                        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
                        $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
                        $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
                        // We remove the FrontendWorkspaceRestriction in this case, because we need to get the LIVE record
                        // of the language record before doing the version overlay of the language again. WorkspaceRestriction
                        // does this for us, PLUS we need to ensure to get a possible LIVE record first (that's why
                        // the "orderBy" query is there, so the LIVE record is found first), as there might only be a
                        // versioned record (e.g. new version) or both (common for modifying, moving etc).
                        if ($this->hasTableWorkspaceSupport($table)) {
                            $queryBuilder->getRestrictions()->removeByType(FrontendWorkspaceRestriction::class);
                            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->versioningWorkspaceId));
                            $queryBuilder->orderBy('t3ver_wsid', 'ASC');
                        }
                    }

                    $pid = $row['pid'];
                    // When inside a workspace, the already versioned $row of the default language is coming in
                    // For moved versioned records, the PID MIGHT be different. However, the idea of this function is
                    // to get the language overlay of the LIVE default record, and afterwards get the versioned record
                    // the found (live) language record again, see the versionOL() call a few lines below.
                    // This means, we need to modify the $pid value for moved records, as they might be on a different
                    // page and use the PID of the LIVE version.
                    if (isset($row['_ORIG_pid']) && $this->hasTableWorkspaceSupport($table) && VersionState::cast($row['t3ver_state'] ?? 0)->equals(VersionState::MOVE_POINTER)) {
                        $pid = $row['_ORIG_pid'];
                    }
                    $olrow = $queryBuilder->select('*')
                        ->from($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                $tableControl['languageField'],
                                $queryBuilder->createNamedParameter($sys_language_content, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                $tableControl['transOrigPointerField'],
                                $queryBuilder->createNamedParameter($row['uid'], \PDO::PARAM_INT)
                            )
                        )
                        ->setMaxResults(1)
                        ->executeQuery()
                        ->fetchAssociative();

                    $this->versionOL($table, $olrow);
                    // Merge record content by traversing all fields:
                    if (is_array($olrow)) {
                        if (isset($olrow['_ORIG_uid'])) {
                            $row['_ORIG_uid'] = $olrow['_ORIG_uid'];
                        }
                        if (isset($olrow['_ORIG_pid'])) {
                            $row['_ORIG_pid'] = $olrow['_ORIG_pid'];
                        }
                        foreach ($row as $fN => $fV) {
                            if ($fN !== 'uid' && $fN !== 'pid' && array_key_exists($fN, $olrow)) {
                                $row[$fN] = $olrow[$fN];
                            } elseif ($fN === 'uid') {
                                $row['_LOCALIZED_UID'] = $olrow['uid'];
                            }
                        }
                    } elseif ($OLmode === 'hideNonTranslated' && (int)$row[$tableControl['languageField']] === 0) {
                        // Unset, if non-translated records should be hidden. ONLY done if the source
                        // record really is default language and not [All] in which case it is allowed.
                        $row = null;
                    }
                } elseif ($sys_language_content != $row[$tableControl['languageField']]) {
                    $row = null;
                }
            } else {
                // When default language is displayed, we never want to return a record carrying
                // another language!
                if ($row[$tableControl['languageField']] > 0) {
                    $row = null;
                }
            }
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getRecordOverlay'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof PageRepositoryGetRecordOverlayHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . PageRepositoryGetRecordOverlayHookInterface::class, 1269881659);
            }
            $hookObject->getRecordOverlay_postProcess($table, $row, $sys_language_content, $OLmode, $this);
        }

        return $row;
    }

    /************************************************
     *
     * Page related: Menu, Domain record, Root line
     *
     ************************************************/

    /**
     * Returns an array with page rows for subpages of a certain page ID. This is used for menus in the frontend.
     * If there are mount points in overlay mode the _MP_PARAM field is set to the correct MPvar.
     *
     * If the $pageId being input does in itself require MPvars to define a correct
     * rootline these must be handled externally to this function.
     *
     * @param int|int[] $pageId The page id (or array of page ids) for which to fetch subpages (PID)
     * @param string $fields Fields to select, `*` is the default - If a custom list is set, make sure the list
     *                       contains the `uid` field. It's mandatory for further processing of the result row.
     * @param string $sortField The field to sort by. Default is "sorting
     * @param string $additionalWhereClause Optional additional where clauses. Like "AND title like '%some text%'" for instance.
     * @param bool $checkShortcuts Check if shortcuts exist, checks by default
     * @return array Array with key/value pairs; keys are page-uid numbers. values are the corresponding page records (with overlaid localized fields, if any)
     * @see getPageShortcut()
     * @see \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject::makeMenu()
     */
    public function getMenu($pageId, $fields = '*', $sortField = 'sorting', $additionalWhereClause = '', $checkShortcuts = true)
    {
        // @todo: Restricting $fields to a list like 'uid, title' here, leads to issues from methods like
        //        getSubpagesForPages() which access keys like 'doktype'. This is odd, select field list
        //        should be handled better here, probably at least containing fields that are used in the
        //        sub methods. In the end, it might be easier to drop argument $fields altogether and
        //        always select * ?
        return $this->getSubpagesForPages((array)$pageId, $fields, $sortField, $additionalWhereClause, $checkShortcuts);
    }

    /**
     * Returns an array with page-rows for pages with uid in $pageIds.
     *
     * This is used for menus. If there are mount points in overlay mode
     * the _MP_PARAM field is set to the correct MPvar.
     *
     * @param int[] $pageIds Array of page ids to fetch
     * @param string $fields Fields to select, `*` is the default - If a custom list is set, make sure the list
     *                       contains the `uid` field. It's mandatory for further processing of the result row.
     * @param string $sortField The field to sort by. Default is "sorting"
     * @param string $additionalWhereClause Optional additional where clauses. Like "AND title like '%some text%'" for instance.
     * @param bool $checkShortcuts Check if shortcuts exist, checks by default
     * @return array Array with key/value pairs; keys are page-uid numbers. values are the corresponding page records (with overlaid localized fields, if any)
     */
    public function getMenuForPages(array $pageIds, $fields = '*', $sortField = 'sorting', $additionalWhereClause = '', $checkShortcuts = true)
    {
        return $this->getSubpagesForPages($pageIds, $fields, $sortField, $additionalWhereClause, $checkShortcuts, false);
    }

    /**
     * Loads page records either by PIDs or by UIDs.
     *
     * By default the subpages of the given page IDs are loaded (as the method name suggests). If $parentPages is set
     * to FALSE, the page records for the given page IDs are loaded directly.
     *
     * Concerning the rationale, please see these two other methods:
     *
     * @see PageRepository::getMenu()
     * @see PageRepository::getMenuForPages()
     *
     * Version and language overlay are applied to the loaded records.
     *
     * If a record is a mount point in overlay mode, the the overlaying page record is returned in place of the
     * record. The record is enriched by the field _MP_PARAM containing the mount point mapping for the mount
     * point.
     *
     * The query can be customized by setting fields, sorting and additional WHERE clauses. If additional WHERE
     * clauses are given, the clause must start with an operator, i.e: "AND title like '%some text%'".
     *
     * The keys of the returned page records are the page UIDs.
     *
     * CAUTION: In case of an overlaid mount point, it is the original UID.
     *
     * @param int[] $pageIds PIDs or UIDs to load records for
     * @param string $fields Fields to select, `*` is the default - If a custom list is set, make sure the list
     *                       contains the `uid` field. It's mandatory for further processing of the result row.
     * @param string $sortField the field to sort by
     * @param string $additionalWhereClause optional additional WHERE clause
     * @param bool $checkShortcuts whether to check if shortcuts exist
     * @param bool $parentPages Switch to load pages (false) or child pages (true).
     * @return array page records
     *
     * @see self::getPageShortcut()
     * @see \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject::makeMenu()
     */
    protected function getSubpagesForPages(
        array $pageIds,
        string $fields = '*',
        string $sortField = 'sorting',
        string $additionalWhereClause = '',
        bool $checkShortcuts = true,
        bool $parentPages = true
    ): array {
        $relationField = $parentPages ? 'pid' : 'uid';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->versioningWorkspaceId));

        $res = $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields, true))
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    $relationField,
                    $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                QueryHelper::stripLogicalOperatorPrefix($this->where_hid_del),
                QueryHelper::stripLogicalOperatorPrefix($this->where_groupAccess),
                QueryHelper::stripLogicalOperatorPrefix($additionalWhereClause)
            );

        if (!empty($sortField)) {
            $orderBy = QueryHelper::parseOrderBy($sortField);
            foreach ($orderBy as $order) {
                $res->addOrderBy($order[0], $order[1] ?? 'ASC');
            }
        }
        $result = $res->executeQuery();

        $pages = [];
        while ($page = $result->fetchAssociative()) {
            $originalUid = $page['uid'];

            // Versioning Preview Overlay
            $this->versionOL('pages', $page, true);
            // Skip if page got disabled due to version overlay (might be delete placeholder)
            if (empty($page)) {
                continue;
            }

            // Add a mount point parameter if needed
            $page = $this->addMountPointParameterToPage((array)$page);

            // If shortcut, look up if the target exists and is currently visible
            if ($checkShortcuts) {
                $page = $this->checkValidShortcutOfPage((array)$page, $additionalWhereClause);
            }

            // If the page still is there, we add it to the output
            if (!empty($page)) {
                $pages[$originalUid] = $page;
            }
        }

        // Finally load language overlays
        return $this->getPagesOverlay($pages);
    }

    /**
     * Replaces the given page record with mounted page if required
     *
     * If the given page record is a mount point in overlay mode, the page
     * record is replaced by the record of the overlaying page. The overlay
     * record is enriched by setting the mount point mapping into the field
     * _MP_PARAM as string for example '23-14'.
     *
     * In all other cases the given page record is returned as is.
     *
     * @todo Find a better name. The current doesn't hit the point.
     *
     * @param array $page The page record to handle.
     * @return array The given page record or it's replacement.
     */
    protected function addMountPointParameterToPage(array $page): array
    {
        if (empty($page)) {
            return [];
        }

        // $page MUST have "uid", "pid", "doktype", "mount_pid", "mount_pid_ol" fields in it
        $mountPointInfo = $this->getMountPointInfo($page['uid'], $page);

        // There is a valid mount point in overlay mode.
        if (is_array($mountPointInfo) && $mountPointInfo['overlay']) {

            // Using "getPage" is OK since we need the check for enableFields AND for type 2
            // of mount pids we DO require a doktype < 200!
            $mountPointPage = $this->getPage($mountPointInfo['mount_pid']);

            if (!empty($mountPointPage)) {
                $page = $mountPointPage;
                $page['_MP_PARAM'] = $mountPointInfo['MPvar'];
            } else {
                $page = [];
            }
        }
        return $page;
    }

    /**
     * If shortcut, look up if the target exists and is currently visible
     *
     * @param array $page The page to check
     * @param string $additionalWhereClause Optional additional where clauses. Like "AND title like '%some text%'" for instance.
     * @return array
     */
    protected function checkValidShortcutOfPage(array $page, $additionalWhereClause)
    {
        if (empty($page)) {
            return [];
        }

        $dokType = (int)$page['doktype'];
        $shortcutMode = (int)$page['shortcut_mode'];

        if ($dokType === self::DOKTYPE_SHORTCUT && ($page['shortcut'] || $shortcutMode)) {
            if ($shortcutMode === self::SHORTCUT_MODE_NONE) {
                // No shortcut_mode set, so target is directly set in $page['shortcut']
                $searchField = 'uid';
                $searchUid = (int)$page['shortcut'];
            } elseif ($shortcutMode === self::SHORTCUT_MODE_FIRST_SUBPAGE || $shortcutMode === self::SHORTCUT_MODE_RANDOM_SUBPAGE) {
                // Check subpages - first subpage or random subpage
                $searchField = 'pid';
                // If a shortcut mode is set and no valid page is given to select subpages
                // from use the actual page.
                $searchUid = (int)$page['shortcut'] ?: $page['uid'];
            } elseif ($shortcutMode === self::SHORTCUT_MODE_PARENT_PAGE) {
                // Shortcut to parent page
                $searchField = 'uid';
                $searchUid = $page['pid'];
            } else {
                $searchField = '';
                $searchUid = 0;
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll();
            $count = $queryBuilder->count('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $searchField,
                        $queryBuilder->createNamedParameter($searchUid, \PDO::PARAM_INT)
                    ),
                    QueryHelper::stripLogicalOperatorPrefix($this->where_hid_del),
                    QueryHelper::stripLogicalOperatorPrefix($this->where_groupAccess),
                    QueryHelper::stripLogicalOperatorPrefix($additionalWhereClause)
                )
                ->executeQuery()
                ->fetchOne();

            if (!$count) {
                $page = [];
            }
        } elseif ($dokType === self::DOKTYPE_SHORTCUT) {
            // Neither shortcut target nor mode is set. Remove the page from the menu.
            $page = [];
        }
        return $page;
    }

    /**
     * Get page shortcut; Finds the records pointed to by input value $SC (the shortcut value)
     *
     * @param string $shortcutFieldValue The value of the "shortcut" field from the pages record
     * @param int $shortcutMode The shortcut mode: 1 will select first subpage, 2 a random subpage, 3 the parent page; default is the page pointed to by $SC
     * @param int $thisUid The current page UID of the page which is a shortcut
     * @param int $iteration Safety feature which makes sure that the function is calling itself recursively max 20 times (since this function can find shortcuts to other shortcuts to other shortcuts...)
     * @param array $pageLog An array filled with previous page uids tested by the function - new page uids are evaluated against this to avoid going in circles.
     * @param bool $disableGroupCheck If true, the group check is disabled when fetching the target page (needed e.g. for menu generation)
     * @param bool $resolveRandomPageShortcuts If true (default) this will also resolve shortcut to random subpages. In case of linking from a page to a shortcut page, we do not want to cache the "random" logic.
     *
     * @throws \RuntimeException
     * @throws ShortcutTargetPageNotFoundException
     * @return mixed Returns the page record of the page that the shortcut pointed to. If $resolveRandomPageShortcuts = false, and the shortcut page is configured to point to a random shortcut then an empty array is returned
     * @internal
     * @see getPageAndRootline()
     */
    public function getPageShortcut($shortcutFieldValue, $shortcutMode, $thisUid, $iteration = 20, $pageLog = [], $disableGroupCheck = false, bool $resolveRandomPageShortcuts = true)
    {
        $idArray = GeneralUtility::intExplode(',', $shortcutFieldValue);
        if ($resolveRandomPageShortcuts === false && (int)$shortcutMode === self::SHORTCUT_MODE_RANDOM_SUBPAGE) {
            return [];
        }
        // Find $page record depending on shortcut mode:
        switch ($shortcutMode) {
            case self::SHORTCUT_MODE_FIRST_SUBPAGE:
            case self::SHORTCUT_MODE_RANDOM_SUBPAGE:
                $excludedDoktypes = [
                    self::DOKTYPE_SPACER,
                    self::DOKTYPE_SYSFOLDER,
                    self::DOKTYPE_RECYCLER,
                    self::DOKTYPE_BE_USER_SECTION,
                ];
                $savedWhereGroupAccess = '';
                // "getMenu()" does not allow to hand over $disableGroupCheck, for this reason it is manually disabled and re-enabled afterwards.
                if ($disableGroupCheck) {
                    $savedWhereGroupAccess = $this->where_groupAccess;
                    $this->where_groupAccess = '';
                }
                $pageArray = $this->getMenu($idArray[0] ?: $thisUid, '*', 'sorting', 'AND pages.doktype NOT IN (' . implode(', ', $excludedDoktypes) . ')');
                if ($disableGroupCheck) {
                    $this->where_groupAccess = $savedWhereGroupAccess;
                }
                $pO = 0;
                if ($shortcutMode == self::SHORTCUT_MODE_RANDOM_SUBPAGE && !empty($pageArray)) {
                    $pO = (int)random_int(0, count($pageArray) - 1);
                }
                $c = 0;
                $page = [];
                foreach ($pageArray as $pV) {
                    if ($c === $pO) {
                        $page = $pV;
                        break;
                    }
                    $c++;
                }
                if (empty($page)) {
                    $message = 'This page (ID ' . $thisUid . ') is of type "Shortcut" and configured to redirect to a subpage. However, this page has no accessible subpages.';
                    throw new ShortcutTargetPageNotFoundException($message, 1301648328);
                }
                break;
            case self::SHORTCUT_MODE_PARENT_PAGE:
                $parent = $this->getPage($idArray[0] ?: $thisUid, $disableGroupCheck);
                $page = $this->getPage($parent['pid'], $disableGroupCheck);
                if (empty($page)) {
                    $message = 'This page (ID ' . $thisUid . ') is of type "Shortcut" and configured to redirect to its parent page. However, the parent page is not accessible.';
                    throw new ShortcutTargetPageNotFoundException($message, 1301648358);
                }
                break;
            default:
                $page = $this->getPage($idArray[0], $disableGroupCheck);
                if (empty($page)) {
                    $message = 'This page (ID ' . $thisUid . ') is of type "Shortcut" and configured to redirect to a page, which is not accessible (ID ' . $idArray[0] . ').';
                    throw new ShortcutTargetPageNotFoundException($message, 1301648404);
                }
        }
        // Check if short cut page was a shortcut itself, if so look up recursively:
        if ((int)$page['doktype'] === self::DOKTYPE_SHORTCUT) {
            if (!in_array($page['uid'], $pageLog) && $iteration > 0) {
                $pageLog[] = $page['uid'];
                $page = $this->getPageShortcut($page['shortcut'], $page['shortcut_mode'], $page['uid'], $iteration - 1, $pageLog, $disableGroupCheck);
            } else {
                $pageLog[] = $page['uid'];
                $this->logger->error('Page shortcuts were looping in uids {uids}', ['uids' => implode(', ', array_values($pageLog))]);
                throw new \RuntimeException('Page shortcuts were looping in uids: ' . implode(', ', array_values($pageLog)), 1294587212);
            }
        }
        // Return resulting page:
        return $page;
    }

    /**
     * Check if page is a shortcut, then resolve the target page directly.
     * This is a better method than "getPageShortcut()" and should be used instead, as this automatically checks for $page records
     * and returns the shortcut pages directly.
     *
     * This method also provides a runtime cache around resolving the shortcut resolving, in order to speed up link generation
     * to the same shortcut page.
     *
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getPageAndRootline()
     */
    public function resolveShortcutPage(array $page, bool $resolveRandomSubpages = false, bool $disableGroupAccessCheck = false): array
    {
        if ((int)($page['doktype'] ?? 0) !== self::DOKTYPE_SHORTCUT) {
            return $page;
        }
        $shortcutMode = (int)($page['shortcut_mode'] ?? self::SHORTCUT_MODE_NONE);
        $shortcutTarget = (string)($page['shortcut'] ?? '');

        $cacheIdentifier = 'shortcuts_resolved_' . ($disableGroupAccessCheck ? '1' : '0') . '_' . $page['uid'] . '_' . $this->sys_language_uid . '_' . $page['sys_language_uid'];
        // Only use the runtime cache if we do not support the random subpages functionality
        if ($resolveRandomSubpages === false) {
            $cachedResult = $this->getRuntimeCache()->get($cacheIdentifier);
            if (is_array($cachedResult)) {
                return $cachedResult;
            }
        }
        $shortcut = $this->getPageShortcut(
            $shortcutTarget,
            $shortcutMode,
            $page['uid'],
            20,
            [],
            $disableGroupAccessCheck,
            $resolveRandomSubpages
        );
        if (!empty($shortcut)) {
            $page = $shortcut;
            $page['_SHORTCUT_ORIGINAL_PAGE_UID'] = $page['uid'];
        }

        if ($resolveRandomSubpages === false) {
            $this->getRuntimeCache()->set($cacheIdentifier, $page);
        }

        return $page;
    }
    /**
     * Returns the redirect URL for the input page row IF the doktype is set to 3.
     *
     * @param array $pagerow The page row to return URL type for
     * @return string|bool The URL from based on the data from "pages:url". False if not found.
     */
    public function getExtURL($pagerow)
    {
        if ((int)$pagerow['doktype'] === self::DOKTYPE_LINK) {
            $redirectTo = $pagerow['url'];
            $uI = parse_url($redirectTo);
            // If relative path, prefix Site URL
            // If it's a valid email without protocol, add "mailto:"
            if (!($uI['scheme'] ?? false)) {
                if (GeneralUtility::validEmail($redirectTo)) {
                    $redirectTo = 'mailto:' . $redirectTo;
                } elseif ($redirectTo[0] !== '/') {
                    $redirectTo = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getSiteUrl() . $redirectTo;
                }
            }
            return $redirectTo;
        }
        return false;
    }

    /**
     * Returns a MountPoint array for the specified page
     *
     * Does a recursive search if the mounted page should be a mount page
     * itself.
     *
     * Note:
     *
     * Recursive mount points are not supported by all parts of the core.
     * The usage is discouraged. They may be removed from this method.
     *
     * @see https://decisions.typo3.org/t/supporting-or-prohibiting-recursive-mount-points/165/3
     *
     * An array will be returned if mount pages are enabled, the correct
     * doktype (7) is set for page and there IS a mount_pid with a valid
     * record.
     *
     * The optional page record must contain at least uid, pid, doktype,
     * mount_pid,mount_pid_ol. If it is not supplied it will be looked up by
     * the system at additional costs for the lookup.
     *
     * Returns FALSE if no mount point was found, "-1" if there should have been
     * one, but no connection to it, otherwise an array with information
     * about mount pid and modes.
     *
     * @param int $pageId Page id to do the lookup for.
     * @param array|bool $pageRec Optional page record for the given page.
     * @param array $prevMountPids Internal register to prevent lookup cycles.
     * @param int $firstPageUid The first page id.
     * @return mixed Mount point array or failure flags (-1, false).
     * @see \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject
     */
    public function getMountPointInfo($pageId, $pageRec = false, $prevMountPids = [], $firstPageUid = 0)
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids']) {
            return false;
        }
        $cacheIdentifier = 'PageRepository_getMountPointInfo_' . $pageId;
        $cache = $this->getRuntimeCache();
        if ($cache->has($cacheIdentifier)) {
            return $cache->get($cacheIdentifier);
        }
        $result = false;
        // Get pageRec if not supplied:
        if (!is_array($pageRec)) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $pageRec = $queryBuilder->select('uid', 'pid', 'doktype', 'mount_pid', 'mount_pid_ol', 't3ver_state', 'l10n_parent')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        'doktype',
                        $queryBuilder->createNamedParameter(self::DOKTYPE_RECYCLER, \PDO::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();

            // Only look for version overlay if page record is not supplied; This assumes
            // that the input record is overlaid with preview version, if any!
            $this->versionOL('pages', $pageRec);
        }
        // Set first Page uid:
        if (!$firstPageUid) {
            $firstPageUid = (int)($pageRec['l10n_parent'] ?? false) ?: $pageRec['uid'] ?? 0;
        }
        // Look for mount pid value plus other required circumstances:
        $mount_pid = (int)($pageRec['mount_pid'] ?? 0);
        $doktype = (int)($pageRec['doktype'] ?? 0);
        if (is_array($pageRec) && $doktype === self::DOKTYPE_MOUNTPOINT && $mount_pid > 0 && !in_array($mount_pid, $prevMountPids, true)) {
            // Get the mount point record (to verify its general existence):
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $mountRec = $queryBuilder->select('uid', 'pid', 'doktype', 'mount_pid', 'mount_pid_ol', 't3ver_state', 'l10n_parent')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($mount_pid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        'doktype',
                        $queryBuilder->createNamedParameter(self::DOKTYPE_RECYCLER, \PDO::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();

            $this->versionOL('pages', $mountRec);
            if (is_array($mountRec)) {
                // Look for recursive mount point:
                $prevMountPids[] = $mount_pid;
                $recursiveMountPid = $this->getMountPointInfo($mount_pid, $mountRec, $prevMountPids, $firstPageUid);
                // Return mount point information:
                $result = $recursiveMountPid ?: [
                    'mount_pid' => $mount_pid,
                    'overlay' => $pageRec['mount_pid_ol'],
                    'MPvar' => $mount_pid . '-' . $firstPageUid,
                    'mount_point_rec' => $pageRec,
                    'mount_pid_rec' => $mountRec,
                ];
            } else {
                // Means, there SHOULD have been a mount point, but there was none!
                $result = -1;
            }
        }
        $cache->set($cacheIdentifier, $result);
        return $result;
    }

    /**
     * Removes Page UID numbers from the input array which are not available due to QueryRestrictions
     * This is also very helpful to add a custom RestrictionContainer to add custom Restrictions such as "bad doktypes" e.g. RECYCLER doktypes
     *
     * @param int[] $pageIds Array of Page UID numbers to check
     * @param QueryRestrictionContainerInterface|null $restrictionContainer
     * @return int[] Returns the array of remaining page UID numbers
     */
    public function filterAccessiblePageIds(array $pageIds, QueryRestrictionContainerInterface $restrictionContainer = null): array
    {
        if ($pageIds === []) {
            return [];
        }
        $validPageIds = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        if ($restrictionContainer instanceof QueryRestrictionContainerInterface) {
            $queryBuilder->setRestrictions($restrictionContainer);
        } else {
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context));
        }
        $statement = $queryBuilder->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                )
            )
            ->executeQuery();
        while ($row = $statement->fetchAssociative()) {
            $validPageIds[] = (int)$row['uid'];
        }
        return $validPageIds;
    }
    /********************************
     *
     * Selecting records in general
     *
     ********************************/

    /**
     * Checks if a record exists and is accessible.
     * The row is returned if everything's OK.
     *
     * @param string $table The table name to search
     * @param int $uid The uid to look up in $table
     * @param bool|int $checkPage If checkPage is set, it's also required that the page on which the record resides is accessible
     * @return array|int Returns array (the record) if OK, otherwise blank/0 (zero)
     */
    public function checkRecord($table, $uid, $checkPage = 0)
    {
        $uid = (int)$uid;
        if (is_array($GLOBALS['TCA'][$table]) && $uid > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context));
            $row = $queryBuilder->select('*')
                ->from($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
                ->executeQuery()
                ->fetchAssociative();

            if ($row) {
                $this->versionOL($table, $row);
                if (is_array($row)) {
                    if ($checkPage) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                            ->getQueryBuilderForTable('pages');
                        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context));
                        $numRows = (int)$queryBuilder->count('*')
                            ->from('pages')
                            ->where(
                                $queryBuilder->expr()->eq(
                                    'uid',
                                    $queryBuilder->createNamedParameter($row['pid'], \PDO::PARAM_INT)
                                )
                            )
                            ->executeQuery()
                            ->fetchOne();
                        if ($numRows > 0) {
                            return $row;
                        }
                        return 0;
                    }
                    return $row;
                }
            }
        }
        return 0;
    }

    /**
     * Returns record no matter what - except if record is deleted
     *
     * @param string $table The table name to search
     * @param int $uid The uid to look up in $table
     * @param string $fields Fields to select, `*` is the default - If a custom list is set, make sure the list
     *                       contains the `uid` field. It's mandatory for further processing of the result row.
     * @return mixed Returns array (the record) if found, otherwise blank/0 (zero)
     * @see getPage_noCheck()
     */
    public function getRawRecord($table, $uid, $fields = '*')
    {
        $uid = (int)$uid;
        if (isset($GLOBALS['TCA'][$table]) && is_array($GLOBALS['TCA'][$table]) && $uid > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $row = $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields, true))
                ->from($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
                ->executeQuery()
                ->fetchAssociative();

            if ($row) {
                $this->versionOL($table, $row);
                if (is_array($row)) {
                    return $row;
                }
            }
        }
        return 0;
    }

    /********************************
     *
     * Standard clauses
     *
     ********************************/

    /**
     * Returns a part of a WHERE clause which will filter out records with start/end
     * times or hidden/fe_groups fields set to values that should de-select them
     * according to the current time, preview settings or user login. Definitely a
     * frontend function.
     *
     * Is using the $GLOBALS['TCA'] arrays "ctrl" part where the key "enablefields"
     * determines for each table which of these features applies to that table.
     *
     * @param string $table Table name found in the $GLOBALS['TCA'] array
     * @param int $show_hidden If $show_hidden is set (0/1), any hidden-fields in records are ignored. NOTICE: If you call this function, consider what to do with the show_hidden parameter. Maybe it should be set? See ContentObjectRenderer->enableFields where it's implemented correctly.
     * @param array $ignore_array Array you can pass where keys can be "disabled", "starttime", "endtime", "fe_group" (keys from "enablefields" in TCA) and if set they will make sure that part of the clause is not added. Thus disables the specific part of the clause. For previewing etc.
     * @throws \InvalidArgumentException
     * @return string The clause starting like " AND ...=... AND ...=...
     */
    public function enableFields($table, $show_hidden = -1, $ignore_array = [])
    {
        if ($show_hidden === -1) {
            // If show_hidden was not set from outside, use the current context
            $show_hidden = (int)$this->context->getPropertyFromAspect('visibility', $table === 'pages' ? 'includeHiddenPages' : 'includeHiddenContent', false);
        }
        // If show_hidden was not changed during the previous evaluation, do it here.
        $ctrl = $GLOBALS['TCA'][$table]['ctrl'] ?? null;
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table)
            ->expr();
        $constraints = [];
        if (is_array($ctrl)) {
            // Delete field check:
            if ($ctrl['delete'] ?? false) {
                $constraints[] = $expressionBuilder->eq($table . '.' . $ctrl['delete'], 0);
            }
            if ($this->hasTableWorkspaceSupport($table)) {
                // this should work exactly as WorkspaceRestriction and WorkspaceRestriction should be used instead
                if ($this->versioningWorkspaceId === 0) {
                    // Filter out placeholder records (new/deleted items)
                    // in case we are NOT in a version preview (that means we are online!)
                    $constraints[] = $expressionBuilder->lte(
                        $table . '.t3ver_state',
                        new VersionState(VersionState::DEFAULT_STATE)
                    );
                    $constraints[] = $expressionBuilder->eq($table . '.t3ver_wsid', 0);
                } else {
                    // show only records of live and of the current workspace
                    // in case we are in a versioning preview
                    $constraints[] = $expressionBuilder->orX(
                        $expressionBuilder->eq($table . '.t3ver_wsid', 0),
                        $expressionBuilder->eq($table . '.t3ver_wsid', (int)$this->versioningWorkspaceId)
                    );
                }

                // Filter out versioned records
                if (empty($ignore_array['pid'])) {
                    // Always filter out versioned records that have an "offline" record
                    $constraints[] = $expressionBuilder->orX(
                        $expressionBuilder->eq($table . '.t3ver_oid', 0),
                        $expressionBuilder->eq($table . '.t3ver_state', VersionState::MOVE_POINTER)
                    );
                }
            }

            // Enable fields:
            if (is_array($ctrl['enablecolumns'] ?? false)) {
                // In case of versioning-preview, enableFields are ignored (checked in
                // versionOL())
                if ($this->versioningWorkspaceId === 0 || !$this->hasTableWorkspaceSupport($table)) {
                    if (($ctrl['enablecolumns']['disabled'] ?? false) && !$show_hidden && !($ignore_array['disabled'] ?? false)) {
                        $field = $table . '.' . $ctrl['enablecolumns']['disabled'];
                        $constraints[] = $expressionBuilder->eq($field, 0);
                    }
                    if (($ctrl['enablecolumns']['starttime'] ?? false) && !($ignore_array['starttime'] ?? false)) {
                        $field = $table . '.' . $ctrl['enablecolumns']['starttime'];
                        $constraints[] = $expressionBuilder->lte(
                            $field,
                            $this->context->getPropertyFromAspect('date', 'accessTime', 0)
                        );
                    }
                    if (($ctrl['enablecolumns']['endtime'] ?? false) && !($ignore_array['endtime'] ?? false)) {
                        $field = $table . '.' . $ctrl['enablecolumns']['endtime'];
                        $constraints[] = $expressionBuilder->orX(
                            $expressionBuilder->eq($field, 0),
                            $expressionBuilder->gt(
                                $field,
                                $this->context->getPropertyFromAspect('date', 'accessTime', 0)
                            )
                        );
                    }
                    if (($ctrl['enablecolumns']['fe_group'] ?? false) && !($ignore_array['fe_group'] ?? false)) {
                        $field = $table . '.' . $ctrl['enablecolumns']['fe_group'];
                        $constraints[] = QueryHelper::stripLogicalOperatorPrefix(
                            $this->getMultipleGroupsWhereClause($field, $table)
                        );
                    }
                    // Call hook functions for additional enableColumns
                    // It is used by the extension ingmar_accessctrl which enables assigning more
                    // than one usergroup to content and page records
                    $_params = [
                        'table' => $table,
                        'show_hidden' => $show_hidden,
                        'ignore_array' => $ignore_array,
                        'ctrl' => $ctrl,
                    ];
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns'] ?? [] as $_funcRef) {
                        $constraints[] = QueryHelper::stripLogicalOperatorPrefix(
                            GeneralUtility::callUserFunction($_funcRef, $_params, $this)
                        );
                    }
                }
            }
        } else {
            throw new \InvalidArgumentException('There is no entry in the $TCA array for the table "' . $table . '". This means that the function enableFields() is called with an invalid table name as argument.', 1283790586);
        }

        return empty($constraints) ? '' : ' AND ' . $expressionBuilder->andX(...$constraints);
    }

    /**
     * Creating where-clause for checking group access to elements in enableFields
     * function
     *
     * @param string $field Field with group list
     * @param string $table Table name
     * @return string AND sql-clause
     * @see enableFields()
     */
    public function getMultipleGroupsWhereClause($field, $table)
    {
        if (!$this->context->hasAspect('frontend.user')) {
            return '';
        }
        /** @var UserAspect $userAspect */
        $userAspect = $this->context->getAspect('frontend.user');
        $memberGroups = $userAspect->getGroupIds();
        $cache = $this->getRuntimeCache();
        $cacheIdentifier = 'PageRepository_groupAccessWhere_' . md5($field . '_' . $table . '_' . implode('_', $memberGroups));
        $cacheEntry = $cache->get($cacheIdentifier);
        if ($cacheEntry) {
            return $cacheEntry;
        }

        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table)
            ->expr();
        $orChecks = [];
        // If the field is empty, then OK
        $orChecks[] = $expressionBuilder->eq($field, $expressionBuilder->literal(''));
        // If the field is NULL, then OK
        $orChecks[] = $expressionBuilder->isNull($field);
        // If the field contains zero, then OK
        $orChecks[] = $expressionBuilder->eq($field, $expressionBuilder->literal('0'));
        foreach ($memberGroups as $value) {
            $orChecks[] = $expressionBuilder->inSet($field, $expressionBuilder->literal($value));
        }

        $accessGroupWhere = ' AND (' . $expressionBuilder->orX(...$orChecks) . ')';
        $cache->set($cacheIdentifier, $accessGroupWhere);
        return $accessGroupWhere;
    }

    /**********************
     *
     * Versioning Preview
     *
     **********************/

    /**
     * Finding online PID for offline version record
     *
     * ONLY active when backend user is previewing records. MUST NEVER affect a site
     * served which is not previewed by backend users!!!
     *
     * What happens in this method:
     * If a record was moved in a workspace, the records' PID might be different. This is only reason
     * nowadays why this method exists.
     *
     * This is checked:
     * 1. If the record has a "online pendant" (t3ver_oid > 0), it overrides the "pid" with the one from the online version.
     * 2. If a record is a live version, check if there is a moved version in this workspace, and override the LIVE version with the new moved "pid" value.
     *
     * Used whenever you are tracking something back, like making the root line.
     *
     * Principle; Record offline! => Find online?
     *
     * @param string $table Table name
     * @param array $rr Record array passed by reference. As minimum, "pid" and "uid" fields must exist! Having "t3ver_state" and "t3ver_wsid" is nice and will save you a DB query.
     * @see BackendUtility::fixVersioningPid()
     * @see versionOL()
     * @deprecated will be removed in TYPO3 v12, use versionOL() directly to achieve the same result.
     */
    public function fixVersioningPid($table, &$rr)
    {
        trigger_error('PageRepository->fixVersioningPid() will be removed in TYPO3 v12, use PageRepository->versionOL() instead.', E_USER_DEPRECATED);
        if ($this->versioningWorkspaceId <= 0) {
            return;
        }
        if (!is_array($rr)) {
            return;
        }
        if (!$this->hasTableWorkspaceSupport($table)) {
            return;
        }
        $uid = (int)$rr['uid'];
        $workspaceId = 0;
        $versionState = null;
        // Check values for t3ver_state and t3ver_wsid
        if (isset($rr['t3ver_wsid']) && isset($rr['t3ver_state'])) {
            // If "t3ver_state" is already a field, just set the needed values
            $workspaceId = (int)$rr['t3ver_wsid'];
            $versionState = (int)$rr['t3ver_state'];
        } elseif ($uid > 0) {
            // Otherwise we have to expect "uid" to be in the record and look up based
            // on this:
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $newPidRec = $queryBuilder->select('t3ver_wsid', 't3ver_state')
                ->from($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
                ->execute()
                ->fetchAssociative();

            if (is_array($newPidRec)) {
                $workspaceId = (int)$newPidRec['t3ver_wsid'];
                $versionState = (int)$newPidRec['t3ver_state'];
            }
        }

        // Workspace does not match, so this is skipped
        if ($workspaceId !== (int)$this->versioningWorkspaceId) {
            return;
        }
        // Changing PID in case there is a move pointer
        // This happens if the $uid is still a live version but the overlay happened (via t3ver_oid) and the t3ver_state was
        // Changed to MOVE_POINTER. This logic happens in versionOL(), where the "pid" of the live version is kept.
        if ($versionState === VersionState::MOVE_POINTER && $movedPageId = $this->getMovedPidOfVersionedRecord($table, $uid)) {
            $rr['_ORIG_pid'] = $rr['pid'];
            $rr['pid'] = $movedPageId;
        }
    }

    /**
     * Versioning Preview Overlay
     *
     * ONLY active when backend user is previewing records. MUST NEVER affect a site
     * served which is not previewed by backend users!!!
     *
     * Generally ALWAYS used when records are selected based on uid or pid. If
     * records are selected on other fields than uid or pid (eg. "email = ....") then
     * usage might produce undesired results and that should be evaluated on
     * individual basis.
     *
     * Principle; Record online! => Find offline?
     *
     * @param string $table Table name
     * @param array $row Record array passed by reference. As minimum, the "uid", "pid" and "t3ver_state" fields must exist! The record MAY be set to FALSE in which case the calling function should act as if the record is forbidden to access!
     * @param bool $unsetMovePointers If set, the $row is cleared in case it is a move-pointer. This is only for preview of moved records (to remove the record from the original location so it appears only in the new location)
     * @param bool $bypassEnableFieldsCheck Unless this option is TRUE, the $row is unset if enablefields for BOTH the version AND the online record deselects it. This is because when versionOL() is called it is assumed that the online record is already selected with no regards to it's enablefields. However, after looking for a new version the online record enablefields must ALSO be evaluated of course. This is done all by this function!
     * @see fixVersioningPid()
     * @see BackendUtility::workspaceOL()
     */
    public function versionOL($table, &$row, $unsetMovePointers = false, $bypassEnableFieldsCheck = false)
    {
        if ($this->versioningWorkspaceId > 0 && is_array($row)) {
            // implode(',',array_keys($row)) = Using fields from original record to make
            // sure no additional fields are selected. This is best for eg. getPageOverlay()
            // Computed properties are excluded since those would lead to SQL errors.
            $fieldNames = implode(',', array_keys($this->purgeComputedProperties($row)));
            // will overlay any incoming moved record with the live record, which in turn
            // will be overlaid with its workspace version again to fetch both PID fields.
            $incomingRecordIsAMoveVersion = (int)($row['t3ver_oid'] ?? 0) > 0 && (int)($row['t3ver_state'] ?? 0) === VersionState::MOVE_POINTER;
            if ($incomingRecordIsAMoveVersion) {
                // Fetch the live version again if the given $row is a move pointer, so we know the original PID
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $row = $queryBuilder->select(...GeneralUtility::trimExplode(',', $fieldNames, true))
                    ->from($table)
                    ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$row['t3ver_oid'], \PDO::PARAM_INT)))
                    ->executeQuery()
                    ->fetchAssociative();
            }
            if ($wsAlt = $this->getWorkspaceVersionOfRecord($this->versioningWorkspaceId, $table, $row['uid'], $fieldNames, $bypassEnableFieldsCheck)) {
                if (is_array($wsAlt)) {
                    $rowVersionState = VersionState::cast($wsAlt['t3ver_state'] ?? null);
                    if ($rowVersionState->equals(VersionState::MOVE_POINTER)) {
                        // For move pointers, store the actual live PID in the _ORIG_pid
                        // The only place where PID is actually different in a workspace
                        $wsAlt['_ORIG_pid'] = $row['pid'];
                    }
                    // For versions of single elements or page+content, preserve online UID
                    // (this will produce true "overlay" of element _content_, not any references)
                    // For new versions there is no online counterpart
                    if (!$rowVersionState->equals(VersionState::NEW_PLACEHOLDER)) {
                        $wsAlt['_ORIG_uid'] = $wsAlt['uid'];
                    }
                    $wsAlt['uid'] = $row['uid'];
                    // Changing input record to the workspace version alternative:
                    $row = $wsAlt;
                    // Unset record if it turned out to be deleted in workspace
                    if ($rowVersionState->equals(VersionState::DELETE_PLACEHOLDER)) {
                        $row = false;
                    }
                    // Check if move-pointer in workspace (unless if a move-placeholder is the
                    // reason why it appears!):
                    // You have to specifically set $unsetMovePointers in order to clear these
                    // because it is normally a display issue if it should be shown or not.
                    if ($rowVersionState->equals(VersionState::MOVE_POINTER) && !$incomingRecordIsAMoveVersion && $unsetMovePointers) {
                        // Unset record if it turned out to be deleted in workspace
                        $row = false;
                    }
                } else {
                    // No version found, then check if online version is dummy-representation
                    // Notice, that unless $bypassEnableFieldsCheck is TRUE, the $row is unset if
                    // enablefields for BOTH the version AND the online record deselects it. See
                    // note for $bypassEnableFieldsCheck
                    /** @var VersionState $versionState */
                    $versionState = VersionState::cast($row['t3ver_state'] ?? 0);
                    if ($wsAlt <= -1 || $versionState->indicatesPlaceholder()) {
                        // Unset record if it turned out to be "hidden"
                        $row = false;
                    }
                }
            }
        }
    }

    /**
     * Returns the PID of the new (moved) location within a version, when a $liveUid is given.
     *
     * Please note: This is only performed within a workspace.
     * This was previously stored in the move placeholder's PID, but move pointer's PID and move placeholder's PID
     * are the same since TYPO3 v10, so the MOVE_POINTER is queried.
     *
     * @param string $table Table name
     * @param int $liveUid Record UID of online version
     * @return int|null If found, the Page ID of the moved record, otherwise null.
     */
    protected function getMovedPidOfVersionedRecord(string $table, int $liveUid): ?int
    {
        if ($this->versioningWorkspaceId <= 0) {
            return null;
        }
        if (!$this->hasTableWorkspaceSupport($table)) {
            return null;
        }
        // Select workspace version of record
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder->select('pid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    't3ver_state',
                    $queryBuilder->createNamedParameter(
                        (string)VersionState::cast(VersionState::MOVE_POINTER),
                        \PDO::PARAM_INT
                    )
                ),
                $queryBuilder->expr()->eq(
                    't3ver_oid',
                    $queryBuilder->createNamedParameter($liveUid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($this->versioningWorkspaceId, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetchAssociative();

        if (is_array($row)) {
            return (int)$row['pid'];
        }
        return null;
    }

    /**
     * Select the version of a record for a workspace
     *
     * @param int $workspace Workspace ID
     * @param string $table Table name to select from
     * @param int $uid Record uid for which to find workspace version.
     * @param string $fields Fields to select, `*` is the default - If a custom list is set, make sure the list
     *                       contains the `uid` field. It's mandatory for further processing of the result row.
     * @param bool $bypassEnableFieldsCheck If TRUE, enablefields are not checked for.
     * @return mixed If found, return record, otherwise other value: Returns 1 if version was sought for but not found, returns -1/-2 if record (offline/online) existed but had enableFields that would disable it. Returns FALSE if not in workspace or no versioning for record. Notice, that the enablefields of the online record is also tested.
     * @see BackendUtility::getWorkspaceVersionOfRecord()
     */
    public function getWorkspaceVersionOfRecord($workspace, $table, $uid, $fields = '*', $bypassEnableFieldsCheck = false)
    {
        if ($workspace !== 0 && $this->hasTableWorkspaceSupport($table)) {
            $workspace = (int)$workspace;
            $uid = (int)$uid;
            // Select workspace version of record, only testing for deleted.
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $newrow = $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields, true))
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
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            // If version found, check if it could have been selected with enableFields on
            // as well:
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context));
            // Remove the frontend workspace restriction because we are testing a version record
            $queryBuilder->getRestrictions()->removeByType(FrontendWorkspaceRestriction::class);
            $queryBuilder->select('uid')
                ->from($table)
                ->setMaxResults(1);

            if (is_array($newrow)) {
                $queryBuilder->where(
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
                );
                if ($bypassEnableFieldsCheck || $queryBuilder->executeQuery()->fetchOne()) {
                    // Return offline version, tested for its enableFields.
                    return $newrow;
                }
                // Return -1 because offline version was de-selected due to its enableFields.
                return -1;
            }
            // OK, so no workspace version was found. Then check if online version can be
            // selected with full enable fields and if so, return 1:
            $queryBuilder->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
            );
            if ($bypassEnableFieldsCheck || $queryBuilder->executeQuery()->fetchOne()) {
                // Means search was done, but no version found.
                return 1;
            }
            // Return -2 because the online record was de-selected due to its enableFields.
            return -2;
        }
        // No look up in database because versioning not enabled / or workspace not
        // offline
        return false;
    }

    /**
     * Purges computed properties from database rows,
     * such as _ORIG_uid or _ORIG_pid for instance.
     *
     * @param array $row
     * @return array
     */
    protected function purgeComputedProperties(array $row)
    {
        foreach ($this->computedPropertyNames as $computedPropertyName) {
            if (array_key_exists($computedPropertyName, $row)) {
                unset($row[$computedPropertyName]);
            }
        }
        return $row;
    }

    /**
     * @return VariableFrontend
     */
    protected function getRuntimeCache(): VariableFrontend
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }

    protected function hasTableWorkspaceSupport(string $tableName): bool
    {
        return !empty($GLOBALS['TCA'][$tableName]['ctrl']['versioningWS']);
    }
}
