<?php

/**
	'SYS' => Array(			// System related concerning both frontend and backend.
		'sitename' => 'TYPO3',					// Name of the base-site. This title shows up in the root of the tree structure if you're an 'admin' backend user.
		'compat_version' => '3.8',				// Compatibility version. TYPO3 behavior will try to be compatible with the output from the TYPO3 version set here. It is recommended to change this setting with the Upgrade Wizard.
		'encryptionKey' => '',					// This is a "salt" used for various kinds of encryption, CRC checksums and validations. You can enter any rubbish string here but try to keep it secret. You should notice that a change to this value might invalidate temporary information, URLs etc. At least, clear all cache if you change this so any such information can be rebuild with the new key.
		'cookieDomain' => '',					// When setting the value to ".example.com" (replace example.com with your domain!), login sessions will be shared across subdomains. Alternatively, if you have more than one domain with sub-domains, you can set the value to a regular expression to match against the domain of the HTTP request. The result of the match is used as the domain for the cookie. eg. /\.(example1|example2)\.com$/ or /\.(example1\.com)|(example2\.net)$/
		'doNotCheckReferer' => 0,				// Boolean. If set, it's NOT checked numerous places that the refering host is the same as the current. This is an option you should set if you have problems with proxies not passing the HTTP_REFERER variable.
		'recursiveDomainSearch' => 0,			// Boolean. If set, the search for domain records will be done recursively by stripping parts of the host name off until a matching domain record is found.
		'devIPmask' => '127.0.0.1,::1',			// Defines a list of IP addresses which will allow development-output to display. The debug() function will use this as a filter. See the function t3lib_div::cmpIP() for details on syntax. Setting this to blank value will deny all. Setting to "*" will allow all.
		'sqlDebug' => 0,						// Boolean. If set, then database queries that fails are outputted in browser. For development.
		'enable_DLOG' => FALSE,					// Whether the developer log is enabled. See constant "TYPO3_DLOG"
		'ddmmyy' => 'd-m-y',					// Format of Date-Month-Year - see PHP-function date()
		'hhmm' => 'H:i',						// Format of Hours-Minutes - see PHP-function date()
		'USdateFormat' => 0,					// Boolean. If true, dates entered in the TCEforms of the backend will be formatted mm-dd-yyyy
		'loginCopyrightWarrantyProvider' => '',	// String: If you provide warranty for TYPO3 to your customers insert you (company) name here. It will appear in the login-dialog as the warranty provider. (You must also set URL below).
		'loginCopyrightWarrantyURL' => '',		// String: Add the URL where you explain the extend of the warranty you provide. This URL is displayed in the login dialog as the place where people can learn more about the conditions of your warranty. Must be set (more than 10 chars) in addition with the 'loginCopyrightWarrantyProvider' message.
		'loginCopyrightShowVersion' => 0,		// Boolean: If set, the current TYPO3 version is shown.
		'curlUse' => 0,							// Boolean: If set, try to use Curl to fetch external URLs
		'curlProxyServer' => '',				// String: Proxyserver as http://proxy:port/.
		'curlProxyTunnel' => 0,					// Boolean: If set, use a tunneled connection through the proxy (usefull for websense etc.).
		'curlProxyUserPass' => '',				// String: Proxyserver authentication user:pass.
		'form_enctype' => 'multipart/form-data',	// String: This is the default form encoding type for most forms in TYPO3. It allows for file uploads to be in the form. However if file-upload is disabled for your PHP version even ordinary data sent with this encryption will not get to the server. So if you have file_upload disabled, you will have to change this to eg. 'application/x-www-form-urlencoded'
		'textfile_ext' => 'txt,html,htm,css,inc,php,php3,tmpl,js,sql',	// Text file extensions. Those that can be edited. php,php3 cannot be edited in webspace if they are disallowed! Notice:
		'contentTable' => '',					// This is the page-content table (Normally 'tt_content')
		'T3instID' => 'N/A',					// A unique installation ID - not used yet. The idea is that a TYPO3 installation can identify itself by this ID string to the Extension Repository on TYPO3.org so that we can keep a realistic count of serious TYPO3 installations.
		'binPath' => '', 						// String: List of absolute paths where external programs should be searched for. Eg. '/usr/local/webbin/,/home/xyz/bin/'. (ImageMagick path have to be configured separately)
		'binSetup' => '', 						// String (textarea): List of programs (separated by newline or comma). By default programs will be searched in default paths and the special paths defined by 'binPath'. When PHP has openbasedir enabled the programs can not be found and have to be configured here. Example: 'perl=/usr/bin/perl,unzip=/usr/local/bin/unzip'
		't3lib_cs_convMethod' => '',			// String (values: "iconv", "recode", "mbstring", default is homemade PHP-code). Defines which of these PHP-features to use for various Charset conversing functions in t3lib_cs. Will speed up charset conversion radically.
		't3lib_cs_utils' => '',					// String (values: "iconv" - PHP 5.0 only!, "mbstring", default is homemade PHP-code). Defines which of these PHP-features to use for various Charset processing functions in t3lib_cs. Will speed up charset functions radically.
		'no_pconnect' => 0,						// Boolean: If true, "connect" is used instead of "pconnect" when connecting to the database!
		'multiplyDBfieldSize' => 1,				// Double: 1-5: Amount used to multiply the DB field size when the install tool is evaluating the database size (eg. "2.5"). This is only useful e.g. if your database is iso-8859-1 encoded but you want to use utf-8 for your site. For Western European sites using utf-8 the need should not be for more than twice the normal single-byte size (2) and for Chinese / Asian languages 3 should suffice. NOTICE: It is recommended to change the native database charset instead! (see http://wiki.typo3.org/index.php/UTF-8_support for more information)
		'setDBinit' => '',						// String (textarea): Commands to send to database right after connecting, separated by newline. Ignored by the DBAL extension except for the 'native' type!
		'setMemoryLimit' => 0,					// Integer, memory_limit in MB: If more than 16, TYPO3 will try to use ini_set() to set the memory limit of PHP to the value. This works only if the function ini_set() is not disabled by your sysadmin.
		'forceReturnPath' => 0,					// Boolean: Force return path to be applied in mail() calls. If this is set, all calls to mail() done by t3lib_htmlmail will be called with '-f<return_path> as the 5th parameter. This will make the return path correct on almost all Unix systems. There is a known problem with Postfix below version 2: Mails are not sent if this option is set and Postfix is used. On Windows platforms, the return path is set via a call to ini_set. This has no effect if safe_mode in PHP is on.
		'displayErrors' => -1,					// Integer, -1,0,1,2. 0=Do not display any PHP error messages. 1=Display error messages. 2=Display only if client matches TYPO3_CONF_VARS[SYS][devIPmask]. -1=Default setting. With this option, you can override the PHP setting "display_errors". It is suggested that you set this to "0" and enable the "error_log" option in php.ini instead.
		'serverTimeZone' => 1,					// Integer, GMT offset of servers time (from time()). Default is "1" which is "GMT+1" (central european time). This value can be used in extensions that are GMT aware and wants to convert times to/from other timezones.
		'systemLog' => '',						// String, semi-colon separated list: Defines one or more logging methods. Possible methods: file,<abs-path-to-file>[,<level>];mail,<to>[/<from>][,<level>];syslog,<facility>,[,<level>];error_log[,,<level>]. "file" logs to a file, "mail" sends the log entries via mail, "syslog" uses the operating system's log, "error_log" uses the PHP error log. The level is the individual logging level (see [SYS][systemLogLevel]. Facility may be one of LOCAL0..LOCAL7, USER (on Windows USER is the only valid type).
		'systemLogLevel' => 0,					// Integer: Only messages with same or higher severity are logged; 0 is info, 1 is notice, 2 is warning, 3 is error, 4 is fatal error.
		'maxFileNameLength' => 60,				// Integer, This is the maximum file name length. The value will be taken into account by basic file operations like renaming or creation of files and folders.
		'UTF8filesystem' => 0,					// Boolean: If true and [BE][forceCharset] is set to utf-8, then TYPO3 uses utf-8 to store file names. This allows for accented Latin letters as well as any other non-latin characters like Cyrillic and Chinese.
	),

	
*/

