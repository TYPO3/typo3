<?php
namespace TYPO3\CMS\Core\Page;

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

use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * TYPO3 pageRender class (new in TYPO3 4.3.0)
 * This class render the HTML of a webpage, usable for BE and FE
 */
class PageRenderer implements \TYPO3\CMS\Core\SingletonInterface
{
    // Constants for the part to be rendered
    const PART_COMPLETE = 0;
    const PART_HEADER = 1;
    const PART_FOOTER = 2;
    // jQuery Core version that is shipped with TYPO3
    const JQUERY_VERSION_LATEST = '3.2.1';
    // jQuery namespace options
    const JQUERY_NAMESPACE_NONE = 'none';
    const JQUERY_NAMESPACE_DEFAULT = 'jQuery';
    const JQUERY_NAMESPACE_DEFAULT_NOCONFLICT = 'defaultNoConflict';

    /**
     * @var bool
     */
    protected $compressJavascript = false;

    /**
     * @var bool
     */
    protected $compressCss = false;

    /**
     * @var bool
     */
    protected $removeLineBreaksFromTemplate = false;

    /**
     * @var bool
     */
    protected $concatenateFiles = false;

    /**
     * @var bool
     */
    protected $concatenateJavascript = false;

    /**
     * @var bool
     */
    protected $concatenateCss = false;

    /**
     * @var bool
     */
    protected $moveJsFromHeaderToFooter = false;

    /**
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    protected $csConvObj;

    /**
     * @var \TYPO3\CMS\Core\Localization\Locales
     */
    protected $locales;

    /**
     * The language key
     * Two character string or 'default'
     *
     * @var string
     */
    protected $lang;

    /**
     * List of language dependencies for actual language. This is used for local variants of a language
     * that depend on their "main" language, like Brazilian Portuguese or Canadian French.
     *
     * @var array
     */
    protected $languageDependencies = [];

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceCompressor
     */
    protected $compressor;

    // Arrays containing associative array for the included files
    /**
     * @var array
     */
    protected $jsFiles = [];

    /**
     * @var array
     */
    protected $jsFooterFiles = [];

    /**
     * @var array
     */
    protected $jsLibs = [];

    /**
     * @var array
     */
    protected $jsFooterLibs = [];

    /**
     * @var array
     */
    protected $cssFiles = [];

    /**
     * @var array
     */
    protected $cssLibs = [];

    /**
     * The title of the page
     *
     * @var string
     */
    protected $title;

    /**
     * Charset for the rendering
     *
     * @var string
     */
    protected $charSet;

    /**
     * @var string
     */
    protected $favIcon;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var bool
     */
    protected $renderXhtml = true;

    // Static header blocks
    /**
     * @var string
     */
    protected $xmlPrologAndDocType = '';

    /**
     * @var array
     */
    protected $metaTags = [];

    /**
     * @var array
     */
    protected $inlineComments = [];

    /**
     * @var array
     */
    protected $headerData = [];

    /**
     * @var array
     */
    protected $footerData = [];

    /**
     * @var string
     */
    protected $titleTag = '<title>|</title>';

    /**
     * @var string
     */
    protected $metaCharsetTag = '<meta http-equiv="Content-Type" content="text/html; charset=|" />';

    /**
     * @var string
     */
    protected $htmlTag = '<html>';

    /**
     * @var string
     */
    protected $headTag = '<head>';

    /**
     * @var string
     */
    protected $baseUrlTag = '<base href="|" />';

    /**
     * @var string
     */
    protected $iconMimeType = '';

    /**
     * @var string
     */
    protected $shortcutTag = '<link rel="shortcut icon" href="%1$s"%2$s />';

    // Static inline code blocks
    /**
     * @var array
     */
    protected $jsInline = [];

    /**
     * @var array
     */
    protected $jsFooterInline = [];

    /**
     * @var array
     */
    protected $extOnReadyCode = [];

    /**
     * @var array
     */
    protected $cssInline = [];

    /**
     * @var string
     */
    protected $bodyContent;

    /**
     * @var string
     */
    protected $templateFile;

    /**
     * @var array
     */
    protected $jsLibraryNames = ['extjs'];

    // Paths to contributed libraries

    /**
     * default path to the requireJS library, relative to the typo3/ directory
     * @var string
     */
    protected $requireJsPath = 'EXT:core/Resources/Public/JavaScript/Contrib/';

    /**
     * @var string
     */
    protected $extJsPath = 'EXT:core/Resources/Public/JavaScript/Contrib/extjs/';

    /**
     * The local directory where one can find jQuery versions and plugins
     *
     * @var string
     */
    protected $jQueryPath = 'EXT:core/Resources/Public/JavaScript/Contrib/jquery/';

    // Internal flags for JS-libraries
    /**
     * This array holds all jQuery versions that should be included in the
     * current page.
     * Each version is described by "source", "version" and "namespace"
     *
     * The namespace of every particular version is the key
     * of that array, because only one version per namespace can exist.
     *
     * The type "source" describes where the jQuery core should be included from
     * currently, TYPO3 supports "local" (make use of jQuery path), "google",
     * "jquery", "msn" and "cloudflare".
     *
     * Currently there are downsides to "local" which supports only the latest/shipped
     * jQuery core out of the box.
     *
     * @var array
     */
    protected $jQueryVersions = [];

    /**
     * Array of jQuery version numbers shipped with the core
     *
     * @var array
     */
    protected $availableLocalJqueryVersions = [
        self::JQUERY_VERSION_LATEST
    ];

    /**
     * Array of jQuery CDNs with placeholders
     *
     * @var array
     */
    protected $jQueryCdnUrls = [
        'google' => 'https://ajax.googleapis.com/ajax/libs/jquery/%1$s/jquery%2$s.js',
        'msn' => 'https://ajax.aspnetcdn.com/ajax/jQuery/jquery-%1$s%2$s.js',
        'jquery' => 'https://code.jquery.com/jquery-%1$s%2$s.js',
        'cloudflare' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery/%1$s/jquery%2$s.js'
    ];

    /**
     * if set, the requireJS library is included
     * @var bool
     */
    protected $addRequireJs = false;

    /**
     * inline configuration for requireJS
     * @var array
     */
    protected $requireJsConfig = [];

    /**
     * @var bool
     */
    protected $addExtJS = false;

    /**
     * @var bool
     */
    protected $extDirectCodeAdded = false;

    /**
     * @var bool
     */
    protected $enableExtJsDebug = false;

    /**
     * @var bool
     */
    protected $enableJqueryDebug = false;

    /**
     * @var bool
     */
    protected $extJStheme = true;

    /**
     * @var bool
     */
    protected $extJScss = true;

    /**
     * @var array
     */
    protected $inlineLanguageLabels = [];

    /**
     * @var array
     */
    protected $inlineLanguageLabelFiles = [];

    /**
     * @var array
     */
    protected $inlineSettings = [];

    /**
     * @var array
     */
    protected $inlineJavascriptWrap = [];

    /**
     * @var array
     */
    protected $inlineCssWrap = [];

    /**
     * Saves error messages generated during compression
     *
     * @var string
     */
    protected $compressError = '';

    /**
     * Is empty string for HTML and ' /' for XHTML rendering
     *
     * @var string
     */
    protected $endingSlash = '';

