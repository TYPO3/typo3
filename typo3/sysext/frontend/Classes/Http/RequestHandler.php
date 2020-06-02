<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Http;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

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
class RequestHandler implements RequestHandlerInterface, PsrRequestHandlerInterface
{
    /**
     * Instance of the timetracker
     * @var TimeTracker
     */
    protected $timeTracker;

    /**
     * Handles a frontend request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    /**
     * Puts parameters that have been added or removed from the global _GET or _POST arrays
     * into the given request (however, the PSR-7 request information takes precedence).
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function addModifiedGlobalsToIncomingRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $originalGetParameters = $request->getAttribute('_originalGetParameters', null);
        if ($originalGetParameters !== null && !empty($_GET) && $_GET !== $originalGetParameters) {
            // Find out what has been changed.
            $modifiedGetParameters = ArrayUtility::arrayDiffAssocRecursive($_GET ?? [], $originalGetParameters);
            if (!empty($modifiedGetParameters)) {
                $queryParams = array_replace_recursive($modifiedGetParameters, $request->getQueryParams());
                $request = $request->withQueryParams($queryParams);
                $GLOBALS['TYPO3_REQUEST'] = $request;
                $this->timeTracker->setTSlogMessage('GET parameters have been modified during Request building in a hook.');
            }
        }
        // do same for $_POST if the request is a POST request
        $originalPostParameters = $request->getAttribute('_originalPostParameters', null);
        if ($request->getMethod() === 'POST' && $originalPostParameters !== null && !empty($_POST) && $_POST !== $originalPostParameters) {
            // Find out what has been changed
            $modifiedPostParameters = ArrayUtility::arrayDiffAssocRecursive($_POST ?? [], $originalPostParameters);
            if (!empty($modifiedPostParameters)) {
                $parsedBody = array_replace_recursive($modifiedPostParameters, $request->getParsedBody());
                $request = $request->withParsedBody($parsedBody);
                $GLOBALS['TYPO3_REQUEST'] = $request;
                $this->timeTracker->setTSlogMessage('POST parameters have been modified during Request building in a hook.');
            }
        }
        return $request;
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
    }
    /**
     * Handles a frontend request, after finishing running middlewares
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Fetch the initialized time tracker object
        $this->timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
        /** @var TypoScriptFrontendController $controller */
        $controller = $GLOBALS['TSFE'];

        // safety net, will be removed in TYPO3 v10.0. Aligns $_GET/$_POST to the incoming request.
        $request = $this->addModifiedGlobalsToIncomingRequest($request);
        $this->resetGlobalsToCurrentRequest($request);

        // Generate page
        if ($controller->isGeneratePage()) {
            $this->timeTracker->push('Page generation');
            $controller->generatePage_preProcessing();
            $controller->preparePageContentGeneration($request);

            // Content generation
            $this->timeTracker->incStackPointer();
            $this->timeTracker->push($controller->sPre, 'PAGE');

            // If 'disableAllHeaderCode' is set, all the header-code is discarded
            if ($controller->config['config']['disableAllHeaderCode'] ?? false) {
                $controller->content = $this->generatePageContent($controller);
            } else {
                $controller->content = $this->generatePageContentWithHeader($controller, $request->getAttribute('language', null));
            }

            $this->timeTracker->pull($this->timeTracker->LR ? $controller->content : '');
            $this->timeTracker->decStackPointer();

            $controller->setAbsRefPrefix();
            $controller->generatePage_postProcessing();
            $this->timeTracker->pull();
        }
        $controller->releaseLocks();

        // Render non-cached page parts by replacing placeholders which are taken from cache or added during page generation
        if ($controller->isINTincScript()) {
            if (!$controller->isGeneratePage()) {
                // When page was generated, this was already called. Avoid calling this twice.
                $controller->preparePageContentGeneration($request);
            }
            $this->timeTracker->push('Non-cached objects');
            $controller->INTincScript();
            $this->timeTracker->pull();
        }

        // Create a Response object when sending content
        $response = new Response();

        // Output content
        $isOutputting = $controller->isOutputting();
        if ($isOutputting) {
            $this->timeTracker->push('Print Content');
            $response = $controller->applyHttpHeadersToResponse($response);
            $controller->processContentForOutput();
            $this->timeTracker->pull();
        }
        // Store session data for fe_users
        $controller->fe_user->storeSessionData();

        // @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0.
        $redirectResponse = $controller->redirectToExternalUrl(true);
        if ($redirectResponse instanceof ResponseInterface) {
            $controller->sendHttpHeadersDirectly();
            return $redirectResponse;
        }

