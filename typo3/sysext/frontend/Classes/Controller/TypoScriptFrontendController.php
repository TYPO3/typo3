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

namespace TYPO3\CMS\Frontend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\PageTsConfig;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Access\RecordAccessVoter;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Error\Http\AbstractServerErrorException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Error\Http\ShortcutTargetPageNotFoundException;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use TYPO3\CMS\Frontend\Typolink\LinkVarsCalculator;

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
class TypoScriptFrontendController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * The page id (int)
     */
    public int $id;

    /**
     * The type (read-only)
     * @var int|string
     */
    public $type = 0;

    protected Site $site;
    protected SiteLanguage $language;

    /**
     * @internal
     */
    protected PageArguments $pageArguments;

    /**
     * Page will not be cached. Write only TRUE. Never clear value (some other
     * code might have reasons to set it TRUE).
     * @var bool
     */
    public $no_cache = false;

    /**
     * The rootLine (all the way to tree root, not only the current site!)
     */
    public array $rootLine = [];

    /**
     * The pagerecord
     * @var array
     */
    public $page = [];

    /**
     * This will normally point to the same value as id, but can be changed to
     * point to another page from which content will then be displayed instead.
     */
    public int $contentPid = 0;

    /**
     * Gets set when we are processing a page of type mountpoint with enabled overlay in getPageAndRootline()
     * Used later in checkPageForMountpointRedirect() to determine the final target URL where the user
     * should be redirected to.
     */
    protected ?array $originalMountPointPage = null;

    /**
     * Gets set when we are processing a page of type shortcut in the early stages
     * of the request, used later in the request to resolve the shortcut and redirect again.
     */
    protected ?array $originalShortcutPage = null;

    /**
     * sys_page-object, pagefunctions
     *
     * @var PageRepository|string
     */
    public $sys_page = '';

    /**
     * Is set to 1 if a pageNotFound handler could have been called.
     * @var int
     * @internal
     */
    public $pageNotFound = 0;

    /**
     * Array containing a history of why a requested page was not accessible.
     */
    protected array $pageAccessFailureHistory = [];

    /**
     * @var string
     * @internal
     */
    public $MP = '';

    /**
     * The frontend user
     *
     * @var FrontendUserAuthentication
     */
    public $fe_user;

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
    public $tmpl;

    /**
     * Is set to the time-to-live time of cached pages. Default is 60*60*24, which is 24 hours.
     *
     * @var int
     * @internal
     */
    protected int $cacheTimeOutDefault = 0;

    /**
     * Set if cached content was fetched from the cache.
     */
    protected bool $pageContentWasLoadedFromCache = false;

    /**
     * Set to the expire time of cached content
     * @internal
     */
    protected int $cacheExpires = 0;

    /**
     * Used by template fetching system. This array is an identification of
     * the template. If $this->all is empty it's because the template-data is not
     * cached, which it must be.
     * @var array
     * @internal
     */
    public $all = [];

    /**
     * Toplevel - objArrayName, eg 'page'
     * @var string
     * @internal should only be used by TYPO3 Core
     */
    public $sPre = '';

    /**
     * TypoScript configuration of the page-object pointed to by sPre.
     * $this->tmpl->setup[$this->sPre.'.']
     * @var array|string
     * @internal should only be used by TYPO3 Core
     */
    public $pSetup = '';

    /**
     * This hash is unique to the template, the $this->id and $this->type vars and
     * the list of groups. Used to get and later store the cached data
     * @internal
     */
    public string $newHash = '';

    /**
     * This flag is set before the page is generated IF $this->no_cache is set. If this
     * flag is set after the page content was generated, $this->no_cache is forced to be set.
     * This is done in order to make sure that PHP code from Plugins / USER scripts does not falsely
     * clear the no_cache flag.
     * @internal
     */
    protected bool $no_cacheBeforePageGen = false;

    /**
     * May be set to the pagesTSconfig
     * @internal
     */
    protected ?array $pagesTSconfig = null;

    /**
     * Eg. insert JS-functions in this array ($additionalHeaderData) to include them
     * once. Use associative keys.
     *
     * Keys in use:
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
     * If set, typolink() function encrypts email addresses.
     */
    public int $spamProtectEmailAddresses = 0;

    /**
     * Absolute Reference prefix
     * @var string
     */
    public $absRefPrefix = '';

    /**
     * A string prepared for insertion in all links on the page as url-parameters.
     * Based on configuration in TypoScript where you defined which GET parameters you
     * would like to pass on.
     */
    public string $linkVars = '';

    /**
     * 'Global' Storage for various applications. Keys should be 'tx_'.extKey for
     * extensions.
     */
    public array $applicationData = [];

    public array $register = [];

    /**
     * Stack used for storing array and retrieving register arrays (see
     * LOAD_REGISTER and RESTORE_REGISTER)
     */
    public array $registerStack = [];

    /**
     * Used by RecordContentObject and ContentContentObject to ensure the a records is NOT
     * rendered twice through it!
     */
    public array $recordRegister = [];

    /**
     * This is set to the [table]:[uid] of the latest record rendered. Note that
     * class ContentObjectRenderer has an equal value, but that is pointing to the
     * record delivered in the $data-array of the ContentObjectRenderer instance, if
     * the cObjects CONTENT or RECORD created that instance
     */
    public string $currentRecord = '';

    /**
     * Used to generate page-unique keys. Point is that uniqid() functions is very
     * slow, so a unique key is made based on this, see function uniqueHash()
     * @internal
     */
    protected int $uniqueCounter = 0;

    /**
     * @internal
     */
    protected string $uniqueString = '';

    /**
     * This value will be used as the title for the page in the indexer (if
     * indexing happens)
     * @internal only used by TYPO3 Core, use PageTitle API instead.
     */
    public string $indexedDocTitle = '';

    /**
     * The base URL set for the page header.
     * @var string
     */
    public $baseUrl = '';

    /**
     * Page content render object
     *
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * All page content is accumulated in this variable. See RequestHandler
     * @var string
     */
    public $content = '';

    /**
     * Internal calculations for labels
     */
    protected ?LanguageService $languageService = null;

    /**
     * @var LockingStrategyInterface[][]
     */
    protected array $locks = [];
    protected ?PageRenderer $pageRenderer = null;

    /**
     * The page cache object, use this to save pages to the cache and to
     * retrieve them again
     *
     * @var FrontendInterface
     */
    protected $pageCache;

    protected array $pageCacheTags = [];

    /**
     * Content type HTTP header being sent in the request.
     * @todo Ticket: #63642 Should be refactored to a request/response model later
     * @internal Should only be used by TYPO3 core for now
     */
    protected string $contentType = 'text/html';

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
     * Originally requested id from PageArguments
     */
    protected int $requestedId = 0;

    /**
     * The context for keeping the current state, mostly related to current page information,
     * backend user / frontend user access, workspaceId
     */
    protected Context $context;

    /**
     * If debug mode is enabled, this contains the information if a page is fetched from cache,
     * and sent as HTTP Response Header.
     */
    protected string $debugInformationHeader = '';

    /**
     * Since TYPO3 v10.0, TSFE is composed out of
     *  - Context
     *  - Site
     *  - SiteLanguage
     *  - PageArguments (containing ID, Type, cHash and MP arguments)
     *
     * Also sets a unique string (->uniqueString) for this script instance; A md5 hash of the microtime()
     *
     * @param Context $context the Context object to work with
     * @param Site $site The resolved site to work with
     * @param SiteLanguage $siteLanguage The resolved language to work with
     * @param PageArguments $pageArguments The PageArguments object containing Page ID, type and GET parameters
     * @param FrontendUserAuthentication $frontendUser a FrontendUserAuthentication object
     */
    public function __construct(Context $context, Site $site, SiteLanguage $siteLanguage, PageArguments $pageArguments, FrontendUserAuthentication $frontendUser)
    {
        $this->initializeContext($context);
        $this->site = $site;
        $this->language = $siteLanguage;
        $this->setPageArguments($pageArguments);
        $this->fe_user = $frontendUser;
        $this->uniqueString = md5(microtime());
        $this->initPageRenderer();
        $this->initCaches();
        // Initialize LLL behaviour
        $this->setOutputLanguage();
    }

    private function initializeContext(Context $context): void
    {
        $this->context = $context;
        if (!$this->context->hasAspect('frontend.preview')) {
            $this->context->setAspect('frontend.preview', GeneralUtility::makeInstance(PreviewAspect::class));
        }
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
        // As initPageRenderer could be called in constructor and for USER_INTs, this information is only set
        // once - in order to not override any previous settings of PageRenderer.
        $this->pageRenderer->setLanguage($this->language->getTypo3Language());
    }

    /**
     * @param string $contentType
     * @internal Should only be used by TYPO3 core for now
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
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
        $this->pageCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('pages');
    }

    /**
     * Initializes the front-end user groups.
     * Sets frontend.user aspect based on front-end user status.
     * @deprecated will be removed in TYPO3 v13.0. Use the Context API directly.
     */
    public function initUserGroups()
    {
        trigger_error('TSFE->initUserGroups() will be removed in TYPO3 v13.0. Use the Context API directly.', E_USER_DEPRECATED);
        $this->context->setAspect('frontend.user', $this->fe_user->createUserAspect());
    }

    /**
     * Checking if a user is logged in or a group constellation different from "0,-1"
     *
     * @return bool TRUE if either a login user is found (array fe_user->user) OR if the gr_list is set to something else than '0,-1' (could be done even without a user being logged in!)
     * @deprecated will be removed in TYPO3 v13.0. Use the Context API directly.
     */
    public function isUserOrGroupSet()
    {
        trigger_error('TSFE->isUserOrGroupSet() will be removed in TYPO3 v13.0. Use the Context API directly.', E_USER_DEPRECATED);
        /** @var UserAspect $userAspect */
        $userAspect = $this->context->getAspect('frontend.user');
        return $userAspect->isUserOrGroupSet();
    }

    /**
     * Checks if a backend user is logged in
     *
     * @return bool whether a backend user is logged in
     * @deprecated will be removed in TYPO3 v13.0. Use the Context API directly.
     */
    public function isBackendUserLoggedIn()
    {
        trigger_error('TSFE->isBackendUserLoggedIn() will be removed in TYPO3 v13.0. Use the Context API directly.', E_USER_DEPRECATED);
        return (bool)$this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
    }

    /**
     * Resolves the page id and sets up several related properties.
     *
     * At this point, the Context object already contains relevant preview
     * settings (if a backend user is logged in etc).
     *
     * If $this->id is not set at all, the method does its best to set the
     * value to an integer. Resolving is based on this options:
     *
     * - Finding the domain record start page
     * - First visible page
     * - Relocating the id below the site if outside the site / domain
     *
     * The following properties may be set up or updated:
     *
     * - id
     * - sys_page
     * - sys_page->where_groupAccess
     * - sys_page->where_hid_del
     * - register['SYS_LASTCHANGED']
     * - pageNotFound
     *
     * Via getPageAndRootline()
     *
     * - rootLine
     * - page
     * - MP
     * - originalShortcutPage
     * - originalMountPointPage
     * - pageAccessFailureHistory['direct_access']
     * - pageNotFound
     *
     * @todo:
     *
     * On the first impression the method does too much.
     * The reasons are manifold.
     *
     * 1.) The workflow of the resolution could be elaborated to be less
     * tangled. Maybe the check of the page id to be below the domain via the
     * root line doesn't need to be done each time, but for the final result
     * only.
     *
     * 2.) The root line does not need to be directly addressed by this class.
     * A root line is always related to one page. The rootline could be handled
     * indirectly by page objects. Page objects still don't exist.
     *
     * @param ServerRequestInterface $request
     */
    public function determineId(ServerRequestInterface $request): void
    {
        // Call pre processing function for id determination
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PreProcessing'] ?? [] as $functionReference) {
            $parameters = ['parentObject' => $this];
            GeneralUtility::callUserFunction($functionReference, $parameters, $this);
        }
        $timeTracker = $this->getTimeTracker();

        $this->sys_page = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        $timeTracker->push('determineId rootLine/');
        try {
            // Sets ->page and ->rootline information based on ->id. ->id may change during this operation.
            // If the found Page ID is not within the site, then pageNotFound is set.
            $this->getPageAndRootline($request);
            // Checks if the rootPageId of the site is in the resolved rootLine.
            // This is necessary so that references to page-id's via ?id=123 from other sites are not possible.
            $siteRootWithinRootlineFound = false;
            foreach ($this->rootLine as $pageInRootLine) {
                if ((int)$pageInRootLine['uid'] === $this->site->getRootPageId()) {
                    $siteRootWithinRootlineFound = true;
                    break;
                }
            }
            // Page is 'not found' in case the id was outside the domain, code 3
            // This can only happen if there was a shortcut. So $this->page is now the shortcut target
            // But the original page is in $this->originalShortcutPage.
            // This only happens if people actually call TYPO3 with index.php?id=123 where 123 is in a different
            // page tree. This is not allowed.
            $directlyRequestedId = (int)($request->getQueryParams()['id'] ?? 0);
            if (!$siteRootWithinRootlineFound && $directlyRequestedId && (int)($this->originalShortcutPage['uid'] ?? 0) !== $directlyRequestedId) {
                $this->pageNotFound = 3;
                $this->id = $this->site->getRootPageId();
                // re-get the page and rootline if the id was not found.
                $this->getPageAndRootline($request);
            }
        } catch (ShortcutTargetPageNotFoundException $e) {
            $this->pageNotFound = 1;
        }
        $timeTracker->pull();
        if ($this->pageNotFound) {
            switch ($this->pageNotFound) {
                case 1:
                    $response = GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                        $request,
                        'ID was not an accessible page',
                        $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED)
                    );
                    break;
                case 2:
                    $response = GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                        $request,
                        'Subsection was found and not accessible',
                        $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED)
                    );
                    break;
                case 3:
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'ID was outside the domain',
                        $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_HOST_PAGE_MISMATCH)
                    );
                    break;
                default:
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'Unspecified error',
                        $this->getPageAccessFailureReasons()
                    );
            }
            throw new PropagateResponseException($response, 1533931329);
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['fetchPageId-PostProcessing'] ?? [] as $functionReference) {
            $parameters = ['parentObject' => $this];
            GeneralUtility::callUserFunction($functionReference, $parameters, $this);
        }

        // Setting language and fetch translated page
        $this->settingLanguage($request);
        // Check the "content_from_pid" field of the resolved page
        $this->contentPid = $this->resolveContentPid($request);

        // Update SYS_LASTCHANGED at the time, when $this->page might be changed by settingLanguage() and the $this->page was finally resolved
        $this->setRegisterValueForSysLastChanged($this->page);

        // Call post processing function for id determination:
        $_params = ['pObj' => &$this];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['determineId-PostProc'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
    }

    /**
     * Loads the page and root line records based on $this->id
     *
     * A final page and the matching root line are determined and loaded by
     * the algorithm defined by this method.
     *
     * First it loads the initial page from the page repository for $this->id.
     * If that can't be loaded directly, it gets the root line for $this->id.
     * It walks up the root line towards the root page until the page
     * repository can deliver a page record. (The loading restrictions of
     * the root line records are more liberal than that of the page record.)
     *
     * Now the page type is evaluated and handled if necessary. If the page is
     * a short cut, it is replaced by the target page. If the page is a mount
     * point in overlay mode, the page is replaced by the mounted page.
     *
     * After this potential replacements are done, the root line is loaded
     * (again) for this page record. It walks up the root line up to
     * the first viewable record.
     *
     * (While upon the first accessibility check of the root line it was done
     * by loading page by page from the page repository, this time the method
     * checkRootlineForIncludeSection() is used to find the most distant
     * accessible page within the root line.)
     *
     * Having found the final page id, the page record and the root line are
     * loaded for last time by this method.
     *
     * Exceptions may be thrown for DOKTYPE_SPACER and not loadable page records
     * or root lines.
     *
     * May set or update these properties:
     *
     * @see TypoScriptFrontendController::$id
     * @see TypoScriptFrontendController::$MP
     * @see TypoScriptFrontendController::$page
     * @see TypoScriptFrontendController::$pageNotFound
     * @see TypoScriptFrontendController::$pageAccessFailureHistory
     * @see TypoScriptFrontendController::$originalMountPointPage
     * @see TypoScriptFrontendController::$originalShortcutPage
     *
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     * @throws PageNotFoundException
     * @throws ShortcutTargetPageNotFoundException
     */
    protected function getPageAndRootline(ServerRequestInterface $request)
    {
        $requestedPageRowWithoutGroupCheck = [];
        $this->page = $this->sys_page->getPage($this->id);
        if (empty($this->page)) {
            // If no page, we try to find the page above in the rootLine.
            // Page is 'not found' in case the id itself was not an accessible page. code 1
            $this->pageNotFound = 1;
            $requestedPageIsHidden = false;
            try {
                $hiddenField = $GLOBALS['TCA']['pages']['ctrl']['enablecolumns']['disabled'] ?? '';
                $includeHiddenPages = $this->context->getPropertyFromAspect('visibility', 'includeHiddenPages') || $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
                if (!empty($hiddenField) && !$includeHiddenPages) {
                    // Page is "hidden" => 404 (deliberately done in default language, as this cascades to language overlays)
                    $rawPageRecord = $this->sys_page->getPage_noCheck($this->id);
                    $requestedPageIsHidden = (bool)$rawPageRecord[$hiddenField];
                }

                $requestedPageRowWithoutGroupCheck = $this->sys_page->getPage($this->id, true);
                if (!empty($requestedPageRowWithoutGroupCheck)) {
                    $this->pageAccessFailureHistory['direct_access'][] = $requestedPageRowWithoutGroupCheck;
                }
                $this->rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $this->id, $this->MP, $this->context)->get();
                if (!empty($this->rootLine)) {
                    $c = count($this->rootLine) - 1;
                    while ($c > 0) {
                        // Add to page access failure history:
                        $this->pageAccessFailureHistory['direct_access'][] = $this->rootLine[$c];
                        // Decrease to next page in rootline and check the access to that, if OK, set as page record and ID value.
                        $c--;
                        $this->id = (int)$this->rootLine[$c]['uid'];
                        $this->page = $this->sys_page->getPage($this->id);
                        if (!empty($this->page)) {
                            break;
                        }
                    }
                }
            } catch (RootLineException $e) {
                $this->rootLine = [];
            }
            // If still no page...
            if ($requestedPageIsHidden || (empty($requestedPageRowWithoutGroupCheck) && empty($this->page))) {
                $message = 'The requested page does not exist!';
                try {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        $message,
                        $this->getPageAccessFailureReasons(PageAccessFailureReasons::PAGE_NOT_FOUND)
                    );
                    throw new PropagateResponseException($response, 1533931330);
                } catch (PageNotFoundException $e) {
                    throw new PageNotFoundException($message, 1301648780);
                }
            }
        }
        // Spacer and sysfolders is not accessible in frontend
        $isSpacerOrSysfolder = $this->page['doktype'] == PageRepository::DOKTYPE_SPACER || $this->page['doktype'] == PageRepository::DOKTYPE_SYSFOLDER;
        // Page itself is not accessible, but the parent page is a spacer/sysfolder
        if ($isSpacerOrSysfolder && !empty($requestedPageRowWithoutGroupCheck)) {
            try {
                $response = GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                    $request,
                    'Subsection was found and not accessible',
                    $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED)
                );
                throw new PropagateResponseException($response, 1633171038);
            } catch (PageNotFoundException $e) {
                throw new PageNotFoundException('Subsection was found and not accessible', 1633171172);
            }
        }

        if ($isSpacerOrSysfolder) {
            $message = 'The requested page does not exist!';
            try {
                $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    $message,
                    $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_INVALID_PAGETYPE)
                );
                throw new PropagateResponseException($response, 1533931343);
            } catch (PageNotFoundException $e) {
                throw new PageNotFoundException($message, 1301648781);
            }
        }
        // Is the ID a link to another page??
        if ($this->page['doktype'] == PageRepository::DOKTYPE_SHORTCUT) {
            // We need to clear MP if the page is a shortcut. Reason is if the shortcut goes to another page, then we LEAVE the rootline which the MP expects.
            $this->MP = '';
            // saving the page so that we can check later - when we know
            // about languages - whether we took the correct shortcut or
            // whether a translation of the page overwrites the shortcut
            // target and we need to follow the new target
            $this->originalShortcutPage = $this->page;
            $this->page = $this->sys_page->resolveShortcutPage($this->page, true);
            $this->id = (int)$this->page['uid'];
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
            // If the current page is a shortcut, the MP parameter will be replaced
            if ($this->MP === '' || !empty($this->originalShortcutPage)) {
                $this->MP = $this->page['uid'] . '-' . $this->originalMountPointPage['uid'];
            } else {
                $this->MP .= ',' . $this->page['uid'] . '-' . $this->originalMountPointPage['uid'];
            }
            $this->id = (int)$this->page['uid'];
        }
        // Gets the rootLine
        try {
            $this->rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $this->id, $this->MP, $this->context)->get();
        } catch (RootLineException $e) {
            $this->rootLine = [];
        }
        // If not rootline we're off...
        if (empty($this->rootLine)) {
            $message = 'The requested page didn\'t have a proper connection to the tree-root!';
            $this->logPageAccessFailure($message, $request);
            try {
                $response = GeneralUtility::makeInstance(ErrorController::class)->internalErrorAction(
                    $request,
                    $message,
                    $this->getPageAccessFailureReasons(PageAccessFailureReasons::ROOTLINE_BROKEN)
                );
                throw new PropagateResponseException($response, 1533931350);
            } catch (AbstractServerErrorException $e) {
                $this->logger->error($message, ['exception' => $e]);
                $exceptionClass = get_class($e);
                throw new $exceptionClass($message, 1301648167);
            }
        }
        // Checking for include section regarding the hidden/starttime/endtime/fe_user (that is access control of a whole subbranch!)
        if ($this->checkRootlineForIncludeSection()) {
            if (empty($this->rootLine)) {
                $message = 'The requested page does not exist!';
                try {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        $message,
                        $this->getPageAccessFailureReasons(PageAccessFailureReasons::PAGE_NOT_FOUND)
                    );
                    throw new PropagateResponseException($response, 1533931351);
                } catch (AbstractServerErrorException $e) {
                    $this->logger->warning($message);
                    $exceptionClass = get_class($e);
                    throw new $exceptionClass($message, 1301648234);
                }
            } else {
                $el = reset($this->rootLine);
                $this->id = (int)$el['uid'];
                $this->page = $this->sys_page->getPage($this->id);
                try {
                    $this->rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $this->id, $this->MP, $this->context)->get();
                } catch (RootLineException $e) {
                    $this->rootLine = [];
                }
            }
        }
    }

    /**
     * Checks if visibility of the page is blocked upwards in the root line.
     *
     * If any page in the root line is blocking visibility, true is returned.
     *
     * All pages from the blocking page downwards are removed from the root
     * line, so that the remaining pages can be used to relocate the page up
     * to lowest visible page.
     *
     * The blocking feature of a page must be turned on by setting the page
     * record field 'extendToSubpages' to 1 in case of hidden, starttime,
     * endtime or fe_group restrictions.
     *
     * Additionally, this method checks for backend user sections in root line
     * and if found, evaluates if a backend user is logged in and has access.
     *
     * Recyclers are also checked and trigger page not found if found in root
     * line.
     *
     * @todo Find a better name, i.e. checkVisibilityByRootLine
     * @todo Invert boolean return value. Return true if visible.
     */
    protected function checkRootlineForIncludeSection(): bool
    {
        $c = count($this->rootLine);
        $removeTheRestFlag = false;
        $accessVoter = GeneralUtility::makeInstance(RecordAccessVoter::class);
        for ($a = 0; $a < $c; $a++) {
            if (!$accessVoter->accessGrantedForPageInRootLine($this->rootLine[$a], $this->context)) {
                // Add to page access failure history and mark the page as not found
                // Keep the rootline however to trigger an access denied error instead of a service unavailable error
                $this->pageAccessFailureHistory['sub_section'][] = $this->rootLine[$a];
                $this->pageNotFound = 2;
            }

            if ((int)$this->rootLine[$a]['doktype'] === PageRepository::DOKTYPE_BE_USER_SECTION) {
                // If there is a backend user logged in, check if they have read access to the page:
                if ($this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)) {
                    // If there was no page selected, the user apparently did not have read access to the
                    // current page (not position in rootline) and we set the remove-flag...
                    if (!$this->getBackendUser()->doesUserHaveAccess($this->page, Permission::PAGE_SHOW)) {
                        $removeTheRestFlag = true;
                    }
                } else {
                    // Don't go here, if there is no backend user logged in.
                    $removeTheRestFlag = true;
                }
            } elseif ((int)$this->rootLine[$a]['doktype'] === PageRepository::DOKTYPE_RECYCLER) {
                // page is in a recycler
                $removeTheRestFlag = true;
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
     * Takes notice of the includeHiddenPages visibility aspect flag and uses SIM_ACCESS_TIME for start/endtime evaluation
     *
     * @param array $row The page record to evaluate (needs fields: hidden, starttime, endtime, fe_group)
     * @param bool $bypassGroupCheck Bypass group-check
     * @return bool TRUE, if record is viewable.
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Use RecordAccessVoter instead.
     */
    public function checkEnableFields($row, $bypassGroupCheck = false)
    {
        trigger_error(
            'Method ' . __METHOD__ . ' has been deprecated in v12 and will be removed with v13. Use RecordAccessVoter instead.',
            E_USER_DEPRECATED
        );
        return GeneralUtility::makeInstance(RecordAccessVoter::class)->accessGranted('pages', $row, $this->context);
    }

    /**
     * Analysing $this->pageAccessFailureHistory into a summary array telling which features disabled display and on which pages and conditions. That data can be used inside a page-not-found handler
     *
     * @param string|null $failureReasonCode the error code to be attached (optional), see PageAccessFailureReasons list for details
     * @return array Summary of why page access was not allowed.
     */
    public function getPageAccessFailureReasons(string $failureReasonCode = null)
    {
        $output = [];
        if ($failureReasonCode) {
            $output['code'] = $failureReasonCode;
        }
        $combinedRecords = array_merge(
            is_array($this->pageAccessFailureHistory['direct_access'] ?? false) ? $this->pageAccessFailureHistory['direct_access'] : [['fe_group' => 0]],
            is_array($this->pageAccessFailureHistory['sub_section'] ?? false) ? $this->pageAccessFailureHistory['sub_section'] : []
        );
        if (!empty($combinedRecords)) {
            $accessVoter = GeneralUtility::makeInstance(RecordAccessVoter::class);
            foreach ($combinedRecords as $k => $pagerec) {
                // If $k=0 then it is the very first page the original ID was pointing at and that will get a full check of course
                // If $k>0 it is parent pages being tested. They are only significant for the access to the first page IF they had the extendToSubpages flag set, hence checked only then!
                if (!$k || $pagerec['extendToSubpages']) {
                    if ($pagerec['hidden'] ?? false) {
                        $output['hidden'][$pagerec['uid']] = true;
                    }
                    if (isset($pagerec['starttime']) && $pagerec['starttime'] > $GLOBALS['SIM_ACCESS_TIME']) {
                        $output['starttime'][$pagerec['uid']] = $pagerec['starttime'];
                    }
                    if (isset($pagerec['endtime']) && $pagerec['endtime'] != 0 && $pagerec['endtime'] <= $GLOBALS['SIM_ACCESS_TIME']) {
                        $output['endtime'][$pagerec['uid']] = $pagerec['endtime'];
                    }
                    if (!$accessVoter->groupAccessGranted('pages', $pagerec, $this->context)) {
                        $output['fe_group'][$pagerec['uid']] = $pagerec['fe_group'];
                    }
                }
            }
        }
        return $output;
    }

    /********************************************
     *
     * Template and caching related functions.
     *
     *******************************************/

    protected function setPageArguments(PageArguments $pageArguments): void
    {
        $this->pageArguments = $pageArguments;
        $this->id = $pageArguments->getPageId();
        // We store the originally requested id
        $this->requestedId = $this->id;
        $this->type = (int)($pageArguments->getPageType() ?: 0);
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['enable_mount_pids']) {
            $this->MP = (string)($pageArguments->getArguments()['MP'] ?? '');
            // Ensure no additional arguments are given via the &MP=123-345,908-172 (e.g. "/")
            $this->MP = preg_replace('/[^0-9,-]/', '', $this->MP);
        }
    }

    /**
     * Fetches the arguments that are relevant for creating the hash base from the given PageArguments object.
     * Excluded parameters are not taken into account when calculating the hash base.
     *
     * @param PageArguments $pageArguments
     * @return array
     */
    protected function getRelevantParametersForCachingFromPageArguments(PageArguments $pageArguments): array
    {
        $queryParams = $pageArguments->getDynamicArguments();
        if (!empty($queryParams) && ($pageArguments->getArguments()['cHash'] ?? false)) {
            $queryParams['id'] = $pageArguments->getPageId();
            return GeneralUtility::makeInstance(CacheHashCalculator::class)
                ->getRelevantParameters(HttpUtility::buildQueryString($queryParams));
        }
        return [];
    }

    /**
     * See if page is in cache and get it if so, populates the page content to $this->content.
     * Also fetches the raw cached pagesection information (TypoScript information) before.
     *
     * @param ServerRequestInterface $request
     */
    public function getFromCache(ServerRequestInterface $request)
    {
        // clearing the content-variable, which will hold the pagecontent
        $this->content = '';
        // Unsetting the lowlevel config
        $this->config = [];
        $this->pageContentWasLoadedFromCache = false;

        if ($this->no_cache) {
            return;
        }

        if (!$this->tmpl instanceof TemplateService) {
            $this->tmpl = GeneralUtility::makeInstance(TemplateService::class, $this->context, null, $this);
        }

        $pageSectionCacheContent = $this->tmpl->getCurrentPageData($this->id, (string)$this->MP);
        if (!is_array($pageSectionCacheContent)) {
            // Nothing in the cache, we acquire an "exclusive lock" for the key now.
            // We use the Registry to store this lock centrally,
            // but we protect the access again with a global exclusive lock to avoid race conditions

            $this->acquireLock('pagesection', $this->id . '::' . $this->MP);
            //
            // from this point on we're the only one working on that page ($key)
            //

            // query the cache again to see if the page data are there meanwhile
            $pageSectionCacheContent = $this->tmpl->getCurrentPageData($this->id, (string)$this->MP);
            if (is_array($pageSectionCacheContent)) {
                // we have the content, nice that some other process did the work for us already
                $this->releaseLock('pagesection');
            }
            // We keep the lock set, because we are the ones generating the page now and filling the cache.
            // This indicates that we have to release the lock later in releaseLocks()
        }

        if (is_array($pageSectionCacheContent)) {
            // BE CAREFUL to change the content of the cc-array. This array is serialized and an md5-hash based on this is used for caching the page.
            // If this hash is not the same in here in this section and after page-generation, then the page will not be properly cached!
            // This array is an identification of the template. If $this->all is empty it's because the template-data is not cached, which it must be.
            $pageSectionCacheContent = $this->tmpl->matching($pageSectionCacheContent);
            ksort($pageSectionCacheContent);
            $this->all = $pageSectionCacheContent;
        }

        // Look for page in cache only if a shift-reload is not sent to the server.
        $lockHash = $this->getLockHash();
        if ($this->shouldAcquireCacheData($request) && $this->all) {
            // we got page section information (TypoScript), so lets see if there is also a cached version
            // of this page in the pages cache.
            $this->newHash = $this->getHash();
            $this->getTimeTracker()->push('Cache Row');
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
                // We keep the lock set, because we are the ones generating the page now and filling the cache.
                // This indicates that we have to release the lock later in releaseLocks()
            }
            if (is_array($row)) {
                $this->populatePageDataFromCache($row);
            }
            $this->getTimeTracker()->pull();
        } else {
            // the user forced rebuilding the page cache or there was no pagesection information
            // get a lock for the page content so other processes will not interrupt the regeneration
            $this->acquireLock('pages', $lockHash);
        }
    }

    /**
     * Returning the cached version of page with hash = newHash
     *
     * @return array Cached row, if any. Otherwise void.
     */
    public function getFromCache_queryRow()
    {
        $this->getTimeTracker()->push('Cache Query');
        $row = $this->pageCache->get($this->newHash);
        $this->getTimeTracker()->pull();
        return $row;
    }

    /**
     * This method properly sets the values given from the pages cache into the corresponding
     * TSFE variables. The counterpart is setPageCacheContent() where all relevant information is fetched.
     * This also contains all data that could be cached, even for pages that are partially cached, as they
     * have non-cacheable content still to be rendered.
     *
     * @see getFromCache()
     * @see setPageCacheContent()
     * @param array $cachedData
     */
    protected function populatePageDataFromCache(array $cachedData): void
    {
        // Call hook when a page is retrieved from cache
        $_params = ['pObj' => &$this, 'cache_pages_row' => &$cachedData];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageLoadedFromCache'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
        // Fetches the lowlevel config stored with the cached data
        $this->config = $cachedData['cache_data'];
        // Getting the content
        $this->content = $cachedData['content'];
        // Setting flag, so we know, that some cached content has been loaded
        $this->pageContentWasLoadedFromCache = true;
        $this->cacheExpires = $cachedData['expires'];
        // Restore the current tags as they can be retrieved by getPageCacheTags()
        $this->pageCacheTags = $cachedData['cacheTags'] ?? [];

        // Restore page title information, this is needed to generate the page title for
        // partially cached pages.
        $this->page['title'] = $cachedData['pageTitleInfo']['title'];
        $this->indexedDocTitle = $cachedData['pageTitleInfo']['indexedDocTitle'];

        if (isset($this->config['config']['debug'])) {
            $debugCacheTime = (bool)$this->config['config']['debug'];
        } else {
            $debugCacheTime = !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
        }
        if ($debugCacheTime) {
            $this->prepareDebugInformationForCachedPage($cachedData);
        }
    }

    protected function prepareDebugInformationForCachedPage(array $cachedData): void
    {
        $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
        $timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
        $this->debugInformationHeader = 'Cached page generated ' . date($dateFormat . ' ' . $timeFormat, $cachedData['tstamp']) . '. Expires ' . date($dateFormat . ' ' . $timeFormat, $cachedData['expires']);
    }

    /**
     * Detecting if shift-reload has been clicked.
     * This option will have no effect if re-generation of page happens by other reasons (for instance that the page is not in cache yet).
     * Also, a backend user MUST be logged in for the shift-reload to be detected due to DoS-attack-security reasons.
     *
     * @return bool If shift-reload in client browser has been clicked, disable getting cached page and regenerate the page content.
     */
    protected function shouldAcquireCacheData(ServerRequestInterface $request): bool
    {
        $shouldUseCachedPageData = true;
        if ($this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)
            && (strtolower($request->getServerParams()['HTTP_CACHE_CONTROL'] ?? '') === 'no-cache'
                || strtolower($request->getServerParams()['HTTP_PRAGMA'] ?? '') === 'no-cache')) {
            $shouldUseCachedPageData = false;
        }

        // Trigger event for possible by-pass of requiring of page cache (for re-caching purposes)
        $event = new ShouldUseCachedPageDataIfAvailableEvent($request, $this, $shouldUseCachedPageData);
        GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
        return $event->shouldUseCachedPageData();
    }

    /**
     * Calculates the cache-hash
     * This hash is unique to the template, the variables ->id, ->type, list of fe user groups, ->MP (Mount Points) and cHash array
     * Used to get and later store the cached data.
     *
     * @return string MD5 hash of serialized hash base from createHashBase(), prefixed with page id
     * @see getFromCache()
     * @see getLockHash()
     */
    protected function getHash(): string
    {
        return $this->id . '_' . md5($this->createHashBase(false));
    }

    /**
     * Calculates the lock-hash
     * This hash is unique to the above hash, except that it doesn't contain the template information in $this->all.
     *
     * @return string MD5 hash prefixed with page id
     * @see getFromCache()
     * @see getHash()
     */
    protected function getLockHash(): string
    {
        $lockHash = $this->createHashBase(true);
        return $this->id . '_' . md5($lockHash);
    }

    /**
     * Calculates the cache-hash (or the lock-hash)
     * This hash is unique to the template,
     * the variables ->id, ->type, list of frontend user groups,
     * ->MP (Mount Points) and cHash array
     * Used to get and later store the cached data.
     *
     * @param bool $createLockHashBase Whether to create the lock hash, which doesn't contain the "this->all" (the template information)
     * @return string the serialized hash base
     */
    protected function createHashBase($createLockHashBase = false)
    {
        // Fetch the list of user groups
        /** @var UserAspect $userAspect */
        $userAspect = $this->context->getAspect('frontend.user');
        $hashParameters = [
            'id' => $this->id,
            'type' => $this->type,
            'groupIds' => (string)implode(',', $userAspect->getGroupIds()),
            'MP' => (string)$this->MP,
            'site' => $this->site->getIdentifier(),
            // Ensure the language base is used for the hash base calculation as well, otherwise TypoScript and page-related rendering
            // is not cached properly as we don't have any language-specific conditions anymore
            'siteBase' => (string)$this->language->getBase(),
            // additional variation trigger for static routes
            'staticRouteArguments' => $this->pageArguments->getStaticArguments(),
            // dynamic route arguments (if route was resolved)
            'dynamicArguments' => $this->getRelevantParametersForCachingFromPageArguments($this->pageArguments),
        ];
        // Include the template information if we shouldn't create a lock hash
        if (!$createLockHashBase) {
            $hashParameters['all'] = $this->all;
        }
        // Call hook to influence the hash calculation
        $_params = [
            'hashParameters' => &$hashParameters,
            'createLockHashBase' => $createLockHashBase,
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
        return serialize($hashParameters);
    }

    /**
     * Checks if config-array exists already but if not, gets it
     *
     * @param ServerRequestInterface $request
     * @throws \TYPO3\CMS\Core\Error\Http\InternalServerErrorException
     * @throws \TYPO3\CMS\Core\Error\Http\ServiceUnavailableException
     */
    public function getConfigArray(ServerRequestInterface $request): void
    {
        if (!$this->tmpl instanceof TemplateService) {
            $this->tmpl = GeneralUtility::makeInstance(TemplateService::class, $this->context, null, $this);
        }

        // If config is not set by the cache (which would be a major mistake somewhere) OR if INTincScripts-include-scripts have been registered, then we must parse the template in order to get it
        if (empty($this->config) || $this->isINTincScript() || $this->context->getPropertyFromAspect('typoscript', 'forcedTemplateParsing')) {
            $timeTracker = $this->getTimeTracker();
            $timeTracker->push('Parse template');
            // Start parsing the TS template. Might return cached version.
            $this->tmpl->start($this->rootLine);
            $timeTracker->pull();
            // At this point we have a valid pagesection_cache (generated in $this->tmpl->start()),
            // so let all other processes proceed now. (They are blocked at the pagessection_lock in getFromCache())
            $this->releaseLock('pagesection');
            if ($this->tmpl->loaded) {
                $timeTracker->push('Setting the config-array');
                // toplevel - objArrayName
                $typoScriptPageTypeName = $this->tmpl->setup['types.'][$this->type] ?? '';
                $this->sPre = $typoScriptPageTypeName;
                $this->pSetup = $this->tmpl->setup[$typoScriptPageTypeName . '.'] ?? '';
                if (!is_array($this->pSetup)) {
                    $this->logger->alert('The page is not configured! [type={type}][{type_name}].', ['type' => $this->type, 'type_name' => $typoScriptPageTypeName]);
                    try {
                        $message = 'The page is not configured! [type=' . $this->type . '][' . $typoScriptPageTypeName . '].';
                        $response = GeneralUtility::makeInstance(ErrorController::class)->internalErrorAction(
                            $request,
                            $message,
                            ['code' => PageAccessFailureReasons::RENDERING_INSTRUCTIONS_NOT_CONFIGURED]
                        );
                        throw new PropagateResponseException($response, 1533931374);
                    } catch (AbstractServerErrorException $e) {
                        $explanation = 'This means that there is no TypoScript object of type PAGE with typeNum=' . $this->type . ' configured.';
                        $exceptionClass = get_class($e);
                        throw new $exceptionClass($message . ' ' . $explanation, 1294587217);
                    }
                } else {
                    if (!isset($this->config['config'])) {
                        $this->config['config'] = [];
                    }
                    // Filling the config-array, first with the main "config." part
                    if (is_array($this->tmpl->setup['config.'] ?? null)) {
                        $this->tmpl->setup['config.'] = array_replace_recursive($this->tmpl->setup['config.'], $this->config['config']);
                        $this->config['config'] = $this->tmpl->setup['config.'];
                    }
                    // override it with the page/type-specific "config."
                    if (is_array($this->pSetup['config.'] ?? null)) {
                        $this->config['config'] = array_replace_recursive($this->config['config'], $this->pSetup['config.']);
                    }
                    // Processing for the config_array:
                    $this->config['rootLine'] = $this->tmpl->rootLine;
                }
                $timeTracker->pull();
            } else {
                $message = 'No TypoScript template found!';
                $this->logger->alert($message);
                try {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->internalErrorAction(
                        $request,
                        $message,
                        ['code' => PageAccessFailureReasons::RENDERING_INSTRUCTIONS_NOT_FOUND]
                    );
                    throw new PropagateResponseException($response, 1533931380);
                } catch (AbstractServerErrorException $e) {
                    $exceptionClass = get_class($e);
                    throw new $exceptionClass($message, 1294587218);
                }
            }
        }

        // No cache
        // Set $this->no_cache TRUE if the config.no_cache value is set!
        if ($this->config['config']['no_cache'] ?? false) {
            $this->set_no_cache('config.no_cache is set', true);
        }

        // Auto-configure settings when a site is configured
        $this->config['config']['absRefPrefix'] = $this->config['config']['absRefPrefix'] ?? 'auto';

        // Hook for postProcessing the configuration array
        $params = ['config' => &$this->config['config']];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'] ?? [] as $funcRef) {
            GeneralUtility::callUserFunction($funcRef, $params, $this);
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
     * @param ServerRequestInterface $request
     * @internal
     */
    protected function settingLanguage(ServerRequestInterface $request)
    {
        $_params = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess'] ?? [] as $_funcRef) {
            $ref = $this; // introduced for phpstan to not lose type information when passing $this into callUserFunction
            GeneralUtility::callUserFunction($_funcRef, $_params, $ref);
        }

        // Get values from site language
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($this->language);

        $languageId = $languageAspect->getId();
        $languageContentId = $languageAspect->getContentId();

        $pageTranslationVisibility = new PageTranslationVisibility((int)($this->page['l18n_cfg'] ?? 0));
        // If sys_language_uid is set to another language than default:
        if ($languageAspect->getId() > 0) {
            // Request the overlay record for the sys_language_uid:
            $olRec = $this->sys_page->getPageOverlay($this->id, $languageAspect->getId());
            if (empty($olRec)) {
                // If requested translation is not available:
                if ($pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()) {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'Page is not available in the requested language.',
                        ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE]
                    );
                    throw new PropagateResponseException($response, 1533931388);
                }
                switch ((string)$languageAspect->getLegacyLanguageMode()) {
                    case 'strict':
                        $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                            $request,
                            'Page is not available in the requested language (strict).',
                            ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE_STRICT_MODE]
                        );
                        throw new PropagateResponseException($response, 1533931395);
                    case 'fallback':
                    case 'content_fallback':
                        // Setting content uid (but leaving the sys_language_uid) when a content_fallback
                        // value was found.
                        foreach ($languageAspect->getFallbackChain() as $orderValue) {
                            if ($orderValue === '0' || $orderValue === 0 || $orderValue === '') {
                                $languageContentId = 0;
                                break;
                            }
                            if (MathUtility::canBeInterpretedAsInteger($orderValue) && !empty($this->sys_page->getPageOverlay($this->id, (int)$orderValue))) {
                                $languageContentId = (int)$orderValue;
                                break;
                            }
                            if ($orderValue === 'pageNotFound') {
                                // The existing fallbacks have not been found, but instead of continuing
                                // page rendering with default language, a "page not found" message should be shown
                                // instead.
                                $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                                    $request,
                                    'Page is not available in the requested language (fallbacks did not apply).',
                                    ['code' => PageAccessFailureReasons::LANGUAGE_AND_FALLBACKS_NOT_AVAILABLE]
                                );
                                throw new PropagateResponseException($response, 1533931402);
                            }
                        }
                        break;
                    case 'ignore':
                        $languageContentId = $languageAspect->getId();
                        break;
                    default:
                        // Default is that everything defaults to the default language...
                        $languageId = ($languageContentId = 0);
                }
            }

            // Define the language aspect again now
            $languageAspect = GeneralUtility::makeInstance(
                LanguageAspect::class,
                $languageId,
                $languageContentId,
                $languageAspect->getOverlayType(),
                $languageAspect->getFallbackChain()
            );

            // Setting the site language if an overlay record was found (which it is only if a language is used)
            // We'll do this every time since the language aspect might have changed now
            // Doing this ensures that page properties like the page title are returned in the correct language
            $this->page = $this->sys_page->getPageOverlay($this->page, $languageAspect->getContentId());
        }

        // Set the language aspect
        $this->context->setAspect('language', $languageAspect);

        // Setting sys_language_uid inside sys-page by creating a new page repository
        $this->sys_page = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        // If default language is not available:
        if ((!$languageAspect->getContentId() || !$languageAspect->getId())
            && $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage()
        ) {
            $message = 'Page is not available in default language.';
            $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                $message,
                ['code' => PageAccessFailureReasons::LANGUAGE_DEFAULT_NOT_AVAILABLE]
            );
            throw new PropagateResponseException($response, 1533931423);
        }

        if ($languageAspect->getId() > 0) {
            $this->updateRootLinesWithTranslations();
        }

        $_params = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
    }

    /**
     * Updating content of the two rootLines IF the language key is set!
     */
    protected function updateRootLinesWithTranslations()
    {
        try {
            $this->rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $this->id, $this->MP, $this->context)->get();
        } catch (RootLineException $e) {
            $this->rootLine = [];
        }
    }

    /**
     * Calculates and sets the internal linkVars based upon the current request parameters
     * and the setting "config.linkVars".
     *
     * @param array $queryParams $_GET (usually called with a PSR-7 $request->getQueryParams())
     */
    public function calculateLinkVars(array $queryParams)
    {
        $this->linkVars = GeneralUtility::makeInstance(LinkVarsCalculator::class)
            ->getAllowedLinkVarsFromRequest(
                (string)($this->config['config']['linkVars'] ?? ''),
                $queryParams,
                $this->context
            );
    }

    /**
     * Returns URI of target page, if the current page is an overlaid mountpoint.
     *
     * If the current page is of type mountpoint and should be overlaid with the contents of the mountpoint page
     * and is accessed directly, the user will be redirected to the mountpoint context.
     * @internal
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function getRedirectUriForMountPoint(ServerRequestInterface $request): ?string
    {
        if (!empty($this->originalMountPointPage) && (int)$this->originalMountPointPage['doktype'] === PageRepository::DOKTYPE_MOUNTPOINT) {
            return $this->getUriToCurrentPageForRedirect($request);
        }

        return null;
    }

    /**
     * Returns URI of target page, if the current page is a Shortcut.
     *
     * If the current page is of type shortcut and accessed directly via its URL,
     * the user will be redirected to shortcut target.
     *
     * @internal
     * @param ServerRequestInterface $request
     * @return string|null
     */
    public function getRedirectUriForShortcut(ServerRequestInterface $request): ?string
    {
        if (!empty($this->originalShortcutPage) && $this->originalShortcutPage['doktype'] == PageRepository::DOKTYPE_SHORTCUT) {
            // Check if the shortcut page is actually on the current site, if not, this is a "page not found"
            // because the request was www.mydomain.com/?id=23 where page ID 23 (which is a shortcut) is on another domain/site.
            if ((int)($request->getQueryParams()['id'] ?? 0) > 0) {
                try {
                    $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($this->originalShortcutPage['l10n_parent'] ?: $this->originalShortcutPage['uid']);
                } catch (SiteNotFoundException $e) {
                    $site = null;
                }
                if ($site !== $this->site) {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'ID was outside the domain',
                        $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_HOST_PAGE_MISMATCH)
                    );
                    throw new ImmediateResponseException($response, 1638022483);
                }
            }
            return $this->getUriToCurrentPageForRedirect($request);
        }

        return null;
    }

    /**
     * Instantiate \TYPO3\CMS\Frontend\ContentObject to generate the correct target URL
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    protected function getUriToCurrentPageForRedirect(ServerRequestInterface $request): string
    {
        $this->calculateLinkVars($request->getQueryParams());
        $parameter = $this->page['uid'];
        if ($this->type) {
            $parameter .= ',' . $this->type;
        }
        return GeneralUtility::makeInstance(ContentObjectRenderer::class, $this)->createUrl([
            'parameter' => $parameter,
            'addQueryString' => true,
            'addQueryString.' => ['exclude' => 'id,type'],
            'forceAbsoluteUrl' => true,
        ]);
    }

    /********************************************
     *
     * Page generation; cache handling
     *
     *******************************************/
    /**
     * Returns TRUE if the page content should be generated.
     */
    public function isGeneratePage(): bool
    {
        return !$this->pageContentWasLoadedFromCache;
    }

    /**
     * Set cache content to $this->content
     */
    protected function realPageCacheContent()
    {
        // seconds until a cached page is too old
        $cacheTimeout = $this->get_cache_timeout();
        $timeOutTime = $GLOBALS['EXEC_TIME'] + $cacheTimeout;
        $usePageCache = true;
        // Hook for deciding whether page cache should be written to the cache backend or not
        // NOTE: as hooks are called in a loop, the last hook will have the final word (however each
        // hook receives the current status of the $usePageCache flag)
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['usePageCache'] ?? [] as $className) {
            $usePageCache = GeneralUtility::makeInstance($className)->usePageCache($this, $usePageCache);
        }
        // Write the page to cache, if necessary
        if ($usePageCache) {
            $this->setPageCacheContent($this->content, $this->config, $timeOutTime);
        }
        // Hook for cache post processing (eg. writing static files!)
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache'] ?? [] as $className) {
            GeneralUtility::makeInstance($className)->insertPageIncache($this, $timeOutTime);
        }
    }

    /**
     * Sets cache content; Inserts the content string into the cache_pages cache.
     *
     * @param string $content The content to store in the HTML field of the cache table
     * @param mixed $data The additional cache_data array, fx. $this->config
     * @param int $expirationTstamp Expiration timestamp
     * @see realPageCacheContent()
     */
    protected function setPageCacheContent($content, $data, $expirationTstamp)
    {
        $cacheData = [
            'identifier' => $this->newHash,
            'page_id' => $this->id,
            'content' => $content,
            'cache_data' => $data,
            'expires' => $expirationTstamp,
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'pageTitleInfo' => [
                'title' => $this->page['title'],
                'indexedDocTitle' => $this->indexedDocTitle,
            ],
        ];
        $this->cacheExpires = $expirationTstamp;
        $this->pageCacheTags[] = 'pageId_' . $cacheData['page_id'];
        // Respect the page cache when content of pid is shown
        if ($this->id !== $this->contentPid) {
            $this->pageCacheTags[] = 'pageId_' . $this->contentPid;
        }
        if (!empty($this->page['cache_tags'])) {
            $tags = GeneralUtility::trimExplode(',', $this->page['cache_tags'], true);
            $this->pageCacheTags = array_merge($this->pageCacheTags, $tags);
        }
        // Add the cache themselves as well, because they are fetched by getPageCacheTags()
        $cacheData['cacheTags'] = $this->pageCacheTags;
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
     * Setting the SYS_LASTCHANGED value in the pagerecord: This value will thus be set to the highest tstamp of records rendered on the page.
     * This includes all records with no regard to hidden records, userprotection and so on.
     *
     * The important part is that this actually updates a translated "pages" record (_PAGES_OVERLAY_UID) if
     * the Frontend is called with a translation.
     *
     * @see ContentObjectRenderer::lastChanged()
     * @see setRegisterValueForSysLastChanged()
     */
    protected function setSysLastChanged()
    {
        // We only update the info if browsing the live workspace
        $isInWorkspace = $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
        if ($isInWorkspace) {
            return;
        }
        if ($this->page['SYS_LASTCHANGED'] < (int)$this->register['SYS_LASTCHANGED']) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('pages');
            $pageId = $this->page['_PAGES_OVERLAY_UID'] ?? $this->id;
            $connection->update(
                'pages',
                [
                    'SYS_LASTCHANGED' => (int)$this->register['SYS_LASTCHANGED'],
                ],
                [
                    'uid' => (int)$pageId,
                ]
            );
        }
    }

    /**
     * Set the SYS_LASTCHANGED register value, is also called when a translated page is in use,
     * so the register reflects the state of the translated page, not the page in the default language.
     *
     * @param array $page
     * @internal
     * @see setSysLastChanged()
     */
    protected function setRegisterValueForSysLastChanged(array $page): void
    {
        $this->register['SYS_LASTCHANGED'] = (int)$page['tstamp'];
        if ($this->register['SYS_LASTCHANGED'] < (int)$page['SYS_LASTCHANGED']) {
            $this->register['SYS_LASTCHANGED'] = (int)$page['SYS_LASTCHANGED'];
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

    /**
     * @return array
     */
    public function getPageCacheTags(): array
    {
        return $this->pageCacheTags;
    }

    /********************************************
     *
     * Page generation; rendering and inclusion
     *
     *******************************************/
    /**
     * Does some processing BEFORE the page content is generated / built.
     */
    public function generatePage_preProcessing()
    {
        // Same codeline as in getFromCache(). But $this->all has been changed by
        // \TYPO3\CMS\Core\TypoScript\TemplateService::start() in the meantime, so this must be called again!
        $this->newHash = $this->getHash();

        // Used as a safety check in case a PHP script is falsely disabling $this->no_cache during page generation.
        $this->no_cacheBeforePageGen = $this->no_cache;
    }

    /**
     * Check the value of "content_from_pid" of the current page record, and see if the current request
     * should actually show content from another page.
     *
     * By using $TSFE->getPageAndRootline() on the cloned object, all rootline restrictions (extendToSubPages)
     * are evaluated as well.
     *
     * @param ServerRequestInterface $request
     * @return int the current page ID or another one if resolved properly - usually set to $this->contentPid
     */
    protected function resolveContentPid(ServerRequestInterface $request): int
    {
        if (!isset($this->page['content_from_pid']) || empty($this->page['content_from_pid'])) {
            return $this->id;
        }
        // make REAL copy of TSFE object - not reference!
        $temp_copy_TSFE = clone $this;
        // Set ->id to the content_from_pid value - we are going to evaluate this pid as was it a given id for a page-display!
        $temp_copy_TSFE->id = (int)$this->page['content_from_pid'];
        $temp_copy_TSFE->MP = '';
        $temp_copy_TSFE->getPageAndRootline($request);
        return $temp_copy_TSFE->id;
    }
    /**
     * Sets up TypoScript "config." options and set properties in $TSFE.
     */
    public function preparePageContentGeneration(ServerRequestInterface $request)
    {
        $this->getTimeTracker()->push('Prepare page content generation');
        // Global vars...
        $this->indexedDocTitle = $this->page['title'] ?? null;
        // Base url:
        if (isset($this->config['config']['baseURL'])) {
            $this->baseUrl = $this->config['config']['baseURL'];
        }
        // Internal and External target defaults
        $this->intTarget = (string)($this->config['config']['intTarget'] ?? '');
        $this->extTarget = (string)($this->config['config']['extTarget'] ?? '');
        $this->fileTarget = (string)($this->config['config']['fileTarget'] ?? '');
        if (($this->config['config']['spamProtectEmailAddresses'] ?? '') === 'ascii') {
            $this->logDeprecatedTyposcript('config.spamProtectEmailAddresses = ascii', 'This setting has no effect anymore. Change it to a number between -10 and 10 or remove it completely');
            $this->config['config']['spamProtectEmailAddresses'] = 0;
        }
        $this->spamProtectEmailAddresses = (int)($this->config['config']['spamProtectEmailAddresses'] ?? 0);
        $this->spamProtectEmailAddresses = MathUtility::forceIntegerInRange($this->spamProtectEmailAddresses, -10, 10, 0);
        // calculate the absolute path prefix
        if (!empty($this->absRefPrefix = trim($this->config['config']['absRefPrefix'] ?? ''))) {
            if ($this->absRefPrefix === 'auto') {
                $normalizedParams = $request->getAttribute('normalizedParams');
                $this->absRefPrefix = $normalizedParams->getSitePath();
            }
        }
        // linkVars
        $this->calculateLinkVars($request->getQueryParams());
        // Setting XHTML-doctype from doctype
        $this->config['config']['xhtmlDoctype'] = $this->config['config']['xhtmlDoctype'] ?? $this->config['config']['doctype'] ?? '';
        if ($this->config['config']['xhtmlDoctype']) {
            $this->xhtmlDoctype = $this->config['config']['xhtmlDoctype'];
            // Checking XHTML-docytpe
            switch ((string)$this->config['config']['xhtmlDoctype']) {
                case 'xhtml_trans':
                case 'xhtml_strict':
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
        $this->newCObj($request);
        $this->getTimeTracker()->pull();
    }

    /**
     * Does processing of the content after the page content was generated.
     *
     * This includes caching the page, indexing the page (if configured) and setting sysLastChanged
     */
    public function generatePage_postProcessing()
    {
        $this->setAbsRefPrefix();
        // This is to ensure, that the page is NOT cached if the no_cache parameter was set before the page was generated. This is a safety precaution, as it could have been unset by some script.
        if ($this->no_cacheBeforePageGen) {
            $this->set_no_cache('no_cache has been set before the page was generated - safety check', true);
        }
        // Hook for post-processing of page content cached/non-cached:
        $_params = ['pObj' => &$this];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
        // Processing if caching is enabled:
        if (!$this->no_cache) {
            // Hook for post-processing of page content before being cached:
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached'] ?? [] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        // Storing for cache:
        if (!$this->no_cache) {
            $this->realPageCacheContent();
        }
        // Sets sys-last-change:
        $this->setSysLastChanged();
    }

    /**
     * Generate the page title, can be called multiple times,
     * as PageTitleProvider might have been modified by an uncached plugin etc.
     *
     * @return string the generated page title
     */
    public function generatePageTitle(): string
    {
        // Check for a custom pageTitleSeparator, and perform stdWrap on it
        $pageTitleSeparator = (string)$this->cObj->stdWrapValue('pageTitleSeparator', $this->config['config'] ?? []);
        if ($pageTitleSeparator !== '' && $pageTitleSeparator === ($this->config['config']['pageTitleSeparator'] ?? '')) {
            $pageTitleSeparator .= ' ';
        }

        $titleProvider = GeneralUtility::makeInstance(PageTitleProviderManager::class);
        if (!empty($this->config['config']['pageTitleCache'])) {
            $titleProvider->setPageTitleCache($this->config['config']['pageTitleCache']);
        }
        $pageTitle = $titleProvider->getTitle();
        $this->config['config']['pageTitleCache'] = $titleProvider->getPageTitleCache();

        if ($pageTitle !== '') {
            $this->indexedDocTitle = $pageTitle;
        }

        $titleTagContent = $this->printTitle(
            $pageTitle,
            (bool)($this->config['config']['noPageTitle'] ?? false),
            (bool)($this->config['config']['pageTitleFirst'] ?? false),
            $pageTitleSeparator
        );
        $this->config['config']['pageTitle'] = $titleTagContent;
        // stdWrap around the title tag
        $titleTagContent = $this->cObj->stdWrapValue('pageTitle', $this->config['config']);

        // config.noPageTitle = 2 - means do not render the page title
        if (isset($this->config['config']['noPageTitle']) && (int)$this->config['config']['noPageTitle'] === 2) {
            $titleTagContent = '';
        }
        if ($titleTagContent !== '') {
            $this->pageRenderer->setTitle($titleTagContent);
        }
        return (string)$titleTagContent;
    }

    /**
     * Compiles the content for the page <title> tag.
     *
     * @param string $pageTitle The input title string, typically the "title" field of a page's record.
     * @param bool $noTitle If set, then only the site title is outputted
     * @param bool $showTitleFirst If set, then website title and $title is swapped
     * @param string $pageTitleSeparator an alternative to the ": " as the separator between site title and page title
     * @return string The page title on the form "[website title]: [input-title]". Not htmlspecialchar()'ed.
     * @see generatePageTitle()
     */
    protected function printTitle(string $pageTitle, bool $noTitle = false, bool $showTitleFirst = false, string $pageTitleSeparator = ''): string
    {
        $websiteTitle = $this->getWebsiteTitle();
        $pageTitle = $noTitle ? '' : $pageTitle;
        if ($showTitleFirst) {
            $temp = $websiteTitle;
            $websiteTitle = $pageTitle;
            $pageTitle = $temp;
        }
        // only show a separator if there are both site title and page title
        if ($pageTitle === '' || $websiteTitle === '') {
            $pageTitleSeparator = '';
        } elseif (empty($pageTitleSeparator)) {
            // use the default separator if non given
            $pageTitleSeparator = ': ';
        }
        return $websiteTitle . $pageTitleSeparator . $pageTitle;
    }

    /**
     * @return string
     */
    protected function getWebsiteTitle(): string
    {
        if (trim($this->language->getWebsiteTitle()) !== '') {
            return trim($this->language->getWebsiteTitle());
        }
        if (trim($this->site->getConfiguration()['websiteTitle'] ?? '') !== '') {
            return trim($this->site->getConfiguration()['websiteTitle']);
        }

        return '';
    }

    /**
     * Processes the INTinclude-scripts
     */
    public function INTincScript(ServerRequestInterface $request): void
    {
        $this->additionalHeaderData = $this->config['INTincScript_ext']['additionalHeaderData'] ?? [];
        $this->additionalFooterData = $this->config['INTincScript_ext']['additionalFooterData'] ?? [];
        if (empty($this->config['INTincScript_ext']['pageRendererState'])) {
            $this->initPageRenderer();
        } else {
            $pageRendererState = unserialize($this->config['INTincScript_ext']['pageRendererState'], ['allowed_classes' => false]);
            $this->pageRenderer->updateState($pageRendererState);
        }
        if (!empty($this->config['INTincScript_ext']['assetCollectorState'])) {
            $assetCollectorState = unserialize($this->config['INTincScript_ext']['assetCollectorState'], ['allowed_classes' => false]);
            GeneralUtility::makeInstance(AssetCollector::class)->updateState($assetCollectorState);
        }

        $this->recursivelyReplaceIntPlaceholdersInContent($request);
        $this->getTimeTracker()->push('Substitute header section');
        $this->INTincScript_loadJSCode();
        $this->generatePageTitle();

        $this->content = str_replace(
            [
                '<!--HD_' . $this->config['INTincScript_ext']['divKey'] . '-->',
                '<!--FD_' . $this->config['INTincScript_ext']['divKey'] . '-->',
            ],
            [
                implode(LF, $this->additionalHeaderData),
                implode(LF, $this->additionalFooterData),
            ],
            $this->pageRenderer->renderJavaScriptAndCssForProcessingOfUncachedContentObjects($this->content, $this->config['INTincScript_ext']['divKey'])
        );
        // Replace again, because header and footer data and page renderer replacements may introduce additional placeholders (see #44825)
        $this->recursivelyReplaceIntPlaceholdersInContent($request);
        $this->setAbsRefPrefix();
        $this->getTimeTracker()->pull();
    }

    /**
     * Replaces INT placeholders (COA_INT and USER_INT) in $this->content
     * In case the replacement adds additional placeholders, it loops
     * until no new placeholders are found any more.
     */
    protected function recursivelyReplaceIntPlaceholdersInContent(ServerRequestInterface $request)
    {
        do {
            $nonCacheableData = $this->config['INTincScript'];
            $this->processNonCacheableContentPartsAndSubstituteContentMarkers($nonCacheableData, $request);
            // Check if there were new items added to INTincScript during the previous execution:
            // array_diff_assoc throws notices if values are arrays but not strings. We suppress this here.
            $nonCacheableData = @array_diff_assoc($this->config['INTincScript'], $nonCacheableData);
            $reprocess = count($nonCacheableData) > 0;
        } while ($reprocess);
    }

    /**
     * Processes the INTinclude-scripts and substitute in content.
     *
     * Takes $this->content, and splits the content by <!--INT_SCRIPT.12345 --> and then puts the content
     * back together.
     *
     * @param array $nonCacheableData $GLOBALS['TSFE']->config['INTincScript'] or part of it
     * @see INTincScript()
     */
    protected function processNonCacheableContentPartsAndSubstituteContentMarkers(array $nonCacheableData, ServerRequestInterface $request)
    {
        $timeTracker = $this->getTimeTracker();
        $timeTracker->push('Split content');
        // Splits content with the key.
        $contentSplitByUncacheableMarkers = explode('<!--INT_SCRIPT.', $this->content);
        $this->content = '';
        $timeTracker->setTSlogMessage('Parts: ' . count($contentSplitByUncacheableMarkers), LogLevel::INFO);
        $timeTracker->pull();
        foreach ($contentSplitByUncacheableMarkers as $counter => $contentPart) {
            // If the split had a comment-end after 32 characters it's probably a split-string
            if (substr($contentPart, 32, 3) === '-->') {
                $nonCacheableKey = 'INT_SCRIPT.' . substr($contentPart, 0, 32);
                if (is_array($nonCacheableData[$nonCacheableKey])) {
                    $label = 'Include ' . $nonCacheableData[$nonCacheableKey]['type'];
                    $timeTracker->push($label);
                    $nonCacheableContent = '';
                    $contentObjectRendererForNonCacheable = unserialize($nonCacheableData[$nonCacheableKey]['cObj']);
                    /* @var ContentObjectRenderer $contentObjectRendererForNonCacheable */
                    $contentObjectRendererForNonCacheable->setRequest($request);
                    switch ($nonCacheableData[$nonCacheableKey]['type']) {
                        case 'COA':
                            $nonCacheableContent = $contentObjectRendererForNonCacheable->cObjGetSingle('COA', $nonCacheableData[$nonCacheableKey]['conf']);
                            break;
                        case 'FUNC':
                            $nonCacheableContent = $contentObjectRendererForNonCacheable->cObjGetSingle('USER', $nonCacheableData[$nonCacheableKey]['conf']);
                            break;
                        case 'POSTUSERFUNC':
                            $nonCacheableContent = $contentObjectRendererForNonCacheable->callUserFunction($nonCacheableData[$nonCacheableKey]['postUserFunc'], $nonCacheableData[$nonCacheableKey]['conf'], $nonCacheableData[$nonCacheableKey]['content']);
                            break;
                    }
                    $this->content .= $nonCacheableContent;
                    $this->content .= substr($contentPart, 35);
                    $timeTracker->pull($nonCacheableContent);
                } else {
                    $this->content .= substr($contentPart, 35);
                }
            } elseif ($counter) {
                // If it's not the first entry (which would be "0" of the array keys), then re-add the INT_SCRIPT part
                $this->content .= '<!--INT_SCRIPT.' . $contentPart;
            } else {
                $this->content .= $contentPart;
            }
        }
    }

    /**
     * Loads the JavaScript/CSS code for INTincScript, if there are non-cacheable content objects
     * it prepares the placeholders, otherwise populates options directly.
     *
     * @internal this method should be renamed as it does not only handle JS, but all additional header data
     */
    public function INTincScript_loadJSCode()
    {
        // Prepare code and placeholders for additional header and footer files (and make sure that this isn't called twice)
        if ($this->isINTincScript() && !isset($this->config['INTincScript_ext'])) {
            $substituteHash = $this->uniqueHash();
            $this->config['INTincScript_ext']['divKey'] = $substituteHash;
            // Storing the header-data array
            $this->config['INTincScript_ext']['additionalHeaderData'] = $this->additionalHeaderData;
            // Storing the footer-data array
            $this->config['INTincScript_ext']['additionalFooterData'] = $this->additionalFooterData;
            // Clearing the array
            $this->additionalHeaderData = ['<!--HD_' . $substituteHash . '-->'];
            // Clearing the array
            $this->additionalFooterData = ['<!--FD_' . $substituteHash . '-->'];
        }
    }

    /**
     * Determines if there are any INTincScripts to include = "non-cacheable" parts
     *
     * @return bool Returns TRUE if scripts are found
     */
    public function isINTincScript()
    {
        return !empty($this->config['INTincScript']) && is_array($this->config['INTincScript']);
    }

    /**
     * Add HTTP headers to the response object.
     *
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function applyHttpHeadersToResponse(ResponseInterface $response): ResponseInterface
    {
        // Set header for charset-encoding unless disabled
        if (empty($this->config['config']['disableCharsetHeader'])) {
            $response = $response->withHeader('Content-Type', $this->contentType . '; charset=utf-8');
        }
        // Set header for content language unless disabled
        $contentLanguage = $this->language->getTwoLetterIsoCode();
        if (empty($this->config['config']['disableLanguageHeader']) && !empty($contentLanguage)) {
            $response = $response->withHeader('Content-Language', trim($contentLanguage));
        }

        // Add a Response header to show debug information if a page was fetched from cache
        if ($this->debugInformationHeader) {
            $response = $response->withHeader('X-TYPO3-Debug-Cache', $this->debugInformationHeader);
        }

        // Set cache related headers to client (used to enable proxy / client caching!)
        if (!empty($this->config['config']['sendCacheHeaders'])) {
            $headers = $this->getCacheHeaders();
            foreach ($headers as $header => $value) {
                $response = $response->withHeader($header, $value);
            }
        }
        // Set additional headers if any have been configured via TypoScript
        $additionalHeaders = $this->getAdditionalHeaders();
        foreach ($additionalHeaders as $headerConfig) {
            [$header, $value] = GeneralUtility::trimExplode(':', $headerConfig['header'], false, 2);
            if ($headerConfig['statusCode']) {
                $response = $response->withStatus((int)$headerConfig['statusCode']);
            }
            if ($headerConfig['replace']) {
                $response = $response->withHeader($header, $value);
            } else {
                $response = $response->withAddedHeader($header, $value);
            }
        }
        return $response;
    }

    /**
     * Get cache headers good for client/reverse proxy caching.
     *
     * @return array
     */
    protected function getCacheHeaders(): array
    {
        // Getting status whether we can send cache control headers for proxy caching:
        $doCache = $this->isStaticCacheble();
        $isBackendUserLoggedIn = $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
        $isInWorkspace = $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
        // Finally, when backend users are logged in, do not send cache headers at all (Admin Panel might be displayed for instance).
        $isClientCachable = $doCache && !$isBackendUserLoggedIn && !$isInWorkspace;
        if ($isClientCachable) {
            $headers = [
                'Expires' => gmdate('D, d M Y H:i:s T', $this->cacheExpires),
                'ETag' => '"' . md5($this->content) . '"',
                'Cache-Control' => 'max-age=' . ($this->cacheExpires - $GLOBALS['EXEC_TIME']),
                // no-cache
                'Pragma' => 'public',
            ];
        } else {
            // "no-store" is used to ensure that the client HAS to ask the server every time, and is not allowed to store anything at all
            $headers = [
                'Cache-Control' => 'private, no-store',
            ];
            // Now, if a backend user is logged in, tell him in the Admin Panel log what the caching status would have been:
            if ($isBackendUserLoggedIn) {
                if ($doCache) {
                    $this->getTimeTracker()->setTSlogMessage('Cache-headers with max-age "' . ($this->cacheExpires - $GLOBALS['EXEC_TIME']) . '" would have been sent');
                } else {
                    $reasonMsg = [];
                    if ($this->no_cache) {
                        $reasonMsg[] = 'Caching disabled (no_cache).';
                    }
                    if ($this->isINTincScript()) {
                        $reasonMsg[] = '*_INT object(s) on page.';
                    }
                    if ($this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false)) {
                        $reasonMsg[] = 'Frontend user logged in.';
                    }
                    $this->getTimeTracker()->setTSlogMessage('Cache-headers would disable proxy caching! Reason(s): "' . implode(' ', $reasonMsg) . '"', LogLevel::NOTICE);
                }
            }
        }
        return $headers;
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
        return !$this->no_cache && !$this->isINTincScript() && !$this->context->getAspect('frontend.user')->isUserOrGroupSet();
    }

    /********************************************
     *
     * Various internal API functions
     *
     *******************************************/
    /**
     * Creates an instance of ContentObjectRenderer in $this->cObj
     * This instance is used to start the rendering of the TypoScript template structure
     *
     * @param ServerRequestInterface|null $request
     */
    public function newCObj(ServerRequestInterface $request = null)
    {
        $this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $this);
        $this->cObj->start($this->page, 'pages', $request);
    }

    /**
     * Converts relative paths in the HTML source to absolute paths for fileadmin/, typo3conf/ext/ and media/ folders.
     *
     * @internal
     * @see \TYPO3\CMS\Frontend\Http\RequestHandler
     * @see INTincScript()
     */
    protected function setAbsRefPrefix()
    {
        if (!$this->absRefPrefix) {
            return;
        }
        $search = [
            '"_assets/',
            '"typo3temp/',
            '"' . PathUtility::stripPathSitePrefix(Environment::getExtensionsPath()) . '/',
            '"' . PathUtility::stripPathSitePrefix(Environment::getFrameworkBasePath()) . '/',
        ];
        $replace = [
            '"' . $this->absRefPrefix . '_assets/',
            '"' . $this->absRefPrefix . 'typo3temp/',
            '"' . $this->absRefPrefix . PathUtility::stripPathSitePrefix(Environment::getExtensionsPath()) . '/',
            '"' . $this->absRefPrefix . PathUtility::stripPathSitePrefix(Environment::getFrameworkBasePath()) . '/',
        ];
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
        $this->getTimeTracker()->setTSlogMessage($typoScriptProperty . ' is deprecated.' . $explanationText, LogLevel::WARNING);
        trigger_error('TypoScript property ' . $typoScriptProperty . ' is deprecated' . $explanationText, E_USER_DEPRECATED);
    }

    /********************************************
     * PUBLIC ACCESSIBLE WORKSPACES FUNCTIONS
     *******************************************/

    /**
     * Returns TRUE if workspace preview is enabled
     *
     * @return bool Returns TRUE if workspace preview is enabled
     * @deprecated will be removed in TYPO3 v13.0. Use the Context API directly.
     */
    public function doWorkspacePreview()
    {
        trigger_error('TSFE->doWorkspacePreview() will be removed in TYPO3 v13.0. Use the Context API directly.', E_USER_DEPRECATED);
        return $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
    }

    /**
     * Returns the uid of the current workspace
     *
     * @return int returns workspace integer for which workspace is being preview. 0 if none (= live workspace).
     * @deprecated will be removed in TYPO3 v13.0. Use the Context API directly.
     */
    public function whichWorkspace(): int
    {
        trigger_error('TSFE->whichWorkspace() will be removed in TYPO3 v13.0. Use the Context API directly.', E_USER_DEPRECATED);
        return $this->context->getPropertyFromAspect('workspace', 'id', 0);
    }

    /********************************************
     *
     * Various external API functions - for use in plugins etc.
     *
     *******************************************/

    /**
     * Returns the pages TSconfig array based on the current ->rootLine
     *
     * @return array
     */
    public function getPagesTSconfig(): array
    {
        if (!is_array($this->pagesTSconfig)) {
            $matcher = GeneralUtility::makeInstance(ConditionMatcher::class, $this->context, $this->id, $this->rootLine);
            $this->pagesTSconfig = GeneralUtility::makeInstance(PageTsConfig::class)
                ->getForRootLine(
                    array_reverse($this->rootLine),
                    $this->site,
                    $matcher
                );
        }
        return $this->pagesTSconfig;
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
     * @param string $reason An optional reason to be written to the log.
     * @param bool $internal Whether the call is done from core itself (should only be used by core).
     */
    public function set_no_cache($reason = '', $internal = false)
    {
        $context = [];
        if ($reason !== '') {
            $warning = '$TSFE->set_no_cache() was triggered. Reason: {reason}.';
            $context['reason'] = $reason;
        } else {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            // This is a hack to work around ___FILE___ resolving symbolic links
            $realWebPath = PathUtility::dirname((string)realpath(Environment::getBackendPath())) . '/';
            $file = $trace[0]['file'];
            if (str_starts_with($file, $realWebPath)) {
                $file = str_replace($realWebPath, '', $file);
            } else {
                $file = str_replace(Environment::getPublicPath() . '/', '', $file);
            }
            $warning = '$GLOBALS[\'TSFE\']->set_no_cache() was triggered by {file} on line {line}.';
            $context['file'] = $file;
            $context['line'] = $trace[0]['line'];
        }
        if (!$internal && $GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter']) {
            $warning .= ' However, $TYPO3_CONF_VARS[\'FE\'][\'disableNoCacheParameter\'] is set, so it will be ignored!';
            $this->getTimeTracker()->setTSlogMessage($warning, LogLevel::WARNING);
        } else {
            $warning .= ' Caching is disabled!';
            $this->disableCache();
        }
        if ($internal) {
            $this->logger->notice($warning, $context);
        } else {
            $this->logger->warning($warning, $context);
        }
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
        $seconds = (int)$seconds;
        if ($seconds > 0) {
            $this->cacheTimeOutDefault = $seconds;
        }
    }

    /**
     * Get the cache timeout for the current page.
     */
    public function get_cache_timeout(): int
    {
        return GeneralUtility::makeInstance(CacheLifetimeCalculator::class)
            ->calculateLifetimeForPage(
                (int)$this->id,
                $this->page,
                $this->config['config'] ?? [],
                $this->cacheTimeOutDefault,
                $this->context
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
     * @param string $input Key string. Accepts the "LLL:" prefix.
     * @return string Label value, if any.
     */
    public function sL($input)
    {
        return $this->languageService->sL($input);
    }

    /**
     * Sets all internal measures what language the page should be rendered.
     * This is not for records, but rather the HTML / charset and the locallang labels
     */
    protected function setOutputLanguage()
    {
        $this->languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromSiteLanguage($this->language);
        // Always disable debugging for TSFE
        $this->languageService->debugKey = false;
    }

    /**
     * Returns the originally requested page uid when TSFE was instantiated initially.
     */
    public function getRequestedId(): int
    {
        return $this->requestedId;
    }

    /**
     * Acquire a page specific lock
     *
     *
     * The schematics here is:
     * - First acquire an access lock. This is using the type of the requested lock as key.
     *   Since the number of types is rather limited we can use the type as key as it will only
     *   eat up a limited number of lock resources on the system (files, semaphores)
     * - Second, we acquire the actual lock (named page lock). We can be sure we are the only process at this
     *   very moment, hence we either get the lock for the given key or we get an error as we request a non-blocking mode.
     *
     * Interleaving two locks is extremely important, because the actual page lock uses a hash value as key (see callers
     * of this function). If we would simply employ a normal blocking lock, we would get a potentially unlimited
     * (number of pages at least) number of different locks. Depending on the available locking methods on the system
     * we might run out of available resources. (e.g. maximum limit of semaphores is a system setting and applies
     * to the whole system)
     * We therefore must make sure that page locks are destroyed again if they are not used anymore, such that
     * we never use more locking resources than parallel requests to different pages (hashes).
     * In order to ensure this, we need to guarantee that no other process is waiting on a page lock when
     * the process currently having the lock on the page lock is about to release the lock again.
     * This can only be achieved by using a non-blocking mode, such that a process is never put into wait state
     * by the kernel, but only checks the availability of the lock. The access lock is our guard to be sure
     * that no two processes are at the same time releasing/destroying a page lock, whilst the other one tries to
     * get a lock for this page lock.
     * The only drawback of this implementation is that we basically have to poll the availability of the page lock.
     *
     * Note that the access lock resources are NEVER deleted/destroyed, otherwise the whole thing would be broken.
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
        if ($this->locks[$type]['accessLock'] ?? false) {
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
     * Send additional headers from config.additionalHeaders
     */
    protected function getAdditionalHeaders(): array
    {
        if (!isset($this->config['config']['additionalHeaders.'])) {
            return [];
        }
        $additionalHeaders = [];
        ksort($this->config['config']['additionalHeaders.']);
        foreach ($this->config['config']['additionalHeaders.'] as $options) {
            if (!is_array($options)) {
                continue;
            }
            $header = trim($options['header'] ?? '');
            if ($header === '') {
                continue;
            }
            $additionalHeaders[] = [
                'header' => $header,
                // "replace existing headers" is turned on by default, unless turned off
                'replace' => ($options['replace'] ?? '') !== '0',
                'statusCode' => (int)($options['httpResponseCode'] ?? 0) ?: null,
            ];
        }
        return $additionalHeaders;
    }

    /**
     * Log the page access failure with additional request information
     *
     * @param string $message
     * @param ServerRequestInterface $request
     */
    protected function logPageAccessFailure(string $message, ServerRequestInterface $request): void
    {
        $context = ['pageId' => $this->id];
        if (($normalizedParams = $request->getAttribute('normalizedParams')) instanceof NormalizedParams) {
            $context['requestUrl'] = $normalizedParams->getRequestUrl();
        }
        $this->logger->error($message, $context);
    }

    /**
     * Returns the current BE user.
     * @todo: Add PHP return type declaration and ensure, that classes using TSFE in BE/CLI context always instantiate
     *        a FrontendBackendUserAuthentication object in $GLOBALS['BE_USER'].
     *
     * @return FrontendBackendUserAuthentication|null
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'] ?? null;
    }

    /**
     * @return TimeTracker
     */
    protected function getTimeTracker()
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    public function getLanguage(): SiteLanguage
    {
        return $this->language;
    }

    public function getSite(): Site
    {
        return $this->site;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getPageArguments(): PageArguments
    {
        return $this->pageArguments;
    }
}
