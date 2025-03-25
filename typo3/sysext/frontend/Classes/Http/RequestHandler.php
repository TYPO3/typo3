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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Cache\NonceValueSubstitution;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\ModifyHrefLangTagsEvent;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\CMS\Frontend\Resource\PublicUrlPrefixer;

/**
 * This is the main entry point of the TypoScript driven standard front-end.
 *
 * "handle()" is called when all PSR-15 middlewares have been set up the PSR-7 ServerRequest object and the following
 * things have been evaluated
 * - correct page ID, page type (typeNum), rootline, MP etc.
 * - info if is cached content already available
 * - proper language
 * - proper TypoScript which should be processed.
 *
 * Then, this class is able to render the actual HTTP body part built via TypoScript. Here this is split into two parts:
 * - Everything included in <body>, done via page.10, page.20 etc.
 * - Everything around.
 *
 * If the content has been built together within the cache (cache_pages), it is fetched directly, and
 * any so-called "uncached" content is generated again.
 *
 * Some further events allow to post-process the content.
 *
 * Then the right HTTP response headers are compiled together and sent as well.
 */
class RequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ListenerProvider $listenerProvider,
        private readonly TimeTracker $timeTracker,
        private readonly FilePathSanitizer $filePathSanitizer,
        private readonly TypoScriptService $typoScriptService,
        private readonly Context $context,
    ) {}

    /**
     * Sets the global GET and POST to the values, so if people access $_GET and $_POST
     * Within hooks starting NOW (e.g. cObject), they get the "enriched" data from query params.
     *
     * This needs to be run after the request object has been enriched with modified GET/POST variables.
     *
     * @param ServerRequestInterface $request
     * @internal this safety net will be removed in TYPO3 v10.0.
     */
    protected function resetGlobalsToCurrentRequest(ServerRequestInterface $request)
    {
        if ($request->getQueryParams() !== $_GET) {
            $queryParams = $request->getQueryParams();
            $_GET = $queryParams;
            $GLOBALS['HTTP_GET_VARS'] = $_GET;
        }
        if ($request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody) && $parsedBody !== $_POST) {
                $_POST = $parsedBody;
                $GLOBALS['HTTP_POST_VARS'] = $_POST;
            }
        }
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    /**
     * Handles a frontend request, after finishing running middlewares
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controller = $request->getAttribute('frontend.controller');

        $this->resetGlobalsToCurrentRequest($request);

        // Generate page
        if ($controller->isGeneratePage()) {
            $this->timeTracker->push('Page generation');

            // forward `ConsumableNonce` containing a nonce to `PageRenderer`
            $nonce = $request->getAttribute('nonce');
            $this->getPageRenderer()->setNonce($nonce);

            $controller->preparePageContentGeneration($request);

            // Make sure all FAL resources are prefixed with absPrefPrefix
            $this->listenerProvider->addListener(
                GeneratePublicUrlForResourceEvent::class,
                PublicUrlPrefixer::class,
                'prefixWithAbsRefPrefix'
            );

            // Content generation
            $this->timeTracker->incStackPointer();
            $this->timeTracker->push('Page generation PAGE object');

            $controller->content = $this->generatePageContent($controller, $request);

            $this->timeTracker->pull($this->timeTracker->LR ? $controller->content : '');
            $this->timeTracker->decStackPointer();

            // In case the nonce value was actually consumed during the rendering process, add a
            // permanent substitution of the current value (that will be cached), with a future
            // value (that will be generated and issued in the HTTP CSP header).
            // Besides that, the same handling is triggered in case there are other uncached items
            // already - this is due to the fact that the `PageRenderer` state has been serialized
            // before and note executed via `$pageRenderer->render()` and did not consume any nonce values
            // (see serialization in `generatePageContent()`).
            if ($nonce instanceof ConsumableNonce && (count($nonce) > 0 || $controller->isINTincScript())) {
                // nonce was consumed
                $controller->config['INTincScript'][] = [
                    'target' => NonceValueSubstitution::class . '->substituteNonce',
                    'parameters' => ['nonce' => $nonce->value],
                    'permanent' => true,
                ];
            }

            $controller->generatePage_postProcessing($request);
            $this->timeTracker->pull();
        }

        // Render non-cached page parts by replacing placeholders which are taken from cache or added during page generation
        if ($controller->isINTincScript()) {
            if (!$controller->isGeneratePage()) {
                // When the page was generated, this was already called. Avoid calling this twice.
                $controller->preparePageContentGeneration($request);

                // Make sure all FAL resources are prefixed with absPrefPrefix
                $this->listenerProvider->addListener(
                    GeneratePublicUrlForResourceEvent::class,
                    PublicUrlPrefixer::class,
                    'prefixWithAbsRefPrefix'
                );
            }
            $this->timeTracker->push('Non-cached objects');
            $controller->INTincScript($request);
            $this->timeTracker->pull();
        }

        // Create a default Response object and add headers and body to it
        $response = new Response();
        $response = $controller->applyHttpHeadersToResponse($request, $response);
        $this->displayPreviewInfoMessage($request, $controller);
        $response->getBody()->write($controller->content);
        return $response;
    }

    /**
     * Generates the main body part for the page, and if "config.disableAllHeaderCode" is not active, triggers
     * pageRenderer to evaluate includeCSS, headTag etc. TypoScript processing to populate the pageRenderer.
     */
    protected function generatePageContent(TypoScriptFrontendController $controller, ServerRequestInterface $request): string
    {
        // Generate the main content between the <body> tags
        // This has to be done first, as some additional TSFE-related code could have been written
        $pageContent = $this->generatePageBodyContent($controller, $request);
        // If 'disableAllHeaderCode' is set, all the pageRenderer settings are not evaluated
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        if ($typoScriptConfigArray['disableAllHeaderCode'] ?? false) {
            return $pageContent;
        }
        // Now, populate pageRenderer with all additional data
        $this->processHtmlBasedRenderingSettings($controller, $request);
        $pageRenderer = $this->getPageRenderer();
        // Add previously generated page content within the <body> tag afterwards
        $pageRenderer->addBodyContent(LF . $pageContent);
        if ($controller->isINTincScript()) {
            // Store the serialized pageRenderer state in configuration
            $controller->config['INTincScript_ext']['pageRendererState'] = serialize($pageRenderer->getState());
            // Store the serialized AssetCollector state in configuration
            $controller->config['INTincScript_ext']['assetCollectorState'] = serialize(GeneralUtility::makeInstance(AssetCollector::class)->getState());
            // Render complete page, keep placeholders for JavaScript and CSS
            return $pageRenderer->renderPageWithUncachedObjects($controller->config['INTincScript_ext']['divKey'] ?? '');
        }
        // Render complete page
        return $pageRenderer->render();
    }

    /**
     * Generates the main content part within <body> tags (except JS files/CSS files), this means:
     * render everything that can be cached, otherwise put placeholders for COA_INT/USER_INT objects
     * in the content that is processed later-on.
     */
    protected function generatePageBodyContent(TypoScriptFrontendController $controller, ServerRequestInterface $request): string
    {
        $typoScriptPageSetupArray = $request->getAttribute('frontend.typoscript')->getPageArray();
        $pageContent = $controller->cObj->cObjGet($typoScriptPageSetupArray) ?: '';
        if ($typoScriptPageSetupArray['wrap'] ?? false) {
            $pageContent = $controller->cObj->wrap($pageContent, $typoScriptPageSetupArray['wrap']);
        }
        if ($typoScriptPageSetupArray['stdWrap.'] ?? false) {
            $pageContent = $controller->cObj->stdWrap($pageContent, $typoScriptPageSetupArray['stdWrap.']);
        }
        return $pageContent;
    }

    /**
     * At this point, the cacheable content has just been generated: Content is available but hasn't been added
     * to PageRenderer yet. The method is called after the "main" page content, since some JS may be inserted at that point
     * that has been registered by cacheable plugins.
     * PageRenderer is now populated with all <head> data and additional JavaScript/CSS/FooterData/HeaderData that can be cached.
     * Once finished, the content is added to the >addBodyContent() functionality.
     */
    protected function processHtmlBasedRenderingSettings(TypoScriptFrontendController $controller, ServerRequestInterface $request): void
    {
        $pageRenderer = $this->getPageRenderer();
        $typoScript = $request->getAttribute('frontend.typoscript');
        $typoScriptSetupArray = $typoScript->getSetupArray();
        $typoScriptConfigArray = $typoScript->getConfigArray();
        $typoScriptPageArray = $typoScript->getPageArray();

        if ($typoScriptConfigArray['moveJsFromHeaderToFooter'] ?? false) {
            $pageRenderer->enableMoveJsFromHeaderToFooter();
        }
        if ($typoScriptConfigArray['pageRendererTemplateFile'] ?? false) {
            try {
                $file = $this->filePathSanitizer->sanitize($typoScriptConfigArray['pageRendererTemplateFile'], true);
                $pageRenderer->setTemplateFile($file);
            } catch (Exception) {
                // Custom template is not set if sanitize() throws
            }
        }
        $headerComment = trim($typoScriptConfigArray['headerComment'] ?? '');
        if ($headerComment) {
            $pageRenderer->addInlineComment("\t" . str_replace(LF, LF . "\t", $headerComment) . LF);
        }
        $htmlTagAttributes = [];

        // @todo: Check when/if there are scenarios where attribute 'language' is not yet set in $request.
        $siteLanguage = $request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage();
        if ($siteLanguage->getLocale()->isRightToLeftLanguageDirection()) {
            $htmlTagAttributes['dir'] = 'rtl';
        }
        $docType = $pageRenderer->getDocType();
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
            $pageRenderer->setXmlPrologAndDocType(implode(LF, $docTypeParts));
        }
        // See https://www.w3.org/International/questions/qa-html-language-declarations.en.html#attributes
        $htmlTagAttributes[$docType->isXmlCompliant() ? 'xml:lang' : 'lang'] = $siteLanguage->getLocale()->getLanguageCode();

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

        $pageRenderer->setHtmlTag($this->generateHtmlTag($htmlTagAttributes, $typoScriptConfigArray, $controller->cObj));

        $headTag = $typoScriptPageArray['headTag'] ?? '<head>';
        if (isset($typoScriptPageArray['headTag.'])) {
            $headTag = $controller->cObj->stdWrap($headTag, $typoScriptPageArray['headTag.']);
        }
        $pageRenderer->setHeadTag($headTag);

        $pageRenderer->addInlineComment(GeneralUtility::makeInstance(Typo3Information::class)->getInlineHeaderComment());

        if ($typoScriptPageArray['shortcutIcon'] ?? false) {
            try {
                $favIcon = $this->filePathSanitizer->sanitize($typoScriptPageArray['shortcutIcon']);
                $iconFileInfo = GeneralUtility::makeInstance(ImageInfo::class, Environment::getPublicPath() . '/' . $favIcon);
                if ($iconFileInfo->isFile()) {
                    $iconMimeType = $iconFileInfo->getMimeType();
                    if ($iconMimeType) {
                        $iconMimeType = ' type="' . $iconMimeType . '"';
                        $pageRenderer->setIconMimeType($iconMimeType);
                    }
                    $pageRenderer->setFavIcon(PathUtility::getAbsoluteWebPath($controller->absRefPrefix . $favIcon));
                }
            } catch (Exception) {
                // FavIcon is not set if sanitize() throws
            }
        }

        // Inline CSS from plugins, files, libraries and inline
        if (is_array($typoScriptSetupArray['plugin.'] ?? false)) {
            $stylesFromPlugins = '';
            foreach ($typoScriptSetupArray['plugin.'] as $key => $iCSScode) {
                if (is_array($iCSScode)) {
                    if (($iCSScode['_CSS_DEFAULT_STYLE'] ?? false) && empty($typoScriptConfigArray['removeDefaultCss'])) {
                        $cssDefaultStyle = $controller->cObj->stdWrapValue('_CSS_DEFAULT_STYLE', $iCSScode);
                        $stylesFromPlugins .= '/* default styles for extension "' . substr($key, 0, -1) . '" */' . LF . $cssDefaultStyle . LF;
                    }
                }
            }
            if (!empty($stylesFromPlugins)) {
                $this->addCssToPageRenderer($request, $stylesFromPlugins, false, 'InlineDefaultCss');
            }
        }
        if (is_array($typoScriptPageArray['includeCSS.'] ?? false)) {
            foreach ($typoScriptPageArray['includeCSS.'] as $key => $cssResource) {
                if (is_array($cssResource)) {
                    continue;
                }
                $cssResourceConfig = $additionalAttributes = $typoScriptPageArray['includeCSS.'][$key . '.'] ?? [];
                if (isset($cssResourceConfig['if.']) && !$controller->cObj->checkIf($cssResourceConfig['if.'])) {
                    continue;
                }
                if (!($cssResourceConfig['external'] ?? false)) {
                    try {
                        $cssResource = $this->filePathSanitizer->sanitize($cssResource, true);
                    } catch (Exception) {
                        continue;
                    }
                }
                $additionalAttributes = $this->cleanupAdditionalAttributeKeys($additionalAttributes, 'css');
                $pageRenderer->addCssFile(
                    $cssResource,
                    ($cssResourceConfig['alternate'] ?? false) ? 'alternate stylesheet' : 'stylesheet',
                    ($cssResourceConfig['media'] ?? false) ?: 'all',
                    ($cssResourceConfig['title'] ?? false) ?: '',
                    empty($cssResourceConfig['external']) && empty($cssResourceConfig['inline']) && empty($cssResourceConfig['disableCompression']),
                    (bool)($cssResourceConfig['forceOnTop'] ?? false),
                    $cssResourceConfig['allWrap'] ?? '',
                    ($cssResourceConfig['excludeFromConcatenation'] ?? false) || ($cssResourceConfig['inline'] ?? false),
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
                if (isset($cssResourceConfig['if.']) && !$controller->cObj->checkIf($cssResourceConfig['if.'])) {
                    continue;
                }
                if (!($cssResourceConfig['external'] ?? false)) {
                    try {
                        $cssResource = $this->filePathSanitizer->sanitize($cssResource, true);
                    } catch (Exception) {
                        continue;
                    }
                }
                $additionalAttributes = $this->cleanupAdditionalAttributeKeys($additionalAttributes, 'css');
                $pageRenderer->addCssLibrary(
                    $cssResource,
                    ($cssResourceConfig['alternate'] ?? false) ? 'alternate stylesheet' : 'stylesheet',
                    ($cssResourceConfig['media'] ?? false) ?: 'all',
                    ($cssResourceConfig['title'] ?? false) ?: '',
                    empty($cssResourceConfig['external']) && empty($cssResourceConfig['inline']) && empty($cssResourceConfig['disableCompression']),
                    (bool)($cssResourceConfig['forceOnTop'] ?? false),
                    $cssResourceConfig['allWrap'] ?? '',
                    ($cssResourceConfig['excludeFromConcatenation'] ?? false) || ($cssResourceConfig['inline'] ?? false),
                    $cssResourceConfig['allWrap.']['splitChar'] ?? '|',
                    (bool)($cssResourceConfig['inline'] ?? false),
                    $additionalAttributes
                );
            }
        }
        $style = $controller->cObj->cObjGet($typoScriptPageArray['cssInline.'] ?? null, 'cssInline.');
        if (trim($style)) {
            $this->addCssToPageRenderer($request, $style, true, 'additionalTSFEInlineStyle');
        }

        // JavaScript includes
        if (is_array($typoScriptPageArray['includeJSLibs.'] ?? false)) {
            foreach ($typoScriptPageArray['includeJSLibs.'] as $key => $jsResource) {
                if (is_array($jsResource)) {
                    continue;
                }
                $jsResourceConfig = $additionalAttributes = $typoScriptPageArray['includeJSLibs.'][$key . '.'] ?? [];
                if (isset($jsResourceConfig['if.']) && !$controller->cObj->checkIf($jsResourceConfig['if.'])) {
                    continue;
                }
                if (!($jsResourceConfig['external'] ?? false)) {
                    try {
                        $jsResource = $this->filePathSanitizer->sanitize($jsResource, true);
                    } catch (Exception) {
                        continue;
                    }
                }
                $crossOrigin = (string)($jsResourceConfig['crossorigin'] ?? '');
                if ($crossOrigin === '' && ($jsResourceConfig['integrity'] ?? false) && ($jsResourceConfig['external'] ?? false)) {
                    $crossOrigin = 'anonymous';
                }
                $additionalAttributes = $this->cleanupAdditionalAttributeKeys($additionalAttributes, 'js');
                $pageRenderer->addJsLibrary(
                    $key,
                    $jsResource,
                    $jsResourceConfig['type'] ?? null,
                    empty($jsResourceConfig['external']) && empty($jsResourceConfig['disableCompression']),
                    (bool)($jsResourceConfig['forceOnTop'] ?? false),
                    $jsResourceConfig['allWrap'] ?? '',
                    (bool)($jsResourceConfig['excludeFromConcatenation'] ?? false),
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
                if (isset($jsResourceConfig['if.']) && !$controller->cObj->checkIf($jsResourceConfig['if.'])) {
                    continue;
                }
                if (!($jsResourceConfig['external'] ?? false)) {
                    try {
                        $jsResource = $this->filePathSanitizer->sanitize($jsResource, true);
                    } catch (Exception) {
                        continue;
                    }
                }
                $crossOrigin = (string)($jsResourceConfig['crossorigin'] ?? '');
                if ($crossOrigin === '' && ($jsResourceConfig['integrity'] ?? false) && ($jsResourceConfig['external'] ?? false)) {
                    $crossOrigin = 'anonymous';
                }
                $additionalAttributes = $this->cleanupAdditionalAttributeKeys($additionalAttributes, 'js');
                $pageRenderer->addJsFooterLibrary(
                    $key,
                    $jsResource,
                    $jsResourceConfig['type'] ?? null,
                    empty($jsResourceConfig['external']) && empty($jsResourceConfig['disableCompression']),
                    (bool)($jsResourceConfig['forceOnTop'] ?? false),
                    $jsResourceConfig['allWrap'] ?? '',
                    (bool)($jsResourceConfig['excludeFromConcatenation'] ?? false),
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
                if (isset($jsResourceConfig['if.']) && !$controller->cObj->checkIf($jsResourceConfig['if.'])) {
                    continue;
                }
                if (!($jsResourceConfig['external'] ?? false)) {
                    try {
                        $jsResource = $this->filePathSanitizer->sanitize($jsResource, true);
                    } catch (Exception) {
                        continue;
                    }
                }
                $crossOrigin = (string)($jsResourceConfig['crossorigin'] ?? '');
                if ($crossOrigin === '' && ($jsResourceConfig['integrity'] ?? false) && ($jsResourceConfig['external'] ?? false)) {
                    $crossOrigin = 'anonymous';
                }
                $pageRenderer->addJsFile(
                    $jsResource,
                    $jsResourceConfig['type'] ?? null,
                    empty($jsResourceConfig['external']) && empty($jsResourceConfig['disableCompression']),
                    (bool)($jsResourceConfig['forceOnTop'] ?? false),
                    $jsResourceConfig['allWrap'] ?? '',
                    (bool)($jsResourceConfig['excludeFromConcatenation'] ?? false),
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
                if (isset($jsResourceConfig['if.']) && !$controller->cObj->checkIf($jsResourceConfig['if.'])) {
                    continue;
                }
                if (!($jsResourceConfig['external'] ?? false)) {
                    try {
                        $jsResource = $this->filePathSanitizer->sanitize($jsResource, true);
                    } catch (Exception) {
                        continue;
                    }
                }
                $crossOrigin = (string)($jsResourceConfig['crossorigin'] ?? '');
                if ($crossOrigin === '' && ($jsResourceConfig['integrity'] ?? false) && ($jsResourceConfig['external'] ?? false)) {
                    $crossOrigin = 'anonymous';
                }
                $pageRenderer->addJsFooterFile(
                    $jsResource,
                    $jsResourceConfig['type'] ?? null,
                    empty($jsResourceConfig['external']) && empty($jsResourceConfig['disableCompression']),
                    (bool)($jsResourceConfig['forceOnTop'] ?? false),
                    $jsResourceConfig['allWrap'] ?? '',
                    (bool)($jsResourceConfig['excludeFromConcatenation'] ?? false),
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
            $pageRenderer->addHeaderData($controller->cObj->cObjGet($typoScriptPageArray['headerData.'], 'headerData.'));
        }
        if (is_array($typoScriptPageArray['footerData.'] ?? false)) {
            $pageRenderer->addFooterData($controller->cObj->cObjGet($typoScriptPageArray['footerData.'], 'footerData.'));
        }

        $controller->generatePageTitle($request);

        // @internal hook for EXT:seo, will be gone soon, do not use it in your own extensions
        $_params = ['request' => $request];
        $_ref = null;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Frontend\Page\PageGenerator']['generateMetaTags'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $_ref);
        }

        $this->generateHrefLangTags($controller, $request, $pageRenderer);
        $this->generateMetaTagHtml($typoScriptPageArray['meta.'] ?? [], $controller->cObj);

        // Javascript inline and inline footer code
        $inlineJS = implode(LF, $controller->cObj->cObjGetSeparated($typoScriptPageArray['jsInline.'] ?? null, 'jsInline.'));
        $inlineFooterJs = implode(LF, $controller->cObj->cObjGetSeparated($typoScriptPageArray['jsFooterInline.'] ?? null, 'jsFooterInline.'));

        // Needs to be called after all cObjGet() calls in order to get all headerData and footerData and replacements
        $controller->INTincScript_loadJSCode();

        $compressJs = (bool)($typoScriptConfigArray['compressJs'] ?? false);
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
                $pageRenderer->addJsInlineCode('TS_inlineJSint', $inlineJSint, $compressJs);
            }
            if (trim($inlineJS)) {
                $pageRenderer->addJsFile(GeneralUtility::writeJavaScriptContentToTemporaryFile($inlineJS), null, $compressJs);
            }
            if ($inlineFooterJs) {
                $inlineFooterJSint = '';
                $this->stripIntObjectPlaceholder($inlineFooterJs, $inlineFooterJSint);
                if ($inlineFooterJSint) {
                    $pageRenderer->addJsFooterInlineCode('TS_inlineFooterJSint', $inlineFooterJSint, $compressJs);
                }
                $pageRenderer->addJsFooterFile(GeneralUtility::writeJavaScriptContentToTemporaryFile($inlineFooterJs), null, $compressJs);
            }
        } else {
            // Include only inlineJS
            if ($inlineJS) {
                $pageRenderer->addJsInlineCode('TS_inlineJS', $inlineJS, $compressJs);
            }
            if ($inlineFooterJs) {
                $pageRenderer->addJsFooterInlineCode('TS_inlineFooter', $inlineFooterJs, $compressJs);
            }
        }
        if (is_array($typoScriptPageArray['inlineLanguageLabelFiles.'] ?? false)) {
            foreach ($typoScriptPageArray['inlineLanguageLabelFiles.'] as $key => $languageFile) {
                if (is_array($languageFile)) {
                    continue;
                }
                $languageFileConfig = $typoScriptPageArray['inlineLanguageLabelFiles.'][$key . '.'] ?? [];
                if (isset($languageFileConfig['if.']) && !$controller->cObj->checkIf($languageFileConfig['if.'])) {
                    continue;
                }
                $pageRenderer->addInlineLanguageLabelFile(
                    $languageFile,
                    ($languageFileConfig['selectionPrefix'] ?? false) ? $languageFileConfig['selectionPrefix'] : '',
                    ($languageFileConfig['stripFromSelectionName'] ?? false) ? $languageFileConfig['stripFromSelectionName'] : ''
                );
            }
        }
        if (is_array($typoScriptPageArray['inlineSettings.'] ?? false)) {
            $pageRenderer->addInlineSettingArray('TS', $typoScriptPageArray['inlineSettings.']);
        }
        // Compression and concatenate settings
        if ($typoScriptConfigArray['compressCss'] ?? false) {
            $pageRenderer->enableCompressCss();
        }
        if ($compressJs) {
            $pageRenderer->enableCompressJavascript();
        }
        if ($typoScriptConfigArray['concatenateCss'] ?? false) {
            $pageRenderer->enableConcatenateCss();
        }
        if ($typoScriptConfigArray['concatenateJs'] ?? false) {
            $pageRenderer->enableConcatenateJavascript();
        }
        // Add header data block
        if ($controller->additionalHeaderData) {
            $pageRenderer->addHeaderData(implode(LF, $controller->additionalHeaderData));
        }
        // Add footer data block
        if ($controller->additionalFooterData) {
            $pageRenderer->addFooterData(implode(LF, $controller->additionalFooterData));
        }
        // Header complete, now the body tag is added so the regular content can be applied later-on
        if ($typoScriptConfigArray['disableBodyTag'] ?? false) {
            $pageRenderer->addBodyContent(LF);
        } else {
            $bodyTag = '<body>';
            if ($typoScriptPageArray['bodyTag'] ?? false) {
                $bodyTag = $typoScriptPageArray['bodyTag'];
            } elseif ($typoScriptPageArray['bodyTagCObject'] ?? false) {
                $bodyTag = $controller->cObj->cObjGetSingle($typoScriptPageArray['bodyTagCObject'], $typoScriptPageArray['bodyTagCObject.'] ?? [], 'bodyTagCObject');
            }
            if (trim($typoScriptPageArray['bodyTagAdd'] ?? '')) {
                $bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' ' . trim($typoScriptPageArray['bodyTagAdd']) . '>';
            }
            $pageRenderer->addBodyContent(LF . $bodyTag);
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
        $pageRenderer = $this->getPageRenderer();
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
                    $pageRenderer->setMetaTag($attribute, $key, $subValue, [], $replace);
                }
            }
        }
    }

    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Adds inline CSS code, by respecting the inlineStyle2TempFile option
     *
     * @param string $cssStyles the inline CSS styling
     * @param bool $excludeFromConcatenation option to see if it should be concatenated
     * @param string $inlineBlockName the block name to add it
     */
    protected function addCssToPageRenderer(ServerRequestInterface $request, string $cssStyles, bool $excludeFromConcatenation, string $inlineBlockName): void
    {
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        // This option is enabled by default on purpose
        if (empty($typoScriptConfigArray['inlineStyle2TempFile'] ?? true)) {
            $this->getPageRenderer()->addCssInlineBlock($inlineBlockName, $cssStyles, !empty($typoScriptConfigArray['compressCss'] ?? false));
        } else {
            $this->getPageRenderer()->addCssFile(
                GeneralUtility::writeStyleSheetContentToTemporaryFile($cssStyles),
                'stylesheet',
                'all',
                '',
                (bool)($typoScriptConfigArray['compressCss'] ?? false),
                false,
                '',
                $excludeFromConcatenation
            );
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

    protected function generateHrefLangTags(TypoScriptFrontendController $controller, ServerRequestInterface $request, PageRenderer $pageRenderer): void
    {
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        if ($typoScriptConfigArray['disableHrefLang'] ?? false) {
            return;
        }
        $endingSlash = $pageRenderer->getDocType()->isXmlCompliant() ? '/' : '';
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
            $controller->additionalHeaderData[] = implode(LF, $data);
        }
    }

    /**
     * Include the preview block in case we're looking at a hidden page in the LIVE workspace
     *
     * @internal this method might get moved to a PSR-15 middleware at some point
     */
    protected function displayPreviewInfoMessage(ServerRequestInterface $request, TypoScriptFrontendController $controller)
    {
        $isInWorkspace = $this->context->getPropertyFromAspect('workspace', 'isOffline', false);
        $isInPreviewMode = $this->context->hasAspect('frontend.preview')
            && $this->context->getPropertyFromAspect('frontend.preview', 'isPreview');
        $typoScriptConfigArray = $request->getAttribute('frontend.typoscript')->getConfigArray();
        if (!$isInPreviewMode || $isInWorkspace || ($typoScriptConfigArray['disablePreviewNotification'] ?? false)) {
            return;
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
            $styles[] = 'font-size: 14px';
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
            $controller->content = str_ireplace('</body>', $message . '</body>', $controller->content);
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }

    /**
     * Filter out known TypoScript attributes so that they are NOT passed along
     * to a <link rel...> or <script...> tag as additional attributes.
     * NOTE: Some keys are unset here even though they are valid attributes to
     * the <link> or <script> tag. This is because these extra attribute keys are specifically
     * evaluated, in the addCssFile/addCssLibrary/addJsFile/addJsFooterLibrary methods.
     *
     * @param string $cleanupType: Indicate if "css" <link> or "js" <script> is cleaned up.
     * @internal
     */
    private function cleanupAdditionalAttributeKeys(array $additionalAttributes, string $cleanupType): array
    {
        // Common (CSS+JS)
        unset(
            $additionalAttributes['if.'],
            $additionalAttributes['external'],
            $additionalAttributes['allWrap'],
            $additionalAttributes['allWrap.'],
            $additionalAttributes['disableCompression'],
            $additionalAttributes['excludeFromConcatenation'],
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
}
