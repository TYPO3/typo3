<?php
namespace TYPO3\CMS\Frontend\Plugin;

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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Base class for frontend plugins
 * Most legacy frontend plugins are extension classes of this one.
 * This class contains functions which assists these plugins in creating lists, searching, displaying menus, page-browsing (next/previous/1/2/3) and handling links.
 * Functions are all prefixed "pi_" which is reserved for this class. Those functions can of course be overridden in the extension classes (that is the point...)
 */
class AbstractPlugin
{
    /**
     * The backReference to the mother cObj object set at call time
     *
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * Should be same as classname of the plugin, used for CSS classes, variables
     *
     * @var string
     */
    public $prefixId;

    /**
     * Path to the plugin class script relative to extension directory, eg. 'pi1/class.tx_newfaq_pi1.php'
     *
     * @var string
     */
    public $scriptRelPath;

    /**
     * Extension key.
     *
     * @var string
     */
    public $extKey;

    /**
     * This is the incoming array by name $this->prefixId merged between POST and GET, POST taking precedence.
     * Eg. if the class name is 'tx_myext'
     * then the content of this array will be whatever comes into &tx_myext[...]=...
     *
     * @var array
     */
    public $piVars = [
        'pointer' => '',
        // Used as a pointer for lists
        'mode' => '',
        // List mode
        'sword' => '',
        // Search word
        'sort' => ''
    ];

    /**
     * Local pointer variabe array.
     * Holds pointer information for the MVC like approach Kasper
     * initially proposed
     *
     * @var array
     */
    public $internal = ['res_count' => 0, 'results_at_a_time' => 20, 'maxPages' => 10, 'currentRow' => [], 'currentTable' => ''];

    /**
     * Local Language content
     *
     * @var array
     */
    public $LOCAL_LANG = [];

    /**
     * Contains those LL keys, which have been set to (empty) in TypoScript.
     * This is necessary, as we cannot distinguish between a nonexisting
     * translation and a label that has been cleared by TS.
     * In both cases ['key'][0]['target'] is "".
     *
     * @var array
     */
    protected $LOCAL_LANG_UNSET = [];

    /**
     * Flag that tells if the locallang file has been fetch (or tried to
     * be fetched) already.
     *
     * @var bool
     */
    public $LOCAL_LANG_loaded = false;

    /**
     * Pointer to the language to use.
     *
     * @var string
     */
    public $LLkey = 'default';

    /**
     * Pointer to alternative fall-back language to use.
     *
     * @var string
     */
    public $altLLkey = '';

    /**
     * You can set this during development to some value that makes it
     * easy for you to spot all labels that ARe delivered by the getLL function.
     *
     * @var string
     */
    public $LLtestPrefix = '';

    /**
     * Save as LLtestPrefix, but additional prefix for the alternative value
     * in getLL() function calls
     *
     * @var string
     */
    public $LLtestPrefixAlt = '';

    /**
     * @var string
     */
    public $pi_isOnlyFields = 'mode,pointer';

    /**
     * @var int
     */
    public $pi_alwaysPrev = 0;

    /**
     * @var int
     */
    public $pi_lowerThan = 5;

    /**
     * @var string
     */
    public $pi_moreParams = '';

    /**
     * @var string
     */
    public $pi_listFields = '*';

    /**
     * @var array
     */
    public $pi_autoCacheFields = [];

    /**
     * @var bool
     */
    public $pi_autoCacheEn = false;

    /**
     * If set, then links are
     * 1) not using cHash and
     * 2) not allowing pages to be cached. (Set this for all USER_INT plugins!)
     *
     * @var bool
     */
    public $pi_USER_INT_obj = false;

    /**
     * If set, then caching is disabled if piVars are incoming while
     * no cHash was set (Set this for all USER plugins!)
     *
     * @var bool
     */
    public $pi_checkCHash = false;

    /**
     * Should normally be set in the main function with the TypoScript content passed to the method.
     *
     * $conf[LOCAL_LANG][_key_] is reserved for Local Language overrides.
     * $conf[userFunc] reserved for setting up the USER / USER_INT object. See TSref
     *
     * @var array
     */
    public $conf = [];

    /**
     * internal, don't mess with...
     *
     * @var ContentObjectRenderer
     */
    public $pi_EPtemp_cObj;

    /**
     * @var int
     */
    public $pi_tmpPageId = 0;

    /**
     * Property for accessing TypoScriptFrontendController centrally
     *
     * @var TypoScriptFrontendController
     */
    protected $frontendController;

    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;

    /**
     * Class Constructor (true constructor)
     * Initializes $this->piVars if $this->prefixId is set to any value
     * Will also set $this->LLkey based on the config.language setting.
     *
     * @param null $_ unused,
     * @param TypoScriptFrontendController $frontendController
     */
    public function __construct($_ = null, TypoScriptFrontendController $frontendController = null)
    {
        $this->frontendController = $frontendController ?: $GLOBALS['TSFE'];
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        // Setting piVars:
        if ($this->prefixId) {
            $this->piVars = GeneralUtility::_GPmerged($this->prefixId);
            // cHash mode check
            // IMPORTANT FOR CACHED PLUGINS (USER cObject): As soon as you generate cached plugin output which
            // depends on parameters (eg. seeing the details of a news item) you MUST check if a cHash value is set.
            // Background: The function call will check if a cHash parameter was sent with the URL because only if
            // it was the page may be cached. If no cHash was found the function will simply disable caching to
            // avoid unpredictable caching behaviour. In any case your plugin can generate the expected output and
            // the only risk is that the content may not be cached. A missing cHash value is considered a mistake
            // in the URL resulting from either URL manipulation, "realurl" "grayzones" etc. The problem is rare
            // (more frequent with "realurl") but when it occurs it is very puzzling!
            if ($this->pi_checkCHash && !empty($this->piVars)) {
                $this->frontendController->reqCHash();
            }
        }
        $siteLanguage = $this->getCurrentSiteLanguage();
        if ($siteLanguage) {
            $this->LLkey = $siteLanguage->getTypo3Language();
        } elseif (!empty($this->frontendController->config['config']['language'])) {
            $this->LLkey = $this->frontendController->config['config']['language'];
        }

        if (empty($this->frontendController->config['config']['language_alt'])) {
            /** @var Locales $locales */
            $locales = GeneralUtility::makeInstance(Locales::class);
            if (in_array($this->LLkey, $locales->getLocales())) {
                $this->altLLkey = '';
                foreach ($locales->getLocaleDependencies($this->LLkey) as $language) {
                    $this->altLLkey .= $language . ',';
                }
                $this->altLLkey = rtrim($this->altLLkey, ',');
            }
        } else {
            $this->altLLkey = $this->frontendController->config['config']['language_alt'];
        }
    }

    /**
     * Recursively looks for stdWrap and executes it
     *
     * @param array $conf Current section of configuration to work on
     * @param int $level Current level being processed (currently just for tracking; no limit enforced)
     * @return array Current section of configuration after stdWrap applied
     */
    protected function applyStdWrapRecursive(array $conf, $level = 0)
    {
        foreach ($conf as $key => $confNextLevel) {
            if (strpos($key, '.') !== false) {
                $key = substr($key, 0, -1);

                // descend into all non-stdWrap-subelements first
                foreach ($confNextLevel as $subKey => $subConfNextLevel) {
                    if (is_array($subConfNextLevel) && strpos($subKey, '.') !== false && $subKey !== 'stdWrap.') {
                        $conf[$key . '.'] = $this->applyStdWrapRecursive($confNextLevel, $level + 1);
                    }
                }

                // now for stdWrap
                foreach ($confNextLevel as $subKey => $subConfNextLevel) {
                    if (is_array($subConfNextLevel) && $subKey === 'stdWrap.') {
                        $conf[$key] = $this->cObj->stdWrap($conf[$key] ?? '', $conf[$key . '.']['stdWrap.'] ?? []);
                        unset($conf[$key . '.']['stdWrap.']);
                        if (empty($conf[$key . '.'])) {
                            unset($conf[$key . '.']);
                        }
                    }
                }
            }
        }
        return $conf;
    }

