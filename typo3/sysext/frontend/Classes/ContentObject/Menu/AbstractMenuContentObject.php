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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use TYPO3\CMS\Frontend\ContentObject\Menu\Exception\NoSuchMenuTypeException;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\FilterMenuItemsEvent;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

/**
 * Generating navigation/menus from TypoScript
 *
 * The HMENU content object uses this (or more precisely one of the extension classes).
 * Among others the class generates an array of menu items. Thereafter functions from the subclasses are called.
 * The class is always used through extension classes like TextMenuContentObject.
 */
abstract class AbstractMenuContentObject
{
    /**
     * tells you which menu number this is. This is important when getting data from the setup
     */
    protected int $menuNumber = 1;

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
     * @deprecated since v12: Remove property and usages in v13 when TemplateService is removed
     */
    protected TemplateService|null $tmpl;

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
     * @var string Unused
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

    protected ServerRequestInterface $request;

    /**
     * Can be set to contain menu item arrays for sub-levels.
     */
    protected array $alternativeMenuTempArray = [];

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

    protected bool $disableGroupAccessCheck = false;

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
     * @param TemplateService|null $_ Obsolete argument
     * @param PageRepository $sys_page The $this->getTypoScriptFrontendController()->sys_page object
     * @param int|string $id A starting point page id. This should probably be blank since the 'entryLevel' value will be used then.
     * @param array $conf The TypoScript configuration for the HMENU cObject
     * @param int $menuNumber Menu number; 1,2,3. Should probably be 1
     * @param string $objSuffix Submenu Object suffix. This offers submenus a way to use alternative configuration for specific positions in the menu; By default "1 = TMENU" would use "1." for the TMENU configuration, but if this string is set to eg. "a" then "1a." would be used for configuration instead (while "1 = " is still used for the overall object definition of "TMENU")
     * @return bool Returns TRUE on success
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::HMENU()
     */
    public function start($_, $sys_page, $id, $conf, int $menuNumber, $objSuffix = '', ?ServerRequestInterface $request = null)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $this->conf = $conf;
        $this->menuNumber = $menuNumber;
        $this->mconf = $conf[$this->menuNumber . $objSuffix . '.'];
        $this->request = $request;
        // Sets the internal vars. $tmpl MUST be the template-object. $sys_page MUST be the PageRepository object
        if ($this->conf[$this->menuNumber . $objSuffix] && is_object($sys_page)) {
            // @deprecated since v12, will be removed in v13: Remove assignment and property when TemplateService is removed
            $this->tmpl = $_;
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
                $this->excludedDoktypes = GeneralUtility::intExplode(',', (string)($this->conf['excludeDoktypes']));
            }
            // EntryLevel
            $this->entryLevel = $this->parent_cObj->getKey(
                $this->parent_cObj->stdWrapValue('entryLevel', $this->conf ?? []),
                $tsfe->config['rootLine'] ?? []
            );
            // Set parent page: If $id not stated with start() then the base-id will be found from rootLine[$this->entryLevel]
            // Called as the next level in a menu. It is assumed that $this->MP_array is set from parent menu.
            if ($id) {
                $this->id = (int)$id;
            } else {
                // This is a BRAND NEW menu, first level. So we take ID from rootline and also find MP_array (mount points)
                $this->id = (int)($tsfe->config['rootLine'][$this->entryLevel]['uid'] ?? 0);

                // Traverse rootline to build MP_array of pages BEFORE the entryLevel
                // (MP var for ->id is picked up in the next part of the code...)
                foreach (($tsfe->config['rootLine'] ?? []) as $entryLevel => $levelRec) {
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
                foreach (($tsfe->config['rootLine'] ?? []) as $v_rl) {
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
                    $value = $tsfe->id;
                }
                $directoryLevel = $this->getRootlineLevel($tsfe->config['rootLine'], (string)$value);
            }
            // Setting "nextActive": This is the page uid + MPvar of the NEXT page in rootline. Used to expand the menu if we are in the right branch of the tree
            // Notice: The automatic expansion of a menu is designed to work only when no "special" modes (except "directory") are used.
            $startLevel = $directoryLevel ?: $this->entryLevel;
            $currentLevel = $startLevel + $this->menuNumber;
            if (is_array($tsfe->config['rootLine'][$currentLevel] ?? null)) {
                $nextMParray = $this->MP_array;
                if (empty($nextMParray) && !($tsfe->config['rootLine'][$currentLevel]['_MOUNT_OL'] ?? false) && $currentLevel > 0) {
                    // Make sure to slide-down any mount point information (_MP_PARAM) to children records in the rootline
                    // otherwise automatic expansion will not work
                    $parentRecord = $tsfe->config['rootLine'][$currentLevel - 1] ?? [];
                    if (isset($parentRecord['_MP_PARAM'])) {
                        $nextMParray[] = $parentRecord['_MP_PARAM'];
                    }
                }
                // In overlay mode, add next level MPvars as well:
                if ($tsfe->config['rootLine'][$currentLevel]['_MOUNT_OL'] ?? false) {
                    $nextMParray[] = $tsfe->config['rootLine'][$currentLevel]['_MP_PARAM'] ?? [];
                }
                $this->nextActive = ($tsfe->config['rootLine'][$currentLevel]['uid']  ?? 0) .
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

        $frontendController = $this->getTypoScriptFrontendController();
        // Initializing showAccessRestrictedPages
        $SAVED_where_groupAccess = '';
        if ($this->mconf['showAccessRestrictedPages'] ?? false) {
            $this->disableGroupAccessCheck = true;
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
        $this->menuArr = [];
        foreach ($menuItems as &$data) {
            $data['isSpacer'] = ($data['isSpacer'] ?? false) || (int)($data['doktype'] ?? 0) === PageRepository::DOKTYPE_SPACER || ($data['ITEM_STATE'] ?? '') === 'SPC';
        }
        $menuItems = $this->removeInaccessiblePages($menuItems);
        // Fill in the menuArr with elements that should go into the menu
        foreach ($menuItems as $menuItem) {
            $c_b++;
            // If the beginning item has been reached, add the items.
            if ($begin <= $c_b) {
                $this->menuArr[$c] = $menuItem;
                $c++;
                if ($maxItems && $c >= $maxItems) {
                    break;
                }
            }
        }
        // Fill in fake items, if min-items is set.
        if ($minItems) {
            while ($c < $minItems) {
                $this->menuArr[$c] = [
                    'title' => '...',
                    'uid' => $frontendController->id,
                ];
                $c++;
            }
        }
        //	Passing the menuArr through a user defined function:
        if ($this->mconf['itemArrayProcFunc'] ?? false) {
            $this->menuArr = $this->userProcess('itemArrayProcFunc', $this->menuArr);
        }
        // Setting number of menu items
        $frontendController->register['count_menuItems'] = count($this->menuArr);
        $this->generate();
        // End showAccessRestrictedPages
        if ($this->mconf['showAccessRestrictedPages'] ?? false) {
            $this->disableGroupAccessCheck = false;
        }
    }

    /**
     * Calls processItemStates() so that the common configuration for the menu items are resolved into individual configuration per item.
     * Sets the result for the new "normal state" in $this->result
     *
     * @see AbstractMenuContentObject::processItemStates()
     */
    public function generate()
    {
        $itemConfiguration = [];
        $splitCount = count($this->menuArr);
        if ($splitCount) {
            $itemConfiguration = $this->processItemStates($splitCount);
        }
        $this->result = $itemConfiguration;
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
     */
    protected function removeInaccessiblePages(array $pages): array
    {
        $banned = $this->getBannedUids();
        $filteredPages = [];
        foreach ($pages as $aPage) {
            $isSpacerPage = ((int)($aPage['doktype'] ?? 0) === PageRepository::DOKTYPE_SPACER) || ($aPage['isSpacer'] ?? false);
            if ($this->filterMenuPages($aPage, $banned, $isSpacerPage)) {
                $filteredPages[] = $aPage;
            }
        }
        $event = new FilterMenuItemsEvent(
            $pages,
            $filteredPages,
            $this->mconf,
            $this->conf,
            $banned,
            $this->excludedDoktypes,
            $this->getCurrentSite(),
            $this->getTypoScriptFrontendController()->getContext(),
            $this->getTypoScriptFrontendController()->page
        );
        $event = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
        return $event->getFilteredMenuItems();
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
            $subMenuDecision = $this->getRuntimeCache()->get($this->getCacheIdentifierForSubMenuDecision($this->id));
            if (!isset($subMenuDecision['result']) || $subMenuDecision['result'] === true) {
                $menuItems = $this->sys_page->getMenu($this->id, '*', $alternativeSortingField, $additionalWhere, true, $this->disableGroupAccessCheck);
            }
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
        $currentPageWithNoOverlay = ($tsfe->page['_TRANSLATION_SOURCE'] ?? null)?->toArray(true) ?? $tsfe->page;

        $languages = $this->getCurrentSite()->getLanguages();
        if ($specialValue === 'auto') {
            $languageItems = array_keys($languages);
        } else {
            $languageItems = GeneralUtility::intExplode(',', $specialValue);
        }

        $tsfe->register['languages_HMENU'] = implode(',', $languageItems);

        $currentLanguageId = $this->getCurrentLanguageAspect()->getId();

        // @todo Fetch all language overlays in a single query
        foreach ($languageItems as $sUid) {
            // Find overlay record:
            if ($sUid) {
                $languageAspect = LanguageAspectFactory::createFromSiteLanguage($languages[$sUid]);
                $pageRepository = $this->buildPageRepository($languageAspect);
                $lRecs = $pageRepository->getPageOverlay($currentPageWithNoOverlay, $languageAspect);
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
     * Builds PageRepository instance without depending on global context, e.g.
     * not automatically overlaying records based on current request language.
     */
    protected function buildPageRepository(LanguageAspect $languageAspect = null): PageRepository
    {
        // clone global context object (singleton)
        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect(
            'language',
            $languageAspect ?? GeneralUtility::makeInstance(LanguageAspect::class)
        );
        return GeneralUtility::makeInstance(
            PageRepository::class,
            $context
        );
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
            $subPages = $this->sys_page->getMenu($id, '*', $sortingField, '', true, $this->disableGroupAccessCheck);
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
        $pageRecords = $this->sys_page->getMenuForPages($pageIds, '*', 'sorting', '', true, $disableGroupAccessCheck);
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
        $pageIds = [];
        foreach ($items as $id) {
            // Exclude the current ID if beginAtLevel is > 0
            if ($beginAtLevel > 0) {
                $pageIds = array_merge($pageIds, $this->sys_page->getDescendantPageIdsRecursive($id, $depth - 1 + $beginAtLevel, $beginAtLevel - 1));
            } else {
                $pageIds = array_merge($pageIds, [$id], $this->sys_page->getDescendantPageIdsRecursive($id, $depth - 1 + $beginAtLevel, $beginAtLevel - 1));
            }
        }
        // Get sortField (mode)
        $sortField = $this->getMode($mode);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $extraWhere = ($this->conf['includeNotInMenu'] ? '' : ' AND pages.nav_hide=0') . $this->getDoktypeExcludeWhere();
        if ($this->conf['special.']['excludeNoSearchPages'] ?? false) {
            $extraWhere .= sprintf(' AND %s=%s', $connection->quoteIdentifier('pages.no_search'), $connection->quote(0, Connection::PARAM_INT));
        }
        if ($maxAge > 0) {
            $extraWhere .= sprintf(' AND %s>%s', $connection->quoteIdentifier($sortField), $connection->quote(($GLOBALS['SIM_ACCESS_TIME'] - $maxAge), Connection::PARAM_INT));
        }
        $extraWhere = sprintf('%s>=%s', $connection->quoteIdentifier($sortField), $connection->quote(0, Connection::PARAM_INT)) . $extraWhere;

        $i = 0;
        $pageRecords = $this->sys_page->getMenuForPages($pageIds, '*', $sortingField ?: $sortField . ' DESC', $extraWhere, true, $this->disableGroupAccessCheck);
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
            $kw = trim($this->parent_cObj->keywords($value_rec[$kfieldSrc] ?? ''));
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
            $tsfe->config['rootLine'] ?? []
        );
        $startUid = (int)($tsfe->config['rootLine'][$eLevel]['uid'] ?? 0);
        // Which field is for keywords
        $kfield = 'keywords';
        if ($this->conf['special.']['keywordsField'] ?? false) {
            [$kfield] = explode(' ', trim($this->conf['special.']['keywordsField']));
        }
        // If there are keywords and the startUid is present
        if ($kw && $startUid) {
            $bA = MathUtility::forceIntegerInRange(($this->conf['special.']['beginAtLevel'] ?? 0), 0, 100);
            $id_list = $this->sys_page->getDescendantPageIdsRecursive($startUid, $depth - 1 + $bA, $bA - 1);
            $id_list = array_merge([(int)$startUid], $id_list);
            $kwArr = GeneralUtility::trimExplode(',', $kw, true);
            $keyWordsWhereArr = [];
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            foreach ($kwArr as $word) {
                $keyWordsWhereArr[] = $queryBuilder->expr()->like(
                    $kfield,
                    $queryBuilder->createNamedParameter(
                        '%' . $queryBuilder->escapeLikeWildcards($word) . '%'
                    )
                );
            }
            $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $id_list
                    ),
                    $queryBuilder->expr()->neq(
                        'uid',
                        $queryBuilder->createNamedParameter($specialValue, Connection::PARAM_INT)
                    )
                );

            if (!empty($keyWordsWhereArr)) {
                $queryBuilder->andWhere($queryBuilder->expr()->or(...$keyWordsWhereArr));
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
        $tsfe = $this->getTypoScriptFrontendController();
        $menuItems = [];
        $range = (string)$this->parent_cObj->stdWrapValue('range', $this->conf['special.'] ?? []);
        $begin_end = explode('|', $range);
        $begin_end[0] = (int)$begin_end[0];
        if (!MathUtility::canBeInterpretedAsInteger($begin_end[1] ?? '')) {
            $begin_end[1] = -1;
        }
        $beginKey = $this->parent_cObj->getKey($begin_end[0], $tsfe->config['rootLine'] ?? []);
        $endKey = $this->parent_cObj->getKey($begin_end[1], $tsfe->config['rootLine'] ?? []);
        if ($endKey < $beginKey) {
            $endKey = $beginKey;
        }
        $rl_MParray = [];
        foreach (($tsfe->config['rootLine'] ?? []) as $k_rl => $v_rl) {
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
        $tsfe = $this->getTypoScriptFrontendController();
        $menuItems = [];
        [$specialValue] = GeneralUtility::intExplode(',', $specialValue);
        if (!$specialValue) {
            $specialValue = $this->getTypoScriptFrontendController()->page['uid'];
        }
        // Will not work out of rootline
        if ($specialValue != ($tsfe->config['rootLine'][0]['uid'] ?? null)) {
            $recArr = [];
            // The page record of the 'value'.
            $value_rec = $this->sys_page->getPage($specialValue, $this->disableGroupAccessCheck);
            // 'up' page cannot be outside rootline
            if ($value_rec['pid']) {
                // The page record of 'up'.
                $recArr['up'] = $this->sys_page->getPage($value_rec['pid'], $this->disableGroupAccessCheck);
            }
            // If the 'up' item was NOT level 0 in rootline...
            if (($recArr['up']['pid'] ?? 0) && $value_rec['pid'] != ($tsfe->config['rootLine'][0]['uid'] ?? null)) {
                // The page record of "index".
                $recArr['index'] = $this->sys_page->getPage($recArr['up']['pid']);
            }
            // check if certain pages should be excluded
            $additionalWhere .= ($this->conf['includeNotInMenu'] ? '' : ' AND pages.nav_hide=0') . $this->getDoktypeExcludeWhere();
            if ($this->conf['special.']['excludeNoSearchPages'] ?? false) {
                $additionalWhere .= ' AND pages.no_search=0';
            }
            // prev / next is found
            $prevnext_menu = $this->removeInaccessiblePages($this->sys_page->getMenu($value_rec['pid'], '*', $sortingField, $additionalWhere, true, $this->disableGroupAccessCheck));
            $nextActive = false;
            foreach ($prevnext_menu as $k_b => $v_b) {
                if ($nextActive) {
                    $recArr['next'] = $v_b;
                    $nextActive = false;
                }
                if ($v_b['uid'] == $specialValue) {
                    if (isset($lastKey)) {
                        $recArr['prev'] = $prevnext_menu[$lastKey];
                    }
                    $nextActive = true;
                }
                $lastKey = $k_b;
            }
            unset($lastKey);

            $recArr['first'] = reset($prevnext_menu);
            $recArr['last'] = end($prevnext_menu);
            // prevsection / nextsection is found
            // You can only do this, if there is a valid page two levels up!
            if (!empty($recArr['index']['uid'])) {
                $prevnextsection_menu = $this->removeInaccessiblePages($this->sys_page->getMenu($recArr['index']['uid'], '*', $sortingField, $additionalWhere, true, $this->disableGroupAccessCheck));
                $nextActive = false;
                foreach ($prevnextsection_menu as $k_b => $v_b) {
                    if ($nextActive) {
                        $sectionRec_temp = $this->removeInaccessiblePages($this->sys_page->getMenu($v_b['uid'], '*', $sortingField, $additionalWhere, true, $this->disableGroupAccessCheck));
                        if (!empty($sectionRec_temp)) {
                            $recArr['nextsection'] = reset($sectionRec_temp);
                            $recArr['nextsection_last'] = end($sectionRec_temp);
                            $nextActive = false;
                        }
                    }
                    if ($v_b['uid'] == $value_rec['pid']) {
                        if (isset($lastKey)) {
                            $sectionRec_temp = $this->removeInaccessiblePages($this->sys_page->getMenu($prevnextsection_menu[$lastKey]['uid'], '*', $sortingField, $additionalWhere, true, $this->disableGroupAccessCheck));
                            if (!empty($sectionRec_temp)) {
                                $recArr['prevsection'] = reset($sectionRec_temp);
                                $recArr['prevsection_last'] = end($sectionRec_temp);
                            }
                        }
                        $nextActive = true;
                    }
                    $lastKey = $k_b;
                }
                unset($lastKey);
            }
            if ($this->conf['special.']['items.']['prevnextToSection'] ?? false) {
                if (!is_array($recArr['prev'] ?? false) && is_array($recArr['prevsection_last'] ?? false)) {
                    $recArr['prev'] = $recArr['prevsection_last'];
                }
                if (!is_array($recArr['next'] ?? false) && is_array($recArr['nextsection'] ?? false)) {
                    $recArr['next'] = $recArr['nextsection'];
                }
            }
            $items = explode('|', $this->conf['special.']['items']);
            $c = 0;
            foreach ($items as $k_b => $v_b) {
                $v_b = strtolower(trim($v_b));
                if ((int)($this->conf['special.'][$v_b . '.']['uid'] ?? false)) {
                    $recArr[$v_b] = $this->sys_page->getPage((int)$this->conf['special.'][$v_b . '.']['uid'], $this->disableGroupAccessCheck);
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
     *
     * @param int $key Pointer to a key in the $this->menuArr array where the value for that key represents the menu item we are linking to (page record)
     * @param string $altTarget Alternative target
     * @param string $typeOverride Alternative type
     * @return LinkResultInterface|null
     */
    protected function link($key, $altTarget, $typeOverride)
    {
        $runtimeCache = $this->getRuntimeCache();
        $MP_var = $this->getMPvar($key);
        $cacheId = 'menu-generated-links-' . md5(
            $key
                . ($altTarget ?: ($this->mconf['target'] ?? '') . (isset($this->mconf['target.']) ? json_encode($this->mconf['target.']) : ''))
                . $typeOverride
                . $MP_var
                . ($this->mconf['addParams'] ?? '')
                . ($this->I['val']['additionalParams'] ?? '')
                . ((string)($this->mconf['showAccessRestrictedPages'] ?? '_'))
                . (isset($this->mconf['showAccessRestrictedPages.']) ? json_encode($this->mconf['showAccessRestrictedPages.']) : '')
                . json_encode($this->menuArr[$key])
                . ($this->I['val']['ATagParams'] ?? '')
                . (isset($this->I['val']['ATagParams.']) ? json_encode($this->I['val']['ATagParams.']) : '')
        );
        $runtimeCachedLink = $runtimeCache->get($cacheId);
        if ($runtimeCachedLink !== false) {
            return $runtimeCachedLink;
        }

        $tsfe = $this->getTypoScriptFrontendController();

        $SAVED_link_to_restricted_pages = '';
        $SAVED_link_to_restricted_pages_additional_params = '';
        $SAVED_link_to_restricted_pages_tag_attributes = '';
        // links to a specific page
        if ($this->mconf['showAccessRestrictedPages'] ?? false) {
            $SAVED_link_to_restricted_pages = $tsfe->config['config']['typolinkLinkAccessRestrictedPages'] ?? false;
            $SAVED_link_to_restricted_pages_additional_params = $tsfe->config['config']['typolinkLinkAccessRestrictedPages_addParams'] ?? null;
            $SAVED_link_to_restricted_pages_tag_attributes = $tsfe->config['config']['typolinkLinkAccessRestrictedPages.']['ATagParams'] ?? '';
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages'] = $this->mconf['showAccessRestrictedPages'];
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages_addParams'] = $this->mconf['showAccessRestrictedPages.']['addParams'] ?? '';
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages.']['ATagParams'] = $this->mconf['showAccessRestrictedPages.']['ATagParams'] ?? '';
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
        // Creating link
        $addParams = ($this->mconf['addParams'] ?? '') . ($this->I['val']['additionalParams'] ?? '') . $MP_params;
        try {
            $linkResult = $this->menuTypoLink($this->menuArr[$key], $mainTarget, $addParams, $typeOverride, $overrideId);
            // Overriding URL / Target if set to do so:
            if ($this->menuArr[$key]['_OVERRIDE_HREF'] ?? false) {
                $linkResult = $linkResult->withAttribute('href', $this->menuArr[$key]['_OVERRIDE_HREF']);
                if ($this->menuArr[$key]['_OVERRIDE_TARGET'] ?? false) {
                    $linkResult = $linkResult->withAttribute('target', $this->menuArr[$key]['_OVERRIDE_TARGET']);
                }
            }
        } catch (UnableToLinkException $e) {
            $linkResult = null;
        }
        $runtimeCache->set($cacheId, $linkResult);

        // End showAccessRestrictedPages
        if ($this->mconf['showAccessRestrictedPages'] ?? false) {
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages'] = $SAVED_link_to_restricted_pages;
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages_addParams'] = $SAVED_link_to_restricted_pages_additional_params;
            $tsfe->config['config']['typolinkLinkAccessRestrictedPages.']['ATagParams'] = $SAVED_link_to_restricted_pages_tag_attributes;
        }

        return $linkResult;
    }

    /**
     * Creates a submenu level to the current level - if configured for.
     *
     * @param int $uid Page id of the current page for which a submenu MAY be produced (if conditions are met)
     * @param string $objSuffix Object prefix, see ->start()
     * @return string HTML content of the submenu
     */
    protected function subMenu(int $uid, string $objSuffix, int $menuItemKey)
    {
        // Setting alternative menu item array if _SUB_MENU has been defined in the current ->menuArr
        $altArray = '';
        if (is_array($this->menuArr[$menuItemKey]['_SUB_MENU'] ?? null) && !empty($this->menuArr[$menuItemKey]['_SUB_MENU'])) {
            $altArray = $this->menuArr[$menuItemKey]['_SUB_MENU'];
        }
        // Make submenu if the page is the next active
        $menuType = $this->conf[($this->menuNumber + 1) . $objSuffix] ?? '';
        // stdWrap for expAll
        $this->mconf['expAll'] = $this->parent_cObj->stdWrapValue('expAll', $this->mconf ?? []);
        if (($this->mconf['expAll'] || $this->isNext($uid, $this->getMPvar($menuItemKey)) || is_array($altArray)) && !($this->mconf['sectionIndex'] ?? false)) {
            try {
                $menuObjectFactory = GeneralUtility::makeInstance(MenuContentObjectFactory::class);
                /** @var AbstractMenuContentObject $submenu */
                $submenu = $menuObjectFactory->getMenuObjectByType($menuType);
                $submenu->entryLevel = $this->entryLevel + 1;
                $submenu->rL_uidRegister = $this->rL_uidRegister;
                $submenu->MP_array = $this->MP_array;
                if ($this->menuArr[$menuItemKey]['_MP_PARAM'] ?? false) {
                    $submenu->MP_array[] = $this->menuArr[$menuItemKey]['_MP_PARAM'];
                }
                // Especially scripts that build the submenu needs the parent data
                $submenu->parent_cObj = $this->parent_cObj;
                $submenu->setParentMenu($this->menuArr, $menuItemKey);
                // Setting alternativeMenuTempArray (will be effective only if an array and not empty)
                if (is_array($altArray) && !empty($altArray)) {
                    $submenu->alternativeMenuTempArray = $altArray;
                }
                // @deprecated since v12, will be removed in v13: Hand over null as first argument.
                if ($submenu->start($this->tmpl, $this->sys_page, $uid, $this->conf, $this->menuNumber + 1, $objSuffix, $this->request)) {
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
            $page = $this->sys_page->resolveShortcutPage($page, $this->disableGroupAccessCheck);
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
        $cacheId = $this->getCacheIdentifierForSubMenuDecision($uid);
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

        // Collect subpages for all pages on current level
        $pageIdsOnSameLevel = array_column($this->menuArr, 'uid');
        $cacheIdentifierPagesNextLevel = 'menucontentobject-is-submenu-pages-next-level-' . $this->menuNumber . '-' . sha1(json_encode($pageIdsOnSameLevel));
        $cachePagesNextLevel = $runtimeCache->get($cacheIdentifierPagesNextLevel);
        if (!is_array($cachePagesNextLevel)) {
            $cachePagesNextLevel = $this->sys_page->getMenu($pageIdsOnSameLevel, 'uid,pid,doktype,mount_pid,mount_pid_ol,nav_hide,shortcut,shortcut_mode,l18n_cfg');
            $runtimeCache->set($cacheIdentifierPagesNextLevel, $cachePagesNextLevel);
        }

        $recs = array_filter($cachePagesNextLevel, static fn (array $item) => (int)$item['pid'] === (int)$uid);

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

    protected function getCacheIdentifierForSubMenuDecision($uid): string
    {
        return 'menucontentobject-is-submenu-decision-' . $uid . '-' . (int)($this->conf['includeNotInMenu'] ?? 0);
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
     * Creates the <A> tag parts for the current item (in $this->I, [A1] and [A2]) based on the given link result
     */
    protected function setATagParts(?LinkResultInterface $linkResult)
    {
        $this->I['A1'] = $linkResult ? '<a ' . GeneralUtility::implodeAttributes($linkResult->getAttributes(), true) . '>' : '';
        $this->I['A2'] = $linkResult ? '</a>' : '';
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
     */
    protected function menuTypoLink(array $page, string $oTarget, $addParams, $typeOverride, ?int $overridePageId = null): LinkResultInterface
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
        // $this->I['val'] contains the configuration of the ItemState (e.g. NO / SPC) etc, which should be handed in
        // to this method instead of accessed directly in the future.
        if (isset($this->I['val']['ATagParams']) || isset($this->I['val']['ATagParams.'])) {
            $conf['ATagParams'] = $this->I['val']['ATagParams'] ?? '';
            $conf['ATagParams.'] = $this->I['val']['ATagParams.'] ?? [];
        }
        if ($page['sectionIndex_uid'] ?? false) {
            $conf['section'] = $page['sectionIndex_uid'];
        }
        $conf['page'] = new Page($page);
        return $this->parent_cObj->createLink('|', $conf);
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
                $row = $this->sys_page->getLanguageOverlay(
                    'tt_content',
                    $row,
                    $languageAspect
                );
            }
            if (is_array($row)) {
                $sectionIndexType = $this->mconf['sectionIndex.']['type'] ?? '';
                if ($sectionIndexType !== 'all') {
                    $doIncludeInSectionIndex = $row['sectionIndex'] >= 1;
                    $doHeaderCheck = $sectionIndexType === 'header';
                    $isValidHeader = ((int)$row['header_layout'] !== 100 || !empty($this->mconf['sectionIndex.']['includeHiddenHeaders'])) && trim($row['header']) !== '';
                    if (!$doIncludeInSectionIndex || ($doHeaderCheck && !$isValidHeader)) {
                        continue;
                    }
                }
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
     * @throws ContentRenderingException
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        $frontendController = $this->parent_cObj->getTypoScriptFrontendController();
        if (!$frontendController instanceof TypoScriptFrontendController) {
            throw new ContentRenderingException('TypoScriptFrontendController is not available.', 1655725105);
        }

        return $frontendController;
    }

    protected function getCurrentLanguageAspect(): LanguageAspect
    {
        return GeneralUtility::makeInstance(Context::class)->getAspect('language');
    }

    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    protected function getCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('hash');
    }

    protected function getRuntimeCache(): FrontendInterface
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
    }

    /**
     * Returns the currently configured "site" if a site is configured (= resolved) in the current request.
     */
    protected function getCurrentSite(): Site
    {
        return $this->getTypoScriptFrontendController()->getSite();
    }

    /**
     * Set the parentMenuArr and key to provide the parentMenu information to the
     * subMenu, special fur IProcFunc and itemArrayProcFunc user functions.
     *
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

    /**
     * Returns the level of the given page in the rootline - Multiple pages can be given by separating the UIDs by comma.
     *
     * @param string $list A list of UIDs for which the rootline-level should get returned
     * @return int The level in the rootline. If more than one page was given the lowest level will get returned.
     */
    private function getRootlineLevel(array $rootLine, string $list): int
    {
        $idx = 0;
        foreach ($rootLine as $page) {
            if (GeneralUtility::inList($list, $page['uid'])) {
                return $idx;
            }
            $idx++;
        }
        return 0;
    }
}
