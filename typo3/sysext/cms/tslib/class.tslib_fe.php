<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * $Id$
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  182: class tslib_fe
 *  337:     function tslib_fe($TYPO3_CONF_VARS, $id, $type, $no_cache='', $cHash='', $jumpurl='',$MP='',$RDCT='')
 *  368:     function connectToMySQL()
 *  404:     function sendRedirect()
 *
 *              SECTION: Initializing, resolving page id
 *  442:     function initFEuser()
 *  496:     function checkAlternativeIdMethods()
 *  548:     function clear_preview()
 *  561:     function determineId()
 *  641:     function fetch_the_id()
 *  746:     function getPageAndRootline()
 *  808:     function getPageShortcut($SC,$mode,$thisUid,$itera=20,$pageLog=array())
 *  858:     function checkRootlineForIncludeSection()
 *  891:     function checkEnableFields($row)
 *  909:     function checkPagerecordForIncludeSection($row)
 *  921:     function setIDfromArgV()
 *  937:     function getPageAndRootlineWithDomain($domainStartPage)
 *  965:     function findDomainRecord($recursive=0)
 *  986:     function pageNotFoundHandler($code,$header='')
 * 1008:     function checkAndSetAlias()
 * 1023:     function idPartsAnalyze($str)
 * 1048:     function mergingWithGetVars($GET_VARS)
 *
 *              SECTION: Template and caching related functions.
 * 1096:     function makeCacheHash()
 * 1119:     function cHashParams($addQueryParams)
 * 1140:     function initTemplate()
 * 1152:     function getFromCache()
 * 1210:     function getHash()
 * 1230:     function getConfigArray()
 *
 *              SECTION: Further initialization and data processing
 * 1344:     function getCompressedTCarray()
 * 1381:     function includeTCA($TCAloaded=1)
 * 1407:     function settingLanguage()
 * 1447:     function checkDataSubmission()
 * 1474:     function fe_tce()
 * 1488:     function locDataCheck($locationData)
 * 1504:     function sendFormmail()
 * 1547:     function checkJumpUrl()
 * 1629:     function jumpUrl()
 * 1672:     function setUrlIdToken()
 *
 *              SECTION: Page generation; cache handling
 * 1715:     function isGeneratePage()
 * 1725:     function tempPageCacheContent()
 * 1756:     function realPageCacheContent()
 * 1778:     function setPageCacheContent($c,$d,$t)
 * 1800:     function clearPageCacheContent()
 * 1810:     function clearPageCacheContent_pidList($pidList)
 * 1821:     function setSysLastChanged()
 *
 *              SECTION: Page generation; rendering and inclusion
 * 1857:     function generatePage_preProcessing()
 * 1893:     function generatePage_whichScript()
 * 1905:     function generatePage_postProcessing()
 * 1993:     function INTincScript()
 * 2054:     function INTincScript_loadJSCode()
 * 2095:     function isINTincScript()
 * 2104:     function isSearchIndexPage()
 * 2113:     function doXHTML_cleaning()
 * 2122:     function doLocalAnchorFix()
 *
 *              SECTION: Finished off; outputting, storing session data, statistics...
 * 2153:     function isOutputting()
 * 2164:     function processOutput()
 * 2230:     function isEXTincScript()
 * 2239:     function storeSessionData()
 * 2249:     function setParseTime()
 * 2261:     function statistics()
 * 2355:     function previewInfo()
 * 2376:     function beLoginLinkIPList()
 *
 *              SECTION: Various internal API functions
 * 2431:     function makeSimulFileName($inTitle,$page,$type,$addParams='',$no_cache='')
 * 2478:     function simulateStaticDocuments_pEnc_onlyP_proc($linkVars)
 * 2506:     function getSimulFileName()
 * 2519:     function encryptEmail($string,$back=0)
 * 2538:     function codeString($string, $decode=FALSE)
 * 2564:     function roundTripCryptString($string)
 * 2584:     function checkFileInclude($incFile)
 * 2599:     function newCObj()
 * 2612:     function setAbsRefPrefix()
 * 2628:     function printError($label,$header='Error!')
 * 2639:     function updateMD5paramsRecord($hash)
 * 2650:     function tidyHTML($content)
 * 2676:     function prefixLocalAnchorsWithScript()
 *
 *              SECTION: Various external API functions - for use in plugins etc.
 * 2720:     function getStorageSiterootPids()
 * 2735:     function getPagesTSconfig()
 * 2768:     function setJS($key,$content='')
 * 2806:     function setCSS($key,$content)
 * 2821:     function make_seed()
 * 2834:     function uniqueHash($str='')
 * 2843:     function set_no_cache()
 * 2853:     function set_cache_timeout_default($seconds)
 * 2869:     function plainMailEncoded($email,$subject,$message,$headers='')
 * 2892:     function sL($input)
 * 2929:     function csConv($str,$from='')
 * 2948:     function readLLfile($fileRef)
 * 2963:     function getLLL($index,$LOCAL_LANG)
 * 2977:     function initLLvars()
 *
 * TOTAL FUNCTIONS: 87
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */





















