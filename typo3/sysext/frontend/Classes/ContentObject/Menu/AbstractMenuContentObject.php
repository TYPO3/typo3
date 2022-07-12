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

namespace TYPO3\CMS\Frontend\ContentObject\Menu;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Page\DefaultJavaScriptAssetTrait;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Menu\Exception\NoSuchMenuTypeException;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;

/**
 * Generating navigation/menus from TypoScript
 *
 * The HMENU content object uses this (or more precisely one of the extension classes).
 * Among others the class generates an array of menu items. Thereafter functions from the subclasses are called.
 * The class is always used through extension classes like TextMenuContentObject.
 */
abstract class AbstractMenuContentObject
{
    use DefaultJavaScriptAssetTrait;

    /**
     * tells you which menu number this is. This is important when getting data from the setup
     *
     * @var int
     */
    protected $menuNumber = 1;

    /**
     * 0 = rootFolder
     *
     * @var int
     */
    protected $entryLevel = 0;

    /**
     * Doktypes that define which should not be included in a menu
     *
     * @var int[]
     */
    protected $excludedDoktypes = [PageRepository::DOKTYPE_BE_USER_SECTION, PageRepository::DOKTYPE_SYSFOLDER];

    /**
     * @var int[]
     */
    protected $alwaysActivePIDlist = [];

    /**
     * Loaded with the parent cObj-object when a new HMENU is made
     *
     * @var ContentObjectRenderer
     */
    public $parent_cObj;

    /**
     * accumulation of mount point data
     *
     * @var string[]
     */
    protected $MP_array = [];

    /**
     * HMENU configuration
     *
     * @var array
     */
    protected $conf = [];

    /**
     * xMENU configuration (TMENU etc)
     *
     * @var array
     */
    protected $mconf = [];

    /**
     * @var TemplateService
     */
    protected $tmpl;

    /**
     * @var PageRepository
     */
    protected $sys_page;

    /**
     * The base page-id of the menu.
     *
     * @var int
     */
    protected $id;

    /**
     * Holds the page uid of the NEXT page in the root line from the page pointed to by entryLevel;
     * Used to expand the menu automatically if in a certain root line.
     *
     * @var string
     */
    protected $nextActive;

    /**
     * The array of menuItems which is built
     *
     * @var array[]
     */
    protected $menuArr;

    /**
     * @var string
     */
    protected $hash;

    /**
     * @var array
     */
    protected $result = [];

    /**
     * Is filled with an array of page uid numbers + RL parameters which are in the current
     * root line (used to evaluate whether a menu item is in active state)
     *
     * @var array
     */
    protected $rL_uidRegister;

    /**
     * @var mixed[]
     */
    protected $I;

    /**
     * @var string
     */
    protected $WMresult;

    /**
     * @var int
     */
    protected $WMmenuItems;

    /**
     * @var array[]
     */
    protected $WMsubmenuObjSuffixes;

    /**
     * @var ContentObjectRenderer
     */
    protected $WMcObj;

    protected ?ServerRequestInterface $request = null;

    /**
     * Can be set to contain menu item arrays for sub-levels.
     *
     * @var array
     */
    protected $alternativeMenuTempArray = [];

    /**
     * Array key of the parentMenuItem in the parentMenuArr, if this menu is a subMenu.
     *
     * @var int|null
     */
    protected $parentMenuArrItemKey;

    /**
     * @var array
     */
    protected $parentMenuArr;

    protected const customItemStates = [
        // IFSUB is TRUE if there exist submenu items to the current item
        'IFSUB',
        'ACT',
        // ACTIFSUB is TRUE if there exist submenu items to the current item and the current item is active
        'ACTIFSUB',
        // CUR is TRUE if the current page equals the item here!
        'CUR',
        // CURIFSUB is TRUE if there exist submenu items to the current item and the current page equals the item here!
        'CURIFSUB',
        'USR',
        'SPC',
        'USERDEF1',
        'USERDEF2',
    ];

