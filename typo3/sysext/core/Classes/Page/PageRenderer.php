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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * TYPO3 pageRender class
 * This class render the HTML of a webpage, usable for BE and FE
 */
class PageRenderer implements \TYPO3\CMS\Core\SingletonInterface
{
    // Constants for the part to be rendered
    const PART_COMPLETE = 0;
    const PART_HEADER = 1;
    const PART_FOOTER = 2;
    // @deprecated will be removed in TYPO3 v10.0.
    // jQuery Core version that is shipped with TYPO3
    const JQUERY_VERSION_LATEST = '3.3.1';
    // jQuery namespace options
    // @deprecated will be removed in TYPO3 v10.0.
    const JQUERY_NAMESPACE_NONE = 'none';

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
     * @deprecated will be removed in TYPO3 v10.0, in favor of concatenateJavaScript and concatenateCss
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
     * META Tags added via the API
     *
     * @var array
     */
    protected $metaTagsByAPI = [];

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

    // Paths to contributed libraries

    /**
     * default path to the requireJS library, relative to the typo3/ directory
     * @var string
     */
    protected $requireJsPath = 'EXT:core/Resources/Public/JavaScript/Contrib/';

    /**
     * The local directory where one can find jQuery versions and plugins
     *
     * @var string
     * @deprecated will be removed in TYPO3 v10.0.
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
     * @deprecated will be removed in TYPO3 v10.0.
     */
    protected $jQueryVersions = [];

    /**
     * Array of jQuery version numbers shipped with the core
     *
     * @var array
     * @deprecated will be removed in TYPO3 v10.0.
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
    protected $enableJqueryDebug = false;

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
     * @var MetaTagManagerRegistry
     */
    protected $metaTagRegistry;

    /**
     * @var FrontendInterface
     */
    protected static $cache = null;

    /**
     * @param string $templateFile Declare the used template file. Omit this parameter will use default template
     */
    public function __construct($templateFile = '')
    {
        $this->reset();
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

        $this->metaTagRegistry = GeneralUtility::makeInstance(MetaTagManagerRegistry::class);
        $this->setMetaTag('name', 'generator', 'TYPO3 CMS');
    }

