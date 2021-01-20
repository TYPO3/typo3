<?php
namespace TYPO3\CMS\Frontend\Page;

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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Error\Http\ShortcutTargetPageNotFoundException;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
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
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * List of all deprecated public properties
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'versioningPreview' => 'Using $versioningPreview of class PageRepository is discouraged, just use versioningWorkspaceId to determine if a workspace should be previewed.',
        'workspaceCache' => 'Using $workspaceCache of class PageRepository from the outside is discouraged, as this only reflects a local runtime cache.',
        'error_getRootLine' => 'Using $error_getRootLine of class PageRepository from the outside is deprecated as this property only exists for legacy reasons.',
        'error_getRootLine_failPid' => 'Using $error_getRootLine_failPid of class PageRepository from the outside is deprecated as this property only exists for legacy reasons.',
        'sys_language_uid' => 'Using $sys_language_uid of class PageRepository from the outside is deprecated as this information is now stored within the Context Language Aspect given by the constructor.',
        'versioningWorkspaceId' => 'Using $versioningWorkspaceId of class PageRepository from the outside is deprecated as this information is now stored within the Context Workspace Aspect given by the constructor.',
    ];

    /**
     * List of all deprecated public methods
     * @var array
     */
    protected $deprecatedPublicMethods = [
        'init' => 'init() is now called implicitly on object creation, and is not necessary anymore to be called explicitly. Calling init() will throw an error in TYPO3 v10.0.',
        'movePlhOL' => 'Using movePlhOL is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'getMovePlaceholder' => 'Using getMovePlaceholder is deprecated and will not be possible anymore in TYPO3 v10.0.'
    ];

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
     * @var int
     * @deprecated will be removed in TYPO3 v10.0, all occurrences should be replaced with the language->id() aspect property in TYPO3 v10.0
     * However, the usage within the class is kept as the property could be overwritten by third-party classes
     */
    protected $sys_language_uid = 0;

    /**
     * If TRUE, versioning preview of other record versions is allowed. THIS MUST
     * ONLY BE SET IF the page is not cached and truly previewed by a backend
     * user!!!
     *
     * @var bool
     * @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0. As $versioningWorkspaceId now indicates what records to fetch.
     */
    protected $versioningPreview = false;

    /**
     * Workspace ID for preview
     * If > 0, versioning preview of other record versions is allowed. THIS MUST
     * ONLY BE SET IF the page is not cached and truly previewed by a backend
     * user!
     *
     * @var int
     * @deprecated This method will be kept protected from TYPO3 v10.0 on, instantiate a new pageRepository object with a custom workspace aspect to override this setting.
     */
    protected $versioningWorkspaceId = 0;

    /**
     * @var array
     */
    protected $workspaceCache = [];

    /**
     * Error string set by getRootLine()
     *
     * @var string
     */
    protected $error_getRootLine = '';

    /**
     * Error uid set by getRootLine()
     *
     * @var int
     */
    protected $error_getRootLine_failPid = 0;

    /**
     * @var array
     */
    protected $cache_getPage = [];

    /**
     * @var array
     */
    protected $cache_getPage_noCheck = [];

    /**
     * @var array
     */
    protected $cache_getPageIdFromAlias = [];

    /**
     * @var array
     */
    protected $cache_getMountPointInfo = [];

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
                    $expressionBuilder->lt('pages.doktype', 200)
                );
            } else {
                // add starttime / endtime, and check for hidden/deleted
                // Filter out new/deleted place-holder pages in case we are NOT in a
                // versioning preview (that means we are online!)
                $this->where_hid_del = ' AND ' . (string)$expressionBuilder->andX(
                    QueryHelper::stripLogicalOperatorPrefix(
                        $this->enableFields('pages', $show_hidden, ['fe_group' => true], true)
                    ),
                    $expressionBuilder->lt('pages.doktype', 200)
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
                    $this->sys_language_uid
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

        $row = $queryBuilder->execute()->fetch();
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
            ->execute()
            ->fetch();

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
     * Returns the $row of the first web-page in the tree (for the default menu...)
     *
     * @param int $uid The page id for which to fetch first subpages (PID)
     * @return mixed If found: The page record (with overlaid localized fields, if any). If NOT found: blank value (not array!)
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::fetch_the_id()
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use getMenu() to fetch all subpages of a page.
     */
    public function getFirstWebPage($uid)
    {
        trigger_error('PageRepository->getFirstWebPage() will be removed in TYPO3 v10.0. Use "getMenu()" instead.', E_USER_DEPRECATED);
        $output = '';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)),
                QueryHelper::stripLogicalOperatorPrefix($this->where_hid_del),
                QueryHelper::stripLogicalOperatorPrefix($this->where_groupAccess)
            )
            ->orderBy('sorting')
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if ($row) {
            $this->versionOL('pages', $row);
            if (is_array($row)) {
                $output = $this->getPageOverlay($row);
            }
        }
        return $output;
    }

    /**
     * Returns a pagerow for the page with alias $alias
     *
     * @param string $alias The alias to look up the page uid for.
     * @return int Returns page uid (int) if found, otherwise 0 (zero)
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::checkAndSetAlias(), ContentObjectRenderer::typoLink()
     */
    public function getPageIdFromAlias($alias)
    {
        $alias = strtolower($alias);
        $cacheIdentifier = 'PageRepository_getPageIdFromAlias_' . md5($alias);
        $cache = $this->getRuntimeCache();
        $cacheEntry = $cache->get($cacheIdentifier);
        if ($cacheEntry !== false) {
            return $cacheEntry;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $row = $queryBuilder->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('alias', $queryBuilder->createNamedParameter($alias, \PDO::PARAM_STR)),
                // "AND pid>=0" because of versioning (means that aliases sent MUST be online!)
                $queryBuilder->expr()->gte('pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if ($row) {
            $cache->set($cacheIdentifier, $row['uid']);
            return $row['uid'];
        }
        $cache->set($cacheIdentifier, 0);
        return 0;
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
                    $languageAspect->getContentId(),
                    $languageAspect->getOverlayType() === $languageAspect::OVERLAYS_MIXED ? '1' : 'hideNonTranslated'
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
        if ($languageUid === -1) {
            trigger_error('Calling getPageOverlay() with "-1" as languageId is discouraged and will be unsupported in TYPO3 v10.0. Omit the parameter or use "null" instead.', E_USER_DEPRECATED);
            $languageUid = null;
        }
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
        } elseif ($languageUid < 0) {
            trigger_error('Calling getPagesOverlay() with "-1" as languageId is discouraged and will be unsupported in TYPO3 v10.0. Omit the parameter or use "null" instead.', E_USER_DEPRECATED);
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
                    $pageIds[] = (int)$origPage['uid'];
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
                if (isset($overlays[$origPage['uid']])) {
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
        if (GeneralUtility::hideIfDefaultLanguage($page['l18n_cfg']) && (!$languageUid || $languageUid && !$page['_PAGES_OVERLAY'])) {
            return false;
        }
        if ($languageUid > 0 && GeneralUtility::hideIfNotTranslated($page['l18n_cfg'])) {
            if (!$page['_PAGES_OVERLAY'] || (int)$page['_PAGES_OVERLAY_LANGUAGE'] !== $languageUid) {
                return false;
            }
        } elseif ($languageUid > 0) {
            $languageUids = array_merge([$languageUid], $this->getLanguageFallbackChain($languageAspect));
            return in_array((int)$page['sys_language_uid'], $languageUids, true);
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
        return array_filter($languageAspect->getFallbackChain(), function ($item) {
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
                ->execute();

            // Create a list of rows ordered by values in $languageUids
            while ($row = $result->fetch()) {
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
                    unset($row['uid'], $row['pid'], $row['alias']);
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
     * @param int $sys_language_content Pointer to the sys_language uid for content on the site.
     * @param string $OLmode Overlay mode. If "hideNonTranslated" then records without translation will not be returned  un-translated but unset (and return value is FALSE)
     * @throws \UnexpectedValueException
     * @return mixed Returns the input record, possibly overlaid with a translation.  But if $OLmode is "hideNonTranslated" then it will return FALSE if no translation is found.
     */
    public function getRecordOverlay($table, $row, $sys_language_content, $OLmode = '')
    {
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
            // TODO: Fix call stack to prevent this situation in the first place
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
                    $olrow = $queryBuilder->select('*')
                        ->from($table)
                        ->where(
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($row['pid'], \PDO::PARAM_INT)
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
                        ->execute()
                        ->fetch();

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
                            if ($fN !== 'uid' && $fN !== 'pid' && isset($olrow[$fN])) {
                                $row[$fN] = $olrow[$fN];
                            } elseif ($fN === 'uid') {
                                $row['_LOCALIZED_UID'] = $olrow['uid'];
                            }
                        }
                    } elseif ($OLmode === 'hideNonTranslated' && (int)$row[$tableControl['languageField']] === 0) {
                        // Unset, if non-translated records should be hidden. ONLY done if the source
                        // record really is default language and not [All] in which case it is allowed.
                        unset($row);
                    }
                } elseif ($sys_language_content != $row[$tableControl['languageField']]) {
                    unset($row);
                }
            } else {
                // When default language is displayed, we never want to return a record carrying
                // another language!
                if ($row[$tableControl['languageField']] > 0) {
                    unset($row);
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
     * @param string $fields List of fields to select. Default is "*" = all
     * @param string $sortField The field to sort by. Default is "sorting
     * @param string $additionalWhereClause Optional additional where clauses. Like "AND title like '%some text%'" for instance.
     * @param bool $checkShortcuts Check if shortcuts exist, checks by default
     * @return array Array with key/value pairs; keys are page-uid numbers. values are the corresponding page records (with overlaid localized fields, if any)
     * @see self::getPageShortcut(), \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject::makeMenu()
     */
    public function getMenu($pageId, $fields = '*', $sortField = 'sorting', $additionalWhereClause = '', $checkShortcuts = true)
    {
        return $this->getSubpagesForPages((array)$pageId, $fields, $sortField, $additionalWhereClause, $checkShortcuts);
    }

    /**
     * Returns an array with page-rows for pages with uid in $pageIds.
     *
     * This is used for menus. If there are mount points in overlay mode
     * the _MP_PARAM field is set to the correct MPvar.
     *
     * @param int[] $pageIds Array of page ids to fetch
     * @param string $fields List of fields to select. Default is "*" = all
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
     * @param string $fields fields to select
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
        $queryBuilder->getRestrictions()->removeAll();

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
        $result = $res->execute();

        $pages = [];
        while ($page = $result->fetch()) {
            $originalUid = $page['uid'];

            // Versioning Preview Overlay
            $this->versionOL('pages', $page, true);
            // Skip if page got disabled due to version overlay
            // (might be delete or move placeholder)
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
                // If a shortcut mode is set and no valid page is given to select subpags
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
                ->execute()
                ->fetchColumn();

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
     * @param int $shortcutFieldValue The value of the "shortcut" field from the pages record
     * @param int $shortcutMode The shortcut mode: 1 will select first subpage, 2 a random subpage, 3 the parent page; default is the page pointed to by $SC
     * @param int $thisUid The current page UID of the page which is a shortcut
     * @param int $iteration Safety feature which makes sure that the function is calling itself recursively max 20 times (since this function can find shortcuts to other shortcuts to other shortcuts...)
     * @param array $pageLog An array filled with previous page uids tested by the function - new page uids are evaluated against this to avoid going in circles.
     * @param bool $disableGroupCheck If true, the group check is disabled when fetching the target page (needed e.g. for menu generation)
     *
     * @throws \RuntimeException
     * @throws ShortcutTargetPageNotFoundException
     * @return mixed Returns the page record of the page that the shortcut pointed to.
     * @internal
     * @see getPageAndRootline()
     */
    public function getPageShortcut($shortcutFieldValue, $shortcutMode, $thisUid, $iteration = 20, $pageLog = [], $disableGroupCheck = false)
    {
        $idArray = GeneralUtility::intExplode(',', $shortcutFieldValue);
        // Find $page record depending on shortcut mode:
        switch ($shortcutMode) {
            case self::SHORTCUT_MODE_FIRST_SUBPAGE:
            case self::SHORTCUT_MODE_RANDOM_SUBPAGE:
                $pageArray = $this->getMenu($idArray[0] ?: $thisUid, '*', 'sorting', 'AND pages.doktype<199 AND pages.doktype!=' . self::DOKTYPE_BE_USER_SECTION);
                $pO = 0;
                if ($shortcutMode == self::SHORTCUT_MODE_RANDOM_SUBPAGE && !empty($pageArray)) {
                    $pO = (int)rand(0, count($pageArray) - 1);
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
        if ($page['doktype'] == self::DOKTYPE_SHORTCUT) {
            if (!in_array($page['uid'], $pageLog) && $iteration > 0) {
                $pageLog[] = $page['uid'];
                $page = $this->getPageShortcut($page['shortcut'], $page['shortcut_mode'], $page['uid'], $iteration - 1, $pageLog, $disableGroupCheck);
            } else {
                $pageLog[] = $page['uid'];
                $message = 'Page shortcuts were looping in uids ' . implode(',', $pageLog) . '...!';
                $this->logger->error($message);
                throw new \RuntimeException($message, 1294587212);
            }
        }
        // Return resulting page:
        return $page;
    }

    /**
     * Will find the page carrying the domain record matching the input domain.
     *
     * @param string $domain Domain name to search for. Eg. "www.typo3.com". Typical the HTTP_HOST value.
     * @param string $path Path for the current script in domain. Eg. "/somedir/subdir". Typ. supplied by \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME')
     * @param string $request_uri Request URI: Used to get parameters from if they should be appended. Typ. supplied by \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')
     * @return mixed If found, returns integer with page UID where found. Otherwise blank. Might exit if location-header is sent, see description.
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::findDomainRecord()
     * @deprecated will be removed in TYPO3 v10.0.
     */
    public function getDomainStartPage($domain, $path = '', $request_uri = '')
    {
        trigger_error('This method will be removed in TYPO3 v10.0. As the SiteResolver middleware resolves the domain start page.', E_USER_DEPRECATED);
        $domain = explode(':', $domain);
        $domain = strtolower(preg_replace('/\\.$/', '', $domain[0]));
        // Removing extra trailing slashes
        $path = trim(preg_replace('/\\/[^\\/]*$/', '', $path));
        // Appending to domain string
        $domain .= $path;
        $domain = preg_replace('/\\/*$/', '', $domain);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select(
                'pages.uid'
            )
            ->from('pages')
            ->from('sys_domain')
            ->where(
                $queryBuilder->expr()->eq('pages.uid', $queryBuilder->quoteIdentifier('sys_domain.pid')),
                $queryBuilder->expr()->eq(
                    'sys_domain.hidden',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq(
                        'sys_domain.domainName',
                        $queryBuilder->createNamedParameter($domain, \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_domain.domainName',
                        $queryBuilder->createNamedParameter($domain . '/', \PDO::PARAM_STR)
                    )
                ),
                QueryHelper::stripLogicalOperatorPrefix($this->where_hid_del),
                QueryHelper::stripLogicalOperatorPrefix($this->where_groupAccess)
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if (!$row) {
            return '';
        }
        return $row['uid'];
    }

    /**
     * Returns array with fields of the pages from here ($uid) and back to the root
     *
     * NOTICE: This function only takes deleted pages into account! So hidden,
     * starttime and endtime restricted pages are included no matter what.
     *
     * Further: If any "recycler" page is found (doktype=255) then it will also block
     * for the rootline)
     *
     * If you want more fields in the rootline records than default such can be added
     * by listing them in $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields']
     *
     * @param int $uid The page uid for which to seek back to the page tree root.
     * @param string $MP Commalist of MountPoint parameters, eg. "1-2,3-4" etc. Normally this value comes from the GET var, MP
     * @param bool $ignoreMPerrors If set, some errors related to Mount Points in root line are ignored.
     * @throws \Exception
     * @throws \RuntimeException
     * @return array Array with page records from the root line as values. The array is ordered with the outer records first and root record in the bottom. The keys are numeric but in reverse order. So if you traverse/sort the array by the numeric keys order you will get the order from root and out. If an error is found (like eternal looping or invalid mountpoint) it will return an empty array.
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getPageAndRootline()
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getRootLine($uid, $MP = '', $ignoreMPerrors = null)
    {
        trigger_error('Calling PageRepository->getRootLine() will be removed in TYPO3 v10.0. Use RootlineUtility directly.', E_USER_DEPRECATED);
        $rootline = GeneralUtility::makeInstance(RootlineUtility::class, $uid, $MP, $this->context);
        try {
            return $rootline->get();
        } catch (\RuntimeException $ex) {
            if ($ignoreMPerrors) {
                $this->error_getRootLine = $ex->getMessage();
                if (substr($this->error_getRootLine, -7) === 'uid -1.') {
                    $this->error_getRootLine_failPid = -1;
                }
                return [];
            }
            if ($ex->getCode() === 1343589451) {
                /** @see \TYPO3\CMS\Core\Utility\RootlineUtility::getRecordArray */
                return [];
            }
            throw $ex;
        }
    }

    /**
     * Returns the redirect URL for the input page row IF the doktype is set to 3.
     *
     * @param array $pagerow The page row to return URL type for
     * @return string|bool The URL from based on the data from "pages:url". False if not found.
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::initializeRedirectUrlHandlers()
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
                    $redirectTo = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $redirectTo;
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
     * @see: https://decisions.typo3.org/t/supporting-or-prohibiting-recursive-mount-points/165/3
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

            $pageRec = $queryBuilder->select('uid', 'pid', 'doktype', 'mount_pid', 'mount_pid_ol', 't3ver_state')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        'doktype',
                        $queryBuilder->createNamedParameter(255, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();

            // Only look for version overlay if page record is not supplied; This assumes
            // that the input record is overlaid with preview version, if any!
            $this->versionOL('pages', $pageRec);
        }
        // Set first Page uid:
        if (!$firstPageUid) {
            $firstPageUid = $pageRec['uid'];
        }
        // Look for mount pid value plus other required circumstances:
        $mount_pid = (int)$pageRec['mount_pid'];
        if (is_array($pageRec) && (int)$pageRec['doktype'] === self::DOKTYPE_MOUNTPOINT && $mount_pid > 0 && !in_array($mount_pid, $prevMountPids, true)) {
            // Get the mount point record (to verify its general existence):
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $mountRec = $queryBuilder->select('uid', 'pid', 'doktype', 'mount_pid', 'mount_pid_ol', 't3ver_state')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($mount_pid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        'doktype',
                        $queryBuilder->createNamedParameter(255, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();

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
                    'mount_pid_rec' => $mountRec
                ];
            } else {
                // Means, there SHOULD have been a mount point, but there was none!
                $result = -1;
            }
        }
        $cache->set($cacheIdentifier, $result);
        return $result;
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
                ->execute()
                ->fetch();

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
                            ->execute()
                            ->fetchColumn();
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
     * @param string $fields The fields to select, default is "*
     * @param bool $noWSOL If set, no version overlay is applied
     * @return mixed Returns array (the record) if found, otherwise blank/0 (zero)
     * @see getPage_noCheck()
     */
    public function getRawRecord($table, $uid, $fields = '*', $noWSOL = null)
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
                ->execute()
                ->fetch();

            if ($row) {
                if ($noWSOL !== null) {
                    trigger_error('The fourth parameter of PageRepository->getRawRecord() will be removed in TYPO3 v10.0. Use a SQL statement directly.', E_USER_DEPRECATED);
                }
                // @deprecated - remove this if-clause in TYPO3 v10.0
                if (!$noWSOL) {
                    $this->versionOL($table, $row);
                }
                if (is_array($row)) {
                    return $row;
                }
            }
        }
        return 0;
    }

    /**
     * Selects records based on matching a field (ei. other than UID) with a value
     *
     * @param string $theTable The table name to search, eg. "pages" or "tt_content
     * @param string $theField The fieldname to match, eg. "uid" or "alias
     * @param string $theValue The value that fieldname must match, eg. "123" or "frontpage
     * @param string $whereClause Optional additional WHERE clauses put in the end of the query. DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
     * @param string $groupBy Optional GROUP BY field(s). If none, supply blank string.
     * @param string $orderBy Optional ORDER BY field(s). If none, supply blank string.
     * @param string $limit Optional LIMIT value ([begin,]max). If none, supply blank string.
     * @return mixed Returns array (the record) if found, otherwise nothing (void)
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0
     */
    public function getRecordsByField($theTable, $theField, $theValue, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '')
    {
        trigger_error('PageRepository->getRecordsByField() should not be used any longer, this method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        if (is_array($GLOBALS['TCA'][$theTable])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($theTable);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $queryBuilder->select('*')
                ->from($theTable)
                ->where($queryBuilder->expr()->eq($theField, $queryBuilder->createNamedParameter($theValue)));

            if ($whereClause !== '') {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($whereClause));
            }

            if ($groupBy !== '') {
                $queryBuilder->groupBy(QueryHelper::parseGroupBy($groupBy));
            }

            if ($orderBy !== '') {
                foreach (QueryHelper::parseOrderBy($orderBy) as $orderPair) {
                    [$fieldName, $order] = $orderPair;
                    $queryBuilder->addOrderBy($fieldName, $order);
                }
            }

            if ($limit !== '') {
                if (strpos($limit, ',')) {
                    $limitOffsetAndMax = GeneralUtility::intExplode(',', $limit);
                    $queryBuilder->setFirstResult((int)$limitOffsetAndMax[0]);
                    $queryBuilder->setMaxResults((int)$limitOffsetAndMax[1]);
                } else {
                    $queryBuilder->setMaxResults((int)$limit);
                }
            }

            $rows = $queryBuilder->execute()->fetchAll();

            if (!empty($rows)) {
                return $rows;
            }
        }
        return null;
    }

    /********************************
     *
     * Standard clauses
     *
     ********************************/

    /**
     * Returns the "AND NOT deleted" clause for the tablename given IF
     * $GLOBALS['TCA'] configuration points to such a field.
     *
     * @param string $table Tablename
     * @return string
     * @see enableFields()
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, use QueryBuilders' Restrictions directly instead.
     */
    public function deleteClause($table)
    {
        trigger_error('PageRepository->deleteClause() will be removed in TYPO3 v10.0. The delete clause can be applied via the DeletedRestrictions via QueryBuilder.', E_USER_DEPRECATED);
        return $GLOBALS['TCA'][$table]['ctrl']['delete'] ? ' AND ' . $table . '.' . $GLOBALS['TCA'][$table]['ctrl']['delete'] . '=0' : '';
    }

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
     * @param bool $noVersionPreview If set, enableFields will be applied regardless of any versioning preview settings which might otherwise disable enableFields
     * @throws \InvalidArgumentException
     * @return string The clause starting like " AND ...=... AND ...=...
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::enableFields()
     */
    public function enableFields($table, $show_hidden = -1, $ignore_array = [], $noVersionPreview = false)
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
            if ($ctrl['delete']) {
                $constraints[] = $expressionBuilder->eq($table . '.' . $ctrl['delete'], 0);
            }
            if ($ctrl['versioningWS'] ?? false) {
                if (!$this->versioningWorkspaceId > 0) {
                    // Filter out placeholder records (new/moved/deleted items)
                    // in case we are NOT in a versioning preview (that means we are online!)
                    $constraints[] = $expressionBuilder->lte(
                        $table . '.t3ver_state',
                        new VersionState(VersionState::DEFAULT_STATE)
                    );
                } elseif ($table !== 'pages') {
                    // show only records of live and of the current workspace
                    // in case we are in a versioning preview
                    $constraints[] = $expressionBuilder->orX(
                        $expressionBuilder->eq($table . '.t3ver_wsid', 0),
                        $expressionBuilder->eq($table . '.t3ver_wsid', (int)$this->versioningWorkspaceId)
                    );
                }

                // Filter out versioned records
                if (!$noVersionPreview && empty($ignore_array['pid'])) {
                    $constraints[] = $expressionBuilder->neq($table . '.pid', -1);
                }
            }

            // Enable fields:
            if (is_array($ctrl['enablecolumns'])) {
                // In case of versioning-preview, enableFields are ignored (checked in
                // versionOL())
                if ($this->versioningWorkspaceId <= 0 || !$ctrl['versioningWS'] || $noVersionPreview) {
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
                        'ctrl' => $ctrl
                    ];
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['addEnableColumns'] ?? [] as $_funcRef) {
                        $constraints[] = QueryHelper::stripLogicalOperatorPrefix(
                            GeneralUtility::callUserFunction($_funcRef, $_params, $this)
                        );
                    }
                }
            }
        } else {
            throw new \InvalidArgumentException('There is no entry in the $TCA array for the table "' . $table . '". This means that the function enableFields() is ' . 'called with an invalid table name as argument.', 1283790586);
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
     * Will look if the "pid" value of the input record is -1 (it is an offline
     * version) and if the table supports versioning; if so, it will translate the -1
     * PID into the PID of the original record.
     *
     * Used whenever you are tracking something back, like making the root line.
     *
     * Principle; Record offline! => Find online?
     *
     * @param string $table Table name
     * @param array $rr Record array passed by reference. As minimum, "pid" and "uid" fields must exist! "t3ver_oid" and "t3ver_wsid" is nice and will save you a DB query.
     * @see BackendUtility::fixVersioningPid(), versionOL(), getRootLine()
     */
    public function fixVersioningPid($table, &$rr)
    {
        if ($this->versioningWorkspaceId > 0 && is_array($rr) && (int)$rr['pid'] === -1 && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
            $oid = 0;
            $wsid = 0;
            // Check values for t3ver_oid and t3ver_wsid:
            if (isset($rr['t3ver_oid']) && isset($rr['t3ver_wsid'])) {
                // If "t3ver_oid" is already a field, just set this:
                $oid = $rr['t3ver_oid'];
                $wsid = $rr['t3ver_wsid'];
            } else {
                // Otherwise we have to expect "uid" to be in the record and look up based
                // on this:
                $uid = (int)$rr['uid'];
                if ($uid > 0) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                    $queryBuilder->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $newPidRec = $queryBuilder->select('t3ver_oid', 't3ver_wsid')
                        ->from($table)
                        ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
                        ->execute()
                        ->fetch();

                    if (is_array($newPidRec)) {
                        $oid = $newPidRec['t3ver_oid'];
                        $wsid = $newPidRec['t3ver_wsid'];
                    }
                }
            }
            // If workspace ids matches and ID of current online version is found, look up
            // the PID value of that:
            if ($oid && (int)$wsid === (int)$this->versioningWorkspaceId) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $oidRec = $queryBuilder->select('pid')
                    ->from($table)
                    ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($oid, \PDO::PARAM_INT)))
                    ->execute()
                    ->fetch();

                if (is_array($oidRec)) {
                    // SWAP uid as well? Well no, because when fixing a versioning PID happens it is
                    // assumed that this is a "branch" type page and therefore the uid should be
                    // kept (like in versionOL()). However if the page is NOT a branch version it
                    // should not happen - but then again, direct access to that uid should not
                    // happen!
                    $rr['_ORIG_pid'] = $rr['pid'];
                    $rr['pid'] = $oidRec['pid'];
                }
            }
        }
        // Changing PID in case of moving pointer:
        if ($movePlhRec = $this->getMovePlaceholder($table, $rr['uid'], 'pid')) {
            $rr['pid'] = $movePlhRec['pid'];
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
     * @see fixVersioningPid(), BackendUtility::workspaceOL()
     */
    public function versionOL($table, &$row, $unsetMovePointers = false, $bypassEnableFieldsCheck = false)
    {
        if ($this->versioningWorkspaceId > 0 && is_array($row)) {
            // will overlay any movePlhOL found with the real record, which in turn
            // will be overlaid with its workspace version if any.
            $movePldSwap = $this->movePlhOL($table, $row);
            // implode(',',array_keys($row)) = Using fields from original record to make
            // sure no additional fields are selected. This is best for eg. getPageOverlay()
            // Computed properties are excluded since those would lead to SQL errors.
            $fieldNames = implode(',', array_keys($this->purgeComputedProperties($row)));
            if ($wsAlt = $this->getWorkspaceVersionOfRecord($this->versioningWorkspaceId, $table, $row['uid'], $fieldNames, $bypassEnableFieldsCheck)) {
                if (is_array($wsAlt)) {
                    // Always fix PID (like in fixVersioningPid() above). [This is usually not
                    // the important factor for versioning OL]
                    // Keep the old (-1) - indicates it was a version...
                    $wsAlt['_ORIG_pid'] = $wsAlt['pid'];
                    // Set in the online versions PID.
                    $wsAlt['pid'] = $row['pid'];
                    // For versions of single elements or page+content, preserve online UID and PID
                    // (this will produce true "overlay" of element _content_, not any references)
                    // For page+content the "_ORIG_uid" should actually be used as PID for selection.
                    $wsAlt['_ORIG_uid'] = $wsAlt['uid'];
                    $wsAlt['uid'] = $row['uid'];
                    // Translate page alias as well so links are pointing to the _online_ page:
                    if ($table === 'pages') {
                        $wsAlt['alias'] = $row['alias'];
                    }
                    // Changing input record to the workspace version alternative:
                    $row = $wsAlt;
                    // Check if it is deleted/new
                    $rowVersionState = VersionState::cast($row['t3ver_state'] ?? null);
                    if (
                        $rowVersionState->equals(VersionState::NEW_PLACEHOLDER)
                        || $rowVersionState->equals(VersionState::DELETE_PLACEHOLDER)
                    ) {
                        // Unset record if it turned out to be deleted in workspace
                        $row = false;
                    }
                    // Check if move-pointer in workspace (unless if a move-placeholder is the
                    // reason why it appears!):
                    // You have to specifically set $unsetMovePointers in order to clear these
                    // because it is normally a display issue if it should be shown or not.
                    if (
                        (
                            $rowVersionState->equals(VersionState::MOVE_POINTER)
                            && !$movePldSwap
                        ) && $unsetMovePointers
                    ) {
                        // Unset record if it turned out to be deleted in workspace
                        $row = false;
                    }
                } else {
                    // No version found, then check if t3ver_state = VersionState::NEW_PLACEHOLDER
                    // (online version is dummy-representation)
                    // Notice, that unless $bypassEnableFieldsCheck is TRUE, the $row is unset if
                    // enablefields for BOTH the version AND the online record deselects it. See
                    // note for $bypassEnableFieldsCheck
                    /** @var \TYPO3\CMS\Core\Versioning\VersionState $versionState */
                    $versionState = VersionState::cast($row['t3ver_state']);
                    if ($wsAlt <= -1 || $versionState->indicatesPlaceholder()) {
                        // Unset record if it turned out to be "hidden"
                        $row = false;
                    }
                }
            }
        }
    }

    /**
     * Checks if record is a move-placeholder
     * (t3ver_state==VersionState::MOVE_PLACEHOLDER) and if so it will set $row to be
     * the pointed-to live record (and return TRUE) Used from versionOL
     *
     * @param string $table Table name
     * @param array $row Row (passed by reference) - only online records...
     * @return bool TRUE if overlay is made.
     * @see BackendUtility::movePlhOl()
     */
    protected function movePlhOL($table, &$row)
    {
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['versioningWS'])
            && (int)VersionState::cast($row['t3ver_state'])->equals(VersionState::MOVE_PLACEHOLDER)
        ) {
            $moveID = 0;
            // If t3ver_move_id is not found, then find it (but we like best if it is here)
            if (!isset($row['t3ver_move_id'])) {
                if ((int)$row['uid'] > 0) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                    $queryBuilder->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $moveIDRec = $queryBuilder->select('t3ver_move_id')
                        ->from($table)
                        ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($row['uid'], \PDO::PARAM_INT)))
                        ->execute()
                        ->fetch();

                    if (is_array($moveIDRec)) {
                        $moveID = $moveIDRec['t3ver_move_id'];
                    }
                }
            } else {
                $moveID = $row['t3ver_move_id'];
            }
            // Find pointed-to record.
            if ($moveID) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context));
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
                $origRow = $queryBuilder->select(...array_keys($this->purgeComputedProperties($row)))
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($moveID, \PDO::PARAM_INT)
                        )
                    )
                    ->setMaxResults(1)
                    ->execute()
                    ->fetch();

                if ($origRow) {
                    $row = $origRow;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns move placeholder of online (live) version
     *
     * @param string $table Table name
     * @param int $uid Record UID of online version
     * @param string $fields Field list, default is *
     * @return array If found, the record, otherwise nothing.
     * @see BackendUtility::getMovePlaceholder()
     */
    protected function getMovePlaceholder($table, $uid, $fields = '*')
    {
        $workspace = (int)$this->versioningWorkspaceId;
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) && $workspace > 0) {
            // Select workspace version of record:
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $row = $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields, true))
                ->from($table)
                ->where(
                    $queryBuilder->expr()->neq('pid', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        't3ver_state',
                        $queryBuilder->createNamedParameter(
                            (string)VersionState::cast(VersionState::MOVE_PLACEHOLDER),
                            \PDO::PARAM_INT
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_move_id',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter($workspace, \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();

            if (is_array($row)) {
                return $row;
            }
        }
        return false;
    }

    /**
     * Select the version of a record for a workspace
     *
     * @param int $workspace Workspace ID
     * @param string $table Table name to select from
     * @param int $uid Record uid for which to find workspace version.
     * @param string $fields Field list to select
     * @param bool $bypassEnableFieldsCheck If TRUE, enablefields are not checked for.
     * @return mixed If found, return record, otherwise other value: Returns 1 if version was sought for but not found, returns -1/-2 if record (offline/online) existed but had enableFields that would disable it. Returns FALSE if not in workspace or no versioning for record. Notice, that the enablefields of the online record is also tested.
     * @see BackendUtility::getWorkspaceVersionOfRecord()
     */
    public function getWorkspaceVersionOfRecord($workspace, $table, $uid, $fields = '*', $bypassEnableFieldsCheck = false)
    {
        if ($workspace !== 0 && !empty($GLOBALS['TCA'][$table]['ctrl']['versioningWS'])) {
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
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        't3ver_oid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter($workspace, \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();

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
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq(
                        't3ver_oid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_wsid',
                        $queryBuilder->createNamedParameter($workspace, \PDO::PARAM_INT)
                    )
                );
                if ($bypassEnableFieldsCheck || $queryBuilder->execute()->fetchColumn()) {
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
            if ($bypassEnableFieldsCheck || $queryBuilder->execute()->fetchColumn()) {
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
     * Checks if user has access to workspace.
     *
     * @param int $wsid Workspace ID
     * @return bool true if the backend user has access to a certain workspace
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. Use $BE_USER->checkWorkspace() directly if necessary.
     */
    public function checkWorkspaceAccess($wsid)
    {
        trigger_error('PageRepository->checkWorkspaceAccess() will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        if (!$this->getBackendUser() || !ExtensionManagementUtility::isLoaded('workspaces')) {
            return false;
        }
        if (!isset($this->workspaceCache[$wsid])) {
            $this->workspaceCache[$wsid] = $this->getBackendUser()->checkWorkspace($wsid);
        }
        return (string)$this->workspaceCache[$wsid]['_ACCESS'] !== '';
    }

    /**
     * Gets file references for a given record field.
     *
     * @param string $tableName Name of the table
     * @param string $fieldName Name of the field
     * @param array $element The parent element referencing to files
     * @return array
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0
     */
    public function getFileReferences($tableName, $fieldName, array $element)
    {
        trigger_error('PageRepository->getFileReferences() should not be used any longer, this method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        /** @var FileRepository $fileRepository */
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $currentId = !empty($element['uid']) ? $element['uid'] : 0;

        // Fetch the references of the default element
        try {
            $references = $fileRepository->findByRelation($tableName, $fieldName, $currentId);
        } catch (FileDoesNotExistException $e) {
            /**
             * We just catch the exception here
             * Reasoning: There is nothing an editor or even admin could do
             */
            return [];
        } catch (\InvalidArgumentException $e) {
            /**
             * The storage does not exist anymore
             * Log the exception message for admins as they maybe can restore the storage
             */
            $logMessage = $e->getMessage() . ' (table: "' . $tableName . '", fieldName: "' . $fieldName . '", currentId: ' . $currentId . ')';
            $this->logger->error($logMessage, ['exception' => $e]);
            return [];
        }

        $localizedId = null;
        if (isset($element['_LOCALIZED_UID'])) {
            $localizedId = $element['_LOCALIZED_UID'];
        } elseif (isset($element['_PAGES_OVERLAY_UID'])) {
            $localizedId = $element['_PAGES_OVERLAY_UID'];
        }

        $isTableLocalizable = (
            !empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])
            && !empty($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])
        );
        if ($isTableLocalizable && $localizedId !== null) {
            $localizedReferences = $fileRepository->findByRelation($tableName, $fieldName, $localizedId);
            $references = $localizedReferences;
        }

        return $references;
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
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     * @deprecated will be removed in TYPO3 v10.0 as will not be used anymore then because checkWorkspaceAccess() will be removed.
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return VariableFrontend
     */
    protected function getRuntimeCache(): VariableFrontend
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
    }
}
