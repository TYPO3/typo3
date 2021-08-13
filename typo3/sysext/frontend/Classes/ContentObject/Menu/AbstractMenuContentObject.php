<?php
namespace TYPO3\CMS\Frontend\ContentObject\Menu;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;

/**
 * Generating navigation/menus from TypoScript
 *
 * The HMENU content object uses this (or more precisely one of the extension classes).
 * Among others the class generates an array of menu items. Thereafter functions from the subclasses are called.
 * The class is always used through extension classes (like GraphicalMenuContentObject or TextMenuContentObject).
 */
abstract class AbstractMenuContentObject
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    protected $deprecatedPublicProperties = [
        'menuNumber' => 'Using $menuNumber of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'entryLevel' => 'Using $entryLevel of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'spacerIDList' => 'Using $spacerIDList of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'doktypeExcludeList' => 'Using $doktypeExcludeList of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'alwaysActivePIDlist' => 'Using $alwaysActivePIDlist of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'imgNamePrefix' => 'Using $imgNamePrefix of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'imgNameNotRandom' => 'Using $imgNameNotRandom of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'debug' => 'Using $debug of cObject HMENU from the outside is discouraged, as this variable is not in use anymore and will be removed in TYPO3 v10.0.',
        'GMENU_fixKey' => 'Using $GMENU_fixKey of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'MP_array' => 'Using $MP_array of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'conf' => 'Using $conf of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'mconf' => 'Using $mconf of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'tmpl' => 'Using $tmpl of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'sys_page' => 'Using $sys_page of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'id' => 'Using $id of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'nextActive' => 'Using $nextActive of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'menuArr' => 'Using $menuArr of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'hash' => 'Using $hash of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'result' => 'Using $result of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'rL_uidRegister' => 'Using $rL_uidRegister of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'INPfixMD5' => 'Using $INPfixMD5 of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'I' => 'Using $I of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'WMresult' => 'Using $WMresult of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'WMfreezePrefix' => 'Using $WMfreezePrefix of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'WMmenuItems' => 'Using $WMmenuItems of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'WMsubmenuObjSuffixes' => 'Using $WMsubmenuObjSuffixes of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'WMextraScript' => 'Using $WMextraScript of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'WMcObj' => 'Using $WMcObj of cObject HMENU is discouraged, as all graphical-related functionality will be removed in TYPO3 v10.0.',
        'alternativeMenuTempArray' => 'Using $alternativeMenuTempArray of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
        'nameAttribute' => 'Using $nameAttribute of cObject HMENU from the outside is discouraged, as this variable is only used for internal storage.',
    ];

    protected $deprecatedPublicMethods = [
        'subMenu' => 'Using subMenu() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'link' => 'Using link() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'procesItemStates' => 'Using procesItemStates() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'changeLinksForAccessRestrictedPages' => 'Using changeLinksForAccessRestrictedPages() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'isNext' => 'Using isNext() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'isActive' => 'Using isActive() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'isCurrent' => 'Using isCurrent() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'isSubMenu' => 'Using isSubMenu() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'isItemState' => 'Using isItemState() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'accessKey' => 'Using accessKey() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'userProcess' => 'Using userProcess() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'setATagParts' => 'Using setATagParts() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'getPageTitle' => 'Using getPageTitle() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'getMPvar' => 'Using getMPvar() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'getDoktypeExcludeWhere' => 'Using getDoktypeExcludeWhere() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'getBannedUids' => 'Using getBannedUids() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'menuTypoLink' => 'Using menuTypoLink() within HMENU is discouraged, as this is internal functionality that should not be exposed to the public.',
        'extProc_RO' => 'Using extProc_RO() within HMENU extensions is discouraged, as rollover functionality will be removed in TYPO3 v10.0.',
        'extProc_init' => 'Using extProc_init() within HMENU extensions is discouraged, as extending HMENU should only happens via userFunc options.',
        'extProc_beforeLinking' => 'Using extProc_beforeLinking() within HMENU extensions is discouraged, as extending HMENU should only happens via userFunc options.',
        'extProc_afterLinking' => 'Using extProc_afterLinking() within HMENU extensions is discouraged, as extending HMENU should only happens via userFunc options.',
        'extProc_beforeAllWrap' => 'Using extProc_beforeAllWrap() within HMENU extensions is discouraged, as extending HMENU should only happens via userFunc options.',
        'extProc_finish' => 'Using extProc_finish() within HMENU extensions is discouraged, as extending HMENU should only happens via userFunc options.',
        'getBeforeAfter' => 'Using getBeforeAfter() within HMENU extensions is discouraged, as extending HMENU should only happens via userFunc options.',
    ];

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
     * The doktype-number that defines a spacer
     *
     * @var string
     */
    protected $spacerIDList = '199';

    /**
     * Doktypes that define which should not be included in a menu
     *
     * @var string
     */
    protected $doktypeExcludeList = '6';

    /**
     * @var int[]
     */
    protected $alwaysActivePIDlist = [];

    /**
     * @var string
     */
    protected $imgNamePrefix = 'img';

    /**
     * @var int
     */
    protected $imgNameNotRandom = 0;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * Loaded with the parent cObj-object when a new HMENU is made
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $parent_cObj;

    /**
     * @var string
     */
    protected $GMENU_fixKey = 'gmenu';

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
     * xMENU configuration (TMENU, GMENU etc)
     *
     * @var array
     */
    protected $mconf = [];

    /**
     * @var \TYPO3\CMS\Core\TypoScript\TemplateService
     */
    protected $tmpl;

    /**
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
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
     * @var string
     */
    protected $INPfixMD5;

    /**
     * @var mixed[]
     */
    protected $I;

    /**
     * @var string
     */
    protected $WMresult;

    /**
     * @var string
     */
    protected $WMfreezePrefix;

    /**
     * @var int
     */
    protected $WMmenuItems;

    /**
     * @var array[]
     */
    protected $WMsubmenuObjSuffixes;

    /**
     * @var string
     */
    protected $WMextraScript;

    /**
     * @var ContentObjectRenderer
     */
    protected $WMcObj;

    /**
     * Can be set to contain menu item arrays for sub-levels.
     *
     * @var string
     */
    protected $alternativeMenuTempArray = '';

    /**
     * Will be 'id' in XHTML-mode
     *
     * @var string
     */
    protected $nameAttribute = 'name';

    /**
     * TRUE to use cHash in generated link (normally only for the language
     * selector and if parameters exist in the URL).
     *
     * @var bool
     */
    protected $useCacheHash = false;

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

    /**
     * The initialization of the object. This just sets some internal variables.
     *
     * @param TemplateService $tmpl The $this->getTypoScriptFrontendController()->tmpl object
     * @param PageRepository $sys_page The $this->getTypoScriptFrontendController()->sys_page object
     * @param int|string $id A starting point page id. This should probably be blank since the 'entryLevel' value will be used then.
     * @param array $conf The TypoScript configuration for the HMENU cObject
     * @param int $menuNumber Menu number; 1,2,3. Should probably be 1
     * @param string $objSuffix Submenu Object suffix. This offers submenus a way to use alternative configuration for specific positions in the menu; By default "1 = TMENU" would use "1." for the TMENU configuration, but if this string is set to eg. "a" then "1a." would be used for configuration instead (while "1 = " is still used for the overall object definition of "TMENU")
     * @return bool Returns TRUE on success
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::HMENU()
     */
    public function start($tmpl, $sys_page, $id, $conf, $menuNumber, $objSuffix = '')
    {
        $tsfe = $this->getTypoScriptFrontendController();
        // Init:
        $this->conf = $conf;
        $this->menuNumber = $menuNumber;
        $this->mconf = $conf[$this->menuNumber . $objSuffix . '.'];
        $this->debug = !empty($tsfe->config['config']['debug']);
        $this->WMcObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        // In XHTML and HTML5 there is no "name" attribute anymore
        switch ($tsfe->xhtmlDoctype) {
            case 'xhtml_strict':
                // intended fall-through
            case 'xhtml_11':
                // intended fall-through
            case 'html5':
                // intended fall-through
            case '':
                // empty means that it's HTML5 by default
                $this->nameAttribute = 'id';
                break;
            default:
                $this->nameAttribute = 'name';
        }
        // Sets the internal vars. $tmpl MUST be the template-object. $sys_page MUST be the sys_page object
        if ($this->conf[$this->menuNumber . $objSuffix] && is_object($tmpl) && is_object($sys_page)) {
            $this->tmpl = $tmpl;
            $this->sys_page = $sys_page;
            // alwaysActivePIDlist initialized:
            if (trim($this->conf['alwaysActivePIDlist']) || isset($this->conf['alwaysActivePIDlist.'])) {
                if (isset($this->conf['alwaysActivePIDlist.'])) {
                    $this->conf['alwaysActivePIDlist'] = $this->parent_cObj->stdWrap(
                        $this->conf['alwaysActivePIDlist'],
                        $this->conf['alwaysActivePIDlist.']
                    );
                }
                $this->alwaysActivePIDlist = GeneralUtility::intExplode(',', $this->conf['alwaysActivePIDlist']);
            }
            // includeNotInMenu initialized:
            $includeNotInMenu = $this->conf['includeNotInMenu'];
            $includeNotInMenuConf = $this->conf['includeNotInMenu.'] ?? null;
            $this->conf['includeNotInMenu'] = is_array($includeNotInMenuConf)
                ? $this->parent_cObj->stdWrap($includeNotInMenu, $includeNotInMenuConf)
                : $includeNotInMenu;
            // 'not in menu' doktypes
            if ($this->conf['excludeDoktypes']) {
                $this->doktypeExcludeList = implode(',', GeneralUtility::intExplode(',', $this->conf['excludeDoktypes']));
            }
            // EntryLevel
            $this->entryLevel = $this->parent_cObj->getKey(
                isset($conf['entryLevel.']) ? $this->parent_cObj->stdWrap(
                    $conf['entryLevel'],
                    $conf['entryLevel.']
                ) : $conf['entryLevel'],
                $this->tmpl->rootLine
            );
            // Set parent page: If $id not stated with start() then the base-id will be found from rootLine[$this->entryLevel]
            // Called as the next level in a menu. It is assumed that $this->MP_array is set from parent menu.
            if ($id) {
                $this->id = (int)$id;
            } else {
                // This is a BRAND NEW menu, first level. So we take ID from rootline and also find MP_array (mount points)
                $this->id = (int)$this->tmpl->rootLine[$this->entryLevel]['uid'];
                // Traverse rootline to build MP_array of pages BEFORE the entryLevel
                // (MP var for ->id is picked up in the next part of the code...)
                foreach ($this->tmpl->rootLine as $entryLevel => $levelRec) {
                    // For overlaid mount points, set the variable right now:
                    if ($levelRec['_MP_PARAM'] && $levelRec['_MOUNT_OL']) {
                        $this->MP_array[] = $levelRec['_MP_PARAM'];
                    }
                    // Break when entry level is reached:
                    if ($entryLevel >= $this->entryLevel) {
                        break;
                    }
                    // For normal mount points, set the variable for next level.
                    if ($levelRec['_MP_PARAM'] && !$levelRec['_MOUNT_OL']) {
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
                    if ($v_rl['_MP_PARAM'] && $v_rl['_MOUNT_OL']) {
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
                    if ($v_rl['_MP_PARAM'] && !$v_rl['_MOUNT_OL']) {
                        $rl_MParray[] = $v_rl['_MP_PARAM'];
                    }
                }
            }
            // Set $directoryLevel so the following evalution of the nextActive will not return
            // an invalid value if .special=directory was set
            $directoryLevel = 0;
            if ($this->conf['special'] === 'directory') {
                $value = isset($this->conf['special.']['value.']) ? $this->parent_cObj->stdWrap(
                    $this->conf['special.']['value'],
                    $this->conf['special.']['value.']
                ) : $this->conf['special.']['value'];
                if ($value === '') {
                    $value = $tsfe->page['uid'];
                }
                $directoryLevel = (int)$tsfe->tmpl->getRootlineLevel($value);
            }
            // Setting "nextActive": This is the page uid + MPvar of the NEXT page in rootline. Used to expand the menu if we are in the right branch of the tree
            // Notice: The automatic expansion of a menu is designed to work only when no "special" modes (except "directory") are used.
            $startLevel = $directoryLevel ?: $this->entryLevel;
            $currentLevel = $startLevel + $this->menuNumber;
            if (is_array($this->tmpl->rootLine[$currentLevel])) {
                $nextMParray = $this->MP_array;
                if (empty($nextMParray) && !$this->tmpl->rootLine[$currentLevel]['_MOUNT_OL'] && $currentLevel > 0) {
                    // Make sure to slide-down any mount point information (_MP_PARAM) to children records in the rootline
                    // otherwise automatic expansion will not work
                    $parentRecord = $this->tmpl->rootLine[$currentLevel - 1];
                    if (isset($parentRecord['_MP_PARAM'])) {
                        $nextMParray[] = $parentRecord['_MP_PARAM'];
                    }
                }
                // In overlay mode, add next level MPvars as well:
                if ($this->tmpl->rootLine[$currentLevel]['_MOUNT_OL']) {
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
            // imgNamePrefix
            if ($this->mconf['imgNamePrefix']) {
                $this->imgNamePrefix = $this->mconf['imgNamePrefix'];
            }
            $this->imgNameNotRandom = $this->mconf['imgNameNotRandom'];
            $retVal = true;
        } else {
            $this->getTimeTracker()->setTSlogMessage('ERROR in menu', 3);
            $retVal = false;
        }
        return $retVal;
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

        $this->useCacheHash = false;

        // Initializing showAccessRestrictedPages
        $SAVED_where_groupAccess = '';
        if ($this->mconf['showAccessRestrictedPages']) {
            // SAVING where_groupAccess
            $SAVED_where_groupAccess = $this->sys_page->where_groupAccess;
            // Temporarily removing fe_group checking!
            $this->sys_page->where_groupAccess = '';
        }

        $menuItems = $this->prepareMenuItems();

        $c = 0;
        $c_b = 0;
        $minItems = (int)($this->mconf['minItems'] ?: $this->conf['minItems']);
        $maxItems = (int)($this->mconf['maxItems'] ?: $this->conf['maxItems']);
        $begin = $this->parent_cObj->calc($this->mconf['begin'] ? $this->mconf['begin'] : $this->conf['begin']);
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
            $spacer = GeneralUtility::inList($this->spacerIDList, $data['doktype']) || $data['ITEM_STATE'] === 'SPC';
            // if item is a spacer, $spacer is set
            if ($this->filterMenuPages($data, $banUidArray, $spacer)) {
                $c_b++;
                // If the beginning item has been reached.
                if ($begin <= $c_b) {
                    $this->menuArr[$c] = $this->determineOriginalShortcutPage($data);
                    $this->menuArr[$c]['isSpacer'] = $spacer;
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
                    'uid' => $this->getTypoScriptFrontendController()->id
                ];
                $c++;
            }
        }
        //	Passing the menuArr through a user defined function:
        if ($this->mconf['itemArrayProcFunc']) {
            $this->menuArr = $this->userProcess('itemArrayProcFunc', $this->menuArr);
        }
        // Setting number of menu items
        $this->getTypoScriptFrontendController()->register['count_menuItems'] = count($this->menuArr);
        $this->hash = md5(
            serialize($this->menuArr) .
            serialize($this->mconf) .
            serialize($this->tmpl->rootLine) .
            serialize($this->MP_array)
        );
        // Get the cache timeout:
        if ($this->conf['cache_period']) {
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
        if ($this->mconf['showAccessRestrictedPages']) {
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
            if ($this->filterMenuPages($aPage, $banned, $aPage['doktype'] === PageRepository::DOKTYPE_SPACER)) {
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
        $alternativeSortingField = trim($this->mconf['alternativeSortingField']) ?: 'sorting';

        // Additional where clause, usually starts with AND (as usual with all additionalWhere functionality in TS)
        $additionalWhere = $this->mconf['additionalWhere'] ?? '';
        if (isset($this->mconf['additionalWhere.'])) {
            $additionalWhere = $this->parent_cObj->stdWrap($additionalWhere, $this->mconf['additionalWhere.']);
        }

        // ... only for the FIRST level of a HMENU
        if ($this->menuNumber == 1 && $this->conf['special']) {
            $value = isset($this->conf['special.']['value.'])
                ? $this->parent_cObj->stdWrap($this->conf['special.']['value'], $this->conf['special.']['value.'])
                : $this->conf['special.']['value'];
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
                        $this->mconf['alternativeSortingField'] ?: false
                    );
                    break;
                case 'keywords':
                    $menuItems = $this->prepareMenuItemsForKeywordsMenu(
                        $value,
                        $this->mconf['alternativeSortingField'] ?: false
                    );
                    break;
                case 'categories':
                    /** @var CategoryMenuUtility $categoryMenuUtility */
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
            if ($this->mconf['sectionIndex']) {
                $sectionIndexes = [];
                foreach ($menuItems as $page) {
                    $sectionIndexes = $sectionIndexes + $this->sectionIndex($alternativeSortingField, $page['uid']);
                }
                $menuItems = $sectionIndexes;
            }
        } elseif (is_array($this->alternativeMenuTempArray)) {
            // Setting $menuItems array if not level 1.
            $menuItems = $this->alternativeMenuTempArray;
        } elseif ($this->mconf['sectionIndex']) {
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
        if (!is_array($menuItems)) {
            $menuItems = [];
        }
        return $menuItems;
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
        $currentPageWithNoOverlay = $this->sys_page->getRawRecord('pages', $tsfe->page['uid']);

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
                $lRecs = $this->sys_page->getPageOverlay($tsfe->page['uid'], $sUid);
            } else {
                $lRecs = [];
            }
            // Checking if the "disabled" state should be set.
            if (GeneralUtility::hideIfNotTranslated($tsfe->page['l18n_cfg']) && $sUid &&
                empty($lRecs) || GeneralUtility::hideIfDefaultLanguage($tsfe->page['l18n_cfg']) &&
                (!$sUid || empty($lRecs)) ||
                !$this->conf['special.']['normalWhenNoLanguage'] && $sUid && empty($lRecs)
            ) {
                $iState = $currentLanguageId == $sUid ? 'USERDEF2' : 'USERDEF1';
            } else {
                $iState = $currentLanguageId == $sUid ? 'ACT' : 'NO';
            }
            if ($this->conf['addQueryString']) {
                $getVars = $this->parent_cObj->getQueryArguments(
                    $this->conf['addQueryString.'],
                    ['L' => $sUid],
                    true
                );
                $this->analyzeCacheHashRequirements($getVars);
            } else {
                $getVars = '&L=' . $sUid;
            }
            // Adding menu item:
            $menuItems[] = array_merge(
                array_merge($currentPageWithNoOverlay, $lRecs),
                [
                    'ITEM_STATE' => $iState,
                    '_ADD_GETVARS' => $getVars,
                    '_SAFE' => true
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
            $specialValue = $tsfe->page['uid'];
        }
        $items = GeneralUtility::intExplode(',', $specialValue);
        $pageLinkBuilder = GeneralUtility::makeInstance(PageLinkBuilder::class, $this->parent_cObj);
        foreach ($items as $id) {
            $MP = $pageLinkBuilder->getMountPointParameterFromRootPointMaps($id);
            // Checking if a page is a mount page and if so, change the ID and set the MP var properly.
            $mount_info = $this->sys_page->getMountPointInfo($id);
            if (is_array($mount_info)) {
                if ($mount_info['overlay']) {
                    // Overlays should already have their full MPvars calculated:
                    $MP = $pageLinkBuilder->getMountPointParameterFromRootPointMaps((int)$mount_info['mount_pid']);
                    $MP = $MP ? $MP : $mount_info['MPvar'];
                } else {
                    $MP = ($MP ? $MP . ',' : '') . $mount_info['MPvar'];
                }
                $id = $mount_info['mount_pid'];
            }
            // Get sub-pages:
            $statement = $this->parent_cObj->exec_getQuery('pages', ['pidInList' => $id, 'orderBy' => $sortingField]);
            while ($row = $statement->fetch()) {
                // When the site language configuration is in "free" mode, then the page without overlay is fetched
                // (which is kind-of strange for pages, but this is what exec_getQuery() is doing)
                // this means, that $row is a translated page, but hasn't been overlaid. For this reason, we fetch
                // the default translation page again, (which does a ->getPageOverlay() again - doing this on a
                // translated page would result in no record at all)
                if ($row['l10n_parent'] > 0 && !isset($row['_PAGES_OVERLAY'])) {
                    $row = $this->sys_page->getPage($row['l10n_parent'], true);
                }
                $tsfe->sys_page->versionOL('pages', $row, true);
                if (!empty($row)) {
                    // Keep mount point?
                    $mount_info = $this->sys_page->getMountPointInfo($row['uid'], $row);
                    // There is a valid mount point.
                    if (is_array($mount_info) && $mount_info['overlay']) {
                        // Using "getPage" is OK since we need the check for enableFields
                        // AND for type 2 of mount pids we DO require a doktype < 200!
                        $mp_row = $this->sys_page->getPage($mount_info['mount_pid']);
                        if (!empty($mp_row)) {
                            $row = $mp_row;
                            $row['_MP_PARAM'] = $mount_info['MPvar'];
                        } else {
                            // If the mount point could not be fetched with respect
                            // to enableFields, unset the row so it does not become a part of the menu!
                            unset($row);
                        }
                    }
                    // Add external MP params, then the row:
                    if (!empty($row)) {
                        if ($MP) {
                            $row['_MP_PARAM'] = $MP . ($row['_MP_PARAM'] ? ',' . $row['_MP_PARAM'] : '');
                        }
                        $menuItems[] = $this->sys_page->getPageOverlay($row);
                    }
                }
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
        $skippedEnableFields = [];
        if (!empty($this->mconf['showAccessRestrictedPages'])) {
            $skippedEnableFields = ['fe_group' => 1];
        }
        /** @var RelationHandler $loadDB*/
        $loadDB = GeneralUtility::makeInstance(RelationHandler::class);
        $loadDB->setFetchAllFields(true);
        $loadDB->start($specialValue, 'pages');
        $loadDB->additionalWhere['pages'] = $this->sys_page->enableFields('pages', -1, $skippedEnableFields);
        $loadDB->getFromDB();
        $pageLinkBuilder = GeneralUtility::makeInstance(PageLinkBuilder::class, $this->parent_cObj);
        foreach ($loadDB->itemArray as $val) {
            $MP = $pageLinkBuilder->getMountPointParameterFromRootPointMaps((int)$val['id']);
            // Keep mount point?
            $mount_info = $this->sys_page->getMountPointInfo($val['id']);
            // There is a valid mount point.
            if (is_array($mount_info) && $mount_info['overlay']) {
                // Using "getPage" is OK since we need the check for enableFields
                // AND for type 2 of mount pids we DO require a doktype < 200!
                $mp_row = $this->sys_page->getPage($mount_info['mount_pid']);
                if (!empty($mp_row)) {
                    $row = $mp_row;
                    $row['_MP_PARAM'] = $mount_info['MPvar'];
                    // Overlays should already have their full MPvars calculated
                    if ($mount_info['overlay']) {
                        $MP = $pageLinkBuilder->getMountPointParameterFromRootPointMaps((int)$mount_info['mount_pid']);
                        if ($MP) {
                            unset($row['_MP_PARAM']);
                        }
                    }
                } else {
                    // If the mount point could not be fetched with respect to
                    // enableFields, unset the row so it does not become a part of the menu!
                    unset($row);
                }
            } else {
                $row = $loadDB->results['pages'][$val['id']];
            }
            // Add versioning overlay for current page (to respect workspaces)
            if (isset($row) && is_array($row)) {
                $this->sys_page->versionOL('pages', $row, true);
            }
            // Add external MP params, then the row:
            if (isset($row) && is_array($row)) {
                if ($MP) {
                    $row['_MP_PARAM'] = $MP . ($row['_MP_PARAM'] ? ',' . $row['_MP_PARAM'] : '');
                }
                $menuItems[] = $this->sys_page->getPageOverlay($row);
            }
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
            $specialValue = $tsfe->page['uid'];
        }
        $items = GeneralUtility::intExplode(',', $specialValue);
        if (MathUtility::canBeInterpretedAsInteger($this->conf['special.']['depth'])) {
            $depth = MathUtility::forceIntegerInRange($this->conf['special.']['depth'], 1, 20);
        } else {
            $depth = 20;
        }
        // Max number of items
        $limit = MathUtility::forceIntegerInRange($this->conf['special.']['limit'], 0, 100);
        $maxAge = (int)$this->parent_cObj->calc($this->conf['special.']['maxAge']);
        if (!$limit) {
            $limit = 10;
        }
        // *'auto', 'manual', 'tstamp'
        $mode = $this->conf['special.']['mode'];
        // Get id's
        $beginAtLevel = MathUtility::forceIntegerInRange($this->conf['special.']['beginAtLevel'], 0, 100);
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
        // Get sortField (mode)
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
        $extraWhere = ($this->conf['includeNotInMenu'] ? '' : ' AND pages.nav_hide=0') . $this->getDoktypeExcludeWhere();
        if ($this->conf['special.']['excludeNoSearchPages']) {
            $extraWhere .= ' AND pages.no_search=0';
        }
        if ($maxAge > 0) {
            $extraWhere .= ' AND ' . $sortField . '>' . ($GLOBALS['SIM_ACCESS_TIME'] - $maxAge);
        }
        $statement = $this->parent_cObj->exec_getQuery('pages', [
            'pidInList' => '0',
            'uidInList' => $id_list,
            'where' => $sortField . '>=0' . $extraWhere,
            'orderBy' => $sortingField ?: $sortField . ' DESC',
            'max' => $limit
        ]);
        while ($row = $statement->fetch()) {
            // When the site language configuration is in "free" mode, then the page without overlay is fetched
            // (which is kind-of strange for pages, but this is what exec_getQuery() is doing)
            // this means, that $row is a translated page, but hasn't been overlaid. For this reason, we fetch
            // the default translation page again, (which does a ->getPageOverlay() again - doing this on a
            // translated page would result in no record at all)
            if ($row['l10n_parent'] > 0 && !isset($row['_PAGES_OVERLAY'])) {
                $row = $this->sys_page->getPage($row['l10n_parent'], true);
            }
            $tsfe->sys_page->versionOL('pages', $row, true);
            if (is_array($row)) {
                $menuItems[$row['uid']] = $this->sys_page->getPageOverlay($row);
            }
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
            $specialValue = $tsfe->page['uid'];
        }
        if ($this->conf['special.']['setKeywords'] || $this->conf['special.']['setKeywords.']) {
            $kw = isset($this->conf['special.']['setKeywords.']) ? $this->parent_cObj->stdWrap($this->conf['special.']['setKeywords'], $this->conf['special.']['setKeywords.']) : $this->conf['special.']['setKeywords'];
        } else {
            // The page record of the 'value'.
            $value_rec = $this->sys_page->getPage($specialValue);
            $kfieldSrc = $this->conf['special.']['keywordsField.']['sourceField'] ? $this->conf['special.']['keywordsField.']['sourceField'] : 'keywords';
            // keywords.
            $kw = trim($this->parent_cObj->keywords($value_rec[$kfieldSrc]));
        }
        // *'auto', 'manual', 'tstamp'
        $mode = $this->conf['special.']['mode'];
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
        // Depth, limit, extra where
        if (MathUtility::canBeInterpretedAsInteger($this->conf['special.']['depth'])) {
            $depth = MathUtility::forceIntegerInRange($this->conf['special.']['depth'], 0, 20);
        } else {
            $depth = 20;
        }
        // Max number of items
        $limit = MathUtility::forceIntegerInRange($this->conf['special.']['limit'], 0, 100);
        // Start point
        $eLevel = $this->parent_cObj->getKey(
            isset($this->conf['special.']['entryLevel.'])
            ? $this->parent_cObj->stdWrap($this->conf['special.']['entryLevel'], $this->conf['special.']['entryLevel.'])
            : $this->conf['special.']['entryLevel'],
            $this->tmpl->rootLine
        );
        $startUid = (int)$this->tmpl->rootLine[$eLevel]['uid'];
        // Which field is for keywords
        $kfield = 'keywords';
        if ($this->conf['special.']['keywordsField']) {
            [$kfield] = explode(' ', trim($this->conf['special.']['keywordsField']));
        }
        // If there are keywords and the startuid is present
        if ($kw && $startUid) {
            $bA = MathUtility::forceIntegerInRange($this->conf['special.']['beginAtLevel'], 0, 100);
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

            if (count($keyWordsWhereArr) !== 0) {
                $queryBuilder->andWhere($queryBuilder->expr()->orX(...$keyWordsWhereArr));
            }

            if ($this->doktypeExcludeList) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->notIn(
                        'pages.doktype',
                        GeneralUtility::intExplode(',', $this->doktypeExcludeList, true)
                    )
                );
            }

            if (!$this->conf['includeNotInMenu']) {
                $queryBuilder->andWhere($queryBuilder->expr()->eq('pages.nav_hide', 0));
            }

            if ($this->conf['special.']['excludeNoSearchPages']) {
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

            $result = $queryBuilder->execute();
            while ($row = $result->fetch()) {
                $tsfe->sys_page->versionOL('pages', $row, true);
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
        $range = isset($this->conf['special.']['range.'])
            ? $this->parent_cObj->stdWrap($this->conf['special.']['range'], $this->conf['special.']['range.'])
            : $this->conf['special.']['range'];
        $begin_end = explode('|', $range);
        $begin_end[0] = (int)$begin_end[0];
        if (!MathUtility::canBeInterpretedAsInteger($begin_end[1])) {
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
            if ($v_rl['_MP_PARAM'] && $v_rl['_MOUNT_OL']) {
                $rl_MParray[] = $v_rl['_MP_PARAM'];
            }
            // Traverse rootline:
            if ($k_rl >= $beginKey && $k_rl <= $endKey) {
                $temp_key = $k_rl;
                $menuItems[$temp_key] = $this->sys_page->getPage($v_rl['uid']);
                if (!empty($menuItems[$temp_key])) {
                    // If there are no specific target for the page, put the level specific target on.
                    if (!$menuItems[$temp_key]['target']) {
                        $menuItems[$temp_key]['target'] = $this->conf['special.']['targets.'][$k_rl];
                        $menuItems[$temp_key]['_MP_PARAM'] = implode(',', $rl_MParray);
                    }
                } else {
                    unset($menuItems[$temp_key]);
                }
            }
            // For normal mount points, set the variable for next level.
            if ($v_rl['_MP_PARAM'] && !$v_rl['_MOUNT_OL']) {
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
            if ($recArr['up']['pid'] && $value_rec['pid'] != $this->tmpl->rootLine[0]['uid']) {
                // The page record of "index".
                $recArr['index'] = $this->sys_page->getPage($recArr['up']['pid']);
            }
            // check if certain pages should be excluded
            $additionalWhere .= ($this->conf['includeNotInMenu'] ? '' : ' AND pages.nav_hide=0') . $this->getDoktypeExcludeWhere();
            if ($this->conf['special.']['excludeNoSearchPages']) {
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
            if ($this->conf['special.']['items.']['prevnextToSection']) {
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
                if ((int)$this->conf['special.'][$v_b . '.']['uid']) {
                    $recArr[$v_b] = $this->sys_page->getPage((int)$this->conf['special.'][$v_b . '.']['uid']);
                }
                if (is_array($recArr[$v_b])) {
                    $menuItems[$c] = $recArr[$v_b];
                    if ($this->conf['special.'][$v_b . '.']['target']) {
                        $menuItems[$c]['target'] = $this->conf['special.'][$v_b . '.']['target'];
                    }
                    $tmpSpecialFields = $this->conf['special.'][$v_b . '.']['fields.'];
                    if (is_array($tmpSpecialFields)) {
                        foreach ($tmpSpecialFields as $fk => $val) {
                            $menuItems[$c][$fk] = $val;
                        }
                    }
                    $c++;
                }
            }
        }
        return $menuItems;
    }

    /**
     * Analyzes the parameters to find if the link needs a cHash parameter.
     *
     * @param string $queryString
     */
    protected function analyzeCacheHashRequirements($queryString)
    {
        $parameters = GeneralUtility::explodeUrl2Array($queryString);
        if (!empty($parameters)) {
            if (!isset($parameters['id'])) {
                $queryString .= '&id=' . $this->getTypoScriptFrontendController()->id;
            }
            /** @var CacheHashCalculator $cacheHashCalculator */
            $cacheHashCalculator = GeneralUtility::makeInstance(CacheHashCalculator::class);
            $cHashParameters = $cacheHashCalculator->getRelevantParameters($queryString);
            if (count($cHashParameters) > 1) {
                $this->useCacheHash = (
                    $GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter'] ||
                    !isset($parameters['no_cache']) ||
                    !$parameters['no_cache']
                );
            }
        }
    }

    /**
     * Checks if a page is OK to include in the final menu item array. Pages can be excluded if the doktype is wrong,
     * if they are hidden in navigation, have a uid in the list of banned uids etc.
     *
     * @param array $data Array of menu items
     * @param array $banUidArray Array of page uids which are to be excluded
     * @param bool $spacer If set, then the page is a spacer.
     * @return bool Returns TRUE if the page can be safely included.
     *
     * @throws \UnexpectedValueException
     */
    public function filterMenuPages(&$data, $banUidArray, $spacer)
    {
        $includePage = true;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof AbstractMenuFilterPagesHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . AbstractMenuFilterPagesHookInterface::class, 1269877402);
            }
            $includePage = $includePage && $hookObject->processFilter($data, $banUidArray, $spacer, $this);
        }
        if (!$includePage) {
            return false;
        }
        if ($data['_SAFE']) {
            return true;
        }

        if (
            ($this->mconf['SPC'] || !$spacer) // If the spacer-function is not enabled, spacers will not enter the $menuArr
            && (!$data['nav_hide'] || $this->conf['includeNotInMenu']) // Not hidden in navigation
            && !GeneralUtility::inList($this->doktypeExcludeList, $data['doktype']) // Page may not be 'not_in_menu' or 'Backend User Section'
            && !in_array($data['uid'], $banUidArray, false) // not in banned uid's
        ) {
            // Checking if a page should be shown in the menu depending on whether a translation exists or if the default language is disabled
            if ($this->sys_page->isPageSuitableForLanguage($data, $this->getCurrentLanguageAspect())) {
                // Checking if "&L" should be modified so links to non-accessible pages will not happen.
                if ($this->getCurrentLanguageAspect()->getId() > 0 && $this->conf['protectLvar']) {
                    if ($this->conf['protectLvar'] === 'all' || GeneralUtility::hideIfNotTranslated($data['l18n_cfg'])) {
                        $olRec = $this->sys_page->getPageOverlay($data['uid'], $this->getCurrentLanguageAspect()->getId());
                        if (empty($olRec)) {
                            // If no page translation record then page can NOT be accessed in
                            // the language pointed to by "&L" and therefore we protect the link by setting "&L=0"
                            $data['_ADD_GETVARS'] .= '&L=0';
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Generating the per-menu-item configuration arrays based on the settings for item states (NO, RO, ACT, CUR etc)
     * set in ->mconf (config for the current menu object)
     * Basically it will produce an individual array for each menu item based on the item states.
     * BUT in addition the "optionSplit" syntax for the values is ALSO evaluated here so that all property-values
     * are "option-splitted" and the output will thus be resolved.
     * Is called from the "generate" functions in the extension classes. The function is processor intensive due to
     * the option split feature in particular. But since the generate function is not always called
     * (since the ->result array may be cached, see makeMenu) it doesn't hurt so badly.
     *
     * @param int $splitCount Number of menu items in the menu
     * @return array An array with two keys: array($NOconf,$ROconf) - where $NOconf contains the resolved configuration for each item when NOT rolled-over and $ROconf contains the ditto for the mouseover state (if any)
     */
    protected function procesItemStates($splitCount)
    {
        // Prepare normal settings
        if (!is_array($this->mconf['NO.']) && $this->mconf['NO']) {
            // Setting a blank array if NO=1 and there are no properties.
            $this->mconf['NO.'] = [];
        }
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $NOconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['NO.'], $splitCount);
        // Prepare rollOver settings, overriding normal settings
        $ROconf = [];
        if ($this->mconf['RO']) {
            $ROconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['RO.'], $splitCount);
        }
        // Prepare IFSUB settings, overriding normal settings
        // IFSUB is TRUE if there exist submenu items to the current item
        if (!empty($this->mconf['IFSUB'])) {
            $IFSUBconf = null;
            $IFSUBROconf = null;
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState('IFSUB', $key)) {
                    // if this is the first IFSUB element, we must generate IFSUB.
                    if ($IFSUBconf === null) {
                        $IFSUBconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['IFSUB.'], $splitCount);
                        if (!empty($this->mconf['IFSUBRO'])) {
                            $IFSUBROconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['IFSUBRO.'], $splitCount);
                        }
                    }
                    // Substitute normal with ifsub
                    if (isset($IFSUBconf[$key])) {
                        $NOconf[$key] = $IFSUBconf[$key];
                    }
                    // If rollOver on normal, we must apply a state for rollOver on the active
                    if ($ROconf) {
                        // If RollOver on active then apply this
                        $ROconf[$key] = !empty($IFSUBROconf[$key]) ? $IFSUBROconf[$key] : $IFSUBconf[$key];
                    }
                }
            }
        }
        // Prepare active settings, overriding normal settings
        if (!empty($this->mconf['ACT'])) {
            $ACTconf = null;
            $ACTROconf = null;
            // Find active
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState('ACT', $key)) {
                    // If this is the first 'active', we must generate ACT.
                    if ($ACTconf === null) {
                        $ACTconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['ACT.'], $splitCount);
                        // Prepare active rollOver settings, overriding normal active settings
                        if (!empty($this->mconf['ACTRO'])) {
                            $ACTROconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['ACTRO.'], $splitCount);
                        }
                    }
                    // Substitute normal with active
                    if (isset($ACTconf[$key])) {
                        $NOconf[$key] = $ACTconf[$key];
                    }
                    // If rollOver on normal, we must apply a state for rollOver on the active
                    if ($ROconf) {
                        // If RollOver on active then apply this
                        $ROconf[$key] = !empty($ACTROconf[$key]) ? $ACTROconf[$key] : $ACTconf[$key];
                    }
                }
            }
        }
        // Prepare ACT (active)/IFSUB settings, overriding normal settings
        // ACTIFSUB is TRUE if there exist submenu items to the current item and the current item is active
        if (!empty($this->mconf['ACTIFSUB'])) {
            $ACTIFSUBconf = null;
            $ACTIFSUBROconf = null;
            // Find active
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState('ACTIFSUB', $key)) {
                    // If this is the first 'active', we must generate ACTIFSUB.
                    if ($ACTIFSUBconf === null) {
                        $ACTIFSUBconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['ACTIFSUB.'], $splitCount);
                        // Prepare active rollOver settings, overriding normal active settings
                        if (!empty($this->mconf['ACTIFSUBRO'])) {
                            $ACTIFSUBROconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['ACTIFSUBRO.'], $splitCount);
                        }
                    }
                    // Substitute normal with active
                    if (isset($ACTIFSUBconf[$key])) {
                        $NOconf[$key] = $ACTIFSUBconf[$key];
                    }
                    // If rollOver on normal, we must apply a state for rollOver on the active
                    if ($ROconf) {
                        // If RollOver on active then apply this
                        $ROconf[$key] = !empty($ACTIFSUBROconf[$key]) ? $ACTIFSUBROconf[$key] : $ACTIFSUBconf[$key];
                    }
                }
            }
        }
        // Prepare CUR (current) settings, overriding normal settings
        // CUR is TRUE if the current page equals the item here!
        if (!empty($this->mconf['CUR'])) {
            $CURconf = null;
            $CURROconf = null;
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState('CUR', $key)) {
                    // if this is the first 'current', we must generate CUR. Basically this control is just inherited
                    // from the other implementations as current would only exist one time and that's it
                    // (unless you use special-features of HMENU)
                    if ($CURconf === null) {
                        $CURconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['CUR.'], $splitCount);
                        if (!empty($this->mconf['CURRO'])) {
                            $CURROconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['CURRO.'], $splitCount);
                        }
                    }
                    // Substitute normal with current
                    if (isset($CURconf[$key])) {
                        $NOconf[$key] = $CURconf[$key];
                    }
                    // If rollOver on normal, we must apply a state for rollOver on the active
                    if ($ROconf) {
                        // If RollOver on active then apply this
                        $ROconf[$key] = !empty($CURROconf[$key]) ? $CURROconf[$key] : $CURconf[$key];
                    }
                }
            }
        }
        // Prepare CUR (current)/IFSUB settings, overriding normal settings
        // CURIFSUB is TRUE if there exist submenu items to the current item and the current page equals the item here!
        if (!empty($this->mconf['CURIFSUB'])) {
            $CURIFSUBconf = null;
            $CURIFSUBROconf = null;
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState('CURIFSUB', $key)) {
                    // If this is the first 'current', we must generate CURIFSUB.
                    if ($CURIFSUBconf === null) {
                        $CURIFSUBconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['CURIFSUB.'], $splitCount);
                        // Prepare current rollOver settings, overriding normal current settings
                        if (!empty($this->mconf['CURIFSUBRO'])) {
                            $CURIFSUBROconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['CURIFSUBRO.'], $splitCount);
                        }
                    }
                    // Substitute normal with active
                    if ($CURIFSUBconf[$key]) {
                        $NOconf[$key] = $CURIFSUBconf[$key];
                    }
                    // If rollOver on normal, we must apply a state for rollOver on the current
                    if ($ROconf) {
                        // If RollOver on current then apply this
                        $ROconf[$key] = !empty($CURIFSUBROconf[$key]) ? $CURIFSUBROconf[$key] : $CURIFSUBconf[$key];
                    }
                }
            }
        }
        // Prepare active settings, overriding normal settings
        if (!empty($this->mconf['USR'])) {
            $USRconf = null;
            $USRROconf = null;
            // Find active
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState('USR', $key)) {
                    // if this is the first active, we must generate USR.
                    if ($USRconf === null) {
                        $USRconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['USR.'], $splitCount);
                        // Prepare active rollOver settings, overriding normal active settings
                        if (!empty($this->mconf['USRRO'])) {
                            $USRROconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['USRRO.'], $splitCount);
                        }
                    }
                    // Substitute normal with active
                    if ($USRconf[$key]) {
                        $NOconf[$key] = $USRconf[$key];
                    }
                    // If rollOver on normal, we must apply a state for rollOver on the active
                    if ($ROconf) {
                        // If RollOver on active then apply this
                        $ROconf[$key] = !empty($USRROconf[$key]) ? $USRROconf[$key] : $USRconf[$key];
                    }
                }
            }
        }
        // Prepare spacer settings, overriding normal settings
        if (!empty($this->mconf['SPC'])) {
            $SPCconf = null;
            // Find spacers
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState('SPC', $key)) {
                    // If this is the first spacer, we must generate SPC.
                    if ($SPCconf === null) {
                        $SPCconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['SPC.'], $splitCount);
                    }
                    // Substitute normal with spacer
                    if (isset($SPCconf[$key])) {
                        $NOconf[$key] = $SPCconf[$key];
                    }
                }
            }
        }
        // Prepare Userdefined settings
        if (!empty($this->mconf['USERDEF1'])) {
            $USERDEF1conf = null;
            $USERDEF1ROconf = null;
            // Find active
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState('USERDEF1', $key)) {
                    // If this is the first active, we must generate USERDEF1.
                    if ($USERDEF1conf === null) {
                        $USERDEF1conf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['USERDEF1.'], $splitCount);
                        // Prepare active rollOver settings, overriding normal active settings
                        if (!empty($this->mconf['USERDEF1RO'])) {
                            $USERDEF1ROconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['USERDEF1RO.'], $splitCount);
                        }
                    }
                    // Substitute normal with active
                    if (isset($USERDEF1conf[$key])) {
                        $NOconf[$key] = $USERDEF1conf[$key];
                    }
                    // If rollOver on normal, we must apply a state for rollOver on the active
                    if ($ROconf) {
                        // If RollOver on active then apply this
                        $ROconf[$key] = !empty($USERDEF1ROconf[$key]) ? $USERDEF1ROconf[$key] : $USERDEF1conf[$key];
                    }
                }
            }
        }
        // Prepare Userdefined settings
        if (!empty($this->mconf['USERDEF2'])) {
            $USERDEF2conf = null;
            $USERDEF2ROconf = null;
            // Find active
            foreach ($NOconf as $key => $val) {
                if ($this->isItemState('USERDEF2', $key)) {
                    // If this is the first active, we must generate USERDEF2.
                    if ($USERDEF2conf === null) {
                        $USERDEF2conf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['USERDEF2.'], $splitCount);
                        // Prepare active rollOver settings, overriding normal active settings
                        if (!empty($this->mconf['USERDEF2RO'])) {
                            $USERDEF2ROconf = $typoScriptService->explodeConfigurationForOptionSplit((array)$this->mconf['USERDEF2RO.'], $splitCount);
                        }
                    }
                    // Substitute normal with active
                    if (isset($USERDEF2conf[$key])) {
                        $NOconf[$key] = $USERDEF2conf[$key];
                    }
                    // If rollOver on normal, we must apply a state for rollOver on the active
                    if ($ROconf) {
                        // If RollOver on active then apply this
                        $ROconf[$key] = !empty($USERDEF2ROconf[$key]) ? $USERDEF2ROconf[$key] : $USERDEF2conf[$key];
                    }
                }
            }
        }
        return [$NOconf, $ROconf];
    }

    /**
     * Creates the URL, target and onclick values for the menu item link. Returns them in an array as key/value pairs for <A>-tag attributes
     * This function doesn't care about the url, because if we let the url be redirected, it will be logged in the stat!!!
     *
     * @param int $key Pointer to a key in the $this->menuArr array where the value for that key represents the menu item we are linking to (page record)
     * @param string $altTarget Alternative target
     * @param string $typeOverride Alternative type
     * @return array Returns an array with A-tag attributes as key/value pairs (HREF, TARGET and onClick)
     */
    protected function link($key, $altTarget = '', $typeOverride = '')
    {
        $runtimeCache = $this->getRuntimeCache();
        $MP_var = $this->getMPvar($key);
        $cacheId = 'menu-generated-links-' . md5($key . $altTarget . $typeOverride . $MP_var . serialize($this->menuArr[$key]));
        $runtimeCachedLink = $runtimeCache->get($cacheId);
        if ($runtimeCachedLink !== false) {
            return $runtimeCachedLink;
        }

        // Mount points:
        $MP_params = $MP_var ? '&MP=' . rawurlencode($MP_var) : '';
        // Setting override ID
        if ($this->mconf['overrideId'] || $this->menuArr[$key]['overrideId']) {
            $overrideArray = [];
            // If a user script returned the value overrideId in the menu array we use that as page id
            $overrideArray['uid'] = $this->mconf['overrideId'] ?: $this->menuArr[$key]['overrideId'];
            $overrideArray['alias'] = '';
            // Clear MP parameters since ID was changed.
            $MP_params = '';
        } else {
            $overrideArray = '';
        }
        // Setting main target:
        if ($altTarget) {
            $mainTarget = $altTarget;
        } elseif ($this->mconf['target.']) {
            $mainTarget = $this->parent_cObj->stdWrap($this->mconf['target'], $this->mconf['target.']);
        } else {
            $mainTarget = $this->mconf['target'];
        }
        // Creating link:
        $addParams = $this->mconf['addParams'] . $MP_params;
        if ($this->mconf['collapse'] && $this->isActive($this->menuArr[$key]['uid'], $this->getMPvar($key))) {
            $thePage = $this->sys_page->getPage($this->menuArr[$key]['pid']);
            $addParams .= $this->menuArr[$key]['_ADD_GETVARS'];
            $LD = $this->menuTypoLink($thePage, $mainTarget, '', '', $overrideArray, $addParams, $typeOverride);
        } else {
            $addParams .= $this->I['val']['additionalParams'] . $this->menuArr[$key]['_ADD_GETVARS'];
            $LD = $this->menuTypoLink($this->menuArr[$key], $mainTarget, '', '', $overrideArray, $addParams, $typeOverride);
        }
        // Override default target configuration if option is set
        if ($this->menuArr[$key]['target']) {
            $LD['target'] = $this->menuArr[$key]['target'];
        }
        // Override URL if using "External URL"
        if ($this->menuArr[$key]['doktype'] == PageRepository::DOKTYPE_LINK) {
            $externalUrl = $this->getSysPage()->getExtURL($this->menuArr[$key]);
            // Create link using typolink (concerning spamProtectEmailAddresses) for email links
            $LD['totalURL'] = $this->parent_cObj->typoLink_URL(['parameter' => $externalUrl]);
            // Links to emails should not have any target
            if (stripos($externalUrl, 'mailto:') === 0) {
                $LD['target'] = '';
            // use external target for the URL
            } elseif (empty($LD['target']) && !empty($this->getTypoScriptFrontendController()->extTarget)) {
                $LD['target'] = $this->getTypoScriptFrontendController()->extTarget;
            }
        }

        $tsfe = $this->getTypoScriptFrontendController();

        // Override url if current page is a shortcut
        $shortcut = null;
        if ($this->menuArr[$key]['doktype'] == PageRepository::DOKTYPE_SHORTCUT && $this->menuArr[$key]['shortcut_mode'] != PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE) {
            $menuItem = $this->menuArr[$key];
            try {
                $shortcut = $tsfe->sys_page->getPageShortcut(
                    $menuItem['shortcut'],
                    $menuItem['shortcut_mode'],
                    $menuItem['uid'],
                    20,
                    [],
                    true
                );
            } catch (\Exception $ex) {
            }
            if (!is_array($shortcut)) {
                $runtimeCache->set($cacheId, []);
                return [];
            }
            // Only setting url, not target
            $LD['totalURL'] = $this->parent_cObj->typoLink_URL([
                'parameter' => $shortcut['uid'],
                'language' => 'current',
                'additionalParams' => $addParams . $this->I['val']['additionalParams'] . $menuItem['_ADD_GETVARS'],
                'linkAccessRestrictedPages' => !empty($this->mconf['showAccessRestrictedPages'])
            ]);
        }
        if ($shortcut) {
            $pageData = $shortcut;
            $pageData['_SHORTCUT_PAGE_UID'] = $this->menuArr[$key]['uid'];
        } else {
            $pageData = $this->menuArr[$key];
        }
        // Manipulation in case of access restricted pages:
        $this->changeLinksForAccessRestrictedPages($LD, $pageData, $mainTarget, $typeOverride);
        // Overriding URL / Target if set to do so:
        if ($this->menuArr[$key]['_OVERRIDE_HREF']) {
            $LD['totalURL'] = $this->menuArr[$key]['_OVERRIDE_HREF'];
            if ($this->menuArr[$key]['_OVERRIDE_TARGET']) {
                $LD['target'] = $this->menuArr[$key]['_OVERRIDE_TARGET'];
            }
        }
        // OnClick open in windows.
        $onClick = '';
        if ($this->mconf['JSWindow']) {
            $conf = $this->mconf['JSWindow.'];
            $url = $LD['totalURL'];
            $LD['totalURL'] = '#';
            $onClick = 'openPic('
                . GeneralUtility::quoteJSvalue($tsfe->baseUrlWrap($url)) . ','
                . '\'' . ($conf['newWindow'] ? md5($url) : 'theNewPage') . '\','
                . GeneralUtility::quoteJSvalue($conf['params']) . '); return false;';
            $tsfe->setJS('openPic');
        }
        // look for type and popup
        // following settings are valid in field target:
        // 230								will add type=230 to the link
        // 230 500x600						will add type=230 to the link and open in popup window with 500x600 pixels
        // 230 _blank						will add type=230 to the link and open with target "_blank"
        // 230x450:resizable=0,location=1	will open in popup window with 500x600 pixels with settings "resizable=0,location=1"
        $matches = [];
        $targetIsType = $LD['target'] && MathUtility::canBeInterpretedAsInteger($LD['target']) ? (int)$LD['target'] : false;
        if (preg_match('/([0-9]+[\\s])?(([0-9]+)x([0-9]+))?(:.+)?/s', $LD['target'], $matches) || $targetIsType) {
            // has type?
            if ((int)$matches[1] || $targetIsType) {
                $LD['totalURL'] .= (strpos($LD['totalURL'], '?') === false ? '?' : '&') . 'type=' . ($targetIsType ?: (int)$matches[1]);
                $LD['target'] = $targetIsType ? '' : trim(substr($LD['target'], strlen($matches[1]) + 1));
            }
            // Open in popup window?
            if ($matches[3] && $matches[4]) {
                $JSparamWH = 'width=' . $matches[3] . ',height=' . $matches[4] . ($matches[5] ? ',' . substr($matches[5], 1) : '');
                $onClick = 'openPic('
                    . GeneralUtility::quoteJSvalue($tsfe->baseUrlWrap($LD['totalURL']))
                    . ',\'FEopenLink\',' . GeneralUtility::quoteJSvalue($JSparamWH) . ');return false;';
                $tsfe->setJS('openPic');
                $LD['target'] = '';
            }
        }
        // out:
        $list = [];
        // Added this check: What it does is to enter the baseUrl (if set, which it should for "realurl" based sites)
        // as URL if the calculated value is empty. The problem is that no link is generated with a blank URL
        // and blank URLs might appear when the realurl encoding is used and a link to the frontpage is generated.
        $list['HREF'] = (string)$LD['totalURL'] !== '' ? $LD['totalURL'] : $tsfe->baseUrl;
        $list['TARGET'] = $LD['target'];
        $list['onClick'] = $onClick;
        $runtimeCache->set($cacheId, $list);
        return $list;
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
     * Will change $LD (passed by reference) if the page is access restricted
     *
     * @param array $LD The array from the linkData() function
     * @param array $page Page array
     * @param string $mainTarget Main target value
     * @param string $typeOverride Type number override if any
     */
    protected function changeLinksForAccessRestrictedPages(&$LD, $page, $mainTarget, $typeOverride)
    {
        // If access restricted pages should be shown in menus, change the link of such pages to link to a redirection page:
        if ($this->mconf['showAccessRestrictedPages'] && $this->mconf['showAccessRestrictedPages'] !== 'NONE' && !$this->getTypoScriptFrontendController()->checkPageGroupAccess($page)) {
            $thePage = $this->sys_page->getPage($this->mconf['showAccessRestrictedPages']);
            $addParams = str_replace(
                [
                    '###RETURN_URL###',
                    '###PAGE_ID###'
                ],
                [
                    rawurlencode($LD['totalURL']),
                    $page['_SHORTCUT_PAGE_UID'] ?? $page['uid']
                ],
                $this->mconf['showAccessRestrictedPages.']['addParams']
            );
            $LD = $this->menuTypoLink($thePage, $mainTarget, '', '', '', $addParams, $typeOverride);
        }
    }

    /**
     * Creates a submenu level to the current level - if configured for.
     *
     * @param int $uid Page id of the current page for which a submenu MAY be produced (if conditions are met)
     * @param string $objSuffix Object prefix, see ->start()
     * @return string HTML content of the submenu
     */
    protected function subMenu($uid, $objSuffix = '')
    {
        // Setting alternative menu item array if _SUB_MENU has been defined in the current ->menuArr
        $altArray = '';
        if (is_array($this->menuArr[$this->I['key']]['_SUB_MENU']) && !empty($this->menuArr[$this->I['key']]['_SUB_MENU'])) {
            $altArray = $this->menuArr[$this->I['key']]['_SUB_MENU'];
        }
        // Make submenu if the page is the next active
        $menuType = $this->conf[($this->menuNumber + 1) . $objSuffix];
        // stdWrap for expAll
        if (isset($this->mconf['expAll.'])) {
            $this->mconf['expAll'] = $this->parent_cObj->stdWrap($this->mconf['expAll'], $this->mconf['expAll.']);
        }
        if (($this->mconf['expAll'] || $this->isNext($uid, $this->getMPvar($this->I['key'])) || is_array($altArray)) && !$this->mconf['sectionIndex']) {
            try {
                $menuObjectFactory = GeneralUtility::makeInstance(MenuContentObjectFactory::class);
                /** @var AbstractMenuContentObject $submenu */
                $submenu = $menuObjectFactory->getMenuObjectByType($menuType);
                $submenu->entryLevel = $this->entryLevel + 1;
                $submenu->rL_uidRegister = $this->rL_uidRegister;
                $submenu->MP_array = $this->MP_array;
                if ($this->menuArr[$this->I['key']]['_MP_PARAM']) {
                    $submenu->MP_array[] = $this->menuArr[$this->I['key']]['_MP_PARAM'];
                }
                // Especially scripts that build the submenu needs the parent data
                $submenu->parent_cObj = $this->parent_cObj;
                $submenu->setParentMenu($this->menuArr, $this->I['key']);
                // Setting alternativeMenuTempArray (will be effective only if an array)
                if (is_array($altArray)) {
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
            } catch (Exception\NoSuchMenuTypeException $e) {
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
    protected function isNext($uid, $MPvar = '')
    {
        // Check for always active PIDs:
        if (!empty($this->alwaysActivePIDlist) && in_array((int)$uid, $this->alwaysActivePIDlist, true)) {
            return true;
        }
        $testUid = $uid . ($MPvar ? ':' . $MPvar : '');
        if ($uid && $testUid == $this->nextActive) {
            return true;
        }
        return false;
    }

    /**
     * Returns TRUE if the page with UID $uid is active (in the current rootline)
     *
     * @param int $uid Page uid to evaluate.
     * @param string $MPvar MPvar for the current position of item.
     * @return bool TRUE if page with $uid is active
     */
    protected function isActive($uid, $MPvar = '')
    {
        // Check for always active PIDs:
        if (!empty($this->alwaysActivePIDlist) && in_array((int)$uid, $this->alwaysActivePIDlist, true)) {
            return true;
        }
        $testUid = $uid . ($MPvar ? ':' . $MPvar : '');
        if ($uid && in_array('ITEM:' . $testUid, $this->rL_uidRegister, true)) {
            return true;
        }
        return false;
    }

    /**
     * Returns TRUE if the page with UID $uid is the CURRENT page (equals $this->getTypoScriptFrontendController()->id)
     *
     * @param int $uid Page uid to evaluate.
     * @param string $MPvar MPvar for the current position of item.
     * @return bool TRUE if page $uid = $this->getTypoScriptFrontendController()->id
     */
    protected function isCurrent($uid, $MPvar = '')
    {
        $testUid = $uid . ($MPvar ? ':' . $MPvar : '');
        return $uid && end($this->rL_uidRegister) === 'ITEM:' . $testUid;
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
        $cacheId = 'menucontentobject-is-submenu-decision-' . $uid;
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
            if (GeneralUtility::inList($this->doktypeExcludeList, $theRec['doktype'] ?? '')) {
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
            if (!$languageId && GeneralUtility::hideIfDefaultLanguage($theRec['l18n_cfg'] ?? 0)) {
                continue;
            }
            // No valid subpage if the alternative language should be shown and the page settings
            // are requiring a valid overlay but it doesn't exists
            $hideIfNotTranslated = GeneralUtility::hideIfNotTranslated($theRec['l18n_cfg'] ?? null);
            if ($languageId && $hideIfNotTranslated && !$theRec['_PAGES_OVERLAY']) {
                continue;
            }
            // No valid subpage if the subpage is banned by excludeUidList
            if (in_array($theRec['uid'], $bannedUids)) {
                continue;
            }
            $hasSubPages = true;
            break;
        }
        $runtimeCache->set($cacheId, ['result' => $hasSubPages]);
        return $hasSubPages;
    }

    /**
     * Used by procesItemStates() to evaluate if a menu item (identified by $key) is in a certain state.
     *
     * @param string $kind The item state to evaluate (SPC, IFSUB, ACT etc... but no xxxRO states of course)
     * @param int $key Key pointing to menu item from ->menuArr
     * @return bool Returns TRUE if state matches
     * @see procesItemStates()
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
                    $natVal = $this->isSubMenu($this->menuArr[$key]['uid']);
                    break;
                case 'ACT':
                    $natVal = $this->isActive($this->menuArr[$key]['uid'], $this->getMPvar($key));
                    break;
                case 'ACTIFSUB':
                    $natVal = $this->isActive($this->menuArr[$key]['uid'], $this->getMPvar($key)) && $this->isSubMenu($this->menuArr[$key]['uid']);
                    break;
                case 'CUR':
                    $natVal = $this->isCurrent($this->menuArr[$key]['uid'], $this->getMPvar($key));
                    break;
                case 'CURIFSUB':
                    $natVal = $this->isCurrent($this->menuArr[$key]['uid'], $this->getMPvar($key)) && $this->isSubMenu($this->menuArr[$key]['uid']);
                    break;
                case 'USR':
                    $natVal = (bool)$this->menuArr[$key]['fe_group'];
                    break;
            }
        }
        return $natVal;
    }

    /**
     * Creates an access-key for a TMENU/GMENU menu item based on the menu item titles first letter
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
                $tsfe->accessKey[$key] = 1;
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
            $funcConf = $this->mconf[$mConfKey . '.'];
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
        $params = trim($this->I['val']['ATagParams']) . $this->I['accessKey']['code'];
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
            if ($this->menuArr[$key]['_MP_PARAM']) {
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
        return $this->doktypeExcludeList ? ' AND pages.doktype NOT IN (' . $this->doktypeExcludeList . ')' : '';
    }

    /**
     * Returns an array of banned UIDs (from excludeUidList)
     *
     * @return array Array of banned UIDs
     */
    protected function getBannedUids()
    {
        $excludeUidList = isset($this->conf['excludeUidList.'])
            ? $this->parent_cObj->stdWrap($this->conf['excludeUidList'], $this->conf['excludeUidList.'])
            : $this->conf['excludeUidList'];

        if (!trim($excludeUidList)) {
            return [];
        }

        $banUidList = str_replace('current', $this->getTypoScriptFrontendController()->page['uid'] ?? null, $excludeUidList);
        return GeneralUtility::intExplode(',', $banUidList);
    }

    /**
     * Calls typolink to create menu item links.
     *
     * @param array $page Page record (uid points where to link to)
     * @param string $oTarget Target frame/window
     * @param bool $no_cache TRUE if caching should be disabled
     * @param string $script Alternative script name (unused)
     * @param array|string $overrideArray Array to override values in $page, empty string to skip override
     * @param string $addParams Parameters to add to URL
     * @param int|string $typeOverride "type" value, empty string means "not set"
     * @return array See linkData
     */
    protected function menuTypoLink($page, $oTarget, $no_cache, $script, $overrideArray = '', $addParams = '', $typeOverride = '')
    {
        $conf = [
            'parameter' => is_array($overrideArray) && $overrideArray['uid'] ? $overrideArray['uid'] : $page['uid']
        ];
        if (MathUtility::canBeInterpretedAsInteger($typeOverride)) {
            $conf['parameter'] .= ',' . (int)$typeOverride;
        }
        if ($addParams) {
            $conf['additionalParams'] = $addParams;
        }

        // Ensure that the typolink gets an info which language was actually requested. The $page record could be the record
        // from page translation language=1 as fallback but page translation language=2 was requested. Search for
        // "_PAGES_OVERLAY_REQUESTEDLANGUAGE" for more details
        if ($page['_PAGES_OVERLAY_REQUESTEDLANGUAGE'] ?? 0) {
            $conf['language'] = $page['_PAGES_OVERLAY_REQUESTEDLANGUAGE'];
        }
        if ($no_cache) {
            $conf['no_cache'] = true;
        } elseif ($this->useCacheHash) {
            $conf['useCacheHash'] = true;
        }
        if ($oTarget) {
            $conf['target'] = $oTarget;
        }
        if ($page['sectionIndex_uid'] ?? false) {
            $conf['section'] = $page['sectionIndex_uid'];
        }
        $conf['linkAccessRestrictedPages'] = !empty($this->mconf['showAccessRestrictedPages']);
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
        $tsfe = $this->getTypoScriptFrontendController();
        $configuration = $this->mconf['sectionIndex.'] ?? [];
        $useColPos = 0;
        if (trim($configuration['useColPos'] ?? '') !== ''
            || (isset($configuration['useColPos.']) && is_array($configuration['useColPos.']))
        ) {
            $useColPos = $tsfe->cObj->stdWrap($configuration['useColPos'] ?? '', $configuration['useColPos.'] ?? []);
            $useColPos = (int)$useColPos;
        }
        $selectSetup = [
            'pidInList' => $pid,
            'orderBy' => $altSortField,
            'languageField' => 'sys_language_uid',
            'where' => ''
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
        while ($row = $statement->fetch()) {
            $this->sys_page->versionOL('tt_content', $row);
            if ($this->getCurrentLanguageAspect()->doOverlays() && $basePageRow['_PAGES_OVERLAY_LANGUAGE']) {
                $row = $this->sys_page->getRecordOverlay(
                    'tt_content',
                    $row,
                    $basePageRow['_PAGES_OVERLAY_LANGUAGE'],
                    $this->getCurrentLanguageAspect()->getOverlayType() === LanguageAspect::OVERLAYS_MIXED ? '1' : 'hideNonTranslated'
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
     * @return \TYPO3\CMS\Frontend\Page\PageRepository
     */
    public function getSysPage()
    {
        return $this->sys_page;
    }

    /**
     * Returns the parent content object
     *
     * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
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
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
    }

    /**
     * @return \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected function getRuntimeCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
    }

    /**
     * Returns the currently configured "site" if a site is configured (= resolved) in the current request.
     *
     * @return SiteInterface
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    protected function getCurrentSite(): SiteInterface
    {
        $matcher = GeneralUtility::makeInstance(SiteMatcher::class);
        return $matcher->matchByPageId((int)$this->getTypoScriptFrontendController()->id);
    }

    /**
     * Set the parentMenuArr and key to provide the parentMenu informations to the
     * subMenu, special fur IProcFunc and itemArrayProcFunc user functions.
     *
     * @param array $menuArr
     * @param int $menuItemKey
     * @internal
     */
    public function setParentMenu(array $menuArr = [], $menuItemKey)
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
     * Check if there is an valid parentMenuArr.
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
     * Check if we have an parentMenutArrItemKey
     */
    protected function hasParentMenuItemKey()
    {
        return null !== $this->parentMenuArrItemKey;
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
        // check if we have an parentMenuItem and if it is an array
        if ($this->hasParentMenuItem()
            && is_array($this->getParentMenuArr()[$this->parentMenuArrItemKey])
        ) {
            return $this->getParentMenuArr()[$this->parentMenuArrItemKey];
        }

        return null;
    }
}
