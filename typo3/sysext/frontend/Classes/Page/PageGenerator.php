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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

/**
 * Class for starting TypoScript page generation
 *
 * The class is not instantiated as an objects but called directly with the "::" operator.
 * @deprecated this class will be removed in TYPO3 v10.0, as all functionality has been built in RequestHandler already.
 */
class PageGenerator
{
    /**
     * Do not render title tag
     * Typoscript setting: [config][noPageTitle]
     * @deprecated will not be used anymore, and will be removed in TYPO3 v10.0.
     */
    const NO_PAGE_TITLE = 2;

    /**
     * Rendering the page content
     * @deprecated since TYPO3 v9.4 will be removed in TYPO3 v10.0. This functionality is now within RequestHandler.
     */
    public static function renderContent()
    {
        trigger_error('PageGenerator::renderContent() will be removed in TYPO3 v10.0. This logic is now built in TYPO3s Frontend RequestHandler.', E_USER_DEPRECATED);
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];

        /** @var TimeTracker $timeTracker */
        $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);

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
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. This functionality is now within TYPO3's Frontend Request Handler.
     */
    public static function renderContentWithHeader($pageContent)
    {
        trigger_error('PageGenerator::renderContentWithHeader() will be removed in TYPO3 v10.0. This logic is now built in TYPO3s Frontend RequestHandler.', E_USER_DEPRECATED);
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];

        /** @var TimeTracker $timeTracker */
        $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);

        $pageRenderer = static::getPageRenderer();
        if ($tsfe->config['config']['moveJsFromHeaderToFooter'] ?? false) {
            $pageRenderer->enableMoveJsFromHeaderToFooter();
        }
        if ($tsfe->config['config']['pageRendererTemplateFile'] ?? false) {
            try {
                $file = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($tsfe->config['config']['pageRendererTemplateFile']);
                $pageRenderer->setTemplateFile($file);
            } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                // do nothing
            }
        }
        $headerComment = $tsfe->config['config']['headerComment'] ?? null;
        if (trim($headerComment)) {
            $pageRenderer->addInlineComment("\t" . str_replace(LF, LF . "\t", trim($headerComment)) . LF);
        }
        // Setting charset:
        $theCharset = $tsfe->metaCharset;
        // Reset the content variables:
        $tsfe->content = '';
        $htmlTagAttributes = [];
        $htmlLang = $tsfe->config['config']['htmlTag_langKey'] ?? ($tsfe->sys_language_isocode ?: 'en');
        // Set content direction
        // More info: http://www.tau.ac.il/~danon/Hebrew/HTML_and_Hebrew.html)
        $direction = $tsfe->config['config']['htmlTag_dir'] ?? null;
        if (self::getCurrentSiteLanguage()) {
            $direction = self::getCurrentSiteLanguage()->getDirection();
            $htmlLang = self::getCurrentSiteLanguage()->getTwoLetterIsoCode();
        }
        if ($direction) {
            $htmlTagAttributes['dir'] = htmlspecialchars($direction);
        }
        // Setting document type:
        $docTypeParts = [];
        $xmlDocument = true;
        // Part 1: XML prologue
        switch ((string)($tsfe->config['config']['xmlprologue'] ?? '')) {
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
        $doctype = $tsfe->config['config']['doctype'] ?? null;
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
        if ($tsfe->config['config']['doctypeSwitch'] ?? false) {
            $docTypeParts = array_reverse($docTypeParts);
        }
        // Adding doctype parts:
        if (!empty($docTypeParts)) {
            $pageRenderer->setXmlPrologAndDocType(implode(LF, $docTypeParts));
        }
        // Begin header section:
        if (is_array($tsfe->config['config']['htmlTag.']['attributes.'] ?? null)) {
            $_attr = '';
            foreach ($tsfe->config['config']['htmlTag.']['attributes.'] as $attributeName => $value) {
                $_attr .= ' ' . htmlspecialchars($attributeName) . ($value !== '' ? '="' . htmlspecialchars((string)$value) . '"' : '');
                // If e.g. "htmlTag.attributes.dir" is set, make sure it is not added again with "implodeAttributes()"
                if (isset($htmlTagAttributes[$attributeName])) {
                    unset($htmlTagAttributes[$attributeName]);
                }
            }
            $_attr = GeneralUtility::implodeAttributes($htmlTagAttributes) . $_attr;
        } elseif (($tsfe->config['config']['htmlTag_setParams'] ?? '') === 'none') {
            $_attr = '';
        } elseif (isset($tsfe->config['config']['htmlTag_setParams'])) {
            $_attr = $tsfe->config['config']['htmlTag_setParams'];
        } else {
            $_attr = GeneralUtility::implodeAttributes($htmlTagAttributes);
        }
        $htmlTag = '<html' . ($_attr ? ' ' . $_attr : '') . '>';
        if (isset($tsfe->config['config']['htmlTag_stdWrap.'])) {
            $htmlTag = $tsfe->cObj->stdWrap($htmlTag, $tsfe->config['config']['htmlTag_stdWrap.']);
        }
        $pageRenderer->setHtmlTag($htmlTag);
        // Head tag:
        $headTag = $tsfe->pSetup['headTag'] ?? '<head>';
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
        if ($tsfe->pSetup['shortcutIcon'] ?? false) {
            try {
                $favIcon = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($tsfe->pSetup['shortcutIcon']);
                $iconFileInfo = GeneralUtility::makeInstance(ImageInfo::class, Environment::getPublicPath() . '/' . $favIcon);
                if ($iconFileInfo->isFile()) {
                    $iconMimeType = $iconFileInfo->getMimeType();
                    if ($iconMimeType) {
                        $iconMimeType = ' type="' . $iconMimeType . '"';
                        $pageRenderer->setIconMimeType($iconMimeType);
                    }
                    $pageRenderer->setFavIcon(PathUtility::getAbsoluteWebPath($tsfe->absRefPrefix . $favIcon));
                }
            } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                // do nothing
            }
        }
        // Including CSS files
        if (isset($tsfe->tmpl->setup['plugin.']) && is_array($tsfe->tmpl->setup['plugin.'])) {
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
        /**********************************************************************/
        /* config.includeCSS / config.includeCSSLibs
        /**********************************************************************/
        if (isset($tsfe->pSetup['includeCSS.']) && is_array($tsfe->pSetup['includeCSS.'])) {
            foreach ($tsfe->pSetup['includeCSS.'] as $key => $CSSfile) {
                if (!is_array($CSSfile)) {
                    $cssFileConfig = &$tsfe->pSetup['includeCSS.'][$key . '.'];
                    if (isset($cssFileConfig['if.']) && !$tsfe->cObj->checkIf($cssFileConfig['if.'])) {
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
                            $pageRenderer->addCssInlineBlock('import_' . $key, '@import url("' . htmlspecialchars($ss) . '") ' . htmlspecialchars($cssFileConfig['media']) . ';', empty($cssFileConfig['disableCompression']), (bool)$cssFileConfig['forceOnTop']);
                        } else {
                            $pageRenderer->addCssFile(
                                $ss,
                                $cssFileConfig['alternate'] ? 'alternate stylesheet' : 'stylesheet',
                                $cssFileConfig['media'] ?: 'all',
                                $cssFileConfig['title'] ?: '',
                                $cssFileConfig['external'] ? false : empty($cssFileConfig['disableCompression']),
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
        if (isset($tsfe->pSetup['includeCSSLibs.']) && is_array($tsfe->pSetup['includeCSSLibs.'])) {
            foreach ($tsfe->pSetup['includeCSSLibs.'] as $key => $CSSfile) {
                if (!is_array($CSSfile)) {
                    $cssFileConfig = &$tsfe->pSetup['includeCSSLibs.'][$key . '.'];
                    if (isset($cssFileConfig['if.']) && !$tsfe->cObj->checkIf($cssFileConfig['if.'])) {
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
                            $pageRenderer->addCssInlineBlock('import_' . $key, '@import url("' . htmlspecialchars($ss) . '") ' . htmlspecialchars($cssFileConfig['media']) . ';', empty($cssFileConfig['disableCompression']), (bool)$cssFileConfig['forceOnTop']);
                        } else {
                            $pageRenderer->addCssLibrary(
                                $ss,
                                $cssFileConfig['alternate'] ? 'alternate stylesheet' : 'stylesheet',
                                $cssFileConfig['media'] ?: 'all',
                                $cssFileConfig['title'] ?: '',
                                $cssFileConfig['external'] ? false : empty($cssFileConfig['disableCompression']),
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
        $style = trim($tsfe->pSetup['CSS_inlineStyle'] ?? '');
        $style .= $tsfe->cObj->cObjGet($tsfe->pSetup['cssInline.'] ?? null, 'cssInline.');
        if (trim($style)) {
            self::addCssToPageRenderer($style, true, 'additionalTSFEInlineStyle');
        }
        // Javascript Libraries
        if (isset($tsfe->pSetup['javascriptLibs.']) && is_array($tsfe->pSetup['javascriptLibs.'])) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, the setting page.javascriptLibs has been deprecated and will be removed in TYPO3 v10.0.
            trigger_error('The setting page.javascriptLibs will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);

            // Include jQuery into the page renderer
            if (!empty($tsfe->pSetup['javascriptLibs.']['jQuery'])) {
                // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, the setting page.javascriptLibs.jQuery has been deprecated and will be removed in TYPO3 v10.0.
                trigger_error('The setting page.javascriptLibs.jQuery will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);

                $jQueryTS = $tsfe->pSetup['javascriptLibs.']['jQuery.'];
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
        if (isset($tsfe->pSetup['includeJSLibs.']) && is_array($tsfe->pSetup['includeJSLibs.'])) {
            foreach ($tsfe->pSetup['includeJSLibs.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($tsfe->pSetup['includeJSLibs.'][$key . '.']['if.']) && !$tsfe->cObj->checkIf($tsfe->pSetup['includeJSLibs.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($tsfe->pSetup['includeJSLibs.'][$key . '.']['external']) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsFileConfig = &$tsfe->pSetup['includeJSLibs.'][$key . '.'];
                        $type = $jsFileConfig['type'];
                        if (!$type) {
                            $type = 'text/javascript';
                        }
                        $crossorigin = $jsFileConfig['crossorigin'];
                        if (!$crossorigin && $jsFileConfig['integrity'] && $jsFileConfig['external']) {
                            $crossorigin = 'anonymous';
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
                            $crossorigin
                        );
                        unset($jsFileConfig);
                    }
                }
            }
        }
        if (isset($tsfe->pSetup['includeJSFooterlibs.']) && is_array($tsfe->pSetup['includeJSFooterlibs.'])) {
            foreach ($tsfe->pSetup['includeJSFooterlibs.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($tsfe->pSetup['includeJSFooterlibs.'][$key . '.']['if.']) && !$tsfe->cObj->checkIf($tsfe->pSetup['includeJSFooterlibs.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($tsfe->pSetup['includeJSFooterlibs.'][$key . '.']['external']) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsFileConfig = &$tsfe->pSetup['includeJSFooterlibs.'][$key . '.'];
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
        if (isset($tsfe->pSetup['includeJS.']) && is_array($tsfe->pSetup['includeJS.'])) {
            foreach ($tsfe->pSetup['includeJS.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($tsfe->pSetup['includeJS.'][$key . '.']['if.']) && !$tsfe->cObj->checkIf($tsfe->pSetup['includeJS.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($tsfe->pSetup['includeJS.'][$key . '.']['external']) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsConfig = &$tsfe->pSetup['includeJS.'][$key . '.'];
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
        if (isset($tsfe->pSetup['includeJSFooter.']) && is_array($tsfe->pSetup['includeJSFooter.'])) {
            foreach ($tsfe->pSetup['includeJSFooter.'] as $key => $JSfile) {
                if (!is_array($JSfile)) {
                    if (isset($tsfe->pSetup['includeJSFooter.'][$key . '.']['if.']) && !$tsfe->cObj->checkIf($tsfe->pSetup['includeJSFooter.'][$key . '.']['if.'])) {
                        continue;
                    }
                    if ($tsfe->pSetup['includeJSFooter.'][$key . '.']['external']) {
                        $ss = $JSfile;
                    } else {
                        try {
                            $ss = GeneralUtility::makeInstance(FilePathSanitizer::class)->sanitize($JSfile);
                        } catch (\TYPO3\CMS\Core\Resource\Exception $e) {
                            $ss = null;
                        }
                    }
                    if ($ss) {
                        $jsConfig = &$tsfe->pSetup['includeJSFooter.'][$key . '.'];
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
        if (isset($tsfe->pSetup['headerData.']) && is_array($tsfe->pSetup['headerData.'])) {
            $pageRenderer->addHeaderData($tsfe->cObj->cObjGet($tsfe->pSetup['headerData.'], 'headerData.'));
        }
        // Footerdata
        if (isset($tsfe->pSetup['footerData.']) && is_array($tsfe->pSetup['footerData.'])) {
            $pageRenderer->addFooterData($tsfe->cObj->cObjGet($tsfe->pSetup['footerData.'], 'footerData.'));
        }
        $tsfe->generatePageTitle();

        // @internal hook for EXT:seo, will be gone soon, do not use it in your own extensions
        $_params = ['page' => $tsfe->page];
        $_ref = '';
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['TYPO3\CMS\Frontend\Page\PageGenerator']['generateMetaTags'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $_ref);
        }

        static::generateMetaTagHtml(
            $tsfe->pSetup['meta.'] ?? [],
            $tsfe->cObj
        );

        unset($tsfe->additionalHeaderData['JSCode']);
        if (isset($tsfe->config['INTincScript']) && is_array($tsfe->config['INTincScript'])) {
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
        $scriptJsCode = '';

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
        $inline = $tsfe->cObj->cObjGet($tsfe->pSetup['jsInline.'] ?? null, 'jsInline.');
        if ($inline) {
            $inlineJS .= LF . $inline . LF;
        }
        // Javascript inline code for Footer
        $inlineFooterJs = $tsfe->cObj->cObjGet($tsfe->pSetup['jsFooterInline.'] ?? null, 'jsFooterInline.');
        // Should minify?
        if ($tsfe->config['config']['compressJs'] ?? false) {
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
        if (!isset($tsfe->config['config']['removeDefaultJS']) || !$tsfe->config['config']['removeDefaultJS']) {
            // include default and inlineJS
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
        if (isset($tsfe->pSetup['inlineLanguageLabelFiles.']) && is_array($tsfe->pSetup['inlineLanguageLabelFiles.'])) {
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
                    $languageFileConfig['stripFromSelectionName'] ?: ''
                );
            }
        }
        if (isset($tsfe->pSetup['inlineSettings.']) && is_array($tsfe->pSetup['inlineSettings.'])) {
            $pageRenderer->addInlineSettingArray('TS', $tsfe->pSetup['inlineSettings.']);
        }
        // Compression and concatenate settings
        if ($tsfe->config['config']['compressCss'] ?? false) {
            $pageRenderer->enableCompressCss();
        }
        if ($tsfe->config['config']['compressJs'] ?? false) {
            $pageRenderer->enableCompressJavascript();
        }
        if ($tsfe->config['config']['concatenateCss'] ?? false) {
            $pageRenderer->enableConcatenateCss();
        }
        if ($tsfe->config['config']['concatenateJs'] ?? false) {
            $pageRenderer->enableConcatenateJavascript();
        }
        // Backward compatibility for old configuration
        // @deprecated - remove this option in TYPO3 v10.0.
        if ($tsfe->config['config']['concatenateJsAndCss'] ?? false) {
            trigger_error('Setting config.concatenateJsAndCss is deprecated in favor of config.concatenateJs and config.concatenateCss, and will have no effect anymore in TYPO3 v10.0.', E_USER_DEPRECATED);
            $pageRenderer->enableConcatenateCss();
            $pageRenderer->enableConcatenateJavascript();
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
        // Bodytag:
        if ($tsfe->config['config']['disableBodyTag'] ?? false) {
            $bodyTag = '';
        } else {
            $defBT = (isset($tsfe->pSetup['bodyTagCObject']) && $tsfe->pSetup['bodyTagCObject'])
                ? $tsfe->cObj->cObjGetSingle($tsfe->pSetup['bodyTagCObject'], $tsfe->pSetup['bodyTagCObject.'], 'bodyTagCObject')
                : '<body>';
            $bodyTag = (isset($tsfe->pSetup['bodyTag']) && $tsfe->pSetup['bodyTag'])
                ? $tsfe->pSetup['bodyTag']
                : $defBT;
            if (trim($tsfe->pSetup['bodyTagAdd'] ?? '')) {
                $bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' ' . trim($tsfe->pSetup['bodyTagAdd']) . '>';
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
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. This functionality is now within TYPO3's Frontend Request Handler.
     */
    public static function inline2TempFile($str, $ext)
    {
        trigger_error('PageGenerator::inline2TempFile() will be removed in TYPO3 v10.0. This logic is now built in TYPO3s Frontend RequestHandler.', E_USER_DEPRECATED);
        // Create filename / tags:
        $script = '';
        switch ($ext) {
            case 'js':
                $script = 'typo3temp/assets/js/' . GeneralUtility::shortMD5($str) . '.js';
                break;
            case 'css':
                $script = 'typo3temp/assets/css/' . GeneralUtility::shortMD5($str) . '.css';
                break;
        }
        // Write file
        if ($script && !@is_file(Environment::getPublicPath() . '/' . $script)) {
            GeneralUtility::writeFileToTypo3tempDir(Environment::getPublicPath() . '/' . $script, $str);
        }
        return $script;
    }

    /**
     * Checks if the value defined in "config.linkVars" contains an allowed value. Otherwise, return FALSE which means the value will not be added to any links.
     *
     * @param string $haystack The string in which to find $needle
     * @param string $needle The string to find in $haystack
     * @return bool Returns TRUE if $needle matches or is found in $haystack
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, is now called within TSFE itself, if needed outside the regular calculations, reimplement the method on your own.
     */
    public static function isAllowedLinkVarValue($haystack, $needle)
    {
        trigger_error('The method will be removed in TYPO3 v10.0, if needed outside of linkVar calculation, re-implement the method in your own extension.', E_USER_DEPRECATED);
        $OK = false;
        // Integer
        if ($needle === 'int' || $needle === 'integer') {
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, as TSFE->generatePageTitle() should be used instead.
     */
    public static function generatePageTitle()
    {
        trigger_error('This method will be removed in TYPO3 v10.0. Use $TSFE->generatePageTitle() instead.', E_USER_DEPRECATED);
        $GLOBALS['TSFE']->generatePageTitle();
    }

    /**
     * Generate meta tags from meta tag TypoScript
     *
     * @param array $metaTagTypoScript TypoScript configuration for meta tags (e.g. $GLOBALS['TSFE']->pSetup['meta.'])
     * @param ContentObjectRenderer $cObj
     */
    protected static function generateMetaTagHtml(array $metaTagTypoScript, ContentObjectRenderer $cObj)
    {
        $pageRenderer = static::getPageRenderer();

        /** @var TypoScriptService $typoScriptService */
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $conf = $typoScriptService->convertTypoScriptArrayToPlainArray($metaTagTypoScript);
        foreach ($conf as $key => $properties) {
            $replace = false;
            if (is_array($properties)) {
                $nodeValue = $properties['_typoScriptNodeValue'] ?? '';
                $value = trim($cObj->stdWrap($nodeValue, $metaTagTypoScript[$key . '.']));
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
                if (trim($subValue) !== '') {
                    $pageRenderer->setMetaTag($attribute, $key, $subValue, [], $replace);
                }
            }
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
     * @param bool $excludeFromConcatenation option to see if it should be concatenated
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

    /**
     * Returns the currently configured "site language" if a site is configured (= resolved) in the current request.
     *
     * @internal
     */
    protected static function getCurrentSiteLanguage(): ?SiteLanguage
    {
        if (isset($GLOBALS['TYPO3_REQUEST'])
            && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface
            && $GLOBALS['TYPO3_REQUEST']->getAttribute('language') instanceof SiteLanguage) {
            return $GLOBALS['TYPO3_REQUEST']->getAttribute('language');
        }
        return null;
    }
}
