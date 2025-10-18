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
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\CacheEntry;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendBackendUserAuthentication;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\CMS\Frontend\Cache\MetaDataState;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent;
use TYPO3\CMS\Frontend\Page\FrontendUrlPrefix;

/**
 * Main controller class of the TypoScript based frontend.
 *
 * This is prepared in Frontend middlewares and the content rendering is
 * ultimately called in \TYPO3\CMS\Frontend\Http\RequestHandler.
 *
 * @deprecated since TYPO3 v13, will vanish during v14 development. There are some
 *             remaining internal usages that can be adapted without further .rst
 *             files.
 */
#[Autoconfigure(public: true)]
readonly class TypoScriptFrontendController
{
    public function __construct(
        private Context $context,
        #[Autowire(service: 'cache.pages')]
        private FrontendInterface $pageCache,
    ) {}

    /**
     * Sets cache content; Inserts the content string into the pages cache.
     *
     * @param ServerRequestInterface $request
     * @param string $content The content to store in the HTML field of the cache table
     * @see PrepareTypoScriptFrontendRendering
     */
    protected function setPageCacheContent(
        ServerRequestInterface $request,
        string $content,
        array $INTincScript,
        array $pageTitleCache,
        array $metaDataState = [],
    ): void {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageParts = $request->getAttribute('frontend.page.parts');
        $pageId = $pageInformation->getId();
        $pageRecord = $pageInformation->getPageRecord();

        $lifetime = $this->get_cache_timeout($request);
        $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
        $cacheDataCollector->addCacheTags(new CacheTag('pageId_' . $pageId, $lifetime));

        // Respect the page cache when content of pid is shown
        if ($pageId !== $pageInformation->getContentFromPid()) {
            $cacheDataCollector->addCacheTags(new CacheTag('pageId_' . $pageInformation->getContentFromPid(), $lifetime));
        }

        // Respect the translation page id on translated pages
        if ((int)($pageRecord['_LOCALIZED_UID'] ?? 0) > 0) {
            $cacheDataCollector->addCacheTags(new CacheTag('pageId_' . $pageRecord['_LOCALIZED_UID'], $lifetime));
        }

        if (!empty($pageRecord['cache_tags'])) {
            $tags = GeneralUtility::trimExplode(',', $pageRecord['cache_tags'], true);
            array_walk($tags, fn(string $tag) => $cacheDataCollector->addCacheTags(new CacheTag($tag, $lifetime)));
        }

        $cacheData = [
            'page_id' => $pageId,
            'content' => $content,
            'contentType' => $request->getAttribute('frontend.page.parts')->getHttpContentType(),
            'INTincScript' => $INTincScript,
            'pageRendererSubstitutionHash' => $pageParts->getPageRendererSubstitutionHash(),
            'pageRendererState' => serialize(GeneralUtility::makeInstance(PageRenderer::class)->getState()),
            'assetCollectorState' => serialize(GeneralUtility::makeInstance(AssetCollector::class)->getState()),
            'pageTitleCache' => $pageTitleCache,
            'pageCacheGeneratedTimestamp' => $GLOBALS['EXEC_TIME'],
            'metaDataState' => $metaDataState,
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
                    $content['pageCacheExpireTimestamp'] = $GLOBALS['EXEC_TIME'] + $cacheTimeout;
                    $this->pageCache->set($cacheDataCollector->getPageCacheIdentifier(), $content, $pageCacheTags, $cacheTimeout);

                    // Event for cache post processing (eg. writing static files)
                    $event = new AfterCachedPageIsPersistedEvent($request, $cacheDataCollector->getPageCacheIdentifier(), $content, $cacheTimeout);
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
        $pageParts = $request->getAttribute('frontend.page.parts');
        $pageRecord = $pageInformation->getPageRecord();
        if ($pageRecord['SYS_LASTCHANGED'] < $pageParts->getLastChanged()) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
            $pageId = $pageRecord['_LOCALIZED_UID'] ?? $pageInformation->getId();
            $connection->update(
                'pages',
                [
                    'SYS_LASTCHANGED' => $pageParts->getLastChanged(),
                ],
                [
                    'uid' => (int)$pageId,
                ]
            );
        }
    }

    /**
     * Does processing of the content after the page content was generated.
     * This includes caching the page, indexing the page (if configured) and setting sysLastChanged
     *
     * @internal
     */
    public function generatePage_postProcessing(ServerRequestInterface $request, string $content): string
    {
        $absRefPrefix = GeneralUtility::makeInstance(FrontendUrlPrefix::class)->getUrlPrefix($request);
        $content = $this->setAbsRefPrefixInContent($content, $absRefPrefix);
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $usePageCache = $request->getAttribute('frontend.cache.instruction')->isCachingAllowed();
        $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
        $pageParts = $request->getAttribute('frontend.page.parts');

        $event = new AfterCacheableContentIsGeneratedEvent($request, $content, $cacheDataCollector->getPageCacheIdentifier(), $usePageCache);
        $content = $event->getContent();
        $event = $eventDispatcher->dispatch($event);

        // Processing if caching is enabled
        if ($event->isCachingEnabled()) {
            // Fetch meta-data state
            $metaDataState = GeneralUtility::makeInstance(MetaDataState::class)->getState();
            // Write the page to cache, but do not cache localRootLine since that is always determined
            // and coming from PageInformation->getLocalRootLine().
            $this->setPageCacheContent(
                $request,
                $content,
                $pageParts->getNotCachedContentElementRegistry(),
                $pageParts->getPageTitle(),
                $metaDataState,
            );
        }
        $this->setSysLastChanged($request);
        return $content;
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

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start($request->getAttribute('frontend.page.information')->getPageRecord(), 'pages');

        // Check for a custom pageTitleSeparator, and perform stdWrap on it
        $pageTitleSeparator = (string)$contentObjectRenderer->stdWrapValue('pageTitleSeparator', $typoScriptConfigArray);
        if ($pageTitleSeparator !== '' && $pageTitleSeparator === ($typoScriptConfigArray['pageTitleSeparator'] ?? '')) {
            $pageTitleSeparator .= ' ';
        }

        $pageParts = $request->getAttribute('frontend.page.parts');
        $titleProvider = GeneralUtility::makeInstance(PageTitleProviderManager::class);
        $titleProvider->setPageTitleCache($pageParts->getPageTitle());
        $pageTitle = $titleProvider->getTitle($request);
        $pageParts->setPageTitle($titleProvider->getPageTitleCache());

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
            $titleTagContent = $contentObjectRenderer->stdWrapValue('pageTitle', $pageTitleStdWrapArray);
        }

        if ($titleTagContent !== '') {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->setTitle($titleTagContent);
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
    public function INTincScript(ServerRequestInterface $request, string $content): string
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        $content = $this->recursivelyReplaceIntPlaceholdersInContent($request, $content);
        $this->getTimeTracker()->push('Substitute header section');
        $this->generatePageTitle($request);

        $pageParts = $request->getAttribute('frontend.page.parts');
        $content = $pageRenderer->renderJavaScriptAndCssForProcessingOfUncachedContentObjects($content, $pageParts->getPageRendererSubstitutionHash());
        // Replace again, because header and footer data and page renderer replacements may introduce additional placeholders (see #44825)
        $content = $this->recursivelyReplaceIntPlaceholdersInContent($request, $content);
        $absRefPrefix = GeneralUtility::makeInstance(FrontendUrlPrefix::class)->getUrlPrefix($request);
        $content = $this->setAbsRefPrefixInContent($content, $absRefPrefix);
        $this->getTimeTracker()->pull();
        return $content;
    }

    /**
     * Replace INT placeholders (COA_INT and USER_INT) in content. In case the replacement adds
     * additional placeholders, it loops until no new placeholders are found anymore.
     */
    protected function recursivelyReplaceIntPlaceholdersInContent(ServerRequestInterface $request, string $content): string
    {
        $pageParts = $request->getAttribute('frontend.page.parts');
        do {
            $nonCacheableData = $pageParts->getNotCachedContentElementRegistry();
            $content = $this->processNonCacheableContentPartsAndSubstituteContentMarkers($nonCacheableData, $request, $content);
            // Check if there were new items added to INTincScript during the previous execution:
            // array_diff_assoc throws notices if values are arrays but not strings. We suppress this here.
            $nonCacheableData = @array_diff_assoc($pageParts->getNotCachedContentElementRegistry(), $nonCacheableData);
            $reprocess = count($nonCacheableData) > 0;
        } while ($reprocess);
        return $content;
    }

    /**
     * Processes the INTinclude-scripts and substitute in content.
     * Takes content and splits it content by <!--INT_SCRIPT.12345 --> and then puts the content back together.
     *
     * @param array $nonCacheableData
     */
    protected function processNonCacheableContentPartsAndSubstituteContentMarkers(array $nonCacheableData, ServerRequestInterface $request, string $incomingContent): string
    {
        $timeTracker = $this->getTimeTracker();
        $timeTracker->push('Split content');
        // Splits content with the key.
        $contentSplitByUncacheableMarkers = explode('<!--INT_SCRIPT.', $incomingContent);
        $timeTracker->setTSlogMessage('Parts: ' . count($contentSplitByUncacheableMarkers), LogLevel::INFO);
        $timeTracker->pull();
        $content = '';
        foreach ($contentSplitByUncacheableMarkers as $counter => $contentPart) {
            // If the split had a comment-end after 32 characters it's probably a split-string
            if (substr($contentPart, 32, 3) === '-->') {
                $nonCacheableKey = 'INT_SCRIPT.' . substr($contentPart, 0, 32);
                $nonCacheableConfig = [];
                foreach ($nonCacheableData as $nonCacheableDataKey => $nonCacheableDataValues) {
                    if ($nonCacheableDataValues['substKey'] === $nonCacheableKey) {
                        $nonCacheableConfig = $nonCacheableDataValues;
                        break;
                    }
                }
                if (!empty($nonCacheableConfig)) {
                    $label = 'Include ' . $nonCacheableConfig['type'];
                    $timeTracker->push($label);
                    $nonCacheableContent = '';
                    $contentObjectRendererForNonCacheable = unserialize($nonCacheableConfig['cObj']);
                    if ($contentObjectRendererForNonCacheable instanceof ContentObjectRenderer) {
                        $contentObjectRendererForNonCacheable->setRequest($request);
                        $nonCacheableContent = match ($nonCacheableConfig['type']) {
                            'COA' => $contentObjectRendererForNonCacheable->cObjGetSingle('COA', $nonCacheableConfig['conf']),
                            'FUNC' => $contentObjectRendererForNonCacheable->cObjGetSingle('USER', $nonCacheableConfig['conf']),
                            'POSTUSERFUNC' => $contentObjectRendererForNonCacheable->callUserFunction($nonCacheableConfig['postUserFunc'], $nonCacheableConfig['conf'], $nonCacheableConfig['content']),
                            default => '',
                        };
                    }
                    $content .= $nonCacheableContent;
                    $content .= substr($contentPart, 35);
                    $timeTracker->pull($nonCacheableContent);
                } else {
                    $content .= substr($contentPart, 35);
                }
            } elseif ($counter) {
                // If it's not the first entry (which would be "0" of the array keys), then re-add the INT_SCRIPT part
                $content .= '<!--INT_SCRIPT.' . $contentPart;
            } else {
                $content .= $contentPart;
            }
        }
        // Invoke permanent, general handlers. This has been implemented for nonce handling.
        foreach ($nonCacheableData as $item) {
            if (empty($item['permanent']) || empty($item['target'])) {
                continue;
            }
            $parameters = array_merge($item['parameters'] ?? [], ['content' => $content]);
            $content = GeneralUtility::callUserFunction($item['target'], $parameters) ?? $content;
        }
        return $content;
    }

    /**
     * Add HTTP headers to the response object.
     *
     * @internal
     */
    public function applyHttpHeadersToResponse(ServerRequestInterface $request, ResponseInterface $response, string $content): ResponseInterface
    {
        $pageParts = $request->getAttribute('frontend.page.parts');
        $response = $response->withHeader('Content-Type', $pageParts->getHttpContentType());
        $typoScriptConfigTree = $request->getAttribute('frontend.typoscript')->getConfigTree();
        if (empty($typoScriptConfigTree->getChildByName('disableLanguageHeader')?->getValue())) {
            // Set header for content language unless disabled
            // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
            $language = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();
            $response = $response->withHeader('Content-Language', (string)$language->getLocale());
        }

        // Add a Response header to show debug information if a page was fetched from cache
        if ($pageParts->hasPageContentBeenLoadedFromCache() && ($typoScriptConfigTree->getChildByName('debug')?->getValue() || !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']))) {
            // Prepare X-TYPO3-Debug-Cache HTTP header
            $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
            $timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
            $response = $response->withHeader(
                'X-TYPO3-Debug-Cache',
                'Cached page generated ' . date($dateFormat . ' ' . $timeFormat, $pageParts->getPageCacheGeneratedTimestamp()) . '. Expires ' . date($dateFormat . ' ' . $timeFormat, $pageParts->getPageCacheExpireTimestamp())
            );
        }

        // Set cache related headers to client (used to enable proxy / client caching!)
        $headers = $this->getCacheHeaders($request, $content);
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
    protected function getCacheHeaders(ServerRequestInterface $request, string $content): array
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
                    'Expires' => gmdate('D, d M Y H:i:s T', (min($GLOBALS['EXEC_TIME'] + $lifetime, PHP_INT_MAX))),
                    'ETag' => '"' . md5($content) . '"',
                    // Do not cache for private caches, but store in shared caches
                    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#:~:text=Age%3A%20100-,s%2Dmaxage,-The%20s%2Dmaxage
                    'Cache-Control' => 'max-age=0, s-maxage=' . $lifetime,
                    'Pragma' => 'public',
                ];
            } elseif ($sendCacheHeadersToClient) {
                $headers = [
                    'Expires' => gmdate('D, d M Y H:i:s T', (min($GLOBALS['EXEC_TIME'] + $lifetime, PHP_INT_MAX))),
                    'ETag' => '"' . md5($content) . '"',
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
                if ($request->getAttribute('frontend.page.parts')->hasNotCachedContentElements()) {
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
     * There can be no USER_INT objects on the page because they implicitly indicate dynamic content
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
        $pageParts = $request->getAttribute('frontend.page.parts');
        return $isCachingAllowed && !$pageParts->hasNotCachedContentElements() && !$this->context->getAspect('frontend.user')->isUserOrGroupSet();
    }

    /**
     * Converts relative paths in the HTML source to absolute paths for fileadmin/, typo3conf/ext/ and media/ folders.
     */
    protected function setAbsRefPrefixInContent(string $content, string $absRefPrefix): string
    {
        if ($absRefPrefix === '') {
            return $content;
        }
        $encodedAbsRefPrefix = htmlspecialchars($absRefPrefix, ENT_QUOTES | ENT_HTML5);
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
        return str_replace($search, $replace, $content);
    }

    /**
     * Get the cache timeout for the current page.
     */
    protected function get_cache_timeout(ServerRequestInterface $request): int
    {
        $pageInformation = $request->getAttribute('frontend.page.information');
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        return GeneralUtility::makeInstance(CacheLifetimeCalculator::class)
            ->calculateLifetimeForPage($pageInformation->getId(), $pageInformation->getPageRecord(), $typoScriptConfigArray, $this->context);
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
