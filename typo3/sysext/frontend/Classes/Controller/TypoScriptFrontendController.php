<?php
namespace TYPO3\CMS\Frontend\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Class for the built TypoScript based Front End. Instantiated in
 * index_ts.php script as the global object TSFE
 * Main frontend class, instantiated in the index_ts.php script as the global object TSFE
 *
 * This class has a lot of functions and internal variable which are use from index_ts.php.
 * The class is instantiated as $GLOBALS['TSFE'] in index_ts.php.
 * The use of this class should be inspired by the order of function calls as found in index_ts.php.
 *
 * Revised for TYPO3 3.6 June/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TypoScriptFrontendController {

	// CURRENT PAGE:
	// The page id (int)
	/**
	 * @todo Define visibility
	 */
	public $id = '';

	// RO The type (int)
	/**
	 * @todo Define visibility
	 */
	public $type = '';

	// Loaded with the id, exploded by ','
	/**
	 * @todo Define visibility
	 */
	public $idParts = array();

	// The submitted cHash
	/**
	 * @todo Define visibility
	 */
	public $cHash = '';

	// Page will not be cached. Write only TRUE. Never clear value (some other code might have reasons to set it TRUE)
	/**
	 * @todo Define visibility
	 */
	public $no_cache = '';

	// The rootLine (all the way to tree root, not only the current site!) (array)
	/**
	 * @todo Define visibility
	 */
	public $rootLine = '';

	// The pagerecord (array)
	/**
	 * @todo Define visibility
	 */
	public $page = '';

	// This will normally point to the same value as id, but can be changed to point to another page from which content will then be displayed instead.
	/**
	 * @todo Define visibility
	 */
	public $contentPid = 0;

	// Gets set when we are processing a page of type shortcut in the early stages opf init.php when we do not know about languages yet, used later in init.php to determine the correct shortcut in case a translation changes the shortcut target (array)
	protected $originalShortcutPage = NULL;

	/**
	 * sys_page-object, pagefunctions
	 *
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 * @todo Define visibility
	 */
	public $sys_page = '';

	/**
	 * @todo Define visibility
	 */
	public $jumpurl = '';

	// Is set to 1 if a pageNotFound handler could have been called.
	/**
	 * @todo Define visibility
	 */
	public $pageNotFound = 0;

	// Domain start page
	/**
	 * @todo Define visibility
	 */
	public $domainStartPage = 0;

	// Array containing a history of why a requested page was not accessible.
	/**
	 * @todo Define visibility
	 */
	public $pageAccessFailureHistory = array();

	/**
	 * @todo Define visibility
	 */
	public $MP = '';

	/**
	 * @todo Define visibility
	 */
	public $RDCT = '';

	// This can be set from applications as a way to tag cached versions of a page and later perform some external cache management, like clearing only a part of the cache of a page...
	/**
	 * @todo Define visibility
	 */
	public $page_cache_reg1 = 0;

	// Contains the value of the current script path that activated the frontend. Typically "index.php" but by rewrite rules it could be something else! Used for Speaking Urls / Simulate Static Documents.
	/**
	 * @todo Define visibility
	 */
	public $siteScript = '';

	/**
	 * The FE user
	 *
	 * @var \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication
	 * @todo Define visibility
	 */
	public $fe_user = '';

	// Global flag indicating that a front-end user is logged in. This is set only if a user really IS logged in. The group-list may show other groups (like added by IP filter or so) even though there is no user.
	/**
	 * @todo Define visibility
	 */
	public $loginUser = '';

	// (RO=readonly) The group list, sorted numerically. Group '0,-1' is the default group, but other groups may be added by other means than a user being logged in though...
	/**
	 * @todo Define visibility
	 */
	public $gr_list = '';

	// Flag that indicates if a Backend user is logged in!
	/**
	 * @todo Define visibility
	 */
	public $beUserLogin = '';

	// Integer, that indicates which workspace is being previewed.
	/**
	 * @todo Define visibility
	 */
	public $workspacePreview = 0;

	// Shows whether logins are allowed in branch
	/**
	 * @todo Define visibility
	 */
	public $loginAllowedInBranch = TRUE;

	// Shows specific mode (all or groups)
	/**
	 * @todo Define visibility
	 */
	public $loginAllowedInBranch_mode = '';

	// Integer, set to backend user ID to initialize when keyword-based preview is used.
	/**
	 * @todo Define visibility
	 */
	public $ADMCMD_preview_BEUSER_uid = 0;

	// PREVIEW
	// Flag indication that preview is active. This is based on the login of a backend user and whether the backend user has read access to the current page. A value of 1 means ordinary preview, 2 means preview of a non-live workspace
	/**
	 * @todo Define visibility
	 */
	public $fePreview = '';

	// Flag indicating that hidden pages should be shown, selected and so on. This goes for almost all selection of pages!
	/**
	 * @todo Define visibility
	 */
	public $showHiddenPage = '';

	// Flag indicating that hidden records should be shown. This includes sys_template, pages_language_overlay and even fe_groups in addition to all other regular content. So in effect, this includes everything except pages.
	/**
	 * @todo Define visibility
	 */
	public $showHiddenRecords = '';

	// Value that contains the simulated usergroup if any
	/**
	 * @todo Define visibility
	 */
	public $simUserGroup = '0';

	// CONFIGURATION
	/**
	 * Copy of $GLOBALS['TYPO3_CONF_VARS']
	 *
	 * @var array
	 */
	public $TYPO3_CONF_VARS = array();

	// "CONFIG" object from TypoScript. Array generated based on the TypoScript configuration of the current page. Saved with the cached pages.
	/**
	 * @todo Define visibility
	 */
	public $config = '';

	// TEMPLATE / CACHE
	/**
	 * The TypoScript template object. Used to parse the TypoScript template
	 *
	 * @var \TYPO3\CMS\Core\TypoScript\TemplateService
	 * @todo Define visibility
	 */
	public $tmpl = '';

	// Is set to the time-to-live time of cached pages. If FALSE, default is 60*60*24, which is 24 hours.
	/**
	 * @todo Define visibility
	 */
	public $cacheTimeOutDefault = FALSE;

	// Set internally if cached content is fetched from the database
	/**
	 * @todo Define visibility
	 */
	public $cacheContentFlag = 0;

	// Set to the expire time of cached content
	/**
	 * @todo Define visibility
	 */
	public $cacheExpires = 0;

	// Set if cache headers allowing caching are sent.
	/**
	 * @todo Define visibility
	 */
	public $isClientCachable = FALSE;

	// $all used by template fetching system. This array is an identification of the template. If $this->all is empty it's because the template-data is not cached, which it must be.
	/**
	 * @todo Define visibility
	 */
	public $all = '';

	// toplevel - objArrayName, eg 'page'
	/**
	 * @todo Define visibility
	 */
	public $sPre = '';

	// TypoScript configuration of the page-object pointed to by sPre. $this->tmpl->setup[$this->sPre.'.']
	/**
	 * @todo Define visibility
	 */
	public $pSetup = '';

	// This hash is unique to the template, the $this->id and $this->type vars and the gr_list (list of groups). Used to get and later store the cached data
	/**
	 * @todo Define visibility
	 */
	public $newHash = '';

	// If config.ftu (Frontend Track User) is set in TypoScript for the current page, the string value of this var is substituted in the rendered source-code with the string, '&ftu=[token...]' which enables GET-method usertracking as opposed to cookie based
	/**
	 * @todo Define visibility
	 */
	public $getMethodUrlIdToken = '';

	// This flag is set before inclusion of pagegen.php IF no_cache is set. If this flag is set after the inclusion of pagegen.php, no_cache is forced to be set. This is done in order to make sure that php-code from pagegen does not falsely clear the no_cache flag.
	/**
	 * @todo Define visibility
	 */
	public $no_cacheBeforePageGen = '';

	// This flag indicates if temporary content went into the cache during page-generation.
	/**
	 * @todo Define visibility
	 */
	public $tempContent = FALSE;

	// Boolean, passed to TypoScript template class and tells it to render the template forcibly
	/**
	 * @todo Define visibility
	 */
	public $forceTemplateParsing = '';

	// The array which cHash_calc is based on, see ->makeCacheHash().
	/**
	 * @todo Define visibility
	 */
	public $cHash_array = array();

	// Loaded with the serialized array that is used for generating a hashstring for the cache
	/**
	 * @todo Define visibility
	 */
	public $hash_base = '';

	// May be set to the pagesTSconfig
	/**
	 * @todo Define visibility
	 */
	public $pagesTSconfig = '';

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
	// used to accumulate additional HTML-code for the header-section, <head>...</head>. Insert either associative keys (like additionalHeaderData['myStyleSheet'], see reserved keys above) or num-keys (like additionalHeaderData[] = '...')
	/**
	 * @todo Define visibility
	 */
	public $additionalHeaderData = array();

	// used to accumulate additional HTML-code for the footer-section of the template
	public $additionalFooterData = array();

	// used to accumulate additional JavaScript-code. Works like additionalHeaderData. Reserved keys at 'openPic' and 'mouseOver'
	/**
	 * @todo Define visibility
	 */
	public $additionalJavaScript = array();

	// used to accumulate additional Style code. Works like additionalHeaderData.
	/**
	 * @todo Define visibility
	 */
	public $additionalCSS = array();

	// you can add JavaScript functions to each entry in these arrays. Please see how this is done in the GMENU_LAYERS script. The point is that many applications on a page can set handlers for onload, onmouseover and onmouseup
	/**
	 * @todo Define visibility
	 */
	public $JSeventFuncCalls = array(
		'onmousemove' => array(),
		'onmouseup' => array(),
		'onkeydown' => array(),
		'onkeyup' => array(),
		'onkeypress' => array(),
		'onload' => array(),
		'onunload' => array()
	);

	// Used to accumulate JavaScript loaded images (by menus)
	/**
	 * @todo Define visibility
	 */
	public $JSImgCode = '';

	// Used to accumulate DHTML-layers.
	/**
	 * @todo Define visibility
	 */
	public $divSection = '';

	// Default bodytag, if nothing else is set. This can be overridden by applications like TemplaVoila.
	/**
	 * @todo Define visibility
	 */
	public $defaultBodyTag = '<body>';

	// RENDERING configuration, settings from TypoScript is loaded into these vars. See pagegen.php
	// Debug flag, may output special debug html-code.
	/**
	 * @todo Define visibility
	 */
	public $debug = '';

	// Default internal target
	/**
	 * @todo Define visibility
	 */
	public $intTarget = '';

	// Default external target
	/**
	 * @todo Define visibility
	 */
	public $extTarget = '';

	// Default file link target
	/**
	 * @todo Define visibility
	 */
	public $fileTarget = '';

	// Keys are page ids and values are default &MP (mount point) values to set when using the linking features...)
	/**
	 * @todo Define visibility
	 */
	public $MP_defaults = array();

	// If set, typolink() function encrypts email addresses. Is set in pagegen-class.
	/**
	 * @todo Define visibility
	 */
	public $spamProtectEmailAddresses = 0;

	// Absolute Reference prefix
	/**
	 * @todo Define visibility
	 */
	public $absRefPrefix = '';

	// Absolute Reference prefix force flag. This is set, if the type and id is retrieve from PATH_INFO and thus we NEED to prefix urls with at least '/'
	/**
	 * @todo Define visibility
	 */
	public $absRefPrefix_force = 0;

	// Factor for form-field widths compensation
	/**
	 * @todo Define visibility
	 */
	public $compensateFieldWidth = '';

	// Lock file path
	/**
	 * @todo Define visibility
	 */
	public $lockFilePath = '';

	// <A>-tag parameters
	/**
	 * @todo Define visibility
	 */
	public $ATagParams = '';

	// Search word regex, calculated if there has been search-words send. This is used to mark up the found search words on a page when jumped to from a link in a search-result.
	/**
	 * @todo Define visibility
	 */
	public $sWordRegEx = '';

	// Is set to the incoming array sword_list in case of a page-view jumped to from a search-result.
	/**
	 * @todo Define visibility
	 */
	public $sWordList = '';

	// A string prepared for insertion in all links on the page as url-parameters. Based on configuration in TypoScript where you defined which GET_VARS you would like to pass on.
	/**
	 * @todo Define visibility
	 */
	public $linkVars = '';

	// A string set with a comma list of additional GET vars which should NOT be included in the cHash calculation. These vars should otherwise be detected and involved in caching, eg. through a condition in TypoScript.
	/**
	 * @todo Define visibility
	 */
	public $excludeCHashVars = '';

	// If set, edit icons are rendered aside content records. Must be set only if the ->beUserLogin flag is set and set_no_cache() must be called as well.
	/**
	 * @todo Define visibility
	 */
	public $displayEditIcons = '';

	// If set, edit icons are rendered aside individual fields of content. Must be set only if the ->beUserLogin flag is set and set_no_cache() must be called as well.
	/**
	 * @todo Define visibility
	 */
	public $displayFieldEditIcons = '';

	// Site language, 0 (zero) is default, int+ is uid pointing to a sys_language record. Should reflect which language menus, templates etc is displayed in (master language) - but not necessarily the content which could be falling back to default (see sys_language_content)
	/**
	 * @todo Define visibility
	 */
	public $sys_language_uid = 0;

	// Site language mode for content fall back.
	/**
	 * @todo Define visibility
	 */
	public $sys_language_mode = '';

	// Site content selection uid (can be different from sys_language_uid if content is to be selected from a fall-back language. Depends on sys_language_mode)
	/**
	 * @todo Define visibility
	 */
	public $sys_language_content = 0;

	// Site content overlay flag; If set - and sys_language_content is > 0 - , records selected will try to look for a translation pointing to their uid. (If configured in [ctrl][languageField] / [ctrl][transOrigP...]
	/**
	 * @todo Define visibility
	 */
	public $sys_language_contentOL = 0;

	// Is set to the iso code of the sys_language_content if that is properly defined by the sys_language record representing the sys_language_uid. (Requires the extension "static_info_tables")
	/**
	 * @todo Define visibility
	 */
	public $sys_language_isocode = '';

	// RENDERING data
	// 'Global' Storage for various applications. Keys should be 'tx_'.extKey for extensions.
	/**
	 * @todo Define visibility
	 */
	public $applicationData = array();

	/**
	 * @todo Define visibility
	 */
	public $register = array();

	// Stack used for storing array and retrieving register arrays (see LOAD_REGISTER and RESTORE_REGISTER)
	/**
	 * @todo Define visibility
	 */
	public $registerStack = array();

	// Checking that the function is not called eternally. This is done by interrupting at a depth of 50
	/**
	 * @todo Define visibility
	 */
	public $cObjectDepthCounter = 50;

	// used by cObj->RECORDS and cObj->CONTENT to ensure the a records is NOT rendered twice through it!
	/**
	 * @todo Define visibility
	 */
	public $recordRegister = array();

	// This is set to the [table]:[uid] of the latest record rendered. Note that class tslib_cObj has an equal value, but that is pointing to the record delivered in the $data-array of the tslib_cObj instance, if the cObjects CONTENT or RECORD created that instance
	/**
	 * @todo Define visibility
	 */
	public $currentRecord = '';

	// Used by class tslib_menu to keep track of access-keys.
	/**
	 * @todo Define visibility
	 */
	public $accessKey = array();

	// Numerical array where image filenames are added if they are referenced in the rendered document. This includes only TYPO3 generated/inserted images.
	/**
	 * @todo Define visibility
	 */
	public $imagesOnPage = array();

	// Is set in tslib_cObj->cImage() function to the info-array of the most recent rendered image. The information is used in tslib_cObj->IMGTEXT
	/**
	 * @todo Define visibility
	 */
	public $lastImageInfo = array();

	// Used to generate page-unique keys. Point is that uniqid() functions is very slow, so a unikey key is made based on this, see function uniqueHash()
	/**
	 * @todo Define visibility
	 */
	public $uniqueCounter = 0;

	/**
	 * @todo Define visibility
	 */
	public $uniqueString = '';

	// This value will be used as the title for the page in the indexer (if indexing happens)
	/**
	 * @todo Define visibility
	 */
	public $indexedDocTitle = '';

	// Alternative page title (normally the title of the page record). Can be set from applications you make.
	/**
	 * @todo Define visibility
	 */
	public $altPageTitle = '';

	// The base URL set for the page header.
	/**
	 * @todo Define visibility
	 */
	public $baseUrl = '';

	// The proper anchor prefix needed when using speaking urls. (only set if baseUrl is set)
	/**
	 * @todo Define visibility
	 */
	public $anchorPrefix = '';

	/**
	 * Page content render object
	 *
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 * @todo Define visibility
	 */
	public $cObj = '';

	// CONTENT accumulation
	// All page content is accumulated in this variable. See pagegen.php
	/**
	 * @todo Define visibility
	 */
	public $content = '';

	// GENERAL
	// Set to the browser: net / msie if 4+ browsers
	/**
	 * @todo Define visibility
	 */
	public $clientInfo = '';

	/**
	 * @todo Define visibility
	 */
	public $scriptParseTime = 0;

	// Character set (charset) conversion object:
	/**
	 * charset conversion class. May be used by any application.
	 *
	 * @var \TYPO3\CMS\Core\Charset\CharsetConverter
	 * @todo Define visibility
	 */
	public $csConvObj;

	// The default charset used in the frontend if nothing else is set.
	/**
	 * @todo Define visibility
	 */
	public $defaultCharSet = 'utf-8';

	// Internal charset of the frontend during rendering. (Default: UTF-8)
	/**
	 * @todo Define visibility
	 */
	public $renderCharset = '';

	// Output charset of the websites content. This is the charset found in the header, meta tag etc. If different from $renderCharset a conversion happens before output to browser. Defaults to ->renderCharset if not set.
	/**
	 * @todo Define visibility
	 */
	public $metaCharset = '';

	// Assumed charset of locale strings.
	/**
	 * @todo Define visibility
	 */
	public $localeCharset = '';

	// LANG:
	// Set to the system language key (used on the site)
	/**
	 * @todo Define visibility
	 */
	public $lang = '';

	/**
	 * @todo Define visibility
	 */
	public $LL_labels_cache = array();

	/**
	 * @todo Define visibility
	 */
	public $LL_files_cache = array();

	/**
	 * List of language dependencies for actual language. This is used for local variants of a language
	 * that depend on their "main" language, like Brazilian Portuguese or Canadian French.
	 *
	 * @var array
	 */
	protected $languageDependencies = array();

	/**
	 * Locking object for accessing "cache_pagesection"
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker
	 * @todo Define visibility
	 */
	public $pagesection_lockObj;

	/**
	 * Locking object for accessing "cache_pages"
	 *
	 * @var \TYPO3\CMS\Core\Locking\Locker
	 * @todo Define visibility
	 */
	public $pages_lockObj;

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected $pageRenderer;

	/**
	 * The page cache object, use this to save pages to the cache and to
	 * retrieve them again
	 *
	 * @var \TYPO3\CMS\Cache\Backend\AbstractBackend
	 */
	protected $pageCache;

	protected $pageCacheTags = array();

	/**
	 * @var \TYPO3\CMS\Frontend\Page\CacheHashCalculator The cHash Service class used for cHash related functionality
	 */
	protected $cacheHash;

	/**
	 * Class constructor
	 * Takes a number of GET/POST input variable as arguments and stores them internally.
	 * The processing of these variables goes on later in this class.
	 * Also sets internal clientInfo array (browser information) and a unique string (->uniqueString) for this script instance; A md5 hash of the microtime()
	 *
	 * @param array $TYPO3_CONF_VARS The global $TYPO3_CONF_VARS array. Will be set internally in ->TYPO3_CONF_VARS
	 * @param mixed $id The value of \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id')
	 * @param integer $type The value of \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type')
	 * @param boolean $no_cache The value of \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('no_cache'), evaluated to 1/0
	 * @param string $cHash The value of \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cHash')
	 * @param string $jumpurl The value of \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('jumpurl')
	 * @param string $MP The value of \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('MP')
	 * @param string $RDCT The value of \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('RDCT')
	 * @see index_ts.php
	 * @todo Define visibility
	 */
	public function __construct($TYPO3_CONF_VARS, $id, $type, $no_cache = '', $cHash = '', $jumpurl = '', $MP = '', $RDCT = '') {
		// Setting some variables:
		$this->TYPO3_CONF_VARS = $TYPO3_CONF_VARS;
		$this->id = $id;
		$this->type = $type;
		if ($no_cache) {
			if ($this->TYPO3_CONF_VARS['FE']['disableNoCacheParameter']) {
				$warning = '&no_cache=1 has been ignored because $TYPO3_CONF_VARS[\'FE\'][\'disableNoCacheParameter\'] is set!';
				$GLOBALS['TT']->setTSlogMessage($warning, 2);
			} else {
				$warning = '&no_cache=1 has been supplied, so caching is disabled! URL: "' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '"';
				$this->disableCache();
			}
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($warning, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
		}
		$this->cHash = $cHash;
		$this->jumpurl = $jumpurl;
		$this->MP = $this->TYPO3_CONF_VARS['FE']['enable_mount_pids'] ? (string) $MP : '';
		$this->RDCT = $RDCT;
		$this->clientInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::clientInfo();
		$this->uniqueString = md5(microtime());
		$this->csConvObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
		// Call post processing function for constructor:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc'])) {
			$_params = array('pObj' => &$this);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		$this->cacheHash = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\CacheHashCalculator');
		$this->initCaches();
	}

	/**
	 * Connect to SQL database. May exit after outputting an error message
	 * or some JavaScript redirecting to the install tool.
	 *
	 * @throws \RuntimeException
	 * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
	 * @return void
	 */
	public function connectToDB() {
		try {
			$GLOBALS['TYPO3_DB']->connectDB();
		} catch (\RuntimeException $exception) {
			switch ($exception->getCode()) {
			case 1270853883:
				// Cannot connect to current database
				$message = 'Cannot connect to the configured database "' . TYPO3_db . '"';
				if ($this->checkPageUnavailableHandler()) {
					$this->pageUnavailableAndExit($message);
				} else {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					throw new \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException($message, 1301648782);
				}
				break;
			case 1270853884:
				// Username / password not accepted
				$message = 'The current username, password or host was not accepted when' . ' the connection to the database was attempted to be established!';
				if ($this->checkPageUnavailableHandler()) {
					$this->pageUnavailableAndExit($message);
				} else {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					throw new \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException('Database Error: ' . $message, 1301648945);
				}
				break;
			default:
				throw $exception;
			}
		}
		// Call post processing function for DB connection:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'])) {
			$_params = array('pObj' => &$this);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Looks up the value of $this->RDCT in the database and if it is
	 * found to be associated with a redirect URL then the redirection
	 * is carried out with a 'Location:' header
	 * May exit after sending a location-header.
	 *
	 * @return void
	 */
	public function sendRedirect() {
		if ($this->RDCT) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('params', 'cache_md5params', 'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->RDCT, 'cache_md5params'));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$this->updateMD5paramsRecord($this->RDCT);
				header('Location: ' . $row['params']);
				die;
			}
		}
	}

	/**
	 * Gets instance of PageRenderer
	 *
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	public function getPageRenderer() {
		if (!isset($this->pageRenderer)) {
			$this->pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Page\\PageRenderer');
			$this->pageRenderer->setTemplateFile(PATH_tslib . 'templates/tslib_page_frontend.html');
			$this->pageRenderer->setBackPath(TYPO3_mainDir);
		}
		return $this->pageRenderer;
	}

	/**
	 * This is needed for USER_INT processing
	 *
	 * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
	 */
	protected function setPageRenderer(\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer) {
		$this->pageRenderer = $pageRenderer;
	}

	/********************************************
	 *
	 * Initializing, resolving page id
	 *
	 ********************************************/
	/**
	 * Initializes the caching system.
	 *
	 * @return void
	 */
	protected function initCaches() {
		$this->pageCache = $GLOBALS['typo3CacheManager']->getCache('cache_pages');
	}

	/**
	 * Initializes the front-end login user.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function initFEuser() {
		$this->fe_user = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Authentication\\FrontendUserAuthentication');
		$this->fe_user->lockIP = $this->TYPO3_CONF_VARS['FE']['lockIP'];
		$this->fe_user->checkPid = $this->TYPO3_CONF_VARS['FE']['checkFeUserPid'];
		$this->fe_user->lifetime = intval($this->TYPO3_CONF_VARS['FE']['lifetime']);
		// List of pid's acceptable
		$this->fe_user->checkPid_value = $GLOBALS['TYPO3_DB']->cleanIntList(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pid'));
		// Check if a session is transferred:
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('FE_SESSION_KEY')) {
			$fe_sParts = explode('-', \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('FE_SESSION_KEY'));
			// If the session key hash check is OK:
			if (!strcmp(md5(($fe_sParts[0] . '/' . $this->TYPO3_CONF_VARS['SYS']['encryptionKey'])), $fe_sParts[1])) {
				$cookieName = \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication::getCookieName();
				$_COOKIE[$cookieName] = $fe_sParts[0];
				if (isset($_SERVER['HTTP_COOKIE'])) {
					// See http://forge.typo3.org/issues/27740
					$_SERVER['HTTP_COOKIE'] .= ';' . $cookieName . '=' . $fe_sParts[0];
				}
				$this->fe_user->forceSetCookie = 1;
				unset($cookieName);
			}
		}
		if ($this->TYPO3_CONF_VARS['FE']['dontSetCookie']) {
			$this->fe_user->dontSetCookie = 1;
		}
		$this->fe_user->start();
		$this->fe_user->unpack_uc('');
		// Gets session data
		$this->fe_user->fetchSessionData();
		$recs = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('recs');
		// If any record registration is submitted, register the record.
		if (is_array($recs)) {
			$this->fe_user->record_registration($recs, $this->TYPO3_CONF_VARS['FE']['maxSessionDataSize']);
		}
		// Call hook for possible manipulation of frontend user object
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'])) {
			$_params = array('pObj' => &$this);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		// For every 60 seconds the is_online timestamp is updated.
		if (is_array($this->fe_user->user) && $this->fe_user->user['uid'] && $this->fe_user->user['is_online'] < $GLOBALS['EXEC_TIME'] - 60) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid=' . intval($this->fe_user->user['uid']), array('is_online' => $GLOBALS['EXEC_TIME']));
		}
	}

	/**
	 * Initializes the front-end user groups.
	 * Sets ->loginUser and ->gr_list based on front-end user status.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function initUserGroups() {
		// This affects the hidden-flag selecting the fe_groups for the user!
		$this->fe_user->showHiddenRecords = $this->showHiddenRecords;
		// no matter if we have an active user we try to fetch matching groups which can be set without an user (simulation for instance!)
		$this->fe_user->fetchGroupData();
		if (is_array($this->fe_user->user) && count($this->fe_user->groupData['uid'])) {
			// global flag!
			$this->loginUser = 1;
			// group -2 is not an existing group, but denotes a 'default' group when a user IS logged in. This is used to let elements be shown for all logged in users!
			$this->gr_list = '0,-2';
			$gr_array = $this->fe_user->groupData['uid'];
		} else {
			$this->loginUser = 0;
			// group -1 is not an existing group, but denotes a 'default' group when not logged in. This is used to let elements be hidden, when a user is logged in!
			$this->gr_list = '0,-1';
			if ($this->loginAllowedInBranch) {
				// For cases where logins are not banned from a branch usergroups can be set based on IP masks so we should add the usergroups uids.
				$gr_array = $this->fe_user->groupData['uid'];
			} else {
				// Set to blank since we will NOT risk any groups being set when no logins are allowed!
				$gr_array = array();
			}
		}
		// Clean up.
		// Make unique...
		$gr_array = array_unique($gr_array);
		// sort
		sort($gr_array);
		if (count($gr_array) && !$this->loginAllowedInBranch_mode) {
			$this->gr_list .= ',' . implode(',', $gr_array);
		}
		if ($this->fe_user->writeDevLog) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Valid usergroups for TSFE: ' . $this->gr_list, 'TYPO3\\CMS\\Frontend\\Controller\\TypoScriptFrontendController');
		}
	}

	/**
	 * Checking if a user is logged in or a group constellation different from "0,-1"
	 *
	 * @return boolean TRUE if either a login user is found (array fe_user->user) OR if the gr_list is set to something else than '0,-1' (could be done even without a user being logged in!)
	 * @todo Define visibility
	 */
	public function isUserOrGroupSet() {
		return is_array($this->fe_user->user) || $this->gr_list !== '0,-1';
	}

	/**
	 * Provides ways to bypass the '?id=[xxx]&type=[xx]' format, using either PATH_INFO or virtual HTML-documents (using Apache mod_rewrite)
	 *
	 * Two options:
	 * 1) Use PATH_INFO (also Apache) to extract id and type from that var. Does not require any special modules compiled with apache. (less typical)
	 * 2) Using hook which enables features like those provided from "realurl" extension (AKA "Speaking URLs")
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function checkAlternativeIdMethods() {
		$this->siteScript = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_SCRIPT');
		// Call post processing function for custom URL methods.
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc'])) {
			$_params = array('pObj' => &$this);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Clears the preview-flags, sets sim_exec_time to current time.
	 * Hidden pages must be hidden as default, $GLOBALS['SIM_EXEC_TIME'] is set to $GLOBALS['EXEC_TIME']
	 * in bootstrap initializeGlobalTimeVariables(). Alter it by adding or subtracting seconds.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function clear_preview() {
		$this->showHiddenPage = 0;
		$this->showHiddenRecords = 0;
		$GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
		$GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];
		$this->fePreview = 0;
	}

	/**
	 * Checks if a backend user is logged in
	 *
	 * @return boolean whether a backend user is logged in
	 */
	public function isBackendUserLoggedIn() {
		return $this->beUserLogin ? TRUE : FALSE;
	}

	/**
	 * Creates the backend user object and returns it.
	 *
	 * @return \TYPO3\CMS\Backend\FrontendBackendUserAuthentication the backend user object
	 */
	public function initializeBackendUser() {
		// PRE BE_USER HOOK
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['preBeUser'])) {
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['preBeUser'] as $_funcRef) {
				$_params = array();
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		/** @var $BE_USER \TYPO3\CMS\Backend\FrontendBackendUserAuthentication */
		$BE_USER = NULL;
		// If the backend cookie is set,
		// we proceed and check if a backend user is logged in.
		if ($_COOKIE[\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::getCookieName()]) {
			$GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'] = microtime(TRUE);
			$GLOBALS['TT']->push('Back End user initialized', '');
			// TODO: validate the comment below: is this necessary? if so,
			// formfield_status should be set to "" in \TYPO3\CMS\Backend\FrontendBackendUserAuthentication
			// which is a subclass of \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
			// ----
			// the value this->formfield_status is set to empty in order to
			// disable login-attempts to the backend account through this script
			// New backend user object
			$BE_USER = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\FrontendBackendUserAuthentication');
			$BE_USER->OS = TYPO3_OS;
			$BE_USER->lockIP = $this->TYPO3_CONF_VARS['BE']['lockIP'];
			// Object is initialized
			$BE_USER->start();
			$BE_USER->unpack_uc('');
			if ($BE_USER->user['uid']) {
				$BE_USER->fetchGroupData();
				$this->beUserLogin = 1;
			}
			// Unset the user initialization.
			if (!$BE_USER->checkLockToIP() || !$BE_USER->checkBackendAccessSettingsFromInitPhp() || !$BE_USER->user['uid']) {
				$BE_USER = NULL;
				$this->beUserLogin = 0;
				$_SESSION['TYPO3-TT-start'] = FALSE;
			}
			$GLOBALS['TT']->pull();
			$GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'] = microtime(TRUE);
		}
		// POST BE_USER HOOK
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['postBeUser'])) {
			$_params = array(
				'BE_USER' => &$BE_USER
			);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/index_ts.php']['postBeUser'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		return $BE_USER;
	}

	/**
	 * Determines the id and evaluates any preview settings
	 * Basically this function is about determining whether a backend user is logged in, if he has read access to the page and if he's previewing the page. That all determines which id to show and how to initialize the id.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function determineId() {
		// Call pre processing function for id determination
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PreProcessing'])) {
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PreProcessing'] as $functionReference) {
				$parameters = array('parentObject' => $this);
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($functionReference, $parameters, $this);
			}
		}
		// Getting ARG-v values if some
		$this->setIDfromArgV();
		// If there is a Backend login we are going to check for any preview settings:
		$GLOBALS['TT']->push('beUserLogin', '');
		if ($this->beUserLogin || $this->doWorkspacePreview()) {
			// Backend user preview features:
			if ($this->beUserLogin && $GLOBALS['BE_USER']->adminPanel instanceof \TYPO3\CMS\Frontend\View\AdminPanelView) {
				$this->fePreview = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview') ? TRUE : FALSE;
				// If admin panel preview is enabled...
				if ($this->fePreview) {
					$fe_user_OLD_USERGROUP = $this->fe_user->user['usergroup'];
					$this->showHiddenPage = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview', 'showHiddenPages');
					$this->showHiddenRecords = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview', 'showHiddenRecords');
					// Simulate date
					$simTime = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview', 'simulateDate');
					if ($simTime) {
						$GLOBALS['SIM_EXEC_TIME'] = $simTime;
						$GLOBALS['SIM_ACCESS_TIME'] = $simTime - $simTime % 60;
					}
					// simulate user
					$simUserGroup = $GLOBALS['BE_USER']->adminPanel->extGetFeAdminValue('preview', 'simulateUserGroup');
					$this->simUserGroup = $simUserGroup;
					if ($simUserGroup) {
						$this->fe_user->user['usergroup'] = $simUserGroup;
					}
					if (!$simUserGroup && !$simTime && !$this->showHiddenPage && !$this->showHiddenRecords) {
						$this->fePreview = 0;
					}
				}
			}
			if ($this->id) {
				if ($this->determineIdIsHiddenPage()) {
					// The preview flag is set only if the current page turns out to actually be hidden!
					$this->fePreview = 1;
					$this->showHiddenPage = 1;
				}
				// For Live workspace: Check root line for proper connection to tree root (done because of possible preview of page / branch versions)
				if (!$this->fePreview && $this->whichWorkspace() === 0) {
					// Initialize the page-select functions to check rootline:
					$temp_sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
					$temp_sys_page->init($this->showHiddenPage);
					// If root line contained NO records and ->error_getRootLine_failPid tells us that it was because of a pid=-1 (indicating a "version" record)...:
					if (!count($temp_sys_page->getRootLine($this->id, $this->MP)) && $temp_sys_page->error_getRootLine_failPid == -1) {
						// Setting versioningPreview flag and try again:
						$temp_sys_page->versioningPreview = TRUE;
						if (count($temp_sys_page->getRootLine($this->id, $this->MP))) {
							// Finally, we got a root line (meaning that it WAS due to versioning preview of a page somewhere) and we set the fePreview flag which in itself will allow sys_page class to display previews of versionized records.
							$this->fePreview = 1;
						}
					}
				}
			}
			// The preview flag will be set if a backend user is in an offline workspace
			if (($GLOBALS['BE_USER']->user['workspace_preview'] || \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ADMCMD_view') || $this->doWorkspacePreview()) && ($this->whichWorkspace() === -1 || $this->whichWorkspace() > 0)) {
				// Will show special preview message.
				$this->fePreview = 2;
			}
			// If the front-end is showing a preview, caching MUST be disabled.
			if ($this->fePreview) {
				$this->disableCache();
			}
		}
		$GLOBALS['TT']->pull();
		// Now, get the id, validate access etc:
		$this->fetch_the_id();
		// Check if backend user has read access to this page. If not, recalculate the id.
		if ($this->beUserLogin && $this->fePreview) {
			if (!$GLOBALS['BE_USER']->doesUserHaveAccess($this->page, 1)) {
				// Resetting
				$this->clear_preview();
				$this->fe_user->user['usergroup'] = $fe_user_OLD_USERGROUP;
				// Fetching the id again, now with the preview settings reset.
				$this->fetch_the_id();
			}
		}
		// Checks if user logins are blocked for a certain branch and if so, will unset user login and re-fetch ID.
		$this->loginAllowedInBranch = $this->checkIfLoginAllowedInBranch();
		// Logins are not allowed:
		if (!$this->loginAllowedInBranch) {
			// Only if there is a login will we run this...
			if ($this->isUserOrGroupSet()) {
				if ($this->loginAllowedInBranch_mode == 'all') {
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
		// Make sure it's an integer
		$this->id = ($this->contentPid = intval($this->id));
		// Make sure it's an integer
		$this->type = intval($this->type);
		// Call post processing function for id determination:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc'])) {
			$_params = array('pObj' => &$this);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Checks if the page is hidden in the active workspace.
	 * If it is hidden, preview flags will be set.
	 *
	 * @return boolean
	 */
	protected function determineIdIsHiddenPage() {
		$field = \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->id) ? 'uid' : 'alias';
		$pageSelectCondition = $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->id, 'pages');
		$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid,hidden,starttime,endtime', 'pages', $pageSelectCondition . ' AND pid>=0 AND deleted=0');
		$workspace = $this->whichWorkspace();
		if ($workspace !== 0 && $workspace !== FALSE) {
			// Fetch overlay of page if in workspace and check if it is hidden
			$pageSelectObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
			$pageSelectObject->versioningPreview = TRUE;
			$pageSelectObject->init(FALSE);
			$targetPage = $pageSelectObject->getWorkspaceVersionOfRecord($this->whichWorkspace(), 'pages', $page['uid']);
			$result = $targetPage === -1 || $targetPage === -2;
		} else {
			$result = is_array($page) && ($page['hidden'] || $page['starttime'] > $GLOBALS['SIM_EXEC_TIME'] || $page['endtime'] != 0 && $page['endtime'] <= $GLOBALS['SIM_EXEC_TIME']);
		}
		return $result;
	}

	/**
	 * Get The Page ID
	 * This gets the id of the page, checks if the page is in the domain and if the page is accessible
	 * Sets variables such as $this->sys_page, $this->loginUser, $this->gr_list, $this->id, $this->type, $this->domainStartPage, $this->idParts
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function fetch_the_id() {
		$GLOBALS['TT']->push('fetch_the_id initialize/', '');
		// Initialize the page-select functions.
		$this->sys_page = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		$this->sys_page->versioningPreview = $this->fePreview === 2 || intval($this->workspacePreview) || \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ADMCMD_view') ? TRUE : FALSE;
		$this->sys_page->versioningWorkspaceId = $this->whichWorkspace();
		$this->sys_page->init($this->showHiddenPage);
		// Set the valid usergroups for FE
		$this->initUserGroups();
		// Sets sys_page where-clause
		$this->setSysPageWhereClause();
		// Splitting $this->id by a period (.).
		// First part is 'id' and second part (if exists) will overrule the &type param
		$idParts = explode('.', $this->id, 2);
		$this->id = $idParts[0];
		if (isset($idParts[1])) {
			$this->type = $idParts[1];
		}
		// Splitting $this->id by a comma (,).
		// First part is 'id' and other parts are just stored for use in scripts.
		// Still used in the old wapversion.lib files.
		$this->idParts = explode(',', $this->id);
		$this->id = $this->idParts[0];

			// Hook for further id manipulation
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['modifyPageId'])) {
			$_params = array('id' => $this->id);
			$newId = FALSE;
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['modifyPageId'] as $_funcRef) {
				$deprecationMessage = 'Hook $TYPO3_CONF_VARS[SC_OPTIONS][tslib/class.tslib_fe.php][modifyPageId] is ' .
					'deprecated since 6.0 and will be removed two versions later';
				if (!is_object($_funcRef) || !is_a($_funcRef, 'Closure')) {
					$deprecationMessage .= '- used by ' . $_funcRef;
				}
				\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog($deprecationMessage);
				$newIdFromFunc = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
				if ($newIdFromFunc !== FALSE) {
					$newId = $newIdFromFunc;
				}
			}
			if ($newId !== FALSE) {
				$this->id = $newId;
			}
		}

		// If $this->id is a string, it's an alias
		$this->checkAndSetAlias();
		// The id and type is set to the integer-value - just to be sure...
		$this->id = intval($this->id);
		$this->type = intval($this->type);
		$GLOBALS['TT']->pull();
		// We find the first page belonging to the current domain
		$GLOBALS['TT']->push('fetch_the_id domain/', '');
		// The page_id of the current domain
		$this->domainStartPage = $this->findDomainRecord($this->TYPO3_CONF_VARS['SYS']['recursiveDomainSearch']);
		if (!$this->id) {
			if ($this->domainStartPage) {
				// If the id was not previously set, set it to the id of the domain.
				$this->id = $this->domainStartPage;
			} else {
				// Find the first 'visible' page in that domain
				$theFirstPage = $this->sys_page->getFirstWebPage($this->id);
				if ($theFirstPage) {
					$this->id = $theFirstPage['uid'];
				} else {
					$message = 'No pages are found on the rootlevel!';
					if ($this->checkPageUnavailableHandler()) {
						$this->pageUnavailableAndExit($message);
					} else {
						\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
						throw new \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException($message, 1301648975);
					}
				}
			}
		}
		$GLOBALS['TT']->pull();
		$GLOBALS['TT']->push('fetch_the_id rootLine/', '');
		// We store the originally requested id
		$requestedId = $this->id;
		$this->getPageAndRootlineWithDomain($this->domainStartPage);
		$GLOBALS['TT']->pull();
		if ($this->pageNotFound && $this->TYPO3_CONF_VARS['FE']['pageNotFound_handling']) {
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
			$requestUrlScheme = parse_url(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), PHP_URL_SCHEME);
			if ((int) $this->page['url_scheme'] === \TYPO3\CMS\Core\Utility\HttpUtility::SCHEME_HTTP && $requestUrlScheme == 'https') {
				$newUrl = 'http://' . substr(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), 8);
			} elseif ((int) $this->page['url_scheme'] === \TYPO3\CMS\Core\Utility\HttpUtility::SCHEME_HTTPS && $requestUrlScheme == 'http') {
				$newUrl = 'https://' . substr(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), 7);
			}
			if ($newUrl !== '') {
				if ($_SERVER['REQUEST_METHOD'] === 'POST') {
					$headerCode = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303;
				} else {
					$headerCode = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_301;
				}
				\TYPO3\CMS\Core\Utility\HttpUtility::redirect($newUrl, $headerCode);
			}
		}
		// Set no_cache if set
		if ($this->page['no_cache']) {
			$this->set_no_cache('no_cache is set in page properties');
		}
		// Init SYS_LASTCHANGED
		$this->register['SYS_LASTCHANGED'] = intval($this->page['tstamp']);
		if ($this->register['SYS_LASTCHANGED'] < intval($this->page['SYS_LASTCHANGED'])) {
			$this->register['SYS_LASTCHANGED'] = intval($this->page['SYS_LASTCHANGED']);
		}
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['fetchPageId-PostProcessing'])) {
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['fetchPageId-PostProcessing'] as $functionReference) {
				$parameters = array('parentObject' => $this);
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($functionReference, $parameters, $this);
			}
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
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function getPageAndRootline() {
		$this->page = $this->sys_page->getPage($this->id);
		if (!count($this->page)) {
			// If no page, we try to find the page before in the rootLine.
			// Page is 'not found' in case the id itself was not an accessible page. code 1
			$this->pageNotFound = 1;
			$this->rootLine = $this->sys_page->getRootLine($this->id, $this->MP);
			if (count($this->rootLine)) {
				$c = count($this->rootLine) - 1;
				while ($c > 0) {
					// Add to page access failure history:
					$this->pageAccessFailureHistory['direct_access'][] = $this->rootLine[$c];
					// Decrease to next page in rootline and check the access to that, if OK, set as page record and ID value.
					$c--;
					$this->id = $this->rootLine[$c]['uid'];
					$this->page = $this->sys_page->getPage($this->id);
					if (count($this->page)) {
						break;
					}
				}
			}
			// If still no page...
			if (!count($this->page)) {
				$message = 'The requested page does not exist!';
				if ($this->TYPO3_CONF_VARS['FE']['pageNotFound_handling']) {
					$this->pageNotFoundAndExit($message);
				} else {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					throw new \TYPO3\CMS\Core\Error\Http\PageNotFoundException($message, 1301648780);
				}
			}
		}
		// Spacer is not accessible in frontend
		if ($this->page['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SPACER) {
			$message = 'The requested page does not exist!';
			if ($this->TYPO3_CONF_VARS['FE']['pageNotFound_handling']) {
				$this->pageNotFoundAndExit($message);
			} else {
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
				throw new \TYPO3\CMS\Core\Error\Http\PageNotFoundException($message, 1301648781);
			}
		}
		// Is the ID a link to another page??
		if ($this->page['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT) {
			// We need to clear MP if the page is a shortcut. Reason is if the short cut goes to another page, then we LEAVE the rootline which the MP expects.
			$this->MP = '';
			// saving the page so that we can check later - when we know
			// about languages - whether we took the correct shortcut or
			// whether a translation of the page overwrites the shortcut
			// target and we need to follow the new target
			$this->originalShortcutPage = $this->page;
			$this->page = $this->getPageShortcut($this->page['shortcut'], $this->page['shortcut_mode'], $this->page['uid']);
			$this->id = $this->page['uid'];
		}
		// Gets the rootLine
		$this->rootLine = $this->sys_page->getRootLine($this->id, $this->MP);
		// If not rootline we're off...
		if (!count($this->rootLine)) {
			$ws = $this->whichWorkspace();
			if ($this->sys_page->error_getRootLine_failPid == -1 && $ws) {
				$this->sys_page->versioningPreview = TRUE;
				$this->versioningWorkspaceId = $ws;
				$this->rootLine = $this->sys_page->getRootLine($this->id, $this->MP);
			}
			if (!count($this->rootLine)) {
				$message = 'The requested page didn\'t have a proper connection to the tree-root!';
				if ($this->checkPageUnavailableHandler()) {
					$this->pageUnavailableAndExit($message);
				} else {
					$rootline = '(' . $this->sys_page->error_getRootLine . ')';
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					throw new \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException($message . '<br /><br />' . $rootline, 1301648167);
				}
			}
			$this->fePreview = 1;
		}
		// Checking for include section regarding the hidden/starttime/endtime/fe_user (that is access control of a whole subbranch!)
		if ($this->checkRootlineForIncludeSection()) {
			if (!count($this->rootLine)) {
				$message = 'The requested page was not accessible!';
				if ($this->checkPageUnavailableHandler()) {
					$this->pageUnavailableAndExit($message);
				} else {
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					throw new \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException($message, 1301648234);
				}
			} else {
				$el = reset($this->rootLine);
				$this->id = $el['uid'];
				$this->page = $this->sys_page->getPage($this->id);
				$this->rootLine = $this->sys_page->getRootLine($this->id, $this->MP);
			}
		}
	}

	/**
	 * Get page shortcut; Finds the records pointed to by input value $SC (the shortcut value)
	 *
	 * @param integer $SC The value of the "shortcut" field from the pages record
	 * @param integer $mode The shortcut mode: 1 will select first subpage, 2 a random subpage, 3 the parent page; default is the page pointed to by $SC
	 * @param integer $thisUid The current page UID of the page which is a shortcut
	 * @param integer $itera Safety feature which makes sure that the function is calling itself recursively max 20 times (since this function can find shortcuts to other shortcuts to other shortcuts...)
	 * @param array $pageLog An array filled with previous page uids tested by the function - new page uids are evaluated against this to avoid going in circles.
	 * @return mixed Returns the page record of the page that the shortcut pointed to.
	 * @access private
	 * @see getPageAndRootline()
	 * @todo Define visibility
	 */
	public function getPageShortcut($SC, $mode, $thisUid, $itera = 20, $pageLog = array()) {
		$idArray = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $SC);
		// Find $page record depending on shortcut mode:
		switch ($mode) {
		case \TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE:

		case \TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE:
			$pageArray = $this->sys_page->getMenu($idArray[0] ? $idArray[0] : $thisUid, '*', 'sorting', 'AND pages.doktype<199 AND pages.doktype!=' . \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_BE_USER_SECTION);
			$pO = 0;
			if ($mode == \TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE && count($pageArray)) {
				$randval = intval(rand(0, count($pageArray) - 1));
				$pO = $randval;
			}
			$c = 0;
			foreach ($pageArray as $pV) {
				if ($c == $pO) {
					$page = $pV;
					break;
				}
				$c++;
			}
			if (count($page) == 0) {
				$message = 'This page (ID ' . $thisUid . ') is of type "Shortcut" and configured to redirect to a subpage. ' . 'However, this page has no accessible subpages.';
				throw new \TYPO3\CMS\Core\Error\Http\PageNotFoundException($message, 1301648328);
			}
			break;
		case \TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_PARENT_PAGE:
			$parent = $this->sys_page->getPage($thisUid);
			$page = $this->sys_page->getPage($parent['pid']);
			if (count($page) == 0) {
				$message = 'This page (ID ' . $thisUid . ') is of type "Shortcut" and configured to redirect to its parent page. ' . 'However, the parent page is not accessible.';
				throw new \TYPO3\CMS\Core\Error\Http\PageNotFoundException($message, 1301648358);
			}
			break;
		default:
			$page = $this->sys_page->getPage($idArray[0]);
			if (count($page) == 0) {
				$message = 'This page (ID ' . $thisUid . ') is of type "Shortcut" and configured to redirect to a page, which is not accessible (ID ' . $idArray[0] . ').';
				throw new \TYPO3\CMS\Core\Error\Http\PageNotFoundException($message, 1301648404);
			}
			break;
		}
		// Check if short cut page was a shortcut itself, if so look up recursively:
		if ($page['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT) {
			if (!in_array($page['uid'], $pageLog) && $itera > 0) {
				$pageLog[] = $page['uid'];
				$page = $this->getPageShortcut($page['shortcut'], $page['shortcut_mode'], $page['uid'], $itera - 1, $pageLog);
			} else {
				$pageLog[] = $page['uid'];
				$message = 'Page shortcuts were looping in uids ' . implode(',', $pageLog) . '...!';
				\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
				throw new \RuntimeException($message, 1294587212);
			}
		}
		// Return resulting page:
		return $page;
	}

	/**
	 * Checks the current rootline for defined sections.
	 *
	 * @return boolean
	 * @access private
	 * @todo Define visibility
	 */
	public function checkRootlineForIncludeSection() {
		$c = count($this->rootLine);
		$removeTheRestFlag = 0;
		for ($a = 0; $a < $c; $a++) {
			if (!$this->checkPagerecordForIncludeSection($this->rootLine[$a])) {
				// Add to page access failure history:
				$this->pageAccessFailureHistory['sub_section'][] = $this->rootLine[$a];
				$removeTheRestFlag = 1;
			}
			if ($this->rootLine[$a]['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_BE_USER_SECTION) {
				// If there is a backend user logged in, check if he has read access to the page:
				if ($this->beUserLogin) {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'uid=' . intval($this->id) . ' AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));
					// versionOL()?
					list($isPage) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
					if (!$isPage) {
						// If there was no page selected, the user apparently did not have read access to the current PAGE (not position in rootline) and we set the remove-flag...
						$removeTheRestFlag = 1;
					}
				} else {
					// Dont go here, if there is no backend user logged in.
					$removeTheRestFlag = 1;
				}
			}
			if ($removeTheRestFlag) {
				// Page is 'not found' in case a subsection was found and not accessible, code 2
				$this->pageNotFound = 2;
				unset($this->rootLine[$a]);
			}
		}
		return $removeTheRestFlag;
	}

	/**
	 * Checks page record for enableFields
	 * Returns TRUE if enableFields does not disable the page record.
	 * Takes notice of the ->showHiddenPage flag and uses SIM_ACCESS_TIME for start/endtime evaluation
	 *
	 * @param array $row The page record to evaluate (needs fields: hidden, starttime, endtime, fe_group)
	 * @param boolean $bypassGroupCheck Bypass group-check
	 * @return boolean TRUE, if record is viewable.
	 * @see tslib_cObj::getTreeList(), checkPagerecordForIncludeSection()
	 * @todo Define visibility
	 */
	public function checkEnableFields($row, $bypassGroupCheck = FALSE) {
		if ((!$row['hidden'] || $this->showHiddenPage) && $row['starttime'] <= $GLOBALS['SIM_ACCESS_TIME'] && ($row['endtime'] == 0 || $row['endtime'] > $GLOBALS['SIM_ACCESS_TIME']) && ($bypassGroupCheck || $this->checkPageGroupAccess($row))) {
			return TRUE;
		}
	}

	/**
	 * Check group access against a page record
	 *
	 * @param array $row The page record to evaluate (needs field: fe_group)
	 * @param mixed $groupList List of group id's (comma list or array). Default is $this->gr_list
	 * @return boolean TRUE, if group access is granted.
	 * @access private
	 * @todo Define visibility
	 */
	public function checkPageGroupAccess($row, $groupList = NULL) {
		if (is_null($groupList)) {
			$groupList = $this->gr_list;
		}
		if (!is_array($groupList)) {
			$groupList = explode(',', $groupList);
		}
		$pageGroupList = explode(',', $row['fe_group'] ? $row['fe_group'] : 0);
		return count(array_intersect($groupList, $pageGroupList)) > 0;
	}

	/**
	 * Checks page record for include section
	 *
	 * @param array $row The page record to evaluate (needs fields: extendToSubpages + hidden, starttime, endtime, fe_group)
	 * @return boolean Returns TRUE if either extendToSubpages is not checked or if the enableFields does not disable the page record.
	 * @access private
	 * @see checkEnableFields(), tslib_cObj::getTreeList(), checkRootlineForIncludeSection()
	 * @todo Define visibility
	 */
	public function checkPagerecordForIncludeSection($row) {
		return !$row['extendToSubpages'] || $this->checkEnableFields($row) ? 1 : 0;
	}

	/**
	 * Checks if logins are allowed in the current branch of the page tree. Traverses the full root line and returns TRUE if logins are OK, otherwise FALSE (and then the login user must be unset!)
	 *
	 * @return boolean returns TRUE if logins are OK, otherwise FALSE (and then the login user must be unset!)
	 * @todo Define visibility
	 */
	public function checkIfLoginAllowedInBranch() {
		// Initialize:
		$c = count($this->rootLine);
		$disable = FALSE;
		// Traverse root line from root and outwards:
		for ($a = 0; $a < $c; $a++) {
			// If a value is set for login state:
			if ($this->rootLine[$a]['fe_login_mode'] > 0) {
				// Determine state from value:
				if ((int) $this->rootLine[$a]['fe_login_mode'] === 1) {
					$disable = TRUE;
					$this->loginAllowedInBranch_mode = 'all';
				} elseif ((int) $this->rootLine[$a]['fe_login_mode'] === 3) {
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
	 * @return array Summary of why page access was not allowed.
	 * @todo Define visibility
	 */
	public function getPageAccessFailureReasons() {
		$output = array();
		$combinedRecords = array_merge(is_array($this->pageAccessFailureHistory['direct_access']) ? $this->pageAccessFailureHistory['direct_access'] : array(array('fe_group' => 0)), is_array($this->pageAccessFailureHistory['sub_section']) ? $this->pageAccessFailureHistory['sub_section'] : array());
		if (count($combinedRecords)) {
			foreach ($combinedRecords as $k => $pagerec) {
				// If $k=0 then it is the very first page the original ID was pointing at and that will get a full check of course
				// If $k>0 it is parent pages being tested. They are only significant for the access to the first page IF they had the extendToSubpages flag set, hence checked only then!
				if (!$k || $pagerec['extendToSubpages']) {
					if ($pagerec['hidden']) {
						$output['hidden'][$pagerec['uid']] = TRUE;
					}
					if ($pagerec['starttime'] > $GLOBALS['SIM_ACCESS_TIME']) {
						$output['starttime'][$pagerec['uid']] = $pagerec['starttime'];
					}
					if ($pagerec['endtime'] != 0 && $pagerec['endtime'] <= $GLOBALS['SIM_ACCESS_TIME']) {
						$output['endtime'][$pagerec['uid']] = $pagerec['endtime'];
					}
					if (!$this->checkPageGroupAccess($pagerec)) {
						$output['fe_group'][$pagerec['uid']] = $pagerec['fe_group'];
					}
				}
			}
		}
		return $output;
	}

	/**
	 * This checks if there are ARGV-parameters in the QUERY_STRING and if so, those are used for the id
	 * $this->id must be 'FALSE' in order for any processing to happen in here
	 * If an id/alias value is extracted from the QUERY_STRING it is set in $this->id
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function setIDfromArgV() {
		if (!$this->id) {
			list($theAlias) = explode('&', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('QUERY_STRING'));
			$theAlias = trim($theAlias);
			$this->id = $theAlias != '' && strpos($theAlias, '=') === FALSE ? $theAlias : 0;
		}
	}

	/**
	 * Gets ->page and ->rootline information based on ->id. ->id may change during this operation.
	 * If not inside domain, then default to first page in domain.
	 *
	 * @param integer $domainStartPage Page uid of the page where the found domain record is (pid of the domain record)
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function getPageAndRootlineWithDomain($domainStartPage) {
		$this->getPageAndRootline();
		// Checks if the $domain-startpage is in the rootLine. This is necessary so that references to page-id's from other domains are not possible.
		if ($domainStartPage && is_array($this->rootLine)) {
			$idFound = 0;
			foreach ($this->rootLine as $key => $val) {
				if ($val['uid'] == $domainStartPage) {
					$idFound = 1;
					break;
				}
			}
			if (!$idFound) {
				// Page is 'not found' in case the id was outside the domain, code 3
				$this->pageNotFound = 3;
				$this->id = $domainStartPage;
				// re-get the page and rootline if the id was not found.
				$this->getPageAndRootline();
			}
		}
	}

	/**
	 * Sets sys_page where-clause
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function setSysPageWhereClause() {
		$this->sys_page->where_hid_del .= ' AND pages.doktype<200';
		$this->sys_page->where_groupAccess = $this->sys_page->getMultipleGroupsWhereClause('pages.fe_group', 'pages');
	}

	/**
	 * Looking up a domain record based on HTTP_HOST
	 *
	 * @param boolean $recursive If set, it looks "recursively" meaning that a domain like "123.456.typo3.com" would find a domain record like "typo3.com" if "123.456.typo3.com" or "456.typo3.com" did not exist.
	 * @return integer Returns the page id of the page where the domain record was found.
	 * @access private
	 * @todo Define visibility
	 */
	public function findDomainRecord($recursive = 0) {
		if ($recursive) {
			$host = explode('.', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST'));
			while (count($host)) {
				$pageUid = $this->sys_page->getDomainStartPage(implode('.', $host), \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME'), \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
				if ($pageUid) {
					return $pageUid;
				} else {
					array_shift($host);
				}
			}
			return $pageUid;
		} else {
			return $this->sys_page->getDomainStartPage(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST'), \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME'), \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
		}
	}

	/**
	 * Page unavailable handler for use in frontend plugins from extensions.
	 *
	 * @param string $reason Reason text
	 * @param string $header HTTP header to send
	 * @return void Function exits.
	 * @todo Define visibility
	 */
	public function pageUnavailableAndExit($reason = '', $header = '') {
		$header = $header ? $header : $this->TYPO3_CONF_VARS['FE']['pageUnavailable_handling_statheader'];
		$this->pageUnavailableHandler($this->TYPO3_CONF_VARS['FE']['pageUnavailable_handling'], $header, $reason);
		die;
	}

	/**
	 * Page-not-found handler for use in frontend plugins from extensions.
	 *
	 * @param string $reason Reason text
	 * @param string $header HTTP header to send
	 * @return void Function exits.
	 * @todo Define visibility
	 */
	public function pageNotFoundAndExit($reason = '', $header = '') {
		$header = $header ? $header : $this->TYPO3_CONF_VARS['FE']['pageNotFound_handling_statheader'];
		$this->pageNotFoundHandler($this->TYPO3_CONF_VARS['FE']['pageNotFound_handling'], $header, $reason);
		die;
	}

	/**
	 * Checks whether the pageUnavailableHandler should be used. To be used, pageUnavailable_handling must be set
	 * and devIPMask must not match the current visitor's IP address.
	 *
	 * @return boolean TRUE/FALSE whether the pageUnavailable_handler should be used.
	 * @todo Define visibility
	 */
	public function checkPageUnavailableHandler() {
		if (
			$this->TYPO3_CONF_VARS['FE']['pageUnavailable_handling']
			&& !\TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(
				\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),
				$this->TYPO3_CONF_VARS['SYS']['devIPmask']
			)
		) {
			$checkPageUnavailableHandler = TRUE;
		} else {
			$checkPageUnavailableHandler = FALSE;
		}
		return $checkPageUnavailableHandler;
	}

	/**
	 * Page unavailable handler. Acts a wrapper for the pageErrorHandler method.
	 *
	 * @param mixed $code Which type of handling; If a true PHP-boolean or TRUE then a \TYPO3\CMS\Core\Messaging\ErrorpageMessage is outputted. If integer an error message with that number is shown. Otherwise the $code value is expected to be a "Location:" header value.
	 * @param string $header If set, this is passed directly to the PHP function, header()
	 * @param string $reason If set, error messages will also mention this as the reason for the page-not-found.
	 * @return void (The function exits!)
	 * @todo Define visibility
	 */
	public function pageUnavailableHandler($code, $header, $reason) {
		$this->pageErrorHandler($code, $header, $reason);
	}

	/**
	 * Page not found handler. Acts a wrapper for the pageErrorHandler method.
	 *
	 * @param mixed $code Which type of handling; If a true PHP-boolean or TRUE then a \TYPO3\CMS\Core\Messaging\ErrorpageMessage is outputted. If integer an error message with that number is shown. Otherwise the $code value is expected to be a "Location:" header value.
	 * @param string $header If set, this is passed directly to the PHP function, header()
	 * @param string $reason If set, error messages will also mention this as the reason for the page-not-found.
	 * @return void (The function exits!)
	 * @todo Define visibility
	 */
	public function pageNotFoundHandler($code, $header = '', $reason = '') {
		$this->pageErrorHandler($code, $header, $reason);
	}

	/**
	 * Generic error page handler.
	 * Exits.
	 *
	 * @param mixed $code Which type of handling; If a true PHP-boolean or TRUE then a \TYPO3\CMS\Core\Messaging\ErrorpageMessage is outputted. If integer an error message with that number is shown. Otherwise the $code value is expected to be a "Location:" header value.
	 * @param string $header If set, this is passed directly to the PHP function, header()
	 * @param string $reason If set, error messages will also mention this as the reason for the page-not-found.
	 * @return void (The function exits!)
	 * @todo Define visibility
	 */
	public function pageErrorHandler($code, $header = '', $reason = '') {
		// Issue header in any case:
		if ($header) {
			$headerArr = preg_split('/\\r|\\n/', $header, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($headerArr as $header) {
				header($header);
			}
		}
		// Create response:
		// Simply boolean; Just shows TYPO3 error page with reason:
		if (gettype($code) == 'boolean' || !strcmp($code, 1)) {
			$title = 'Page Not Found';
			$message = 'The page did not exist or was inaccessible.' . ($reason ? ' Reason: ' . htmlspecialchars($reason) : '');
			$messagePage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\ErrorpageMessage', $message, $title);
			$messagePage->output();
			die;
		} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($code, 'USER_FUNCTION:')) {
			$funcRef = trim(substr($code, 14));
			$params = array(
				'currentUrl' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'),
				'reasonText' => $reason,
				'pageAccessFailureReasons' => $this->getPageAccessFailureReasons()
			);
			echo \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
		} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($code, 'READFILE:')) {
			$readFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(trim(substr($code, 9)));
			if (@is_file($readFile)) {
				$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($readFile);
				$fileContent = str_replace('###CURRENT_URL###', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'), $fileContent);
				$fileContent = str_replace('###REASON###', htmlspecialchars($reason), $fileContent);
				echo $fileContent;
			} else {
				throw new \RuntimeException('Configuration Error: 404 page "' . $readFile . '" could not be found.', 1294587214);
			}
		} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($code, 'REDIRECT:')) {
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect(substr($code, 9));
		} elseif (strlen($code)) {
			// Check if URL is relative
			$url_parts = parse_url($code);
			if ($url_parts['host'] == '') {
				$url_parts['host'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_HOST');
				if ($code[0] === '/') {
					$code = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . $code;
				} else {
					$code = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR') . $code;
				}
				$checkBaseTag = FALSE;
			} else {
				$checkBaseTag = TRUE;
			}
			// Check recursion
			if ($code == \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')) {
				if ($reason == '') {
					$reason = 'Page cannot be found.';
				}
				$reason .= LF . LF . 'Additionally, ' . $code . ' was not found while trying to retrieve the error document.';
				throw new \RuntimeException(nl2br(htmlspecialchars($reason)), 1294587215);
			}
			// Prepare headers
			$headerArr = array(
				'User-agent: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT'),
				'Referer: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')
			);
			$res = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($code, 1, $headerArr);
			// Header and content are separated by an empty line
			list($header, $content) = explode(CRLF . CRLF, $res, 2);
			$content .= CRLF;
			if (FALSE === $res) {
				// Last chance -- redirect
				\TYPO3\CMS\Core\Utility\HttpUtility::redirect($code);
			} else {
				// Forward these response headers to the client
				$forwardHeaders = array(
					'Content-Type:'
				);
				$headerArr = preg_split('/\\r|\\n/', $header, -1, PREG_SPLIT_NO_EMPTY);
				foreach ($headerArr as $header) {
					foreach ($forwardHeaders as $h) {
						if (preg_match('/^' . $h . '/', $header)) {
							header($header);
						}
					}
				}
				// Put <base> if necesary
				if ($checkBaseTag) {
					// If content already has <base> tag, we do not need to do anything
					if (FALSE === stristr($content, '<base ')) {
						// Generate href for base tag
						$base = $url_parts['scheme'] . '://';
						if ($url_parts['user'] != '') {
							$base .= $url_parts['user'];
							if ($url_parts['pass'] != '') {
								$base .= ':' . $url_parts['pass'];
							}
							$base .= '@';
						}
						$base .= $url_parts['host'];
						// Add path portion skipping possible file name
						$base .= preg_replace('/(.*\\/)[^\\/]*/', '${1}', $url_parts['path']);
						// Put it into content (generate also <head> if necessary)
						$replacement = LF . '<base href="' . htmlentities($base) . '" />' . LF;
						if (stristr($content, '<head>')) {
							$content = preg_replace('/(<head>)/i', '\\1' . $replacement, $content);
						} else {
							$content = preg_replace('/(<html[^>]*>)/i', '\\1<head>' . $replacement . '</head>', $content);
						}
					}
				}
				// Output the content
				echo $content;
			}
		} else {
			$title = 'Page Not Found';
			$message = $reason ? 'Reason: ' . htmlspecialchars($reason) : 'Page cannot be found.';
			$messagePage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\ErrorpageMessage', $message, $title);
			$messagePage->output();
		}
		die;
	}

	/**
	 * Fetches the integer page id for a page alias.
	 * Looks if ->id is not an integer and if so it will search for a page alias and if found the page uid of that page is stored in $this->id
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function checkAndSetAlias() {
		if ($this->id && !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->id)) {
			$aid = $this->sys_page->getPageIdFromAlias($this->id);
			if ($aid) {
				$this->id = $aid;
			} else {
				$this->pageNotFound = 4;
			}
		}
	}

	/**
	 * Merging values into the global $_GET
	 *
	 * @param array $GET_VARS Array of key/value pairs that will be merged into the current GET-vars. (Non-escaped values)
	 * @return void
	 * @todo Define visibility
	 */
	public function mergingWithGetVars($GET_VARS) {
		if (is_array($GET_VARS)) {
			// Getting $_GET var, unescaped.
			$realGet = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
			if (!is_array($realGet)) {
				$realGet = array();
			}
			// Merge new values on top:
			$realGet = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($realGet, $GET_VARS);
			// Write values back to $_GET:
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GETset($realGet);
			// Setting these specifically (like in the init-function):
			if (isset($GET_VARS['type'])) {
				$this->type = intval($GET_VARS['type']);
			}
			if (isset($GET_VARS['cHash'])) {
				$this->cHash = $GET_VARS['cHash'];
			}
			if (isset($GET_VARS['jumpurl'])) {
				$this->jumpurl = $GET_VARS['jumpurl'];
			}
			if (isset($GET_VARS['MP'])) {
				$this->MP = $this->TYPO3_CONF_VARS['FE']['enable_mount_pids'] ? $GET_VARS['MP'] : '';
			}
			if (isset($GET_VARS['no_cache']) && $GET_VARS['no_cache']) {
				$this->set_no_cache('no_cache is requested via GET parameter');
			}
		}
	}

	/********************************************
	 *
	 * Template and caching related functions.
	 *
	 *******************************************/
	/**
	 * Calculates a hash string based on additional parameters in the url.
	 *
	 * Calculated hash is stored in $this->cHash_array.
	 * This is used to cache pages with more parameters than just id and type.
	 *
	 * @return void
	 * @see reqCHash()
	 * @todo Define visibility
	 */
	public function makeCacheHash() {
		// No need to test anything if caching was already disabled.
		if ($this->no_cache && !$this->TYPO3_CONF_VARS['FE']['pageNotFoundOnCHashError']) {
			return;
		}
		$GET = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
		if ($this->cHash && is_array($GET)) {
			$this->cHash_array = $this->cacheHash->getRelevantParameters(\TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $GET));
			$cHash_calc = $this->cacheHash->calculateCacheHash($this->cHash_array);
			if ($cHash_calc != $this->cHash) {
				if ($this->TYPO3_CONF_VARS['FE']['pageNotFoundOnCHashError']) {
					$this->pageNotFoundAndExit('Request parameters could not be validated (&cHash comparison failed)');
				} else {
					$this->disableCache();
					$GLOBALS['TT']->setTSlogMessage('The incoming cHash "' . $this->cHash . '" and calculated cHash "' . $cHash_calc . '" did not match, so caching was disabled. The fieldlist used was "' . implode(',', array_keys($this->cHash_array)) . '"', 2);
				}
			}
		} elseif (is_array($GET)) {
			// No cHash is set, check if that is correct
			if ($this->cacheHash->doParametersRequireCacheHash(\TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $GET))) {
				$this->reqCHash();
			}
		}
	}

	/**
	 * Will disable caching if the cHash value was not set.
	 * This function should be called to check the _existence_ of "&cHash" whenever a plugin generating cachable output is using extra GET variables. If there _is_ a cHash value the validation of it automatically takes place in makeCacheHash() (see above)
	 *
	 * @return void
	 * @see makeCacheHash(), tslib_pibase::pi_cHashCheck()
	 * @todo Define visibility
	 */
	public function reqCHash() {
		if (!$this->cHash) {
			if ($this->TYPO3_CONF_VARS['FE']['pageNotFoundOnCHashError']) {
				if ($this->tempContent) {
					$this->clearPageCacheContent();
				}
				$this->pageNotFoundAndExit('Request parameters could not be validated (&cHash empty)');
			} else {
				$this->disableCache();
				$GLOBALS['TT']->setTSlogMessage('TSFE->reqCHash(): No &cHash parameter was sent for GET vars though required so caching is disabled', 2);
			}
		}
	}

	/**
	 * Initialize the TypoScript template parser
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function initTemplate() {
		$this->tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
		$this->tmpl->init();
		$this->tmpl->tt_track = $this->beUserLogin ? 1 : 0;
	}

	/**
	 * See if page is in cache and get it if so
	 * Stores the page content in $this->content if something is found.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function getFromCache() {
		if (!$this->no_cache) {
			$cc = $this->tmpl->getCurrentPageData();
			if (!is_array($cc)) {
				$key = $this->id . '::' . $this->MP;
				// Returns TRUE if the lock is active now
				$isLocked = $this->acquirePageGenerationLock($this->pagesection_lockObj, $key);
				if (!$isLocked) {
					// Lock is no longer active, the data in "cache_pagesection" is now ready
					$cc = $this->tmpl->getCurrentPageData();
					if (is_array($cc)) {
						// Release the lock
						$this->releasePageGenerationLock($this->pagesection_lockObj);
					}
				}
			}
			if (is_array($cc)) {
				// BE CAREFUL to change the content of the cc-array. This array is serialized and an md5-hash based on this is used for caching the page.
				// If this hash is not the same in here in this section and after page-generation, then the page will not be properly cached!
				// This array is an identification of the template. If $this->all is empty it's because the template-data is not cached, which it must be.
				$cc = $this->tmpl->matching($cc);
				ksort($cc);
				$this->all = $cc;
			}
			unset($cc);
		}
		// clearing the content-variable, which will hold the pagecontent
		$this->content = '';
		// Unsetting the lowlevel config
		unset($this->config);
		$this->cacheContentFlag = 0;
		// Look for page in cache only if caching is not disabled and if a shift-reload is not sent to the server.
		if (!$this->no_cache && !$this->headerNoCache()) {
			$lockHash = $this->getLockHash();
			if ($this->all) {
				$this->newHash = $this->getHash();
				$GLOBALS['TT']->push('Cache Row', '');
				$row = $this->getFromCache_queryRow();
				if (!is_array($row)) {
					$isLocked = $this->acquirePageGenerationLock($this->pages_lockObj, $lockHash);
					if (!$isLocked) {
						// Lock is no longer active, the data in "cache_pages" is now ready
						$row = $this->getFromCache_queryRow();
						if (is_array($row)) {
							// Release the lock
							$this->releasePageGenerationLock($this->pages_lockObj);
						}
					}
				}
				if (is_array($row)) {
					// Release this lock
					$this->releasePageGenerationLock($this->pages_lockObj);
					// Call hook when a page is retrieved from cache:
					if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache'])) {
						$_params = array('pObj' => &$this, 'cache_pages_row' => &$row);
						foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache'] as $_funcRef) {
							\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
						}
					}
					// Fetches the lowlevel config stored with the cached data
					$this->config = (array) unserialize($row['cache_data']);
					// Getting the content
					$this->content = $row['content'];
					// Flag for temp content
					$this->tempContent = $row['temp_content'];
					// Setting flag, so we know, that some cached content has been loaded
					$this->cacheContentFlag = 1;
					$this->cacheExpires = $row['expires'];
					if ($this->TYPO3_CONF_VARS['FE']['debug'] || isset($this->config['config']['debug']) && $this->config['config']['debug']) {
						$dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
						$timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
						$this->content .= LF . '<!-- Cached page generated ' . date(($dateFormat . ' ' . $timeFormat), $row['tstamp']) . '. Expires ' . Date(($dateFormat . ' ' . $timeFormat), $row['expires']) . ' -->';
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
	 * @return array Cached row, if any. Otherwise void.
	 * @todo Define visibility
	 */
	public function getFromCache_queryRow() {
		$GLOBALS['TT']->push('Cache Query', '');
		$row = $this->pageCache->get($this->newHash);
		$GLOBALS['TT']->pull();
		return $row;
	}

	/**
	 * Detecting if shift-reload has been clicked
	 * Will not be called if re-generation of page happens by other reasons (for instance that the page is not in cache yet!)
	 * Also, a backend user MUST be logged in for the shift-reload to be detected due to DoS-attack-security reasons.
	 *
	 * @return boolean If shift-reload in client browser has been clicked, disable getting cached page (and regenerate it).
	 * @todo Define visibility
	 */
	public function headerNoCache() {
		$disableAcquireCacheData = FALSE;
		if ($this->beUserLogin) {
			if (strtolower($_SERVER['HTTP_CACHE_CONTROL']) === 'no-cache' || strtolower($_SERVER['HTTP_PRAGMA']) === 'no-cache') {
				$disableAcquireCacheData = TRUE;
			}
		}
		// Call hook for possible by-pass of requiring of page cache (for recaching purpose)
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache'])) {
			$_params = array('pObj' => &$this, 'disableAcquireCacheData' => &$disableAcquireCacheData);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		return $disableAcquireCacheData;
	}

	/**
	 * Calculates the cache-hash
	 * This hash is unique to the template, the variables ->id, ->type, ->gr_list (list of groups), ->MP (Mount Points) and cHash array
	 * Used to get and later store the cached data.
	 *
	 * @return string MD5 hash of $this->hash_base which is a serialized version of there variables.
	 * @access private
	 * @see getFromCache(), getLockHash()
	 * @todo Define visibility
	 */
	public function getHash() {
		$this->hash_base = $this->createHashBase(FALSE);
		return md5($this->hash_base);
	}

	/**
	 * Calculates the lock-hash
	 * This hash is unique to the above hash, except that it doesn't contain the template information in $this->all.
	 *
	 * @return string MD5 hash
	 * @access private
	 * @see getFromCache(), getHash()
	 * @todo Define visibility
	 */
	public function getLockHash() {
		$lockHash = $this->createHashBase(TRUE);
		return md5($lockHash);
	}

	/**
	 * Calculates the cache-hash (or the lock-hash)
	 * This hash is unique to the template,
	 * the variables ->id, ->type, ->gr_list (list of groups),
	 * ->MP (Mount Points) and cHash array
	 * Used to get and later store the cached data.
	 *
	 * @param boolean $createLockHashBase Whether to create the lock hash, which doesn't contain the "this->all" (the template information)
	 * @return string the serialized hash base
	 */
	protected function createHashBase($createLockHashBase = FALSE) {
		$hashParameters = array(
			'id' => intval($this->id),
			'type' => intval($this->type),
			'gr_list' => (string) $this->gr_list,
			'MP' => (string) $this->MP,
			'cHash' => $this->cHash_array,
			'domainStartPage' => $this->domainStartPage
		);
		// Include the template information if we shouldn't create a lock hash
		if (!$createLockHashBase) {
			$hashParameters['all'] = $this->all;
		}
		// Call hook to influence the hash calculation
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'])) {
			$_params = array(
				'hashParameters' => &$hashParameters,
				'createLockHashBase' => $createLockHashBase
			);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		return serialize($hashParameters);
	}

	/**
	 * Checks if config-array exists already but if not, gets it
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function getConfigArray() {
		$setStatPageName = FALSE;
		// If config is not set by the cache (which would be a major mistake somewhere) OR if INTincScripts-include-scripts have been registered, then we must parse the template in order to get it
		if (!is_array($this->config) || is_array($this->config['INTincScript']) || $this->forceTemplateParsing) {
			$GLOBALS['TT']->push('Parse template', '');
			// Force parsing, if set?:
			$this->tmpl->forceTemplateParsing = $this->forceTemplateParsing;
			// Start parsing the TS template. Might return cached version.
			$this->tmpl->start($this->rootLine);
			$GLOBALS['TT']->pull();
			if ($this->tmpl->loaded) {
				$GLOBALS['TT']->push('Setting the config-array', '');
				// toplevel - objArrayName
				$this->sPre = $this->tmpl->setup['types.'][$this->type];
				$this->pSetup = $this->tmpl->setup[$this->sPre . '.'];
				if (!is_array($this->pSetup)) {
					$message = 'The page is not configured! [type=' . $this->type . '][' . $this->sPre . '].';
					if ($this->checkPageUnavailableHandler()) {
						$this->pageUnavailableAndExit($message);
					} else {
						$explanation = 'This means that there is no TypoScript object of type PAGE with typeNum=' . $this->type . ' configured.';
						\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
						throw new \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException($message . ' ' . $explanation, 1294587217);
					}
				} else {
					$this->config['config'] = array();
					// Filling the config-array, first with the main "config." part
					if (is_array($this->tmpl->setup['config.'])) {
						$this->config['config'] = $this->tmpl->setup['config.'];
					}
					// override it with the page/type-specific "config."
					if (is_array($this->pSetup['config.'])) {
						$this->config['config'] = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($this->config['config'], $this->pSetup['config.']);
					}
					if ($this->config['config']['typolinkEnableLinksAcrossDomains']) {
						$this->config['config']['typolinkCheckRootline'] = TRUE;
					}
					// Set default values for removeDefaultJS and inlineStyle2TempFile so CSS and JS are externalized if compatversion is higher than 4.0
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('4.0')) {
						if (!isset($this->config['config']['removeDefaultJS'])) {
							$this->config['config']['removeDefaultJS'] = 'external';
						}
						if (!isset($this->config['config']['inlineStyle2TempFile'])) {
							$this->config['config']['inlineStyle2TempFile'] = 1;
						}
					}
					if (!isset($this->config['config']['compressJs'])) {
						$this->config['config']['compressJs'] = 0;
					}
					// Processing for the config_array:
					$this->config['rootLine'] = $this->tmpl->rootLine;
					$this->config['mainScript'] = trim($this->config['config']['mainScript']) ? trim($this->config['config']['mainScript']) : 'index.php';
					// Class for render Header and Footer parts
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
				if ($this->checkPageUnavailableHandler()) {
					$this->pageUnavailableAndExit('No TypoScript template found!');
				} else {
					$message = 'No TypoScript template found!';
					\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
					throw new \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException($message, 1294587218);
				}
			}
		}
		// Initialize charset settings etc.
		$this->initLLvars();
		// No cache
		// Set $this->no_cache TRUE if the config.no_cache value is set!
		if ($this->config['config']['no_cache']) {
			$this->set_no_cache('config.no_cache is set');
		}
		// Merge GET with defaultGetVars
		if (!empty($this->config['config']['defaultGetVars.'])) {
			$modifiedGetVars = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(\TYPO3\CMS\Core\Utility\GeneralUtility::removeDotsFromTS($this->config['config']['defaultGetVars.']), \TYPO3\CMS\Core\Utility\GeneralUtility::_GET());
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GETset($modifiedGetVars);
		}
		// Hook for postProcessing the configuration array
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'])) {
			$params = array('config' => &$this->config['config']);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'] as $funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($funcRef, $params, $this);
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
	 * Get the compressed $GLOBALS['TCA'] array for use in the front-end
	 * A compressed $GLOBALS['TCA'] array holds only the ctrl- and feInterface-part for each table.
	 * But the column-definitions are omitted in order to save some memory and be more efficient.
	 * Operates on the global variable, $TCA
	 *
	 * @return void
	 * @deprecated since 6,1, will be removed in two versions.
	 */
	public function getCompressedTCarray() {
		// Full TCA is always loaded during bootstrap in FE, this method is obsolete.
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
	}

	/**
	 * Includes TCA definitions from loaded extensions (ext_table.php files).
	 * Normally in the frontend only a part of the global $TCA array is loaded,
	 * namely the "ctrl" part. Thus it doesn't take up too much memory. To load
	 * full TCA for the table, use \TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA($tableName)
	 * after calling this function.
	 *
	 * @param integer $TCAloaded Probably, keep hands of this value. Just don't set it.
	 * @return void
	 * @see getCompressedTCarray()
	 * @deprecated since 6.1, will be removed in two versions. Obsolete in regular frontend, eid scripts should use \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA()
	 */
	public function includeTCA($TCAloaded = 1) {
		// Full TCA is always loaded during bootstrap in FE, this method is obsolete.
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

		// Compatibility layer:
		// The if below is NOT true in usual frontend (non eid) context, TCA is loaded by bootstrap.
		// If an eid script calls this method to load TCA, use
		// \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA() instead.
		if (!isset($GLOBALS['TCA']['pages'])) {
			\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->loadCachedTca();
		}
	}

	/**
	 * Setting the language key that will be used by the current page.
	 * In this function it should be checked, 1) that this language exists, 2) that a page_overlay_record exists, .. and if not the default language, 0 (zero), should be set.
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function settingLanguage() {
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess'])) {
			$_params = array();
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		// Get values from TypoScript:
		$this->sys_language_uid = ($this->sys_language_content = intval($this->config['config']['sys_language_uid']));
		list($this->sys_language_mode, $sys_language_content) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $this->config['config']['sys_language_mode']);
		$this->sys_language_contentOL = $this->config['config']['sys_language_overlay'];
		// If sys_language_uid is set to another language than default:
		if ($this->sys_language_uid > 0) {
			// check whether a shortcut is overwritten by a translated page
			// we can only do this now, as this is the place where we get
			// to know about translations
			$this->checkTranslatedShortcut();
			// Request the overlay record for the sys_language_uid:
			$olRec = $this->sys_page->getPageOverlay($this->id, $this->sys_language_uid);
			if (!count($olRec)) {
				// If no OL record exists and a foreign language is asked for...
				if ($this->sys_language_uid) {
					// If requested translation is not available:
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::hideIfNotTranslated($this->page['l18n_cfg'])) {
						$this->pageNotFoundAndExit('Page is not available in the requested language.');
					} else {
						switch ((string) $this->sys_language_mode) {
						case 'strict':
							$this->pageNotFoundAndExit('Page is not available in the requested language (strict).');
							break;
						case 'content_fallback':
							$fallBackOrder = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $sys_language_content);
							foreach ($fallBackOrder as $orderValue) {
								if (!strcmp($orderValue, '0') || count($this->sys_page->getPageOverlay($this->id, $orderValue))) {
									$this->sys_language_content = $orderValue;
									// Setting content uid (but leaving the sys_language_uid)
									break;
								}
							}
							break;
						case 'ignore':
							$this->sys_language_content = $this->sys_language_uid;
							break;
						default:
							// Default is that everything defaults to the default language...
							$this->sys_language_uid = ($this->sys_language_content = 0);
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
		if ((!$this->sys_language_uid || !$this->sys_language_content) && $this->page['l18n_cfg'] & 1) {
			$message = 'Page is not available in default language.';
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($message, 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			$this->pageNotFoundAndExit($message);
		}
		$this->updateRootLinesWithTranslations();
		// Finding the ISO code:
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables') && $this->sys_language_content) {
			// using sys_language_content because the ISO code only (currently) affect content selection from FlexForms - which should follow "sys_language_content"
			$sys_language_row = $this->sys_page->getRawRecord('sys_language', $this->sys_language_content, 'static_lang_isocode');
			if (is_array($sys_language_row) && $sys_language_row['static_lang_isocode']) {
				$stLrow = $this->sys_page->getRawRecord('static_languages', $sys_language_row['static_lang_isocode'], 'lg_iso_2');
				$this->sys_language_isocode = $stLrow['lg_iso_2'];
			}
		}
		// Setting softMergeIfNotBlank:
		$table_fields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->config['config']['sys_language_softMergeIfNotBlank'], 1);
		foreach ($table_fields as $TF) {
			list($tN, $fN) = explode(':', $TF);
			$GLOBALS['TCA'][$tN]['columns'][$fN]['l10n_mode'] = 'mergeIfNotBlank';
		}
		// Setting softExclude:
		$table_fields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->config['config']['sys_language_softExclude'], 1);
		foreach ($table_fields as $TF) {
			list($tN, $fN) = explode(':', $TF);
			$GLOBALS['TCA'][$tN]['columns'][$fN]['l10n_mode'] = 'exclude';
		}
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess'])) {
			$_params = array();
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Updating content of the two rootLines IF the language key is set!
	 */
	protected function updateRootLinesWithTranslations() {
		if ($this->sys_language_uid && is_array($this->tmpl->rootLine)) {
			$this->tmpl->rootLine = array_reverse($this->sys_page->getRootLine($this->id, $this->MP));
		}
		if ($this->sys_language_uid && is_array($this->rootLine)) {
			$this->rootLine = $this->sys_page->getRootLine($this->id, $this->MP);
		}
	}

	/**
	 * Setting locale for frontend rendering
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function settingLocale() {
		// Setting locale
		if ($this->config['config']['locale_all']) {
			// There's a problem that PHP parses float values in scripts wrong if the
			// locale LC_NUMERIC is set to something with a comma as decimal point
			// Do we set all except LC_NUMERIC
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
				$GLOBALS['TT']->setTSlogMessage('Locale "' . htmlspecialchars($this->config['config']['locale_all']) . '" not found.', 3);
			}
		}
	}

	/**
	 * Checks whether a translated shortcut page has a different shortcut
	 * target than the original language page.
	 * If that is the case, things get corrected to follow that alternative
	 * shortcut
	 *
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	protected function checkTranslatedShortcut() {
		if (!is_null($this->originalShortcutPage)) {
			$originalShortcutPageOverlay = $this->sys_page->getPageOverlay($this->originalShortcutPage['uid'], $this->sys_language_uid);
			if (!empty($originalShortcutPageOverlay['shortcut']) && $originalShortcutPageOverlay['shortcut'] != $this->id) {
				// the translation of the original shortcut page has a different shortcut target!
				// set the correct page and id
				$shortcut = $this->getPageShortcut($originalShortcutPageOverlay['shortcut'], $originalShortcutPageOverlay['shortcut_mode'], $originalShortcutPageOverlay['uid']);
				$this->id = ($this->contentPid = $shortcut['uid']);
				$this->page = $this->sys_page->getPage($this->id);
				// Fix various effects on things like menus f.e.
				$this->fetch_the_id();
				$this->tmpl->rootLine = array_reverse($this->rootLine);
			}
		}
	}

	/**
	 * Handle data submission
	 *
	 * @return void
	 */
	public function handleDataSubmission() {
		// Check Submission of data.
		// This is done at this point, because we need the config values
		switch ($this->checkDataSubmission()) {
		case 'email':
			$this->sendFormmail();
			break;
		}
	}

	/**
	 * Checks if any email-submissions
	 *
	 * @return string "email" if a formmail has been sent, "" if none.
	 */
	protected function checkDataSubmission() {
		$ret = '';
		$formtype_mail = isset($_POST['formtype_mail']) || isset($_POST['formtype_mail_x']);
		if ($formtype_mail) {
			$refInfo = parse_url(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_REFERER'));
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY') == $refInfo['host'] || $this->TYPO3_CONF_VARS['SYS']['doNotCheckReferer']) {
				if ($this->locDataCheck($_POST['locationData'])) {
					if ($formtype_mail) {
						$ret = 'email';
					}
					$GLOBALS['TT']->setTSlogMessage('"Check Data Submission": Return value: ' . $ret, 0);
					return $ret;
				}
			} else {
				$GLOBALS['TT']->setTSlogMessage('"Check Data Submission": HTTP_HOST and REFERER HOST did not match when processing submitted formdata!', 3);
			}
		}
		// Hook for processing data submission to extensions:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission'])) {
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission'] as $_classRef) {
				$_procObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->checkDataSubmission($this);
			}
		}
		return $ret;
	}

	/**
	 * Processes submitted user data (obsolete "Frontend TCE")
	 *
	 * @return void
	 * @see tslib_feTCE
	 * @deprecated since 6.0, will be removed two versions later
	 * @todo Define visibility
	 */
	public function fe_tce() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
	}

	/**
	 * Checks if a formmail submission can be sent as email
	 *
	 * @param string $locationData The input from $_POST['locationData']
	 * @return void
	 * @access private
	 * @see checkDataSubmission()
	 * @todo Define visibility
	 */
	public function locDataCheck($locationData) {
		$locData = explode(':', $locationData);
		if (!$locData[1] || $this->sys_page->checkRecord($locData[1], $locData[2], 1)) {
			// $locData[1] -check means that a record is checked only if the locationData has a value for a record else than the page.
			if (count($this->sys_page->getPage($locData[0]))) {
				return 1;
			} else {
				$GLOBALS['TT']->setTSlogMessage('LocationData Error: The page pointed to by location data (' . $locationData . ') was not accessible.', 2);
			}
		} else {
			$GLOBALS['TT']->setTSlogMessage('LocationData Error: Location data (' . $locationData . ') record pointed to was not accessible.', 2);
		}
	}

	/**
	 * Sends the emails from the formmail content object.
	 *
	 * @return void
	 * @see checkDataSubmission()
	 */
	protected function sendFormmail() {
		/** @var $formmail \TYPO3\CMS\Frontend\Controller\DataSubmissionController */
		$formmail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Controller\\DataSubmissionController');
		$EMAIL_VARS = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST();
		$locationData = $EMAIL_VARS['locationData'];
		unset($EMAIL_VARS['locationData']);
		unset($EMAIL_VARS['formtype_mail'], $EMAIL_VARS['formtype_mail_x'], $EMAIL_VARS['formtype_mail_y']);
		$integrityCheck = $this->TYPO3_CONF_VARS['FE']['strictFormmail'];
		if (!$this->TYPO3_CONF_VARS['FE']['secureFormmail']) {
			// Check recipient field:
			// These two fields are the ones which contain recipient addresses that can be misused to send mail from foreign servers.
			$encodedFields = explode(',', 'recipient, recipient_copy');
			foreach ($encodedFields as $fieldKey) {
				if (strlen($EMAIL_VARS[$fieldKey])) {
					// Decode...
					if ($res = $this->codeString($EMAIL_VARS[$fieldKey], TRUE)) {
						$EMAIL_VARS[$fieldKey] = $res;
					} elseif ($integrityCheck) {
						// Otherwise abort:
						$GLOBALS['TT']->setTSlogMessage('"Formmail" discovered a field (' . $fieldKey . ') which could not be decoded to a valid string. Sending formmail aborted due to security reasons!', 3);
						return FALSE;
					} else {
						$GLOBALS['TT']->setTSlogMessage('"Formmail" discovered a field (' . $fieldKey . ') which could not be decoded to a valid string. The security level accepts this, but you should consider a correct coding though!', 2);
					}
				}
			}
		} else {
			$locData = explode(':', $locationData);
			$record = $this->sys_page->checkRecord($locData[1], $locData[2], 1);
			$EMAIL_VARS['recipient'] = $record['subheader'];
			$EMAIL_VARS['recipient_copy'] = $this->extractRecipientCopy($record['bodytext']);
		}
		// Hook for preprocessing of the content for formmails:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sendFormmail-PreProcClass'])) {
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sendFormmail-PreProcClass'] as $_classRef) {
				$_procObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$EMAIL_VARS = $_procObj->sendFormmail_preProcessVariables($EMAIL_VARS, $this);
			}
		}
		$formmail->start($EMAIL_VARS);
		$formmail->sendtheMail();
		$GLOBALS['TT']->setTSlogMessage('"Formmail" invoked, sending mail to ' . $EMAIL_VARS['recipient'], 0);
	}

	/**
	 * Extracts the value of recipient copy field from a formmail CE bodytext
	 *
	 * @param string $bodytext The content of the related bodytext field
	 * @return string The value of the recipient_copy field, or an empty string
	 * @todo Define visibility
	 */
	public function extractRecipientCopy($bodytext) {
		$recipient_copy = '';
		$fdef = array();
		//|recipient_copy=hidden|karsten@localhost.localdomain
		preg_match('/^[\\s]*\\|[\\s]*recipient_copy[\\s]*=[\\s]*hidden[\\s]*\\|(.*)$/m', $bodytext, $fdef);
		$recipient_copy = !empty($fdef[1]) ? $fdef[1] : '';
		return $recipient_copy;
	}

	/**
	 * Sets the jumpurl for page type "External URL"
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function setExternalJumpUrl() {
		if ($extUrl = $this->sys_page->getExtURL($this->page, $this->config['config']['disablePageExternalUrl'])) {
			$this->jumpurl = $extUrl;
			\TYPO3\CMS\Core\Utility\GeneralUtility::_GETset(\TYPO3\CMS\Core\Utility\GeneralUtility::hmac($this->jumpurl, 'jumpurl'), 'juHash');
		}
	}

	/**
	 * Check the jumpUrl referer if required
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function checkJumpUrlReferer() {
		if (strlen($this->jumpurl) && !$this->TYPO3_CONF_VARS['SYS']['doNotCheckReferer']) {
			$referer = parse_url(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_REFERER'));
			if (isset($referer['host']) && !($referer['host'] == \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY'))) {
				unset($this->jumpurl);
			}
		}
	}

	/**
	 * Sends a header "Location" to jumpUrl, if jumpurl is set.
	 * Will exit if a location header is sent (for instance if jumpUrl was triggered)
	 *
	 * "jumpUrl" is a concept where external links are redirected from the index_ts.php script, which first logs the URL.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function jumpUrl() {
		if ($this->jumpurl) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('juSecure')) {
				$locationData = (string) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('locationData');
				// Need a type cast here because mimeType is optional!
				$mimeType = (string) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('mimeType');
				$hArr = array(
					$this->jumpurl,
					$locationData,
					$mimeType
				);
				$calcJuHash = \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(serialize($hArr));
				$juHash = (string) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('juHash');
				if ($juHash === $calcJuHash) {
					if ($this->locDataCheck($locationData)) {
						// 211002 - goes with cObj->filelink() rawurlencode() of filenames so spaces can be allowed.
						$this->jumpurl = rawurldecode($this->jumpurl);
						// Deny access to files that match TYPO3_CONF_VARS[SYS][fileDenyPattern] and whose parent directory is typo3conf/ (there could be a backup file in typo3conf/ which does not match against the fileDenyPattern)
						$absoluteFileName = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(\TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($this->jumpurl), FALSE);
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($absoluteFileName) && \TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern($absoluteFileName) && !\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($absoluteFileName, (PATH_site . 'typo3conf'))) {
							if (@is_file($absoluteFileName)) {
								$mimeType = $mimeType ? $mimeType : 'application/octet-stream';
								header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
								header('Content-Type: ' . $mimeType);
								header('Content-Disposition: attachment; filename="' . basename($absoluteFileName) . '"');
								readfile($absoluteFileName);
								die;
							} else {
								throw new \Exception('jumpurl Secure: "' . $this->jumpurl . '" was not a valid file!', 1294585193);
							}
						} else {
							throw new \Exception('jumpurl Secure: The requested file was not allowed to be accessed through jumpUrl (path or file not allowed)!', 1294585194);
						}
					} else {
						throw new \Exception('jumpurl Secure: locationData, ' . $locationData . ', was not accessible.', 1294585195);
					}
				} else {
					throw new \Exception('jumpurl Secure: Calculated juHash did not match the submitted juHash.', 1294585196);
				}
			} else {
				$TSConf = $this->getPagesTSconfig();
				if ($TSConf['TSFE.']['jumpUrl_transferSession']) {
					$uParts = parse_url($this->jumpurl);
					$params = '&FE_SESSION_KEY=' . rawurlencode(($this->fe_user->id . '-' . md5(($this->fe_user->id . '/' . $this->TYPO3_CONF_VARS['SYS']['encryptionKey']))));
					// Add the session parameter ...
					$this->jumpurl .= ($uParts['query'] ? '' : '?') . $params;
				}
				if ($TSConf['TSFE.']['jumpURL_HTTPStatusCode']) {
					switch (intval($TSConf['TSFE.']['jumpURL_HTTPStatusCode'])) {
					case 301:
						$statusCode = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_301;
						break;
					case 302:
						$statusCode = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_302;
						break;
					case 307:
						$statusCode = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_307;
						break;
					case 303:

					default:
						$statusCode = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303;
						break;
					}
				}

				$allowRedirect = FALSE;
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::hmac($this->jumpurl, 'jumpurl') === (string)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('juHash')) {
					$allowRedirect = TRUE;
				} elseif (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['jumpurlRedirectHandler'])) {
					foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['jumpurlRedirectHandler'] as $classReference) {
						$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classReference);
						$allowRedirectFromHook = FALSE;
						if (method_exists($hookObject, 'jumpurlRedirectHandler')) {
							$allowRedirectFromHook = $hookObject->jumpurlRedirectHandler($this->jumpurl, $this);
						}
						if ($allowRedirectFromHook === TRUE) {
							$allowRedirect = TRUE;
							break;
						}
					}
				}

				if ($allowRedirect) {
					\TYPO3\CMS\Core\Utility\HttpUtility::redirect($this->jumpurl, $statusCode);
				} else {
					throw new \Exception('jumpurl: Calculated juHash did not match the submitted juHash.', 1359987599);
				}
			}
		}
	}

	/**
	 * Sets the URL_ID_TOKEN in the internal var, $this->getMethodUrlIdToken
	 * This feature allows sessions to use a GET-parameter instead of a cookie.
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function setUrlIdToken() {
		if ($this->config['config']['ftu']) {
			$this->getMethodUrlIdToken = $this->TYPO3_CONF_VARS['FE']['get_url_id_token'];
		} else {
			$this->getMethodUrlIdToken = '';
		}
	}

	/**
	 * Calculates and sets the internal linkVars based upon the current
	 * $_GET parameters and the setting "config.linkVars".
	 *
	 * @return void
	 */
	public function calculateLinkVars() {
		$this->linkVars = '';
		$linkVars = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', (string) $this->config['config']['linkVars']);
		if (empty($linkVars)) {
			return;
		}
		$getData = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
		foreach ($linkVars as $linkVar) {
			$test = ($value = '');
			if (preg_match('/^(.*)\\((.+)\\)$/', $linkVar, $match)) {
				$linkVar = trim($match[1]);
				$test = trim($match[2]);
			}
			if ($linkVar === '' || !isset($getData[$linkVar])) {
				continue;
			}
			if (!is_array($getData[$linkVar])) {
				$temp = rawurlencode($getData[$linkVar]);
				if ($test !== '' && !\TYPO3\CMS\Frontend\Page\PageGenerator::isAllowedLinkVarValue($temp, $test)) {
					// Error: This value was not allowed for this key
					continue;
				}
				$value = '&' . $linkVar . '=' . $temp;
			} else {
				if ($test !== '' && strcmp('array', $test)) {
					// Error: This key must not be an array!
					continue;
				}
				$value = \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl($linkVar, $getData[$linkVar]);
			}
			$this->linkVars .= $value;
		}
	}

	/**
	 * Redirect to target page, if the current page is a Shortcut.
	 *
	 * If the current page is of type shortcut and accessed directly via its URL, this function redirects to the
	 * Shortcut target using a Location header.
	 *
	 * @return void If page is not a Shortcut, redirects and exits otherwise
	 */
	public function checkPageForShortcutRedirect() {
		if (!empty($this->originalShortcutPage) && $this->originalShortcutPage['doktype'] == \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT) {
			$this->calculateLinkVars();
			// instantiate tslib_content to generate the correct target URL
			/** @var $cObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
			$cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			$parameter = $this->page['uid'];
			$type = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('type');
			if ($type) {
				$parameter .= ',' . $type;
			}
			$redirectUrl = $cObj->typoLink_URL(array('parameter' => $parameter));
			// HTTP Status code header - dependent on shortcut type
			$redirectStatus = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_301;
			if ($this->originalShortcutPage['shortcut_mode'] == \TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE) {
				$redirectStatus = \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_307;
			}
			// redirect and exit
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl, $redirectStatus);
		}
	}

	/********************************************
	 *
	 * Page generation; cache handling
	 *
	 *******************************************/
	/**
	 * Returns TRUE if the page should be generated
	 * That is if jumpurl is not set and the cacheContentFlag is not set.
	 *
	 * @return boolean
	 * @todo Define visibility
	 */
	public function isGeneratePage() {
		return !$this->cacheContentFlag && !$this->jumpurl;
	}

	/**
	 * Temp cache content
	 * The temporary cache will expire after a few seconds (typ. 30) or will be cleared by the rendered page, which will also clear and rewrite the cache.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function tempPageCacheContent() {
		$this->tempContent = FALSE;
		if (!$this->no_cache) {
			$seconds = 30;
			$title = htmlspecialchars($this->tmpl->printTitle($this->page['title']));
			$request_uri = htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));
			$stdMsg = '
		<strong>Page is being generated.</strong><br />
		If this message does not disappear within ' . $seconds . ' seconds, please reload.';
			$message = $this->config['config']['message_page_is_being_generated'];
			if (strcmp('', $message)) {
				// This page is always encoded as UTF-8
				$message = $this->csConvObj->utf8_encode($message, $this->renderCharset);
				$message = str_replace('###TITLE###', $title, $message);
				$message = str_replace('###REQUEST_URI###', $request_uri, $message);
			} else {
				$message = $stdMsg;
			}
			$temp_content = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>' . $title . '</title>
		<meta http-equiv="refresh" content="10" />
	</head>
	<body style="background-color:white; font-family:Verdana,Arial,Helvetica,sans-serif; color:#cccccc; text-align:center;">' . $message . '
	</body>
</html>';
			// Fix 'nice errors' feature in modern browsers
			$padSuffix = '<!--pad-->';
			// prevent any trims
			$padSize = 768 - strlen($padSuffix) - strlen($temp_content);
			if ($padSize > 0) {
				$temp_content = str_pad($temp_content, $padSize, LF) . $padSuffix;
			}
			if (!$this->headerNoCache() && ($cachedRow = $this->getFromCache_queryRow())) {
				// We are here because between checking for cached content earlier and now some other HTTP-process managed to store something in cache AND it was not due to a shift-reload by-pass.
				// This is either the "Page is being generated" screen or it can be the final result.
				// In any case we should not begin another rendering process also, so we silently disable caching and render the page ourselves and that's it.
				// Actually $cachedRow contains content that we could show instead of rendering. Maybe we should do that to gain more performance but then we should set all the stuff done in $this->getFromCache()... For now we stick to this...
				$this->set_no_cache('Another process wrote into the cache since the beginning of the render process', TRUE);
			} else {
				$this->tempContent = TRUE;
				// This flag shows that temporary content is put in the cache
				$this->setPageCacheContent($temp_content, $this->config, $GLOBALS['EXEC_TIME'] + $seconds);
			}
		}
	}

	/**
	 * Set cache content to $this->content
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function realPageCacheContent() {
		// seconds until a cached page is too old
		$cacheTimeout = $this->get_cache_timeout();
		$timeOutTime = $GLOBALS['EXEC_TIME'] + $cacheTimeout;
		$this->tempContent = FALSE;
		$usePageCache = TRUE;
		// Hook for deciding whether page cache should be written to the cache backend or not
		// NOTE: as hooks are called in a loop, the last hook will have the final word (however each
		// hook receives the current status of the $usePageCache flag)
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['usePageCache'])) {
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['usePageCache'] as $_classRef) {
				$_procObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$usePageCache = $_procObj->usePageCache($this, $usePageCache);
			}
		}
		// Write the page to cache, if necessary
		if ($usePageCache) {
			$this->setPageCacheContent($this->content, $this->config, $timeOutTime);
		}
		// Hook for cache post processing (eg. writing static files!)
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache'])) {
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache'] as $_classRef) {
				$_procObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->insertPageIncache($this, $timeOutTime);
			}
		}
	}

	/**
	 * Sets cache content; Inserts the content string into the cache_pages cache.
	 *
	 * @param string $content The content to store in the HTML field of the cache table
	 * @param mixed $data The additional cache_data array, fx. $this->config
	 * @param integer $expirationTstamp Expiration timestamp
	 * @return void
	 * @see realPageCacheContent(), tempPageCacheContent()
	 * @todo Define visibility
	 */
	public function setPageCacheContent($content, $data, $expirationTstamp) {
		$cacheData = array(
			'identifier' => $this->newHash,
			'page_id' => $this->id,
			'content' => $content,
			'temp_content' => $this->tempContent,
			'cache_data' => serialize($data),
			'expires' => $expirationTstamp,
			'tstamp' => $GLOBALS['EXEC_TIME']
		);
		$this->cacheExpires = $expirationTstamp;
		$this->pageCacheTags[] = 'pageId_' . $cacheData['page_id'];
		if ($this->page_cache_reg1) {
			$reg1 = intval($this->page_cache_reg1);
			$cacheData['reg1'] = $reg1;
			$this->pageCacheTags[] = 'reg1_' . $reg1;
		}
		if (!empty($this->page['cache_tags'])) {
			$tags = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->page['cache_tags'], TRUE);
			$this->pageCacheTags = array_merge($this->pageCacheTags, $tags);
		}
		$this->pageCache->set($this->newHash, $cacheData, $this->pageCacheTags, $expirationTstamp - $GLOBALS['EXEC_TIME']);
	}

	/**
	 * Clears cache content (for $this->newHash)
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function clearPageCacheContent() {
		$this->pageCache->remove($this->newHash);
	}

	/**
	 * Clears cache content for a list of page ids
	 *
	 * @param string $pidList A list of INTEGER numbers which points to page uids for which to clear entries in the cache_pages cache (page content cache)
	 * @return void
	 * @todo Define visibility
	 */
	public function clearPageCacheContent_pidList($pidList) {
		$pageIds = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $pidList);
		foreach ($pageIds as $pageId) {
			$this->pageCache->flushByTag('pageId_' . (int) $pageId);
		}
	}

	/**
	 * Sets sys last changed
	 * Setting the SYS_LASTCHANGED value in the pagerecord: This value will thus be set to the highest tstamp of records rendered on the page. This includes all records with no regard to hidden records, userprotection and so on.
	 *
	 * @return void
	 * @see tslib_cObj::lastChanged()
	 * @todo Define visibility
	 */
	public function setSysLastChanged() {
		if ($this->page['SYS_LASTCHANGED'] < intval($this->register['SYS_LASTCHANGED'])) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid=' . intval($this->id), array('SYS_LASTCHANGED' => intval($this->register['SYS_LASTCHANGED'])));
		}
	}

	/**
	 * Lock the page generation process
	 * The lock is used to queue page requests until this page is successfully stored in the cache.
	 *
	 * @param \TYPO3\CMS\Core\Locking\Locker $lockObj Reference to a locking object
	 * @param string $key String to identify the lock in the system
	 * @return boolean Returns TRUE if the lock could be obtained, FALSE otherwise (= process had to wait for existing lock to be released)
	 * @see releasePageGenerationLock()
	 * @todo Define visibility
	 */
	public function acquirePageGenerationLock(&$lockObj, $key) {
		if ($this->no_cache || $this->headerNoCache()) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Locking: Page is not cached, no locking required', 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_INFO);
			// No locking is needed if caching is disabled
			return TRUE;
		}
		try {
			if (!is_object($lockObj)) {
				$lockObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Locking\\Locker', $key, $this->TYPO3_CONF_VARS['SYS']['lockingMode']);
			}
			$success = FALSE;
			if (strlen($key)) {
				// TRUE = Page could get locked without blocking
				// FALSE = Page could get locked but process was blocked before
				$success = $lockObj->acquire();
				if ($lockObj->getLockStatus()) {
					$lockObj->sysLog('Acquired lock');
				}
			}
		} catch (\Exception $e) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Locking: Failed to acquire lock: ' . $e->getMessage(), 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
			// If locking fails, return with FALSE and continue without locking
			$success = FALSE;
		}
		return $success;
	}

	/**
	 * Release the page generation lock
	 *
	 * @param \TYPO3\CMS\Core\Locking\Locker $lockObj Reference to a locking object
	 * @return boolean Returns TRUE on success, FALSE otherwise
	 * @see acquirePageGenerationLock()
	 * @todo Define visibility
	 */
	public function releasePageGenerationLock(&$lockObj) {
		$success = FALSE;
		// If lock object is set and was acquired (may also happen if no_cache was enabled during runtime), release it:
		if (is_object($lockObj) && $lockObj instanceof \TYPO3\CMS\Core\Locking\Locker && $lockObj->getLockStatus()) {
			$success = $lockObj->release();
			$lockObj->sysLog('Released lock');
			$lockObj = NULL;
		} elseif ($this->no_cache || $this->headerNoCache()) {
			$success = TRUE;
		}
		return $success;
	}

	/**
	 * Adds tags to this page's cache entry, you can then f.e. remove cache
	 * entries by tag
	 *
	 * @param array $tags An array of tag
	 * @return void
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
	 * @return void
	 * @todo Define visibility
	 */
	public function generatePage_preProcessing() {
		// Same codeline as in getFromCache(). But $this->all has been changed by
		// \TYPO3\CMS\Core\TypoScript\TemplateService::start() in the meantime, so this must be called again!
		$this->newHash = $this->getHash();
		// For cache management informational purposes.
		$this->config['hash_base'] = $this->hash_base;
		if (!is_object($this->pages_lockObj) || $this->pages_lockObj->getLockStatus() == FALSE) {
			// Here we put some temporary stuff in the cache in order to let the first hit generate the page. The temporary cache will expire after a few seconds (typ. 30) or will be cleared by the rendered page, which will also clear and rewrite the cache.
			$this->tempPageCacheContent();
		}
		// Setting cache_timeout_default. May be overridden by PHP include scripts.
		$this->cacheTimeOutDefault = intval($this->config['config']['cache_period']);
		// Page is generated
		$this->no_cacheBeforePageGen = $this->no_cache;
	}

	/**
	 * Determines to include custom or pagegen.php script
	 * returns script-filename if a TypoScript (config) script is defined and should be include instead of pagegen.php
	 *
	 * @return string The relative filepath of "config.pageGenScript" if found and allowed
	 * @todo Define visibility
	 */
	public function generatePage_whichScript() {
		if (!$this->TYPO3_CONF_VARS['FE']['noPHPscriptInclude'] && $this->config['config']['pageGenScript']) {
			return $this->tmpl->getFileName($this->config['config']['pageGenScript']);
		}
	}

	/**
	 * Does some processing AFTER the pagegen script is included.
	 * This includes calling tidy (if configured), XHTML cleaning (if configured), caching the page, indexing the page (if configured) and setting sysLastChanged
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function generatePage_postProcessing() {
		// This is to ensure, that the page is NOT cached if the no_cache parameter was set before the page was generated. This is a safety precaution, as it could have been unset by some script.
		if ($this->no_cacheBeforePageGen) {
			$this->set_no_cache('no_cache has been set before the page was generated - safety check', TRUE);
		}
		// Tidy up the code, if flag...
		if ($this->TYPO3_CONF_VARS['FE']['tidy_option'] == 'all') {
			$GLOBALS['TT']->push('Tidy, all', '');
			$this->content = $this->tidyHTML($this->content);
			$GLOBALS['TT']->pull();
		}
		// XHTML-clean the code, if flag set
		if ($this->doXHTML_cleaning() == 'all') {
			$GLOBALS['TT']->push('XHTML clean, all', '');
			$XHTML_clean = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
			$this->content = $XHTML_clean->XHTML_clean($this->content);
			$GLOBALS['TT']->pull();
		}
		// Fix local anchors in links, if flag set
		if ($this->doLocalAnchorFix() == 'all') {
			$GLOBALS['TT']->push('Local anchor fix, all', '');
			$this->prefixLocalAnchorsWithScript();
			$GLOBALS['TT']->pull();
		}
		// Hook for post-processing of page content cached/non-cached:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'])) {
			$_params = array('pObj' => &$this);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		// Processing if caching is enabled:
		if (!$this->no_cache) {
			// Tidy up the code, if flag...
			if ($this->TYPO3_CONF_VARS['FE']['tidy_option'] == 'cached') {
				$GLOBALS['TT']->push('Tidy, cached', '');
				$this->content = $this->tidyHTML($this->content);
				$GLOBALS['TT']->pull();
			}
			// XHTML-clean the code, if flag set
			if ($this->doXHTML_cleaning() == 'cached') {
				$GLOBALS['TT']->push('XHTML clean, cached', '');
				$XHTML_clean = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
				$this->content = $XHTML_clean->XHTML_clean($this->content);
				$GLOBALS['TT']->pull();
			}
			// Fix local anchors in links, if flag set
			if ($this->doLocalAnchorFix() == 'cached') {
				$GLOBALS['TT']->push('Local anchor fix, cached', '');
				$this->prefixLocalAnchorsWithScript();
				$GLOBALS['TT']->pull();
			}
			// Hook for post-processing of page content before being cached:
			if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'])) {
				$_params = array('pObj' => &$this);
				foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'] as $_funcRef) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
				}
			}
		}
		// Convert char-set for output: (should be BEFORE indexing of the content (changed 22/4 2005)), because otherwise indexed search might convert from the wrong charset! One thing is that the charset mentioned in the HTML header would be wrong since the output charset (metaCharset) has not been converted to from renderCharset. And indexed search will internally convert from metaCharset to renderCharset so the content MUST be in metaCharset already!
		$this->content = $this->convOutputCharset($this->content, 'mainpage');
		// Hook for indexing pages
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'])) {
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'] as $_classRef) {
				$_procObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->hook_indexContent($this);
			}
		}
		// Storing for cache:
		if (!$this->no_cache) {
			$this->realPageCacheContent();
		} elseif ($this->tempContent) {
			// If there happens to be temporary content in the cache and the cache was not cleared due to new content, put it in... ($this->no_cache=0)
			$this->clearPageCacheContent();
			$this->tempContent = FALSE;
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
	 * @return void
	 * @todo Define visibility
	 */
	public function INTincScript() {
		// Deprecated stuff:
		// @deprecated: annotation added TYPO3 4.6
		$this->additionalHeaderData = is_array($this->config['INTincScript_ext']['additionalHeaderData']) ? $this->config['INTincScript_ext']['additionalHeaderData'] : array();
		$this->additionalFooterData = is_array($this->config['INTincScript_ext']['additionalFooterData']) ? $this->config['INTincScript_ext']['additionalFooterData'] : array();
		$this->additionalJavaScript = $this->config['INTincScript_ext']['additionalJavaScript'];
		$this->additionalCSS = $this->config['INTincScript_ext']['additionalCSS'];
		$this->JSImgCode = $this->additionalHeaderData['JSImgCode'];
		$this->divSection = '';
		if (!empty($this->config['INTincScript_ext']['pageRenderer'])) {
			$this->setPageRenderer(unserialize($this->config['INTincScript_ext']['pageRenderer']));
		}
		do {
			$INTiS_config = $this->config['INTincScript'];
			$this->INTincScript_includeLibs($INTiS_config);
			$this->INTincScript_process($INTiS_config);
			// Check if there were new items added to INTincScript during the previous execution:
			$INTiS_config = array_diff_assoc($this->config['INTincScript'], $INTiS_config);
			$reprocess = count($INTiS_config) ? TRUE : FALSE;
		} while ($reprocess);
		$GLOBALS['TT']->push('Substitute header section');
		$this->INTincScript_loadJSCode();
		$this->content = $this->getPageRenderer()->renderJavaScriptAndCssForProcessingOfUncachedContentObjects($this->content, $this->config['INTincScript_ext']['divKey']);
		$this->content = str_replace('<!--HD_' . $this->config['INTincScript_ext']['divKey'] . '-->', $this->convOutputCharset(implode(LF, $this->additionalHeaderData), 'HD'), $this->content);
		$this->content = str_replace('<!--FD_' . $this->config['INTincScript_ext']['divKey'] . '-->', $this->convOutputCharset(implode(LF, $this->additionalFooterData), 'FD'), $this->content);
		$this->content = str_replace('<!--TDS_' . $this->config['INTincScript_ext']['divKey'] . '-->', $this->convOutputCharset($this->divSection, 'TDS'), $this->content);
		$this->setAbsRefPrefix();
		$GLOBALS['TT']->pull();
	}

	/**
	 * Include libraries for uncached objects.
	 *
	 * @param array $INTiS_config $GLOBALS['TSFE']->config['INTincScript'] or part of it
	 * @return void
	 * @see INTincScript()
	 */
	protected function INTincScript_includeLibs($INTiS_config) {
		foreach ($INTiS_config as $INTiS_cPart) {
			if (isset($INTiS_cPart['conf']['includeLibs']) && $INTiS_cPart['conf']['includeLibs']) {
				$INTiS_resourceList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $INTiS_cPart['conf']['includeLibs'], TRUE);
				$this->includeLibraries($INTiS_resourceList);
			}
		}
	}

	/**
	 * Processes the INTinclude-scripts and substitue in content.
	 *
	 * @param array $INTiS_config $GLOBALS['TSFE']->config['INTincScript'] or part of it
	 * @return void
	 * @see INTincScript()
	 */
	protected function INTincScript_process($INTiS_config) {
		$GLOBALS['TT']->push('Split content');
		// Splits content with the key.
		$INTiS_splitC = explode('<!--INT_SCRIPT.', $this->content);
		$this->content = '';
		$GLOBALS['TT']->setTSlogMessage('Parts: ' . count($INTiS_splitC));
		$GLOBALS['TT']->pull();
		foreach ($INTiS_splitC as $INTiS_c => $INTiS_cPart) {
			// If the split had a comment-end after 32 characters it's probably a split-string
			if (substr($INTiS_cPart, 32, 3) === '-->') {
				$INTiS_key = 'INT_SCRIPT.' . substr($INTiS_cPart, 0, 32);
				if (is_array($INTiS_config[$INTiS_key])) {
					$GLOBALS['TT']->push('Include ' . $INTiS_config[$INTiS_key]['file'], '');
					$incContent = '';
					$INTiS_cObj = unserialize($INTiS_config[$INTiS_key]['cObj']);
					/* @var $INTiS_cObj tslib_cObj */
					$INTiS_cObj->INT_include = 1;
					switch ($INTiS_config[$INTiS_key]['type']) {
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
					$this->content .= $this->convOutputCharset($incContent, 'INC-' . $INTiS_c);
					$this->content .= substr($INTiS_cPart, 35);
					$GLOBALS['TT']->pull($incContent);
				} else {
					$this->content .= substr($INTiS_cPart, 35);
				}
			} else {
				$this->content .= ($INTiS_c ? '<!--INT_SCRIPT.' : '') . $INTiS_cPart;
			}
		}
	}

	/**
	 * Loads the JavaScript code for INTincScript
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function INTincScript_loadJSCode() {
		// If any images added, then add them to the javascript section
		$jsImgCode = trim($this->JSImgCode);
		if ($jsImgCode !== '') {
			$this->additionalHeaderData['JSImgCode'] = '
<script type="text/javascript">
	/*<![CDATA[*/
<!--
if (version == "n3") {
' . $jsImgCode . '
}
// -->
	/*]]>*/
</script>';
		}
		// Add javascript
		$jsCode = trim($this->JSCode);
		$additionalJavaScript = is_array($this->additionalJavaScript)
			? implode(LF, $this->additionalJavaScript)
			: $this->additionalJavaScript;
		$additionalJavaScript = trim($additionalJavaScript);
		if ($jsCode !== '' || $additionalJavaScript !== '') {
			$this->additionalHeaderData['JSCode'] = '
<script type="text/javascript">
	/*<![CDATA[*/
<!--
' . $additionalJavaScript . '
' . $jsCode . '
// -->
	/*]]>*/
</script>';
		}
		// Add CSS
		$additionalCss = is_array($this->additionalCSS) ? implode(LF, $this->additionalCSS) : $this->additionalCSS;
		$additionalCss = trim($additionalCss);
		if ($additionalCss !== '') {
			$this->additionalHeaderData['_CSS'] = '
<style type="text/css">
	/*<![CDATA[*/
<!--
' . $additionalCss . '
// -->
	/*]]>*/
</style>';
		}
	}

	/**
	 * Determines if there are any INTincScripts to include
	 *
	 * @return boolean Returns TRUE if scripts are found (and not jumpurl)
	 * @todo Define visibility
	 */
	public function isINTincScript() {
		return is_array($this->config['INTincScript']) && !$this->jumpurl;
	}

	/**
	 * Returns the mode of XHTML cleaning
	 *
	 * @return string Keyword: "all", "cached" or "output
	 * @todo Define visibility
	 */
	public function doXHTML_cleaning() {
		return $this->config['config']['xhtml_cleaning'];
	}

	/**
	 * Returns the mode of Local Anchor prefixing
	 *
	 * @return string Keyword: "all", "cached" or "output
	 * @todo Define visibility
	 */
	public function doLocalAnchorFix() {
		return isset($this->config['config']['prefixLocalAnchors']) ? $this->config['config']['prefixLocalAnchors'] : NULL;
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
	 * @return boolean Returns TRUE if $this->jumpurl is not set.
	 * @todo Define visibility
	 */
	public function isOutputting() {
		// Initialize by status of jumpUrl:
		$enableOutput = !$this->jumpurl;
		// Call hook for possible disabling of output:
		if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']) && is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting'])) {
			$_params = array('pObj' => &$this, 'enableOutput' => &$enableOutput);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		return $enableOutput;
	}

	/**
	 * Process the output before it's actually outputted. Sends headers also.
	 * This includes substituting the "username" comment, sending additional headers (as defined in the TypoScript "config.additionalheaders" object), tidy'ing content, XHTML cleaning content (if configured)
	 * Works on $this->content.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function processOutput() {
		// Set header for charset-encoding unless disabled
		if (empty($this->config['config']['disableCharsetHeader'])) {
			$headLine = 'Content-Type: text/html; charset=' . trim($this->metaCharset);
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
			$GLOBALS['TT']->push('Tidy, output', '');
			$this->content = $this->tidyHTML($this->content);
			$GLOBALS['TT']->pull();
		}
		// XHTML-clean the code, if flag set
		if ($this->doXHTML_cleaning() == 'output') {
			$GLOBALS['TT']->push('XHTML clean, output', '');
			$XHTML_clean = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
			$this->content = $XHTML_clean->XHTML_clean($this->content);
			$GLOBALS['TT']->pull();
		}
		// Fix local anchors in links, if flag set
		if ($this->doLocalAnchorFix() == 'output') {
			$GLOBALS['TT']->push('Local anchor fix, output', '');
			$this->prefixLocalAnchorsWithScript();
			$GLOBALS['TT']->pull();
		}
		// Hook for post-processing of page content before output:
		if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']) && is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'])) {
			$_params = array('pObj' => &$this);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		// Send content-lenght header.
		// Notice that all HTML content outside the length of the content-length header will be cut off! Therefore content of unknown length from included PHP-scripts and if admin users are logged in (admin panel might show...) or if debug mode is turned on, we disable it!
		if (!empty($this->config['config']['enableContentLengthHeader']) && !$this->beUserLogin && !$this->TYPO3_CONF_VARS['FE']['debug'] && !$this->config['config']['debug'] && !$this->doWorkspacePreview()) {
			header('Content-Length: ' . strlen($this->content));
		}
	}

	/**
	 * Send cache headers good for client/reverse proxy caching
	 * This function should not be called if the page content is temporary (like for "Page is being generated..." message, but in that case it is ok because the config-variables are not yet available and so will not allow to send cache headers)
	 *
	 * @return void
	 * @co-author Ole Tange, Forbrugernes Hus, Denmark
	 * @todo Define visibility
	 */
	public function sendCacheHeaders() {
		// Getting status whether we can send cache control headers for proxy caching:
		$doCache = $this->isStaticCacheble();
		// This variable will be TRUE unless cache headers are configured to be sent ONLY if a branch does not allow logins and logins turns out to be allowed anyway...
		$loginsDeniedCfg = empty($this->config['config']['sendCacheHeaders_onlyWhenLoginDeniedInBranch']) || empty($this->loginAllowedInBranch);
		// Finally, when backend users are logged in, do not send cache headers at all (Admin Panel might be displayed for instance).
		if ($doCache && !$this->beUserLogin && !$this->doWorkspacePreview() && $loginsDeniedCfg) {
			// Build headers:
			$headers = array(
				'Last-Modified: ' . gmdate('D, d M Y H:i:s T', $this->register['SYS_LASTCHANGED']),
				'Expires: ' . gmdate('D, d M Y H:i:s T', $this->cacheExpires),
				'ETag: "' . md5($this->content) . '"',
				'Cache-Control: max-age=' . ($this->cacheExpires - $GLOBALS['EXEC_TIME']),
				// no-cache
				'Pragma: public'
			);
			$this->isClientCachable = TRUE;
		} else {
			// Build headers:
			$headers = array(
				'Cache-Control: private'
			);
			$this->isClientCachable = FALSE;
			// Now, if a backend user is logged in, tell him in the Admin Panel log what the caching status would have been:
			if ($this->beUserLogin) {
				if ($doCache) {
					$GLOBALS['TT']->setTSlogMessage('Cache-headers with max-age "' . ($this->cacheExpires - $GLOBALS['EXEC_TIME']) . '" would have been sent');
				} else {
					$reasonMsg = '';
					$reasonMsg .= !$this->no_cache ? '' : 'Caching disabled (no_cache). ';
					$reasonMsg .= !$this->isINTincScript() ? '' : '*_INT object(s) on page. ';
					$reasonMsg .= !is_array($this->fe_user->user) ? '' : 'Frontend user logged in. ';
					$GLOBALS['TT']->setTSlogMessage('Cache-headers would disable proxy caching! Reason(s): "' . $reasonMsg . '"', 1);
				}
			}
		}
		// Send headers:
		foreach ($headers as $hL) {
			header($hL);
		}
	}

	/**
	 * Reporting status whether we can send cache control headers for proxy caching or publishing to static files
	 *
	 * Rules are:
	 * no_cache cannot be set: If it is, the page might contain dynamic content and should never be cached.
	 * There can be no USER_INT objects on the page ("isINTincScript()") because they implicitly indicate dynamic content
	 * There can be no logged in user because user sessions are based on a cookie and thereby does not offer client caching a chance to know if the user is logged in. Actually, there will be a reverse problem here; If a page will somehow change when a user is logged in he may not see it correctly if the non-login version sent a cache-header! So do NOT use cache headers in page sections where user logins change the page content. (unless using such as realurl to apply a prefix in case of login sections)
	 *
	 * @return boolean
	 * @todo Define visibility
	 */
	public function isStaticCacheble() {
		$doCache = !$this->no_cache && !$this->isINTincScript() && !$this->isUserOrGroupSet();
		return $doCache;
	}

	/**
	 * Substitute various tokens in content. This should happen only if the content is not cached by proxies or client browsers.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function contentStrReplace() {
		$search = array();
		$replace = array();
		// Substitutes username mark with the username
		if ($this->fe_user->user['uid']) {
			// User name:
			$token = isset($this->config['config']['USERNAME_substToken']) ? trim($this->config['config']['USERNAME_substToken']) : '';
			$search[] = $token ? $token : '<!--###USERNAME###-->';
			$replace[] = $this->fe_user->user['username'];
			// User uid (if configured):
			$token = isset($this->config['config']['USERUID_substToken']) ? trim($this->config['config']['USERUID_substToken']) : '';
			if ($token) {
				$search[] = $token;
				$replace[] = $this->fe_user->user['uid'];
			}
		}
		// Substitutes get_URL_ID in case of GET-fallback
		if ($this->getMethodUrlIdToken) {
			$search[] = $this->getMethodUrlIdToken;
			$replace[] = $this->fe_user->get_URL_ID;
		}
		// Hook for supplying custom search/replace data
		if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace'])) {
			$contentStrReplaceHooks = &$this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace'];
			if (is_array($contentStrReplaceHooks)) {
				$_params = array(
					'search' => &$search,
					'replace' => &$replace
				);
				foreach ($contentStrReplaceHooks as $_funcRef) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
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
	 * @return boolean TRUE, if external php scripts should be included (set by PHP_SCRIPT_EXT cObjects)
	 * @see tslib_cObj::PHP_SCRIPT
	 * @deprecated since 6.0, will be removed two versions later
	 * @todo Define visibility
	 */
	public function isEXTincScript() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return FALSE;
	}

	/**
	 * Stores session data for the front end user
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function storeSessionData() {
		$this->fe_user->storeSessionData();
	}

	/**
	 * Sets the parsetime of the page.
	 *
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function setParseTime() {
		// Compensates for the time consumed with Back end user initialization.
		$microtime_start = isset($GLOBALS['TYPO3_MISC']['microtime_start']) ? $GLOBALS['TYPO3_MISC']['microtime_start'] : NULL;
		$microtime_end = isset($GLOBALS['TYPO3_MISC']['microtime_end']) ? $GLOBALS['TYPO3_MISC']['microtime_end'] : NULL;
		$microtime_BE_USER_start = isset($GLOBALS['TYPO3_MISC']['microtime_BE_USER_start']) ? $GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'] : NULL;
		$microtime_BE_USER_end = isset($GLOBALS['TYPO3_MISC']['microtime_BE_USER_end']) ? $GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'] : NULL;
		$this->scriptParseTime = $GLOBALS['TT']->getMilliseconds($microtime_end) - $GLOBALS['TT']->getMilliseconds($microtime_start) - ($GLOBALS['TT']->getMilliseconds($microtime_BE_USER_end) - $GLOBALS['TT']->getMilliseconds($microtime_BE_USER_start));
	}

	/**
	 * Get the (partially) anonymized IP address for the log file
	 * Configure: set set config.stat_IP_anonymize=1
	 *
	 * @return string the IP to log
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function getLogIPAddress() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$result = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR');
		if ($this->config['config']['stat_IP_anonymize']) {
			if (strpos($result, ':')) {
				$result = $this->stripIPv6($result);
			} else {
				$result = $this->stripIPv4($result);
			}
		}
		return $result;
	}

	/**
	 * Get the (possibly) anonymized host name for the log file
	 * Configure: set config.stat_IP_anonymize=1
	 *
	 * @return the host name to log
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function getLogHostName() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		if ($this->config['config']['stat_IP_anonymize']) {
			// Ignore hostname if IP anonymized
			$hostName = '<anonymized>';
		} else {
			$hostName = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_HOST');
		}
		return $hostName;
	}

	/**
	 * Get the (possibly) anonymized username or user id for the log file
	 * Configure: set config.stat_IP_anonymize=1
	 *
	 * @return string The user name /uid to log
	 * @deprecated since 6.0, will be removed with 6.2
	 */
	public function getLogUserName() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$logUser = isset($this->config['config']['stat_logUser']) ? $this->config['config']['stat_logUser'] : TRUE;
		if ($this->loginUser && $logUser) {
			$userName = $this->fe_user->user['username'];
		} else {
			$userName = '-';
		}
		return $userName;
	}

	/**
	 * Outputs preview info.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function previewInfo() {
		if ($this->fePreview !== 0) {
			$previewInfo = '';
			if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo']) && is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo'])) {
				$_params = array('pObj' => &$this);
				foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo'] as $_funcRef) {
					$previewInfo .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
				}
			}
			$this->content = str_ireplace('</body>', $previewInfo . '</body>', $this->content);
		}
	}

	/**
	 * End-Of-Frontend hook
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function hook_eofe() {
		// Call hook for end-of-frontend processing:
		if (isset($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']) && is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe'])) {
			$_params = array('pObj' => &$this);
			foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe'] as $_funcRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
	}

	/**
	 * Returns a link to the BE login screen with redirect to the front-end
	 *
	 * @return string HTML, a tag for a link to the backend.
	 * @todo Define visibility
	 */
	public function beLoginLinkIPList() {
		if (!empty($this->config['config']['beLoginLinkIPList'])) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), $this->config['config']['beLoginLinkIPList'])) {
				$label = !$this->beUserLogin ? $this->config['config']['beLoginLinkIPList_login'] : $this->config['config']['beLoginLinkIPList_logout'];
				if ($label) {
					if (!$this->beUserLogin) {
						$link = '<a href="' . htmlspecialchars((TYPO3_mainDir . 'index.php?redirect_url=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . $label . '</a>';
					} else {
						$link = '<a href="' . htmlspecialchars((TYPO3_mainDir . 'index.php?L=OUT&redirect_url=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . $label . '</a>';
					}
					return $link;
				}
			}
		}
	}

	/**
	 * Sends HTTP headers for temporary content. These headers prevent search engines from caching temporary content and asks them to revisit this page again.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function addTempContentHttpHeaders() {
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
	 * Encryption (or decryption) of a single character.
	 * Within the given range the character is shifted with the supplied offset.
	 *
	 * @param integer $n Ordinal of input character
	 * @param integer $start Start of range
	 * @param integer $end End of range
	 * @param integer $offset Offset
	 * @return string encoded/decoded version of character
	 * @todo Define visibility
	 */
	public function encryptCharcode($n, $start, $end, $offset) {
		$n = $n + $offset;
		if ($offset > 0 && $n > $end) {
			$n = $start + ($n - $end - 1);
		} elseif ($offset < 0 && $n < $start) {
			$n = $end - ($start - $n - 1);
		}
		return chr($n);
	}

	/**
	 * Encryption of email addresses for <A>-tags See the spam protection setup in TS 'config.'
	 *
	 * @param string $string Input string to en/decode: "mailto:blabla@bla.com
	 * @param boolean $back If set, the process is reversed, effectively decoding, not encoding.
	 * @return string encoded/decoded version of $string
	 * @todo Define visibility
	 */
	public function encryptEmail($string, $back = 0) {
		$out = '';
		if ($this->spamProtectEmailAddresses === 'ascii') {
			$stringLength = strlen($string);
			for ($a = 0; $a < $stringLength; $a++) {
				$out .= '&#' . ord(substr($string, $a, 1)) . ';';
			}
		} else {
			// like str_rot13() but with a variable offset and a wider character range
			$len = strlen($string);
			$offset = intval($this->spamProtectEmailAddresses) * ($back ? -1 : 1);
			for ($i = 0; $i < $len; $i++) {
				$charValue = ord($string[$i]);
				// 0-9 . , - + / :
				if ($charValue >= 43 && $charValue <= 58) {
					$out .= $this->encryptCharcode($charValue, 43, 58, $offset);
				} elseif ($charValue >= 64 && $charValue <= 90) {
					// A-Z @
					$out .= $this->encryptCharcode($charValue, 64, 90, $offset);
				} elseif ($charValue >= 97 && $charValue <= 122) {
					// a-z
					$out .= $this->encryptCharcode($charValue, 97, 122, $offset);
				} else {
					$out .= $string[$i];
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
	 * @param string $string Input string to en/decode
	 * @param boolean $decode If set, string is decoded, not encoded.
	 * @return string encoded/decoded version of $string
	 * @todo Define visibility
	 */
	public function codeString($string, $decode = FALSE) {
		if ($decode) {
			list($md5Hash, $str) = explode(':', $string, 2);
			$newHash = substr(md5($this->TYPO3_CONF_VARS['SYS']['encryptionKey'] . ':' . $str), 0, 10);
			if (!strcmp($md5Hash, $newHash)) {
				$str = base64_decode($str);
				$str = $this->roundTripCryptString($str);
				return $str;
			} else {
				return FALSE;
			}
		} else {
			$str = $string;
			$str = $this->roundTripCryptString($str);
			$str = base64_encode($str);
			$newHash = substr(md5($this->TYPO3_CONF_VARS['SYS']['encryptionKey'] . ':' . $str), 0, 10);
			return $newHash . ':' . $str;
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
	 * @param string $string String to crypt, may be empty
	 * @return string binary crypt string, will have the same length as $string
	 */
	protected function roundTripCryptString($string) {
		$out = '';
		$cleartextLength = strlen($string);
		$key = sha1($this->TYPO3_CONF_VARS['SYS']['encryptionKey']);
		$keyLength = strlen($key);
		for ($a = 0; $a < $cleartextLength; $a++) {
			$xorVal = ord($key[$a % $keyLength]);
			$out .= chr(ord($string[$a]) ^ $xorVal);
		}
		return $out;
	}

	/**
	 * Checks if a PHPfile may be included.
	 *
	 * @param string $incFile Relative path to php file
	 * @return boolean Returns TRUE if $GLOBALS['TYPO3_CONF_VARS']['FE']['noPHPscriptInclude'] is not set OR if the file requested for inclusion is found in one of the allowed paths.
	 * @see tslib_menu::includeMakeMenu()
	 * @todo Define visibility
	 */
	public function checkFileInclude($incFile) {
		return !$this->TYPO3_CONF_VARS['FE']['noPHPscriptInclude'] || substr($incFile, 0, 4 + strlen(TYPO3_mainDir)) == TYPO3_mainDir . 'ext/' || substr($incFile, 0, 7 + strlen(TYPO3_mainDir)) == TYPO3_mainDir . 'sysext/' || substr($incFile, 0, 14) == 'typo3conf/ext/';
	}

	/**
	 * Creates an instance of tslib_cObj in $this->cObj
	 * This instance is used to start the rendering of the TypoScript template structure
	 *
	 * @return void
	 * @see pagegen.php
	 * @todo Define visibility
	 */
	public function newCObj() {
		$this->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$this->cObj->start($this->page, 'pages');
	}

	/**
	 * Converts relative paths in the HTML source to absolute paths for fileadmin/, typo3conf/ext/ and media/ folders.
	 *
	 * @return void
	 * @access private
	 * @see pagegen.php, INTincScript()
	 * @todo Define visibility
	 */
	public function setAbsRefPrefix() {
		if ($this->absRefPrefix) {
			$this->content = str_replace('"typo3temp/', '"' . $this->absRefPrefix . 'typo3temp/', $this->content);
			$this->content = str_replace('"typo3conf/ext/', '"' . $this->absRefPrefix . 'typo3conf/ext/', $this->content);
			$this->content = str_replace('"' . TYPO3_mainDir . 'contrib/', '"' . $this->absRefPrefix . TYPO3_mainDir . 'contrib/', $this->content);
			$this->content = str_replace('"' . TYPO3_mainDir . 'ext/', '"' . $this->absRefPrefix . TYPO3_mainDir . 'ext/', $this->content);
			$this->content = str_replace('"' . TYPO3_mainDir . 'sysext/', '"' . $this->absRefPrefix . TYPO3_mainDir . 'sysext/', $this->content);
			$this->content = str_replace('"' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '"' . $this->absRefPrefix . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], $this->content);
			$this->content = str_replace('"' . $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'], '"' . $this->absRefPrefix . $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'], $this->content);
			// Process additional directories
			$directories = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'], TRUE);
			foreach ($directories as $directory) {
				$this->content = str_replace('"' . $directory, '"' . $this->absRefPrefix . $directory, $this->content);
			}
		}
	}

	/**
	 * Prefixing the input URL with ->baseUrl If ->baseUrl is set and the input url is not absolute in some way.
	 * Designed as a wrapper functions for use with all frontend links that are processed by JavaScript (for "realurl" compatibility!). So each time a URL goes into window.open, window.location.href or otherwise, wrap it with this function!
	 *
	 * @param string $url Input URL, relative or absolute
	 * @return string Processed input value.
	 * @todo Define visibility
	 */
	public function baseUrlWrap($url) {
		if ($this->baseUrl) {
			$urlParts = parse_url($url);
			if (!strlen($urlParts['scheme']) && $url[0] !== '/') {
				$url = $this->baseUrl . $url;
			}
		}
		return $url;
	}

	/**
	 * Logs access to deprecated TypoScript objects and properties.
	 *
	 * Dumps message to the TypoScript message log (admin panel) and the TYPO3 deprecation log.
	 * Note: The second parameter was introduced in TYPO3 4.5 and is not available in older versions
	 *
	 * @param string $typoScriptProperty Deprecated object or property
	 * @param string $explanation Message or additional information
	 * @return void
	 * @todo Define visibility
	 */
	public function logDeprecatedTyposcript($typoScriptProperty, $explanation = '') {
		$explanationText = strlen($explanation) ? ' - ' . $explanation : '';
		$GLOBALS['TT']->setTSlogMessage($typoScriptProperty . ' is deprecated.' . $explanationText, 2);
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('TypoScript ' . $typoScriptProperty . ' is deprecated' . $explanationText);
	}

	/**
	 * Updates the tstamp field of a cache_md5params record to the current time.
	 *
	 * @param string $hash The hash string identifying the cache_md5params record for which to update the "tstamp" field to the current time.
	 * @return void
	 * @access private
	 * @todo Define visibility
	 */
	public function updateMD5paramsRecord($hash) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cache_md5params', 'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($hash, 'cache_md5params'), array('tstamp' => $GLOBALS['EXEC_TIME']));
	}

	/**
	 * Pass the content through tidy - a little program that cleans up HTML-code.
	 * Requires $this->TYPO3_CONF_VARS['FE']['tidy'] to be TRUE and $this->TYPO3_CONF_VARS['FE']['tidy_path'] to
	 * contain the filename/path of tidy including clean-up arguments for tidy. See default value in
	 * TYPO3_CONF_VARS in ext:core/Configuration/DefaultConfiguration.php
	 *
	 * @param string $content The page content to clean up. Will be written to a temporary file which "tidy" is then asked to clean up. File content is read back and returned.
	 * @return string Returns the
	 * @todo Define visibility
	 */
	public function tidyHTML($content) {
		if ($this->TYPO3_CONF_VARS['FE']['tidy'] && $this->TYPO3_CONF_VARS['FE']['tidy_path']) {
			$oldContent = $content;
			// Create temporary name
			$fname = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('typo3_tidydoc_');
			// Delete if exists, just to be safe.
			@unlink($fname);
			// Open for writing
			$fp = fopen($fname, 'wb');
			// Put $content
			fputs($fp, $content);
			// Close
			@fclose($fp);
			// run the $content through 'tidy', which formats the HTML to nice code.
			exec($this->TYPO3_CONF_VARS['FE']['tidy_path'] . ' ' . $fname, $output);
			// Delete the tempfile again
			@unlink($fname);
			$content = implode(LF, $output);
			if (!trim($content)) {
				// Restore old content due empty return value.
				$content = $oldContent;
				$GLOBALS['TT']->setTSlogMessage('"tidy" returned an empty value!', 2);
			}
			$GLOBALS['TT']->setTSlogMessage('"tidy" content lenght: ' . strlen($content), 0);
		}
		return $content;
	}

	/**
	 * Substitutes all occurencies of <a href="#"... in $this->content with <a href="[path-to-url]#"...
	 *
	 * @return void Works directly on $this->content
	 * @todo Define visibility
	 */
	public function prefixLocalAnchorsWithScript() {
		$scriptPath = $GLOBALS['TSFE']->absRefPrefix . substr(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), strlen(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL')));
		$originalContent = $this->content;
		$this->content = preg_replace('/(<(?:a|area).*?href=")(#[^"]*")/i', '${1}' . htmlspecialchars($scriptPath) . '${2}', $originalContent);
		// There was an error in the call to preg_replace, so keep the original content (behavior prior to PHP 5.2)
		if (preg_last_error() > 0) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('preg_replace returned error-code: ' . preg_last_error() . ' in function prefixLocalAnchorsWithScript. Replacement not done!', 'cms', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_FATAL);
			$this->content = $originalContent;
		}
	}

	/********************************************
	 * PUBLIC ACCESSIBLE WORKSPACES FUNCTIONS
	 *******************************************/

	/**
	 * Returns TRUE if workspace preview is enabled
	 *
	 * @return boolean Returns TRUE if workspace preview is enabled
	 */
	public function doWorkspacePreview() {
		return $this->workspacePreview !== 0 ? TRUE : FALSE;
	}

	/**
	 * Returns the name of the workspace
	 *
	 * @param boolean $returnTitle If set, returns title of current workspace being previewed
	 * @return mixed If $returnTitle is set, returns string (title), otherwise workspace integer for which workspace is being preview. False if none.
	 * @todo Define visibility
	 */
	public function whichWorkspace($returnTitle = FALSE) {
		if ($this->doWorkspacePreview()) {
			$ws = intval($this->workspacePreview);
		} elseif ($this->beUserLogin) {
			$ws = $GLOBALS['BE_USER']->workspace;
		} else {
			return FALSE;
		}
		if ($returnTitle) {
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title', 'sys_workspace', 'uid=' . intval($ws));
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
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
	 * @param array $libraries The libraries to be included.
	 * @return void
	 */
	public function includeLibraries(array $libraries) {
		global $TYPO3_CONF_VARS;
		$GLOBALS['TT']->push('Include libraries');
		$GLOBALS['TT']->setTSlogMessage('Files for inclusion: "' . implode(', ', $libraries) . '"');
		foreach ($libraries as $library) {
			$file = $GLOBALS['TSFE']->tmpl->getFileName($library);
			if ($file) {
				include_once './' . $file;
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
	 * @return array Array with keys '_STORAGE_PID' and '_SITEROOT' set to the first occurances found.
	 * @todo Define visibility
	 */
	public function getStorageSiterootPids() {
		$res = array();
		if (!is_array($this->rootLine)) {
			return array();
		}
		foreach ($this->rootLine as $rC) {
			if (!$res['_STORAGE_PID']) {
				$res['_STORAGE_PID'] = intval($rC['storage_pid']);
			}
			if (!$res['_SITEROOT']) {
				$res['_SITEROOT'] = $rC['is_siteroot'] ? intval($rC['uid']) : 0;
			}
		}
		return $res;
	}

	/**
	 * Returns the pages TSconfig array based on the currect ->rootLine
	 *
	 * @return array
	 * @todo Define visibility
	 */
	public function getPagesTSconfig() {
		if (!is_array($this->pagesTSconfig)) {
			$TSdataArray = array();
			// Setting default configuration:
			$TSdataArray[] = $this->TYPO3_CONF_VARS['BE']['defaultPageTSconfig'];
			foreach ($this->rootLine as $k => $v) {
				$TSdataArray[] = $v['TSconfig'];
			}
			// Parsing the user TS (or getting from cache)
			$TSdataArray = \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::checkIncludeLines_array($TSdataArray);
			$userTS = implode(LF . '[GLOBAL]' . LF, $TSdataArray);
			$hash = md5('pageTS:' . $userTS);
			$cachedContent = $this->sys_page->getHash($hash);
			if (isset($cachedContent)) {
				$this->pagesTSconfig = unserialize($cachedContent);
			} else {
				$parseObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
				$parseObj->parse($userTS);
				$this->pagesTSconfig = $parseObj->setup;
				$this->sys_page->storeHash($hash, serialize($this->pagesTSconfig), 'PAGES_TSconfig');
			}
		}
		return $this->pagesTSconfig;
	}

	/**
	 * Sets JavaScript code in the additionalJavaScript array
	 *
	 * @param string $key is the key in the array, for num-key let the value be empty. Note reserved keys 'openPic' and 'mouseOver'
	 * @param string $content is the content if you want any
	 * @return void
	 * @see tslib_gmenu::writeMenu(), tslib_cObj::imageLinkWrap()
	 * @todo Define visibility
	 */
	public function setJS($key, $content = '') {
		if ($key) {
			switch ($key) {
			case 'mouseOver':
				$this->additionalJavaScript[$key] = '		// JS function for mouse-over
	function over(name, imgObj) {	//
		if (version == "n3" && document[name]) {document[name].src = eval(name+"_h.src");}
		else if (document.getElementById && document.getElementById(name)) {document.getElementById(name).src = eval(name+"_h.src");}
		else if (imgObj)	{imgObj.src = eval(name+"_h.src");}
	}
		// JS function for mouse-out
	function out(name, imgObj) {	//
		if (version == "n3" && document[name]) {document[name].src = eval(name+"_n.src");}
		else if (document.getElementById && document.getElementById(name)) {document.getElementById(name).src = eval(name+"_n.src");}
		else if (imgObj)	{imgObj.src = eval(name+"_n.src");}
	}';
				break;
			case 'openPic':
				$this->additionalJavaScript[$key] = '	function openPic(url, winName, winParams) {	//
		var theWindow = window.open(url, winName, winParams);
		if (theWindow)	{theWindow.focus();}
	}';
				break;
			default:
				$this->additionalJavaScript[$key] = $content;
				break;
			}
		}
	}

	/**
	 * Sets CSS data in the additionalCSS array
	 *
	 * @param string $key Is the key in the array, for num-key let the value be empty
	 * @param string $content Is the content if you want any
	 * @return void
	 * @see setJS()
	 * @todo Define visibility
	 */
	public function setCSS($key, $content) {
		if ($key) {
			switch ($key) {
			default:
				$this->additionalCSS[$key] = $content;
				break;
			}
		}
	}

	/**
	 * Returns a unique md5 hash.
	 * There is no special magic in this, the only point is that you don't have to call md5(uniqid()) which is slow and by this you are sure to get a unique string each time in a little faster way.
	 *
	 * @param string $str Some string to include in what is hashed. Not significant at all.
	 * @return string MD5 hash of ->uniqueString, input string and uniqueCounter
	 * @todo Define visibility
	 */
	public function uniqueHash($str = '') {
		return md5($this->uniqueString . '_' . $str . $this->uniqueCounter++);
	}

	/**
	 * Sets the cache-flag to 1. Could be called from user-included php-files in order to ensure that a page is not cached.
	 *
	 * @param string $reason An optional reason to be written to the syslog.
	 * @param boolean $internal Whether the call is done from core itself (should only be used by core).
	 * @return void
	 * @todo Define visibility
	 */
	public function set_no_cache($reason = '', $internal = FALSE) {
		if ($internal && isset($GLOBALS['BE_USER]'])) {
			$severity = \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_NOTICE;
		} else {
			$severity = \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING;
		}

		if (strlen($reason)) {
			$warning = '$TSFE->set_no_cache() was triggered. Reason: ' . $reason . '.';
		} else {
			$trace = debug_backtrace();
			// This is a hack to work around ___FILE___ resolving symbolic links
			$PATH_site_real = str_replace('t3lib', '', realpath(PATH_site . 't3lib'));
			$file = $trace[0]['file'];
			if (substr($file, 0, strlen($PATH_site_real)) === $PATH_site_real) {
				$file = str_replace($PATH_site_real, '', $file);
			} else {
				$file = str_replace(PATH_site, '', $file);
			}
			$line = $trace[0]['line'];
			$trigger = $file . ' on line ' . $line;
			$warning = '$TSFE->set_no_cache() was triggered by ' . $trigger . '.';
		}
		if ($this->TYPO3_CONF_VARS['FE']['disableNoCacheParameter']) {
			$warning .= ' However, $TYPO3_CONF_VARS[\'FE\'][\'disableNoCacheParameter\'] is set, so it will be ignored!';
			$GLOBALS['TT']->setTSlogMessage($warning, 2);
		} else {
			$warning .= ' Caching is disabled!';
			$this->disableCache();
		}
		\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog($warning, 'cms', $severity);
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
	 * @param integer $seconds Cache-timeout in seconds
	 * @return void
	 * @todo Define visibility
	 */
	public function set_cache_timeout_default($seconds) {
		$this->cacheTimeOutDefault = intval($seconds);
	}

	/**
	 * Get the cache timeout for the current page.
	 *
	 * @return integer The cache timeout for the current page.
	 * @todo Define visibility
	 */
	public function get_cache_timeout() {
		/** @var $runtimeCache \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend */
		$runtimeCache = $GLOBALS['typo3CacheManager']->getCache('cache_runtime');
		$cachedCacheLifetimeIdentifier = 'core-tslib_fe-get_cache_timeout';
		$cachedCacheLifetime = $runtimeCache->get($cachedCacheLifetimeIdentifier);
		if ($cachedCacheLifetime === FALSE) {
			if ($this->page['cache_timeout']) {
				// Cache period was set for the page:
				$cacheTimeout = $this->page['cache_timeout'];
			} elseif ($this->cacheTimeOutDefault) {
				// Cache period was set for the whole site:
				$cacheTimeout = $this->cacheTimeOutDefault;
			} else {
				// No cache period set at all, so we take one day (60*60*24 seconds = 86400 seconds):
				$cacheTimeout = 86400;
			}
			if ($this->config['config']['cache_clearAtMidnight']) {
				$timeOutTime = $GLOBALS['EXEC_TIME'] + $cacheTimeout;
				$midnightTime = mktime(0, 0, 0, date('m', $timeOutTime), date('d', $timeOutTime), date('Y', $timeOutTime));
				// If the midnight time of the expire-day is greater than the current time,
				// we may set the timeOutTime to the new midnighttime.
				if ($midnightTime > $GLOBALS['EXEC_TIME']) {
					$cacheTimeout = $midnightTime - $GLOBALS['EXEC_TIME'];
				}
			} else {
				// If cache_clearAtMidnight is not set calculate the timeout time for records on the page
				$calculatedCacheTimeout = $this->calculatePageCacheTimeout();
				$cacheTimeout = $calculatedCacheTimeout < $cacheTimeout ? $calculatedCacheTimeout : $cacheTimeout;
			}
			if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['get_cache_timeout'])) {
				foreach ($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['get_cache_timeout'] as $_funcRef) {
					$params = array('cacheTimeout' => $cacheTimeout);
					$cacheTimeout = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $params, $this);
				}
			}
			$runtimeCache->set($cachedCacheLifetimeIdentifier, $cacheTimeout);
			$cachedCacheLifetime = $cacheTimeout;
		}
		return $cachedCacheLifetime;
	}

	/**
	 * Substitute function for the PHP mail() function.
	 * It will encode the email with the setting of TS 'config.notification_email_encoding' (base64 or none)
	 * It will also find all links to http:// in the text and substitute with a shorter link using the redirect feature which stores the long link in the database. Depends on configuration in TS 'config.notification_email_urlmode'
	 *
	 * @param string $email recipient email address (or list of)
	 * @param string $subject The subject
	 * @param string $message The message
	 * @param string $headers The headers (string with lines)
	 * @return void
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::plainMailEncoded()
	 * @todo Define visibility
	 * @deprecated since 6.1, will be removed two versions later - Use \TYPO3\CMS\Core\Mail\Mailer instead
	 */
	public function plainMailEncoded($email, $subject, $message, $headers = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		// '76', 'all', ''
		$urlmode = $this->config['config']['notification_email_urlmode'];
		if ($urlmode) {
			$message = \TYPO3\CMS\Core\Utility\GeneralUtility::substUrlsInPlainText($message, $urlmode);
		}
		$encoding = $this->config['config']['notification_email_encoding'] ? $this->config['config']['notification_email_encoding'] : '';
		$charset = $this->renderCharset;
		$convCharset = FALSE;
		// do we need to convert mail data?
		// Respect config.notification_email_charset if it was set
		if ($this->config['config']['notification_email_charset']) {
			$charset = $this->csConvObj->parse_charset($this->config['config']['notification_email_charset']);
			if ($charset != $this->renderCharset) {
				$convCharset = TRUE;
			}
		} elseif ($this->metaCharset != $this->renderCharset) {
			// Use metaCharset for mail if different from renderCharset
			$charset = $this->metaCharset;
			$convCharset = TRUE;
		}
		if ($convCharset) {
			$email = $this->csConvObj->conv($email, $this->renderCharset, $charset);
			$subject = $this->csConvObj->conv($subject, $this->renderCharset, $charset);
			$message = $this->csConvObj->conv($message, $this->renderCharset, $charset);
			$headers = $this->csConvObj->conv($headers, $this->renderCharset, $charset);
		}
		\TYPO3\CMS\Core\Utility\GeneralUtility::plainMailEncoded($email, $subject, $message, $headers, $encoding, $charset);
	}

	/*********************************************
	 *
	 * Localization and character set conversion
	 *
	 *********************************************/
	/**
	 * Split Label function for front-end applications.
	 *
	 * @param string $input Key string. Accepts the "LLL:" prefix.
	 * @return string Label value, if any.
	 * @todo Define visibility
	 */
	public function sL($input) {
		if (substr($input, 0, 4) !== 'LLL:') {
			// Not a label, return the key as this
			return $input;
		}
		// If cached label
		if (!isset($this->LL_labels_cache[$this->lang][$input])) {
			$restStr = trim(substr($input, 4));
			$extPrfx = '';
			if (!strcmp(substr($restStr, 0, 4), 'EXT:')) {
				$restStr = trim(substr($restStr, 4));
				$extPrfx = 'EXT:';
			}
			$parts = explode(':', $restStr);
			$parts[0] = $extPrfx . $parts[0];
			// Getting data if not cached
			if (!isset($this->LL_files_cache[$parts[0]])) {
				$this->LL_files_cache[$parts[0]] = $this->readLLfile($parts[0]);
			}
			$this->LL_labels_cache[$this->lang][$input] = $this->getLLL($parts[1], $this->LL_files_cache[$parts[0]]);
		}
		return $this->LL_labels_cache[$this->lang][$input];
	}

	/**
	 * Read locallang files - for frontend applications
	 *
	 * @param string $fileRef Reference to a relative filename to include.
	 * @return array Returns the $LOCAL_LANG array found in the file. If no array found, returns empty array.
	 * @todo Define visibility
	 */
	public function readLLfile($fileRef) {
		if ($this->lang !== 'default') {
			$languages = array_reverse($this->languageDependencies);
			// At least we need to have English
			if (empty($languages)) {
				$languages[] = 'default';
			}
		} else {
			$languages = array('default');
		}

		$localLanguage = array();
		foreach ($languages as $language) {
			$tempLL = \TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile($fileRef, $language, $this->renderCharset);
			$localLanguage['default'] = $tempLL['default'];
			if (!isset($localLanguage[$this->lang])) {
				$localLanguage[$this->lang] = $localLanguage['default'];
			}
			if ($this->lang !== 'default' && isset($tempLL[$language])) {
				// Merge current language labels onto labels from previous language
				// This way we have a label with fall back applied
				$localLanguage[$this->lang] = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($localLanguage[$this->lang], $tempLL[$language], FALSE, FALSE);
			}
		}

		return $localLanguage;
	}

	/**
	 * Returns 'locallang' label - may need initializing by initLLvars
	 *
	 * @param string $index Local_lang key for which to return label (language is determined by $this->lang)
	 * @param array $LOCAL_LANG The locallang array in which to search
	 * @return string Label value of $index key.
	 * @todo Define visibility
	 */
	public function getLLL($index, $LOCAL_LANG) {
		if (isset($LOCAL_LANG[$this->lang][$index][0]['target'])) {
			return $LOCAL_LANG[$this->lang][$index][0]['target'];
		} elseif (isset($LOCAL_LANG['default'][$index][0]['target'])) {
			return $LOCAL_LANG['default'][$index][0]['target'];
		}
		return FALSE;
	}

	/**
	 * Initializing the getLL variables needed.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function initLLvars() {
		// Setting language key and split index:
		$this->lang = $this->config['config']['language'] ? $this->config['config']['language'] : 'default';
		$this->getPageRenderer()->setLanguage($this->lang);

		// Finding the requested language in this list based
		// on the $lang key being inputted to this function.
		/** @var $locales \TYPO3\CMS\Core\Localization\Locales */
		$locales = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Localization\\Locales');

		// Language is found. Configure it:
		if (in_array($this->lang, $locales->getLocales())) {
			$this->languageDependencies[] = $this->lang;
			foreach ($locales->getLocaleDependencies($this->lang) as $language) {
				$this->languageDependencies[] = $language;
			}
		}

		// Setting charsets:
		$this->renderCharset = $this->csConvObj->parse_charset($this->config['config']['renderCharset'] ? $this->config['config']['renderCharset'] : 'utf-8');
		// Rendering charset of HTML page.
		$this->metaCharset = $this->csConvObj->parse_charset($this->config['config']['metaCharset'] ? $this->config['config']['metaCharset'] : $this->renderCharset);
	}

	/**
	 * Converts the charset of the input string if applicable.
	 * The "to" charset is determined by the currently used charset for the page which is "utf-8" by default or set by $GLOBALS['TSFE']->config['config']['renderCharset']
	 * Only if there is a difference between the two charsets will a conversion be made
	 * The conversion is done real-time - no caching for performance at this point!
	 *
	 * @param string $str String to convert charset for
	 * @param string $from Optional "from" charset.
	 * @return string Output string, converted if needed.
	 * @see \TYPO3\CMS\Core\Charset\CharsetConverter
	 * @todo Define visibility
	 */
	public function csConv($str, $from = '') {
		if ($from) {
			$output = $this->csConvObj->conv($str, $this->csConvObj->parse_charset($from), $this->renderCharset, 1);
			return $output ? $output : $str;
		} else {
			return $str;
		}
	}

	/**
	 * Converts input string from renderCharset to metaCharset IF the two charsets are different.
	 *
	 * @param string $content Content to be converted.
	 * @param string $label Label (just for fun, no function)
	 * @return string Converted content string.
	 * @todo Define visibility
	 */
	public function convOutputCharset($content, $label = '') {
		if ($this->renderCharset != $this->metaCharset) {
			$content = $this->csConvObj->conv($content, $this->renderCharset, $this->metaCharset, TRUE);
		}
		return $content;
	}

	/**
	 * Converts the $_POST array from metaCharset (page HTML charset from input form) to renderCharset (internal processing) IF the two charsets are different.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function convPOSTCharset() {
		if ($this->renderCharset != $this->metaCharset && is_array($_POST) && count($_POST)) {
			$this->csConvObj->convArray($_POST, $this->metaCharset, $this->renderCharset);
			$GLOBALS['HTTP_POST_VARS'] = $_POST;
		}
	}

	/**
	 * Calculates page cache timeout according to the records with starttime/endtime on the page.
	 *
	 * @return integer Page cache timeout or PHP_INT_MAX if cannot be determined
	 */
	protected function calculatePageCacheTimeout() {
		$result = PHP_INT_MAX;
		// Get the configuration
		$tablesToConsider = $this->getCurrentPageCacheConfiguration();
		// Get the time, rounded to the minute (do not polute MySQL cache!)
		// It is ok that we do not take seconds into account here because this
		// value will be substracted later. So we never get the time "before"
		// the cache change.
		$now = $GLOBALS['ACCESS_TIME'];
		// Find timeout by checking every table
		foreach ($tablesToConsider as $tableDef) {
			$result = min($result, $this->getFirstTimeValueForRecord($tableDef, $now));
		}
		// We return + 1 second just to ensure that cache is definitely regenerated
		return $result == PHP_INT_MAX ? PHP_INT_MAX : $result - $now + 1;
	}

	/**
	 * Obtains a list of table/pid pairs to consider for page caching.
	 *
	 * TS configuration looks like this:
	 *
	 * The cache lifetime of all pages takes starttime and endtime of news records of page 14 into account:
	 * config.cache.all = tt_news:14
	 *
	 * The cache lifetime of page 42 takes starttime and endtime of news records of page 15 and addresses of page 16 into account:
	 * config.cache.42 = tt_news:15,tt_address:16
	 *
	 * @return array Array of 'tablename:pid' pairs. There is at least a current page id in the array
	 * @see tslib_fe::calculatePageCacheTimeout()
	 */
	protected function getCurrentPageCacheConfiguration() {
		$result = array('tt_content:' . $this->id);
		if (isset($this->config['config']['cache.'][$this->id])) {
			$result = array_merge($result, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->config['config']['cache.'][$this->id]));
		}
		if (isset($this->config['config']['cache.']['all'])) {
			$result = array_merge($result, \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->config['config']['cache.']['all']));
		}
		return array_unique($result);
	}

	/**
	 * Find the minimum starttime or endtime value in the table and pid that is greater than the current time.
	 *
	 * @param string $tableDef Table definition (format tablename:pid)
	 * @param integer $now "Now" time value
	 * @return integer Value of the next start/stop time or PHP_INT_MAX if not found
	 * @see tslib_fe::calculatePageCacheTimeout()
	 */
	protected function getFirstTimeValueForRecord($tableDef, $now) {
		$result = PHP_INT_MAX;
		list($tableName, $pid) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $tableDef);
		if (empty($tableName) || empty($pid)) {
			throw new \InvalidArgumentException('Unexpected value for parameter $tableDef. Expected <tablename>:<pid>, got \'' . htmlspecialchars($tableDef) . '\'.', 1307190365);
		}
		// Additional fields
		$showHidden = $tableName === 'pages' ? $this->showHiddenPage : $this->showHiddenRecords;
		$enableFields = $this->sys_page->enableFields($tableName, $showHidden, array('starttime' => TRUE, 'endtime' => TRUE));
		// For each start or end time field, get the minimum value
		foreach (array('starttime', 'endtime') as $field) {
			// Note: there is no need to load TCA because we need only enable columns!
			if (isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field])) {
				$timeField = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field];
				$selectField = 'MIN(' . $timeField . ') AS ' . $field;
				$whereCondition = $timeField . ' > ' . $now;
				// Find the smallest timestamp which could influence the cache duration (but is larger than 0)
				$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow($selectField, $tableName, 'pid = ' . intval($pid) . ' AND ' . $whereCondition . $enableFields);
				if ($row && !is_null($row[$timeField])) {
					$result = min($result, $row[$timeField]);
				}
			}
		}
		return $result;
	}


	/**
	 * Fetches/returns the cached contents of the sys_domain database table.
	 *
	 * @return array Domain data
	 */
	protected function getSysDomainCache() {
		$entryIdentifier = 'core-database-sys_domain-complete';
		/** @var $runtimeCache \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend */
		$runtimeCache = $GLOBALS['typo3CacheManager']->getCache('cache_runtime');

		$sysDomainData = array();
		if ($runtimeCache->has($entryIdentifier)) {
			$sysDomainData = $runtimeCache->get($entryIdentifier);
		} else {
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'pid, domainName, forced',
				'sys_domain',
				'redirectTo=\'\' ' . $GLOBALS['TSFE']->sys_page->enableFields('sys_domain'),
				'',
				'sorting ASC'
			);

			while (FALSE !== ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result))) {
				// if there is already an entry for this pid, check if we should overwrite it
				if (isset($sysDomainData[$row['pid']])) {
					// there is already a "forced" entry, which must not be overwritten
					if ($sysDomainData[$row['pid']]['forced']) {
						continue;
					}

					// there is alread a NOT-forced entry and the current one is also NOT-forced, thus keep the old
					if (!$sysDomainData[$row['pid']]['forced'] && !$row['forced']) {
						continue;
					}
				}

				// as we passed all previous checks, we save this domain for the current pid
				$sysDomainData[$row['pid']] = array(
					'pid' => $row['pid'],
					'domainName' => rtrim($row['domainName'], '/'),
					'forced' => $row['forced'],
				);
			}
			$runtimeCache->set($entryIdentifier, $sysDomainData);
			$GLOBALS['TYPO3_DB']->sql_free_result($result);
		}
		return $sysDomainData;
	}

	/**
	 * Obtains domain data for the target pid. Domain data is an array with
	 * 'pid', 'domainName' and 'forced' members (see sys_domain table for
	 * meaning of these fields.
	 *
	 * @param int $targetPid Target page id
	 * @return mixed Return domain data or NULL
	*/
	public function getDomainDataForPid($targetPid) {
		$result = NULL;

		$sysDomainData = $this->getSysDomainCache();
		$rootline = $this->sys_page->getRootLine($targetPid);
		// walk the rootline downwards from the target page to the root, until a domain record is found
		foreach ($rootline as $pageInRootline) {
			$pidInRootline = $pageInRootline['uid'];
			if (isset($sysDomainData[$pidInRootline])) {
				$result = $sysDomainData[$pidInRootline];
				break;
			}
		}
		return $result;
	}

	/**
	 * Obtains the domain name for the target pid. If there are several domains,
	 * the first is returned.
	 *
	 * @param int $targetPid Target page id
	 * @return mixed Return domain name or NULL if not found
	 */
	public function getDomainNameForPid($targetPid) {
		$domainData = $this->getDomainDataForPid($targetPid);
		return $domainData ? $domainData['domainName'] : NULL;
	}
}

?>
