<?php
/**

'EXT' => Array (	// Options related to the Extension Management
		'noEdit' => 1,							// Boolean: If set, the Extension Manager does NOT allow extension files to be edited! (Otherwise both local and global extensions can be edited.)
		'allowGlobalInstall' => 0,				// Boolean: If set, global extensions in typo3/ext/ are allowed to be installed, updated and deleted etc.
		'allowLocalInstall' => 1,				// Boolean: If set, local extensions in typo3conf/ext/ are allowed to be installed, updated and deleted etc.
		'allowSystemInstall' => 0,				// If set, you can install extensions in the sysext/ dir. Use this to upgrade the 'cms' and 'lang' extensions.
		'em_wsdlURL' => 'http://typo3.org/wsdl/tx_ter_wsdl.php',				// The SOAP URL for uploading extensions to the TER2. Usually doesn't need to be changed.
		'em_mirrorListURL' => 'http://repositories.typo3.org/mirrors.xml.gz',				// Allows to preset the URL for fetching the extension repository mirror list from. Used in the Extension Manager.

		'requiredExt' => 'cms,version,lang,sv',	// String list: List of extensions which are REQUIRED and cannot be unloaded by the Extension Manager!
		'excludeForPackaging' => '(CVS|\..*|.*~|.*\.bak)',		// String list: List of directories and files which will not be packaged into extensions nor taken into account otherwise by the Extension Manager. Perl regular expression syntax!
		'extCache' => 1,						// Int. 0,1,2,3: 0: ext-scripts (ext_localconf.php and ext_tables.php) are NOT cached, but included every time. 1: scripts cached to typo3conf/temp_CACHED_[sitePathHash]* (saves some milliseconds even with PHP accelerators), 2: scripts cached and prefix includes a hash based on the 'extList' string, 3: scripts cached to typo3conf/temp_CACHED_* (no hash included at all...)
		'extList' => 'tsconfig_help,context_help,extra_page_cm_options,impexp,belog,aboutmodules,setup,opendocs,install',						// String list: List of extensions which are enabled for this install. Use the Extension Manager (EM) to manage this!
		'extConf' => array(						// Config-options for extensions, stored as serialized arrays by extension-keys. Handled automatically by the EM.
//			'--key--' => array()
		),
	),

	'BE' => Array(		// Backend Configuration.
		'unzip_path' => '',						// Path to "unzip".
		'diff_path' => 'diff',					// Path to "diff". For Windows this program can be downloaded here: http://unxutils.sourceforge.net/
		'fileadminDir' => 'fileadmin/',			// Path to the fileadmin dir. This is relative to PATH_site. (Automatically mounted for admin-users if set)
		'RTEenabled' => 1,						// Boolean. If set, the Rich Text editor will be an option in the backend. Notice that the editor must be enabled per-user and options are configurable. See admin guide.
		'RTE_imageStorageDir' => 'uploads/',	// Default storage directory for Rich Text Editor files
		'RTE_reg' => array(),					// Contains arrays of possible RTEs available (keys=extKey, values=cfg-array). Each array contains a key, "objRef", which contains a user function call with prefixed script path and instanciating a persistent global object. This can report back if browser requirements are OK, draw the RTE and do the transformations needed.
		'staticFileEditPath' => 'fileadmin/static/',	// Path to directory with static files for editing (see table sys_staticfiles_edit). Relative to PATH_site.
		'lockRootPath' => '',					// This path is used to evaluate if paths outside of PATH_site should be allowed. Ending slash required! This path is also used to restrict userHomePath/groupHomePath. Observe that the first part of 'userHomePath' and 'groupHomePath' must be the value of 'lockRootPath'. Eg. '/home/typo3/'.
		'userHomePath' => '',					// Path to the directory where TYPO3 backend-users have their home-dirs.  Eg. '/home/typo3/users/'. A home for backend user 2 would be: '/home/typo3/users/2/'. Ending slash required!
		'groupHomePath' => '',					// Path to the directory where TYPO3 backend-groups have their home-dirs. Remember that the first part of this path must be 'lockRootPath'. Eg. '/home/typo3/groups/'. A home for backend group 1 would be: '/home/typo3/groups/1/'. Ending slash required!
		'userUploadDir' => '',					// Suffix to the user home dir which is what gets mounted in TYPO3. Eg. if the user dir is "../123_user/" and this value is "/upload" then "../123_user/upload" gets mounted.
		'fileCreateMask' => '0644',				// File mode mask for Unix file systems (when files are uploaded/created).
		'folderCreateMask' => '0755',			// As above, but for folders.
		'createGroup' => '',					// Group for newly created files and folders (Unix only). Group ownership can be changed on Unix file systems (see above). Set this if you want to change the group ownership of created files/folders to a specific group. This makes sense in all cases where the webserver is running with a different user/group as you do. Create a new group on your system and add you and the webserver user to the group. Now you can safely set the last bit in fileCreateMask/folderCreateMask to 0 (e.g. 770). Important: The user who is running your webserver needs to be a member of the group you specify here! Otherwise you might get some error messages.
		'warning_email_addr' => '',				// Email-address that will receive a warning if there has been failed logins 4 times within an hour (all users).
		'warning_mode' => '',					// Bit 1: If set, warning_email_addr gets a mail everytime a user logs in. Bit 2: If set, a mail is sent if an ADMIN user logs in! Other bits reserved for future options.
		'lockIP' => 4,							// Integer (0-4). Session IP locking for backend users. See [FE][lockIP] for details. Default is 4 (which is locking the FULL IP address to session).
		'sessionTimeout' => 3600,				// Integer, seconds. Session time out for backend users. Default is 3600 seconds = 1 hour.
		'IPmaskList' => '',						// String. Lets you define a list of IP-numbers (with *-wildcards) that are the ONLY ones allowed access to ANY backend activity. On error an error header is sent and the script exits. Works like IP masking for users configurable through TSconfig. See syntax for that (or look up syntax for the function t3lib_div::cmpIP())
		'lockBeUserToDBmounts' => 1,			// Boolean. If set, the backend user is allowed to work only within his page-mount. It's advisable to leave this on because it makes security easy to manage.
		'lockSSL' => 0,							// Int. 0,1,2,3: If set (1,2,3), the backend can only be operated from an ssl-encrypted connection (https). Set to 2 you will be redirected to the https admin-url supposed to be the http-url, but with https scheme instead. If set to 3, only the login is forced to SSL, then the user switches back to non-SSL-mode
		'enabledBeUserIPLock' => 1,				// Boolean. If set, the User/Group TSconfig option 'option.lockToIP' is enabled.
		'loginSecurityLevel' => '',				// String. Keywords that determines the security level of login to the backend. "normal" means the password from the login form is sent in clear-text, "challenged" means the password is not sent but hashed with some other values, "superchallenged" (default) means the password is first hashed before being hashed with the challenge values again (means the password is stored as a hashed string in the database also). DO NOT CHANGE this value manually; without an alternative authentication service it will only prevent logins in TYPO3 since the "superchallenged" method is hardcoded in the default authentication system.
		'adminOnly' => 0,						// Int. If set (>=1), the only "admin" users can log in to the backend. If "<=-1" then the backend is totally shut down! For maintenance purposes.
		'disable_exec_function' => 0,			// Boolean. Don't use exec() function (except for ImageMagick which is disabled by [GFX][im]=0). If set, all fileoperations are done by the default PHP-functions. This is nescessary under Windows! On Unix the system commands by exec() can be used, unless this is disabled.
		'usePHPFileFunctions' => 1,				// Boolean. If set, all fileoperations are done by the default PHP-functions. Default on Unix is using the system commands by exec(). You need to set this flag under safe_mode.
		'compressionLevel' => 0,				// Determines output compression of BE output. Makes output smaller but slows down the page generation depending on the compression level. Requires zlib in your PHP4 installation. Range 1-9, where 1 is least compression (approx. 50%) and 9 is greatest compression (approx 33%). 'true' as value will set the compression based on the system load (works with Linux, FreeBSD). Suggested value is 3. For more info, see class in t3lib/class.gzip_encode.php written by Sandy McArthur, Jr. <Leknor@Leknor.com>
		'maxFileSize' => '10240',				// Integer. If set this is the max filesize in KB's for file operations in the backend. Can be overridden through $TCA per table field separately.
		'forceCharset' => '',					// String. Normally the charset of the backend users language selection is used. If you set this value to a charset found in t3lib/csconvtbl/ (or "utf-8") the backend (and database) will ALWAYS use this charset. Always use a lowercase value.
		'installToolPassword' => '',			// String. This is the md5-hashed password for the Install Tool. Set this to '' and access will be totally denied. PLEASE consider to externally password protect the typo3/install/ folder, eg. with a .htaccess file.
		'trackBeUser' => 0,						// Boolean. If set, every invokation of a backend script is logged in sys_trackbeuser. This is used to get a view of the backend users behaviour. Mostly for debugging, support and user interaction analysis. Requires 'beuser_tracking' extension.
 		'defaultUserTSconfig' => 'options.shortcutFrame=1',			// String. Enter lines of default backend user/group TSconfig.
		'defaultPageTSconfig' => '',			// Enter lines of default Page TSconfig.
		'defaultPermissions' => array (			// Default permissions set for new pages in t3lib/tce_main.php. Keys are 'show,edit,delete,new,editcontent'. Enter as comma-list
//			'user' => '',						// default in tce_main is 'show,edit,delete,new,editcontent'. If this is set (uncomment), this value is used instead.
//			'group' => '',						// default in tce_main is 'show,edit,new,editcontent'. If this is set (uncomment), this value is used instead.
//			'everybody' => ''					// default in tce_main is ''. If this is set (uncomment), this value is used instead.
		),
		'newPagesVersioningType' => -1,			// Integer. Default versioning type for new pages create as versions. -1 means "element", 0 means "page", 1 means "branch"
		'defaultUC' => array (					// Override default settings for BE-users. See class.t3lib_beuserauth.php, array $uc_default
		),
			// The control of fileextensions goes in two catagories. Webspace and Ftpspace. Webspace is folders accessible from a webbrowser (below TYPO3_DOCUMENT_ROOT) and ftpspace is everything else.
			// The control is done like this: If an extension matches 'allow' then the check returns true. If not and an extension matches 'deny' then the check return false. If no match at all, returns true.
			// You list extensions comma-separated. If the value is a '*' every extension is matched
			// If no fileextension, true is returned if 'allow' is '*', false if 'deny' is '*' and true if none of these matches
			// This configuration below accepts everything in ftpspace and everything in webspace except php3 or php files
		'fileExtensions' => array (
			'webspace' => array('allow'=>'', 'deny'=>'php,php3,php4,php5,php6,php7'),
			'ftpspace' => array('allow'=>'*', 'deny'=>'')
		),
		'customPermOptions' => array(),			// Array with sets of custom permission options. Syntax is; 'key' => array('header' => 'header string, language splitted', 'items' => array('key' => array('label, language splitted', 'icon reference', 'Description text, language splitted'))). Keys cannot contain ":|," characters.
		'fileDenyPattern' => '\.php$|\.php.$',	// A regular expression that - if it matches a filename - will deny the file upload/rename or whatever in the webspace. Matching with eregi() (case-insensitive).
		'interfaces' => 'backend',					// This determines which interface options is available in the login prompt and in which order (All options: ",backend,frontend")
		'useOnContextMenuHandler' => 1,			// Boolean. If set, the context menus (clickmenus) in the backend are activated on right-click - although this is not a XHTML attribute!
		'loginLabels' => 'Username|Password|Interface|Log In|Log Out|Backend,Front End|Administration Login on ###SITENAME###|(Note: Cookies and JavaScript must be enabled!)|Important Messages:|Your login attempt did not succeed. Make sure to spell your username and password correctly, including upper/lowercase characters.',		// Language labels of the login prompt.
		'loginNews' => array(),						// In this array you can define news-items for the login screen. To this array, add arrays with assoc keys 'date', 'header', 'content' (HTML content) and for those appropriate value pairs
		'XLLfile' => Array(),					// For extension/overriding of the arrays in 'locallang' files in the backend. See 'Inside TYPO3' for more information.
		'notificationPrefix' => '[TYPO3 Note]',
		'accessListRenderMode' => 'singlebox',	// Can be "singlebox", "checkbox" or blank. Refers to the "renderMode" for the selector boxes in be-groups configuration.
		'explicitADmode' => 'explicitDeny',	// Sets the general allow/deny mode for selector box values. Value can be either "explicitAllow" or "explicitDeny", nothing else!
		'XCLASS' => Array(),					// See 'Inside TYPO3' document for more information.
		'niceFlexFormXMLtags' => TRUE,			// If set, the flexform XML will be stored with meaningful tags which can be validated with DTD/schema. If you rely on custom reading of the XML from pre-4.0 versions you should set this to false if you don't like to change your reader code (internally it is insignificant since t3lib_div::xml2array() doesn't care for the tags if the index-attribute value is set)
		'flexFormXMLincludeDiffBase' => TRUE,	// If set, an additional tag with index "vXX.vDEFbase" is created for translations in flexforms holding the value of the default language when translation was changed. Used to show diff of value. This setting will change whether the system thinks flexform XML looks clean. For example when FALSE XX.vDEFbase fields will be removed in cleaning while accepted if TRUE (of course)
		'compactFlexFormXML' => 0,				// If set, the flexform XML will not contain indentation spaces making XML more compact
		'elementVersioningOnly' => FALSE,		// If true, only element versioning is allowed in the backend. This is recommended for new installations of TYPO3 4.2+ since "page" and "branch" versioning types are known for the drawbacks of loosing ids and "element" type versions supports moving now.
	),

	'FE' => Array(			// Configuration for the TypoScript frontend (FE). Nothing here relates to the administration backend!
		'png_to_gif' => 0,						// Boolean. Enables conversion back to gif of all png-files generated in the frontend libraries. Notice that this leaves an increased number of temporary files in typo3temp/
		'tidy' => 0,							// Boolean. If set, the output html-code will be passed through 'tidy' which is a little program you can get from http://www.w3.org/People/Raggett/tidy/. 'Tidy' cleans the HTML-code for nice display!
		'tidy_option' => 'cached',				// options [all, cached, output]. 'all' = the content is always passed through 'tidy' before it may be stored in cache. 'cached' = only if the page is put into the cache, 'output' = only the output code just before it's echoed out.
		'tidy_path' => 'tidy -i --quiet true --tidy-mark true -wrap 0 -raw',		// Path with options for tidy. For XHTML output, add " --output-xhtml true"
		'logfile_dir' => '', 					// Path where TYPO3 should write webserver-style logfiles to. This path must be write-enabled for the webserver. If this path is outside of PATH_site, you have to allow it using [BE][lockRootPath]
		'publish_dir' => '',					// Path where TYPO3 should write staticly published documents. This path must be write-enabled for the webserver. Remember slash AFTER! Eg: 'publish/' or '/www/htdocs/publish/'. See admPanel option 'publish'
		'addAllowedPaths' => '',				// Additional relative paths (comma-list) to allow TypoScript resources be in. Should be prepended with '/'. If not, then any path where the first part is like this path will match. That is: 'myfolder/ , myarchive' will match eg. 'myfolder/', 'myarchive/', 'myarchive_one/', 'myarchive_2/' ... No check is done to see if this directory actually exists in the root of the site. Paths are matched by simply checking if these strings equals the first part of any TypoScript resource filepath. (See class template, function init() in t3lib/class.t3lib_tsparser.php)
		'allowedTempPaths' => '',				// Additional paths allowed for temporary images. Used with imgResource. Eg. 'alttypo3temp/,another_temp_dir/';
		'debug' => 0,							// Boolean. If set, some debug HTML-comments may be output somewhere. Can also be set by TypoScript.
		'simulateStaticDocuments' => 0,			// Boolean. This is the default value for simulateStaticDocuments (configurable with TypoScript which overrides this, if the TypoScript value is present)
		'noPHPscriptInclude' => 0,				// Boolean. If set, PHP-scripts are not included by TypoScript configurations, unless they reside in 'media/scripts/'-folder. This is a security option to ensure that users with template-access do not terrorize
		'strictFormmail' => TRUE,				// Boolean. If set, the internal "formmail" feature in TYPO3 will send mail ONLY to recipients which has been encoded by the system itself. This protects against spammers misusing the formmailer.
		'secureFormmail' => TRUE,				// Boolean. If set, the internal "formmail" feature in TYPO3 will send mail ONLY to the recipients that are defined in the form CE record. This protects against spammers misusing the formmailer.
		'compressionLevel' => 0,				// Determines output compression of FE output. Makes output smaller but slows down the page generation depending on the compression level. Requires zlib in your PHP4 installation. Range 1-9, where 1 is least compression (approx. 50%) and 9 is greatest compression (approx 33%). 'true' as value will set the compression based on the system load (works with Linux, FreeBSD). Suggested value is 3. For more info, see class in t3lib/class.gzip_encode.php written by Sandy McArthur, Jr. <Leknor@Leknor.com>
		'compressionDebugInfo' => 0,			// Boolean. If set, then in the end of the pages, the sizes of the compressed and non-compressed document is output. This should be used ONLY as a test, because the content is compressed twice in order to output this statistics!
		'pageNotFound_handling' => '',			// How TYPO3 should handle requests for non-existing/accessible pages. false (default): The 'nearest' page is shown. TRUE or '1': An TYPO3 error box is displayed. Strings: page to show (reads content and outputs with correct headers), eg. 'notfound.html' or 'http://www.domain.org/errors/notfound.html'. If prefixed "REDIRECT:" it will redirect to the URL/script after the prefix (original behaviour). If prefixed with "READFILE:" then it will expect the remaining string to be a HTML file which will be read and outputted directly after having the marker "###CURRENT_URL###" substituted with REQUEST_URI and ###REASON### with reason text, for example: "READFILE:fileadmin/notfound.html". Another option is the prefix "USER_FUNCTION:" which will call a user function, eg. "USER_FUNCTION:typo3conf/pageNotFoundHandling.php:user_pageNotFound->pageNotFound" where the file must contain a class "user_pageNotFound" with a method "pageNotFound" inside with two parameters, $param and $ref
		'pageNotFound_handling_statheader' => 'HTTP/1.0 404 Not Found',			// If 'pageNotFound_handling' is enabled, this string will always be sent as header before the actual handling.
		'pageNotFoundOnCHashError' => 0,		// Boolean. If true, a page not found call is made when cHash evaluation error occurs. By default they will just disable caching but still display page output.
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
	),

*/
$MCA['setup'] = array (
	'general' => array (
		'title' => 'module_setup_title',
	)
);
?>
