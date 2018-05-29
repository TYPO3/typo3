<?php
namespace TYPO3\CMS\Frontend\Controller;

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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Controller\ErrorPageController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ServiceUnavailableException;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Http\UrlHandlerInterface;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageGenerator;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\View\AdminPanelView;

/**
 * Class for the built TypoScript based frontend. Instantiated in
 * \TYPO3\CMS\Frontend\Http\RequestHandler as the global object TSFE.
 *
 * Main frontend class, instantiated in \TYPO3\CMS\Frontend\Http\RequestHandler
 * as the global object TSFE.
 *
 * This class has a lot of functions and internal variable which are used from
 * \TYPO3\CMS\Frontend\Http\RequestHandler
 *
 * The class is instantiated as $GLOBALS['TSFE'] in \TYPO3\CMS\Frontend\Http\RequestHandler.
 *
 * The use of this class should be inspired by the order of function calls as
 * found in \TYPO3\CMS\Frontend\Http\RequestHandler.
 */
class TypoScriptFrontendController
{
    /**
     * The page id (int)
     * @var string
     */
    public $id = '';

    /**
     * The type (read-only)
     * @var int
     */
    public $type = '';

    /**
     * The submitted cHash
     * @var string
     */
    public $cHash = '';

    /**
     * Page will not be cached. Write only TRUE. Never clear value (some other
     * code might have reasons to set it TRUE).
     * @var bool
     */
    public $no_cache = false;

    /**
     * The rootLine (all the way to tree root, not only the current site!)
     * @var array
     */
    public $rootLine = '';

    /**
     * The pagerecord
     * @var array
     */
    public $page = '';

    /**
     * This will normally point to the same value as id, but can be changed to
     * point to another page from which content will then be displayed instead.
     * @var int
     */
    public $contentPid = 0;

    /**
     * Gets set when we are processing a page of type mounpoint with enabled overlay in getPageAndRootline()
     * Used later in checkPageForMountpointRedirect() to determine the final target URL where the user
     * should be redirected to.
     *
     * @var array|null
     */
    protected $originalMountPointPage = null;

    /**
     * Gets set when we are processing a page of type shortcut in the early stages
     * of the request when we do not know about languages yet, used later in the request
     * to determine the correct shortcut in case a translation changes the shortcut
     * target
     * @var array|null
     * @see checkTranslatedShortcut()
     */
    protected $originalShortcutPage = null;

    /**
     * sys_page-object, pagefunctions
     *
     * @var PageRepository
     */
    public $sys_page = '';

    /**
     * Contains all URL handler instances that are active for the current request.
     *
     * The methods isGeneratePage(), isOutputting() and isINTincScript() depend on this property.
     *
     * @var \TYPO3\CMS\Frontend\Http\UrlHandlerInterface[]
     * @see initializeRedirectUrlHandlers()
     */
    protected $activeUrlHandlers = [];

    /**
     * Is set to 1 if a pageNotFound handler could have been called.
     * @var int
     */
    public $pageNotFound = 0;

    /**
     * Domain start page
     * @var int
     */
    public $domainStartPage = 0;

    /**
     * Array containing a history of why a requested page was not accessible.
     * @var array
     */
    public $pageAccessFailureHistory = [];

    /**
     * @var string
     */
    public $MP = '';

    /**
     * @var string
     */
    public $RDCT = '';

    /**
     * This can be set from applications as a way to tag cached versions of a page
     * and later perform some external cache management, like clearing only a part
     * of the cache of a page...
     * @var int
     */
    public $page_cache_reg1 = 0;

    /**
     * Contains the value of the current script path that activated the frontend.
     * Typically "index.php" but by rewrite rules it could be something else! Used
     * for Speaking Urls / Simulate Static Documents.
     * @var string
     */
    public $siteScript = '';

    /**
     * The frontend user
     *
     * @var FrontendUserAuthentication
     */
    public $fe_user = '';

    /**
     * Global flag indicating that a frontend user is logged in. This is set only if
     * a user really IS logged in. The group-list may show other groups (like added
     * by IP filter or so) even though there is no user.
     * @var bool
     */
    public $loginUser = false;

    /**
     * (RO=readonly) The group list, sorted numerically. Group '0,-1' is the default
     * group, but other groups may be added by other means than a user being logged
     * in though...
     * @var string
     */
    public $gr_list = '';

    /**
     * Flag that indicates if a backend user is logged in!
     * @var bool
     */
    public $beUserLogin = false;

    /**
     * Integer, that indicates which workspace is being previewed.
     * @var int
     */
    public $workspacePreview = 0;

    /**
     * Shows whether logins are allowed in branch
     * @var bool
     */
    public $loginAllowedInBranch = true;

    /**
     * Shows specific mode (all or groups)
     * @var string
     */
    public $loginAllowedInBranch_mode = '';

    /**
     * Set to backend user ID to initialize when keyword-based preview is used
     * @var int
     */
    public $ADMCMD_preview_BEUSER_uid = 0;

    /**
     * Flag indication that preview is active. This is based on the login of a
     * backend user and whether the backend user has read access to the current
     * page. A value of 1 means ordinary preview, 2 means preview of a non-live
     * workspace
     * @var int
     */
    public $fePreview = 0;

    /**
     * Flag indicating that hidden pages should be shown, selected and so on. This
     * goes for almost all selection of pages!
     * @var bool
     */
    public $showHiddenPage = false;

    /**
     * Flag indicating that hidden records should be shown. This includes
     * sys_template, pages_language_overlay and even fe_groups in addition to all
     * other regular content. So in effect, this includes everything except pages.
     * @var bool
     */
    public $showHiddenRecords = false;

    /**
     * Value that contains the simulated usergroup if any
     * @var int
     */
    public $simUserGroup = 0;

    /**
     * "CONFIG" object from TypoScript. Array generated based on the TypoScript
     * configuration of the current page. Saved with the cached pages.
     * @var array
     */
    public $config = [];

    /**
     * The TypoScript template object. Used to parse the TypoScript template
     *
     * @var TemplateService
     */
    public $tmpl = null;

    /**
     * Is set to the time-to-live time of cached pages. If FALSE, default is
     * 60*60*24, which is 24 hours.
     * @var bool|int
     */
    public $cacheTimeOutDefault = false;

    /**
     * Set internally if cached content is fetched from the database
     * @var bool
     * @internal
     */
    public $cacheContentFlag = false;

    /**
     * Set to the expire time of cached content
     * @var int
     */
    public $cacheExpires = 0;

    /**
     * Set if cache headers allowing caching are sent.
     * @var bool
     */
    public $isClientCachable = false;

    /**
     * Used by template fetching system. This array is an identification of
     * the template. If $this->all is empty it's because the template-data is not
     * cached, which it must be.
     * @var array
     */
    public $all = [];

    /**
     * Toplevel - objArrayName, eg 'page'
     * @var string
     */
    public $sPre = '';

    /**
     * TypoScript configuration of the page-object pointed to by sPre.
     * $this->tmpl->setup[$this->sPre.'.']
     * @var array
     */
    public $pSetup = '';

    /**
     * This hash is unique to the template, the $this->id and $this->type vars and
     * the gr_list (list of groups). Used to get and later store the cached data
     * @var string
     */
    public $newHash = '';

    /**
     * If config.ftu (Frontend Track User) is set in TypoScript for the current
     * page, the string value of this var is substituted in the rendered source-code
     * with the string, '&ftu=[token...]' which enables GET-method usertracking as
     * opposed to cookie based
     * @var string
     */
    public $getMethodUrlIdToken = '';

    /**
     * This flag is set before inclusion of pagegen.php IF no_cache is set. If this
     * flag is set after the inclusion of pagegen.php, no_cache is forced to be set.
     * This is done in order to make sure that php-code from pagegen does not falsely
     * clear the no_cache flag.
     * @var bool
     */
    public $no_cacheBeforePageGen = false;

    /**
     * This flag indicates if temporary content went into the cache during
     * page-generation.
     * @var mixed
     */
    public $tempContent = false;

    /**
     * Passed to TypoScript template class and tells it to force template rendering
     * @var bool
     */
    public $forceTemplateParsing = false;

    /**
     * The array which cHash_calc is based on, see ->makeCacheHash().
     * @var array
     */
    public $cHash_array = [];

    /**
     * May be set to the pagesTSconfig
     * @var array
     */
    public $pagesTSconfig = '';

    /**
     * Eg. insert JS-functions in this array ($additionalHeaderData) to include them
     * once. Use associative keys.
     *
     * Keys in use:
     *
     * JSFormValidate: <script type="text/javascript" src="'.$GLOBALS["TSFE"]->absRefPrefix.'typo3/sysext/frontend/Resources/Public/JavaScript/jsfunc.validateform.js"></script>
     * JSMenuCode, JSMenuCode_menu: JavaScript for the JavaScript menu
     * JSCode: reserved
     *
     * used to accumulate additional HTML-code for the header-section,
     * <head>...</head>. Insert either associative keys (like
     * additionalHeaderData['myStyleSheet'], see reserved keys above) or num-keys
     * (like additionalHeaderData[] = '...')
     *
     * @var array
     */
    public $additionalHeaderData = [];

    /**
     * Used to accumulate additional HTML-code for the footer-section of the template
     * @var array
     */
    public $additionalFooterData = [];

    /**
     * Used to accumulate additional JavaScript-code. Works like
     * additionalHeaderData. Reserved keys at 'openPic' and 'mouseOver'
     *
     * @var array
     */
    public $additionalJavaScript = [];

    /**
     * Used to accumulate additional Style code. Works like additionalHeaderData.
     *
     * @var array
     */
    public $additionalCSS = [];

    /**
     * @var  string
     */
    public $JSCode;

    /**
     * @var string
     */
    public $inlineJS;

    /**
     * Used to accumulate DHTML-layers.
     * @var string
     */
    public $divSection = '';

    /**
     * Debug flag. If TRUE special debug-output maybe be shown (which includes html-formatting).
     * @var bool
     */
    public $debug = false;

    /**
     * Default internal target
     * @var string
     */
    public $intTarget = '';

    /**
     * Default external target
     * @var string
     */
    public $extTarget = '';

    /**
     * Default file link target
     * @var string
     */
    public $fileTarget = '';

    /**
     * Keys are page ids and values are default &MP (mount point) values to set
     * when using the linking features...)
     * @var array
     */
    public $MP_defaults = [];

    /**
     * If set, typolink() function encrypts email addresses. Is set in pagegen-class.
     * @var string|int
     */
    public $spamProtectEmailAddresses = 0;

    /**
     * Absolute Reference prefix
     * @var string
     */
    public $absRefPrefix = '';

    /**
     * Factor for form-field widths compensation
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     * @var string
     */
    public $compensateFieldWidth = '';

    /**
     * Lock file path
     * @var string
     */
    public $lockFilePath = '';

    /**
     * <A>-tag parameters
     * @var string
     */
    public $ATagParams = '';

    /**
     * Search word regex, calculated if there has been search-words send. This is
     * used to mark up the found search words on a page when jumped to from a link
     * in a search-result.
     * @var string
     */
    public $sWordRegEx = '';

    /**
     * Is set to the incoming array sword_list in case of a page-view jumped to from
     * a search-result.
     * @var string
     */
    public $sWordList = '';

    /**
     * A string prepared for insertion in all links on the page as url-parameters.
     * Based on configuration in TypoScript where you defined which GET_VARS you
     * would like to pass on.
     * @var string
     */
    public $linkVars = '';

    /**
     * A string set with a comma list of additional GET vars which should NOT be
     * included in the cHash calculation. These vars should otherwise be detected
     * and involved in caching, eg. through a condition in TypoScript.
     * @deprecatd since TYPO3 v8, will be removed in TYPO3 v9, this is taken care of via TYPO3_CONF_VARS nowadays
     * @var string
     */
    public $excludeCHashVars = '';

    /**
     * If set, edit icons are rendered aside content records. Must be set only if
     * the ->beUserLogin flag is set and set_no_cache() must be called as well.
     * @var string
     */
    public $displayEditIcons = '';

    /**
     * If set, edit icons are rendered aside individual fields of content. Must be
     * set only if the ->beUserLogin flag is set and set_no_cache() must be called as
     * well.
     * @var string
     */
    public $displayFieldEditIcons = '';

    /**
     * Site language, 0 (zero) is default, int+ is uid pointing to a sys_language
     * record. Should reflect which language menus, templates etc is displayed in
     * (master language) - but not necessarily the content which could be falling
     * back to default (see sys_language_content)
     * @var int
     */
    public $sys_language_uid = 0;

    /**
     * Site language mode for content fall back.
     * @var string
     */
    public $sys_language_mode = '';

    /**
     * Site content selection uid (can be different from sys_language_uid if content
     * is to be selected from a fall-back language. Depends on sys_language_mode)
     * @var int
     */
    public $sys_language_content = 0;

    /**
     * Site content overlay flag; If set - and sys_language_content is > 0 - ,
     * records selected will try to look for a translation pointing to their uid. (If
     * configured in [ctrl][languageField] / [ctrl][transOrigP...]
     * Possible values: [0,1,hideNonTranslated]
     * This flag is set based on TypoScript config.sys_language_overlay setting
     *
     * @var int|string
     */
    public $sys_language_contentOL = 0;

    /**
     * Is set to the iso code of the sys_language_content if that is properly defined
     * by the sys_language record representing the sys_language_uid.
     * @var string
     */
    public $sys_language_isocode = '';

    /**
     * 'Global' Storage for various applications. Keys should be 'tx_'.extKey for
     * extensions.
     * @var array
     */
    public $applicationData = [];

    /**
     * @var array
     */
    public $register = [];

    /**
     * Stack used for storing array and retrieving register arrays (see
     * LOAD_REGISTER and RESTORE_REGISTER)
     * @var array
     */
    public $registerStack = [];

    /**
     * Checking that the function is not called eternally. This is done by
     * interrupting at a depth of 50
     * @var int
     */
    public $cObjectDepthCounter = 50;

    /**
     * Used by RecordContentObject and ContentContentObject to ensure the a records is NOT
     * rendered twice through it!
     * @var array
     */
    public $recordRegister = [];

    /**
     * This is set to the [table]:[uid] of the latest record rendered. Note that
     * class ContentObjectRenderer has an equal value, but that is pointing to the
     * record delivered in the $data-array of the ContentObjectRenderer instance, if
     * the cObjects CONTENT or RECORD created that instance
     * @var string
     */
    public $currentRecord = '';

    /**
     * Used by class \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject
     * to keep track of access-keys.
     * @var array
     */
    public $accessKey = [];

    /**
     * Numerical array where image filenames are added if they are referenced in the
     * rendered document. This includes only TYPO3 generated/inserted images.
     * @var array
     */
    public $imagesOnPage = [];

    /**
     * Is set in ContentObjectRenderer->cImage() function to the info-array of the
     * most recent rendered image. The information is used in ImageTextContentObject
     * @var array
     */
    public $lastImageInfo = [];

    /**
     * Used to generate page-unique keys. Point is that uniqid() functions is very
     * slow, so a unikey key is made based on this, see function uniqueHash()
     * @var int
     */
    public $uniqueCounter = 0;

    /**
     * @var string
     */
    public $uniqueString = '';

    /**
     * This value will be used as the title for the page in the indexer (if
     * indexing happens)
     * @var string
     */
    public $indexedDocTitle = '';

    /**
     * Alternative page title (normally the title of the page record). Can be set
     * from applications you make.
     * @var string
     */
    public $altPageTitle = '';

    /**
     * The base URL set for the page header.
     * @var string
     */
    public $baseUrl = '';

    /**
     * IDs we already rendered for this page (to make sure they are unique)
     * @var array
     */
    private $usedUniqueIds = [];

    /**
     * Page content render object
     *
     * @var ContentObjectRenderer
     */
    public $cObj = '';

    /**
     * All page content is accumulated in this variable. See pagegen.php
     * @var string
     */
    public $content = '';

    /**
     * @var int
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use the calculations in setParseTime() directly
     */
    public $scriptParseTime = 0;

    /**
     * Character set (charset) conversion object:
     * charset conversion class. May be used by any application.
     *
     * @var CharsetConverter
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, instantiate CharsetConverter on your own if you need it
     */
    public $csConvObj;

    /**
     * The default charset used in the frontend if nothing else is set.
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     * @var string
     */
    public $defaultCharSet = 'utf-8';

    /**
     * Internal charset of the frontend during rendering. (Default: UTF-8)
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     * @var string
     */
    public $renderCharset = 'utf-8';

    /**
     * Output charset of the websites content. This is the charset found in the
     * header, meta tag etc. If different than utf-8 a conversion
     * happens before output to browser. Defaults to utf-8.
     * @var string
     */
    public $metaCharset = 'utf-8';

    /**
     * Set to the system language key (used on the site)
     * @var string
     */
    public $lang = '';

    /**
     * @var array
     */
    public $LL_labels_cache = [];

    /**
     * @var array
     */
    public $LL_files_cache = [];

    /**
     * List of language dependencies for actual language. This is used for local
     * variants of a language that depend on their "main" language, like Brazilian,
     * Portuguese or Canadian French.
     *
     * @var array
     */
    protected $languageDependencies = [];

    /**
     * @var LockingStrategyInterface[][]
     */
    protected $locks = [];

    /**
     * @var PageRenderer
     */
    protected $pageRenderer = null;

    /**
     * The page cache object, use this to save pages to the cache and to
     * retrieve them again
     *
     * @var \TYPO3\CMS\Core\Cache\Backend\AbstractBackend
     */
    protected $pageCache;

    /**
     * @var array
     */
    protected $pageCacheTags = [];

    /**
     * The cHash Service class used for cHash related functionality
     *
     * @var CacheHashCalculator
     */
    protected $cacheHash;

    /**
     * Runtime cache of domains per processed page ids.
     *
     * @var array
     */
    protected $domainDataCache = [];

    /**
     * Content type HTTP header being sent in the request.
     * @todo Ticket: #63642 Should be refactored to a request/response model later
     * @internal Should only be used by TYPO3 core for now
     *
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * Doctype to use
     *
     * @var string
     */
    public $xhtmlDoctype = '';

    /**
     * @var int
     */
    public $xhtmlVersion;

    /**
     * Originally requested id from the initial $_GET variable
     *
     * @var int
     */
    protected $requestedId;

    /**
     * @var bool
     */
    public $dtdAllowsFrames;