    /**
     * If internal TypoScript property "_DEFAULT_PI_VARS." is set then it will merge the current $this->piVars array onto these default values.
     */
    public function pi_setPiVarDefaults()
    {
        if (isset($this->conf['_DEFAULT_PI_VARS.']) && is_array($this->conf['_DEFAULT_PI_VARS.'])) {
            $this->conf['_DEFAULT_PI_VARS.'] = $this->applyStdWrapRecursive($this->conf['_DEFAULT_PI_VARS.']);
            $tmp = $this->conf['_DEFAULT_PI_VARS.'];
            ArrayUtility::mergeRecursiveWithOverrule($tmp, is_array($this->piVars) ? $this->piVars : []);
            $this->piVars = $tmp;
        }
    }

    /***************************
     *
     * Link functions
     *
     **************************/
    /**
     * Get URL to some page.
     * Returns the URL to page $id with $target and an array of additional url-parameters, $urlParameters
     * Simple example: $this->pi_getPageLink(123) to get the URL for page-id 123.
     *
     * The function basically calls $this->cObj->getTypoLink_URL()
     *
     * @param int $id Page id
     * @param string $target Target value to use. Affects the &type-value of the URL, defaults to current.
     * @param array|string $urlParameters As an array key/value pairs represent URL parameters to set. Values NOT URL-encoded yet, keys should be URL-encoded if needed. As a string the parameter is expected to be URL-encoded already.
     * @return string The resulting URL
     * @see pi_linkToPage()
     * @see ContentObjectRenderer->getTypoLink()
     */
    public function pi_getPageLink($id, $target = '', $urlParameters = [])
    {
        return $this->cObj->getTypoLink_URL($id, $urlParameters, $target);
    }

    /**
     * Link a string to some page.
     * Like pi_getPageLink() but takes a string as first parameter which will in turn be wrapped with the URL including target attribute
     * Simple example: $this->pi_linkToPage('My link', 123) to get something like <a href="index.php?id=123&type=1">My link</a>
     *
     * @param string $str The content string to wrap in <a> tags
     * @param int $id Page id
     * @param string $target Target value to use. Affects the &type-value of the URL, defaults to current.
     * @param array|string $urlParameters As an array key/value pairs represent URL parameters to set. Values NOT URL-encoded yet, keys should be URL-encoded if needed. As a string the parameter is expected to be URL-encoded already.
     * @return string The input string wrapped in <a> tags with the URL and target set.
     * @see pi_getPageLink(), ContentObjectRenderer::getTypoLink()
     */
    public function pi_linkToPage($str, $id, $target = '', $urlParameters = [])
    {
        return $this->cObj->getTypoLink($str, $id, $urlParameters, $target);
    }

    /**
     * Link string to the current page.
     * Returns the $str wrapped in <a>-tags with a link to the CURRENT page, but with $urlParameters set as extra parameters for the page.
     *
     * @param string $str The content string to wrap in <a> tags
     * @param array $urlParameters Array with URL parameters as key/value pairs. They will be "imploded" and added to the list of parameters defined in the plugins TypoScript property "parent.addParams" plus $this->pi_moreParams.
     * @param bool $cache If $cache is set (0/1), the page is asked to be cached by a &cHash value (unless the current plugin using this class is a USER_INT). Otherwise the no_cache-parameter will be a part of the link.
     * @param int $altPageId Alternative page ID for the link. (By default this function links to the SAME page!)
     * @return string The input string wrapped in <a> tags
     * @see pi_linkTP_keepPIvars(), ContentObjectRenderer::typoLink()
     */
    public function pi_linkTP($str, $urlParameters = [], $cache = false, $altPageId = 0)
    {
        $conf = [];
        $conf['useCacheHash'] = $this->pi_USER_INT_obj ? 0 : $cache;
        $conf['no_cache'] = $this->pi_USER_INT_obj ? 0 : !$cache;
        $conf['parameter'] = $altPageId ? $altPageId : ($this->pi_tmpPageId ? $this->pi_tmpPageId : $this->frontendController->id);
        $conf['additionalParams'] = $this->conf['parent.']['addParams'] . HttpUtility::buildQueryString($urlParameters, '&', true) . $this->pi_moreParams;
        return $this->cObj->typoLink($str, $conf);
    }

    /**
     * Link a string to the current page while keeping currently set values in piVars.
     * Like pi_linkTP, but $urlParameters is by default set to $this->piVars with $overrulePIvars overlaid.
     * This means any current entries from this->piVars are passed on (except the key "DATA" which will be unset before!) and entries in $overrulePIvars will OVERRULE the current in the link.
     *
     * @param string $str The content string to wrap in <a> tags
     * @param array $overrulePIvars Array of values to override in the current piVars. Contrary to pi_linkTP the keys in this array must correspond to the real piVars array and therefore NOT be prefixed with the $this->prefixId string. Further, if a value is a blank string it means the piVar key will not be a part of the link (unset)
     * @param bool $cache If $cache is set, the page is asked to be cached by a &cHash value (unless the current plugin using this class is a USER_INT). Otherwise the no_cache-parameter will be a part of the link.
     * @param bool $clearAnyway If set, then the current values of piVars will NOT be preserved anyways... Practical if you want an easy way to set piVars without having to worry about the prefix, "tx_xxxxx[]
     * @param int $altPageId Alternative page ID for the link. (By default this function links to the SAME page!)
     * @return string The input string wrapped in <a> tags
     * @see pi_linkTP()
     */
    public function pi_linkTP_keepPIvars($str, $overrulePIvars = [], $cache = false, $clearAnyway = false, $altPageId = 0)
    {
        if (is_array($this->piVars) && is_array($overrulePIvars) && !$clearAnyway) {
            $piVars = $this->piVars;
            unset($piVars['DATA']);
            ArrayUtility::mergeRecursiveWithOverrule($piVars, $overrulePIvars);
            $overrulePIvars = $piVars;
            if ($this->pi_autoCacheEn) {
                $cache = $this->pi_autoCache($overrulePIvars);
            }
        }
        return $this->pi_linkTP($str, [$this->prefixId => $overrulePIvars], $cache, $altPageId);
    }

    /**
     * Get URL to the current page while keeping currently set values in piVars.
     * Same as pi_linkTP_keepPIvars but returns only the URL from the link.
     *
     * @param array $overrulePIvars See pi_linkTP_keepPIvars
     * @param bool $cache See pi_linkTP_keepPIvars
     * @param bool $clearAnyway See pi_linkTP_keepPIvars
     * @param int $altPageId See pi_linkTP_keepPIvars
     * @return string The URL ($this->cObj->lastTypoLinkUrl)
     * @see pi_linkTP_keepPIvars()
     */
    public function pi_linkTP_keepPIvars_url($overrulePIvars = [], $cache = false, $clearAnyway = false, $altPageId = 0)
    {
        $this->pi_linkTP_keepPIvars('|', $overrulePIvars, $cache, $clearAnyway, $altPageId);
        return $this->cObj->lastTypoLinkUrl;
    }

