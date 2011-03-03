<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class for the built TypoScript based Front End
 *
 * This class has a lot of functions and internal variable which are use from index_ts.php.
 * The class is instantiated as $GLOBALS['TSFE'] in index_ts.php.
 * The use of this class should be inspired by the order of function calls as found in index_ts.php.
 *
 * Revised for TYPO3 3.6 June/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  213: class tslib_fe
 *  382:     function tslib_fe($TYPO3_CONF_VARS, $id, $type, $no_cache='', $cHash='', $jumpurl='',$MP='',$RDCT='')
 *  415:     function connectToMySQL()
 *  425:     function connectToDB()
 *  470:     function sendRedirect()
 *
 *              SECTION: Initializing, resolving page id
 *  508:     function initFEuser()
 *  558:     function initUserGroups()
 *  593:     function isUserOrGroupSet()
 *  618:     function checkAlternativeIdMethods()
 *  670:     function clear_preview()
 *  683:     function determineId()
 *  817:     function fetch_the_id()
 *  911:     function getPageAndRootline()
 *  994:     function getPageShortcut($SC,$mode,$thisUid,$itera=20,$pageLog=array())
 * 1044:     function checkRootlineForIncludeSection()
 * 1081:     function checkEnableFields($row,$bypassGroupCheck=FALSE)
 * 1097:     function checkPageGroupAccess($row, $groupList=NULL)
 * 1116:     function checkPagerecordForIncludeSection($row)
 * 1125:     function checkIfLoginAllowedInBranch()
 * 1150:     function getPageAccessFailureReasons()
 * 1182:     function setIDfromArgV()
 * 1198:     function getPageAndRootlineWithDomain($domainStartPage)
 * 1225:     function setSysPageWhereClause()
 * 1237:     function findDomainRecord($recursive=0)
 * 1257:     function pageNotFoundAndExit($reason='', $header='')
 * 1272:     function pageNotFoundHandler($code, $header='', $reason='')
 * 1316:     function checkAndSetAlias()
 * 1335:     function idPartsAnalyze($str)
 * 1360:     function mergingWithGetVars($GET_VARS)
 * 1390:     function ADMCMD_preview()
 * 1433:     function ADMCMD_preview_postInit($previewConfig)
 *
 *              SECTION: Template and caching related functions.
 * 1465:     function makeCacheHash()
 * 1489:     function reqCHash()
 * 1511:     function cHashParams($addQueryParams)
 * 1520:     function initTemplate()
 * 1532:     function getFromCache()
 * 1578:     function getFromCache_queryRow()
 * 1608:     function headerNoCache()
 * 1637:     function getHash()
 * 1657:     function getConfigArray()
 *
 *              SECTION: Further initialization and data processing
 * 1818:     function getCompressedTCarray()
 * 1872:     function includeTCA($TCAloaded=1)
 * 1899:     function settingLanguage()
 * 1992:     function settingLocale()
 * 2017:     function checkDataSubmission()
 * 2050:     function fe_tce()
 * 2064:     function locDataCheck($locationData)
 * 2080:     function sendFormmail()
 * 2131:     function extractRecipientCopy($bodytext)
 * 2145:     function setExternalJumpUrl()
 * 2156:     function checkJumpUrlReferer()
 * 2171:     function jumpUrl()
 * 2215:     function setUrlIdToken()
 *
 *              SECTION: Page generation; cache handling
 * 2258:     function isGeneratePage()
 * 2268:     function tempPageCacheContent()
 * 2325:     function realPageCacheContent()
 * 2355:     function setPageCacheContent($content,$data,$tstamp)
 * 2382:     function clearPageCacheContent()
 * 2392:     function clearPageCacheContent_pidList($pidList)
 * 2426:     function setSysLastChanged()
 *
 *              SECTION: Page generation; rendering and inclusion
 * 2462:     function generatePage_preProcessing()
 * 2484:     function generatePage_whichScript()
 * 2496:     function generatePage_postProcessing()
 * 2588:     function INTincScript()
 * 2648:     function INTincScript_loadJSCode()
 * 2689:     function isINTincScript()
 * 2698:     function doXHTML_cleaning()
 * 2707:     function doLocalAnchorFix()
 *
 *              SECTION: Finished off; outputting, storing session data, statistics...
 * 2738:     function isOutputting()
 * 2761:     function processOutput()
 * 2834:     function sendCacheHeaders()
 * 2902:     function isStaticCacheble()
 * 2915:     function contentStrReplace()
 * 2941:     function isEXTincScript()
 * 2950:     function storeSessionData()
 * 2960:     function setParseTime()
 * 2972:     function statistics()
 * 3066:     function previewInfo()
 * 3101:     function hook_eofe()
 * 3117:     function beLoginLinkIPList()
 * 3138:     function addTempContentHttpHeaders()
 *
 *              SECTION: Various internal API functions
 * 3184:     function makeSimulFileName($inTitle,$page,$type,$addParams='',$no_cache='')
 * 3231:     function simulateStaticDocuments_pEnc_onlyP_proc($linkVars)
 * 3260:     function getSimulFileName()
 * 3271:     function setSimulReplacementChar()
 * 3291:     function fileNameASCIIPrefix($inTitle,$titleChars,$mergeChar='.')
 * 3314:     function encryptEmail($string,$back=0)
 * 3340:     function codeString($string, $decode=FALSE)
 * 3366:     function roundTripCryptString($string)
 * 3386:     function checkFileInclude($incFile)
 * 3401:     function newCObj()
 * 3414:     function setAbsRefPrefix()
 * 3428:     function baseUrlWrap($url)
 * 3447:     function printError($label,$header='Error!')
 * 3458:     function updateMD5paramsRecord($hash)
 * 3469:     function tidyHTML($content)
 * 3495:     function prefixLocalAnchorsWithScript()
 * 3505:     function workspacePreviewInit()
 * 3521:     function doWorkspacePreview()
 * 3531:     function whichWorkspace($returnTitle = FALSE)
 *
 *              SECTION: Various external API functions - for use in plugins etc.
 * 3589:     function getStorageSiterootPids()
 * 3604:     function getPagesTSconfig()
 * 3637:     function setJS($key,$content='')
 * 3677:     function setCSS($key,$content)
 * 3692:     function make_seed()
 * 3705:     function uniqueHash($str='')
 * 3714:     function set_no_cache()
 * 3724:     function set_cache_timeout_default($seconds)
 * 3740:     function plainMailEncoded($email,$subject,$message,$headers='')
 *
 *              SECTION: Localization and character set conversion
 * 3784:     function sL($input)
 * 3813:     function readLLfile($fileRef)
 * 3824:     function getLLL($index,$LOCAL_LANG)
 * 3838:     function initLLvars()
 * 3872:     function csConv($str,$from='')
 * 3890:     function convOutputCharset($content,$label='')
 * 3903:     function convPOSTCharset()
 *
 * TOTAL FUNCTIONS: 116
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * Main frontend class, instantiated in the index_ts.php script as the global object TSFE
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
 class tslib_fe {

		// CURRENT PAGE:
	var $id='';							// The page id (int)
	var $type='';						// RO The type (int)
	var $idParts=array();				// Loaded with the id, exploded by ','
	var $cHash='';						// The submitted cHash
	var $no_cache=''; 					// Page will not be cached. Write only true. Never clear value (some other code might have reasons to set it true)
	var $rootLine='';					// The rootLine (all the way to tree root, not only the current site!) (array)
	var $page='';						// The pagerecord (array)
	var $contentPid=0;					// This will normally point to the same value as id, but can be changed to point to another page from which content will then be displayed instead.
	protected $originalShortcutPage = null;	// gets set when we are processing a page of type shortcut in the early stages opf init.php when we do not know about languages yet, used later in init.php to determine the correct shortcut in case a translation changes the shortcut target (array)

	/**
	 * sys_page-object, pagefunctions
	 *
	 * @var t3lib_pageSelect
	 */
	var $sys_page='';
	var $jumpurl='';
	var $pageNotFound=0;				// Is set to 1 if a pageNotFound handler could have been called.
	var $domainStartPage=0;				// Domain start page
	var $pageAccessFailureHistory=array();	// Array containing a history of why a requested page was not accessible.
	var $MP='';
	var $RDCT='';
	var $page_cache_reg1=0;				// This can be set from applications as a way to tag cached versions of a page and later perform some external cache management, like clearing only a part of the cache of a page...
	var $siteScript='';					// Contains the value of the current script path that activated the frontend. Typically "index.php" but by rewrite rules it could be something else! Used for Speaking Urls / Simulate Static Documents.

		// USER

	/**
	 * The FE user
	 *
	 * @var tslib_feUserAuth
	 */
	var $fe_user='';
	var $loginUser='';					// Global flag indicating that a front-end user is logged in. This is set only if a user really IS logged in. The group-list may show other groups (like added by IP filter or so) even though there is no user.
	var $gr_list='';					// (RO=readonly) The group list, sorted numerically. Group '0,-1' is the default group, but other groups may be added by other means than a user being logged in though...
	var $beUserLogin='';				// Flag that indicates if a Backend user is logged in!
	var $workspacePreview='';			// Integer, that indicates which workspace is being previewed.
	var $loginAllowedInBranch = TRUE;	// Shows whether logins are allowed in branch
	var $loginAllowedInBranch_mode = '';	// Shows specific mode (all or groups)
	var $ADMCMD_preview_BEUSER_uid = 0;	// Integer, set to backend user ID to initialize when keyword-based preview is used.

		// PREVIEW
	var $fePreview='';					// Flag indication that preview is active. This is based on the login of a backend user and whether the backend user has read access to the current page. A value of 1 means ordinary preview, 2 means preview of a non-live workspace
	var $showHiddenPage='';				// Flag indicating that hidden pages should be shown, selected and so on. This goes for almost all selection of pages!
	var $showHiddenRecords='';			// Flag indicating that hidden records should be shown. This includes sys_template, pages_language_overlay and even fe_groups in addition to all other regular content. So in effect, this includes everything except pages.
	var $simUserGroup='0';				// Value that contains the simulated usergroup if any

		// CONFIGURATION
	var $TYPO3_CONF_VARS=array();		// The configuration array as set up in t3lib/config_default.php. Should be an EXACT copy of the global array.
	var $config='';						// "CONFIG" object from TypoScript. Array generated based on the TypoScript configuration of the current page. Saved with the cached pages.
	var $TCAcachedExtras=array();		// Array of cached information from TCA. This is NOT TCA itself!

		// TEMPLATE / CACHE

	/**
	 * The TypoScript template object. Used to parse the TypoScript template
	 *
	 * @var t3lib_TStemplate
	 */
	var $tmpl='';
	var $cacheTimeOutDefault = FALSE;		// Is set to the time-to-live time of cached pages. If false, default is 60*60*24, which is 24 hours.
	var $cacheContentFlag = 0;			// Set internally if cached content is fetched from the database
	var $cacheExpires=0;				// Set to the expire time of cached content
	var $isClientCachable=FALSE;		// Set if cache headers allowing caching are sent.
	var $all='';						// $all used by template fetching system. This array is an identification of the template. If $this->all is empty it's because the template-data is not cached, which it must be.
	var $sPre='';						// toplevel - objArrayName, eg 'page'
	var $pSetup='';						// TypoScript configuration of the page-object pointed to by sPre. $this->tmpl->setup[$this->sPre.'.']
	var $newHash='';					// This hash is unique to the template, the $this->id and $this->type vars and the gr_list (list of groups). Used to get and later store the cached data
	var $getMethodUrlIdToken='';		// If config.ftu (Frontend Track User) is set in TypoScript for the current page, the string value of this var is substituted in the rendered source-code with the string, '&ftu=[token...]' which enables GET-method usertracking as opposed to cookie based
	var $no_CacheBeforePageGen='';		// This flag is set before inclusion of pagegen.php IF no_cache is set. If this flag is set after the inclusion of pagegen.php, no_cache is forced to be set. This is done in order to make sure that php-code from pagegen does not falsely clear the no_cache flag.
	var $tempContent = FALSE;			// This flag indicates if temporary content went into the cache during page-generation.
	var $forceTemplateParsing='';				// Boolean, passed to TypoScript template class and tells it to render the template forcibly
	var $cHash_array=array();			// The array which cHash_calc is based on, see ->makeCacheHash().
	var $hash_base='';					// Loaded with the serialized array that is used for generating a hashstring for the cache
	var $pagesTSconfig='';				// May be set to the pagesTSconfig
		// PAGE-GENERATION / cOBJ
	/*
		Eg. insert JS-functions in this array ($additionalHeaderData) to include them once. Use associative keys.
		Keys in use:
			JSFormValidate	:		<script type="text/javascript" src="'.$GLOBALS["TSFE"]->absRefPrefix.'t3lib/jsfunc.validateform.js"></script>
			JSincludeFormupdate :	<script type="text/javascript" src="t3lib/jsfunc.updateform.js"></script>
			JSMenuCode, JSMenuCode_menu :			JavaScript for the JavaScript menu
			JSCode : reserved
			JSImgCode : reserved
	*/
	var $additionalHeaderData=array();	// used to accumulate additional HTML-code for the header-section, <head>...</head>. Insert either associative keys (like additionalHeaderData['myStyleSheet'], see reserved keys above) or num-keys (like additionalHeaderData[] = '...')
	var $additionalJavaScript=array();	// used to accumulate additional JavaScript-code. Works like additionalHeaderData. Reserved keys at 'openPic' and 'mouseOver'
	var $additionalCSS=array();			// used to accumulate additional Style code. Works like additionalHeaderData.
	var $JSeventFuncCalls = array(		// you can add JavaScript functions to each entry in these arrays. Please see how this is done in the GMENU_LAYERS script. The point is that many applications on a page can set handlers for onload, onmouseover and onmouseup
		'onmousemove' => array(),
		'onmouseup' => array(),
		'onmousemove' => array(),
		'onkeydown' => array(),
		'onkeyup' => array(),
		'onkeypress' => array(),
		'onload' => array(),
		'onunload' => array(),
	);
	/**
	 * Adds JavaScript code
	 *
	 * @var string
	 * @deprecated since TYPO3 3.5 - use additionalJavaScript instead.
	 */
	var $JSCode='';
	var $JSImgCode='';					// Used to accumulate JavaScript loaded images (by menus)
	var $divSection='';					// Used to accumulate DHTML-layers.
	var $defaultBodyTag='<body>';		// Default bodytag, if nothing else is set. This can be overridden by applications like TemplaVoila.

		// RENDERING configuration, settings from TypoScript is loaded into these vars. See pagegen.php
	var $debug='';						// Debug flag, may output special debug html-code.
	var $intTarget='';					// Default internal target
	var $extTarget='';					// Default external target
	var $fileTarget='';					// Default file link target
	var $MP_defaults=array();			// Keys are page ids and values are default &MP (mount point) values to set when using the linking features...)
	var $spamProtectEmailAddresses=0;	// If set, typolink() function encrypts email addresses. Is set in pagegen-class.
	var $absRefPrefix='';				// Absolute Reference prefix
	var $absRefPrefix_force=0;			// Absolute Reference prefix force flag. This is set, if the type and id is retrieve from PATH_INFO and thus we NEED to prefix urls with at least '/'
	var $compensateFieldWidth='';		// Factor for form-field widths compensation
	var $lockFilePath='';				// Lock file path
	var $ATagParams='';					// <A>-tag parameters
	var $sWordRegEx='';					// Search word regex, calculated if there has been search-words send. This is used to mark up the found search words on a page when jumped to from a link in a search-result.
	var $sWordList='';					// Is set to the incoming array sword_list in case of a page-view jumped to from a search-result.
	var $linkVars='';					// A string prepared for insertion in all links on the page as url-parameters. Based on configuration in TypoScript where you defined which GET_VARS you would like to pass on.
	var $excludeCHashVars='';			// A string set with a comma list of additional GET vars which should NOT be included in the cHash calculation. These vars should otherwise be detected and involved in caching, eg. through a condition in TypoScript.
	var $displayEditIcons='';			// If set, edit icons are rendered aside content records. Must be set only if the ->beUserLogin flag is set and set_no_cache() must be called as well.
	var $displayFieldEditIcons='';		// If set, edit icons are rendered aside individual fields of content. Must be set only if the ->beUserLogin flag is set and set_no_cache() must be called as well.
	var $sys_language_uid=0;			// Site language, 0 (zero) is default, int+ is uid pointing to a sys_language record. Should reflect which language menus, templates etc is displayed in (master language) - but not necessarily the content which could be falling back to default (see sys_language_content)
	var $sys_language_mode='';			// Site language mode for content fall back.
	var $sys_language_content=0;		// Site content selection uid (can be different from sys_language_uid if content is to be selected from a fall-back language. Depends on sys_language_mode)
	var $sys_language_contentOL=0;		// Site content overlay flag; If set - and sys_language_content is > 0 - , records selected will try to look for a translation pointing to their uid. (If configured in [ctrl][languageField] / [ctrl][transOrigP...]
	var $sys_language_isocode = '';		// Is set to the iso code of the sys_language_content if that is properly defined by the sys_language record representing the sys_language_uid. (Requires the extension "static_info_tables")

		// RENDERING data
	var $applicationData=Array();		//	 'Global' Storage for various applications. Keys should be 'tx_'.extKey for extensions.
	var $register=Array();
	var $registerStack=Array();			// Stack used for storing array and retrieving register arrays (see LOAD_REGISTER and CLEAR_REGISTER)
	var $cObjectDepthCounter = 50;		// Checking that the function is not called eternally. This is done by interrupting at a depth of 50
	var $recordRegister = Array();		// used by cObj->RECORDS and cObj->CONTENT to ensure the a records is NOT rendered twice through it!
	var $currentRecord = '';			// This is set to the [table]:[uid] of the latest record rendered. Note that class tslib_cObj has an equal value, but that is pointing to the record delivered in the $data-array of the tslib_cObj instance, if the cObjects CONTENT or RECORD created that instance
	var $accessKey =array();			// Used by class tslib_menu to keep track of access-keys.
	var $imagesOnPage=array();			// Numerical array where image filenames are added if they are referenced in the rendered document. This includes only TYPO3 generated/inserted images.
	var $lastImageInfo=array();			// Is set in tslib_cObj->cImage() function to the info-array of the most recent rendered image. The information is used in tslib_cObj->IMGTEXT
	var $uniqueCounter=0;				// Used to generate page-unique keys. Point is that uniqid() functions is very slow, so a unikey key is made based on this, see function uniqueHash()
	var $uniqueString='';
	var $indexedDocTitle='';			// This value will be used as the title for the page in the indexer (if indexing happens)
	var $altPageTitle='';				// Alternative page title (normally the title of the page record). Can be set from applications you make.
	/**
	 * An array that holds parameter names (keys) of GET parameters which MAY be MD5/base64 encoded with simulate_static_documents method.
	 * @var array
	 * @deprecated since TYPO3 4.3, remove in TYPO3 4.5
	 */
	var $pEncAllowedParamNames=array();
	var $baseUrl='';					// The base URL set for the page header.
	var $anchorPrefix='';				// The proper anchor prefix needed when using speaking urls. (only set if baseUrl is set)

	/**
	 * Page content render object
	 *
	 * @var tslib_cObj
	 */
	var $cObj ='';

		// CONTENT accumulation
	var $content='';					// All page content is accumulated in this variable. See pagegen.php

		// GENERAL
	var $clientInfo='';					// Set to the browser: net / msie if 4+ browsers
	var $scriptParseTime=0;
	var $TCAloaded = 0;					// Set ONLY if the full TCA is loaded

		// Character set (charset) conversion object:

	/**
	 * charset conversion class. May be used by any application.
	 *
	 * @var t3lib_cs
	 */
	var $csConvObj;
	var $defaultCharSet = 'iso-8859-1';	// The default charset used in the frontend if nothing else is set.
	var $renderCharset='';				// Internal charset of the frontend during rendering: Defaults to "forceCharset" and if that is not set, to ->defaultCharSet
	var $metaCharset='';				// Output charset of the websites content. This is the charset found in the header, meta tag etc. If different from $renderCharset a conversion happens before output to browser. Defaults to ->renderCharset if not set.
	var $localeCharset='';				// Assumed charset of locale strings.

		// LANG:
	var $lang='';						// Set to the system language key (used on the site)
	var $langSplitIndex=0;				// Set to the index number of the language key
	var $LL_labels_cache=array();
	var $LL_files_cache=array();

	/**
	 * Locking object
	 *
	 * @var t3lib_lock
	 */
	var $pagesection_lockObj;				// Locking object for accessing "cache_pagesection"

	/**
	 * Locking object
	 *
	 * @var t3lib_lock
	 */
	var $pages_lockObj;					// Locking object for accessing "cache_pages"

	/**
	 * @var t3lib_PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * the page cache object, use this to save pages to the cache and to
	 * retrieve them again
	 *
	 * @var t3lib_cache_AbstractBackend
	 */
	protected $pageCache;
	protected $pageCacheTags = array();


	/**
	 * Class constructor
	 * Takes a number of GET/POST input variable as arguments and stores them internally.
	 * The processing of these variables goes on later in this class.
	 * Also sets internal clientInfo array (browser information) and a unique string (->uniqueString) for this script instance; A md5 hash of the microtime()
	 *
	 * @param	array		The global $TYPO3_CONF_VARS array. Will be set internally in ->TYPO3_CONF_VARS
	 * @param	mixed		The value of t3lib_div::_GP('id')
	 * @param	integer		The value of t3lib_div::_GP('type')
	 * @param	boolean		The value of t3lib_div::_GP('no_cache'), evaluated to 1/0
	 * @param	string		The value of t3lib_div::_GP('cHash')
	 * @param	string		The value of t3lib_div::_GP('jumpurl')
	 * @param	string		The value of t3lib_div::_GP('MP')
	 * @param	string		The value of t3lib_div::_GP('RDCT')
	 * @return	void
	 * @see index_ts.php
	 */
	function tslib_fe($TYPO3_CONF_VARS, $id, $type, $no_cache='', $cHash='', $jumpurl='',$MP='',$RDCT='')	{

			// Setting some variables:
		$this->TYPO3_CONF_VARS = $TYPO3_CONF_VARS;
		$this->id = $id;
		$this->type = $type;
		if ($no_cache) {
			if ($this->TYPO3_CONF_VARS['FE']['disableNoCacheParameter']) {
				$warning = '&no_cache=1 has been ignored because $TYPO3_CONF_VARS[\'FE\'][\'disableNoCacheParameter\'] is set!';
				$GLOBALS['TT']->setTSlogMessage($warning,2);
			} else {
				$warning = '&no_cache=1 has been supplied, so caching is disabled! URL: "'.t3lib_div::getIndpEnv('TYPO3_REQUEST_URL').'"';
				$this->disableCache();
			}
			t3lib_div::sysLog($warning, 'cms', 2);
		}
		$this->cHash = $cHash;
		$this->jumpurl = $jumpurl;
		$this->MP = $this->TYPO3_CONF_VARS['FE']['enable_mount_pids'] ? (string)$MP : '';
		$this->RDCT = $RDCT;
		$this->clientInfo = t3lib_div::clientInfo();
		$this->uniqueString=md5(microtime());

		$this->csConvObj = t3lib_div::makeInstance('t3lib_cs');

			// Call post processing function for constructor:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc']))	{
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

		if (TYPO3_UseCachingFramework) {
			$this->initCaches();
		}
	}

	/**
	 * Connect to SQL database
	 * May exit after outputting an error message or some JavaScript redirecting to the install tool.
	 *
	 * @return	void
	 */
	function connectToDB()	{
		if (!TYPO3_db) {
				// jump into Install Tool 1-2-3 mode, if no DB name is defined (fresh installation)
			t3lib_utility_Http::redirect(TYPO3_mainDir.'install/index.php?mode=123&step=1&password=joh316');
		}

			// sql_pconnect() can throw an Exception in case of some failures, or it returns FALSE
		$link = $GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password);
		if ($link !== FALSE) {
				// Connection to DB server ok, now select the database
			if (!$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db))	{
				if ($this->checkPageUnavailableHandler())	{
					$this->pageUnavailableAndExit('Cannot connect to the configured database "'.TYPO3_db.'"');
				} else {
					$message = 'Cannot connect to the configured database "'.TYPO3_db.'"';
					t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
					header('HTTP/1.0 503 Service Temporarily Unavailable');
					throw new RuntimeException('Database Error: ' . $message, 1293617736);
				}
			}
		} else {
			if ($this->checkPageUnavailableHandler())	{
				$this->pageUnavailableAndExit('The current username, password or host was not accepted when the connection to the database was attempted to be established!');
			} else {
				$message = 'The current username, password or host was not accepted when the connection to the database was attempted to be established!';
				t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
				header('HTTP/1.0 503 Service Temporarily Unavailable');
				throw new RuntimeException('Database Error: ' . $message, 1293617741);
			}
		}


			// Call post processing function for DB connection:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']))	{
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}
	}

	/**
	 * Looks up the value of $this->RDCT in the database and if it is found to be associated with a redirect URL then the redirection is carried out with a 'Location:' header
	 * May exit after sending a location-header.
	 *
	 * @return	void
	 */
	function sendRedirect()	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('params', 'cache_md5params', 'md5hash='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->RDCT, 'cache_md5params'));
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$this->updateMD5paramsRecord($this->RDCT);
			header('Location: '.$row['params']);
			exit;
		}
	}

	/**
	 * Gets instance of PageRenderer
	 *
	 * @return	t3lib_PageRenderer
	 */
	public function getPageRenderer() {
		if (!isset($this->pageRenderer)) {
			$this->pageRenderer = t3lib_div::makeInstance('t3lib_PageRenderer');
			$this->pageRenderer->setTemplateFile(PATH_tslib . 'templates/tslib_page_frontend.html');
			$this->pageRenderer->setBackPath(TYPO3_mainDir);
		}
		return $this->pageRenderer;
	}
















	/********************************************
	 *
	 * Initializing, resolving page id
	 *
	 ********************************************/

	/**
	 * Initializes the caching system.
	 *
	 * @return	void
	 */
	protected function initCaches() {
		if (TYPO3_UseCachingFramework) {
			$GLOBALS['TT']->push('Initializing the Caching System','');

			$GLOBALS['typo3CacheManager'] = t3lib_div::makeInstance('t3lib_cache_Manager');
			$GLOBALS['typo3CacheFactory'] = t3lib_div::makeInstance('t3lib_cache_Factory');
			$GLOBALS['typo3CacheFactory']->setCacheManager($GLOBALS['typo3CacheManager']);

			try {
				$this->pageCache = $GLOBALS['typo3CacheManager']->getCache(
					'cache_pages'
				);
			} catch(t3lib_cache_exception_NoSuchCache $e) {
				t3lib_cache::initPageCache();

				$this->pageCache = $GLOBALS['typo3CacheManager']->getCache(
					'cache_pages'
				);
			}

			t3lib_cache::initPageSectionCache();
			t3lib_cache::initContentHashCache();

			$GLOBALS['TT']->pull();
		}
	}

	/**
	 * Initializes the front-end login user.
	 *
	 * @return	void
	 */
	function initFEuser()	{
		$this->fe_user = t3lib_div::makeInstance('tslib_feUserAuth');

		$this->fe_user->lockIP = $this->TYPO3_CONF_VARS['FE']['lockIP'];
		$this->fe_user->checkPid = $this->TYPO3_CONF_VARS['FE']['checkFeUserPid'];
		$this->fe_user->lifetime = intval($this->TYPO3_CONF_VARS['FE']['lifetime']);
		$this->fe_user->checkPid_value = $GLOBALS['TYPO3_DB']->cleanIntList(t3lib_div::_GP('pid'));	// List of pid's acceptable

			// Check if a session is transferred:
		if (t3lib_div::_GP('FE_SESSION_KEY'))	{
			$fe_sParts = explode('-',t3lib_div::_GP('FE_SESSION_KEY'));
			if (!strcmp(md5($fe_sParts[0].'/'.$this->TYPO3_CONF_VARS['SYS']['encryptionKey']), $fe_sParts[1]))	{	// If the session key hash check is OK:
				$_COOKIE[$this->fe_user->name] = $fe_sParts[0];
				$this->fe_user->forceSetCookie = 1;
			}
		}

		if ($this->TYPO3_CONF_VARS['FE']['dontSetCookie'])	{
			$this->fe_user->dontSetCookie=1;
		}

		$this->fe_user->start();
		$this->fe_user->unpack_uc('');
		$this->fe_user->fetchSessionData();	// Gets session data
		$recs = t3lib_div::_GP('recs');
		if (is_array($recs))	{	// If any record registration is submitted, register the record.
			$this->fe_user->record_registration($recs, $this->TYPO3_CONF_VARS['FE']['maxSessionDataSize']);
		}

			// Call hook for possible manipulation of frontend user object
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser']))	{
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

			// For every 60 seconds the is_online timestamp is updated.
		if (is_array($this->fe_user->user) && $this->fe_user->user['uid'] && $this->fe_user->user['is_online']<($GLOBALS['EXEC_TIME']-60))	{
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.intval($this->fe_user->user['uid']), array('is_online' => $GLOBALS['EXEC_TIME']));
		}
	}

	/**
	 * Initializes the front-end user groups.
	 * Sets ->loginUser and ->gr_list based on front-end user status.
	 *
	 * @return	void
	 */
	function initUserGroups() {

		$this->fe_user->showHiddenRecords = $this->showHiddenRecords;		// This affects the hidden-flag selecting the fe_groups for the user!
		$this->fe_user->fetchGroupData(); 	// no matter if we have an active user we try to fetch matching groups which can be set without an user (simulation for instance!)

		if (is_array($this->fe_user->user) && count($this->fe_user->groupData['uid']))	{
			$this->loginUser=1;	// global flag!
			$this->gr_list = '0,-2';	// group -2 is not an existing group, but denotes a 'default' group when a user IS logged in. This is used to let elements be shown for all logged in users!
			$gr_array = $this->fe_user->groupData['uid'];
		} else {
			$this->loginUser=0;
			$this->gr_list = '0,-1';	// group -1 is not an existing group, but denotes a 'default' group when not logged in. This is used to let elements be hidden, when a user is logged in!

			if ($this->loginAllowedInBranch)	{
				$gr_array = $this->fe_user->groupData['uid'];	// For cases where logins are not banned from a branch usergroups can be set based on IP masks so we should add the usergroups uids.
			} else {
				$gr_array = array();		// Set to blank since we will NOT risk any groups being set when no logins are allowed!
			}
		}

			// Clean up.
		$gr_array = array_unique($gr_array);	// Make unique...
		sort($gr_array);	// sort
		if (count($gr_array) && !$this->loginAllowedInBranch_mode)	{
			$this->gr_list.=','.implode(',',$gr_array);
		}

		if ($this->fe_user->writeDevLog) 	t3lib_div::devLog('Valid usergroups for TSFE: '.$this->gr_list, 'tslib_fe');
	}

	/**
	 * Checking if a user is logged in or a group constellation different from "0,-1"
	 *
	 * @return	boolean		TRUE if either a login user is found (array fe_user->user) OR if the gr_list is set to something else than '0,-1' (could be done even without a user being logged in!)
	 */
	function isUserOrGroupSet()	{
		return is_array($this->fe_user->user) || $this->gr_list!=='0,-1';
	}

	/**
	 * Provides ways to bypass the '?id=[xxx]&type=[xx]' format, using either PATH_INFO or virtual HTML-documents (using Apache mod_rewrite)
	 *
	 * Two options:
	 * 1) Use PATH_INFO (also Apache) to extract id and type from that var. Does not require any special modules compiled with apache. (less typical)
	 * 2) Using hook which enables features like those provided from "simulatestatic" or "realurl" extension (AKA "Speaking URLs")
	 *
	 * @return	void
	 */
	function checkAlternativeIdMethods()	{
		$this->siteScript = t3lib_div::getIndpEnv('TYPO3_SITE_SCRIPT');

			// Call post processing function for custom URL methods.
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']))	{
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}
	}

	/**
	 * Clears the preview-flags, sets sim_exec_time to current time.
	 * Hidden pages must be hidden as default, $GLOBALS['SIM_EXEC_TIME'] is set to $GLOBALS['EXEC_TIME'] in t3lib/config_default.inc. Alter it by adding or subtracting seconds.
	 *
	 * @return	void
	 */
	function clear_preview()	{
		$this->showHiddenPage = 0;
		$this->showHiddenRecords = 0;
		$GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
		$GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];
		$this->fePreview = 0;
	}

	/**
	 * Determines the id and evaluates any preview settings
	 * Basically this function is about determining whether a backend user is logged in, if he has read access to the page and if he's previewing the page. That all determines which id to show and how to initialize the id.
	 *
	 * @return	void
	 */
	function determineId()	{

			// Getting ARG-v values if some
		$this->setIDfromArgV();

			// If there is a Backend login we are going to check for any preview settings:
		$GLOBALS['TT']->push('beUserLogin','');
		if ($this->beUserLogin || $this->doWorkspacePreview())	{

				// Backend user preview features:
			if ($this->beUserLogin && ($GLOBALS['BE_USER']->adminPanel instanceof tslib_AdminPanel)) {
				$this->fePreview = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview') ? true : false;

					// If admin panel preview is enabled...
				if ($this->fePreview)	{
					$fe_user_OLD_USERGROUP = $this->fe_user->user['usergroup'];

					$this->showHiddenPage = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview', 'showHiddenPages');
					$this->showHiddenRecords = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview', 'showHiddenRecords');
						// simulate date
					$simTime = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview', 'simulateDate');
					if ($simTime)	{
						$GLOBALS['SIM_EXEC_TIME'] = $simTime;
						$GLOBALS['SIM_ACCESS_TIME'] = $simTime - ($simTime % 60);
					}
						// simulate user
					$simUserGroup = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview', 'simulateUserGroup');
					$this->simUserGroup = $simUserGroup;
					if ($simUserGroup)	$this->fe_user->user['usergroup']=$simUserGroup;
					if (!$simUserGroup && !$simTime && !$this->showHiddenPage && !$this->showHiddenRecords)	{
						$this->fePreview=0;
					}
				}
			}

			if ($this->id)	{

					// Now it's investigated if the raw page-id points to a hidden page and if so, the flag is set.
					// This does not require the preview flag to be set in the admin panel
				$idQ = t3lib_div::testInt($this->id) ? 'uid='.intval($this->id) : 'alias='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, 'pages').' AND pid>=0';	// pid>=0 added for the sake of versioning...
				$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'pages', $idQ . ' AND hidden!=0 AND deleted=0');
				if ($count) {
					$this->fePreview = 1;	// The preview flag is set only if the current page turns out to actually be hidden!
					$this->showHiddenPage = 1;
				}

					// For Live workspace: Check root line for proper connection to tree root (done because of possible preview of page / branch versions)
				if (!$this->fePreview && $this->whichWorkspace()===0)	{

						// Initialize the page-select functions to check rootline:
					$temp_sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
					$temp_sys_page->init($this->showHiddenPage);
						// If root line contained NO records and ->error_getRootLine_failPid tells us that it was because of a pid=-1 (indicating a "version" record)...:
					if (!count($temp_sys_page->getRootLine($this->id,$this->MP)) && $temp_sys_page->error_getRootLine_failPid==-1)	{

							// Setting versioningPreview flag and try again:
						$temp_sys_page->versioningPreview = TRUE;
						if (count($temp_sys_page->getRootLine($this->id,$this->MP)))	{
								// Finally, we got a root line (meaning that it WAS due to versioning preview of a page somewhere) and we set the fePreview flag which in itself will allow sys_page class to display previews of versionized records.
							$this->fePreview = 1;
						}
					}
				}
			}

				// The preview flag will be set if a backend user is in an offline workspace
			if (($GLOBALS['BE_USER']->user['workspace_preview'] || t3lib_div::_GP('ADMCMD_view') || $this->doWorkspacePreview()) && ($this->whichWorkspace()===-1 || $this->whichWorkspace()>0))	{
				$this->fePreview = 2;	// Will show special preview message.
			}

				// If the front-end is showing a preview, caching MUST be disabled.
			if ($this->fePreview)	{
				$this->disableCache();
			}
		}
		$GLOBALS['TT']->pull();

			// Now, get the id, validate access etc:
		$this->fetch_the_id();

			// Check if backend user has read access to this page. If not, recalculate the id.
		if ($this->beUserLogin && $this->fePreview)	{
			if (!$GLOBALS['BE_USER']->doesUserHaveAccess($this->page,1))	{

					// Resetting
				$this->clear_preview();
				$this->fe_user->user['usergroup'] = $fe_user_OLD_USERGROUP;

					// Fetching the id again, now with the preview settings reset.
				$this->fetch_the_id();
			}
		}

			// Checks if user logins are blocked for a certain branch and if so, will unset user login and re-fetch ID.
		$this->loginAllowedInBranch = $this->checkIfLoginAllowedInBranch();
		if (!$this->loginAllowedInBranch)	{	// Logins are not allowed:
			if ($this->isUserOrGroupSet())	{	// Only if there is a login will we run this...
				if ($this->loginAllowedInBranch_mode=='all')	{
						// Clear out user and group:
					unset($this->fe_user->user);
					$this->gr_list = '0,-1';
				} else {
					$this->gr_list = '0,-2';
				}

					// Fetching the id again, now with the preview settings reset.
				$this->fetch_the_id();
			}
		}

			// Final cleaning.
		$this->id = $this->contentPid = intval($this->id);	// Make sure it's an integer
		$this->type = intval($this->type);	// Make sure it's an integer

			// Look for alternative content PID if page is under version preview:
		if ($this->fePreview)	{
			if ($this->page['_ORIG_pid']==-1 && $this->page['t3ver_swapmode']==0)	{	// Current page must have been an offline version and have swapmode set to 0:
					// Setting contentPid here for preview might not be completely correct to do. Strictly the "_ORIG_uid" value should be used for tables where "versioning_followPages" is set and for others not. However this is a working quick-fix to display content elements at least!
				$this->contentPid = $this->page['_ORIG_uid'];
			}
		}

			// Call post processing function for id determination:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc']))	{
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}
	}

	/**
	 * Get The Page ID
	 * This gets the id of the page, checks if the page is in the domain and if the page is accessible
	 * Sets variables such as $this->sys_page, $this->loginUser, $this->gr_list, $this->id, $this->type, $this->domainStartPage, $this->idParts
	 *
	 * @return	void
	 * @access private
	 */
	function fetch_the_id()	{
		$GLOBALS['TT']->push('fetch_the_id initialize/','');

			// Initialize the page-select functions.
		$this->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$this->sys_page->versioningPreview = ($this->fePreview===2 || intval($this->workspacePreview) || t3lib_div::_GP('ADMCMD_view')) ? TRUE : FALSE;
		$this->sys_page->versioningWorkspaceId = $this->whichWorkspace();
		$this->sys_page->init($this->showHiddenPage);

			// Set the valid usergroups for FE
		$this->initUserGroups();

			// Sets sys_page where-clause
		$this->setSysPageWhereClause();

			// Splitting $this->id by a period (.). First part is 'id' and second part - if exists - will overrule the &type param if given
		$pParts = explode('.',$this->id);
		$this->id = $pParts[0];	// Set it.
		if (isset($pParts[1]))	{$this->type=$pParts[1];}

			// Splitting $this->id by a comma (,). First part is 'id' and other parts are just stored for use in scripts.
		$this->idParts = explode(',',$this->id);

			// Splitting by a '+' sign - used for base64/md5 methods of parameter encryption for simulate static documents.
		list($pgID,$SSD_p)=explode('+',$this->idParts[0],2);
		if ($SSD_p)	{	$this->idPartsAnalyze($SSD_p);	}
		$this->id = $pgID;	// Set id

			// If $this->id is a string, it's an alias
		$this->checkAndSetAlias();

			// The id and type is set to the integer-value - just to be sure...
		$this->id = intval($this->id);
		$this->type = intval($this->type);
		$GLOBALS['TT']->pull();

			// We find the first page belonging to the current domain
		$GLOBALS['TT']->push('fetch_the_id domain/','');
		$this->domainStartPage = $this->findDomainRecord($this->TYPO3_CONF_VARS['SYS']['recursiveDomainSearch']);	// the page_id of the current domain
		if (!$this->id)	{
			if ($this->domainStartPage)	{
				$this->id = $this->domainStartPage;	// If the id was not previously set, set it to the id of the domain.
			} else {
				$theFirstPage = $this->sys_page->getFirstWebPage($this->id);	// Find the first 'visible' page in that domain
				if ($theFirstPage)	{
					$this->id = $theFirstPage['uid'];
				} else {
					if ($this->checkPageUnavailableHandler())	{
						$this->pageUnavailableAndExit('No pages are found on the rootlevel!');
					} else {
						$message = 'No pages are found on the rootlevel!';
						t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
						header('HTTP/1.0 503 Service Temporarily Unavailable');
						throw new RuntimeException($message, 1294587207);
					}
				}
			}
		}
		$GLOBALS['TT']->pull();

		$GLOBALS['TT']->push('fetch_the_id rootLine/','');
		$requestedId = $this->id;		// We store the originally requested id
		$this->getPageAndRootlineWithDomain($this->domainStartPage);
		$GLOBALS['TT']->pull();

		if ($this->pageNotFound && $this->TYPO3_CONF_VARS['FE']['pageNotFound_handling'])	{
			$pNotFoundMsg = array(
				1 => 'ID was not an accessible page',
				2 => 'Subsection was found and not accessible',
				3 => 'ID was outside the domain',
				4 => 'The requested page alias does not exist'
			);
			$this->pageNotFoundAndExit($pNotFoundMsg[$this->pageNotFound]);
		}

		if ($this->page['url_scheme'] > 0) {
			$newUrl = '';
			$requestUrlScheme = parse_url(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'), PHP_URL_SCHEME);
			if ((int) $this->page['url_scheme'] === t3lib_utility_http::SCHEME_HTTP && $requestUrlScheme == 'https') {
				$newUrl = 'http://' . substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'), 8);
			} elseif ((int) $this->page['url_scheme'] === t3lib_utility_http::SCHEME_HTTPS && $requestUrlScheme == 'http') {
				$newUrl = 'https://' . substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'), 7);
			}
			if ($newUrl !== '') {
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$headerCode = t3lib_utility_Http::HTTP_STATUS_303;
				} else {
					$headerCode = t3lib_utility_Http::HTTP_STATUS_301;
				}
				t3lib_utility_http::redirect($newUrl, $headerCode);
			}
		}
			// set no_cache if set
		if ($this->page['no_cache'])	{
			$this->set_no_cache();
		}

			// Init SYS_LASTCHANGED
		$this->register['SYS_LASTCHANGED'] = intval($this->page['tstamp']);
		if ($this->register['SYS_LASTCHANGED'] < intval($this->page['SYS_LASTCHANGED']))	{
			$this->register['SYS_LASTCHANGED'] = intval($this->page['SYS_LASTCHANGED']);
		}
	}

	/**
	 * Gets the page and rootline arrays based on the id, $this->id
	 *
	 * If the id does not correspond to a proper page, the 'previous' valid page in the rootline is found
	 * If the page is a shortcut (doktype=4), the ->id is loaded with that id
	 *
	 * Whether or not the ->id is changed to the shortcut id or the previous id in rootline (eg if a page is hidden), the ->page-array and ->rootline is found and must also be valid.
	 *
	 * Sets or manipulates internal variables such as: $this->id, $this->page, $this->rootLine, $this->MP, $this->pageNotFound
	 *
	 * @return	void
	 * @access private
	 */
	function getPageAndRootline() {
		$this->page = $this->sys_page->getPage($this->id);
		if (!count($this->page))	{
				// If no page, we try to find the page before in the rootLine.
			$this->pageNotFound=1;			// Page is 'not found' in case the id itself was not an accessible page. code 1
			$this->rootLine = $this->sys_page->getRootLine($this->id,$this->MP);
			if (count($this->rootLine))	{
				$c=count($this->rootLine)-1;
				while($c>0)	{

						// Add to page access failure history:
					$this->pageAccessFailureHistory['direct_access'][] = $this->rootLine[$c];

						// Decrease to next page in rootline and check the access to that, if OK, set as page record and ID value.
					$c--;
					$this->id = $this->rootLine[$c]['uid'];
					$this->page = $this->sys_page->getPage($this->id);
					if (count($this->page)) { break; }
				}
			}
				// If still no page...
			if (!count($this->page))	{
				if ($this->TYPO3_CONF_VARS['FE']['pageNotFound_handling'])	{
					$this->pageNotFoundAndExit('The requested page does not exist!');
				} else {
					$message = 'The requested page does not exist!';
					header('HTTP/1.0 404 Page Not Found');
					t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
					throw new RuntimeException($message, 1294587208);
				}
			}
		}

			// Spacer is not accessible in frontend
		if ($this->page['doktype'] == t3lib_pageSelect::DOKTYPE_SPACER) {
			if ($this->TYPO3_CONF_VARS['FE']['pageNotFound_handling'])	{
				$this->pageNotFoundAndExit('The requested page does not exist!');
			} else {
				$message = 'The requested page does not exist!';
				header('HTTP/1.0 404 Page Not Found');
				t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
				throw new RuntimeException($message, 1294587209);
			}
		}

			// Is the ID a link to another page??
		if ($this->page['doktype'] == t3lib_pageSelect::DOKTYPE_SHORTCUT) {
			$this->MP = '';		// We need to clear MP if the page is a shortcut. Reason is if the short cut goes to another page, then we LEAVE the rootline which the MP expects.

				// saving the page so that we can check later - when we know
				// about languages - whether we took the correct shortcut or
				// whether a translation of the page overwrites the shortcut
				// target and we need to follow the new target
			$this->originalShortcutPage = $this->page;

			$this->page = $this->getPageShortcut($this->page['shortcut'],$this->page['shortcut_mode'],$this->page['uid']);
			$this->id = $this->page['uid'];
		}

			// Gets the rootLine
		$this->rootLine = $this->sys_page->getRootLine($this->id,$this->MP);

			// If not rootline we're off...
		if (!count($this->rootLine))	{
			$ws = $this->whichWorkspace();
			if ($this->sys_page->error_getRootLine_failPid==-1 && $ws) {
				$this->sys_page->versioningPreview = TRUE;
				$this->versioningWorkspaceId = $ws;
				$this->rootLine = $this->sys_page->getRootLine($this->id,$this->MP);
			}
			if (!count($this->rootLine))	{
				if ($this->checkPageUnavailableHandler())	{
					$this->pageUnavailableAndExit('The requested page didn\'t have a proper connection to the tree-root!');
				} else {
					$message = 'The requested page didn\'t have a proper connection to the tree-root! <br /><br />('.$this->sys_page->error_getRootLine.')';
					header('HTTP/1.0 503 Service Temporarily Unavailable');
					t3lib_div::sysLog(str_replace('<br /><br />','',$message), 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
					throw new RuntimeException($message, 1294587210);
				}
			}
			$this->fePreview = 1;
		}

			// Checking for include section regarding the hidden/starttime/endtime/fe_user (that is access control of a whole subbranch!)
		if ($this->checkRootlineForIncludeSection())	{
			if (!count($this->rootLine))	{
				if ($this->checkPageUnavailableHandler())	{
					$this->pageUnavailableAndExit('The requested page was not accessible!');
				} else {
					$message = 'The requested page was not accessible!';
					header('HTTP/1.0 503 Service Temporarily Unavailable');
					t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
					throw new RuntimeException($message, 1294587211);
				}
			} else {
				$el = reset($this->rootLine);
				$this->id = $el['uid'];
				$this->page = $this->sys_page->getPage($this->id);
				$this->rootLine = $this->sys_page->getRootLine($this->id,$this->MP);
			}
		}
	}

	/**
	 * Get page shortcut; Finds the records pointed to by input value $SC (the shortcut value)
	 *
	 * @param	integer		The value of the "shortcut" field from the pages record
	 * @param	integer		The shortcut mode: 1 will select first subpage, 2 a random subpage, 3 the parent page; default is the page pointed to by $SC
	 * @param	integer		The current page UID of the page which is a shortcut
	 * @param	integer		Safety feature which makes sure that the function is calling itself recursively max 20 times (since this function can find shortcuts to other shortcuts to other shortcuts...)
	 * @param	array		An array filled with previous page uids tested by the function - new page uids are evaluated against this to avoid going in circles.
	 * @return	mixed		Returns the page record of the page that the shortcut pointed to.
	 * @access private
	 * @see getPageAndRootline()
	 */
	function getPageShortcut($SC,$mode,$thisUid,$itera=20,$pageLog=array())	{
		$idArray = t3lib_div::intExplode(',',$SC);

			// Find $page record depending on shortcut mode:
		switch($mode)	{
			case t3lib_pageSelect::SHORTCUT_MODE_FIRST_SUBPAGE:
			case t3lib_pageSelect::SHORTCUT_MODE_RANDOM_SUBPAGE:
				$pageArray = $this->sys_page->getMenu(($idArray[0] ? $idArray[0] : $thisUid), '*', 'sorting', 'AND pages.doktype<199 AND pages.doktype!=' . t3lib_pageSelect::DOKTYPE_BE_USER_SECTION);
				$pO = 0;
				if ($mode == t3lib_pageSelect::SHORTCUT_MODE_RANDOM_SUBPAGE && count($pageArray)) {
					$randval = intval(rand(0,count($pageArray)-1));
					$pO = $randval;
				}
				$c = 0;
				foreach ($pageArray as $pV) {
					if ($c==$pO)	{
						$page = $pV;
						break;
					}
					$c++;
				}
			break;
			case t3lib_pageSelect::SHORTCUT_MODE_PARENT_PAGE:
				$parent = $this->sys_page->getPage($thisUid);
				$page = $this->sys_page->getPage($parent['pid']);
			break;
			default:
				$page = $this->sys_page->getPage($idArray[0]);
			break;
		}

			// Check if short cut page was a shortcut itself, if so look up recursively:
		if ($page['doktype'] == t3lib_pageSelect::DOKTYPE_SHORTCUT) {
			if (!in_array($page['uid'],$pageLog) && $itera>0)	{
				$pageLog[] = $page['uid'];
				$page = $this->getPageShortcut($page['shortcut'],$page['shortcut_mode'],$page['uid'],$itera-1,$pageLog);
			} else {
				$pageLog[] = $page['uid'];
				$message = 'Page shortcuts were looping in uids '.implode(',',$pageLog).'...!';
				header('HTTP/1.0 500 Internal Server Error');
				t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
				throw new RuntimeException($message, 1294587212);
			}
		}
			// Return resulting page:
		return $page;
	}

	/**
	 * Checks the current rootline for defined sections.
	 *
	 * @return	boolean
	 * @access private
	 */
	function checkRootlineForIncludeSection()	{
		$c=count($this->rootLine);
		$removeTheRestFlag=0;

		for ($a=0;$a<$c;$a++)	{
			if (!$this->checkPagerecordForIncludeSection($this->rootLine[$a]))	{
					// Add to page access failure history:
				$this->pageAccessFailureHistory['sub_section'][] = $this->rootLine[$a];
				$removeTheRestFlag=1;
			}
			if ($this->rootLine[$a]['doktype'] == t3lib_pageSelect::DOKTYPE_BE_USER_SECTION) {
				if ($this->beUserLogin)	{	// If there is a backend user logged in, check if he has read access to the page:
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'uid='.intval($this->id).' AND '.$GLOBALS['BE_USER']->getPagePermsClause(1));	// versionOL()?
					list($isPage) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
					if (!$isPage)	$removeTheRestFlag=1;	// If there was no page selected, the user apparently did not have read access to the current PAGE (not position in rootline) and we set the remove-flag...
				} else {	// Dont go here, if there is no backend user logged in.
					$removeTheRestFlag=1;
				}
			}
			if ($removeTheRestFlag)	{
				$this->pageNotFound=2;			// Page is 'not found' in case a subsection was found and not accessible, code 2
				unset($this->rootLine[$a]);
			}
		}
		return $removeTheRestFlag;
	}

	/**
	 * Checks page record for enableFields
	 * Returns true if enableFields does not disable the page record.
	 * Takes notice of the ->showHiddenPage flag and uses SIM_ACCESS_TIME for start/endtime evaluation
	 *
	 * @param	array		The page record to evaluate (needs fields: hidden, starttime, endtime, fe_group)
	 * @param	boolean		Bypass group-check
	 * @return	boolean		True, if record is viewable.
	 * @see tslib_cObj::getTreeList(), checkPagerecordForIncludeSection()
	 */
	function checkEnableFields($row,$bypassGroupCheck=FALSE)	{
		if ((!$row['hidden'] || $this->showHiddenPage)
			&& $row['starttime']<=$GLOBALS['SIM_ACCESS_TIME']
			&& ($row['endtime']==0 || $row['endtime']>$GLOBALS['SIM_ACCESS_TIME'])
			&& ($bypassGroupCheck || $this->checkPageGroupAccess($row))
		) { return TRUE; }
	}

	/**
	 * Check group access against a page record
	 *
	 * @param	array		The page record to evaluate (needs field: fe_group)
	 * @param	mixed		List of group id's (comma list or array). Default is $this->gr_list
	 * @return	boolean		True, if group access is granted.
	 * @access private
	 */
	function checkPageGroupAccess($row, $groupList=NULL) {
		if(is_null($groupList)) {
			$groupList = $this->gr_list;
		}
		if(!is_array($groupList)) {
			$groupList = explode(',', $groupList);
		}
		$pageGroupList = explode(',', $row['fe_group'] ? $row['fe_group'] : 0);
		return count(array_intersect($groupList, $pageGroupList)) > 0;
	}

	/**
	 * Checks page record for include section
	 *
	 * @param	array		The page record to evaluate (needs fields: extendToSubpages + hidden, starttime, endtime, fe_group)
	 * @return	boolean		Returns true if either extendToSubpages is not checked or if the enableFields does not disable the page record.
	 * @access private
	 * @see checkEnableFields(), tslib_cObj::getTreeList(), checkRootlineForIncludeSection()
	 */
	function checkPagerecordForIncludeSection($row)	{
		return (!$row['extendToSubpages'] || $this->checkEnableFields($row)) ? 1 : 0;
	}

	/**
	 * Checks if logins are allowed in the current branch of the page tree. Traverses the full root line and returns TRUE if logins are OK, otherwise false (and then the login user must be unset!)
	 *
	 * @return	boolean		returns TRUE if logins are OK, otherwise false (and then the login user must be unset!)
	 */
	function checkIfLoginAllowedInBranch()	{

			// Initialize:
		$c = count($this->rootLine);
		$disable = FALSE;

			// Traverse root line from root and outwards:
		for ($a=0; $a<$c; $a++)	{

				// If a value is set for login state:
			if ($this->rootLine[$a]['fe_login_mode'] > 0)	{

					// Determine state from value:
				if ((int)$this->rootLine[$a]['fe_login_mode'] === 1)	{
					$disable = TRUE;
					$this->loginAllowedInBranch_mode = 'all';
				} elseif ((int)$this->rootLine[$a]['fe_login_mode'] === 3)	{
					$disable = TRUE;
					$this->loginAllowedInBranch_mode = 'groups';
				} else {
					$disable = FALSE;
				}
			}
		}

		return !$disable;
	}

	/**
	 * Analysing $this->pageAccessFailureHistory into a summary array telling which features disabled display and on which pages and conditions. That data can be used inside a page-not-found handler
	 *
	 * @return	array		Summary of why page access was not allowed.
	 */
	function getPageAccessFailureReasons()	{
		$output = array();

		$combinedRecords = array_merge(
			is_array($this->pageAccessFailureHistory['direct_access']) ? $this->pageAccessFailureHistory['direct_access'] : array(array('fe_group'=>0)),	// Adding fake first record for direct access if none, otherwise $k==0 below will be indicating a sub-section record to be first direct_access record which is of course false!
			is_array($this->pageAccessFailureHistory['sub_section']) ? $this->pageAccessFailureHistory['sub_section'] : array()
		);

		if (count($combinedRecords))	{
			foreach($combinedRecords as $k => $pagerec)	{
				// If $k=0 then it is the very first page the original ID was pointing at and that will get a full check of course
				// If $k>0 it is parent pages being tested. They are only significant for the access to the first page IF they had the extendToSubpages flag set, hence checked only then!
				if (!$k || $pagerec['extendToSubpages'])	{
					if ($pagerec['hidden'])	$output['hidden'][$pagerec['uid']] = TRUE;
					if ($pagerec['starttime'] > $GLOBALS['SIM_ACCESS_TIME'])	$output['starttime'][$pagerec['uid']] = $pagerec['starttime'];
					if ($pagerec['endtime']!=0 && $pagerec['endtime'] <= $GLOBALS['SIM_ACCESS_TIME'])	$output['endtime'][$pagerec['uid']] = $pagerec['endtime'];
					if (!$this->checkPageGroupAccess($pagerec))	$output['fe_group'][$pagerec['uid']] = $pagerec['fe_group'];
				}
			}
		}

		return $output;
	}

	/**
	 * This checks if there are ARGV-parameters in the QUERY_STRING and if so, those are used for the id
	 * $this->id must be 'false' in order for any processing to happen in here
	 * If an id/alias value is extracted from the QUERY_STRING it is set in $this->id
	 *
	 * @return	void
	 * @access private
	 */
	function setIDfromArgV()	{
		if (!$this->id)	{
			list($theAlias) = explode('&',t3lib_div::getIndpEnv('QUERY_STRING'));
			$theAlias = trim($theAlias);
			$this->id = ($theAlias != '' && strpos($theAlias, '=') === false) ? $theAlias : 0;
		}
	}

	/**
	 * Gets ->page and ->rootline information based on ->id. ->id may change during this operation.
	 * If not inside domain, then default to first page in domain.
	 *
	 * @param	integer		Page uid of the page where the found domain record is (pid of the domain record)
	 * @return	void
	 * @access private
	 */
	function getPageAndRootlineWithDomain($domainStartPage)	{
		$this->getPageAndRootline();

		// Checks if the $domain-startpage is in the rootLine. This is necessary so that references to page-id's from other domains are not possible.
		if ($domainStartPage && is_array($this->rootLine)) {
			$idFound = 0;
			foreach ($this->rootLine as $key => $val) {
				if ($val['uid']==$domainStartPage)	{
					$idFound=1;
					break;
				}
			}
			if (!$idFound)	{
				$this->pageNotFound=3;			// Page is 'not found' in case the id was outside the domain, code 3
				$this->id = $domainStartPage;
				$this->getPageAndRootline();		//re-get the page and rootline if the id was not found.
			}
		}
	}

	/**
	 * Sets sys_page where-clause
	 *
	 * @return	void
	 * @access private
	 */
	function setSysPageWhereClause()	{
		$this->sys_page->where_hid_del.=' AND pages.doktype<200';
		$this->sys_page->where_groupAccess = $this->sys_page->getMultipleGroupsWhereClause('pages.fe_group', 'pages');
	}

	/**
	 * Looking up a domain record based on HTTP_HOST
	 *
	 * @param	boolean		If set, it looks "recursively" meaning that a domain like "123.456.typo3.com" would find a domain record like "typo3.com" if "123.456.typo3.com" or "456.typo3.com" did not exist.
	 * @return	integer		Returns the page id of the page where the domain record was found.
	 * @access private
	 */
	function findDomainRecord($recursive=0)	{
		if ($recursive)	{
			$host = explode('.',t3lib_div::getIndpEnv('HTTP_HOST'));
			while(count($host))	{
				$pageUid = $this->sys_page->getDomainStartPage(implode('.',$host),t3lib_div::getIndpEnv('SCRIPT_NAME'),t3lib_div::getIndpEnv('REQUEST_URI'));
				if ($pageUid)	return $pageUid; else array_shift($host);
			}
			return $pageUid;
		} else {
			return $this->sys_page->getDomainStartPage(t3lib_div::getIndpEnv('HTTP_HOST'),t3lib_div::getIndpEnv('SCRIPT_NAME'),t3lib_div::getIndpEnv('REQUEST_URI'));
		}
	}

	/**
	 * Page unavailable handler for use in frontend plugins from extensions.
	 *
	 * @param	string		Reason text
	 * @param	string		HTTP header to send
	 * @return	void		Function exits.
	 */
	function pageUnavailableAndExit($reason='', $header='')	{
		$header = $header ? $header : $this->TYPO3_CONF_VARS['FE']['pageUnavailable_handling_statheader'];
		$this->pageUnavailableHandler($this->TYPO3_CONF_VARS['FE']['pageUnavailable_handling'], $header, $reason);
		exit;
	}

	/**
	 * Page-not-found handler for use in frontend plugins from extensions.
	 *
	 * @param	string		Reason text
	 * @param	string		HTTP header to send
	 * @return	void		Function exits.
	 */
	function pageNotFoundAndExit($reason='', $header='')	{
		$header = $header ? $header : $this->TYPO3_CONF_VARS['FE']['pageNotFound_handling_statheader'];
		$this->pageNotFoundHandler($this->TYPO3_CONF_VARS['FE']['pageNotFound_handling'], $header, $reason);
		exit;
	}

	/**
	 * Checks whether the pageUnavailableHandler should be used. To be used, pageUnavailable_handling must be set
	 * and devIPMask must not match the current visitor's IP address.
	 *
	 * @return	boolean		True/false whether the pageUnavailable_handler should be used.
	 */
	function checkPageUnavailableHandler()	{
		if($this->TYPO3_CONF_VARS['FE']['pageUnavailable_handling'] &&
		   !t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $this->TYPO3_CONF_VARS['SYS']['devIPmask'])) {
			$checkPageUnavailableHandler = TRUE;
		} else {
			$checkPageUnavailableHandler = FALSE;
		}

		return $checkPageUnavailableHandler;
	}

	/**
	 * Page unavailable handler. Acts a wrapper for the pageErrorHandler method.
	 *
	 * @param	mixed		Which type of handling; If a true PHP-boolean or TRUE then a ->t3lib_message_ErrorPageMessage is outputted. If integer an error message with that number is shown. Otherwise the $code value is expected to be a "Location:" header value.
	 * @param	string		If set, this is passed directly to the PHP function, header()
	 * @param	string		If set, error messages will also mention this as the reason for the page-not-found.
	 * @return	void		(The function exits!)
	 */
	function pageUnavailableHandler($code, $header, $reason)	{
		$this->pageErrorHandler($code, $header, $reason);
	}

	/**
	 * Page not found handler. Acts a wrapper for the pageErrorHandler method.
	 *
	 * @param	mixed		Which type of handling; If a true PHP-boolean or TRUE then a ->t3lib_message_ErrorPageMessage is outputted. If integer an error message with that number is shown. Otherwise the $code value is expected to be a "Location:" header value.
	 * @param	string		If set, this is passed directly to the PHP function, header()
	 * @param	string		If set, error messages will also mention this as the reason for the page-not-found.
	 * @return	void		(The function exits!)
	 */
	function pageNotFoundHandler($code, $header='', $reason='')	{
		$this->pageErrorHandler($code, $header, $reason);
	}

	/**
	 * Generic error page handler.
	 * Exits.
	 *
	 * @param	mixed		Which type of handling; If a true PHP-boolean or TRUE then a ->t3lib_message_ErrorPageMessage is outputted. If integer an error message with that number is shown. Otherwise the $code value is expected to be a "Location:" header value.
	 * @param	string		If set, this is passed directly to the PHP function, header()
	 * @param	string		If set, error messages will also mention this as the reason for the page-not-found.
	 * @return	void		(The function exits!)
	 */
	function pageErrorHandler($code, $header='', $reason='')	{

			// Issue header in any case:
		if ($header)	{
			$headerArr = preg_split('/\r|\n/',$header,-1,PREG_SPLIT_NO_EMPTY);
			foreach ($headerArr as $header)	{
				header ($header);
			}
		}

			// Create response:
		if (gettype($code)=='boolean' || !strcmp($code,1))	{	// Simply boolean; Just shows TYPO3 error page with reason:
			throw new RuntimeException('The page did not exist or was inaccessible.' . ($reason ? ' Reason: ' . htmlspecialchars($reason) : ''), 1294587213);
		} elseif (t3lib_div::isFirstPartOfStr($code,'USER_FUNCTION:')) {
			$funcRef = trim(substr($code,14));
			$params = array(
				'currentUrl' => t3lib_div::getIndpEnv('REQUEST_URI'),
				'reasonText' => $reason,
				'pageAccessFailureReasons' => $this->getPageAccessFailureReasons()
			);
			echo t3lib_div::callUserFunction($funcRef,$params,$this);
		} elseif (t3lib_div::isFirstPartOfStr($code,'READFILE:')) {
			$readFile = t3lib_div::getFileAbsFileName(trim(substr($code,9)));
			if (@is_file($readFile))	{
				$fileContent = t3lib_div::getUrl($readFile);
				$fileContent = str_replace('###CURRENT_URL###', t3lib_div::getIndpEnv('REQUEST_URI'), $fileContent);
				$fileContent = str_replace('###REASON###', htmlspecialchars($reason), $fileContent);
				echo $fileContent;
			} else {
				throw new RuntimeException('Configuration Error: 404 page "' . $readFile.'" could not be found.', 1294587214);
			}
		} elseif (t3lib_div::isFirstPartOfStr($code,'REDIRECT:')) {
			t3lib_utility_Http::redirect(substr($code, 9));
		} elseif (strlen($code))	{
				// Check if URL is relative
			$url_parts = parse_url($code);
			if ($url_parts['host'] == '')	{
				$url_parts['host'] = t3lib_div::getIndpEnv('HTTP_HOST');
				$code = t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . $code;
				$checkBaseTag = false;
			} else {
				$checkBaseTag = true;
			}

				// Check recursion
			if ($code == t3lib_div::getIndpEnv('TYPO3_REQUEST_URL')) {
				if ($reason == '') {
					$reason = 'Page cannot be found.';
				}
				$reason.= LF . LF . 'Additionally, ' . $code . ' was not found while trying to retrieve the error document.';
				throw new RuntimeException(nl2br(htmlspecialchars($reason)), 1294587215);
			}

				// Prepare headers
			$headerArr = array(
				'User-agent: ' . t3lib_div::getIndpEnv('HTTP_USER_AGENT'),
				'Referer: ' . t3lib_div::getIndpEnv('TYPO3_REQUEST_URL')
			);
			$res = t3lib_div::getURL($code, 1, $headerArr);

				// Header and content are separated by an empty line
			list($header, $content) = explode(CRLF . CRLF, $res, 2);
			$content.= CRLF;

			if (false === $res) {
					// Last chance -- redirect
				t3lib_utility_Http::redirect($code);
			} else {

				$forwardHeaders = array(	// Forward these response headers to the client
					'Content-Type:',
				);
				$headerArr = preg_split('/\r|\n/',$header,-1,PREG_SPLIT_NO_EMPTY);
				foreach ($headerArr as $header)	{
					foreach ($forwardHeaders as $h)	{
						if (preg_match('/^'.$h.'/', $header))	{
							header ($header);
						}
					}
				}
					// Put <base> if necesary
				if ($checkBaseTag)	{

						// If content already has <base> tag, we do not need to do anything
					if (false === stristr($content, '<base '))	{

							// Generate href for base tag
						$base = $url_parts['scheme'] . '://';
						if ($url_parts['user'] != '')	{
							$base.= $url_parts['user'];
							if ($url_parts['pass'] != '')	{
								$base.= ':' . $url_parts['pass'];
							}
							$base.= '@';
						}
						$base.= $url_parts['host'];

							// Add path portion skipping possible file name
						$base.= preg_replace('/(.*\/)[^\/]*/', '${1}', $url_parts['path']);

							// Put it into content (generate also <head> if necessary)
						$replacement = LF . '<base href="' . htmlentities($base) . '" />' . LF;
						if (stristr($content, '<head>'))	{
							$content = preg_replace('/(<head>)/i', '\1' . $replacement, $content);
						} else {
							$content = preg_replace('/(<html[^>]*>)/i', '\1<head>' . $replacement . '</head>', $content);
						}
					}
				}
				echo $content;	// Output the content
			}
		} else {
			throw new RuntimeException($reason ? htmlspecialchars($reason) : 'Page cannot be found.', 1294587216);
		}
		exit();
	}

	/**
	 * Fetches the integer page id for a page alias.
	 * Looks if ->id is not an integer and if so it will search for a page alias and if found the page uid of that page is stored in $this->id
	 *
	 * @return	void
	 * @access private
	 */
	function checkAndSetAlias()	{
		if ($this->id && !t3lib_div::testInt($this->id))	{
			$aid = $this->sys_page->getPageIdFromAlias($this->id);
			if ($aid)	{
				$this->id = $aid;
			} else {
				$this->pageNotFound = 4;
			}
		}
	}

	/**
	 * Analyzes the second part of a id-string (after the "+"), looking for B6 or M5 encoding and if found it will resolve it and restore the variables in global $_GET
	 * If values for ->cHash, ->no_cache, ->jumpurl and ->MP is found, they are also loaded into the internal vars of this class.
	 *
	 * @param	string		String to analyze
	 * @return	void
	 * @access private
	 * @deprecated since TYPO3 4.3, will be removed in TYPO3 4.5, please use the "simulatestatic" sysext directly
	 * @todo	Deprecated but still used in the Core!
	 */
	function idPartsAnalyze($str)	{
		$GET_VARS = '';
		switch(substr($str,0,2))	{
			case 'B6':
				$addParams = base64_decode(str_replace('_','=',str_replace('-','/',substr($str,2))));
				parse_str($addParams,$GET_VARS);
			break;
			case 'M5':
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('params', 'cache_md5params', 'md5hash='.$GLOBALS['TYPO3_DB']->fullQuoteStr(substr($str,2), 'cache_md5params'));
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

				$this->updateMD5paramsRecord(substr($str,2));
				parse_str($row['params'],$GET_VARS);
			break;
		}

		$this->mergingWithGetVars($GET_VARS);
	}

	/**
	 * Merging values into the global $_GET
	 *
	 * @param	array		Array of key/value pairs that will be merged into the current GET-vars. (Non-escaped values)
	 * @return	void
	 */
	function mergingWithGetVars($GET_VARS)	{
		if (is_array($GET_VARS))	{
			$realGet = t3lib_div::_GET();		// Getting $_GET var, unescaped.
			if (!is_array($realGet))	$realGet = array();

				// Merge new values on top:
			$realGet = t3lib_div::array_merge_recursive_overrule($realGet,$GET_VARS);

				// Write values back to $_GET:
			t3lib_div::_GETset($realGet);

				// Setting these specifically (like in the init-function):
			if (isset($GET_VARS['type']))		$this->type = intval($GET_VARS['type']);
			if (isset($GET_VARS['cHash']))		$this->cHash = $GET_VARS['cHash'];
			if (isset($GET_VARS['jumpurl']))	$this->jumpurl = $GET_VARS['jumpurl'];
			if (isset($GET_VARS['MP']))			$this->MP = $this->TYPO3_CONF_VARS['FE']['enable_mount_pids'] ? $GET_VARS['MP'] : '';

			if (isset($GET_VARS['no_cache']) && $GET_VARS['no_cache'])	$this->set_no_cache();
		}
	}

	/**
	 * Looking for a ADMCMD_prev code, looks it up if found and returns configuration data.
	 * Background: From the backend a request to the frontend to show a page, possibly with workspace preview can be "recorded" and associated with a keyword. When the frontend is requested with this keyword the associated request parameters are restored from the database AND the backend user is loaded - only for that request.
	 * The main point is that a special URL valid for a limited time, eg. http://localhost/typo3site/index.php?ADMCMD_prev=035d9bf938bd23cb657735f68a8cedbf will open up for a preview that doesn't require login. Thus it's useful for sending in an email to someone without backend account.
	 * This can also be used to generate previews of hidden pages, start/endtimes, usergroups and those other settings from the Admin Panel - just not implemented yet.
	 *
	 * @return	array		Preview configuration array from sys_preview record.
	 * @see t3lib_BEfunc::compilePreviewKeyword()
	 */
	function ADMCMD_preview(){
		$inputCode = t3lib_div::_GP('ADMCMD_prev');

			// If no inputcode and a cookie is set, load input code from cookie:
		if (!$inputCode && $_COOKIE['ADMCMD_prev'])	{
			$inputCode = $_COOKIE['ADMCMD_prev'];
		}

			// If inputcode now, look up the settings:
		if ($inputCode)	{

			if ($inputCode=='LOGOUT') {	// "log out":
				SetCookie('ADMCMD_prev', '', 0, t3lib_div::getIndpEnv('TYPO3_SITE_PATH'));
				if ($this->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate'])	{
					if (@is_file(PATH_site.$this->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate']))	{
						$message = t3lib_div::getUrl(PATH_site.$this->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate']);
					} else {
						$message = '<strong>ERROR!</strong><br>Template File "'.$this->TYPO3_CONF_VARS['FE']['workspacePreviewLogoutTemplate'].'" configured with $TYPO3_CONF_VARS["FE"]["workspacePreviewLogoutTemplate"] not found. Please contact webmaster about this problem.';
					}
				} else {
					$message = 'You logged out from Workspace preview mode. Click this link to <a href="%1$s">go back to the website</a>';
				}

				$returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GET('returnUrl'));
				die(sprintf($message,
					htmlspecialchars(preg_replace('/\&?ADMCMD_prev=[[:alnum:]]+/', '', $returnUrl))
					));
			}

				// Look for keyword configuration record:
			$previewData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'*',
				'sys_preview',
				'keyword='.$GLOBALS['TYPO3_DB']->fullQuoteStr($inputCode, 'sys_preview').
					' AND endtime>' . $GLOBALS['EXEC_TIME']
			);

				// Get: Backend login status, Frontend login status
				// - Make sure to remove fe/be cookies (temporarily); BE already done in ADMCMD_preview_postInit()
			if (is_array($previewData))	{
				if (!count(t3lib_div::_POST()))	{
						// Unserialize configuration:
					$previewConfig = unserialize($previewData['config']);

					if ($previewConfig['fullWorkspace']) {	// For full workspace preview we only ADD a get variable to set the preview of the workspace - so all other Get vars are accepted. Hope this is not a security problem. Still posting is not allowed and even if a backend user get initialized it shouldn't lead to situations where users can use those credentials.

							// Set the workspace preview value:
						t3lib_div::_GETset($previewConfig['fullWorkspace'],'ADMCMD_previewWS');

							// If ADMCMD_prev is set the $inputCode value cannot come from a cookie and we set that cookie here. Next time it will be found from the cookie if ADMCMD_prev is not set again...
						if (t3lib_div::_GP('ADMCMD_prev'))	{
							SetCookie('ADMCMD_prev', t3lib_div::_GP('ADMCMD_prev'), 0, t3lib_div::getIndpEnv('TYPO3_SITE_PATH'));	// Lifetime is 1 hour, does it matter much? Requires the user to click the link from their email again if it expires.
						}
						return $previewConfig;
					} elseif (t3lib_div::getIndpEnv('TYPO3_SITE_URL').'index.php?ADMCMD_prev='.$inputCode === t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'))	{

							// Set GET variables:
						$GET_VARS = '';
						parse_str($previewConfig['getVars'], $GET_VARS);
						t3lib_div::_GETset($GET_VARS);

							// Return preview keyword configuration:
						return $previewConfig;
					} else throw new Exception(htmlspecialchars('Request URL did not match "' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . 'index.php?ADMCMD_prev=' . $inputCode . '"', 1294585190));	// This check is to prevent people from setting additional GET vars via realurl or other URL path based ways of passing parameters.
				} else throw new Exception('POST requests are incompatible with keyword preview.', 1294585191);
			} else throw new Exception('ADMCMD command could not be executed! (No keyword configuration found)', 1294585192);
		}
	}

	/**
	 * Configuration after initialization of TSFE object.
	 * Basically this unsets the BE cookie if any and forces the BE user set according to the preview configuration.
	 *
	 * @param	array		Preview configuration, see ADMCMD_preview()
	 * @return	void
	 * @see ADMCMD_preview(), index_ts.php
	 */
	function ADMCMD_preview_postInit(array $previewConfig){
			// Clear cookies:
		unset($_COOKIE['be_typo_user']);
		$this->ADMCMD_preview_BEUSER_uid = $previewConfig['BEUSER_uid'];
	}











	/********************************************
	 *
	 * Template and caching related functions.
	 *
	 *******************************************/

	/**
	 * Calculates a hash string based on additional parameters in the url. This is used to cache pages with more parameters than just id and type
	 *
	 * @return	void
	 * @see reqCHash()
	 */
	function makeCacheHash()	{
		// No need to test anything if caching was already disabled.
		if ($this->no_cache && !$this->TYPO3_CONF_VARS['FE']['pageNotFoundOnCHashError']) {
			return;
		}

		$GET = t3lib_div::_GET();
		if ($this->cHash && is_array($GET))	{
			$this->cHash_array = t3lib_div::cHashParams(t3lib_div::implodeArrayForUrl('',$GET));
			$cHash_calc = t3lib_div::calculateCHash($this->cHash_array);

			if ($cHash_calc!=$this->cHash)	{
				if ($this->TYPO3_CONF_VARS['FE']['pageNotFoundOnCHashError']) {
					$this->pageNotFoundAndExit('Request parameters could not be validated (&cHash comparison failed)');
				} else {
					$this->set_no_cache();
					$GLOBALS['TT']->setTSlogMessage('The incoming cHash "'.$this->cHash.'" and calculated cHash "'.$cHash_calc.'" did not match, so caching was disabled. The fieldlist used was "'.implode(',',array_keys($this->cHash_array)).'"',2);
				}
			}
		}
	}

	/**
	 * Will disable caching if the cHash value was not set.
	 * This function should be called to check the _existence_ of "&cHash" whenever a plugin generating cachable output is using extra GET variables. If there _is_ a cHash value the validation of it automatically takes place in makeCacheHash() (see above)
	 *
	 * @return	void
	 * @see makeCacheHash(), tslib_pibase::pi_cHashCheck()
	 */
	function reqCHash()	{
		if (!$this->cHash)	{
			if ($this->TYPO3_CONF_VARS['FE']['pageNotFoundOnCHashError']) {
				if ($this->tempContent)	{ $this->clearPageCacheContent(); }
				$this->pageNotFoundAndExit('Request parameters could not be validated (&cHash empty)');
			} else {
				$this->set_no_cache();
				$GLOBALS['TT']->setTSlogMessage('TSFE->reqCHash(): No &cHash parameter was sent for GET vars though required so caching is disabled',2);
			}
		}
	}

	/**
	 * Splits the input query-parameters into an array with certain parameters filtered out.
	 * Used to create the cHash value
	 *
	 * @param	string		Query-parameters: "&xxx=yyy&zzz=uuu"
	 * @return	array		Array with key/value pairs of query-parameters WITHOUT a certain list of variable names (like id, type, no_cache etc) and WITH a variable, encryptionKey, specific for this server/installation
	 * @access private
	 * @see makeCacheHash(), tslib_cObj::typoLink()
	 * @obsolete
	 */
	function cHashParams($addQueryParams) {
		return t3lib_div::cHashParams($addQueryParams);
	}

	/**
	 * Initialize the TypoScript template parser
	 *
	 * @return	void
	 */
	function initTemplate()	{
		$this->tmpl = t3lib_div::makeInstance('t3lib_TStemplate');
		$this->tmpl->init();
		$this->tmpl->tt_track= $this->beUserLogin ? 1 : 0;
	}

	/**
	 * See if page is in cache and get it if so
	 * Stores the page content in $this->content if something is found.
	 *
	 * @return	void
	 */
	function getFromCache()	{
		if (!$this->no_cache) {
			$cc = $this->tmpl->getCurrentPageData();

			if (!is_array($cc)) {
				$key = $this->id.'::'.$this->MP;
				$isLocked = $this->acquirePageGenerationLock($this->pagesection_lockObj, $key);	// Returns true if the lock is active now

				if (!$isLocked) {
						// Lock is no longer active, the data in "cache_pagesection" is now ready
					$cc = $this->tmpl->getCurrentPageData();
					if (is_array($cc)) {
						$this->releasePageGenerationLock($this->pagesection_lockObj);	// Release the lock
					}
				}
			}

			if (is_array($cc)) {
					// BE CAREFUL to change the content of the cc-array. This array is serialized and an md5-hash based on this is used for caching the page.
					// If this hash is not the same in here in this section and after page-generation, then the page will not be properly cached!
				$cc = $this->tmpl->matching($cc);	// This array is an identification of the template. If $this->all is empty it's because the template-data is not cached, which it must be.
				ksort($cc);

				$this->all = $cc;
			}
			unset($cc);
		}

		$this->content = '';	// clearing the content-variable, which will hold the pagecontent
		unset($this->config);	// Unsetting the lowlevel config
		$this->cacheContentFlag = 0;

			// Look for page in cache only if caching is not disabled and if a shift-reload is not sent to the server.
		if (!$this->no_cache && !$this->headerNoCache()) {
			$lockHash = $this->getLockHash();

			if ($this->all) {
				$this->newHash = $this->getHash();

				$GLOBALS['TT']->push('Cache Row','');
					$row = $this->getFromCache_queryRow();

					if (!is_array($row)) {
						$isLocked = $this->acquirePageGenerationLock($this->pages_lockObj, $lockHash);

						if (!$isLocked) {
								// Lock is no longer active, the data in "cache_pages" is now ready
							$row = $this->getFromCache_queryRow();
							if (is_array($row)) {
								$this->releasePageGenerationLock($this->pages_lockObj);	// Release the lock
							}
						}
					}

					if (is_array($row)) {
							// Release this lock
						$this->releasePageGenerationLock($this->pages_lockObj);

							// Call hook when a page is retrieved from cache:
						if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache']))	{
							$_params = array('pObj' => &$this, 'cache_pages_row' => &$row);
							foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache'] as $_funcRef)	{
								t3lib_div::callUserFunction($_funcRef,$_params,$this);
							}
						}

						$this->config = (array)unserialize($row['cache_data']);		// Fetches the lowlevel config stored with the cached data
						$this->content = (TYPO3_UseCachingFramework ? $row['content'] : $row['HTML']);	// Getting the content
						$this->tempContent = $row['temp_content'];	// Flag for temp content
						$this->cacheContentFlag = 1;	// Setting flag, so we know, that some cached content has been loaded
						$this->cacheExpires = $row['expires'];

						if ($this->TYPO3_CONF_VARS['FE']['debug'] || (isset($this->config['config']['debug']) && $this->config['config']['debug'])) {
							$dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
							$timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];

							$this->content.= LF.'<!-- Cached page generated '.date($dateFormat.' '.$timeFormat, $row['tstamp']).'. Expires '.Date($dateFormat.' '.$timeFormat, $row['expires']).' -->';
						}
					}
				$GLOBALS['TT']->pull();

			} else {
				$this->acquirePageGenerationLock($this->pages_lockObj, $lockHash);
			}
		}
	}

	/**
	 * Returning the cached version of page with hash = newHash
	 *
	 * @return	array		Cached row, if any. Otherwise void.
	 */
	function getFromCache_queryRow() {
		if (TYPO3_UseCachingFramework) {
			$GLOBALS['TT']->push('Cache Query', '');
			$row = $this->pageCache->get($this->newHash);
			$GLOBALS['TT']->pull();
		} else {
			$GLOBALS['TT']->push('Cache Query','');
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'S.*',
				'cache_pages S,pages P',
				'S.hash='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->newHash, 'cache_pages').'
					AND S.page_id=P.uid
					AND S.expires > '.intval($GLOBALS['ACCESS_TIME']).'
					AND P.deleted=0
					AND P.hidden=0
					AND P.starttime<='.intval($GLOBALS['ACCESS_TIME']).'
					AND (P.endtime=0 OR P.endtime>'.intval($GLOBALS['ACCESS_TIME']).')'
			);
			$GLOBALS['TT']->pull();

			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$this->pageCachePostProcess($row,'get');
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $row;
	}

	/**
	 * Detecting if shift-reload has been clicked
	 * Will not be called if re-generation of page happens by other reasons (for instance that the page is not in cache yet!)
	 * Also, a backend user MUST be logged in for the shift-reload to be detected due to DoS-attack-security reasons.
	 *
	 * @return	boolean		If shift-reload in client browser has been clicked, disable getting cached page (and regenerate it).
	 */
	function headerNoCache()	{
		$disableAcquireCacheData = FALSE;

		if ($this->beUserLogin)	{
			if (strtolower($_SERVER['HTTP_CACHE_CONTROL'])==='no-cache' || strtolower($_SERVER['HTTP_PRAGMA'])==='no-cache')	{
				$disableAcquireCacheData = TRUE;
			}
		}

			// Call hook for possible by-pass of requiring of page cache (for recaching purpose)
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache']))	{
			$_params = array('pObj' => &$this, 'disableAcquireCacheData' => &$disableAcquireCacheData);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

		return $disableAcquireCacheData;
	}

	/**
	 * Calculates the cache-hash
	 * This hash is unique to the template, the variables ->id, ->type, ->gr_list (list of groups), ->MP (Mount Points) and cHash array
	 * Used to get and later store the cached data.
	 *
	 * @return	string		MD5 hash of $this->hash_base which is a serialized version of there variables.
	 * @access private
	 * @see getFromCache(), getLockHash()
	 */
	function getHash()	{
		$this->hash_base = serialize(
			array(
				'all' => $this->all,
				'id' => intval($this->id),
				'type' => intval($this->type),
				'gr_list' => (string)$this->gr_list,
				'MP' => (string)$this->MP,
				'cHash' => $this->cHash_array,
				'domainStartPage' => $this->domainStartPage,
			)
		);

		return md5($this->hash_base);
	}

	/**
	 * Calculates the lock-hash
	 * This hash is unique to the above hash, except that it doesn't contain the template information in $this->all.
	 *
	 * @return	string		MD5 hash
	 * @access private
	 * @see getFromCache(), getHash()
	 */
	function getLockHash()	{
		$lockHash = serialize(
			array(
				'id' => intval($this->id),
				'type' => intval($this->type),
				'gr_list' => (string)$this->gr_list,
				'MP' => (string)$this->MP,
				'cHash' => $this->cHash_array,
				'domainStartPage' => $this->domainStartPage,
			)
		);

		return md5($lockHash);
	}

	/**
	 * Checks if config-array exists already but if not, gets it
	 *
	 * @return	void
	 */
	function getConfigArray()	{
		$setStatPageName = false;

		if (!is_array($this->config) || is_array($this->config['INTincScript']) || $this->forceTemplateParsing)	{	// If config is not set by the cache (which would be a major mistake somewhere) OR if INTincScripts-include-scripts have been registered, then we must parse the template in order to get it
				$GLOBALS['TT']->push('Parse template','');

				// Force parsing, if set?:
			$this->tmpl->forceTemplateParsing = $this->forceTemplateParsing;

				// Start parsing the TS template. Might return cached version.
			$this->tmpl->start($this->rootLine);
				$GLOBALS['TT']->pull();

			if ($this->tmpl->loaded)	{
				$GLOBALS['TT']->push('Setting the config-array','');
			//	t3lib_div::print_array($this->tmpl->setup);
				$this->sPre = $this->tmpl->setup['types.'][$this->type];	// toplevel - objArrayName
				$this->pSetup = $this->tmpl->setup[$this->sPre.'.'];

				if (!is_array($this->pSetup))	{
					if ($this->checkPageUnavailableHandler())	{
						$this->pageUnavailableAndExit('The page is not configured! [type= '.$this->type.']['.$this->sPre.']');
					} else {
						$message = 'The page is not configured! [type= '.$this->type.']['.$this->sPre.']';
						header('HTTP/1.0 503 Service Temporarily Unavailable');
						t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
						throw new RuntimeException($message, 1294587217);
					}
				} else {
					$this->config['config'] = array();

					// Filling the config-array, first with the main "config." part
					if (is_array($this->tmpl->setup['config.'])) {
						$this->config['config'] = $this->tmpl->setup['config.'];
					}
					// override it with the page/type-specific "config."
					if (is_array($this->pSetup['config.'])) {
						$this->config['config'] = t3lib_div::array_merge_recursive_overrule($this->config['config'], $this->pSetup['config.']);
					}

					if ($this->config['config']['typolinkEnableLinksAcrossDomains']) {
						$this->config['config']['typolinkCheckRootline'] = true;
					}

						// Set default values for removeDefaultJS, inlineStyle2TempFile and minifyJS so CSS and JS are externalized/minified if compatversion is higher than 4.0
					if (t3lib_div::compat_version('4.0')) {
						if (!isset($this->config['config']['removeDefaultJS'])) {
							$this->config['config']['removeDefaultJS'] = 'external';
						}
						if (!isset($this->config['config']['inlineStyle2TempFile'])) {
							$this->config['config']['inlineStyle2TempFile'] = 1;
						}
						if (!isset($this->config['config']['minifyJS'])) {
							$this->config['config']['minifyJS'] = 1;
						}
					}

							// Processing for the config_array:
					$this->config['rootLine'] = $this->tmpl->rootLine;
					$this->config['mainScript'] = trim($this->config['config']['mainScript']) ? trim($this->config['config']['mainScript']) : 'index.php';

						// Initialize statistics handling: Check filename and permissions
					$setStatPageName = $this->statistics_init();

					$this->config['FEData'] = $this->tmpl->setup['FEData'];
					$this->config['FEData.'] = $this->tmpl->setup['FEData.'];

						// class for render Header and Footer parts
					$template = '';
					if ($this->pSetup['pageHeaderFooterTemplateFile']) {
						$file = $this->tmpl->getFileName($this->pSetup['pageHeaderFooterTemplateFile']);
						if ($file) {
							$this->setTemplateFile($file);
						}
					}

				}
				$GLOBALS['TT']->pull();
			} else {
				if ($this->checkPageUnavailableHandler())	{
					$this->pageUnavailableAndExit('No TypoScript template found!');
				} else {
					$message = 'No TypoScript template found!';
					header('HTTP/1.0 503 Service Temporarily Unavailable');
					t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
					throw new RuntimeException($message, 1294587218);
				}
			}
		}

			// Initialize charset settings etc.
		$this->initLLvars();

			// We want nice names, so we need to handle the charset
		if ($setStatPageName)	{
			$this->statistics_init_pagename();
		}

			// No cache
		if ($this->config['config']['no_cache'])	{ $this->set_no_cache(); }		// Set $this->no_cache true if the config.no_cache value is set!

			// merge GET with defaultGetVars
		if (!empty($this->config['config']['defaultGetVars.'])) {
			$modifiedGetVars = t3lib_div::array_merge_recursive_overrule(
				t3lib_div::removeDotsFromTS($this->config['config']['defaultGetVars.']),
				t3lib_div::_GET()
			);

			t3lib_div::_GETset($modifiedGetVars);
		}

			// Hook for postProcessing the configuration array
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'])) {
			$params = array('config' => &$this->config['config']);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}
	}














	/********************************************
	 *
	 * Further initialization and data processing
	 * (jumpurl/submission of forms)
	 *
	 *******************************************/

	/**
	 * Get the compressed $TCA array for use in the front-end
	 * A compressed $TCA array holds only the ctrl- and feInterface-part for each table. But the column-definitions are omitted in order to save some memory and be more efficient.
	 * Operates on the global variable, $TCA
	 *
	 * @return	void
	 * @see includeTCA()
	 */
	function getCompressedTCarray()	{
		global $TCA;

		$GLOBALS['TT']->push('Get Compressed TC array');
		if (!$this->TCAloaded)	{
				// Create hash string for storage / retrieval of cached content:
			$tempHash = md5('tables.php:'.
				filemtime(TYPO3_extTableDef_script ? PATH_typo3conf.TYPO3_extTableDef_script : PATH_t3lib.'stddb/tables.php').
				(TYPO3_extTableDef_script?filemtime(PATH_typo3conf.TYPO3_extTableDef_script):'').
				($GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] ? filemtime(PATH_typo3conf.$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'].'_ext_tables.php') : '')
			);

			if ($this->TYPO3_CONF_VARS['EXT']['extCache'] != 0) {
				// Try to fetch if cache is enabled
				list($TCA, $this->TCAcachedExtras) = unserialize($this->sys_page->getHash($tempHash));
			}

				// If no result, create it:
			if (!is_array($TCA))	{
				$this->includeTCA(0);
				$newTc = Array();
				$this->TCAcachedExtras = array();	// Collects other information

				foreach($TCA as $key => $val)		{
					$newTc[$key]['ctrl'] = $val['ctrl'];
					$newTc[$key]['feInterface'] = $val['feInterface'];

						// Collect information about localization exclusion of fields:
					t3lib_div::loadTCA($key);
					if (is_array($TCA[$key]['columns']))	{
						$this->TCAcachedExtras[$key]['l10n_mode'] = array();
						foreach($TCA[$key]['columns'] as $fN => $fV)	{
							if ($fV['l10n_mode'])	{
								$this->TCAcachedExtras[$key]['l10n_mode'][$fN] = $fV['l10n_mode'];
							}
						}
					}
				}

				$TCA = $newTc;
				// Store it in cache if cache is enabled
				if ($this->TYPO3_CONF_VARS['EXT']['extCache'] != 0) {
					$this->sys_page->storeHash($tempHash, serialize(array($newTc,$this->TCAcachedExtras)), 'SHORT_TC');
				}
			}
		}
		$GLOBALS['TT']->pull();
	}

	/**
	 * Includes TCA definitions from loaded extensions (ext_table.php files).
	 * Normally in the frontend only a part of the global $TCA array is loaded,
	 * namely the "ctrl" part. Thus it doesn't take up too much memory. To load
	 * full TCA for the table, use t3lib_div::loadTCA($tableName) after calling
	 * this function.
	 *
	 * @param	boolean		Probably, keep hands of this value. Just don't set it. (This may affect the first-ever time this function is called since if you set it to zero/false any subsequent call will still trigger the inclusion; In other words, this value will be set in $this->TCAloaded after inclusion and therefore if its false, another inclusion will be possible on the next call. See ->getCompressedTCarray())
	 * @return	void
	 * @see getCompressedTCarray()
	 */
	function includeTCA($TCAloaded=1)	{
		global $TCA, $PAGES_TYPES, $TBE_MODULES;
		if (!$this->TCAloaded)	{
			$TCA = Array();
			include (TYPO3_tables_script ? PATH_typo3conf.TYPO3_tables_script : PATH_t3lib.'stddb/tables.php');
				// Extension additions
			if ($GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'])	{
				include(PATH_typo3conf.$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'].'_ext_tables.php');
			} else {
				include(PATH_t3lib.'stddb/load_ext_tables.php');
			}
				// ext-script
			if (TYPO3_extTableDef_script)	{
				include (PATH_typo3conf.TYPO3_extTableDef_script);
			}

			$this->TCAloaded = $TCAloaded;
		}
	}

	/**
	 * Setting the language key that will be used by the current page.
	 * In this function it should be checked, 1) that this language exists, 2) that a page_overlay_record exists, .. and if not the default language, 0 (zero), should be set.
	 *
	 * @return	void
	 * @access private
	 */
	function settingLanguage()	{

		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess']))	{
			$_params = array();
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}

			// Get values from TypoScript:
		$this->sys_language_uid = $this->sys_language_content = intval($this->config['config']['sys_language_uid']);
		list($this->sys_language_mode,$sys_language_content) = t3lib_div::trimExplode(';', $this->config['config']['sys_language_mode']);
		$this->sys_language_contentOL = $this->config['config']['sys_language_overlay'];

			// If sys_language_uid is set to another language than default:
		if ($this->sys_language_uid>0)	{

				// check whether a shortcut is overwritten by a translated page
				// we can only do this now, as this is the place where we get
				// to know about translations
			$this->checkTranslatedShortcut();

				// Request the overlay record for the sys_language_uid:
			$olRec = $this->sys_page->getPageOverlay($this->id, $this->sys_language_uid);
			if (!count($olRec))	{

					// If no OL record exists and a foreign language is asked for...
				if ($this->sys_language_uid)	{

						// If requested translation is not available:
					if (t3lib_div::hideIfNotTranslated($this->page['l18n_cfg']))	{
						$this->pageNotFoundAndExit('Page is not available in the requested language.');
					} else {
						switch((string)$this->sys_language_mode)	{
							case 'strict':
								$this->pageNotFoundAndExit('Page is not available in the requested language (strict).');
							break;
							case 'content_fallback':
								$fallBackOrder = t3lib_div::intExplode(',', $sys_language_content);
								foreach($fallBackOrder as $orderValue)	{
									if (!strcmp($orderValue,'0') || count($this->sys_page->getPageOverlay($this->id, $orderValue)))	{
										$this->sys_language_content = $orderValue;	// Setting content uid (but leaving the sys_language_uid)
										break;
									}
								}
							break;
							case 'ignore':
								$this->sys_language_content = $this->sys_language_uid;
							break;
							default:
									// Default is that everything defaults to the default language...
								$this->sys_language_uid = $this->sys_language_content = 0;
							break;
						}
					}
				}
			} else {
					// Setting sys_language if an overlay record was found (which it is only if a language is used)
				$this->page = $this->sys_page->getPageOverlay($this->page, $this->sys_language_uid);
			}
		}

			// Setting sys_language_uid inside sys-page:
		$this->sys_page->sys_language_uid = $this->sys_language_uid;

			// If default translation is not available:
		if ((!$this->sys_language_uid || !$this->sys_language_content) && $this->page['l18n_cfg']&1)	{
			$message = 'Page is not available in default language.';
			t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
			$this->pageNotFoundAndExit($message);
		}

			// Updating content of the two rootLines IF the language key is set!
		if ($this->sys_language_uid && is_array($this->tmpl->rootLine))	{
			foreach ($this->tmpl->rootLine as $rLk => $value) {
				$this->tmpl->rootLine[$rLk] = $this->sys_page->getPageOverlay($this->tmpl->rootLine[$rLk]);
			}
		}
		if ($this->sys_language_uid && is_array($this->rootLine))	{
			foreach ($this->rootLine as $rLk => $value) {
				$this->rootLine[$rLk] = $this->sys_page->getPageOverlay($this->rootLine[$rLk]);
			}
		}

			// Finding the ISO code:
		if (t3lib_extMgm::isLoaded('static_info_tables') && $this->sys_language_content)	{	// using sys_language_content because the ISO code only (currently) affect content selection from FlexForms - which should follow "sys_language_content"
			$sys_language_row = $this->sys_page->getRawRecord('sys_language',$this->sys_language_content,'static_lang_isocode');
			if (is_array($sys_language_row) && $sys_language_row['static_lang_isocode'])	{
				$stLrow = $this->sys_page->getRawRecord('static_languages',$sys_language_row['static_lang_isocode'],'lg_iso_2');
				$this->sys_language_isocode = $stLrow['lg_iso_2'];
			}
		}

			// Setting softMergeIfNotBlank:
		$table_fields = t3lib_div::trimExplode(',', $this->config['config']['sys_language_softMergeIfNotBlank'],1);
		foreach($table_fields as $TF)	{
			list($tN,$fN) = explode(':',$TF);
			$this->TCAcachedExtras[$tN]['l10n_mode'][$fN] = 'mergeIfNotBlank';
		}

			// Setting softExclude:
		$table_fields = t3lib_div::trimExplode(',', $this->config['config']['sys_language_softExclude'],1);
		foreach($table_fields as $TF)	{
			list($tN,$fN) = explode(':',$TF);
			$this->TCAcachedExtras[$tN]['l10n_mode'][$fN] = 'exclude';
		}

		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess']))	{
			$_params = array();
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Setting locale for frontend rendering
	 *
	 * @return	void
	 */
	function settingLocale()	{

			// Setting locale
		if ($this->config['config']['locale_all'])	{
			# Change by René Fritz, 22/10 2002
			# there's a problem that PHP parses float values in scripts wrong if the locale LC_NUMERIC is set to something with a comma as decimal point
			# this does not work in php 4.2.3
			#setlocale('LC_ALL',$this->config['config']['locale_all']);
			#setlocale('LC_NUMERIC','en_US');

			# so we set all except LC_NUMERIC
			$locale = setlocale(LC_COLLATE, $this->config['config']['locale_all']);
			if ($locale) {

					// PHP fatals with uppercase I characters in method names with turkish locale LC_CTYPE
					// @see http://bugs.php.net/bug.php?id=35050
				if (substr($this->config['config']['locale_all'], 0, 2) != 'tr') {
					setlocale(LC_CTYPE, $this->config['config']['locale_all']);
				}

				setlocale(LC_MONETARY, $this->config['config']['locale_all']);
				setlocale(LC_TIME, $this->config['config']['locale_all']);

				$this->localeCharset = $this->csConvObj->get_locale_charset($this->config['config']['locale_all']);
			} else {
				$GLOBALS['TT']->setTSlogMessage('Locale "'.htmlspecialchars($this->config['config']['locale_all']).'" not found.', 3);
			}
		}
	}

	/**
	 * checks whether a translated shortcut page has a different shortcut
	 * target than the original language page.
	 * If that is the case, things get corrected to follow that alternative
	 * shortcut
	 *
	 * @return	void
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	protected function checkTranslatedShortcut() {

		if (!is_null($this->originalShortcutPage)) {
			$originalShortcutPageOverlay = $this->sys_page->getPageOverlay($this->originalShortcutPage['uid'], $this->sys_language_uid);

			if (!empty($originalShortcutPageOverlay['shortcut']) && $originalShortcutPageOverlay['shortcut'] != $this->id) {
					// the translation of the original shortcut page has a different shortcut target!
					// set the correct page and id

				$shortcut = $this->getPageShortcut(
					$originalShortcutPageOverlay['shortcut'],
					$originalShortcutPageOverlay['shortcut_mode'],
					$originalShortcutPageOverlay['uid']
				);

				$this->id   = $this->contentPid = $shortcut['uid'];
				$this->page = $this->sys_page->getPage($this->id);

					// fix various effects on things like menus f.e.
				$this->fetch_the_id();
				$this->tmpl->rootLine = array_reverse($this->rootLine);
			}
		}
	}

	/**
	 * Checks if any email-submissions or submission via the fe_tce
	 *
	 * @return	string		"email" if a formmail has been sent, "fe_tce" if front-end data submission (like forums, guestbooks) is sent. "" if none.
	 */
	function checkDataSubmission()	{
		$ret = '';
		$formtype_db = isset($_POST['formtype_db']) || isset($_POST['formtype_db_x']);
		$formtype_mail = isset($_POST['formtype_mail']) || isset($_POST['formtype_mail_x']);
		if ($formtype_db || $formtype_mail)	{
			$refInfo = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
			if (t3lib_div::getIndpEnv('TYPO3_HOST_ONLY')==$refInfo['host'] || $this->TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
				if ($this->locDataCheck($_POST['locationData']))	{
					if ($formtype_mail)	{
						$ret = 'email';
					} elseif ($formtype_db && is_array($_POST['data']))	{
						$ret = 'fe_tce';
					}
					$GLOBALS['TT']->setTSlogMessage('"Check Data Submission": Return value: '.$ret,0);
					return $ret;
				}
			} else $GLOBALS['TT']->setTSlogMessage('"Check Data Submission": HTTP_HOST and REFERER HOST did not match when processing submitted formdata!',3);
		}

			// Hook for processing data submission to extensions:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission']))	{
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission'] as $_classRef)	{
				$_procObj = t3lib_div::getUserObj($_classRef);
				$_procObj->checkDataSubmission($this);
			}
		}
		return $ret;
	}

	/**
	 * Processes submitted user data (obsolete "Frontend TCE")
	 *
	 * @return	void
	 * @see tslib_feTCE
	 */
	function fe_tce()	{
		$fe_tce = t3lib_div::makeInstance('tslib_feTCE');
		$fe_tce->start(t3lib_div::_POST('data'),$this->config['FEData.']);
		$fe_tce->includeScripts();
	}

	/**
	 * Checks if a formmail submission can be sent as email
	 *
	 * @param	string		The input from $_POST['locationData']
	 * @return	void
	 * @access private
	 * @see checkDataSubmission()
	 */
	function locDataCheck($locationData)	{
		$locData = explode(':',$locationData);
		if (!$locData[1] ||  $this->sys_page->checkRecord($locData[1],$locData[2],1))	{
			if (count($this->sys_page->getPage($locData[0])))	{	// $locData[1] -check means that a record is checked only if the locationData has a value for a record else than the page.
				return 1;
			} else $GLOBALS['TT']->setTSlogMessage('LocationData Error: The page pointed to by location data ('.$locationData.') was not accessible.',2);
		} else $GLOBALS['TT']->setTSlogMessage('LocationData Error: Location data ('.$locationData.') record pointed to was not accessible.',2);
	}

	/**
	 * Sends the emails from the formmail content object.
	 *
	 * @return	void
	 * @access private
	 * @see checkDataSubmission()
	 */
	function sendFormmail()	{
		$formmail = t3lib_div::makeInstance('t3lib_formmail');

		$EMAIL_VARS = t3lib_div::_POST();
		$locationData = $EMAIL_VARS['locationData'];
		unset($EMAIL_VARS['locationData']);
		unset($EMAIL_VARS['formtype_mail'], $EMAIL_VARS['formtype_mail_x'], $EMAIL_VARS['formtype_mail_y']);

		$integrityCheck = $this->TYPO3_CONF_VARS['FE']['strictFormmail'];

		if (!$this->TYPO3_CONF_VARS['FE']['secureFormmail'])	{
				// Check recipient field:
			$encodedFields = explode(',','recipient,recipient_copy');	// These two fields are the ones which contain recipient addresses that can be misused to send mail from foreign servers.
			foreach ($encodedFields as $fieldKey)	{
				if (strlen($EMAIL_VARS[$fieldKey]))	{
					if ($res = $this->codeString($EMAIL_VARS[$fieldKey], TRUE))	{	// Decode...
						$EMAIL_VARS[$fieldKey] = $res;	// Set value if OK
					} elseif ($integrityCheck)	{	// Otherwise abort:
						$GLOBALS['TT']->setTSlogMessage('"Formmail" discovered a field ('.$fieldKey.') which could not be decoded to a valid string. Sending formmail aborted due to security reasons!',3);
						return false;
					} else {
						$GLOBALS['TT']->setTSlogMessage('"Formmail" discovered a field ('.$fieldKey.') which could not be decoded to a valid string. The security level accepts this, but you should consider a correct coding though!',2);
					}
				}
			}
		} else	{
			$locData = explode(':',$locationData);
			$record = $this->sys_page->checkRecord($locData[1],$locData[2],1);
			$EMAIL_VARS['recipient'] = $record['subheader'];
			$EMAIL_VARS['recipient_copy'] = $this->extractRecipientCopy($record['bodytext']);
		}

			// Hook for preprocessing of the content for formmails:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sendFormmail-PreProcClass']))	{
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sendFormmail-PreProcClass'] as $_classRef)	{
				$_procObj = t3lib_div::getUserObj($_classRef);
				$EMAIL_VARS = $_procObj->sendFormmail_preProcessVariables($EMAIL_VARS,$this);
			}
		}

		$formmail->start($EMAIL_VARS);
		$formmail->sendtheMail();
		$GLOBALS['TT']->setTSlogMessage('"Formmail" invoked, sending mail to '.$EMAIL_VARS['recipient'],0);
	}

	/**
	 * Extracts the value of recipient copy field from a formmail CE bodytext
	 *
	 * @param	string		$bodytext The content of the related bodytext field
	 * @return	string		The value of the recipient_copy field, or an empty string
	 */
	function extractRecipientCopy($bodytext) {
		$recipient_copy = '';
		$fdef = array();
		//|recipient_copy=hidden|karsten@localhost.localdomain
		preg_match('/^[\s]*\|[\s]*recipient_copy[\s]*=[\s]*hidden[\s]*\|(.*)$/m', $bodytext, $fdef);
		$recipient_copy = (!empty($fdef[1])) ? $fdef[1] : '';
		return $recipient_copy;
	}

	/**
	 * Sets the jumpurl for page type "External URL"
	 *
	 * @return	void
	 */
	function setExternalJumpUrl()	{
		if ($extUrl = $this->sys_page->getExtURL($this->page, $this->config['config']['disablePageExternalUrl']))	{
			$this->jumpurl = $extUrl;
		}
	}

	/**
	 * Check the jumpUrl referer if required
	 *
	 * @return	void
	 */
	function checkJumpUrlReferer()	{
		if (strlen($this->jumpurl) && !$this->TYPO3_CONF_VARS['SYS']['doNotCheckReferer']) {
			$referer = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
			if (isset($referer['host']) && !($referer['host'] == t3lib_div::getIndpEnv('TYPO3_HOST_ONLY'))) {
				unset($this->jumpurl);
 			}
		}
	}

	/**
	 * Sends a header "Location" to jumpUrl, if jumpurl is set.
	 * Will exit if a location header is sent (for instance if jumpUrl was triggered)
	 *
	 * "jumpUrl" is a concept where external links are redirected from the index_ts.php script, which first logs the URL.
	 * This feature is only interesting if config.sys_stat is used.
	 *
	 * @return	void
	 */
	function jumpUrl()	{
		if ($this->jumpurl)	{
			if (t3lib_div::_GP('juSecure'))	{
				$locationData = (string)t3lib_div::_GP('locationData');
				$mimeType = (string)t3lib_div::_GP('mimeType');  // Need a type cast here because mimeType is optional!

				$hArr = array(
					$this->jumpurl,
					$locationData,
					$mimeType
				);
				$calcJuHash = t3lib_div::hmac(serialize($hArr));
				$juHash = (string)t3lib_div::_GP('juHash');
				if ($juHash === $calcJuHash)	{
					if ($this->locDataCheck($locationData))	{
						$this->jumpurl = rawurldecode($this->jumpurl);	// 211002 - goes with cObj->filelink() rawurlencode() of filenames so spaces can be allowed.
							// Deny access to files that match TYPO3_CONF_VARS[SYS][fileDenyPattern] and whose parent directory is typo3conf/ (there could be a backup file in typo3conf/ which does not match against the fileDenyPattern)
						$absoluteFileName = t3lib_div::getFileAbsFileName(t3lib_div::resolveBackPath($this->jumpurl), FALSE);
						if (t3lib_div::isAllowedAbsPath($absoluteFileName) && t3lib_div::verifyFilenameAgainstDenyPattern($absoluteFileName) && !t3lib_div::isFirstPartOfStr($absoluteFileName, PATH_site . 'typo3conf')) {
							if (@is_file($absoluteFileName)) {
								$mimeType = $mimeType ? $mimeType : 'application/octet-stream';
								header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
								header('Content-Type: '.$mimeType);
								header('Content-Disposition: attachment; filename="'.basename($absoluteFileName) . '"');
								readfile($absoluteFileName);
								exit;
							} else throw new Exception('jumpurl Secure: "' . $this->jumpurl . '" was not a valid file!', 1294585193);
						} else throw new Exception('jumpurl Secure: The requested file was not allowed to be accessed through jumpUrl (path or file not allowed)!', 1294585194);
					} else throw new Exception('jumpurl Secure: locationData, ' . $locationData . ', was not accessible.', 1294585195);
				} else throw new Exception('jumpurl Secure: Calculated juHash did not match the submitted juHash.', 1294585196);
			} else {
				$TSConf = $this->getPagesTSconfig();
				if ($TSConf['TSFE.']['jumpUrl_transferSession'])	{
					$uParts = parse_url($this->jumpurl);
					$params = '&FE_SESSION_KEY='.rawurlencode($this->fe_user->id.'-'.md5($this->fe_user->id.'/'.$this->TYPO3_CONF_VARS['SYS']['encryptionKey']));
					$this->jumpurl.= ($uParts['query']?'':'?').$params;	// Add the session parameter ...
				}
				if ($TSConf['TSFE.']['jumpURL_HTTPStatusCode']) {
					switch (intval($TSConf['TSFE.']['jumpURL_HTTPStatusCode'])){
						case 301:
							$statusCode = t3lib_utility_Http::HTTP_STATUS_301;
							break;
						case 302:
							$statusCode = t3lib_utility_Http::HTTP_STATUS_302;
							break;
						case 307:
							$statusCode = t3lib_utility_Http::HTTP_STATUS_307;
							break;
						case 303:
						default:
							$statusCode = t3lib_utility_Http::HTTP_STATUS_303;
							break;
					}
				}
				t3lib_utility_Http::redirect($this->jumpurl, $statusCode);
			}
		}
	}

	/**
	 * Sets the URL_ID_TOKEN in the internal var, $this->getMethodUrlIdToken
	 * This feature allows sessions to use a GET-parameter instead of a cookie.
	 *
	 * @return	void
	 * @access private
	 */
	function setUrlIdToken()	{
		if ($this->config['config']['ftu'])	{
			$this->getMethodUrlIdToken = $this->TYPO3_CONF_VARS['FE']['get_url_id_token'];
		} else {
			$this->getMethodUrlIdToken = '';
		}
	}























	/********************************************
	 *
	 * Page generation; cache handling
	 *
	 *******************************************/

	/**
	 * Returns true if the page should be generated
	 * That is if jumpurl is not set and the cacheContentFlag is not set.
	 *
	 * @return	boolean
	 */
	function isGeneratePage()	{
		return (!$this->cacheContentFlag && !$this->jumpurl);
	}

	/**
	 * Temp cache content
	 * The temporary cache will expire after a few seconds (typ. 30) or will be cleared by the rendered page, which will also clear and rewrite the cache.
	 *
	 * @return	void
	 */
	function tempPageCacheContent()	{
		$this->tempContent = false;

		if (!$this->no_cache)	{
			$seconds = 30;
			$title = htmlspecialchars($this->tmpl->printTitle($this->page['title']));
			$request_uri = htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI'));

			$stdMsg = '
		<strong>Page is being generated.</strong><br />
		If this message does not disappear within '.$seconds.' seconds, please reload.';

			$message = $this->config['config']['message_page_is_being_generated'];
			if (strcmp('', $message))	{
				$message = $this->csConvObj->utf8_encode($message,$this->renderCharset);	// This page is always encoded as UTF-8
				$message = str_replace('###TITLE###', $title, $message);
				$message = str_replace('###REQUEST_URI###', $request_uri, $message);
			} else $message = $stdMsg;

			$temp_content = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>'.$title.'</title>
		<meta http-equiv="refresh" content="10" />
	</head>
	<body style="background-color:white; font-family:Verdana,Arial,Helvetica,sans-serif; color:#cccccc; text-align:center;">'.
		$message.'
	</body>
</html>';

				// Fix 'nice errors' feature in modern browsers
			$padSuffix = '<!--pad-->';	// prevent any trims
			$padSize = 768 - strlen($padSuffix) - strlen($temp_content);
			if ($padSize > 0) {
				$temp_content = str_pad($temp_content, $padSize, LF) . $padSuffix;
			}

			if (!$this->headerNoCache() && $cachedRow = $this->getFromCache_queryRow())	{
					// We are here because between checking for cached content earlier and now some other HTTP-process managed to store something in cache AND it was not due to a shift-reload by-pass.
					// This is either the "Page is being generated" screen or it can be the final result.
					// In any case we should not begin another rendering process also, so we silently disable caching and render the page ourselves and thats it.
					// Actually $cachedRow contains content that we could show instead of rendering. Maybe we should do that to gain more performance but then we should set all the stuff done in $this->getFromCache()... For now we stick to this...
				$this->set_no_cache();
			} else {
				$this->tempContent = TRUE;		// This flag shows that temporary content is put in the cache
				$this->setPageCacheContent($temp_content, $this->config, $GLOBALS['EXEC_TIME']+$seconds);
			}
		}
	}

	/**
	 * Set cache content to $this->content
	 *
	 * @return	void
	 */
	function realPageCacheContent()	{
		$cache_timeout = $this->get_cache_timeout();		// seconds until a cached page is too old
		$timeOutTime = $GLOBALS['EXEC_TIME']+$cache_timeout;
		if ($this->config['config']['cache_clearAtMidnight'])	{
			$midnightTime = mktime (0,0,0,date('m',$timeOutTime),date('d',$timeOutTime),date('Y',$timeOutTime));
			if ($midnightTime > $GLOBALS['EXEC_TIME'])	{		// If the midnight time of the expire-day is greater than the current time, we may set the timeOutTime to the new midnighttime.
				$timeOutTime = $midnightTime;
			}
		}
		$this->tempContent = false;
		$this->setPageCacheContent($this->content, $this->config, $timeOutTime);

			// Hook for cache post processing (eg. writing static files!)
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache']))	{
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache'] as $_classRef)	{
				$_procObj = t3lib_div::getUserObj($_classRef);
				$_procObj->insertPageIncache($this,$timeOutTime);
			}
		}
	}

	/**
	 * Sets cache content; Inserts the content string into the cache_pages cache.
	 *
	 * @param	string		The content to store in the HTML field of the cache table
	 * @param	mixed		The additional cache_data array, fx. $this->config
	 * @param	integer		Expiration timestamp
	 * @return	void
	 * @see realPageCacheContent(), tempPageCacheContent()
	 */
	function setPageCacheContent($content, $data, $expirationTstamp) {

		if (TYPO3_UseCachingFramework) {
			$cacheData = array(
				'identifier'	=> $this->newHash,
				'page_id'		=> $this->id,
				'content'			=> $content,
				'temp_content'	=> $this->tempContent,
				'cache_data'	=> serialize($data),
				'expires'		=> $expirationTstamp,
				'tstamp'		=> $GLOBALS['EXEC_TIME']
			);

			$this->cacheExpires = $expirationTstamp;

			$this->pageCacheTags[] = 'pageId_' . $cacheData['page_id'];

			if ($this->page_cache_reg1) {
				$reg1 = intval($this->page_cache_reg1);

				$cacheData['reg1']     = $reg1;
				$this->pageCacheTags[] = 'reg1_' . $reg1;
			}

			$this->pageCache->set(
				$this->newHash,
				$cacheData,
				$this->pageCacheTags,
				$expirationTstamp - $GLOBALS['EXEC_TIME']
			);
		} else {
			$this->clearPageCacheContent();
			$insertFields = array(
				'hash' => $this->newHash,
				'page_id' => $this->id,
				'HTML' => $content,
				'temp_content' => $this->tempContent,
				'cache_data' => serialize($data),
				'expires' => $expirationTstamp,
				'tstamp' => $GLOBALS['EXEC_TIME']
			);

			$this->cacheExpires = $expirationTstamp;

			if ($this->page_cache_reg1)	{
				$insertFields['reg1'] = intval($this->page_cache_reg1);
			}
			$this->pageCachePostProcess($insertFields,'set');
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_pages', $insertFields);
		}
	}

	/**
	 * Clears cache content (for $this->newHash)
	 *
	 * @return	void
	 */
	function clearPageCacheContent() {
		if (TYPO3_UseCachingFramework) {
			$this->pageCache->remove($this->newHash);
		} else {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'hash='.$GLOBALS['TYPO3_DB']->fullQuoteStr($this->newHash, 'cache_pages'));
		}
	}

 	/**
	 * Post processing page cache rows for both get and set.
	 *
	 * @param	array		Input "cache_pages" row, passed by reference!
	 * @param	string		Type of operation, either "get" or "set"
	 * @return	void
	 */
	function pageCachePostProcess(&$row,$type)	{

		if ($this->TYPO3_CONF_VARS['FE']['pageCacheToExternalFiles'])	{
			$cacheFileName = PATH_site.'typo3temp/cache_pages/'.$row['hash']{0}.$row['hash']{1}.'/'.$row['hash'].'.html';
			switch((string)$type)	{
				case 'get':
					$row['HTML'] = @is_file($cacheFileName) ? t3lib_div::getUrl($cacheFileName) : '<!-- CACHING ERROR, sorry -->';
				break;
				case 'set':
					t3lib_div::writeFileToTypo3tempDir($cacheFileName, $row['HTML']);
					$row['HTML'] = '';
				break;
			}
		}
	}

	/**
	 * Clears cache content for a list of page ids
	 *
	 * @param	string		A list of INTEGER numbers which points to page uids for which to clear entries in the cache_pages cache (page content cache)
	 * @return	void
	 */
	function clearPageCacheContent_pidList($pidList) {
		if (TYPO3_UseCachingFramework) {
			$pageIds = t3lib_div::trimExplode(',', $pidList);
			foreach ($pageIds as $pageId) {
				$this->pageCache->flushByTag('pageId_' . (int) $pageId);
			}
		} else {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($pidList).')');
		}
	}

	/**
	 * Sets sys last changed
	 * Setting the SYS_LASTCHANGED value in the pagerecord: This value will thus be set to the highest tstamp of records rendered on the page. This includes all records with no regard to hidden records, userprotection and so on.
	 *
	 * @return	void
	 * @see tslib_cObj::lastChanged()
	 */
	function setSysLastChanged()	{
		if ($this->page['SYS_LASTCHANGED'] < intval($this->register['SYS_LASTCHANGED']))	{
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid='.intval($this->id), array('SYS_LASTCHANGED' => intval($this->register['SYS_LASTCHANGED'])));
		}
	}

	/**
	 * Lock the page generation process
	 * The lock is used to queue page requests until this page is successfully stored in the cache.
	 *
	 * @param	t3lib_lock	Reference to a locking object
	 * @param	string		String to identify the lock in the system
	 * @return	boolean		Returns true if the lock could be obtained, false otherwise (= process had to wait for existing lock to be released)
	 * @see releasePageGenerationLock()
	 */
	function acquirePageGenerationLock(&$lockObj, $key)	{
		if ($this->no_cache || $this->headerNoCache()) {
			t3lib_div::sysLog('Locking: Page is not cached, no locking required', 'cms', t3lib_div::SYSLOG_SEVERITY_INFO);
			return true;	// No locking is needed if caching is disabled
		}

		try {
			if (!is_object($lockObj)) {
				$lockObj = t3lib_div::makeInstance('t3lib_lock', $key, $this->TYPO3_CONF_VARS['SYS']['lockingMode']);
			}

			$success = false;
			if (strlen($key)) {
					// true = Page could get locked without blocking
					// false = Page could get locked but process was blocked before
				$success = $lockObj->acquire();
				if ($lockObj->getLockStatus()) {
					$lockObj->sysLog('Acquired lock');
				}
			}
		} catch (Exception $e) {
			t3lib_div::sysLog('Locking: Failed to acquire lock: '.$e->getMessage(), 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
			$success = false;	// If locking fails, return with false and continue without locking
		}

		return $success;
	}

	/**
	 * Release the page generation lock
	 *
	 * @param	t3lib_lock	Reference to a locking object
	 * @return	boolean		Returns true on success, false otherwise
	 * @see acquirePageGenerationLock()
	 */
	function releasePageGenerationLock(&$lockObj) {
		$success = false;
			// If lock object is set and was acquired (may also happen if no_cache was enabled during runtime), release it:
		if (is_object($lockObj) && $lockObj instanceof t3lib_lock && $lockObj->getLockStatus()) {
			$success = $lockObj->release();
			$lockObj->sysLog('Released lock');
			$lockObj = null;
			// Otherwise, if caching is disabled, no locking is required:
		} elseif ($this->no_cache || $this->headerNoCache()) {
			$success = true;
		}
		return $success;
	}

	/**
	 * adds tags to this page's cache entry, you can then f.e. remove cache
	 * entries by tag
	 *
	 * @param array an array of tag
	 * @return	void
	 */
	public function addCacheTags(array $tags) {
		$this->pageCacheTags = array_merge($this->pageCacheTags, $tags);
	}




















	/********************************************
	 *
	 * Page generation; rendering and inclusion
	 *
	 *******************************************/

	/**
	 * Does some processing BEFORE the pagegen script is included.
	 *
	 * @return	void
	 */
	function generatePage_preProcessing()	{
		$this->newHash = $this->getHash();	// Same codeline as in getFromCache(). But $this->all has been changed by t3lib_TStemplate::start() in the meantime, so this must be called again!
		$this->config['hash_base'] = $this->hash_base;	// For cache management informational purposes.

		if (!is_object($this->pages_lockObj) || $this->pages_lockObj->getLockStatus()==false) {
				// Here we put some temporary stuff in the cache in order to let the first hit generate the page. The temporary cache will expire after a few seconds (typ. 30) or will be cleared by the rendered page, which will also clear and rewrite the cache.
			$this->tempPageCacheContent();
		}

			// Setting cache_timeout_default. May be overridden by PHP include scritps.
		$this->cacheTimeOutDefault = intval($this->config['config']['cache_period']);

			// page is generated
		$this->no_cacheBeforePageGen = $this->no_cache;
	}

	/**
	 * Determines to include custom or pagegen.php script
	 * returns script-filename if a TypoScript (config) script is defined and should be include instead of pagegen.php
	 *
	 * @return	string		The relative filepath of "config.pageGenScript" if found and allowed
	 */
	function generatePage_whichScript()	{
		if (!$this->TYPO3_CONF_VARS['FE']['noPHPscriptInclude'] && $this->config['config']['pageGenScript'])	{
			return $this->tmpl->getFileName($this->config['config']['pageGenScript']);
		}
	}

	/**
	 * Does some processing AFTER the pagegen script is included.
	 * This includes calling tidy (if configured), XHTML cleaning (if configured), caching the page, indexing the page (if configured) and setting sysLastChanged
	 *
	 * @return	void
	 */
	function generatePage_postProcessing()	{
			// This is to ensure, that the page is NOT cached if the no_cache parameter was set before the page was generated. This is a safety precaution, as it could have been unset by some script.
		if ($this->no_cacheBeforePageGen) $this->set_no_cache();

			// Tidy up the code, if flag...
		if ($this->TYPO3_CONF_VARS['FE']['tidy_option'] == 'all') {
			$GLOBALS['TT']->push('Tidy, all','');
				$this->content = $this->tidyHTML($this->content);
			$GLOBALS['TT']->pull();
		}

			// XHTML-clean the code, if flag set
		if ($this->doXHTML_cleaning() == 'all') {
			$GLOBALS['TT']->push('XHTML clean, all','');
				$XHTML_clean = t3lib_div::makeInstance('t3lib_parsehtml');
				$this->content = $XHTML_clean->XHTML_clean($this->content);
			$GLOBALS['TT']->pull();
		}

			// Fix local anchors in links, if flag set
		if ($this->doLocalAnchorFix() == 'all') {
			$GLOBALS['TT']->push('Local anchor fix, all','');
				$this->prefixLocalAnchorsWithScript();
			$GLOBALS['TT']->pull();
		}

			// Hook for post-processing of page content cached/non-cached:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'])) {
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

			// Processing if caching is enabled:
		if (!$this->no_cache) {
					// Tidy up the code, if flag...
			if ($this->TYPO3_CONF_VARS['FE']['tidy_option'] == 'cached') {
				$GLOBALS['TT']->push('Tidy, cached','');
					$this->content = $this->tidyHTML($this->content);
				$GLOBALS['TT']->pull();
			}
				// XHTML-clean the code, if flag set
			if ($this->doXHTML_cleaning() == 'cached')		{
				$GLOBALS['TT']->push('XHTML clean, cached','');
					$XHTML_clean = t3lib_div::makeInstance('t3lib_parsehtml');
					$this->content = $XHTML_clean->XHTML_clean($this->content);
				$GLOBALS['TT']->pull();
			}
				// Fix local anchors in links, if flag set
			if ($this->doLocalAnchorFix() == 'cached')		{
				$GLOBALS['TT']->push('Local anchor fix, cached','');
					$this->prefixLocalAnchorsWithScript();
				$GLOBALS['TT']->pull();
			}

				// Hook for post-processing of page content before being cached:
			if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached']))	{
				$_params = array('pObj' => &$this);
				foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'] as $_funcRef)	{
					t3lib_div::callUserFunction($_funcRef,$_params,$this);
				}
			}
		}

			// Convert char-set for output: (should be BEFORE indexing of the content (changed 22/4 2005)), because otherwise indexed search might convert from the wrong charset! One thing is that the charset mentioned in the HTML header would be wrong since the output charset (metaCharset) has not been converted to from renderCharset. And indexed search will internally convert from metaCharset to renderCharset so the content MUST be in metaCharset already!
		$this->content = $this->convOutputCharset($this->content,'mainpage');

			// Hook for indexing pages
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'])) {
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'] as $_classRef) {
				$_procObj = t3lib_div::getUserObj($_classRef);
				$_procObj->hook_indexContent($this);
			}
		}

			// Storing for cache:
		if (!$this->no_cache)	{
			$this->realPageCacheContent();
		} elseif ($this->tempContent)	{		// If there happens to be temporary content in the cache and the cache was not cleared due to new content, put it in... ($this->no_cache=0)
			$this->clearPageCacheContent();
			$this->tempContent = false;
		}

			// Release open locks
		$this->releasePageGenerationLock($this->pagesection_lockObj);
		$this->releasePageGenerationLock($this->pages_lockObj);

			// Sets sys-last-change:
		$this->setSysLastChanged();
	}

	/**
	 * Processes the INTinclude-scripts
	 *
	 * @return	void
	 */
	function INTincScript()	{
			// Deprecated stuff:
		$this->additionalHeaderData = is_array($this->config['INTincScript_ext']['additionalHeaderData']) ? $this->config['INTincScript_ext']['additionalHeaderData'] : array();
		$this->additionalJavaScript = $this->config['INTincScript_ext']['additionalJavaScript'];
		$this->additionalCSS = $this->config['INTincScript_ext']['additionalCSS'];
		$this->JSCode = $this->additionalHeaderData['JSCode'];
		$this->JSImgCode = $this->additionalHeaderData['JSImgCode'];
		$this->divSection='';

		do {
			$INTiS_config = $this->config['INTincScript'];
			$this->INTincScript_includeLibs($INTiS_config);
			$this->INTincScript_process($INTiS_config);
				// Check if there were new items added to INTincScript during the previous execution:
			$INTiS_config = array_diff_assoc($this->config['INTincScript'], $INTiS_config);
			$reprocess = (count($INTiS_config) ? true : false);
		} while($reprocess);

		$GLOBALS['TT']->push('Substitute header section');
		$this->INTincScript_loadJSCode();
		$this->content = str_replace('<!--HD_'.$this->config['INTincScript_ext']['divKey'].'-->', $this->convOutputCharset(implode(LF,$this->additionalHeaderData),'HD'), $this->content);
		$this->content = str_replace('<!--TDS_'.$this->config['INTincScript_ext']['divKey'].'-->', $this->convOutputCharset($this->divSection,'TDS'), $this->content);
		$this->setAbsRefPrefix();
		$GLOBALS['TT']->pull();
	}

	/**
	 * Include libraries for uncached objects.
	 *
	 * @param	array		$INTiS_config: $GLOBALS['TSFE']->config['INTincScript'] or part of it
	 * @return	void
	 * @see		INTincScript()
	 */
	protected function INTincScript_includeLibs($INTiS_config) {
		foreach($INTiS_config as $INTiS_cPart) {
			if (isset($INTiS_cPart['conf']['includeLibs']) && $INTiS_cPart['conf']['includeLibs']) {
				$INTiS_resourceList = t3lib_div::trimExplode(',', $INTiS_cPart['conf']['includeLibs'], true);
				$this->includeLibraries($INTiS_resourceList);
			}
		}
	}

 	/**
	 * Processes the INTinclude-scripts and substitue in content.
	 *
	 * @param	array		$INTiS_config: $GLOBALS['TSFE']->config['INTincScript'] or part of it
	 * @return	void
	 * @see		INTincScript()
	 */
	protected function INTincScript_process($INTiS_config)	{
		$GLOBALS['TT']->push('Split content');
		$INTiS_splitC = explode('<!--INT_SCRIPT.',$this->content);			// Splits content with the key.
		$this->content = '';
		$GLOBALS['TT']->setTSlogMessage('Parts: '.count($INTiS_splitC));
		$GLOBALS['TT']->pull();

		foreach($INTiS_splitC as $INTiS_c => $INTiS_cPart)	{
			if (substr($INTiS_cPart,32,3)=='-->')	{	// If the split had a comment-end after 32 characters it's probably a split-string
				$INTiS_key = 'INT_SCRIPT.'.substr($INTiS_cPart,0,32);
				$GLOBALS['TT']->push('Include '.$INTiS_config[$INTiS_key]['file'],'');
				$incContent='';
				if (is_array($INTiS_config[$INTiS_key]))	{
					$INTiS_cObj = unserialize($INTiS_config[$INTiS_key]['cObj']);
					/* @var $INTiS_cObj tslib_cObj */
					$INTiS_cObj->INT_include=1;
					switch($INTiS_config[$INTiS_key]['type'])	{
						case 'SCRIPT':
							$incContent = $INTiS_cObj->PHP_SCRIPT($INTiS_config[$INTiS_key]['conf']);
						break;
						case 'COA':
							$incContent = $INTiS_cObj->COBJ_ARRAY($INTiS_config[$INTiS_key]['conf']);
						break;
						case 'FUNC':
							$incContent = $INTiS_cObj->USER($INTiS_config[$INTiS_key]['conf']);
						break;
						case 'POSTUSERFUNC':
							$incContent = $INTiS_cObj->callUserFunction($INTiS_config[$INTiS_key]['postUserFunc'], $INTiS_config[$INTiS_key]['conf'], $INTiS_config[$INTiS_key]['content']);
						break;
					}
				}
				$this->content.= $this->convOutputCharset($incContent,'INC-'.$INTiS_c);
				$this->content.= substr($INTiS_cPart,35);
				$GLOBALS['TT']->pull($incContent);
			} else {
				$this->content.= ($INTiS_c?'<!--INT_SCRIPT.':'').$INTiS_cPart;
			}
		}
	}

	/**
	 * Loads the JavaScript code for INTincScript
	 *
	 * @return	void
	 * @access private
	 */
	function INTincScript_loadJSCode()	{
		if ($this->JSImgCode)	{	// If any images added, then add them to the javascript section
			$this->additionalHeaderData['JSImgCode']='
<script type="text/javascript">
	/*<![CDATA[*/
<!--
if (version == "n3") {
'.trim($this->JSImgCode).'
}
// -->
	/*]]>*/
</script>';
		}
		if ($this->JSCode || count($this->additionalJavaScript))	{	// Add javascript
			$this->additionalHeaderData['JSCode']='
<script type="text/javascript">
	/*<![CDATA[*/
<!--
'.implode(LF,$this->additionalJavaScript).'
'.trim($this->JSCode).'
// -->
	/*]]>*/
</script>';
		}
		if (count($this->additionalCSS))	{	// Add javascript
			$this->additionalHeaderData['_CSS']='
<style type="text/css">
	/*<![CDATA[*/
<!--
'.implode(LF,$this->additionalCSS).'
// -->
	/*]]>*/
</style>';
		}
	}

	/**
	 * Determines if there are any INTincScripts to include
	 *
	 * @return	boolean		Returns true if scripts are found (and not jumpurl)
	 */
	function isINTincScript()	{
		return	(is_array($this->config['INTincScript']) && !$this->jumpurl);
	}

	/**
	 * Returns the mode of XHTML cleaning
	 *
	 * @return	string		Keyword: "all", "cached" or "output"
	 */
	function doXHTML_cleaning()	{
		return $this->config['config']['xhtml_cleaning'];
	}

	/**
	 * Returns the mode of Local Anchor prefixing
	 *
	 * @return	string		Keyword: "all", "cached" or "output"
	 */
	function doLocalAnchorFix()	{
		return (isset($this->config['config']['prefixLocalAnchors'])) ? $this->config['config']['prefixLocalAnchors'] : NULL;
	}
















	/********************************************
	 *
	 * Finished off; outputting, storing session data, statistics...
	 *
	 *******************************************/

	/**
	 * Determines if content should be outputted.
	 * Outputting content is done only if jumpUrl is NOT set.
	 *
	 * @return	boolean		Returns true if $this->jumpurl is not set.
	 */
	function isOutputting()	{

			// Initialize by status of jumpUrl:
		$enableOutput = (!$this->jumpurl);

			// Call hook for possible disabling of output:
		if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting'])
			&& is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting'])) {

			$_params = array('pObj' => &$this, 'enableOutput' => &$enableOutput);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

		return $enableOutput;
	}

	/**
	 * Process the output before it's actually outputted. Sends headers also.
	 * This includes substituting the "username" comment, sending additional headers (as defined in the TypoScript "config.additionalheaders" object), tidy'ing content, XHTML cleaning content (if configured)
	 * Works on $this->content.
	 *
	 * @return	void
	 */
	function processOutput() {

			// Set header for charset-encoding unless disabled
		if (empty($this->config['config']['disableCharsetHeader'])) {
			$headLine = 'Content-Type: text/html; charset='.trim($this->metaCharset);
			header($headLine);
		}

			// Set cache related headers to client (used to enable proxy / client caching!)
		if (!empty($this->config['config']['sendCacheHeaders'])) {
			$this->sendCacheHeaders();
		}

			// Set headers, if any
		if (!empty($this->config['config']['additionalHeaders'])) {
			$headerArray = explode('|', $this->config['config']['additionalHeaders']);
			foreach ($headerArray as $headLine) {
				$headLine = trim($headLine);
				header($headLine);
			}
		}

			// Send appropriate status code in case of temporary content
		if ($this->tempContent) {
			$this->addTempContentHttpHeaders();
		}

			// Make substitution of eg. username/uid in content only if cache-headers for client/proxy caching is NOT sent!
		if (!$this->isClientCachable) {
			$this->contentStrReplace();
		}

			// Tidy up the code, if flag...
		if ($this->TYPO3_CONF_VARS['FE']['tidy_option'] == 'output') {
			$GLOBALS['TT']->push('Tidy, output','');
				$this->content = $this->tidyHTML($this->content);
			$GLOBALS['TT']->pull();
		}
			// XHTML-clean the code, if flag set
		if ($this->doXHTML_cleaning() == 'output') {
			$GLOBALS['TT']->push('XHTML clean, output','');
				$XHTML_clean = t3lib_div::makeInstance('t3lib_parsehtml');
				$this->content = $XHTML_clean->XHTML_clean($this->content);
			$GLOBALS['TT']->pull();
		}
			// Fix local anchors in links, if flag set
		if ($this->doLocalAnchorFix() == 'output') {
			$GLOBALS['TT']->push('Local anchor fix, output','');
				$this->prefixLocalAnchorsWithScript();
			$GLOBALS['TT']->pull();
		}

			// Hook for post-processing of page content before output:
		if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']) && is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'])) {
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'] as $_funcRef) {
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

			// Send content-lenght header.
			// Notice that all HTML content outside the length of the content-length header will be cut off! Therefore content of unknown length from included PHP-scripts and if admin users are logged in (admin panel might show...) or if debug mode is turned on, we disable it!
		if (!empty($this->config['config']['enableContentLengthHeader']) &&
			!$this->isEXTincScript() &&
			!$this->beUserLogin  &&
			!$this->TYPO3_CONF_VARS['FE']['debug'] &&
			!$this->config['config']['debug'] &&
			!$this->doWorkspacePreview()
		) {
			header('Content-Length: '.strlen($this->content));
		}
	}

	/**
	 * Send cache headers good for client/reverse proxy caching
	 * This function should not be called if the page content is temporary (like for "Page is being generated..." message, but in that case it is ok because the config-variables are not yet available and so will not allow to send cache headers)
	 *
	 * @return	void
	 * @co-author	Ole Tange, Forbrugernes Hus, Denmark
	 */
	function sendCacheHeaders()	{

			// Getting status whether we can send cache control headers for proxy caching:
		$doCache = $this->isStaticCacheble();

			// This variable will be TRUE unless cache headers are configured to be sent ONLY if a branch does not allow logins and logins turns out to be allowed anyway...
		$loginsDeniedCfg = (empty($this->config['config']['sendCacheHeaders_onlyWhenLoginDeniedInBranch']) || empty($this->loginAllowedInBranch));

			// Finally, when backend users are logged in, do not send cache headers at all (Admin Panel might be displayed for instance).
		if ($doCache
				&& !$this->beUserLogin
				&& !$this->doWorkspacePreview()
				&& $loginsDeniedCfg)	{

				// Build headers:
			$headers = array(
				'Last-Modified: '.gmdate('D, d M Y H:i:s T', $this->register['SYS_LASTCHANGED']),
				'Expires: '.gmdate('D, d M Y H:i:s T', $this->cacheExpires),
				'ETag: "' . md5($this->content) . '"',
				'Cache-Control: max-age='.($this->cacheExpires - $GLOBALS['EXEC_TIME']),		// no-cache
				'Pragma: public',
			);

			$this->isClientCachable = TRUE;
		} else {
				// Build headers:
			$headers = array(
				#'Last-Modified: '.gmdate('D, d M Y H:i:s T', $this->register['SYS_LASTCHANGED']),
				#'ETag: '.md5($this->content),

				#'Cache-Control: no-cache',
				#'Pragma: no-cache',
				'Cache-Control: private',		// Changed to this according to Ole Tange, FI.dk
			);

			$this->isClientCachable = FALSE;

				// Now, if a backend user is logged in, tell him in the Admin Panel log what the caching status would have been:
			if ($this->beUserLogin) {
				if ($doCache)	{
					$GLOBALS['TT']->setTSlogMessage('Cache-headers with max-age "'.($this->cacheExpires - $GLOBALS['EXEC_TIME']).'" would have been sent');
				} else {
					$reasonMsg = '';
					$reasonMsg.= !$this->no_cache ? '' : 'Caching disabled (no_cache). ';
					$reasonMsg.= !$this->isINTincScript() ? '' : '*_INT object(s) on page. ';
					$reasonMsg.= !$this->isEXTincScript() ? '' : '*_EXT object(s) on page. ';
					$reasonMsg.= !is_array($this->fe_user->user) ? '' : 'Frontend user logged in. ';
					$GLOBALS['TT']->setTSlogMessage('Cache-headers would disable proxy caching! Reason(s): "'.$reasonMsg.'"',1);
				}
			}
		}

			// Send headers:
		foreach($headers as $hL)	{
			header($hL);
		}
	}

	/**
	 * Reporting status whether we can send cache control headers for proxy caching or publishing to static files
	 *
	 * Rules are:
	 * no_cache cannot be set: If it is, the page might contain dynamic content and should never be cached.
	 * There can be no USER_INT objects on the page ("isINTincScript()" / "isEXTincScript()") because they implicitly indicate dynamic content
	 * There can be no logged in user because user sessions are based on a cookie and thereby does not offer client caching a chance to know if the user is logged in. Actually, there will be a reverse problem here; If a page will somehow change when a user is logged in he may not see it correctly if the non-login version sent a cache-header! So do NOT use cache headers in page sections where user logins change the page content. (unless using such as realurl to apply a prefix in case of login sections)
	 *
	 * @return	boolean
	 */
	function isStaticCacheble()	{
		$doCache = !$this->no_cache
				&& !$this->isINTincScript()
				&& !$this->isEXTincScript()
				&& !$this->isUserOrGroupSet();
		return $doCache;
	}

	/**
	 * Substitute various tokens in content. This should happen only if the content is not cached by proxies or client browsers.
	 *
	 * @return	void
	 */
	function contentStrReplace()	{
		$search = array();
		$replace = array();

			// Substitutes username mark with the username
		if ($this->fe_user->user['uid'])	{

				// User name:
			$token = (isset($this->config['config']['USERNAME_substToken'])) ? trim($this->config['config']['USERNAME_substToken']) : '';
			$search[] = ($token ? $token : '<!--###USERNAME###-->');
			$replace[] = $this->fe_user->user['username'];

				// User uid (if configured):
			$token = (isset($this->config['config']['USERUID_substToken'])) ? trim($this->config['config']['USERUID_substToken']) : '';
			if ($token) {
				$search[] = $token;
				$replace[] = $this->fe_user->user['uid'];
			}
		}

			// Substitutes get_URL_ID in case of GET-fallback
		if ($this->getMethodUrlIdToken)	{
			$search[] = $this->getMethodUrlIdToken;
			$replace[] = $this->fe_user->get_URL_ID;
		}

			// Hook for supplying custom search/replace data
		if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace'])) {
			$contentStrReplaceHooks = &$this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace'];
			if (is_array($contentStrReplaceHooks)) {
				$_params = array(
					'search' => &$search,
					'replace' => &$replace,
				);
				foreach ($contentStrReplaceHooks as $_funcRef) {
					t3lib_div::callUserFunction($_funcRef, $_params, $this);
				}
			}
		}

		if (count($search)) {
			$this->content = str_replace($search, $replace, $this->content);
		}
	}

	/**
	 * Determines if any EXTincScripts should be included
	 *
	 * @return	boolean		True, if external php scripts should be included (set by PHP_SCRIPT_EXT cObjects)
	 * @see tslib_cObj::PHP_SCRIPT
	 */
	function isEXTincScript()	{
		return (isset($this->config['EXTincScript']) && is_array($this->config['EXTincScript']));
	}

	/**
	 * Stores session data for the front end user
	 *
	 * @return	void
	 */
	function storeSessionData()	{
		$this->fe_user->storeSessionData();
	}

	/**
	 * Sets the parsetime of the page.
	 *
	 * @return	void
	 * @access private
	 */
	function setParseTime()	{
        // Compensates for the time consumed with Back end user initialization.
        $microtime_start            = (isset($GLOBALS['TYPO3_MISC']['microtime_start'])) ? $GLOBALS['TYPO3_MISC']['microtime_start'] : NULL;
        $microtime_end              = (isset($GLOBALS['TYPO3_MISC']['microtime_end'])) ? $GLOBALS['TYPO3_MISC']['microtime_end'] : NULL;
        $microtime_BE_USER_start    = (isset($GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'])) ? $GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'] : NULL;
        $microtime_BE_USER_end      = (isset($GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'])) ? $GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'] : NULL;

        $this->scriptParseTime = $GLOBALS['TT']->getMilliseconds($microtime_end) - $GLOBALS['TT']->getMilliseconds($microtime_start)
                                - ($GLOBALS['TT']->getMilliseconds($microtime_BE_USER_end) - $GLOBALS['TT']->getMilliseconds($microtime_BE_USER_start));
    }

	/**
	 * Initialize file-based statistics handling: Check filename and permissions, and create the logfile if it does not exist yet.
	 * This function should be called with care because it might overwrite existing settings otherwise.
	 *
	 * @return	boolean		True if statistics are enabled (will require some more processing after charset handling is initialized)
	 * @access private
	 */
	protected function statistics_init()	{
		$setStatPageName = false;
		$theLogFile = $this->TYPO3_CONF_VARS['FE']['logfile_dir'].strftime($this->config['config']['stat_apache_logfile']);

			// Add PATH_site left to $theLogFile if the path is not absolute yet
		if (!t3lib_div::isAbsPath($theLogFile)) {
			$theLogFile = PATH_site.$theLogFile;
		}

		if ($this->config['config']['stat_apache'] && $this->config['config']['stat_apache_logfile'] && !strstr($this->config['config']['stat_apache_logfile'],'/')) {
			if (t3lib_div::isAllowedAbsPath($theLogFile)) {
				if (!@is_file($theLogFile)) {
					touch($theLogFile);	// Try to create the logfile
					t3lib_div::fixPermissions($theLogFile);
				}

				if (@is_file($theLogFile) && @is_writable($theLogFile)) {
					$this->config['stat_vars']['logFile'] = $theLogFile;
					$setStatPageName = true;	// Set page name later on
				} else {
					$GLOBALS['TT']->setTSlogMessage('Could not set logfile path. Check filepath and permissions.',3);
				}
			}
		}

		return $setStatPageName;
	}

	/**
	 * Set the pagename for the logfile entry
	 *
	 * @return	void
	 * @access private
	 */
	protected function statistics_init_pagename()	{
		if (preg_match('/utf-?8/i', $this->config['config']['stat_apache_niceTitle'])) {	// Make life easier and accept variants for utf-8
			$this->config['config']['stat_apache_niceTitle'] = 'utf-8';
		}

		if ($this->config['config']['stat_apache_niceTitle'] == 'utf-8') {
			$shortTitle = $this->csConvObj->utf8_encode($this->page['title'],$this->renderCharset);
		} elseif ($this->config['config']['stat_apache_niceTitle']) {
			$shortTitle = $this->csConvObj->specCharsToASCII($this->renderCharset,$this->page['title']);
		} else {
			$shortTitle = $this->page['title'];
		}

		$len = t3lib_div::intInRange($this->config['config']['stat_apache_pageLen'],1,100,30);
		if ($this->config['config']['stat_apache_niceTitle'] == 'utf-8') {
			$shortTitle = rawurlencode($this->csConvObj->substr('utf-8',$shortTitle,0,$len));
		} else {
			$shortTitle = substr(preg_replace('/[^.[:alnum:]_-]/','_',$shortTitle),0,$len);
		}

		$pageName = $this->config['config']['stat_apache_pagenames'] ? $this->config['config']['stat_apache_pagenames'] : '[path][title]--[uid].html';
		$pageName = str_replace('[title]', $shortTitle ,$pageName);
		$pageName = str_replace('[uid]',$this->page['uid'],$pageName);
		$pageName = str_replace('[alias]',$this->page['alias'],$pageName);
		$pageName = str_replace('[type]',$this->type,$pageName);
		$pageName = str_replace('[request_uri]',t3lib_div::getIndpEnv('REQUEST_URI'),$pageName);

		$temp = $this->config['rootLine'];
		if ($temp) {	// rootLine does not exist if this function is called at early stage (e.g. if DB connection failed)
			array_pop($temp);
			if ($this->config['config']['stat_apache_noRoot']) {
				array_shift($temp);
			}

			$len = t3lib_div::intInRange($this->config['config']['stat_titleLen'],1,100,20);
			if ($this->config['config']['stat_apache_niceTitle'] == 'utf-8') {
				$path = '';
				$c = count($temp);
				for ($i=0; $i<$c; $i++) {
					if ($temp[$i]['uid']) {
						$p = $this->csConvObj->crop('utf-8',$this->csConvObj->utf8_encode($temp[$i]['title'],$this->renderCharset),$len,"\xE2\x80\xA6");	// U+2026; HORIZONTAL ELLIPSIS
						$path.= '/' . rawurlencode($p);
					}
				}
			} elseif ($this->config['config']['stat_apache_niceTitle']) {
				$path = $this->csConvObj->specCharsToASCII($this->renderCharset,$this->sys_page->getPathFromRootline($temp,$len));
			} else {
				$path = $this->sys_page->getPathFromRootline($temp,$len);
			}
		} else {
			$path = '';	// If rootLine is missing, we just drop the path...
		}

		if ($this->config['config']['stat_apache_niceTitle'] == 'utf-8') {
			$this->config['stat_vars']['pageName'] = str_replace('[path]', $path.'/', $pageName);
		} else {
			$this->config['stat_vars']['pageName'] = str_replace('[path]', preg_replace('/[^.[:alnum:]\/_-]/','_',$path.'/'), $pageName);
		}
	}

	/**
	 * Saves hit statistics
	 *
	 * @return	void
	 */
	function statistics()	{
		if (!empty($this->config['config']['stat']) &&
				(!strcmp('',$this->config['config']['stat_typeNumList']) || t3lib_div::inList(str_replace(' ','',$this->config['config']['stat_typeNumList']), $this->type)) &&
				(empty($this->config['config']['stat_excludeBEuserHits']) || !$this->beUserLogin) &&
				(empty($this->config['config']['stat_excludeIPList']) || !t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'),str_replace(' ','',$this->config['config']['stat_excludeIPList'])))) {

			$GLOBALS['TT']->push('Stat');
				if (t3lib_extMgm::isLoaded('sys_stat') && !empty($this->config['config']['stat_mysql'])) {

						// Jumpurl:
					$sword = t3lib_div::_GP('sword');
					if ($sword)	{
						$jumpurl_msg = 'sword:'.$sword;
					} elseif ($this->jumpurl) {
						$jumpurl_msg = 'jumpurl:'.$this->jumpurl;
					} else {
						$jumpurl_msg = '';
					}

						// Flags: bits: 0 = BE_user, 1=Cached page?
					$flags=0;
					if ($this->beUserLogin) {$flags|=1;}
					if ($this->cacheContentFlag) {$flags|=2;}

						// Ref url:
					$refUrl = t3lib_div::getIndpEnv('HTTP_REFERER');
					$thisUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR');
					if (t3lib_div::isFirstPartOfStr($refUrl,$thisUrl))	{
						$refUrl='[LOCAL]';
					}

					$insertFields = array(
						'page_id' => intval($this->id),							// id
						'page_type' => intval($this->type),						// type
						'jumpurl' => $jumpurl_msg,								// jumpurl message
						'feuser_id' => $this->fe_user->user['uid'],				// fe_user id, integer
						'cookie' => $this->fe_user->id,							// cookie as set or retrieve. If people has cookies disabled this will vary all the time...
						'sureCookie' => hexdec(substr($this->fe_user->cookieId,0,8)),	// This is the cookie value IF the cookie WAS actually set. However the first hit where the cookie is set will thus NOT be logged here. So this lets you select for a session of at least two clicks...
						'rl0' => $this->config['rootLine'][0]['uid'],			// RootLevel 0 uid
						'rl1' => $this->config['rootLine'][1]['uid'],			// RootLevel 1 uid
						'client_browser' => $GLOBALS['CLIENT']['BROWSER'],		// Client browser (net, msie, opera)
						'client_version' => $GLOBALS['CLIENT']['VERSION'],		// Client version (double value)
						'client_os' => $GLOBALS['CLIENT']['SYSTEM'],			// Client Operating system (win, mac, unix)
						'parsetime' => intval($this->scriptParseTime),			// Parsetime for the page.
						'flags' => $flags,										// Flags: Is be user logged in? Is page cached?
						'IP' => t3lib_div::getIndpEnv('REMOTE_ADDR'),			// Remote IP address
						'host' => t3lib_div::getIndpEnv('REMOTE_HOST'),			// Remote Host Address
						'referer' => $refUrl,									// Referer URL
						'browser' => t3lib_div::getIndpEnv('HTTP_USER_AGENT'),	// User Agent Info.
						'tstamp' => $GLOBALS['EXEC_TIME']						// Time stamp
					);

						// Hook for preprocessing the list of fields to insert into sys_stat:
					if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sys_stat-PreProcClass']) && is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sys_stat-PreProcClass'])) {
						foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sys_stat-PreProcClass'] as $_classRef)    {
							$_procObj = t3lib_div::getUserObj($_classRef);
							$insertFields = $_procObj->sysstat_preProcessFields($insertFields,$this);
						}
					}


					$GLOBALS['TT']->push('Store SQL');
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_stat', $insertFields);
					$GLOBALS['TT']->pull();
				}

					// Apache:
				if (!empty($this->config['config']['stat_apache']) && !empty($this->config['stat_vars']['pageName'])) {
					if (@is_file($this->config['stat_vars']['logFile'])) {
							// Build a log line (format is derived from the NCSA extended/combined log format)
							// Log part 1: Remote hostname / address
						$LogLine = (t3lib_div::getIndpEnv('REMOTE_HOST') && empty($this->config['config']['stat_apache_noHost'])) ? t3lib_div::getIndpEnv('REMOTE_HOST') : t3lib_div::getIndpEnv('REMOTE_ADDR');
							// Log part 2: Fake the remote logname
						$LogLine.= ' -';
							// Log part 3: Remote username
						$LogLine.= ' '.($this->loginUser ? $this->fe_user->user['username'] : '-');
							// Log part 4: Time
						$LogLine.= ' '.date('[d/M/Y:H:i:s +0000]',$GLOBALS['EXEC_TIME']);
							// Log part 5: First line of request (the request filename)
						$LogLine.= ' "GET '.$this->config['stat_vars']['pageName'].' HTTP/1.1"';
							// Log part 6: Status and content length (ignores special content like admin panel!)
						$LogLine.= ' 200 '.strlen($this->content);

						if (empty($this->config['config']['stat_apache_notExtended'])) {
							$referer = t3lib_div::getIndpEnv('HTTP_REFERER');
							$LogLine.= ' "'.($referer ? $referer : '-').'" "'.t3lib_div::getIndpEnv('HTTP_USER_AGENT').'"';
						}

						$GLOBALS['TT']->push('Write to log file (fputs)');
							$logfilehandle = fopen($this->config['stat_vars']['logFile'], 'a');
							fputs($logfilehandle, $LogLine.LF);
							@fclose($logfilehandle);
						$GLOBALS['TT']->pull();

						$GLOBALS['TT']->setTSlogMessage('Writing to logfile: OK',0);
					} else {
						$GLOBALS['TT']->setTSlogMessage('Writing to logfile: Error - logFile did not exist!',3);
					}
				}
			$GLOBALS['TT']->pull();
		}
	}

	/**
	 * Outputs preview info.
	 *
	 * @return	void
	 */
	function previewInfo()	{
		if ($this->fePreview !== 0) {
			$previewInfo = '';
			if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo']) && is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo'])) {
				$_params = array('pObj' => &$this);
				foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo'] as $_funcRef) {
					$previewInfo .= t3lib_div::callUserFunction($_funcRef,$_params,$this);
				}
			}
			$this->content = str_ireplace('</body>',  $previewInfo . '</body>', $this->content);
		}
	}

	/**
	 * End-Of-Frontend hook
	 *
	 * @return	void
	 */
	function hook_eofe()	{

			// Call hook for end-of-frontend processing:
		if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']) && is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe'])) {
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}
	}

	/**
	 * Returns a link to the BE login screen with redirect to the front-end
	 *
	 * @return	string		HTML, a tag for a link to the backend.
	 */
	function beLoginLinkIPList()	{
		if (!empty($this->config['config']['beLoginLinkIPList'])) {
			if (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $this->config['config']['beLoginLinkIPList']))	{
				$label = !$this->beUserLogin ? $this->config['config']['beLoginLinkIPList_login'] : $this->config['config']['beLoginLinkIPList_logout'];
				if ($label)	{
					if (!$this->beUserLogin)	{
						$link = '<a href="'.htmlspecialchars(TYPO3_mainDir.'index.php?redirect_url='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.$label.'</a>';
					} else {
						$link = '<a href="'.htmlspecialchars(TYPO3_mainDir.'index.php?L=OUT&redirect_url='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.$label.'</a>';
					}
					return $link;
				}
			}
		}
	}

	/**
	 * Sends HTTP headers for temporary content. These headers prevent search engines from caching temporary content and asks them to revisit this page again.
	 *
	 * @return	void
	 */
	function addTempContentHttpHeaders() {
		header('HTTP/1.0 503 Service unavailable');
		header('Retry-after: 3600');
		header('Pragma: no-cache');
		header('Cache-control: no-cache');
		header('Expire: 0');
	}





















	/********************************************
	 *
	 * Various internal API functions
	 *
	 *******************************************/


	/**
	 * Make simulation filename (without the ".html" ending, only body of filename)
	 *
	 * @param	string		The page title to use
	 * @param	mixed		The page id (integer) or alias (string)
	 * @param	integer		The type number
	 * @param	string		Query-parameters to encode (will be done only if caching is enabled and TypoScript configured for it. I don't know it this makes much sense in fact...)
	 * @param	boolean		The "no_cache" status of the link.
	 * @return	string		The body of the filename.
	 * @see getSimulFileName(), t3lib_tstemplate::linkData(), tslib_frameset::frameParams()
	 * @deprecated since TYPO3 4.3, will be removed in TYPO3 4.6, please use the "simulatestatic" sysext directly
	 * @todo	Deprecated but still used in the Core!
	 */
	function makeSimulFileName($inTitle, $page, $type, $addParams = '', $no_cache = false) {
		t3lib_div::logDeprecatedFunction();

		if (t3lib_extMgm::isLoaded('simulatestatic')) {
			$parameters = array(
				'inTitle' => $inTitle,
				'page' => $page,
				'type' => $type,
				'addParams' => $addParams,
				'no_cache' => $no_cache,
			);
			return t3lib_div::callUserFunction(
				'EXT:simulatestatic/class.tx_simulatestatic.php:&tx_simulatestatic->makeSimulatedFileNameCompat',
				$parameters,
				$this
			);
		} else {
			return false;
		}
	}

	/**
	 * Processes a query-string with GET-parameters and returns two strings, one with the parameters that CAN be encoded and one array with those which can't be encoded (encoded by the M5 or B6 methods)
	 *
	 * @param	string		Query string to analyse
	 * @return	array		Two num keys returned, first is the parameters that MAY be encoded, second is the non-encodable parameters.
	 * @see makeSimulFileName(), t3lib_tstemplate::linkData()
	 * @deprecated since TYPO3 4.3, will be removed in TYPO3 4.6, please use the "simulatestatic" sysext directly
	 */
	function simulateStaticDocuments_pEnc_onlyP_proc($linkVars)	{
		t3lib_div::logDeprecatedFunction();

		if (t3lib_extMgm::isLoaded('simulatestatic')) {
			return t3lib_div::callUserFunction(
				'EXT:simulatestatic/class.tx_simulatestatic.php:&tx_simulatestatic->processEncodedQueryString',
				$linkVars,
				$this
			);
		} else {
			return false;
		}
	}

	/**
	 * Returns the simulated static file name (*.html) for the current page (using the page record in $this->page)
	 *
	 * @return	string		The filename (without path)
	 * @see makeSimulFileName(), publish.php
	 * @deprecated since TYPO3 4.3, will be removed in TYPO3 4.6, please use the "simulatestatic" sysext directly
	 * @todo	Deprecated but still used in the Core!
	 */
	function getSimulFileName()	{
		t3lib_div::logDeprecatedFunction();

		return $this->makeSimulFileName(
			$this->page['title'],
			($this->page['alias'] ? $this->page['alias'] : $this->id),
			$this->type
		) . '.html';
	}

	/**
	 * Checks and sets replacement character for simulateStaticDocuments. Default is underscore.
	 *
	 * @return	void
	 * @deprecated since TYPO3 4.3, will be removed in TYPO3 4.6, please use the "simulatestatic" sysext directly
	 */
	function setSimulReplacementChar() {
		t3lib_div::logDeprecatedFunction();

		$replacement = $defChar = t3lib_div::compat_version('4.0') ? '-' : '_';
		if (isset($this->config['config']['simulateStaticDocuments_replacementChar'])) {
			$replacement = trim($this->config['config']['simulateStaticDocuments_replacementChar']);
			if (urlencode($replacement) != $replacement) {
					// Invalid character
				$replacement = $defChar;
			}
		}
		$this->config['config']['simulateStaticDocuments_replacementChar'] = $replacement;
	}

	/**
	 * Converts input string to an ASCII based file name prefix
	 *
	 * @param	string		String to base output on
	 * @param	integer		Number of characters in the string
	 * @param	string		Character to put in the end of string to merge it with the next value.
	 * @return	string		String
	 * @deprecated since TYPO3, 4.3, will be removed in TYPO3 4.6, please use the "simulatestatic" sysext directly
	 * @todo	Deprecated but still used in the Core!
	 */
	function fileNameASCIIPrefix($inTitle,$titleChars,$mergeChar='.')	{
		t3lib_div::logDeprecatedFunction();
		$out = $this->csConvObj->specCharsToASCII($this->renderCharset, $inTitle);
			// Get replacement character
		$replacementChar = $this->config['config']['simulateStaticDocuments_replacementChar'];
		$replacementChars = '_\-' . ($replacementChar != '_' && $replacementChar != '-' ? $replacementChar : '');
		$out = preg_replace('/[^A-Za-z0-9_-]/', $replacementChar, trim(substr($out, 0, $titleChars)));
		$out = preg_replace('/([' . $replacementChars . ']){2,}/', '\1', $out);
		$out = preg_replace('/[' . $replacementChars . ']?$/', '', $out);
		$out = preg_replace('/^[' . $replacementChars . ']?/', '', $out);
		if (strlen($out)) {
			$out.= $mergeChar;
		}

		return $out;
	}

	/**
	 * Encryption (or decryption) of a single character.
	 * Within the given range the character is shifted with the supplied offset.
	 *
	 * @param	int		Ordinal of input character
	 * @param	int		Start of range
	 * @param	int		End of range
	 * @param	int		Offset
	 * @return	string		encoded/decoded version of character
	 */
	function encryptCharcode($n,$start,$end,$offset)	{
		$n = $n + $offset;
		if ($offset > 0 && $n > $end)	{
			$n = $start + ($n - $end - 1);
		} else if ($offset < 0 && $n < $start)	{
			$n = $end - ($start - $n - 1);
		}
		return chr($n);
	}

	/**
	 * Encryption of email addresses for <A>-tags See the spam protection setup in TS 'config.'
	 *
	 * @param	string		Input string to en/decode: "mailto:blabla@bla.com"
	 * @param	boolean		If set, the process is reversed, effectively decoding, not encoding.
	 * @return	string		encoded/decoded version of $string
	 */
	function encryptEmail($string,$back=0)	{
		$out = '';

		if ($this->spamProtectEmailAddresses === 'ascii') {
			for ($a=0; $a<strlen($string); $a++) {
				$out .= '&#'.ord(substr($string, $a, 1)).';';
			}
		} else	{
				// like str_rot13() but with a variable offset and a wider character range
			$len = strlen($string);
			$offset = intval($this->spamProtectEmailAddresses)*($back?-1:1);
			for ($i=0; $i<$len; $i++)	{
				$charValue = ord($string{$i});
				if ($charValue >= 0x2B && $charValue <= 0x3A)	{	// 0-9 . , - + / :
					$out .= $this->encryptCharcode($charValue,0x2B,0x3A,$offset);
				} elseif ($charValue >= 0x40 && $charValue <= 0x5A)	{	// A-Z @
					$out .= $this->encryptCharcode($charValue,0x40,0x5A,$offset);
				} else if ($charValue >= 0x61 && $charValue <= 0x7A)	{	// a-z
					$out .= $this->encryptCharcode($charValue,0x61,0x7A,$offset);
				} else {
					$out .= $string{$i};
				}
			}
		}
		return $out;
	}

	/**
	 * En/decodes strings with lightweight encryption and a hash containing the server encryptionKey (salt)
	 * Can be used for authentication of information sent from server generated pages back to the server to establish that the server generated the page. (Like hidden fields with recipient mail addresses)
	 * Encryption is mainly to avoid spam-bots to pick up information.
	 *
	 * @param	string		Input string to en/decode
	 * @param	boolean		If set, string is decoded, not encoded.
	 * @return	string		encoded/decoded version of $string
	 */
	function codeString($string, $decode=FALSE)	{

		if ($decode)	{
			list($md5Hash, $str) = explode(':',$string,2);
			$newHash = substr(md5($this->TYPO3_CONF_VARS['SYS']['encryptionKey'].':'.$str),0,10);
			if (!strcmp($md5Hash, $newHash))	{
				$str = base64_decode($str);
				$str = $this->roundTripCryptString($str);
				return $str;
			} else return FALSE;	// Decoding check failed! Original string not produced by this server!
		} else {
			$str = $string;
			$str = $this->roundTripCryptString($str);
			$str = base64_encode($str);
			$newHash = substr(md5($this->TYPO3_CONF_VARS['SYS']['encryptionKey'].':'.$str),0,10);
			return $newHash.':'.$str;
		}
	}

	/**
	 * Encrypts a strings by XOR'ing all characters with a key derived from the
	 * TYPO3 encryption key.
	 *
	 * Using XOR means that the string can be decrypted by simply calling the
	 * function again - just like rot-13 works (but in this case for ANY byte
	 * value).
	 *
	 * @param string $string string to crypt, may be empty
	 *
	 * @return string binary crypt string, will have the same length as $string
	 */
	protected function roundTripCryptString($string) {
		$out = '';

		$cleartextLength = strlen($string);
		$key = sha1($this->TYPO3_CONF_VARS['SYS']['encryptionKey']);
		$keyLength = strlen($key);

		for ($a = 0; $a < $cleartextLength; $a++) {
			$xorVal = ord($key{($a % $keyLength)});
			$out .= chr(ord($string{$a}) ^ $xorVal);
		}

		return $out;
	}

	/**
	 * Checks if a PHPfile may be included.
	 *
	 * @param	string		Relative path to php file
	 * @return	boolean		Returns true if $GLOBALS['TYPO3_CONF_VARS']['FE']['noPHPscriptInclude'] is not set OR if the file requested for inclusion is found in one of the allowed paths.
	 * @see tslib_cObj::PHP_SCRIPT(), tslib_feTCE::includeScripts(), tslib_menu::includeMakeMenu()
	 */
	function checkFileInclude($incFile)	{
		return !$this->TYPO3_CONF_VARS['FE']['noPHPscriptInclude']
			|| substr($incFile,0,14)=='media/scripts/'
			|| substr($incFile,0,4+strlen(TYPO3_mainDir))==TYPO3_mainDir.'ext/'
			|| substr($incFile,0,7+strlen(TYPO3_mainDir))==TYPO3_mainDir.'sysext/'
			|| substr($incFile,0,14)=='typo3conf/ext/';
	}

	/**
	 * Creates an instance of tslib_cObj in $this->cObj
	 * This instance is used to start the rendering of the TypoScript template structure
	 *
	 * @return	void
	 * @see pagegen.php
	 */
	function newCObj()	{
		$this->cObj =t3lib_div::makeInstance('tslib_cObj');
		$this->cObj->start($this->page,'pages');
	}

	/**
	 * Converts relative paths in the HTML source to absolute paths for fileadmin/, typo3conf/ext/ and media/ folders.
	 *
	 * @return	void
	 * @access private
	 * @see pagegen.php, INTincScript()
	 */
	function setAbsRefPrefix()	{
		if ($this->absRefPrefix)	{
			$this->content = str_replace('"media/', '"'.t3lib_extMgm::siteRelPath('cms').'tslib/media/', $this->content);
			$this->content = str_replace('"typo3conf/ext/', '"'.$this->absRefPrefix.'typo3conf/ext/', $this->content);
			$this->content = str_replace('"' . TYPO3_mainDir . 'contrib/', '"' . $this->absRefPrefix . TYPO3_mainDir . 'contrib/', $this->content);
			$this->content = str_replace('"' . TYPO3_mainDir . 'ext/', '"' . $this->absRefPrefix . TYPO3_mainDir . 'ext/', $this->content);
			$this->content = str_replace('"' . TYPO3_mainDir . 'sysext/' , '"' . $this->absRefPrefix . TYPO3_mainDir . 'sysext/', $this->content);
			$this->content = str_replace('"'.$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '"'.$this->absRefPrefix.$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], $this->content);
			$this->content = str_replace('"' . $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'], '"' . $this->absRefPrefix . $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'], $this->content);
			// Process additional directories
			$directories = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'], true);
			foreach ($directories as $directory) {
				$this->content = str_replace('"' . $directory, '"' . $this->absRefPrefix . $directory, $this->content);
			}
		}
	}

	/**
	 * Prefixing the input URL with ->baseUrl If ->baseUrl is set and the input url is not absolute in some way.
	 * Designed as a wrapper functions for use with all frontend links that are processed by JavaScript (for "realurl" compatibility!). So each time a URL goes into window.open, window.location.href or otherwise, wrap it with this function!
	 *
	 * @param	string		Input URL, relative or absolute
	 * @return	string		Processed input value.
	 */
	function baseUrlWrap($url)	{
		if ($this->baseUrl)	{
			$urlParts = parse_url($url);
			if (!strlen($urlParts['scheme']) && $url{0}!=='/')	{
				$url = $this->baseUrl.$url;
			}
		}
		return $url;
	}

	/**
	 * Prints error msg/header.
	 * Echoes out the HTML content
	 *
	 * @param	string		Message string
	 * @param	string		Header string
	 * @return	void
	 * @see t3lib_timeTrack::debug_typo3PrintError()
	 * @see	t3lib_message_ErrorPageMessage
	 * @deprecated since TYPO3 4.5, will be removed in TYPO3 4.7
	 */
	function printError($label,$header='Error!') {
		t3lib_div::logDeprecatedFunction();
		t3lib_timeTrack::debug_typo3PrintError($header, $label, 0, t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
	}

	/**
	 * Logs access to deprecated TypoScript objects and properties.
	 *
	 * Dumps message to the TypoScript message log (admin panel) and the TYPO3 deprecation log.
	 * Note: The second parameter was introduced in TYPO3 4.5 and is not available in older versions
	 *
	 * @param	string		Deprecated object or property
	 * @param	string		Message or additional information
	 * @return	void
	 * @see t3lib_div::deprecationLog(), t3lib_timeTrack::setTSlogMessage()
	 */
	function logDeprecatedTyposcript($typoScriptProperty, $explanation = '') {
		$explanationText = (strlen($explanation) ? ' - ' . $explanation : '');
		$GLOBALS['TT']->setTSlogMessage($typoScriptProperty . ' is deprecated.' . $explanationText, 2);
		t3lib_div::deprecationLog('TypoScript ' . $typoScriptProperty . ' is deprecated' . $explanationText);
	}

	/**
	 * Updates the tstamp field of a cache_md5params record to the current time.
	 *
	 * @param	string		The hash string identifying the cache_md5params record for which to update the "tstamp" field to the current time.
	 * @return	void
	 * @access private
	 */
	function updateMD5paramsRecord($hash)	{
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'cache_md5params',
			'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, 'cache_md5params'), array('tstamp' => $GLOBALS['EXEC_TIME'])
		);
	}

	/**
	 * Pass the content through tidy - a little program that cleans up HTML-code.
	 * Requires $this->TYPO3_CONF_VARS['FE']['tidy'] to be true and $this->TYPO3_CONF_VARS['FE']['tidy_path'] to contain the filename/path of tidy including clean-up arguments for tidy. See default value in TYPO3_CONF_VARS in t3lib/config_default.php
	 *
	 * @param	string		The page content to clean up. Will be written to a temporary file which "tidy" is then asked to clean up. File content is read back and returned.
	 * @return	string		Returns the
	 */
	function tidyHTML($content)		{
		if ($this->TYPO3_CONF_VARS['FE']['tidy'] && $this->TYPO3_CONF_VARS['FE']['tidy_path'])	{
			$oldContent = $content;
			$fname = t3lib_div::tempnam('typo3_tidydoc_');		// Create temporary name
			@unlink ($fname);	// Delete if exists, just to be safe.
			$fp = fopen ($fname,'wb');	// Open for writing
			fputs ($fp, $content);	// Put $content
			@fclose ($fp);	// Close

			exec ($this->TYPO3_CONF_VARS['FE']['tidy_path'].' '.$fname, $output);			// run the $content through 'tidy', which formats the HTML to nice code.
			@unlink ($fname);	// Delete the tempfile again
			$content = implode(LF,$output);
			if (!trim($content))	{
				$content = $oldContent;	// Restore old content due empty return value.
				$GLOBALS['TT']->setTSlogMessage('"tidy" returned an empty value!',2);
			}
			$GLOBALS['TT']->setTSlogMessage('"tidy" content lenght: '.strlen($content),0);
		}
		return $content;
	}

	/**
	 * Substitutes all occurencies of <a href="#"... in $this->content with <a href="[path-to-url]#"...
	 *
	 * @return	void		Works directly on $this->content
	 */
	function prefixLocalAnchorsWithScript()	{
		$scriptPath = $GLOBALS['TSFE']->absRefPrefix . substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),strlen(t3lib_div::getIndpEnv('TYPO3_SITE_URL')));
		$originalContent = $this->content;
		$this->content = preg_replace('/(<(?:a|area).*?href=")(#[^"]*")/i', '${1}' . htmlspecialchars($scriptPath) . '${2}', $originalContent);
			// There was an error in the call to preg_replace, so keep the original content (behavior prior to PHP 5.2)
		if (preg_last_error() > 0) {
			t3lib_div::sysLog('preg_replace returned error-code: ' . preg_last_error().' in function prefixLocalAnchorsWithScript. Replacement not done!' , 'cms', 4);
			$this->content = $originalContent;
		}
	}

	/**
	 * Initialize workspace preview
	 *
	 * @return	void
	 */
	function workspacePreviewInit()	{
		$previewWS = t3lib_div::_GP('ADMCMD_previewWS');
		if ($this->beUserLogin && is_object($GLOBALS['BE_USER']) && t3lib_div::testInt($previewWS))	{
			if ($previewWS==0 || ($previewWS>=-1 && $GLOBALS['BE_USER']->checkWorkspace($previewWS))) {	// Check Access to workspace. Live (0) is OK to preview for all.
				$this->workspacePreview = intval($previewWS);
			} else {
				$this->workspacePreview = -99;	// No preview, will default to "Live" at the moment
			}
		}
	}

	/**
	 * Returns true if workspace preview is enabled
	 *
	 * @return	boolean		Returns true if workspace preview is enabled
	 */
	function doWorkspacePreview()	{
		return (string)$this->workspacePreview!=='';
	}

	/**
	 * Returns the name of the workspace
	 *
	 * @param	boolean		If set, returns title of current workspace being previewed
	 * @return	mixed		If $returnTitle is set, returns string (title), otherwise workspace integer for which workspace is being preview. False if none.
	 */
	function whichWorkspace($returnTitle = FALSE)	{
		if ($this->doWorkspacePreview())	{
			$ws = intval($this->workspacePreview);
		} elseif ($this->beUserLogin) {
			$ws = $GLOBALS['BE_USER']->workspace;
		} else return FALSE;

		if ($returnTitle)	{
			if ($ws===-1)	{
				return 'Default Draft Workspace';
			} elseif (t3lib_extMgm::isLoaded('workspaces')) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title', 'sys_workspace', 'uid='.intval($ws));
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					return $row['title'];
				}
			}
		} else {
			return $ws;
		}
	}

	/**
	 * Includes a comma-separated list of library files by PHP function include_once.
	 *
	 * @param	array		$libraries: The libraries to be included.
	 * @return	void
	 */
	public function includeLibraries(array $libraries) {
		global $TYPO3_CONF_VARS;

		$GLOBALS['TT']->push('Include libraries');
		$GLOBALS['TT']->setTSlogMessage('Files for inclusion: "' . implode(', ', $libraries) . '"');

		foreach ($libraries as $library) {
			$file = $GLOBALS['TSFE']->tmpl->getFileName($library);
			if ($file) {
				include_once('./' . $file);
			} else {
				$GLOBALS['TT']->setTSlogMessage('Include file "' . $file . '" did not exist!', 2);
			}
		}

		$GLOBALS['TT']->pull();
	}



























	/********************************************
	 *
	 * Various external API functions - for use in plugins etc.
	 *
	 *******************************************/


	/**
	 * Traverses the ->rootLine and returns an array with the first occurrance of storage pid and siteroot pid
	 *
	 * @return	array		Array with keys '_STORAGE_PID' and '_SITEROOT' set to the first occurances found.
	 */
	function getStorageSiterootPids()	{
		$res=array();

		if(!is_array($this->rootLine)) {
			return array();
		}

		foreach ($this->rootLine as $rC) {
			if (!$res['_STORAGE_PID'])	$res['_STORAGE_PID']=intval($rC['storage_pid']);
			if (!$res['_SITEROOT'])	$res['_SITEROOT']=$rC['is_siteroot']?intval($rC['uid']):0;
		}
		return $res;
	}

	/**
	 * Returns the pages TSconfig array based on the currect ->rootLine
	 *
	 * @return	array
	 */
	function getPagesTSconfig()	{
		if (!is_array($this->pagesTSconfig))	{
			$TSdataArray = array();
			$TSdataArray[] = $this->TYPO3_CONF_VARS['BE']['defaultPageTSconfig'];	// Setting default configuration:
			foreach ($this->rootLine as $k => $v) {
				$TSdataArray[]=$v['TSconfig'];
			}
				// Parsing the user TS (or getting from cache)
			$TSdataArray = t3lib_TSparser::checkIncludeLines_array($TSdataArray);
			$userTS = implode(LF.'[GLOBAL]'.LF,$TSdataArray);
			$hash = md5('pageTS:'.$userTS);
			$cachedContent = $this->sys_page->getHash($hash);
			if (isset($cachedContent))	{
				$this->pagesTSconfig = unserialize($cachedContent);
			} else {
				$parseObj = t3lib_div::makeInstance('t3lib_TSparser');
				$parseObj->parse($userTS);
				$this->pagesTSconfig = $parseObj->setup;
				$this->sys_page->storeHash($hash,serialize($this->pagesTSconfig),'PAGES_TSconfig');
			}
		}
		return $this->pagesTSconfig;
	}

	/**
	 * Sets JavaScript code in the additionalJavaScript array
	 *
	 * @param	string		$key is the key in the array, for num-key let the value be empty. Note reserved keys 'openPic' and 'mouseOver'
	 * @param	string		$content is the content if you want any
	 * @return	void
	 * @see tslib_gmenu::writeMenu(), tslib_cObj::imageLinkWrap()
	 */
	function setJS($key,$content='')	{
		if ($key)	{
			switch($key)	{
				case 'mouseOver':
					$this->additionalJavaScript[$key]=
'		// JS function for mouse-over
	function over(name,imgObj)	{	//
		if (version == "n3" && document[name]) {document[name].src = eval(name+"_h.src");}
		else if (document.getElementById && document.getElementById(name)) {document.getElementById(name).src = eval(name+"_h.src");}
		else if (imgObj)	{imgObj.src = eval(name+"_h.src");}
	}
		// JS function for mouse-out
	function out(name,imgObj)	{	//
		if (version == "n3" && document[name]) {document[name].src = eval(name+"_n.src");}
		else if (document.getElementById && document.getElementById(name)) {document.getElementById(name).src = eval(name+"_n.src");}
		else if (imgObj)	{imgObj.src = eval(name+"_n.src");}
	}';
				break;
				case 'openPic':
					$this->additionalJavaScript[$key]=
'	function openPic(url,winName,winParams)	{	//
		var theWindow = window.open(url,winName,winParams);
		if (theWindow)	{theWindow.focus();}
	}';
				break;
				default:
					$this->additionalJavaScript[$key]=$content;
				break;
			}
		}
	}

	/**
	 * Sets CSS data in the additionalCSS array
	 *
	 * @param	string		$key is the key in the array, for num-key let the value be empty
	 * @param	string		$content is the content if you want any
	 * @return	void
	 * @see setJS()
	 */
	function setCSS($key,$content)	{
		if ($key)	{
			switch($key)	{
				default:
					$this->additionalCSS[$key]=$content;
				break;
			}
		}
	}

	/**
	 * Returns a unique md5 hash.
	 * There is no special magic in this, the only point is that you don't have to call md5(uniqid()) which is slow and by this you are sure to get a unique string each time in a little faster way.
	 *
	 * @param	string		Some string to include in what is hashed. Not significant at all.
	 * @return	string		MD5 hash of ->uniqueString, input string and uniqueCounter
	 */
	function uniqueHash($str='')	{
		return md5($this->uniqueString.'_'.$str.$this->uniqueCounter++);
	}

	/**
	 * Sets the cache-flag to 1. Could be called from user-included php-files in order to ensure that a page is not cached.
	 *
	 * @param	string		$reason: An optional reason to be written to the syslog.
	 *						If not set, debug_backtrace() will be used to track down the call.
	 * @return	void
	 */
	function set_no_cache($reason = '') {
		if (strlen($reason)) {
			$warning = '$TSFE->set_no_cache() was triggered. Reason: ' . $reason . '.';
		} else {
			$trace = debug_backtrace();
				// This is a hack to work around ___FILE___ resolving symbolic links
			$PATH_site_real = str_replace('t3lib','',realpath(PATH_site.'t3lib'));
			$file = $trace[0]['file'];
			if (substr($file,0,strlen($PATH_site_real))===$PATH_site_real) {
				$file = str_replace($PATH_site_real,'',$file);
			} else {
				$file = str_replace(PATH_site,'',$file);
			}
			$line = $trace[0]['line'];
			$trigger = $file.' on line '.$line;

			$warning = '$TSFE->set_no_cache() was triggered by ' . $trigger.'.';
		}

		if ($this->TYPO3_CONF_VARS['FE']['disableNoCacheParameter']) {
			$warning.= ' However, $TYPO3_CONF_VARS[\'FE\'][\'disableNoCacheParameter\'] is set, so it will be ignored!';
			$GLOBALS['TT']->setTSlogMessage($warning,2);
		} else {
			$warning.= ' Caching is disabled!';
			$this->disableCache();
		}

		t3lib_div::sysLog($warning, 'cms', 2);
	}

	/**
	 * Disables caching of the current page.
	 *
	 * @return void
	 * @internal
	 */
	protected function disableCache() {
		$this->no_cache = 1;
	}

	/**
	 * Sets the cache-timeout in seconds
	 *
	 * @param	integer		cache-timeout in seconds
	 * @return	void
	 */
	function set_cache_timeout_default($seconds)	{
		$this->cacheTimeOutDefault = intval($seconds);
	}

	/**
	 * Get the cache timeout for the current page.
	 *
	 * @return	integer		The cache timeout for the current page.
	 */
	function get_cache_timeout() {
			// Cache period was set for the page:
		if ($this->page['cache_timeout']) {
			$cacheTimeout = intval($this->page['cache_timeout']);
			// Cache period was set for the whole site:
		} elseif ($this->cacheTimeOutDefault) {
			$cacheTimeout = $this->cacheTimeOutDefault;
			// No cache period set at all, so we take one day (60*60*24 seconds = 86400 seconds):
		} else {
			$cacheTimeout = 86400;
		}
		return $cacheTimeout;
	}

	/**
	 * Substitute function for the PHP mail() function.
	 * It will encode the email with the setting of TS 'config.notification_email_encoding' (base64 or none)
	 * It will also find all links to http:// in the text and substitute with a shorter link using the redirect feature which stores the long link in the database. Depends on configuration in TS 'config.notification_email_urlmode'
	 *
	 * @param	string		recipient email address (or list of)
	 * @param	string		The subject
	 * @param	string		The message
	 * @param	string		The headers (string with lines)
	 * @return	void
	 * @see t3lib_div::plainMailEncoded()
	 */
	function plainMailEncoded($email,$subject,$message,$headers='')	{
		$urlmode = $this->config['config']['notification_email_urlmode'];	// '76', 'all', ''

		if ($urlmode)	{
			$message = t3lib_div::substUrlsInPlainText($message,$urlmode);
		}

		$encoding = $this->config['config']['notification_email_encoding'] ? $this->config['config']['notification_email_encoding'] : '';
		$charset = $this->renderCharset;

		$convCharset = FALSE;	// do we need to convert mail data?
		if ($this->config['config']['notification_email_charset'])	{	// Respect config.notification_email_charset if it was set
			$charset = $this->csConvObj->parse_charset($this->config['config']['notification_email_charset']);
			if ($charset != $this->renderCharset)	{
				$convCharset = TRUE;
			}

		} elseif ($this->metaCharset != $this->renderCharset)	{	// Use metaCharset for mail if different from renderCharset
			$charset = $this->metaCharset;
			$convCharset = TRUE;
		}

		if ($convCharset)	{
			$email = $this->csConvObj->conv($email,$this->renderCharset,$charset);
			$subject = $this->csConvObj->conv($subject,$this->renderCharset,$charset);
			$message = $this->csConvObj->conv($message,$this->renderCharset,$charset);
			$headers = $this->csConvObj->conv($headers,$this->renderCharset,$charset);
		}

		t3lib_div::plainMailEncoded(
			$email,
			$subject,
			$message,
			$headers,
			$encoding,
			$charset
		);
	}













	/*********************************************
	 *
	 * Localization and character set conversion
	 *
	 *********************************************/

	/**
	 * Split Label function for front-end applications.
	 *
	 * @param	string		Key string. Accepts the "LLL:" prefix.
	 * @return	string		Label value, if any.
	 */
	function sL($input)	{
		if (strcmp(substr($input,0,4),'LLL:'))	{
			$t = explode('|',$input);
			return $t[$this->langSplitIndex] ? $t[$this->langSplitIndex] : $t[0];
		} else {
			if (!isset($this->LL_labels_cache[$this->lang][$input])) {	// If cached label
				$restStr = trim(substr($input,4));
				$extPrfx='';
				if (!strcmp(substr($restStr,0,4),'EXT:'))	{
					$restStr = trim(substr($restStr,4));
					$extPrfx='EXT:';
				}
				$parts = explode(':',$restStr);
				$parts[0] = $extPrfx.$parts[0];
				if (!isset($this->LL_files_cache[$parts[0]]))	{	// Getting data if not cached
					$this->LL_files_cache[$parts[0]] = $this->readLLfile($parts[0]);
				}
				$this->LL_labels_cache[$this->lang][$input] = $this->getLLL($parts[1],$this->LL_files_cache[$parts[0]]);
			}
			return $this->LL_labels_cache[$this->lang][$input];
		}
	}

	/**
	 * Read locallang files - for frontend applications
	 *
	 * @param	string		Reference to a relative filename to include.
	 * @return	array		Returns the $LOCAL_LANG array found in the file. If no array found, returns empty array.
	 */
	function readLLfile($fileRef)	{
		return t3lib_div::readLLfile($fileRef, $this->lang, $this->renderCharset);
	}

	/**
	 * Returns 'locallang' label - may need initializing by initLLvars
	 *
	 * @param	string		Local_lang key for which to return label (language is determined by $this->lang)
	 * @param	array		The locallang array in which to search
	 * @return	string		Label value of $index key.
	 */
	function getLLL($index, &$LOCAL_LANG)	{
		if (strcmp($LOCAL_LANG[$this->lang][$index],''))	{
			return $LOCAL_LANG[$this->lang][$index];
		} else {
			return $LOCAL_LANG['default'][$index];
		}
	}

	/**
	 * Initializing the getLL variables needed.
	 * Sets $this->langSplitIndex based on $this->config['config']['language']
	 *
	 * @return	void
	 */
	function initLLvars()	{

			// Setting language key and split index:
		$this->lang = $this->config['config']['language'] ? $this->config['config']['language'] : 'default';
		$this->getPageRenderer()->setLanguage($this->lang);

		$ls = explode('|',TYPO3_languages);
		foreach ($ls as $i => $v) {
			if ($v==$this->lang)	{$this->langSplitIndex=$i; break;}
		}

			// Setting charsets:
		$this->renderCharset = $this->csConvObj->parse_charset($this->config['config']['renderCharset'] ? $this->config['config']['renderCharset'] : ($this->TYPO3_CONF_VARS['BE']['forceCharset'] ? $this->TYPO3_CONF_VARS['BE']['forceCharset'] : $this->defaultCharSet));	// Rendering charset of HTML page.
		$this->metaCharset = $this->csConvObj->parse_charset($this->config['config']['metaCharset'] ? $this->config['config']['metaCharset'] : $this->renderCharset);	// Output charset of HTML page.
	}

	/**
	 * Converts the charset of the input string if applicable.
	 * The "to" charset is determined by the currently used charset for the page which is "iso-8859-1" by default or set by $GLOBALS['TSFE']->config['config']['renderCharset']
	 * Only if there is a difference between the two charsets will a conversion be made
	 * The conversion is done real-time - no caching for performance at this point!
	 *
	 * @param	string		String to convert charset for
	 * @param	string		Optional "from" charset.
	 * @return	string		Output string, converted if needed.
	 * @see t3lib_cs
	 */
	function csConv($str,$from='')	{
		if ($from)	{
			$output = $this->csConvObj->conv($str,$this->csConvObj->parse_charset($from),$this->renderCharset,1);
			return $output ? $output : $str;
		} else {
			return $str;
		}
	}

	/**
	 * Converts input string from renderCharset to metaCharset IF the two charsets are different.
	 *
	 * @param	string		Content to be converted.
	 * @param	string		Label (just for fun, no function)
	 * @return	string		Converted content string.
	 */
	function convOutputCharset($content,$label='')	{
		if ($this->renderCharset != $this->metaCharset)	{
			$content = $this->csConvObj->conv($content,$this->renderCharset,$this->metaCharset,TRUE);
		}

		return $content;
	}

	/**
	 * Converts the $_POST array from metaCharset (page HTML charset from input form) to renderCharset (internal processing) IF the two charsets are different.
	 *
	 * @return	void
	 */
	function convPOSTCharset()	{
		if ($this->renderCharset != $this->metaCharset && is_array($_POST) && count($_POST))	{
			$this->csConvObj->convArray($_POST,$this->metaCharset,$this->renderCharset);
			$GLOBALS['HTTP_POST_VARS'] = $_POST;
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_fe.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_fe.php']);
}

?>
