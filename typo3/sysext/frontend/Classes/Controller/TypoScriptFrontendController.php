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
use TYPO3\CMS\Core\Cache\CacheEntry;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent;

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
     * @todo: deprecate
     */
    public int $id;

    /**
     * @var array<int, array<string, mixed>>
     * @todo: deprecate
     */
    public array $rootLine = [];

    /**
     * The page record.
     *
     * Read-only! Extensions may read but never write this property!
     * @todo: deprecate
     */
    public ?array $page = [];

    /**
     * This will normally point to the same value as id, but can be changed to
     * point to another page from which content will then be displayed instead.
     *
     * Read-only! Extensions may read but never write this property!
     * @todo: deprecate
     */
    public int $contentPid = 0;

    /**
     * Read-only! Extensions may read but never write this property!
     * @todo: deprecate
     */
    public PageRepository $sys_page;

    /**
     * A central data array consisting of various keys, initialized and
     * processed at various places in the class.
     *
     * This array is cached along with the rendered page content and contains
     * for instance a list of INT identifiers used to calculate 'dynamic' page
     * parts when a page is retrieved from cache.
     *
     * 'config': This is the TypoScript ['config.'] sub-array, with some
     *           settings being sanitized and merged.
     *
     * 'INTincScript': (internal) List of INT instructions
     * 'INTincScript_ext': (internal) Further state for INT instructions
     * 'pageTitleCache': (internal)
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
     *
     * @internal Used by a middleware. Will be removed.
     */
    public bool $pageContentWasLoadedFromCache = false;

    /**
     * @internal Used by a middleware. Will be removed.
     */
    public int $cacheGenerated = 0;

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

    protected LanguageService $languageService;

    protected ?PageRenderer $pageRenderer = null;
    protected FrontendInterface $pageCache;

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
     * @internal Used by a middleware. Will be removed.
     */
    public ?string $debugInformationHeader = null;

    /**
     * @internal Extensions should usually not need to create own instances of TSFE
     */
    public function __construct()
    {
        $this->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $this->context = GeneralUtility::makeInstance(Context::class);
        $this->uniqueString = md5(microtime());
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $this->pageCache = $cacheManager->getCache('pages');
    }

    /**
     * @internal
     */
    public function initializePageRenderer(ServerRequestInterface $request): void
    {
        if ($this->pageRenderer !== null) {
            return;
        }
        $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $this->pageRenderer->setTemplateFile('EXT:frontend/Resources/Private/Templates/MainPage.html');
        // As initPageRenderer could be called in constructor and for USER_INTs, this information is only set
        // once - in order to not override any previous settings of PageRenderer.
        $language = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();
        if ($language->hasCustomTypo3Language()) {
            $locale = GeneralUtility::makeInstance(Locales::class)->createLocale($language->getTypo3Language());
        } else {
            $locale = $language->getLocale();
        }
        $this->pageRenderer->setLanguage($locale);
    }

    /**
     * This is only needed for sL() to be initialized properly.
     *
     * @internal
     */
    public function initializeLanguageService(ServerRequestInterface $request): void
    {
        $language = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();
        $this->languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromSiteLanguage($language);
    }

    /**
     * @internal Must only be used by TYPO3 core
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
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
     * @param ServerRequestInterface $request
     * @param string $content The content to store in the HTML field of the cache table
     * @param array $INTincScript
     * @param array $INTincScript_ext
     * @param array $pageTitleCache
     *
     * @see PrepareTypoScriptFrontendRendering
     */
    protected function setPageCacheContent(
        ServerRequestInterface $request,
        string $content,
        array $INTincScript,
        array $INTincScript_ext,
        array $pageTitleCache
    ): void {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageId = $pageInformation->getId();
        $pageRecord = $pageInformation->getPageRecord();

        $lifetime = $this->get_cache_timeout($request);
        $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
        $cacheDataCollector->addCacheTags(new CacheTag('pageId_' . $pageId, $lifetime));

        // Respect the page cache when content of pid is shown
        if ($pageId !== $pageInformation->getContentFromPid()) {
            $cacheDataCollector->addCacheTags(new CacheTag('pageId_' . $this->contentPid, $lifetime));
        }
        if (!empty($pageRecord['cache_tags'])) {
            $tags = GeneralUtility::trimExplode(',', $pageRecord['cache_tags'], true);
            array_walk($tags, fn(string $tag) => $cacheDataCollector->addCacheTags(new CacheTag($tag, $lifetime)));
        }

        $cacheData = [
            'page_id' => $pageId,
            'content' => $content,
            'contentType' => $this->contentType,
            'INTincScript' => $INTincScript,
            'INTincScript_ext' => $INTincScript_ext,
            'pageTitleCache' => $pageTitleCache,
            'tstamp' => $GLOBALS['EXEC_TIME'],
        ];

        $cacheDataCollector->enqueueCacheEntry(
            new CacheEntry(
                identifier: 'tsfe-page-cache',
                content: $cacheData,
                persist: function (ServerRequestInterface $request, string $identifier, mixed $content) {
                    $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
                    $cacheTimeout = $cacheDataCollector->resolveLifetime();
                    $pageCacheTags = array_map(fn(CacheTag $cacheTag) => $cacheTag->name, $cacheDataCollector->getCacheTags());

                    $content['cacheTags'] = $pageCacheTags;
                    $content['expires'] = $GLOBALS['EXEC_TIME'] + $cacheTimeout;
                    $this->pageCache->set($this->newHash, $content, $pageCacheTags, $cacheTimeout);

                    // Event for cache post processing (eg. writing static files)
                    $event = new AfterCachedPageIsPersistedEvent($request, $this, $this->newHash, $content, $cacheTimeout);
                    GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($event);
                }
            )
        );
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
    protected function setSysLastChanged(ServerRequestInterface $request): void
    {
        // Only update if browsing the live workspace
        $isInWorkspace = $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
        if ($isInWorkspace) {
            return;
        }
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageRecord = $pageInformation->getPageRecord();
        if ($pageRecord['SYS_LASTCHANGED'] < (int)($this->register['SYS_LASTCHANGED'] ?? 0)) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
            $pageId = $pageRecord['_LOCALIZED_UID'] ?? $pageInformation->getId();
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
     *
     * @param array $tags An array of tag
     * @deprecated since TYPO3 v13, will be removed in TYPO3 v14. Use $request->getAttribute('frontend.cache.collector')->addCacheTags(new CacheTag($tag, $lifetime)) instead.
     */
    public function addCacheTags(array $tags)
    {
        trigger_error(
            'TypoScriptFrontendController->addCacheTags has been marked as deprecated in TYPO3 v13. Use $request->getAttribute(\'cacheTags\')->addCacheTags(new CacheTag($tag, $lifetime)) instead.',
            E_USER_DEPRECATED,
        );
        $cacheDataCollector = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.cache.collector');
        $cacheDataCollector->addCacheTags(...array_map(fn(string $tag) => new CacheTag($tag), $tags));
    }

    /**
     * @return array
     * @deprecated since TYPO3 v13, will be removed in TYPO3 v14. Use $request->getAttribute('frontend.cache.collector')->getCacheTags() instead.
     */
    public function getPageCacheTags(): array
    {
        trigger_error(
            'TypoScriptFrontendController->getPageCacheTags has been marked as deprecated in TYPO3 v13. Use $request->getAttribute(\'cacheTags\')->getCacheTags() instead.',
            E_USER_DEPRECATED,
        );
        $cacheDataCollector = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.cache.collector');
        return array_map(fn(CacheTag $cacheTag) => $cacheTag->name, $cacheDataCollector->getCacheTags());
    }

    /**
     * Sets up TypoScript "config." options and set properties in $TSFE.
     *
     * @internal
     */
    public function preparePageContentGeneration(ServerRequestInterface $request): void
    {
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        // calculate the absolute path prefix
        if (!empty($this->absRefPrefix = trim($typoScriptConfigArray['absRefPrefix']))) {
            if ($this->absRefPrefix === 'auto') {
                $normalizedParams = $request->getAttribute('normalizedParams');
                $this->absRefPrefix = $normalizedParams->getSitePath();
            }
        }
        // config.forceAbsoluteUrls will override absRefPrefix
        if ($typoScriptConfigArray['forceAbsoluteUrls'] ?? false) {
            $normalizedParams = $request->getAttribute('normalizedParams');
            $this->absRefPrefix = $normalizedParams->getSiteUrl();
        }

        $docType = DocType::createFromConfigurationKey($typoScriptConfigArray['doctype']);
        $this->pageRenderer->setDocType($docType);

        // Global content object
        $this->newCObj($request);
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
            // Write the page to cache, but do not cache localRootLine since that is always determined
            // and coming from PageInformation->getLocalRootLine().
            $this->setPageCacheContent(
                $request,
                $this->content,
                $this->config['INTincScript'] ?? [],
                $this->config['INTincScript_ext'] ?? [],
                $this->config['pageTitleCache'] ?? []
            );
        }
        $this->setSysLastChanged($request);
    }

    /**
     * Generate the page title, can be called multiple times,
     * as PageTitleProvider might have been modified by an uncached plugin etc.
     *
     * @internal
     */
    public function generatePageTitle(ServerRequestInterface $request): string
    {
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        // config.noPageTitle = 2 - means do not render the page title
        if ((int)($typoScriptConfigArray['noPageTitle'] ?? 0) === 2) {
            return '';
        }

        // Check for a custom pageTitleSeparator, and perform stdWrap on it
        $pageTitleSeparator = (string)$this->cObj->stdWrapValue('pageTitleSeparator', $typoScriptConfigArray);
        if ($pageTitleSeparator !== '' && $pageTitleSeparator === ($typoScriptConfigArray['pageTitleSeparator'] ?? '')) {
            $pageTitleSeparator .= ' ';
        }

        $titleProvider = GeneralUtility::makeInstance(PageTitleProviderManager::class);
        if (!empty($this->config['pageTitleCache'])) {
            $titleProvider->setPageTitleCache($this->config['pageTitleCache']);
        }
        $pageTitle = $titleProvider->getTitle($request);
        $this->config['pageTitleCache'] = $titleProvider->getPageTitleCache();

        $titleTagContent = $this->printTitle(
            $request,
            $pageTitle,
            (bool)($typoScriptConfigArray['noPageTitle'] ?? false),
            (bool)($typoScriptConfigArray['pageTitleFirst'] ?? false),
            $pageTitleSeparator,
            (bool)($typoScriptConfigArray['showWebsiteTitle'] ?? true)
        );

        if (isset($typoScriptConfigArray['pageTitle.']) && is_array($typoScriptConfigArray['pageTitle.'])) {
            // stdWrap for pageTitle if set in config.pageTitle.
            $pageTitleStdWrapArray = [
                'pageTitle' => $titleTagContent,
                'pageTitle.' => $typoScriptConfigArray['pageTitle.'],
            ];
            $titleTagContent = $this->cObj->stdWrapValue('pageTitle', $pageTitleStdWrapArray);
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
    protected function printTitle(ServerRequestInterface $request, string $pageTitle, bool $noPageTitle = false, bool $showPageTitleFirst = false, string $pageTitleSeparator = '', bool $showWebsiteTitle = true): string
    {
        $websiteTitle = $showWebsiteTitle ? $this->getWebsiteTitle($request) : '';
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

    protected function getWebsiteTitle(ServerRequestInterface $request): string
    {
        // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
        $language = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();
        if (trim($language->getWebsiteTitle()) !== '') {
            return trim($language->getWebsiteTitle());
        }
        $siteConfiguration = $request->getAttribute('site')->getConfiguration();
        if (trim($siteConfiguration['websiteTitle'] ?? '') !== '') {
            return trim($siteConfiguration['websiteTitle']);
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
            $this->initializePageRenderer($request);
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
        $this->generatePageTitle($request);

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
        if ($this->isINTincScript() && (!isset($this->config['INTincScript_ext']) || $this->config['INTincScript_ext'] === [])) {
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
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        if (empty($typoScriptConfigArray['disableLanguageHeader'])) {
            // Set header for content language unless disabled
            // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
            $language = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();
            $response = $response->withHeader('Content-Language', (string)$language->getLocale());
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
        $additionalHeaders = $this->getAdditionalHeaders($request);
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
        // Even though we "could" tell the clients to cache the page, we tell clients not to cache this page
        // by default.
        // If TYPO3 does not define this, then a malformed .htaccess might send "cache every HTML file for 30 minutes"
        // and exposing content that should not be cached.
        // "no-store" is used to ensure that the client HAS to ask the server every time,
        // and is not allowed to store anything at all
        $headers = [
            'Cache-Control' => 'private, no-store',
        ];
        // Getting status whether we can send cache control headers for proxy caching:
        $doCache = $this->isStaticCacheble($request);
        $isBackendUserLoggedIn = $this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);
        $isInWorkspace = $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
        // Finally, when backend users are logged in, do not send cache headers at all (Admin Panel might be displayed for instance).
        $isClientCachable = $doCache && !$isBackendUserLoggedIn && !$isInWorkspace;
        $lifetime = $request->getAttribute('frontend.cache.collector')->resolveLifetime();
        if ($isClientCachable) {
            // Only send the headers to the client that they are allowed to cache if explicitly activated.
            $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
            $sendCacheHeadersToClient = !empty($typoScriptConfigArray['sendCacheHeaders']);
            // The flag "config.sendCacheHeadersForSharedCaches" is preferred over "config.sendCacheHeaders"
            $sendCacheHeadersForSharedCaches = $typoScriptConfigArray['sendCacheHeadersForSharedCaches'] ?? '';
            $isBehindReverseProxy = $request->getAttribute('normalizedParams')?->isBehindReverseProxy();
            if (
                $sendCacheHeadersForSharedCaches === 'force' ||
                ($sendCacheHeadersForSharedCaches === 'auto' && $isBehindReverseProxy)
            ) {
                $headers = [
                    'Expires' => gmdate('D, d M Y H:i:s T', ($GLOBALS['EXEC_TIME'] + $lifetime)),
                    'ETag' => '"' . md5($this->content) . '"',
                    // Do not cache for private caches, but store in shared caches
                    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#:~:text=Age%3A%20100-,s%2Dmaxage,-The%20s%2Dmaxage
                    'Cache-Control' => 'max-age=0, s-maxage=' . $lifetime,
                    'Pragma' => 'public',
                ];
            } elseif ($sendCacheHeadersToClient) {
                $headers = [
                    'Expires' => gmdate('D, d M Y H:i:s T', ($GLOBALS['EXEC_TIME'] + $lifetime)),
                    'ETag' => '"' . md5($this->content) . '"',
                    'Cache-Control' => 'max-age=' . $lifetime,
                    'Pragma' => 'public',
                ];
            }
        } elseif ($isBackendUserLoggedIn) {
            // Now, if a backend user is logged in, tell the user in the Admin Panel log
            // what the caching status would have been.
            if ($doCache) {
                $this->getTimeTracker()->setTSlogMessage('Cache-headers with max-age "' . $lifetime . '" would have been sent');
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
     * @internal
     */
    public function newCObj(ServerRequestInterface $request): void
    {
        $this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $this);
        $this->cObj->setRequest($request);
        $this->cObj->start($request->getAttribute('frontend.page.information')->getPageRecord(), 'pages');
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
     * Sets the default page cache timeout in seconds
     * @internal
     */
    public function set_cache_timeout_default(int $seconds): void
    {
        if ($seconds > 0) {
            $this->cacheTimeOutDefault = $seconds;
        }
    }

    /**
     * Get the cache timeout for the current page.
     */
    protected function get_cache_timeout(ServerRequestInterface $request): int
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        return GeneralUtility::makeInstance(CacheLifetimeCalculator::class)
            ->calculateLifetimeForPage(
                $pageInformation->getId(),
                $pageInformation->getPageRecord(),
                $typoScriptConfigArray,
                $this->cacheTimeOutDefault,
                $this->context
            );
    }

    /**
     * Split Label function for front-end applications.
     *
     * @param string $input Key string. Accepts the "LLL:" prefix.
     * @return string Label value, if any.
     * @todo: deprecate
     */
    public function sL(string $input): string
    {
        return $this->languageService->sL($input);
    }

    /**
     * Send additional headers from config.additionalHeaders
     */
    protected function getAdditionalHeaders(ServerRequestInterface $request): array
    {
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        if (!isset($typoScriptConfigArray['additionalHeaders.'])) {
            return [];
        }
        $additionalHeaders = [];
        $additionalHeadersConfig = $typoScriptConfigArray['additionalHeaders.'];
        ksort($additionalHeadersConfig);
        foreach ($additionalHeadersConfig as $options) {
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
}