$GLOBALS['MCA']['system'] = array (
	'general' => array (
		'title' => 'module_system_title',
	),

	'options' => array (

		/** BACKEND **/
	
		'sessionTimeout' => array (
			'categoryMain' => 'system',
			'categorySub' => 'backend',
			'tags' => array ('system', 'session', 'timeout'),
			'elementType' => 'input',
			'value' => 'LC:BE/sessionTimeout',
			'default' => 3600,
			'valueType' => 'int'
		),
		
		/** FRONTEND **/
		
		'png_to_gif' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'checkbox',
			'value' => 'LC:FE/png_to_gif',
			'default' => 0,
		),
		'tidy' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'checkbox',
			'value' => 'LC:FE/tidy',
			'default' => 0,
		),
		'tidy_option' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'selectbox',
			'elements' => array('all' => 'all', 'cached' => 'cached', 'output' => 'output'),
			'value' => 'LC:FE/tidy_option',
			'default' => 'cached',
		),
		'tidy_path' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'input',
			'value' => 'LC:FE/tidy_path',
			'size' => 40,
			'default' => 'tidy -i --quiet true --tidy-mark true -wrap 0 -raw',
		),
		
		/*
		'png_to_gif' => 0,						// Boolean. Enables conversion back to gif of all png-files generated in the frontend libraries. Notice that this leaves an increased number of temporary files in typo3temp/
		'tidy' => 0,							// Boolean. If set, the output html-code will be passed through 'tidy' which is a little program you can get from http://www.w3.org/People/Raggett/tidy/. 'Tidy' cleans the HTML-code for nice display!
		'tidy_option' => 'cached',				// options [all, cached, output]. 'all' = the content is always passed through 'tidy' before it may be stored in cache. 'cached' = only if the page is put into the cache, 'output' = only the output code just before it's echoed out.
		'tidy_path' => 'tidy -i --quiet true --tidy-mark true -wrap 0 -raw',		// Path with options for tidy. For XHTML output, add " --output-xhtml true"
		*/
		
		'logfile_dir' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'input',
			'size' => 40,
			'value' => 'LC:FE/logfile_dir',
			'default' => ''
		),
		'publish_dir' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'input',
			'size' => 40,
			'value' => 'LC:FE/publish_dir',
			'default' => ''
		),
		'addAllowedPaths' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'input',
			'size' => 40,
			'value' => 'LC:FE/addAllowedPaths',
			'default' => ''
		),
		'allowedTempPaths' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'input',
			'size' => 40,
			'value' => 'LC:FE/allowedTempPaths',
			'default' => ''
		),
		/*
		'logfile_dir' => '', 					// Path where TYPO3 should write webserver-style logfiles to. This path must be write-enabled for the webserver. If this path is outside of PATH_site, you have to allow it using [BE][lockRootPath]
		'publish_dir' => '',					// Path where TYPO3 should write staticly published documents. This path must be write-enabled for the webserver. Remember slash AFTER! Eg: 'publish/' or '/www/htdocs/publish/'. See admPanel option 'publish'
		'addAllowedPaths' => '',				// Additional relative paths (comma-list) to allow TypoScript resources be in. Should be prepended with '/'. If not, then any path where the first part is like this path will match. That is: 'myfolder/ , myarchive' will match eg. 'myfolder/', 'myarchive/', 'myarchive_one/', 'myarchive_2/' ... No check is done to see if this directory actually exists in the root of the site. Paths are matched by simply checking if these strings equals the first part of any TypoScript resource filepath. (See class template, function init() in t3lib/class.t3lib_tsparser.php)
		'allowedTempPaths' => '',				// Additional paths allowed for temporary images. Used with imgResource. Eg. 'alttypo3temp/,another_temp_dir/';
		*/
		
		'debug' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'checkbox',
			'value' => 'LC:FE/debug',
			'default' => 0
		),
		'simulateStaticDocuments' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'checkbox',
			'value' => 'LC:FE/simulateStaticDocuments',
			'default' => 0
		),
		'noPHPscriptInclude' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'checkbox',
			'value' => 'LC:FE/noPHPscriptInclude',
			'default' => 0
		),
		'strictFormmail' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'checkbox',
			'value' => 'LC:FE/strictFormmail',
			'default' => 1
		),
		'secureFormmail' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'checkbox',
			'value' => 'LC:FE/secureFormmail',
			'default' => 1
		),
		/*
		'debug' => 0,							// Boolean. If set, some debug HTML-comments may be output somewhere. Can also be set by TypoScript.
		'simulateStaticDocuments' => 0,			// Boolean. This is the default value for simulateStaticDocuments (configurable with TypoScript which overrides this, if the TypoScript value is present)
		'noPHPscriptInclude' => 0,				// Boolean. If set, PHP-scripts are not included by TypoScript configurations, unless they reside in 'media/scripts/'-folder. This is a security option to ensure that users with template-access do not terrorize
		'strictFormmail' => TRUE,				// Boolean. If set, the internal "formmail" feature in TYPO3 will send mail ONLY to recipients which has been encoded by the system itself. This protects against spammers misusing the formmailer.
		'secureFormmail' => TRUE,				// Boolean. If set, the internal "formmail" feature in TYPO3 will send mail ONLY to the recipients that are defined in the form CE record. This protects against spammers misusing the formmailer.
		*/
		
		'compressionLevel' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'input',
			'valueType' => 'integer',
			'value' => 'LC:FE/compressionLevel',
			'default' => 0
		),
		'compressionDebugInfo' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'checkbox',
			'value' => 'LC:FE/compressionDebugInfo',
			'default' => 0
		),
		/*
		'compressionLevel' => 0,				// Determines output compression of FE output. Makes output smaller but slows down the page generation depending on the compression level. Requires zlib in your PHP4 installation. Range 1-9, where 1 is least compression (approx. 50%) and 9 is greatest compression (approx 33%). 'true' as value will set the compression based on the system load (works with Linux, FreeBSD). Suggested value is 3. For more info, see class in t3lib/class.gzip_encode.php written by Sandy McArthur, Jr. <Leknor@Leknor.com>
		'compressionDebugInfo' => 0,			// Boolean. If set, then in the end of the pages, the sizes of the compressed and non-compressed document is output. This should be used ONLY as a test, because the content is compressed twice in order to output this statistics!
		*/
		
		
		'pageNotFound_handling' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'input',
			'value' => 'LC:FE/pageNotFound_handling',
			'default' => ''
		),
		'pageNotFound_handling_statheader' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'input',
			'value' => 'LC:FE/pageNotFound_handling_statheader',
			'default' => 'HTTP/1.0 404 Not Found'
		),
		'pageNotFoundOnCHashError' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => 'checkbox',
			'value' => 'LC:FE/pageNotFoundOnCHashError',
			'default' => 0
		),
		/*
		'pageNotFound_handling' => '',			// How TYPO3 should handle requests for non-existing/accessible pages. false (default): The 'nearest' page is shown. TRUE or '1': An TYPO3 error box is displayed. Strings: page to show (reads content and outputs with correct headers), eg. 'notfound.html' or 'http://www.domain.org/errors/notfound.html'. If prefixed "REDIRECT:" it will redirect to the URL/script after the prefix (original behaviour). If prefixed with "READFILE:" then it will expect the remaining string to be a HTML file which will be read and outputted directly after having the marker "###CURRENT_URL###" substituted with REQUEST_URI and ###REASON### with reason text, for example: "READFILE:fileadmin/notfound.html". Another option is the prefix "USER_FUNCTION:" which will call a user function, eg. "USER_FUNCTION:typo3conf/pageNotFoundHandling.php:user_pageNotFound->pageNotFound" where the file must contain a class "user_pageNotFound" with a method "pageNotFound" inside with two parameters, $param and $ref
		'pageNotFound_handling_statheader' => 'HTTP/1.0 404 Not Found',			// If 'pageNotFound_handling' is enabled, this string will always be sent as header before the actual handling.
		'pageNotFoundOnCHashError' => 0,		// Boolean. If true, a page not found call is made when cHash evaluation error occurs. By default they will just disable caching but still display page output.
		*/
		
		/*
		'' => array (
			'categoryMain' => 'system',
			'categorySub' => 'frontend',
			'tags' => array(),
			'elementType' => '',
			'value' => 'LC:FE/',
			'default' => ''
		),
		*/
		
		
		/*
		'userFuncClassPrefix' => 'user_',		// This prefix must be the first part of any function or class name called from TypoScript, for instance in the stdWrap function.
		'addRootLineFields' => '',				// Comma-list of fields from the 'pages'-table. These fields are added to the select query for fields in the rootline.
		'checkFeUserPid' => 1,					// Boolean. If set, the pid of fe_user logins must be sent in the form as the field 'pid' and then the user must be located in the pid. If you unset this, you should change the fe_users.username eval-flag 'uniqueInPid' to 'unique' in $TCA. This will do: $TCA['fe_users']['columns']['username']['config']['eval']= 'nospace,lower,required,unique';
		'lockIP' => 2,							// Integer (0-4). If >0, fe_users are locked to (a part of) their REMOTE_ADDR IP for their session. Enhances security but may throw off users that may change IP during their session (in which case you can lower it to 2 or 3). The integer indicates how many parts of the IP address to include in the check. Reducing to 1-3 means that only first, second or third part of the IP address is used. 4 is the FULL IP address and recommended. 0 (zero) disables checking of course.
		'loginSecurityLevel' => '',				// See description for TYPO3_CONF_VARS[BE][loginSecurityLevel]. Default state for frontend is "normal". Alternative authentication services can implement higher levels if preferred.
		'lifetime' => 0,						// Integer, positive. If >0, the cookie of FE users will NOT be a session cookie (deleted when browser is shut down) but rather a cookie with a lifetime of the number of seconds this value indicates. Setting this value to 3600*24*7 will result in automatic login of FE users during a whole week.
		'permalogin' => 2,						// Integer. -1: Permanent login for FE users disabled. 0: By default permalogin is disabled for FE users but can be enabled by a form control in the login form. 1: Permanent login is by default enabled but can be disabled by a form control in the login form. // 2: Permanent login is forced to be enabled.  // In any case, permanent login is only possible if TYPO3_CONF_VARS[FE][lifetime] lifetime is > 0.
		'maxSessionDataSize' => 10000,			// Integer. Setting the maximum size (bytes) of frontend session data stored in the table fe_session_data. Set to zero (0) means no limit, but this is not recommended since it also disables a check that session data is stored only if a confirmed cookie is set.
		'lockHashKeyWords' => 'useragent',		// Keyword list (Strings commaseparated). Currently only "useragent"; If set, then the FE user session is locked to the value of HTTP_USER_AGENT. This lowers the risk of session hi-jacking. However some cases (like payment gateways) might have to use the session cookie and in this case you will have to disable that feature (eg. with a blank string).
		'defaultUserTSconfig' => '',			// Enter lines of default frontend user/group TSconfig.
		'defaultTypoScript_constants' => '',	// Enter lines of default TypoScript, constants-field.
		'defaultTypoScript_constants.' => Array(),	// Lines of TS to include after a static template with the uid = the index in the array (Constants)
		'defaultTypoScript_setup' => '',		// Enter lines of default TypoScript, setup-field.
		'defaultTypoScript_setup.' => Array(),		// As above, but for Setup
		'defaultTypoScript_editorcfg' => '',		// Enter lines of default TypoScript, editorcfg-field (Backend Editor Configuration)
		'defaultTypoScript_editorcfg.' => Array(),		// As above, but for Backend Editor Configuration
		'dontSetCookie' => 0,					// If set, the no cookies is attempted to be set in the front end. Of course no userlogins are possible either...
		'IPmaskMountGroups' => array(			// This allows you to specify an array of IPmaskLists/fe_group-uids. If the REMOTE_ADDR of the user matches an IPmaskList, then the given fe_group is add to the gr_list. So this is an automatic mounting of a user-group. But no fe_user is logged in though! This feature is implemented for the default frontend user authentication and might not be implemented for alternative authentication services.
			// array('IPmaskList_1','fe_group uid'), array('IPmaskList_2','fe_group uid')
		),
		'get_url_id_token' => '#get_URL_ID_TOK#',	// This is the token, which is substituted in the output code in order to keep a GET-based session going. Normally the GET-session-id is 5 chars ('&ftu=') + hash_length (norm. 10)
		'content_doktypes' => '1,2,5,7',			// List of pages.doktype values which can contain content (so shortcut pages and external url pages are excluded, but all pages below doktype 199 should be included. doktype=6 is not either (backend users only...). For doktypes going into menus see class.tslib_menu.php, line 494 (search for 'doktype'))
		'enable_mount_pids' => 1,					// If set to "1", the mount_pid feature allowing 'symlinks' in the page tree (for frontend operation) is allowed.
		'pageOverlayFields' => 'uid,title,subtitle,nav_title,media,keywords,description,abstract,author,author_email',				// List of fields from the table "pages_language_overlay" which should be overlaid on page records. See t3lib_page::getPageOverlay()
		'hidePagesIfNotTranslatedByDefault' => FALSE,	// If TRUE, pages that has no translation will be hidden by default. Basically this will inverse the effect of the page localization setting "Hide page if no translation for current language exists" to "Show page even if no translation exists"
		'eID_include' => array(),				// Array of key/value pairs where key is "tx_[ext]_[optional suffix]" and value is relative filename of class to include. Key is used as "?eID=" for index_ts.php to include the code file which renders the page from that point. (Useful for functionality that requires a low initialization footprint, eg. frontend ajax applications)
		'XCLASS' => Array(),					// See 'Inside TYPO3' document for more information.
		'pageCacheToExternalFiles' => FALSE		// If set, page cache entries will be stored in typo3temp/cache_pages/ab/ instead of the database. Still, "cache_pages" will be filled in database but the "HTML" field will be empty. When the cache is flushed the files in cache_pages/ab/ will not be flush - you will have to garbage clean manually once in a while.
		 */
		
		
		/** DEVELOPMENT **/
		'devIPmask' => array (
			'categoryMain' => 'system',
			'categorySub' => 'development',
			'tags' => array ('development', 'dev', 'ip'),
			'elementType' => 'input',
			'value' => 'LC:SYS/devIPmask',
			'default' => '127.0.0.1,::1',
		),
	)
);
?>