    /**
     * Wraps the $str in a link to a single display of the record (using piVars[showUid])
     * Uses pi_linkTP for the linking
     *
     * @param string $str The content string to wrap in <a> tags
     * @param int $uid UID of the record for which to display details (basically this will become the value of [showUid]
     * @param bool $cache See pi_linkTP_keepPIvars
     * @param array $mergeArr Array of values to override in the current piVars. Same as $overrulePIvars in pi_linkTP_keepPIvars
     * @param bool $urlOnly If TRUE, only the URL is returned, not a full link
     * @param int $altPageId Alternative page ID for the link. (By default this function links to the SAME page!)
     * @return string The input string wrapped in <a> tags
     * @see pi_linkTP(), pi_linkTP_keepPIvars()
     */
    public function pi_list_linkSingle($str, $uid, $cache = false, $mergeArr = [], $urlOnly = false, $altPageId = 0)
    {
        if ($this->prefixId) {
            if ($cache) {
                $overrulePIvars = $uid ? ['showUid' => $uid] : [];
                $overrulePIvars = array_merge($overrulePIvars, (array)$mergeArr);
                $str = $this->pi_linkTP($str, [$this->prefixId => $overrulePIvars], $cache, $altPageId);
            } else {
                $overrulePIvars = ['showUid' => $uid ?: ''];
                $overrulePIvars = array_merge($overrulePIvars, (array)$mergeArr);
                $str = $this->pi_linkTP_keepPIvars($str, $overrulePIvars, $cache, 0, $altPageId);
            }
            // If urlOnly flag, return only URL as it has recently be generated.
            if ($urlOnly) {
                $str = $this->cObj->lastTypoLinkUrl;
            }
        }
        return $str;
    }

    /**
     * Will change the href value from <a> in the input string and turn it into an onclick event that will open a new window with the URL
     *
     * @param string $str The string to process. This should be a string already wrapped/including a <a> tag which will be modified to contain an onclick handler. Only the attributes "href" and "onclick" will be left.
     * @param string $winName Window name for the pop-up window
     * @param string $winParams Window parameters, see the default list for inspiration
     * @return string The processed input string, modified IF a <a> tag was found
     */
    public function pi_openAtagHrefInJSwindow($str, $winName = '', $winParams = 'width=670,height=500,status=0,menubar=0,scrollbars=1,resizable=1')
    {
        if (preg_match('/(.*)(<a[^>]*>)(.*)/i', $str, $match)) {
            // decode HTML entities, `href` is used in escaped JavaScript context
            $aTagContent = GeneralUtility::get_tag_attributes($match[2], true);
            $onClick = 'vHWin=window.open('
                . GeneralUtility::quoteJSvalue($this->frontendController->baseUrlWrap($aTagContent['href'])) . ','
                . GeneralUtility::quoteJSvalue($winName ?: md5($aTagContent['href'])) . ','
                . GeneralUtility::quoteJSvalue($winParams) . ');vHWin.focus();return false;';
            $match[2] = '<a href="#" onclick="' . htmlspecialchars($onClick) . '">';
            $str = $match[1] . $match[2] . $match[3];
        }
        return $str;
    }