    /**
     * The initialization of the object. This just sets some internal variables.
     *
     * @param TemplateService $tmpl The $this->getTypoScriptFrontendController()->tmpl object
     * @param PageRepository $sys_page The $this->getTypoScriptFrontendController()->sys_page object
     * @param int|string $id A starting point page id. This should probably be blank since the 'entryLevel' value will be used then.
     * @param array $conf The TypoScript configuration for the HMENU cObject
     * @param int $menuNumber Menu number; 1,2,3. Should probably be 1
     * @param string $objSuffix Submenu Object suffix. This offers submenus a way to use alternative configuration for specific positions in the menu; By default "1 = TMENU" would use "1." for the TMENU configuration, but if this string is set to eg. "a" then "1a." would be used for configuration instead (while "1 = " is still used for the overall object definition of "TMENU")
     * @param ServerRequestInterface|null $request
     * @return bool Returns TRUE on success
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::HMENU()
     */
    public function start($tmpl, $sys_page, $id, $conf, $menuNumber, $objSuffix = '', ?ServerRequestInterface $request = null)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $this->conf = $conf;
        $this->menuNumber = $menuNumber;
        $this->mconf = $conf[$this->menuNumber . $objSuffix . '.'];
        $this->request = $request;
        $this->WMcObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        // Sets the internal vars. $tmpl MUST be the template-object. $sys_page MUST be the PageRepository object
        if ($this->conf[$this->menuNumber . $objSuffix] && is_object($tmpl) && is_object($sys_page)) {
            $this->tmpl = $tmpl;
            $this->sys_page = $sys_page;
            // alwaysActivePIDlist initialized:
            $this->conf['alwaysActivePIDlist'] = (string)$this->parent_cObj->stdWrapValue('alwaysActivePIDlist', $this->conf ?? []);
            if (trim($this->conf['alwaysActivePIDlist'])) {
                $this->alwaysActivePIDlist = GeneralUtility::intExplode(',', $this->conf['alwaysActivePIDlist']);
            }
            // includeNotInMenu initialized:
            $this->conf['includeNotInMenu'] = $this->parent_cObj->stdWrapValue('includeNotInMenu', $this->conf, false);
            // exclude doktypes that should not be shown in menu (e.g. backend user section)
            if ($this->conf['excludeDoktypes'] ?? false) {
                $this->excludedDoktypes = GeneralUtility::intExplode(',', $this->conf['excludeDoktypes']);
            }
            // EntryLevel
            $this->entryLevel = $this->parent_cObj->getKey(
                $this->parent_cObj->stdWrapValue('entryLevel', $this->conf ?? []),
                $this->tmpl->rootLine
            );
            // Set parent page: If $id not stated with start() then the base-id will be found from rootLine[$this->entryLevel]
            // Called as the next level in a menu. It is assumed that $this->MP_array is set from parent menu.
            if ($id) {
                $this->id = (int)$id;
            } else {
                // This is a BRAND NEW menu, first level. So we take ID from rootline and also find MP_array (mount points)
                $this->id = (int)($this->tmpl->rootLine[$this->entryLevel]['uid'] ?? 0);

                // Traverse rootline to build MP_array of pages BEFORE the entryLevel
                // (MP var for ->id is picked up in the next part of the code...)
                foreach ($this->tmpl->rootLine as $entryLevel => $levelRec) {
                    // For overlaid mount points, set the variable right now:
                    if (($levelRec['_MP_PARAM'] ?? false) && ($levelRec['_MOUNT_OL'] ?? false)) {
                        $this->MP_array[] = $levelRec['_MP_PARAM'];
                    }

                    // Break when entry level is reached:
                    if ($entryLevel >= $this->entryLevel) {
                        break;
                    }

                    // For normal mount points, set the variable for next level.
                    if (!empty($levelRec['_MP_PARAM']) && empty($levelRec['_MOUNT_OL'])) {
                        $this->MP_array[] = $levelRec['_MP_PARAM'];
                    }
                }
            }
            // Return FALSE if no page ID was set (thus no menu of subpages can be made).
            if ($this->id <= 0) {
                return false;
            }
            // Check if page is a mount point, and if so set id and MP_array
            // (basically this is ONLY for non-overlay mode, but in overlay mode an ID with a mount point should never reach this point anyways, so no harm done...)
            $mount_info = $this->sys_page->getMountPointInfo($this->id);
            if (is_array($mount_info)) {
                $this->MP_array[] = $mount_info['MPvar'];
                $this->id = $mount_info['mount_pid'];
            }
            // Gather list of page uids in root line (for "isActive" evaluation). Also adds the MP params in the path so Mount Points are respected.
            // (List is specific for this rootline, so it may be supplied from parent menus for speed...)
            if ($this->rL_uidRegister === null) {
                $this->rL_uidRegister = [];
                $rl_MParray = [];
                foreach ($this->tmpl->rootLine as $v_rl) {
                    // For overlaid mount points, set the variable right now:
                    if (($v_rl['_MP_PARAM'] ?? false) && ($v_rl['_MOUNT_OL'] ?? false)) {
                        $rl_MParray[] = $v_rl['_MP_PARAM'];
                    }
                    // Add to register:
                    $this->rL_uidRegister[] = 'ITEM:' . $v_rl['uid'] .
                        (
                            !empty($rl_MParray)
                            ? ':' . implode(',', $rl_MParray)
                            : ''
                        );
                    // For normal mount points, set the variable for next level.
                    if (($v_rl['_MP_PARAM'] ?? false) && !($v_rl['_MOUNT_OL'] ?? false)) {
                        $rl_MParray[] = $v_rl['_MP_PARAM'];
                    }
                }
            }
            // Set $directoryLevel so the following evaluation of the nextActive will not return
            // an invalid value if .special=directory was set
            $directoryLevel = 0;
            if (($this->conf['special'] ?? '') === 'directory') {
                $value = $this->parent_cObj->stdWrapValue('value', $this->conf['special.'] ?? [], null);
                if ($value === '') {
                    $value = (string)$tsfe->id;
                }
                $directoryLevel = (int)$tsfe->tmpl->getRootlineLevel($value);
            }
            // Setting "nextActive": This is the page uid + MPvar of the NEXT page in rootline. Used to expand the menu if we are in the right branch of the tree
            // Notice: The automatic expansion of a menu is designed to work only when no "special" modes (except "directory") are used.
            $startLevel = $directoryLevel ?: $this->entryLevel;
            $currentLevel = $startLevel + $this->menuNumber;
            if (is_array($this->tmpl->rootLine[$currentLevel] ?? null)) {
                $nextMParray = $this->MP_array;
                if (empty($nextMParray) && !($this->tmpl->rootLine[$currentLevel]['_MOUNT_OL'] ?? false) && $currentLevel > 0) {
                    // Make sure to slide-down any mount point information (_MP_PARAM) to children records in the rootline
                    // otherwise automatic expansion will not work
                    $parentRecord = $this->tmpl->rootLine[$currentLevel - 1];
                    if (isset($parentRecord['_MP_PARAM'])) {
                        $nextMParray[] = $parentRecord['_MP_PARAM'];
                    }
                }
                // In overlay mode, add next level MPvars as well:
                if ($this->tmpl->rootLine[$currentLevel]['_MOUNT_OL'] ?? false) {
                    $nextMParray[] = $this->tmpl->rootLine[$currentLevel]['_MP_PARAM'];
                }
                $this->nextActive = $this->tmpl->rootLine[$currentLevel]['uid'] .
                    (
                        !empty($nextMParray)
                        ? ':' . implode(',', $nextMParray)
                        : ''
                    );
            } else {
                $this->nextActive = '';
            }
            return true;
        }
        $this->getTimeTracker()->setTSlogMessage('ERROR in menu', LogLevel::ERROR);
        return false;
    }

    /**
     * Creates the menu in the internal variables, ready for output.
     * Basically this will read the page records needed and fill in the internal $this->menuArr
     * Based on a hash of this array and some other variables the $this->result variable will be
     * loaded either from cache OR by calling the generate() method of the class to create the menu for real.
     */
    public function makeMenu()
    {
        if (!$this->id) {
            return;
        }

        // Initializing showAccessRestrictedPages
        $SAVED_where_groupAccess = '';
        if ($this->mconf['showAccessRestrictedPages'] ?? false) {
            // SAVING where_groupAccess
            $SAVED_where_groupAccess = $this->sys_page->where_groupAccess;
            // Temporarily removing fe_group checking!
            $this->sys_page->where_groupAccess = '';
        }

        $menuItems = $this->prepareMenuItems();

        $c = 0;
        $c_b = 0;

        $minItems = (int)(($this->mconf['minItems'] ?? 0) ?: ($this->conf['minItems'] ?? 0));
        $maxItems = (int)(($this->mconf['maxItems'] ?? 0) ?: ($this->conf['maxItems'] ?? 0));
        $begin = $this->parent_cObj->calc(($this->mconf['begin'] ?? 0) ?: ($this->conf['begin'] ?? 0));
        $minItemsConf = $this->mconf['minItems.'] ?? $this->conf['minItems.'] ?? null;
        $minItems = is_array($minItemsConf) ? $this->parent_cObj->stdWrap($minItems, $minItemsConf) : $minItems;
        $maxItemsConf = $this->mconf['maxItems.'] ?? $this->conf['maxItems.'] ?? null;
        $maxItems = is_array($maxItemsConf) ? $this->parent_cObj->stdWrap($maxItems, $maxItemsConf) : $maxItems;
        $beginConf = $this->mconf['begin.'] ?? $this->conf['begin.'] ?? null;
        $begin = is_array($beginConf) ? $this->parent_cObj->stdWrap($begin, $beginConf) : $begin;
        $banUidArray = $this->getBannedUids();
        // Fill in the menuArr with elements that should go into the menu:
        $this->menuArr = [];
        foreach ($menuItems as $data) {
            $isSpacerPage = (int)($data['doktype'] ?? 0) === PageRepository::DOKTYPE_SPACER || ($data['ITEM_STATE'] ?? '') === 'SPC';
            // if item is a spacer, $spacer is set
            if ($this->filterMenuPages($data, $banUidArray, $isSpacerPage)) {
                $c_b++;
                // If the beginning item has been reached.
                if ($begin <= $c_b) {
                    $this->menuArr[$c] = $this->determineOriginalShortcutPage($data);
                    $this->menuArr[$c]['isSpacer'] = $isSpacerPage;
                    $c++;
                    if ($maxItems && $c >= $maxItems) {
                        break;
                    }
                }
            }
        }
        // Fill in fake items, if min-items is set.
        if ($minItems) {
            while ($c < $minItems) {
                $this->menuArr[$c] = [
                    'title' => '...',
                    'uid' => $this->getTypoScriptFrontendController()->id,
                ];
                $c++;
            }
        }
        //	Passing the menuArr through a user defined function:
        if ($this->mconf['itemArrayProcFunc'] ?? false) {
            $this->menuArr = $this->userProcess('itemArrayProcFunc', $this->menuArr);
        }
        // Setting number of menu items
        $this->getTypoScriptFrontendController()->register['count_menuItems'] = count($this->menuArr);
        $this->hash = md5(
            json_encode($this->menuArr) .
            json_encode($this->mconf) .
            json_encode($this->tmpl->rootLine) .
            json_encode($this->MP_array)
        );
        // Get the cache timeout:
        if ($this->conf['cache_period'] ?? false) {
            $cacheTimeout = $this->conf['cache_period'];
        } else {
            $cacheTimeout = $this->getTypoScriptFrontendController()->get_cache_timeout();
        }
        $cache = $this->getCache();
        $cachedData = $cache->get($this->hash);
        if (!is_array($cachedData)) {
            $this->generate();
            $cache->set($this->hash, $this->result, ['ident_MENUDATA'], (int)$cacheTimeout);
        } else {
            $this->result = $cachedData;
        }
        // End showAccessRestrictedPages
        if ($this->mconf['showAccessRestrictedPages'] ?? false) {
            // RESTORING where_groupAccess
            $this->sys_page->where_groupAccess = $SAVED_where_groupAccess;
        }
    }

    /**
     * Generates the the menu data.
     *
     * Subclasses should overwrite this method.
     */
    public function generate()
    {
    }

    /**
     * @return string The HTML for the menu
     */
    public function writeMenu()
    {
        return '';
    }

    /**
     * Gets an array of page rows and removes all, which are not accessible
     *
     * @param array $pages
     * @return array
     */
    protected function removeInaccessiblePages(array $pages)
    {
        $banned = $this->getBannedUids();
        $filteredPages = [];
        foreach ($pages as $aPage) {
            if ($this->filterMenuPages($aPage, $banned, (int)$aPage['doktype'] === PageRepository::DOKTYPE_SPACER)) {
                $filteredPages[$aPage['uid']] = $aPage;
            }
        }
        return $filteredPages;
    }

    /**
     * Main function for retrieving menu items based on the menu type (special or sectionIndex or "normal")
     *
     * @return array
     */
    protected function prepareMenuItems()
    {
        $menuItems = [];
        $alternativeSortingField = trim($this->mconf['alternativeSortingField'] ?? '') ?: 'sorting';

        // Additional where clause, usually starts with AND (as usual with all additionalWhere functionality in TS)
        $additionalWhere = $this->parent_cObj->stdWrapValue('additionalWhere', $this->mconf ?? []);
        $additionalWhere .= $this->getDoktypeExcludeWhere();

        // ... only for the FIRST level of a HMENU
        if ($this->menuNumber == 1 && ($this->conf['special'] ?? false)) {
            $value = (string)$this->parent_cObj->stdWrapValue('value', $this->conf['special.'] ?? [], null);
            switch ($this->conf['special']) {
                case 'userfunction':
                    $menuItems = $this->prepareMenuItemsForUserSpecificMenu($value, $alternativeSortingField);
                    break;
                case 'language':
                    $menuItems = $this->prepareMenuItemsForLanguageMenu($value);
                    break;
                case 'directory':
                    $menuItems = $this->prepareMenuItemsForDirectoryMenu($value, $alternativeSortingField);
                    break;
                case 'list':
                    $menuItems = $this->prepareMenuItemsForListMenu($value);
                    break;
                case 'updated':
                    $menuItems = $this->prepareMenuItemsForUpdatedMenu(
                        $value,
                        $this->mconf['alternativeSortingField'] ?? ''
                    );
                    break;
                case 'keywords':
                    $menuItems = $this->prepareMenuItemsForKeywordsMenu(
                        $value,
                        $this->mconf['alternativeSortingField'] ?? ''
                    );
                    break;
                case 'categories':
                    $categoryMenuUtility = GeneralUtility::makeInstance(CategoryMenuUtility::class);
                    $menuItems = $categoryMenuUtility->collectPages($value, $this->conf['special.'], $this);
                    break;
                case 'rootline':
                    $menuItems = $this->prepareMenuItemsForRootlineMenu();
                    break;
                case 'browse':
                    $menuItems = $this->prepareMenuItemsForBrowseMenu($value, $alternativeSortingField, $additionalWhere);
                    break;
            }
            if ($this->mconf['sectionIndex'] ?? false) {
                $sectionIndexes = [];
                foreach ($menuItems as $page) {
                    $sectionIndexes = $sectionIndexes + $this->sectionIndex($alternativeSortingField, $page['uid']);
                }
                $menuItems = $sectionIndexes;
            }
        } elseif ($this->alternativeMenuTempArray !== []) {
            // Setting $menuItems array if not level 1.
            $menuItems = $this->alternativeMenuTempArray;
        } elseif ($this->mconf['sectionIndex'] ?? false) {
            $menuItems = $this->sectionIndex($alternativeSortingField);
        } else {
            // Default: Gets a hierarchical menu based on subpages of $this->id
            $menuItems = $this->sys_page->getMenu($this->id, '*', $alternativeSortingField, $additionalWhere);
        }
        return $menuItems;
    }

    /**
     * Fetches all menuitems if special = userfunction is set
     *
     * @param string $specialValue The value from special.value
     * @param string $sortingField The sorting field
     * @return array
     */
    protected function prepareMenuItemsForUserSpecificMenu($specialValue, $sortingField)
    {
        $menuItems = $this->parent_cObj->callUserFunction(
            $this->conf['special.']['userFunc'],
            array_merge($this->conf['special.'], ['value' => $specialValue, '_altSortField' => $sortingField]),
            ''
        );
        return is_array($menuItems) ? $menuItems : [];
    }

    /**
     * Fetches all menuitems if special = language is set
     *
     * @param string $specialValue The value from special.value
     * @return array
     */
    protected function prepareMenuItemsForLanguageMenu($specialValue)
    {
        $menuItems = [];
        // Getting current page record NOT overlaid by any translation:
        $tsfe = $this->getTypoScriptFrontendController();
        $currentPageWithNoOverlay = $this->sys_page->getRawRecord('pages', $tsfe->id);

        if ($specialValue === 'auto') {
            $site = $this->getCurrentSite();
            $languages = $site->getLanguages();
            $languageItems = array_keys($languages);
        } else {
            $languageItems = GeneralUtility::intExplode(',', $specialValue);
        }

        $tsfe->register['languages_HMENU'] = implode(',', $languageItems);

        $currentLanguageId = $this->getCurrentLanguageAspect()->getId();

        foreach ($languageItems as $sUid) {
            // Find overlay record:
            if ($sUid) {
                $lRecs = $this->sys_page->getPageOverlay($currentPageWithNoOverlay, $sUid);
                // getPageOverlay() might return the original record again, if so this is emptied
                // this should be fixed in PageRepository in the future.
                if (!empty($lRecs) && !isset($lRecs['_PAGES_OVERLAY'])) {
                    $lRecs = [];
                }
            } else {
                $lRecs = [];
            }
            // Checking if the "disabled" state should be set.
            $pageTranslationVisibility = new PageTranslationVisibility((int)($currentPageWithNoOverlay['l18n_cfg'] ?? 0));
            if ($pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists() && $sUid &&
                empty($lRecs) || $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage() &&
                (!$sUid || empty($lRecs)) ||
                !($this->conf['special.']['normalWhenNoLanguage'] ?? false) && $sUid && empty($lRecs)
            ) {
                $iState = $currentLanguageId === $sUid ? 'USERDEF2' : 'USERDEF1';
            } else {
                $iState = $currentLanguageId === $sUid ? 'ACT' : 'NO';
            }
            // Adding menu item:
            $menuItems[] = array_merge(
                array_merge($currentPageWithNoOverlay, $lRecs),
                [
                    '_PAGES_OVERLAY_REQUESTEDLANGUAGE' => $sUid,
                    'ITEM_STATE' => $iState,
                    '_ADD_GETVARS' => $this->conf['addQueryString'] ?? false,
                    '_SAFE' => true,
                ]
            );
        }
        return $menuItems;
    }

    /**
     * Fetches all menuitems if special = directory is set
     *
     * @param string $specialValue The value from special.value
     * @param string $sortingField The sorting field
     * @return array
     */
    protected function prepareMenuItemsForDirectoryMenu($specialValue, $sortingField)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $menuItems = [];
        if ($specialValue == '') {
            $specialValue = $tsfe->id;
        }
        $items = GeneralUtility::intExplode(',', (string)$specialValue);
        $pageLinkBuilder = GeneralUtility::makeInstance(PageLinkBuilder::class, $this->parent_cObj);
        foreach ($items as $id) {
            $MP = $pageLinkBuilder->getMountPointParameterFromRootPointMaps($id);
            // Checking if a page is a mount page and if so, change the ID and set the MP var properly.
            $mount_info = $this->sys_page->getMountPointInfo($id);
            if (is_array($mount_info)) {
                if ($mount_info['overlay']) {
                    // Overlays should already have their full MPvars calculated:
                    $MP = $pageLinkBuilder->getMountPointParameterFromRootPointMaps((int)$mount_info['mount_pid']);
                    $MP = $MP ?: $mount_info['MPvar'];
                } else {
                    $MP = ($MP ? $MP . ',' : '') . $mount_info['MPvar'];
                }
                $id = $mount_info['mount_pid'];
            }
            $subPages = $this->sys_page->getMenu($id, '*', $sortingField);
            foreach ($subPages as $row) {
                // Add external MP params
                if ($MP) {
                    $row['_MP_PARAM'] = $MP . (($row['_MP_PARAM'] ?? '') ? ',' . $row['_MP_PARAM'] : '');
                }
                $menuItems[] = $row;
            }
        }

        return $menuItems;
    }

    /**
     * Fetches all menuitems if special = list is set
     *
     * @param string $specialValue The value from special.value
     * @return array
     */
    protected function prepareMenuItemsForListMenu($specialValue)
    {
        $menuItems = [];
        if ($specialValue == '') {
            $specialValue = $this->id;
        }
        $pageIds = GeneralUtility::intExplode(',', (string)$specialValue);
        $disableGroupAccessCheck = !empty($this->mconf['showAccessRestrictedPages']);
        $pageRecords = $this->sys_page->getMenuForPages($pageIds);
        // After fetching the page records, restore the initial order by using the page id list as arrays keys and
        // replace them with the resolved page records. The id list is cleaned up first, since ids might be invalid.
        $pageRecords = array_replace(
            array_flip(array_intersect(array_values($pageIds), array_keys($pageRecords))),
            $pageRecords
        );
        $pageLinkBuilder = GeneralUtility::makeInstance(PageLinkBuilder::class, $this->parent_cObj);
        foreach ($pageRecords as $row) {
            $pageId = (int)$row['uid'];
            $MP = $pageLinkBuilder->getMountPointParameterFromRootPointMaps($pageId);
            // Keep mount point?
            $mount_info = $this->sys_page->getMountPointInfo($pageId, $row);
            // $pageId is a valid mount point
            if (is_array($mount_info) && $mount_info['overlay']) {
                $mountedPageId = (int)$mount_info['mount_pid'];
                // Using "getPage" is OK since we need the check for enableFields
                // AND for type 2 of mount pids we DO require a doktype < 200!
                $mountedPageRow = $this->sys_page->getPage($mountedPageId, $disableGroupAccessCheck);
                if (empty($mountedPageRow)) {
                    // If the mount point could not be fetched with respect to
                    // enableFields, the page should not become a part of the menu!
                    continue;
                }
                $row = $mountedPageRow;
                $row['_MP_PARAM'] = $mount_info['MPvar'];
                // Overlays should already have their full MPvars calculated, that's why we unset the
                // existing $row['_MP_PARAM'], as the full $MP will be added again below
                $MP = $pageLinkBuilder->getMountPointParameterFromRootPointMaps($mountedPageId);
                if ($MP) {
                    unset($row['_MP_PARAM']);
                }
            }
            if ($MP) {
                $row['_MP_PARAM'] = $MP . ($row['_MP_PARAM'] ? ',' . $row['_MP_PARAM'] : '');
            }
            $menuItems[] = $row;
        }
        return $menuItems;
    }

    /**
     * Fetches all menuitems if special = updated is set
     *
     * @param string $specialValue The value from special.value
     * @param string $sortingField The sorting field
     * @return array
     */
    protected function prepareMenuItemsForUpdatedMenu($specialValue, $sortingField)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $menuItems = [];
        if ($specialValue == '') {
            $specialValue = $tsfe->id;
        }
        $items = GeneralUtility::intExplode(',', (string)$specialValue);
        if (MathUtility::canBeInterpretedAsInteger($this->conf['special.']['depth'] ?? null)) {
            $depth = MathUtility::forceIntegerInRange($this->conf['special.']['depth'], 1, 20);
        } else {
            $depth = 20;
        }
        // Max number of items
        $limit = MathUtility::forceIntegerInRange(($this->conf['special.']['limit'] ?? 0), 0, 100);
        $maxAge = (int)($this->parent_cObj->calc($this->conf['special.']['maxAge'] ?? 0));
        if (!$limit) {
            $limit = 10;
        }
        // 'auto', 'manual', 'tstamp'
        $mode = $this->conf['special.']['mode'] ?? '';
        // Get id's
        $beginAtLevel = MathUtility::forceIntegerInRange(($this->conf['special.']['beginAtLevel'] ?? 0), 0, 100);
        $id_list_arr = [];
        foreach ($items as $id) {
            // Exclude the current ID if beginAtLevel is > 0
            if ($beginAtLevel > 0) {
                $id_list_arr[] = $this->parent_cObj->getTreeList($id, $depth - 1 + $beginAtLevel, $beginAtLevel - 1);
            } else {
                $id_list_arr[] = $this->parent_cObj->getTreeList(-1 * $id, $depth - 1 + $beginAtLevel, $beginAtLevel - 1);
            }
        }
        $id_list = implode(',', $id_list_arr);
        $pageIds = GeneralUtility::intExplode(',', $id_list);
        // Get sortField (mode)
        $sortField = $this->getMode($mode);

        $extraWhere = ($this->conf['includeNotInMenu'] ? '' : ' AND pages.nav_hide=0') . $this->getDoktypeExcludeWhere();
        if ($this->conf['special.']['excludeNoSearchPages'] ?? false) {
            $extraWhere .= ' AND pages.no_search=0';
        }
        if ($maxAge > 0) {
            $extraWhere .= ' AND ' . $sortField . '>' . ($GLOBALS['SIM_ACCESS_TIME'] - $maxAge);
        }
        $extraWhere = $sortField . '>=0' . $extraWhere;

        $i = 0;
        $pageRecords = $this->sys_page->getMenuForPages($pageIds, '*', $sortingField ?: $sortField . ' DESC', $extraWhere);
        foreach ($pageRecords as $row) {
            // Build a custom LIMIT clause as "getMenuForPages()" does not support this
            if (++$i > $limit) {
                continue;
            }
            $menuItems[$row['uid']] = $row;
        }

        return $menuItems;
    }

    /**
     * Fetches all menuitems if special = keywords is set
     *
     * @param string $specialValue The value from special.value
     * @param string $sortingField The sorting field
     * @return array
     */
    protected function prepareMenuItemsForKeywordsMenu($specialValue, $sortingField)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $menuItems = [];
        [$specialValue] = GeneralUtility::intExplode(',', $specialValue);
        if (!$specialValue) {
            $specialValue = $tsfe->id;
        }
        if (($this->conf['special.']['setKeywords'] ?? false) || ($this->conf['special.']['setKeywords.'] ?? false)) {
            $kw = (string)$this->parent_cObj->stdWrapValue('setKeywords', $this->conf['special.'] ?? []);
        } else {
            // The page record of the 'value'.
            $value_rec = $this->sys_page->getPage($specialValue);
            $kfieldSrc = ($this->conf['special.']['keywordsField.']['sourceField'] ?? false) ? $this->conf['special.']['keywordsField.']['sourceField'] : 'keywords';
            // keywords.
            $kw = trim($this->parent_cObj->keywords($value_rec[$kfieldSrc]));
        }
        // *'auto', 'manual', 'tstamp'
        $mode = $this->conf['special.']['mode'] ?? '';
        $sortField = $this->getMode($mode);
        // Depth, limit, extra where
        if (MathUtility::canBeInterpretedAsInteger($this->conf['special.']['depth'] ?? null)) {
            $depth = MathUtility::forceIntegerInRange($this->conf['special.']['depth'], 0, 20);
        } else {
            $depth = 20;
        }
        // Max number of items
        $limit = MathUtility::forceIntegerInRange(($this->conf['special.']['limit'] ?? 0), 0, 100);
        // Start point
        $eLevel = $this->parent_cObj->getKey(
            $this->parent_cObj->stdWrapValue('entryLevel', $this->conf['special.'] ?? []),
            $this->tmpl->rootLine
        );
        $startUid = (int)($this->tmpl->rootLine[$eLevel]['uid'] ?? 0);
        // Which field is for keywords
        $kfield = 'keywords';
        if ($this->conf['special.']['keywordsField'] ?? false) {
            [$kfield] = explode(' ', trim($this->conf['special.']['keywordsField']));
        }
        // If there are keywords and the startuid is present
        if ($kw && $startUid) {
            $bA = MathUtility::forceIntegerInRange(($this->conf['special.']['beginAtLevel'] ?? 0), 0, 100);
            $id_list = $this->parent_cObj->getTreeList(-1 * $startUid, $depth - 1 + $bA, $bA - 1);
            $kwArr = GeneralUtility::trimExplode(',', $kw, true);
            $keyWordsWhereArr = [];
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            foreach ($kwArr as $word) {
                $keyWordsWhereArr[] = $queryBuilder->expr()->like(
                    $kfield,
                    $queryBuilder->createNamedParameter(
                        '%' . $queryBuilder->escapeLikeWildcards($word) . '%',
                        \PDO::PARAM_STR
                    )
                );
            }
            $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        GeneralUtility::intExplode(',', $id_list, true)
                    ),
                    $queryBuilder->expr()->neq(
                        'uid',
                        $queryBuilder->createNamedParameter($specialValue, \PDO::PARAM_INT)
                    )
                );

            if (!empty($keyWordsWhereArr)) {
                $queryBuilder->andWhere($queryBuilder->expr()->orX(...$keyWordsWhereArr));
            }

            if (!empty($this->excludedDoktypes)) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->notIn(
                        'pages.doktype',
                        $this->excludedDoktypes
                    )
                );
            }

            if (!$this->conf['includeNotInMenu']) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq('pages.nav_hide', 0));
            }

            if ($this->conf['special.']['excludeNoSearchPages'] ?? false) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq('pages.no_search', 0));
            }

            if ($limit > 0) {
                $queryBuilder->setMaxResults($limit);
            }

            if ($sortingField) {
                $queryBuilder->orderBy($sortingField);
            } else {
                $queryBuilder->orderBy($sortField, 'desc');
            }

            $result = $queryBuilder->executeQuery();
            while ($row = $result->fetchAssociative()) {
                $this->sys_page->versionOL('pages', $row, true);
                if (is_array($row)) {
                    $menuItems[$row['uid']] = $this->sys_page->getPageOverlay($row);
                }
            }
        }

        return $menuItems;
    }

    /**
     * Fetches all menuitems if special = rootline is set
     *
     * @return array
     */
    protected function prepareMenuItemsForRootlineMenu()
    {
        $menuItems = [];
        $range = (string)$this->parent_cObj->stdWrapValue('range', $this->conf['special.'] ?? []);
        $begin_end = explode('|', $range);
        $begin_end[0] = (int)$begin_end[0];
        if (!MathUtility::canBeInterpretedAsInteger($begin_end[1] ?? '')) {
            $begin_end[1] = -1;
        }
        $beginKey = $this->parent_cObj->getKey($begin_end[0], $this->tmpl->rootLine);
        $endKey = $this->parent_cObj->getKey($begin_end[1], $this->tmpl->rootLine);
        if ($endKey < $beginKey) {
            $endKey = $beginKey;
        }
        $rl_MParray = [];
        foreach ($this->tmpl->rootLine as $k_rl => $v_rl) {
            // For overlaid mount points, set the variable right now:
            if (($v_rl['_MP_PARAM'] ?? false) && ($v_rl['_MOUNT_OL'] ?? false)) {
                $rl_MParray[] = $v_rl['_MP_PARAM'];
            }
            // Traverse rootline:
            if ($k_rl >= $beginKey && $k_rl <= $endKey) {
                $temp_key = $k_rl;
                $menuItems[$temp_key] = $this->sys_page->getPage($v_rl['uid']);
                if (!empty($menuItems[$temp_key])) {
                    // If there are no specific target for the page, put the level specific target on.
                    if (!$menuItems[$temp_key]['target']) {
                        $menuItems[$temp_key]['target'] = $this->conf['special.']['targets.'][$k_rl] ?? '';
                        $menuItems[$temp_key]['_MP_PARAM'] = implode(',', $rl_MParray);
                    }
                } else {
                    unset($menuItems[$temp_key]);
                }
            }
            // For normal mount points, set the variable for next level.
            if (($v_rl['_MP_PARAM'] ?? false) && !($v_rl['_MOUNT_OL'] ?? false)) {
                $rl_MParray[] = $v_rl['_MP_PARAM'];
            }
        }
        // Reverse order of elements (e.g. "1,2,3,4" gets "4,3,2,1"):
        if (isset($this->conf['special.']['reverseOrder']) && $this->conf['special.']['reverseOrder']) {
            $menuItems = array_reverse($menuItems);
        }
        return $menuItems;
    }

    /**
     * Fetches all menuitems if special = browse is set
     *
     * @param string $specialValue The value from special.value
     * @param string $sortingField The sorting field
     * @param string $additionalWhere Additional WHERE clause
     * @return array
     */
    protected function prepareMenuItemsForBrowseMenu($specialValue, $sortingField, $additionalWhere)
    {
        $menuItems = [];
        [$specialValue] = GeneralUtility::intExplode(',', $specialValue);
        if (!$specialValue) {
            $specialValue = $this->getTypoScriptFrontendController()->page['uid'];
        }
        // Will not work out of rootline
        if ($specialValue != $this->tmpl->rootLine[0]['uid']) {
            $recArr = [];
            // The page record of the 'value'.
            $value_rec = $this->sys_page->getPage($specialValue);
            // 'up' page cannot be outside rootline
            if ($value_rec['pid']) {
                // The page record of 'up'.
                $recArr['up'] = $this->sys_page->getPage($value_rec['pid']);
            }
            // If the 'up' item was NOT level 0 in rootline...
            if (($recArr['up']['pid'] ?? 0) && $value_rec['pid'] != $this->tmpl->rootLine[0]['uid']) {
                // The page record of "index".
                $recArr['index'] = $this->sys_page->getPage($recArr['up']['pid']);
            }
            // check if certain pages should be excluded
            $additionalWhere .= ($this->conf['includeNotInMenu'] ? '' : ' AND pages.nav_hide=0') . $this->getDoktypeExcludeWhere();
            if ($this->conf['special.']['excludeNoSearchPages'] ?? false) {
                $additionalWhere .= ' AND pages.no_search=0';
            }
            // prev / next is found
            $prevnext_menu = $this->removeInaccessiblePages($this->sys_page->getMenu($value_rec['pid'], '*', $sortingField, $additionalWhere));
            $lastKey = 0;
            $nextActive = 0;
            foreach ($prevnext_menu as $k_b => $v_b) {
                if ($nextActive) {
                    $recArr['next'] = $v_b;
                    $nextActive = 0;
                }
                if ($v_b['uid'] == $specialValue) {
                    if ($lastKey) {
                        $recArr['prev'] = $prevnext_menu[$lastKey];
                    }
                    $nextActive = 1;
                }
                $lastKey = $k_b;
            }

            $recArr['first'] = reset($prevnext_menu);
            $recArr['last'] = end($prevnext_menu);
            // prevsection / nextsection is found
            // You can only do this, if there is a valid page two levels up!
            if (!empty($recArr['index']['uid'])) {
                $prevnextsection_menu = $this->removeInaccessiblePages($this->sys_page->getMenu($recArr['index']['uid'], '*', $sortingField, $additionalWhere));
                $lastKey = 0;
                $nextActive = 0;
                foreach ($prevnextsection_menu as $k_b => $v_b) {
                    if ($nextActive) {
                        $sectionRec_temp = $this->removeInaccessiblePages($this->sys_page->getMenu($v_b['uid'], '*', $sortingField, $additionalWhere));
                        if (!empty($sectionRec_temp)) {
                            $recArr['nextsection'] = reset($sectionRec_temp);
                            $recArr['nextsection_last'] = end($sectionRec_temp);
                            $nextActive = 0;
                        }
                    }
                    if ($v_b['uid'] == $value_rec['pid']) {
                        if ($lastKey) {
                            $sectionRec_temp = $this->removeInaccessiblePages($this->sys_page->getMenu($prevnextsection_menu[$lastKey]['uid'], '*', $sortingField, $additionalWhere));
                            if (!empty($sectionRec_temp)) {
                                $recArr['prevsection'] = reset($sectionRec_temp);
                                $recArr['prevsection_last'] = end($sectionRec_temp);
                            }
                        }
                        $nextActive = 1;
                    }
                    $lastKey = $k_b;
                }
            }
            if ($this->conf['special.']['items.']['prevnextToSection'] ?? false) {
                if (!is_array($recArr['prev']) && is_array($recArr['prevsection_last'])) {
                    $recArr['prev'] = $recArr['prevsection_last'];
                }
                if (!is_array($recArr['next']) && is_array($recArr['nextsection'])) {
                    $recArr['next'] = $recArr['nextsection'];
                }
            }
            $items = explode('|', $this->conf['special.']['items']);
            $c = 0;
            foreach ($items as $k_b => $v_b) {
                $v_b = strtolower(trim($v_b));
                if ((int)($this->conf['special.'][$v_b . '.']['uid'] ?? false)) {
                    $recArr[$v_b] = $this->sys_page->getPage((int)$this->conf['special.'][$v_b . '.']['uid']);
                }
                if (is_array($recArr[$v_b] ?? false)) {
                    $menuItems[$c] = $recArr[$v_b];
                    if ($this->conf['special.'][$v_b . '.']['target'] ?? false) {
                        $menuItems[$c]['target'] = $this->conf['special.'][$v_b . '.']['target'];
                    }
                    foreach ((array)($this->conf['special.'][$v_b . '.']['fields.'] ?? []) as $fk => $val) {
                        $menuItems[$c][$fk] = $val;
                    }
                    $c++;
                }
            }
        }
        return $menuItems;
    }

    /**
     * Checks if a page is OK to include in the final menu item array. Pages can be excluded if the doktype is wrong,
     * if they are hidden in navigation, have a uid in the list of banned uids etc.
     *
     * @param array $data Array of menu items
     * @param array $banUidArray Array of page uids which are to be excluded
     * @param bool $isSpacerPage If set, then the page is a spacer.
     * @return bool Returns TRUE if the page can be safely included.
     *
     * @throws \UnexpectedValueException
     */
    public function filterMenuPages(&$data, $banUidArray, $isSpacerPage)
    {
        $includePage = true;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof AbstractMenuFilterPagesHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . AbstractMenuFilterPagesHookInterface::class, 1269877402);
            }
            $includePage = $includePage && $hookObject->processFilter($data, $banUidArray, $isSpacerPage, $this);
        }
        if (!$includePage) {
            return false;
        }
        if ($data['_SAFE'] ?? false) {
            return true;
        }
        // If the spacer-function is not enabled, spacers will not enter the $menuArr
        if (!($this->mconf['SPC'] ?? false) && $isSpacerPage) {
            return false;
        }
        // Page may not be a 'Backend User Section' or any other excluded doktype
        if (in_array((int)($data['doktype'] ?? 0), $this->excludedDoktypes, true)) {
            return false;
        }
        $languageId = $this->getCurrentLanguageAspect()->getId();
        // PageID should not be banned (check for default language pages as well)
        if (($data['_PAGES_OVERLAY_UID'] ?? 0) > 0 && in_array((int)($data['_PAGES_OVERLAY_UID'] ?? 0), $banUidArray, true)) {
            return false;
        }
        if (in_array((int)($data['uid'] ?? 0), $banUidArray, true)) {
            return false;
        }
        // If the page is hide in menu, but the menu does not include them do not show the page
        if (($data['nav_hide'] ?? false) && !($this->conf['includeNotInMenu'] ?? false)) {
            return false;
        }
        // Checking if a page should be shown in the menu depending on whether a translation exists or if the default language is disabled
        if (!$this->sys_page->isPageSuitableForLanguage($data, $this->getCurrentLanguageAspect())) {
            return false;
        }
        // Checking if the link should point to the default language so links to non-accessible pages will not happen
        if ($languageId > 0 && !empty($this->conf['protectLvar'])) {
            $pageTranslationVisibility = new PageTranslationVisibility((int)($data['l18n_cfg'] ?? 0));
            if ($this->conf['protectLvar'] === 'all' || $pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()) {
                $olRec = $this->sys_page->getPageOverlay($data['uid'], $languageId);
                if (empty($olRec)) {
                    // If no page translation record then page can NOT be accessed in
                    // the language pointed to, therefore we protect the link by linking to the default language
                    $data['_PAGES_OVERLAY_REQUESTEDLANGUAGE'] = '0';
                }
            }
        }
        return true;
    }

    /**
     * Generating the per-menu-item configuration arrays based on the settings for item states (NO, ACT, CUR etc)
     * set in ->mconf (config for the current menu object)
     * Basically it will produce an individual array for each menu item based on the item states.
     * BUT in addition the "optionSplit" syntax for the values is ALSO evaluated here so that all property-values
     * are "option-splitted" and the output will thus be resolved.
     * Is called from the "generate" functions in the extension classes. The function is processor intensive due to
     * the option split feature in particular. But since the generate function is not always called
     * (since the ->result array may be cached, see makeMenu) it doesn't hurt so badly.
     *
     * @param int $splitCount Number of menu items in the menu
     * @return array the resolved configuration for each item
     */
    protected function processItemStates($splitCount)
    {
        // Prepare normal settings
        if (!is_array($this->mconf['NO.'] ?? null) && $this->mconf['NO']) {
            // Setting a blank array if NO=1 and there are no properties.
            $this->mconf['NO.'] = [];
        }
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $NOconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['NO.'], $splitCount);

        // Prepare custom states settings, overriding normal settings
        foreach (self::customItemStates as $state) {
            if (empty($this->mconf[$state])) {
                continue;
            }
            $customConfiguration = null;
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState($state, $key)) {
                    // if this is the first element of type $state, we must generate the custom configuration.
                    if ($customConfiguration === null) {
                        $customConfiguration = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf[$state . '.'], $splitCount);
                    }
                    // Substitute normal with the custom (e.g. IFSUB)
                    if (isset($customConfiguration[$key])) {
                        $NOconf[$key] = $customConfiguration[$key];
                    }
                }
            }
        }

        return $NOconf;
    }

    /**
     * Creates the URL, target and data-window-* attributes for the menu item link. Returns them in an array as key/value pairs for <A>-tag attributes
     * This function doesn't care about the url, because if we let the url be redirected, it will be logged in the stat!!!
     *
     * @param int $key Pointer to a key in the $this->menuArr array where the value for that key represents the menu item we are linking to (page record)
     * @param string $altTarget Alternative target
     * @param string $typeOverride Alternative type
     * @return array Returns an array with A-tag attributes as key/value pairs (HREF, TARGET and data-window-* attrs)
     */
    protected function link($key, $altTarget, $typeOverride)
    {
        $attrs = [];
        $runtimeCache = $this->getRuntimeCache();
        $MP_var = $this->getMPvar($key);
        $cacheId = 'menu-generated-links-' . md5($key . $altTarget . $typeOverride . $MP_var . ((string)($this->mconf['showAccessRestrictedPages'] ?? '_')) . json_encode($this->menuArr[$key]));
        $runtimeCachedLink = $runtimeCache->get($cacheId);
        if ($runtimeCachedLink !== false) {
            return $runtimeCachedLink;
        }

        $tsfe = $this->getTypoScriptFrontendController();

        $SAVED_link_to_restricted_pages = '';
        $SAVED_link_to_restricted_pages_additional_params = '';
        // links to a specific page
        if ($this->mconf['showAccessRestrictedPages'] ?? false) {
            $SAVED_link_to_restricted_pages = $tsfe->config['config']['typolinkLinkAccessRestrictedPages'] ?? false;
            $SAVED_link_to_restricted_pages_additional_params = $tsfe->config['config']['typolinkLinkAccessRestrictedPages_addParams'] ?? null;
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages'] = $this->mconf['showAccessRestrictedPages'];
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages_addParams'] = $this->mconf['showAccessRestrictedPages.']['addParams'] ?? '';
        }
        // If a user script returned the value overrideId in the menu array we use that as page id
        if (($this->mconf['overrideId'] ?? false) || ($this->menuArr[$key]['overrideId'] ?? false)) {
            $overrideId = (int)($this->mconf['overrideId'] ?: $this->menuArr[$key]['overrideId']);
            $overrideId = $overrideId > 0 ? $overrideId : null;
            // Clear MP parameters since ID was changed.
            $MP_params = '';
        } else {
            $overrideId = null;
            // Mount points:
            $MP_params = $MP_var ? '&MP=' . rawurlencode($MP_var) : '';
        }
        // Setting main target
        $mainTarget = $altTarget ?: (string)$this->parent_cObj->stdWrapValue('target', $this->mconf ?? []);
        // Creating link:
        $addParams = ($this->mconf['addParams'] ?? '') . $MP_params;
        if (($this->mconf['collapse'] ?? false) && $this->isActive($this->menuArr[$key] ?? [], $this->getMPvar($key))) {
            $thePage = $this->sys_page->getPage($this->menuArr[$key]['pid']);
            $LD = $this->menuTypoLink($thePage, $mainTarget, $addParams, $typeOverride, $overrideId);
        } else {
            $addParams .= ($this->I['val']['additionalParams'] ?? '');
            $LD = $this->menuTypoLink($this->menuArr[$key], $mainTarget, $addParams, $typeOverride, $overrideId);
        }
        // Overriding URL / Target if set to do so:
        if ($this->menuArr[$key]['_OVERRIDE_HREF'] ?? false) {
            $LD['totalURL'] = $this->menuArr[$key]['_OVERRIDE_HREF'];
            if ($this->menuArr[$key]['_OVERRIDE_TARGET']) {
                $LD['target'] = $this->menuArr[$key]['_OVERRIDE_TARGET'];
            }
        }
        // opens URL in new window
        // @deprecated will be removed in TYPO3 v12.0.
        if ($this->mconf['JSWindow'] ?? false) {
            trigger_error('Calling HMENU with option JSwindow will stop working in TYPO3 v12.0. Use a external JavaScript file with proper event listeners to open a custom window.', E_USER_DEPRECATED);
            $conf = $this->mconf['JSWindow.'];
            $url = $LD['totalURL'];
            $LD['totalURL'] = '#';
            $attrs['data-window-url'] = $tsfe->baseUrlWrap($url);
            $attrs['data-window-target'] = $conf['newWindow'] ? md5($url) : 'theNewPage';
            if (!empty($conf['params'])) {
                $attrs['data-window-features'] = $conf['params'];
            }
            $this->addDefaultFrontendJavaScript();
        }
        // look for type and popup
        // following settings are valid in field target:
        // 230								will add type=230 to the link
        // 230 500x600						will add type=230 to the link and open in popup window with 500x600 pixels
        // 230 _blank						will add type=230 to the link and open with target "_blank"
        // 230x450:resizable=0,location=1	will open in popup window with 500x600 pixels with settings "resizable=0,location=1"
        $matches = [];
        $targetIsType = ($LD['target'] ?? false) && MathUtility::canBeInterpretedAsInteger($LD['target']) ? (int)$LD['target'] : false;
        if (preg_match('/([0-9]+[\\s])?(([0-9]+)x([0-9]+))?(:.+)?/s', ($LD['target'] ?? ''), $matches) || $targetIsType) {
            // has type?
            if ((int)($matches[1] ?? 0) || $targetIsType) {
                $LD['totalURL'] .= (!str_contains($LD['totalURL'], '?') ? '?' : '&') . 'type=' . ($targetIsType ?: (int)$matches[1]);
                $LD['target'] = $targetIsType ? '' : trim(substr($LD['target'], strlen($matches[1]) + 1));
            }
            // Open in popup window?
            // @deprecated will be removed in TYPO3 v12.0.
            if (($matches[3] ?? false) && ($matches[4] ?? false)) {
                trigger_error('Calling HMENU with a special target to open a link in a window will be removed in TYPO3 v12.0. Use a external JavaScript file with proper event listeners to open a custom window.', E_USER_DEPRECATED);
                $attrs['data-window-url'] = $tsfe->baseUrlWrap($LD['totalURL']);
                $attrs['data-window-target'] = $LD['target'] ?? 'FEopenLink';
                $attrs['data-window-features'] = 'width=' . $matches[3] . ',height=' . $matches[4] . ($matches[5] ? ',' . substr($matches[5], 1) : '');
                $LD['target'] = '';
                $this->addDefaultFrontendJavaScript();
            }
        }
        // Added this check: What it does is to enter the baseUrl (if set, which it should for "realurl" based sites)
        // as URL if the calculated value is empty. The problem is that no link is generated with a blank URL
        // and blank URLs might appear when the realurl encoding is used and a link to the frontpage is generated.
        $attrs['HREF'] = (string)$LD['totalURL'] !== '' ? $LD['totalURL'] : $tsfe->baseUrl;
        $attrs['TARGET'] = $LD['target'] ?? '';
        $runtimeCache->set($cacheId, $attrs);

        // End showAccessRestrictedPages
        if ($this->mconf['showAccessRestrictedPages'] ?? false) {
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages'] = $SAVED_link_to_restricted_pages;
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages_addParams'] = $SAVED_link_to_restricted_pages_additional_params;
        }

        return $attrs;
    }

    /**
     * Determines original shortcut destination in page overlays.
     *
     * Since the pages records used for menu rendering are overlaid by default,
     * the original 'shortcut' value is lost, if a translation did not define one.
     *
     * @param array $page
     * @return array
     */
    protected function determineOriginalShortcutPage(array $page)
    {
        // Check if modification is required
        if (
            $this->getCurrentLanguageAspect()->getId() > 0
            && empty($page['shortcut'])
            && !empty($page['uid'])
            && !empty($page['_PAGES_OVERLAY'])
            && !empty($page['_PAGES_OVERLAY_UID'])
        ) {
            // Using raw record since the record was overlaid and is correct already:
            $originalPage = $this->sys_page->getRawRecord('pages', $page['uid']);

            if ($originalPage['shortcut_mode'] === $page['shortcut_mode'] && !empty($originalPage['shortcut'])) {
                $page['shortcut'] = $originalPage['shortcut'];
            }
        }

        return $page;
    }

    /**
     * Creates a submenu level to the current level - if configured for.
     *
     * @param int $uid Page id of the current page for which a submenu MAY be produced (if conditions are met)
     * @param string $objSuffix Object prefix, see ->start()
     * @return string HTML content of the submenu
     */
    protected function subMenu(int $uid, string $objSuffix)
    {
        // Setting alternative menu item array if _SUB_MENU has been defined in the current ->menuArr
        $altArray = '';
        if (is_array($this->menuArr[$this->I['key']]['_SUB_MENU'] ?? null) && !empty($this->menuArr[$this->I['key']]['_SUB_MENU'])) {
            $altArray = $this->menuArr[$this->I['key']]['_SUB_MENU'];
        }
        // Make submenu if the page is the next active
        $menuType = $this->conf[($this->menuNumber + 1) . $objSuffix] ?? '';
        // stdWrap for expAll
        $this->mconf['expAll'] = $this->parent_cObj->stdWrapValue('expAll', $this->mconf ?? []);
        if (($this->mconf['expAll'] || $this->isNext($uid, $this->getMPvar($this->I['key'])) || is_array($altArray)) && !($this->mconf['sectionIndex'] ?? false)) {
            try {
                $menuObjectFactory = GeneralUtility::makeInstance(MenuContentObjectFactory::class);
                /** @var AbstractMenuContentObject $submenu */
                $submenu = $menuObjectFactory->getMenuObjectByType($menuType);
                $submenu->entryLevel = $this->entryLevel + 1;
                $submenu->rL_uidRegister = $this->rL_uidRegister;
                $submenu->MP_array = $this->MP_array;
                if ($this->menuArr[$this->I['key']]['_MP_PARAM'] ?? false) {
                    $submenu->MP_array[] = $this->menuArr[$this->I['key']]['_MP_PARAM'];
                }
                // Especially scripts that build the submenu needs the parent data
                $submenu->parent_cObj = $this->parent_cObj;
                $submenu->setParentMenu($this->menuArr, $this->I['key']);
                // Setting alternativeMenuTempArray (will be effective only if an array and not empty)
                if (is_array($altArray) && !empty($altArray)) {
                    $submenu->alternativeMenuTempArray = $altArray;
                }
                if ($submenu->start($this->tmpl, $this->sys_page, $uid, $this->conf, $this->menuNumber + 1, $objSuffix)) {
                    $submenu->makeMenu();
                    // Memorize the current menu item count
                    $tsfe = $this->getTypoScriptFrontendController();
                    $tempCountMenuObj = $tsfe->register['count_MENUOBJ'];
                    // Reset the menu item count for the submenu
                    $tsfe->register['count_MENUOBJ'] = 0;
                    $content = $submenu->writeMenu();
                    // Restore the item count now that the submenu has been handled
                    $tsfe->register['count_MENUOBJ'] = $tempCountMenuObj;
                    $tsfe->register['count_menuItems'] = count($this->menuArr);
                    return $content;
                }
            } catch (NoSuchMenuTypeException $e) {
            }
        }
        return '';
    }

    /**
     * Returns TRUE if the page with UID $uid is the NEXT page in root line (which means a submenu should be drawn)
     *
     * @param int $uid Page uid to evaluate.
     * @param string $MPvar MPvar for the current position of item.
     * @return bool TRUE if page with $uid is active
     * @see subMenu()
     */
    protected function isNext($uid, $MPvar)
    {
        // Check for always active PIDs:
        if (in_array((int)$uid, $this->alwaysActivePIDlist, true)) {
            return true;
        }
        $testUid = $uid . ($MPvar ? ':' . $MPvar : '');
        if ($uid && $testUid == $this->nextActive) {
            return true;
        }
        return false;
    }

    /**
     * Returns TRUE if the given page is active (in the current rootline)
     *
     * @param array $page Page record to evaluate.
     * @param string $MPvar MPvar for the current position of item.
     * @return bool TRUE if $page is active
     */
    protected function isActive(array $page, $MPvar)
    {
        // Check for always active PIDs
        $uid = (int)($page['uid'] ?? 0);
        if (in_array($uid, $this->alwaysActivePIDlist, true)) {
            return true;
        }
        $testUid = $uid . ($MPvar ? ':' . $MPvar : '');
        if ($uid && in_array('ITEM:' . $testUid, $this->rL_uidRegister, true)) {
            return true;
        }
        try {
            $page = $this->sys_page->resolveShortcutPage($page);
            $shortcutPage = (int)($page['_SHORTCUT_ORIGINAL_PAGE_UID'] ?? 0);
            if ($shortcutPage) {
                if (in_array($shortcutPage, $this->alwaysActivePIDlist, true)) {
                    return true;
                }
                $testUid = $shortcutPage . ($MPvar ? ':' . $MPvar : '');
                if (in_array('ITEM:' . $testUid, $this->rL_uidRegister, true)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Shortcut could not be resolved
            return false;
        }
        return false;
    }

    /**
     * Returns TRUE if the page is the CURRENT page (equals $this->getTypoScriptFrontendController()->id)
     *
     * @param array $page Page record to evaluate.
     * @param string $MPvar MPvar for the current position of item.
     * @return bool TRUE if resolved page ID = $this->getTypoScriptFrontendController()->id
     */
    protected function isCurrent(array $page, $MPvar)
    {
        $testUid = ($page['uid'] ?? 0) . ($MPvar ? ':' . $MPvar : '');
        if (($page['uid'] ?? 0) && end($this->rL_uidRegister) === 'ITEM:' . $testUid) {
            return true;
        }
        try {
            $page = $this->sys_page->resolveShortcutPage($page);
            $shortcutPage = (int)($page['_SHORTCUT_ORIGINAL_PAGE_UID'] ?? 0);
            if ($shortcutPage) {
                $testUid = $shortcutPage . ($MPvar ? ':' . $MPvar : '');
                if (end($this->rL_uidRegister) === 'ITEM:' . $testUid) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Shortcut could not be resolved
            return false;
        }
        return false;
    }

    /**
     * Returns TRUE if there is a submenu with items for the page id, $uid
     * Used by the item states "IFSUB", "ACTIFSUB" and "CURIFSUB" to check if there is a submenu
     *
     * @param int $uid Page uid for which to search for a submenu
     * @return bool Returns TRUE if there was a submenu with items found
     */
    protected function isSubMenu($uid)
    {
        $cacheId = 'menucontentobject-is-submenu-decision-' . $uid . '-' . (int)($this->conf['includeNotInMenu'] ?? 0);
        $runtimeCache = $this->getRuntimeCache();
        $cachedDecision = $runtimeCache->get($cacheId);
        if (isset($cachedDecision['result'])) {
            return $cachedDecision['result'];
        }
        // Looking for a mount-pid for this UID since if that
        // exists we should look for a subpages THERE and not in the input $uid;
        $mount_info = $this->sys_page->getMountPointInfo($uid);
        if (is_array($mount_info)) {
            $uid = $mount_info['mount_pid'];
        }
        $recs = $this->sys_page->getMenu($uid, 'uid,pid,doktype,mount_pid,mount_pid_ol,nav_hide,shortcut,shortcut_mode,l18n_cfg');
        $hasSubPages = false;
        $bannedUids = $this->getBannedUids();
        $languageId = $this->getCurrentLanguageAspect()->getId();
        foreach ($recs as $theRec) {
            // no valid subpage if the document type is excluded from the menu
            if (in_array((int)($theRec['doktype'] ?? 0), $this->excludedDoktypes, true)) {
                continue;
            }
            // No valid subpage if the page is hidden inside menus and
            // it wasn't forced to show such entries
            if (isset($theRec['nav_hide']) && $theRec['nav_hide']
                && (!isset($this->conf['includeNotInMenu']) || !$this->conf['includeNotInMenu'])
            ) {
                continue;
            }
            // No valid subpage if the default language should be shown and the page settings
            // are excluding the visibility of the default language
            $pageTranslationVisibility = new PageTranslationVisibility((int)($theRec['l18n_cfg'] ?? 0));
            if (!$languageId && $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage()) {
                continue;
            }
            // No valid subpage if the alternative language should be shown and the page settings
            // are requiring a valid overlay but it doesn't exists
            if ($pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists() && $languageId > 0 && !($theRec['_PAGES_OVERLAY'] ?? false)) {
                continue;
            }
            // No valid subpage if the subpage is banned by excludeUidList (check for default language pages as well)
            if (($theRec['_PAGES_OVERLAY_UID'] ?? 0) > 0 && in_array((int)($theRec['_PAGES_OVERLAY_UID'] ?? 0), $bannedUids, true)) {
                continue;
            }
            if (in_array((int)($theRec['uid'] ?? 0), $bannedUids, true)) {
                continue;
            }
            $hasSubPages = true;
            break;
        }
        $runtimeCache->set($cacheId, ['result' => $hasSubPages]);
        return $hasSubPages;
    }

    /**
     * Used by processItemStates() to evaluate if a menu item (identified by $key) is in a certain state.
     *
     * @param string $kind The item state to evaluate (SPC, IFSUB, ACT etc...)
     * @param int $key Key pointing to menu item from ->menuArr
     * @return bool Returns TRUE if state matches
     * @see processItemStates()
     */
    protected function isItemState($kind, $key)
    {
        $natVal = false;
        // If any value is set for ITEM_STATE the normal evaluation is discarded
        if ($this->menuArr[$key]['ITEM_STATE'] ?? false) {
            if ((string)$this->menuArr[$key]['ITEM_STATE'] === (string)$kind) {
                $natVal = true;
            }
        } else {
            switch ($kind) {
                case 'SPC':
                    $natVal = (bool)$this->menuArr[$key]['isSpacer'];
                    break;
                case 'IFSUB':
                    $natVal = $this->isSubMenu($this->menuArr[$key]['uid'] ?? 0);
                    break;
                case 'ACT':
                    $natVal = $this->isActive(($this->menuArr[$key] ?? []), $this->getMPvar($key));
                    break;
                case 'ACTIFSUB':
                    $natVal = $this->isActive(($this->menuArr[$key] ?? []), $this->getMPvar($key)) && $this->isSubMenu($this->menuArr[$key]['uid']);
                    break;
                case 'CUR':
                    $natVal = $this->isCurrent(($this->menuArr[$key] ?? []), $this->getMPvar($key));
                    break;
                case 'CURIFSUB':
                    $natVal = $this->isCurrent(($this->menuArr[$key] ?? []), $this->getMPvar($key)) && $this->isSubMenu($this->menuArr[$key]['uid']);
                    break;
                case 'USR':
                    $natVal = (bool)$this->menuArr[$key]['fe_group'];
                    break;
            }
        }
        return $natVal;
    }

    /**
     * Creates an access-key for a TMENU menu item based on the menu item titles first letter
     *
     * @param string $title Menu item title.
     * @return array Returns an array with keys "code" ("accesskey" attribute for the img-tag) and "alt" (text-addition to the "alt" attribute) if an access key was defined. Otherwise array was empty
     */
    protected function accessKey($title)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        // The global array ACCESSKEY is used to globally control if letters are already used!!
        $result = [];
        $title = trim(strip_tags($title));
        $titleLen = strlen($title);
        for ($a = 0; $a < $titleLen; $a++) {
            $key = strtoupper(substr($title, $a, 1));
            if (preg_match('/[A-Z]/', $key) && !isset($tsfe->accessKey[$key])) {
                $tsfe->accessKey[$key] = true;
                $result['code'] = ' accesskey="' . $key . '"';
                $result['alt'] = ' (ALT+' . $key . ')';
                $result['key'] = $key;
                break;
            }
        }
        return $result;
    }

    /**
     * Calls a user function for processing of internal data.
     * Used for the properties "IProcFunc" and "itemArrayProcFunc"
     *
     * @param string $mConfKey Key pointing for the property in the current ->mconf array holding possibly parameters to pass along to the function/method. Currently the keys used are "IProcFunc" and "itemArrayProcFunc".
     * @param mixed $passVar A variable to pass to the user function and which should be returned again from the user function. The idea is that the user function modifies this variable according to what you want to achieve and then returns it. For "itemArrayProcFunc" this variable is $this->menuArr, for "IProcFunc" it is $this->I
     * @return mixed The processed $passVar
     */
    protected function userProcess($mConfKey, $passVar)
    {
        if ($this->mconf[$mConfKey]) {
            $funcConf = (array)($this->mconf[$mConfKey . '.'] ?? []);
            $funcConf['parentObj'] = $this;
            $passVar = $this->parent_cObj->callUserFunction($this->mconf[$mConfKey], $funcConf, $passVar);
        }
        return $passVar;
    }

    /**
     * Creates the <A> tag parts for the current item (in $this->I, [A1] and [A2]) based on other information in this array (like $this->I['linkHREF'])
     */
    protected function setATagParts()
    {
        $params = trim($this->I['val']['ATagParams']) . ($this->I['accessKey']['code'] ?? '');
        $params = $params !== '' ? ' ' . $params : '';
        $this->I['A1'] = '<a ' . GeneralUtility::implodeAttributes($this->I['linkHREF'], true) . $params . '>';
        $this->I['A2'] = '</a>';
    }

    /**
     * Returns the title for the navigation
     *
     * @param string $title The current page title
     * @param string $nav_title The current value of the navigation title
     * @return string Returns the navigation title if it is NOT blank, otherwise the page title.
     */
    protected function getPageTitle($title, $nav_title)
    {
        return trim($nav_title) !== '' ? $nav_title : $title;
    }

    /**
     * Return MPvar string for entry $key in ->menuArr
     *
     * @param int $key Pointer to element in ->menuArr
     * @return string MP vars for element.
     * @see link()
     */
    protected function getMPvar($key)
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids']) {
            $localMP_array = $this->MP_array;
            // NOTICE: "_MP_PARAM" is allowed to be a commalist of PID pairs!
            if ($this->menuArr[$key]['_MP_PARAM'] ?? false) {
                $localMP_array[] = $this->menuArr[$key]['_MP_PARAM'];
            }
            return !empty($localMP_array) ? implode(',', $localMP_array) : '';
        }
        return '';
    }

    /**
     * Returns where clause part to exclude 'not in menu' pages
     *
     * @return string where clause part.
     */
    protected function getDoktypeExcludeWhere()
    {
        return !empty($this->excludedDoktypes) ? ' AND pages.doktype NOT IN (' . implode(',', $this->excludedDoktypes) . ')' : '';
    }

    /**
     * Returns an array of banned UIDs (from excludeUidList)
     *
     * @return array Array of banned UIDs
     */
    protected function getBannedUids()
    {
        $excludeUidList = (string)$this->parent_cObj->stdWrapValue('excludeUidList', $this->conf ?? []);
        if (!trim($excludeUidList)) {
            return [];
        }

        $banUidList = str_replace('current', (string)($this->getTypoScriptFrontendController()->page['uid'] ?? ''), $excludeUidList);
        return GeneralUtility::intExplode(',', $banUidList);
    }

    /**
     * Calls typolink to create menu item links.
     *
     * @param array $page Page record (uid points where to link to)
     * @param string $oTarget Target frame/window
     * @param string $addParams Parameters to add to URL
     * @param int|string $typeOverride "type" value, empty string means "not set"
     * @param int|null $overridePageId link to this page instead of the $page[uid] value
     * @return array See linkData
     */
    protected function menuTypoLink($page, $oTarget, $addParams, $typeOverride, ?int $overridePageId = null)
    {
        $conf = [
            'parameter' => $overridePageId ?? $page['uid'] ?? 0,
        ];
        if (MathUtility::canBeInterpretedAsInteger($typeOverride)) {
            $conf['parameter'] .= ',' . (int)$typeOverride;
        }
        if ($addParams) {
            $conf['additionalParams'] = $addParams;
        }
        // Used only for special=language
        if ($page['_ADD_GETVARS'] ?? false) {
            $conf['addQueryString'] = 1;
            $conf['addQueryString.'] = $this->conf['addQueryString.'] ?? [];
        }

        // Ensure that the typolink gets an info which language was actually requested. The $page record could be the record
        // from page translation language=1 as fallback but page translation language=2 was requested. Search for
        // "_PAGES_OVERLAY_REQUESTEDLANGUAGE" for more details
        if (isset($page['_PAGES_OVERLAY_REQUESTEDLANGUAGE'])) {
            $conf['language'] = $page['_PAGES_OVERLAY_REQUESTEDLANGUAGE'];
        }
        if ($oTarget) {
            $conf['target'] = $oTarget;
        }
        if ($page['sectionIndex_uid'] ?? false) {
            $conf['section'] = $page['sectionIndex_uid'];
        }
        $this->parent_cObj->typoLink('|', $conf);
        $LD = $this->parent_cObj->lastTypoLinkLD;
        $LD['totalURL'] = $this->parent_cObj->lastTypoLinkUrl;
        return $LD;
    }

    /**
     * Generates a list of content objects with sectionIndex enabled
     * available on a specific page
     *
     * Used for menus with sectionIndex enabled
     *
     * @param string $altSortField Alternative sorting field
     * @param int $pid The page id to search for sections
     * @throws \UnexpectedValueException if the query to fetch the content elements unexpectedly fails
     * @return array
     */
    protected function sectionIndex($altSortField, $pid = null)
    {
        $pid = (int)($pid ?: $this->id);
        $basePageRow = $this->sys_page->getPage($pid);
        if (!is_array($basePageRow)) {
            return [];
        }
        $useColPos = (int)$this->parent_cObj->stdWrapValue('useColPos', $this->mconf['sectionIndex.'] ?? [], 0);
        $selectSetup = [
            'pidInList' => $pid,
            'orderBy' => $altSortField,
            'languageField' => 'sys_language_uid',
            'where' => '',
        ];

        if ($useColPos >= 0) {
            $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tt_content')
                ->getExpressionBuilder();
            $selectSetup['where'] = $expressionBuilder->eq('colPos', $useColPos);
        }

        if ($basePageRow['content_from_pid'] ?? false) {
            // If the page is configured to show content from a referenced page the sectionIndex contains only contents of
            // the referenced page
            $selectSetup['pidInList'] = $basePageRow['content_from_pid'];
        }
        $statement = $this->parent_cObj->exec_getQuery('tt_content', $selectSetup);
        if (!$statement) {
            $message = 'SectionIndex: Query to fetch the content elements failed!';
            throw new \UnexpectedValueException($message, 1337334849);
        }
        $result = [];
        while ($row = $statement->fetchAssociative()) {
            $this->sys_page->versionOL('tt_content', $row);
            if ($this->getCurrentLanguageAspect()->doOverlays() && $basePageRow['_PAGES_OVERLAY_LANGUAGE']) {
                $languageAspect = new LanguageAspect($basePageRow['_PAGES_OVERLAY_LANGUAGE'], $basePageRow['_PAGES_OVERLAY_LANGUAGE'], $this->getCurrentLanguageAspect()->getOverlayType());
                $row = $this->sys_page->getRecordOverlay(
                    'tt_content',
                    $row,
                    $languageAspect
                );
            }
            if ($this->mconf['sectionIndex.']['type'] !== 'all') {
                $doIncludeInSectionIndex = $row['sectionIndex'] >= 1;
                $doHeaderCheck = $this->mconf['sectionIndex.']['type'] === 'header';
                $isValidHeader = ((int)$row['header_layout'] !== 100 || !empty($this->mconf['sectionIndex.']['includeHiddenHeaders'])) && trim($row['header']) !== '';
                if (!$doIncludeInSectionIndex || $doHeaderCheck && !$isValidHeader) {
                    continue;
                }
            }
            if (is_array($row)) {
                $uid = $row['uid'] ?? null;
                $result[$uid] = $basePageRow;
                $result[$uid]['title'] = $row['header'];
                $result[$uid]['nav_title'] = $row['header'];
                // Prevent false exclusion in filterMenuPages, thus: Always show tt_content records
                $result[$uid]['nav_hide'] = 0;
                $result[$uid]['subtitle'] = $row['subheader'] ?? '';
                $result[$uid]['starttime'] = $row['starttime'] ?? '';
                $result[$uid]['endtime'] = $row['endtime'] ?? '';
                $result[$uid]['fe_group'] = $row['fe_group'] ?? '';
                $result[$uid]['media'] = $row['media'] ?? '';
                $result[$uid]['header_layout'] = $row['header_layout'] ?? '';
                $result[$uid]['bodytext'] = $row['bodytext'] ?? '';
                $result[$uid]['image'] = $row['image'] ?? '';
                $result[$uid]['sectionIndex_uid'] = $uid;
            }
        }

        return $result;
    }

    /**
     * Returns the sys_page object
     *
     * @return PageRepository
     */
    public function getSysPage()
    {
        return $this->sys_page;
    }

    /**
     * Returns the parent content object
     *
     * @return ContentObjectRenderer
     */
    public function getParentContentObject()
    {
        return $this->parent_cObj;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    protected function getCurrentLanguageAspect(): LanguageAspect
    {
        return GeneralUtility::makeInstance(Context::class)->getAspect('language');
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected function getCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('hash');
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected function getRuntimeCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }

    /**
     * Returns the currently configured "site" if a site is configured (= resolved) in the current request.
     *
     * @return Site
     */
    protected function getCurrentSite(): Site
    {
        return $this->getTypoScriptFrontendController()->getSite();
    }

    /**
     * Set the parentMenuArr and key to provide the parentMenu information to the
     * subMenu, special fur IProcFunc and itemArrayProcFunc user functions.
     *
     * @param array $menuArr
     * @param int $menuItemKey
     * @internal
     */
    public function setParentMenu(array $menuArr, $menuItemKey)
    {
        // check if menuArr is a valid array and that menuItemKey matches an existing menuItem in menuArr
        if (is_array($menuArr)
            && (is_int($menuItemKey) && $menuItemKey >= 0 && isset($menuArr[$menuItemKey]))
        ) {
            $this->parentMenuArr = $menuArr;
            $this->parentMenuArrItemKey = $menuItemKey;
        }
    }

    /**
     * Check if there is a valid parentMenuArr.
     *
     * @return bool
     */
    protected function hasParentMenuArr()
    {
        return
            $this->menuNumber > 1
            && is_array($this->parentMenuArr)
            && !empty($this->parentMenuArr)
        ;
    }

    /**
     * Check if we have a parentMenuArrItemKey
     */
    protected function hasParentMenuItemKey()
    {
        return $this->parentMenuArrItemKey !== null;
    }

    /**
     * Check if the the parentMenuItem exists
     */
    protected function hasParentMenuItem()
    {
        return
            $this->hasParentMenuArr()
            && $this->hasParentMenuItemKey()
            && isset($this->getParentMenuArr()[$this->parentMenuArrItemKey])
        ;
    }

    /**
     * Get the parentMenuArr, if this is subMenu.
     *
     * @return array
     */
    public function getParentMenuArr()
    {
        return $this->hasParentMenuArr() ? $this->parentMenuArr : [];
    }

    /**
     * Get the parentMenuItem from the parentMenuArr, if this is a subMenu
     *
     * @return array|null
     */
    public function getParentMenuItem()
    {
        // check if we have a parentMenuItem and if it is an array
        if ($this->hasParentMenuItem()
            && is_array($this->getParentMenuArr()[$this->parentMenuArrItemKey])
        ) {
            return $this->getParentMenuArr()[$this->parentMenuArrItemKey];
        }

        return null;
    }

    /**
     * @param string $mode
     * @return string
     */
    private function getMode(string $mode = ''): string
    {
        switch ($mode) {
            case 'starttime':
                $sortField = 'starttime';
                break;
            case 'lastUpdated':
            case 'manual':
                $sortField = 'lastUpdated';
                break;
            case 'tstamp':
                $sortField = 'tstamp';
                break;
            case 'crdate':
                $sortField = 'crdate';
                break;
            default:
                $sortField = 'SYS_LASTCHANGED';
        }

        return $sortField;
    }
}
