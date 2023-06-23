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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Domain\ConsumableString;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
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
 * Some further hooks allow to post-processing the content.
 *
 * Then the right HTTP response headers are compiled together and sent as well.
 */
class RequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ListenerProvider $listenerProvider,
        private readonly TimeTracker $timeTracker,
    ) {
    }

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

            // forward `ConsumableString` containing a nonce to `PageRenderer`
            $nonce = $request->getAttribute('nonce');
            $this->getPageRenderer()->setNonce($nonce);

            $controller->generatePage_preProcessing();
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
            if ($nonce instanceof ConsumableString && (count($nonce) > 0 || $controller->isINTincScript())) {
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
        $controller->releaseLocks();

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
        $response = $controller->applyHttpHeadersToResponse($response);
        $this->displayPreviewInfoMessage($controller);
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
        $pageContent = $this->generatePageBodyContent($controller);
        // If 'disableAllHeaderCode' is set, all the pageRenderer settings are not evaluated
        if ($controller->config['config']['disableAllHeaderCode'] ?? false) {
            return $pageContent;
        }
        // Now, populate pageRenderer with all additional data
        $this->processHtmlBasedRenderingSettings($controller, $controller->getLanguage(), $request);
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
    protected function generatePageBodyContent(TypoScriptFrontendController $controller): string
    {
        $pageContent = $controller->cObj->cObjGet($controller->pSetup) ?: '';
        if ($controller->pSetup['wrap'] ?? false) {
            $pageContent = $controller->cObj->wrap($pageContent, $controller->pSetup['wrap']);
        }
        if ($controller->pSetup['stdWrap.'] ?? false) {
            $pageContent = $controller->cObj->stdWrap($pageContent, $controller->pSetup['stdWrap.']);
        }
        return $pageContent;
    }

    /**
     * At this point, the cacheable content has just been generated (thus, all content is available but hasn't been added
     * to PageRenderer yet). The method is called after the "main" page content, since some JS may be inserted at that point
     * that has been registered by cacheable plugins.
     * PageRenderer is now populated with all <head> data and additional JavaScript/CSS/FooterData/HeaderData that can be cached.
     * Once finished, the content is added to the >addBodyContent() functionality.
     */
    protected function processHtmlBasedRenderingSettings(TypoScriptFrontendController $controller, SiteLanguage $siteLanguage, ServerRequestInterface $request): void
    {
        $pageRenderer = $this->getPageRenderer();
        if ($controller->config['config']['moveJsFromHeaderToFooter'] ?? false) {
            $pageRenderer->enableMoveJsFromHeaderToFooter();
        }
        if ($controller->config['config']['pageRendererTemplateFile'] ?? false) {
            try {
                $file = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($controller->config['config']['pageRendererTemplateFile'], true);
                $pageRenderer->setTemplateFile($file);
            } catch (Exception $e) {
                // do nothing
            }
        }
        $headerComment = trim($controller->config['config']['headerComment'] ?? '');
        if ($headerComment) {
            $pageRenderer->addInlineComment("\t" . str_replace(LF, LF . "\t", $headerComment) . LF);
        }
        $htmlTagAttributes = [];

        if ($siteLanguage->getLocale()->isRightToLeftLanguageDirection()) {
            $htmlTagAttributes['dir'] = 'rtl';
        }
        $docType = $pageRenderer->getDocType();
        // Setting document type:
        $docTypeParts = [];
        $xmlDocument = true;
        // Part 1: XML prologue
        $xmlPrologue = (string)($controller->config['config']['xmlprologue'] ?? '');
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
        // Part 2: DTD
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
            if (is_array($controller->config['config']['namespaces.'] ?? null)) {
                foreach ($controller->config['config']['namespaces.'] as $prefix => $uri) {
                    // $uri gets htmlspecialchared later
                    $htmlTagAttributes['xmlns:' . htmlspecialchars($prefix)] = $uri;
                }
            }
        }
        // Begin header section:
        $htmlTag = $this->generateHtmlTag($htmlTagAttributes, $controller->config['config'] ?? [], $controller->cObj);
        $pageRenderer->setHtmlTag($htmlTag);
        // Head tag:
        $headTag = $controller->pSetup['headTag'] ?? '<head>';
        if (isset($controller->pSetup['headTag.'])) {
            $headTag = $controller->cObj->stdWrap($headTag, $controller->pSetup['headTag.']);
        }
        $pageRenderer->setHeadTag($headTag);
        $pageRenderer->addInlineComment(GeneralUtility::makeInstance(Typo3Information::class)->getInlineHeaderComment());
        $baseUrl = $controller->config['config']['baseURL'] ?? '';
        if ($baseUrl) {
            $controller->logDeprecatedTyposcript('config.baseURL', 'This setting will be removed in TYPO3 v13.0 - <base> tags are not supported anymore in TYPO3.');
            $pageRenderer->setBaseUrl($baseUrl, true);
        }
        if ($controller->pSetup['shortcutIcon'] ?? false) {
            try {
                $favIcon = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($controller->pSetup['shortcutIcon']);
                $iconFileInfo = GeneralUtility::makeInstance(ImageInfo::class, Environment::getPublicPath() . '/' . $favIcon);
                if ($iconFileInfo->isFile()) {
                    $iconMimeType = $iconFileInfo->getMimeType();
                    if ($iconMimeType) {
                        $iconMimeType = ' type="' . $iconMimeType . '"';
                        $pageRenderer->setIconMimeType($iconMimeType);
                    }
                    $pageRenderer->setFavIcon(PathUtility::getAbsoluteWebPath($controller->absRefPrefix . $favIcon));
                }
            } catch (Exception $e) {
                // do nothing
            }
        }
        // Including CSS files
        $typoScriptSetupArray = $request->getAttribute('frontend.typoscript')->getSetupArray();
        if (is_array($typoScriptSetupArray['plugin.'] ?? null)) {
            $stylesFromPlugins = '';
            foreach ($typoScriptSetupArray['plugin.'] as $key => $iCSScode) {
                if (is_array($iCSScode)) {
                    if (($iCSScode['_CSS_DEFAULT_STYLE'] ?? false) && empty($controller->config['config']['removeDefaultCss'])) {
                        $cssDefaultStyle = $controller->cObj->stdWrapValue('_CSS_DEFAULT_STYLE', $iCSScode ?? []);
                        $stylesFromPlugins .= '/* default styles for extension "' . substr($key, 0, -1) . '" */' . LF . $cssDefaultStyle . LF;
                    }
                    if (($iCSScode['_CSS_PAGE_STYLE'] ?? false) && empty($controller->config['config']['removePageCss'])) {
                        // @deprecated since v12, remove with v13: Entire if().
                        trigger_error(
                            'Handling of TypoScript setup property "plugin._CSS_PAGE_STYLE" and "config.removePageCss" have been deprecated'
                            . ' in TYPO3 v12 and will be removed with v13: Use "includeCSS" or "cssInline" of the "PAGE" object instead.',
                            E_USER_DEPRECATED
                        );
                        if (is_array($iCSScode['_CSS_PAGE_STYLE'])) {
                            $cssPageStyle = implode(LF, $iCSScode['_CSS_PAGE_STYLE']);
                        } else {
                            $cssPageStyle = $iCSScode['_CSS_PAGE_STYLE'];
                        }
                        if (isset($iCSScode['_CSS_PAGE_STYLE.'])) {
                            $cssPageStyle = $controller->cObj->stdWrap($cssPageStyle, $iCSScode['_CSS_PAGE_STYLE.']);
                        }
                        $cssPageStyle = '/* specific page styles for extension "' . substr($key, 0, -1) . '" */' . LF . $cssPageStyle;
                        $this->addCssToPageRenderer($controller, $cssPageStyle, true, 'InlinePageCss');
                    }
                }
            }
            if (!empty($stylesFromPlugins)) {
                $this->addCssToPageRenderer($controller, $stylesFromPlugins, false, 'InlineDefaultCss');
            }
        }
        /**********************************************************************/
        /* config.includeCSS / config.includeCSSLibs
        /**********************************************************************/
        if (is_array($controller->pSetup['includeCSS.'] ?? null)) {
            foreach ($controller->pSetup['includeCSS.'] as $key => $CSSfile) {
                if (!is_array($CSSfile)) {
                    $cssFileConfig = &$controller->pSetup['includeCSS.'][$key . '.'];
                    if (isset($cssFileConfig['if.']) && !$controller->cObj->checkIf($cssFileConfig['if.'])) {
                        continue;
                    }
                    if ($cssFileConfig['external'] ?? false) {
                        $ss = $CSSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($CSSfile, true);
                        } catch (Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $additionalAttributes = $cssFileConfig ?? [];
                        unset(
                            $additionalAttributes['if.'],
                            $additionalAttributes['alternate'],
                            $additionalAttributes['media'],
                            $additionalAttributes['title'],
                            $additionalAttributes['external'],
                            $additionalAttributes['inline'],
                            $additionalAttributes['disableCompression'],
                            $additionalAttributes['excludeFromConcatenation'],
                            $additionalAttributes['allWrap'],
                            $additionalAttributes['allWrap.'],
                            $additionalAttributes['forceOnTop'],
                        );
                        $pageRenderer->addCssFile(
                            $ss,
                            ($cssFileConfig['alternate'] ?? false) ? 'alternate stylesheet' : 'stylesheet',
                            ($cssFileConfig['media'] ?? false) ?: 'all',
                            ($cssFileConfig['title'] ?? false) ?: '',
                            empty($cssFileConfig['external']) && empty($cssFileConfig['inline']) && empty($cssFileConfig['disableCompression']),
                            (bool)($cssFileConfig['forceOnTop'] ?? false),
                            $cssFileConfig['allWrap'] ?? '',
                            ($cssFileConfig['excludeFromConcatenation'] ?? false) || ($cssFileConfig['inline'] ?? false),
                            $cssFileConfig['allWrap.']['splitChar'] ?? '|',
                            (bool)($cssFileConfig['inline'] ?? false),
                            $additionalAttributes
                        );
                    }
                    unset($cssFileConfig);
                }
            }
        }
        if (is_array($controller->pSetup['includeCSSLibs.'] ?? null)) {
            foreach ($controller->pSetup['includeCSSLibs.'] as $key => $CSSfile) {
                if (!is_array($CSSfile)) {
                    $cssFileConfig = &$controller->pSetup['includeCSSLibs.'][$key . '.'];
                    if (isset($cssFileConfig['if.']) && !$controller->cObj->checkIf($cssFileConfig['if.'])) {
                        continue;
                    }
                    if ($cssFileConfig['external'] ?? false) {
                        $ss = $CSSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($CSSfile, true);
                        } catch (Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $additionalAttributes = $cssFileConfig ?? [];
                        unset(
                            $additionalAttributes['if.'],
                            $additionalAttributes['alternate'],
                            $additionalAttributes['media'],
                            $additionalAttributes['title'],
                            $additionalAttributes['external'],
                            $additionalAttributes['internal'],
                            $additionalAttributes['disableCompression'],
                            $additionalAttributes['excludeFromConcatenation'],
                            $additionalAttributes['allWrap'],
                            $additionalAttributes['allWrap.'],
                            $additionalAttributes['forceOnTop'],
                        );
                        $pageRenderer->addCssLibrary(
                            $ss,
                            ($cssFileConfig['alternate'] ?? false) ? 'alternate stylesheet' : 'stylesheet',
                            ($cssFileConfig['media'] ?? false) ?: 'all',
                            ($cssFileConfig['title'] ?? false) ?: '',
                            empty($cssFileConfig['external']) && empty($cssFileConfig['inline']) && empty($cssFileConfig['disableCompression']),
                            (bool)($cssFileConfig['forceOnTop'] ?? false),
                            $cssFileConfig['allWrap'] ?? '',
                            ($cssFileConfig['excludeFromConcatenation'] ?? false) || ($cssFileConfig['inline'] ?? false),
                            $cssFileConfig['allWrap.']['splitChar'] ?? '|',
                            (bool)($cssFileConfig['inline'] ?? false),
                            $additionalAttributes
                        );
                    }
                    unset($cssFileConfig);
                }
            }
        }

        $style = $controller->cObj->cObjGet($controller->pSetup['cssInline.'] ?? null, 'cssInline.');
        if (trim($style)) {
            $this->addCssToPageRenderer($controller, $style, true, 'additionalTSFEInlineStyle');
        }
        // JavaScript library files
        if (is_array($controller->pSetup['includeJSLibs.'] ?? null)) {
            foreach ($controller->pSetup['includeJSLibs.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($controller->pSetup['includeJSLibs.'][$key . '.']['if.']) && !$controller->cObj->checkIf($controller->pSetup['includeJSLibs.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($controller->pSetup['includeJSLibs.'][$key . '.']['external'] ?? false) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile, true);
                        } catch (Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsFileConfig = &$controller->pSetup['includeJSLibs.'][$key . '.'];
                        $additionalAttributes = $jsFileConfig ?? [];
                        $crossOrigin = (string)($jsFileConfig['crossorigin'] ?? '');
                        if ($crossOrigin === '' && ($jsFileConfig['integrity'] ?? false) && ($jsFileConfig['external'] ?? false)) {
                            $crossOrigin = 'anonymous';
                        }
                        unset(
                            $additionalAttributes['if.'],
                            $additionalAttributes['type'],
                            $additionalAttributes['crossorigin'],
                            $additionalAttributes['integrity'],
                            $additionalAttributes['external'],
                            $additionalAttributes['allWrap'],
                            $additionalAttributes['allWrap.'],
                            $additionalAttributes['disableCompression'],
                            $additionalAttributes['excludeFromConcatenation'],
                            $additionalAttributes['integrity'],
                            $additionalAttributes['defer'],
                            $additionalAttributes['nomodule'],
                        );
                        $pageRenderer->addJsLibrary(
                            $key,
                            $ss,
                            $jsFileConfig['type'] ?? null,
                            empty($jsFileConfig['external']) && empty($jsFileConfig['disableCompression']),
                            (bool)($jsFileConfig['forceOnTop'] ?? false),
                            $jsFileConfig['allWrap'] ?? '',
                            (bool)($jsFileConfig['excludeFromConcatenation'] ?? false),
                            $jsFileConfig['allWrap.']['splitChar'] ?? '|',
                            (bool)($jsFileConfig['async'] ?? false),
                            $jsFileConfig['integrity'] ?? '',
                            (bool)($jsFileConfig['defer'] ?? false),
                            $crossOrigin,
                            (bool)($jsFileConfig['nomodule'] ?? false),
                            $additionalAttributes
                        );
                        unset($jsFileConfig);
                    }
                }
            }
        }
        if (is_array($controller->pSetup['includeJSFooterlibs.'] ?? null)) {
            foreach ($controller->pSetup['includeJSFooterlibs.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($controller->pSetup['includeJSFooterlibs.'][$key . '.']['if.']) && !$controller->cObj->checkIf($controller->pSetup['includeJSFooterlibs.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($controller->pSetup['includeJSFooterlibs.'][$key . '.']['external'] ?? false) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile, true);
                        } catch (Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsFileConfig = &$controller->pSetup['includeJSFooterlibs.'][$key . '.'];
                        $additionalAttributes = $jsFileConfig ?? [];
                        $crossOrigin = (string)($jsFileConfig['crossorigin'] ?? '');
                        if ($crossOrigin === '' && ($jsFileConfig['integrity'] ?? false) && ($jsFileConfig['external'] ?? false)) {
                            $crossOrigin = 'anonymous';
                        }
                        unset(
                            $additionalAttributes['if.'],
                            $additionalAttributes['type'],
                            $additionalAttributes['crossorigin'],
                            $additionalAttributes['integrity'],
                            $additionalAttributes['external'],
                            $additionalAttributes['allWrap'],
                            $additionalAttributes['allWrap.'],
                            $additionalAttributes['disableCompression'],
                            $additionalAttributes['excludeFromConcatenation'],
                            $additionalAttributes['integrity'],
                            $additionalAttributes['defer'],
                            $additionalAttributes['nomodule'],
                        );
                        $pageRenderer->addJsFooterLibrary(
                            $key,
                            $ss,
                            $jsFileConfig['type'] ?? null,
                            empty($jsFileConfig['external']) && empty($jsFileConfig['disableCompression']),
                            (bool)($jsFileConfig['forceOnTop'] ?? false),
                            $jsFileConfig['allWrap'] ?? '',
                            (bool)($jsFileConfig['excludeFromConcatenation'] ?? false),
                            $jsFileConfig['allWrap.']['splitChar'] ?? '|',
                            (bool)($jsFileConfig['async'] ?? false),
                            $jsFileConfig['integrity'] ?? '',
                            (bool)($jsFileConfig['defer'] ?? false),
                            $crossOrigin,
                            (bool)($jsFileConfig['nomodule'] ?? false),
                            $additionalAttributes
                        );
                        unset($jsFileConfig);
                    }
                }
            }
        }
        // JavaScript files
        if (is_array($controller->pSetup['includeJS.'] ?? null)) {
            foreach ($controller->pSetup['includeJS.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($controller->pSetup['includeJS.'][$key . '.']['if.']) && !$controller->cObj->checkIf($controller->pSetup['includeJS.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($controller->pSetup['includeJS.'][$key . '.']['external'] ?? false) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile, true);
                        } catch (Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsConfig = &$controller->pSetup['includeJS.'][$key . '.'];
                        $crossOrigin = (string)($jsConfig['crossorigin'] ?? '');
                        if ($crossOrigin === '' && ($jsConfig['integrity'] ?? false) && ($jsConfig['external'] ?? false)) {
                            $crossOrigin = 'anonymous';
                        }
                        $pageRenderer->addJsFile(
                            $ss,
                            $jsConfig['type'] ?? null,
                            empty($jsConfig['external']) && empty($jsConfig['disableCompression']),
                            (bool)($jsConfig['forceOnTop'] ?? false),
                            $jsConfig['allWrap'] ?? '',
                            (bool)($jsConfig['excludeFromConcatenation'] ?? false),
                            $jsConfig['allWrap.']['splitChar'] ?? '|',
                            (bool)($jsConfig['async'] ?? false),
                            $jsConfig['integrity'] ?? '',
                            (bool)($jsConfig['defer'] ?? false),
                            $crossOrigin,
                            (bool)($jsConfig['nomodule'] ?? false),
                            $jsConfig['data.'] ?? []
                        );
                        unset($jsConfig);
                    }
                }
            }
        }
        if (is_array($controller->pSetup['includeJSFooter.'] ?? null)) {
            foreach ($controller->pSetup['includeJSFooter.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($controller->pSetup['includeJSFooter.'][$key . '.']['if.']) && !$controller->cObj->checkIf($controller->pSetup['includeJSFooter.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($controller->pSetup['includeJSFooter.'][$key . '.']['external'] ?? false) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile, true);
                        } catch (Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsConfig = &$controller->pSetup['includeJSFooter.'][$key . '.'];
                        $crossOrigin = (string)($jsConfig['crossorigin'] ?? '');
                        if ($crossOrigin === '' && ($jsConfig['integrity'] ?? false) && ($jsConfig['external'] ?? false)) {
                            $crossOrigin = 'anonymous';
                        }
                        $pageRenderer->addJsFooterFile(
                            $ss,
                            $jsConfig['type'] ?? null,
                            empty($jsConfig['external']) && empty($jsConfig['disableCompression']),
                            (bool)($jsConfig['forceOnTop'] ?? false),
                            $jsConfig['allWrap'] ?? '',
                            (bool)($jsConfig['excludeFromConcatenation'] ?? false),
                            $jsConfig['allWrap.']['splitChar'] ?? '|',
                            (bool)($jsConfig['async'] ?? false),
                            $jsConfig['integrity'] ?? '',
                            (bool)($jsConfig['defer'] ?? false),
                            $crossOrigin,
                            (bool)($jsConfig['nomodule'] ?? false),
                            $jsConfig['data.'] ?? []
                        );
                        unset($jsConfig);
                    }
                }
            }
        }
        // Headerdata
        if (is_array($controller->pSetup['headerData.'] ?? null)) {
            $pageRenderer->addHeaderData($controller->cObj->cObjGet($controller->pSetup['headerData.'], 'headerData.'));
        }
        // Footerdata
        if (is_array($controller->pSetup['footerData.'] ?? null)) {
            $pageRenderer->addFooterData($controller->cObj->cObjGet($controller->pSetup['footerData.'], 'footerData.'));
        }
        $controller->generatePageTitle();

        // @internal hook for EXT:seo, will be gone soon, do not use it in your own extensions
        $_params = ['page' => $controller->page];
        $_ref = null;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Frontend\Page\PageGenerator']['generateMetaTags'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $_ref);
        }

        $this->generateHrefLangTags($controller, $request);
        $this->generateMetaTagHtml(
            $controller->pSetup['meta.'] ?? [],
            $controller->cObj
        );

        // Javascript inline code
        $inlineJS = (string)$controller->cObj->cObjGet($controller->pSetup['jsInline.'] ?? null, 'jsInline.');
        // Javascript inline code for Footer
        $inlineFooterJs = (string)$controller->cObj->cObjGet($controller->pSetup['jsFooterInline.'] ?? null, 'jsFooterInline.');
        $compressJs = (bool)($controller->config['config']['compressJs'] ?? false);

        // Needs to be called after call cObjGet() calls in order to get all headerData and footerData and replacements
        // see #100216
        $controller->INTincScript_loadJSCode();

        // this option is set to "external" as default
        if (($controller->config['config']['removeDefaultJS'] ?? 'external') === 'external') {
            /*
             * This keeps inlineJS from *_INT Objects from being moved to external files.
             * At this point in frontend rendering *_INT Objects only have placeholders instead
             * of actual content so moving these placeholders to external files would
             *     a) break the JS file (syntax errors due to the placeholders)
             *     b) the needed JS would never get included to the page
             * Therefore inlineJS from *_INT Objects must not be moved to external files but
             * kept internal.
             */
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
        if (isset($controller->pSetup['inlineLanguageLabelFiles.']) && is_array($controller->pSetup['inlineLanguageLabelFiles.'])) {
            foreach ($controller->pSetup['inlineLanguageLabelFiles.'] as $key => $languageFile) {
                if (is_array($languageFile)) {
                    continue;
                }
                $languageFileConfig = &$controller->pSetup['inlineLanguageLabelFiles.'][$key . '.'];
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
        if (isset($controller->pSetup['inlineSettings.']) && is_array($controller->pSetup['inlineSettings.'])) {
            $pageRenderer->addInlineSettingArray('TS', $controller->pSetup['inlineSettings.']);
        }
        // Compression and concatenate settings
        if ($controller->config['config']['compressCss'] ?? false) {
            $pageRenderer->enableCompressCss();
        }
        if ($compressJs ?? false) {
            $pageRenderer->enableCompressJavascript();
        }
        if ($controller->config['config']['concatenateCss'] ?? false) {
            $pageRenderer->enableConcatenateCss();
        }
        if ($controller->config['config']['concatenateJs'] ?? false) {
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
        if ($controller->config['config']['disableBodyTag'] ?? false) {
            $bodyTag = '';
        } else {
            $defBT = (isset($controller->pSetup['bodyTagCObject']) && $controller->pSetup['bodyTagCObject'])
                ? $controller->cObj->cObjGetSingle($controller->pSetup['bodyTagCObject'], $controller->pSetup['bodyTagCObject.'] ?? [], 'bodyTagCObject')
                : '<body>';
            $bodyTag = (isset($controller->pSetup['bodyTag']) && $controller->pSetup['bodyTag'])
                ? $controller->pSetup['bodyTag']
                : $defBT;
            if (trim($controller->pSetup['bodyTagAdd'] ?? '')) {
                $bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' ' . trim($controller->pSetup['bodyTagAdd']) . '>';
            }
        }
        $pageRenderer->addBodyContent(LF . $bodyTag);
    }

    /*************************
     *
     * Helper functions
     *
     *************************/

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
     * @param array $metaTagTypoScript TypoScript configuration for meta tags (e.g. $GLOBALS['TSFE']->pSetup['meta.'])
     */
    protected function generateMetaTagHtml(array $metaTagTypoScript, ContentObjectRenderer $cObj)
    {
        $pageRenderer = $this->getPageRenderer();

        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $conf = $typoScriptService->convertTypoScriptArrayToPlainArray($metaTagTypoScript);
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
    protected function addCssToPageRenderer(TypoScriptFrontendController $controller, string $cssStyles, bool $excludeFromConcatenation, string $inlineBlockName)
    {
        // This option is enabled by default on purpose
        if (empty($controller->config['config']['inlineStyle2TempFile'] ?? true)) {
            $this->getPageRenderer()->addCssInlineBlock($inlineBlockName, $cssStyles, !empty($controller->config['config']['compressCss'] ?? false));
        } else {
            $this->getPageRenderer()->addCssFile(
                GeneralUtility::writeStyleSheetContentToTemporaryFile($cssStyles),
                'stylesheet',
                'all',
                '',
                (bool)($controller->config['config']['compressCss'] ?? false),
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

    protected function generateHrefLangTags(TypoScriptFrontendController $controller, ServerRequestInterface $request): void
    {
        if ($controller->config['config']['disableHrefLang'] ?? false) {
            return;
        }

        $hrefLangs = $this->eventDispatcher->dispatch(
            new ModifyHrefLangTagsEvent($request)
        )->getHrefLangs();
        if (count($hrefLangs) > 1) {
            $data = [];
            foreach ($hrefLangs as $hrefLang => $href) {
                $data[] = sprintf('<link %s/>', GeneralUtility::implodeAttributes([
                    'rel' => 'alternate',
                    'hreflang' => $hrefLang,
                    'href' => $href,
                ], true));
            }
            $controller->additionalHeaderData[] = implode(LF, $data);
        }
    }

    /**
     * Include the preview block in case we're looking at a hidden page in the LIVE workspace
     *
     * @internal this method might get moved to a PSR-15 middleware at some point
     */
    protected function displayPreviewInfoMessage(TypoScriptFrontendController $controller)
    {
        $context = $controller->getContext();
        $isInWorkspace = $context->getPropertyFromAspect('workspace', 'isOffline', false);
        $isInPreviewMode = $context->hasAspect('frontend.preview')
            && $context->getPropertyFromAspect('frontend.preview', 'isPreview');
        if (!$isInPreviewMode || $isInWorkspace || ($controller->config['config']['disablePreviewNotification'] ?? false)) {
            return;
        }
        if ($controller->config['config']['message_preview'] ?? '') {
            $message = $controller->config['config']['message_preview'];
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
}