    /**
     * @param FrontendInterface $cache
     */
    public static function setCache(FrontendInterface $cache)
    {
        static::$cache = $cache;
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
        $this->metaTagsByAPI = [];
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
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0
     */
    public function enableConcatenateFiles()
    {
        trigger_error('This method will be removed in TYPO3 v10.0. Use concatenateCss() and concatenateJavascript() instead.', E_USER_DEPRECATED);
        $this->concatenateFiles = true;
    }

    /**
     * Disables concatenation of js and css files
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0
     */
    public function disableConcatenateFiles()
    {
        trigger_error('This method will be removed in TYPO3 v10.0. Use concatenateCss() and concatenateJavascript() instead.', E_USER_DEPRECATED);
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
        $this->concatenateCss = false;
        $this->concatenateJavascript = false;
        $this->removeLineBreaksFromTemplate = false;
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
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0
     */
    public function getConcatenateFiles()
    {
        trigger_error('This method will be removed in TYPO3 v10.0. Use concatenateCss() and concatenateJavascript() instead.', E_USER_DEPRECATED);
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     * @param string $meta Meta data (complete metatag)
     */
    public function addMetaTag($meta)
    {
        trigger_error('Method pageRenderer->addMetaTag will be removed with TYPO3 v10.0. Use pageRenderer->setMetaTag instead.', E_USER_DEPRECATED);
        if (!in_array($meta, $this->metaTags)) {
            $this->metaTags[] = $meta;
        }
    }

    /**
     * Sets a given meta tag
     *
     * @param string $type The type of the meta tag. Allowed values are property, name or http-equiv
     * @param string $name The name of the property to add
     * @param string $content The content of the meta tag
     * @param array $subProperties Subproperties of the meta tag (like e.g. og:image:width)
     * @param bool $replace Replace earlier set meta tag
     * @throws \InvalidArgumentException
     */
    public function setMetaTag(string $type, string $name, string $content, array $subProperties = [], $replace = true)
    {
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);
        if (!in_array($type, ['property', 'name', 'http-equiv'], true)) {
            throw new \InvalidArgumentException(
                'When setting a meta tag the only types allowed are property, name or http-equiv. "' . $type . '" given.',
                1496402460
            );
        }

        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $manager->addProperty($name, $content, $subProperties, $replace, $type);
    }

    /**
     * Returns the requested meta tag
     *
     * @param string $type
     * @param string $name
     *
     * @return array
     */
    public function getMetaTag(string $type, string $name): array
    {
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);

        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $propertyContent = $manager->getProperty($name, $type);

        if (!empty($propertyContent[0])) {
            return [
                'type' => $type,
                'name' => $name,
                'content' => $propertyContent[0]['content']
            ];
        }
        return [];
    }

    /**
     * Unset the requested meta tag
     *
     * @param string $type
     * @param string $name
     */
    public function removeMetaTag(string $type, string $name)
    {
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);

        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $manager->removeProperty($name, $type);
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
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     */
    public function addJsLibrary($name, $file, $type = 'text/javascript', $compress = false, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '')
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
                'defer' => $defer,
                'crossorigin' => $crossorigin,
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
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     */
    public function addJsFooterLibrary($name, $file, $type = 'text/javascript', $compress = false, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '')
    {
        if (!$type) {
            $type = 'text/javascript';
        }
        $name .= '_jsFooterLibrary';
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
                'defer' => $defer,
                'crossorigin' => $crossorigin,
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
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     */
    public function addJsFile($file, $type = 'text/javascript', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '')
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
                'defer' => $defer,
                'crossorigin' => $crossorigin,
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
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     */
    public function addJsFooterFile($file, $type = 'text/javascript', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '')
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
                'defer' => $defer,
                'crossorigin' => $crossorigin,
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
     * @param bool $inline
     */
    public function addCssFile($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $inline = false)
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
                'splitChar' => $splitChar,
                'inline' => $inline
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
     * @param bool $inline
     */
    public function addCssLibrary($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $inline = false)
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
                'splitChar' => $splitChar,
                'inline' => $inline
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
     * @param bool $isCoreCall if set, then the deprecation message is suppressed.
     * @throws \UnexpectedValueException
     * @deprecated since TYPO3 v9.5, will be removed in TYPO3 v10.0. This is still in use in deprecated code, so it does not trigger a deprecation warning.
     */
    public function loadJquery($version = null, $source = null, $namespace = self::JQUERY_NAMESPACE_NONE, bool $isCoreCall = false)
    {
        if (!$isCoreCall) {
            trigger_error('PageRenderer->loadJquery() will be removed in TYPO3 v10.0. Use a package manager for frontend or custom jQuery files instead.', E_USER_DEPRECATED);
        }
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
        /** @var FrontendInterface $cache */
        $cache = static::$cache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('assets');
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
            $requireJsConfig['urlArgs'] = 'bust=' . GeneralUtility::hmac(TYPO3_version . Environment::getProjectPath());
        }
        $corePath = ExtensionManagementUtility::extPath('core', 'Resources/Public/JavaScript/Contrib/');
        $corePath = PathUtility::getAbsoluteWebPath($corePath);
        // first, load all paths for the namespaces, and configure contrib libs.
        $requireJsConfig['paths'] = [
            'jquery' => $corePath . '/jquery/jquery',
            'jquery-ui' => $corePath . 'jquery-ui',
            'datatables' => $corePath . 'jquery.dataTables',
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
        $requireJsConfig['waitSeconds']  = 30;
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
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RequireJS']['postInitializationModules'] ?? false)) {
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
     * Adds Javascript Inline Label. This will occur in TYPO3.lang - object
     * The label can be used in scripts with TYPO3.lang.<key>
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
     *
     * @param array $array
     * @param bool $parseWithLanguageService
     */
    public function addInlineLanguageLabelArray(array $array, $parseWithLanguageService = null)
    {
        if ($parseWithLanguageService === true) {
            trigger_error('PageRenderer::addInlineLanguageLabelArray() second method argument set to true will not be supported anymore in TYPO3 v10.0.', E_USER_DEPRECATED);
            foreach ($array as $key => $value) {
                if (TYPO3_MODE === 'FE') {
                    $array[$key] = $this->getTypoScriptFrontendController()->sL($value);
                } else {
                    $array[$key] = $this->getLanguageService()->sL($value);
                }
            }
        } elseif ($parseWithLanguageService !== null) {
            trigger_error('PageRenderer::addInlineLanguageLabelArray() does not need a second method argument anymore, and will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        }

        $this->inlineLanguageLabels = array_merge($this->inlineLanguageLabels, $array);
    }

    /**
     * Gets labels to be used in JavaScript fetched from a locallang file.
     *
     * @param string $fileRef Input is a file-reference (see GeneralUtility::getFileAbsFileName). That file is expected to be a 'locallang.xlf' file containing a valid XML TYPO3 language structure.
     * @param string $selectionPrefix Prefix to select the correct labels (default: '')
     * @param string $stripFromSelectionName String to be removed from the label names in the output. (default: '')
     */
    public function addInlineLanguageLabelFile($fileRef, $selectionPrefix = '', $stripFromSelectionName = '')
    {
        $index = md5($fileRef . $selectionPrefix . $stripFromSelectionName);
        if ($fileRef && !isset($this->inlineLanguageLabelFiles[$index])) {
            $this->inlineLanguageLabelFiles[$index] = [
                'fileRef' => $fileRef,
                'selectionPrefix' => $selectionPrefix,
                'stripFromSelectionName' => $stripFromSelectionName
            ];
        }
    }

    /**
     * Adds Javascript Inline Setting. This will occur in TYPO3.settings - object
     * The label can be used in scripts with TYPO3.setting.<key>
     *
     * @param string $namespace
     * @param string $key
     * @param mixed $value
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
        $metaTags = implode(LF, array_merge($this->metaTags, $this->renderMetaTagsFromAPI()));
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
     * Renders metaTags based on tags added via the API
     *
     * @return array
     */
    protected function renderMetaTagsFromAPI()
    {
        $metaTags = [];
        $metaTagManagers = $this->metaTagRegistry->getAllManagers();
        try {
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_pages');
        } catch (NoSuchCacheException $e) {
            $cache = null;
        }

        foreach ($metaTagManagers as $manager => $managerObject) {
            $cacheIdentifier =  $this->getTypoScriptFrontendController()->newHash . '-metatag-' . $manager;

            $existingCacheEntry = false;
            if ($cache instanceof FrontendInterface && $properties = $cache->get($cacheIdentifier)) {
                $existingCacheEntry = true;
            } else {
                $properties = $managerObject->renderAllProperties();
            }

            if (!empty($properties)) {
                $metaTags[] = $properties;

                if ($cache instanceof FrontendInterface && !$existingCacheEntry && ($this->getTypoScriptFrontendController()->page['uid'] ?? false)) {
                    $cache->set(
                        $cacheIdentifier,
                        $properties,
                        ['pageId_' . $this->getTypoScriptFrontendController()->page['uid']],
                        $this->getTypoScriptFrontendController()->get_cache_timeout()
                    );
                }
            }
        }
        return $metaTags;
    }

    /**
     * Render the page but not the JavaScript and CSS Files
     *
     * @param string $substituteHash The hash that is used for the placehoder markers
     * @internal
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
     * @internal
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
            '<!-- ###META' . $substituteHash . '### -->' => implode(LF, array_merge($this->metaTags, $this->renderMetaTagsFromAPI())),
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
     * @param string $jsLibs
     * @param string $jsFiles
     * @param string $jsFooterFiles
     * @param string $cssLibs
     * @param string $cssFiles
     * @param string $jsInline
     * @param string $cssInline
     * @param string $jsFooterInline
     * @param string $jsFooterLibs
     * @param string $metaTags
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
     * currently: RequireJS, jQuery
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

        $this->loadJavaScriptLanguageStrings();
        if (TYPO3_MODE === 'BE') {
            $this->addAjaxUrlsToInlineSettings();
        }
        $inlineSettings = '';
        $languageLabels = $this->parseLanguageLabelsForJavaScript();
        if (!empty($languageLabels)) {
            $inlineSettings .= 'TYPO3.lang = ' . json_encode($languageLabels) . ';';
        }
        $inlineSettings .= $this->inlineSettings ? 'TYPO3.settings = ' . json_encode($this->inlineSettings) . ';' : '';

        if ($inlineSettings !== '') {
            // make sure the global TYPO3 is available
            $inlineSettings = 'var TYPO3 = TYPO3 || {};' . CRLF . $inlineSettings;
            $out .= $this->inlineJavascriptWrap[0] . $inlineSettings . $this->inlineJavascriptWrap[1];
        }

        return $out;
    }

    /**
     * Converts the language labels for usage in JavaScript
     *
     * @return array
     */
    protected function parseLanguageLabelsForJavaScript(): array
    {
        if (empty($this->inlineLanguageLabels)) {
            return [];
        }

        $labels = [];
        foreach ($this->inlineLanguageLabels as $key => $translationUnit) {
            if (is_array($translationUnit)) {
                $translationUnit = current($translationUnit);
                $labels[$key] = $translationUnit['target'] ?? $translationUnit['source'];
            } else {
                $labels[$key] = $translationUnit;
            }
        }

        return $labels;
    }

    /**
     * Load the language strings into JavaScript
     */
    protected function loadJavaScriptLanguageStrings()
    {
        if (!empty($this->inlineLanguageLabelFiles)) {
            foreach ($this->inlineLanguageLabelFiles as $languageLabelFile) {
                $this->includeLanguageFileForInline($languageLabelFile['fileRef'], $languageLabelFile['selectionPrefix'], $languageLabelFile['stripFromSelectionName']);
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
        // Add the ajax-based routes
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
                $jQueryFileName = $this->jQueryPath . 'jquery';
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
        $scriptTag = '<script src="' . htmlspecialchars($jQueryFileName) . '" type="text/javascript"></script>' . LF;
        // Set the noConflict mode to be globally available via "jQuery"
        if ($namespace !== self::JQUERY_NAMESPACE_NONE) {
            $scriptTag .= GeneralUtility::wrapJS('jQuery.noConflict();') . LF;
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
                $tag = $this->createCssTag($properties, $file);
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
                $tag = $this->createCssTag($properties, $file);
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
     * Create link (inline=0) or style (inline=1) tag
     *
     * @param array $properties
     * @param string $file
     * @return string
     */
    private function createCssTag(array $properties, string $file): string
    {
        if ($properties['inline'] && @is_file($file)) {
            $tag = $this->createInlineCssTagFromFile($file, $properties);
        } else {
            $href = $this->getStreamlinedFileName($file);
            $tag = '<link rel="' . htmlspecialchars($properties['rel'])
                . '" type="text/css" href="' . htmlspecialchars($href)
                . '" media="' . htmlspecialchars($properties['media']) . '"'
                . ($properties['title'] ? ' title="' . htmlspecialchars($properties['title']) . '"' : '')
                . $this->endingSlash . '>';
        }
        if ($properties['allWrap']) {
            $wrapArr = explode($properties['splitChar'] ?: '|', $properties['allWrap'], 2);
            $tag = $wrapArr[0] . $tag . $wrapArr[1];
        }
        $tag .= LF;

        return $tag;
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
                $async = $properties['async'] ? ' async="async"' : '';
                $defer = $properties['defer'] ? ' defer="defer"' : '';
                $integrity = $properties['integrity'] ? ' integrity="' . htmlspecialchars($properties['integrity']) . '"' : '';
                $crossorigin = $properties['crossorigin'] ? ' crossorigin="' . htmlspecialchars($properties['crossorigin']) . '"' : '';
                $tag = '<script src="' . htmlspecialchars($properties['file']) . '" type="' . htmlspecialchars($properties['type']) . '"' . $async . $defer . $integrity . $crossorigin . '></script>';
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
                $async = $properties['async'] ? ' async="async"' : '';
                $defer = $properties['defer'] ? ' defer="defer"' : '';
                $integrity = $properties['integrity'] ? ' integrity="' . htmlspecialchars($properties['integrity']) . '"' : '';
                $crossorigin = $properties['crossorigin'] ? ' crossorigin="' . htmlspecialchars($properties['crossorigin']) . '"' : '';
                $tag = '<script src="' . htmlspecialchars($file) . '" type="' . htmlspecialchars($properties['type']) . '"' . $async . $defer . $integrity . $crossorigin . '></script>';
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
     * @throws \RuntimeException
     */
    protected function includeLanguageFileForInline($fileRef, $selectionPrefix = '', $stripFromSelectionName = '')
    {
        if (!isset($this->lang) || !isset($this->charSet)) {
            throw new \RuntimeException('Language and character encoding are not set.', 1284906026);
        }
        $labelsFromFile = [];
        $allLabels = $this->readLLfile($fileRef);
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
     * @return array Returns the $LOCAL_LANG array found in the file. If no array found, returns empty array.
     */
    protected function readLLfile($fileRef)
    {
        /** @var LocalizationFactory $languageFactory */
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
            $tempLL = $languageFactory->getParsedData($fileRef, $language);

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
     * @return \TYPO3\CMS\Core\Localization\LanguageService
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
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'] ?? false;
        if (!$hooks) {
            return;
        }
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
        foreach ($hooks as $hook) {
            GeneralUtility::callUserFunction($hook, $params, $this);
        }
    }

    /**
     * PostTransform for possible manipulation of concatenated and compressed files
     */
    protected function executeRenderPostTransformHook()
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform'] ?? false;
        if (!$hooks) {
            return;
        }
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
        foreach ($hooks as $hook) {
            GeneralUtility::callUserFunction($hook, $params, $this);
        }
    }

    /**
     * Execute postRenderHook for possible manipulation
     *
     * @param string $jsLibs
     * @param string $jsFiles
     * @param string $jsFooterFiles
     * @param string $cssLibs
     * @param string $cssFiles
     * @param string $jsInline
     * @param string $cssInline
     * @param string $jsFooterInline
     * @param string $jsFooterLibs
     */
    protected function executePostRenderHook(&$jsLibs, &$jsFiles, &$jsFooterFiles, &$cssLibs, &$cssFiles, &$jsInline, &$cssInline, &$jsFooterInline, &$jsFooterLibs)
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'] ?? false;
        if (!$hooks) {
            return;
        }
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
        foreach ($hooks as $hook) {
            GeneralUtility::callUserFunction($hook, $params, $this);
        }
    }

    /**
     * Creates an CSS inline tag
     *
     * @param string $file the filename to process
     * @param array $properties
     * @return string
     */
    protected function createInlineCssTagFromFile(string $file, array $properties): string
    {
        $cssInline = file_get_contents($file);

        return '<style type="text/css"'
            . ' media="' . htmlspecialchars($properties['media']) . '"'
            . ($properties['title'] ? ' title="' . htmlspecialchars($properties['title']) . '"' : '')
            . '>' . LF
            . '/*<![CDATA[*/' . LF . '<!-- ' . LF
            . $cssInline
            . '-->' . LF . '/*]]>*/' . LF . '</style>' . LF;
    }
}