        // Statistics
        $GLOBALS['TYPO3_MISC']['microtime_end'] = microtime(true);
        if ($isOutputting && ($controller->config['config']['debug'] ?? !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']))) {
            $response = $response->withHeader('X-TYPO3-Parsetime', $this->timeTracker->getParseTime() . 'ms');
        }

        // Preview info
        // @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0.
        $controller->previewInfo(true);

        // Hook for "end-of-frontend"
        $_params = ['pObj' => &$controller];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $controller);
        }

        // Finish time tracking (started in TYPO3\CMS\Frontend\Middleware\TimeTrackerInitialization)
        $this->timeTracker->pull();

        if ($isOutputting) {
            $response->getBody()->write($controller->content);
        }

        return $isOutputting ? $response : new NullResponse();
    }

    /**
     * Generates the main content part within <body> tags (except JS files/CSS files).
     *
     * @param TypoScriptFrontendController $controller
     * @return string
     */
    protected function generatePageContent(TypoScriptFrontendController $controller): string
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
     * Rendering normal HTML-page with header by wrapping the generated content ($pageContent) in body-tags and setting the header accordingly.
     * Render HTML page with header parts (<head> tag content and wrap around <body> tag) - this is done
     * after the "main" page Content, since some JS may be inserted at that point.
     *
     * @param TypoScriptFrontendController $controller
     * @param SiteLanguage|null $siteLanguage
     * @return string
     */
    protected function generatePageContentWithHeader(TypoScriptFrontendController $controller, ?SiteLanguage $siteLanguage): string
    {
        // Generate the page content, this has to be first, as some additional TSFE-related code could have been written
        $pageContent = $this->generatePageContent($controller);
        $pageRenderer = $this->getPageRenderer();
        if ($controller->config['config']['moveJsFromHeaderToFooter'] ?? false) {
            $pageRenderer->enableMoveJsFromHeaderToFooter();
        }
        if ($controller->config['config']['pageRendererTemplateFile'] ?? false) {
            try {
                $file = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($controller->config['config']['pageRendererTemplateFile']);
                $pageRenderer->setTemplateFile($file);
            } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                // do nothing
            }
        }
        $headerComment = trim($controller->config['config']['headerComment'] ?? '');
        if ($headerComment) {
            $pageRenderer->addInlineComment("\t" . str_replace(LF, LF . "\t", $headerComment) . LF);
        }
        // Setting charset:
        $theCharset = $controller->metaCharset;
        // Reset the content variables:
        $controller->content = '';
        $htmlTagAttributes = [];
        $htmlLang = $controller->config['config']['htmlTag_langKey'] ?? ($controller->sys_language_isocode ?: 'en');
        $direction = $controller->config['config']['htmlTag_dir'] ?? null;
        if ($siteLanguage !== null) {
            $direction = $siteLanguage->getDirection();
            $htmlLang = $siteLanguage->getHreflang();
        }

        if ($direction) {
            $htmlTagAttributes['dir'] = htmlspecialchars($direction);
        }
        // Setting document type:
        $docTypeParts = [];
        $xmlDocument = true;
        // Part 1: XML prologue
        switch ((string)($controller->config['config']['xmlprologue'] ?? '')) {
            case 'none':
                $xmlDocument = false;
                break;
            case 'xml_10':
                $docTypeParts[] = '<?xml version="1.0" encoding="' . $theCharset . '"?>';
                break;
            case 'xml_11':
                $docTypeParts[] = '<?xml version="1.1" encoding="' . $theCharset . '"?>';
                break;
            case '':
                if ($controller->xhtmlVersion) {
                    $docTypeParts[] = '<?xml version="1.0" encoding="' . $theCharset . '"?>';
                } else {
                    $xmlDocument = false;
                }
                break;
            default:
                $docTypeParts[] = $controller->config['config']['xmlprologue'];
        }
        // Part 2: DTD
        $doctype = $controller->config['config']['doctype'] ?? null;
        if ($doctype) {
            switch ($doctype) {
                case 'xhtml_trans':
                    $docTypeParts[] = '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
                    break;
                case 'xhtml_strict':
                    $docTypeParts[] = '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
                    break;
                case 'xhtml_basic':
                    $docTypeParts[] = '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN"
    "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">';
                    break;
                case 'xhtml_11':
                    $docTypeParts[] = '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
                    break;
                case 'xhtml+rdfa_10':
                    $docTypeParts[] = '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"
    "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">';
                    break;
                case 'html5':
                    $docTypeParts[] = '<!DOCTYPE html>';
                    if ($xmlDocument) {
                        $pageRenderer->setMetaCharsetTag('<meta charset="|" />');
                    } else {
                        $pageRenderer->setMetaCharsetTag('<meta charset="|">');
                    }
                    break;
                case 'none':
                    break;
                default:
                    $docTypeParts[] = $doctype;
            }
        } else {
            $docTypeParts[] = '<!DOCTYPE html>';
            if ($xmlDocument) {
                $pageRenderer->setMetaCharsetTag('<meta charset="|" />');
            } else {
                $pageRenderer->setMetaCharsetTag('<meta charset="|">');
            }
        }
        if ($controller->xhtmlVersion) {
            $htmlTagAttributes['xml:lang'] = $htmlLang;
        }
        if ($controller->xhtmlVersion < 110 || $doctype === 'html5') {
            $htmlTagAttributes['lang'] = $htmlLang;
        }
        if ($controller->xhtmlVersion || $doctype === 'html5' && $xmlDocument) {
            // We add this to HTML5 to achieve a slightly better backwards compatibility
            $htmlTagAttributes['xmlns'] = 'http://www.w3.org/1999/xhtml';
            if (is_array($controller->config['config']['namespaces.'])) {
                foreach ($controller->config['config']['namespaces.'] as $prefix => $uri) {
                    // $uri gets htmlspecialchared later
                    $htmlTagAttributes['xmlns:' . htmlspecialchars($prefix)] = $uri;
                }
            }
        }
        // Swap XML and doctype order around (for MSIE / Opera standards compliance)
        if ($controller->config['config']['doctypeSwitch'] ?? false) {
            $docTypeParts = array_reverse($docTypeParts);
        }
        // Adding doctype parts:
        if (!empty($docTypeParts)) {
            $pageRenderer->setXmlPrologAndDocType(implode(LF, $docTypeParts));
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
        // Setting charset meta tag:
        $pageRenderer->setCharSet($theCharset);
        $pageRenderer->addInlineComment('	This website is powered by TYPO3 - inspiring people to share!
	TYPO3 is a free open source Content Management Framework initially created by Kasper Skaarhoj and licensed under GNU/GPL.
	TYPO3 is copyright ' . TYPO3_copyright_year . ' of Kasper Skaarhoj. Extensions are copyright of their respective owners.
	Information and contribution at ' . TYPO3_URL_GENERAL . '
');
        if ($controller->baseUrl) {
            $pageRenderer->setBaseUrl($controller->baseUrl);
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
            } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                // do nothing
            }
        }
        // Including CSS files
        if (isset($controller->tmpl->setup['plugin.']) && is_array($controller->tmpl->setup['plugin.'])) {
            $stylesFromPlugins = '';
            foreach ($controller->tmpl->setup['plugin.'] as $key => $iCSScode) {
                if (is_array($iCSScode)) {
                    if ($iCSScode['_CSS_DEFAULT_STYLE'] && empty($controller->config['config']['removeDefaultCss'])) {
                        if (isset($iCSScode['_CSS_DEFAULT_STYLE.'])) {
                            $cssDefaultStyle = $controller->cObj->stdWrap($iCSScode['_CSS_DEFAULT_STYLE'], $iCSScode['_CSS_DEFAULT_STYLE.']);
                        } else {
                            $cssDefaultStyle = $iCSScode['_CSS_DEFAULT_STYLE'];
                        }
                        $stylesFromPlugins .= '/* default styles for extension "' . substr($key, 0, -1) . '" */' . LF . $cssDefaultStyle . LF;
                    }
                    if ($iCSScode['_CSS_PAGE_STYLE'] && empty($controller->config['config']['removePageCss'])) {
                        $cssPageStyle = implode(LF, $iCSScode['_CSS_PAGE_STYLE']);
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
        if (isset($controller->pSetup['includeCSS.']) && is_array($controller->pSetup['includeCSS.'])) {
            foreach ($controller->pSetup['includeCSS.'] as $key => $CSSfile) {
                if (!is_array($CSSfile)) {
                    $cssFileConfig = &$controller->pSetup['includeCSS.'][$key . '.'];
                    if (isset($cssFileConfig['if.']) && !$controller->cObj->checkIf($cssFileConfig['if.'])) {
                        continue;
                    }
                    if ($cssFileConfig['external']) {
                        $ss = $CSSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($CSSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        if ($cssFileConfig['import']) {
                            if (!$cssFileConfig['external'] && $ss[0] !== '/') {
                                // To fix MSIE 6 that cannot handle these as relative paths (according to Ben v Ende)
                                $ss = GeneralUtility::dirname(GeneralUtility::getIndpEnv('SCRIPT_NAME')) . '/' . $ss;
                            }
                            $cssMedia = !empty($cssFileConfig['media']) ? ' ' . htmlspecialchars($cssFileConfig['media']) : '';
                            $pageRenderer->addCssInlineBlock('import_' . $key, '@import url("' . htmlspecialchars($ss) . '")' . $cssMedia . ';', empty($cssFileConfig['disableCompression']), (bool)$cssFileConfig['forceOnTop']);
                        } else {
                            $pageRenderer->addCssFile(
                                $ss,
                                $cssFileConfig['alternate'] ? 'alternate stylesheet' : 'stylesheet',
                                $cssFileConfig['media'] ?: 'all',
                                $cssFileConfig['title'] ?: '',
                                $cssFileConfig['external']  || (bool)$cssFileConfig['inline'] ? false : empty($cssFileConfig['disableCompression']),
                                (bool)$cssFileConfig['forceOnTop'],
                                $cssFileConfig['allWrap'],
                                (bool)$cssFileConfig['excludeFromConcatenation'] || (bool)$cssFileConfig['inline'],
                                $cssFileConfig['allWrap.']['splitChar'],
                                $cssFileConfig['inline']
                            );
                            unset($cssFileConfig);
                        }
                    }
                }
            }
        }
        if (isset($controller->pSetup['includeCSSLibs.']) && is_array($controller->pSetup['includeCSSLibs.'])) {
            foreach ($controller->pSetup['includeCSSLibs.'] as $key => $CSSfile) {
                if (!is_array($CSSfile)) {
                    $cssFileConfig = &$controller->pSetup['includeCSSLibs.'][$key . '.'];
                    if (isset($cssFileConfig['if.']) && !$controller->cObj->checkIf($cssFileConfig['if.'])) {
                        continue;
                    }
                    if ($cssFileConfig['external']) {
                        $ss = $CSSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($CSSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        if ($cssFileConfig['import']) {
                            if (!$cssFileConfig['external'] && $ss[0] !== '/') {
                                // To fix MSIE 6 that cannot handle these as relative paths (according to Ben v Ende)
                                $ss = GeneralUtility::dirname(GeneralUtility::getIndpEnv('SCRIPT_NAME')) . '/' . $ss;
                            }
                            $cssMedia = !empty($cssFileConfig['media']) ? ' ' . htmlspecialchars($cssFileConfig['media']) : '';
                            $pageRenderer->addCssInlineBlock('import_' . $key, '@import url("' . htmlspecialchars($ss) . '")' . $cssMedia . ';', empty($cssFileConfig['disableCompression']), (bool)$cssFileConfig['forceOnTop']);
                        } else {
                            $pageRenderer->addCssLibrary(
                                $ss,
                                $cssFileConfig['alternate'] ? 'alternate stylesheet' : 'stylesheet',
                                $cssFileConfig['media'] ?: 'all',
                                $cssFileConfig['title'] ?: '',
                                $cssFileConfig['external'] || (bool)$cssFileConfig['inline'] ? false : empty($cssFileConfig['disableCompression']),
                                (bool)$cssFileConfig['forceOnTop'],
                                $cssFileConfig['allWrap'],
                                (bool)$cssFileConfig['excludeFromConcatenation'] || (bool)$cssFileConfig['inline'],
                                $cssFileConfig['allWrap.']['splitChar'],
                                $cssFileConfig['inline']
                            );
                            unset($cssFileConfig);
                        }
                    }
                }
            }
        }

        // CSS_inlineStyle from TS
        $style = trim($controller->pSetup['CSS_inlineStyle'] ?? '');
        $style .= $controller->cObj->cObjGet($controller->pSetup['cssInline.'] ?? null, 'cssInline.');
        if (trim($style)) {
            $this->addCssToPageRenderer($controller, $style, true, 'additionalTSFEInlineStyle');
        }
        // Javascript Libraries
        if (isset($controller->pSetup['javascriptLibs.']) && is_array($controller->pSetup['javascriptLibs.'])) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, the setting page.javascriptLibs has been deprecated and will be removed in TYPO3 v10.0.
            trigger_error('The setting page.javascriptLibs will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);

            // Include jQuery into the page renderer
            if (!empty($controller->pSetup['javascriptLibs.']['jQuery'])) {
                // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, the setting page.javascriptLibs.jQuery has been deprecated and will be removed in TYPO3 v10.0.
                trigger_error('The setting page.javascriptLibs.jQuery will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);

                $jQueryTS = $controller->pSetup['javascriptLibs.']['jQuery.'];
                // Check if version / source is set, if not set variable to "NULL" to use the default of the page renderer
                $version = $jQueryTS['version'] ?? null;
                $source = $jQueryTS['source'] ?? null;
                // When "noConflict" is not set or "1" enable the default jQuery noConflict mode, otherwise disable the namespace
                if (!isset($jQueryTS['noConflict']) || !empty($jQueryTS['noConflict'])) {
                    $namespace = 'noConflict';
                } else {
                    $namespace = PageRenderer::JQUERY_NAMESPACE_NONE;
                }
                $pageRenderer->loadJquery($version, $source, $namespace, true);
            }
        }
        // JavaScript library files
        if (isset($controller->pSetup['includeJSLibs.']) && is_array($controller->pSetup['includeJSLibs.'])) {
            foreach ($controller->pSetup['includeJSLibs.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($controller->pSetup['includeJSLibs.'][$key . '.']['if.']) && !$controller->cObj->checkIf($controller->pSetup['includeJSLibs.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($controller->pSetup['includeJSLibs.'][$key . '.']['external']) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsFileConfig = &$controller->pSetup['includeJSLibs.'][$key . '.'];
                        $type = $jsFileConfig['type'];
                        if (!$type) {
                            $type = 'text/javascript';
                        }
                        $crossOrigin = $jsFileConfig['crossorigin'];
                        if (!$crossOrigin && $jsFileConfig['integrity'] && $jsFileConfig['external']) {
                            $crossOrigin = 'anonymous';
                        }
                        $pageRenderer->addJsLibrary(
                            $key,
                            $ss,
                            $type,
                            $jsFileConfig['external'] ? false : empty($jsFileConfig['disableCompression']),
                            (bool)$jsFileConfig['forceOnTop'],
                            $jsFileConfig['allWrap'],
                            (bool)$jsFileConfig['excludeFromConcatenation'],
                            $jsFileConfig['allWrap.']['splitChar'],
                            (bool)$jsFileConfig['async'],
                            $jsFileConfig['integrity'],
                            (bool)$jsFileConfig['defer'],
                            $crossOrigin
                        );
                        unset($jsFileConfig);
                    }
                }
            }
        }
        if (isset($controller->pSetup['includeJSFooterlibs.']) && is_array($controller->pSetup['includeJSFooterlibs.'])) {
            foreach ($controller->pSetup['includeJSFooterlibs.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($controller->pSetup['includeJSFooterlibs.'][$key . '.']['if.']) && !$controller->cObj->checkIf($controller->pSetup['includeJSFooterlibs.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($controller->pSetup['includeJSFooterlibs.'][$key . '.']['external']) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsFileConfig = &$controller->pSetup['includeJSFooterlibs.'][$key . '.'];
                        $type = $jsFileConfig['type'];
                        if (!$type) {
                            $type = 'text/javascript';
                        }
                        $crossorigin = $jsFileConfig['crossorigin'];
                        if (!$crossorigin && $jsFileConfig['integrity'] && $jsFileConfig['external']) {
                            $crossorigin = 'anonymous';
                        }
                        $pageRenderer->addJsFooterLibrary(
                            $key,
                            $ss,
                            $type,
                            $jsFileConfig['external'] ? false : empty($jsFileConfig['disableCompression']),
                            (bool)$jsFileConfig['forceOnTop'],
                            $jsFileConfig['allWrap'],
                            (bool)$jsFileConfig['excludeFromConcatenation'],
                            $jsFileConfig['allWrap.']['splitChar'],
                            (bool)$jsFileConfig['async'],
                            $jsFileConfig['integrity'],
                            (bool)$jsFileConfig['defer'],
                            $crossorigin
                        );
                        unset($jsFileConfig);
                    }
                }
            }
        }
        // JavaScript files
        if (isset($controller->pSetup['includeJS.']) && is_array($controller->pSetup['includeJS.'])) {
            foreach ($controller->pSetup['includeJS.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($controller->pSetup['includeJS.'][$key . '.']['if.']) && !$controller->cObj->checkIf($controller->pSetup['includeJS.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($controller->pSetup['includeJS.'][$key . '.']['external']) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsConfig = &$controller->pSetup['includeJS.'][$key . '.'];
                        $type = $jsConfig['type'];
                        if (!$type) {
                            $type = 'text/javascript';
                        }
                        $crossorigin = $jsConfig['crossorigin'];
                        if (!$crossorigin && $jsConfig['integrity'] && $jsConfig['external']) {
                            $crossorigin = 'anonymous';
                        }
                        $pageRenderer->addJsFile(
                            $ss,
                            $type,
                            $jsConfig['external'] ? false : empty($jsConfig['disableCompression']),
                            (bool)$jsConfig['forceOnTop'],
                            $jsConfig['allWrap'],
                            (bool)$jsConfig['excludeFromConcatenation'],
                            $jsConfig['allWrap.']['splitChar'],
                            (bool)$jsConfig['async'],
                            $jsConfig['integrity'],
                            (bool)$jsConfig['defer'],
                            $crossorigin
                        );
                        unset($jsConfig);
                    }
                }
            }
        }
        if (isset($controller->pSetup['includeJSFooter.']) && is_array($controller->pSetup['includeJSFooter.'])) {
            foreach ($controller->pSetup['includeJSFooter.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($controller->pSetup['includeJSFooter.'][$key . '.']['if.']) && !$controller->cObj->checkIf($controller->pSetup['includeJSFooter.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($controller->pSetup['includeJSFooter.'][$key . '.']['external']) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsConfig = &$controller->pSetup['includeJSFooter.'][$key . '.'];
                        $type = $jsConfig['type'];
                        if (!$type) {
                            $type = 'text/javascript';
                        }
                        $crossorigin = $jsConfig['crossorigin'];
                        if (!$crossorigin && $jsConfig['integrity'] && $jsConfig['external']) {
                            $crossorigin = 'anonymous';
                        }
                        $pageRenderer->addJsFooterFile(
                            $ss,
                            $type,
                            $jsConfig['external'] ? false : empty($jsConfig['disableCompression']),
                            (bool)$jsConfig['forceOnTop'],
                            $jsConfig['allWrap'],
                            (bool)$jsConfig['excludeFromConcatenation'],
                            $jsConfig['allWrap.']['splitChar'],
                            (bool)$jsConfig['async'],
                            $jsConfig['integrity'],
                            (bool)$jsConfig['defer'],
                            $crossorigin
                        );
                        unset($jsConfig);
                    }
                }
            }
        }
        // Headerdata
        if (isset($controller->pSetup['headerData.']) && is_array($controller->pSetup['headerData.'])) {
            $pageRenderer->addHeaderData($controller->cObj->cObjGet($controller->pSetup['headerData.'], 'headerData.'));
        }
        // Footerdata
        if (isset($controller->pSetup['footerData.']) && is_array($controller->pSetup['footerData.'])) {
            $pageRenderer->addFooterData($controller->cObj->cObjGet($controller->pSetup['footerData.'], 'footerData.'));
        }
        $controller->generatePageTitle();

        // @internal hook for EXT:seo, will be gone soon, do not use it in your own extensions
        $_params = ['page' => $controller->page];
        $_ref = '';
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Frontend\Page\PageGenerator']['generateMetaTags'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $_ref);
        }

        $this->generateMetaTagHtml(
            $controller->pSetup['meta.'] ?? [],
            $controller->cObj
        );

        unset($controller->additionalHeaderData['JSCode']);
        if (isset($controller->config['INTincScript']) && is_array($controller->config['INTincScript'])) {
            $controller->additionalHeaderData['JSCode'] = $controller->JSCode;
            // Storing the JSCode vars...
            $controller->config['INTincScript_ext']['divKey'] = $controller->uniqueHash();
            $controller->config['INTincScript_ext']['additionalHeaderData'] = $controller->additionalHeaderData;
            // Storing the header-data array
            $controller->config['INTincScript_ext']['additionalFooterData'] = $controller->additionalFooterData;
            // Storing the footer-data array
            $controller->config['INTincScript_ext']['additionalJavaScript'] = $controller->additionalJavaScript;
            // Storing the JS-data array
            $controller->config['INTincScript_ext']['additionalCSS'] = $controller->additionalCSS;
            // Storing the Style-data array
            $controller->additionalHeaderData = ['<!--HD_' . $controller->config['INTincScript_ext']['divKey'] . '-->'];
            // Clearing the array
            $controller->additionalFooterData = ['<!--FD_' . $controller->config['INTincScript_ext']['divKey'] . '-->'];
            // Clearing the array
            $controller->divSection .= '<!--TDS_' . $controller->config['INTincScript_ext']['divKey'] . '-->';
        } else {
            $controller->INTincScript_loadJSCode();
        }
        $scriptJsCode = '';

        if ($controller->spamProtectEmailAddresses && $controller->spamProtectEmailAddresses !== 'ascii') {
            $scriptJsCode = '
			/* decrypt helper function */
		function decryptCharcode(n,start,end,offset) {
			n = n + offset;
			if (offset > 0 && n > end) {
				n = start + (n - end - 1);
			} else if (offset < 0 && n < start) {
				n = end - (start - n - 1);
			}
			return String.fromCharCode(n);
		}
			/* decrypt string */
		function decryptString(enc,offset) {
			var dec = "";
			var len = enc.length;
			for(var i=0; i < len; i++) {
				var n = enc.charCodeAt(i);
				if (n >= 0x2B && n <= 0x3A) {
					dec += decryptCharcode(n,0x2B,0x3A,offset);	/* 0-9 . , - + / : */
				} else if (n >= 0x40 && n <= 0x5A) {
					dec += decryptCharcode(n,0x40,0x5A,offset);	/* A-Z @ */
				} else if (n >= 0x61 && n <= 0x7A) {
					dec += decryptCharcode(n,0x61,0x7A,offset);	/* a-z */
				} else {
					dec += enc.charAt(i);
				}
			}
			return dec;
		}
			/* decrypt spam-protected emails */
		function linkTo_UnCryptMailto(s) {
			location.href = decryptString(s,' . $controller->spamProtectEmailAddresses * -1 . ');
		}
		';
        }
        // Add inline JS
        $inlineJS = '';
        // defined in php
        if (is_array($controller->inlineJS)) {
            foreach ($controller->inlineJS as $key => $val) {
                if (!is_array($val)) {
                    $inlineJS .= LF . $val . LF;
                }
            }
        }
        // defined in TS with page.inlineJS
        // Javascript inline code
        $inline = $controller->cObj->cObjGet($controller->pSetup['jsInline.'] ?? null, 'jsInline.');
        if ($inline) {
            $inlineJS .= LF . $inline . LF;
        }
        // Javascript inline code for Footer
        $inlineFooterJs = $controller->cObj->cObjGet($controller->pSetup['jsFooterInline.'] ?? null, 'jsFooterInline.');
        // Should minify?
        if ($controller->config['config']['compressJs'] ?? false) {
            $pageRenderer->enableCompressJavascript();
            $minifyErrorScript = ($minifyErrorInline = '');
            $scriptJsCode = GeneralUtility::minifyJavaScript($scriptJsCode, $minifyErrorScript);
            if ($minifyErrorScript) {
                $this->timeTracker->setTSlogMessage($minifyErrorScript, 3);
            }
            if ($inlineJS) {
                $inlineJS = GeneralUtility::minifyJavaScript($inlineJS, $minifyErrorInline);
                if ($minifyErrorInline) {
                    $this->timeTracker->setTSlogMessage($minifyErrorInline, 3);
                }
            }
            if ($inlineFooterJs) {
                $inlineFooterJs = GeneralUtility::minifyJavaScript($inlineFooterJs, $minifyErrorInline);
                if ($minifyErrorInline) {
                    $this->timeTracker->setTSlogMessage($minifyErrorInline, 3);
                }
            }
        }
        if (!isset($controller->config['config']['removeDefaultJS']) || !$controller->config['config']['removeDefaultJS']) {
            // include default and inlineJS
            if ($scriptJsCode) {
                $pageRenderer->addJsInlineCode('_scriptCode', $scriptJsCode, $controller->config['config']['compressJs']);
            }
            if ($inlineJS) {
                $pageRenderer->addJsInlineCode('TS_inlineJS', $inlineJS, $controller->config['config']['compressJs']);
            }
            if ($inlineFooterJs) {
                $pageRenderer->addJsFooterInlineCode('TS_inlineFooter', $inlineFooterJs, $controller->config['config']['compressJs']);
            }
        } elseif ($controller->config['config']['removeDefaultJS'] === 'external') {
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
                $pageRenderer->addJsInlineCode('TS_inlineJSint', $inlineJSint, $controller->config['config']['compressJs']);
            }
            if (trim($scriptJsCode . $inlineJS)) {
                $pageRenderer->addJsFile(GeneralUtility::writeJavaScriptContentToTemporaryFile($scriptJsCode . $inlineJS), 'text/javascript', $controller->config['config']['compressJs']);
            }
            if ($inlineFooterJs) {
                $inlineFooterJSint = '';
                $this->stripIntObjectPlaceholder($inlineFooterJs, $inlineFooterJSint);
                if ($inlineFooterJSint) {
                    $pageRenderer->addJsFooterInlineCode('TS_inlineFooterJSint', $inlineFooterJSint, $controller->config['config']['compressJs']);
                }
                $pageRenderer->addJsFooterFile(GeneralUtility::writeJavaScriptContentToTemporaryFile($inlineFooterJs), 'text/javascript', $controller->config['config']['compressJs']);
            }
        } else {
            // Include only inlineJS
            if ($inlineJS) {
                $pageRenderer->addJsInlineCode('TS_inlineJS', $inlineJS, $controller->config['config']['compressJs']);
            }
            if ($inlineFooterJs) {
                $pageRenderer->addJsFooterInlineCode('TS_inlineFooter', $inlineFooterJs, $controller->config['config']['compressJs']);
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
                    $languageFileConfig['selectionPrefix'] ?: '',
                    $languageFileConfig['stripFromSelectionName'] ?: ''
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
        if ($controller->config['config']['compressJs'] ?? false) {
            $pageRenderer->enableCompressJavascript();
        }
        if ($controller->config['config']['concatenateCss'] ?? false) {
            $pageRenderer->enableConcatenateCss();
        }
        if ($controller->config['config']['concatenateJs'] ?? false) {
            $pageRenderer->enableConcatenateJavascript();
        }
        // Backward compatibility for old configuration
        // @deprecated - remove this option in TYPO3 v10.0.
        if ($controller->config['config']['concatenateJsAndCss'] ?? false) {
            trigger_error('Setting config.concatenateJsAndCss is deprecated in favor of config.concatenateJs and config.concatenateCss, and will have no effect anymore in TYPO3 v10.0.', E_USER_DEPRECATED);
            $pageRenderer->enableConcatenateCss();
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
        // Header complete, now add content
        // Bodytag:
        if ($controller->config['config']['disableBodyTag'] ?? false) {
            $bodyTag = '';
        } else {
            $defBT = (isset($controller->pSetup['bodyTagCObject']) && $controller->pSetup['bodyTagCObject'])
                ? $controller->cObj->cObjGetSingle($controller->pSetup['bodyTagCObject'], $controller->pSetup['bodyTagCObject.'], 'bodyTagCObject')
                : '<body>';
            $bodyTag = (isset($controller->pSetup['bodyTag']) && $controller->pSetup['bodyTag'])
                ? $controller->pSetup['bodyTag']
                : $defBT;
            if (trim($controller->pSetup['bodyTagAdd'] ?? '')) {
                $bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' ' . trim($controller->pSetup['bodyTagAdd']) . '>';
            }
        }
        $pageRenderer->addBodyContent(LF . $bodyTag);
        // Div-sections
        if ($controller->divSection) {
            $pageRenderer->addBodyContent(LF . $controller->divSection);
        }
        // Page content
        $pageRenderer->addBodyContent(LF . $pageContent);
        if (!empty($controller->config['INTincScript']) && is_array($controller->config['INTincScript'])) {
            // Store the serialized pageRenderer in configuration
            $controller->config['INTincScript_ext']['pageRenderer'] = serialize($pageRenderer);
            // Render complete page, keep placeholders for JavaScript and CSS
            $pageContent = $pageRenderer->renderPageWithUncachedObjects($controller->config['INTincScript_ext']['divKey']);
        } else {
            // Render complete page
            $pageContent = $pageRenderer->render();
        }
        return $pageContent ?: '';
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
     * @param ContentObjectRenderer $cObj
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

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Adds inline CSS code, by respecting the inlineStyle2TempFile option
     *
     * @param TypoScriptFrontendController $controller
     * @param string $cssStyles the inline CSS styling
     * @param bool $excludeFromConcatenation option to see if it should be concatenated
     * @param string $inlineBlockName the block name to add it
     */
    protected function addCssToPageRenderer(TypoScriptFrontendController $controller, string $cssStyles, bool $excludeFromConcatenation, string $inlineBlockName)
    {
        if (empty($controller->config['config']['inlineStyle2TempFile'] ?? false)) {
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
     * Generates the <html> tag by evaluting TypoScript configuration, usually found via:
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

    /**
     * This request handler can handle any frontend request.
     *
     * @param ServerRequestInterface $request
     * @return bool If the request is not an eID request, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        return true;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 50;
    }
}