    /**
     * Class constructor
     * Takes a number of GET/POST input variable as arguments and stores them internally.
     * The processing of these variables goes on later in this class.
     * Also sets a unique string (->uniqueString) for this script instance; A md5 hash of the microtime()
     *
     * @param array $_ unused, previously defined to set TYPO3_CONF_VARS
     * @param mixed $id The value of GeneralUtility::_GP('id')
     * @param int $type The value of GeneralUtility::_GP('type')
     * @param bool|string $no_cache The value of GeneralUtility::_GP('no_cache'), evaluated to 1/0
     * @param string $cHash The value of GeneralUtility::_GP('cHash')
     * @param string $_2 previously was used to define the jumpURL
     * @param string $MP The value of GeneralUtility::_GP('MP')
     * @param string $RDCT The value of GeneralUtility::_GP('RDCT')
     * @see \TYPO3\CMS\Frontend\Http\RequestHandler
     */
    public function __construct($_ = null, $id, $type, $no_cache = '', $cHash = '', $_2 = null, $MP = '', $RDCT = '')
    {
        // Setting some variables:
        $this->id = $id;
        $this->type = $type;
        if ($no_cache) {
            if ($GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter']) {
                $warning = '&no_cache=1 has been ignored because $TYPO3_CONF_VARS[\'FE\'][\'disableNoCacheParameter\'] is set!';
                $this->getTimeTracker()->setTSlogMessage($warning, 2);
            } else {
                $warning = '&no_cache=1 has been supplied, so caching is disabled! URL: "' . GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '"';
                $this->disableCache();
            }
            GeneralUtility::sysLog($warning, 'cms', GeneralUtility::SYSLOG_SEVERITY_WARNING);
        }
        $this->cHash = $cHash;
        $this->MP = $GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] ? (string)$MP : '';
        $this->RDCT = $RDCT;
        $this->uniqueString = md5(microtime());
        $this->csConvObj = GeneralUtility::makeInstance(CharsetConverter::class);
        $this->initPageRenderer();
        // Call post processing function for constructor:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc'])) {
            $_params = ['pObj' => &$this];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        $this->cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class);
        $this->initCaches();
    }

    /**
     * Initializes the page renderer object
     */
    protected function initPageRenderer()
    {
        if ($this->pageRenderer !== null) {
            return;
        }
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->pageRenderer->setTemplateFile('EXT:frontend/Resources/Private/Templates/MainPage.html');
    }

    /**
     * @param string $contentType
     * @internal Should only be used by TYPO3 core for now
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Connect to SQL database. May exit after outputting an error message
     * or some JavaScript redirecting to the install tool.
     *
     * @throws \RuntimeException
     * @throws ServiceUnavailableException
     */
    public function connectToDB()
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
            $connection->connect();
        } catch (DBALException $exception) {
            // Cannot connect to current database
            $message = sprintf(
                'Cannot connect to the configured database. Connection failed with: "%s"',
                $exception->getMessage()
            );
            if ($this->checkPageUnavailableHandler()) {
                $this->pageUnavailableAndExit($message);
            } else {
                GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                throw new ServiceUnavailableException($message, 1301648782);
            }
        }
        // Call post processing function for DB connection:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'])) {
            $_params = ['pObj' => &$this];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
    }

    /**
     * Looks up the value of $this->RDCT in the database and if it is
     * found to be associated with a redirect URL then the redirection
     * is carried out with a 'Location:' header
     * May exit after sending a location-header.
     */
    public function sendRedirect()
    {
        if ($this->RDCT) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('cache_md5params');

            $row = $queryBuilder
                ->select('params')
                ->from('cache_md5params')
                ->where(
                    $queryBuilder->expr()->eq(
                        'md5hash',
                        $queryBuilder->createNamedParameter($this->RDCT, \PDO::PARAM_STR)
                    )
                )
                ->execute()
                ->fetch();

            if ($row) {
                $this->updateMD5paramsRecord($this->RDCT);
                header('Location: ' . $row['params']);
                die;
            }
        }
    }

    /********************************************
     *
     * Initializing, resolving page id
     *
     ********************************************/
    /**
     * Initializes the caching system.
     */
    protected function initCaches()
    {
        $this->pageCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_pages');
    }

    /**
     * Initializes the front-end login user.
     */
    public function initFEuser()
    {
        $this->fe_user = GeneralUtility::makeInstance(FrontendUserAuthentication::class);
        // List of pid's acceptable
        $pid = GeneralUtility::_GP('pid');
        $this->fe_user->checkPid_value = $pid ? implode(',', GeneralUtility::intExplode(',', $pid)) : 0;
        // Check if a session is transferred:
        if (GeneralUtility::_GP('FE_SESSION_KEY')) {
            $fe_sParts = explode('-', GeneralUtility::_GP('FE_SESSION_KEY'));
            // If the session key hash check is OK:
            if (md5(($fe_sParts[0] . '/' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) === (string)$fe_sParts[1]) {
                $cookieName = FrontendUserAuthentication::getCookieName();
                $_COOKIE[$cookieName] = $fe_sParts[0];
                if (isset($_SERVER['HTTP_COOKIE'])) {
                    // See http://forge.typo3.org/issues/27740
                    $_SERVER['HTTP_COOKIE'] .= ';' . $cookieName . '=' . $fe_sParts[0];
                }
                $this->fe_user->forceSetCookie = 1;
                $this->fe_user->dontSetCookie = false;
                unset($cookieName);
            }
        }
        $this->fe_user->start();
        $this->fe_user->unpack_uc();

        // @deprecated since TYPO3 v8, will be removed in TYPO3 v9
        // @todo: With the removal of that in v9, TYPO3_CONF_VARS maxSessionDataSize can be removed as well,
        // @todo: and a silent ugrade wizard to remove the setting from LocalConfiguration should be added.
        $recs = GeneralUtility::_GP('recs');
        if (is_array($recs)) {
            // If any record registration is submitted, register the record.
            $this->fe_user->record_registration($recs, $GLOBALS['TYPO3_CONF_VARS']['FE']['maxSessionDataSize']);
        }

        // Call hook for possible manipulation of frontend user object
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'])) {
            $_params = ['pObj' => &$this];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        // For every 60 seconds the is_online timestamp is updated.
        if (is_array($this->fe_user->user) && $this->fe_user->user['uid'] && $this->fe_user->user['is_online'] < $GLOBALS['EXEC_TIME'] - 60) {
            $dbConnection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('fe_users');
            $dbConnection->update(
                'fe_users',
                [
                    'is_online' => $GLOBALS['EXEC_TIME']
                ],
                [
                    'uid' => (int)$this->fe_user->user['uid']
                ]
            );
        }
    }

    /**
     * Initializes the front-end user groups.
     * Sets ->loginUser and ->gr_list based on front-end user status.
     */
    public function initUserGroups()
    {
        // This affects the hidden-flag selecting the fe_groups for the user!
        $this->fe_user->showHiddenRecords = $this->showHiddenRecords;
        // no matter if we have an active user we try to fetch matching groups which can be set without an user (simulation for instance!)
        $this->fe_user->fetchGroupData();
        if (is_array($this->fe_user->user) && !empty($this->fe_user->groupData['uid'])) {
            // global flag!
            $this->loginUser = true;
            // group -2 is not an existing group, but denotes a 'default' group when a user IS logged in. This is used to let elements be shown for all logged in users!
            $this->gr_list = '0,-2';
            $gr_array = $this->fe_user->groupData['uid'];
        } else {
            $this->loginUser = false;
            // group -1 is not an existing group, but denotes a 'default' group when not logged in. This is used to let elements be hidden, when a user is logged in!
            $this->gr_list = '0,-1';
            if ($this->loginAllowedInBranch) {
                // For cases where logins are not banned from a branch usergroups can be set based on IP masks so we should add the usergroups uids.
                $gr_array = $this->fe_user->groupData['uid'];
            } else {
                // Set to blank since we will NOT risk any groups being set when no logins are allowed!
                $gr_array = [];
            }
        }
        // Clean up.
        // Make unique...
        $gr_array = array_unique($gr_array);
        // sort
        sort($gr_array);
        if (!empty($gr_array) && !$this->loginAllowedInBranch_mode) {
            $this->gr_list .= ',' . implode(',', $gr_array);
        }
        if ($this->fe_user->writeDevLog) {
            GeneralUtility::devLog('Valid usergroups for TSFE: ' . $this->gr_list, __CLASS__);
        }
    }

    /**
     * Checking if a user is logged in or a group constellation different from "0,-1"
     *
     * @return bool TRUE if either a login user is found (array fe_user->user) OR if the gr_list is set to something else than '0,-1' (could be done even without a user being logged in!)
     */
    public function isUserOrGroupSet()
    {
        return is_array($this->fe_user->user) || $this->gr_list !== '0,-1';
    }

    /**
     * Provides ways to bypass the '?id=[xxx]&type=[xx]' format, using either PATH_INFO or virtual HTML-documents (using Apache mod_rewrite)
     *
     * Two options:
     * 1) Use PATH_INFO (also Apache) to extract id and type from that var. Does not require any special modules compiled with apache. (less typical)
     * 2) Using hook which enables features like those provided from "realurl" extension (AKA "Speaking URLs")
     */
    public function checkAlternativeIdMethods()
    {
        $this->siteScript = GeneralUtility::getIndpEnv('TYPO3_SITE_SCRIPT');
        // Call post processing function for custom URL methods.
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc'])) {
            $_params = ['pObj' => &$this];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
    }

    /**
     * Clears the preview-flags, sets sim_exec_time to current time.
     * Hidden pages must be hidden as default, $GLOBALS['SIM_EXEC_TIME'] is set to $GLOBALS['EXEC_TIME']
     * in bootstrap initializeGlobalTimeVariables(). Alter it by adding or subtracting seconds.
     */
    public function clear_preview()
    {
        $this->showHiddenPage = false;
        $this->showHiddenRecords = false;
        $GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
        $GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];
        $this->fePreview = 0;
    }

    /**
     * Checks if a backend user is logged in
     *
     * @return bool whether a backend user is logged in
     */
    public function isBackendUserLoggedIn()
    {
        return (bool)$this->beUserLogin;
    }

    /**
     * Creates the backend user object and returns it.
     *
     * @return FrontendBackendUserAuthentication the backend user object
     */
    public function initializeBackendUser()
    {
        // PRE BE_USER HOOK
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preBeUser'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preBeUser'] as $_funcRef) {
                $_params = [];
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        $backendUserObject = null;
        // If the backend cookie is set,
        // we proceed and check if a backend user is logged in.
        if ($_COOKIE[BackendUserAuthentication::getCookieName()]) {
            $GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'] = microtime(true);
            $this->getTimeTracker()->push('Back End user initialized', '');
            $this->beUserLogin = false;
            // New backend user object
            $backendUserObject = GeneralUtility::makeInstance(FrontendBackendUserAuthentication::class);
            $backendUserObject->start();
            $backendUserObject->unpack_uc();
            if (!empty($backendUserObject->user['uid'])) {
                $backendUserObject->fetchGroupData();
            }
            // Unset the user initialization if any setting / restriction applies
            if (!$backendUserObject->checkBackendAccessSettingsFromInitPhp()) {
                $backendUserObject = null;
            } elseif (!empty($backendUserObject->user['uid'])) {
                // If the user is active now, let the controller know
                $this->beUserLogin = true;
            } else {
                $backendUserObject = null;
            }
            $this->getTimeTracker()->pull();
            $GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'] = microtime(true);
        }
        // POST BE_USER HOOK
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser'])) {
            $_params = [
                'BE_USER' => &$backendUserObject
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        return $backendUserObject;
    }

    /**
     * Determines the id and evaluates any preview settings
     * Basically this function is about determining whether a backend user is logged in,
     * if he has read access to the page and if he's previewing the page.
     * That all determines which id to show and how to initialize the id.
     */
    public function determineId()
    {
        // Call pre processing function for id determination
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PreProcessing'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PreProcessing'] as $functionReference) {
                $parameters = ['parentObject' => $this];
                GeneralUtility::callUserFunction($functionReference, $parameters, $this);
            }
        }
        // If there is a Backend login we are going to check for any preview settings:
        $this->getTimeTracker()->push('beUserLogin', '');
        $originalFrontendUser = null;
        $backendUser = $this->getBackendUser();
        if ($this->beUserLogin || $this->doWorkspacePreview()) {
            // Backend user preview features:
            if ($this->beUserLogin && $backendUser->adminPanel instanceof AdminPanelView) {
                $this->fePreview = (int)$backendUser->adminPanel->extGetFeAdminValue('preview');
                // If admin panel preview is enabled...
                if ($this->fePreview) {
                    if ($this->fe_user->user) {
                        $originalFrontendUser = $this->fe_user->user;
                    }
                    $this->showHiddenPage = (bool)$backendUser->adminPanel->extGetFeAdminValue('preview', 'showHiddenPages');
                    $this->showHiddenRecords = (bool)$backendUser->adminPanel->extGetFeAdminValue('preview', 'showHiddenRecords');
                    // Simulate date
                    $simTime = $backendUser->adminPanel->extGetFeAdminValue('preview', 'simulateDate');
                    if ($simTime) {
                        $simTime -= date('Z', $simTime);
                        $GLOBALS['SIM_EXEC_TIME'] = $simTime;
                        $GLOBALS['SIM_ACCESS_TIME'] = $simTime - $simTime % 60;
                    }
                    // simulate user
                    $simUserGroup = $backendUser->adminPanel->extGetFeAdminValue('preview', 'simulateUserGroup');
                    $this->simUserGroup = $simUserGroup;
                    if ($simUserGroup) {
                        if ($this->fe_user->user) {
                            $this->fe_user->user[$this->fe_user->usergroup_column] = $simUserGroup;
                        } else {
                            $this->fe_user->user = [
                                $this->fe_user->usergroup_column => $simUserGroup
                            ];
                        }
                    }
                    if (!$simUserGroup && !$simTime && !$this->showHiddenPage && !$this->showHiddenRecords) {
                        $this->fePreview = 0;
                    }
                }
            }
            if ($this->id && $this->determineIdIsHiddenPage()) {
                // The preview flag is set only if the current page turns out to actually be hidden!
                $this->fePreview = 1;
                $this->showHiddenPage = true;
            }
            // The preview flag will be set if a backend user is in an offline workspace
            if (
                    (
                        $backendUser->user['workspace_preview']
                        || GeneralUtility::_GP('ADMCMD_view')
                        || $this->doWorkspacePreview()
                    )
                    && (
                        $this->whichWorkspace() === -1
                        || $this->whichWorkspace() > 0
                    )
                    && !GeneralUtility::_GP('ADMCMD_noBeUser')
            ) {
                // Will show special preview message.
                $this->fePreview = 2;
            }
            // If the front-end is showing a preview, caching MUST be disabled.
            if ($this->fePreview) {
                $this->disableCache();
            }
        }
        $this->getTimeTracker()->pull();
        // Now, get the id, validate access etc:
        $this->fetch_the_id();
        // Check if backend user has read access to this page. If not, recalculate the id.
        if ($this->beUserLogin && $this->fePreview) {
            if (!$backendUser->doesUserHaveAccess($this->page, 1)) {
                // Resetting
                $this->clear_preview();
                $this->fe_user->user = $originalFrontendUser;
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
                if ($this->loginAllowedInBranch_mode === 'all') {
                    // Clear out user and group:
                    $this->fe_user->hideActiveLogin();
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
        $this->id = ($this->contentPid = (int)$this->id);
        // Make sure it's an integer
        $this->type = (int)$this->type;
        // Call post processing function for id determination:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc'])) {
            $_params = ['pObj' => &$this];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
    }

    /**
     * Checks if the page is hidden in the active workspace.
     * If it is hidden, preview flags will be set.
     *
     * @return bool
     */
    protected function determineIdIsHiddenPage()
    {
        $field = MathUtility::canBeInterpretedAsInteger($this->id) ? 'uid' : 'alias';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $page = $queryBuilder
            ->select('uid', 'hidden', 'starttime', 'endtime')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($this->id)),
                $queryBuilder->expr()->gte('pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        $workspace = $this->whichWorkspace();
        if ($workspace !== 0 && $workspace !== false) {
            // Fetch overlay of page if in workspace and check if it is hidden
            $pageSelectObject = GeneralUtility::makeInstance(PageRepository::class);
            $pageSelectObject->versioningPreview = true;
            $pageSelectObject->init(false);
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
     * Sets variables such as $this->sys_page, $this->loginUser, $this->gr_list, $this->id, $this->type, $this->domainStartPage
     *
     * @throws ServiceUnavailableException
     * @access private
     */
    public function fetch_the_id()
    {
        $timeTracker = $this->getTimeTracker();
        $timeTracker->push('fetch_the_id initialize/', '');
        // Initialize the page-select functions.
        $this->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $this->sys_page->versioningPreview = $this->fePreview === 2 || (int)$this->workspacePreview || (bool)GeneralUtility::_GP('ADMCMD_view');
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

        // If $this->id is a string, it's an alias
        $this->checkAndSetAlias();
        // The id and type is set to the integer-value - just to be sure...
        $this->id = (int)$this->id;
        $this->type = (int)$this->type;
        $timeTracker->pull();
        // We find the first page belonging to the current domain
        $timeTracker->push('fetch_the_id domain/', '');
        // The page_id of the current domain
        $this->domainStartPage = $this->findDomainRecord($GLOBALS['TYPO3_CONF_VARS']['SYS']['recursiveDomainSearch']);
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
                        GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                        throw new ServiceUnavailableException($message, 1301648975);
                    }
                }
            }
        }
        $timeTracker->pull();
        $timeTracker->push('fetch_the_id rootLine/', '');
        // We store the originally requested id
        $this->requestedId = $this->id;
        $this->getPageAndRootlineWithDomain($this->domainStartPage);
        $timeTracker->pull();
        if ($this->pageNotFound && $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling']) {
            $pNotFoundMsg = [
                1 => 'ID was not an accessible page',
                2 => 'Subsection was found and not accessible',
                3 => 'ID was outside the domain',
                4 => 'The requested page alias does not exist'
            ];
            $header = '';
            if ($this->pageNotFound === 1 || $this->pageNotFound === 2) {
                $header = $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_accessdeniedheader'];
            }
            $this->pageNotFoundAndExit($pNotFoundMsg[$this->pageNotFound], $header);
        }
        // Set no_cache if set
        if ($this->page['no_cache']) {
            $this->set_no_cache('no_cache is set in page properties');
        }
        // Init SYS_LASTCHANGED
        $this->register['SYS_LASTCHANGED'] = (int)$this->page['tstamp'];
        if ($this->register['SYS_LASTCHANGED'] < (int)$this->page['SYS_LASTCHANGED']) {
            $this->register['SYS_LASTCHANGED'] = (int)$this->page['SYS_LASTCHANGED'];
        }
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['fetchPageId-PostProcessing'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['fetchPageId-PostProcessing'] as $functionReference) {
                $parameters = ['parentObject' => $this];
                GeneralUtility::callUserFunction($functionReference, $parameters, $this);
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
     * @throws ServiceUnavailableException
     * @throws PageNotFoundException
     * @access private
     */
    public function getPageAndRootline()
    {
        $this->page = $this->sys_page->getPage($this->id);
        if (empty($this->page)) {
            // If no page, we try to find the page before in the rootLine.
            // Page is 'not found' in case the id itself was not an accessible page. code 1
            $this->pageNotFound = 1;
            $this->rootLine = $this->sys_page->getRootLine($this->id, $this->MP);
            if (!empty($this->rootLine)) {
                $c = count($this->rootLine) - 1;
                while ($c > 0) {
                    // Add to page access failure history:
                    $this->pageAccessFailureHistory['direct_access'][] = $this->rootLine[$c];
                    // Decrease to next page in rootline and check the access to that, if OK, set as page record and ID value.
                    $c--;
                    $this->id = $this->rootLine[$c]['uid'];
                    $this->page = $this->sys_page->getPage($this->id);
                    if (!empty($this->page)) {
                        break;
                    }
                }
            }
            // If still no page...
            if (empty($this->page)) {
                $message = 'The requested page does not exist!';
                if ($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling']) {
                    $this->pageNotFoundAndExit($message);
                } else {
                    GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                    throw new PageNotFoundException($message, 1301648780);
                }
            }
        }
        // Spacer is not accessible in frontend
        if ($this->page['doktype'] == PageRepository::DOKTYPE_SPACER) {
            $message = 'The requested page does not exist!';
            if ($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling']) {
                $this->pageNotFoundAndExit($message);
            } else {
                GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                throw new PageNotFoundException($message, 1301648781);
            }
        }
        // Is the ID a link to another page??
        if ($this->page['doktype'] == PageRepository::DOKTYPE_SHORTCUT) {
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
        // If the page is a mountpoint which should be overlaid with the contents of the mounted page,
        // it must never be accessible directly, but only in the mountpoint context. Therefore we change
        // the current ID and the user is redirected by checkPageForMountpointRedirect().
        if ($this->page['doktype'] == PageRepository::DOKTYPE_MOUNTPOINT && $this->page['mount_pid_ol']) {
            $this->originalMountPointPage = $this->page;
            $this->page = $this->sys_page->getPage($this->page['mount_pid']);
            if (empty($this->page)) {
                $message = 'This page (ID ' . $this->originalMountPointPage['uid'] . ') is of type "Mount point" and '
                    . 'mounts a page which is not accessible (ID ' . $this->originalMountPointPage['mount_pid'] . ').';
                throw new PageNotFoundException($message, 1402043263);
            }
            $this->MP = $this->page['uid'] . '-' . $this->originalMountPointPage['uid'];
            $this->id = $this->page['uid'];
        }
        // Gets the rootLine
        $this->rootLine = $this->sys_page->getRootLine($this->id, $this->MP);
        // If not rootline we're off...
        if (empty($this->rootLine)) {
            $message = 'The requested page didn\'t have a proper connection to the tree-root!';
            if ($this->checkPageUnavailableHandler()) {
                $this->pageUnavailableAndExit($message);
            } else {
                GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                throw new ServiceUnavailableException($message, 1301648167);
            }
        }
        // Checking for include section regarding the hidden/starttime/endtime/fe_user (that is access control of a whole subbranch!)
        if ($this->checkRootlineForIncludeSection()) {
            if (empty($this->rootLine)) {
                $message = 'The requested page was not accessible!';
                if ($this->checkPageUnavailableHandler()) {
                    $this->pageUnavailableAndExit($message);
                } else {
                    GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                    throw new ServiceUnavailableException($message, 1301648234);
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
     * @param int $SC The value of the "shortcut" field from the pages record
     * @param int $mode The shortcut mode: 1 will select first subpage, 2 a random subpage, 3 the parent page; default is the page pointed to by $SC
     * @param int $thisUid The current page UID of the page which is a shortcut
     * @param int $itera Safety feature which makes sure that the function is calling itself recursively max 20 times (since this function can find shortcuts to other shortcuts to other shortcuts...)
     * @param array $pageLog An array filled with previous page uids tested by the function - new page uids are evaluated against this to avoid going in circles.
     * @param bool $disableGroupCheck If true, the group check is disabled when fetching the target page (needed e.g. for menu generation)
     * @throws \RuntimeException
     * @throws PageNotFoundException
     * @return mixed Returns the page record of the page that the shortcut pointed to.
     * @access private
     * @see getPageAndRootline()
     */
    public function getPageShortcut($SC, $mode, $thisUid, $itera = 20, $pageLog = [], $disableGroupCheck = false)
    {
        $idArray = GeneralUtility::intExplode(',', $SC);
        // Find $page record depending on shortcut mode:
        switch ($mode) {
            case PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE:

            case PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE:
                $pageArray = $this->sys_page->getMenu($idArray[0] ? $idArray[0] : $thisUid, '*', 'sorting', 'AND pages.doktype<199 AND pages.doktype!=' . PageRepository::DOKTYPE_BE_USER_SECTION);
                $pO = 0;
                if ($mode == PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE && !empty($pageArray)) {
                    $randval = (int)rand(0, count($pageArray) - 1);
                    $pO = $randval;
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
                    $message = 'This page (ID ' . $thisUid . ') is of type "Shortcut" and configured to redirect to a subpage. ' . 'However, this page has no accessible subpages.';
                    throw new PageNotFoundException($message, 1301648328);
                }
                break;
            case PageRepository::SHORTCUT_MODE_PARENT_PAGE:
                $parent = $this->sys_page->getPage($idArray[0] ? $idArray[0] : $thisUid, $disableGroupCheck);
                $page = $this->sys_page->getPage($parent['pid'], $disableGroupCheck);
                if (empty($page)) {
                    $message = 'This page (ID ' . $thisUid . ') is of type "Shortcut" and configured to redirect to its parent page. ' . 'However, the parent page is not accessible.';
                    throw new PageNotFoundException($message, 1301648358);
                }
                break;
            default:
                $page = $this->sys_page->getPage($idArray[0], $disableGroupCheck);
                if (empty($page)) {
                    $message = 'This page (ID ' . $thisUid . ') is of type "Shortcut" and configured to redirect to a page, which is not accessible (ID ' . $idArray[0] . ').';
                    throw new PageNotFoundException($message, 1301648404);
                }
        }
        // Check if short cut page was a shortcut itself, if so look up recursively:
        if ($page['doktype'] == PageRepository::DOKTYPE_SHORTCUT) {
            if (!in_array($page['uid'], $pageLog) && $itera > 0) {
                $pageLog[] = $page['uid'];
                $page = $this->getPageShortcut($page['shortcut'], $page['shortcut_mode'], $page['uid'], $itera - 1, $pageLog, $disableGroupCheck);
            } else {
                $pageLog[] = $page['uid'];
                $message = 'Page shortcuts were looping in uids ' . implode(',', $pageLog) . '...!';
                GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                throw new \RuntimeException($message, 1294587212);
            }
        }
        // Return resulting page:
        return $page;
    }

    /**
     * Checks the current rootline for defined sections.
     *
     * @return bool
     * @access private
     */
    public function checkRootlineForIncludeSection()
    {
        $c = count($this->rootLine);
        $removeTheRestFlag = 0;
        for ($a = 0; $a < $c; $a++) {
            if (!$this->checkPagerecordForIncludeSection($this->rootLine[$a])) {
                // Add to page access failure history:
                $this->pageAccessFailureHistory['sub_section'][] = $this->rootLine[$a];
                $removeTheRestFlag = 1;
            }

            if ($this->rootLine[$a]['doktype'] == PageRepository::DOKTYPE_BE_USER_SECTION) {
                // If there is a backend user logged in, check if he has read access to the page:
                if ($this->beUserLogin) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('pages');

                    $queryBuilder
                        ->getRestrictions()
                        ->removeAll();

                    $row = $queryBuilder
                        ->select('uid')
                        ->from('pages')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                            ),
                            $this->getBackendUser()->getPagePermsClause(1)
                        )
                        ->execute()
                        ->fetch();

                    // versionOL()?
                    if (!$row) {
                        // If there was no page selected, the user apparently did not have read access to the current PAGE (not position in rootline) and we set the remove-flag...
                        $removeTheRestFlag = 1;
                    }
                } else {
                    // Don't go here, if there is no backend user logged in.
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
     * @param bool $bypassGroupCheck Bypass group-check
     * @return bool TRUE, if record is viewable.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getTreeList(), checkPagerecordForIncludeSection()
     */
    public function checkEnableFields($row, $bypassGroupCheck = false)
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields'])) {
            $_params = ['pObj' => $this, 'row' => &$row, 'bypassGroupCheck' => &$bypassGroupCheck];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_checkEnableFields'] as $_funcRef) {
                // Call hooks: If one returns FALSE, method execution is aborted with result "This record is not available"
                $return = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                if ($return === false) {
                    return false;
                }
            }
        }
        if ((!$row['hidden'] || $this->showHiddenPage) && $row['starttime'] <= $GLOBALS['SIM_ACCESS_TIME'] && ($row['endtime'] == 0 || $row['endtime'] > $GLOBALS['SIM_ACCESS_TIME']) && ($bypassGroupCheck || $this->checkPageGroupAccess($row))) {
            return true;
        }
        return false;
    }

    /**
     * Check group access against a page record
     *
     * @param array $row The page record to evaluate (needs field: fe_group)
     * @param mixed $groupList List of group id's (comma list or array). Default is $this->gr_list
     * @return bool TRUE, if group access is granted.
     * @access private
     */
    public function checkPageGroupAccess($row, $groupList = null)
    {
        if (is_null($groupList)) {
            $groupList = $this->gr_list;
        }
        if (!is_array($groupList)) {
            $groupList = explode(',', $groupList);
        }
        $pageGroupList = explode(',', $row['fe_group'] ?: 0);
        return count(array_intersect($groupList, $pageGroupList)) > 0;
    }

    /**
     * Checks page record for include section
     *
     * @param array $row The page record to evaluate (needs fields: extendToSubpages + hidden, starttime, endtime, fe_group)
     * @return bool Returns TRUE if either extendToSubpages is not checked or if the enableFields does not disable the page record.
     * @access private
     * @see checkEnableFields(), \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getTreeList(), checkRootlineForIncludeSection()
     */
    public function checkPagerecordForIncludeSection($row)
    {
        return !$row['extendToSubpages'] || $this->checkEnableFields($row) ? 1 : 0;
    }

    /**
     * Checks if logins are allowed in the current branch of the page tree. Traverses the full root line and returns TRUE if logins are OK, otherwise FALSE (and then the login user must be unset!)
     *
     * @return bool returns TRUE if logins are OK, otherwise FALSE (and then the login user must be unset!)
     */
    public function checkIfLoginAllowedInBranch()
    {
        // Initialize:
        $c = count($this->rootLine);
        $loginAllowed = true;
        // Traverse root line from root and outwards:
        for ($a = 0; $a < $c; $a++) {
            // If a value is set for login state:
            if ($this->rootLine[$a]['fe_login_mode'] > 0) {
                // Determine state from value:
                if ((int)$this->rootLine[$a]['fe_login_mode'] === 1) {
                    $loginAllowed = false;
                    $this->loginAllowedInBranch_mode = 'all';
                } elseif ((int)$this->rootLine[$a]['fe_login_mode'] === 3) {
                    $loginAllowed = false;
                    $this->loginAllowedInBranch_mode = 'groups';
                } else {
                    $loginAllowed = true;
                }
            }
        }
        return $loginAllowed;
    }

    /**
     * Analysing $this->pageAccessFailureHistory into a summary array telling which features disabled display and on which pages and conditions. That data can be used inside a page-not-found handler
     *
     * @return array Summary of why page access was not allowed.
     */
    public function getPageAccessFailureReasons()
    {
        $output = [];
        $combinedRecords = array_merge(is_array($this->pageAccessFailureHistory['direct_access']) ? $this->pageAccessFailureHistory['direct_access'] : [['fe_group' => 0]], is_array($this->pageAccessFailureHistory['sub_section']) ? $this->pageAccessFailureHistory['sub_section'] : []);
        if (!empty($combinedRecords)) {
            foreach ($combinedRecords as $k => $pagerec) {
                // If $k=0 then it is the very first page the original ID was pointing at and that will get a full check of course
                // If $k>0 it is parent pages being tested. They are only significant for the access to the first page IF they had the extendToSubpages flag set, hence checked only then!
                if (!$k || $pagerec['extendToSubpages']) {
                    if ($pagerec['hidden']) {
                        $output['hidden'][$pagerec['uid']] = true;
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
     * Gets ->page and ->rootline information based on ->id. ->id may change during this operation.
     * If not inside domain, then default to first page in domain.
     *
     * @param int $domainStartPage Page uid of the page where the found domain record is (pid of the domain record)
     * @access private
     */
    public function getPageAndRootlineWithDomain($domainStartPage)
    {
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
     * @access private
     */
    public function setSysPageWhereClause()
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->getExpressionBuilder();
        $this->sys_page->where_hid_del = ' AND ' . (string)$expressionBuilder->andX(
            QueryHelper::stripLogicalOperatorPrefix($this->sys_page->where_hid_del),
            $expressionBuilder->lt('pages.doktype', 200)
        );
        $this->sys_page->where_groupAccess = $this->sys_page->getMultipleGroupsWhereClause('pages.fe_group', 'pages');
    }

    /**
     * Looking up a domain record based on HTTP_HOST
     *
     * @param bool $recursive If set, it looks "recursively" meaning that a domain like "123.456.typo3.com" would find a domain record like "typo3.com" if "123.456.typo3.com" or "456.typo3.com" did not exist.
     * @return int Returns the page id of the page where the domain record was found.
     * @access private
     */
    public function findDomainRecord($recursive = false)
    {
        if ($recursive) {
            $pageUid = 0;
            $host = explode('.', GeneralUtility::getIndpEnv('HTTP_HOST'));
            while (count($host)) {
                $pageUid = $this->sys_page->getDomainStartPage(implode('.', $host), GeneralUtility::getIndpEnv('SCRIPT_NAME'), GeneralUtility::getIndpEnv('REQUEST_URI'));
                if ($pageUid) {
                    return $pageUid;
                }
                array_shift($host);
            }
            return $pageUid;
        }
        return $this->sys_page->getDomainStartPage(GeneralUtility::getIndpEnv('HTTP_HOST'), GeneralUtility::getIndpEnv('SCRIPT_NAME'), GeneralUtility::getIndpEnv('REQUEST_URI'));
    }

    /**
     * Page unavailable handler for use in frontend plugins from extensions.
     *
     * @param string $reason Reason text
     * @param string $header HTTP header to send
     */
    public function pageUnavailableAndExit($reason = '', $header = '')
    {
        $header = $header ?: $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling_statheader'];
        $this->pageUnavailableHandler($GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling'], $header, $reason);
        die;
    }

    /**
     * Page-not-found handler for use in frontend plugins from extensions.
     *
     * @param string $reason Reason text
     * @param string $header HTTP header to send
     */
    public function pageNotFoundAndExit($reason = '', $header = '')
    {
        $header = $header ?: $GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_statheader'];
        $this->pageNotFoundHandler($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'], $header, $reason);
        die;
    }

    /**
     * Checks whether the pageUnavailableHandler should be used. To be used, pageUnavailable_handling must be set
     * and devIPMask must not match the current visitor's IP address.
     *
     * @return bool TRUE/FALSE whether the pageUnavailable_handler should be used.
     */
    public function checkPageUnavailableHandler()
    {
        if (
            $GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling']
            && !GeneralUtility::cmpIP(
                GeneralUtility::getIndpEnv('REMOTE_ADDR'),
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
            )
        ) {
            $checkPageUnavailableHandler = true;
        } else {
            $checkPageUnavailableHandler = false;
        }
        return $checkPageUnavailableHandler;
    }

    /**
     * Page unavailable handler. Acts a wrapper for the pageErrorHandler method.
     *
     * @param mixed $code See ['FE']['pageUnavailable_handling'] for possible values
     * @param string $header If set, this is passed directly to the PHP function, header()
     * @param string $reason If set, error messages will also mention this as the reason for the page-not-found.
     */
    public function pageUnavailableHandler($code, $header, $reason)
    {
        $this->pageErrorHandler($code, $header, $reason);
    }

    /**
     * Page not found handler. Acts a wrapper for the pageErrorHandler method.
     *
     * @param mixed $code See docs of ['FE']['pageNotFound_handling'] for possible values
     * @param string $header If set, this is passed directly to the PHP function, header()
     * @param string $reason If set, error messages will also mention this as the reason for the page-not-found.
     */
    public function pageNotFoundHandler($code, $header = '', $reason = '')
    {
        $this->pageErrorHandler($code, $header, $reason);
    }

    /**
     * Generic error page handler.
     * Exits.
     *
     * @param mixed $code See docs of ['FE']['pageNotFound_handling'] and ['FE']['pageUnavailable_handling'] for all possible values
     * @param string $header If set, this is passed directly to the PHP function, header()
     * @param string $reason If set, error messages will also mention this as the reason for the page-not-found.
     * @throws \RuntimeException
     */
    public function pageErrorHandler($code, $header = '', $reason = '')
    {
        // Issue header in any case:
        if ($header) {
            $headerArr = preg_split('/\\r|\\n/', $header, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($headerArr as $header) {
                header($header);
            }
        }
        // Create response:
        // Simply boolean; Just shows TYPO3 error page with reason:
        if (strtolower($code) === 'true' || (string)$code === '1' || gettype($code) === 'boolean') {
            echo GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                'Page Not Found',
                'The page did not exist or was inaccessible.' . ($reason ? ' Reason: ' . $reason : '')
            );
        } elseif (GeneralUtility::isFirstPartOfStr($code, 'USER_FUNCTION:')) {
            $funcRef = trim(substr($code, 14));
            $params = [
                'currentUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                'reasonText' => $reason,
                'pageAccessFailureReasons' => $this->getPageAccessFailureReasons()
            ];
            try {
                echo GeneralUtility::callUserFunction($funcRef, $params, $this);
            } catch (\Exception $e) {
                throw new \RuntimeException('Error: 404 page by USER_FUNCTION "' . $funcRef . '" failed.', 1509296032, $e);
            }
        } elseif (GeneralUtility::isFirstPartOfStr($code, 'READFILE:')) {
            $readFile = GeneralUtility::getFileAbsFileName(trim(substr($code, 9)));
            if (@is_file($readFile)) {
                echo str_replace(
                    [
                        '###CURRENT_URL###',
                        '###REASON###'
                    ],
                    [
                        GeneralUtility::getIndpEnv('REQUEST_URI'),
                        htmlspecialchars($reason)
                    ],
                    file_get_contents($readFile)
                );
            } else {
                throw new \RuntimeException('Configuration Error: 404 page "' . $readFile . '" could not be found.', 1294587214);
            }
        } elseif (GeneralUtility::isFirstPartOfStr($code, 'REDIRECT:')) {
            HttpUtility::redirect(substr($code, 9));
        } elseif ($code !== '') {
            // Check if URL is relative
            $url_parts = parse_url($code);
            // parse_url could return an array without the key "host", the empty check works better than strict check
            if (empty($url_parts['host'])) {
                $url_parts['host'] = GeneralUtility::getIndpEnv('HTTP_HOST');
                if ($code[0] === '/') {
                    $code = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . $code;
                } else {
                    $code = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR') . $code;
                }
                $checkBaseTag = false;
            } else {
                $checkBaseTag = true;
            }
            // Check recursion
            if ($code == GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')) {
                if ($reason == '') {
                    $reason = 'Page cannot be found.';
                }
                $reason .= LF . LF . 'Additionally, ' . $code . ' was not found while trying to retrieve the error document.';
                throw new \RuntimeException(nl2br(htmlspecialchars($reason)), 1294587215);
            }
            // Prepare headers
            $headerArr = [
                'User-agent: ' . GeneralUtility::getIndpEnv('HTTP_USER_AGENT'),
                'Referer: ' . GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')
            ];
            $report = [];
            $res = GeneralUtility::getUrl($code, 1, $headerArr, $report);
            if ((int)$report['error'] !== 0 && (int)$report['error'] !== 200) {
                throw new \RuntimeException('Failed to fetch error page "' . $code . '", reason: ' . $report['message'], 1509296606);
            }
            // Header and content are separated by an empty line
            list($header, $content) = explode(CRLF . CRLF, $res, 2);
            $content .= CRLF;
            if (false === $res) {
                // Last chance -- redirect
                HttpUtility::redirect($code);
            } else {
                // Forward these response headers to the client
                $forwardHeaders = [
                    'Content-Type:'
                ];
                $headerArr = preg_split('/\\r|\\n/', $header, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($headerArr as $header) {
                    foreach ($forwardHeaders as $h) {
                        if (preg_match('/^' . $h . '/', $header)) {
                            header($header);
                        }
                    }
                }
                // Put <base> if necessary
                if ($checkBaseTag) {
                    // If content already has <base> tag, we do not need to do anything
                    if (false === stristr($content, '<base ')) {
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
            echo GeneralUtility::makeInstance(ErrorPageController::class)->errorAction(
                'Page Not Found',
                $reason ? 'Reason: ' . $reason : 'Page cannot be found.'
            );
        }
        die;
    }

    /**
     * Fetches the integer page id for a page alias.
     * Looks if ->id is not an integer and if so it will search for a page alias and if found the page uid of that page is stored in $this->id
     *
     * @access private
     */
    public function checkAndSetAlias()
    {
        if ($this->id && !MathUtility::canBeInterpretedAsInteger($this->id)) {
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
     */
    public function mergingWithGetVars($GET_VARS)
    {
        if (is_array($GET_VARS)) {
            // Getting $_GET var, unescaped.
            $realGet = GeneralUtility::_GET();
            if (!is_array($realGet)) {
                $realGet = [];
            }
            // Merge new values on top:
            ArrayUtility::mergeRecursiveWithOverrule($realGet, $GET_VARS);
            // Write values back to $_GET:
            GeneralUtility::_GETset($realGet);
            // Setting these specifically (like in the init-function):
            if (isset($GET_VARS['type'])) {
                $this->type = (int)$GET_VARS['type'];
            }
            if (isset($GET_VARS['cHash'])) {
                $this->cHash = $GET_VARS['cHash'];
            }
            if (isset($GET_VARS['MP'])) {
                $this->MP = $GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids'] ? $GET_VARS['MP'] : '';
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
     * @see reqCHash()
     */
    public function makeCacheHash()
    {
        // No need to test anything if caching was already disabled.
        if ($this->no_cache && !$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError']) {
            return;
        }
        $GET = GeneralUtility::_GET();
        if ($this->cHash && is_array($GET)) {
            // Make sure we use the page uid and not the page alias
            $GET['id'] = $this->id;
            $this->cHash_array = $this->cacheHash->getRelevantParameters(GeneralUtility::implodeArrayForUrl('', $GET));
            $cHash_calc = $this->cacheHash->calculateCacheHash($this->cHash_array);
            if (!hash_equals($cHash_calc, $this->cHash)) {
                if ($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError']) {
                    $this->pageNotFoundAndExit('Request parameters could not be validated (&cHash comparison failed)');
                } else {
                    $this->disableCache();
                    $this->getTimeTracker()->setTSlogMessage('The incoming cHash "' . $this->cHash . '" and calculated cHash "' . $cHash_calc . '" did not match, so caching was disabled. The fieldlist used was "' . implode(',', array_keys($this->cHash_array)) . '"', 2);
                }
            }
        } elseif (is_array($GET)) {
            // No cHash is set, check if that is correct
            if ($this->cacheHash->doParametersRequireCacheHash(GeneralUtility::implodeArrayForUrl('', $GET))) {
                $this->reqCHash();
            }
        }
    }

    /**
     * Will disable caching if the cHash value was not set.
     * This function should be called to check the _existence_ of "&cHash" whenever a plugin generating cacheable output is using extra GET variables. If there _is_ a cHash value the validation of it automatically takes place in makeCacheHash() (see above)
     *
     * @see makeCacheHash(), \TYPO3\CMS\Frontend\Plugin\AbstractPlugin::pi_cHashCheck()
     */
    public function reqCHash()
    {
        if (!$this->cHash) {
            if ($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFoundOnCHashError']) {
                if ($this->tempContent) {
                    $this->clearPageCacheContent();
                }
                $this->pageNotFoundAndExit('Request parameters could not be validated (&cHash empty)');
            } else {
                $this->disableCache();
                $this->getTimeTracker()->setTSlogMessage('TSFE->reqCHash(): No &cHash parameter was sent for GET vars though required so caching is disabled', 2);
            }
        }
    }

    /**
     * Initialize the TypoScript template parser
     */
    public function initTemplate()
    {
        $this->tmpl = GeneralUtility::makeInstance(TemplateService::class);
        $this->tmpl->setVerbose((bool)$this->beUserLogin);
        $this->tmpl->init();
        $this->tmpl->tt_track = (bool)$this->beUserLogin;
    }

    /**
     * See if page is in cache and get it if so
     * Stores the page content in $this->content if something is found.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getFromCache()
    {
        // clearing the content-variable, which will hold the pagecontent
        $this->content = '';
        // Unsetting the lowlevel config
        $this->config = [];
        $this->cacheContentFlag = false;

        if ($this->no_cache) {
            return;
        }

        $pageSectionCacheContent = $this->tmpl->getCurrentPageData();
        if (!is_array($pageSectionCacheContent)) {
            // Nothing in the cache, we acquire an "exclusive lock" for the key now.
            // We use the Registry to store this lock centrally,
            // but we protect the access again with a global exclusive lock to avoid race conditions

            $this->acquireLock('pagesection', $this->id . '::' . $this->MP);
            //
            // from this point on we're the only one working on that page ($key)
            //

            // query the cache again to see if the page data are there meanwhile
            $pageSectionCacheContent = $this->tmpl->getCurrentPageData();
            if (is_array($pageSectionCacheContent)) {
                // we have the content, nice that some other process did the work for us already
                $this->releaseLock('pagesection');
            }
            // We keep the lock set, because we are the ones generating the page now
                // and filling the cache.
                // This indicates that we have to release the lock in the Registry later in releaseLocks()
        }

        if (is_array($pageSectionCacheContent)) {
            // BE CAREFUL to change the content of the cc-array. This array is serialized and an md5-hash based on this is used for caching the page.
            // If this hash is not the same in here in this section and after page-generation, then the page will not be properly cached!
            // This array is an identification of the template. If $this->all is empty it's because the template-data is not cached, which it must be.
            $pageSectionCacheContent = $this->tmpl->matching($pageSectionCacheContent);
            ksort($pageSectionCacheContent);
            $this->all = $pageSectionCacheContent;
        }
        unset($pageSectionCacheContent);

        // Look for page in cache only if a shift-reload is not sent to the server.
        $lockHash = $this->getLockHash();
        if (!$this->headerNoCache()) {
            if ($this->all) {
                // we got page section information
                $this->newHash = $this->getHash();
                $this->getTimeTracker()->push('Cache Row', '');
                $row = $this->getFromCache_queryRow();
                if (!is_array($row)) {
                    // nothing in the cache, we acquire an exclusive lock now

                    $this->acquireLock('pages', $lockHash);
                    //
                    // from this point on we're the only one working on that page ($lockHash)
                    //

                    // query the cache again to see if the data are there meanwhile
                    $row = $this->getFromCache_queryRow();
                    if (is_array($row)) {
                        // we have the content, nice that some other process did the work for us
                        $this->releaseLock('pages');
                    }
                    // We keep the lock set, because we are the ones generating the page now
                        // and filling the cache.
                        // This indicates that we have to release the lock in the Registry later in releaseLocks()
                }
                if (is_array($row)) {
                    // we have data from cache

                    // Call hook when a page is retrieved from cache:
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache'])) {
                        $_params = ['pObj' => &$this, 'cache_pages_row' => &$row];
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache'] as $_funcRef) {
                            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                        }
                    }
                    // Fetches the lowlevel config stored with the cached data
                    $this->config = $row['cache_data'];
                    // Getting the content
                    $this->content = $row['content'];
                    // Flag for temp content
                    $this->tempContent = $row['temp_content'];
                    // Setting flag, so we know, that some cached content has been loaded
                    $this->cacheContentFlag = true;
                    $this->cacheExpires = $row['expires'];

                    // Restore page title information, this is needed to generate the page title for
                    // partially cached pages.
                    $this->page['title'] = $row['pageTitleInfo']['title'];
                    $this->altPageTitle = $row['pageTitleInfo']['altPageTitle'];
                    $this->indexedDocTitle = $row['pageTitleInfo']['indexedDocTitle'];

                    if (isset($this->config['config']['debug'])) {
                        $debugCacheTime = (bool)$this->config['config']['debug'];
                    } else {
                        $debugCacheTime = !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
                    }
                    if ($debugCacheTime) {
                        $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
                        $timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
                        $this->content .= LF . '<!-- Cached page generated ' . date(($dateFormat . ' ' . $timeFormat), $row['tstamp']) . '. Expires ' . date(($dateFormat . ' ' . $timeFormat), $row['expires']) . ' -->';
                    }
                }
                $this->getTimeTracker()->pull();

                return;
            }
        }
        // the user forced rebuilding the page cache or there was no pagesection information
        // get a lock for the page content so other processes will not interrupt the regeneration
        $this->acquireLock('pages', $lockHash);
    }

    /**
     * Returning the cached version of page with hash = newHash
     *
     * @return array Cached row, if any. Otherwise void.
     */
    public function getFromCache_queryRow()
    {
        $this->getTimeTracker()->push('Cache Query', '');
        $row = $this->pageCache->get($this->newHash);
        $this->getTimeTracker()->pull();
        return $row;
    }

    /**
     * Detecting if shift-reload has been clicked
     * Will not be called if re-generation of page happens by other reasons (for instance that the page is not in cache yet!)
     * Also, a backend user MUST be logged in for the shift-reload to be detected due to DoS-attack-security reasons.
     *
     * @return bool If shift-reload in client browser has been clicked, disable getting cached page (and regenerate it).
     */
    public function headerNoCache()
    {
        $disableAcquireCacheData = false;
        if ($this->beUserLogin) {
            if (strtolower($_SERVER['HTTP_CACHE_CONTROL']) === 'no-cache' || strtolower($_SERVER['HTTP_PRAGMA']) === 'no-cache') {
                $disableAcquireCacheData = true;
            }
        }
        // Call hook for possible by-pass of requiring of page cache (for recaching purpose)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache'])) {
            $_params = ['pObj' => &$this, 'disableAcquireCacheData' => &$disableAcquireCacheData];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        return $disableAcquireCacheData;
    }

    /**
     * Calculates the cache-hash
     * This hash is unique to the template, the variables ->id, ->type, ->gr_list (list of groups), ->MP (Mount Points) and cHash array
     * Used to get and later store the cached data.
     *
     * @return string MD5 hash of serialized hash base from createHashBase()
     * @access private
     * @see getFromCache(), getLockHash()
     */
    public function getHash()
    {
        return md5($this->createHashBase(false));
    }

    /**
     * Calculates the lock-hash
     * This hash is unique to the above hash, except that it doesn't contain the template information in $this->all.
     *
     * @return string MD5 hash
     * @access private
     * @see getFromCache(), getHash()
     */
    public function getLockHash()
    {
        $lockHash = $this->createHashBase(true);
        return md5($lockHash);
    }

    /**
     * Calculates the cache-hash (or the lock-hash)
     * This hash is unique to the template,
     * the variables ->id, ->type, ->gr_list (list of groups),
     * ->MP (Mount Points) and cHash array
     * Used to get and later store the cached data.
     *
     * @param bool $createLockHashBase Whether to create the lock hash, which doesn't contain the "this->all" (the template information)
     * @return string the serialized hash base
     */
    protected function createHashBase($createLockHashBase = false)
    {
        $hashParameters = [
            'id' => (int)$this->id,
            'type' => (int)$this->type,
            'gr_list' => (string)$this->gr_list,
            'MP' => (string)$this->MP,
            'cHash' => $this->cHash_array,
            'domainStartPage' => $this->domainStartPage
        ];
        // Include the template information if we shouldn't create a lock hash
        if (!$createLockHashBase) {
            $hashParameters['all'] = $this->all;
        }
        // Call hook to influence the hash calculation
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'])) {
            $_params = [
                'hashParameters' => &$hashParameters,
                'createLockHashBase' => $createLockHashBase
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        return serialize($hashParameters);
    }

    /**
     * Checks if config-array exists already but if not, gets it
     *
     * @throws ServiceUnavailableException
     */
    public function getConfigArray()
    {
        // If config is not set by the cache (which would be a major mistake somewhere) OR if INTincScripts-include-scripts have been registered, then we must parse the template in order to get it
        if (empty($this->config) || is_array($this->config['INTincScript']) || $this->forceTemplateParsing) {
            $timeTracker = $this->getTimeTracker();
            $timeTracker->push('Parse template', '');
            // Force parsing, if set?:
            $this->tmpl->forceTemplateParsing = $this->forceTemplateParsing;
            // Start parsing the TS template. Might return cached version.
            $this->tmpl->start($this->rootLine);
            $timeTracker->pull();
            if ($this->tmpl->loaded) {
                $timeTracker->push('Setting the config-array', '');
                // toplevel - objArrayName
                $this->sPre = $this->tmpl->setup['types.'][$this->type];
                $this->pSetup = $this->tmpl->setup[$this->sPre . '.'];
                if (!is_array($this->pSetup)) {
                    $message = 'The page is not configured! [type=' . $this->type . '][' . $this->sPre . '].';
                    if ($this->checkPageUnavailableHandler()) {
                        $this->pageUnavailableAndExit($message);
                    } else {
                        $explanation = 'This means that there is no TypoScript object of type PAGE with typeNum=' . $this->type . ' configured.';
                        GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                        throw new ServiceUnavailableException($message . ' ' . $explanation, 1294587217);
                    }
                } else {
                    if (!isset($this->config['config'])) {
                        $this->config['config'] = [];
                    }
                    // Filling the config-array, first with the main "config." part
                    if (is_array($this->tmpl->setup['config.'])) {
                        ArrayUtility::mergeRecursiveWithOverrule($this->tmpl->setup['config.'], $this->config['config']);
                        $this->config['config'] = $this->tmpl->setup['config.'];
                    }
                    // override it with the page/type-specific "config."
                    if (is_array($this->pSetup['config.'])) {
                        ArrayUtility::mergeRecursiveWithOverrule($this->config['config'], $this->pSetup['config.']);
                    }
                    if ($this->config['config']['typolinkEnableLinksAcrossDomains']) {
                        $this->config['config']['typolinkCheckRootline'] = true;
                    }
                    // Set default values for removeDefaultJS and inlineStyle2TempFile so CSS and JS are externalized if compatversion is higher than 4.0
                    if (!isset($this->config['config']['removeDefaultJS'])) {
                        $this->config['config']['removeDefaultJS'] = 'external';
                    }
                    if (!isset($this->config['config']['inlineStyle2TempFile'])) {
                        $this->config['config']['inlineStyle2TempFile'] = 1;
                    }

                    if (!isset($this->config['config']['compressJs'])) {
                        $this->config['config']['compressJs'] = 0;
                    }
                    // Processing for the config_array:
                    $this->config['rootLine'] = $this->tmpl->rootLine;
                    $this->config['mainScript'] = trim($this->config['config']['mainScript']) ?: 'index.php';
                    if (isset($this->config['config']['mainScript']) || $this->config['mainScript'] !== 'index.php') {
                        $this->logDeprecatedTyposcript('config.mainScript', 'Setting the frontend script to something else than index.php is deprecated as of TYPO3 v8, and will not be possible in TYPO3 v9 without a custom extension');
                    }
                    // Class for render Header and Footer parts
                    if ($this->pSetup['pageHeaderFooterTemplateFile']) {
                        $file = $this->tmpl->getFileName($this->pSetup['pageHeaderFooterTemplateFile']);
                        if ($file) {
                            $this->pageRenderer->setTemplateFile($file);
                        }
                    }
                }
                $timeTracker->pull();
            } else {
                if ($this->checkPageUnavailableHandler()) {
                    $this->pageUnavailableAndExit('No TypoScript template found!');
                } else {
                    $message = 'No TypoScript template found!';
                    GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
                    throw new ServiceUnavailableException($message, 1294587218);
                }
            }
        }

        // No cache
        // Set $this->no_cache TRUE if the config.no_cache value is set!
        if ($this->config['config']['no_cache']) {
            $this->set_no_cache('config.no_cache is set');
        }
        // Merge GET with defaultGetVars
        if (!empty($this->config['config']['defaultGetVars.'])) {
            $modifiedGetVars = GeneralUtility::removeDotsFromTS($this->config['config']['defaultGetVars.']);
            ArrayUtility::mergeRecursiveWithOverrule($modifiedGetVars, GeneralUtility::_GET());
            GeneralUtility::_GETset($modifiedGetVars);
        }
        // Hook for postProcessing the configuration array
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'])) {
            $params = ['config' => &$this->config['config']];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'] as $funcRef) {
                GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
    }

    /********************************************
     *
     * Further initialization and data processing
     *
     *******************************************/

    /**
     * Setting the language key that will be used by the current page.
     * In this function it should be checked, 1) that this language exists, 2) that a page_overlay_record exists, .. and if not the default language, 0 (zero), should be set.
     *
     * @access private
     */
    public function settingLanguage()
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess'])) {
            $_params = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }

        // Initialize charset settings etc.
        $this->initLLvars();

        // Get values from TypoScript:
        $this->sys_language_uid = ($this->sys_language_content = (int)$this->config['config']['sys_language_uid']);
        list($this->sys_language_mode, $sys_language_content) = GeneralUtility::trimExplode(';', $this->config['config']['sys_language_mode']);
        $this->sys_language_contentOL = $this->config['config']['sys_language_overlay'];
        // If sys_language_uid is set to another language than default:
        if ($this->sys_language_uid > 0) {
            // check whether a shortcut is overwritten by a translated page
            // we can only do this now, as this is the place where we get
            // to know about translations
            $this->checkTranslatedShortcut();
            // Request the overlay record for the sys_language_uid:
            $olRec = $this->sys_page->getPageOverlay($this->id, $this->sys_language_uid);
            if (empty($olRec)) {
                // If no OL record exists and a foreign language is asked for...
                if ($this->sys_language_uid) {
                    // If requested translation is not available:
                    if (GeneralUtility::hideIfNotTranslated($this->page['l18n_cfg'])) {
                        $this->pageNotFoundAndExit('Page is not available in the requested language.');
                    } else {
                        switch ((string)$this->sys_language_mode) {
                            case 'strict':
                                $this->pageNotFoundAndExit('Page is not available in the requested language (strict).');
                                break;
                            case 'content_fallback':
                                // Setting content uid (but leaving the sys_language_uid) when a content_fallback
                                // value was found.
                                $fallBackOrder = GeneralUtility::trimExplode(',', $sys_language_content);
                                foreach ($fallBackOrder as $orderValue) {
                                    if ($orderValue === '0' || $orderValue === '') {
                                        $this->sys_language_content = 0;
                                        break;
                                    }
                                    if (MathUtility::canBeInterpretedAsInteger($orderValue) && !empty($this->sys_page->getPageOverlay($this->id, (int)$orderValue))) {
                                        $this->sys_language_content = (int)$orderValue;
                                        break;
                                    }
                                    if ($orderValue === 'pageNotFound') {
                                        // The existing fallbacks have not been found, but instead of continuing
                                        // page rendering with default language, a "page not found" message should be shown
                                        // instead.
                                        $this->pageNotFoundAndExit('Page is not available in the requested language (fallbacks did not apply).');
                                    }
                                }
                                break;
                            case 'ignore':
                                $this->sys_language_content = $this->sys_language_uid;
                                break;
                            default:
                                // Default is that everything defaults to the default language...
                                $this->sys_language_uid = ($this->sys_language_content = 0);
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
        if ((!$this->sys_language_uid || !$this->sys_language_content) && GeneralUtility::hideIfDefaultLanguage($this->page['l18n_cfg'])) {
            $message = 'Page is not available in default language.';
            GeneralUtility::sysLog($message, 'cms', GeneralUtility::SYSLOG_SEVERITY_ERROR);
            $this->pageNotFoundAndExit($message);
        }
        $this->updateRootLinesWithTranslations();

        // Finding the ISO code for the currently selected language
        // fetched by the sys_language record when not fetching content from the default language
        if ($this->sys_language_content > 0) {
            // using sys_language_content because the ISO code only (currently) affect content selection from FlexForms - which should follow "sys_language_content"
            // Set the fourth parameter to TRUE in the next two getRawRecord() calls to
            // avoid versioning overlay to be applied as it generates an SQL error
            $sys_language_row = $this->sys_page->getRawRecord('sys_language', $this->sys_language_content, 'language_isocode,static_lang_isocode', true);
            if (is_array($sys_language_row) && !empty($sys_language_row['language_isocode'])) {
                $this->sys_language_isocode = $sys_language_row['language_isocode'];
            }
            // the DB value is overridden by TypoScript
            if (!empty($this->config['config']['sys_language_isocode'])) {
                $this->sys_language_isocode = $this->config['config']['sys_language_isocode'];
            }
        } else {
            // fallback to the TypoScript option when rendering with sys_language_uid=0
            // also: use "en" by default
            if (!empty($this->config['config']['sys_language_isocode_default'])) {
                $this->sys_language_isocode = $this->config['config']['sys_language_isocode_default'];
            } else {
                $this->sys_language_isocode = $this->lang !== 'default' ? $this->lang : 'en';
            }
        }

        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess'])) {
            $_params = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
    }

    /**
     * Updating content of the two rootLines IF the language key is set!
     */
    protected function updateRootLinesWithTranslations()
    {
        if ($this->sys_language_uid) {
            $this->rootLine = $this->sys_page->getRootLine($this->id, $this->MP);
            $this->tmpl->updateRootlineData($this->rootLine);
        }
    }

    /**
     * Setting locale for frontend rendering
     */
    public function settingLocale()
    {
        // Setting locale
        if ($this->config['config']['locale_all']) {
            $availableLocales = GeneralUtility::trimExplode(',', $this->config['config']['locale_all'], true);
            // If LC_NUMERIC is set e.g. to 'de_DE' PHP parses float values locale-aware resulting in strings with comma
            // as decimal point which causes problems with value conversions - so we set all locale types except LC_NUMERIC
            // @see https://bugs.php.net/bug.php?id=53711
            $locale = setlocale(LC_COLLATE, ...$availableLocales);
            if ($locale) {
                // As str_* methods are locale aware and turkish has no upper case I
                // Class autoloading and other checks depending on case changing break with turkish locale LC_CTYPE
                // @see http://bugs.php.net/bug.php?id=35050
                if (substr($this->config['config']['locale_all'], 0, 2) !== 'tr') {
                    setlocale(LC_CTYPE, ...$availableLocales);
                }
                setlocale(LC_MONETARY, ...$availableLocales);
                setlocale(LC_TIME, ...$availableLocales);
            } else {
                $this->getTimeTracker()->setTSlogMessage('Locale "' . htmlspecialchars($this->config['config']['locale_all']) . '" not found.', 3);
            }
        }
    }

    /**
     * Checks whether a translated shortcut page has a different shortcut
     * target than the original language page.
     * If that is the case, things get corrected to follow that alternative
     * shortcut
     */
    protected function checkTranslatedShortcut()
    {
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
     * This is done at this point, because we need the config values
     */
    public function handleDataSubmission()
    {
        // Hook for processing data submission to extensions
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission'] as $_classRef) {
                $_procObj = GeneralUtility::getUserObj($_classRef);
                $_procObj->checkDataSubmission($this);
            }
        }
    }

    /**
     * Loops over all configured URL handlers and registers all active handlers in the redirect URL handler array.
     *
     * @see $activeRedirectUrlHandlers
     */
    public function initializeRedirectUrlHandlers()
    {
        if (
            empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlHandlers'])
            || !is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlHandlers'])
        ) {
            return;
        }

        $urlHandlers = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['urlProcessing']['urlHandlers'];
        foreach ($urlHandlers as $identifier => $configuration) {
            if (empty($configuration) || !is_array($configuration)) {
                throw new \RuntimeException('Missing configuration for URL handler "' . $identifier . '".', 1442052263);
            }
            if (!is_string($configuration['handler']) || empty($configuration['handler']) || !class_exists($configuration['handler']) || !is_subclass_of($configuration['handler'], UrlHandlerInterface::class)) {
                throw new \RuntimeException('The URL handler "' . $identifier . '" defines an invalid provider. Ensure the class exists and implements the "' . UrlHandlerInterface::class . '".', 1442052249);
            }
        }

        $orderedHandlers = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($urlHandlers);

        foreach ($orderedHandlers as $configuration) {
            /** @var UrlHandlerInterface $urlHandler */
            $urlHandler = GeneralUtility::makeInstance($configuration['handler']);
            if ($urlHandler->canHandleCurrentUrl()) {
                $this->activeUrlHandlers[] = $urlHandler;
            }
        }
    }

    /**
     * Loops over all registered URL handlers and lets them process the current URL.
     *
     * If no handler has stopped the current process (e.g. by redirecting) and a
     * the redirectUrl propert is not empty, the user will be redirected to this URL.
     *
     * @internal Should be called by the FrontendRequestHandler only.
     */
    public function redirectToExternalUrl()
    {
        foreach ($this->activeUrlHandlers as $redirectHandler) {
            $redirectHandler->handle();
        }

        if (!empty($this->activeUrlHandlers)) {
            throw new \RuntimeException('A URL handler is active but did not process the URL.', 1442305505);
        }
    }

    /**
     * Sets the URL_ID_TOKEN in the internal var, $this->getMethodUrlIdToken
     * This feature allows sessions to use a GET-parameter instead of a cookie.
     *
     * @access private
     */
    public function setUrlIdToken()
    {
        if ($this->config['config']['ftu']) {
            $this->getMethodUrlIdToken = $GLOBALS['TYPO3_CONF_VARS']['FE']['get_url_id_token'];
        } else {
            $this->getMethodUrlIdToken = '';
        }
    }

    /**
     * Calculates and sets the internal linkVars based upon the current
     * $_GET parameters and the setting "config.linkVars".
     */
    public function calculateLinkVars()
    {
        $this->linkVars = '';
        if (empty($this->config['config']['linkVars'])) {
            return;
        }

        $linkVars = $this->splitLinkVarsString((string)$this->config['config']['linkVars']);

        if (empty($linkVars)) {
            return;
        }
        $getData = GeneralUtility::_GET();
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
                if ($test !== '' && !PageGenerator::isAllowedLinkVarValue($temp, $test)) {
                    // Error: This value was not allowed for this key
                    continue;
                }
                $value = '&' . $linkVar . '=' . $temp;
            } else {
                if ($test !== '' && $test !== 'array') {
                    // Error: This key must not be an array!
                    continue;
                }
                $value = GeneralUtility::implodeArrayForUrl($linkVar, $getData[$linkVar]);
            }
            $this->linkVars .= $value;
        }
    }

    /**
     * Split the link vars string by "," but not if the "," is inside of braces
     *
     * @param $string
     *
     * @return array
     */
    protected function splitLinkVarsString(string $string): array
    {
        $tempCommaReplacementString = '###KASPER###';

        // replace every "," wrapped in "()" by a "unique" string
        $string = preg_replace_callback('/\((?>[^()]|(?R))*\)/', function ($result) use ($tempCommaReplacementString) {
            return str_replace(',', $tempCommaReplacementString, $result[0]);
        }, $string);

        $string = GeneralUtility::trimExplode(',', $string);

        // replace all "unique" strings back to ","
        return str_replace($tempCommaReplacementString, ',', $string);
    }

    /**
     * Redirect to target page if the current page is an overlaid mountpoint.
     *
     * If the current page is of type mountpoint and should be overlaid with the contents of the mountpoint page
     * and is accessed directly, the user will be redirected to the mountpoint context.
     */
    public function checkPageForMountpointRedirect()
    {
        if (!empty($this->originalMountPointPage) && $this->originalMountPointPage['doktype'] == PageRepository::DOKTYPE_MOUNTPOINT) {
            $this->redirectToCurrentPage();
        }
    }

    /**
     * Redirect to target page, if the current page is a Shortcut.
     *
     * If the current page is of type shortcut and accessed directly via its URL, this function redirects to the
     * Shortcut target using a Location header.
     */
    public function checkPageForShortcutRedirect()
    {
        if (!empty($this->originalShortcutPage) && $this->originalShortcutPage['doktype'] == PageRepository::DOKTYPE_SHORTCUT) {
            $this->redirectToCurrentPage();
        }
    }

    /**
     * Builds a typolink to the current page, appends the type paremeter if required
     * and redirects the user to the generated URL using a Location header.
     */
    protected function redirectToCurrentPage()
    {
        $this->calculateLinkVars();
        // Instantiate \TYPO3\CMS\Frontend\ContentObject to generate the correct target URL
        /** @var $cObj ContentObjectRenderer */
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $parameter = $this->page['uid'];
        $type = GeneralUtility::_GET('type');
        if ($type && MathUtility::canBeInterpretedAsInteger($type)) {
            $parameter .= ',' . $type;
        }
        $redirectUrl = $cObj->typoLink_URL(['parameter' => $parameter, 'addQueryString' => true,
            'addQueryString.' => ['exclude' => 'id']]);

        // Prevent redirection loop
        if (!empty($redirectUrl) && GeneralUtility::getIndpEnv('REQUEST_URI') !== '/' . $redirectUrl) {
            // redirect and exit
            HttpUtility::redirect($redirectUrl, HttpUtility::HTTP_STATUS_307);
        }
    }

    /********************************************
     *
     * Page generation; cache handling
     *
     *******************************************/
    /**
     * Returns TRUE if the page should be generated.
     * That is if no URL handler is active and the cacheContentFlag is not set.
     *
     * @return bool
     */
    public function isGeneratePage()
    {
        return !$this->cacheContentFlag && empty($this->activeUrlHandlers);
    }

    /**
     * Temp cache content
     * The temporary cache will expire after a few seconds (typ. 30) or will be cleared by the rendered page, which will also clear and rewrite the cache.
     */
    public function tempPageCacheContent()
    {
        $this->tempContent = false;
        if (!$this->no_cache) {
            $seconds = 30;
            $title = htmlspecialchars($this->tmpl->printTitle($this->page['title']));
            $request_uri = htmlspecialchars(GeneralUtility::getIndpEnv('REQUEST_URI'));
            $stdMsg = '
		<strong>Page is being generated.</strong><br />
		If this message does not disappear within ' . $seconds . ' seconds, please reload.';
            $message = $this->config['config']['message_page_is_being_generated'];
            if ((string)$message !== '') {
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
                $this->set_no_cache('Another process wrote into the cache since the beginning of the render process', true);

            // Since the new Locking API this should never be the case
            } else {
                $this->tempContent = true;
                // This flag shows that temporary content is put in the cache
                $this->setPageCacheContent($temp_content, $this->config, $GLOBALS['EXEC_TIME'] + $seconds);
            }
        }
    }

    /**
     * Set cache content to $this->content
     */
    public function realPageCacheContent()
    {
        // seconds until a cached page is too old
        $cacheTimeout = $this->get_cache_timeout();
        $timeOutTime = $GLOBALS['EXEC_TIME'] + $cacheTimeout;
        $this->tempContent = false;
        $usePageCache = true;
        // Hook for deciding whether page cache should be written to the cache backend or not
        // NOTE: as hooks are called in a loop, the last hook will have the final word (however each
        // hook receives the current status of the $usePageCache flag)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['usePageCache'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['usePageCache'] as $_classRef) {
                $_procObj = GeneralUtility::getUserObj($_classRef);
                $usePageCache = $_procObj->usePageCache($this, $usePageCache);
            }
        }
        // Write the page to cache, if necessary
        if ($usePageCache) {
            $this->setPageCacheContent($this->content, $this->config, $timeOutTime);
        }
        // Hook for cache post processing (eg. writing static files!)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache'] as $_classRef) {
                $_procObj = GeneralUtility::getUserObj($_classRef);
                $_procObj->insertPageIncache($this, $timeOutTime);
            }
        }
    }

    /**
     * Sets cache content; Inserts the content string into the cache_pages cache.
     *
     * @param string $content The content to store in the HTML field of the cache table
     * @param mixed $data The additional cache_data array, fx. $this->config
     * @param int $expirationTstamp Expiration timestamp
     * @see realPageCacheContent(), tempPageCacheContent()
     */
    public function setPageCacheContent($content, $data, $expirationTstamp)
    {
        $cacheData = [
            'identifier' => $this->newHash,
            'page_id' => $this->id,
            'content' => $content,
            'temp_content' => $this->tempContent,
            'cache_data' => $data,
            'expires' => $expirationTstamp,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'pageTitleInfo' => [
                'title' => $this->page['title'],
                'altPageTitle' => $this->altPageTitle,
                'indexedDocTitle' => $this->indexedDocTitle
            ]
        ];
        $this->cacheExpires = $expirationTstamp;
        $this->pageCacheTags[] = 'pageId_' . $cacheData['page_id'];
        if ($this->page_cache_reg1) {
            $reg1 = (int)$this->page_cache_reg1;
            $cacheData['reg1'] = $reg1;
            $this->pageCacheTags[] = 'reg1_' . $reg1;
        }
        if (!empty($this->page['cache_tags'])) {
            $tags = GeneralUtility::trimExplode(',', $this->page['cache_tags'], true);
            $this->pageCacheTags = array_merge($this->pageCacheTags, $tags);
        }
        $this->pageCache->set($this->newHash, $cacheData, $this->pageCacheTags, $expirationTstamp - $GLOBALS['EXEC_TIME']);
    }

    /**
     * Clears cache content (for $this->newHash)
     */
    public function clearPageCacheContent()
    {
        $this->pageCache->remove($this->newHash);
    }

    /**
     * Clears cache content for a list of page ids
     *
     * @param string $pidList A list of INTEGER numbers which points to page uids for which to clear entries in the cache_pages cache (page content cache)
     */
    public function clearPageCacheContent_pidList($pidList)
    {
        $pageIds = GeneralUtility::trimExplode(',', $pidList);
        foreach ($pageIds as $pageId) {
            $this->pageCache->flushByTag('pageId_' . (int)$pageId);
        }
    }

    /**
     * Sets sys last changed
     * Setting the SYS_LASTCHANGED value in the pagerecord: This value will thus be set to the highest tstamp of records rendered on the page. This includes all records with no regard to hidden records, userprotection and so on.
     *
     * @see ContentObjectRenderer::lastChanged()
     */
    public function setSysLastChanged()
    {
        // Draft workspaces are always uid 1 or more. We do not update SYS_LASTCHANGED if we are browsing page from one of theses workspaces
        if ((int)$this->whichWorkspace() < 1 && $this->page['SYS_LASTCHANGED'] < (int)$this->register['SYS_LASTCHANGED']) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('pages');
            $connection->update(
                'pages',
                [
                    'SYS_LASTCHANGED' => (int)$this->register['SYS_LASTCHANGED']
                ],
                [
                    'uid' => (int)$this->id
                ]
            );
        }
    }

    /**
     * Release pending locks
     *
     * @internal
     */
    public function releaseLocks()
    {
        $this->releaseLock('pagesection');
        $this->releaseLock('pages');
    }

    /**
     * Adds tags to this page's cache entry, you can then f.e. remove cache
     * entries by tag
     *
     * @param array $tags An array of tag
     */
    public function addCacheTags(array $tags)
    {
        $this->pageCacheTags = array_merge($this->pageCacheTags, $tags);
    }

    /********************************************
     *
     * Page generation; rendering and inclusion
     *
     *******************************************/
    /**
     * Does some processing BEFORE the pagegen script is included.
     */
    public function generatePage_preProcessing()
    {
        // Same codeline as in getFromCache(). But $this->all has been changed by
        // \TYPO3\CMS\Core\TypoScript\TemplateService::start() in the meantime, so this must be called again!
        $this->newHash = $this->getHash();

        // If the pages_lock is set, we are in charge of generating the page.
        if (is_object($this->locks['pages']['accessLock'])) {
            // Here we put some temporary stuff in the cache in order to let the first hit generate the page.
            // The temporary cache will expire after a few seconds (typ. 30) or will be cleared by the rendered page,
            // which will also clear and rewrite the cache.
            $this->tempPageCacheContent();
        }
        // At this point we have a valid pagesection_cache and also some temporary page_cache content,
        // so let all other processes proceed now. (They are blocked at the pagessection_lock in getFromCache())
        $this->releaseLock('pagesection');

        // Setting cache_timeout_default. May be overridden by PHP include scripts.
        $this->cacheTimeOutDefault = (int)$this->config['config']['cache_period'];
        // Page is generated
        $this->no_cacheBeforePageGen = $this->no_cache;
    }

    /**
     * Previously located in static method in PageGenerator::init. Is solely used to set up TypoScript
     * config. options and set properties in $TSFE for that.
     */
    public function preparePageContentGeneration()
    {
        $this->getTimeTracker()->push('Prepare page content generation');
        if ($this->page['content_from_pid'] > 0) {
            // make REAL copy of TSFE object - not reference!
            $temp_copy_TSFE = clone $this;
            // Set ->id to the content_from_pid value - we are going to evaluate this pid as was it a given id for a page-display!
            $temp_copy_TSFE->id = $this->page['content_from_pid'];
            $temp_copy_TSFE->MP = '';
            $temp_copy_TSFE->getPageAndRootlineWithDomain($this->config['config']['content_from_pid_allowOutsideDomain'] ? 0 : $this->domainStartPage);
            $this->contentPid = (int)$temp_copy_TSFE->id;
            unset($temp_copy_TSFE);
        }
        if ($this->config['config']['MP_defaults']) {
            $temp_parts = GeneralUtility::trimExplode('|', $this->config['config']['MP_defaults'], true);
            foreach ($temp_parts as $temp_p) {
                list($temp_idP, $temp_MPp) = explode(':', $temp_p, 2);
                $temp_ids = GeneralUtility::intExplode(',', $temp_idP);
                foreach ($temp_ids as $temp_id) {
                    $this->MP_defaults[$temp_id] = $temp_MPp;
                }
            }
        }
        // Global vars...
        $this->indexedDocTitle = $this->page['title'];
        $this->debug = !empty($this->config['config']['debug']);
        // Base url:
        if (isset($this->config['config']['baseURL'])) {
            $this->baseUrl = $this->config['config']['baseURL'];
        }
        // Internal and External target defaults
        $this->intTarget = '' . $this->config['config']['intTarget'];
        $this->extTarget = '' . $this->config['config']['extTarget'];
        $this->fileTarget = '' . $this->config['config']['fileTarget'];
        if ($this->config['config']['spamProtectEmailAddresses'] === 'ascii') {
            $this->spamProtectEmailAddresses = 'ascii';
        } else {
            $this->spamProtectEmailAddresses = MathUtility::forceIntegerInRange($this->config['config']['spamProtectEmailAddresses'], -10, 10, 0);
        }
        // calculate the absolute path prefix
        if (!empty($this->config['config']['absRefPrefix'])) {
            $absRefPrefix = trim($this->config['config']['absRefPrefix']);
            if ($absRefPrefix === 'auto') {
                $this->absRefPrefix = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
            } else {
                $this->absRefPrefix = $absRefPrefix;
            }
        } else {
            $this->absRefPrefix = '';
        }
        if ($this->type && $this->config['config']['frameReloadIfNotInFrameset']) {
            $this->logDeprecatedTyposcript(
                'config.frameReloadIfNotInFrameset',
                'frameReloadIfNotInFrameset has been marked as deprecated since TYPO3 v8, ' .
                'and will be removed in TYPO3 v9.'
            );
            $tdlLD = $this->tmpl->linkData($this->page, '_top', $this->no_cache, '');
            $this->additionalJavaScript['JSCode'] .= 'if(!parent.' . trim($this->sPre) . ' && !parent.view_frame) top.location.href="' . $this->baseUrlWrap($tdlLD['totalURL']) . '"';
        }
        $this->compensateFieldWidth = '' . $this->config['config']['compensateFieldWidth'];
        $this->lockFilePath = '' . $this->config['config']['lockFilePath'];
        $this->lockFilePath = $this->lockFilePath ?: $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];
        if (isset($this->config['config']['noScaleUp'])) {
            $this->logDeprecatedTyposcript(
                'config.noScaleUp',
                'The TypoScript property "config.noScaleUp" is deprecated since TYPO3 v8 and will be removed in TYPO3 v9. ' .
                'Please use the global TYPO3 configuration setting "GFX/processor_allowUpscaling" instead.'
            );
        }
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling'] = (bool)(isset($this->config['config']['noScaleUp']) ? !$this->config['config']['noScaleUp'] : $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowUpscaling']);
        $this->ATagParams = trim($this->config['config']['ATagParams']) ? ' ' . trim($this->config['config']['ATagParams']) : '';
        if ($this->config['config']['setJS_mouseOver']) {
            $this->logDeprecatedTyposcript(
                'config.setJS_mouseOver',
                'The TypoScript property "config.setJS_mouseOver" is deprecated since TYPO3 v8 and will be removed in TYPO3 v9. Please include the JavaScript snippet directly via TypoScript page.jsInline.'
            );
            $this->setJS('mouseOver');
        }
        if ($this->config['config']['setJS_openPic']) {
            $this->logDeprecatedTyposcript(
                'config.setJS_openPic',
                'The TypoScript property "config.setJS_openPic" is deprecated since TYPO3 v8 and will be removed in TYPO3 v9. Please include the JavaScript snippet directly via TypoScript page.jsInline.'
            );
            $this->setJS('openPic');
        }
        $this->initializeSearchWordDataInTsfe();
        // linkVars
        $this->calculateLinkVars();
        // dtdAllowsFrames indicates whether to use the target attribute in links
        $this->dtdAllowsFrames = false;
        if ($this->config['config']['doctype']) {
            if (in_array(
                (string)$this->config['config']['doctype'],
                ['xhtml_trans', 'xhtml_frames', 'xhtml_basic', 'html5'],
                true
            )
            ) {
                $this->dtdAllowsFrames = true;
            }
        } else {
            $this->dtdAllowsFrames = true;
        }
        // Setting XHTML-doctype from doctype
        if (!$this->config['config']['xhtmlDoctype']) {
            $this->config['config']['xhtmlDoctype'] = $this->config['config']['doctype'];
        }
        if ($this->config['config']['xhtmlDoctype']) {
            $this->xhtmlDoctype = $this->config['config']['xhtmlDoctype'];
            // Checking XHTML-docytpe
            switch ((string)$this->config['config']['xhtmlDoctype']) {
                case 'xhtml_trans':
                case 'xhtml_strict':
                    $this->xhtmlVersion = 100;
                    break;
                case 'xhtml_frames':
                    $this->logDeprecatedTyposcript(
                        'config.xhtmlDoctype=frames',
                        'xhtmlDoctype = xhtml_frames  and doctype = xhtml_frames have been marked as deprecated since TYPO3 v8, ' .
                        'and will be removed in TYPO3 v9.'
                    );
                    $this->xhtmlVersion = 100;
                    break;
                case 'xhtml_basic':
                    $this->xhtmlVersion = 105;
                    break;
                case 'xhtml_11':
                case 'xhtml+rdfa_10':
                    $this->xhtmlVersion = 110;
                    break;
                default:
                    $this->pageRenderer->setRenderXhtml(false);
                    $this->xhtmlDoctype = '';
                    $this->xhtmlVersion = 0;
            }
        } else {
            $this->pageRenderer->setRenderXhtml(false);
        }

        // Global content object
        $this->newCObj();
        $this->getTimeTracker()->pull();
    }

    /**
     * Fills the sWordList property and builds the regular expression in TSFE that can be used to split
     * strings by the submitted search words.
     *
     * @see sWordList
     * @see sWordRegEx
     */
    protected function initializeSearchWordDataInTsfe()
    {
        $this->sWordRegEx = '';
        $this->sWordList = GeneralUtility::_GP('sword_list');
        if (is_array($this->sWordList)) {
            $space = !empty($this->config['config']['sword_standAlone']) ? '[[:space:]]' : '';
            foreach ($this->sWordList as $val) {
                if (trim($val) !== '') {
                    $this->sWordRegEx .= $space . preg_quote($val, '/') . $space . '|';
                }
            }
            $this->sWordRegEx = rtrim($this->sWordRegEx, '|');
        }
    }

    /**
     * Determines to include custom or pagegen.php script
     * returns script-filename if a TypoScript (config) script is defined and should be included instead of pagegen.php
     *
     * @return string|null The relative filepath of "config.pageGenScript" if found and allowed
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function generatePage_whichScript()
    {
        if (!$GLOBALS['TYPO3_CONF_VARS']['FE']['noPHPscriptInclude'] && $this->config['config']['pageGenScript']) {
            GeneralUtility::logDeprecatedFunction();
            return $this->tmpl->getFileName($this->config['config']['pageGenScript']);
        }
        return null;
    }

    /**
     * Does some processing AFTER the pagegen script is included.
     * This includes caching the page, indexing the page (if configured) and setting sysLastChanged
     */
    public function generatePage_postProcessing()
    {
        // This is to ensure, that the page is NOT cached if the no_cache parameter was set before the page was generated. This is a safety precaution, as it could have been unset by some script.
        if ($this->no_cacheBeforePageGen) {
            $this->set_no_cache('no_cache has been set before the page was generated - safety check', true);
        }
        // Hook for post-processing of page content cached/non-cached:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'])) {
            $_params = ['pObj' => &$this];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        // Processing if caching is enabled:
        if (!$this->no_cache) {
            // Hook for post-processing of page content before being cached:
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'])) {
                $_params = ['pObj' => &$this];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'] as $_funcRef) {
                    GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            }
        }
        // Convert char-set for output: (should be BEFORE indexing of the content (changed 22/4 2005)),
        // because otherwise indexed search might convert from the wrong charset!
        // One thing is that the charset mentioned in the HTML header would be wrong since the output charset (metaCharset)
        // has not been converted to from utf-8. And indexed search will internally convert from metaCharset
        // to utf-8 so the content MUST be in metaCharset already!
        $this->content = $this->convOutputCharset($this->content);
        // Hook for indexing pages
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'] as $_classRef) {
                $_procObj = GeneralUtility::getUserObj($_classRef);
                $_procObj->hook_indexContent($this);
            }
        }
        // Storing for cache:
        if (!$this->no_cache) {
            $this->realPageCacheContent();
        } elseif ($this->tempContent) {
            // If there happens to be temporary content in the cache and the cache was not cleared due to new content, put it in... ($this->no_cache=0)
            $this->clearPageCacheContent();
            $this->tempContent = false;
        }
        // Sets sys-last-change:
        $this->setSysLastChanged();
    }

    /**
     * Generate the page title again as TSFE->altPageTitle might have been modified by an inc script
     */
    protected function regeneratePageTitle()
    {
        PageGenerator::generatePageTitle();
    }

    /**
     * Processes the INTinclude-scripts
     */
    public function INTincScript()
    {
        // Deprecated stuff:
        // @deprecated: annotation added TYPO3 4.6
        $this->additionalHeaderData = is_array($this->config['INTincScript_ext']['additionalHeaderData']) ? $this->config['INTincScript_ext']['additionalHeaderData'] : [];
        $this->additionalFooterData = is_array($this->config['INTincScript_ext']['additionalFooterData']) ? $this->config['INTincScript_ext']['additionalFooterData'] : [];
        $this->additionalJavaScript = $this->config['INTincScript_ext']['additionalJavaScript'];
        $this->additionalCSS = $this->config['INTincScript_ext']['additionalCSS'];
        $this->divSection = '';
        if (empty($this->config['INTincScript_ext']['pageRenderer'])) {
            $this->initPageRenderer();
        } else {
            /** @var PageRenderer $pageRenderer */
            $pageRenderer = unserialize($this->config['INTincScript_ext']['pageRenderer']);
            $this->pageRenderer = $pageRenderer;
            GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);
        }

        $this->recursivelyReplaceIntPlaceholdersInContent();
        $this->getTimeTracker()->push('Substitute header section');
        $this->INTincScript_loadJSCode();
        $this->regeneratePageTitle();

        $this->content = str_replace(
            [
                '<!--HD_' . $this->config['INTincScript_ext']['divKey'] . '-->',
                '<!--FD_' . $this->config['INTincScript_ext']['divKey'] . '-->',
                '<!--TDS_' . $this->config['INTincScript_ext']['divKey'] . '-->'
            ],
            [
                $this->convOutputCharset(implode(LF, $this->additionalHeaderData)),
                $this->convOutputCharset(implode(LF, $this->additionalFooterData)),
                $this->convOutputCharset($this->divSection),
            ],
            $this->pageRenderer->renderJavaScriptAndCssForProcessingOfUncachedContentObjects($this->content, $this->config['INTincScript_ext']['divKey'])
        );
        // Replace again, because header and footer data and page renderer replacements may introduce additional placeholders (see #44825)
        $this->recursivelyReplaceIntPlaceholdersInContent();
        $this->setAbsRefPrefix();
        $this->getTimeTracker()->pull();
    }

    /**
     * Replaces INT placeholders (COA_INT and USER_INT) in $this->content
     * In case the replacement adds additional placeholders, it loops
     * until no new placeholders are found any more.
     */
    protected function recursivelyReplaceIntPlaceholdersInContent()
    {
        do {
            $INTiS_config = $this->config['INTincScript'];
            $this->INTincScript_process($INTiS_config);
            // Check if there were new items added to INTincScript during the previous execution:
            $INTiS_config = array_diff_assoc($this->config['INTincScript'], $INTiS_config);
            $reprocess = count($INTiS_config) > 0;
        } while ($reprocess);
    }

    /**
     * Processes the INTinclude-scripts and substitue in content.
     *
     * @param array $INTiS_config $GLOBALS['TSFE']->config['INTincScript'] or part of it
     * @see INTincScript()
     */
    protected function INTincScript_process($INTiS_config)
    {
        $timeTracker = $this->getTimeTracker();
        $timeTracker->push('Split content');
        // Splits content with the key.
        $INTiS_splitC = explode('<!--INT_SCRIPT.', $this->content);
        $this->content = '';
        $timeTracker->setTSlogMessage('Parts: ' . count($INTiS_splitC));
        $timeTracker->pull();
        foreach ($INTiS_splitC as $INTiS_c => $INTiS_cPart) {
            // If the split had a comment-end after 32 characters it's probably a split-string
            if (substr($INTiS_cPart, 32, 3) === '-->') {
                $INTiS_key = 'INT_SCRIPT.' . substr($INTiS_cPart, 0, 32);
                if (is_array($INTiS_config[$INTiS_key])) {
                    $label = 'Include ' . $INTiS_config[$INTiS_key]['type'];
                    $label = $label . isset($INTiS_config[$INTiS_key]['file']) ? ' ' . $INTiS_config[$INTiS_key]['file'] : '';
                    $timeTracker->push($label, '');
                    $incContent = '';
                    $INTiS_cObj = unserialize($INTiS_config[$INTiS_key]['cObj']);
                    /* @var $INTiS_cObj ContentObjectRenderer */
                    switch ($INTiS_config[$INTiS_key]['type']) {
                        case 'COA':
                            $incContent = $INTiS_cObj->cObjGetSingle('COA', $INTiS_config[$INTiS_key]['conf']);
                            break;
                        case 'FUNC':
                            $incContent = $INTiS_cObj->cObjGetSingle('USER', $INTiS_config[$INTiS_key]['conf']);
                            break;
                        case 'POSTUSERFUNC':
                            $incContent = $INTiS_cObj->callUserFunction($INTiS_config[$INTiS_key]['postUserFunc'], $INTiS_config[$INTiS_key]['conf'], $INTiS_config[$INTiS_key]['content']);
                            break;
                    }
                    $this->content .= $this->convOutputCharset($incContent);
                    $this->content .= substr($INTiS_cPart, 35);
                    $timeTracker->pull($incContent);
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
     */
    public function INTincScript_loadJSCode()
    {
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
' . $additionalCss . '
</style>';
        }
    }

    /**
     * Determines if there are any INTincScripts to include.
     *
     * @return bool Returns TRUE if scripts are found and no URL handler is active.
     */
    public function isINTincScript()
    {
        return is_array($this->config['INTincScript']) && empty($this->activeUrlHandlers);
    }

    /********************************************
     *
     * Finished off; outputting, storing session data, statistics...
     *
     *******************************************/
    /**
     * Determines if content should be outputted.
     * Outputting content is done only if no URL handler is active and no hook disables the output.
     *
     * @return bool Returns TRUE if no redirect URL is set and no hook disables the output.
     */
    public function isOutputting()
    {
        // Initialize by status if there is a Redirect URL
        $enableOutput = empty($this->activeUrlHandlers);
        // Call hook for possible disabling of output:
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting'])) {
            $_params = ['pObj' => &$this, 'enableOutput' => &$enableOutput];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        return $enableOutput;
    }

    /**
     * Process the output before it's actually outputted. Sends headers also.
     *
     * This includes substituting the "username" comment, sending additional headers
     * (as defined in the TypoScript "config.additionalheaders" object), XHTML cleaning content (if configured)
     * Works on $this->content.
     */
    public function processOutput()
    {
        // Set header for charset-encoding unless disabled
        if (empty($this->config['config']['disableCharsetHeader'])) {
            $headLine = 'Content-Type: ' . $this->contentType . '; charset=' . trim($this->metaCharset);
            header($headLine);
        }
        // Set header for content language unless disabled
        if (empty($this->config['config']['disableLanguageHeader']) && !empty($this->sys_language_isocode)) {
            $headLine = 'Content-Language: ' . trim($this->sys_language_isocode);
            header($headLine);
        }
        // Set cache related headers to client (used to enable proxy / client caching!)
        if (!empty($this->config['config']['sendCacheHeaders'])) {
            $this->sendCacheHeaders();
        }
        // Set headers, if any
        if (is_array($this->config['config']['additionalHeaders.'])) {
            ksort($this->config['config']['additionalHeaders.']);
            foreach ($this->config['config']['additionalHeaders.'] as $options) {
                header(
                    trim($options['header']),
                    // "replace existing headers" is turned on by default, unless turned off
                    ($options['replace'] !== '0'),
                    ((int)$options['httpResponseCode'] ?: null)
                );
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
        // Hook for post-processing of page content before output:
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'])) {
            $_params = ['pObj' => &$this];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
    }

    /**
     * Send cache headers good for client/reverse proxy caching
     * This function should not be called if the page content is temporary (like for "Page is being generated..." message, but in that case it is ok because the config-variables are not yet available and so will not allow to send cache headers)
     *
     * @co-author Ole Tange, Forbrugernes Hus, Denmark
     */
    public function sendCacheHeaders()
    {
        // Getting status whether we can send cache control headers for proxy caching:
        $doCache = $this->isStaticCacheble();
        // This variable will be TRUE unless cache headers are configured to be sent ONLY if a branch does not allow logins and logins turns out to be allowed anyway...
        $loginsDeniedCfg = empty($this->config['config']['sendCacheHeaders_onlyWhenLoginDeniedInBranch']) || empty($this->loginAllowedInBranch);
        // Finally, when backend users are logged in, do not send cache headers at all (Admin Panel might be displayed for instance).
        if ($doCache && !$this->beUserLogin && !$this->doWorkspacePreview() && $loginsDeniedCfg) {
            // Build headers:
            $headers = [
                'Expires: ' . gmdate('D, d M Y H:i:s T', $this->cacheExpires),
                'ETag: "' . md5($this->content) . '"',
                'Cache-Control: max-age=' . ($this->cacheExpires - $GLOBALS['EXEC_TIME']),
                // no-cache
                'Pragma: public'
            ];
            $this->isClientCachable = true;
        } else {
            // Build headers
            // "no-store" is used to ensure that the client HAS to ask the server every time, and is not allowed to store anything at all
            $headers = [
                'Cache-Control: private, no-store'
            ];
            $this->isClientCachable = false;
            // Now, if a backend user is logged in, tell him in the Admin Panel log what the caching status would have been:
            if ($this->beUserLogin) {
                if ($doCache) {
                    $this->getTimeTracker()->setTSlogMessage('Cache-headers with max-age "' . ($this->cacheExpires - $GLOBALS['EXEC_TIME']) . '" would have been sent');
                } else {
                    $reasonMsg = '';
                    $reasonMsg .= !$this->no_cache ? '' : 'Caching disabled (no_cache). ';
                    $reasonMsg .= !$this->isINTincScript() ? '' : '*_INT object(s) on page. ';
                    $reasonMsg .= !is_array($this->fe_user->user) ? '' : 'Frontend user logged in. ';
                    $this->getTimeTracker()->setTSlogMessage('Cache-headers would disable proxy caching! Reason(s): "' . $reasonMsg . '"', 1);
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
     * @return bool
     */
    public function isStaticCacheble()
    {
        $doCache = !$this->no_cache && !$this->isINTincScript() && !$this->isUserOrGroupSet();
        return $doCache;
    }

    /**
     * Substitute various tokens in content. This should happen only if the content is not cached by proxies or client browsers.
     */
    public function contentStrReplace()
    {
        $search = [];
        $replace = [];
        // Substitutes username mark with the username
        if (!empty($this->fe_user->user['uid'])) {
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
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace'])) {
            $contentStrReplaceHooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace'];
            if (is_array($contentStrReplaceHooks)) {
                $_params = [
                    'search' => &$search,
                    'replace' => &$replace
                ];
                foreach ($contentStrReplaceHooks as $_funcRef) {
                    GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            }
        }
        if (!empty($search)) {
            $this->content = str_replace($search, $replace, $this->content);
        }
    }

    /**
     * Stores session data for the front end user
     */
    public function storeSessionData()
    {
        $this->fe_user->storeSessionData();
    }

    /**
     * Sets the parsetime of the page.
     *
     * @access private
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, as the Request Handler is taking care of that now
     */
    public function setParseTime()
    {
        GeneralUtility::logDeprecatedFunction();
        // Compensates for the time consumed with Back end user initialization.
        $microtime_start = isset($GLOBALS['TYPO3_MISC']['microtime_start']) ? $GLOBALS['TYPO3_MISC']['microtime_start'] : null;
        $microtime_end = isset($GLOBALS['TYPO3_MISC']['microtime_end']) ? $GLOBALS['TYPO3_MISC']['microtime_end'] : null;
        $microtime_BE_USER_start = isset($GLOBALS['TYPO3_MISC']['microtime_BE_USER_start']) ? $GLOBALS['TYPO3_MISC']['microtime_BE_USER_start'] : null;
        $microtime_BE_USER_end = isset($GLOBALS['TYPO3_MISC']['microtime_BE_USER_end']) ? $GLOBALS['TYPO3_MISC']['microtime_BE_USER_end'] : null;
        $timeTracker = $this->getTimeTracker();
        $this->scriptParseTime = $timeTracker->getMilliseconds($microtime_end) - $timeTracker->getMilliseconds($microtime_start) - ($timeTracker->getMilliseconds($microtime_BE_USER_end) - $timeTracker->getMilliseconds($microtime_BE_USER_start));
    }

    /**
     * Outputs preview info.
     */
    public function previewInfo()
    {
        if ($this->fePreview !== 0) {
            $previewInfo = '';
            if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo'])) {
                $_params = ['pObj' => &$this];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo'] as $_funcRef) {
                    $previewInfo .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            }
            $this->content = str_ireplace('</body>', $previewInfo . '</body>', $this->content);
        }
    }

    /**
     * End-Of-Frontend hook
     */
    public function hook_eofe()
    {
        // Call hook for end-of-frontend processing:
        if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']) && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe'])) {
            $_params = ['pObj' => &$this];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
    }

    /**
     * Returns a link to the BE login screen with redirect to the front-end
     *
     * @return string HTML, a tag for a link to the backend.
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function beLoginLinkIPList()
    {
        GeneralUtility::logDeprecatedFunction();
        if (!empty($this->config['config']['beLoginLinkIPList'])) {
            if (GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $this->config['config']['beLoginLinkIPList'])) {
                $label = !$this->beUserLogin ? $this->config['config']['beLoginLinkIPList_login'] : $this->config['config']['beLoginLinkIPList_logout'];
                if ($label) {
                    if (!$this->beUserLogin) {
                        $link = '<a href="' . htmlspecialchars((TYPO3_mainDir . 'index.php?redirect_url=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . $label . '</a>';
                    } else {
                        $link = '<a href="' . htmlspecialchars((TYPO3_mainDir . 'index.php?L=OUT&redirect_url=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI')))) . '">' . $label . '</a>';
                    }
                    return $link;
                }
            }
        }
        return '';
    }

    /**
     * Sends HTTP headers for temporary content. These headers prevent search engines from caching temporary content and asks them to revisit this page again.
     */
    public function addTempContentHttpHeaders()
    {
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
     * @param int $n Ordinal of input character
     * @param int $start Start of range
     * @param int $end End of range
     * @param int $offset Offset
     * @return string encoded/decoded version of character
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, this functionality has been moved to ContentObjectRenderer
     */
    public function encryptCharcode($n, $start, $end, $offset)
    {
        GeneralUtility::logDeprecatedFunction();
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
     * @param bool $back If set, the process is reversed, effectively decoding, not encoding.
     * @return string encoded/decoded version of $string
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, this functionality has been moved to ContentObjectRenderer
     */
    public function encryptEmail($string, $back = false)
    {
        GeneralUtility::logDeprecatedFunction();
        $out = '';
        // obfuscates using the decimal HTML entity references for each character
        if ($this->spamProtectEmailAddresses === 'ascii') {
            $stringLength = strlen($string);
            for ($a = 0; $a < $stringLength; $a++) {
                $out .= '&#' . ord(substr($string, $a, 1)) . ';';
            }
        } else {
            // like str_rot13() but with a variable offset and a wider character range
            $len = strlen($string);
            $offset = (int)$this->spamProtectEmailAddresses * ($back ? -1 : 1);
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
     * Creates an instance of ContentObjectRenderer in $this->cObj
     * This instance is used to start the rendering of the TypoScript template structure
     *
     * @see pagegen.php
     */
    public function newCObj()
    {
        $this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->cObj->start($this->page, 'pages');
    }

    /**
     * Converts relative paths in the HTML source to absolute paths for fileadmin/, typo3conf/ext/ and media/ folders.
     *
     * @access private
     * @see pagegen.php, INTincScript()
     */
    public function setAbsRefPrefix()
    {
        if (!$this->absRefPrefix) {
            return;
        }
        $search = [
            '"typo3temp/',
            '"typo3conf/ext/',
            '"' . TYPO3_mainDir . 'ext/',
            '"' . TYPO3_mainDir . 'sysext/'
        ];
        $replace = [
            '"' . $this->absRefPrefix . 'typo3temp/',
            '"' . $this->absRefPrefix . 'typo3conf/ext/',
            '"' . $this->absRefPrefix . TYPO3_mainDir . 'ext/',
            '"' . $this->absRefPrefix . TYPO3_mainDir . 'sysext/'
        ];
        /** @var $storageRepository StorageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $storages = $storageRepository->findAll();
        foreach ($storages as $storage) {
            if ($storage->getDriverType() === 'Local' && $storage->isPublic() && $storage->isOnline()) {
                $folder = $storage->getPublicUrl($storage->getRootLevelFolder(), true);
                $search[] = '"' . $folder;
                $replace[] = '"' . $this->absRefPrefix . $folder;
            }
        }
        // Process additional directories
        $directories = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'], true);
        foreach ($directories as $directory) {
            $search[] = '"' . $directory;
            $replace[] = '"' . $this->absRefPrefix . $directory;
        }
        $this->content = str_replace(
            $search,
            $replace,
            $this->content
        );
    }

    /**
     * Prefixing the input URL with ->baseUrl If ->baseUrl is set and the input url is not absolute in some way.
     * Designed as a wrapper functions for use with all frontend links that are processed by JavaScript (for "realurl" compatibility!). So each time a URL goes into window.open, window.location.href or otherwise, wrap it with this function!
     *
     * @param string $url Input URL, relative or absolute
     * @return string Processed input value.
     */
    public function baseUrlWrap($url)
    {
        if ($this->baseUrl) {
            $urlParts = parse_url($url);
            if (empty($urlParts['scheme']) && $url[0] !== '/') {
                $url = $this->baseUrl . $url;
            }
        }
        return $url;
    }

    /**
     * Logs access to deprecated TypoScript objects and properties.
     *
     * Dumps message to the TypoScript message log (admin panel) and the TYPO3 deprecation log.
     *
     * @param string $typoScriptProperty Deprecated object or property
     * @param string $explanation Message or additional information
     */
    public function logDeprecatedTyposcript($typoScriptProperty, $explanation = '')
    {
        $explanationText = $explanation !== '' ? ' - ' . $explanation : '';
        $this->getTimeTracker()->setTSlogMessage($typoScriptProperty . ' is deprecated.' . $explanationText, 2);
        GeneralUtility::deprecationLog('TypoScript ' . $typoScriptProperty . ' is deprecated' . $explanationText);
    }

    /**
     * Updates the tstamp field of a cache_md5params record to the current time.
     *
     * @param string $hash The hash string identifying the cache_md5params record for which to update the "tstamp" field to the current time.
     * @access private
     */
    public function updateMD5paramsRecord($hash)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('cache_md5params');
        $connection->update(
            'cache_md5params',
            [
                'tstamp' => $GLOBALS['EXEC_TIME']
            ],
            [
                'md5hash' => $hash
            ]
        );
    }

    /********************************************
     * PUBLIC ACCESSIBLE WORKSPACES FUNCTIONS
     *******************************************/

    /**
     * Returns TRUE if workspace preview is enabled
     *
     * @return bool Returns TRUE if workspace preview is enabled
     */
    public function doWorkspacePreview()
    {
        return $this->workspacePreview !== 0;
    }

    /**
     * Returns the name of the workspace
     *
     * @param bool $returnTitle If set, returns title of current workspace being previewed, please be aware that this parameter is deprecated as of TYPO3 v8, and will be removed in TYPO3 v9
     * @return string|int|null If $returnTitle is set, returns string (title), otherwise workspace integer for which workspace is being preview. NULL if none.
     */
    public function whichWorkspace($returnTitle = false)
    {
        $ws = null;
        if ($this->doWorkspacePreview()) {
            $ws = (int)$this->workspacePreview;
        } elseif ($this->beUserLogin) {
            $ws = $this->getBackendUser()->workspace;
        }
        if ($ws && $returnTitle) {
            GeneralUtility::deprecationLog('The parameter $returnTitle of $TSFE->whichWorkspace() is marked as deprecated and has no effect anymore. It will be removed in TYPO3 v9.');
            if (ExtensionManagementUtility::isLoaded('workspaces')) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('sys_workspace');

                $queryBuilder->getRestrictions()->removeAll();

                $row = $queryBuilder
                    ->select('title')
                    ->from('sys_workspace')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($ws, \PDO::PARAM_INT)
                        )
                    )
                    ->execute()
                    ->fetch();

                if ($row) {
                    return $row['title'];
                }
            }
        }
        return $ws;
    }

    /**
     * Includes a comma-separated list of library files by PHP function include_once.
     *
     * @param array $libraries The libraries to be included.
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9, use proper class loading instead.
     */
    public function includeLibraries(array $libraries)
    {
        GeneralUtility::logDeprecatedFunction();
        $timeTracker = $this->getTimeTracker();
        $timeTracker->push('Include libraries');
        $timeTracker->setTSlogMessage('Files for inclusion: "' . implode(', ', $libraries) . '"');
        foreach ($libraries as $library) {
            $file = $this->tmpl->getFileName($library);
            if ($file) {
                include_once './' . $file;
            } else {
                $timeTracker->setTSlogMessage('Include file "' . $file . '" did not exist!', 2);
            }
        }
        $timeTracker->pull();
    }

    /********************************************
     *
     * Various external API functions - for use in plugins etc.
     *
     *******************************************/

    /**
     * Returns the pages TSconfig array based on the currect ->rootLine
     *
     * @return array
     */
    public function getPagesTSconfig()
    {
        if (!is_array($this->pagesTSconfig)) {
            $TSdataArray = [];
            foreach ($this->rootLine as $k => $v) {
                // add TSconfig first, as $TSdataArray is reversed below and it shall be included last
                $TSdataArray[] = $v['TSconfig'];
                if (trim($v['tsconfig_includes'])) {
                    $includeTsConfigFileList = GeneralUtility::trimExplode(',', $v['tsconfig_includes'], true);
                    // reverse the includes first to make sure their order is preserved when $TSdataArray is reversed
                    $includeTsConfigFileList = array_reverse($includeTsConfigFileList);
                    // Traversing list
                    foreach ($includeTsConfigFileList as $includeTsConfigFile) {
                        if (strpos($includeTsConfigFile, 'EXT:') === 0) {
                            list($includeTsConfigFileExtensionKey, $includeTsConfigFilename) = explode(
                                '/',
                                substr($includeTsConfigFile, 4),
                                2
                            );
                            if ((string)$includeTsConfigFileExtensionKey !== ''
                                && (string)$includeTsConfigFilename !== ''
                                && ExtensionManagementUtility::isLoaded($includeTsConfigFileExtensionKey)
                            ) {
                                $includeTsConfigFileAndPath = ExtensionManagementUtility::extPath($includeTsConfigFileExtensionKey)
                                    . $includeTsConfigFilename;
                                if (file_exists($includeTsConfigFileAndPath)) {
                                    $TSdataArray[] = file_get_contents($includeTsConfigFileAndPath);
                                }
                            }
                        }
                    }
                }
            }
            // Adding the default configuration:
            $TSdataArray[] = $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'];
            // Bring everything in the right order. Default first, then the Rootline down to the current page
            $TSdataArray = array_reverse($TSdataArray);
            // Parsing the user TS (or getting from cache)
            $TSdataArray = TypoScriptParser::checkIncludeLines_array($TSdataArray);
            $userTS = implode(LF . '[GLOBAL]' . LF, $TSdataArray);
            $identifier = md5('pageTS:' . $userTS);
            $contentHashCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_hash');
            $this->pagesTSconfig = $contentHashCache->get($identifier);
            if (!is_array($this->pagesTSconfig)) {
                $parseObj = GeneralUtility::makeInstance(TypoScriptParser::class);
                $parseObj->parse($userTS);
                $this->pagesTSconfig = $parseObj->setup;
                $contentHashCache->set($identifier, $this->pagesTSconfig, ['PAGES_TSconfig'], 0);
            }
        }
        return $this->pagesTSconfig;
    }

    /**
     * Sets JavaScript code in the additionalJavaScript array
     *
     * @param string $key is the key in the array, for num-key let the value be empty. Note reserved keys 'openPic' and 'mouseOver'
     * @param string $content is the content if you want any
     * @see \TYPO3\CMS\Frontend\ContentObject\Menu\GraphicalMenuContentObject::writeMenu(), ContentObjectRenderer::imageLinkWrap()
     */
    public function setJS($key, $content = '')
    {
        if ($key) {
            switch ($key) {
                case 'mouseOver':
                    $this->additionalJavaScript[$key] = '		// JS function for mouse-over
		function over(name, imgObj) {	//
			if (document[name]) {document[name].src = eval(name+"_h.src");}
			else if (document.getElementById && document.getElementById(name)) {document.getElementById(name).src = eval(name+"_h.src");}
			else if (imgObj)	{imgObj.src = eval(name+"_h.src");}
		}
			// JS function for mouse-out
		function out(name, imgObj) {	//
			if (document[name]) {document[name].src = eval(name+"_n.src");}
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
            }
        }
    }

    /**
     * Sets CSS data in the additionalCSS array
     *
     * @param string $key Is the key in the array, for num-key let the value be empty
     * @param string $content Is the content if you want any
     * @see setJS()
     */
    public function setCSS($key, $content)
    {
        if ($key) {
            $this->additionalCSS[$key] = $content;
        }
    }

    /**
     * Returns a unique md5 hash.
     * There is no special magic in this, the only point is that you don't have to call md5(uniqid()) which is slow and by this you are sure to get a unique string each time in a little faster way.
     *
     * @param string $str Some string to include in what is hashed. Not significant at all.
     * @return string MD5 hash of ->uniqueString, input string and uniqueCounter
     */
    public function uniqueHash($str = '')
    {
        return md5($this->uniqueString . '_' . $str . $this->uniqueCounter++);
    }

    /**
     * Sets the cache-flag to 1. Could be called from user-included php-files in order to ensure that a page is not cached.
     *
     * @param string $reason An optional reason to be written to the syslog.
     * @param bool $internal Whether the call is done from core itself (should only be used by core).
     */
    public function set_no_cache($reason = '', $internal = false)
    {
        if ($internal && isset($GLOBALS['BE_USER'])) {
            $severity = GeneralUtility::SYSLOG_SEVERITY_NOTICE;
        } else {
            $severity = GeneralUtility::SYSLOG_SEVERITY_WARNING;
        }

        if ($reason !== '') {
            $warning = '$TSFE->set_no_cache() was triggered. Reason: ' . $reason . '.';
        } else {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            // This is a hack to work around ___FILE___ resolving symbolic links
            $PATH_site_real = dirname(realpath(PATH_site . 'typo3')) . '/';
            $file = $trace[0]['file'];
            if (strpos($file, $PATH_site_real) === 0) {
                $file = str_replace($PATH_site_real, '', $file);
            } else {
                $file = str_replace(PATH_site, '', $file);
            }
            $line = $trace[0]['line'];
            $trigger = $file . ' on line ' . $line;
            $warning = '$GLOBALS[\'TSFE\']->set_no_cache() was triggered by ' . $trigger . '.';
        }
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter']) {
            $warning .= ' However, $TYPO3_CONF_VARS[\'FE\'][\'disableNoCacheParameter\'] is set, so it will be ignored!';
            $this->getTimeTracker()->setTSlogMessage($warning, 2);
        } else {
            $warning .= ' Caching is disabled!';
            $this->disableCache();
        }
        GeneralUtility::sysLog($warning, 'cms', $severity);
    }

    /**
     * Disables caching of the current page.
     *
     * @internal
     */
    protected function disableCache()
    {
        $this->no_cache = true;
    }

    /**
     * Sets the cache-timeout in seconds
     *
     * @param int $seconds Cache-timeout in seconds
     */
    public function set_cache_timeout_default($seconds)
    {
        $this->cacheTimeOutDefault = (int)$seconds;
    }

    /**
     * Get the cache timeout for the current page.
     *
     * @return int The cache timeout for the current page.
     */
    public function get_cache_timeout()
    {
        /** @var $runtimeCache \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend */
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
        $cachedCacheLifetimeIdentifier = 'core-tslib_fe-get_cache_timeout';
        $cachedCacheLifetime = $runtimeCache->get($cachedCacheLifetimeIdentifier);
        if ($cachedCacheLifetime === false) {
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
            }

            // Calculate the timeout time for records on the page and adjust cache timeout if necessary
            $cacheTimeout = min($this->calculatePageCacheTimeout(), $cacheTimeout);

            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['get_cache_timeout'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['get_cache_timeout'] as $_funcRef) {
                    $params = ['cacheTimeout' => $cacheTimeout];
                    $cacheTimeout = GeneralUtility::callUserFunction($_funcRef, $params, $this);
                }
            }
            $runtimeCache->set($cachedCacheLifetimeIdentifier, $cacheTimeout);
            $cachedCacheLifetime = $cacheTimeout;
        }
        return $cachedCacheLifetime;
    }

    /**
     * Returns a unique id to be used as a XML ID (in HTML / XHTML mode)
     *
     * @param string $desired The desired id. If already used it is suffixed with a number
     * @return string The unique id
     */
    public function getUniqueId($desired = '')
    {
        if ($desired === '') {
            // id has to start with a letter to reach XHTML compliance
            $uniqueId = 'a' . $this->uniqueHash();
        } else {
            $uniqueId = $desired;
            for ($i = 1; isset($this->usedUniqueIds[$uniqueId]); $i++) {
                $uniqueId = $desired . '_' . $i;
            }
        }
        $this->usedUniqueIds[$uniqueId] = true;
        return $uniqueId;
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
     */
    public function sL($input)
    {
        if (substr($input, 0, 4) !== 'LLL:') {
            // Not a label, return the key as this
            return $input;
        }
        // If cached label
        if (!isset($this->LL_labels_cache[$this->lang][$input])) {
            $restStr = trim(substr($input, 4));
            $extPrfx = '';
            if (strpos($restStr, 'EXT:') === 0) {
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
     */
    public function readLLfile($fileRef)
    {
        /** @var $languageFactory LocalizationFactory */
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);

        if ($this->lang !== 'default') {
            $languages = array_reverse($this->languageDependencies);
            // At least we need to have English
            if (empty($languages)) {
                $languages[] = 'default';
            }
        } else {
            $languages = ['default'];
        }

        $localLanguage = [];
        foreach ($languages as $language) {
            $tempLL = $languageFactory->getParsedData($fileRef, $language, 'utf-8');
            $localLanguage['default'] = $tempLL['default'];
            if (!isset($localLanguage[$this->lang])) {
                $localLanguage[$this->lang] = $localLanguage['default'];
            }
            if ($this->lang !== 'default' && isset($tempLL[$language])) {
                // Merge current language labels onto labels from previous language
                // This way we have a label with fall back applied
                ArrayUtility::mergeRecursiveWithOverrule($localLanguage[$this->lang], $tempLL[$language], true, false);
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
     */
    public function getLLL($index, $LOCAL_LANG)
    {
        if (isset($LOCAL_LANG[$this->lang][$index][0]['target'])) {
            return $LOCAL_LANG[$this->lang][$index][0]['target'];
        }
        if (isset($LOCAL_LANG['default'][$index][0]['target'])) {
            return $LOCAL_LANG['default'][$index][0]['target'];
        }
        return false;
    }

    /**
     * Initializing the getLL variables needed.
     */
    public function initLLvars()
    {
        // Init languageDependencies list
        $this->languageDependencies = [];
        // Setting language key and split index:
        $this->lang = $this->config['config']['language'] ?: 'default';
        $this->pageRenderer->setLanguage($this->lang);

        // Finding the requested language in this list based
        // on the $lang key being inputted to this function.
        /** @var $locales Locales */
        $locales = GeneralUtility::makeInstance(Locales::class);
        $locales->initialize();

        // Language is found. Configure it:
        if (in_array($this->lang, $locales->getLocales())) {
            $this->languageDependencies[] = $this->lang;
            foreach ($locales->getLocaleDependencies($this->lang) as $language) {
                $this->languageDependencies[] = $language;
            }
        }

        // Rendering charset of HTML page.
        if ($this->config['config']['metaCharset']) {
            $this->metaCharset = trim(strtolower($this->config['config']['metaCharset']));
        }
    }

    /**
     * Converts the charset of the input string if applicable.
     * The "to" charset is determined by the currently used charset for the page which is "utf-8" by default
     * Only if there is a difference between the two charsets will a conversion be made
     * The conversion is done real-time - no caching for performance at this point!
     *
     * @param string $str String to convert charset for
     * @param string $from Optional "from" charset.
     * @return string Output string, converted if needed.
     * @see CharsetConverter
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function csConv($str, $from = '')
    {
        GeneralUtility::logDeprecatedFunction();
        if ($from) {
            /** @var CharsetConverter $charsetConverter */
            $charsetConverter = GeneralUtility::makeInstance(CharsetConverter::class);
            $output = $charsetConverter->conv($str, $charsetConverter->parse_charset($from), 'utf-8');
            return $output ?: $str;
        }
        return $str;
    }

    /**
     * Converts input string from utf-8 to metaCharset IF the two charsets are different.
     *
     * @param string $content Content to be converted.
     * @return string Converted content string.
     */
    public function convOutputCharset($content)
    {
        if ($this->metaCharset !== 'utf-8') {
            /** @var CharsetConverter $charsetConverter */
            $charsetConverter = GeneralUtility::makeInstance(CharsetConverter::class);
            $content = $charsetConverter->conv($content, 'utf-8', $this->metaCharset, true);
        }
        return $content;
    }

    /**
     * Converts the $_POST array from metaCharset (page HTML charset from input form) to utf-8 (internal processing) IF the two charsets are different.
     */
    public function convPOSTCharset()
    {
        if ($this->metaCharset !== 'utf-8' && is_array($_POST) && !empty($_POST)) {
            $this->convertCharsetRecursivelyToUtf8($_POST, $this->metaCharset);
            $GLOBALS['HTTP_POST_VARS'] = $_POST;
        }
    }

    /**
     * Small helper function to convert charsets for arrays to UTF-8
     *
     * @param mixed $data given by reference (string/array usually)
     * @param string $fromCharset convert FROM this charset
     */
    protected function convertCharsetRecursivelyToUtf8(&$data, string $fromCharset)
    {
        foreach ($data as $key => $value) {
            if (is_array($data[$key])) {
                $this->convertCharsetRecursivelyToUtf8($data[$key], $fromCharset);
            } elseif (is_string($data[$key])) {
                $data[$key] = mb_convert_encoding($data[$key], 'utf-8', $fromCharset);
            }
        }
    }

    /**
     * Calculates page cache timeout according to the records with starttime/endtime on the page.
     *
     * @return int Page cache timeout or PHP_INT_MAX if cannot be determined
     */
    protected function calculatePageCacheTimeout()
    {
        $result = PHP_INT_MAX;
        // Get the configuration
        $tablesToConsider = $this->getCurrentPageCacheConfiguration();
        // Get the time, rounded to the minute (do not pollute MySQL cache!)
        // It is ok that we do not take seconds into account here because this
        // value will be subtracted later. So we never get the time "before"
        // the cache change.
        $now = $GLOBALS['ACCESS_TIME'];
        // Find timeout by checking every table
        foreach ($tablesToConsider as $tableDef) {
            $result = min($result, $this->getFirstTimeValueForRecord($tableDef, $now));
        }
        // We return + 1 second just to ensure that cache is definitely regenerated
        return $result === PHP_INT_MAX ? PHP_INT_MAX : $result - $now + 1;
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
     * @see TypoScriptFrontendController::calculatePageCacheTimeout()
     */
    protected function getCurrentPageCacheConfiguration()
    {
        $result = ['tt_content:' . $this->id];
        if (isset($this->config['config']['cache.'][$this->id])) {
            $result = array_merge($result, GeneralUtility::trimExplode(',', $this->config['config']['cache.'][$this->id]));
        }
        if (isset($this->config['config']['cache.']['all'])) {
            $result = array_merge($result, GeneralUtility::trimExplode(',', $this->config['config']['cache.']['all']));
        }
        return array_unique($result);
    }

    /**
     * Find the minimum starttime or endtime value in the table and pid that is greater than the current time.
     *
     * @param string $tableDef Table definition (format tablename:pid)
     * @param int $now "Now" time value
     * @throws \InvalidArgumentException
     * @return int Value of the next start/stop time or PHP_INT_MAX if not found
     * @see TypoScriptFrontendController::calculatePageCacheTimeout()
     */
    protected function getFirstTimeValueForRecord($tableDef, $now)
    {
        $now = (int)$now;
        $result = PHP_INT_MAX;
        list($tableName, $pid) = GeneralUtility::trimExplode(':', $tableDef);
        if (empty($tableName) || empty($pid)) {
            throw new \InvalidArgumentException('Unexpected value for parameter $tableDef. Expected <tablename>:<pid>, got \'' . htmlspecialchars($tableDef) . '\'.', 1307190365);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);
        $timeFields = [];
        $timeConditions = $queryBuilder->expr()->orX();
        foreach (['starttime', 'endtime'] as $field) {
            if (isset($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field])) {
                $timeFields[$field] = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'][$field];
                $queryBuilder->addSelectLiteral(
                    'MIN('
                        . 'CASE WHEN '
                        . $queryBuilder->expr()->lte(
                            $timeFields[$field],
                            $queryBuilder->createNamedParameter($now, \PDO::PARAM_INT)
                        )
                        . ' THEN NULL ELSE ' . $queryBuilder->quoteIdentifier($timeFields[$field]) . ' END'
                        . ') AS ' . $queryBuilder->quoteIdentifier($timeFields[$field])
                );
                $timeConditions->add(
                    $queryBuilder->expr()->gt(
                        $timeFields[$field],
                        $queryBuilder->createNamedParameter($now, \PDO::PARAM_INT)
                    )
                );
            }
        }

        // if starttime or endtime are defined, evaluate them
        if (!empty($timeFields)) {
            // find the timestamp, when the current page's content changes the next time
            $row = $queryBuilder
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                    ),
                    $timeConditions
                )
                ->execute()
                ->fetch();

            if ($row) {
                foreach ($timeFields as $timeField => $_) {
                    // if a MIN value is found, take it into account for the
                    // cache lifetime we have to filter out start/endtimes < $now,
                    // as the SQL query also returns rows with starttime < $now
                    // and endtime > $now (and using a starttime from the past
                    // would be wrong)
                    if ($row[$timeField] !== null && (int)$row[$timeField] > $now) {
                        $result = min($result, (int)$row[$timeField]);
                    }
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
    protected function getSysDomainCache()
    {
        $entryIdentifier = 'core-database-sys_domain-complete';
        /** @var $runtimeCache \TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend */
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');

        $sysDomainData = [];
        if ($runtimeCache->has($entryIdentifier)) {
            $sysDomainData = $runtimeCache->get($entryIdentifier);
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_domain');
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(DefaultRestrictionContainer::class));
            $result = $queryBuilder
                ->select('uid', 'pid', 'domainName', 'forced')
                ->from('sys_domain')
                ->where(
                    $queryBuilder->expr()->eq(
                        'redirectTo',
                        $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)
                    )
                )
                ->orderBy('sorting', 'ASC')
                ->execute();

            while ($row = $result->fetch()) {
                // if there is already an entry for this pid, check if we should overwrite it
                if (isset($sysDomainData[$row['pid']])) {
                    // There is already a "forced" entry, which must not be overwritten
                    if ($sysDomainData[$row['pid']]['forced']) {
                        continue;
                    }

                    // The current domain record is also NOT-forced, keep the old unless the new one matches the current request
                    if (!$row['forced'] && !$this->domainNameMatchesCurrentRequest($row['domainName'])) {
                        continue;
                    }
                }

                // as we passed all previous checks, we save this domain for the current pid
                $sysDomainData[$row['pid']] = [
                    'uid' => $row['uid'],
                    'pid' => $row['pid'],
                    'domainName' => rtrim($row['domainName'], '/'),
                    'forced' => $row['forced'],
                ];
            }
            $runtimeCache->set($entryIdentifier, $sysDomainData);
        }
        return $sysDomainData;
    }

    /**
     * Whether the given domain name (potentially including a path segment) matches currently requested host or
     * the host including the path segment
     *
     * @param string $domainName
     * @return bool
     */
    public function domainNameMatchesCurrentRequest($domainName)
    {
        $currentDomain = GeneralUtility::getIndpEnv('HTTP_HOST');
        $currentPathSegment = trim(preg_replace('|/[^/]*$|', '', GeneralUtility::getIndpEnv('SCRIPT_NAME')));
        return $currentDomain === $domainName || $currentDomain . $currentPathSegment === $domainName;
    }

    /**
     * Obtains domain data for the target pid. Domain data is an array with
     * 'pid', 'domainName' and 'forced' members (see sys_domain table for
     * meaning of these fields.
     *
     * @param int $targetPid Target page id
     * @return mixed Return domain data or NULL
    */
    public function getDomainDataForPid($targetPid)
    {
        // Using array_key_exists() here, nice $result can be NULL
        // (happens, if there's no domain records defined)
        if (!array_key_exists($targetPid, $this->domainDataCache)) {
            $result = null;
            $sysDomainData = $this->getSysDomainCache();
            $rootline = $this->sys_page->getRootLine($targetPid);
            // walk the rootline downwards from the target page
            // to the root page, until a domain record is found
            foreach ($rootline as $pageInRootline) {
                $pidInRootline = $pageInRootline['uid'];
                if (isset($sysDomainData[$pidInRootline])) {
                    $result = $sysDomainData[$pidInRootline];
                    break;
                }
            }
            $this->domainDataCache[$targetPid] = $result;
        }

        return $this->domainDataCache[$targetPid];
    }

    /**
     * Obtains the domain name for the target pid. If there are several domains,
     * the first is returned.
     *
     * @param int $targetPid Target page id
     * @return mixed Return domain name or NULL if not found
     */
    public function getDomainNameForPid($targetPid)
    {
        $domainData = $this->getDomainDataForPid($targetPid);
        return $domainData ? $domainData['domainName'] : null;
    }

    /**
     * Fetches the originally requested id, fallsback to $this->id
     *
     * @return int the originally requested page uid
     * @see fetch_the_id()
     */
    public function getRequestedId()
    {
        return $this->requestedId ?: $this->id;
    }

    /**
     * Acquire a page specific lock
     *
     * @param string $type
     * @param string $key
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function acquireLock($type, $key)
    {
        $lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $this->locks[$type]['accessLock'] = $lockFactory->createLocker($type);

        $this->locks[$type]['pageLock'] = $lockFactory->createLocker(
            $key,
            LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
        );

        do {
            if (!$this->locks[$type]['accessLock']->acquire()) {
                throw new \RuntimeException('Could not acquire access lock for "' . $type . '"".', 1294586098);
            }

            try {
                $locked = $this->locks[$type]['pageLock']->acquire(
                    LockingStrategyInterface::LOCK_CAPABILITY_EXCLUSIVE | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
                );
            } catch (LockAcquireWouldBlockException $e) {
                // somebody else has the lock, we keep waiting

                // first release the access lock
                $this->locks[$type]['accessLock']->release();
                // now lets make a short break (100ms) until we try again, since
                // the page generation by the lock owner will take a while anyways
                usleep(100000);
                continue;
            }
            $this->locks[$type]['accessLock']->release();
            if ($locked) {
                break;
            }
            throw new \RuntimeException('Could not acquire page lock for ' . $key . '.', 1460975877);
        } while (true);
    }

    /**
     * Release a page specific lock
     *
     * @param string $type
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException
     */
    protected function releaseLock($type)
    {
        if ($this->locks[$type]['accessLock']) {
            if (!$this->locks[$type]['accessLock']->acquire()) {
                throw new \RuntimeException('Could not acquire access lock for "' . $type . '"".', 1460975902);
            }

            $this->locks[$type]['pageLock']->release();
            $this->locks[$type]['pageLock']->destroy();
            $this->locks[$type]['pageLock'] = null;

            $this->locks[$type]['accessLock']->release();
            $this->locks[$type]['accessLock'] = null;
        }
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Backend\FrontendBackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * Returns an instance of DocumentTemplate
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }
}