    /***************************
     *
     * Functions for listing, browsing, searching etc.
     *
     **************************/
    /**
     * Returns a results browser. This means a bar of page numbers plus a "previous" and "next" link. For each entry in the bar the piVars "pointer" will be pointing to the "result page" to show.
     * Using $this->piVars['pointer'] as pointer to the page to display. Can be overwritten with another string ($pointerName) to make it possible to have more than one pagebrowser on a page)
     * Using $this->internal['res_count'], $this->internal['results_at_a_time'] and $this->internal['maxPages'] for count number, how many results to show and the max number of pages to include in the browse bar.
     * Using $this->internal['dontLinkActivePage'] as switch if the active (current) page should be displayed as pure text or as a link to itself
     * Using $this->internal['showFirstLast'] as switch if the two links named "<< First" and "LAST >>" will be shown and point to the first or last page.
     * Using $this->internal['pagefloat']: this defines were the current page is shown in the list of pages in the Pagebrowser. If this var is an integer it will be interpreted as position in the list of pages. If its value is the keyword "center" the current page will be shown in the middle of the pagelist.
     * Using $this->internal['showRange']: this var switches the display of the pagelinks from pagenumbers to ranges f.e.: 1-5 6-10 11-15... instead of 1 2 3...
     * Using $this->pi_isOnlyFields: this holds a comma-separated list of fieldnames which - if they are among the GETvars - will not disable caching for the page with pagebrowser.
     *
     * The third parameter is an array with several wraps for the parts of the pagebrowser. The following elements will be recognized:
     * disabledLinkWrap, inactiveLinkWrap, activeLinkWrap, browseLinksWrap, showResultsWrap, showResultsNumbersWrap, browseBoxWrap.
     *
     * If $wrapArr['showResultsNumbersWrap'] is set, the formatting string is expected to hold template markers (###FROM###, ###TO###, ###OUT_OF###, ###FROM_TO###, ###CURRENT_PAGE###, ###TOTAL_PAGES###)
     * otherwise the formatting string is expected to hold sprintf-markers (%s) for from, to, outof (in that sequence)
     *
     * @param int $showResultCount Determines how the results of the page browser will be shown. See description below
     * @param string $tableParams Attributes for the table tag which is wrapped around the table cells containing the browse links
     * @param array $wrapArr Array with elements to overwrite the default $wrapper-array.
     * @param string $pointerName varname for the pointer.
     * @param bool $hscText Enable htmlspecialchars() on language labels
     * @param bool $forceOutput Forces the output of the page browser if you set this option to "TRUE" (otherwise it's only drawn if enough entries are available)
     * @return string Output HTML-Table, wrapped in <div>-tags with a class attribute (if $wrapArr is not passed,
     */
    public function pi_list_browseresults($showResultCount = 1, $tableParams = '', $wrapArr = [], $pointerName = 'pointer', $hscText = true, $forceOutput = false)
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['pi_list_browseresults'] ?? [] as $classRef) {
            $hookObj = GeneralUtility::makeInstance($classRef);
            if (method_exists($hookObj, 'pi_list_browseresults')) {
                $pageBrowser = $hookObj->pi_list_browseresults($showResultCount, $tableParams, $wrapArr, $pointerName, $hscText, $forceOutput, $this);
                if (is_string($pageBrowser) && trim($pageBrowser) !== '') {
                    return $pageBrowser;
                }
            }
        }
        // example $wrapArr-array how it could be traversed from an extension
        /* $wrapArr = array(
        'browseBoxWrap' => '<div class="browseBoxWrap">|</div>',
        'showResultsWrap' => '<div class="showResultsWrap">|</div>',
        'browseLinksWrap' => '<div class="browseLinksWrap">|</div>',
        'showResultsNumbersWrap' => '<span class="showResultsNumbersWrap">|</span>',
        'disabledLinkWrap' => '<span class="disabledLinkWrap">|</span>',
        'inactiveLinkWrap' => '<span class="inactiveLinkWrap">|</span>',
        'activeLinkWrap' => '<span class="activeLinkWrap">|</span>'
        );*/
        // Initializing variables:
        $pointer = (int)$this->piVars[$pointerName];
        $count = (int)$this->internal['res_count'];
        $results_at_a_time = MathUtility::forceIntegerInRange($this->internal['results_at_a_time'], 1, 1000);
        $totalPages = ceil($count / $results_at_a_time);
        $maxPages = MathUtility::forceIntegerInRange($this->internal['maxPages'], 1, 100);
        $pi_isOnlyFields = $this->pi_isOnlyFields($this->pi_isOnlyFields);
        if (!$forceOutput && $count <= $results_at_a_time) {
            return '';
        }
        // $showResultCount determines how the results of the pagerowser will be shown.
        // If set to 0: only the result-browser will be shown
        //	 		 1: (default) the text "Displaying results..." and the result-browser will be shown.
        //	 		 2: only the text "Displaying results..." will be shown
        $showResultCount = (int)$showResultCount;
        // If this is set, two links named "<< First" and "LAST >>" will be shown and point to the very first or last page.
        $showFirstLast = !empty($this->internal['showFirstLast']);
        // If this has a value the "previous" button is always visible (will be forced if "showFirstLast" is set)
        $alwaysPrev = $showFirstLast ? 1 : $this->pi_alwaysPrev;
        if (isset($this->internal['pagefloat'])) {
            if (strtoupper($this->internal['pagefloat']) === 'CENTER') {
                $pagefloat = ceil(($maxPages - 1) / 2);
            } else {
                // pagefloat set as integer. 0 = left, value >= $this->internal['maxPages'] = right
                $pagefloat = MathUtility::forceIntegerInRange($this->internal['pagefloat'], -1, $maxPages - 1);
            }
        } else {
            // pagefloat disabled
            $pagefloat = -1;
        }
        // Default values for "traditional" wrapping with a table. Can be overwritten by vars from $wrapArr
        $wrapper['disabledLinkWrap'] = '<td class="nowrap"><p>|</p></td>';
        $wrapper['inactiveLinkWrap'] = '<td class="nowrap"><p>|</p></td>';
        $wrapper['activeLinkWrap'] = '<td' . $this->pi_classParam('browsebox-SCell') . ' class="nowrap"><p>|</p></td>';
        $wrapper['browseLinksWrap'] = rtrim('<table ' . $tableParams) . '><tr>|</tr></table>';
        $wrapper['showResultsWrap'] = '<p>|</p>';
        $wrapper['browseBoxWrap'] = '
		<!--
			List browsing box:
		-->
		<div ' . $this->pi_classParam('browsebox') . '>
			|
		</div>';
        // Now overwrite all entries in $wrapper which are also in $wrapArr
        $wrapper = array_merge($wrapper, $wrapArr);
        // Show pagebrowser
        if ($showResultCount != 2) {
            if ($pagefloat > -1) {
                $lastPage = min($totalPages, max($pointer + 1 + $pagefloat, $maxPages));
                $firstPage = max(0, $lastPage - $maxPages);
            } else {
                $firstPage = 0;
                $lastPage = MathUtility::forceIntegerInRange($totalPages, 1, $maxPages);
            }
            $links = [];
            // Make browse-table/links:
            // Link to first page
            if ($showFirstLast) {
                if ($pointer > 0) {
                    $label = $this->pi_getLL('pi_list_browseresults_first', '<< First');
                    $links[] = $this->cObj->wrap($this->pi_linkTP_keepPIvars($hscText ? htmlspecialchars($label) : $label, [$pointerName => null], $pi_isOnlyFields), $wrapper['inactiveLinkWrap']);
                } else {
                    $label = $this->pi_getLL('pi_list_browseresults_first', '<< First');
                    $links[] = $this->cObj->wrap($hscText ? htmlspecialchars($label) : $label, $wrapper['disabledLinkWrap']);
                }
            }
            // Link to previous page
            if ($alwaysPrev >= 0) {
                if ($pointer > 0) {
                    $label = $this->pi_getLL('pi_list_browseresults_prev', '< Previous');
                    $links[] = $this->cObj->wrap($this->pi_linkTP_keepPIvars($hscText ? htmlspecialchars($label) : $label, [$pointerName => ($pointer - 1) ?: ''], $pi_isOnlyFields), $wrapper['inactiveLinkWrap']);
                } elseif ($alwaysPrev) {
                    $label = $this->pi_getLL('pi_list_browseresults_prev', '< Previous');
                    $links[] = $this->cObj->wrap($hscText ? htmlspecialchars($label) : $label, $wrapper['disabledLinkWrap']);
                }
            }
            // Links to pages
            for ($a = $firstPage; $a < $lastPage; $a++) {
                if ($this->internal['showRange']) {
                    $pageText = ($a * $results_at_a_time + 1) . '-' . min($count, ($a + 1) * $results_at_a_time);
                } else {
                    $label = $this->pi_getLL('pi_list_browseresults_page', 'Page');
                    $pageText = trim(($hscText ? htmlspecialchars($label) : $label) . ' ' . ($a + 1));
                }
                // Current page
                if ($pointer == $a) {
                    if ($this->internal['dontLinkActivePage']) {
                        $links[] = $this->cObj->wrap($pageText, $wrapper['activeLinkWrap']);
                    } else {
                        $links[] = $this->cObj->wrap($this->pi_linkTP_keepPIvars($pageText, [$pointerName => $a ?: ''], $pi_isOnlyFields), $wrapper['activeLinkWrap']);
                    }
                } else {
                    $links[] = $this->cObj->wrap($this->pi_linkTP_keepPIvars($pageText, [$pointerName => $a ?: ''], $pi_isOnlyFields), $wrapper['inactiveLinkWrap']);
                }
            }
            if ($pointer < $totalPages - 1 || $showFirstLast) {
                // Link to next page
                if ($pointer >= $totalPages - 1) {
                    $label = $this->pi_getLL('pi_list_browseresults_next', 'Next >');
                    $links[] = $this->cObj->wrap($hscText ? htmlspecialchars($label) : $label, $wrapper['disabledLinkWrap']);
                } else {
                    $label = $this->pi_getLL('pi_list_browseresults_next', 'Next >');
                    $links[] = $this->cObj->wrap($this->pi_linkTP_keepPIvars($hscText ? htmlspecialchars($label) : $label, [$pointerName => $pointer + 1], $pi_isOnlyFields), $wrapper['inactiveLinkWrap']);
                }
            }
            // Link to last page
            if ($showFirstLast) {
                if ($pointer < $totalPages - 1) {
                    $label = $this->pi_getLL('pi_list_browseresults_last', 'Last >>');
                    $links[] = $this->cObj->wrap($this->pi_linkTP_keepPIvars($hscText ? htmlspecialchars($label) : $label, [$pointerName => $totalPages - 1], $pi_isOnlyFields), $wrapper['inactiveLinkWrap']);
                } else {
                    $label = $this->pi_getLL('pi_list_browseresults_last', 'Last >>');
                    $links[] = $this->cObj->wrap($hscText ? htmlspecialchars($label) : $label, $wrapper['disabledLinkWrap']);
                }
            }
            $theLinks = $this->cObj->wrap(implode(LF, $links), $wrapper['browseLinksWrap']);
        } else {
            $theLinks = '';
        }
        $pR1 = $pointer * $results_at_a_time + 1;
        $pR2 = $pointer * $results_at_a_time + $results_at_a_time;
        if ($showResultCount) {
            if ($wrapper['showResultsNumbersWrap']) {
                // This will render the resultcount in a more flexible way using markers (new in TYPO3 3.8.0).
                // The formatting string is expected to hold template markers (see function header). Example: 'Displaying results ###FROM### to ###TO### out of ###OUT_OF###'
                $markerArray['###FROM###'] = $this->cObj->wrap($this->internal['res_count'] > 0 ? $pR1 : 0, $wrapper['showResultsNumbersWrap']);
                $markerArray['###TO###'] = $this->cObj->wrap(min($this->internal['res_count'], $pR2), $wrapper['showResultsNumbersWrap']);
                $markerArray['###OUT_OF###'] = $this->cObj->wrap($this->internal['res_count'], $wrapper['showResultsNumbersWrap']);
                $markerArray['###FROM_TO###'] = $this->cObj->wrap(($this->internal['res_count'] > 0 ? $pR1 : 0) . ' ' . $this->pi_getLL('pi_list_browseresults_to', 'to') . ' ' . min($this->internal['res_count'], $pR2), $wrapper['showResultsNumbersWrap']);
                $markerArray['###CURRENT_PAGE###'] = $this->cObj->wrap($pointer + 1, $wrapper['showResultsNumbersWrap']);
                $markerArray['###TOTAL_PAGES###'] = $this->cObj->wrap($totalPages, $wrapper['showResultsNumbersWrap']);
                // Substitute markers
                $resultCountMsg = $this->templateService->substituteMarkerArray($this->pi_getLL('pi_list_browseresults_displays', 'Displaying results ###FROM### to ###TO### out of ###OUT_OF###'), $markerArray);
            } else {
                // Render the resultcount in the "traditional" way using sprintf
                $resultCountMsg = sprintf(str_replace('###SPAN_BEGIN###', '<span' . $this->pi_classParam('browsebox-strong') . '>', $this->pi_getLL('pi_list_browseresults_displays', 'Displaying results ###SPAN_BEGIN###%s to %s</span> out of ###SPAN_BEGIN###%s</span>')), $count > 0 ? $pR1 : 0, min($count, $pR2), $count);
            }
            $resultCountMsg = $this->cObj->wrap($resultCountMsg, $wrapper['showResultsWrap']);
        } else {
            $resultCountMsg = '';
        }
        $sTables = $this->cObj->wrap($resultCountMsg . $theLinks, $wrapper['browseBoxWrap']);
        return $sTables;
    }

    /**
     * Returns a mode selector; a little menu in a table normally put in the top of the page/list.
     *
     * @param array $items Key/Value pairs for the menu; keys are the piVars[mode] values and the "values" are the labels for them.
     * @param string $tableParams Attributes for the table tag which is wrapped around the table cells containing the menu
     * @return string Output HTML, wrapped in <div>-tags with a class attribute
     */
    public function pi_list_modeSelector($items = [], $tableParams = '')
    {
        $cells = [];
        foreach ($items as $k => $v) {
            $cells[] = '
					<td' . ($this->piVars['mode'] == $k ? $this->pi_classParam('modeSelector-SCell') : '') . '><p>' . $this->pi_linkTP_keepPIvars(htmlspecialchars($v), ['mode' => $k], $this->pi_isOnlyFields($this->pi_isOnlyFields)) . '</p></td>';
        }
        $sTables = '

		<!--
			Mode selector (menu for list):
		-->
		<div' . $this->pi_classParam('modeSelector') . '>
			<' . rtrim('table ' . $tableParams) . '>
				<tr>
					' . implode('', $cells) . '
				</tr>
			</table>
		</div>';
        return $sTables;
    }

    /**
     * Returns the list of items based on the input SQL result pointer
     * For each result row the internal var, $this->internal['currentRow'], is set with the row returned.
     * $this->pi_list_header() makes the header row for the list
     * $this->pi_list_row() is used for rendering each row
     * Notice that these two functions are typically ALWAYS defined in the extension class of the plugin since they are directly concerned with the specific layout for that plugins purpose.
     *
     * @param Statement $statement Result pointer to a SQL result which can be traversed.
     * @param string $tableParams Attributes for the table tag which is wrapped around the table rows containing the list
     * @return string Output HTML, wrapped in <div>-tags with a class attribute
     * @see pi_list_row(), pi_list_header()
     */
    public function pi_list_makelist($statement, $tableParams = '')
    {
        // Make list table header:
        $tRows = [];
        $this->internal['currentRow'] = '';
        $tRows[] = $this->pi_list_header();
        // Make list table rows
        $c = 0;
        while ($this->internal['currentRow'] = $statement->fetch()) {
            $tRows[] = $this->pi_list_row($c);
            $c++;
        }
        $out = '

		<!--
			Record list:
		-->
		<div' . $this->pi_classParam('listrow') . '>
			<' . rtrim('table ' . $tableParams) . '>
				' . implode('', $tRows) . '
			</table>
		</div>';
        return $out;
    }

    /**
     * Returns a list row. Get data from $this->internal['currentRow'];
     * (Dummy)
     * Notice: This function should ALWAYS be defined in the extension class of the plugin since it is directly concerned with the specific layout of the listing for your plugins purpose.
     *
     * @param int $c Row counting. Starts at 0 (zero). Used for alternating class values in the output rows.
     * @return string HTML output, a table row with a class attribute set (alternative based on odd/even rows)
     */
    public function pi_list_row($c)
    {
        // Dummy
        return '<tr' . ($c % 2 ? $this->pi_classParam('listrow-odd') : '') . '><td><p>[dummy row]</p></td></tr>';
    }

    /**
     * Returns a list header row.
     * (Dummy)
     * Notice: This function should ALWAYS be defined in the extension class of the plugin since it is directly concerned with the specific layout of the listing for your plugins purpose.
     *
     * @return string HTML output, a table row with a class attribute set
     */
    public function pi_list_header()
    {
        return '<tr' . $this->pi_classParam('listrow-header') . '><td><p>[dummy header row]</p></td></tr>';
    }

    /***************************
     *
     * Stylesheet, CSS
     *
     **************************/
    /**
     * Returns a class-name prefixed with $this->prefixId and with all underscores substituted to dashes (-)
     *
     * @param string $class The class name (or the END of it since it will be prefixed by $this->prefixId.'-')
     * @return string The combined class name (with the correct prefix)
     */
    public function pi_getClassName($class)
    {
        return str_replace('_', '-', $this->prefixId) . ($this->prefixId ? '-' : '') . $class;
    }

    /**
     * Returns the class-attribute with the correctly prefixed classname
     * Using pi_getClassName()
     *
     * @param string $class The class name(s) (suffix) - separate multiple classes with commas
     * @param string $addClasses Additional class names which should not be prefixed - separate multiple classes with commas
     * @return string A "class" attribute with value and a single space char before it.
     * @see pi_getClassName()
     */
    public function pi_classParam($class, $addClasses = '')
    {
        $output = '';
        $classNames = GeneralUtility::trimExplode(',', $class);
        foreach ($classNames as $className) {
            $output .= ' ' . $this->pi_getClassName($className);
        }
        $additionalClassNames = GeneralUtility::trimExplode(',', $addClasses);
        foreach ($additionalClassNames as $additionalClassName) {
            $output .= ' ' . $additionalClassName;
        }
        return ' class="' . trim($output) . '"';
    }

    /**
     * Wraps the input string in a <div> tag with the class attribute set to the prefixId.
     * All content returned from your plugins should be returned through this function so all content from your plugin is encapsulated in a <div>-tag nicely identifying the content of your plugin.
     *
     * @param string $str HTML content to wrap in the div-tags with the "main class" of the plugin
     * @return string HTML content wrapped, ready to return to the parent object.
     */
    public function pi_wrapInBaseClass($str)
    {
        $content = '<div class="' . str_replace('_', '-', $this->prefixId) . '">
		' . $str . '
	</div>
	';
        if (!$this->frontendController->config['config']['disablePrefixComment']) {
            $content = '


	<!--

		BEGIN: Content of extension "' . $this->extKey . '", plugin "' . $this->prefixId . '"

	-->
	' . $content . '
	<!-- END: Content of extension "' . $this->extKey . '", plugin "' . $this->prefixId . '" -->

	';
        }
        return $content;
    }

    /***************************
     *
     * Frontend editing: Edit panel, edit icons
     *
     **************************/
    /**
     * Returns the Backend User edit panel for the $row from $tablename
     *
     * @param array $row Record array.
     * @param string $tablename Table name
     * @param string $label A label to show with the panel.
     * @param array $conf TypoScript parameters to pass along to the EDITPANEL content Object that gets rendered. The property "allow" WILL get overridden/set though.
     * @return string Returns FALSE/blank if no BE User login and of course if the panel is not shown for other reasons. Otherwise the HTML for the panel (a table).
     * @see ContentObjectRenderer::EDITPANEL()
     */
    public function pi_getEditPanel($row = [], $tablename = '', $label = '', $conf = [])
    {
        $panel = '';
        if (!$row || !$tablename) {
            $row = $this->internal['currentRow'];
            $tablename = $this->internal['currentTable'];
        }
        if ($this->frontendController->isBackendUserLoggedIn()) {
            // Create local cObj if not set:
            if (!is_object($this->pi_EPtemp_cObj)) {
                $this->pi_EPtemp_cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
                $this->pi_EPtemp_cObj->setParent($this->cObj->data, $this->cObj->currentRecord);
            }
            // Initialize the cObj object with current row
            $this->pi_EPtemp_cObj->start($row, $tablename);
            // Setting TypoScript values in the $conf array. See documentation in TSref for the EDITPANEL cObject.
            $conf['allow'] = 'edit,new,delete,move,hide';
            $panel = $this->pi_EPtemp_cObj->cObjGetSingle('EDITPANEL', $conf, 'editpanel');
        }
        if ($panel) {
            if ($label) {
                return '<!-- BEGIN: EDIT PANEL --><table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td valign="top">' . $label . '</td><td valign="top" align="right">' . $panel . '</td></tr></table><!-- END: EDIT PANEL -->';
            }
            return '<!-- BEGIN: EDIT PANEL -->' . $panel . '<!-- END: EDIT PANEL -->';
        }
        return $label;
    }

    /**
     * Adds edit-icons to the input content.
     * ContentObjectRenderer::editIcons used for rendering
     *
     * @param string $content HTML content to add icons to. The icons will be put right after the last content part in the string (that means before the ending series of HTML tags)
     * @param string $fields The list of fields to edit when the icon is clicked.
     * @param string $title Title for the edit icon.
     * @param array $row Table record row
     * @param string $tablename Table name
     * @param array $oConf Conf array
     * @return string The processed content
     * @see ContentObjectRenderer::editIcons()
     */
    public function pi_getEditIcon($content, $fields, $title = '', $row = [], $tablename = '', $oConf = [])
    {
        if ($this->frontendController->isBackendUserLoggedIn()) {
            if (!$row || !$tablename) {
                $row = $this->internal['currentRow'];
                $tablename = $this->internal['currentTable'];
            }
            $conf = array_merge([
                'beforeLastTag' => 1,
                'iconTitle' => $title
            ], $oConf);
            $content = $this->cObj->editIcons($content, $tablename . ':' . $fields, $conf, $tablename . ':' . $row['uid'], $row, '&viewUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')));
        }
        return $content;
    }

    /***************************
     *
     * Localization, locallang functions
     *
     **************************/
    /**
     * Returns the localized label of the LOCAL_LANG key, $key
     * Notice that for debugging purposes prefixes for the output values can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param string $alternativeLabel Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
     * @return string The value from LOCAL_LANG.
     */
    public function pi_getLL($key, $alternativeLabel = '')
    {
        $word = null;
        if (!empty($this->LOCAL_LANG[$this->LLkey][$key][0]['target'])
            || isset($this->LOCAL_LANG_UNSET[$this->LLkey][$key])
        ) {
            $word = $this->LOCAL_LANG[$this->LLkey][$key][0]['target'];
        } elseif ($this->altLLkey) {
            $alternativeLanguageKeys = GeneralUtility::trimExplode(',', $this->altLLkey, true);
            $alternativeLanguageKeys = array_reverse($alternativeLanguageKeys);
            foreach ($alternativeLanguageKeys as $languageKey) {
                if (!empty($this->LOCAL_LANG[$languageKey][$key][0]['target'])
                    || isset($this->LOCAL_LANG_UNSET[$languageKey][$key])
                ) {
                    // Alternative language translation for key exists
                    $word = $this->LOCAL_LANG[$languageKey][$key][0]['target'];
                    break;
                }
            }
        }
        if ($word === null) {
            if (!empty($this->LOCAL_LANG['default'][$key][0]['target'])
                || isset($this->LOCAL_LANG_UNSET['default'][$key])
            ) {
                // Get default translation (without charset conversion, english)
                $word = $this->LOCAL_LANG['default'][$key][0]['target'];
            } else {
                // Return alternative string or empty
                $word = isset($this->LLtestPrefixAlt) ? $this->LLtestPrefixAlt . $alternativeLabel : $alternativeLabel;
            }
        }
        return isset($this->LLtestPrefix) ? $this->LLtestPrefix . $word : $word;
    }

    /**
     * Loads local-language values from the file passed as a parameter or
     * by looking for a "locallang" file in the
     * plugin class directory ($this->scriptRelPath).
     * Also locallang values set in the TypoScript property "_LOCAL_LANG" are
     * merged onto the values found in the "locallang" file.
     * Supported file extensions xlf, xml
     *
     * @param string $languageFilePath path to the plugin language file in format EXT:....
     */
    public function pi_loadLL($languageFilePath = '')
    {
        if ($this->LOCAL_LANG_loaded) {
            return;
        }

        if ($languageFilePath === '' && $this->scriptRelPath) {
            $languageFilePath = 'EXT:' . $this->extKey . '/' . PathUtility::dirname($this->scriptRelPath) . '/locallang.xlf';
        }
        if ($languageFilePath !== '') {
            /** @var LocalizationFactory $languageFactory */
            $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
            $this->LOCAL_LANG = $languageFactory->getParsedData($languageFilePath, $this->LLkey);
            $alternativeLanguageKeys = GeneralUtility::trimExplode(',', $this->altLLkey, true);
            foreach ($alternativeLanguageKeys as $languageKey) {
                $tempLL = $languageFactory->getParsedData($languageFilePath, $languageKey);
                if ($this->LLkey !== 'default' && isset($tempLL[$languageKey])) {
                    $this->LOCAL_LANG[$languageKey] = $tempLL[$languageKey];
                }
            }
            // Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
            if (isset($this->conf['_LOCAL_LANG.'])) {
                // Clear the "unset memory"
                $this->LOCAL_LANG_UNSET = [];
                foreach ($this->conf['_LOCAL_LANG.'] as $languageKey => $languageArray) {
                    // Remove the dot after the language key
                    $languageKey = substr($languageKey, 0, -1);
                    // Don't process label if the language is not loaded
                    if (is_array($languageArray) && isset($this->LOCAL_LANG[$languageKey])) {
                        foreach ($languageArray as $labelKey => $labelValue) {
                            if (!is_array($labelValue)) {
                                $this->LOCAL_LANG[$languageKey][$labelKey][0]['target'] = $labelValue;
                                if ($labelValue === '') {
                                    $this->LOCAL_LANG_UNSET[$languageKey][$labelKey] = '';
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->LOCAL_LANG_loaded = true;
    }

    /***************************
     *
     * Database, queries
     *
     **************************/
    /**
     * Executes a standard SELECT query for listing of records based on standard input vars from the 'browser' ($this->internal['results_at_a_time'] and $this->piVars['pointer']) and 'searchbox' ($this->piVars['sword'] and $this->internal['searchFieldList'])
     * Set $count to 1 if you wish to get a count(*) query for selecting the number of results.
     * Notice that the query will use $this->conf['pidList'] and $this->conf['recursive'] to generate a PID list within which to search for records.
     *
     * @param string $table The table name to make the query for.
     * @param bool $count If set, you will get a "count(*)" query back instead of field selecting
     * @param string $addWhere Additional WHERE clauses (should be starting with " AND ....")
     * @param mixed $mm_cat If an array, then it must contain the keys "table", "mmtable" and (optionally) "catUidList" defining a table to make a MM-relation to in the query (based on fields uid_local and uid_foreign). If not array, the query will be a plain query looking up data in only one table.
     * @param string $groupBy If set, this is added as a " GROUP BY ...." part of the query.
     * @param string $orderBy If set, this is added as a " ORDER BY ...." part of the query. The default is that an ORDER BY clause is made based on $this->internal['orderBy'] and $this->internal['descFlag'] where the orderBy field must be found in $this->internal['orderByList']
     * @param string $query If set, this is taken as the first part of the query instead of what is created internally. Basically this should be a query starting with "FROM [table] WHERE ... AND ...". The $addWhere clauses and all the other stuff is still added. Only the tables and PID selecting clauses are bypassed. May be deprecated in the future!
     * @return Statement
     */
    public function pi_exec_query($table, $count = false, $addWhere = '', $mm_cat = '', $groupBy = '', $orderBy = '', $query = '')
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->from($table);

        // Begin Query:
        if (!$query) {
            // This adds WHERE-clauses that ensures deleted, hidden, starttime/endtime/access records are NOT
            // selected, if they should not! Almost ALWAYS add this to your queries!
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

            // Fetches the list of PIDs to select from.
            // TypoScript property .pidList is a comma list of pids. If blank, current page id is used.
            // TypoScript property .recursive is an int+ which determines how many levels down from the pids in the pid-list subpages should be included in the select.
            $pidList = GeneralUtility::intExplode(',', $this->pi_getPidList($this->conf['pidList'], $this->conf['recursive']), true);
            if (is_array($mm_cat)) {
                $queryBuilder->from($mm_cat['table'])
                    ->from($mm_cat['mmtable'])
                    ->where(
                        $queryBuilder->expr()->eq($table . '.uid', $queryBuilder->quoteIdentifier($mm_cat['mmtable'] . '.uid_local')),
                        $queryBuilder->expr()->eq($mm_cat['table'] . '.uid', $queryBuilder->quoteIdentifier($mm_cat['mmtable'] . '.uid_foreign')),
                        $queryBuilder->expr()->in(
                            $table . '.pid',
                            $queryBuilder->createNamedParameter($pidList, Connection::PARAM_INT_ARRAY)
                        )
                    );
                if (strcmp($mm_cat['catUidList'], '')) {
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->in(
                            $mm_cat['table'] . '.uid',
                            $queryBuilder->createNamedParameter(
                                GeneralUtility::intExplode(',', $mm_cat['catUidList'], true),
                                Connection::PARAM_INT_ARRAY
                            )
                        )
                    );
                }
            } else {
                $queryBuilder->where(
                    $queryBuilder->expr()->in(
                        'pid',
                        $queryBuilder->createNamedParameter($pidList, Connection::PARAM_INT_ARRAY)
                    )
                );
            }
        } else {
            // Restrictions need to be handled by the $query parameter!
            $queryBuilder->getRestrictions()->removeAll();

            // Split the "FROM ... WHERE" string so we get the WHERE part and TABLE names separated...:
            list($tableListFragment, $whereFragment) = preg_split('/WHERE/i', trim($query), 2);
            foreach (QueryHelper::parseTableList($tableListFragment) as $tableNameAndAlias) {
                list($tableName, $tableAlias) = $tableNameAndAlias;
                $queryBuilder->from($tableName, $tableAlias);
            }
            $queryBuilder->where(QueryHelper::stripLogicalOperatorPrefix($whereFragment));
        }

        // Add '$addWhere'
        if ($addWhere) {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($addWhere));
        }
        // Search word:
        if ($this->piVars['sword'] && $this->internal['searchFieldList']) {
            $searchWhere = QueryHelper::stripLogicalOperatorPrefix(
                $this->cObj->searchWhere($this->piVars['sword'], $this->internal['searchFieldList'], $table)
            );
            if (!empty($searchWhere)) {
                $queryBuilder->andWhere($searchWhere);
            }
        }

        if ($count) {
            $queryBuilder->count('*');
        } else {
            // Add 'SELECT'
            $fields = $this->pi_prependFieldsWithTable($table, $this->pi_listFields);
            $queryBuilder->select(...GeneralUtility::trimExplode(',', $fields, true));

            // Order by data:
            if (!$orderBy && $this->internal['orderBy']) {
                if (GeneralUtility::inList($this->internal['orderByList'], $this->internal['orderBy'])) {
                    $sorting = $this->internal['descFlag'] ? ' DESC' : 'ASC';
                    $queryBuilder->orderBy($table . '.' . $this->internal['orderBy'], $sorting);
                }
            } elseif ($orderBy) {
                foreach (QueryHelper::parseOrderBy($orderBy) as $fieldNameAndSorting) {
                    list($fieldName, $sorting) = $fieldNameAndSorting;
                    $queryBuilder->addOrderBy($fieldName, $sorting);
                }
            }

            // Limit data:
            $pointer = (int)$this->piVars['pointer'];
            $results_at_a_time = MathUtility::forceIntegerInRange($this->internal['results_at_a_time'], 1, 1000);
            $queryBuilder->setFirstResult($pointer * $results_at_a_time)
                ->setMaxResults($results_at_a_time);

            // Grouping
            if (!empty($groupBy)) {
                $queryBuilder->groupBy(...QueryHelper::parseGroupBy($groupBy));
            }
        }

        return $queryBuilder->execute();
    }

    /**
     * Returns the row $uid from $table
     * (Simply calling $this->frontendEngine->sys_page->checkRecord())
     *
     * @param string $table The table name
     * @param int $uid The uid of the record from the table
     * @param bool $checkPage If $checkPage is set, it's required that the page on which the record resides is accessible
     * @return array If record is found, an array. Otherwise FALSE.
     */
    public function pi_getRecord($table, $uid, $checkPage = false)
    {
        return $this->frontendController->sys_page->checkRecord($table, $uid, $checkPage);
    }

    /**
     * Returns a commalist of page ids for a query (eg. 'WHERE pid IN (...)')
     *
     * @param string $pid_list A comma list of page ids (if empty current page is used)
     * @param int $recursive An integer >=0 telling how deep to dig for pids under each entry in $pid_list
     * @return string List of PID values (comma separated)
     */
    public function pi_getPidList($pid_list, $recursive = 0)
    {
        if (!strcmp($pid_list, '')) {
            $pid_list = $this->frontendController->id;
        }
        $recursive = MathUtility::forceIntegerInRange($recursive, 0);
        $pid_list_arr = array_unique(GeneralUtility::trimExplode(',', $pid_list, true));
        $pid_list = [];
        foreach ($pid_list_arr as $val) {
            $val = MathUtility::forceIntegerInRange($val, 0);
            if ($val) {
                $_list = $this->cObj->getTreeList(-1 * $val, $recursive);
                if ($_list) {
                    $pid_list[] = $_list;
                }
            }
        }
        return implode(',', $pid_list);
    }

    /**
     * Having a comma list of fields ($fieldList) this is prepended with the $table.'.' name
     *
     * @param string $table Table name to prepend
     * @param string $fieldList List of fields where each element will be prepended with the table name given.
     * @return string List of fields processed.
     */
    public function pi_prependFieldsWithTable($table, $fieldList)
    {
        $list = GeneralUtility::trimExplode(',', $fieldList, true);
        $return = [];
        foreach ($list as $listItem) {
            $return[] = $table . '.' . $listItem;
        }
        return implode(',', $return);
    }

    /**
     * Will select all records from the "category table", $table, and return them in an array.
     *
     * @param string $table The name of the category table to select from.
     * @param int $pid The page from where to select the category records.
     * @param string $whereClause Optional additional WHERE clauses put in the end of the query. DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
     * @param string $groupBy Optional GROUP BY field(s), if none, supply blank string.
     * @param string $orderBy Optional ORDER BY field(s), if none, supply blank string.
     * @param string $limit Optional LIMIT value ([begin,]max), if none, supply blank string.
     * @return array The array with the category records in.
     */
    public function pi_getCategoryTableContents($table, $pid, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '')
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                ),
                QueryHelper::stripLogicalOperatorPrefix($whereClause)
            );

        if (!empty($orderBy)) {
            foreach (QueryHelper::parseOrderBy($orderBy) as $fieldNameAndSorting) {
                list($fieldName, $sorting) = $fieldNameAndSorting;
                $queryBuilder->addOrderBy($fieldName, $sorting);
            }
        }

        if (!empty($groupBy)) {
            $queryBuilder->groupBy(...QueryHelper::parseGroupBy($groupBy));
        }

        if (!empty($limit)) {
            $limitValues = GeneralUtility::intExplode(',', $limit, true);
            if (count($limitValues) === 1) {
                $queryBuilder->setMaxResults($limitValues[0]);
            } else {
                $queryBuilder->setFirstResult($limitValues[0])
                    ->setMaxResults($limitValues[1]);
            }
        }

        $result = $queryBuilder->execute();
        $outArr = [];
        while ($row = $result->fetch()) {
            $outArr[$row['uid']] = $row;
        }
        return $outArr;
    }

    /***************************
     *
     * Various
     *
     **************************/
    /**
     * Returns TRUE if the piVars array has ONLY those fields entered that is set in the $fList (commalist) AND if none of those fields value is greater than $lowerThan field if they are integers.
     * Notice that this function will only work as long as values are integers.
     *
     * @param string $fList List of fields (keys from piVars) to evaluate on
     * @param int $lowerThan Limit for the values.
     * @return bool|null Returns TRUE (1) if conditions are met.
     */
    public function pi_isOnlyFields($fList, $lowerThan = -1)
    {
        $lowerThan = $lowerThan == -1 ? $this->pi_lowerThan : $lowerThan;
        $fList = GeneralUtility::trimExplode(',', $fList, true);
        $tempPiVars = $this->piVars;
        foreach ($fList as $k) {
            if (!MathUtility::canBeInterpretedAsInteger($tempPiVars[$k]) || $tempPiVars[$k] < $lowerThan) {
                unset($tempPiVars[$k]);
            }
        }
        if (empty($tempPiVars)) {
            //@TODO: How do we deal with this? return TRUE would be the right thing to do here but that might be breaking
            return 1;
        }
        return null;
    }

    /**
     * Returns TRUE if the array $inArray contains only values allowed to be cached based on the configuration in $this->pi_autoCacheFields
     * Used by ->pi_linkTP_keepPIvars
     * This is an advanced form of evaluation of whether a URL should be cached or not.
     *
     * @param array $inArray An array with piVars values to evaluate
     * @return bool|null Returns TRUE (1) if conditions are met.
     * @see pi_linkTP_keepPIvars()
     */
    public function pi_autoCache($inArray)
    {
        if (is_array($inArray)) {
            foreach ($inArray as $fN => $fV) {
                if (!strcmp($inArray[$fN], '')) {
                    unset($inArray[$fN]);
                } elseif (is_array($this->pi_autoCacheFields[$fN])) {
                    if (is_array($this->pi_autoCacheFields[$fN]['range']) && (int)$inArray[$fN] >= (int)$this->pi_autoCacheFields[$fN]['range'][0] && (int)$inArray[$fN] <= (int)$this->pi_autoCacheFields[$fN]['range'][1]) {
                        unset($inArray[$fN]);
                    }
                    if (is_array($this->pi_autoCacheFields[$fN]['list']) && in_array($inArray[$fN], $this->pi_autoCacheFields[$fN]['list'])) {
                        unset($inArray[$fN]);
                    }
                }
            }
        }
        if (empty($inArray)) {
            //@TODO: How do we deal with this? return TRUE would be the right thing to do here but that might be breaking
            return 1;
        }
        return null;
    }

    /**
     * Will process the input string with the parseFunc function from ContentObjectRenderer based on configuration
     * set in "lib.parseFunc_RTE" in the current TypoScript template.
     *
     * @param string $str The input text string to process
     * @return string The processed string
     * @see ContentObjectRenderer::parseFunc()
     */
    public function pi_RTEcssText($str)
    {
        $parseFunc = $this->frontendController->tmpl->setup['lib.']['parseFunc_RTE.'];
        if (is_array($parseFunc)) {
            $str = $this->cObj->parseFunc($str, $parseFunc);
        }
        return $str;
    }

    /*******************************
     *
     * FlexForms related functions
     *
     *******************************/
    /**
     * Converts $this->cObj->data['pi_flexform'] from XML string to flexForm array.
     *
     * @param string $field Field name to convert
     */
    public function pi_initPIflexForm($field = 'pi_flexform')
    {
        // Converting flexform data into array:
        if (!is_array($this->cObj->data[$field]) && $this->cObj->data[$field]) {
            $this->cObj->data[$field] = GeneralUtility::xml2array($this->cObj->data[$field]);
            if (!is_array($this->cObj->data[$field])) {
                $this->cObj->data[$field] = [];
            }
        }
    }

    /**
     * Return value from somewhere inside a FlexForm structure
     *
     * @param array $T3FlexForm_array FlexForm data
     * @param string $fieldName Field name to extract. Can be given like "test/el/2/test/el/field_templateObject" where each part will dig a level deeper in the FlexForm data.
     * @param string $sheet Sheet pointer, eg. "sDEF
     * @param string $lang Language pointer, eg. "lDEF
     * @param string $value Value pointer, eg. "vDEF
     * @return string|null The content.
     */
    public function pi_getFFvalue($T3FlexForm_array, $fieldName, $sheet = 'sDEF', $lang = 'lDEF', $value = 'vDEF')
    {
        $sheetArray = is_array($T3FlexForm_array) ? $T3FlexForm_array['data'][$sheet][$lang] : '';
        if (is_array($sheetArray)) {
            return $this->pi_getFFvalueFromSheetArray($sheetArray, explode('/', $fieldName), $value);
        }
        return null;
    }

    /**
     * Returns part of $sheetArray pointed to by the keys in $fieldNameArray
     *
     * @param array $sheetArray Multidimensiona array, typically FlexForm contents
     * @param array $fieldNameArr Array where each value points to a key in the FlexForms content - the input array will have the value returned pointed to by these keys. All integer keys will not take their integer counterparts, but rather traverse the current position in the array an return element number X (whether this is right behavior is not settled yet...)
     * @param string $value Value for outermost key, typ. "vDEF" depending on language.
     * @return mixed The value, typ. string.
     * @internal
     * @see pi_getFFvalue()
     */
    public function pi_getFFvalueFromSheetArray($sheetArray, $fieldNameArr, $value)
    {
        $tempArr = $sheetArray;
        foreach ($fieldNameArr as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($v)) {
                if (is_array($tempArr)) {
                    $c = 0;
                    foreach ($tempArr as $values) {
                        if ($c == $v) {
                            $tempArr = $values;
                            break;
                        }
                        $c++;
                    }
                }
            } else {
                $tempArr = $tempArr[$v];
            }
        }
        return $tempArr[$value];
    }

    /**
     * Returns the currently configured "site language" if a site is configured (= resolved) in the current request.
     *
     * @internal
     */
    protected function getCurrentSiteLanguage(): ?SiteLanguage
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        return $request
               && $request instanceof ServerRequestInterface
               && $request->getAttribute('language') instanceof SiteLanguage
            ? $request->getAttribute('language')
            : null;
    }
}
