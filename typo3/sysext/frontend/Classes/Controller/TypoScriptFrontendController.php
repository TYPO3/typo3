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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Error\Http\AbstractServerErrorException;
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
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
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
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent;
use TYPO3\CMS\Frontend\Event\ModifyTypoScriptConstantsEvent;
use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

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

    /**
     * The page id (int).
     *
     * Read-only! Extensions may read but never write this property!
     */
    public int $id;

    protected Site $site;
    protected SiteLanguage $language;
    protected PageArguments $pageArguments;

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
     * Read-only! Extensions may read but never write this property!
     *
     * @var array<int, array<string, mixed>>
     */
    public array $rootLine = [];

    /**
     * The page record.
     *
     * Read-only! Extensions may read but never write this property!
     */
    public ?array $page = [];

    /**
     * This will normally point to the same value as id, but can be changed to
     * point to another page from which content will then be displayed instead.
     *
     * Read-only! Extensions may read but never write this property!
     */
    public int $contentPid = 0;

    /**
     * Read-only! Extensions may read but never write this property!
     */
    public ?PageRepository $sys_page = null;

    /**
     * @internal
     */
    public string $MP = '';

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
     * 'INTincScript': (internal) List of INT instructions
     * 'INTincScript_ext': (internal) Further state for INT instructions
     *
     * Read-only! Extensions may read but never write this property!
     *
     * @var array<string, mixed>
     */
    public array $config = [];

    /**
     * Is set to the time-to-live time of cached pages. Default is 60*60*24, which is 24 hours.
     */
    protected int $cacheTimeOutDefault = 0;

    /**
     * Set if cached content was fetched from the cache.
     */
    protected bool $pageContentWasLoadedFromCache = false;

    /**
     * Set to the expiry time of cached content
     */
    protected int $cacheExpires = 0;

    /**
     * This hash is unique to the page id, involved TS templates, TS condition verdicts, and
     * some other parameters that influence page render result. Used to get/set page cache.
     * @internal
     */
    public string $newHash = '';

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
     * @internal
     */
    public array $additionalHeaderData = [];

    /**
     * Used to accumulate additional HTML-code for the footer-section of the template
     * @internal
     */
    public array $additionalFooterData = [];

    /**
     * Absolute Reference prefix
     *
     * Read-only! Extensions may read but never write this property!
     */
    public string $absRefPrefix = '';

    /**
     * @internal
     */
    public array $register = [];

    /**
     * Stack used for storing array and retrieving register arrays.
     * See LOAD_REGISTER and RESTORE_REGISTER.
     * @internal
     */
    public array $registerStack = [];

    /**
     * Used by RecordContentObject and ContentContentObject to ensure the a records is NOT
     * rendered twice through it!
     *
     * @internal
     */
    public array $recordRegister = [];

    /**
     * This is set to the [table]:[uid] of the latest record rendered. Note that
     * class ContentObjectRenderer has an equal value, but that is pointing to the
     * record delivered in the $data-array of the ContentObjectRenderer instance, if
     * the cObjects CONTENT or RECORD created that instance
     *
     * @internal
     */
    public string $currentRecord = '';

    /**
     * Used to generate page-unique keys. Point is that uniqid() functions is very
     * slow, so a unique key is made based on this, see function uniqueHash()
     */
    protected int $uniqueCounter = 0;

    protected string $uniqueString = '';

    /**
     * Page content render object
     *
     * Read-only! Extensions may read but never write this property!
     */
    public ContentObjectRenderer $cObj;

    /**
     * All page content is accumulated in this variable. See RequestHandler
     * @internal
     */
    public string $content = '';

    /**
     * Internal calculations for labels
     */
    protected ?LanguageService $languageService = null;

    /**
     * @internal Internal locking. May move to a middleware soon.
     */
    public ?ResourceMutex $lock = null;

    protected ?PageRenderer $pageRenderer = null;
    protected FrontendInterface $pageCache;
    protected array $pageCacheTags = [];

    /**
     * Content type HTTP header being sent in the request.
     * @todo Ticket: #63642 Should be refactored to a request/response model later
     */
    protected string $contentType = 'text/html; charset=utf-8';

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
     * @internal Extensions should usually not need to create own instances of TSFE
     */
    public function __construct(Context $context, Site $site, SiteLanguage $siteLanguage, PageArguments $pageArguments)
    {
        $this->context = $context;
        $this->site = $site;
        $this->language = $siteLanguage;
        $this->pageArguments = $pageArguments;
        $this->id = $pageArguments->getPageId();
        $this->uniqueString = md5(microtime());
        $this->initPageRenderer();
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $this->pageCache = $cacheManager->getCache('pages');
    }

    protected function initPageRenderer(): void
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
     * @internal Must only be used by TYPO3 core
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
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

        $site = $this->getSite();
        $isCachingAllowed = $request->getAttribute('frontend.cache.instruction')->isCachingAllowed();

        $tokenizer = new LossyTokenizer();
        $treeBuilder = GeneralUtility::makeInstance(SysTemplateTreeBuilder::class);
        $includeTreeTraverser = new IncludeTreeTraverser();
        $includeTreeTraverserConditionVerdictAware = new ConditionVerdictAwareIncludeTreeTraverser();
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        /** @var PhpFrontend|null $typoscriptCache */
        $typoscriptCache = null;
        if ($isCachingAllowed) {
            // disableCache() might have been called by earlier middlewares. This means we don't do fancy cache
            // stuff, calculate full TypoScript and don't get() from nor set() to typoscript and page cache.
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
        if ($isCachingAllowed && $constantConditionIncludeTree = $typoscriptCache->require($constantConditionIncludeListCacheIdentifier)) {
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
        if (!$isCachingAllowed || !$gotConstantFromCache) {
            // We did not get constants from cache, or are not allowed to use cache. We have to build constants from scratch.
            // This means we'll fetch the full constants include tree (from cache if possible), register the condition
            // matcher and register the AST builder and traverse include tree to retrieve constants AST and calculate
            // 'flat constants' from it. Both are cached if allowed afterwards for the 'if' above to kick in next time.
            $constantIncludeTree = $treeBuilder->getTreeBySysTemplateRowsAndSite('constants', $sysTemplateRows, $tokenizer, $site, $typoscriptCache);
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
            // @internal Dispatch an experimental event allowing listeners to still change the constants AST,
            //           to for instance implement nested constants if really needed. Note this event may change
            //           or vanish later without further notice.
            $constantsAst = GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(new ModifyTypoScriptConstantsEvent($constantsAst))->getConstantsAst();
            $flatConstants = $constantsAst->flatten();
            if ($isCachingAllowed) {
                // We are allowed to cache and can create both the full list of conditions, plus the constant AST and flat constant
                // list cache entry. To do that, we need all (!) conditions, but the above ConditionVerdictAwareIncludeTreeTraverser
                // did not find nested conditions if an upper condition did not match. We thus have to traverse include tree a
                // second time with the IncludeTreeTraverser that does traverse into not matching conditions as well.
                $includeTreeTraverserVisitors = [];
                $conditionMatcherVisitor = GeneralUtility::makeInstance(IncludeTreeConditionMatcherVisitor::class);
                $conditionMatcherVisitor->initializeExpressionMatcherWithVariables($expressionMatcherVariables);
                $includeTreeTraverserVisitors[] = $conditionMatcherVisitor;
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
        if ($isCachingAllowed && $setupConditionIncludeTree = $typoscriptCache->require($setupConditionIncludeListCacheIdentifier)) {
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
        $setupIncludeTree = null;
        if (!$isCachingAllowed || !$gotSetupConditionsFromCache) {
            // We did not get setup condition list from cache, or are not allowed to use cache. We have to build setup
            // condition list from scratch. This means we'll fetch the full setup include tree (from cache if possible),
            // register the constant substitution visitor, and register condition matcher and register the condition
            // accumulator visitor.
            $setupIncludeTree = $treeBuilder->getTreeBySysTemplateRowsAndSite('setup', $sysTemplateRows, $tokenizer, $site, $typoscriptCache);
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
            $typoscriptCache?->set($setupConditionIncludeListCacheIdentifier, 'return unserialize(\'' . addcslashes(serialize($setupConditionIncludeListAccumulatorVisitor->getConditionIncludes()), '\'\\') . '\');');
        }

        // We now gathered everything to calculate the page cache identifier: It depends on sys_template rows, the calculated
        // constant condition verdicts, the setup condition verdicts, plus various not TypoScript related details like
        // obviously the page id.
        $this->lock = GeneralUtility::makeInstance(ResourceMutex::class);
        $this->newHash = $this->createHashBase($sysTemplateRows, $constantConditionList, $setupConditionList);
        if ($isCachingAllowed) {
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

        if (!$isCachingAllowed || empty($this->config) || $this->isINTincScript()) {
            // We don't need the full setup AST in many cached scenarios. However, if caching is not allowed, if no page
            // cache entry could be loaded or if the page cache entry has _INT object, then we still need the full setup AST.
            // If there is "just" an _INT object, we can use a possible cache entry for the setup AST, which speeds up _INT
            // parsing quite a bit. In other cases we calculate full setup AST and cache it if allowed.
            $setupTypoScriptCacheIdentifier = 'setup-' . sha1($serializedSysTemplateRows . $serializedConstantConditionList . serialize($setupConditionList));
            $gotSetupFromCache = false;
            $setupArray = [];
            if ($isCachingAllowed) {
                // We need AST, but we are allowed to potentially get it from cache.
                if ($setupTypoScriptCache = $typoscriptCache->require($setupTypoScriptCacheIdentifier)) {
                    $frontendTypoScript->setSetupTree($setupTypoScriptCache['ast']);
                    $setupArray = $setupTypoScriptCache['array'];
                    $gotSetupFromCache = true;
                }
            }
            if (!$isCachingAllowed || !$gotSetupFromCache) {
                // We need AST and couldn't get it from cache or are now allowed to. We thus need the full setup
                // IncludeTree, which we can get from cache again if allowed, or is calculated a-new if not.
                if (!$isCachingAllowed || $setupIncludeTree === null) {
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
                if ($isCachingAllowed) {
                    // Write cache entry for AST and its array representation, we're allowed to do it.
                    $typoscriptCache->set($setupTypoScriptCacheIdentifier, 'return unserialize(\'' . addcslashes(serialize(['ast' => $setupAst, 'array' => $setupArray]), '\'\\') . '\');');
                }
            }

            $type = (int)($this->pageArguments->getPageType() ?: 0);
            $typoScriptPageTypeName = $setupArray['types.'][$type] ?? '';
            $typoScriptPageTypeSetup = $setupArray[$typoScriptPageTypeName . '.'] ?? null;
            if (!is_array($typoScriptPageTypeSetup)) {
                $this->logger->alert('The page is not configured! [type={type}][{type_name}].', ['type' => $type, 'type_name' => $typoScriptPageTypeName]);
                try {
                    $message = 'The page is not configured! [type=' . $type . '][' . $typoScriptPageTypeName . '].';
                    $response = GeneralUtility::makeInstance(ErrorController::class)->internalErrorAction(
                        $request,
                        $message,
                        ['code' => PageAccessFailureReasons::RENDERING_INSTRUCTIONS_NOT_CONFIGURED]
                    );
                    throw new PropagateResponseException($response, 1533931374);
                } catch (AbstractServerErrorException $e) {
                    $explanation = 'This means that there is no TypoScript object of type PAGE with typeNum=' . $type . ' configured.';
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
            if (is_array($typoScriptPageTypeSetup['config.'] ?? null)) {
                $this->config['config'] = array_replace_recursive($this->config['config'], $typoScriptPageTypeSetup['config.']);
            }
            $this->config['rootLine'] = $localRootline;
            $frontendTypoScript->setSetupArray($setupArray);
        }

        // Disable cache if config.no_cache is set!
        if ($this->config['config']['no_cache'] ?? false) {
            $cacheInstruction = $request->getAttribute('frontend.cache.instruction');
            $cacheInstruction->disableCache('EXT:frontend: Disabled cache due to TypoScript "config.no_cache = 1"');
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
     */
    protected function shouldAcquireCacheData(ServerRequestInterface $request): bool
    {
        // Trigger event for possible by-pass of requiring of page cache.
        $event = new ShouldUseCachedPageDataIfAvailableEvent($request, $this, $request->getAttribute('frontend.cache.instruction')->isCachingAllowed());
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
            'type' => (int)($this->pageArguments->getPageType() ?: 0),
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

    /**
     * Instantiate \TYPO3\CMS\Frontend\ContentObject to generate the correct target URL
     *
     * @internal
     */
    public function getUriToCurrentPageForRedirect(ServerRequestInterface $request): string
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageRecord = $pageInformation->getPageRecord();
        $parameter = $pageRecord['uid'];
        /** @var PageArguments $pageArguments */
        $pageArguments = $request->getAttribute('routing');
        $type = (int)($pageArguments->getPageType() ?: 0);
        if ($type) {
            $parameter .= ',' . $type;
        }
        return GeneralUtility::makeInstance(ContentObjectRenderer::class, $this)->createUrl([
            'parameter' => $parameter,
            'addQueryString' => 'untrusted',
            'addQueryString.' => ['exclude' => 'id,type'],
            'forceAbsoluteUrl' => true,
        ]);
    }

    /**
     * Returns TRUE if the page content should be generated.
     *
     * @internal
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
     * Setting the SYS_LASTCHANGED value in the pagerecord: This value will thus be set to the highest tstamp of records rendered on the page.
     * This includes all records with no regard to hidden records, userprotection and so on.
     *
     * The important part is that this actually updates a translated "pages" record (_LOCALIZED_UID) if
     * the Frontend is called with a translation.
     *
     * @see ContentObjectRenderer::lastChanged()
     * @see setRegisterValueForSysLastChanged()
     */
    protected function setSysLastChanged(): void
    {
        // We only update the info if browsing the live workspace
        $isInWorkspace = $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
        if ($isInWorkspace) {
            return;
        }
        if ($this->page['SYS_LASTCHANGED'] < (int)($this->register['SYS_LASTCHANGED'] ?? 0)) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('pages');
            $pageId = $this->page['_LOCALIZED_UID'] ?? $this->id;
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
     * Adds tags to this page's cache entry, you can then f.e. remove cache
     * entries by tag
     */
    public function addCacheTags(array $tags): void
    {
        $this->pageCacheTags = array_merge($this->pageCacheTags, $tags);
    }

    public function getPageCacheTags(): array
    {
        return $this->pageCacheTags;
    }

    /**
     * Sets up TypoScript "config." options and set properties in $TSFE.
     *
     * @internal
     */
    public function preparePageContentGeneration(ServerRequestInterface $request): void
    {
        $this->getTimeTracker()->push('Prepare page content generation');
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

        // We need to set the doctype to "something defined" otherwise (because this method is called also during USER_INT renderings)
        $this->config['config']['doctype'] ??= 'html5';
        $docType = DocType::createFromConfigurationKey($this->config['config']['doctype']);
        $this->pageRenderer->setDocType($docType);

        // Global content object
        $this->newCObj($request);
        $this->getTimeTracker()->pull();
    }

    /**
     * Does processing of the content after the page content was generated.
     * This includes caching the page, indexing the page (if configured) and setting sysLastChanged
     *
     * @internal
     */
    public function generatePage_postProcessing(ServerRequestInterface $request): void
    {
        $this->setAbsRefPrefix();
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $usePageCache = $request->getAttribute('frontend.cache.instruction')->isCachingAllowed();
        $event = new AfterCacheableContentIsGeneratedEvent($request, $this, $this->newHash, $usePageCache);
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
     * @internal
     */
    public function generatePageTitle(): string
    {
        // config.noPageTitle = 2 - means do not render the page title
        if ((int)($this->config['config']['noPageTitle'] ?? 0) === 2) {
            return '';
        }

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
     *
     * @internal
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
    protected function recursivelyReplaceIntPlaceholdersInContent(ServerRequestInterface $request): void
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
    protected function processNonCacheableContentPartsAndSubstituteContentMarkers(array $nonCacheableData, ServerRequestInterface $request): void
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
    public function INTincScript_loadJSCode(): void
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
     * @internal
     */
    public function isINTincScript(): bool
    {
        return !empty($this->config['INTincScript']) && is_array($this->config['INTincScript']);
    }

    /**
     * Add HTTP headers to the response object.
     *
     * @internal
     */
    public function applyHttpHeadersToResponse(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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
        $headers = $this->getCacheHeaders($request);
        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
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
    protected function getCacheHeaders(ServerRequestInterface $request): array
    {
        $headers = [];
        // Getting status whether we can send cache control headers for proxy caching:
        $doCache = $this->isStaticCacheble($request);
        $isBackendUserLoggedIn = $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
        $isInWorkspace = $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
        // Finally, when backend users are logged in, do not send cache headers at all (Admin Panel might be displayed for instance).
        $isClientCachable = $doCache && !$isBackendUserLoggedIn && !$isInWorkspace;
        if ($isClientCachable) {
            // Only send the headers to the client that they are allowed to cache if explicitly activated.
            if (!empty($this->config['config']['sendCacheHeaders'])) {
                $headers = [
                    'Expires' => gmdate('D, d M Y H:i:s T', $this->cacheExpires),
                    'ETag' => '"' . md5($this->content) . '"',
                    'Cache-Control' => 'max-age=' . ($this->cacheExpires - $GLOBALS['EXEC_TIME']),
                    // no-cache
                    'Pragma' => 'public',
                ];
            }
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
                    if (!$request->getAttribute('frontend.cache.instruction')->isCachingAllowed()) {
                        $reasonMsg[] = 'Caching disabled.';
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
     * There can be no logged-in user because user sessions are based on a cookie and thereby does not offer client caching a
     * chance to know if the user is logged in. Actually, there will be a reverse problem here; If a page will somehow change
     * when a user is logged in he may not see it correctly if the non-login version sent a cache-header! So do NOT use cache
     * headers in page sections where user logins change the page content. (unless using such as realurl to apply a prefix
     * in case of login sections)
     *
     * @internal
     */
    public function isStaticCacheble(ServerRequestInterface $request): bool
    {
        $isCachingAllowed = $request->getAttribute('frontend.cache.instruction')->isCachingAllowed();
        return $isCachingAllowed && !$this->isINTincScript() && !$this->context->getAspect('frontend.user')->isUserOrGroupSet();
    }

    /**
     * Creates an instance of ContentObjectRenderer in $this->cObj
     * This instance is used to start the rendering of the TypoScript template structure
     *
     * @param ServerRequestInterface|null $request
     * @internal
     */
    public function newCObj(ServerRequestInterface $request = null): void
    {
        $this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $this);
        $this->cObj->setRequest($request);
        $this->cObj->start($this->page, 'pages');
    }

    /**
     * Converts relative paths in the HTML source to absolute paths for fileadmin/, typo3conf/ext/ and media/ folders.
     *
     * @see \TYPO3\CMS\Frontend\Http\RequestHandler
     * @see INTincScript()
     */
    protected function setAbsRefPrefix(): void
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
     * Logs access to deprecated TypoScript objects and properties.
     *
     * Dumps message to the TypoScript message log (admin panel) and the TYPO3 deprecation log.
     *
     * @param string $typoScriptProperty Deprecated object or property
     * @param string $explanation Message or additional information
     * @internal
     */
    public function logDeprecatedTyposcript(string $typoScriptProperty, string $explanation = ''): void
    {
        $explanationText = $explanation !== '' ? ' - ' . $explanation : '';
        $this->getTimeTracker()->setTSlogMessage($typoScriptProperty . ' is deprecated.' . $explanationText, LogLevel::WARNING);
        trigger_error('TypoScript property ' . $typoScriptProperty . ' is deprecated' . $explanationText, E_USER_DEPRECATED);
    }

    /**
     * Returns a unique md5 hash.
     * There is no special magic in this, the only point is that you don't have to call md5(uniqid()) which is slow and by this you are sure to get a unique string each time in a little faster way.
     *
     * @param string $str Some string to include in what is hashed. Not significant at all.
     * @return string MD5 hash of ->uniqueString, input string and uniqueCounter
     * @internal
     */
    public function uniqueHash(string $str = ''): string
    {
        return md5($this->uniqueString . '_' . $str . $this->uniqueCounter++);
    }

    /**
     * Sets the cache-flag to 1. Could be called from user-included php-files in order to ensure that a page is not cached.
     *
     * @param string $reason An optional reason to be written to the log.
     * @todo: deprecate
     */
    public function set_no_cache(string $reason = ''): void
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
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['disableNoCacheParameter']) {
            $warning .= ' However, $TYPO3_CONF_VARS[\'FE\'][\'disableNoCacheParameter\'] is set, so it will be ignored!';
            $this->getTimeTracker()->setTSlogMessage($warning, LogLevel::NOTICE);
        } else {
            $warning .= ' Caching is disabled!';
            /** @var ServerRequestInterface $request */
            $request = $GLOBALS['TYPO3_REQUEST'];
            $cacheInstruction = $request->getAttribute('frontend.cache.instruction');
            $cacheInstruction->disableCache('EXT:frontend: Caching disabled using deprecated set_no_cache().');
        }
        $this->logger->notice($warning, $context);
    }

    /**
     * Sets the cache-timeout in seconds
     *
     * @param int $seconds Cache-timeout in seconds
     * @internal
     */
    public function set_cache_timeout_default(int $seconds): void
    {
        $seconds = (int)$seconds;
        if ($seconds > 0) {
            $this->cacheTimeOutDefault = $seconds;
        }
    }

    /**
     * Get the cache timeout for the current page.
     * @internal
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

    /**
     * Split Label function for front-end applications.
     *
     * @param string $input Key string. Accepts the "LLL:" prefix.
     * @return string Label value, if any.
     */
    public function sL(string $input): string
    {
        if ($this->languageService === null) {
            $this->languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromSiteLanguage($this->language);
        }
        return $this->languageService->sL($input);
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

    protected function getBackendUser(): ?FrontendBackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }

    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * @internal
     */
    public function getLanguage(): SiteLanguage
    {
        return $this->language;
    }

    /**
     * @internal
     */
    public function getSite(): Site
    {
        return $this->site;
    }

    /**
     * @internal
     */
    public function getPageArguments(): PageArguments
    {
        return $this->pageArguments;
    }
}
