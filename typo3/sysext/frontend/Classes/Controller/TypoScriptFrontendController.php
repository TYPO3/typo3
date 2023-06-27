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
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
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
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Locking\ResourceMutex;
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
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateTreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\ConditionVerdictAwareIncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionIncludeListAccumulatorVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionMatcherVisitor;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeSetupConditionConstantSubstitutionVisitor;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent;
use TYPO3\CMS\Frontend\Event\AfterPageAndLanguageIsResolvedEvent;
use TYPO3\CMS\Frontend\Event\AfterPageWithRootLineIsResolvedEvent;
use TYPO3\CMS\Frontend\Event\BeforePageIsResolvedEvent;
use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use TYPO3\CMS\Frontend\Typolink\LinkVarsCalculator;

/**
 * Main controller class of the TypoScript based frontend.
 *
 * This is prepared in Frontend middlewares and the content rendering is
 * ultimately called in \TYPO3\CMS\Frontend\Http\RequestHandler.
 *
 * When calling a Frontend page, an instance of this object is available
 * as $GLOBALS['TSFE'], even though the core development strives to get
 * rid of this in the future.
 */
class TypoScriptFrontendController implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use PublicPropertyDeprecationTrait;

    protected array $deprecatedPublicProperties = [
        'intTarget' => '$TSFE->intTarget will be removed in TYPO3 v13.0. Use $TSFE->config[\'config\'][\'intTarget\'] instead.',
        'extTarget' => '$TSFE->extTarget will be removed in TYPO3 v13.0. Use $TSFE->config[\'config\'][\'extTarget\'] instead.',
        'fileTarget' => '$TSFE->fileTarget will be removed in TYPO3 v13.0. Use $TSFE->config[\'config\'][\'fileTarget\'] instead.',
        'spamProtectEmailAddresses' => '$TSFE->spamProtectEmailAddresses will be removed in TYPO3 v13.0. Use $TSFE->config[\'config\'][\'spamProtectEmailAddresses\'] instead.',
        'baseUrl' => '$TSFE->baseUrl will be removed in TYPO3 v13.0. Use $TSFE->config[\'config\'][\'baseURL\'] instead.',
        'xhtmlDoctype' => '$TSFE->xhtmlDoctype will be removed in TYPO3 v13.0. Use PageRenderer->getDocType() instead.',
        'xhtmlVersion' => '$TSFE->xhtmlVersion will be removed in TYPO3 v13.0. Use PageRenderer->getDocType() instead.',
        'type' => '$TSFE->type will be removed in TYPO3 v13.0. Use $TSFE->getPageArguments()->getPageType() instead.',
    ];

    /**
     * The page id (int)
     */
    public int $id;

    /**
     * The type (read-only)
     * @var int|string
     * @internal since TYPO3 v12. Use $TSFE->getPageArguments()->getPageType() instead
     */
    protected $type = 0;

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
     * @internal
     */
    public $no_cache = false;

    /**
     * Rootline of page records all the way to the root.
     *
     * Both language and version overlays are applied to these page records:
     * All "data" fields are set to language / version overlay values, *except* uid and
     * pid, which are the default-language and live-version ids.
     *
     * First array row with the highest key is the deepest page (the requested page),
     * then parent pages with descending keys until (but not including) the
     * project root pseudo page 0.
     *
     * When page uid 5 is called in this example:
     * [0] Project name
     * |- [2] An organizational page, probably with is_siteroot=1 and a site config
     *    |- [3] Site root with a sys_template having "root" flag set
     *       |- [5] Here you are
     *
     * This $absoluteRootLine is:
     * [3] => [uid = 5, pid = 3, title = Here you are, ...]
     * [2] => [uid = 3, pid = 2, title = Site root with a sys_template having "root" flag set, ...]
     * [1] => [uid = 2, pid = 0, title = An organizational page, probably with is_siteroot=1 and a site config, ...]
     *
     * @var array<int, array<string, mixed>>
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
     * Is set to > 0 if the page could not be resolved. This will then result in early returns when resolving the page.
     */
    protected int $pageNotFound = 0;

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
     * A central data array consisting of various keys, initialized and
     * processed at various places in the class.
     *
     * This array is cached along with the rendered page content and contains
     * for instance a list of INT identifiers used to calculate 'dynamic' page
     * parts when a page is retrieved from cache.
     *
     * Some sub keys:
     *
     * 'config': This is the TypoScript ['config.'] sub-array, with some
     *           settings being sanitized and merged.
     *
     * 'rootLine': This is the "local" rootline of a deep page that stops at the first parent
     *             sys_template record that has "root" flag set, in natural parent-child order.
     *
     *             Both language and version overlays are applied to these page records:
     *             All "data" fields are set to language / version overlay values, *except* uid and
     *             pid, which are the default-language and live-version ids.
     *
     *             When page uid 5 is called in this example:
     *             [0] Project name
     *             |- [2] An organizational page, probably with is_siteroot=1 and a site config
     *                |- [3] Site root with a sys_template having "root" flag set
     *                   |- [5] Here you are
     *
     *             This rootLine is:
     *             [0] => [uid = 3, pid = 2, title = Site root with a sys_template having "root" flag set, ...]
     *             [1] => [uid = 5, pid = 3, title = Here you are, ...]
     *
     * @var array<string, mixed>
     */
    public $config = [];

    /**
     * The TypoScript template object. Used to parse the TypoScript template
     *
     * @var TemplateService
     * @internal: Will get a proper deprecation in v12.x.
     * @deprecated: TemplateService is kept for b/w compat in v12 but will be removed in v13.
     */
    public $tmpl;

    /**
     * Is set to the time-to-live time of cached pages. Default is 60*60*24, which is 24 hours.
     *
     * @internal
     */
    protected int $cacheTimeOutDefault = 0;

    /**
     * Set if cached content was fetched from the cache.
     * @internal
     */
    protected bool $pageContentWasLoadedFromCache = false;

    /**
     * Set to the expire time of cached content
     * @internal
     */
    protected int $cacheExpires = 0;

    /**
     * TypoScript configuration of the page-object.
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
     * @deprecated since TYPO3 v12.0. will be removed in TYPO3 v13.0.
     */
    protected $intTarget = '';

    /**
     * Default external target
     * @var string
     * @deprecated since TYPO3 v12.0. will be removed in TYPO3 v13.0.
     */
    protected $extTarget = '';

    /**
     * Default file link target
     * @var string
     * @deprecated since TYPO3 v12.0. will be removed in TYPO3 v13.0.
     */
    protected $fileTarget = '';

    /**
     * If set, typolink() function encrypts email addresses.
     * @deprecated since TYPO3 v12.0. will be removed in TYPO3 v13.0.
     */
    protected int $spamProtectEmailAddresses = 0;

    /**
     * Absolute Reference prefix
     * @var string
     */
    public $absRefPrefix = '';

    /**
     * A string prepared for insertion in all links on the page as url-parameters.
     * Based on configuration in TypoScript where you defined which GET parameters you
     * would like to pass on.
     * @internal if needed, generate linkVars via LinkVarsCalculator
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
     * The base URL set for the page header.
     * @var string
     * @deprecated since TYPO3 v12.0. will be removed in TYPO3 v13.0.
     */
    protected $baseUrl = '';

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
     * Info-array of the last resulting image resource of content object
     * IMG_RESOURCE (if any), containing width, height and so on.
     */
    public ?array $lastImgResourceInfo = null;

    /**
     * Internal calculations for labels
     */
    protected ?LanguageService $languageService = null;

    /**
     * @internal Internal locking. May move to a middleware soon.
     */
    public ?ResourceMutex $lock = null;

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
    protected string $contentType = 'text/html; charset=utf-8';

    /**
     * Doctype to use
     *
     * @var string
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Use PageRenderer->getDocType() instead.
     */
    protected $xhtmlDoctype = '';

    /**
     * @var int
     * @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Use PageRenderer->getDocType() instead.
     */
    protected $xhtmlVersion;

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
        if ($this->language->hasCustomTypo3Language()) {
            $locale = GeneralUtility::makeInstance(Locales::class)->createLocale($this->language->getTypo3Language());
        } else {
            $locale = $this->language->getLocale();
        }
        $this->pageRenderer->setLanguage($locale);
    }

    /**
     * @param string $contentType
     * @internal Must only be used by TYPO3 core
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
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $this->pageCache = $cacheManager->getCache('pages');
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
     */
    public function determineId(ServerRequestInterface $request): ?ResponseInterface
    {
        $this->sys_page = GeneralUtility::makeInstance(PageRepository::class, $this->context);

        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(new BeforePageIsResolvedEvent($this, $request));

        $timeTracker = $this->getTimeTracker();
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

        $event = new AfterPageWithRootLineIsResolvedEvent($this, $request);
        $event = $eventDispatcher->dispatch($event);
        if ($event->getResponse()) {
            return $event->getResponse();
        }

        $response = null;
        try {
            $this->evaluatePageNotFound($this->pageNotFound, $request);

            // Setting language and fetch translated page
            $this->settingLanguage($request);
            // Check the "content_from_pid" field of the resolved page
            $this->contentPid = $this->resolveContentPid($request);

            // Update SYS_LASTCHANGED at the very last, when $this->page might be changed
            // by settingLanguage() and the $this->page was finally resolved
            $this->setRegisterValueForSysLastChanged($this->page);
        } catch (PropagateResponseException $e) {
            $response = $e->getResponse();
        }

        $event = new AfterPageAndLanguageIsResolvedEvent($this, $request, $response);
        $eventDispatcher->dispatch($event);
        return $event->getResponse();
    }

    /**
     * If $this->pageNotFound is set, then throw an exception to stop further page generation process
     */
    protected function evaluatePageNotFound(int $pageNotFoundNumber, ServerRequestInterface $request): void
    {
        if (!$pageNotFoundNumber) {
            return;
        }
        $response = match ($pageNotFoundNumber) {
            1 => GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                $request,
                'ID was not an accessible page',
                $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED)
            ),
            2 => GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                $request,
                'Subsection was found and not accessible',
                $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_SUBSECTION_NOT_RESOLVED)
            ),
            3 => GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'ID was outside the domain',
                $this->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_HOST_PAGE_MISMATCH)
            ),
            default => GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Unspecified error',
                $this->getPageAccessFailureReasons()
            ),
        };
        throw new PropagateResponseException($response, 1533931329);
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

                    // If page record could not be resolved throw exception
                    if ($rawPageRecord === []) {
                        $message = 'The requested page does not exist!';
                        try {
                            $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                                $request,
                                $message,
                                $this->getPageAccessFailureReasons(PageAccessFailureReasons::PAGE_NOT_FOUND)
                            );
                            throw new PropagateResponseException($response, 1674144383);
                        } catch (PageNotFoundException $e) {
                            throw new PageNotFoundException($message, 1674539331);
                        }
                    }

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
        $pageDoktype = (int)($this->page['doktype'] ?? 0);
        $isSpacerOrSysfolder = $pageDoktype === PageRepository::DOKTYPE_SPACER || $pageDoktype === PageRepository::DOKTYPE_SYSFOLDER;
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
        if ($pageDoktype === PageRepository::DOKTYPE_SHORTCUT) {
            // We need to clear MP if the page is a shortcut. Reason is if the shortcut goes to another page, then we LEAVE the rootline which the MP expects.
            $this->MP = '';
            // saving the page so that we can check later - when we know
            // about languages - whether we took the correct shortcut or
            // whether a translation of the page overwrites the shortcut
            // target and we need to follow the new target
            $this->settingLanguage($request);
            $this->originalShortcutPage = $this->page;
            $this->page = $this->sys_page->resolveShortcutPage($this->page, true);
            $this->id = (int)$this->page['uid'];
            $pageDoktype = (int)($this->page['doktype'] ?? 0);
        }
        // If the page is a mountpoint which should be overlaid with the contents of the mounted page,
        // it must never be accessible directly, but only in the mountpoint context. Therefore we change
        // the current ID and the user is redirected by checkPageForMountpointRedirect().
        if ($pageDoktype === PageRepository::DOKTYPE_MOUNTPOINT && $this->page['mount_pid_ol']) {
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
            $pageDoktype = (int)($this->page['doktype'] ?? 0);
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
     * This is a central and quite early method called by PrepareTypoScriptFrontendRendering middleware:
     * This code is *always* executed for *every* frontend call if a general page rendering has to be done,
     * if there is no early redirect or eid call or similar.
     *
     * The goal is to calculate dependencies up to a point to see if a possible page cache can be used,
     * and to prepare TypoScript as far as really needed.
     *
     * @throws PropagateResponseException
     * @throws AbstractServerErrorException
     * @return ServerRequestInterface New request object with typoscript attribute
     *
     * @internal This method may vanish from TypoScriptFrontendController without further notice.
     * @todo: This method is typically called by PrepareTypoScriptFrontendRendering middleware.
     *        However, the RedirectService of (earlier) ext:redirects RedirectHandler middleware
     *        calls this as well. We may want to put this code into some helper class, reduce class
     *        state as much as possible and carry really needed state as request attributes around?!
     */
    public function getFromCache(ServerRequestInterface $request): ServerRequestInterface
    {
        // Reset some state.
        // @todo: Find out which resets are really needed here - Since this is called from a
        //        relatively early middleware, we can expect these properties to be not set already?!
        $this->content = '';
        $this->config = [];
        $this->pageContentWasLoadedFromCache = false;

        // Very first thing, *always* executed: TypoScript is one factor that influences page content.
        // There can be multiple cache entries per page, when TypoScript conditions on the same page
        // create different TypoScript. We thus need the sys_template rows relevant for this page.
        // @todo: Even though all rootline sys_template records are fetched with only one query
        //        in below implementation, we could potentially join or sub select sys_template
        //        records already when pages rootline is queried. This will save one query
        //        and needs an implementation in getPageAndRootline() which is called via determineId()
        //        in TypoScriptFrontendInitialization. This could be done when getPageAndRootline()
        //        switches to a CTE query instead of using RootlineUtility.
        $sysTemplateRepository = GeneralUtility::makeInstance(SysTemplateRepository::class);
        $sysTemplateRows = $sysTemplateRepository->getSysTemplateRowsByRootline($this->rootLine, $request);
        // Needed for cache calculations. Put into a variable here to not serialize multiple times.
        $serializedSysTemplateRows = serialize($sysTemplateRows);

        // Early exception if there is no sys_template at all.
        if (empty($sysTemplateRows)) {
            $message = 'No TypoScript record found!';
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

        if (!$this->tmpl instanceof TemplateService) {
            // @deprecated since v12, will be removed in v13: b/w compat. Remove when TemplateService is dropped.
            $this->tmpl = GeneralUtility::makeInstance(TemplateService::class, $this->context, null, $this);
        }

        // Calculate "local" rootLine that stops at first root=1 template, will be set as $this->config['rootLine']
        $sysTemplateRowsIndexedByPid = array_combine(array_column($sysTemplateRows, 'pid'), $sysTemplateRows);
        $localRootline = [];
        foreach ($this->rootLine as $rootlinePage) {
            array_unshift($localRootline, $rootlinePage);
            if ((int)($rootlinePage['uid'] ?? 0) > 0
                && (int)($sysTemplateRowsIndexedByPid[$rootlinePage['uid']]['root'] ?? 0) === 1
            ) {
                break;
            }
        }
        // @deprecated: since v12, will be removed in v13: b/w compat. Remove when TemplateService is dropped.
        $this->tmpl->rootLine = $localRootline;

        $site = $this->getSite();

        $tokenizer = new LossyTokenizer();
        $treeBuilder = GeneralUtility::makeInstance(SysTemplateTreeBuilder::class);
        $includeTreeTraverser = new IncludeTreeTraverser();
        $includeTreeTraverserConditionVerdictAware = new ConditionVerdictAwareIncludeTreeTraverser();
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        /** @var PhpFrontend|null $typoscriptCache */
        $typoscriptCache = null;
        if (!$this->no_cache) {
            // $this->no_cache = true might have been set by earlier TypoScriptFrontendInitialization middleware.
            // This means we don't do fancy cache stuff, calculate full TypoScript and ignore page cache.
            /** @var PhpFrontend|null $typoscriptCache */
            $typoscriptCache = $cacheManager->getCache('typoscript');
        }

        $topDownRootLine = $this->rootLine;
        ksort($topDownRootLine);
        $expressionMatcherVariables = [
            'request' => $request,
            'pageId' => $this->id,
            // @todo We're using the full page row here to provide all necessary fields (e.g. "backend_layout"),
            //       which are currently not included in the rows, RootlineUtility provides by default. We might
            //       want to switch to $this->rootline as soon as it contains all fields.
            'page' => $this->page,
            'fullRootLine' => $topDownRootLine,
            'localRootLine' => $localRootline,
            'site' => $site,
            'siteLanguage' => $request->getAttribute('language'),
            'tsfe' => $this,
        ];

        // We *always* need the TypoScript constants, one way or the other: Setup conditions can use constants,
        // so we need the constants to substitute their values within setup conditions.
        $constantConditionIncludeListCacheIdentifier = 'constant-condition-include-list-' . sha1($serializedSysTemplateRows);
        $constantConditionList = [];
        $constantsAst = new RootNode();
        $flatConstants = [];
        $serializedConstantConditionList = '';
        $gotConstantFromCache = false;
        if (!$this->no_cache && $constantConditionIncludeTree = $typoscriptCache->require($constantConditionIncludeListCacheIdentifier)) {
            // We got the flat list of all constants conditions for this TypoScript combination from cache. Good. We traverse
            // this list to calculate "current" condition verdicts. With a hash of this list together with a hash of the
            // TypoScript sys_templates, we try to retrieve the full constants TypoScript from cache.
            $conditionMatcherVisitor = GeneralUtility::makeInstance(IncludeTreeConditionMatcherVisitor::class);
            $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
            // It does not matter if we use IncludeTreeTraverser or ConditionVerdictAwareIncludeTreeTraverser here:
            // Condition list is flat, not nested. IncludeTreeTraverser has an if() less, so we use that one.
            $includeTreeTraverser->traverse($constantConditionIncludeTree, [$conditionMatcherVisitor]);
            $constantConditionList = $conditionMatcherVisitor->getConditionListWithVerdicts();
            // Needed for cache identifier calculations. Put into a variable here to not serialize multiple times.
            $serializedConstantConditionList = serialize($constantConditionList);
            $constantCacheEntryIdentifier = 'constant-' . sha1($serializedSysTemplateRows . $serializedConstantConditionList);
            $constantsCacheEntry = $typoscriptCache->require($constantCacheEntryIdentifier);
            if (is_array($constantsCacheEntry)) {
                $constantsAst = $constantsCacheEntry['ast'];
                $flatConstants = $constantsCacheEntry['flatConstants'];
                $gotConstantFromCache = true;
            }
        }
        if ($this->no_cache || !$gotConstantFromCache) {
            // We did not get constants from cache, or are not allowed to use cache. We have to build constants from scratch.
            // This means we'll fetch the full constants include tree (from cache if possible), register the condition
            // matcher and register the AST builder and traverse include tree to retrieve constants AST and calculate
            // 'flat constants' from it. Both are cached if allowed afterwards for the 'if' above to kick in next time.
            if ($this->no_cache) {
                // Note $typoscriptCache *is not* hand over here: IncludeTree is calculated from scratch, we're not allowed to use cache.
                $constantIncludeTree = $treeBuilder->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRows, $tokenizer, $site);
            } else {
                // Note $typoscriptCache *is* hand over here, we can potentially grab the fully cached includeTree here, or cache entry will be created.
                $constantIncludeTree = $treeBuilder->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRows, $tokenizer, $site, $typoscriptCache);
            }
            $conditionMatcherVisitor = GeneralUtility::makeInstance(IncludeTreeConditionMatcherVisitor::class);
            $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
            $includeTreeTraverserConditionVerdictAwareVisitors = [];
            $includeTreeTraverserConditionVerdictAwareVisitors[] = $conditionMatcherVisitor;
            $constantAstBuilderVisitor = GeneralUtility::makeInstance(IncludeTreeAstBuilderVisitor::class);
            $includeTreeTraverserConditionVerdictAwareVisitors[] = $constantAstBuilderVisitor;
            // We must use ConditionVerdictAwareIncludeTreeTraverser here: This one does not walk into
            // children for not matching conditions, which is important to create the correct AST.
            $includeTreeTraverserConditionVerdictAware->traverse($constantIncludeTree, $includeTreeTraverserConditionVerdictAwareVisitors);
            $constantsAst = $constantAstBuilderVisitor->getAst();
            $flatConstants = $constantsAst->flatten();
            if (!$this->no_cache) {
                // We are allowed to cache and can create both the full list of conditions, plus the constant AST and flat constant
                // list cache entry. To do that, we need all (!) conditions, but the above ConditionVerdictAwareIncludeTreeTraverser
                // did not find nested conditions if an upper condition did not match. We thus have to traverse include tree a
                // second time with the IncludeTreeTraverser that does traverse into not matching conditions as well.
                $includeTreeTraverserVisitors = [];
                $conditionMatcherVisitor = GeneralUtility::makeInstance(IncludeTreeConditionMatcherVisitor::class);
                $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
                $includeTreeTraverserVisitors[] = $constantAstBuilderVisitor;
                $constantConditionIncludeListAccumulatorVisitor = new IncludeTreeConditionIncludeListAccumulatorVisitor();
                $includeTreeTraverserVisitors[] = $constantConditionIncludeListAccumulatorVisitor;
                $includeTreeTraverser->traverse($constantIncludeTree, $includeTreeTraverserVisitors);
                $constantConditionList = $conditionMatcherVisitor->getConditionListWithVerdicts();
                // Needed for cache identifier calculations. Put into a variable here to not serialize multiple times.
                $serializedConstantConditionList = serialize($constantConditionList);
                $typoscriptCache->set($constantConditionIncludeListCacheIdentifier, 'return unserialize(\'' . addcslashes(serialize($constantConditionIncludeListAccumulatorVisitor->getConditionIncludes()), '\'\\') . '\');');
                $constantCacheEntryIdentifier = 'constant-' . sha1($serializedSysTemplateRows . $serializedConstantConditionList);
                $typoscriptCache->set($constantCacheEntryIdentifier, 'return unserialize(\'' . addcslashes(serialize(['ast' => $constantsAst, 'flatConstants' => $flatConstants]), '\'\\') . '\');');
            }
        }

        $frontendTypoScript = new FrontendTypoScript($constantsAst, $flatConstants);

        // Next step: We have constants and fetch the setup include tree now. We then calculate setup condition verdicts
        // and set the constants to allow substitution of constants within conditions. Next, we traverse include tree
        // to calculate conditions verdicts and gather them along the way. A hash of these conditions with their verdicts
        // is then part of the page cache identifier hash: When a condition on a page creates a different result, the hash
        // is different from an existing page cache entry and a new one is created later.
        $setupConditionIncludeListCacheIdentifier = 'setup-condition-include-list-' . sha1($serializedSysTemplateRows . $serializedConstantConditionList);
        $setupConditionList = [];
        $gotSetupConditionsFromCache = false;
        if (!$this->no_cache && $setupConditionIncludeTree = $typoscriptCache->require($setupConditionIncludeListCacheIdentifier)) {
            // We got the flat list of all setup conditions for this TypoScript combination from cache. Good. We traverse
            // this list to calculate "current" condition verdicts, which we need as hash to be part of page cache identifier.
            $includeTreeTraverserVisitors = [];
            $setupConditionConstantSubstitutionVisitor = new IncludeTreeSetupConditionConstantSubstitutionVisitor();
            $setupConditionConstantSubstitutionVisitor->setFlattenedConstants($flatConstants);
            $includeTreeTraverserVisitors[] = $setupConditionConstantSubstitutionVisitor;
            $setupMatcherVisitor = GeneralUtility::makeInstance(IncludeTreeConditionMatcherVisitor::class);
            $setupMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
            $includeTreeTraverserVisitors[] = $setupMatcherVisitor;
            // It does not matter if we use IncludeTreeTraverser or ConditionVerdictAwareIncludeTreeTraverser here:
            // Condition list is flat, not nested. IncludeTreeTraverser has an if() less, so we use that one.
            $includeTreeTraverser->traverse($setupConditionIncludeTree, $includeTreeTraverserVisitors);
            $setupConditionList = $setupMatcherVisitor->getConditionListWithVerdicts();
            $gotSetupConditionsFromCache = true;
        }
        if ($this->no_cache || !$gotSetupConditionsFromCache) {
            // We did not get setup condition list from cache, or are not allowed to use cache. We have to build setup
            // condition list from scratch. This means we'll fetch the full setup include tree (from cache if possible),
            // register the constant substitution visitor, and register condition matcher and register the condition
            // accumulator visitor.
            if ($this->no_cache) {
                // Note $typoscriptCache *is not* hand over here: IncludeTree is calculated from scratch, we're not allowed to use cache.
                $setupIncludeTree = $treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, $tokenizer, $site);
            } else {
                // Note $typoscriptCache *is* hand over here, we can potentially grab the fully cached includeTree here, or cache entry will be created.
                $setupIncludeTree = $treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, $tokenizer, $site, $typoscriptCache);
            }
            $includeTreeTraverserVisitors = [];
            $setupConditionConstantSubstitutionVisitor = new IncludeTreeSetupConditionConstantSubstitutionVisitor();
            $setupConditionConstantSubstitutionVisitor->setFlattenedConstants($flatConstants);
            $includeTreeTraverserVisitors[] = $setupConditionConstantSubstitutionVisitor;
            $setupMatcherVisitor = GeneralUtility::makeInstance(IncludeTreeConditionMatcherVisitor::class);
            $setupMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
            $includeTreeTraverserVisitors[] = $setupMatcherVisitor;
            $setupConditionIncludeListAccumulatorVisitor = new IncludeTreeConditionIncludeListAccumulatorVisitor();
            $includeTreeTraverserVisitors[] = $setupConditionIncludeListAccumulatorVisitor;
            // It is important we use IncludeTreeTraverser here: We to have the condition verdicts of *all* conditions, plus
            // want to accumulate all of them. The ConditionVerdictAwareIncludeTreeTraverser wouldn't walk into nested
            // conditions if an upper one does not match.
            $includeTreeTraverser->traverse($setupIncludeTree, $includeTreeTraverserVisitors);
            $setupConditionList = $setupMatcherVisitor->getConditionListWithVerdicts();
            if (!$this->no_cache) {
                $typoscriptCache->set($setupConditionIncludeListCacheIdentifier, 'return unserialize(\'' . addcslashes(serialize($setupConditionIncludeListAccumulatorVisitor->getConditionIncludes()), '\'\\') . '\');');
            }
        }

        // We now gathered everything to calculate the page cache identifier: It depends on sys_template rows, the calculated
        // constant condition verdicts, the setup condition verdicts, plus various not TypoScript related details like
        // obviously the page id.
        $this->lock = GeneralUtility::makeInstance(ResourceMutex::class);
        $this->newHash = $this->createHashBase($sysTemplateRows, $constantConditionList, $setupConditionList);
        if (!$this->no_cache) {
            if ($this->shouldAcquireCacheData($request)) {
                // Try to get a page cache row.
                $this->getTimeTracker()->push('Cache Row');
                $pageCacheRow = $this->pageCache->get($this->newHash);
                if (!is_array($pageCacheRow)) {
                    // Nothing in the cache, we acquire an exclusive lock now.
                    // There are two scenarios when locking: We're either the first process acquiring this lock. This means we'll
                    // "immediately" get it and can continue with page rendering. Or, another process acquired the lock already. In
                    // this case, the below call will wait until the lock is released again. The other process then probably wrote
                    // a page cache entry, which we can use.
                    // To handle the second case - if our process had to wait for another one creating the content for us - we
                    // simply query the page cache again to see if there is a page cache now.
                    $hadToWaitForLock = $this->lock->acquireLock('pages', $this->newHash);
                    // From this point on we're the only one working on that page.
                    if ($hadToWaitForLock) {
                        // Query the cache again to see if the data is there meanwhile: We did not get the lock
                        // immediately, chances are high the other process created a page cache for us.
                        // There is a small chance the other process actually pageCache->set() the content,
                        // but pageCache->get() still returns false, for instance when a database returned "done"
                        // for the INSERT, but SELECT still does not return the new row - may happen in multi-head
                        // DB instances, and with some other distributed cache backends as well. The worst that
                        // can happen here is the page generation is done too often, which we accept as trade-off.
                        $pageCacheRow = $this->pageCache->get($this->newHash);
                        if (is_array($pageCacheRow)) {
                            // We have the content, some other process did the work for us, release our lock again.
                            $this->releaseLocks();
                        }
                    }
                    // We keep the lock set, because we are the ones generating the page now and filling the cache.
                    // This indicates that we have to release the lock later in releaseLocks()!
                }
                if (is_array($pageCacheRow)) {
                    // Note this especially populates $this->config!
                    $this->populatePageDataFromCache($pageCacheRow);
                }
                $this->getTimeTracker()->pull();
            } else {
                // User forced page cache rebuilding. Get a lock for the page content so other processes can't interfere.
                $this->lock->acquireLock('pages', $this->newHash);
            }
        } else {
            // Caching is not allowed. We'll rebuild the page. Lock this.
            $this->lock->acquireLock('pages', $this->newHash);
        }

        $forceTemplateParsing = $this->context->getPropertyFromAspect('typoscript', 'forcedTemplateParsing');
        if ($this->no_cache || empty($this->config) || $this->isINTincScript() || $forceTemplateParsing) {
            // We don't need the full setup AST in many cached scenarios. However, if no_cache is set, if no page cache
            // entry could be loaded, if the page cache entry has _INT object, or if the user forced template
            // parsing (adminpanel), then we still need the full setup AST. If there is "just" an _INT object, we can
            // use a possible cache entry for the setup AST, which speeds up _INT parsing quite a bit. In other cases
            // we calculate full setup AST and cache it if allowed.
            $setupTypoScriptCacheIdentifier = 'setup-' . sha1($serializedSysTemplateRows . $serializedConstantConditionList . serialize($setupConditionList));
            $gotSetupFromCache = false;
            $setupArray = [];
            if (!$this->no_cache && !$forceTemplateParsing) {
                // We need AST, but we are allowed to potentially get it from cache.
                if ($setupTypoScriptCache = $typoscriptCache->require($setupTypoScriptCacheIdentifier)) {
                    $frontendTypoScript->setSetupTree($setupTypoScriptCache['ast']);
                    $setupArray = $setupTypoScriptCache['array'];
                    $gotSetupFromCache = true;
                }
            }
            if ($this->no_cache || $forceTemplateParsing || !$gotSetupFromCache) {
                // We need AST and couldn't get it from cache or are now allowed to. We thus need the full setup
                // IncludeTree, which we can get from cache again if allowed, or is calculated a-new if not.
                if ($this->no_cache || $forceTemplateParsing) {
                    // Note $typoscriptCache *is not* hand over here: IncludeTree is calculated from scratch, we're not allowed to use cache.
                    $setupIncludeTree = $treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, $tokenizer, $site);
                } else {
                    // Note $typoscriptCache *is* hand over here, we can potentially grab the fully cached includeTree here, or cache entry will be created.
                    $setupIncludeTree = $treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, $tokenizer, $site, $typoscriptCache);
                }
                $includeTreeTraverserConditionVerdictAwareVisitors = [];
                $setupConditionConstantSubstitutionVisitor = new IncludeTreeSetupConditionConstantSubstitutionVisitor();
                $setupConditionConstantSubstitutionVisitor->setFlattenedConstants($flatConstants);
                $includeTreeTraverserConditionVerdictAwareVisitors[] = $setupConditionConstantSubstitutionVisitor;
                $setupMatcherVisitor = GeneralUtility::makeInstance(IncludeTreeConditionMatcherVisitor::class);
                $setupMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
                $includeTreeTraverserConditionVerdictAwareVisitors[] = $setupMatcherVisitor;
                $setupAstBuilderVisitor = GeneralUtility::makeInstance(IncludeTreeAstBuilderVisitor::class);
                $setupAstBuilderVisitor->setFlatConstants($flatConstants);
                $includeTreeTraverserConditionVerdictAwareVisitors[] = $setupAstBuilderVisitor;
                $includeTreeTraverserConditionVerdictAware->traverse($setupIncludeTree, $includeTreeTraverserConditionVerdictAwareVisitors);
                $setupAst = $setupAstBuilderVisitor->getAst();
                $frontendTypoScript->setSetupTree($setupAst);

                // Create top-level setup AST 'types' node from all top-level PAGE objects.
                // This is essentially a preparation for type-lookup below and should vanish later.
                $typesNode = new ChildNode('types');
                $gotTypeNumZero = false;
                foreach ($setupAst->getNextChild() as $setupChild) {
                    if ($setupChild->getValue() === 'PAGE') {
                        $typeNumChild = $setupChild->getChildByName('typeNum');
                        if ($typeNumChild) {
                            $typeNumValue = $typeNumChild->getValue();
                            $typesSubNode = new ChildNode($typeNumValue);
                            $typesSubNode->setValue($setupChild->getName());
                            $typesNode->addChild($typesSubNode);
                            if ($typeNumValue === '0') {
                                $gotTypeNumZero = true;
                            }
                        } elseif (!$gotTypeNumZero) {
                            // The first PAGE node that has no typeNum = 0 is considered '0' automatically.
                            $typesSubNode = new ChildNode('0');
                            $typesSubNode->setValue($setupChild->getName());
                            $typesNode->addChild($typesSubNode);
                            $gotTypeNumZero = true;
                        }
                    }
                }
                if ($typesNode->hasChildren()) {
                    $setupAst->addChild($typesNode);
                }
                $setupArray = $setupAst->toArray();
                if (!$this->no_cache && !$forceTemplateParsing) {
                    // Write cache entry for AST and its array representation, we're allowed to do it.
                    $typoscriptCache->set($setupTypoScriptCacheIdentifier, 'return unserialize(\'' . addcslashes(serialize(['ast' => $setupAst, 'array' => $setupArray]), '\'\\') . '\');');
                }
            }

            $typoScriptPageTypeName = $setupArray['types.'][$this->type] ?? '';
            $this->pSetup = $setupArray[$typoScriptPageTypeName . '.'] ?? '';

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
            }

            if (!isset($this->config['config'])) {
                $this->config['config'] = [];
            }
            // Filling the config-array, first with the main "config." part
            if (is_array($setupArray['config.'] ?? null)) {
                // @todo: These operations should happen on AST instead and array is exported (and cached) afterwards
                $setupArray['config.'] = array_replace_recursive($setupArray['config.'], $this->config['config']);
                $this->config['config'] = $setupArray['config.'];
            }
            // Override it with the page/type-specific "config."
            if (is_array($this->pSetup['config.'] ?? null)) {
                $this->config['config'] = array_replace_recursive($this->config['config'], $this->pSetup['config.']);
            }
            $this->config['rootLine'] = $localRootline;
            $frontendTypoScript->setSetupArray($setupArray);

            // @deprecated: since v12, will be removed in v13: b/w compat. Remove when TemplateService is dropped.
            $this->tmpl->setup = $setupArray;
            $this->tmpl->loaded = true;
            $this->tmpl->flatSetup = $flatConstants;
        }

        // Set $this->no_cache TRUE if the config.no_cache value is set!
        if (!$this->no_cache && ($this->config['config']['no_cache'] ?? false)) {
            $this->set_no_cache('config.no_cache is set', true);
        }

        // Auto-configure settings when a site is configured
        $this->config['config']['absRefPrefix'] = $this->config['config']['absRefPrefix'] ?? 'auto';

        // Hook for postProcessing the configuration array
        $params = ['config' => &$this->config['config']];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'] ?? [] as $funcRef) {
            GeneralUtility::callUserFunction($funcRef, $params, $this);
        }

        return $request->withAttribute('frontend.typoscript', $frontendTypoScript);
    }

    /**
     * This method properly sets the values given from the pages cache into the corresponding
     * TSFE variables. The counterpart is setPageCacheContent() where all relevant information is fetched.
     * This also contains all data that could be cached, even for pages that are partially cached, as they
     * have non-cacheable content still to be rendered.
     *
     * @see getFromCache()
     * @see setPageCacheContent()
     * @internal
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
        // Getting the content type
        $this->contentType = $cachedData['contentType'] ?? $this->contentType;
        // Setting flag, so we know, that some cached content has been loaded
        $this->pageContentWasLoadedFromCache = true;
        $this->cacheExpires = $cachedData['expires'];
        // Restore the current tags as they can be retrieved by getPageCacheTags()
        $this->pageCacheTags = $cachedData['cacheTags'] ?? [];

        if (isset($this->config['config']['debug'])) {
            $debugCacheTime = (bool)$this->config['config']['debug'];
        } else {
            $debugCacheTime = !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
        }
        if ($debugCacheTime) {
            $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
            $timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
            $this->debugInformationHeader = 'Cached page generated ' . date($dateFormat . ' ' . $timeFormat, $cachedData['tstamp'])
                . '. Expires ' . date($dateFormat . ' ' . $timeFormat, $cachedData['expires']);
        }
    }

    /**
     * Detecting if shift-reload has been clicked.
     * This option will have no effect if re-generation of page happens by other reasons (for instance that the page is not in cache yet).
     * Also, a backend user MUST be logged in for the shift-reload to be detected due to DoS-attack-security reasons.
     *
     * @return bool If shift-reload in client browser has been clicked, disable getting cached page and regenerate the page content.
     * @internal
     */
    protected function shouldAcquireCacheData(ServerRequestInterface $request): bool
    {
        // Trigger event for possible by-pass of requiring of page cache (for re-caching purposes)
        $event = new ShouldUseCachedPageDataIfAvailableEvent($request, $this, !$this->no_cache);
        GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
        return $event->shouldUseCachedPageData();
    }

    /**
     * This creates a hash used as page cache entry identifier and as page generation lock.
     * When multiple requests try to render the same page that will result in the same page cache entry,
     * this lock allows creation by one request which typically puts the result into page cache, while
     * the other requests wait until this finished and re-use the result.
     *
     * This hash is unique to the TS template and constant and setup condition verdict,
     * the variables ->id, ->type, list of frontend user groups, ->MP (Mount Points) and cHash array.
     *
     * @return string Page cache entry identifier also used as page generation lock
     */
    protected function createHashBase(array $sysTemplateRows, array $constantConditionList, array $setupConditionList): string
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
            'sysTemplateRows' => $sysTemplateRows,
            'constantConditionList' => $constantConditionList,
            'setupConditionList' => $setupConditionList,
        ];
        // Call hook to influence the hash calculation
        $_params = [
            'hashParameters' => &$hashParameters,
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }
        return $this->id . '_' . sha1(serialize($hashParameters));
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
     * @internal
     */
    protected function settingLanguage(ServerRequestInterface $request)
    {
        // Get values from site language
        $languageAspect = LanguageAspectFactory::createFromSiteLanguage($this->language);

        $languageId = $languageAspect->getId();
        $languageContentId = $languageAspect->getContentId();

        $pageTranslationVisibility = new PageTranslationVisibility((int)($this->page['l18n_cfg'] ?? 0));
        // If the incoming language is set to another language than default
        if ($languageAspect->getId() > 0) {
            // Request the translation for the requested language
            $olRec = $this->sys_page->getPageOverlay($this->page, $languageAspect);
            $overlaidLanguageId = (int)($olRec['sys_language_uid'] ?? 0);
            if ($overlaidLanguageId !== $languageAspect->getId()) {
                // If requested translation is not available
                if ($pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()) {
                    $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'Page is not available in the requested language.',
                        ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE]
                    );
                    throw new PropagateResponseException($response, 1533931388);
                }
                switch ($languageAspect->getLegacyLanguageMode()) {
                    case 'strict':
                        $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                            $request,
                            'Page is not available in the requested language (strict).',
                            ['code' => PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE_STRICT_MODE]
                        );
                        throw new PropagateResponseException($response, 1533931395);
                    case 'content_fallback':
                        // Setting content uid (but leaving the sys_language_uid) when a content_fallback
                        // value was found.
                        foreach ($languageAspect->getFallbackChain() as $orderValue) {
                            if ($orderValue === '0' || $orderValue === 0 || $orderValue === '') {
                                $languageContentId = 0;
                                break;
                            }
                            if (MathUtility::canBeInterpretedAsInteger($orderValue) && $overlaidLanguageId === (int)$orderValue) {
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

            // Setting the $this->page if an overlay record was found (which it is only if a language is used)
            // Doing this ensures that page properties like the page title are resolved in the correct language
            $this->page = $olRec;
        }

        // Set the language aspect
        $this->context->setAspect('language', $languageAspect);

        // Setting sys_language_uid inside sys-page by creating a new page repository
        $this->sys_page = GeneralUtility::makeInstance(PageRepository::class, $this->context);
        // If default language is not available
        if ((!$languageAspect->getContentId() || !$languageAspect->getId())
            && $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage()
        ) {
            $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page is not available in default language.',
                ['code' => PageAccessFailureReasons::LANGUAGE_DEFAULT_NOT_AVAILABLE]
            );
            throw new PropagateResponseException($response, 1533931423);
        }

        if ($languageAspect->getId() > 0) {
            $this->updateRootLinesWithTranslations();
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
            'addQueryString' => 'untrusted',
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
     * Sets cache content; Inserts the content string into the pages cache.
     *
     * @param string $content The content to store in the HTML field of the cache table
     * @param array $data The additional cache_data array, fx. $this->config
     * @param int $expirationTstamp Expiration timestamp
     * @see populatePageDataFromCache()
     */
    protected function setPageCacheContent(string $content, array $data, int $expirationTstamp): array
    {
        $cacheData = [
            'page_id' => $this->id,
            'content' => $content,
            'contentType' => $this->contentType,
            'cache_data' => $data,
            'expires' => $expirationTstamp,
            'tstamp' => $GLOBALS['EXEC_TIME'],
        ];
        $this->cacheExpires = $expirationTstamp;
        $this->pageCacheTags[] = 'pageId_' . $this->id;
        // Respect the page cache when content of pid is shown
        if ($this->id !== $this->contentPid) {
            $this->pageCacheTags[] = 'pageId_' . $this->contentPid;
        }
        if (!empty($this->page['cache_tags'])) {
            $tags = GeneralUtility::trimExplode(',', $this->page['cache_tags'], true);
            $this->pageCacheTags = array_merge($this->pageCacheTags, $tags);
        }
        $this->pageCacheTags = array_unique($this->pageCacheTags);
        // Add the cache themselves as well, because they are fetched by getPageCacheTags()
        $cacheData['cacheTags'] = $this->pageCacheTags;
        $this->pageCache->set($this->newHash, $cacheData, $this->pageCacheTags, $expirationTstamp - $GLOBALS['EXEC_TIME']);
        return $cacheData;
    }

    /**
     * Clears cache content (for $this->newHash)
     *
     * @internal
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
        if ($this->page['SYS_LASTCHANGED'] < (int)($this->register['SYS_LASTCHANGED'] ?? 0)) {
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
     * Adds tags to this page's cache entry, you can then f.e. remove cache
     * entries by tag
     *
     * @param array $tags An array of tag
     */
    public function addCacheTags(array $tags)
    {
        $this->pageCacheTags = array_merge($this->pageCacheTags, $tags);
    }

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
        // @deprecated: these properties can be removed in TYPO3 v13.0
        $this->baseUrl = (string)($this->config['config']['baseURL'] ?? '');
        // Internal and External target defaults
        $this->intTarget = (string)($this->config['config']['intTarget'] ?? '');
        $this->extTarget = (string)($this->config['config']['extTarget'] ?? '');
        $this->fileTarget = (string)($this->config['config']['fileTarget'] ?? '');
        if (($this->config['config']['spamProtectEmailAddresses'] ?? '') === 'ascii') {
            $this->logDeprecatedTyposcript('config.spamProtectEmailAddresses = ascii', 'This setting has no effect anymore. Change it to a number between -10 and 10 or remove it completely');
            $this->config['config']['spamProtectEmailAddresses'] = 0;
        }
        // @deprecated: these properties can be removed in TYPO3 v13.0
        $this->spamProtectEmailAddresses = (int)($this->config['config']['spamProtectEmailAddresses'] ?? 0);
        $this->spamProtectEmailAddresses = MathUtility::forceIntegerInRange($this->spamProtectEmailAddresses, -10, 10, 0);
        // calculate the absolute path prefix
        if (!empty($this->absRefPrefix = trim($this->config['config']['absRefPrefix'] ?? ''))) {
            if ($this->absRefPrefix === 'auto') {
                $normalizedParams = $request->getAttribute('normalizedParams');
                $this->absRefPrefix = $normalizedParams->getSitePath();
            }
        }
        // config.forceAbsoluteUrls will override absRefPrefix
        if ($this->config['config']['forceAbsoluteUrls'] ?? false) {
            $normalizedParams = $request->getAttribute('normalizedParams');
            $this->absRefPrefix = $normalizedParams->getSiteUrl();
        }

        // linkVars
        $this->calculateLinkVars($request->getQueryParams());
        // Setting XHTML-doctype from doctype
        if (isset($this->config['config']['xhtmlDoctype']) && !isset($this->config['config']['doctype'])) {
            $this->logDeprecatedTyposcript('config.xhtmlDoctype', 'config.xhtmlDoctype will be removed in favor of config.doctype');
        }
        $this->config['config']['xhtmlDoctype'] = $this->config['config']['xhtmlDoctype'] ?? $this->config['config']['doctype'] ?? '';
        // We need to set the doctype to "something defined" otherwise (because this method is called also during USER_INT renderings)
        // we might have xhtmlDoctype set but doctype isn't and we get a deprecation again (even if originally neither one of them was set)
        $this->config['config']['doctype'] ??= $this->config['config']['xhtmlDoctype'];
        $docType = DocType::createFromConfigurationKey($this->config['config']['doctype']);
        $this->xhtmlDoctype = $docType->getXhtmlDocType();
        $this->xhtmlVersion = $docType->getXhtmlVersion();
        $this->pageRenderer->setDocType($docType);

        // Global content object
        $this->newCObj($request);
        $this->getTimeTracker()->pull();
    }

    /**
     * Does processing of the content after the page content was generated.
     *
     * This includes caching the page, indexing the page (if configured) and setting sysLastChanged
     */
    public function generatePage_postProcessing(ServerRequestInterface $request)
    {
        $this->setAbsRefPrefix();
        // This is to ensure, that the page is NOT cached if the no_cache parameter was set before the page was generated.
        // This is a safety precaution, as it could have been unset by some script.
        if ($this->no_cacheBeforePageGen) {
            $this->set_no_cache('no_cache has been set before the page was generated - safety check', true);
        }
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $event = new AfterCacheableContentIsGeneratedEvent($request, $this, $this->newHash, !$this->no_cache);
        $event = $eventDispatcher->dispatch($event);

        // Processing if caching is enabled
        if ($event->isCachingEnabled()) {
            // Seconds until a cached page is too old
            $cacheTimeout = $this->get_cache_timeout();
            $timeOutTime = $GLOBALS['EXEC_TIME'] + $cacheTimeout;
            // Write the page to cache
            $cachedInformation = $this->setPageCacheContent($this->content, $this->config, $timeOutTime);

            // Event for cache post processing (eg. writing static files)
            $event = new AfterCachedPageIsPersistedEvent($request, $this, $this->newHash, $cachedInformation, $cacheTimeout);
            $eventDispatcher->dispatch($event);
        }
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

        $titleTagContent = $this->printTitle(
            $pageTitle,
            (bool)($this->config['config']['noPageTitle'] ?? false),
            (bool)($this->config['config']['pageTitleFirst'] ?? false),
            $pageTitleSeparator,
            (bool)($this->config['config']['showWebsiteTitle'] ?? true)
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
     * @param bool $noPageTitle If set, the page title will not be printed
     * @param bool $showPageTitleFirst If set, website title and page title are swapped
     * @param string $pageTitleSeparator an alternative to the ": " as the separator between site title and page title
     * @param bool $showWebsiteTitle If set, the website title will be printed
     * @return string The page title on the form "[website title]: [input-title]". Not htmlspecialchar()'ed.
     * @see generatePageTitle()
     */
    protected function printTitle(string $pageTitle, bool $noPageTitle = false, bool $showPageTitleFirst = false, string $pageTitleSeparator = '', bool $showWebsiteTitle = true): string
    {
        $websiteTitle = $showWebsiteTitle ? $this->getWebsiteTitle() : '';
        $pageTitle = $noPageTitle ? '' : $pageTitle;
        // only show a separator if there are both site title and page title
        if ($pageTitle === '' || $websiteTitle === '') {
            $pageTitleSeparator = '';
        } elseif (empty($pageTitleSeparator)) {
            // use the default separator if non given
            $pageTitleSeparator = ': ';
        }
        if ($showPageTitleFirst) {
            return $pageTitle . $pageTitleSeparator . $websiteTitle;
        }
        return $websiteTitle . $pageTitleSeparator . $pageTitle;
    }

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
            $pageRendererState = unserialize($this->config['INTincScript_ext']['pageRendererState'], ['allowed_classes' => [Locale::class]]);
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
                if (is_array($nonCacheableData[$nonCacheableKey] ?? false)) {
                    $label = 'Include ' . $nonCacheableData[$nonCacheableKey]['type'];
                    $timeTracker->push($label);
                    $nonCacheableContent = '';
                    $contentObjectRendererForNonCacheable = unserialize($nonCacheableData[$nonCacheableKey]['cObj']);
                    if ($contentObjectRendererForNonCacheable instanceof ContentObjectRenderer) {
                        $contentObjectRendererForNonCacheable->setRequest($request);
                        $nonCacheableContent = match ($nonCacheableData[$nonCacheableKey]['type']) {
                            'COA' => $contentObjectRendererForNonCacheable->cObjGetSingle('COA', $nonCacheableData[$nonCacheableKey]['conf']),
                            'FUNC' => $contentObjectRendererForNonCacheable->cObjGetSingle('USER', $nonCacheableData[$nonCacheableKey]['conf']),
                            'POSTUSERFUNC' => $contentObjectRendererForNonCacheable->callUserFunction($nonCacheableData[$nonCacheableKey]['postUserFunc'], $nonCacheableData[$nonCacheableKey]['conf'], $nonCacheableData[$nonCacheableKey]['content']),
                            default => '',
                        };
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
        // invokes permanent, general handlers
        foreach ($nonCacheableData as $item) {
            if (empty($item['permanent']) || empty($item['target'])) {
                continue;
            }
            $parameters = array_merge($item['parameters'] ?? [], ['content' => $this->content]);
            $this->content = GeneralUtility::callUserFunction($item['target'], $parameters) ?? $this->content;
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
     */
    public function applyHttpHeadersToResponse(ResponseInterface $response): ResponseInterface
    {
        $response = $response->withHeader('Content-Type', $this->contentType);
        // Set header for content language unless disabled
        $contentLanguage = (string)$this->language->getLocale();
        if (empty($this->config['config']['disableLanguageHeader'])) {
            $response = $response->withHeader('Content-Language', $contentLanguage);
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
        $this->cObj->setRequest($request);
        $this->cObj->start($this->page, 'pages');
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
        $encodedAbsRefPrefix = htmlspecialchars($this->absRefPrefix, ENT_QUOTES | ENT_HTML5);
        $search = [
            '"_assets/',
            '"typo3temp/',
            '"' . PathUtility::stripPathSitePrefix(Environment::getExtensionsPath()) . '/',
            '"' . PathUtility::stripPathSitePrefix(Environment::getFrameworkBasePath()) . '/',
        ];
        $replace = [
            '"' . $encodedAbsRefPrefix . '_assets/',
            '"' . $encodedAbsRefPrefix . 'typo3temp/',
            '"' . $encodedAbsRefPrefix . PathUtility::stripPathSitePrefix(Environment::getExtensionsPath()) . '/',
            '"' . $encodedAbsRefPrefix . PathUtility::stripPathSitePrefix(Environment::getFrameworkBasePath()) . '/',
        ];
        // Process additional directories
        $directories = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'], true);
        foreach ($directories as $directory) {
            $search[] = '"' . $directory;
            $replace[] = '"' . $encodedAbsRefPrefix . $directory;
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
     * @param bool $internal used for TYPO3 Core to avoid deprecation errors in v12 when calling this method directly.
     * @return string Processed input value.
     * @internal only for TYPO3 Core internal purposes. Might be removed at a later point as it was related to RealURL functionality.
     * @deprecated will be removed in TYPO3 v13.0 along with config.baseURL
     */
    public function baseUrlWrap($url, bool $internal = false)
    {
        if (!$internal) {
            trigger_error('Calling $TSFE->baseUrlWrap will not work anymore in TYPO3 v13.0. Use SiteHandling and config.forceAbsoluteUrls anymore, or build your own <base> tag via TypoScript headerData.', E_USER_DEPRECATED);
        }
        if ($this->config['config']['baseURL'] ?? false) {
            $urlParts = parse_url($url);
            if (empty($urlParts['scheme']) && $url[0] !== '/') {
                $url = $this->config['config']['baseURL'] . $url;
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
     * @deprecated since TYPO3 v12, will be removed in v13. Frontend should typically not depend on Backend TsConfig.
     *             If really needed, use PageTsConfigFactory, see usage in DatabaseRecordLinkBuilder.
     *             Remove together with class PageTsConfig.
     */
    public function getPagesTSconfig(): array
    {
        trigger_error('Method getPagesTSconfig() is deprecated since TYPO3 v12 and will be removed with TYPO3 v13.0.', E_USER_DEPRECATED);
        if (!is_array($this->pagesTSconfig)) {
            $matcher = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher::class, $this->context, $this->id, $this->rootLine);
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
     * @param bool $internalRequest Whether the request is internal or not (true should only be used by core calls).
     */
    public function set_no_cache($reason = '', $internalRequest = false)
    {
        $warning = '';
        $context = [];
        if ($reason !== '') {
            $warning = '$TSFE->set_no_cache() was triggered. Reason: {reason}.';
            $context['reason'] = $reason;
        } else {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            if (isset($trace[0]['class'])) {
                $context['class'] = $trace[0]['class'];
                $warning = '$GLOBALS[\'TSFE\']->set_no_cache() was triggered by {class} on line {line}.';
            }
            if (isset($trace[0]['function'])) {
                $context['function'] = $trace[0]['function'];
                $warning = '$GLOBALS[\'TSFE\']->set_no_cache() was triggered by {class}->{function} on line {line}.';
            }
            if ($context === []) {
                // Only store the filename, not the full path for safety reasons
                $context['file'] = basename($trace[0]['file']);
                $warning = '$GLOBALS[\'TSFE\']->set_no_cache() was triggered by {file} on line {line}.';
            }
            $context['line'] = $trace[0]['line'];
        }
        if (!$internalRequest && $GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter']) {
            $warning .= ' However, $TYPO3_CONF_VARS[\'FE\'][\'disableNoCacheParameter\'] is set, so it will be ignored!';
            $this->getTimeTracker()->setTSlogMessage($warning, LogLevel::NOTICE);
        } else {
            $warning .= ' Caching is disabled!';
            $this->disableCache();
        }
        $this->logger->notice($warning, $context);
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
        if ($this->languageService === null) {
            $this->languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromSiteLanguage($this->language);
        }
        return $this->languageService->sL($input);
    }

    /**
     * Returns the originally requested page uid when TSFE was instantiated initially.
     */
    public function getRequestedId(): int
    {
        return $this->requestedId;
    }

    /**
     * Release the page specific lock.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @internal
     */
    public function releaseLocks(): void
    {
        $this->lock?->releaseLock('pages');
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