    /**
     * @param string $templateFile Declare the used template file. Omit this parameter will use default template
     */
    public function __construct($templateFile = '')
    {
        $this->reset();
        $this->csConvObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
        $this->locales = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\Locales::class);
        if ($templateFile !== '') {
            $this->templateFile = $templateFile;
        }
        $this->inlineJavascriptWrap = [
            '<script type="text/javascript">' . LF . '/*<![CDATA[*/' . LF,
            '/*]]>*/' . LF . '</script>' . LF
        ];
        $this->inlineCssWrap = [
            '<style type="text/css">' . LF . '/*<![CDATA[*/' . LF . '<!-- ' . LF,
            '-->' . LF . '/*]]>*/' . LF . '</style>' . LF
        ];
    }

    /**
     * Reset all vars to initial values
     */
    protected function reset()
    {
        $this->templateFile = 'EXT:core/Resources/Private/Templates/PageRenderer.html';
        $this->jsFiles = [];
        $this->jsFooterFiles = [];
        $this->jsInline = [];
        $this->jsFooterInline = [];
        $this->jsLibs = [];
        $this->cssFiles = [];
        $this->cssInline = [];
        $this->metaTags = [];
        $this->inlineComments = [];
        $this->headerData = [];
        $this->footerData = [];
        $this->extOnReadyCode = [];
        $this->jQueryVersions = [];
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Setters                                   */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Sets the title
     *
     * @param string $title	title of webpage
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Enables/disables rendering of XHTML code
     *
     * @param bool $enable Enable XHTML
     */
    public function setRenderXhtml($enable)
    {
        $this->renderXhtml = $enable;
    }

    /**
     * Sets xml prolog and docType
     *
     * @param string $xmlPrologAndDocType Complete tags for xml prolog and docType
     */
    public function setXmlPrologAndDocType($xmlPrologAndDocType)
    {
        $this->xmlPrologAndDocType = $xmlPrologAndDocType;
    }

    /**
     * Sets meta charset
     *
     * @param string $charSet Used charset
     */
    public function setCharSet($charSet)
    {
        $this->charSet = $charSet;
    }

    /**
     * Sets language
     *
     * @param string $lang Used language
     */
    public function setLanguage($lang)
    {
        $this->lang = $lang;
        $this->languageDependencies = [];

        // Language is found. Configure it:
        if (in_array($this->lang, $this->locales->getLocales())) {
            $this->languageDependencies[] = $this->lang;
            foreach ($this->locales->getLocaleDependencies($this->lang) as $language) {
                $this->languageDependencies[] = $language;
            }
        }
    }

    /**
     * Set the meta charset tag
     *
     * @param string $metaCharsetTag
     */
    public function setMetaCharsetTag($metaCharsetTag)
    {
        $this->metaCharsetTag = $metaCharsetTag;
    }

    /**
     * Sets html tag
     *
     * @param string $htmlTag Html tag
     */
    public function setHtmlTag($htmlTag)
    {
        $this->htmlTag = $htmlTag;
    }

    /**
     * Sets HTML head tag
     *
     * @param string $headTag HTML head tag
     */
    public function setHeadTag($headTag)
    {
        $this->headTag = $headTag;
    }

    /**
     * Sets favicon
     *
     * @param string $favIcon
     */
    public function setFavIcon($favIcon)
    {
        $this->favIcon = $favIcon;
    }

    /**
     * Sets icon mime type
     *
     * @param string $iconMimeType
     */
    public function setIconMimeType($iconMimeType)
    {
        $this->iconMimeType = $iconMimeType;
    }

    /**
     * Sets HTML base URL
     *
     * @param string $baseUrl HTML base URL
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Sets template file
     *
     * @param string $file
     */
    public function setTemplateFile($file)
    {
        $this->templateFile = $file;
    }

    /**
     * Sets Content for Body
     *
     * @param string $content
     */
    public function setBodyContent($content)
    {
        $this->bodyContent = $content;
    }

    /**
     * Sets path to requireJS library (relative to typo3 directory)
     *
     * @param string $path Path to requireJS library
     */
    public function setRequireJsPath($path)
    {
        $this->requireJsPath = $path;
    }

    /**
     * Sets Path for ExtJs library (relative to typo3 directory)
     *
     * @param string $path
     */
    public function setExtJsPath($path)
    {
        $this->extJsPath = $path;
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Enablers / Disablers                      */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Enables MoveJsFromHeaderToFooter
     */
    public function enableMoveJsFromHeaderToFooter()
    {
        $this->moveJsFromHeaderToFooter = true;
    }

    /**
     * Disables MoveJsFromHeaderToFooter
     */
    public function disableMoveJsFromHeaderToFooter()
    {
        $this->moveJsFromHeaderToFooter = false;
    }

    /**
     * Enables compression of javascript
     */
    public function enableCompressJavascript()
    {
        $this->compressJavascript = true;
    }

    /**
     * Disables compression of javascript
     */
    public function disableCompressJavascript()
    {
        $this->compressJavascript = false;
    }

    /**
     * Enables compression of css
     */
    public function enableCompressCss()
    {
        $this->compressCss = true;
    }

    /**
     * Disables compression of css
     */
    public function disableCompressCss()
    {
        $this->compressCss = false;
    }

    /**
     * Enables concatenation of js and css files
     */
    public function enableConcatenateFiles()
    {
        $this->concatenateFiles = true;
    }

    /**
     * Disables concatenation of js and css files
     */
    public function disableConcatenateFiles()
    {
        $this->concatenateFiles = false;
    }

    /**
     * Enables concatenation of js files
     */
    public function enableConcatenateJavascript()
    {
        $this->concatenateJavascript = true;
    }

    /**
     * Disables concatenation of js files
     */
    public function disableConcatenateJavascript()
    {
        $this->concatenateJavascript = false;
    }

    /**
     * Enables concatenation of css files
     */
    public function enableConcatenateCss()
    {
        $this->concatenateCss = true;
    }

    /**
     * Disables concatenation of css files
     */
    public function disableConcatenateCss()
    {
        $this->concatenateCss = false;
    }

    /**
     * Sets removal of all line breaks in template
     */
    public function enableRemoveLineBreaksFromTemplate()
    {
        $this->removeLineBreaksFromTemplate = true;
    }

    /**
     * Unsets removal of all line breaks in template
     */
    public function disableRemoveLineBreaksFromTemplate()
    {
        $this->removeLineBreaksFromTemplate = false;
    }

    /**
     * Enables Debug Mode
     * This is a shortcut to switch off all compress/concatenate features to enable easier debug
     */
    public function enableDebugMode()
    {
        $this->compressJavascript = false;
        $this->compressCss = false;
        $this->concatenateFiles = false;
        $this->removeLineBreaksFromTemplate = false;
        $this->enableExtJsDebug = true;
        $this->enableJqueryDebug = true;
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Getters                                   */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Gets the title
     *
     * @return string $title Title of webpage
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Gets the charSet
     *
     * @return string $charSet
     */
    public function getCharSet()
    {
        return $this->charSet;
    }

    /**
     * Gets the language
     *
     * @return string $lang
     */
    public function getLanguage()
    {
        return $this->lang;
    }

    /**
     * Returns rendering mode XHTML or HTML
     *
     * @return bool TRUE if XHTML, FALSE if HTML
     */
    public function getRenderXhtml()
    {
        return $this->renderXhtml;
    }

    /**
     * Gets html tag
     *
     * @return string $htmlTag Html tag
     */
    public function getHtmlTag()
    {
        return $this->htmlTag;
    }

    /**
     * Get meta charset
     *
     * @return string
     */
    public function getMetaCharsetTag()
    {
        return $this->metaCharsetTag;
    }

    /**
     * Gets head tag
     *
     * @return string $tag Head tag
     */
    public function getHeadTag()
    {
        return $this->headTag;
    }

    /**
     * Gets favicon
     *
     * @return string $favIcon
     */
    public function getFavIcon()
    {
        return $this->favIcon;
    }

    /**
     * Gets icon mime type
     *
     * @return string $iconMimeType
     */
    public function getIconMimeType()
    {
        return $this->iconMimeType;
    }

    /**
     * Gets HTML base URL
     *
     * @return string $url
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Gets template file
     *
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->templateFile;
    }

    /**
     * Gets MoveJsFromHeaderToFooter
     *
     * @return bool
     */
    public function getMoveJsFromHeaderToFooter()
    {
        return $this->moveJsFromHeaderToFooter;
    }

    /**
     * Gets compress of javascript
     *
     * @return bool
     */
    public function getCompressJavascript()
    {
        return $this->compressJavascript;
    }

    /**
     * Gets compress of css
     *
     * @return bool
     */
    public function getCompressCss()
    {
        return $this->compressCss;
    }

    /**
     * Gets concatenate of js and css files
     *
     * @return bool
     */
    public function getConcatenateFiles()
    {
        return $this->concatenateFiles;
    }

    /**
     * Gets concatenate of js files
     *
     * @return bool
     */
    public function getConcatenateJavascript()
    {
        return $this->concatenateJavascript;
    }

    /**
     * Gets concatenate of css files
     *
     * @return bool
     */
    public function getConcatenateCss()
    {
        return $this->concatenateCss;
    }

    /**
     * Gets remove of empty lines from template
     *
     * @return bool
     */
    public function getRemoveLineBreaksFromTemplate()
    {
        return $this->removeLineBreaksFromTemplate;
    }

    /**
     * Gets content for body
     *
     * @return string
     */
    public function getBodyContent()
    {
        return $this->bodyContent;
    }

    /**
     * Gets Path for ExtJs library (relative to typo3 directory)
     *
     * @return string
     */
    public function getExtJsPath()
    {
        return $this->extJsPath;
    }

    /**
     * Gets the inline language labels.
     *
     * @return array The inline language labels
     */
    public function getInlineLanguageLabels()
    {
        return $this->inlineLanguageLabels;
    }

    /**
     * Gets the inline language files
     *
     * @return array
     */
    public function getInlineLanguageLabelFiles()
    {
        return $this->inlineLanguageLabelFiles;
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Functions to add Data                     */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Adds meta data
     *
     * @param string $meta Meta data (complete metatag)
     */
    public function addMetaTag($meta)
    {
        if (!in_array($meta, $this->metaTags)) {
            $this->metaTags[] = $meta;
        }
    }

    /**
     * Adds inline HTML comment
     *
     * @param string $comment
     */
    public function addInlineComment($comment)
    {
        if (!in_array($comment, $this->inlineComments)) {
            $this->inlineComments[] = $comment;
        }
    }

    /**
     * Adds header data
     *
     * @param string $data Free header data for HTML header
     */
    public function addHeaderData($data)
    {
        if (!in_array($data, $this->headerData)) {
            $this->headerData[] = $data;
        }
    }

    /**
     * Adds footer data
     *
     * @param string $data Free header data for HTML header
     */
    public function addFooterData($data)
    {
        if (!in_array($data, $this->footerData)) {
            $this->footerData[] = $data;
        }
    }

    /**
     * Adds JS Library. JS Library block is rendered on top of the JS files.
     *
     * @param string $name Arbitrary identifier
     * @param string $file File name
     * @param string $type Content Type
     * @param bool $compress Flag if library should be compressed
     * @param bool $forceOnTop Flag if added library should be inserted at begin of this block
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     */
    public function addJsLibrary($name, $file, $type = 'text/javascript', $compress = false, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '')
    {
        if (!$type) {
            $type = 'text/javascript';
        }
        if (!in_array(strtolower($name), $this->jsLibs)) {
            $this->jsLibs[strtolower($name)] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_HEADER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
            ];
        }
    }

    /**
     * Adds JS Library to Footer. JS Library block is rendered on top of the Footer JS files.
     *
     * @param string $name Arbitrary identifier
     * @param string $file File name
     * @param string $type Content Type
     * @param bool $compress Flag if library should be compressed
     * @param bool $forceOnTop Flag if added library should be inserted at begin of this block
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     */
    public function addJsFooterLibrary($name, $file, $type = 'text/javascript', $compress = false, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '')
    {
        if (!$type) {
            $type = 'text/javascript';
        }
        if (!in_array(strtolower($name), $this->jsLibs)) {
            $this->jsLibs[strtolower($name)] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_FOOTER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
            ];
        }
    }

    /**
     * Adds JS file
     *
     * @param string $file File name
     * @param string $type Content Type
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     */
    public function addJsFile($file, $type = 'text/javascript', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '')
    {
        if (!$type) {
            $type = 'text/javascript';
        }
        if (!isset($this->jsFiles[$file])) {
            $this->jsFiles[$file] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_HEADER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
            ];
        }
    }

    /**
     * Adds JS file to footer
     *
     * @param string $file File name
     * @param string $type Content Type
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     */
    public function addJsFooterFile($file, $type = 'text/javascript', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '')
    {
        if (!$type) {
            $type = 'text/javascript';
        }
        if (!isset($this->jsFiles[$file])) {
            $this->jsFiles[$file] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_FOOTER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
            ];
        }
    }

    /**
     * Adds JS inline code
     *
     * @param string $name
     * @param string $block
     * @param bool $compress
     * @param bool $forceOnTop
     */
    public function addJsInlineCode($name, $block, $compress = true, $forceOnTop = false)
    {
        if (!isset($this->jsInline[$name]) && !empty($block)) {
            $this->jsInline[$name] = [
                'code' => $block . LF,
                'section' => self::PART_HEADER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop
            ];
        }
    }

    /**
     * Adds JS inline code to footer
     *
     * @param string $name
     * @param string $block
     * @param bool $compress
     * @param bool $forceOnTop
     */
    public function addJsFooterInlineCode($name, $block, $compress = true, $forceOnTop = false)
    {
        if (!isset($this->jsInline[$name]) && !empty($block)) {
            $this->jsInline[$name] = [
                'code' => $block . LF,
                'section' => self::PART_FOOTER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop
            ];
        }
    }

    /**
     * Adds Ext.onready code, which will be wrapped in Ext.onReady(function() {...});
     *
     * @param string $block Javascript code
     * @param bool $forceOnTop Position of the javascript code (TRUE for putting it on top, default is FALSE = bottom)
     */
    public function addExtOnReadyCode($block, $forceOnTop = false)
    {
        if (!in_array($block, $this->extOnReadyCode)) {
            if ($forceOnTop) {
                array_unshift($this->extOnReadyCode, $block);
            } else {
                $this->extOnReadyCode[] = $block;
            }
        }
    }

    /**
     * Adds the ExtDirect code
     *
     * @param array $filterNamespaces Limit the output to defined namespaces. If empty, all namespaces are generated
     */
    public function addExtDirectCode(array $filterNamespaces = [])
    {
        if ($this->extDirectCodeAdded) {
            return;
        }
        $this->extDirectCodeAdded = true;
        if (empty($filterNamespaces)) {
            $filterNamespaces = ['TYPO3'];
        }

        // Add language labels for ExtDirect
        $this->addInlineLanguageLabelArray([
            'extDirect_timeoutHeader'  => 'LLL:EXT:lang/Resources/Private/Language/locallang_misc.xlf:extDirect_timeoutHeader',
            'extDirect_timeoutMessage' => 'LLL:EXT:lang/Resources/Private/Language/locallang_misc.xlf:extDirect_timeoutMessage'
        ], true);

        $token = ($api = '');
        if (TYPO3_MODE === 'BE') {
            $formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
            $token = $formprotection->generateToken('extDirect');

            // Debugger Console strings
            $this->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/debugger.xlf');

            $this->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/wizard.xlf');
        }
        /** @var $extDirect \TYPO3\CMS\Core\ExtDirect\ExtDirectApi */
        $extDirect = GeneralUtility::makeInstance(\TYPO3\CMS\Core\ExtDirect\ExtDirectApi::class);
        $api = $extDirect->getApiPhp($filterNamespaces);
        if ($api) {
            $this->addJsInlineCode('TYPO3ExtDirectAPI', $api, false);
        }
        // Note: we need to iterate through the object, because the addProvider method
        // does this only with multiple arguments
        $this->addExtOnReadyCode('
			(function() {
				TYPO3.ExtDirectToken = "' . $token . '";
				for (var api in Ext.app.ExtDirectAPI) {
					var provider = Ext.Direct.addProvider(Ext.app.ExtDirectAPI[api]);
					provider.on("beforecall", function(provider, transaction, meta) {
						if (transaction.data) {
							transaction.data[transaction.data.length] = TYPO3.ExtDirectToken;
						} else {
							transaction.data = [TYPO3.ExtDirectToken];
						}
					});

					provider.on("call", function(provider, transaction, meta) {
						if (transaction.isForm) {
							transaction.params.securityToken = TYPO3.ExtDirectToken;
						}
					});
				}
			})();

			var extDirectDebug = function(message, header, group) {
				var DebugConsole = null;

				if (top && top.TYPO3 && typeof top.TYPO3.DebugConsole === "object") {
					DebugConsole = top.TYPO3.DebugConsole;
				} else if (typeof TYPO3 === "object" && typeof TYPO3.DebugConsole === "object") {
					DebugConsole = TYPO3.DebugConsole;
				}

				if (DebugConsole !== null) {
					DebugConsole.add(message, header, group);
				} else if (typeof console === "object") {
					console.log(message);
				} else {
					document.write(message);
				}
			};

			Ext.Direct.on("exception", function(event) {
				if (event.code === Ext.Direct.exceptions.TRANSPORT && !event.where) {
					top.TYPO3.Notification.error(
						TYPO3.l10n.localize("extDirect_timeoutHeader"),
						TYPO3.l10n.localize("extDirect_timeoutMessage")
					);
				} else {
					var backtrace = "";
					if (event.code === "parse") {
						extDirectDebug(
							"<p>" + event.xhr.responseText + "<\\/p>",
							event.type,
							"ExtDirect - Exception"
						);
					} else if (event.code === "router") {
						top.TYPO3.Notification.error(
							event.code,
							event.message
						);
					} else if (event.where) {
						backtrace = "<p style=\\"margin-top: 20px;\\">" +
							"<strong>Backtrace:<\\/strong><br \\/>" +
							event.where.replace(/#/g, "<br \\/>#") +
							"<\\/p>";
						extDirectDebug(
							"<p>" + event.message + "<\\/p>" + backtrace,
							event.method,
							"ExtDirect - Exception"
						);
					}


				}
			});

			Ext.Direct.on("event", function(event, provider) {
				if (typeof event.debug !== "undefined" && event.debug !== "") {
					extDirectDebug(event.debug, event.method, "ExtDirect - Debug");
				}
			});
			', true);
    }

    /**
     * Adds CSS file
     *
     * @param string $file
     * @param string $rel
     * @param string $media
     * @param string $title
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     */
    public function addCssFile($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|')
    {
        if (!isset($this->cssFiles[$file])) {
            $this->cssFiles[$file] = [
                'file' => $file,
                'rel' => $rel,
                'media' => $media,
                'title' => $title,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar
            ];
        }
    }

    /**
     * Adds CSS file
     *
     * @param string $file
     * @param string $rel
     * @param string $media
     * @param string $title
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     */
    public function addCssLibrary($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|')
    {
        if (!isset($this->cssLibs[$file])) {
            $this->cssLibs[$file] = [
                'file' => $file,
                'rel' => $rel,
                'media' => $media,
                'title' => $title,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar
            ];
        }
    }

    /**
     * Adds CSS inline code
     *
     * @param string $name
     * @param string $block
     * @param bool $compress
     * @param bool $forceOnTop
     */
    public function addCssInlineBlock($name, $block, $compress = false, $forceOnTop = false)
    {
        if (!isset($this->cssInline[$name]) && !empty($block)) {
            $this->cssInline[$name] = [
                'code' => $block,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop
            ];
        }
    }

    /**
     * Call this function if you need to include the jQuery library
     *
     * @param string|null $version The jQuery version that should be included, either "latest" or any available version
     * @param string|null $source The location of the jQuery source, can be "local", "google", "msn", "jquery" or just an URL to your jQuery lib
     * @param string $namespace The namespace in which the jQuery object of the specific version should be stored.
     * @throws \UnexpectedValueException
     */
    public function loadJquery($version = null, $source = null, $namespace = self::JQUERY_NAMESPACE_DEFAULT)
    {
        // Set it to the version that is shipped with the TYPO3 core
        if ($version === null || $version === 'latest') {
            $version = self::JQUERY_VERSION_LATEST;
        }
        // Check if the source is set, otherwise set it to "default"
        if ($source === null) {
            $source = 'local';
        }
        if ($source === 'local' && !in_array($version, $this->availableLocalJqueryVersions)) {
            throw new \UnexpectedValueException('The requested jQuery version is not available in the local filesystem.', 1341505305);
        }
        if (!preg_match('/^[a-zA-Z0-9]+$/', $namespace)) {
            throw new \UnexpectedValueException('The requested namespace contains non alphanumeric characters.', 1341571604);
        }
        $this->jQueryVersions[$namespace] = [
            'version' => $version,
            'source' => $source
        ];
    }

    /**
     * Call function if you need the requireJS library
     * this automatically adds the JavaScript path of all loaded extensions in the requireJS path option
     * so it resolves names like TYPO3/CMS/MyExtension/MyJsFile to EXT:MyExtension/Resources/Public/JavaScript/MyJsFile.js
     * when using requireJS
     */
    public function loadRequireJs()
    {
        $this->addRequireJs = true;
        if (!empty($this->requireJsConfig)) {
            return;
        }

        $loadedExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();
        $isDevelopment = GeneralUtility::getApplicationContext()->isDevelopment();
        $cacheIdentifier = 'requireJS_' . md5(implode(',', $loadedExtensions) . ($isDevelopment ? ':dev' : '') . GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT'));
        /** @var VariableFrontend $cache */
        $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('assets');
        $this->requireJsConfig = $cache->get($cacheIdentifier);

        // if we did not get a configuration from the cache, compute and store it in the cache
        if (empty($this->requireJsConfig)) {
            $this->requireJsConfig = $this->computeRequireJsConfig($isDevelopment, $loadedExtensions);
            $cache->set($cacheIdentifier, $this->requireJsConfig);
        }
    }

    /**
     * Computes the RequireJS configuration, mainly consisting of the paths to the core and all extension JavaScript
     * resource folders plus some additional generic configuration.
     *
     * @param bool $isDevelopment
     * @param array $loadedExtensions
     * @return array The RequireJS configuration
     */
    protected function computeRequireJsConfig($isDevelopment, array $loadedExtensions)
    {
        // load all paths to map to package names / namespaces
        $requireJsConfig = [];

        // In order to avoid browser caching of JS files, adding a GET parameter to the files loaded via requireJS
        if ($isDevelopment) {
            $requireJsConfig['urlArgs'] = 'bust=' . $GLOBALS['EXEC_TIME'];
        } else {
            $requireJsConfig['urlArgs'] = 'bust=' . GeneralUtility::hmac(TYPO3_version . PATH_site);
        }
        $corePath = ExtensionManagementUtility::extPath('core', 'Resources/Public/JavaScript/Contrib/');
        $corePath = PathUtility::getAbsoluteWebPath($corePath);
        // first, load all paths for the namespaces, and configure contrib libs.
        $requireJsConfig['paths'] = [
            'jquery-ui' => $corePath . 'jquery-ui',
            'datatables' => $corePath . 'jquery.dataTables',
            'matchheight' => $corePath . 'jquery.matchHeight-min',
            'nprogress' => $corePath . 'nprogress',
            'moment' => $corePath . 'moment',
            'cropper' => $corePath . 'cropper.min',
            'imagesloaded' => $corePath . 'imagesloaded.pkgd.min',
            'bootstrap' => $corePath . 'bootstrap/bootstrap',
            'twbs/bootstrap-datetimepicker' => $corePath . 'bootstrap-datetimepicker',
            'autosize' => $corePath . 'autosize',
            'taboverride' => $corePath . 'taboverride.min',
            'twbs/bootstrap-slider' => $corePath . 'bootstrap-slider.min',
            'jquery/autocomplete' => $corePath . 'jquery.autocomplete',
            'd3' => $corePath . 'd3/d3'
        ];

        foreach ($loadedExtensions as $packageName) {
            $fullJsPath = 'EXT:' . $packageName . '/Resources/Public/JavaScript/';
            $fullJsPath = GeneralUtility::getFileAbsFileName($fullJsPath);
            $fullJsPath = PathUtility::getAbsoluteWebPath($fullJsPath);
            $fullJsPath = rtrim($fullJsPath, '/');
            if ($fullJsPath) {
                $requireJsConfig['paths']['TYPO3/CMS/' . GeneralUtility::underscoredToUpperCamelCase($packageName)] = $fullJsPath;
            }
        }

        // check if additional AMD modules need to be loaded if a single AMD module is initialized
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RequireJS']['postInitializationModules'])) {
            $this->addInlineSettingArray(
                'RequireJS.PostInitializationModules',
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RequireJS']['postInitializationModules']
            );
        }

        return $requireJsConfig;
    }

    /**
     * Add additional configuration to require js.
     *
     * Configuration will be merged recursive with overrule.
     *
     * To add another path mapping deliver the following configuration:
     * 		'paths' => array(
     *			'EXTERN/mybootstrapjs' => 'sysext/.../twbs/bootstrap.min',
     *      ),
     *
     * @param array $configuration The configuration that will be merged with existing one.
     */
    public function addRequireJsConfiguration(array $configuration)
    {
        if (TYPO3_MODE === 'BE') {
            // Load RequireJS in backend context at first. Doing this in FE could break the output
            $this->loadRequireJs();
        }
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($this->requireJsConfig, $configuration);
    }

    /**
     * includes an AMD-compatible JS file by resolving the ModuleName, and then requires the file via a requireJS request,
     * additionally allowing to execute JavaScript code afterwards
     *
     * this function only works for AMD-ready JS modules, used like "define('TYPO3/CMS/Backend/FormEngine..."
     * in the JS file
     *
     *	TYPO3/CMS/Backend/FormEngine =>
     * 		"TYPO3": Vendor Name
     * 		"CMS": Product Name
     *		"Backend": Extension Name
     *		"FormEngine": FileName in the Resources/Public/JavaScript folder
     *
     * @param string $mainModuleName Must be in the form of "TYPO3/CMS/PackageName/ModuleName" e.g. "TYPO3/CMS/Backend/FormEngine"
     * @param string $callBackFunction loaded right after the requireJS loading, must be wrapped in function() {}
     */
    public function loadRequireJsModule($mainModuleName, $callBackFunction = null)
    {
        $inlineCodeKey = $mainModuleName;
        // make sure requireJS is initialized
        $this->loadRequireJs();

        // execute the main module, and load a possible callback function
        $javaScriptCode = 'require(["' . $mainModuleName . '"]';
        if ($callBackFunction !== null) {
            $inlineCodeKey .= sha1($callBackFunction);
            $javaScriptCode .= ', ' . $callBackFunction;
        }
        $javaScriptCode .= ');';
        $this->addJsInlineCode('RequireJS-Module-' . $inlineCodeKey, $javaScriptCode);
    }

    /**
     * call this function if you need the extJS library
     *
     * @param bool $css Flag, if set the ext-css will be loaded
     * @param bool $theme Flag, if set the ext-theme "grey" will be loaded
     */
    public function loadExtJS($css = true, $theme = true)
    {
        $this->addExtJS = true;
        $this->extJStheme = $theme;
        $this->extJScss = $css;
    }

    /**
     * Call this function to load debug version of ExtJS. Use this for development only
     */
    public function enableExtJsDebug()
    {
        $this->enableExtJsDebug = true;
    }

    /**
     * Adds Javascript Inline Label. This will occur in TYPO3.lang - object
     * The label can be used in scripts with TYPO3.lang.<key>
     * Need extJs loaded
     *
     * @param string $key
     * @param string $value
     */
    public function addInlineLanguageLabel($key, $value)
    {
        $this->inlineLanguageLabels[$key] = $value;
    }

    /**
     * Adds Javascript Inline Label Array. This will occur in TYPO3.lang - object
     * The label can be used in scripts with TYPO3.lang.<key>
     * Array will be merged with existing array.
     * Need extJs loaded
     *
     * @param array $array
     * @param bool $parseWithLanguageService
     */
    public function addInlineLanguageLabelArray(array $array, $parseWithLanguageService = false)
    {
        if ($parseWithLanguageService === true) {
            foreach ($array as $key => $value) {
                if (TYPO3_MODE === 'FE') {
                    $array[$key] = $this->getTypoScriptFrontendController()->sL($value);
                } else {
                    $array[$key] = $this->getLanguageService()->sL($value);
                }
            }
        }

        $this->inlineLanguageLabels = array_merge($this->inlineLanguageLabels, $array);
    }

    /**
     * Gets labels to be used in JavaScript fetched from a locallang file.
     *
     * @param string $fileRef Input is a file-reference (see GeneralUtility::getFileAbsFileName). That file is expected to be a 'locallang.xlf' file containing a valid XML TYPO3 language structure.
     * @param string $selectionPrefix Prefix to select the correct labels (default: '')
     * @param string $stripFromSelectionName String to be removed from the label names in the output. (default: '')
     * @param int $errorMode Error mode (when file could not be found): 0 - syslog entry, 1 - do nothing, 2 - throw an exception
     */
    public function addInlineLanguageLabelFile($fileRef, $selectionPrefix = '', $stripFromSelectionName = '', $errorMode = 0)
    {
        $index = md5($fileRef . $selectionPrefix . $stripFromSelectionName);
        if ($fileRef && !isset($this->inlineLanguageLabelFiles[$index])) {
            $this->inlineLanguageLabelFiles[$index] = [
                'fileRef' => $fileRef,
                'selectionPrefix' => $selectionPrefix,
                'stripFromSelectionName' => $stripFromSelectionName,
                'errorMode' => $errorMode
            ];
        }
    }

    /**
     * Adds Javascript Inline Setting. This will occur in TYPO3.settings - object
     * The label can be used in scripts with TYPO3.setting.<key>
     * Need extJs loaded
     *
     * @param string $namespace
     * @param string $key
     * @param string $value
     */
    public function addInlineSetting($namespace, $key, $value)
    {
        if ($namespace) {
            if (strpos($namespace, '.')) {
                $parts = explode('.', $namespace);
                $a = &$this->inlineSettings;
                foreach ($parts as $part) {
                    $a = &$a[$part];
                }
                $a[$key] = $value;
            } else {
                $this->inlineSettings[$namespace][$key] = $value;
            }
        } else {
            $this->inlineSettings[$key] = $value;
        }
    }

    /**
     * Adds Javascript Inline Setting. This will occur in TYPO3.settings - object
     * The label can be used in scripts with TYPO3.setting.<key>
     * Array will be merged with existing array.
     * Need extJs loaded
     *
     * @param string $namespace
     * @param array $array
     */
    public function addInlineSettingArray($namespace, array $array)
    {
        if ($namespace) {
            if (strpos($namespace, '.')) {
                $parts = explode('.', $namespace);
                $a = &$this->inlineSettings;
                foreach ($parts as $part) {
                    $a = &$a[$part];
                }
                $a = array_merge((array)$a, $array);
            } else {
                $this->inlineSettings[$namespace] = array_merge((array)$this->inlineSettings[$namespace], $array);
            }
        } else {
            $this->inlineSettings = array_merge($this->inlineSettings, $array);
        }
    }

    /**
     * Adds content to body content
     *
     * @param string $content
     */
    public function addBodyContent($content)
    {
        $this->bodyContent .= $content;
    }

    /*****************************************************/
    /*                                                   */
    /*  Render Functions                                 */
    /*                                                   */
    /*****************************************************/
    /**
     * Render the section (Header or Footer)
     *
     * @param int $part Section which should be rendered: self::PART_COMPLETE, self::PART_HEADER or self::PART_FOOTER
     * @return string Content of rendered section
     */
    public function render($part = self::PART_COMPLETE)
    {
        $this->prepareRendering();
        list($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs) = $this->renderJavaScriptAndCss();
        $metaTags = implode(LF, $this->metaTags);
        $markerArray = $this->getPreparedMarkerArray($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs, $metaTags);
        $template = $this->getTemplateForPart($part);

        // The page renderer needs a full reset, even when only rendering one part of the page
        // This means that you can only register footer files *after* the header has been already rendered.
        // In case you render the footer part first, header files can only be added *after* the footer has been rendered
        $this->reset();
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        return trim($templateService->substituteMarkerArray($template, $markerArray, '###|###'));
    }

    /**
     * Render the page but not the JavaScript and CSS Files
     *
     * @param string $substituteHash The hash that is used for the placehoder markers
     * @access private
     * @return string Content of rendered section
     */
    public function renderPageWithUncachedObjects($substituteHash)
    {
        $this->prepareRendering();
        $markerArray = $this->getPreparedMarkerArrayForPageWithUncachedObjects($substituteHash);
        $template = $this->getTemplateForPart(self::PART_COMPLETE);
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        return trim($templateService->substituteMarkerArray($template, $markerArray, '###|###'));
    }

    /**
     * Renders the JavaScript and CSS files that have been added during processing
     * of uncached content objects (USER_INT, COA_INT)
     *
     * @param string $cachedPageContent
     * @param string $substituteHash The hash that is used for the placehoder markers
     * @access private
     * @return string
     */
    public function renderJavaScriptAndCssForProcessingOfUncachedContentObjects($cachedPageContent, $substituteHash)
    {
        $this->prepareRendering();
        list($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs) = $this->renderJavaScriptAndCss();
        $title = $this->title ? str_replace('|', htmlspecialchars($this->title), $this->titleTag) : '';
        $markerArray = [
            '<!-- ###TITLE' . $substituteHash . '### -->' => $title,
            '<!-- ###CSS_LIBS' . $substituteHash . '### -->' => $cssLibs,
            '<!-- ###CSS_INCLUDE' . $substituteHash . '### -->' => $cssFiles,
            '<!-- ###CSS_INLINE' . $substituteHash . '### -->' => $cssInline,
            '<!-- ###JS_INLINE' . $substituteHash . '### -->' => $jsInline,
            '<!-- ###JS_INCLUDE' . $substituteHash . '### -->' => $jsFiles,
            '<!-- ###JS_LIBS' . $substituteHash . '### -->' => $jsLibs,
            '<!-- ###META' . $substituteHash . '### -->' => implode(LF, $this->metaTags),
            '<!-- ###HEADERDATA' . $substituteHash . '### -->' => implode(LF, $this->headerData),
            '<!-- ###FOOTERDATA' . $substituteHash . '### -->' => implode(LF, $this->footerData),
            '<!-- ###JS_LIBS_FOOTER' . $substituteHash . '### -->' => $jsFooterLibs,
            '<!-- ###JS_INCLUDE_FOOTER' . $substituteHash . '### -->' => $jsFooterFiles,
            '<!-- ###JS_INLINE_FOOTER' . $substituteHash . '### -->' => $jsFooterInline
        ];
        foreach ($markerArray as $placeHolder => $content) {
            $cachedPageContent = str_replace($placeHolder, $content, $cachedPageContent);
        }
        $this->reset();
        return $cachedPageContent;
    }

    /**
     * Remove ending slashes from static header block
     * if the page is being rendered as html (not xhtml)
     * and define property $this->endingSlash for further use
     */
    protected function prepareRendering()
    {
        if ($this->getRenderXhtml()) {
            $this->endingSlash = ' /';
        } else {
            $this->metaCharsetTag = str_replace(' />', '>', $this->metaCharsetTag);
            $this->baseUrlTag = str_replace(' />', '>', $this->baseUrlTag);
            $this->shortcutTag = str_replace(' />', '>', $this->shortcutTag);
            $this->endingSlash = '';
        }
    }

    /**
     * Renders all JavaScript and CSS
     *
     * @return array<string>
     */
    protected function renderJavaScriptAndCss()
    {
        $this->executePreRenderHook();
        $mainJsLibs = $this->renderMainJavaScriptLibraries();
        if ($this->concatenateFiles || $this->concatenateJavascript || $this->concatenateCss) {
            // Do the file concatenation
            $this->doConcatenate();
        }
        if ($this->compressCss || $this->compressJavascript) {
            // Do the file compression
            $this->doCompress();
        }
        $this->executeRenderPostTransformHook();
        $cssLibs = $this->renderCssLibraries();
        $cssFiles = $this->renderCssFiles();
        $cssInline = $this->renderCssInline();
        list($jsLibs, $jsFooterLibs) = $this->renderAdditionalJavaScriptLibraries();
        list($jsFiles, $jsFooterFiles) = $this->renderJavaScriptFiles();
        list($jsInline, $jsFooterInline) = $this->renderInlineJavaScript();
        $jsLibs = $mainJsLibs . $jsLibs;
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterLibs = $jsLibs . LF . $jsFooterLibs;
            $jsLibs = '';
            $jsFooterFiles = $jsFiles . LF . $jsFooterFiles;
            $jsFiles = '';
            $jsFooterInline = $jsInline . LF . $jsFooterInline;
            $jsInline = '';
        }
        $this->executePostRenderHook($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs);
        return [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs];
    }

    /**
     * Fills the marker array with the given strings and trims each value
     *
     * @param $jsLibs string
     * @param $jsFiles string
     * @param $jsFooterFiles string
     * @param $cssLibs string
     * @param $cssFiles string
     * @param $jsInline string
     * @param $cssInline string
     * @param $jsFooterInline string
     * @param $jsFooterLibs string
     * @param $metaTags string
     * @return array Marker array
     */
    protected function getPreparedMarkerArray($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs, $metaTags)
    {
        $markerArray = [
            'XMLPROLOG_DOCTYPE' => $this->xmlPrologAndDocType,
            'HTMLTAG' => $this->htmlTag,
            'HEADTAG' => $this->headTag,
            'METACHARSET' => $this->charSet ? str_replace('|', htmlspecialchars($this->charSet), $this->metaCharsetTag) : '',
            'INLINECOMMENT' => $this->inlineComments ? LF . LF . '<!-- ' . LF . implode(LF, $this->inlineComments) . '-->' . LF . LF : '',
            'BASEURL' => $this->baseUrl ? str_replace('|', $this->baseUrl, $this->baseUrlTag) : '',
            'SHORTCUT' => $this->favIcon ? sprintf($this->shortcutTag, htmlspecialchars($this->favIcon), $this->iconMimeType) : '',
            'CSS_LIBS' => $cssLibs,
            'CSS_INCLUDE' => $cssFiles,
            'CSS_INLINE' => $cssInline,
            'JS_INLINE' => $jsInline,
            'JS_INCLUDE' => $jsFiles,
            'JS_LIBS' => $jsLibs,
            'TITLE' => $this->title ? str_replace('|', htmlspecialchars($this->title), $this->titleTag) : '',
            'META' => $metaTags,
            'HEADERDATA' => $this->headerData ? implode(LF, $this->headerData) : '',
            'FOOTERDATA' => $this->footerData ? implode(LF, $this->footerData) : '',
            'JS_LIBS_FOOTER' => $jsFooterLibs,
            'JS_INCLUDE_FOOTER' => $jsFooterFiles,
            'JS_INLINE_FOOTER' => $jsFooterInline,
            'BODY' => $this->bodyContent
        ];
        $markerArray = array_map('trim', $markerArray);
        return $markerArray;
    }

    /**
     * Fills the marker array with the given strings and trims each value
     *
     * @param string $substituteHash The hash that is used for the placehoder markers
     * @return array Marker array
     */
    protected function getPreparedMarkerArrayForPageWithUncachedObjects($substituteHash)
    {
        $markerArray = [
            'XMLPROLOG_DOCTYPE' => $this->xmlPrologAndDocType,
            'HTMLTAG' => $this->htmlTag,
            'HEADTAG' => $this->headTag,
            'METACHARSET' => $this->charSet ? str_replace('|', htmlspecialchars($this->charSet), $this->metaCharsetTag) : '',
            'INLINECOMMENT' => $this->inlineComments ? LF . LF . '<!-- ' . LF . implode(LF, $this->inlineComments) . '-->' . LF . LF : '',
            'BASEURL' => $this->baseUrl ? str_replace('|', $this->baseUrl, $this->baseUrlTag) : '',
            'SHORTCUT' => $this->favIcon ? sprintf($this->shortcutTag, htmlspecialchars($this->favIcon), $this->iconMimeType) : '',
            'META' => '<!-- ###META' . $substituteHash . '### -->',
            'BODY' => $this->bodyContent,
            'TITLE' => '<!-- ###TITLE' . $substituteHash . '### -->',
            'CSS_LIBS' => '<!-- ###CSS_LIBS' . $substituteHash . '### -->',
            'CSS_INCLUDE' => '<!-- ###CSS_INCLUDE' . $substituteHash . '### -->',
            'CSS_INLINE' => '<!-- ###CSS_INLINE' . $substituteHash . '### -->',
            'JS_INLINE' => '<!-- ###JS_INLINE' . $substituteHash . '### -->',
            'JS_INCLUDE' => '<!-- ###JS_INCLUDE' . $substituteHash . '### -->',
            'JS_LIBS' => '<!-- ###JS_LIBS' . $substituteHash . '### -->',
            'HEADERDATA' => '<!-- ###HEADERDATA' . $substituteHash . '### -->',
            'FOOTERDATA' => '<!-- ###FOOTERDATA' . $substituteHash . '### -->',
            'JS_LIBS_FOOTER' => '<!-- ###JS_LIBS_FOOTER' . $substituteHash . '### -->',
            'JS_INCLUDE_FOOTER' => '<!-- ###JS_INCLUDE_FOOTER' . $substituteHash . '### -->',
            'JS_INLINE_FOOTER' => '<!-- ###JS_INLINE_FOOTER' . $substituteHash . '### -->'
        ];
        $markerArray = array_map('trim', $markerArray);
        return $markerArray;
    }

    /**
     * Reads the template file and returns the requested part as string
     *
     * @param int $part
     * @return string
     */
    protected function getTemplateForPart($part)
    {
        $templateFile = GeneralUtility::getFileAbsFileName($this->templateFile);
        if (is_file($templateFile)) {
            $template = file_get_contents($templateFile);
            if ($this->removeLineBreaksFromTemplate) {
                $template = strtr($template, [LF => '', CR => '']);
            }
            if ($part !== self::PART_COMPLETE) {
                $templatePart = explode('###BODY###', $template);
                $template = $templatePart[$part - 1];
            }
        } else {
            $template = '';
        }
        return $template;
    }

    /**
     * Helper function for render the main JavaScript libraries,
     * currently: RequireJS, jQuery, ExtJS
     *
     * @return string Content with JavaScript libraries
     */
    protected function renderMainJavaScriptLibraries()
    {
        $out = '';

        // Include RequireJS
        if ($this->addRequireJs) {
            // load the paths of the requireJS configuration
            $out .= GeneralUtility::wrapJS('var require = ' . json_encode($this->requireJsConfig)) . LF;
            // directly after that, include the require.js file
            $out .= '<script src="' . $this->processJsFile($this->requireJsPath . 'require.js') . '" type="text/javascript"></script>' . LF;
        }

        // Include jQuery Core for each namespace, depending on the version and source
        if (!empty($this->jQueryVersions)) {
            foreach ($this->jQueryVersions as $namespace => $jQueryVersion) {
                $out .= $this->renderJqueryScriptTag($jQueryVersion['version'], $jQueryVersion['source'], $namespace);
            }
        }

        // Include extJS
        if ($this->addExtJS) {
            // Use the base adapter all the time
            $out .= '<script src="' . $this->processJsFile($this->extJsPath . 'adapter/ext-base' . ($this->enableExtJsDebug ? '-debug' : '') . '.js') . '" type="text/javascript"></script>' . LF;
            $out .= '<script src="' . $this->processJsFile($this->extJsPath . 'ext-all' . ($this->enableExtJsDebug ? '-debug' : '') . '.js') . '" type="text/javascript"></script>' . LF;
            // Add extJS localization
            // Load standard ISO mapping and modify for use with ExtJS
            $localeMap = $this->locales->getIsoMapping();
            $localeMap[''] = 'en';
            $localeMap['default'] = 'en';
            // Greek
            $localeMap['gr'] = 'el_GR';
            // Norwegian Bokmaal
            $localeMap['no'] = 'no_BO';
            // Swedish
            $localeMap['se'] = 'se_SV';
            $extJsLang = isset($localeMap[$this->lang]) ? $localeMap[$this->lang] : $this->lang;
            $extJsLocaleFile = $this->extJsPath . 'locale/ext-lang-' . $extJsLang . '.js';
            if (file_exists(PATH_site . $extJsLocaleFile)) {
                $out .= '<script src="' . $this->processJsFile($extJsLocaleFile) . '" type="text/javascript" charset="utf-8"></script>' . LF;
            }
            // Remove extjs from JScodeLibArray
            unset($this->jsFiles[$this->extJsPath . 'ext-all.js'], $this->jsFiles[$this->extJsPath . 'ext-all-debug.js']);
        }
        $this->loadJavaScriptLanguageStrings();
        if (TYPO3_MODE === 'BE') {
            $this->addAjaxUrlsToInlineSettings();
        }
        $inlineSettings = $this->inlineLanguageLabels ? 'TYPO3.lang = ' . json_encode($this->inlineLanguageLabels) . ';' : '';
        $inlineSettings .= $this->inlineSettings ? 'TYPO3.settings = ' . json_encode($this->inlineSettings) . ';' : '';
        if ($this->addExtJS) {
            // Set clear.gif, move it on top, add handler code
            $code = '';
            if (!empty($this->extOnReadyCode)) {
                foreach ($this->extOnReadyCode as $block) {
                    $code .= $block;
                }
            }
            $clearGifPath = GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Public/Images/clear.gif');
            $clearGifPath = htmlspecialchars(PathUtility::getAbsoluteWebPath($clearGifPath));
            $out .= $this->inlineJavascriptWrap[0] . '
				Ext.ns("TYPO3");
				Ext.BLANK_IMAGE_URL = "' . $clearGifPath . '";
				Ext.SSL_SECURE_URL = "' . $clearGifPath . '";' . LF
                . $inlineSettings
                . 'Ext.onReady(function() {'
                    . $code
                . ' });'
                . $this->inlineJavascriptWrap[1];
            $this->extOnReadyCode = [];
            // Include TYPO3.l10n object
            if (TYPO3_MODE === 'BE') {
                $out .= '<script src="' . $this->processJsFile('EXT:lang/Resources/Public/JavaScript/Typo3Lang.js') . '" type="text/javascript" charset="utf-8"></script>' . LF;
            }
            if ($this->extJScss) {
                if (isset($GLOBALS['TBE_STYLES']['extJS']['all'])) {
                    $this->addCssLibrary($GLOBALS['TBE_STYLES']['extJS']['all'], 'stylesheet', 'all', '', true);
                } else {
                    $this->addCssLibrary($this->extJsPath . 'resources/css/ext-all-notheme.css', 'stylesheet', 'all', '', true);
                }
            }
            if ($this->extJStheme) {
                if (isset($GLOBALS['TBE_STYLES']['extJS']['theme'])) {
                    $this->addCssLibrary($GLOBALS['TBE_STYLES']['extJS']['theme'], 'stylesheet', 'all', '', true);
                } else {
                    $this->addCssLibrary($this->extJsPath . 'resources/css/xtheme-blue.css', 'stylesheet', 'all', '', true);
                }
            }
        } else {
            // no extJS loaded, but still inline settings
            if ($inlineSettings !== '') {
                // make sure the global TYPO3 is available
                $inlineSettings = 'var TYPO3 = TYPO3 || {};' . CRLF . $inlineSettings;
                $out .= $this->inlineJavascriptWrap[0] . $inlineSettings . $this->inlineJavascriptWrap[1];
                // Add language module only if also jquery is guaranteed to be there
                if (TYPO3_MODE === 'BE' && !empty($this->jQueryVersions)) {
                    $this->loadRequireJsModule('TYPO3/CMS/Lang/Lang');
                }
            }
        }
        return $out;
    }

    /**
     * Load the language strings into JavaScript
     */
    protected function loadJavaScriptLanguageStrings()
    {
        if (!empty($this->inlineLanguageLabelFiles)) {
            foreach ($this->inlineLanguageLabelFiles as $languageLabelFile) {
                $this->includeLanguageFileForInline($languageLabelFile['fileRef'], $languageLabelFile['selectionPrefix'], $languageLabelFile['stripFromSelectionName'], $languageLabelFile['errorMode']);
            }
        }
        $this->inlineLanguageLabelFiles = [];
        // Convert settings back to UTF-8 since json_encode() only works with UTF-8:
        if ($this->getCharSet() && $this->getCharSet() !== 'utf-8' && is_array($this->inlineSettings)) {
            $this->convertCharsetRecursivelyToUtf8($this->inlineSettings, $this->getCharSet());
        }
    }

    /**
     * Small helper function to convert charsets for arrays into utf-8
     *
     * @param mixed $data given by reference (string/array usually)
     * @param string $fromCharset convert FROM this charset
     */
    protected function convertCharsetRecursivelyToUtf8(&$data, string $fromCharset)
    {
        foreach ($data as $key => $value) {
            if (is_array($data[$key])) {
                $this->convertCharsetRecursivelyToUtf8($data[$key], $fromCharset);
            } elseif (is_string($data[$key])) {
                $data[$key] = mb_convert_encoding($data[$key], 'utf-8', $fromCharset);
            }
        }
    }

    /**
     * Make URLs to all backend ajax handlers available as inline setting.
     */
    protected function addAjaxUrlsToInlineSettings()
    {
        $ajaxUrls = [];
        // Note: this method of adding Ajax URLs is @deprecated as of TYPO3 v8, and will be removed in TYPO3 v9
        foreach ($GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'] as $ajaxHandler => $_) {
            $ajaxUrls[$ajaxHandler] = BackendUtility::getAjaxUrl($ajaxHandler);
        }

        // also add the ajax-based routes
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        /** @var Router $router */
        $router = GeneralUtility::makeInstance(Router::class);
        $routes = $router->getRoutes();
        foreach ($routes as $routeIdentifier => $route) {
            if ($route->getOption('ajax')) {
                $uri = (string)$uriBuilder->buildUriFromRoute($routeIdentifier);
                // use the shortened value in order to use this in JavaScript
                $routeIdentifier = str_replace('ajax_', '', $routeIdentifier);
                $ajaxUrls[$routeIdentifier] = $uri;
            }
        }

        $this->inlineSettings['ajaxUrls'] = $ajaxUrls;
    }

    /**
     * Renders the HTML script tag for the given jQuery version.
     *
     * @param string $version The jQuery version that should be included, either "latest" or any available version
     * @param string $source The location of the jQuery source, can be "local", "google", "msn" or "jquery
     * @param string $namespace The namespace in which the jQuery object of the specific version should be stored
     * @return string
     */
    protected function renderJqueryScriptTag($version, $source, $namespace)
    {
        switch (true) {
            case isset($this->jQueryCdnUrls[$source]):
                if ($this->enableJqueryDebug) {
                    $minifyPart = '';
                } else {
                    $minifyPart = '.min';
                }
                $jQueryFileName = sprintf($this->jQueryCdnUrls[$source], $version, $minifyPart);
                break;
            case $source === 'local':
                $jQueryFileName = $this->jQueryPath . 'jquery-' . rawurlencode($version);
                if ($this->enableJqueryDebug) {
                    $jQueryFileName .= '.js';
                } else {
                    $jQueryFileName .= '.min.js';
                }
                $jQueryFileName = $this->processJsFile($jQueryFileName);
                break;
            default:
                $jQueryFileName = $source;
        }
        // Include the jQuery Core
        $scriptTag = '<script src="' . htmlspecialchars($jQueryFileName) . '" type="text/javascript"></script>' . LF;
        // Set the noConflict mode to be available via "TYPO3.jQuery" in all installations
        switch ($namespace) {
            case self::JQUERY_NAMESPACE_DEFAULT_NOCONFLICT:
                $scriptTag .= GeneralUtility::wrapJS('jQuery.noConflict();') . LF;
                break;
            case self::JQUERY_NAMESPACE_NONE:
                break;
            case self::JQUERY_NAMESPACE_DEFAULT:

            default:
                $scriptTag .= GeneralUtility::wrapJS('var TYPO3 = TYPO3 || {}; TYPO3.' . $namespace . ' = jQuery.noConflict(true); var $ = TYPO3.' . $namespace . ';') . LF;
        }
        return $scriptTag;
    }

    /**
     * Render CSS library files
     *
     * @return string
     */
    protected function renderCssLibraries()
    {
        $cssFiles = '';
        if (!empty($this->cssLibs)) {
            foreach ($this->cssLibs as $file => $properties) {
                $file = $this->getStreamlinedFileName($file);
                $tag = '<link rel="' . htmlspecialchars($properties['rel'])
                    . '" type="text/css" href="' . htmlspecialchars($file)
                    . '" media="' . htmlspecialchars($properties['media']) . '"'
                    . ($properties['title'] ? ' title="' . htmlspecialchars($properties['title']) . '"' : '')
                    . $this->endingSlash . '>';
                if ($properties['allWrap']) {
                    $wrapArr = explode($properties['splitChar'] ?: '|', $properties['allWrap'], 2);
                    $tag = $wrapArr[0] . $tag . $wrapArr[1];
                }
                $tag .= LF;
                if ($properties['forceOnTop']) {
                    $cssFiles = $tag . $cssFiles;
                } else {
                    $cssFiles .= $tag;
                }
            }
        }
        return $cssFiles;
    }

    /**
     * Render CSS files
     *
     * @return string
     */
    protected function renderCssFiles()
    {
        $cssFiles = '';
        if (!empty($this->cssFiles)) {
            foreach ($this->cssFiles as $file => $properties) {
                $file = $this->getStreamlinedFileName($file);
                $tag = '<link rel="' . htmlspecialchars($properties['rel'])
                    . '" type="text/css" href="' . htmlspecialchars($file)
                    . '" media="' . htmlspecialchars($properties['media']) . '"'
                    . ($properties['title'] ? ' title="' . htmlspecialchars($properties['title']) . '"' : '')
                    . $this->endingSlash . '>';
                if ($properties['allWrap']) {
                    $wrapArr = explode($properties['splitChar'] ?: '|', $properties['allWrap'], 2);
                    $tag = $wrapArr[0] . $tag . $wrapArr[1];
                }
                $tag .= LF;
                if ($properties['forceOnTop']) {
                    $cssFiles = $tag . $cssFiles;
                } else {
                    $cssFiles .= $tag;
                }
            }
        }
        return $cssFiles;
    }

    /**
     * Render inline CSS
     *
     * @return string
     */
    protected function renderCssInline()
    {
        $cssInline = '';
        if (!empty($this->cssInline)) {
            foreach ($this->cssInline as $name => $properties) {
                $cssCode = '/*' . htmlspecialchars($name) . '*/' . LF . $properties['code'] . LF;
                if ($properties['forceOnTop']) {
                    $cssInline = $cssCode . $cssInline;
                } else {
                    $cssInline .= $cssCode;
                }
            }
            $cssInline = $this->inlineCssWrap[0] . $cssInline . $this->inlineCssWrap[1];
        }
        return $cssInline;
    }

    /**
     * Render JavaScipt libraries
     *
     * @return array<string> jsLibs and jsFooterLibs strings
     */
    protected function renderAdditionalJavaScriptLibraries()
    {
        $jsLibs = '';
        $jsFooterLibs = '';
        if (!empty($this->jsLibs)) {
            foreach ($this->jsLibs as $properties) {
                $properties['file'] = $this->getStreamlinedFileName($properties['file']);
                $async = ($properties['async']) ? ' async="async"' : '';
                $integrity = ($properties['integrity']) ? ' integrity="' . htmlspecialchars($properties['integrity']) . '" crossorigin="anonymous"' : '';
                $tag = '<script src="' . htmlspecialchars($properties['file']) . '" type="' . htmlspecialchars($properties['type']) . '"' . $async . $integrity . '></script>';
                if ($properties['allWrap']) {
                    $wrapArr = explode($properties['splitChar'] ?: '|', $properties['allWrap'], 2);
                    $tag = $wrapArr[0] . $tag . $wrapArr[1];
                }
                $tag .= LF;
                if ($properties['forceOnTop']) {
                    if ($properties['section'] === self::PART_HEADER) {
                        $jsLibs = $tag . $jsLibs;
                    } else {
                        $jsFooterLibs = $tag . $jsFooterLibs;
                    }
                } else {
                    if ($properties['section'] === self::PART_HEADER) {
                        $jsLibs .= $tag;
                    } else {
                        $jsFooterLibs .= $tag;
                    }
                }
            }
        }
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterLibs = $jsLibs . LF . $jsFooterLibs;
            $jsLibs = '';
        }
        return [$jsLibs, $jsFooterLibs];
    }

    /**
     * Render JavaScript files
     *
     * @return array<string> jsFiles and jsFooterFiles strings
     */
    protected function renderJavaScriptFiles()
    {
        $jsFiles = '';
        $jsFooterFiles = '';
        if (!empty($this->jsFiles)) {
            foreach ($this->jsFiles as $file => $properties) {
                $file = $this->getStreamlinedFileName($file);
                $async = ($properties['async']) ? ' async="async"' : '';
                $integrity = ($properties['integrity']) ? ' integrity="' . htmlspecialchars($properties['integrity']) . '" crossorigin="anonymous"' : '';
                $tag = '<script src="' . htmlspecialchars($file) . '" type="' . htmlspecialchars($properties['type']) . '"' . $async . $integrity . '></script>';
                if ($properties['allWrap']) {
                    $wrapArr = explode($properties['splitChar'] ?: '|', $properties['allWrap'], 2);
                    $tag = $wrapArr[0] . $tag . $wrapArr[1];
                }
                $tag .= LF;
                if ($properties['forceOnTop']) {
                    if ($properties['section'] === self::PART_HEADER) {
                        $jsFiles = $tag . $jsFiles;
                    } else {
                        $jsFooterFiles = $tag . $jsFooterFiles;
                    }
                } else {
                    if ($properties['section'] === self::PART_HEADER) {
                        $jsFiles .= $tag;
                    } else {
                        $jsFooterFiles .= $tag;
                    }
                }
            }
        }
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterFiles = $jsFiles . $jsFooterFiles;
            $jsFiles = '';
        }
        return [$jsFiles, $jsFooterFiles];
    }

    /**
     * Render inline JavaScript
     *
     * @return array<string> jsInline and jsFooterInline string
     */
    protected function renderInlineJavaScript()
    {
        $jsInline = '';
        $jsFooterInline = '';
        if (!empty($this->jsInline)) {
            foreach ($this->jsInline as $name => $properties) {
                $jsCode = '/*' . htmlspecialchars($name) . '*/' . LF . $properties['code'] . LF;
                if ($properties['forceOnTop']) {
                    if ($properties['section'] === self::PART_HEADER) {
                        $jsInline = $jsCode . $jsInline;
                    } else {
                        $jsFooterInline = $jsCode . $jsFooterInline;
                    }
                } else {
                    if ($properties['section'] === self::PART_HEADER) {
                        $jsInline .= $jsCode;
                    } else {
                        $jsFooterInline .= $jsCode;
                    }
                }
            }
        }
        if ($jsInline) {
            $jsInline = $this->inlineJavascriptWrap[0] . $jsInline . $this->inlineJavascriptWrap[1];
        }
        if ($jsFooterInline) {
            $jsFooterInline = $this->inlineJavascriptWrap[0] . $jsFooterInline . $this->inlineJavascriptWrap[1];
        }
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterInline = $jsInline . $jsFooterInline;
            $jsInline = '';
        }
        return [$jsInline, $jsFooterInline];
    }

    /**
     * Include language file for inline usage
     *
     * @param string $fileRef
     * @param string $selectionPrefix
     * @param string $stripFromSelectionName
     * @param int $errorMode
     * @throws \RuntimeException
     */
    protected function includeLanguageFileForInline($fileRef, $selectionPrefix = '', $stripFromSelectionName = '', $errorMode = 0)
    {
        if (!isset($this->lang) || !isset($this->charSet)) {
            throw new \RuntimeException('Language and character encoding are not set.', 1284906026);
        }
        $labelsFromFile = [];
        $allLabels = $this->readLLfile($fileRef, $errorMode);
        if ($allLabels !== false) {
            // Merge language specific translations:
            if ($this->lang !== 'default' && isset($allLabels[$this->lang])) {
                $labels = array_merge($allLabels['default'], $allLabels[$this->lang]);
            } else {
                $labels = $allLabels['default'];
            }
            // Iterate through all locallang labels:
            foreach ($labels as $label => $value) {
                // If $selectionPrefix is set, only respect labels that start with $selectionPrefix
                if ($selectionPrefix === '' || strpos($label, $selectionPrefix) === 0) {
                    // Remove substring $stripFromSelectionName from label
                    $label = str_replace($stripFromSelectionName, '', $label);
                    $labelsFromFile[$label] = $value;
                }
            }
            $this->inlineLanguageLabels = array_merge($this->inlineLanguageLabels, $labelsFromFile);
        }
    }

    /**
     * Reads a locallang file.
     *
     * @param string $fileRef Reference to a relative filename to include.
     * @param int $errorMode Error mode (when file could not be found): 0 - syslog entry, 1 - do nothing, 2 - throw an exception
     * @return array Returns the $LOCAL_LANG array found in the file. If no array found, returns empty array.
     */
    protected function readLLfile($fileRef, $errorMode = 0)
    {
        /** @var $languageFactory LocalizationFactory */
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);

        if ($this->lang !== 'default') {
            $languages = array_reverse($this->languageDependencies);
            // At least we need to have English
            if (empty($languages)) {
                $languages[] = 'default';
            }
        } else {
            $languages = ['default'];
        }

        $localLanguage = [];
        foreach ($languages as $language) {
            $tempLL = $languageFactory->getParsedData($fileRef, $language, $this->charSet, $errorMode);

            $localLanguage['default'] = $tempLL['default'];
            if (!isset($localLanguage[$this->lang])) {
                $localLanguage[$this->lang] = $localLanguage['default'];
            }
            if ($this->lang !== 'default' && isset($tempLL[$language])) {
                // Merge current language labels onto labels from previous language
                // This way we have a labels with fall back applied
                \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($localLanguage[$this->lang], $tempLL[$language], true, false);
            }
        }

        return $localLanguage;
    }

    /*****************************************************/
    /*                                                   */
    /*  Tools                                            */
    /*                                                   */
    /*****************************************************/
    /**
     * Concatenate files into one file
     * registered handler
     */
    protected function doConcatenate()
    {
        $this->doConcatenateCss();
        $this->doConcatenateJavaScript();
    }

    /**
     * Concatenate JavaScript files according to the configuration.
     */
    protected function doConcatenateJavaScript()
    {
        if ($this->concatenateFiles || $this->concatenateJavascript) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsConcatenateHandler'])) {
                // use external concatenation routine
                $params = [
                    'jsLibs' => &$this->jsLibs,
                    'jsFiles' => &$this->jsFiles,
                    'jsFooterFiles' => &$this->jsFooterFiles,
                    'headerData' => &$this->headerData,
                    'footerData' => &$this->footerData
                ];
                GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsConcatenateHandler'], $params, $this);
            } else {
                $this->jsLibs = $this->getCompressor()->concatenateJsFiles($this->jsLibs);
                $this->jsFiles = $this->getCompressor()->concatenateJsFiles($this->jsFiles);
                $this->jsFooterFiles = $this->getCompressor()->concatenateJsFiles($this->jsFooterFiles);
            }
        }
    }

    /**
     * Concatenate CSS files according to configuration.
     */
    protected function doConcatenateCss()
    {
        if ($this->concatenateFiles || $this->concatenateCss) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssConcatenateHandler'])) {
                // use external concatenation routine
                $params = [
                    'cssFiles' => &$this->cssFiles,
                    'cssLibs' => &$this->cssLibs,
                    'headerData' => &$this->headerData,
                    'footerData' => &$this->footerData
                ];
                GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssConcatenateHandler'], $params, $this);
            } else {
                $cssOptions = [];
                if (TYPO3_MODE === 'BE') {
                    $cssOptions = ['baseDirectories' => $GLOBALS['TBE_TEMPLATE']->getSkinStylesheetDirectories()];
                }
                $this->cssLibs = $this->getCompressor()->concatenateCssFiles($this->cssLibs, $cssOptions);
                $this->cssFiles = $this->getCompressor()->concatenateCssFiles($this->cssFiles, $cssOptions);
            }
        }
    }

    /**
     * Compresses inline code
     */
    protected function doCompress()
    {
        $this->doCompressJavaScript();
        $this->doCompressCss();
    }

    /**
     * Compresses CSS according to configuration.
     */
    protected function doCompressCss()
    {
        if ($this->compressCss) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssCompressHandler'])) {
                // Use external compression routine
                $params = [
                    'cssInline' => &$this->cssInline,
                    'cssFiles' => &$this->cssFiles,
                    'cssLibs' => &$this->cssLibs,
                    'headerData' => &$this->headerData,
                    'footerData' => &$this->footerData
                ];
                GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssCompressHandler'], $params, $this);
            } else {
                $this->cssLibs = $this->getCompressor()->compressCssFiles($this->cssLibs);
                $this->cssFiles = $this->getCompressor()->compressCssFiles($this->cssFiles);
            }
        }
    }

    /**
     * Compresses JavaScript according to configuration.
     */
    protected function doCompressJavaScript()
    {
        if ($this->compressJavascript) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler'])) {
                // Use external compression routine
                $params = [
                    'jsInline' => &$this->jsInline,
                    'jsFooterInline' => &$this->jsFooterInline,
                    'jsLibs' => &$this->jsLibs,
                    'jsFiles' => &$this->jsFiles,
                    'jsFooterFiles' => &$this->jsFooterFiles,
                    'headerData' => &$this->headerData,
                    'footerData' => &$this->footerData
                ];
                GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler'], $params, $this);
            } else {
                // Traverse the arrays, compress files
                if (!empty($this->jsInline)) {
                    foreach ($this->jsInline as $name => $properties) {
                        if ($properties['compress']) {
                            $error = '';
                            $this->jsInline[$name]['code'] = GeneralUtility::minifyJavaScript($properties['code'], $error);
                            if ($error) {
                                $this->compressError .= 'Error with minify JS Inline Block "' . $name . '": ' . $error . LF;
                            }
                        }
                    }
                }
                $this->jsLibs = $this->getCompressor()->compressJsFiles($this->jsLibs);
                $this->jsFiles = $this->getCompressor()->compressJsFiles($this->jsFiles);
                $this->jsFooterFiles = $this->getCompressor()->compressJsFiles($this->jsFooterFiles);
            }
        }
    }

    /**
     * Returns instance of \TYPO3\CMS\Core\Resource\ResourceCompressor
     *
     * @return \TYPO3\CMS\Core\Resource\ResourceCompressor
     */
    protected function getCompressor()
    {
        if ($this->compressor === null) {
            $this->compressor = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceCompressor::class);
        }
        return $this->compressor;
    }

    /**
     * Processes a Javascript file dependent on the current context
     *
     * Adds the version number for Frontend, compresses the file for Backend
     *
     * @param string $filename Filename
     * @return string New filename
     */
    protected function processJsFile($filename)
    {
        $filename = $this->getStreamlinedFileName($filename, false);
        if ($this->compressJavascript) {
            $filename = $this->getCompressor()->compressJsFile($filename);
        } elseif (TYPO3_MODE === 'FE') {
            $filename = GeneralUtility::createVersionNumberedFilename($filename);
        }
        return $this->getAbsoluteWebPath($filename);
    }

    /**
     * This function acts as a wrapper to allow relative and paths starting with EXT: to be dealt with
     * in this very case to always return the absolute web path to be included directly before output.
     *
     * This is mainly added so the EXT: syntax can be resolved for PageRenderer in one central place,
     * and hopefully removed in the future by one standard API call.
     *
     * @param string $file the filename to process
     * @param bool $prepareForOutput whether the file should be prepared as version numbered file and prefixed as absolute webpath
     * @return string
     * @internal
     */
    protected function getStreamlinedFileName($file, $prepareForOutput = true)
    {
        if (strpos($file, 'EXT:') === 0) {
            $file = GeneralUtility::getFileAbsFileName($file);
            // as the path is now absolute, make it "relative" to the current script to stay compatible
            $file = PathUtility::getRelativePathTo($file);
            $file = rtrim($file, '/');
        } else {
            $file = GeneralUtility::resolveBackPath($file);
        }
        if ($prepareForOutput) {
            $file = GeneralUtility::createVersionNumberedFilename($file);
            $file = $this->getAbsoluteWebPath($file);
        }
        return $file;
    }

    /**
     * Gets absolute web path of filename for backend disposal.
     * Resolving the absolute path in the frontend with conflict with
     * applying config.absRefPrefix in frontend rendering process.
     *
     * @param string $file
     * @return string
     * @see TypoScriptFrontendController::setAbsRefPrefix()
     */
    protected function getAbsoluteWebPath(string $file): string
    {
        if (TYPO3_MODE === 'FE') {
            return $file;
        }
        return PathUtility::getAbsoluteWebPath($file);
    }

    /**
     * Returns global frontend controller
     *
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Returns global language service instance
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /*****************************************************/
    /*                                                   */
    /*  Hooks                                            */
    /*                                                   */
    /*****************************************************/
    /**
     * Execute PreRenderHook for possible manipulation
     */
    protected function executePreRenderHook()
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'])) {
            $params = [
                'jsLibs' => &$this->jsLibs,
                'jsFooterLibs' => &$this->jsFooterLibs,
                'jsFiles' => &$this->jsFiles,
                'jsFooterFiles' => &$this->jsFooterFiles,
                'cssLibs' => &$this->cssLibs,
                'cssFiles' => &$this->cssFiles,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
                'jsInline' => &$this->jsInline,
                'jsFooterInline' => &$this->jsFooterInline,
                'cssInline' => &$this->cssInline
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'] as $hook) {
                GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
    }

    /**
     * PostTransform for possible manipulation of concatenated and compressed files
     */
    protected function executeRenderPostTransformHook()
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform'])) {
            $params = [
                'jsLibs' => &$this->jsLibs,
                'jsFooterLibs' => &$this->jsFooterLibs,
                'jsFiles' => &$this->jsFiles,
                'jsFooterFiles' => &$this->jsFooterFiles,
                'cssLibs' => &$this->cssLibs,
                'cssFiles' => &$this->cssFiles,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
                'jsInline' => &$this->jsInline,
                'jsFooterInline' => &$this->jsFooterInline,
                'cssInline' => &$this->cssInline
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform'] as $hook) {
                GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
    }

    /**
     * Execute postRenderHook for possible manipulation
     *
     * @param $jsLibs string
     * @param $jsFiles string
     * @param $jsFooterFiles string
     * @param $cssLibs string
     * @param $cssFiles string
     * @param $jsInline string
     * @param $cssInline string
     * @param $jsFooterInline string
     * @param $jsFooterLibs string
     */
    protected function executePostRenderHook(&$jsLibs, &$jsFiles, &$jsFooterFiles, &$cssLibs, &$cssFiles, &$jsInline, &$cssInline, &$jsFooterInline, &$jsFooterLibs)
    {
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'])) {
            $params = [
                'jsLibs' => &$jsLibs,
                'jsFiles' => &$jsFiles,
                'jsFooterFiles' => &$jsFooterFiles,
                'cssLibs' => &$cssLibs,
                'cssFiles' => &$cssFiles,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
                'jsInline' => &$jsInline,
                'cssInline' => &$cssInline,
                'xmlPrologAndDocType' => &$this->xmlPrologAndDocType,
                'htmlTag' => &$this->htmlTag,
                'headTag' => &$this->headTag,
                'charSet' => &$this->charSet,
                'metaCharsetTag' => &$this->metaCharsetTag,
                'shortcutTag' => &$this->shortcutTag,
                'inlineComments' => &$this->inlineComments,
                'baseUrl' => &$this->baseUrl,
                'baseUrlTag' => &$this->baseUrlTag,
                'favIcon' => &$this->favIcon,
                'iconMimeType' => &$this->iconMimeType,
                'titleTag' => &$this->titleTag,
                'title' => &$this->title,
                'metaTags' => &$this->metaTags,
                'jsFooterInline' => &$jsFooterInline,
                'jsFooterLibs' => &$jsFooterLibs,
                'bodyContent' => &$this->bodyContent
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'] as $hook) {
                GeneralUtility::callUserFunction($hook, $params, $this);
            }
        }
    }
}
