<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\Http;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\CacheEntry;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\PolicyBag;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\ResponseService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceException;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator;
use TYPO3\CMS\Frontend\Cache\MetaDataState;
use TYPO3\CMS\Frontend\Cache\NonceValueSubstitution;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;
use TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent;
use TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent;
use TYPO3\CMS\Frontend\Page\PageParts;
use TYPO3\CMS\Frontend\Resource\PublicUrlPrefixer;

/**
 * This is the main entry point of the TypoScript driven standard front-end.
 *
 * "handle()" is called when all PSR-15 middlewares have set up the PSR-7 ServerRequest.
 *
 * Then, this class creates the Response with main body content using the ContentObjectRenderer
 * based on TypoScript configuration and to add main HTTP headers.
 */
readonly class RequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private ListenerProvider $listenerProvider,
        private TimeTracker $timeTracker,
        private SystemResourceFactory $systemResourceFactory,
        private SystemResourcePublisherInterface $resourcePublisher,
        private TypoScriptService $typoScriptService,
        private Context $context,
        private ResponseService $responseService,
        private PolicyProvider $policyProvider,
        // injecting this central stateful singleton. this is usually a smell, but ok in this case as exception.
        private PageRenderer $pageRenderer,
        #[Autowire(service: 'cache.pages')]
        private FrontendInterface $pageCache,
        private ConnectionPool $connectionPool,
        private CacheLifetimeCalculator $cacheLifetimeCalculator,
    ) {}

    /**
     * Handle frontend request after middlewares finished to create a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // b/w compat
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Forward `ConsumableNonce` containing a nonce to `PageRenderer`
        $nonce = $request->getAttribute('nonce');
        $nonce = $nonce instanceof ConsumableNonce ? $nonce : null;
        $this->pageRenderer->setNonce($nonce);
        $policyBag = $request->getAttribute('csp.policyBag');
        $policyBag = $policyBag instanceof PolicyBag ? $policyBag : null;

        // Make sure all FAL resources are prefixed with absPrefPrefix
        $this->listenerProvider->addListener(
            GeneratePublicUrlForResourceEvent::class,
            PublicUrlPrefixer::class,
            'prefixWithAbsRefPrefix'
        );

        $pageParts = $request->getAttribute('frontend.page.parts');
        if (!$pageParts instanceof PageParts) {
            throw new \RuntimeException('Attribute frontend.page.parts must be an instance of PageParts at this point', 1761829876);
        }

        $content = $pageParts->getContent();
        if (!$pageParts->hasPageContentBeenLoadedFromCache()) {
            $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
            $cacheInstruction = $request->getAttribute('frontend.cache.instruction');
            $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
            $pageInformation = $request->getAttribute('frontend.page.information');

            $this->timeTracker->push('Page generation');

            $docType = DocType::createFromConfigurationKey($typoScriptConfigArray['doctype'] ?? '');
            $this->pageRenderer->setDocType($docType);

            // Content generation
            $this->timeTracker->incStackPointer();
            $this->timeTracker->push('Page generation PAGE object');

            $content = $this->generatePageContent($request);

            $this->timeTracker->pull($this->timeTracker->LR ? $content : '');
            $this->timeTracker->decStackPointer();

            // In case the nonce value was actually consumed during the rendering process, add a
            // permanent substitution of the current value (that will be cached), with a future
            // value (that will be generated and issued in the HTTP CSP header).
            // Side-note: Nonce values that are consumed in non-cacheable parts (USER_INT/COA_INT)
            // are not handled here, since it would require writing the caches at the very end of
            // the whole frontend rendering process.
            if ($nonce !== null) {
                // prepare the policy in any case (even if nonce was not consumed)
                // (`AvoidContentSecurityPolicyNonceEventListener` adjusts the behavior)
                if ($policyBag !== null) {
                    $this->policyProvider->prepare($policyBag, $request, $content);
                }
                // register nonce substitution if explicitly enabled, otherwise (if undefined)
                // use it if nonce value was consumed or any non-cached content elements exist
                if ($policyBag?->behavior->useNonce
                    ?? (count($nonce) > 0 || $pageParts->hasNotCachedContentElements())
                ) {
                    $pageParts->addNotCachedContentElement([
                        'substKey' => null,
                        'target' => NonceValueSubstitution::class . '->substituteNonce',
                        'parameters' => ['nonce' => $nonce->value],
                        'permanent' => true,
                    ]);
                }
                if ($policyBag?->behavior->useNonce === false) {
                    $content = $this->responseService->dropNonceFromHtml($content, $nonce);
                }
            }

            $event = new AfterCacheableContentIsGeneratedEvent($request, $content, $cacheDataCollector->getPageCacheIdentifier(), $cacheInstruction->isCachingAllowed());
            $event = $this->eventDispatcher->dispatch($event);
            $content = $event->getContent();

            // Write page cache if allowed
            if ($event->isCachingEnabled()) {
                $pageId = $pageInformation->getId();
                $pageRecord = $pageInformation->getPageRecord();

                $lifetime = $this->cacheLifetimeCalculator->calculateLifetimeForPage($pageInformation->getId(), $pageInformation->getPageRecord(), $typoScriptConfigArray, $this->context);
                $cacheDataCollector->addCacheTags(new CacheTag('pageId_' . $pageId, $lifetime));
                if ($pageId !== $pageInformation->getContentFromPid()) {
                    // Respect the page cache when content from different pid is shown
                    $cacheDataCollector->addCacheTags(new CacheTag('pageId_' . $pageInformation->getContentFromPid(), $lifetime));
                }
                if ((int)($pageRecord['_LOCALIZED_UID'] ?? 0) > 0) {
                    // Respect the translation page id on translated pages
                    $cacheDataCollector->addCacheTags(new CacheTag('pageId_' . $pageRecord['_LOCALIZED_UID'], $lifetime));
                }
                if (!empty($pageRecord['cache_tags'])) {
                    $tags = GeneralUtility::trimExplode(',', $pageRecord['cache_tags'], true);
                    array_walk($tags, fn(string $tag) => $cacheDataCollector->addCacheTags(new CacheTag($tag, $lifetime)));
                }

                $cacheData = [
                    'page_id' => $pageId,
                    'content' => $content,
                    'contentType' => $pageParts->getHttpContentType(),
                    'INTincScript' => $pageParts->getNotCachedContentElementRegistry(),
                    'pageRendererSubstitutionHash' => $pageParts->getPageRendererSubstitutionHash(),
                    'pageRendererState' => serialize($this->pageRenderer->getState()),
                    'assetCollectorState' => serialize(GeneralUtility::makeInstance(AssetCollector::class)->getState()),
                    'pageTitleCache' => $pageParts->getPageTitle(),
                    'pageCacheGeneratedTimestamp' => $GLOBALS['EXEC_TIME'],
                    'metaDataState' => GeneralUtility::makeInstance(MetaDataState::class)->getState(),
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
                            $this->eventDispatcher->dispatch(
                                new AfterCachedPageIsPersistedEvent($request, $cacheDataCollector->getPageCacheIdentifier(), $content, $cacheTimeout)
                            );
                        }
                    )
                );
            }

            $this->updateSysLastChangedInPageRecord($request);

            $this->timeTracker->pull();
        }

        // Render non-cached page parts by replacing placeholders which are taken from cache or added during page generation
        if ($pageParts->hasNotCachedContentElements()) {
            $this->timeTracker->push('Non-cached objects');
            $content = $this->calculateNonCachedElements($request, $content);
            $this->timeTracker->pull();
        }

        $content = $this->displayPreviewInfoMessage($request, $content);

        // Create a default Response object and add headers and body to it
        $response = new Response();
        $response = $this->addHttpHeadersToResponse($request, $response, $content);
        $response->getBody()->write($content);
        return $response;
    }

    /**
     * Generates the main body part for the page, and if "config.disableAllHeaderCode" is not active, triggers
     * pageRenderer to evaluate includeCSS, headTag etc. TypoScript processing to populate the pageRenderer.
     */
    protected function generatePageContent(ServerRequestInterface $request): string
    {
        // Generate the main content between the <body> tags
        // This has to be done first, as some additional frontend related code could have been written?!
        $pageContent = $this->generatePageBodyContent($request);
        // If 'disableAllHeaderCode' is set, all the pageRenderer settings are not evaluated
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        if ($typoScriptConfigArray['disableAllHeaderCode'] ?? false) {
            return $pageContent;
        }
        // Now, populate pageRenderer with all additional data
        $this->processHtmlBasedRenderingSettings($request);
        // Add previously generated page content within the <body> tag afterwards
        $this->pageRenderer->addBodyContent(LF . $pageContent);
        $pageParts = $request->getAttribute('frontend.page.parts');
        if ($pageParts->hasNotCachedContentElements()) {
            // Render complete page, keep placeholders for JavaScript and CSS
            return $this->pageRenderer->renderPageWithUncachedObjects($pageParts->getPageRendererSubstitutionHash());
        }
        // Render complete page
        return $this->pageRenderer->render();
    }

    /**
     * Generates the main content part within <body> tags (except JS files/CSS files), this means:
     * render everything that can be cached, otherwise put placeholders for COA_INT/USER_INT objects
     * in the content that is processed later-on.
     */
    protected function generatePageBodyContent(ServerRequestInterface $request): string
    {
        $typoScriptPageSetupArray = $request->getAttribute('frontend.typoscript')->getPageArray();
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start($request->getAttribute('frontend.page.information')->getPageRecord(), 'pages');
        $pageContent = $contentObjectRenderer->cObjGet($typoScriptPageSetupArray) ?: '';
        if ($typoScriptPageSetupArray['wrap'] ?? false) {
            $pageContent = $contentObjectRenderer->wrap($pageContent, $typoScriptPageSetupArray['wrap']);
        }
        if ($typoScriptPageSetupArray['stdWrap.'] ?? false) {
            $pageContent = $contentObjectRenderer->stdWrap($pageContent, $typoScriptPageSetupArray['stdWrap.']);
        }
        return $pageContent;
    }

    /**
     * Calculate non cached elements and inline to given cacheable content.
     */
    protected function calculateNonCachedElements(ServerRequestInterface $request, string $content): string
    {
        $content = $this->recursivelyReplaceIntPlaceholdersInContent($request, $content);
        $this->timeTracker->push('Substitute header section');
        $titleTagContent = $this->generatePageTitle($request);
        $this->pageRenderer->setTitle($titleTagContent);
        $pageParts = $request->getAttribute('frontend.page.parts');
        $content = $this->pageRenderer->renderJavaScriptAndCssForProcessingOfUncachedContentObjects($content, $pageParts->getPageRendererSubstitutionHash());
        // Replace again, because header and footer data and page renderer replacements may introduce additional placeholders (see #44825)
        $content = $this->recursivelyReplaceIntPlaceholdersInContent($request, $content);
        $this->timeTracker->pull();
        return $content;
    }

    /**
     * At this point, the cacheable content has just been generated: Content is available but hasn't been added
     * to PageRenderer yet. The method is called after the "main" page content, since some JS may be inserted at that point
     * that has been registered by cacheable plugins.
     * PageRenderer is now populated with all <head> data and additional JavaScript/CSS/FooterData/HeaderData that can be cached.
     * Once finished, the content is added to the >addBodyContent() functionality.
     */
    protected function processHtmlBasedRenderingSettings(ServerRequestInterface $request): void
    {
        $typoScript = $request->getAttribute('frontend.typoscript');
        $typoScriptSetupArray = $typoScript->getSetupArray();
        $typoScriptConfigArray = $typoScript->getConfigArray();
        $typoScriptPageArray = $typoScript->getPageArray();

        if ($typoScriptConfigArray['moveJsFromHeaderToFooter'] ?? false) {
            $this->pageRenderer->enableMoveJsFromHeaderToFooter();
        }
        if ($typoScriptConfigArray['pageRendererTemplateFile'] ?? false) {
            try {
                $resource = $this->systemResourceFactory->createResource($typoScriptConfigArray['pageRendererTemplateFile']);
                $this->pageRenderer->setTemplateFile((string)$resource);
            } catch (SystemResourceException) {
                // Custom template is not set if createResource() throws
            }
        }
        $headerComment = trim($typoScriptConfigArray['headerComment'] ?? '');
        if ($headerComment) {
            $this->pageRenderer->addInlineComment("\t" . str_replace(LF, LF . "\t", $headerComment) . LF);
        }
        $htmlTagAttributes = [];

        // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
        $siteLanguage = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();
        if ($siteLanguage->getLocale()->isRightToLeftLanguageDirection()) {
            $htmlTagAttributes['dir'] = 'rtl';
        }
        $docType = $this->pageRenderer->getDocType();
        // Set document type
        $docTypeParts = [];
        $xmlDocument = true;
        // XML prologue
        $xmlPrologue = (string)($typoScriptConfigArray['xmlprologue'] ?? '');
        switch ($xmlPrologue) {
            case 'none':
                $xmlDocument = false;
                break;
            case 'xml_10':
            case 'xml_11':
            case '':
                if ($docType->isXmlCompliant()) {
                    $docTypeParts[] = $docType->getXmlPrologue();
                } else {
                    $xmlDocument = false;
                }
                break;
            default:
                $docTypeParts[] = $xmlPrologue;
        }
        // DTD
        if ($docType->getDoctypeDeclaration() !== '') {
            $docTypeParts[] = $docType->getDoctypeDeclaration();
        }
        if (!empty($docTypeParts)) {
            $this->pageRenderer->setXmlPrologAndDocType(implode(LF, $docTypeParts));
        }

        // See https://www.w3.org/International/questions/qa-html-language-declarations.en.html#attributes
        // and https://datatracker.ietf.org/doc/html/rfc5646
        $htmlTagAttributes[$docType->isXmlCompliant() ? 'xml:lang' : 'lang'] = $siteLanguage->getHreflang();

        if ($docType->isXmlCompliant() || $docType === DocType::html5 && $xmlDocument) {
            // We add this to HTML5 to achieve a slightly better backwards compatibility
            $htmlTagAttributes['xmlns'] = 'http://www.w3.org/1999/xhtml';
            if (is_array($typoScriptConfigArray['namespaces.'] ?? false)) {
                foreach ($typoScriptConfigArray['namespaces.'] as $prefix => $uri) {
                    // $uri gets htmlspecialchared later
                    $htmlTagAttributes['xmlns:' . htmlspecialchars($prefix)] = $uri;
                }
            }
        }

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start($request->getAttribute('frontend.page.information')->getPageRecord(), 'pages');

        $this->pageRenderer->setHtmlTag($this->generateHtmlTag($htmlTagAttributes, $typoScriptConfigArray, $contentObjectRenderer));

        $headTag = $typoScriptPageArray['headTag'] ?? '<head>';
        if (isset($typoScriptPageArray['headTag.'])) {
            $headTag = $contentObjectRenderer->stdWrap($headTag, $typoScriptPageArray['headTag.']);
        }
        $this->pageRenderer->setHeadTag($headTag);

        $this->pageRenderer->addInlineComment(GeneralUtility::makeInstance(Typo3Information::class)->getInlineHeaderComment());

        if ($typoScriptPageArray['shortcutIcon'] ?? false) {
            try {
                $favIconResource = $this->systemResourceFactory->createPublicResource($typoScriptPageArray['shortcutIcon']);
                if ($favIconResource instanceof SystemResourceInterface) {
                    $this->pageRenderer->setIconMimeType(' type="' . $favIconResource->getMimeType() . '"');
                }
                $this->pageRenderer->setFavIcon((string)$this->resourcePublisher->generateUri($favIconResource, $request));
            } catch (SystemResourceException) {
                // FavIcon is not set if sanitize() throws
            }
        }

        // Inline CSS from plugins, files, libraries and inline
        if (is_array($typoScriptSetupArray['plugin.'] ?? false)) {
            $stylesFromPlugins = '';
            foreach ($typoScriptSetupArray['plugin.'] as $key => $iCSScode) {
                if (is_array($iCSScode)) {
                    if (($iCSScode['_CSS_DEFAULT_STYLE'] ?? false) && empty($typoScriptConfigArray['removeDefaultCss'])) {
                        $cssDefaultStyle = $contentObjectRenderer->stdWrapValue('_CSS_DEFAULT_STYLE', $iCSScode);
                        $stylesFromPlugins .= '/* default styles for extension "' . substr($key, 0, -1) . '" */' . LF . $cssDefaultStyle . LF;
                    }
                }
            }
            if (!empty($stylesFromPlugins)) {
                $this->addCssToPageRenderer($request, $stylesFromPlugins, 'InlineDefaultCss');
            }
        }
        if (is_array($typoScriptPageArray['includeCSS.'] ?? false)) {
            foreach ($typoScriptPageArray['includeCSS.'] as $key => $cssResource) {
                if (is_array($cssResource)) {
                    continue;
                }
                $cssResourceConfig = $additionalAttributes = $typoScriptPageArray['includeCSS.'][$key . '.'] ?? [];
                if (isset($cssResourceConfig['if.']) && !$contentObjectRenderer->checkIf($cssResourceConfig['if.'])) {
                    continue;
                }
                if (!($cssResourceConfig['external'] ?? false)) {
                    try {
                        $cssResource = (string)$this->systemResourceFactory->createResource($cssResource);
                    } catch (SystemResourceException) {
                        continue;
                    }
                }
                $additionalAttributes = $this->cleanupAdditionalAttributeKeys($additionalAttributes, 'css');
                $this->pageRenderer->addCssFile(
                    $cssResource,
                    ($cssResourceConfig['alternate'] ?? false) ? 'alternate stylesheet' : 'stylesheet',
                    ($cssResourceConfig['media'] ?? false) ?: 'all',
                    ($cssResourceConfig['title'] ?? false) ?: '',
                    null,
                    (bool)($cssResourceConfig['forceOnTop'] ?? false),
                    $cssResourceConfig['allWrap'] ?? '',
                    null,
                    $cssResourceConfig['allWrap.']['splitChar'] ?? '|',
                    (bool)($cssResourceConfig['inline'] ?? false),
                    $additionalAttributes
                );
            }
        }
        if (is_array($typoScriptPageArray['includeCSSLibs.'] ?? false)) {
            foreach ($typoScriptPageArray['includeCSSLibs.'] as $key => $cssResource) {
                if (is_array($cssResource)) {
                    continue;
                }
                $cssResourceConfig = $additionalAttributes = $typoScriptPageArray['includeCSSLibs.'][$key . '.'] ?? [];
                if (isset($cssResourceConfig['if.']) && !$contentObjectRenderer->checkIf($cssResourceConfig['if.'])) {
                    continue;
                }
                if (!($cssResourceConfig['external'] ?? false)) {
                    try {
                        $cssResource = (string)$this->systemResourceFactory->createResource($cssResource);
                    } catch (SystemResourceException) {
                        continue;
                    }
                }
                $additionalAttributes = $this->cleanupAdditionalAttributeKeys($additionalAttributes, 'css');
                $this->pageRenderer->addCssLibrary(
                    $cssResource,
                    ($cssResourceConfig['alternate'] ?? false) ? 'alternate stylesheet' : 'stylesheet',
                    ($cssResourceConfig['media'] ?? false) ?: 'all',
                    ($cssResourceConfig['title'] ?? false) ?: '',
                    null,
                    (bool)($cssResourceConfig['forceOnTop'] ?? false),
                    $cssResourceConfig['allWrap'] ?? '',
                    null,
                    $cssResourceConfig['allWrap.']['splitChar'] ?? '|',
                    (bool)($cssResourceConfig['inline'] ?? false),
                    $additionalAttributes
                );
            }
        }
        $style = $contentObjectRenderer->cObjGet($typoScriptPageArray['cssInline.'] ?? null, 'cssInline.');
        if (trim($style)) {
            $this->addCssToPageRenderer($request, $style, 'additionalTSFEInlineStyle');
        }

        // JavaScript includes
        if (is_array($typoScriptPageArray['includeJSLibs.'] ?? false)) {
            foreach ($typoScriptPageArray['includeJSLibs.'] as $key => $jsResource) {
                if (is_array($jsResource)) {
                    continue;
                }
                $jsResourceConfig = $additionalAttributes = $typoScriptPageArray['includeJSLibs.'][$key . '.'] ?? [];
                if (isset($jsResourceConfig['if.']) && !$contentObjectRenderer->checkIf($jsResourceConfig['if.'])) {
                    continue;
                }
                if (!($jsResourceConfig['external'] ?? false)) {
                    try {
                        $jsResource = (string)$this->systemResourceFactory->createResource($jsResource);
                    } catch (SystemResourceException) {
                        continue;
                    }
                }
                $crossOrigin = (string)($jsResourceConfig['crossorigin'] ?? '');
                if ($crossOrigin === '' && ($jsResourceConfig['integrity'] ?? false) && ($jsResourceConfig['external'] ?? false)) {
                    $crossOrigin = 'anonymous';
                }
                $additionalAttributes = $this->cleanupAdditionalAttributeKeys($additionalAttributes, 'js');
                $this->pageRenderer->addJsLibrary(
                    $key,
                    $jsResource,
                    $jsResourceConfig['type'] ?? null,
                    null,
                    (bool)($jsResourceConfig['forceOnTop'] ?? false),
                    $jsResourceConfig['allWrap'] ?? '',
                    null,
                    $jsResourceConfig['allWrap.']['splitChar'] ?? '|',
                    (bool)($jsResourceConfig['async'] ?? false),
                    $jsResourceConfig['integrity'] ?? '',
                    (bool)($jsResourceConfig['defer'] ?? false),
                    $crossOrigin,
                    (bool)($jsResourceConfig['nomodule'] ?? false),
                    $additionalAttributes
                );
            }
        }
        if (is_array($typoScriptPageArray['includeJSFooterlibs.'] ?? false)) {
            foreach ($typoScriptPageArray['includeJSFooterlibs.'] as $key => $jsResource) {
                if (is_array($jsResource)) {
                    continue;
                }
                $jsResourceConfig = $additionalAttributes = $typoScriptPageArray['includeJSFooterlibs.'][$key . '.'] ?? [];
                if (isset($jsResourceConfig['if.']) && !$contentObjectRenderer->checkIf($jsResourceConfig['if.'])) {
                    continue;
                }
                if (!($jsResourceConfig['external'] ?? false)) {
                    try {
                        $jsResource = (string)$this->systemResourceFactory->createResource($jsResource);
                    } catch (SystemResourceException) {
                        continue;
                    }
                }
                $crossOrigin = (string)($jsResourceConfig['crossorigin'] ?? '');
                if ($crossOrigin === '' && ($jsResourceConfig['integrity'] ?? false) && ($jsResourceConfig['external'] ?? false)) {
                    $crossOrigin = 'anonymous';
                }
                $additionalAttributes = $this->cleanupAdditionalAttributeKeys($additionalAttributes, 'js');
                $this->pageRenderer->addJsFooterLibrary(
                    $key,
                    $jsResource,
                    $jsResourceConfig['type'] ?? null,
                    null,
                    (bool)($jsResourceConfig['forceOnTop'] ?? false),
                    $jsResourceConfig['allWrap'] ?? '',
                    null,
                    $jsResourceConfig['allWrap.']['splitChar'] ?? '|',
                    (bool)($jsResourceConfig['async'] ?? false),
                    $jsResourceConfig['integrity'] ?? '',
                    (bool)($jsResourceConfig['defer'] ?? false),
                    $crossOrigin,
                    (bool)($jsResourceConfig['nomodule'] ?? false),
                    $additionalAttributes
                );
            }
        }
        if (is_array($typoScriptPageArray['includeJS.'] ?? false)) {
            foreach ($typoScriptPageArray['includeJS.'] as $key => $jsResource) {
                if (is_array($jsResource)) {
                    continue;
                }
                $jsResourceConfig = $typoScriptPageArray['includeJS.'][$key . '.'] ?? [];
                if (isset($jsResourceConfig['if.']) && !$contentObjectRenderer->checkIf($jsResourceConfig['if.'])) {
                    continue;
                }
                if (!($jsResourceConfig['external'] ?? false)) {
                    try {
                        $jsResource = (string)$this->systemResourceFactory->createResource($jsResource);
                    } catch (SystemResourceException) {
                        continue;
                    }
                }
                $crossOrigin = (string)($jsResourceConfig['crossorigin'] ?? '');
                if ($crossOrigin === '' && ($jsResourceConfig['integrity'] ?? false) && ($jsResourceConfig['external'] ?? false)) {
                    $crossOrigin = 'anonymous';
                }
                $this->pageRenderer->addJsFile(
                    $jsResource,
                    $jsResourceConfig['type'] ?? null,
                    null,
                    (bool)($jsResourceConfig['forceOnTop'] ?? false),
                    $jsResourceConfig['allWrap'] ?? '',
                    null,
                    $jsResourceConfig['allWrap.']['splitChar'] ?? '|',
                    (bool)($jsResourceConfig['async'] ?? false),
                    $jsResourceConfig['integrity'] ?? '',
                    (bool)($jsResourceConfig['defer'] ?? false),
                    $crossOrigin,
                    (bool)($jsResourceConfig['nomodule'] ?? false),
                    // @todo: This does not use the same logic as with "additionalAttributes" above. Also not documented correctly.
                    $jsResourceConfig['data.'] ?? []
                );
            }
        }
        if (is_array($typoScriptPageArray['includeJSFooter.'] ?? false)) {
            foreach ($typoScriptPageArray['includeJSFooter.'] as $key => $jsResource) {
                if (is_array($jsResource)) {
                    continue;
                }
                $jsResourceConfig = $typoScriptPageArray['includeJSFooter.'][$key . '.'] ?? [];
                if (isset($jsResourceConfig['if.']) && !$contentObjectRenderer->checkIf($jsResourceConfig['if.'])) {
                    continue;
                }
                if (!($jsResourceConfig['external'] ?? false)) {
                    try {
                        $jsResource = (string)$this->systemResourceFactory->createResource($jsResource);
                    } catch (SystemResourceException) {
                        continue;
                    }
                }
                $crossOrigin = (string)($jsResourceConfig['crossorigin'] ?? '');
                if ($crossOrigin === '' && ($jsResourceConfig['integrity'] ?? false) && ($jsResourceConfig['external'] ?? false)) {
                    $crossOrigin = 'anonymous';
                }
                $this->pageRenderer->addJsFooterFile(
                    $jsResource,
                    $jsResourceConfig['type'] ?? null,
                    null,
                    (bool)($jsResourceConfig['forceOnTop'] ?? false),
                    $jsResourceConfig['allWrap'] ?? '',
                    null,
                    $jsResourceConfig['allWrap.']['splitChar'] ?? '|',
                    (bool)($jsResourceConfig['async'] ?? false),
                    $jsResourceConfig['integrity'] ?? '',
                    (bool)($jsResourceConfig['defer'] ?? false),
                    $crossOrigin,
                    (bool)($jsResourceConfig['nomodule'] ?? false),
                    // @todo: This does not use the same logic as with "additionalAttributes" above. Also not documented correctly.
                    $jsResourceConfig['data.'] ?? []
                );
            }
        }

        // Header and footer data
        if (is_array($typoScriptPageArray['headerData.'] ?? false)) {
            $this->pageRenderer->addHeaderData($contentObjectRenderer->cObjGet($typoScriptPageArray['headerData.'], 'headerData.'));
        }
        if (is_array($typoScriptPageArray['footerData.'] ?? false)) {
            $this->pageRenderer->addFooterData($contentObjectRenderer->cObjGet($typoScriptPageArray['footerData.'], 'footerData.'));
        }

        $titleTagContent = $this->generatePageTitle($request);
        $this->pageRenderer->setTitle($titleTagContent);

        // @internal hook for EXT:seo, will be gone soon, do not use it in your own extensions
        $_params = ['request' => $request];
        $_ref = null;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Frontend\Page\PageGenerator']['generateMetaTags'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $_ref);
        }

        $this->generateHrefLangTags($request);
        $this->generateMetaTagHtml($typoScriptPageArray['meta.'] ?? [], $contentObjectRenderer);

        // Javascript inline and inline footer code
        $inlineJS = implode(LF, $contentObjectRenderer->cObjGetSeparated($typoScriptPageArray['jsInline.'] ?? null, 'jsInline.'));
        $inlineFooterJs = implode(LF, $contentObjectRenderer->cObjGetSeparated($typoScriptPageArray['jsFooterInline.'] ?? null, 'jsFooterInline.'));

        if (($typoScriptConfigArray['removeDefaultJS'] ?? 'external') === 'external') {
            // "removeDefaultJS" is "external" by default
            // This keeps inlineJS from *_INT Objects from being moved to external files.
            // At this point in frontend rendering *_INT Objects only have placeholders instead
            // of actual content. Moving these placeholders to external files would break the JS file with
            // syntax errors due to the placeholders, and the needed JS would never get included to the page.
            // Therefore, inlineJS from *_INT Objects must not be moved to external files but kept internal.
            $inlineJSint = '';
            $this->stripIntObjectPlaceholder($inlineJS, $inlineJSint);
            if ($inlineJSint) {
                $this->pageRenderer->addJsInlineCode('TS_inlineJSint', $inlineJSint);
            }
            if (trim($inlineJS)) {
                $this->pageRenderer->addJsFile(GeneralUtility::writeJavaScriptContentToTemporaryFile($inlineJS), null);
            }
            if ($inlineFooterJs) {
                $inlineFooterJSint = '';
                $this->stripIntObjectPlaceholder($inlineFooterJs, $inlineFooterJSint);
                if ($inlineFooterJSint) {
                    $this->pageRenderer->addJsFooterInlineCode('TS_inlineFooterJSint', $inlineFooterJSint);
                }
                $this->pageRenderer->addJsFooterFile(GeneralUtility::writeJavaScriptContentToTemporaryFile($inlineFooterJs), null);
            }
        } else {
            // Include only inlineJS
            if ($inlineJS) {
                $this->pageRenderer->addJsInlineCode('TS_inlineJS', $inlineJS);
            }
            if ($inlineFooterJs) {
                $this->pageRenderer->addJsFooterInlineCode('TS_inlineFooter', $inlineFooterJs);
            }
        }
        if (is_array($typoScriptPageArray['inlineLanguageLabelFiles.'] ?? false)) {
            foreach ($typoScriptPageArray['inlineLanguageLabelFiles.'] as $key => $languageFile) {
                if (is_array($languageFile)) {
                    continue;
                }
                $languageFileConfig = $typoScriptPageArray['inlineLanguageLabelFiles.'][$key . '.'] ?? [];
                if (isset($languageFileConfig['if.']) && !$contentObjectRenderer->checkIf($languageFileConfig['if.'])) {
                    continue;
                }
                $this->pageRenderer->addInlineLanguageLabelFile(
                    $languageFile,
                    ($languageFileConfig['selectionPrefix'] ?? false) ? $languageFileConfig['selectionPrefix'] : '',
                    ($languageFileConfig['stripFromSelectionName'] ?? false) ? $languageFileConfig['stripFromSelectionName'] : ''
                );
            }
        }
        if (is_array($typoScriptPageArray['inlineSettings.'] ?? false)) {
            $this->pageRenderer->addInlineSettingArray('TS', $typoScriptPageArray['inlineSettings.']);
        }
        // Header complete, now the body tag is added so the regular content can be applied later-on
        if ($typoScriptConfigArray['disableBodyTag'] ?? false) {
            $this->pageRenderer->addBodyContent(LF);
        } else {
            $bodyTag = '<body>';
            if ($typoScriptPageArray['bodyTag'] ?? false) {
                $bodyTag = $typoScriptPageArray['bodyTag'];
            } elseif ($typoScriptPageArray['bodyTagCObject'] ?? false) {
                $bodyTag = $contentObjectRenderer->cObjGetSingle($typoScriptPageArray['bodyTagCObject'], $typoScriptPageArray['bodyTagCObject.'] ?? [], 'bodyTagCObject');
            }
            if (trim($typoScriptPageArray['bodyTagAdd'] ?? '')) {
                $bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' ' . trim($typoScriptPageArray['bodyTagAdd']) . '>';
            }
            $this->pageRenderer->addBodyContent(LF . $bodyTag);
        }
    }

    /**
     * Searches for placeholder created from *_INT cObjects, removes them from
     * $searchString and merges them to $intObjects
     *
     * @param string $searchString The String which should be cleaned from int-object markers
     * @param string $intObjects The String the found int-placeholders are moved to (for further processing)
     */
    protected function stripIntObjectPlaceholder(&$searchString, &$intObjects)
    {
        $tempArray = [];
        preg_match_all('/\\<\\!--INT_SCRIPT.[a-z0-9]*--\\>/', $searchString, $tempArray);
        $searchString = preg_replace('/\\<\\!--INT_SCRIPT.[a-z0-9]*--\\>/', '', $searchString);
        $intObjects = implode('', $tempArray[0]);
    }

    /**
     * Generate meta tags from meta tag TypoScript
     *
     * @param array $metaTagTypoScript TypoScript configuration for meta tags
     */
    protected function generateMetaTagHtml(array $metaTagTypoScript, ContentObjectRenderer $cObj)
    {
        $conf = $this->typoScriptService->convertTypoScriptArrayToPlainArray($metaTagTypoScript);
        foreach ($conf as $key => $properties) {
            $replace = false;
            if (is_array($properties)) {
                $nodeValue = $properties['_typoScriptNodeValue'] ?? '';
                $value = trim((string)$cObj->stdWrap($nodeValue, $metaTagTypoScript[$key . '.']));
                if ($value === '' && !empty($properties['value'])) {
                    $value = $properties['value'];
                    $replace = false;
                }
            } else {
                $value = $properties;
            }

            $attribute = 'name';
            if ((is_array($properties) && !empty($properties['httpEquivalent'])) || strtolower($key) === 'refresh') {
                $attribute = 'http-equiv';
            }
            if (is_array($properties) && !empty($properties['attribute'])) {
                $attribute = $properties['attribute'];
            }
            if (is_array($properties) && !empty($properties['replace'])) {
                $replace = true;
            }

            if (!is_array($value)) {
                $value = (array)$value;
            }
            foreach ($value as $subValue) {
                if (trim($subValue ?? '') !== '') {
                    $this->pageRenderer->setMetaTag($attribute, $key, $subValue, [], $replace);
                }
            }
        }
    }

    /**
     * Adds inline CSS code, by respecting the inlineStyle2TempFile option
     *
     * @param string $cssStyles the inline CSS styling
     * @param string $inlineBlockName the block name to add it
     */
    protected function addCssToPageRenderer(ServerRequestInterface $request, string $cssStyles, string $inlineBlockName): void
    {
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        // This option is enabled by default on purpose
        if (empty($typoScriptConfigArray['inlineStyle2TempFile'] ?? true)) {
            $this->pageRenderer->addCssInlineBlock($inlineBlockName, $cssStyles);
        } else {
            $this->pageRenderer->addCssFile('PKG:typo3/app:' . Environment::getRelativePublicPath() . GeneralUtility::writeStyleSheetContentToTemporaryFile($cssStyles));
        }
    }

    /**
     * Generates the <html> tag by evaluating TypoScript configuration, usually found via:
     *
     * - Adding extra attributes in addition to pre-generated ones (e.g. "dir")
     *     config.htmlTag.attributes.no-js = 1
     *     config.htmlTag.attributes.empty-attribute =
     *
     * - Adding one full string (no stdWrap!) to the "<html $htmlTagAttributes {config.htmlTag_setParams}>" tag
     *     config.htmlTag_setParams = string|"none"
     *
     *   If config.htmlTag_setParams = none is set, even the pre-generated values are not added at all anymore.
     *
     * - "config.htmlTag_stdWrap" always applies over the whole compiled tag.
     *
     * @param array $htmlTagAttributes pre-generated attributes by doctype/direction etc. values.
     * @param array $configuration the TypoScript configuration "config." array
     * @param ContentObjectRenderer $cObj
     * @return string the full <html> tag as string
     */
    protected function generateHtmlTag(array $htmlTagAttributes, array $configuration, ContentObjectRenderer $cObj): string
    {
        if (is_array($configuration['htmlTag.']['attributes.'] ?? null)) {
            $attributeString = '';
            foreach ($configuration['htmlTag.']['attributes.'] as $attributeName => $value) {
                if (str_ends_with($attributeName, '.')) {
                    // Skip this one, but only if the default value is set
                    if (isset($configuration['htmlTag.']['attributes.'][rtrim($attributeName, '.')])) {
                        continue;
                    }
                    $attributeName = rtrim($attributeName, '.');
                    $value = '';

                }
                if (is_array($configuration['htmlTag.']['attributes.'][$attributeName . '.'] ?? null)) {
                    $value = $cObj->stdWrap($value, $configuration['htmlTag.']['attributes.'][$attributeName . '.']);
                }
                $attributeString .= ' ' . htmlspecialchars($attributeName) . ($value !== '' ? '="' . htmlspecialchars((string)$value) . '"' : '');
                // If e.g. "htmlTag.attributes.dir" is set, make sure it is not added again with "implodeAttributes()"
                if (isset($htmlTagAttributes[$attributeName])) {
                    unset($htmlTagAttributes[$attributeName]);
                }
            }
            $attributeString = ltrim(GeneralUtility::implodeAttributes($htmlTagAttributes) . $attributeString);
        } elseif (($configuration['htmlTag_setParams'] ?? '') === 'none') {
            $attributeString = '';
        } elseif (isset($configuration['htmlTag_setParams'])) {
            $attributeString = $configuration['htmlTag_setParams'];
        } else {
            $attributeString = GeneralUtility::implodeAttributes($htmlTagAttributes);
        }
        $htmlTag = '<html' . ($attributeString ? ' ' . $attributeString : '') . '>';
        if (isset($configuration['htmlTag_stdWrap.'])) {
            $htmlTag = $cObj->stdWrap($htmlTag, $configuration['htmlTag_stdWrap.']);
        }
        return $htmlTag;
    }

    protected function generateHrefLangTags(ServerRequestInterface $request): void
    {
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        if ($typoScriptConfigArray['disableHrefLang'] ?? false) {
            return;
        }
        $endingSlash = $this->pageRenderer->getDocType()->isXmlCompliant() ? '/' : '';
        $hrefLangs = $this->eventDispatcher->dispatch(new ModifyHrefLangTagsEvent($request))->getHrefLangs();
        if (count($hrefLangs) > 1) {
            $data = [];
            foreach ($hrefLangs as $hrefLang => $href) {
                $data[] = sprintf('<link %s%s>', GeneralUtility::implodeAttributes([
                    'rel' => 'alternate',
                    'hreflang' => $hrefLang,
                    'href' => $href,
                ], true), $endingSlash);
            }
            $this->pageRenderer->addHeaderData(implode(LF, $data));
        }
    }

    /**
     * Include the preview block in case we're looking at a hidden page in the LIVE workspace
     */
    protected function displayPreviewInfoMessage(ServerRequestInterface $request, string $content): string
    {
        $isInWorkspace = $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
        $isInPreviewMode = $this->context->hasAspect('frontend.preview') && $this->context->getPropertyFromAspect('frontend.preview', 'isPreview');
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        if (!$isInPreviewMode || $isInWorkspace || ($typoScriptConfigArray['disablePreviewNotification'] ?? false)) {
            return $content;
        }
        if ($typoScriptConfigArray['message_preview'] ?? '') {
            $message = $typoScriptConfigArray['message_preview'];
        } else {
            $label = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tsfe.xlf:preview');
            $styles = [];
            $styles[] = 'position: fixed';
            $styles[] = 'top: 15px';
            $styles[] = 'right: 15px';
            $styles[] = 'padding: 8px 18px';
            $styles[] = 'background: #fff3cd';
            $styles[] = 'border: 1px solid #ffeeba';
            $styles[] = 'font-family: sans-serif';
            $styles[] = 'font-size: .875em';
            $styles[] = 'font-weight: bold';
            $styles[] = 'color: #856404';
            $styles[] = 'z-index: 20000';
            $styles[] = 'user-select: none';
            $styles[] = 'pointer-events: none';
            $styles[] = 'text-align: center';
            $styles[] = 'border-radius: 2px';
            $message = '<div id="typo3-preview-info" style="' . implode(';', $styles) . '">' . htmlspecialchars($label) . '</div>';
        }
        if (!empty($message)) {
            $content = str_ireplace('</body>', $message . '</body>', $content);
        }
        return $content;
    }

    /**
     * Filter out known TypoScript attributes so that they are NOT passed along
     * to a <link rel...> or <script...> tag as additional attributes.
     * NOTE: Some keys are unset here even though they are valid attributes to
     * the <link> or <script> tag. This is because these extra attribute keys are specifically
     * evaluated, in the addCssFile/addCssLibrary/addJsFile/addJsFooterLibrary methods.
     *
     * @param string $cleanupType: Indicate if "css" <link> or "js" <script> is cleaned up.
     */
    private function cleanupAdditionalAttributeKeys(array $additionalAttributes, string $cleanupType): array
    {
        // Common (CSS+JS)
        unset(
            $additionalAttributes['if.'],
            $additionalAttributes['external'],
            $additionalAttributes['allWrap'],
            $additionalAttributes['allWrap.'],
            $additionalAttributes['forceOnTop']
        );
        if ($cleanupType === 'css') {
            unset(
                $additionalAttributes['alternate'],
                $additionalAttributes['media'],
                $additionalAttributes['title'],
                $additionalAttributes['inline'],
                $additionalAttributes['internal']
            );
        }
        if ($cleanupType === 'js') {
            unset(
                $additionalAttributes['type'],
                $additionalAttributes['crossorigin'],
                $additionalAttributes['integrity'],
                $additionalAttributes['defer'],
                $additionalAttributes['nomodule']
            );
        }
        return $additionalAttributes;
    }

    /**
     * Setting the SYS_LASTCHANGED value in the page record: This value is set to the highest timestamp
     * of records rendered on the page. This includes all records with no regard to hidden records, user
     * protection and so on. This updates a translated "pages" record (_LOCALIZED_UID) if the Frontend
     * is called with a translation.
     *
     * @see ContentObjectRenderer::lastChanged()
     */
    protected function updateSysLastChangedInPageRecord(ServerRequestInterface $request): void
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
            $connection = $this->connectionPool->getConnectionForTable('pages');
            $pageId = $pageRecord['_LOCALIZED_UID'] ?? $pageInformation->getId();
            $connection->update('pages', ['SYS_LASTCHANGED' => $pageParts->getLastChanged()], ['uid' => (int)$pageId]);
        }
    }

    /**
     * Create and return page title. This ends up as HTML <head> <title> tag.
     *
     * @todo: This is currently called twice: Once after calculation of cached elements,
     *        and (if exists) a second time after calculating non-cached elements. This
     *        is due to PageRenderer rendering logic being triggered at unfortunate places.
     *        The entire logic is odd and should be modeled and routed in a better way
     *        when PageRenderer is disentangled.
     */
    protected function generatePageTitle(ServerRequestInterface $request): string
    {
        $site = $request->getAttribute('site');
        $language = $request->getAttribute('language') ?? $site->getDefaultLanguage();
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        $pageParts = $request->getAttribute('frontend.page.parts');
        $pageInformation = $request->getAttribute('frontend.page.information');

        // config.noPageTitle = 2 - means do not render the page title
        if ((int)($typoScriptConfigArray['noPageTitle'] ?? 0) === 2) {
            return '';
        }

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($request);
        $contentObjectRenderer->start($pageInformation->getPageRecord(), 'pages');

        // Check for a custom pageTitleSeparator, and perform stdWrap on it
        $pageTitleSeparator = (string)$contentObjectRenderer->stdWrapValue('pageTitleSeparator', $typoScriptConfigArray);
        if ($pageTitleSeparator !== '' && $pageTitleSeparator === ($typoScriptConfigArray['pageTitleSeparator'] ?? '')) {
            $pageTitleSeparator .= ' ';
        }

        $titleProvider = GeneralUtility::makeInstance(PageTitleProviderManager::class);
        $titleProvider->setPageTitleCache($pageParts->getPageTitle());
        $pageTitle = $titleProvider->getTitle($request);
        $pageParts->setPageTitle($titleProvider->getPageTitleCache());

        $noPageTitle = (bool)($typoScriptConfigArray['noPageTitle'] ?? false);
        $showPageTitleFirst = (bool)($typoScriptConfigArray['pageTitleFirst'] ?? false);
        $showWebsiteTitle = (bool)($typoScriptConfigArray['showWebsiteTitle'] ?? true);

        $websiteTitle = '';
        if ($showWebsiteTitle) {
            if (trim($language->getWebsiteTitle()) !== '') {
                $websiteTitle = trim($language->getWebsiteTitle());
            } else {
                $siteConfiguration = $site->getConfiguration();
                if (trim($siteConfiguration['websiteTitle'] ?? '') !== '') {
                    $websiteTitle = trim($siteConfiguration['websiteTitle']);
                }
            }
        }
        $pageTitle = $noPageTitle ? '' : $pageTitle;
        if ($pageTitle === '' || $websiteTitle === '') {
            // only show a separator if there are both site title and page title
            $pageTitleSeparator = '';
        } elseif (empty($pageTitleSeparator)) {
            // use the default separator if none given
            $pageTitleSeparator = ': ';
        }
        if ($showPageTitleFirst) {
            $titleTagContent = $pageTitle . $pageTitleSeparator . $websiteTitle;
        } else {
            $titleTagContent = $websiteTitle . $pageTitleSeparator . $pageTitle;
        }

        if (isset($typoScriptConfigArray['pageTitle.']) && is_array($typoScriptConfigArray['pageTitle.'])) {
            // stdWrap for pageTitle if set in config.pageTitle.
            $pageTitleStdWrapArray = [
                'pageTitle' => $titleTagContent,
                'pageTitle.' => $typoScriptConfigArray['pageTitle.'],
            ];
            $titleTagContent = (string)$contentObjectRenderer->stdWrapValue('pageTitle', $pageTitleStdWrapArray);
        }

        return $titleTagContent;
    }

    /**
     * Replace non-cached element placeholders ("INT" placeholders) in content. In case the replacement
     * adds additional placeholders, it loops until no new placeholders are found.
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
     * Splits content by <!--INT_SCRIPT.12345 --> and puts the content back
     * together with content from processed content elements.
     */
    protected function processNonCacheableContentPartsAndSubstituteContentMarkers(array $nonCacheableData, ServerRequestInterface $request, string $incomingContent): string
    {
        $this->timeTracker->push('Split content');
        // Splits content with the key.
        $contentSplitByUncacheableMarkers = explode('<!--INT_SCRIPT.', $incomingContent);
        $this->timeTracker->setTSlogMessage('Parts: ' . count($contentSplitByUncacheableMarkers), LogLevel::INFO);
        $this->timeTracker->pull();
        $content = '';
        foreach ($contentSplitByUncacheableMarkers as $counter => $contentPart) {
            // If the split had a comment-end after 32 characters it's probably a split-string
            if (substr($contentPart, 32, 3) === '-->') {
                $nonCacheableKey = 'INT_SCRIPT.' . substr($contentPart, 0, 32);
                $nonCacheableConfig = [];
                foreach ($nonCacheableData as $nonCacheableDataValues) {
                    if ($nonCacheableDataValues['substKey'] === $nonCacheableKey) {
                        $nonCacheableConfig = $nonCacheableDataValues;
                        break;
                    }
                }
                if (!empty($nonCacheableConfig)) {
                    $label = 'Include ' . $nonCacheableConfig['type'];
                    $this->timeTracker->push($label);
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
                    $this->timeTracker->pull($nonCacheableContent);
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
            // @todo: Separate nonce handling from INT handling.
            if (empty($item['permanent']) || empty($item['target'])) {
                continue;
            }
            $parameters = array_merge($item['parameters'] ?? [], ['content' => $content]);
            $content = GeneralUtility::callUserFunction($item['target'], $parameters) ?? $content;
        }
        return $content;
    }

    protected function addHttpHeadersToResponse(ServerRequestInterface $request, ResponseInterface $response, string $content): ResponseInterface
    {
        $pageParts = $request->getAttribute('frontend.page.parts');
        $typoScriptConfigTree = $request->getAttribute('frontend.typoscript')->getConfigTree();
        // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
        $language = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();

        $response = $response->withHeader('Content-Type', $pageParts->getHttpContentType());

        // Set header for content language unless disabled
        if (empty($typoScriptConfigTree->getChildByName('disableLanguageHeader')?->getValue())) {
            $response = $response->withHeader('Content-Language', (string)$language->getLocale());
        }

        // Add a Response header to show debug information if a page was fetched from cache
        if ($pageParts->hasPageContentBeenLoadedFromCache()
            && ($typoScriptConfigTree->getChildByName('debug')?->getValue() || !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']))
        ) {
            $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
            $timeFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
            $response = $response->withHeader(
                'X-TYPO3-Debug-Cache',
                'Cached page generated ' . date($dateFormat . ' ' . $timeFormat, $pageParts->getPageCacheGeneratedTimestamp()) . '.'
                    . ' Expires ' . date($dateFormat . ' ' . $timeFormat, $pageParts->getPageCacheExpireTimestamp())
            );
        }

        // Add cache related headers for proxy / client caching
        $headers = $this->getClientCacheHeaders($request, $content);
        foreach ($headers as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        // Add additional headers if configured via TypoScript
        $additionalHeaders = $this->getAdditionalHeadersFromTypoScript($request);
        foreach ($additionalHeaders as $headerConfig) {
            if ($headerConfig['statusCode']) {
                $response = $response->withStatus((int)$headerConfig['statusCode']);
            }
            if ($headerConfig['replace']) {
                $response = $response->withHeader($headerConfig['header'], $headerConfig['value']);
            } else {
                $response = $response->withAddedHeader($headerConfig['header'], $headerConfig['value']);
            }
        }

        return $response;
    }

    protected function getClientCacheHeaders(ServerRequestInterface $request, string $content): array
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        $pageParts = $request->getAttribute('frontend.page.parts');
        $cacheInstruction = $request->getAttribute('frontend.cache.instruction');
        $lifetime = $request->getAttribute('frontend.cache.collector')->resolveLifetime();
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();

        $clientCachingPossible = $cacheInstruction->isCachingAllowed() // server side caching denied for some reason, client follows
            && !$pageParts->hasNotCachedContentElements() // having not cached elements must disable client caching
            && !$this->context->getAspect('frontend.user')->isUserOrGroupSet() // no client caching with logged in FE user
            && $this->context->getPropertyFromAspect('workspace', 'isLive', true); // live workspace

        if ($clientCachingPossible
            && !$this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)
            && (($typoScriptConfigArray['sendCacheHeadersForSharedCaches'] ?? '') === 'force'
                || (($typoScriptConfigArray['sendCacheHeadersForSharedCaches'] ?? '') === 'auto' && $normalizedParams->isBehindReverseProxy()))
        ) {
            // Client side caching possible, user not logged in to BE,
            // TypoScript "config.sendCacheHeadersForSharedCaches" takes precedence over "config.sendCacheHeaders" below.
            return [
                'Expires' => gmdate('D, d M Y H:i:s T', (min($GLOBALS['EXEC_TIME'] + $lifetime, PHP_INT_MAX))),
                'ETag' => '"' . md5($content) . '"',
                // Do not cache for private caches, but store in shared caches
                // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#:~:text=Age%3A%20100-,s%2Dmaxage,-The%20s%2Dmaxage
                'Cache-Control' => 'max-age=0, s-maxage=' . $lifetime,
                'Pragma' => 'public',
            ];
        }

        if ($clientCachingPossible
            && !$this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)
            && !empty($typoScriptConfigArray['sendCacheHeaders'])
        ) {
            // Client side caching possible, user not logged in to BE, TypoScript "config.sendCacheHeaders" configured.
            return [
                'Expires' => gmdate('D, d M Y H:i:s T', (min($GLOBALS['EXEC_TIME'] + $lifetime, PHP_INT_MAX))),
                'ETag' => '"' . md5($content) . '"',
                'Cache-Control' => 'max-age=' . $lifetime,
                'Pragma' => 'public',
            ];
        }

        if ($this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)) {
            // User is logged in to BE. Add client cache information details for admin panel.
            if ($clientCachingPossible) {
                $this->timeTracker->setTSlogMessage('Cache-headers with max-age "' . $lifetime . '" would have been sent');
            } else {
                $noClientCacheReasons = [];
                if (!$cacheInstruction->isCachingAllowed()) {
                    $noClientCacheReasons[] = 'Caching disabled.';
                }
                if ($pageParts->hasNotCachedContentElements()) {
                    $noClientCacheReasons[] = 'Not cache elements on page.';
                }
                if ($this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn', false)) {
                    $noClientCacheReasons[] = 'Frontend user logged in.';
                }
                if ($this->context->getPropertyFromAspect('workspace', 'isOffline', false)) {
                    $noClientCacheReasons[] = 'Draft workspace selected.';
                }
                $this->timeTracker->setTSlogMessage('Request would not allow client side caching: "' . implode(' ', $noClientCacheReasons) . '"', LogLevel::NOTICE);
            }
        }

        // Fallback: No client cache headers by default. Explicitly set 'private, no-store'
        // so a .htaccess does not accidentally add unwanted default headers.
        return [
            'Cache-Control' => 'private, no-store',
        ];
    }

    /**
     * Determine additional headers from TypoScript config.additionalHeaders
     */
    protected function getAdditionalHeadersFromTypoScript(ServerRequestInterface $request): array
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
            $headerKeyValue = GeneralUtility::trimExplode(':', $header, false, 2);
            $additionalHeaders[] = [
                'header' => $headerKeyValue[0],
                'value' => $headerKeyValue[1],
                // "replace existing headers" is turned on by default, unless turned off
                'replace' => ($options['replace'] ?? '') !== '0',
                'statusCode' => (int)($options['httpResponseCode'] ?? 0) ?: null,
            ];
        }
        return $additionalHeaders;
    }

    protected function getLanguageService(): LanguageService
    {
        return GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }
}
