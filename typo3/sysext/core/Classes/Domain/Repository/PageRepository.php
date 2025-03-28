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

namespace TYPO3\CMS\Core\Domain\Repository;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformInformation;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Domain\Access\RecordAccessVoter;
use TYPO3\CMS\Core\Domain\Event\AfterRecordLanguageOverlayEvent;
use TYPO3\CMS\Core\Domain\Event\BeforePageIsRetrievedEvent;
use TYPO3\CMS\Core\Domain\Event\BeforePageLanguageOverlayEvent;
use TYPO3\CMS\Core\Domain\Event\BeforeRecordLanguageOverlayEvent;
use TYPO3\CMS\Core\Domain\Event\ModifyDefaultConstraintsForDatabaseQueryEvent;
use TYPO3\CMS\Core\Domain\Page;
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
 *
 * For the Context, the workspace aspect is used to determine the workspace.
 * The Workspace ID is relevant for previewing
 * If > 0, versioning preview of other record versions is allowed. This should only
 * be set if the page is not cached and truly previewed by a backend user!
 */
class PageRepository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * This is not the final clauses. There will normally be conditions for the
     * hidden, starttime and endtime fields as well. This is initialized in the init() function.
     */
    protected string $where_hid_del = 'pages.deleted=0';

    /**
     * Clause for fe_group access
     */
    protected string $where_groupAccess = '';

    /**
     * Computed properties that are added to database rows.
     */
    protected array $computedPropertyNames = [
        '_LOCALIZED_UID',
        '_REQUESTED_OVERLAY_LANGUAGE',
        '_MP_PARAM',
        '_ORIG_uid',
        '_ORIG_pid',
        '_SHORTCUT_ORIGINAL_PAGE_UID',
    ];

    /**
     * Named constants for "magic numbers" of the field doktype
     */
    public const DOKTYPE_DEFAULT = 1;
    public const DOKTYPE_LINK = 3;
    public const DOKTYPE_SHORTCUT = 4;
    public const DOKTYPE_BE_USER_SECTION = 6;
    public const DOKTYPE_MOUNTPOINT = 7;
    public const DOKTYPE_SPACER = 199;
    public const DOKTYPE_SYSFOLDER = 254;

    /**
     * Named constants for "magic numbers" of the field shortcut_mode
     */
    public const SHORTCUT_MODE_NONE = 0;
    public const SHORTCUT_MODE_FIRST_SUBPAGE = 1;
    public const SHORTCUT_MODE_RANDOM_SUBPAGE = 2;
    public const SHORTCUT_MODE_PARENT_PAGE = 3;

    protected Context $context;

    /**
     * PageRepository constructor to set the base context, this will effectively remove the necessity for
     * setting properties from the outside.
     */
    public function __construct(?Context $context = null)
    {
        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
        $this->init();
    }

    /**
     * This sets the internal variable $this->where_hid_del to the correct where
     * clause for page records taking deleted/hidden/starttime/endtime/t3ver_state
     * into account.
     *
     * @internal
     */
    protected function init(): void
    {
        $workspaceId = (int)$this->context->getPropertyFromAspect('workspace', 'id');
        // As PageRepository may be used multiple times during the frontend request, and may
        // actually be used before the usergroups have been resolved, self::getMultipleGroupsWhereClause()
        // and the Event in ->enableFields() need to be reconsidered when the usergroup state changes.
        // When something changes in the context, a second runtime cache entry is built.
        // However, the PageRepository is generally in use for generating e.g. hundreds of links, so they would all use
        // the same cache identifier.
        $userAspect = $this->context->getAspect('frontend.user');
        $frontendUserIdentifier = 'user_' . (int)$userAspect->get('id') . '_groups_' . md5(implode(',', $userAspect->getGroupIds()));

        // We need to respect the date aspect as we might have subrequests with a different time (e.g. backend preview links)
        $dateTimeIdentifier = $this->context->getAspect('date')->get('timestamp');

        // If TRUE, the hidden-field is ignored. Normally this should be FALSE. Is used for previewing.
        $includeHiddenPages = $this->context->getPropertyFromAspect('visibility', 'includeHiddenPages');
        $includeScheduledRecords = $this->context->getPropertyFromAspect('visibility', 'includeScheduledRecords');

        $cache = $this->getRuntimeCache();
        $cacheIdentifier = implode(
            '',
            [
                'PageRepository_hidDelWhere',
                ($includeHiddenPages ? '_ShowHidden' : ''),
                ($includeScheduledRecords ? '_Scheduled' : ''),
                '_',
                (string)$workspaceId,
                '_',
                $frontendUserIdentifier,
                '_',
                (string)$dateTimeIdentifier,
            ]
        );
        $cacheEntry = $cache->get($cacheIdentifier);
        if ($cacheEntry) {
            $this->where_hid_del = $cacheEntry;
        } else {
            // @todo: This is bad. init() is called by __construct() which then performs stuff that
            //        depends on DB setup being ready.
            //        This makes early injection of PageRepository impossible - when DB does not
            //        exist or has not been set up.
            //        The acceptance tests with their early ext:styleguide for instance triggers
            //        events that trigger this indirectly. See comment in ext:form DataStructureIdentifierListener,
            //        it is the reason it declares some dependencies lazy.
            //        After all, when PageRepository is injected, it must not by default start
            //        preparing DB queries. This needs to vanish, the code must not be triggered by __construct().
            $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages')
                ->expr();
            if ($workspaceId > 0) {
                // For version previewing, make sure that enable-fields are not
                // de-selecting hidden pages - we need versionOL() to unset them only
                // if the overlay record instructs us to.
                // Clear where_hid_del and restrict to live and current workspaces
                $this->where_hid_del = (string)$expressionBuilder->and(
                    $expressionBuilder->eq('pages.deleted', 0),
                    $expressionBuilder->or(
                        $expressionBuilder->eq('pages.t3ver_wsid', 0),
                        $expressionBuilder->eq('pages.t3ver_wsid', $workspaceId)
                    )
                );
            } else {
                // add starttime / endtime, and check for hidden/deleted
                // Filter out new/deleted place-holder pages in case we are NOT in a
                // versioning preview (that means we are online!)
                $constraints = $this->getDefaultConstraints('pages', ['fe_group' => true]);
                $this->where_hid_del = $constraints === [] ? '' : (string)$expressionBuilder->and(...$constraints);
            }
            $cache->set($cacheIdentifier, $this->where_hid_del);
        }
        $this->where_groupAccess = $this->getMultipleGroupsWhereClause('pages.fe_group', 'pages');
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
     * The result has constraints filled by the properties $this->where_groupAccess
     * and $this->where_hid_del that are preset by the init() method.
     *
     * @see PageRepository::where_groupAccess
     * @see PageRepository::where_hid_del
     *
     * By default, the usergroup access check is enabled. Use the second method argument
     * to disable the usergroup access check.
     *
     * The given Page ID can be preprocessed by registering an Event.
     *
     * @param int $uid The page id to look up
     * @param bool $disableGroupAccessCheck set to true to disable group access check
     * @return array The resulting page record with overlays or empty array
     * @throws \UnexpectedValueException
     * @see PageRepository::getPage_noCheck()
     */
    public function getPage(int $uid, bool $disableGroupAccessCheck = false): array
    {
        // Dispatch Event to manipulate the page uid for special overlay handling
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
            new BeforePageIsRetrievedEvent($uid, $disableGroupAccessCheck, $this->context)
        );
        if ($event->hasPage()) {
            // In case an event listener resolved the page on its own, directly return it
            return $event->getPage()->toArray(true);
        }
        $disableGroupAccessCheck = $event->isGroupAccessCheckSkipped();
        $uid = $event->getPageId();
        $cacheIdentifier = 'PageRepository_getPage_' . md5(
            implode(
                '-',
                [
                    $uid,
                    $disableGroupAccessCheck ? '' : $this->where_groupAccess,
                    $this->where_hid_del,
                    $this->context->getPropertyFromAspect('language', 'id', 0),
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
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$uid, Connection::PARAM_INT)),
                $this->where_hid_del
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
                $result = $this->getLanguageOverlay('pages', $row);
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
    public function getPage_noCheck(int $uid): array
    {
        $cache = $this->getRuntimeCache();
        $cacheIdentifier = 'PageRepository_getPage_noCheck_' . $uid . '_' . $this->context->getPropertyFromAspect('language', 'id', 0) . '_' . (int)$this->context->getPropertyFromAspect('workspace', 'id');
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
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        $result = [];
        if ($row) {
            $this->versionOL('pages', $row);
            if (is_array($row)) {
                $result = $this->getLanguageOverlay('pages', $row);
            }
        }
        $cache->set($cacheIdentifier, $result);
        return $result;
    }

    /**
     * Master helper method to overlay a record to a language.
     *
     * Be aware that for pages the languageId is taken, and for all other records the contentId of the Aspect is used.
     *
     * @param string $table the name of the table, should be a TCA table with localization enabled
     * @param array $originalRow the current (full-fletched) record.
     * @param LanguageAspect|null $languageAspect an alternative language aspect if needed (optional)
     * @return array|null NULL If overlays were activated but no overlay was found and LanguageAspect was NOT set to MIXED
     */
    public function getLanguageOverlay(string $table, array $originalRow, ?LanguageAspect $languageAspect = null): ?array
    {
        // table is not localizable, so return directly
        if (!isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])) {
            return $originalRow;
        }

        try {
            /** @var LanguageAspect $languageAspect */
            $languageAspect = $languageAspect ?? $this->context->getAspect('language');
        } catch (AspectNotFoundException $e) {
            // no overlays
            return $originalRow;
        }

        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);

        $event = $eventDispatcher->dispatch(new BeforeRecordLanguageOverlayEvent($table, $originalRow, $languageAspect));
        $languageAspect = $event->getLanguageAspect();
        $originalRow = $event->getRecord();

        $attempted = false;
        $localizedRecord = null;
        if ($languageAspect->doOverlays()) {
            $attempted = true;
            // Mixed = if nothing is available in the selected language, try the fallbacks
            // Fallbacks work as follows (happens in the actual methods):
            // 1. We have a default language record and then start doing overlays (= the basis for fallbacks)
            // 2. Check if the actual requested language version is available in the DB (language=3 = canadian-french)
            // 3. If not, we check the next language version in the chain (e.g. language=2 = french) and so forth until we find a record
            if ($languageAspect->getOverlayType() === LanguageAspect::OVERLAYS_MIXED) {
                if ($table === 'pages') {
                    $localizedRecord = $this->getPageOverlay(
                        $originalRow,
                        $languageAspect
                    );
                    if (empty($localizedRecord)) {
                        $localizedRecord = $originalRow;
                    }
                } else {
                    // Loop through each (fallback) language and see if there is a record
                    $localizedRecord = $this->getRecordOverlay(
                        $table,
                        $originalRow,
                        $languageAspect
                    );
                    if ($localizedRecord === null) {
                        // If nothing was found, we set the localized record to the originalRow to simulate
                        // that the default language is "kept" (we want fallback to default language).
                        // Note: Most installations might have "type=fallback" set but do not set the default language
                        // as fallback. In the future - once we want to get rid of the magic "default language",
                        // this needs to behave different, and the "pageNotFound" special handling within fallbacks should be removed
                        // plus: we need to check explicitly on in_array(0, $languageAspect->getFallbackChain())
                        // However, getPageOverlay() a few lines above also returns the "default language page" as well.
                        $localizedRecord = $originalRow;
                    }
                }
            } else {
                // The option to hide records if they were not explicitly selected, was chosen (OVERLAYS_ON/WITH_FLOATING)
                // in the language configuration. So, here no changes are done.
                if ($table === 'pages') {
                    $localizedRecord = $this->getPageOverlay($originalRow, $languageAspect);
                } else {
                    $localizedRecord = $this->getRecordOverlay($table, $originalRow, $languageAspect);
                }
            }
        } else {
            // Free mode.
            // For "pages": Pages are usually retrieved by fetching the page record in the default language.
            // However, the originalRow should still fetch the page in a specific language (with fallbacks).
            // The method "getPageOverlay" should still be called in order to get the page record in the correct language.
            if ($table === 'pages' && $languageAspect->getId() > 0) {
                $attempted = true;
                $localizedRecord = $this->getPageOverlay($originalRow, $languageAspect);
            }
        }

        $event = new AfterRecordLanguageOverlayEvent($table, $originalRow, $localizedRecord, $attempted, $languageAspect);
        $event = $eventDispatcher->dispatch($event);

        // Return localized record or the original row, if no overlays were done
        return $event->overlayingWasAttempted() ? $event->getLocalizedRecord() : $originalRow;
    }

    /**
     * Returns the relevant page overlay record fields
     *
     * @param int|array $pageInput If $pageInput is an integer, it's the pid of the pageOverlay record and thus the page overlay record is returned. If $pageInput is an array, it's a page-record and based on this page record the language record is found and OVERLAID before the page record is returned.
     * @param int|LanguageAspect|null $language language UID if you want to set an alternative value to the given context which is default. Should be >=0
     * @throws \UnexpectedValueException
     * @return array Page row which is overlaid with language_overlay record (or the overlay record alone)
     */
    public function getPageOverlay(int|array $pageInput, LanguageAspect|int|null $language = null): array
    {
        $rows = $this->getPagesOverlay([$pageInput], $language);
        // Always an array in return
        return $rows[0] ?? [];
    }

    /**
     * Returns the relevant page overlay record fields
     *
     * @param array $pagesInput Array of integers or array of arrays. If each value is an integer, it's the pids of the pageOverlay records and thus the page overlay records are returned. If each value is an array, it's page-records and based on this page records the language records are found and OVERLAID before the page records are returned.
     * @param int|LanguageAspect|null $language Language UID if you want to set an alternative value to the given context aspect which is default. Should be >=0
     * @throws \UnexpectedValueException
     * @return array Page rows which are overlaid with language_overlay record.
     *               If the input was an array of integers, missing records are not
     *               included. If the input were page rows, untranslated pages
     *               are returned.
     */
    public function getPagesOverlay(array $pagesInput, int|LanguageAspect|null $language = null): array
    {
        if (empty($pagesInput)) {
            return [];
        }
        if (is_int($language)) {
            $languageAspect = new LanguageAspect($language, $language);
        } else {
            $languageAspect = $language ?? $this->context->getAspect('language');
        }

        $overlays = [];
        // If language UID is different from zero, do overlay:
        if ($languageAspect->getId() > 0) {
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

            $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
                new BeforePageLanguageOverlayEvent($pagesInput, $pageIds, $languageAspect)
            );
            $pagesInput = $event->getPageInput();
            $overlays = $this->getPageOverlaysForLanguage($event->getPageIds(), $event->getLanguageAspect());
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
                    $pagesOutput[$key]['_TRANSLATION_SOURCE'] = new Page($origPage);
                }
            } elseif (isset($overlays[$origPage])) {
                $pagesOutput[$key] = $overlays[$origPage];
            }
        }
        return $pagesOutput;
    }

    /**
     * Checks whether the passed (translated or default language) page is accessible with the given language settings.
     *
     * @param array $page the page translation record or the page in the default language
     * @return bool true if the given page translation record is suited for the given language ID
     * @internal
     */
    public function isPageSuitableForLanguage(array $page, LanguageAspect $languageAspect): bool
    {
        $languageUid = $languageAspect->getId();
        // Checks if the default language version can be shown
        // Block page is set, if l18n_cfg allows plus: 1) Either default language or 2) another language but NO overlay record set for page!
        $pageTranslationVisibility = new PageTranslationVisibility((int)($page['l18n_cfg'] ?? 0));
        if ((!$languageUid || !isset($page['_LOCALIZED_UID']))
            && $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage()
        ) {
            return false;
        }
        if ($languageUid > 0 && $pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()) {
            if (!isset($page['_LOCALIZED_UID']) || (int)($page['sys_language_uid'] ?? 0) !== $languageUid) {
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
     * @return int[]
     */
    protected function getLanguageFallbackChain(?LanguageAspect $languageAspect): array
    {
        $languageAspect = $languageAspect ?? $this->context->getAspect('language');
        return array_filter($languageAspect->getFallbackChain(), MathUtility::canBeInterpretedAsInteger(...));
    }

    /**
     * Returns the first match of overlays for pages in the passed languages.
     *
     * NOTE regarding the query restrictions:
     * Currently the visibility aspect within the FrontendRestrictionContainer will allow
     * page translation records to be selected as they are child-records of a page.
     * However, you may argue that the visibility flag should determine this.
     * But that's not how it's done right now.
     *
     * @param LanguageAspect $languageAspect Used for the fallback chain
     */
    protected function getPageOverlaysForLanguage(array $pageUids, LanguageAspect $languageAspect): array
    {
        if ($pageUids === []) {
            return [];
        }

        $languageUids = array_merge([$languageAspect->getId()], $this->getLanguageFallbackChain($languageAspect));
        // Remove default language ("0")
        $languageUids = array_filter($languageUids);
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $transOrigPointerField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context));
        // Because "fe_group" is an exclude field, so it is synced between overlays, the group restriction is removed for language overlays of pages
        $queryBuilder->getRestrictions()->removeByType(FrontendGroupRestriction::class);

        $candidates = [];
        $maxChunk = PlatformInformation::getMaxBindParameters($queryBuilder->getConnection()->getDatabasePlatform());
        foreach (array_chunk($pageUids, (int)floor($maxChunk / 3)) as $pageUidsChunk) {
            $query = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in(
                        $languageField,
                        $queryBuilder->createNamedParameter($languageUids, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->in(
                        $transOrigPointerField,
                        $queryBuilder->createNamedParameter($pageUidsChunk, Connection::PARAM_INT_ARRAY)
                    )
                );

            // This has cache hits for the current page and for menus (little performance gain).
            $cacheIdentifier = 'PageRepository_getPageOverlaysForLanguage_'
                . hash('xxh3', $query->getSQL() . json_encode($query->getParameters()));
            $rows = $this->getRuntimeCache()->get($cacheIdentifier);
            if (!is_array($rows)) {
                $rows = $query->executeQuery()->fetchAllAssociative();
                $this->getRuntimeCache()->set($cacheIdentifier, $rows);
            }

            foreach ($rows as $row) {
                $pageId = $row[$transOrigPointerField];
                $priority = array_search($row[$languageField], $languageUids);
                $candidates[$pageId][$priority] = $row;
            }
        }

        $overlays = [];
        foreach ($pageUids as $pageId) {
            $languageRows = $candidates[$pageId] ?? [];
            ksort($languageRows, SORT_NATURAL);
            foreach ($languageRows as $row) {
                // Found a result for the current language id
                $this->versionOL('pages', $row);
                if (is_array($row)) {
                    $row['_LOCALIZED_UID'] = (int)$row['uid'];
                    $row['_REQUESTED_OVERLAY_LANGUAGE'] = $languageUids[0];
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
     * in records from the same DB table)
     *
     * The record receives a language overlay and a workspace overlay of the language overlay.
     *
     * @param string $table Table name
     * @param array $row Record to overlay. Must contain uid, pid and $table]['ctrl']['languageField']
     * @return array|null Returns the input record, possibly overlaid with a translation. But if overlays are not mixed ("fallback to default language") then it will return NULL if no translation is found.
     */
    protected function getRecordOverlay(string $table, array $row, LanguageAspect $languageAspect): ?array
    {
        // Early return when no overlays are needed
        if ($languageAspect->getOverlayType() === LanguageAspect::OVERLAYS_OFF) {
            return $row;
        }

        $tableControl = $GLOBALS['TCA'][$table]['ctrl'] ?? [];
        $languageField = $tableControl['languageField'] ?? '';
        $transOrigPointerField = $tableControl['transOrigPointerField'] ?? '';

        // Only try overlays for tables with localization support
        if (empty($languageField)) {
            return $row;
        }
        if (empty($transOrigPointerField)) {
            return $row;
        }
        $incomingLanguageId = (int)($row[$languageField] ?? 0);

        // Return record for ALL languages untouched
        if ($incomingLanguageId === -1) {
            return $row;
        }

        $recordUid = (int)($row['uid'] ?? 0);
        $incomingRecordPid = (int)($row['pid'] ?? 0);

        // @todo: Fix call stack to prevent this situation in the first place
        if ($recordUid <= 0) {
            return $row;
        }
        if ($incomingRecordPid <= 0 && !in_array($tableControl['rootLevel'] ?? false, [true, 1, -1], true)) {
            return $row;
        }
        // When default language is displayed, we never want to return a record carrying
        // another language.
        if ($languageAspect->getContentId() === 0 && $incomingLanguageId > 0) {
            return null;
        }

        // Will try to overlay a record only if the contentId value is larger than zero,
        // contentId is used for regular records, whereas getId() is used for "pages" only.
        if ($languageAspect->getContentId() === 0) {
            return $row;
        }
        // Must be default language, otherwise no overlaying
        if ($incomingLanguageId === 0) {
            // Select overlay record:
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->setRestrictions(
                GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context)
            );
            if ((int)$this->context->getPropertyFromAspect('workspace', 'id') > 0) {
                // If not in live workspace, remove query based "enable fields" checks, it will be done in versionOL()
                // @see functional workspace test createLocalizedNotHiddenWorkspaceContentHiddenInLive()
                $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
                $queryBuilder->getRestrictions()->removeByType(StartTimeRestriction::class);
                $queryBuilder->getRestrictions()->removeByType(EndTimeRestriction::class);
                // We keep the WorkspaceRestriction in this case, because we need to get the LIVE record
                // of the language record before doing the version overlay of the language again. WorkspaceRestriction
                // does this for us, PLUS we need to ensure to get a possible LIVE record first (that's why
                // the "orderBy" query is there, so the LIVE record is found first), as there might only be a
                // versioned record (e.g. new version) or both (common for modifying, moving etc).
                if ($this->hasTableWorkspaceSupport($table)) {
                    $queryBuilder->orderBy('t3ver_wsid', 'ASC');
                }
            }

            $pid = $incomingRecordPid;
            $languageUids = array_merge([$languageAspect->getContentId()], $this->getLanguageFallbackChain($languageAspect));
            // When inside a workspace, the already versioned $row of the default language is coming in
            // For moved versioned records, the PID MIGHT be different. However, the idea of this function is
            // to get the language overlay of the LIVE default record, and afterward get the versioned record
            // the found (live) language record again, see the versionOL() call a few lines below.
            // This means, we need to modify the $pid value for moved records, as they might be on a different
            // page and use the PID of the LIVE version.
            if (isset($row['_ORIG_pid']) && $this->hasTableWorkspaceSupport($table) && VersionState::tryFrom($row['t3ver_state'] ?? 0) === VersionState::MOVE_POINTER) {
                $pid = $row['_ORIG_pid'];
            }
            $overlayRows = $queryBuilder->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        $languageField,
                        $queryBuilder->createNamedParameter($languageUids, Connection::PARAM_INT_ARRAY)
                    ),
                    $queryBuilder->expr()->eq(
                        $transOrigPointerField,
                        $queryBuilder->createNamedParameter($recordUid, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();

            $olrow = false;
            if ($overlayRows !== []) {
                // Note: The exact order of the $languageUid traversal is important
                foreach ($languageUids as $languageId) {
                    foreach ($overlayRows as $overlayRow) {
                        if ((int)$overlayRow[$languageField] === $languageId) {
                            // Found the requested language, stop searching
                            $olrow = $overlayRow;
                            break 2;
                        }
                    }
                }
            }

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
                        $row['_LOCALIZED_UID'] = (int)$olrow['uid'];
                        // will be overridden again outside of this method if there is a multi-level chain
                        $row['_REQUESTED_OVERLAY_LANGUAGE'] = $languageAspect->getContentId();
                    }
                }
                return $row;
            }
            // No overlay found.
            // Unset, if non-translated records should be hidden. ONLY done if the source
            // record really is default language and not [All] in which case it is allowed.
            if (in_array($languageAspect->getOverlayType(), [LanguageAspect::OVERLAYS_ON_WITH_FLOATING, LanguageAspect::OVERLAYS_ON], true)) {
                return null;
            }
        } elseif ($languageAspect->getContentId() !== $incomingLanguageId) {
            return null;
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
    public function getMenu($pageId, $fields = '*', $sortField = 'sorting', $additionalWhereClause = '', $checkShortcuts = true, bool $disableGroupAccessCheck = false)
    {
        // @todo: Restricting $fields to a list like 'uid, title' here, leads to issues from methods like
        //        getSubpagesForPages() which access keys like 'doktype'. This is odd, select field list
        //        should be handled better here, probably at least containing fields that are used in the
        //        sub methods. In the end, it might be easier to drop argument $fields altogether and
        //        always select * ?
        return $this->getSubpagesForPages((array)$pageId, $fields, $sortField, $additionalWhereClause, $checkShortcuts, true, $disableGroupAccessCheck);
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
    public function getMenuForPages(array $pageIds, $fields = '*', $sortField = 'sorting', $additionalWhereClause = '', $checkShortcuts = true, bool $disableGroupAccessCheck = false)
    {
        return $this->getSubpagesForPages($pageIds, $fields, $sortField, $additionalWhereClause, $checkShortcuts, false, $disableGroupAccessCheck);
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
     * If a record is a mount point in overlay mode, the overlaying page record is returned in place of the
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
        bool $parentPages = true,
        bool $disableGroupAccessCheck = false
    ): array {
        $relationField = $parentPages ? 'pid' : 'uid';

        if ($disableGroupAccessCheck) {
            $whereGroupAccessCheck = $this->where_groupAccess;
            $this->where_groupAccess = '';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->context->getPropertyFromAspect('workspace', 'id')));

        $res = $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields, true))
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    $relationField,
                    $queryBuilder->createNamedParameter($pageIds, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $this->where_hid_del,
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

        if ($disableGroupAccessCheck) {
            $this->where_groupAccess = $whereGroupAccessCheck;
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
            $mountPointPage = $this->getPage((int)$mountPointInfo['mount_pid']);

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
     */
    protected function checkValidShortcutOfPage(array $page, string $additionalWhereClause): array
    {
        if (empty($page)) {
            return [];
        }

        $dokType = (int)($page['doktype'] ?? 0);
        $shortcutMode = (int)($page['shortcut_mode'] ?? 0);

        if ($dokType === self::DOKTYPE_SHORTCUT && (($shortcut = (int)($page['shortcut'] ?? 0)) || $shortcutMode)) {
            if ($shortcutMode === self::SHORTCUT_MODE_NONE) {
                // No shortcut_mode set, so target is directly set in $page['shortcut']
                $searchField = 'uid';
                $searchUid = $shortcut;
            } elseif ($shortcutMode === self::SHORTCUT_MODE_FIRST_SUBPAGE || $shortcutMode === self::SHORTCUT_MODE_RANDOM_SUBPAGE) {
                // Check subpages - first subpage or random subpage
                $searchField = 'pid';
                // If a shortcut mode is set and no valid page is given to select subpages
                // from use the actual page.
                $searchUid = $shortcut ?: $page['uid'];
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
                        $queryBuilder->createNamedParameter($searchUid, Connection::PARAM_INT)
                    ),
                    $this->where_hid_del,
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
     */
    protected function getPageShortcut($shortcutFieldValue, $shortcutMode, $thisUid, $iteration = 20, $pageLog = [], $disableGroupCheck = false, bool $resolveRandomPageShortcuts = true)
    {
        // @todo: Simplify! page['shortcut'] is maxitems 1 and not a comma separated list of values!
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
                    self::DOKTYPE_BE_USER_SECTION,
                ];
                $savedWhereGroupAccess = '';
                // "getMenu()" does not allow to hand over $disableGroupCheck, for this reason it is manually disabled and re-enabled afterwards.
                if ($disableGroupCheck) {
                    $savedWhereGroupAccess = $this->where_groupAccess;
                    $this->where_groupAccess = '';
                }
                $pageArray = $this->getMenu($idArray[0] ?: (int)$thisUid, '*', 'sorting', 'AND pages.doktype NOT IN (' . implode(', ', $excludedDoktypes) . ')');
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
                $parent = $this->getPage(($idArray[0] ?: (int)$thisUid), $disableGroupCheck);
                $page = $this->getPage((int)$parent['pid'], $disableGroupCheck);
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
        // Check if shortcut page was a shortcut itself, if so look up recursively
        if ((int)$page['doktype'] === self::DOKTYPE_SHORTCUT) {
            if (!in_array($page['uid'], $pageLog) && $iteration > 0) {
                $pageLog[] = $page['uid'];
                $page = $this->getPageShortcut((string)$page['shortcut'], $page['shortcut_mode'], $page['uid'], $iteration - 1, $pageLog, $disableGroupCheck);
            } else {
                $pageLog[] = $page['uid'];
                $this->logger->error('Page shortcuts were looping in uids {uids}', ['uids' => implode(', ', array_values($pageLog))]);
                // @todo: This shouldn't be a \RuntimeException since editors can construct loops. It should trigger 500 handling or something.
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
     * @throws ShortcutTargetPageNotFoundException
     */
    public function resolveShortcutPage(array $page, bool $resolveRandomSubpages = false, bool $disableGroupAccessCheck = false): array
    {
        if ((int)($page['doktype'] ?? 0) !== self::DOKTYPE_SHORTCUT) {
            return $page;
        }
        $shortcutMode = (int)($page['shortcut_mode'] ?? self::SHORTCUT_MODE_NONE);
        $shortcutTarget = (string)($page['shortcut'] ?? '');

        $cacheIdentifier = 'shortcuts_resolved_' . ($disableGroupAccessCheck ? '1' : '0') . '_' . $page['uid'] . '_' . $this->context->getPropertyFromAspect('language', 'id', 0) . '_' . $page['sys_language_uid'];
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
            $shortcutOriginalPageUid = (int)$page['uid'];
            $page = $shortcut;
            $page['_SHORTCUT_ORIGINAL_PAGE_UID'] = $shortcutOriginalPageUid;
        }

        if ($resolveRandomSubpages === false) {
            $this->getRuntimeCache()->set($cacheIdentifier, $page);
        }

        return $page;
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
     * mount_pid, mount_pid_ol. If it is not supplied it will be looked up by
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
                        $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
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
                        $queryBuilder->createNamedParameter($mount_pid, Connection::PARAM_INT)
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
    public function filterAccessiblePageIds(array $pageIds, ?QueryRestrictionContainerInterface $restrictionContainer = null): array
    {
        if ($pageIds === []) {
            return [];
        }
        $validPageIds = [];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->setRestrictions(
            $restrictionContainer ?? GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context)
        );
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
     * @param bool $checkPage If set, it's also required that the page on which the record resides is accessible
     * @return array|null Returns array (the record) if OK, otherwise null
     */
    public function checkRecord(string $table, int $uid, bool $checkPage = false): ?array
    {
        if (!is_array($GLOBALS['TCA'][$table])) {
            return null;
        }
        if ($uid <= 0) {
            return null;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context));
        $row = $queryBuilder->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
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
                                $queryBuilder->createNamedParameter($row['pid'], Connection::PARAM_INT)
                            )
                        )
                        ->executeQuery()
                        ->fetchOne();
                    if ($numRows > 0) {
                        return $row;
                    }
                    return null;
                }
                return $row;
            }
        }
        return null;
    }

    /**
     * Returns record no matter what - except if record is deleted
     *
     * @param string $table The table name to search
     * @param int $uid The uid to look up in $table
     * @param array $fields Fields to select, `*` is the default - If a custom list is set, make sure the list
     *                       contains the `uid` field. It's mandatory for further processing of the result row.
     * @return array|null Returns array (the record) if found, otherwise null
     * @see getPage_noCheck()
     */
    public function getRawRecord(string $table, int $uid, array $fields = ['*']): ?array
    {
        if ($uid <= 0) {
            return null;
        }
        if (!is_array($GLOBALS['TCA'][$table])) {
            return null;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $row = $queryBuilder
            ->select(...$fields)
            ->from($table)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        if ($row) {
            $this->versionOL($table, $row);
            if (is_array($row)) {
                return $row;
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
     * Returns a WHERE clause which will filter out records with start/end
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
     * @deprecated will be removed in TYPO3 v14.0. Use getDefaultConstraints() instead.
     */
    public function enableFields(string $table, int $show_hidden = -1, array $ignore_array = []): string
    {
        trigger_error('PageRepository->enableFields() will be removed in TYPO3 v14.0. Use ->getDefaultConstraints() instead.', E_USER_DEPRECATED);
        if ($show_hidden === -1) {
            // If show_hidden was not set from outside, use the current context
            $ignore_array['disabled'] = (bool)$this->context->getPropertyFromAspect('visibility', $table === 'pages' ? 'includeHiddenPages' : 'includeHiddenContent', false);
        } else {
            $ignore_array['disabled'] = (bool)$show_hidden;
        }
        $constraints = $this->getDefaultConstraints($table, $ignore_array);
        if ($constraints === []) {
            return '';
        }
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table)
            ->expr();
        return ' AND ' . $expressionBuilder->and(...$constraints);
    }

    /**
     * Returns a DB query constraints (part of the WHERE clause) which will
     * filter out records with start/end times or hidden/fe_groups fields set
     * to values that should de-select them according to the current time, preview
     * settings or user login.
     *
     * Is using the $GLOBALS['TCA'] arrays "ctrl" part where the key "enablecolumns"
     * determines for each table which of these features applies to that table.
     *
     * @param string $table Table name found in the $GLOBALS['TCA'] array
     * @param array $enableFieldsToIgnore Array where values (or keys) can be "disabled", "starttime", "endtime", "fe_group" (keys from "enablefields" in TCA) and if set they will make sure that part of the clause is not added. Thus disables the specific part of the clause. For previewing etc.
     * @return array<string, CompositeExpression|string> Constraints built up by the enableField controls
     */
    public function getDefaultConstraints(string $table, array $enableFieldsToIgnore = [], ?string $tableAlias = null): array
    {
        if (array_is_list($enableFieldsToIgnore)) {
            $enableFieldsToIgnore = array_flip($enableFieldsToIgnore);
            foreach ($enableFieldsToIgnore as $key => $value) {
                $enableFieldsToIgnore[$key] = true;
            }
        }
        $ctrl = $GLOBALS['TCA'][$table]['ctrl'] ?? null;
        if (!is_array($ctrl)) {
            return [];
        }
        $tableAlias ??= $table;

        // If set, any hidden-fields in records are ignored, falling back to the default property from the visibility aspect
        if (!isset($enableFieldsToIgnore['disabled'])) {
            $enableFieldsToIgnore['disabled'] = (bool)$this->context->getPropertyFromAspect('visibility', $table === 'pages' ? 'includeHiddenPages' : 'includeHiddenContent', false);
        }
        $showScheduledRecords = $this->context->getPropertyFromAspect('visibility', 'includeScheduledRecords', false);
        if (!isset($enableFieldsToIgnore['starttime'])) {
            $enableFieldsToIgnore['starttime'] = $showScheduledRecords;
        }
        if (!isset($enableFieldsToIgnore['endtime'])) {
            $enableFieldsToIgnore['endtime'] = $showScheduledRecords;
        }

        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table)
            ->expr();

        $constraints = [];
        // Delete field check
        if ($ctrl['delete'] ?? false) {
            $constraints['deleted'] = $expressionBuilder->eq($tableAlias . '.' . $ctrl['delete'], 0);
        }

        if ($this->hasTableWorkspaceSupport($table)) {
            // This should work exactly as WorkspaceRestriction and WorkspaceRestriction should be used instead
            if ((int)$this->context->getPropertyFromAspect('workspace', 'id') === 0) {
                // Filter out placeholder records (new/deleted items)
                // in case we are NOT in a version preview (that means we are online!)
                $constraints['workspaces'] = $expressionBuilder->and(
                    $expressionBuilder->lte(
                        $tableAlias . '.t3ver_state',
                        VersionState::DEFAULT_STATE->value
                    ),
                    $expressionBuilder->eq($tableAlias . '.t3ver_wsid', 0)
                );
            } else {
                // show only records of live and of the current workspace
                // in case we are in a versioning preview
                $constraints['workspaces'] = $expressionBuilder->or(
                    $expressionBuilder->eq($tableAlias . '.t3ver_wsid', 0),
                    $expressionBuilder->eq($tableAlias . '.t3ver_wsid', (int)$this->context->getPropertyFromAspect('workspace', 'id'))
                );
            }

            // Filter out versioned records
            if (empty($enableFieldsToIgnore['pid'])) {
                // Always filter out versioned records that have an "offline" record
                $constraints['pid'] = $expressionBuilder->or(
                    $expressionBuilder->eq($tableAlias . '.t3ver_oid', 0),
                    $expressionBuilder->eq($tableAlias . '.t3ver_state', VersionState::MOVE_POINTER->value)
                );
            }
        }

        // Enable fields
        if (is_array($ctrl['enablecolumns'] ?? false)) {
            // In case of versioning-preview, enableFields are ignored (checked in versionOL())
            if ((int)$this->context->getPropertyFromAspect('workspace', 'id') === 0 || !$this->hasTableWorkspaceSupport($table)) {

                if (($ctrl['enablecolumns']['disabled'] ?? false) && !$enableFieldsToIgnore['disabled']) {
                    $constraints['disabled'] = $expressionBuilder->eq(
                        $tableAlias . '.' . $ctrl['enablecolumns']['disabled'],
                        0
                    );
                }
                if (($ctrl['enablecolumns']['starttime'] ?? false) && !($enableFieldsToIgnore['starttime'] ?? false)) {
                    $constraints['starttime'] = $expressionBuilder->lte(
                        $tableAlias . '.' . $ctrl['enablecolumns']['starttime'],
                        $this->context->getPropertyFromAspect('date', 'accessTime', 0)
                    );
                }
                if (($ctrl['enablecolumns']['endtime'] ?? false) && !($enableFieldsToIgnore['endtime'] ?? false)) {
                    $field = $tableAlias . '.' . $ctrl['enablecolumns']['endtime'];
                    $constraints['endtime'] = $expressionBuilder->or(
                        $expressionBuilder->eq($field, 0),
                        $expressionBuilder->gt(
                            $field,
                            $this->context->getPropertyFromAspect('date', 'accessTime', 0)
                        )
                    );
                }
                if (($ctrl['enablecolumns']['fe_group'] ?? false) && !($enableFieldsToIgnore['fe_group'] ?? false)) {
                    $field = $tableAlias . '.' . $ctrl['enablecolumns']['fe_group'];
                    $constraints['fe_group'] = QueryHelper::stripLogicalOperatorPrefix(
                        $this->getMultipleGroupsWhereClause($field, $table)
                    );
                }
            }
        }

        // Call a PSR-14 Event for additional constraints
        $event = new ModifyDefaultConstraintsForDatabaseQueryEvent($table, $tableAlias, $expressionBuilder, $constraints, $enableFieldsToIgnore, $this->context);
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
        return $event->getConstraints();
    }

    /**
     * Creating where-clause for checking group access to elements in enableFields
     * function
     *
     * @param string $field Field with group list
     * @param string $table Table name
     * @return string AND sql-clause
     * @see getDefaultConstraints()
     */
    public function getMultipleGroupsWhereClause(string $field, string $table): string
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
            $orChecks[] = $expressionBuilder->inSet($field, $expressionBuilder->literal((string)($value ?? '')));
        }

        $accessGroupWhere = ' AND (' . $expressionBuilder->or(...$orChecks) . ')';
        $cache->set($cacheIdentifier, $accessGroupWhere);
        return $accessGroupWhere;
    }

    /**********************
     *
     * Versioning Preview
     *
     **********************/

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
     * Principle: Record online! => Find offline?
     *
     * @param string $table Table name
     * @param array $row Record array passed by reference. As minimum, the "uid", "pid" and "t3ver_state" fields must exist! The record MAY be set to FALSE in which case the calling function should act as if the record is forbidden to access!
     * @param bool $unsetMovePointers If set, the $row is cleared in case it is a move-pointer. This is only for preview of moved records (to remove the record from the original location so it appears only in the new location)
     * @param bool $bypassEnableFieldsCheck Unless this option is TRUE, the $row is unset if enablefields for BOTH the version AND the online record deselects it. This is because when versionOL() is called it is assumed that the online record is already selected with no regards to it's enablefields. However, after looking for a new version the online record enablefields must ALSO be evaluated of course. This is done all by this function!
     * @see BackendUtility::workspaceOL()
     */
    public function versionOL(string $table, &$row, bool $unsetMovePointers = false, bool $bypassEnableFieldsCheck = false): void
    {
        if ((int)$this->context->getPropertyFromAspect('workspace', 'id') <= 0) {
            return;
        }
        if (!is_array($row)) {
            return;
        }
        if (!isset($row['uid'], $row['t3ver_oid'])) {
            return;
        }
        // implode(',',array_keys($row)) = Using fields from original record to make
        // sure no additional fields are selected. This is best for eg. getPageOverlay()
        // Computed properties are excluded since those would lead to SQL errors.
        $fields = array_keys($this->purgeComputedProperties($row));
        // will overlay any incoming moved record with the live record, which in turn
        // will be overlaid with its workspace version again to fetch both PID fields.
        $incomingRecordIsAMoveVersion = (int)$row['t3ver_oid'] > 0 && VersionState::tryFrom($row['t3ver_state'] ?? 0) === VersionState::MOVE_POINTER;
        if ($incomingRecordIsAMoveVersion) {
            // Fetch the live version again if the given $row is a move pointer, so we know the original PID
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $row = $queryBuilder->select(...$fields)
                ->from($table)
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter((int)$row['t3ver_oid'], Connection::PARAM_INT)))
                ->executeQuery()
                ->fetchAssociative();
        }
        if ($wsAlt = $this->getWorkspaceVersionOfRecord($table, (int)$row['uid'], $fields, $bypassEnableFieldsCheck)) {
            if (is_array($wsAlt)) {
                $rowVersionState = VersionState::tryFrom($wsAlt['t3ver_state'] ?? 0);
                if ($rowVersionState === VersionState::MOVE_POINTER) {
                    // For move pointers, store the actual live PID in the _ORIG_pid
                    // The only place where PID is actually different in a workspace
                    $wsAlt['_ORIG_pid'] = $row['pid'];
                }
                // For versions of single elements or page+content, preserve online UID
                // (this will produce true "overlay" of element _content_, not any references)
                // For new versions there is no online counterpart
                if ($rowVersionState !== VersionState::NEW_PLACEHOLDER) {
                    $wsAlt['_ORIG_uid'] = $wsAlt['uid'];
                }
                $wsAlt['uid'] = $row['uid'];
                // Changing input record to the workspace version alternative:
                $row = $wsAlt;
                // Unset record if it turned out to be deleted in workspace
                if ($rowVersionState === VersionState::DELETE_PLACEHOLDER) {
                    $row = false;
                }
                // Check if move-pointer in workspace (unless if a move-placeholder is the
                // reason why it appears!):
                // You have to specifically set $unsetMovePointers in order to clear these
                // because it is normally a display issue if it should be shown or not.
                if ($rowVersionState === VersionState::MOVE_POINTER && !$incomingRecordIsAMoveVersion && $unsetMovePointers) {
                    // Unset record if it turned out to be deleted in workspace
                    $row = false;
                }
            } else {
                // No version found, then check if online version is dummy-representation
                // Notice, that unless $bypassEnableFieldsCheck is TRUE, the $row is unset if
                // enablefields for BOTH the version AND the online record deselects it. See
                // note for $bypassEnableFieldsCheck
                if ($wsAlt <= -1 || VersionState::tryFrom($row['t3ver_state'] ?? 0)->indicatesPlaceholder()) {
                    // Unset record if it turned out to be "hidden"
                    $row = false;
                }
            }
        }
    }

    /**
     * Select the version of a record for a workspace
     *
     * @param string $table Table name to select from
     * @param int $uid Record uid for which to find workspace version.
     * @param array $fields Fields to select, `*` is the default - If a custom list is set, make sure the list
     *                       contains the `uid` field. It's mandatory for further processing of the result row.
     * @param bool $bypassEnableFieldsCheck If TRUE, enableFields are not checked for.
     * @return array|int|bool If found, return record, otherwise other value: Returns 1 if version was sought for but not found, returns -1/-2 if record (offline/online) existed but had enableFields that would disable it. Returns FALSE if not in workspace or no versioning for record. Notice, that the enablefields of the online record is also tested.
     * @see BackendUtility::getWorkspaceVersionOfRecord()
     * @internal this is a rather low-level method, it is recommended to use versionOL instead()
     */
    public function getWorkspaceVersionOfRecord(string $table, int $uid, array $fields = ['*'], bool $bypassEnableFieldsCheck = false): array|int|bool
    {
        $workspace = (int)$this->context->getPropertyFromAspect('workspace', 'id');
        // No look up in database because versioning not enabled / or workspace not offline
        if ($workspace === 0) {
            return false;
        }
        if (!$this->hasTableWorkspaceSupport($table)) {
            return false;
        }
        // Select workspace version of record, only testing for deleted.
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $newrow = $queryBuilder
            ->select(...$fields)
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($workspace, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->or(
                    // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            't3ver_state',
                            $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT)
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_oid',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
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
        // Remove the workspace restriction because we are testing a version record
        $queryBuilder->getRestrictions()->removeByType(WorkspaceRestriction::class);
        $queryBuilder->select('uid')
            ->from($table)
            ->setMaxResults(1);

        if (is_array($newrow)) {
            $queryBuilder->where(
                $queryBuilder->expr()->eq(
                    't3ver_wsid',
                    $queryBuilder->createNamedParameter($workspace, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->or(
                    // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            't3ver_state',
                            $queryBuilder->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT)
                        )
                    ),
                    $queryBuilder->expr()->eq(
                        't3ver_oid',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
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
            $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
        );
        if ($bypassEnableFieldsCheck || $queryBuilder->executeQuery()->fetchOne()) {
            // Means search was done, but no version found.
            return 1;
        }
        // Return -2 because the online record was de-selected due to its enableFields.
        return -2;
    }

    /**
     * Perfect use case: get storage folders recursively, including the given Page IDs.
     *
     * Difference to "getDescendantPageIdsRecursive" is that this is used with multiple Page IDs,
     * AND it includes the page IDs themselves.
     *
     * @return int[]
     */
    public function getPageIdsRecursive(array $pageIds, int $depth): array
    {
        if ($pageIds === []) {
            return [];
        }
        $pageIds = array_map(intval(...), $pageIds);
        if ($depth === 0) {
            return $pageIds;
        }
        $allPageIds = [];
        foreach ($pageIds as $pageId) {
            $allPageIds = array_merge($allPageIds, [$pageId], $this->getDescendantPageIdsRecursive($pageId, $depth));
        }
        return array_unique($allPageIds);
    }

    /**
     * Generates a list of Page IDs from $startPageId. List does not include $startPageId itself.
     * Only works on default language level.
     *
     * Pages that prevent looking for further subpages:
     * - deleted pages
     * - pages of the Backend User Section (doktype = 6) type
     * - pages that have the extendToSubpages set, where starttime, endtime, hidden or fe_group
     *   would hide the pages
     *
     * Apart from that, pages with enable-fields excluding them, will also be removed.
     *
     * Mount Pages are descended, but note these ID numbers are not useful for links unless the correct MPvar is set.
     *
     * @param int $startPageId The id of the start page from which point in the page tree to descend.
     * @param int $depth Maximum recursion depth. Use 100 or so to descend "infinitely". Stops when 0 is reached.
     * @param int $begin An optional integer the level in the tree to start collecting. Zero means 'start right away', 1 = 'next level and out'
     * @param array $excludePageIds Avoid collecting these pages and their possible subpages
     * @param bool $bypassEnableFieldsCheck If true, then enableFields and other checks are not evaluated
     * @return int[] Returns the list of Page IDs
     */
    public function getDescendantPageIdsRecursive(int $startPageId, int $depth, int $begin = 0, array $excludePageIds = [], bool $bypassEnableFieldsCheck = false): array
    {
        if (!$startPageId) {
            return [];
        }
        if (!$this->getRawRecord('pages', $startPageId, ['uid'])) {
            // Start page does not exist
            return [];
        }
        // Find mount point if any
        $mount_info = $this->getMountPointInfo($startPageId);
        $includePageId = false;
        if (is_array($mount_info)) {
            $startPageId = (int)$mount_info['mount_pid'];
            // In overlay mode, use the mounted page uid
            if ($mount_info['overlay']) {
                $includePageId = true;
            }
        }
        $descendantPageIds = $this->getSubpagesRecursive($startPageId, $depth, $begin, $excludePageIds, $bypassEnableFieldsCheck);
        if ($includePageId) {
            $descendantPageIds = array_merge([$startPageId], $descendantPageIds);
        }
        return $descendantPageIds;
    }

    /**
     * This is an internal (recursive) method which returns the Page IDs for a given $pageId.
     * and also checks for permissions of the pages AND resolves mountpoints.
     *
     * @param int $pageId must be a valid page record (this is not checked)
     * @return int[]
     */
    protected function getSubpagesRecursive(int $pageId, int $depth, int $begin, array $excludePageIds, bool $bypassEnableFieldsCheck, array $prevId_array = []): array
    {
        $descendantPageIds = [];
        // if $depth is 0, then we do not fetch subpages
        if ($depth === 0) {
            return [];
        }
        // Add this ID to the array of IDs
        if ($begin <= 0) {
            $prevId_array[] = $pageId;
        }
        // Select subpages
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->context->getPropertyFromAspect('workspace', 'id')));
        $queryBuilder->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
                // tree is only built by language=0 pages
                $queryBuilder->expr()->eq('sys_language_uid', 0)
            )
            ->orderBy('sorting');

        if ($excludePageIds !== []) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn('uid', $queryBuilder->createNamedParameter($excludePageIds, Connection::PARAM_INT_ARRAY))
            );
        }

        $result = $queryBuilder->executeQuery();
        while ($row = $result->fetchAssociative()) {
            $versionState = VersionState::tryFrom($row['t3ver_state'] ?? 0);
            $this->versionOL('pages', $row, false, $bypassEnableFieldsCheck);
            if ($row === false
                || (int)$row['doktype'] === self::DOKTYPE_BE_USER_SECTION
                || $versionState->indicatesPlaceholder()
            ) {
                // falsy row means Overlay prevents access to this page.
                // Doing this after the overlay to make sure changes
                // in the overlay are respected.
                // However, we do not process pages below of and
                // including of type BE user section
                continue;
            }
            // Find mount point if any:
            $next_id = (int)$row['uid'];
            $mount_info = $this->getMountPointInfo($next_id, $row);
            // Overlay mode:
            if (is_array($mount_info) && $mount_info['overlay']) {
                $next_id = (int)$mount_info['mount_pid'];
                // @todo: check if we could use $mount_info[mount_pid_rec] and check against $excludePageIds?
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('pages');
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                    ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->context->getPropertyFromAspect('workspace', 'id')));
                $queryBuilder->select('*')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($next_id, Connection::PARAM_INT)
                        )
                    )
                    ->orderBy('sorting')
                    ->setMaxResults(1);

                if ($excludePageIds !== []) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->notIn('uid', $queryBuilder->createNamedParameter($excludePageIds, Connection::PARAM_INT_ARRAY))
                    );
                }

                $row = $queryBuilder->executeQuery()->fetchAssociative();
                $this->versionOL('pages', $row);
                $versionState = VersionState::tryFrom($row['t3ver_state'] ?? 0);
                if ($row === false
                    || (int)$row['doktype'] === self::DOKTYPE_BE_USER_SECTION
                    || $versionState->indicatesPlaceholder()
                ) {
                    // Doing this after the overlay to make sure
                    // changes in the overlay are respected.
                    // see above
                    continue;
                }
            }
            $accessVoter = GeneralUtility::makeInstance(RecordAccessVoter::class);
            // Add record:
            if ($bypassEnableFieldsCheck || $accessVoter->accessGrantedForPageInRootLine($row, $this->context)) {
                // Add ID to list:
                if ($begin <= 0) {
                    if ($bypassEnableFieldsCheck || $accessVoter->accessGranted('pages', $row, $this->context)) {
                        $descendantPageIds[] = $next_id;
                    }
                }
                // Next level
                if (!$row['php_tree_stop']) {
                    // Normal mode:
                    if (is_array($mount_info) && !$mount_info['overlay']) {
                        $next_id = (int)$mount_info['mount_pid'];
                    }
                    // Call recursively, if the id is not in prevID_array:
                    if (!in_array($next_id, $prevId_array, true)) {
                        $descendantPageIds = array_merge(
                            $descendantPageIds,
                            $this->getSubpagesRecursive(
                                $next_id,
                                $depth - 1,
                                $begin - 1,
                                $excludePageIds,
                                $bypassEnableFieldsCheck,
                                $prevId_array
                            )
                        );
                    }
                }
            }
        }
        return $descendantPageIds;
    }

    /**
     * Checks if the page is hidden in the active workspace (and the current language), then the "preview"
     * mode for frontend pages is active.
     *
     * @internal this is not part of the TYPO3 Core API.
     */
    public function checkIfPageIsHidden(int $pageId, LanguageAspect $languageAspect): bool
    {
        if ($pageId === 0) {
            return false;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->select('uid', 'hidden', 'starttime', 'endtime')
            ->from('pages')
            ->setMaxResults(1);

        // $pageId always points to the ID of the default language page, so we check
        // the current site language to determine if we need to fetch a translation but consider fallbacks
        if ($languageAspect->getId() > 0) {
            $languagesToCheck = [$languageAspect->getId()];
            // Remove fallback information like "pageNotFound"
            foreach ($languageAspect->getFallbackChain() as $languageToCheck) {
                if (is_numeric($languageToCheck)) {
                    $languagesToCheck[] = $languageToCheck;
                }
            }
            // Check for the language and all its fallbacks (except for default language)
            $constraint = $queryBuilder->expr()->and(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->in('sys_language_uid', $queryBuilder->createNamedParameter(array_filter($languagesToCheck), Connection::PARAM_INT_ARRAY))
            );
            // If the fallback language Ids also contains the default language, this needs to be considered
            if (in_array(0, $languagesToCheck, true)) {
                $constraint = $queryBuilder->expr()->or(
                    $constraint,
                    // Ensure to also fetch the default record
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                        $queryBuilder->expr()->eq('sys_language_uid', 0)
                    )
                );
            }
            $queryBuilder->where($constraint);
            // Ensure that the translated records are shown first (maxResults is set to 1)
            $queryBuilder->orderBy('sys_language_uid', 'DESC');
        } else {
            $queryBuilder->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            );
        }
        $page = $queryBuilder->executeQuery()->fetchAssociative();
        if ((int)$this->context->getPropertyFromAspect('workspace', 'id') > 0) {
            // Fetch overlay of page if in workspace and check if it is hidden
            $backupContext = clone $this->context;
            $this->context->setAspect('visibility', GeneralUtility::makeInstance(VisibilityAspect::class));
            $targetPage = $this->getWorkspaceVersionOfRecord('pages', (int)$page['uid']);
            // Also checks if the workspace version is NOT hidden but the live version is in fact still hidden
            $result = $targetPage === -1 || $targetPage === -2 || (is_array($targetPage) && $targetPage['hidden'] == 0 && $page['hidden'] == 1);
            $this->context = $backupContext;
        } else {
            $result = is_array($page) && ($page['hidden'] || $page['starttime'] > $GLOBALS['SIM_EXEC_TIME'] || $page['endtime'] != 0 && $page['endtime'] <= $GLOBALS['SIM_EXEC_TIME']);
        }
        return $result;
    }

    /**
     * Purges computed properties from database rows,
     * such as _ORIG_uid or _ORIG_pid for instance.
     */
    protected function purgeComputedProperties(array $row): array
    {
        foreach ($this->computedPropertyNames as $computedPropertyName) {
            if (array_key_exists($computedPropertyName, $row)) {
                unset($row[$computedPropertyName]);
            }
        }
        return $row;
    }

    protected function getRuntimeCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }

    protected function hasTableWorkspaceSupport(string $tableName): bool
    {
        return !empty($GLOBALS['TCA'][$tableName]['ctrl']['versioningWS']);
    }
}