/**
 * Main frontend class, instantiated in the index_ts.php script as the global object TSFE
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
 class tslib_fe	{

		// CURRENT PAGE:
	var $id='';							// The page id (int)
	var $type='';						// RO The type (int)
	var $idParts=array();				// Loaded with the id, exploded by ','
	var $cHash='';						// The submitted cHash
	var $no_cache=''; 					// Page will not be cached. Write only true. Never clear value (some other code might have reasons to set it true)
	var $rootLine='';					// The rootLine (all the way to tree root, not only the current site!) (array)
	var $page='';						// The pagerecord (array)
	var $contentPid=0;					// This will normally point to the same value as id, but can be changed to point to another page from which content will then be displayed instead.
	var $sys_page='';					// The object with pagefunctions (object)
	var $jumpurl='';
	var $pageNotFound=0;				// Is set to 1 if a pageNotFound handler could have been called.
	var $domainStartPage=0;				// Domain start page
	var $MP='';
	var $RDCT='';
	var $page_cache_reg1=0;				// This can be set from applications as a way to tag cached versions of a page and later perform some external cache management, like clearing only a part of the cache of a page...
	var $siteScript='';					// Contains the value of the current script path that activated the frontend. Typically "index.php" but by rewrite rules it could be something else! Used for Speaking Urls / Simulate Static Documents.

		// USER
	var $fe_user='';					// The user (object)
	var $loginUser='';					// Global falg indicating that a front-end user is logged in. This is set only if a user really IS logged in. The group-list may show other groups (like added by IP filter or so) even though there is no user.
	var $gr_list='';					// (RO=readonly) The group list, sorted numerically. Group '0,-1' is the default group, but other groups may be added by other means than a user being logged in though...
	var $beUserLogin='';				// Flag that indicates if a Backend user is logged in!

		// PREVIEW
	var $fePreview='';					// Flag indication that preview is active. This is based on the login of a backend user and whether the backend user has read access to the current page.
	var $showHiddenPage='';				// Flag indicating that hidden pages should be shown, selected and so on. This goes for almost all selection of pages!
	var $showHiddenRecords='';			// Flag indicating that hidden records should be shown. This includes sys_template, pages_language_overlay and even fe_groups in addition to all other regular content. So in effect, this includes everything except pages.
	var $simUserGroup='0';				// Value that contains the simulated usergroup if any

		// CONFIGURATION
	var $TYPO3_CONF_VARS=array();		// The configuration array as set up in t3lib/config_default.php. Should be an EXACT copy of the global array.
	var $config='';						// 'CONFIG' object from TypoScript. Array generated based on the TypoScript configuration of the current page. Saved with the cached pages.

		// TEMPLATE / CACHE
	var $tmpl='';						// The TypoScript template object. Used to parse the TypoScript template
	var $cacheTimeOutDefault='';		// Is set to the time-to-live time of cached pages. If false, default is 60*60*24, which is 24 hours.
	var $cacheContentFlag='';			// Set internally if cached content is fetched from the database
	var $all='';						// $all used by template fetching system. This array is an identification of the template. If $this->all is empty it's because the template-data is not cached, which it must be.
	var $sPre='';						// toplevel - objArrayName, eg 'page'
	var $pSetup='';						// TypoScript configuration of the page-object pointed to by sPre. $this->tmpl->setup[$this->sPre.'.']
	var $newHash='';					// This hash is unique to the template, the $this->id and $this->type vars and the gr_list (list of groups). Used to get and later store the cached data
	var $getMethodUrlIdToken='';		// If config.ftu (Frontend Track User) is set in TypoScript for the current page, the string value of this var is substituted in the rendered source-code with the string, '&ftu=[token...]' which enables GET-method usertracking as opposed to cookie based
	var $noCacheBeforePageGen='';		// This flag is set before inclusion of pagegen.php IF no_cache is set. If this flag is set after the inclusion of pagegen.php, no_cache is forced to be set. This is done in order to make sure that php-code from pagegen does not falsely clear the no_cache flag.
	var $tempContent='';				// This flag indicates if temporary content went into the cache during page-generation.
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
	var $defaultBodyTag='<body bgcolor="#FFFFFF">';		// Default bodytag, if nothing else is set. This can be overridden by applications like TemplaVoila.
	var $additionalHeaderData=array();	// used to accumulate additional HTML-code for the header-section, <head>...</head>. Insert either associative keys (like additionalHeaderData['myStyleSheet'], see reserved keys above) or num-keys (like additionalHeaderData[] = '...')
	var $additionalJavaScript=array();	// used to accumulate additional JavaScript-code. Works like additionalHeaderData. Reserved keys at 'openPic' and 'mouseOver'
	var $additionalCSS=array();			// used to accumulate additional Style code. Works like additionalHeaderData.
	var $JSeventFuncCalls = array(		// you can add JavaScript functions to each entry in these arrays. Please see how this is done in the GMENU_LAYERS script. The point is that many applications on a page can set handlers for onload, onmouseover and onmouseup
		'onmousemove' => array(),
		'onmouseup' => array(),
		'onload' => array(),
	);
	var $JSCode='';						// Depreciated, use additionalJavaScript instead.
	var $JSImgCode='';					// Used to accumulate JavaScript loaded images (by menus)
	var $divSection='';					// Used to accumulate DHTML-layers.

		// RENDERING configuration, settings from TypoScript is loaded into these vars. See pagegen.php
	var $debug='';						// Debug flag, may output special debug html-code.
	var $intTarget='';					// Default internal target
	var $extTarget='';					// Default external target
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
	var $sys_language_uid=0;			// Site language, 0 (zero) is default, int+ is uid pointing to a sys_language record.
	var $sys_language_isocode = '';		// Is set to the iso code of the sys_language if that is properly defined by the sys_language record representing the sys_language_uid. (Requires the extension "static_info_tables")

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
	var $pEncAllowedParamNames=array();	// An array that holds parameter names (keys) of GET parameters which MAY be MD5/base64 encoded with simulate_static_documents method.

		// Page content render object
	var $cObj ='';						// is instantiated object of tslib_cObj

		// Character set (charset) conversion object:
	var $csConvObj;						// An instance of the "t3lib_cs" class. May be used by any application.
	var $defaultCharSet='iso-8859-1';	// The default charset used in the frontend if nothing else is set.

		// CONTENT accumulation
	var $content='';					// All page content is accumulated in this variable. See pagegen.php

		// GENERAL
	var $clientInfo='';					// Set to the browser: net / msie if 4+ browsers
	var $scriptParseTime=0;
	var $TCAloaded = 0;					// Set ONLY if the full TCA is loaded

		// LANG:
	var $lang='';						// Set to the system language key (used on the site)
	var $langSplitIndex=0;				// Set to the index number of the language key
	var $labelsCharset='';				// Charset of the labels from locallang (based on $this->lang)
	var $siteCharset='';				// Charset of the website.
	var $convCharsetToFrom='';			// Set to the charsets to convert from/to IF there are any difference. Otherwise this stays a string
	var $LL_labels_cache=array();
	var $LL_files_cache=array();





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
		$this->no_cache = $no_cache ? 1 : 0;
		$this->cHash = $cHash;
		$this->jumpurl = $jumpurl;
		$this->MP = $this->TYPO3_CONF_VARS['FE']['enable_mount_pids'] ? $MP : '';
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
	}

	/**
	 * Connect to SQL database
	 * May exit after outputting an error message or some JavaScript redirecting to the install tool.
	 *
	 * @return	void
	 */
	function connectToMySQL()	{
		if ($GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password))	{
			if (!TYPO3_db)	{
				$this->printError('No database selected','Database Error');
					// Redirects to the Install Tool:
				echo '<script type="text/javascript">
						/*<![CDATA[*/
					document.location = "'.TYPO3_mainDir.'install/index.php?mode=123&step=1&password=joh316";
						/*]]>*/
					</script>';
				exit;
			} elseif (!$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db))	{
				$this->printError('Cannot connect to the current database, "'.TYPO3_db.'"','Database Error');
				exit;
			}
		} else {
			if (!TYPO3_db)	{
					// Redirects to the Install Tool:
				echo '<script type="text/javascript">
						/*<![CDATA[*/
					document.location = "'.TYPO3_mainDir.'install/index.php?mode=123&step=1&password=joh316";
						/*]]>*/
					</script>';
				exit;
			}
			$this->printError('The current username, password or host was not accepted when the connection to the database was attempted to be established!','Database Error');
			exit;
		}
	}

	/**
	 * Looks up the value of $this->RDCT in the database and if it is found to be associated with a redirect URL then the redirection is carried out with a 'Location:' header
	 * May exit after sending a location-header.
	 *
	 * @return	void
	 */
	function sendRedirect()	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('params', 'cache_md5params', 'md5hash="'.$GLOBALS['TYPO3_DB']->quoteStr($this->RDCT, 'cache_md5params').'"');
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$this->updateMD5paramsRecord($this->RDCT);
			header('Location: '.$row['params']);
			exit;
		}
	}


















	/********************************************
	 *
	 * Initializing, resolving page id
	 *
	 ********************************************/


	/**
	 * Initializes the front-end login user.
	 *
	 * @return	void
	 */
	function initFEuser()	{
		$this->fe_user = t3lib_div::makeInstance('tslib_feUserAuth');

		$this->fe_user->lockIP = $this->TYPO3_CONF_VARS['FE']['lockIP'];
		$this->fe_user->checkPid = $this->TYPO3_CONF_VARS['FE']['checkFeUserPid'];
		$this->fe_user->checkPid_value = $GLOBALS['TYPO3_DB']->cleanIntList(t3lib_div::_GP('pid'));	// List of pid's acceptable

			// Check if a session is transferred:
		if (t3lib_div::_GP('FE_SESSION_KEY'))	{
			$fe_sParts = explode('-',t3lib_div::_GP('FE_SESSION_KEY'));
			if (!strcmp(md5($fe_sParts[0].'/'.$this->TYPO3_CONF_VARS['SYS']['encryptionKey']), $fe_sParts[1]))	{	// If the session key hash check is OK:
				$GLOBALS['HTTP_COOKIE_VARS'][$this->fe_user->name]=$fe_sParts[0];
				$this->fe_user->forceSetCookie=1;
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
			$this->fe_user->record_registration($recs);
		}

			// For every 60 seconds the is_online timestamp is updated.
		if (is_array($this->fe_user->user) && $this->fe_user->user['is_online']<($GLOBALS['EXEC_TIME']-60))	{
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid='.intval($this->fe_user->user['uid']), array('is_online' => $GLOBALS['EXEC_TIME']));
		}
	}

	/**
	 * Provides ways to bypass the '?id=[xxx]&type=[xx]' format, using either PATH_INFO or virtual HTML-documents (using Apache mod_rewrite)
	 *
	 * Two options:
	 * 1) Apache mod_rewrite: Here a .htaccess file maps all .html-files to index.php and then we extract the id and type from the name of that HTML-file.
	 * 2) Use PATH_INFO (also Apache) to extract id and type from that var. Does not require any special modules compiled with apache.
	 *
	 * Support for RewriteRule to generate   (simulateStaticDocuments)
	 * With the mod_rewrite compiled into apache, put these lines into a .htaccess in this directory:
	 * RewriteEngine On
	 * RewriteRule   ^[^/]*\.html$  index.php
	 * The url must end with '.html' and the format must comply with either of these:
	 * 1:      '[title].[id].[type].html'			- title is just for easy recognition in the logfile!; no practical use of the title for TYPO3.
	 * 2:      '[id].[type].html'					- above, but title is omitted; no practical use of the title for TYPO3.
	 * 3:      '[id].html'							- only id, type is set to the default, zero!
	 * NOTE: In all case 'id' may be the uid-number OR the page alias (if any)
	 *
	 * @return	void
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&cHash=4ad9d7acb4
	 */
	function checkAlternativeIdMethods()	{

		$this->siteScript = t3lib_div::getIndpEnv('TYPO3_SITE_SCRIPT');

			// Resolving of "simulateStaticDocuments" URLs:
		if ($this->siteScript && substr($this->siteScript,0,9)!='index.php')	{		// If there has been a redirect (basically; we arrived here otherwise than via "index.php" in the URL) this can happend either due to a CGI-script or because of reWrite rule. Earlier we used $GLOBALS['HTTP_SERVER_VARS']['REDIRECT_URL'] to check but
			$uParts = parse_url($this->siteScript);	// Parse the path:
			$fI = t3lib_div::split_fileref($uParts['path']);

			if (!$fI['path'] && $fI['file'] && substr($fI['file'],-5)=='.html')	{
				$parts = explode('.',$fI['file']);
				$pCount = count($parts);
				if ($pCount>2)	{
					$this->type = intval($parts[$pCount-2]);
					$this->id = $parts[$pCount-3];
				} else {
					$this->type = 0;
					$this->id = $parts[0];
				}
			}
		}

			// If PATH_INFO
		if (t3lib_div::getIndpEnv('PATH_INFO'))	{		// If pathinfo contains stuff...
			$parts=t3lib_div::trimExplode('/',t3lib_div::getIndpEnv('PATH_INFO'),1);
			$parts[]='html';
			$pCount = count($parts);
			if ($pCount>2)	{
				$this->type = intval($parts[$pCount-2]);
				$this->id = $parts[$pCount-3];
			} else {
				$this->type = 0;
				$this->id = $parts[0];
			}
			$this->absRefPrefix_force=1;
		}

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
		if ($this->beUserLogin)	{
			$this->fePreview = $GLOBALS['BE_USER']->extGetFeAdminValue('preview');

				// If admin panel preview is enabled...
			if ($this->fePreview)	{
				$fe_user_OLD_USERGROUP = $this->fe_user->user['usergroup'];

				$this->showHiddenPage = $GLOBALS['BE_USER']->extGetFeAdminValue('preview','showHiddenPages');
				$this->showHiddenRecords = $GLOBALS['BE_USER']->extGetFeAdminValue('preview','showHiddenRecords');
					// simulate date
				$simTime = $GLOBALS['BE_USER']->extGetFeAdminValue('preview','simulateDate');
				if ($simTime)	$GLOBALS['SIM_EXEC_TIME']=$simTime;
					// simulate user
				$simUserGroup = $GLOBALS['BE_USER']->extGetFeAdminValue('preview','simulateUserGroup');
				$this->simUserGroup = $simUserGroup;
				if ($simUserGroup)	$this->fe_user->user['usergroup']=$simUserGroup;
				if (!$simUserGroup && !$simTime && !$this->showHiddenPage && !$this->showHiddenRecords)	{
					$this->fePreview=0;
				}
			}

				// Now it's investigated if the raw page-id points to a hidden page and if so, the flag is set.
				// This does not require the preview flag to be set in the admin panel
			if ($this->id)	{
				$idQ = t3lib_div::testInt($this->id) ? 'uid="'.intval($this->id).'"' : 'alias="'.$GLOBALS['TYPO3_DB']->quoteStr($this->id, 'pages').'"';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('hidden', 'pages', $idQ.' AND hidden AND NOT deleted');
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
					$this->fePreview = 1;	// The preview flag is set only if the current page turns out to actually be hidden!
					$this->showHiddenPage = 1;
				}
			}

			if ($this->fePreview)	{	// If the front-end is showing a preview, caching MUST be disabled.
				$this->set_no_cache();
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
			// Final cleaning.
		$this->id=$this->contentPid=intval($this->id);	// Make sure it's an integer
		$this->type=intval($this->type);	// Make sure it's an integer


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
		$this->sys_page->init($this->showHiddenPage);

			// Sets ->loginUser and ->gr_list based on front-end user status.
		$this->fe_user->showHiddenRecords = $this->showHiddenRecords;		// This affects the hidden-flag selecting the fe_groups for the user!
		if (is_array($this->fe_user->user) && $this->fe_user->fetchGroupData())	{
			$this->loginUser=1;	// global flag!
			$this->gr_list = '0,-2';	// group -2 is not an existing group, but denotes a 'default' group when a user IS logged in. This is used to let elements be shown for all logged in users!
			$gr_array = $this->fe_user->groupData['uid'];
		} else {
			$this->loginUser=0;
			$this->gr_list = '0,-1';	// group -1 is not an existing group, but denotes a 'default' group when not logged in. This is used to let elements be hidden, when a user is logged in!
			$gr_array=array();
		}

			// ADD group-numbers if the IPmask matches.
		if (is_array($this->TYPO3_CONF_VARS['FE']['IPmaskMountGroups']))	{
			foreach($this->TYPO3_CONF_VARS['FE']['IPmaskMountGroups'] as $IPel)	{
				if (t3lib_div::getIndpEnv('REMOTE_ADDR') && $IPel[0] && t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'),$IPel[0]))	{$gr_array[]=intval($IPel[1]);}
			}
		}

			// Clean up.
		$gr_array = array_unique($gr_array);	// Make unique...
		sort($gr_array);	// sort
		if (count($gr_array))	{
			$this->gr_list.=','.implode(',',$gr_array);
		}

			// Sets sys_page where-clause
		$this->sys_page->where_hid_del.=' AND doktype<200 AND fe_group IN ('.$this->gr_list.')';

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
					$this->printError('No pages are found on the rootlevel!');
					exit;
				}
			}
		}
		$GLOBALS['TT']->pull();

		$GLOBALS['TT']->push('fetch_the_id rootLine/','');
		$requestedId = $this->id;		// We store the originally requested id
		$this->getPageAndRootlineWithDomain($this->domainStartPage);
		$GLOBALS['TT']->pull();

		if ($this->pageNotFound && $this->TYPO3_CONF_VARS['FE']['pageNotFound_handling'])	{
			$this->pageNotFoundHandler($this->TYPO3_CONF_VARS['FE']['pageNotFound_handling'],$this->TYPO3_CONF_VARS['FE']['pageNotFound_handling_statheader']);
		}

			// set no_cache if set
		if ($this->page['no_cache'])	{
			$this->set_no_cache();
		}
			// Init SYS_LASTCHANGED
		$this->register['SYS_LASTCHANGED'] = intval($this->page['tstamp']);
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
					$c--;
					$this->id=$this->rootLine[$c]['uid'];
					$this->page = $this->sys_page->getPage($this->id);
					if (count($this->page)){break;}
				}
			}
				// If still no page...
			if (!count($this->page))	{
				if ($this->TYPO3_CONF_VARS['FE']['pageNotFound_handling'])	{
					$this->pageNotFoundHandler($this->TYPO3_CONF_VARS['FE']['pageNotFound_handling'],$this->TYPO3_CONF_VARS['FE']['pageNotFound_handling_statheader']);
				} else {
					$this->printError('The requested page does not exist!');
					exit;
				}
			}
		}
			// Is the ID a link to another page??
		if ($this->page['doktype']==4)	{
			$this->MP = '';		// We need to clear MP if the page is a shortcut. Reason is if the short cut goes to another page, then we LEAVE the rootline which the MP expects.
			$this->page = $this->getPageShortcut($this->page['shortcut'],$this->page['shortcut_mode'],$this->page['uid']);
			$this->id = $this->page['uid'];
		}
			// Gets the rootLine
		$this->rootLine = $this->sys_page->getRootLine($this->id,$this->MP);

			// If not rootline we're off...
		if (!count($this->rootLine))	{
			$this->printError('The requested page didn\'t have a proper connection to the tree-root! <br /><br />('.$this->sys_page->error_getRootLine.')');
			exit;
		}

			// Checking for include section regarding the hidden/starttime/endtime/fe_user (that is access control of a whole subbranch!)
		if ($this->checkRootlineForIncludeSection())	{
			if (!count($this->rootLine))	{
				$this->printError('The requested page was not accessible!');
				exit;
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
	 * @param	integer		The shortcut mode: 1 and 2 will select either first subpage or random subpage; the default is the page pointed to by $SC
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
			case 1:
			case 2:
				$pageArray = $this->sys_page->getMenu($idArray[0]?$idArray[0]:$thisUid,'*','sorting','AND pages.doktype<199 AND pages.doktype!=6');
				$pO = 0;
				if ($mode==2 && count($pageArray))	{	// random
					$this->make_seed();
					$randval = intval(rand(0,count($pageArray)-1));
					$pO = $randval;
				}
				$c = 0;
				reset($pageArray);
				while(list(,$pV)=each($pageArray))	{
					if ($c==$pO)	{
						$page = $pV;
						break;
					}
					$c++;
				}
			break;
			default:
				$page = $this->sys_page->getPage($idArray[0]);
			break;
		}

			// Check if short cut page was a shortcut itself, if so look up recursively:
		if ($page['doktype']==4)	{
			if (!in_array($page['uid'],$pageLog) && $itera>0)	{
				$pageLog[] = $page['uid'];
				$page = $this->getPageShortcut($page['shortcut'],$page['shortcut_mode'],$page['uid'],$itera-1,$pageLog);
			} else {
				$pageLog[] = $page['uid'];
				$this->printError('Page shortcuts were looping in uids '.implode(',',$pageLog).'...!');
				exit;
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
				$removeTheRestFlag=1;
			}
			if ($this->rootLine[$a]['doktype']==6)	{
				if ($this->beUserLogin)	{	// If there is a backend user logged in, check if he has read access to the page:
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'uid='.intval($this->id).' AND '.$GLOBALS['BE_USER']->getPagePermsClause(1));
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
	 * Takes notice of the ->showHiddenPage flag and uses SIM_EXEC_TIME for start/endtime evaluation
	 *
	 * @param	array		The page record to evaluate (needs fields; hidden, starttime, endtime, fe_group)
	 * @return	boolean		True, if record is viewable.
	 * @see tslib_cObj::getTreeList(), checkPagerecordForIncludeSection()
	 */
	function checkEnableFields($row)	{
		if ((!$row['hidden'] || $this->showHiddenPage)
			&& $row['starttime']<=$GLOBALS['SIM_EXEC_TIME']
			&& ($row['endtime']==0 || $row['endtime']>$GLOBALS['SIM_EXEC_TIME'])
			&& t3lib_div::inList($this->gr_list,$row['fe_group'])
		) {
			return 1;
		}
	}

	/**
	 * Checks page record for include section
	 *
	 * @param	array		The page record to evaluate (needs fields;extendToSubpages + hidden, starttime, endtime, fe_group)
	 * @return	boolean		Returns true if either extendToSubpages is not checked or if the enableFields does not disable the page record.
	 * @access private
	 * @see checkEnableFields(), tslib_cObj::getTreeList(), checkRootlineForIncludeSection()
	 */
	function checkPagerecordForIncludeSection($row)	{
		return (!$row['extendToSubpages'] || $this->checkEnableFields($row)) ? 1 : 0;
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
			$this->id = $theAlias ? $theAlias : 0;
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
			reset ($this->rootLine);
			$idFound = 0;
			while(list($key,$val)=each($this->rootLine)) {
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
	 * Page not found handler.
	 * Exits.
	 *
	 * @param	mixed		Which type of handling; If a true PHP-boolean and TRUE then a ->printError message is outputted. If integer an error message with that number is shown. Otherwise the $code value is expected to be a "Location:" header value.
	 * @param	string		If set, this is passed directly to the PHP function, header()
	 * @return	void		(The function exists!)
	 */
	function pageNotFoundHandler($code,$header='')	{
		if ($header)	{header($header);}

		if (gettype($code)=='boolean' || !strcmp($code,1))	{
			$this->printError('The page did not exist or was inaccessible.');
			exit;
		} else if (t3lib_div::testInt($code))	{
			$this->printError('Error '.$code);
			exit;
		} else {
			header('Location: '.t3lib_div::locationHeaderUrl($code));
			exit;
		}
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
			if ($aid)	{$this->id=$aid;}
		}
	}

	/**
	 * Analyzes the second part of a id-string (after the "+"), looking for B6 or M5 encoding and if found it will resolve it and restore the variables in global $_GET (but NOT $_GET - yet)
	 * If values for ->cHash, ->no_cache, ->jumpurl and ->MP is found, they are also loaded into the internal vars of this class.
	 *
	 * @param	string		String to analyze
	 * @return	void
	 * @access private
	 */
	function idPartsAnalyze($str)	{
		$GET_VARS = '';
		switch(substr($str,0,2))	{
			case 'B6':
				$addParams = base64_decode(str_replace('_','=',str_replace('-','/',substr($str,2))));
				parse_str($addParams,$GET_VARS);
			break;
			case 'M5':
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('params', 'cache_md5params', 'md5hash="'.$GLOBALS['TYPO3_DB']->quoteStr(substr($str,2), 'cache_md5params').'"');
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

				$this->updateMD5paramsRecord(substr($str,2));
				parse_str($row['params'],$GET_VARS);
			break;
		}

		$this->mergingWithGetVars($GET_VARS);
	}

	/**
	 * Merging values into the global $HTTP_GET_VARS/$_GET
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

















	/********************************************
	 *
	 * Template and caching related functions.
	 *
	 *******************************************/

	/**
	 * Calculates a hash string based on additional parameters in the url. This is used to cache pages with more parameters than just id and type
	 *
	 * @return	void
	 */
	function makeCacheHash()	{
		$GET = t3lib_div::_GET();
		if ($this->cHash && is_array($GET))	{
			$pA = $this->cHashParams(t3lib_div::implodeArrayForUrl('',$GET));
			$this->cHash_array = $pA;
			$cHash_calc = t3lib_div::shortMD5(serialize($this->cHash_array));
#debug(array($cHash_calc,$this->cHash,$pA));
			if ($cHash_calc!=$this->cHash)	{
				$this->set_no_cache();
				$GLOBALS['TT']->setTSlogMessage('The incoming cHash "'.$this->cHash.'" and calculated cHash "'.$cHash_calc.'" did not match, so caching was disabled. The fieldlist used was "'.implode(array_keys($pA),',').'"',2);
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
	 */
	function cHashParams($addQueryParams) {
		$params = explode('&',substr($addQueryParams,1));	// Splitting parameters up

			// Make array:
		$pA = array();
		foreach($params as $theP)	{
			$pKV = explode('=', $theP);	// SPlitting single param by '=' sign
			if (!t3lib_div::inList('id,type,no_cache,cHash,MP,ftu',$pKV[0]))	{
				$pA[$pKV[0]] = (string)rawurldecode($pKV[1]);
			}
		}
		$pA['encryptionKey'] = $this->TYPO3_CONF_VARS['SYS']['encryptionKey'];
		ksort($pA);
		return $pA;
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
		$this->tmpl->getCurrentPageData();
		$cc = Array();
		if (is_array($this->tmpl->currentPageData))	{
				// BE CAREFULL to change the content of the cc-array. This array is serialized and an md5-hash based on this is used for caching the page.
				// If this hash is not the same in here in this section and after page-generation the page will not be properly cached!

			$cc['all'] = $this->tmpl->currentPageData['all'];
			$cc['rowSum'] = $this->tmpl->currentPageData['rowSum'];
			$cc['rootLine'] = $this->tmpl->currentPageData['rootLine'];		// This rootline is used with templates only (matching()-function)
			$this->all = $this->tmpl->matching($cc);	// This array is an identification of the template. If $this->all is empty it's because the template-data is not cached, which it must be.
			ksort($this->all);
		}

		$this->content='';	// clearing the content-variable, which will hold the pagecontent
		unset($this->config);	// Unsetting the lowlevel config
		$this->cacheContentFlag=0;
		if ($this->all && !$this->no_cache)	{
			$this->newHash = $this->getHash();

			$GLOBALS['TT']->push('Cache Query','');
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'S.*',
							'cache_pages AS S,pages AS P',
							'S.hash="'.$GLOBALS['TYPO3_DB']->quoteStr($this->newHash, 'cache_pages').'"
								AND S.page_id=P.uid
								AND S.expires > '.intval($GLOBALS['EXEC_TIME']).'
								AND NOT P.deleted
								AND NOT P.hidden
								AND P.starttime<='.intval($GLOBALS['EXEC_TIME']).'
								AND (P.endtime=0 OR P.endtime>'.intval($GLOBALS['EXEC_TIME']).')'
						);
			$GLOBALS['TT']->pull();
			$GLOBALS['TT']->push('Cache Row','');
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$this->config = unserialize($row['cache_data']);		// Fetches the lowlevel config stored with the cached data
					$this->content = $row['HTML'];	// Getting the content
					$this->cacheContentFlag=1;	// Setting flag, so we know, that some cached content is gotten.

					if ($this->TYPO3_CONF_VARS['FE']['debug'] || $this->config['config']['debug'])	{
						$this->content.=chr(10).'<!-- Cached page generated '.Date('d/m Y H:i', $row['tstamp']).'. Expires '.Date('d/m Y H:i', $row['expires']).' -->';
					}
				}
			$GLOBALS['TT']->pull();

			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
	}

	/**
	 * Calculates the cache-hash
	 * This hash is unique to the template, the variables ->id, ->type, ->gr_list (list of groups), ->MP (Mount Points) and cHash array
	 * Used to get and later store the cached data.
	 *
	 * @return	string		MD5 hash of $this->hash_base which is a serialized version of there variables.
	 * @access private
	 * @see getFromCache()
	 */
	function getHash()	{
		$this->hash_base = serialize(
			array(
				'all' => $this->all,
				'id' => intval($this->id),
				'type' => intval($this->type),
				'gr_list' => $this->gr_list,
				'MP' => $this->MP,
				'cHash' => $this->cHash_array
			)
		);

		return md5($this->hash_base);
	}

	/**
	 * Checks if config-array exists already but if not, gets it
	 *
	 * @return	void
	 */
	function getConfigArray()	{
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
					$this->printError('The page is not configured! [type= '.$this->type.']['.$this->sPre.']');
					exit;
				} else {
					$this->config['config']=Array();

						// Filling the config-array.
					if (is_array($this->tmpl->setup['config.']))	{
						$this->config['config'] = $this->tmpl->setup['config.'];
					}
					if (is_array($this->pSetup['config.']))	{
						reset($this->pSetup['config.']);
						while(list($theK,$theV)=each($this->pSetup['config.']))	{
							$this->config['config'][$theK] = $theV;
						}
					}
						// if .simulateStaticDocuments was not present, the default value will rule.
					if (!isset($this->config['config']['simulateStaticDocuments']))	{
						$this->config['config']['simulateStaticDocuments'] = $this->TYPO3_CONF_VARS['FE']['simulateStaticDocuments'];
					}

							// Processing for the config_array:
					$this->config['rootLine'] = $this->tmpl->rootLine;
					$this->config['mainScript'] = trim($this->config['config']['mainScript']) ? trim($this->config['config']['mainScript']) : 'index.php';

						// STAT:
					$theLogFile = $this->TYPO3_CONF_VARS['FE']['logfile_dir'].$this->config['config']['stat_apache_logfile'];
					if ($this->config['config']['stat_apache'] &&
						$this->config['config']['stat_apache_logfile'] &&
						!strstr($this->config['config']['stat_apache_logfile'],'/') &&
						@is_dir($this->TYPO3_CONF_VARS['FE']['logfile_dir']) && @is_file($theLogFile)	&& @is_writeable($theLogFile))	{
							$this->config['stat_vars']['logFile'] = $theLogFile;
							$shortTitle = substr(ereg_replace('[^\.[:alnum:]_-]','_',$this->page['title']),0,30);
							$pageName = $this->config['config']['stat_apache_pagenames'] ? $this->config['config']['stat_apache_pagenames'] : '[path][title]--[uid].html';
							$pageName = str_replace('[title]', $shortTitle ,$pageName);
							$pageName = str_replace('[uid]',$this->page['uid'],$pageName);
							$pageName = str_replace('[alias]',$this->page['alias'],$pageName);
							$pageName = str_replace('[type]',$this->page['type'],$pageName);
							$temp = $this->config['rootLine'];
							array_pop($temp);
							$len = t3lib_div::intInRange($this->config['config']['stat_titleLen'],1,100,20);
							$pageName = str_replace('[path]', ereg_replace('[^\.[:alnum:]\/_-]','_',$this->sys_page->getPathFromRootline($temp,$len)).'/' ,$pageName);
							$this->config['stat_vars']['pageName'] = $pageName;
					}
					$this->config['FEData'] = $this->tmpl->setup['FEData'];
					$this->config['FEData.'] = $this->tmpl->setup['FEData.'];
				}
				$GLOBALS['TT']->pull();
			} else {
				$this->printError('No template found!');
				exit;
			}
		}
			// No cache
		if ($this->config['config']['no_cache'])	{$this->set_no_cache();}		// Set $this->no_cache true if the config.no_cache value is set!

			// Check PATH_INFO url
		if ($this->absRefPrefix_force && strcmp($this->config['config']['simulateStaticDocuments'],'PATH_INFO'))	{
			$redirectUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR').'index.php?id='.$this->id.'&type='.$this->type;
			if ($this->config['config']['simulateStaticDocuments_dontRedirectPathInfoError'])	{
				$this->printError('PATH_INFO was not configured for this website, and the URL tries to find the page by PATH_INFO!<br /><br /><a href="'.htmlspecialchars($redirectUrl).'">Click here to get to the right page.</a>','Error: PATH_INFO not configured');
			} else {
				header('Location: '.t3lib_div::locationHeaderUrl($redirectUrl));
			}
			exit;
//			$this->set_no_cache();	// Set no_cache if PATH_INFO is NOT used as simulateStaticDoc. and if absRefPrefix_force shows that such an URL has been passed along.
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
			$tempHash = md5('tables.php:'.
				filemtime(TYPO3_extTableDef_script ? PATH_typo3conf.TYPO3_extTableDef_script : PATH_t3lib.'stddb/tables.php').
				(TYPO3_extTableDef_script?filemtime(PATH_typo3conf.TYPO3_extTableDef_script):'').
				($GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] ? filemtime(PATH_typo3conf.$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'].'_ext_tables.php') : '')
			);
			$TCA = unserialize($this->sys_page->getHash($tempHash, 0));
			if (!$TCA)	{
				$this->includeTCA(0);
				reset($TCA);
				$newTc=Array();
				while(list($key,$val)=each($TCA))		{
					$newTc[$key]['ctrl'] = $val['ctrl'];
					$newTc[$key]['feInterface']=$val['feInterface'];
				}
				$TCA=$newTc;
				$this->sys_page->storeHash($tempHash, serialize($newTc), 'SHORT TC');
			}
		}
		$GLOBALS['TT']->pull();
	}

	/**
	 * Includes full TCA.
	 * Normally in the frontend only a part of the global $TCA array is loaded, for instance the "ctrL" part. Thus it doesn't take up too much memory.
	 * If you need the FULL TCA available for some reason (like plugins using it) you should call this function which will include the FULL TCA.
	 * Global vars $TCA, $PAGES_TYPES, $LANG_GENERAL_LABELS can/will be affected.
	 * The flag $this->TCAloaded will make sure that such an inclusion happens only once since; If $this->TCAloaded is set, nothing is included.
	 *
	 * @param	boolean		Probably, keep hands of this value. Just don't set it. (This may affect the first-ever time this function is called since if you set it to zero/false any subsequent call will still trigger the inclusion; In other words, this value will be set in $this->TCAloaded after inclusion and therefore if its false, another inclusion will be possible on the next call. See ->getCompressedTCarray())
	 * @return	void
	 * @see getCompressedTCarray()
	 */
	function includeTCA($TCAloaded=1)	{
		global $TCA, $PAGES_TYPES, $LANG_GENERAL_LABELS, $TBE_MODULES;
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
	 * Setting the language key that'll be used by the current page.
	 * In this function it should be checked, 1) that this language exists, 2) that a page_overlay_record exists, .. and if not the default language, 0 (zero), should be set.
	 *
	 * @return	void
	 * @access private
	 */
	function settingLanguage()	{
		$this->sys_language_uid = intval($this->config['config']['sys_language_uid']);
		$olRec = $this->sys_page->getPageOverlay($this->id,$this->sys_language_uid);

		if (!count($olRec))	{
			$this->sys_language_uid=0;
		} else {
			$this->page = $this->sys_page->getPageOverlay($this->page,$this->sys_language_uid);
		}
		$this->sys_page->sys_language_uid = $this->sys_language_uid;

			// Updating content of the two rootLines IF the language key is set!
		if ($this->sys_language_uid && is_array($this->tmpl->rootLine))	{
			reset($this->tmpl->rootLine);
			while(list($rLk)=each($this->tmpl->rootLine))	{
				$this->tmpl->rootLine[$rLk] = $this->sys_page->getPageOverlay($this->tmpl->rootLine[$rLk]);
			}
		}
		if ($this->sys_language_uid && is_array($this->rootLine))	{
			reset($this->rootLine);
			while(list($rLk)=each($this->rootLine))	{
				$this->rootLine[$rLk] = $this->sys_page->getPageOverlay($this->rootLine[$rLk]);
			}
		}

			// Finding the ISO code:
		if (t3lib_extMgm::isLoaded('static_info_tables') && $this->sys_language_uid)	{
			$sys_language_row = $this->sys_page->getRawRecord('sys_language',$this->sys_language_uid,'static_lang_isocode');
			if (is_array($sys_language_row) && $sys_language_row['static_lang_isocode'])	{
				$stLrow = $this->sys_page->getRawRecord('static_languages',$sys_language_row['static_lang_isocode'],'lg_iso_2');
				$this->sys_language_isocode=$stLrow['lg_iso_2'];
			}
		}
	}

	/**
	 * Checks if any email-submissions or submission via the fe_tce
	 *
	 * @return	string		'email' if a formmail has been send, 'fe_tce' if front-end data submission (like forums, guestbooks) is send. '' if none.
	 */
	function checkDataSubmission()	{
		global $HTTP_POST_VARS;

		if ($HTTP_POST_VARS['formtype_db'] || $HTTP_POST_VARS['formtype_mail'])	{
			$refInfo = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
			if (t3lib_div::getIndpEnv('TYPO3_HOST_ONLY')==$refInfo['host'] || $this->TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
				if ($this->locDataCheck($HTTP_POST_VARS['locationData']))	{
					$ret = '';
					if ($HTTP_POST_VARS['formtype_mail'])	{
						$ret = 'email';
					} elseif ($HTTP_POST_VARS['formtype_db'] && is_array($HTTP_POST_VARS['data']))	{
						$ret = 'fe_tce';
					}
					$GLOBALS['TT']->setTSlogMessage('"Check Data Submission": Return value: '.$ret,0);
					return $ret;
				}
			} else $GLOBALS['TT']->setTSlogMessage('"Check Data Submission": HTTP_HOST and REFERER HOST did not match when processing submitted formdata!',3);
		}
	}

	/**
	 * Processes submitted user data (obsolete "Frontend TCE")
	 *
	 * @return	void
	 * @see tslib_feTCE
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=342&cHash=fdf55adb3b
	 */
	function fe_tce()	{
		$fe_tce = t3lib_div::makeInstance('tslib_feTCE');
		$fe_tce->start(t3lib_div::_POST('data'),$this->config['FEData.']);
		$fe_tce->includeScripts();
	}

	/**
	 * Checks if a formmail submission can be sent as email
	 *
	 * @param	string		The input from $GLOBALS['HTTP_POST_VARS']['locationData']
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
		unset($EMAIL_VARS['locationData']);
		unset($EMAIL_VARS['formtype_mail']);

		$integrityCheck = $this->TYPO3_CONF_VARS['FE']['strictFormmail'];

			// Check recipient field:
		$encodedFields = explode(',','recipient,recipient_copy');	// These two fields are the ones which contain recipient addresses that can be misused to send mail from foreign servers.
		foreach($encodedFields as $fieldKey)	{
			if (strlen($EMAIL_VARS[$fieldKey]))	{
				if ($res = $this->codeString($EMAIL_VARS[$fieldKey], TRUE))	{	// Decode...
					$EMAIL_VARS[$fieldKey] = $res;	// Set value if OK
				} elseif ($integrityCheck)	{	// Otherwise abort:
					$GLOBALS['TT']->setTSlogMessage('"Formmail" discovered a field ('.$fieldKey.') which could not be decoded to a valid string. Sending formmail aborted due to security reasons!',3);
					return FALSE;
				} else {
					$GLOBALS['TT']->setTSlogMessage('"Formmail" discovered a field ('.$fieldKey.') which could not be decoded to a valid string. The security level accepts this, but you should consider a correct coding though!',2);
				}
			}
		}

			// Hook for preprocessing of the content for formmails:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sendFormmail-PreProcClass']))	{
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['sendFormmail-PreProcClass'] as $_classRef)	{
				$_procObj = &t3lib_div::getUserObj($_classRef);
				$EMAIL_VARS = $_procObj->sendFormmail_preProcessVariables($EMAIL_VARS,$this);
			}
		}

		$formmail->start($EMAIL_VARS);
		$formmail->sendtheMail();
		$GLOBALS['TT']->setTSlogMessage('"Formmail" invoked, sending mail to '.$EMAIL_VARS['recipient'],0);
	}

	/**
	 * Checks if jumpurl is set.
	 * This function also takes care of jumpurl utilized by the Direct Mail module (ext: direct_mail) which may set an integer value for jumpurl which refers to a link in a certain mail-record, mid
	 *
	 * @return	void
	 */
	function checkJumpUrl()	{
		global $TCA;

		$mid = t3lib_div::_GP('mid');		// mail id, if direct mail link
		$rid = t3lib_div::_GP('rid');		// recipient id, if direct mail link
		if ((strcmp($this->jumpurl,'') && ((t3lib_div::getIndpEnv('HTTP_REFERER') || $this->TYPO3_CONF_VARS['SYS']['doNotCheckReferer']) || $mid)) || ($this->jumpurl = $this->sys_page->getExtURL($this->page,$this->config['config']['disablePageExternalUrl'])))	{
			if ($mid && is_array($TCA['sys_dmail']))	{	// Yes, it's OK if the link comes from a direct mail. AND sys_dmail module has installed the table, sys_dmail (and therefore we expect sys_dmail_maillog as well!)
				$temp_recip=explode('_',$rid);
				$url_id=0;
				if (t3lib_div::testInt($this->jumpurl))	{
					$temp_res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('mailContent', 'sys_dmail', 'uid='.intval($mid));
					if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($temp_res))	{
						$temp_unpackedMail = unserialize($row['mailContent']);
						$url_id=$this->jumpurl;
						if ($this->jumpurl>=0)	{
							$responseType=1;	// Link (number)
							$this->jumpurl = $temp_unpackedMail['html']['hrefs'][$url_id]['absRef'];
						} else {
							$responseType=2;	// Link (number, plaintext)
							$this->jumpurl = $temp_unpackedMail['plain']['link_ids'][abs($url_id)];
						}
						switch($temp_recip[0])	{
							case 't':
								$theTable = 'tt_address';
							break;
							case 'f':
								$theTable = 'fe_users';
							break;
							default:
								$theTable='';
							break;
						}
						if ($theTable)	{
							$recipRow = $this->sys_page->getRawRecord($theTable,$temp_recip[1]);
							if (is_array($recipRow))	{
//								debug($recipRow);
								$authCode = t3lib_div::stdAuthCode($recipRow['uid']);
								$rowFieldsArray = explode(',', 'uid,name,title,email,phone,www,address,company,city,zip,country,fax,firstname');
								reset($rowFieldsArray);
								while(list(,$substField)=each($rowFieldsArray))	{
									$this->jumpurl = str_replace('###USER_'.$substField.'###', $recipRow[$substField], $this->jumpurl);
								}
								$this->jumpurl = str_replace('###SYS_TABLE_NAME###', $tableNameChar, $this->jumpurl);	// Put in the tablename of the userinformation
								$this->jumpurl = str_replace('###SYS_MAIL_ID###', $mid, $this->jumpurl);	// Put in the uid of the mail-record
								$this->jumpurl = str_replace('###SYS_AUTHCODE###', $authCode, $this->jumpurl);

						//		debug($this->jumpurl);
							}
						}
					}

					$GLOBALS['TYPO3_DB']->sql_free_result($temp_res);

					if (!$this->jumpurl)	die('Error: No further link. Please report error to the mail sender.');
				} else {
					$responseType=-1;	// received (url, dmailerping)
				}
				if ($responseType!=0)	{
					$insertFields = array(
						'mid' => intval($mid),
						'rtbl' => $temp_recip[0],
						'rid' => intval($temp_recip[1]),
						'tstamp' => time(),
						'url' => $this->jumpurl,
						'response_type' => intval($responseType),
						'url_id' => intval($url_id)
					);

					$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_dmail_maillog', $insertFields);
				}
			}
		} else {
			unset($this->jumpurl);
		}
	}

	/**
	 * Sends a header 'Location' to jumpurl, if jumpurl is set.
	 * Will exit if a location header is sent (for instance if JumpUrl was triggered)
	 *
	 * @return	void
	 */
	function jumpUrl()	{
		if ($this->jumpurl)	{
			if (t3lib_div::_GP('juSecure'))	{
				$hArr = array(
					$this->jumpurl,
					t3lib_div::_GP('locationData'),
					$this->TYPO3_CONF_VARS['SYS']['encryptionKey']
				);
				$calcJuHash=t3lib_div::shortMD5(serialize($hArr));
				$locationData = t3lib_div::_GP('locationData');
				$juHash = t3lib_div::_GP('juHash');
				if ($juHash == $calcJuHash)	{
					if ($this->locDataCheck($locationData))	{
						$this->jumpurl = rawurldecode($this->jumpurl);	// 211002 - goes with cObj->filelink() rawurlencode() of filenames so spaces can be allowed.
						if (@is_file($this->jumpurl))	{
							$mimeType = t3lib_div::_GP('mimeType');
							$mimeType = $mimeType ? $mimeType : 'application/octet-stream';
							Header('Content-Type: '.$mimeType);
							Header('Content-Disposition: attachment; filename='.basename($this->jumpurl));
							readfile($this->jumpurl);
							exit;
						} else die('jumpurl Secure: "'.$this->jumpurl.'" was not a valid file!');
					} else die('jumpurl Secure: locationData, '.$locationData.', was not accessible.');
				} else die('jumpurl Secure: Calculated juHash, '.$calcJuHash.', did not match the submitted juHash.');
			} else {
				$TSConf = $this->getPagesTSconfig();
				if ($TSConf['TSFE.']['jumpUrl_transferSession'])	{
					$uParts = parse_url($this->jumpurl);
					$params = '&FE_SESSION_KEY='.rawurlencode($this->fe_user->id.'-'.md5($this->fe_user->id.'/'.$this->TYPO3_CONF_VARS['SYS']['encryptionKey']));
					$this->jumpurl.=($uParts['query']?'':'?').$params;	// Add the session parameter ...
				}
				Header('Location: '.$this->jumpurl);
				exit;
			}
		}
	}

	/**
	 * Sets the URL_ID_TOKEN in the internal var, $this->getMethodUrlIdToken
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
		$this->tempContent=0;
		if (!$this->no_cache)	{
			$seconds=30;
			$stdMsg = '
			<html>
				<head>
					<title>'.htmlspecialchars($this->tmpl->printTitle($this->page['title'])).'</title>
					<meta http-equiv=Refresh Content="3; Url='.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'" />
				</head>
				<body bgcolor="white">
					<font size="1" face="VERDANA,ARIAL,HELVETICA" color="#cccccc">
					<div align="center">
						<b>Page is being generated.</b><br />
						If this message does not disappear within '.$seconds.' seconds, please reload.
					</div>
					</font>
				</body>
			</html>';
			$temp_content = $this->config['config']['message_page_is_being_generated'] ? $this->config['config']['message_page_is_being_generated'] : $stdMsg;

			$this->setPageCacheContent($temp_content,'',$GLOBALS['EXEC_TIME']+$seconds);
			$this->tempContent=1;		// This flag shows that temporary content is put in the cache
		}
	}

	/**
	 * Set cache content to $this->content
	 *
	 * @return	void
	 */
	function realPageCacheContent()	{
		$cache_timeout = $this->page['cache_timeout'] ? $this->page['cache_timeout'] : ($this->cacheTimeOutDefault ? $this->cacheTimeOutDefault : 60*60*24);		// seconds until a cached page is too old
		$timeOutTime = $GLOBALS['EXEC_TIME']+$cache_timeout;
		if ($this->config['config']['cache_clearAtMidnight'])	{
			$midnightTime = mktime (0,0,0,date('m',$timeOutTime),date('d',$timeOutTime),date('Y',$timeOutTime));
			if ($midnightTime > time())	{		// If the midnight time of the expire-day is greater than the current time, we may set the timeOutTime to the new midnighttime.
				$timeOutTime=$midnightTime;
			}
		}
		$this->config['hash_base'] = $this->hash_base;
		$this->setPageCacheContent($this->content,$this->config,$timeOutTime);
	}

	/**
	 * Sets cache content; Inserts the content string into the cache_pages table.
	 *
	 * @param	string		The content to store in the HTML field of the cache table
	 * @param	mixed		The additional cache_data array, fx. $this->config
	 * @param	integer		Timestamp
	 * @return	void
	 * @see realPageCacheContent(), tempPageCacheContent()
	 */
	function setPageCacheContent($c,$d,$t)	{
		$this->clearPageCacheContent();
		$insertFields = array(
			'hash' => $this->newHash,
			'page_id' => $this->id,
			'HTML' => $c,
			'cache_data' => serialize($d),
			'expires' => $t,
			'tstamp' => time()
		);
		if ($this->page_cache_reg1)	{
			$insertFields['reg1'] = intval($this->page_cache_reg1);
		}

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_pages', $insertFields);
	}

	/**
	 * Clears cache content (for $this->newHash)
	 *
	 * @return	void
	 */
	function clearPageCacheContent()	{
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'hash="'.$GLOBALS['TYPO3_DB']->quoteStr($this->newHash, 'cache_pages').'"');
	}

	/**
	 * Clears cache content for a list of page ids
	 *
	 * @param	string		A list of INTEGER numbers which points to page uids for which to clear entries in the cache_pages table (page content cache)
	 * @return	void
	 */
	function clearPageCacheContent_pidList($pidList)	{
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('cache_pages', 'page_id IN ('.$GLOBALS['TYPO3_DB']->cleanIntList($pidList).')');
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
		ksort($this->all);
			// Same codeline as in getFromCache(). BUT $this->all has been set in the meantime, so we can't just skip this line and let it be set above! Keep this line!
		$this->newHash = $this->getHash();

			// Here we put some temporary stuff in the cache in order to let the first hit generate the page. The temporary cache will expire after a few seconds (typ. 30) or will be cleared by the rendered page, which will also clear and rewrite the cache.
		$this->tempPageCacheContent();

			// Setting locale
		if ($this->config['config']['locale_all'])	{
			# Change by Rene Fritz, 22/10 2002
			# there`s the problem that PHP parses float values in scripts wrong if the locale LC_NUMERIC is set to something with a komma as decimal point
			# this does not work in php 4.2.3
			#setlocale('LC_ALL',$this->config['config']['locale_all']);
			#setlocale('LC_NUMERIC','en_US');

			# so we set all except LC_NUMERIC
			setlocale(LC_COLLATE,$this->config['config']['locale_all']);
			setlocale(LC_CTYPE,$this->config['config']['locale_all']);
			setlocale(LC_MONETARY,$this->config['config']['locale_all']);
			setlocale(LC_TIME,$this->config['config']['locale_all']);
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
		if ($this->TYPO3_CONF_VARS['FE']['tidy_option'] == 'all')		{
			$GLOBALS['TT']->push('Tidy, all','');
				$this->content = $this->tidyHTML($this->content);
			$GLOBALS['TT']->pull();
		}

			// XHTML-clean the code, if flag set
		if ($this->doXHTML_cleaning() == 'all')		{
			$GLOBALS['TT']->push('XHTML clean, all','');
				$XHTML_clean = t3lib_div::makeInstance('t3lib_parsehtml');
				$this->content = $XHTML_clean->XHTML_clean($this->content);
			$GLOBALS['TT']->pull();
		}

			// Fix local anchors in links, if flag set
		if ($this->doLocalAnchorFix() == 'all')		{
			$GLOBALS['TT']->push('Local anchor fix, all','');
				$this->prefixLocalAnchorsWithScript();
			$GLOBALS['TT']->pull();
		}

			// Hook for post-processing of page content cached/non-cached:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']))	{
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

			// Storing page
		if (!$this->no_cache)	{
					// Tidy up the code, if flag...
			if ($this->TYPO3_CONF_VARS['FE']['tidy_option'] == 'cached')		{
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

			$this->realPageCacheContent();
		} elseif ($this->tempContent)	{		// If there happens to be temporary content in the cache and the cache was not cleared due to new content put in it... ($this->no_cache=0)
			$this->clearPageCacheContent();
		}

		if ($this->isSearchIndexPage())	{
			$GLOBALS['TT']->push('Index page','');
				$indexer = t3lib_div::makeInstance('tx_indexedsearch_indexer');
				$indexer->init($this->content,$this->config['config'],$this->id,$this->type,$this->gr_list,$this->cHash_array,$this->register['SYS_LASTCHANGED'],$this->config['rootLine']);
				$indexer->indexTypo3PageContent();
			$GLOBALS['TT']->pull();
		} elseif ($this->config['config']['index_enable'] && $this->no_cache) {
			$GLOBALS['TT']->push('Index page','');
			$GLOBALS['TT']->setTSlogMessage('Index page? No, page was set to "no_cache" and so cannot be indexed.');
			$GLOBALS['TT']->pull();
		}
		$this->setSysLastChanged();
	}

	/**
	 * Processes the INTinclude-scripts
	 *
	 * @return	void
	 */
	function INTincScript()	{
		$GLOBALS['TT']->push('Split content');
		$INTiS_splitC = explode('<!--INT_SCRIPT.',$this->content);			// Splits content with the key.
		$this->content='';
		$GLOBALS['TT']->setTSlogMessage('Parts: '.count($INTiS_splitC));
		$GLOBALS['TT']->pull();

			// Depreciated stuff:
		$this->additionalHeaderData = is_array($this->config['INTincScript_ext']['additionalHeaderData']) ? $this->config['INTincScript_ext']['additionalHeaderData'] : array();
		$this->additionalJavaScript = $this->config['INTincScript_ext']['additionalJavaScript'];
		$this->additionalCSS = $this->config['INTincScript_ext']['additionalCSS'];
		$this->JSCode = $this->additionalHeaderData['JSCode'];
		$this->JSImgCode = $this->additionalHeaderData['JSImgCode'];
		$this->divSection='';

		$INTiS_config = $GLOBALS['TSFE']->config['INTincScript'];
		reset($INTiS_splitC);
		while(list($INTiS_c,$INTiS_cPart)=each($INTiS_splitC))	{
			if (substr($INTiS_cPart,32,3)=='-->')	{	// If the split had a comment-end after 32 characters it's probably a split-string
				$GLOBALS['TT']->push('Include '.$INTiS_config[$INTiS_key]['file'],'');
				$INTiS_key = 'INT_SCRIPT.'.substr($INTiS_cPart,0,32);
				$incContent='';
				if (is_array($INTiS_config[$INTiS_key]))	{
					$INTiS_cObj = unserialize($INTiS_config[$INTiS_key]['cObj']);
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
				$this->content.= $incContent;
				$this->content.= substr($INTiS_cPart,35);
				$GLOBALS['TT']->pull($incContent);
			} else {
				$this->content.= ($c?'<!--INT_SCRIPT.':'').$INTiS_cPart;
			}
		}
		$GLOBALS['TT']->push('Substitute header section');
		$this->INTincScript_loadJSCode();
		$this->content = str_replace('<!--HD_'.$this->config['INTincScript_ext']['divKey'].'-->', implode($this->additionalHeaderData,chr(10)), $this->content);
		$this->content = str_replace('<!--TDS_'.$this->config['INTincScript_ext']['divKey'].'-->', $this->divSection, $this->content);
		$this->setAbsRefPrefix();
		$GLOBALS['TT']->pull();
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
'.implode($this->additionalJavaScript,chr(10)).'
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
'.implode($this->additionalCSS,chr(10)).'
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
	 * Returns true if page should be indexed.
	 *
	 * @return	boolean
	 */
	function isSearchIndexPage()	{
		return t3lib_extMgm::isLoaded('indexed_search') && $this->config['config']['index_enable'] && !$this->no_cache;
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
		return $this->config['config']['prefixLocalAnchors'];
	}
















	/********************************************
	 *
	 * Finished off; outputting, storing session data, statistics...
	 *
	 *******************************************/

	/**
	 * Determines if content should be outputted.
	 * Outputting content is done only if jumpurl is NOT set.
	 *
	 * @return	boolean		Returns true if $this->jumpurl is not set.
	 */
	function isOutputting()	{
		return (!$this->jumpurl);
	}

	/**
	 * Processes the output before it's actually outputted. Sends headers also.
	 * This includes substituting the USERNAME comment, getMethodUrlIdToken, sending additional headers (as defined in the TypoScript "config.additionalheaders" object), tidy'ing content, XHTML cleaning content (if configured)
	 * Works on $this->content
	 *
	 * @return	void
	 */
	function processOutput()	{
			// Substitutes username mark with the username
		if ($this->fe_user->user['uid'])	{
			$token = trim($this->config['config']['USERNAME_substToken']);
			$this->content = str_replace($token ? $token : '<!--###USERNAME###-->',$this->fe_user->user['username'],$this->content);
		}
			// Substitutes get_URL_ID in case of GET-fallback
		if ($this->getMethodUrlIdToken)	{
			$this->content = str_replace($this->getMethodUrlIdToken, $this->fe_user->get_URL_ID, $this->content);
		}

			// Set header for charset-encoding if set. Added by RL 17.10.03
		if ($this->config['config']['metaCharset'])	{
			$headLine = 'Content-Type:text/html;charset='.trim($this->config['config']['metaCharset']);
			header ($headLine);
		}

			// Set headers, if any
		if ($this->config['config']['additionalHeaders'])	{
			$headerArray = explode('|', $this->config['config']['additionalHeaders']);
			while(list(,$headLine)=each($headerArray))	{
				$headLine = trim($headLine);
				header($headLine);
			}
		}

				// Tidy up the code, if flag...
		if ($this->TYPO3_CONF_VARS['FE']['tidy_option'] == 'output')		{
			$GLOBALS['TT']->push('Tidy, output','');
				$this->content = $this->tidyHTML($this->content);
			$GLOBALS['TT']->pull();
		}
			// XHTML-clean the code, if flag set
		if ($this->doXHTML_cleaning() == 'output')		{
			$GLOBALS['TT']->push('XHTML clean, output','');
				$XHTML_clean = t3lib_div::makeInstance('t3lib_parsehtml');
				$this->content = $XHTML_clean->XHTML_clean($this->content);
			$GLOBALS['TT']->pull();
		}
			// Fix local anchors in links, if flag set
		if ($this->doLocalAnchorFix() == 'output')		{
			$GLOBALS['TT']->push('Local anchor fix, output','');
				$this->prefixLocalAnchorsWithScript();
			$GLOBALS['TT']->pull();
		}

			// Hook for post-processing of page content before output:
		if (is_array($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']))	{
			$_params = array('pObj' => &$this);
			foreach($this->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'] as $_funcRef)	{
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

/*		if ($this->beUserLogin && t3lib_div::_GP('ADMCMD_view'))	{		// This is a try to change target=_top to target=_self if pages are shown in the Web>View module...
			$this->content = str_replace('target="_top"','target="_self"',$this->content);
			$this->content = str_replace('target=_top','target="_self"',$this->content);
		}*/
	}

	/**
	 * Determines if any EXTincScripts should be included
	 *
	 * @return	boolean		True, if external php scripts should be included (set by PHP_SCRIPT_EXT cObjects)
	 * @see tslib_cObj::PHP_SCRIPT
	 */
	function isEXTincScript()	{
		return is_array($this->config['EXTincScript']);
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
		$this->scriptParseTime = $GLOBALS['TT']->convertMicrotime($GLOBALS['TYPO3_MISC']['microtime_end'])
								- $GLOBALS['TT']->convertMicrotime($GLOBALS['TYPO3_MISC']['microtime_start'])
								- ($GLOBALS['TT']->convertMicrotime($GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'])-$GLOBALS['TT']->convertMicrotime($GLOBALS['TYPO3_MISC']['microtime_BE_USER_start']));
	}

	/**
	 * Saves hit statistics
	 *
	 * @return	void
	 */
	function statistics()	{
		if ($this->config['config']['stat'] &&
				(!strcmp('',$this->config['config']['stat_typeNumList']) || t3lib_div::inList(str_replace(' ','',$this->config['config']['stat_typeNumList']), $this->type)) &&
				(!$this->config['config']['stat_excludeBEuserHits'] || !$this->beUserLogin) &&
				(!$this->config['config']['stat_excludeIPList'] || !t3lib_div::inList(str_replace(' ','',$this->config['config']['stat_excludeIPList']), t3lib_div::getIndpEnv('REMOTE_ADDR')))) {
			$GLOBALS['TT']->push('Stat');
				if (t3lib_extMgm::isLoaded('sys_stat') && $this->config['config']['stat_mysql'])	{

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

					$GLOBALS['TT']->push('Store SQL');
						$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_stat', $insertFields);
					$GLOBALS['TT']->pull();
				}

					// Apache:
				if ($this->config['config']['stat_apache'] && $this->config['stat_vars']['pageName'])	{
					if (@is_file($this->config['stat_vars']['logFile']) && TYPO3_OS!='WIN')	{
						$LogLine = ((t3lib_div::getIndpEnv('REMOTE_HOST') && !$this->config['config']['stat_apache_noHost']) ? t3lib_div::getIndpEnv('REMOTE_HOST') : t3lib_div::getIndpEnv('REMOTE_ADDR')).' - - '.Date('[d/M/Y:H:i:s +0000]',$GLOBALS['EXEC_TIME']).' "GET '.$this->config['stat_vars']['pageName'].' HTTP/1.1" 200 '.strlen($this->content);
						if (!$this->config['config']['stat_apache_notExtended'])	{
							$LogLine.= ' "'.t3lib_div::getIndpEnv('HTTP_REFERER').'" "'.t3lib_div::getIndpEnv('HTTP_USER_AGENT').'"';
						}

						switch($this->TYPO3_CONF_VARS['FE']['logfile_write'])	{
							case 'fputs':
								$GLOBALS['TT']->push('Write to log file (fputs)');
									$logfilehandle = fopen(PATH_site.$this->config['stat_vars']['logFile'], 'a');
									fputs($logfilehandle, $LogLine."\n");
									@fclose($logfilehandle);
								$GLOBALS['TT']->pull();
							break;
							default:
								$GLOBALS['TT']->push('Write to log file (echo)');
									$execCmd = 'echo "'.addslashes($LogLine).'" >> '.PATH_site.$this->config['stat_vars']['logFile'];
									exec($execCmd);
								$GLOBALS['TT']->pull();
							break;
						}

						$GLOBALS['TT']->setTSlogMessage('Writing to logfile: OK',0);
					} else {
						$GLOBALS['TT']->setTSlogMessage('Writing to logfile: Error - logFile did not exist or OS is Windows!',3);
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
		if ($this->fePreview)	{
				$stdMsg = '
				<br />
				<div align="center">
					<table border="3" bordercolor="black" cellpadding="2" bgcolor="red">
						<tr>
							<td>&nbsp;&nbsp;<font face="Verdana" size="1"><b>PREVIEW!</b></font>&nbsp;&nbsp;</td>
						</tr>
					</table>
				</div>';
				$temp_content = $this->config['config']['message_preview'] ? $this->config['config']['message_preview'] : $stdMsg;
				echo $temp_content;
		}
	}

	/**
	 * Returns a link to the login screen with redirect to the front-end
	 *
	 * @return	string		HTML, a tag for a link to the backend.
	 */
	function beLoginLinkIPList()	{
		if ($this->config['config']['beLoginLinkIPList'])	{
			if (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $this->config['config']['beLoginLinkIPList']))	{
				$label = !$this->beUserLogin ? $this->config['config']['beLoginLinkIPList_login'] : $this->config['config']['beLoginLinkIPList_logout'];
				if ($label)	{
					if (!$this->beUserLogin)	{
						$link = '<a href="'.htmlspecialchars(TYPO3_mainDir.'index.php?redirect_url='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))).'">'.$label.'</a>';
					} else {
						$link = '<a href="'.htmlspecialchars(TYPO3_mainDir.'index.php?L=OUT&redirect_url='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI"))).'">'.$label.'</a>';
					}
					return $link;
				}
			}
		}
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
	 */
	function makeSimulFileName($inTitle,$page,$type,$addParams='',$no_cache='')	{
		$titleChars = intval($this->config['config']['simulateStaticDocuments_addTitle']);
		$out = '';
		if ($titleChars)	{
			$out = t3lib_div::convUmlauts($inTitle);
			$out= ereg_replace('[^[:alnum:]_-]','_',trim(substr($out,0,$titleChars)));
			$out= ereg_replace('_*$','',$out);
			$out= ereg_replace('^_*','',$out);
			if ($out)	$out.='.';
		}
		$enc = '';
		if (strcmp($addParams,'') && !$no_cache)	{
			switch ((string)$this->config['config']['simulateStaticDocuments_pEnc'])	{
				case 'md5':
					$md5=substr(md5($addParams),0,10);
					$enc='+M5'.$md5;

					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('md5hash', 'cache_md5params', 'md5hash="'.$GLOBALS['TYPO3_DB']->quoteStr($md5, 'cache_md5params').'"');
					if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
						$insertFields = array(
							'md5hash' => $md5,
							'tstamp' => time(),
							'type' => 1,
							'params' => $addParams
						);

						$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_md5params', $insertFields);
					}
				break;
				case 'base64':
					$enc='+B6'.str_replace('=','_',str_replace('/','-',base64_encode($addParams)));
				break;
			}
		}
			// Setting page and type number:
		$url = $out.$page.$enc;
		$url.= ($type || $out || !$this->config['config']['simulateStaticDocuments_noTypeIfNoTitle']) ? '.'.$type : '';
		return $url;
	}

	/**
	 * Processes a query-string with GET-parameters and returns two strings, one with the parameters that CAN be encoded and one array with those which can't be encoded (encoded by the M5 or B6 methods)
	 *
	 * @param	string		Query string to analyse
	 * @return	array		Two num keys returned, first is the parameters that MAY be encoded, second is the non-encodable parameters.
	 * @see makeSimulFileName(), t3lib_tstemplate::linkData()
	 */
	function simulateStaticDocuments_pEnc_onlyP_proc($linkVars)	{
		$remainLinkVars = '';
		if (strcmp($linkVars,''))	{
			$p = explode('&',$linkVars);
			sort($p);	// This sorts the parameters - and may not be needed and further it will generate new MD5 hashes in many cases. Maybe not so smart. Hmm?
			$rem = array();
			foreach($p as $k => $v)	{
				if (strlen($v))	{
					list($pName) = explode('=',$v,2);
					$pName = rawurldecode($pName);
					if (!$this->pEncAllowedParamNames[$pName])	{
						unset($p[$k]);
						$rem[] = $v;
					}
				} else unset($p[$k]);
			}

			$linkVars = count($p) ? '&'.implode('&',$p) : '';
			$remainLinkVars = count($rem) ? '&'.implode('&',$rem) : '';
		}
		return array($linkVars, $remainLinkVars);
	}

	/**
	 * Returns the simulated static file name (*.html) for the current page (using the page record in $this->page)
	 *
	 * @return	string		The filename (without path)
	 * @see makeSimulFileName(), publish.php
	 */
	function getSimulFileName()	{
		$url='';
		$url.=$this->makeSimulFileName($this->page['title'], $this->page['alias']?$this->page['alias']:$this->id, $this->type).'.html';
		return $url;
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
		for ($a=0; $a<strlen($string); $a++)	{
			$charValue = ord(substr($string,$a,1));
			$charValue+= intval($this->spamProtectEmailAddresses)*($back?-1:1);
			$out.= chr($charValue);
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

		if ($decode) {
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
	 * Encrypts a strings by XOR'ing all characters with the ASCII value of a character in $this->TYPO3_CONF_VARS['SYS']['encryptionKey']
	 * If $this->TYPO3_CONF_VARS['SYS']['encryptionKey'] is empty, 255 is used for XOR'ing. Using XOR means that the string can be decrypted by simply calling the function again - just like rot-13 works (but in this case for ANY byte value).
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function roundTripCryptString($string)	{
		$out = '';
		$strLen = strlen($string);
		$cryptLen = strlen($this->TYPO3_CONF_VARS['SYS']['encryptionKey']);

		for ($a=0; $a < $strLen; $a++)	{
			$xorVal = $cryptLen>0 ? ord($this->TYPO3_CONF_VARS['SYS']['encryptionKey']{($a%$cryptLen)}) : 255;
			$out.= chr(ord($string{$a}) ^ $xorVal);
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
	 * Substitute the path's to files in the media/ folder like icons used in static_template of TypoScript
	 * Works on $this->content
	 *
	 * @return	void
	 * @access private
	 * @see pagegen.php, INTincScript()
	 */
	function setAbsRefPrefix()	{
		if ($this->absRefPrefix)	{
			$this->content = str_replace('"media/', '"'.$this->absRefPrefix.'media/', $this->content);
			$this->content = str_replace('"fileadmin/', '"'.$this->absRefPrefix.'fileadmin/', $this->content);
		}
	}

	/**
	 * Prints error msg/header.
	 * Echoes out the HTML content
	 *
	 * @param	string		Message string
	 * @param	string		Header string
	 * @return	void
	 * @see t3lib_timeTrack::debug_typo3PrintError()
	 */
	function printError($label,$header='Error!')	{
		t3lib_timeTrack::debug_typo3PrintError($header,$label,0);
	}

	/**
	 * Updates the tstamp field of a cache_md5params record to the current time.
	 *
	 * @param	string		The hash string identifying the cache_md5params record for which to update the "tstamp" field to the current time.
	 * @return	void
	 * @access private
	 */
	function updateMD5paramsRecord($hash)	{
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('cache_md5params', 'md5hash="'.$GLOBALS['TYPO3_DB']->quoteStr($hash, 'cache_md5params').'"', array('tstamp' => time()));
	}

	/**
	 * Pass the content through tidy - a little program that cleans up HTML-code
	 * Requires $this->TYPO3_CONF_VARS['FE']['tidy'] to be true and $this->TYPO3_CONF_VARS['FE']['tidy_path'] to contain the filename/path of tidy including clean-up arguments for tidy. See default value in TYPO3_CONF_VARS in t3lib/config_default.php
	 *
	 * @param	string		The page content to clean up. Will be written to a temporary file which "tidy" is then asked to clean up. File content is read back and returned.
	 * @return	string		Returns the
	 */
	function tidyHTML($content)		{
		if ($this->TYPO3_CONF_VARS['FE']['tidy'] && $this->TYPO3_CONF_VARS['FE']['tidy_path'])	{
			$oldContent = $content;
			$fname = t3lib_div::tempnam('Typo3_Tidydoc_');		// Create temporary name
			@unlink ($fname);	// Delete if exists, just to be safe.
			$fp = fopen ($fname,'wb');	// Open for writing
			fputs ($fp, $content);	// Put $content
			@fclose ($fp);	// Close

			exec ($this->TYPO3_CONF_VARS['FE']['tidy_path'].' '.$fname, $output);			// run the $content through 'tidy', which formats the HTML to nice code.
			@unlink ($fname);	// Delete the tempfile again
			$content = implode($output,chr(10));
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
		$scriptPath = substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),strlen(t3lib_div::getIndpEnv('TYPO3_SITE_URL')));
		$this->content = eregi_replace('(<(a|area)[[:space:]]+href=")(#[^"]*")','\1'.htmlspecialchars($scriptPath).'\3',$this->content);
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
		reset($this->rootLine);
		while(list(,$rC)=each($this->rootLine))	{
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
			reset($this->rootLine);
			$TSdataArray = array();
			$TSdataArray[] = $this->TYPO3_CONF_VARS['BE']['defaultPageTSconfig'];	// Setting default configuration:
			while(list($k,$v)=each($this->rootLine))	{
				$TSdataArray[]=$v['TSconfig'];
			}
				// Parsing the user TS (or getting from cache)
			$TSdataArray = t3lib_TSparser::checkIncludeLines_array($TSdataArray);
			$userTS = implode($TSdataArray,chr(10).'[GLOBAL]'.chr(10));
			$hash = md5('pageTS:'.$userTS);
			$cachedContent = $this->sys_page->getHash($hash,0);
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
		else if (imgObj)	{imgObj.src = eval(name+"_h.src");}
	}
		// JS function for mouse-out
	function out(name,imgObj)	{	//
		if (version == "n3" && document[name]) {document[name].src = eval(name+"_n.src");}
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
	 * @see setJS(), tslib_pibase::pi_setClassStyle()
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
	 * Seeds the random number engine.
	 *
	 * @return	void
	 */
	function make_seed() {
	    list($usec, $sec) = explode(' ', microtime());
	    $seedV = (float)$sec + ((float)$usec * 100000);
		srand($seedV);
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
	 * @return	void
	 */
	function set_no_cache()	{
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
		$urlmode=$this->config['config']['notification_email_urlmode'];	// '76', 'all', ''

		if ($urlmode)	{
			$message=t3lib_div::substUrlsInPlainText($message,$urlmode);
		}

		t3lib_div::plainMailEncoded(
			$email,
			$subject,
			$message,
			$headers,
			$this->config['config']['notification_email_encoding'],
			$this->config['config']['notification_email_charset']?$this->config['config']['notification_email_charset']:'ISO-8859-1'
		);
	}













	/**************************
	 *
	 * Localization
	 *
	 **************************/

	/**
	 * Split Label function for front-end applications.
	 *
	 * @param	string		Key string. Accepts the "LLL:" prefix.
	 * @return	string		Label value, if any.
	 */
	function sL($input)	{
		if (!$this->lang)	$this->initLLvars();

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
				$parts[0]=$extPrfx.$parts[0];
				if (!isset($this->LL_files_cache[$parts[0]]))	{	// Getting data if not cached
					$this->LL_files_cache[$parts[0]] = $this->readLLfile($parts[0]);
				}
				$this->LL_labels_cache[$this->lang][$input] = $this->csConv($this->getLLL($parts[1],$this->LL_files_cache[$parts[0]]));
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
		$file = t3lib_div::getFileAbsFileName($fileRef);
		if (@is_file($file))	{
			include($file);
		}
		return is_array($LOCAL_LANG)?$LOCAL_LANG:array();
	}

	/**
	 * Returns 'locallang' label - may need initializing by initLLvars
	 *
	 * @param	string		Local_lang key for which to return label (language is determined by $this->lang)
	 * @param	array		The locallang array in which to search
	 * @return	string		Label value of $index key.
	 */
	function getLLL($index,$LOCAL_LANG)	{
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
		$this->lang = $this->config['config']['language'] ? $this->config['config']['language'] : 'default';

		$ls = explode('|',TYPO3_languages);
		while(list($i,$v)=each($ls))	{
			if ($v==$this->lang)	{$this->langSplitIndex=$i; break;}
		}

			// Setting charsets:
		$this->siteCharset = $this->csConvObj->parse_charset($GLOBALS['TSFE']->config['config']['metaCharset'] ? $GLOBALS['TSFE']->config['config']['metaCharset'] : $GLOBALS['TSFE']->defaultCharSet);
		$this->labelsCharset = $this->csConvObj->parse_charset($this->csConvObj->charSetArray[$this->lang] ? $this->csConvObj->charSetArray[$this->lang] : 'iso-8859-1');
		if ($this->siteCharset != $this->labelsCharset)	{
			$this->convCharsetToFrom = array(
				'from' => $this->labelsCharset,
				'to' => $this->siteCharset
			);
		}
	}

	/**
	 * Converts the charset of the input string if applicable.
	 * The "from" charset is determined by the TYPO3 system charset for the current language key ($this->lang)
	 * The "to" charset is determined by the currently used charset for the page which is "iso-8859-1" by default or set by $GLOBALS['TSFE']->config['config']['metaCharset']
	 * Only if there is a difference between the two charsets will a conversion be made
	 * The conversion is done real-time - no caching for performance at this point!
	 *
	 * @param	string		String to convert charset for
	 * @param	string		Optional "from" charset.
	 * @return	string		Output string, converted if needed.
	 * @see initLLvars(), t3lib_cs
	 */
	function csConv($str,$from='')	{
		if (!$this->lang)	$this->initLLvars();

		if ($from)	{
			$output = $this->csConvObj->conv($str,$this->csConvObj->parse_charset($from),$this->siteCharset,1);
			return $output ? $output : $str;
		} elseif (is_array($this->convCharsetToFrom))	{
			return $this->csConvObj->conv($str,$this->convCharsetToFrom['from'],$this->convCharsetToFrom['to'],1);
		} else {
			return $str;
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_fe.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_fe.php']);
}
?>