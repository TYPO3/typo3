<?php
namespace TYPO3\CMS\Frontend\Page;

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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class for starting TypoScript page generation
 *
 * The class is not instantiated as an objects but called directly with the "::" operator.
 * eg: \TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit()
 */
class PageGenerator
{
    /**
     * Do not render title tag
     * Typoscript setting: [config][noPageTitle]
     */
    const NO_PAGE_TITLE = 2;

    /**
     * Setting some vars in TSFE, primarily based on TypoScript config settings.
     *
     * @return void
     */
    public static function pagegenInit()
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];
        if ($tsfe->page['content_from_pid'] > 0) {
            // make REAL copy of TSFE object - not reference!
            $temp_copy_TSFE = clone $tsfe;
            // Set ->id to the content_from_pid value - we are going to evaluate this pid as was it a given id for a page-display!
            $temp_copy_TSFE->id = $tsfe->page['content_from_pid'];
            $temp_copy_TSFE->MP = '';
            $temp_copy_TSFE->getPageAndRootlineWithDomain($tsfe->config['config']['content_from_pid_allowOutsideDomain'] ? 0 : $tsfe->domainStartPage);
            $tsfe->contentPid = (int)$temp_copy_TSFE->id;
            unset($temp_copy_TSFE);
        }
        if ($tsfe->config['config']['MP_defaults']) {
            $temp_parts = GeneralUtility::trimExplode('|', $tsfe->config['config']['MP_defaults'], true);
            foreach ($temp_parts as $temp_p) {
                list($temp_idP, $temp_MPp) = explode(':', $temp_p, 2);
                $temp_ids = GeneralUtility::intExplode(',', $temp_idP);
                foreach ($temp_ids as $temp_id) {
                    $tsfe->MP_defaults[$temp_id] = $temp_MPp;
                }
            }
        }
        // Global vars...
        $tsfe->indexedDocTitle = $tsfe->page['title'];
        $tsfe->debug = '' . $tsfe->config['config']['debug'];
        // Base url:
        if (isset($tsfe->config['config']['baseURL'])) {
            $tsfe->baseUrl = $tsfe->config['config']['baseURL'];
            // Deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
            $tsfe->anchorPrefix = substr(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), strlen(GeneralUtility::getIndpEnv('TYPO3_SITE_URL')));
        }
        // Internal and External target defaults
        $tsfe->intTarget = '' . $tsfe->config['config']['intTarget'];
        $tsfe->extTarget = '' . $tsfe->config['config']['extTarget'];
        $tsfe->fileTarget = '' . $tsfe->config['config']['fileTarget'];
        if ($tsfe->config['config']['spamProtectEmailAddresses'] === 'ascii') {
            $tsfe->spamProtectEmailAddresses = 'ascii';
        } else {
            $tsfe->spamProtectEmailAddresses = MathUtility::forceIntegerInRange($tsfe->config['config']['spamProtectEmailAddresses'], -10, 10, 0);
        }
        // calculate the absolute path prefix
        if (!empty($tsfe->config['config']['absRefPrefix'])) {
            $absRefPrefix = trim($tsfe->config['config']['absRefPrefix']);
            if ($absRefPrefix === 'auto') {
                $tsfe->absRefPrefix = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH');
            } else {
                $tsfe->absRefPrefix = $absRefPrefix;
            }
        } else {
            $tsfe->absRefPrefix = '';
        }
        if ($tsfe->type && $tsfe->config['config']['frameReloadIfNotInFrameset']) {
            $tdlLD = $tsfe->tmpl->linkData($tsfe->page, '_top', $tsfe->no_cache, '');
            $tsfe->additionalJavaScript['JSCode'] .= 'if(!parent.' . trim($tsfe->sPre) . ' && !parent.view_frame) top.location.href="' . $tsfe->baseUrlWrap($tdlLD['totalURL']) . '"';
        }
        $tsfe->compensateFieldWidth = '' . $tsfe->config['config']['compensateFieldWidth'];
        $tsfe->lockFilePath = '' . $tsfe->config['config']['lockFilePath'];
        $tsfe->lockFilePath = $tsfe->lockFilePath ?: $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'] = isset($tsfe->config['config']['noScaleUp']) ? '' . $tsfe->config['config']['noScaleUp'] : $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'];
        $tsfe->TYPO3_CONF_VARS['GFX']['im_noScaleUp'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'];
        $tsfe->ATagParams = trim($tsfe->config['config']['ATagParams']) ? ' ' . trim($tsfe->config['config']['ATagParams']) : '';
        if ($tsfe->config['config']['setJS_mouseOver']) {
            $tsfe->setJS('mouseOver');
        }
        if ($tsfe->config['config']['setJS_openPic']) {
            $tsfe->setJS('openPic');
        }
        static::initializeSearchWordDataInTsfe();
        // linkVars
        $tsfe->calculateLinkVars();
        // dtdAllowsFrames indicates whether to use the target attribute in links
        $tsfe->dtdAllowsFrames = false;
        if ($tsfe->config['config']['doctype']) {
            if (in_array(
                (string)$tsfe->config['config']['doctype'],
                ['xhtml_trans', 'xhtml_frames', 'xhtml_basic', 'xhtml_2', 'html5'],
                true)
            ) {
                $tsfe->dtdAllowsFrames = true;
            }
        } else {
            $tsfe->dtdAllowsFrames = true;
        }
        // Setting XHTML-doctype from doctype
        if (!$tsfe->config['config']['xhtmlDoctype']) {
            $tsfe->config['config']['xhtmlDoctype'] = $tsfe->config['config']['doctype'];
        }
        if ($tsfe->config['config']['xhtmlDoctype']) {
            $tsfe->xhtmlDoctype = $tsfe->config['config']['xhtmlDoctype'];
            // Checking XHTML-docytpe
            switch ((string)$tsfe->config['config']['xhtmlDoctype']) {
                case 'xhtml_trans':

                case 'xhtml_strict':

                case 'xhtml_frames':
                    $tsfe->xhtmlVersion = 100;
                    break;
                case 'xhtml_basic':
                    $tsfe->xhtmlVersion = 105;
                    break;
                case 'xhtml_11':

                case 'xhtml+rdfa_10':
                    $tsfe->xhtmlVersion = 110;
                    break;
                case 'xhtml_2':
                    GeneralUtility::deprecationLog('The option "config.xhtmlDoctype=xhtml_2" is deprecated since TYPO3 CMS 7, and will be removed with CMS 8');
                    $tsfe->xhtmlVersion = 200;
                    break;
                default:
                    static::getPageRenderer()->setRenderXhtml(false);
                    $tsfe->xhtmlDoctype = '';
                    $tsfe->xhtmlVersion = 0;
            }
        } else {
            static::getPageRenderer()->setRenderXhtml(false);
        }
    }

    /**
     * Processing JavaScript handlers
     *
     * @return array Array with a) a JavaScript section with event handlers and variables set and b) an array with attributes for the body tag.
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use JS directly
     */
    public static function JSeventFunctions()
    {
        $functions = [];
        $setEvents = [];
        $setBody = [];
        foreach ($GLOBALS['TSFE']->JSeventFuncCalls as $event => $handlers) {
            if (!empty($handlers)) {
                GeneralUtility::deprecationLog('The usage of $GLOBALS[\'TSFE\']->JSeventFuncCalls is deprecated as of TYPO3 CMS 7. Use Javascript directly.');
                $functions[] = '	function T3_' . $event . 'Wrapper(e) {	' . implode('   ', $handlers) . '	}';
                $setEvents[] = '	document.' . $event . '=T3_' . $event . 'Wrapper;';
                if ($event == 'onload') {
                    // Dubiuos double setting breaks on some browser - do we need it?
                    $setBody[] = 'onload="T3_onloadWrapper();"';
                }
            }
        }
        return [!empty($functions) ? implode(LF, $functions) . LF . implode(LF, $setEvents) : '', $setBody];
    }

    /**
     * Rendering the page content
     *
     * @return void
     */
    public static function renderContent()
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];

        /** @var TimeTracker $timeTracker */
        $timeTracker = $GLOBALS['TT'];

        // PAGE CONTENT
        $timeTracker->incStackPointer();
        $timeTracker->push($tsfe->sPre, 'PAGE');
        $pageContent = $tsfe->cObj->cObjGet($tsfe->pSetup);
        if ($tsfe->pSetup['wrap']) {
            $pageContent = $tsfe->cObj->wrap($pageContent, $tsfe->pSetup['wrap']);
        }
        if ($tsfe->pSetup['stdWrap.']) {
            $pageContent = $tsfe->cObj->stdWrap($pageContent, $tsfe->pSetup['stdWrap.']);
        }
        // PAGE HEADER (after content - maybe JS is inserted!
        // if 'disableAllHeaderCode' is set, all the header-code is discarded!
        if ($tsfe->config['config']['disableAllHeaderCode']) {
            $tsfe->content = $pageContent;
        } else {
            self::renderContentWithHeader($pageContent);
        }
        $timeTracker->pull($timeTracker->LR ? $tsfe->content : '');
        $timeTracker->decStackPointer();
    }

    /**
     * Rendering normal HTML-page with header by wrapping the generated content ($pageContent) in body-tags and setting the header accordingly.
     *
     * @param string $pageContent The page content which TypoScript objects has generated
     * @return void
     */
    public static function renderContentWithHeader($pageContent)
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];

        /** @var TimeTracker $timeTracker */
        $timeTracker = $GLOBALS['TT'];

        $pageRenderer = static::getPageRenderer();
        if ($tsfe->config['config']['moveJsFromHeaderToFooter']) {
            $pageRenderer->enableMoveJsFromHeaderToFooter();
        }
        if ($tsfe->config['config']['pageRendererTemplateFile']) {
            $file = $tsfe->tmpl->getFileName($tsfe->config['config']['pageRendererTemplateFile']);
            if ($file) {
                $pageRenderer->setTemplateFile($file);
            }
        }
        $headerComment = $tsfe->config['config']['headerComment'];
        if (trim($headerComment)) {
            $pageRenderer->addInlineComment(TAB . str_replace(LF, (LF . TAB), trim($headerComment)) . LF);
        }
        // Setting charset:
        $theCharset = $tsfe->metaCharset;
        // Reset the content variables:
        $tsfe->content = '';
        $htmlTagAttributes = [];
        $htmlLang = $tsfe->config['config']['htmlTag_langKey'] ?: ($tsfe->sys_language_isocode ?: 'en');
        // Set content direction: (More info: http://www.tau.ac.il/~danon/Hebrew/HTML_and_Hebrew.html)
        if ($tsfe->config['config']['htmlTag_dir']) {
            $htmlTagAttributes['dir'] = htmlspecialchars($tsfe->config['config']['htmlTag_dir']);
        }
        // Setting document type:
        $docTypeParts = [];
        $xmlDocument = true;
        // Part 1: XML prologue
        switch ((string)$tsfe->config['config']['xmlprologue']) {
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
                if ($tsfe->xhtmlVersion) {
                    $docTypeParts[] = '<?xml version="1.0" encoding="' . $theCharset . '"?>';
                } else {
                    $xmlDocument = false;
                }
                break;
            default:
                $docTypeParts[] = $tsfe->config['config']['xmlprologue'];
        }
        // Part 2: DTD
        $doctype = $tsfe->config['config']['doctype'];
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
                case 'xhtml_frames':
                    $docTypeParts[] = '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
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
                case 'xhtml_2':
                    $docTypeParts[] = '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 2.0//EN"
    "http://www.w3.org/TR/xhtml2/DTD/xhtml2.dtd">';
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
        if ($tsfe->xhtmlVersion) {
            $htmlTagAttributes['xml:lang'] = $htmlLang;
        }
        if ($tsfe->xhtmlVersion < 110 || $doctype === 'html5') {
            $htmlTagAttributes['lang'] = $htmlLang;
        }
        if ($tsfe->xhtmlVersion || $doctype === 'html5' && $xmlDocument) {
            // We add this to HTML5 to achieve a slightly better backwards compatibility
            $htmlTagAttributes['xmlns'] = 'http://www.w3.org/1999/xhtml';
            if (is_array($tsfe->config['config']['namespaces.'])) {
                foreach ($tsfe->config['config']['namespaces.'] as $prefix => $uri) {
                    // $uri gets htmlspecialchared later
                    $htmlTagAttributes['xmlns:' . htmlspecialchars($prefix)] = $uri;
                }
            }
        }
        // Swap XML and doctype order around (for MSIE / Opera standards compliance)
        if ($tsfe->config['config']['doctypeSwitch']) {
            $docTypeParts = array_reverse($docTypeParts);
        }
        // Adding doctype parts:
        if (!empty($docTypeParts)) {
            $pageRenderer->setXmlPrologAndDocType(implode(LF, $docTypeParts));
        }
        // Begin header section:
        if ($tsfe->config['config']['htmlTag_setParams'] !== 'none') {
            $_attr = $tsfe->config['config']['htmlTag_setParams'] ? $tsfe->config['config']['htmlTag_setParams'] : GeneralUtility::implodeAttributes($htmlTagAttributes);
        } else {
            $_attr = '';
        }
        $htmlTag = '<html' . ($_attr ? ' ' . $_attr : '') . '>';
        if (isset($tsfe->config['config']['htmlTag_stdWrap.'])) {
            $htmlTag = $tsfe->cObj->stdWrap($htmlTag, $tsfe->config['config']['htmlTag_stdWrap.']);
        }
        $pageRenderer->setHtmlTag($htmlTag);
        // Head tag:
        $headTag = $tsfe->pSetup['headTag'] ?: '<head>';
        if (isset($tsfe->pSetup['headTag.'])) {
            $headTag = $tsfe->cObj->stdWrap($headTag, $tsfe->pSetup['headTag.']);
        }
        $pageRenderer->setHeadTag($headTag);
        // Setting charset meta tag:
        $pageRenderer->setCharSet($theCharset);
        $pageRenderer->addInlineComment('	This website is powered by TYPO3 - inspiring people to share!
	TYPO3 is a free open source Content Management Framework initially created by Kasper Skaarhoj and licensed under GNU/GPL.
	TYPO3 is copyright ' . TYPO3_copyright_year . ' of Kasper Skaarhoj. Extensions are copyright of their respective owners.
	Information and contribution at ' . TYPO3_URL_GENERAL . '
');
        if ($tsfe->baseUrl) {
            $pageRenderer->setBaseUrl($tsfe->baseUrl);
        }
        if ($tsfe->pSetup['shortcutIcon']) {
            $favIcon = ltrim($tsfe->tmpl->getFileName($tsfe->pSetup['shortcutIcon']), '/');
            $iconFileInfo = GeneralUtility::makeInstance(ImageInfo::class, PATH_site . $favIcon);
            if ($iconFileInfo->isFile()) {
                $iconMimeType = $iconFileInfo->getMimeType();
                if ($iconMimeType) {
                    $iconMimeType = ' type="' . $iconMimeType . '"';
                    $pageRenderer->setIconMimeType($iconMimeType);
                }
                $pageRenderer->setFavIcon(PathUtility::getAbsoluteWebPath($tsfe->absRefPrefix . $favIcon));
            }
        }
        // Including CSS files
        if (is_array($tsfe->tmpl->setup['plugin.'])) {
            $stylesFromPlugins = '';
            foreach ($tsfe->tmpl->setup['plugin.'] as $key => $iCSScode) {
                if (is_array($iCSScode)) {
                    if ($iCSScode['_CSS_DEFAULT_STYLE'] && empty($tsfe->config['config']['removeDefaultCss'])) {
                        if (isset($iCSScode['_CSS_DEFAULT_STYLE.'])) {
                            $cssDefaultStyle = $tsfe->cObj->stdWrap($iCSScode['_CSS_DEFAULT_STYLE'], $iCSScode['_CSS_DEFAULT_STYLE.']);
                        } else {
                            $cssDefaultStyle = $iCSScode['_CSS_DEFAULT_STYLE'];
                        }
                        $stylesFromPlugins .= '/* default styles for extension "' . substr($key, 0, -1) . '" */' . LF . $cssDefaultStyle . LF;
                    }
                    if ($iCSScode['_CSS_PAGE_STYLE'] && empty($tsfe->config['config']['removePageCss'])) {
                        $cssPageStyle = implode(LF, $iCSScode['_CSS_PAGE_STYLE']);
                        if (isset($iCSScode['_CSS_PAGE_STYLE.'])) {
                            $cssPageStyle = $tsfe->cObj->stdWrap($cssPageStyle, $iCSScode['_CSS_PAGE_STYLE.']);
                        }
                        $cssPageStyle = '/* specific page styles for extension "' . substr($key, 0, -1) . '" */' . LF . $cssPageStyle;
                        self::addCssToPageRenderer($cssPageStyle, true, 'InlinePageCss');
                    }
                }
            }
            if (!empty($stylesFromPlugins)) {
                self::addCssToPageRenderer($stylesFromPlugins, false, 'InlineDefaultCss');
            }
        }
        if ($tsfe->pSetup['stylesheet']) {
            $ss = $tsfe->tmpl->getFileName($tsfe->pSetup['stylesheet']);
            if ($ss) {
                $pageRenderer->addCssFile($ss);
            }
        }
        /**********************************************************************/
        /* config.includeCSS / config.includeCSSLibs
        /**********************************************************************/
        if (is_array($tsfe->pSetup['includeCSS.'])) {
            foreach ($tsfe->pSetup['includeCSS.'] as $key => $CSSfile) {
                if (!is_array($CSSfile)) {
                    $cssFileConfig = &$tsfe->pSetup['includeCSS.'][$key . '.'];
                    if (isset($cssFileConfig['if.']) && !$tsfe->cObj->checkIf($cssFileConfig['if.'])) {
                        continue;
                    }
                    $ss = $cssFileConfig['external'] ? $CSSfile : $tsfe->tmpl->getFileName($CSSfile);
                    if ($ss) {
                        if ($cssFileConfig['import']) {
                            if (!$cssFileConfig['external'] && $ss[0] !== '/') {
                                // To fix MSIE 6 that cannot handle these as relative paths (according to Ben v Ende)
                                $ss = GeneralUtility::dirname(GeneralUtility::getIndpEnv('SCRIPT_NAME')) . '/' . $ss;
                            }
                            $pageRenderer->addCssInlineBlock('import_' . $key, '@import url("' . htmlspecialchars($ss) . '") ' . htmlspecialchars($cssFileConfig['media']) . ';', empty($cssFileConfig['disableCompression']), (bool)$cssFileConfig['forceOnTop'], '');
                        } else {
                            $pageRenderer->addCssFile(
                                $ss,
                                $cssFileConfig['alternate'] ? 'alternate stylesheet' : 'stylesheet',
                                $cssFileConfig['media'] ?: 'all',
                                $cssFileConfig['title'] ?: '',
                                empty($cssFileConfig['disableCompression']),
                                (bool)$cssFileConfig['forceOnTop'],
                                $cssFileConfig['allWrap'],
                                (bool)$cssFileConfig['excludeFromConcatenation'],
                                $cssFileConfig['allWrap.']['splitChar']
                            );
                            unset($cssFileConfig);
                        }
                    }
                }
            }
        }
        if (is_array($tsfe->pSetup['includeCSSLibs.'])) {
            foreach ($tsfe->pSetup['includeCSSLibs.'] as $key => $CSSfile) {
                if (!is_array($CSSfile)) {
                    $cssFileConfig = &$tsfe->pSetup['includeCSSLibs.'][$key . '.'];
                    if (isset($cssFileConfig['if.']) && !$tsfe->cObj->checkIf($cssFileConfig['if.'])) {
                        continue;
                    }
                    $ss = $cssFileConfig['external'] ? $CSSfile : $tsfe->tmpl->getFileName($CSSfile);
                    if ($ss) {
                        if ($cssFileConfig['import']) {
                            if (!$cssFileConfig['external'] && $ss[0] !== '/') {
                                // To fix MSIE 6 that cannot handle these as relative paths (according to Ben v Ende)
                                $ss = GeneralUtility::dirname(GeneralUtility::getIndpEnv('SCRIPT_NAME')) . '/' . $ss;
                            }
                            $pageRenderer->addCssInlineBlock('import_' . $key, '@import url("' . htmlspecialchars($ss) . '") ' . htmlspecialchars($cssFileConfig['media']) . ';', empty($cssFileConfig['disableCompression']), (bool)$cssFileConfig['forceOnTop'], '');
                        } else {
                            $pageRenderer->addCssLibrary(
                                $ss,
                                $cssFileConfig['alternate'] ? 'alternate stylesheet' : 'stylesheet',
                                $cssFileConfig['media'] ?: 'all',
                                $cssFileConfig['title'] ?: '',
                                empty($cssFileConfig['disableCompression']),
                                (bool)$cssFileConfig['forceOnTop'],
                                $cssFileConfig['allWrap'],
                                (bool)$cssFileConfig['excludeFromConcatenation'],
                                $cssFileConfig['allWrap.']['splitChar']
                            );
                            unset($cssFileConfig);
                        }
                    }
                }
            }
        }

        // Stylesheets
        $style = '';
        if ($tsfe->pSetup['insertClassesFromRTE']) {
            $pageTSConfig = $tsfe->getPagesTSconfig();
            $RTEclasses = $pageTSConfig['RTE.']['classes.'];
            if (is_array($RTEclasses)) {
                foreach ($RTEclasses as $RTEclassName => $RTEvalueArray) {
                    if ($RTEvalueArray['value']) {
                        $style .= '
.' . substr($RTEclassName, 0, -1) . ' {' . $RTEvalueArray['value'] . '}';
                    }
                }
            }
            if ($tsfe->pSetup['insertClassesFromRTE.']['add_mainStyleOverrideDefs'] && is_array($pageTSConfig['RTE.']['default.']['mainStyleOverride_add.'])) {
                $mSOa_tList = GeneralUtility::trimExplode(',', strtoupper($tsfe->pSetup['insertClassesFromRTE.']['add_mainStyleOverrideDefs']), true);
                foreach ($pageTSConfig['RTE.']['default.']['mainStyleOverride_add.'] as $mSOa_key => $mSOa_value) {
                    if (!is_array($mSOa_value) && (in_array('*', $mSOa_tList) || in_array($mSOa_key, $mSOa_tList))) {
                        $style .= '
' . $mSOa_key . ' {' . $mSOa_value . '}';
                    }
                }
            }
        }
        // Setting body tag margins in CSS:
        if (isset($tsfe->pSetup['bodyTagMargins']) && $tsfe->pSetup['bodyTagMargins.']['useCSS']) {
            $margins = (int)$tsfe->pSetup['bodyTagMargins'];
            $style .= '
	BODY {margin: ' . $margins . 'px ' . $margins . 'px ' . $margins . 'px ' . $margins . 'px;}';
        }
        // CSS_inlineStyle from TS
        $style .= trim($tsfe->pSetup['CSS_inlineStyle']);
        $style .= $tsfe->cObj->cObjGet($tsfe->pSetup['cssInline.'], 'cssInline.');
        if (trim($style)) {
            self::addCssToPageRenderer($style, true, 'additionalTSFEInlineStyle');
        }
        // Javascript Libraries
        if (is_array($tsfe->pSetup['javascriptLibs.'])) {
            // Include jQuery into the page renderer
            if (!empty($tsfe->pSetup['javascriptLibs.']['jQuery'])) {
                $jQueryTS = $tsfe->pSetup['javascriptLibs.']['jQuery.'];
                // Check if version / source is set, if not set variable to "NULL" to use the default of the page renderer
                $version = isset($jQueryTS['version']) ? $jQueryTS['version'] : null;
                $source = isset($jQueryTS['source']) ? $jQueryTS['source'] : null;
                // When "noConflict" is not set or "1" enable the default jQuery noConflict mode, otherwise disable the namespace
                if (!isset($jQueryTS['noConflict']) || !empty($jQueryTS['noConflict'])) {
                    // Set namespace to the "noConflict.namespace" value if "noConflict.namespace" has a value
                    if (!empty($jQueryTS['noConflict.']['namespace'])) {
                        $namespace = $jQueryTS['noConflict.']['namespace'];
                    } else {
                        $namespace = PageRenderer::JQUERY_NAMESPACE_DEFAULT_NOCONFLICT;
                    }
                } else {
                    $namespace = PageRenderer::JQUERY_NAMESPACE_NONE;
                }
                $pageRenderer->loadJQuery($version, $source, $namespace);
            }
            if ($tsfe->pSetup['javascriptLibs.']['ExtJs']) {
                $css = (bool)$tsfe->pSetup['javascriptLibs.']['ExtJs.']['css'];
                $theme = (bool)$tsfe->pSetup['javascriptLibs.']['ExtJs.']['theme'];
                $pageRenderer->loadExtJs($css, $theme);
                if ($tsfe->pSetup['javascriptLibs.']['ExtJs.']['debug']) {
                    $pageRenderer->enableExtJsDebug();
                }
            }
        }
        // JavaScript library files
        if (is_array($tsfe->pSetup['includeJSlibs.']) || is_array($tsfe->pSetup['includeJSLibs.'])) {
            if (!is_array($tsfe->pSetup['includeJSlibs.'])) {
                $tsfe->pSetup['includeJSlibs.'] = [];
            } else {
                GeneralUtility::deprecationLog('The property page.includeJSlibs is marked for deprecation and will be removed in TYPO3 CMS 8. Please use page.includeJSLibs (with an uppercase L) instead.');
            }
            if (!is_array($tsfe->pSetup['includeJSLibs.'])) {
                $tsfe->pSetup['includeJSLibs.'] = [];
            }
            ArrayUtility::mergeRecursiveWithOverrule(
                $tsfe->pSetup['includeJSLibs.'],
                $tsfe->pSetup['includeJSlibs.']
            );
            unset($tsfe->pSetup['includeJSlibs.']);
            foreach ($tsfe->pSetup['includeJSLibs.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($tsfe->pSetup['includeJSLibs.'][$key . '.']['if.']) && !$tsfe->cObj->checkIf($tsfe->pSetup['includeJSLibs.'][$key . '.']['if.'])) {
                        continue;
                    }
                    $ss = $tsfe->pSetup['includeJSLibs.'][$key . '.']['external'] ? $JSfile : $tsfe->tmpl->getFileName($JSfile);
                    if ($ss) {
                        $jsFileConfig = &$tsfe->pSetup['includeJSLibs.'][$key . '.'];
                        $type = $jsFileConfig['type'];
                        if (!$type) {
                            $type = 'text/javascript';
                        }

                        $pageRenderer->addJsLibrary(
                            $key,
                            $ss,
                            $type,
                            empty($jsFileConfig['disableCompression']),
                            (bool)$jsFileConfig['forceOnTop'],
                            $jsFileConfig['allWrap'],
                            (bool)$jsFileConfig['excludeFromConcatenation'],
                            $jsFileConfig['allWrap.']['splitChar'],
                            (bool)$jsFileConfig['async'],
                            $jsFileConfig['integrity']
                        );
                        unset($jsFileConfig);
                    }
                }
            }
        }
        if (is_array($tsfe->pSetup['includeJSFooterlibs.'])) {
            foreach ($tsfe->pSetup['includeJSFooterlibs.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($tsfe->pSetup['includeJSFooterlibs.'][$key . '.']['if.']) && !$tsfe->cObj->checkIf($tsfe->pSetup['includeJSFooterlibs.'][$key . '.']['if.'])) {
                        continue;
                    }
                    $ss = $tsfe->pSetup['includeJSFooterlibs.'][$key . '.']['external'] ? $JSfile : $tsfe->tmpl->getFileName($JSfile);
                    if ($ss) {
                        $jsFileConfig = &$tsfe->pSetup['includeJSFooterlibs.'][$key . '.'];
                        $type = $jsFileConfig['type'];
                        if (!$type) {
                            $type = 'text/javascript';
                        }
                        $pageRenderer->addJsFooterLibrary(
                            $key,
                            $ss,
                            $type,
                            empty($jsFileConfig['disableCompression']),
                            (bool)$jsFileConfig['forceOnTop'],
                            $jsFileConfig['allWrap'],
                            (bool)$jsFileConfig['excludeFromConcatenation'],
                            $jsFileConfig['allWrap.']['splitChar'],
                            (bool)$jsFileConfig['async'],
                            $jsFileConfig['integrity']
                        );
                        unset($jsFileConfig);
                    }
                }
            }
        }
        // JavaScript files
        if (is_array($tsfe->pSetup['includeJS.'])) {
            foreach ($tsfe->pSetup['includeJS.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($tsfe->pSetup['includeJS.'][$key . '.']['if.']) && !$tsfe->cObj->checkIf($tsfe->pSetup['includeJS.'][$key . '.']['if.'])) {
                        continue;
                    }
                    $ss = $tsfe->pSetup['includeJS.'][$key . '.']['external'] ? $JSfile : $tsfe->tmpl->getFileName($JSfile);
                    if ($ss) {
                        $jsConfig = &$tsfe->pSetup['includeJS.'][$key . '.'];
                        $type = $jsConfig['type'];
                        if (!$type) {
                            $type = 'text/javascript';
                        }
                        $pageRenderer->addJsFile(
                            $ss,
                            $type,
                            empty($jsConfig['disableCompression']),
                            (bool)$jsConfig['forceOnTop'],
                            $jsConfig['allWrap'],
                            (bool)$jsConfig['excludeFromConcatenation'],
                            $jsConfig['allWrap.']['splitChar'],
                            (bool)$jsConfig['async'],
                            $jsConfig['integrity']
                        );
                        unset($jsConfig);
                    }
                }
            }
        }
        if (is_array($tsfe->pSetup['includeJSFooter.'])) {
            foreach ($tsfe->pSetup['includeJSFooter.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($tsfe->pSetup['includeJSFooter.'][$key . '.']['if.']) && !$tsfe->cObj->checkIf($tsfe->pSetup['includeJSFooter.'][$key . '.']['if.'])) {
                        continue;
                    }
                    $ss = $tsfe->pSetup['includeJSFooter.'][$key . '.']['external'] ? $JSfile : $tsfe->tmpl->getFileName($JSfile);
                    if ($ss) {
                        $jsConfig = &$tsfe->pSetup['includeJSFooter.'][$key . '.'];
                        $type = $jsConfig['type'];
                        if (!$type) {
                            $type = 'text/javascript';
                        }
                        $pageRenderer->addJsFooterFile(
                            $ss,
                            $type,
                            empty($jsConfig['disableCompression']),
                            (bool)$jsConfig['forceOnTop'],
                            $jsConfig['allWrap'],
                            (bool)$jsConfig['excludeFromConcatenation'],
                            $jsConfig['allWrap.']['splitChar'],
                            (bool)$jsConfig['async'],
                            $jsConfig['integrity']
                        );
                        unset($jsConfig);
                    }
                }
            }
        }
        // Headerdata
        if (is_array($tsfe->pSetup['headerData.'])) {
            $pageRenderer->addHeaderData($tsfe->cObj->cObjGet($tsfe->pSetup['headerData.'], 'headerData.'));
        }
        // Footerdata
        if (is_array($tsfe->pSetup['footerData.'])) {
            $pageRenderer->addFooterData($tsfe->cObj->cObjGet($tsfe->pSetup['footerData.'], 'footerData.'));
        }
        static::generatePageTitle();

        $metaTagsHtml = static::generateMetaTagHtml(
            isset($tsfe->pSetup['meta.']) ? $tsfe->pSetup['meta.'] : [],
            $tsfe->xhtmlVersion,
            $tsfe->cObj
        );
        foreach ($metaTagsHtml as $metaTag) {
            $pageRenderer->addMetaTag($metaTag);
        }

        unset($tsfe->additionalHeaderData['JSCode']);
        if (is_array($tsfe->config['INTincScript'])) {
            $tsfe->additionalHeaderData['JSCode'] = $tsfe->JSCode;
            // Storing the JSCode vars...
            $tsfe->config['INTincScript_ext']['divKey'] = $tsfe->uniqueHash();
            $tsfe->config['INTincScript_ext']['additionalHeaderData'] = $tsfe->additionalHeaderData;
            // Storing the header-data array
            $tsfe->config['INTincScript_ext']['additionalFooterData'] = $tsfe->additionalFooterData;
            // Storing the footer-data array
            $tsfe->config['INTincScript_ext']['additionalJavaScript'] = $tsfe->additionalJavaScript;
            // Storing the JS-data array
            $tsfe->config['INTincScript_ext']['additionalCSS'] = $tsfe->additionalCSS;
            // Storing the Style-data array
            $tsfe->additionalHeaderData = ['<!--HD_' . $tsfe->config['INTincScript_ext']['divKey'] . '-->'];
            // Clearing the array
            $tsfe->additionalFooterData = ['<!--FD_' . $tsfe->config['INTincScript_ext']['divKey'] . '-->'];
            // Clearing the array
            $tsfe->divSection .= '<!--TDS_' . $tsfe->config['INTincScript_ext']['divKey'] . '-->';
        } else {
            $tsfe->INTincScript_loadJSCode();
        }
        $JSef = self::JSeventFunctions();
        $scriptJsCode = $JSef[0];

        if ($tsfe->spamProtectEmailAddresses && $tsfe->spamProtectEmailAddresses !== 'ascii') {
            $scriptJsCode = '
			// decrypt helper function
		function decryptCharcode(n,start,end,offset) {
			n = n + offset;
			if (offset > 0 && n > end) {
				n = start + (n - end - 1);
			} else if (offset < 0 && n < start) {
				n = end - (start - n - 1);
			}
			return String.fromCharCode(n);
		}
			// decrypt string
		function decryptString(enc,offset) {
			var dec = "";
			var len = enc.length;
			for(var i=0; i < len; i++) {
				var n = enc.charCodeAt(i);
				if (n >= 0x2B && n <= 0x3A) {
					dec += decryptCharcode(n,0x2B,0x3A,offset);	// 0-9 . , - + / :
				} else if (n >= 0x40 && n <= 0x5A) {
					dec += decryptCharcode(n,0x40,0x5A,offset);	// A-Z @
				} else if (n >= 0x61 && n <= 0x7A) {
					dec += decryptCharcode(n,0x61,0x7A,offset);	// a-z
				} else {
					dec += enc.charAt(i);
				}
			}
			return dec;
		}
			// decrypt spam-protected emails
		function linkTo_UnCryptMailto(s) {
			location.href = decryptString(s,' . $tsfe->spamProtectEmailAddresses * -1 . ');
		}
		';
        }
        // Add inline JS
        $inlineJS = '';
        // defined in php
        if (is_array($tsfe->inlineJS)) {
            foreach ($tsfe->inlineJS as $key => $val) {
                if (!is_array($val)) {
                    $inlineJS .= LF . $val . LF;
                }
            }
        }
        // defined in TS with page.inlineJS
        // Javascript inline code
        $inline = $tsfe->cObj->cObjGet($tsfe->pSetup['jsInline.'], 'jsInline.');
        if ($inline) {
            $inlineJS .= LF . $inline . LF;
        }
        // Javascript inline code for Footer
        $inlineFooterJs = $tsfe->cObj->cObjGet($tsfe->pSetup['jsFooterInline.'], 'jsFooterInline.');
        // Should minify?
        if ($tsfe->config['config']['compressJs']) {
            $pageRenderer->enableCompressJavascript();
            $minifyErrorScript = ($minifyErrorInline = '');
            $scriptJsCode = GeneralUtility::minifyJavaScript($scriptJsCode, $minifyErrorScript);
            if ($minifyErrorScript) {
                $timeTracker->setTSlogMessage($minifyErrorScript, 3);
            }
            if ($inlineJS) {
                $inlineJS = GeneralUtility::minifyJavaScript($inlineJS, $minifyErrorInline);
                if ($minifyErrorInline) {
                    $timeTracker->setTSlogMessage($minifyErrorInline, 3);
                }
            }
            if ($inlineFooterJs) {
                $inlineFooterJs = GeneralUtility::minifyJavaScript($inlineFooterJs, $minifyErrorInline);
                if ($minifyErrorInline) {
                    $timeTracker->setTSlogMessage($minifyErrorInline, 3);
                }
            }
        }
        if (!$tsfe->config['config']['removeDefaultJS']) {
            // inlude default and inlineJS
            if ($scriptJsCode) {
                $pageRenderer->addJsInlineCode('_scriptCode', $scriptJsCode, $tsfe->config['config']['compressJs']);
            }
            if ($inlineJS) {
                $pageRenderer->addJsInlineCode('TS_inlineJS', $inlineJS, $tsfe->config['config']['compressJs']);
            }
            if ($inlineFooterJs) {
                $pageRenderer->addJsFooterInlineCode('TS_inlineFooter', $inlineFooterJs, $tsfe->config['config']['compressJs']);
            }
        } elseif ($tsfe->config['config']['removeDefaultJS'] === 'external') {
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
            self::stripIntObjectPlaceholder($inlineJS, $inlineJSint);
            if ($inlineJSint) {
                $pageRenderer->addJsInlineCode('TS_inlineJSint', $inlineJSint, $tsfe->config['config']['compressJs']);
            }
            if (trim($scriptJsCode . $inlineJS)) {
                $pageRenderer->addJsFile(self::inline2TempFile($scriptJsCode . $inlineJS, 'js'), 'text/javascript', $tsfe->config['config']['compressJs']);
            }
            if ($inlineFooterJs) {
                $inlineFooterJSint = '';
                self::stripIntObjectPlaceholder($inlineFooterJs, $inlineFooterJSint);
                if ($inlineFooterJSint) {
                    $pageRenderer->addJsFooterInlineCode('TS_inlineFooterJSint', $inlineFooterJSint, $tsfe->config['config']['compressJs']);
                }
                $pageRenderer->addJsFooterFile(self::inline2TempFile($inlineFooterJs, 'js'), 'text/javascript', $tsfe->config['config']['compressJs']);
            }
        } else {
            // Include only inlineJS
            if ($inlineJS) {
                $pageRenderer->addJsInlineCode('TS_inlineJS', $inlineJS, $tsfe->config['config']['compressJs']);
            }
            if ($inlineFooterJs) {
                $pageRenderer->addJsFooterInlineCode('TS_inlineFooter', $inlineFooterJs, $tsfe->config['config']['compressJs']);
            }
        }
        if (is_array($tsfe->pSetup['inlineLanguageLabelFiles.'])) {
            foreach ($tsfe->pSetup['inlineLanguageLabelFiles.'] as $key => $languageFile) {
                if (is_array($languageFile)) {
                    continue;
                }
                $languageFileConfig = &$tsfe->pSetup['inlineLanguageLabelFiles.'][$key . '.'];
                if (isset($languageFileConfig['if.']) && !$tsfe->cObj->checkIf($languageFileConfig['if.'])) {
                    continue;
                }
                $pageRenderer->addInlineLanguageLabelFile(
                    $languageFile,
                    $languageFileConfig['selectionPrefix'] ?: '',
                    $languageFileConfig['stripFromSelectionName'] ?: '',
                    $languageFileConfig['errorMode'] ? (int)$languageFileConfig['errorMode'] : 0
                );
            }
        }
        // ExtJS specific code
        if (is_array($tsfe->pSetup['inlineLanguageLabel.'])) {
            $pageRenderer->addInlineLanguageLabelArray($tsfe->pSetup['inlineLanguageLabel.'], true);
        }
        if (is_array($tsfe->pSetup['inlineSettings.'])) {
            $pageRenderer->addInlineSettingArray('TS', $tsfe->pSetup['inlineSettings.']);
        }
        if (is_array($tsfe->pSetup['extOnReady.'])) {
            $pageRenderer->addExtOnReadyCode($tsfe->cObj->cObjGet($tsfe->pSetup['extOnReady.'], 'extOnReady.'));
        }
        // Compression and concatenate settings
        if ($tsfe->config['config']['compressCss']) {
            $pageRenderer->enableCompressCss();
        }
        if ($tsfe->config['config']['compressJs']) {
            $pageRenderer->enableCompressJavascript();
        }
        if ($tsfe->config['config']['concatenateCss']) {
            $pageRenderer->enableConcatenateCss();
        }
        if ($tsfe->config['config']['concatenateJs']) {
            $pageRenderer->enableConcatenateJavascript();
        }
        // Backward compatibility for old configuration
        if ($tsfe->config['config']['concatenateJsAndCss']) {
            $pageRenderer->enableConcatenateFiles();
        }
        // Add header data block
        if ($tsfe->additionalHeaderData) {
            $pageRenderer->addHeaderData(implode(LF, $tsfe->additionalHeaderData));
        }
        // Add footer data block
        if ($tsfe->additionalFooterData) {
            $pageRenderer->addFooterData(implode(LF, $tsfe->additionalFooterData));
        }
        // Header complete, now add content
        if ($tsfe->pSetup['frameSet.']) {
            $fs = GeneralUtility::makeInstance(FramesetRenderer::class);
            $pageRenderer->addBodyContent($fs->make($tsfe->pSetup['frameSet.']));
            $pageRenderer->addBodyContent(LF . '<noframes>' . LF);
        }
        // Bodytag:
        if ($tsfe->config['config']['disableBodyTag']) {
            $bodyTag = '';
        } else {
            $defBT = $tsfe->pSetup['bodyTagCObject'] ? $tsfe->cObj->cObjGetSingle($tsfe->pSetup['bodyTagCObject'], $tsfe->pSetup['bodyTagCObject.'], 'bodyTagCObject') : '';
            if (!$defBT) {
                $defBT = $tsfe->defaultBodyTag;
            }
            $bodyTag = $tsfe->pSetup['bodyTag'] ? $tsfe->pSetup['bodyTag'] : $defBT;
            if ($bgImg = $tsfe->cObj->getImgResource($tsfe->pSetup['bgImg'], $tsfe->pSetup['bgImg.'])) {
                GeneralUtility::deprecationLog('The option "page.bgImg" is deprecated since TYPO3 CMS 7, and will be removed with CMS 8');
                $bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' background="' . $tsfe->absRefPrefix . $bgImg[3] . '">';
            }
            if (isset($tsfe->pSetup['bodyTagMargins'])) {
                $margins = (int)$tsfe->pSetup['bodyTagMargins'];
                if ($tsfe->pSetup['bodyTagMargins.']['useCSS']) {
                } else {
                    $bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' leftmargin="' . $margins . '" topmargin="' . $margins . '" marginwidth="' . $margins . '" marginheight="' . $margins . '">';
                }
            }
            if (trim($tsfe->pSetup['bodyTagAdd'])) {
                $bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' ' . trim($tsfe->pSetup['bodyTagAdd']) . '>';
            }
            // Event functions
            if (!empty($JSef[1])) {
                $bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' ' . trim(implode(' ', $JSef[1])) . '>';
            }
        }
        $pageRenderer->addBodyContent(LF . $bodyTag);
        // Div-sections
        if ($tsfe->divSection) {
            $pageRenderer->addBodyContent(LF . $tsfe->divSection);
        }
        // Page content
        $pageRenderer->addBodyContent(LF . $pageContent);
        if (!empty($tsfe->config['INTincScript']) && is_array($tsfe->config['INTincScript'])) {
            // Store the serialized pageRenderer in configuration
            $tsfe->config['INTincScript_ext']['pageRenderer'] = serialize($pageRenderer);
            // Render complete page, keep placeholders for JavaScript and CSS
            $tsfe->content = $pageRenderer->renderPageWithUncachedObjects($tsfe->config['INTincScript_ext']['divKey']);
        } else {
            // Render complete page
            $tsfe->content = $pageRenderer->render();
        }
        // Ending page
        if ($tsfe->pSetup['frameSet.']) {
            $tsfe->content .= LF . '</noframes>';
        }
    }

    /*************************
     *
     * Helper functions
     * Remember: Calls internally must still be done on the non-instantiated class: PageGenerator::inline2TempFile()
     *
     *************************/
    /**
     * Searches for placeholder created from *_INT cObjects, removes them from
     * $searchString and merges them to $intObjects
     *
     * @param string $searchString The String which should be cleaned from int-object markers
     * @param string $intObjects The String the found int-placeholders are moved to (for further processing)
     */
    protected static function stripIntObjectPlaceholder(&$searchString, &$intObjects)
    {
        $tempArray = [];
        preg_match_all('/\\<\\!--INT_SCRIPT.[a-z0-9]*--\\>/', $searchString, $tempArray);
        $searchString = preg_replace('/\\<\\!--INT_SCRIPT.[a-z0-9]*--\\>/', '', $searchString);
        $intObjects = implode('', $tempArray[0]);
    }

    /**
     * Writes string to a temporary file named after the md5-hash of the string
     *
     * @param string $str CSS styles / JavaScript to write to file.
     * @param string $ext Extension: "css" or "js
     * @return string <script> or <link> tag for the file.
     */
    public static function inline2TempFile($str, $ext)
    {
        // Create filename / tags:
        $script = '';
        switch ($ext) {
            case 'js':
                $script = 'typo3temp/Assets/' . substr(md5($str), 0, 10) . '.js';
                break;
            case 'css':
                $script = 'typo3temp/Assets/' . substr(md5($str), 0, 10) . '.css';
                break;
        }
        // Write file:
        if ($script) {
            if (!@is_file(PATH_site . $script)) {
                GeneralUtility::writeFileToTypo3tempDir(PATH_site . $script, $str);
            }
        }
        return $script;
    }

    /**
     * Checks if the value defined in "config.linkVars" contains an allowed value. Otherwise, return FALSE which means the value will not be added to any links.
     *
     * @param string $haystack The string in which to find $needle
     * @param string $needle The string to find in $haystack
     * @return bool Returns TRUE if $needle matches or is found in $haystack
     */
    public static function isAllowedLinkVarValue($haystack, $needle)
    {
        $OK = false;
        // Integer
        if ($needle == 'int' || $needle == 'integer') {
            if (MathUtility::canBeInterpretedAsInteger($haystack)) {
                $OK = true;
            }
        } elseif (preg_match('/^\\/.+\\/[imsxeADSUXu]*$/', $needle)) {
            // Regular expression, only "//" is allowed as delimiter
            if (@preg_match($needle, $haystack)) {
                $OK = true;
            }
        } elseif (strstr($needle, '-')) {
            // Range
            if (MathUtility::canBeInterpretedAsInteger($haystack)) {
                $range = explode('-', $needle);
                if ($range[0] <= $haystack && $range[1] >= $haystack) {
                    $OK = true;
                }
            }
        } elseif (strstr($needle, '|')) {
            // List
            // Trim the input
            $haystack = str_replace(' ', '', $haystack);
            if (strstr('|' . $needle . '|', '|' . $haystack . '|')) {
                $OK = true;
            }
        } elseif ((string)$needle === (string)$haystack) {
            // String comparison
            $OK = true;
        }
        return $OK;
    }

    /**
     * Generate title for page.
     * Takes the settings [config][noPageTitle], [config][pageTitleFirst], [config][titleTagFunction]
     * [config][pageTitleSeparator] and [config][noPageTitle] into account.
     * Furthermore $GLOBALS[TSFE]->altPageTitle is observed.
     *
     * @return void
     */
    public static function generatePageTitle()
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];

        $pageTitleSeparator = '';

        // check for a custom pageTitleSeparator, and perform stdWrap on it
        if (isset($tsfe->config['config']['pageTitleSeparator']) && $tsfe->config['config']['pageTitleSeparator'] !== '') {
            $pageTitleSeparator = $tsfe->config['config']['pageTitleSeparator'];

            if (isset($tsfe->config['config']['pageTitleSeparator.']) && is_array($tsfe->config['config']['pageTitleSeparator.'])) {
                $pageTitleSeparator = $tsfe->cObj->stdWrap($pageTitleSeparator, $tsfe->config['config']['pageTitleSeparator.']);
            } else {
                $pageTitleSeparator .= ' ';
            }
        }

        $titleTagContent = $tsfe->tmpl->printTitle(
            $tsfe->altPageTitle ?: $tsfe->page['title'],
            $tsfe->config['config']['noPageTitle'],
            $tsfe->config['config']['pageTitleFirst'],
            $pageTitleSeparator
        );
        if ($tsfe->config['config']['titleTagFunction']) {
            $titleTagContent = $tsfe->cObj->callUserFunction(
                $tsfe->config['config']['titleTagFunction'],
                [],
                $titleTagContent
            );
        }
        // stdWrap around the title tag
        if (isset($tsfe->config['config']['pageTitle.']) && is_array($tsfe->config['config']['pageTitle.'])) {
            $titleTagContent = $tsfe->cObj->stdWrap($titleTagContent, $tsfe->config['config']['pageTitle.']);
        }
        if ($titleTagContent !== '' && (int)$tsfe->config['config']['noPageTitle'] !== self::NO_PAGE_TITLE) {
            static::getPageRenderer()->setTitle($titleTagContent);
        }
    }

    /**
     * Generate meta tags from meta tag TypoScript
     *
     * @param array $metaTagTypoScript TypoScript configuration for meta tags (e.g. $GLOBALS['TSFE']->pSetup['meta.'])
     * @param bool $xhtml Whether xhtml tag-style should be used. (e.g. pass $GLOBALS['TSFE']->xhtmlVersion here)
     * @param ContentObjectRenderer $cObj
     * @return array Array of HTML meta tags
     */
    protected static function generateMetaTagHtml(array $metaTagTypoScript, $xhtml, ContentObjectRenderer $cObj)
    {
        // Add ending slash only to documents rendered as xhtml
        $endingSlash = $xhtml ? ' /' : '';

        $metaTags = [
            '<meta name="generator" content="TYPO3 CMS"' . $endingSlash . '>'
        ];

        /** @var TypoScriptService $typoScriptService */
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $conf = $typoScriptService->convertTypoScriptArrayToPlainArray($metaTagTypoScript);
        foreach ($conf as $key => $properties) {
            if (is_array($properties)) {
                $nodeValue = isset($properties['_typoScriptNodeValue']) ? $properties['_typoScriptNodeValue'] : '';
                $value = trim($cObj->stdWrap($nodeValue, $metaTagTypoScript[$key . '.']));
                if ($value === '' && !empty($properties['value'])) {
                    $value = $properties['value'];
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

            if (!is_array($value)) {
                $value = (array)$value;
            }
            foreach ($value as $subValue) {
                if (trim($subValue) !== '') {
                    $metaTags[] = '<meta ' . $attribute . '="' . $key . '" content="' . htmlspecialchars($subValue) . '"' . $endingSlash . '>';
                }
            }
        }
        return $metaTags;
    }

    /**
     * Fills the sWordList property and builds the regular expression in TSFE that can be used to split
     * strings by the submitted search words.
     *
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::sWordList
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::sWordRegEx
     */
    protected static function initializeSearchWordDataInTsfe()
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];

        $tsfe->sWordRegEx = '';
        $tsfe->sWordList = GeneralUtility::_GP('sword_list');
        if (is_array($tsfe->sWordList)) {
            $space = !empty($tsfe->config['config']['sword_standAlone']) ? '[[:space:]]' : '';
            foreach ($tsfe->sWordList as $val) {
                if (trim($val) !== '') {
                    $tsfe->sWordRegEx .= $space . preg_quote($val, '/') . $space . '|';
                }
            }
            $tsfe->sWordRegEx = rtrim($tsfe->sWordRegEx, '|');
        }
    }

    /**
     * @return PageRenderer
     */
    protected static function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Adds inline CSS code, by respecting the inlineStyle2TempFile option
     *
     * @param string $cssStyles the inline CSS styling
     * @param bool $excludeFromConcatenation option to see if it should be conctatenated
     * @param string $inlineBlockName the block name to add it
     */
    protected static function addCssToPageRenderer($cssStyles, $excludeFromConcatenation = false, $inlineBlockName = 'TSFEinlineStyle')
    {
        if (empty($GLOBALS['TSFE']->config['config']['inlineStyle2TempFile'])) {
            self::getPageRenderer()->addCssInlineBlock($inlineBlockName, $cssStyles, !empty($GLOBALS['TSFE']->config['config']['compressCss']));
        } else {
            self::getPageRenderer()->addCssFile(
                self::inline2TempFile($cssStyles, 'css'),
                'stylesheet',
                'all',
                '',
                (bool)$GLOBALS['TSFE']->config['config']['compressCss'],
                false,
                '',
                $excludeFromConcatenation
            );
        }
    }
}
